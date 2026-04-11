<?php

/**
 * StegaVault - Secure File Viewer
 * File: api/view.php
 * 
 * Decrypts and serves images on-the-fly.
 * Essential for 'Encryption at Rest' architecture.
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/Encryption.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized');
}

$fileId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

if ($fileId <= 0) {
    http_response_code(400);
    die('Invalid request');
}

// Fetch file details
// Admin can view all, users only their own
// Authorization Logic
// 1. Admin can view all
// 2. Owner can view their own
// 3. Project Member can view files in their project

if ($userRole === 'admin') {
    $stmt = $db->prepare("SELECT file_path, mime_type, original_name FROM files WHERE id = ?");
    $stmt->bind_param('i', $fileId);
} else {
    // Check if user is owner OR member of the project
    $stmt = $db->prepare("
        SELECT f.file_path, f.mime_type, f.original_name
        FROM files f
        LEFT JOIN project_members pm ON f.project_id = pm.project_id
        WHERE f.id = ? AND (f.user_id = ? OR pm.user_id = ?)
        LIMIT 1
    ");
    $stmt->bind_param('iii', $fileId, $userId, $userId);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die('File not found or access denied');
}

$file = $result->fetch_assoc();
$filePath = __DIR__ . '/../' . $file['file_path'];
$uploadsDir = realpath(__DIR__ . '/../uploads');
$realFilePath = realpath($filePath);

// Security: Prevent Directory Traversal
if ($realFilePath === false || strpos($realFilePath, $uploadsDir) !== 0 || !file_exists($realFilePath)) {
    http_response_code(404);
    die('File missing or invalid path');
}

// Decrypt Content
$content = Encryption::decryptFileContent($filePath);

if ($content === false) {
    http_response_code(500);
    die('Decryption failed');
}

// Headers
header('Content-Type: ' . $file['mime_type']);
header('Content-Length: ' . strlen($content));
header('Cache-Control: private, max-age=86400'); // Cache for 1 day, but private only
header('Pragma: private');

// Force download if requested
if (!empty($_GET['download'])) {
    $dlName = $file['original_name'] ?? ('file_' . $fileId);
    header('Content-Disposition: attachment; filename="' . addslashes($dlName) . '"');
}

// Output raw image data
echo $content;
exit;
