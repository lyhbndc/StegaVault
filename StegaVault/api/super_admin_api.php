<?php
/**
 * StegaVault - Super Admin Actions API
 * File: api/super_admin_api.php
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized Access']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    if ($action === 'create_app') {
        $name = trim($input['name'] ?? '');

        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Application name is required']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO web_apps (name, status) VALUES (?, 'active')");
        if ($stmt) {
            $stmt->bind_param('s', $name);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'id' => $db->insert_id]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create application']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } elseif ($action === 'rename_app') {
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');

        if ($id <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID or Name']);
            exit;
        }

        $stmt = $db->prepare("UPDATE web_apps SET name = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $name, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to rename application']);
            }
        }
    } elseif ($action === 'delete_app') {
        $id = intval($input['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM web_apps WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete application']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
}
