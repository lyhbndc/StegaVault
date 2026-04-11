<?php

/**
 * StegaVault – Profile Settings API
 * File: api/settings.php
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// ── Parse body ───────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_POST['action'] ?? '');

// ── Update Name ──────────────────────────────────────────────
if ($action === 'update_name') {
    $name = trim($input['name'] ?? '');
    if (strlen($name) < 2) {
        echo json_encode(['success' => false, 'error' => 'Name must be at least 2 characters']);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->bind_param('si', $name, $userId);
    if ($stmt->execute()) {
        $_SESSION['name'] = $name;
        echo json_encode(['success' => true, 'message' => 'Name updated successfully', 'name' => $name]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update name']);
    }
    exit;
}

// ── Change Password ──────────────────────────────────────────
if ($action === 'change_password') {
    $current  = $input['current_password']  ?? '';
    $newPass  = $input['new_password']      ?? '';
    $confirm  = $input['confirm_password']  ?? '';

    if (empty($current) || empty($newPass) || empty($confirm)) {
        echo json_encode(['success' => false, 'error' => 'All password fields are required']);
        exit;
    }
    if ($newPass !== $confirm) {
        echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
        exit;
    }
    if (strlen($newPass) < 6) {
        echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters']);
        exit;
    }

    // Fetch current hash
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
        exit;
    }

    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
    $upd = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $upd->bind_param('si', $newHash, $userId);
    if ($upd->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to change password']);
    }
    exit;
}

// ── Update Theme Color ───────────────────────────────────────
if ($action === 'update_theme_color') {
    $color = trim($input['color'] ?? '');
    // Validate hex color: #rrggbb
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        echo json_encode(['success' => false, 'error' => 'Invalid color format']);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET theme_color = ? WHERE id = ?");
    $stmt->bind_param('si', $color, $userId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Theme color updated', 'color' => $color]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update theme color']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
