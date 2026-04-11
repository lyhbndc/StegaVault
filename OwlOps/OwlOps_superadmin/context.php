<?php

/**
 * StegaVault - Super Admin Context Switcher
 * File: super_admin/context.php
 */

session_start();
require_once '../StegaVault/includes/db.php';

// Check if user is logged in as Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['web_app_id'])) {
    $appId = (int)$_POST['web_app_id'];

    // Validate app exists
    $stmt = $db->prepare("SELECT id, name FROM web_apps WHERE id = ?");
    $stmt->bind_param('i', $appId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $app = $result->fetch_assoc();
        $_SESSION['manage_web_app_id'] = $app['id'];
        $_SESSION['manage_web_app_name'] = $app['name'];
        header('Location: app_dashboard.php');
        exit;
    }
}

// Fallback to global dashboard
header('Location: dashboard.php');
exit;
