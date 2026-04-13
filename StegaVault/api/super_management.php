<?php

/**
 * StegaVault - Super Admin Global Management API
 * File: api/super_management.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

// Authentication Check: Only super_admin can access this API
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied. Super Admin only.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$input = json_decode(file_get_contents('php://input'), true);

function sendResponse($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

// ============================================
// SUPER ADMIN MANAGEMENT
// ============================================

if ($action === 'list_super_admins') {
    $result = $db->query("SELECT id, email, name, created_at FROM super_admins ORDER BY created_at DESC");
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    sendResponse(true, ['admins' => $admins]);
}

if ($action === 'create_super_admin' && $method === 'POST') {
    $email = trim($input['email'] ?? '');
    $name = trim($input['name'] ?? '');
    $password = $input['password'] ?? '';

    if (!$email || !$name || !$password) {
        sendResponse(false, null, 'Email, Name, and Password are required', 400);
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $db->prepare("INSERT INTO super_admins (email, name, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $email, $name, $passwordHash);

    if ($stmt->execute()) {
        sendResponse(true, ['message' => 'Super Admin created successfully']);
    } else {
        sendResponse(false, null, 'Failed to create Super Admin. Email might already exist.', 500);
    }
}

if ($action === 'delete_super_admin' && $method === 'DELETE') {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) sendResponse(false, null, 'Invalid ID', 400);

    // Prevent deleting itself
    if ($id === $_SESSION['user_id']) {
        sendResponse(false, null, 'You cannot delete your own account', 400);
    }

    $stmt = $db->prepare("DELETE FROM super_admins WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        sendResponse(true, ['message' => 'Super Admin deleted successfully']);
    } else {
        sendResponse(false, null, 'Failed to delete Super Admin', 500);
    }
}

// ============================================
// APP ADMIN MANAGEMENT (In 'users' table)
// ============================================

if ($action === 'list_app_admins') {
    // List all users with role 'admin'
    $result = $db->query("SELECT id, email, name, created_at, status, web_app_id FROM users WHERE role = 'admin' ORDER BY created_at DESC");
    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    sendResponse(true, ['admins' => $admins]);
}

if ($action === 'create_app_admin' && $method === 'POST') {
    $email = trim($input['email'] ?? '');
    $name = trim($input['name'] ?? '');
    $password = $input['password'] ?? '';
    // web_app_id is optional for global admins but usually they belong to an app
    $web_app_id = isset($input['web_app_id']) ? intval($input['web_app_id']) : null; 

    if (!$email || !$name || !$password) {
        sendResponse(false, null, 'Email, Name, and Password are required', 400);
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $status = 'active'; // Default to active for manually created admins by super admin
    $role = 'admin';

    $stmt = $db->prepare("INSERT INTO users (email, name, password_hash, role, status, web_app_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssi', $email, $name, $passwordHash, $role, $status, $web_app_id);

    if ($stmt->execute()) {
        sendResponse(true, ['message' => 'App Admin created successfully']);
    } else {
        sendResponse(false, null, 'Failed to create App Admin. Email might already exist.', 500);
    }
}

if ($action === 'delete_app_admin' && $method === 'DELETE') {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) sendResponse(false, null, 'Invalid ID', 400);

    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        sendResponse(true, ['message' => 'App Admin deleted successfully']);
    } else {
        sendResponse(false, null, 'Failed to delete App Admin', 500);
    }
}

sendResponse(false, null, 'Invalid action or method', 404);
