<?php

/**
 * Forgotten Password Endpoint
 * Takes an email address, generates a reset token, and sends an email.
 */

session_start();
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/EmailService.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email address is required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
    exit;
}

try {
    // Look up the user by email
    $db = new Database();

    $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? AND status = 'active'");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $db->error);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Always return success to prevent email enumeration attacks
    if (!$user) {
        echo json_encode([
            'success' => true,
            'message' => 'If an active account exists with that email, a password reset link has been sent.'
        ]);
        exit;
    }

    // Generate a secure token
    $resetToken = bin2hex(random_bytes(32));
    // Set expiration to 1 hour from now
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Save token to database
    $updateStmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception("Database prepare failed: " . $db->error);
    }

    $updateStmt->bind_param('ssi', $resetToken, $expires, $user['id']);

    if (!$updateStmt->execute()) {
        throw new Exception("Failed to save reset token.");
    }

    // Send the email
    $resetLink = 'http://localhost/StegaVault/reset-password.php?token=' . $resetToken; // TODO: Update domain

    $emailService = new EmailService();
    $emailSent = $emailService->sendPasswordResetEmail($email, $user['name'], $resetLink);

    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'If an active account exists with that email, a password reset link has been sent.'
        ]);
    }
    else {
        // Technically it failed to send, but we still tell the user it was sent to prevent enumeration
        // We log the error internally
        error_log("Failed to send password reset email to: " . $email);
        echo json_encode([
            'success' => true,
            'message' => 'If an active account exists with that email, a password reset link has been sent. Note: Email service may be down.'
        ]);
    }
}
catch (Exception $e) {
    error_log("Forgot Password Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.']);
}