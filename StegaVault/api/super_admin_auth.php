<?php

/**
 * StegaVault - Super Admin Authentication API
 * File: api/super_admin_auth.php
 */

// Start session FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Include database
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/SuperAdminLogger.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Response helper
function sendResponse($success, $data = null, $error = null, $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ], JSON_PRETTY_PRINT);
    exit;
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

        // Validate
        if (empty($email) || empty($password)) {
            sendResponse(false, null, 'Email and password are required', 400);
        }

        // Find user in super_admins table
        $stmt = $db->prepare("SELECT id, email, password_hash, name FROM super_admins WHERE email = ?");
        if (!$stmt) {
            sendResponse(false, null, 'Database error: ' . $db->error, 500);
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sendResponse(false, null, 'Invalid email or password', 401);
        }

        $user = $result->fetch_assoc();

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Log failed attempt (set session fields temporarily for logger)
            $_SESSION['user_id'] = null;
            $_SESSION['name']    = 'Unknown';
            $_SESSION['email']   = $email;
            SuperAdminLogger::log('login_failed', 'auth', ['email' => $email]);
            $_SESSION = [];
            sendResponse(false, null, "Invalid email or password.", 401);
        }

        // Check if MFA is enabled
        $mfaStmt = $db->prepare("SELECT is_mfa_enabled FROM super_admins WHERE id = ?");
        $mfaStmt->bind_param('i', $user['id']);
        $mfaStmt->execute();
        $mfaRow = $mfaStmt->get_result()->fetch_assoc();

        if ($mfaRow && $mfaRow['is_mfa_enabled']) {
            $_SESSION['pending_mfa_user_id'] = $user['id'];
            $_SESSION['pending_mfa_portal']  = 'super_admin';
            // Log MFA challenge using pending session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            SuperAdminLogger::log('login_mfa_challenged', 'auth');
            unset($_SESSION['user_id'], $_SESSION['name'], $_SESSION['email']);
            sendResponse(true, [
                'require_mfa' => true,
                'message'     => 'MFA verification required'
            ], null, 200);
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = 'super_admin';
        SuperAdminLogger::log('login_success', 'auth');

        // Success response
        sendResponse(true, [
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => 'super_admin'
            ],
            'message' => 'Login successful',
            'session_id' => session_id()
        ], null, 200);
    } catch (Exception $e) {
        sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
    }
}

// ============================================
// LOGOUT
// ============================================
if ($method === 'POST' && $action === 'logout') {
    SuperAdminLogger::log('logout', 'auth');
    session_destroy();
    sendResponse(true, ['message' => 'Logged out successfully']);
}

// ============================================
// GET CURRENT USER
// ============================================
if ($method === 'GET' && $action === 'me') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
        sendResponse(false, null, 'Not authenticated', 401);
    }

    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT id, email, name FROM super_admins WHERE id = ?");
    if($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sendResponse(false, null, 'User not found', 404);
        }

        $userData = $result->fetch_assoc();
        $userData['role'] = 'super_admin';
        
        sendResponse(true, ['user' => $userData]);
    }
}

// Invalid endpoint
sendResponse(false, null, 'Invalid endpoint or method', 404);
