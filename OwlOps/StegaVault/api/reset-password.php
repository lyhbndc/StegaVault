<?php

/**
 * Reset Password Endpoint
 * Takes a token, verifies it, and updates the user's password.
 */

session_start();
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/ActivityLogger.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($password) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

try {
    // Look up the user by token and ensure it hasn't expired
    $now = date('Y-m-d H:i:s');
    $db = new Database();

    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > ? AND status = 'active'");

    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $db->error);
    }

    $stmt->bind_param('ss', $token, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired password reset link. Please request a new one.'
        ]);
        exit;
    }

    // Hash the new password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Update password and clear the token
    $updateStmt = $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception("Database prepare failed: " . $db->error);
    }

    $updateStmt->bind_param('si', $passwordHash, $user['id']);

    if ($updateStmt->execute()) {
        // Log the activity
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        logActivityEvent($db, (int)$user['id'], 'password_reset', 'User reset their password using an email link', $ip, null, false);

        echo json_encode([
            'success' => true,
            'message' => 'Your password has been successfully reset. You can now log in.'
        ]);
    }
    else {
        throw new Exception("Failed to update password: " . $updateStmt->error);
    }
}
catch (Exception $e) {
    error_log("Reset Password Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.']);
}