<?php

/**
 * StegaVault - Authentication API
 * File: api/auth.php
 */

// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include database
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Response helper
function sendResponse($success, $data = null, $error = null, $code = 200)
{
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ], JSON_PRETTY_PRINT);
    exit;
}

function logAuthEvent($db, $userId, $action, $description, $role = null)
{
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    logActivityEvent($db, (int)$userId, (string)$action, (string)$description, $ipAddress, $role, false);
}

function getConsecutiveFailedLogins($db, $userId, $role = null)
{
    $resolvedRole = $role ?: getUserRoleForActivityLog($db, (int)$userId);
    $activityTable = getRoleActivityTable($resolvedRole);

    $query = "
        SELECT COUNT(*) AS fail_count
        FROM {$activityTable}
        WHERE user_id = ?
          AND action = 'login_failed'
          AND created_at > COALESCE(
              (SELECT MAX(created_at) FROM {$activityTable} WHERE user_id = ? AND action = 'login_success'),
              '1970-01-01 00:00:00'
          )
    ";

    $stmt = $db->prepare($query);
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return (int)($result['fail_count'] ?? 0);
}

// ============================================
// LOGIN
// ============================================
if ($method === 'POST' && $action === 'login') {
    try {
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            sendResponse(false, null, 'Invalid JSON input', 400);
        }

        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';
        $portal = isset($input['portal']) ? strtolower(trim((string)$input['portal'])) : '';

        // Validate
        if (empty($email) || empty($password)) {
            sendResponse(false, null, 'Email and password are required', 400);
        }

        // Find user
        $stmt = $db->prepare("SELECT id, email, password_hash, name, role, status, expiration_date FROM users WHERE email = ?");
        if (!$stmt) {
            sendResponse(false, null, 'Database error: ' . $db->getConnection()->error, 500);
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sendResponse(false, null, 'Invalid email or password', 401);
        }

        $user = $result->fetch_assoc();

        // Enforce portal-specific login role restrictions BEFORE password checks,
        // so cross-portal attempts do not affect account lock counters.
        if ($portal !== '') {
            $allowedPortalRoles = [
                'admin' => ['admin', 'super_admin'],
                'employee' => ['employee'],
                'collaborator' => ['collaborator']
            ];

            if (isset($allowedPortalRoles[$portal]) && !in_array($user['role'], $allowedPortalRoles[$portal], true)) {
                sendResponse(false, null, 'This account is not allowed to sign in from this portal.', 403);
            }
        }

        // Check status
        if (isset($user['status'])) {
            if ($user['status'] === 'pending_activation') {
                sendResponse(false, null, 'Account is pending activation. Please check your email.', 403);
            }
            if ($user['status'] === 'disabled') {
                sendResponse(false, null, 'Account is locked. Please contact an administrator.', 403);
            }
            if ($user['status'] === 'expired') {
                sendResponse(false, null, 'Account is disabled or expired.', 403);
            }
        }

        // Verify password and lock account after 3 failed attempts
        if (!password_verify($password, $user['password_hash'])) {
            logAuthEvent($db, (int)$user['id'], 'login_failed', 'Failed login attempt', $user['role'] ?? null);
            $failedAttempts = getConsecutiveFailedLogins($db, (int)$user['id'], $user['role'] ?? null);

            if ($failedAttempts >= 3) {
                $disableStmt = $db->prepare("UPDATE users SET status = 'disabled' WHERE id = ? AND status <> 'disabled'");
                if ($disableStmt) {
                    $disableStmt->bind_param('i', $user['id']);
                    $disableStmt->execute();
                }

                logAuthEvent($db, (int)$user['id'], 'account_locked', 'Account locked after 3 failed login attempts', $user['role'] ?? null);
                sendResponse(false, null, 'Account locked after 3 incorrect password attempts. Please contact an administrator.', 403);
            }

            $remainingAttempts = 3 - $failedAttempts;
            $attemptText = $remainingAttempts === 1 ? 'attempt' : 'attempts';
            sendResponse(false, null, "Invalid email or password. {$remainingAttempts} {$attemptText} remaining before account lock.", 401);
        }

        // Password is correct; mark successful login to reset failed-attempt streak
        logAuthEvent($db, (int)$user['id'], 'login_success', 'Successful login', $user['role'] ?? null);

        // Check expiration date
        if (!empty($user['expiration_date'])) {
            $expDate = strtotime($user['expiration_date']);
            if ($expDate !== false && $expDate < time()) {
                sendResponse(false, null, 'Account has expired.', 403);
            }
        }

        // Check if MFA is enabled
        $stmt = $db->prepare("SELECT is_mfa_enabled, mfa_secret FROM users WHERE id = ?");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $mfaResult = $stmt->get_result()->fetch_assoc();

        if ($mfaResult && $mfaResult['is_mfa_enabled']) {
            $_SESSION['pending_mfa_user_id'] = $user['id'];
            $_SESSION['pending_mfa_portal'] = $portal; // Store the portal for redirection on cancel
            
            // Store original redirect details if needed, or simply return require_mfa
            sendResponse(true, [
                'require_mfa' => true,
                'message' => 'MFA verification required'
            ], null, 200);
            exit;
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // Success response
        sendResponse(true, [
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role']
            ],
            'message' => 'Login successful',
            'session_id' => session_id()
        ], null, 200);
    } catch (Exception $e) {
        sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
    }
}

// ============================================
// REGISTER
// ============================================
if ($method === 'POST' && $action === 'register') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            sendResponse(false, null, 'Invalid JSON input', 400);
        }

        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';
        $name = isset($input['name']) ? trim($input['name']) : '';

        // Validate
        if (empty($email) || empty($password) || empty($name)) {
            sendResponse(false, null, 'All fields are required', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, null, 'Invalid email format', 400);
        }

        // ── Password Policy ──────────────────────────────────────
        $pwLen = strlen($password);
        if ($pwLen < 8 || $pwLen > 25) {
            sendResponse(false, null, 'Password must be between 8 and 25 characters.', 400);
        }
        if (!preg_match('/[A-Z]/', $password)) {
            sendResponse(false, null, 'Password must contain at least one uppercase letter.', 400);
        }
        if (!preg_match('/[a-z]/', $password)) {
            sendResponse(false, null, 'Password must contain at least one lowercase letter.', 400);
        }
        if (!preg_match('/[0-9]/', $password)) {
            sendResponse(false, null, 'Password must contain at least one number.', 400);
        }
        if (!preg_match('/[\W_]/', $password)) {
            sendResponse(false, null, 'Password must contain at least one special character (e.g. !@#$%).', 400);
        }

        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            // Security: Use generic error to prevent user enumeration
            sendResponse(false, null, 'Registration failed. Please verify your details.', 400);
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert user
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param('sss', $email, $passwordHash, $name);

        if (!$stmt->execute()) {
            sendResponse(false, null, 'Registration failed: ' . $stmt->error, 500);
        }

        $userId = $db->lastInsertId();

        // Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['name'] = $name;
        $_SESSION['role'] = 'admin';

        sendResponse(true, [
            'user' => [
                'id' => $userId,
                'email' => $email,
                'name' => $name,
                'role' => 'admin'
            ],
            'message' => 'Registration successful'
        ], null, 201);
    } catch (Exception $e) {
        sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
    }
}

// ============================================
// LOGOUT
// ============================================
if ($action === 'logout') {
    $portal = $_SESSION['pending_mfa_portal'] ?? ($_GET['from'] ?? 'admin');
    
    // Explicitly destroy the session
    session_destroy();
    
    // Ensure no JSON content-type header is set if we are redirecting
    if ($method === 'GET') {
        $location = '../admin/login.php';
        if ($portal === 'employee') {
            $location = '../employee/login.php';
        } elseif ($portal === 'collaborator') {
            $location = '../collaborator/login.php';
        }
        
        header("Location: $location");
        exit;
    } else {
        // Only return JSON if it's a POST request (typical logout button)
        sendResponse(true, ['message' => 'Logged out successfully']);
    }
}

// ============================================
// GET CURRENT USER
// ============================================
if ($method === 'GET' && $action === 'me') {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, null, 'Not authenticated', 401);
    }

    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT id, email, name, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        sendResponse(false, null, 'User not found', 404);
    }

    sendResponse(true, ['user' => $result->fetch_assoc()]);
}

// Invalid endpoint
sendResponse(false, null, 'Invalid endpoint or method', 404);
