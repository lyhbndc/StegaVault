<?php

/**
 * StegaVault - Upload API (Video Support + Project Link)
 * File: api/upload.php
 */

// Start session
session_start();

// Set JSON header
header('Content-Type: application/json');

// Include database
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/Encryption.php';
require_once __DIR__ . '/../includes/PdfSecurity.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

// ============================================
// GET FILES (List user's files)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $db->prepare("
        SELECT id, filename, original_name, file_path, file_size, mime_type, 
               watermarked, upload_date, download_count 
        FROM files 
        WHERE user_id = ? 
        ORDER BY upload_date DESC
    ");

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $files = [];
    while ($row = $result->fetch_assoc()) {
        $files[] = [
            'id' => $row['id'],
            'filename' => $row['filename'],
            'original_name' => $row['original_name'],
            'size' => $row['file_size'],
            'type' => $row['mime_type'],
            'watermarked' => (bool) $row['watermarked'],
            'upload_date' => $row['upload_date'],
            'download_count' => $row['download_count'],
            'url' => '../api/view.php?id=' . $row['id']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'files' => $files,
            'total' => count($files)
        ]
    ]);
    exit;
}

// ============================================
// UPLOAD FILE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if file exists
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['file'];
    $projectId = isset($_POST['project_id']) && is_numeric($_POST['project_id']) ? (int) $_POST['project_id'] : null;
    $folderId = isset($_POST['folder_id']) && is_numeric($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;

    // If folder_id provided, validate it belongs to the project
    if ($folderId && $projectId) {
        $fcheck = $db->prepare("SELECT id FROM project_folders WHERE id = ? AND project_id = ?");
        $fcheck->bind_param('ii', $folderId, $projectId);
        $fcheck->execute();
        if ($fcheck->get_result()->num_rows === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Folder does not belong to this project']);
            exit;
        }
    } elseif ($folderId && !$projectId) {
        // folder requires a project
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'folder_id requires project_id']);
        exit;
    }

    // Validate Project Access if project_id is provided
    if ($projectId) {
        // Allow if user is admin OR member of project
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if (!$isAdmin) {
            $stmt = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmt->bind_param('ii', $projectId, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Not a member of this project']);
                exit;
            }
        }
    }

    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Upload failed with error code: ' . $file['error']]);
        exit;
    }

    // Validate size (50MB max for videos)
    $maxSize = 50 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File too large (max 50MB)']);
        exit;
    }

    // Validate file type
    $allowedMimes = [
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',
        'video/mpeg',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: PNG, MP4, PDF, DOC, DOCX, XLS, XLSX. Got: ' . $mimeType]);
        exit;
    }

    // Block duplicate original filenames within the same scope only.
    // - Project uploads: unique per project
    // - Non-project uploads: unique per user
    $originalName = trim($file['name']);
    if ($projectId !== null) {
        $dup = $db->prepare("SELECT id FROM files WHERE LOWER(TRIM(original_name)) = LOWER(TRIM(?)) AND project_id = ? LIMIT 1");
        $dup->bind_param('si', $originalName, $projectId);
    } else {
        $dup = $db->prepare("SELECT id FROM files WHERE LOWER(TRIM(original_name)) = LOWER(TRIM(?)) AND project_id IS NULL AND user_id = ? LIMIT 1");
        $dup->bind_param('si', $originalName, $userId);
    }
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        http_response_code(409);
        $scopeMsg = ($projectId !== null)
            ? 'in this project'
            : 'in your personal uploads';
        echo json_encode(['success' => false, 'error' => 'A file with this name already exists ' . $scopeMsg . '. Please rename your file before uploading.']);
        exit;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('enc_', true) . '.' . $extension;

    // Upload directory
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filepath = $uploadDir . $filename;

    $sourcePathForEncryption = $file['tmp_name'];
    $tempProtectedPdfPath = null;

    // Optional: apply PDF open-password before storage encryption.
    if ($mimeType === 'application/pdf') {
        $pdfPassword = isset($_POST['pdf_password']) ? (string) $_POST['pdf_password'] : '';

        if ($pdfPassword !== '') {
            if (strlen($pdfPassword) < 4 || strlen($pdfPassword) > 64) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'PDF password must be between 4 and 64 characters.']);
                exit;
            }

            $pdfProtectError = null;
            $protectedPdfPath = PdfSecurity::protectPdfWithPassword($file['tmp_name'], $pdfPassword, $pdfProtectError);
            if ($protectedPdfPath === false) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $pdfProtectError ?: 'Failed to apply PDF password.']);
                exit;
            }

            $sourcePathForEncryption = $protectedPdfPath;
            $tempProtectedPdfPath = $protectedPdfPath;
        }
    }

    // ENCRYPT AND SAVE
    if (!Encryption::encryptFile($sourcePathForEncryption, $filepath)) {
        if ($tempProtectedPdfPath && file_exists($tempProtectedPdfPath)) {
            @unlink($tempProtectedPdfPath);
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to encrypt and save file']);
        exit;
    }

    if ($tempProtectedPdfPath && file_exists($tempProtectedPdfPath)) {
        @unlink($tempProtectedPdfPath);
    }

    // Save to database
    $originalName = trim($file['name']);
    $fileSize = ($tempProtectedPdfPath && file_exists($tempProtectedPdfPath)) ? filesize($tempProtectedPdfPath) : $file['size'];
    $relativePath = 'uploads/' . $filename;

    // Step 1: insert core fields (no nullable FK columns to avoid MySQLi null-int issues)
    $requireWatermark = (isset($_POST['require_watermark']) && $_POST['require_watermark'] == '0') ? false : true;

    $stmt = $db->prepare("
        INSERT INTO files (user_id, filename, original_name, file_path, file_size, mime_type, watermarked, upload_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    // Use 'b' for boolean in my shim might works better, or just rely on PHP bool type
    $stmt->bind_param('isssisb', $userId, $filename, $originalName, $relativePath, $fileSize, $mimeType, $requireWatermark);

    if (!$stmt->execute()) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
        exit;
    }

    $fileId = $stmt->insert_id;

    // Step 2: set project_id and/or folder_id if provided
    if ($projectId !== null || $folderId !== null) {
        $upd = $db->prepare("UPDATE files SET project_id = ?, folder_id = ? WHERE id = ?");
        $upd->bind_param('iii', $projectId, $folderId, $fileId);
        if (!$upd->execute()) {
            // Roll back the saved file + DB row to avoid orphaned uploads in wrong scope.
            $del = $db->prepare("DELETE FROM files WHERE id = ?");
            $del->bind_param('i', $fileId);
            $del->execute();
            unlink($filepath);

            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to assign upload to project/folder: ' . $upd->error]);
            exit;
        }
    }

    // Success!
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => [
            'file' => [
                'id' => $fileId,
                'filename' => $filename,
                'original_name' => $originalName,
                'size' => $fileSize,
                'type' => $mimeType,
                'project_id' => $projectId,
                'folder_id' => $folderId,
                'url' => '../api/view.php?id=' . $fileId
            ],
            'message' => 'File encrypted and uploaded successfully'
        ]
    ]);
    exit;
}

// Invalid method
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
