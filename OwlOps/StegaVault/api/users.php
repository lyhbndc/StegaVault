<?php

/**
 * StegaVault - User Management API
 * File: api/users.php
 * 
 * Allows admins to create, view, edit, and delete users
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';
require_once __DIR__ . '/../includes/EmailService.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'employee';

// Only admins can manage users
if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied. Admin only.'
    ]);
    exit;
}

// Response helper
function sendResponse($success, $data = null, $error = null, $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

// Email helper function removed in favor of EmailService class

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ============================================
// GET ALL USERS
// ============================================
if ($method === 'GET' && $action === 'list') {
    try {
        $result = $db->query("
            SELECT id, email, username, name, role, status, expiration_date, created_at,
                   (SELECT COUNT(*) FROM files WHERE user_id = users.id) as file_count,
                   (SELECT COUNT(*) FROM watermark_mappings WHERE user_id = users.id) as download_count
            FROM users 
            ORDER BY created_at DESC
        ");

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id' => $row['id'],
                'email' => $row['email'],
                'username' => $row['username'] ?? '',
                'name' => $row['name'],
                'role' => $row['role'],
                'status' => $row['status'] ?? 'active',
                'expiration_date' => $row['expiration_date'],
                'created_at' => $row['created_at'],
                'file_count' => (int)$row['file_count'],
                'download_count' => (int)$row['download_count']
            ];
        }

        sendResponse(true, ['users' => $users, 'total' => count($users)]);
    }
    catch (Exception $e) {
        sendResponse(false, null, 'Error fetching users: ' . $e->getMessage(), 500);
    }
}

// ============================================
// CREATE NEW USER (Admin creates employee)
// ============================================
if ($method === 'POST' && $action === 'create') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $email = isset($input['email']) ? trim($input['email']) : '';
        $name = isset($input['name']) ? trim($input['name']) : '';
        $username = isset($input['username']) ? trim($input['username']) : '';
        $password = isset($input['password']) ? $input['password'] : '';
        $role = isset($input['role']) ? $input['role'] : 'employee';
        $expiration_date = isset($input['expiration_date']) && !empty($input['expiration_date']) ? $input['expiration_date'] : null;

        // Validate input
        if (empty($email) || empty($name) || empty($password) || empty($username)) {
            sendResponse(false, null, 'Email, username, name, and password are required', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, null, 'Invalid email format', 400);
        }

        if (strlen($password) < 6) {
            sendResponse(false, null, 'Password must be at least 6 characters', 400);
        }

        // Validate role
        $allowedRoles = ['admin', 'manager', 'employee', 'collaborator', 'client'];
        if (!in_array($role, $allowedRoles)) {
            $role = 'employee';
        }

        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            sendResponse(false, null, 'Email is already in use', 400);
        }

        // Check if username already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            sendResponse(false, null, 'Username already taken', 400);
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Data for new user
        $activation_token = bin2hex(random_bytes(32));
        $status = 'pending_activation';

        // Insert user
        $stmt = $db->prepare("INSERT INTO users (email, username, password_hash, name, role, status, expiration_date, activation_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssss', $email, $username, $passwordHash, $name, $role, $status, $expiration_date, $activation_token);

        if ($stmt->execute()) {
            $newUserId = $db->lastInsertId();

            // Send activation email
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
            $baseDir = dirname(dirname($_SERVER['SCRIPT_NAME']));
            if ($baseDir === '/' || $baseDir === '\\')
                $baseDir = '';
            $activationLink = $protocol . $_SERVER['HTTP_HOST'] . $baseDir . "/activate.php?token=" . $activation_token;

            $emailer = new EmailService();
            $emailer->sendActivationEmail($email, $name, $username, $role, $activationLink, $expiration_date);

            // Log activity
            $adminId = $_SESSION['user_id'];
            $description = "Created new user: {$username} ({$email}) with role: {$role}";
            logActivityEvent($db, (int)$adminId, 'user_created', $description, $_SERVER['REMOTE_ADDR'] ?? null, $_SESSION['role'] ?? 'admin', false);

            sendResponse(true, [
                'user' => [
                    'id' => $newUserId,
                    'email' => $email,
                    'username' => $username,
                    'name' => $name,
                    'role' => $role,
                    'status' => $status
                ],
                'message' => 'User created successfully. Activation email sent.'
            ], null, 201);
        }
        else {
            sendResponse(false, null, 'Failed to create user: ' . $stmt->error, 500);
        }
    }
    catch (Exception $e) {
        sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
    }
}

// ============================================
// UPDATE USER
// ============================================
if ($method === 'PUT' && $action === 'update') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        $targetUserId = isset($input['id']) ? (int)$input['id'] : 0;
        $email = isset($input['email']) ? trim($input['email']) : '';
        $name = isset($input['name']) ? trim($input['name']) : '';
        $role = isset($input['role']) ? $input['role'] : '';
        $newPassword = isset($input['password']) ? $input['password'] : '';

        if ($targetUserId <= 0) {
            sendResponse(false, null, 'Invalid user ID', 400);
        }

        // Prevent admin from demoting themselves
        if ($targetUserId == $_SESSION['user_id'] && $role !== 'admin') {
            sendResponse(false, null, 'You cannot change your own role', 400);
        }

        // Build update query
        $updates = [];
        $params = [];
        $types = '';

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendResponse(false, null, 'Invalid email format', 400);
            }

            $checkEmailStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkEmailStmt->bind_param('si', $email, $targetUserId);
            $checkEmailStmt->execute();

            if ($checkEmailStmt->get_result()->num_rows > 0) {
                sendResponse(false, null, 'Email is already in use', 400);
            }

            $updates[] = "email = ?";
            $params[] = $email;
            $types .= 's';
        }

        if (!empty($name)) {
            $updates[] = "name = ?";
            $params[] = $name;
            $types .= 's';
        }

        if (!empty($input['username'])) {
            // Check uniqueness if changed (omitted for brevity, ideally check if != current)
            $updates[] = "username = ?";
            $params[] = $input['username'];
            $types .= 's';
        }

        if (isset($input['expiration_date'])) {
            $expiration = !empty($input['expiration_date']) ? $input['expiration_date'] : null;
            $updates[] = "expiration_date = ?";
            $params[] = $expiration;
            $types .= 's';
        }

        if (isset($input['status'])) {
            $updates[] = "status = ?";
            $params[] = $input['status'];
            $types .= 's';
        }

        if (!empty($role)) {
            $allowedRoles = ['admin', 'manager', 'employee', 'collaborator', 'client'];
            if (in_array($role, $allowedRoles)) {
                $updates[] = "role = ?";
                $params[] = $role;
                $types .= 's';
            }
        }

        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                sendResponse(false, null, 'Password must be at least 6 characters', 400);
            }
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $updates[] = "password_hash = ?";
            $params[] = $passwordHash;
            $types .= 's';
        }

        if (empty($updates)) {
            sendResponse(false, null, 'No fields to update', 400);
        }

        $params[] = $targetUserId;
        $types .= 'i';

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            // Log activity
            $adminId = $_SESSION['user_id'];
            $description = "Updated user ID: {$targetUserId}";
            logActivityEvent($db, (int)$adminId, 'user_updated', $description, $_SERVER['REMOTE_ADDR'] ?? null, $_SESSION['role'] ?? 'admin', false);

            sendResponse(true, ['message' => 'User updated successfully']);
        }
        else {
            sendResponse(false, null, 'Failed to update user', 500);
        }
    }
    catch (Exception $e) {
        sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
    }
}

// ============================================
// DELETE USER
// ============================================
if ($method === 'DELETE' && $action === 'delete') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $targetUserId = isset($input['id']) ? (int)$input['id'] : 0;

        if ($targetUserId <= 0) {
            sendResponse(false, null, 'Invalid user ID', 400);
        }

        // Prevent admin from deleting themselves
        if ($targetUserId == $_SESSION['user_id']) {
            sendResponse(false, null, 'You cannot delete your own account', 400);
        }

        // Get user info before deleting
        $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->bind_param('i', $targetUserId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sendResponse(false, null, 'User not found', 404);
        }

        $userInfo = $result->fetch_assoc();

        // Delete user (CASCADE will delete related files and watermarks)
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $targetUserId);

        if ($stmt->execute()) {
            // Log activity
            $adminId = $_SESSION['user_id'];
            $description = "Deleted user: {$userInfo['name']} ({$userInfo['email']})";
            logActivityEvent($db, (int)$adminId, 'user_deleted', $description, $_SERVER['REMOTE_ADDR'] ?? null, $_SESSION['role'] ?? 'admin', false);

            sendResponse(true, ['message' => 'User deleted successfully']);
        }
        else {
            sendResponse(false, null, 'Failed to delete user', 500);
        }
    }
    catch (Exception $e) {
        sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
    }
}

// ============================================
// GET USER STATISTICS
// ============================================
if ($method === 'GET' && $action === 'stats') {
    try {
        $stats = [];

        // Total users by role
        $result = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        while ($row = $result->fetch_assoc()) {
            $stats['by_role'][$row['role']] = (int)$row['count'];
        }

        // Total users
        $result = $db->query("SELECT COUNT(*) as total FROM users");
        $stats['total_users'] = (int)$result->fetch_assoc()['total'];

        // Total files
        $result = $db->query("SELECT COUNT(*) as total FROM files");
        $stats['total_files'] = (int)$result->fetch_assoc()['total'];

        // Total downloads
        $result = $db->query("SELECT SUM(download_count) as total FROM watermark_mappings");
        $stats['total_downloads'] = (int)($result->fetch_assoc()['total'] ?? 0);

        // Recent registrations (last 7 days)
        $result = $db->query("SELECT COUNT(*) as total FROM users WHERE created_at >= NOW() - INTERVAL '7 days'");
        $stats['recent_registrations'] = (int)$result->fetch_assoc()['total'];

        sendResponse(true, $stats);
    }
    catch (Exception $e) {
        sendResponse(false, null, 'Error fetching stats: ' . $e->getMessage(), 500);
    }
}

// Invalid endpoint
sendResponse(false, null, 'Invalid endpoint or method', 404);