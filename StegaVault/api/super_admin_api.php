<?php
/**
 * StegaVault - Super Admin Actions API
 * File: api/super_admin_api.php
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/EmailService.php';

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
    } elseif ($action === 'create_admin') {
        $name     = trim($input['name'] ?? '');
        $email    = trim($input['email'] ?? '');
        $mode     = $input['mode'] ?? 'auto';
        $password = $input['password'] ?? '';

        if (empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'error' => 'Name and email are required']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email format']);
            exit;
        }

        if ($mode === 'auto') {
            $chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@$!%*?&';
            $password = '';
            while (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{12,}$/', $password)) {
                $password = '';
                for ($i = 0; $i < 14; $i++) {
                    $password .= $chars[random_int(0, strlen($chars) - 1)];
                }
            }
        } else {
            if (strlen($password) < 12) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 12 characters']);
                exit;
            }
        }

        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Email is already in use']);
            exit;
        }

        $passwordHash      = password_hash($password, PASSWORD_BCRYPT);
        $activation_token  = bin2hex(random_bytes(32));
        $status            = 'pending_activation';
        $role              = 'admin';
        $web_app_id        = $_SESSION['manage_web_app_id'] ?? null;
        $username          = strstr($email, '@', true);

        $stmt = $db->prepare("INSERT INTO users (email, username, name, password_hash, role, status, activation_token, web_app_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssssi', $email, $username, $name, $passwordHash, $role, $status, $activation_token, $web_app_id);

        if ($stmt->execute()) {
            $protocol  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
            $baseDir   = dirname(dirname($_SERVER['SCRIPT_NAME']));
            if ($baseDir === '/' || $baseDir === '\\') $baseDir = '';
            $activationLink = $protocol . $_SERVER['HTTP_HOST'] . $baseDir . '/activate.php?token=' . $activation_token;

            $emailer = new EmailService();
            $emailer->sendActivationEmail($email, $name, $username, $password, $role, $activationLink, null);

            echo json_encode(['success' => true, 'message' => 'Admin created successfully. Activation email sent.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create admin. Email might already exist.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
}
