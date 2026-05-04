<?php

/**
 * StegaVault - Upload API (Fixed Storage + Safe Upload + Encryption Pipeline)
 */

session_start();
header('Content-Type: application/json');

ini_set('memory_limit', '512M');
ini_set('max_execution_time', '300');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/Encryption.php';
require_once __DIR__ . '/../includes/PdfSecurity.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

/* =========================================================
   GET FILES
========================================================= */
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

/* =========================================================
   UPLOAD FILE
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['file'])) {
        // When post_max_size is exceeded PHP empties $_FILES entirely
        $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
        if ($contentLength > 100 * 1024 * 1024) {
            http_response_code(413);
            echo json_encode(['success' => false, 'error' => 'File exceeds the maximum upload size of 100MB']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        }
        exit;
    }

    $file = $_FILES['file'];

    $projectId = isset($_POST['project_id']) && is_numeric($_POST['project_id']) ? (int) $_POST['project_id'] : null;
    $folderId = isset($_POST['folder_id']) && is_numeric($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;

    /* =====================================================
       TASK-ASSIGNMENT GATE  (non-admins only)
    ===================================================== */
    $uploaderRole = $_SESSION['role'] ?? 'employee';
    if ($uploaderRole !== 'admin' && $projectId) {
        $taskCheck = $db->prepare(
            "SELECT 1 FROM project_tasks WHERE project_id = ? AND assigned_to = ? LIMIT 1"
        );
        $taskCheck->bind_param('ii', $projectId, $userId);
        $taskCheck->execute();
        if ($taskCheck->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You need an assigned task in this project before you can upload files.']);
            exit;
        }
    }

    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $phpErrors = [
            UPLOAD_ERR_INI_SIZE  => 'File exceeds the maximum upload size of 100MB',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds the maximum upload size of 100MB',
            UPLOAD_ERR_PARTIAL   => 'Upload was interrupted. Please try again.',
        ];
        $msg = $phpErrors[$file['error']] ?? 'Upload failed. Please try again.';
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }

    $maxSize = 100 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File exceeds the maximum upload size of 100MB']);
        exit;
    }

    /* =====================================================
       MIME CHECK
    ===================================================== */

    $allowedMimes = [
        'image/png','image/jpeg','image/jpg','image/webp',
        'video/mp4','video/quicktime','video/x-msvideo','video/mpeg',
        'application/pdf','application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }

    /* =====================================================
       DUPLICATE CHECK
    ===================================================== */

    $originalName = trim($file['name']);

    if ($projectId !== null) {
        $dup = $db->prepare("SELECT id FROM files WHERE LOWER(original_name)=LOWER(?) AND project_id=? LIMIT 1");
        $dup->bind_param('si', $originalName, $projectId);
    } else {
        $dup = $db->prepare("SELECT id FROM files WHERE LOWER(original_name)=LOWER(?) AND project_id IS NULL AND user_id=? LIMIT 1");
        $dup->bind_param('si', $originalName, $userId);
    }

    $dup->execute();

    if ($dup->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Duplicate file name detected']);
        exit;
    }

    /* =====================================================
       STORAGE PATH (FIXED)
    ===================================================== */

    $baseStorage = __DIR__ . '/../uploads';

    $rawDir = $baseStorage . "/raw/";
    $encryptedDir = $baseStorage . "/encrypted/";
    $stegoDir = $baseStorage . "/stego/";
    $backupDir = $baseStorage . "/backups/";

    foreach ([$rawDir, $encryptedDir, $stegoDir, $backupDir] as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to create upload directory. Check server permissions.',
                    'debug' => ['dir' => $dir]
                ]);
                exit;
            }
        }
    }

    if (!is_writable($rawDir)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Upload directory is not writable. Fix permissions on the server.',
            'debug' => ['dir' => $rawDir]
        ]);
        exit;
    }

    /* =====================================================
       STEP 1: SAVE RAW FILE (FIXED)
    ===================================================== */

    if (!is_uploaded_file($file["tmp_name"])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Invalid upload source']);
        exit;
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file["name"]);
    $rawFilename = uniqid('raw_', true) . '_' . $safeName;
    $rawPath = $rawDir . $rawFilename;

    if (!move_uploaded_file($file["tmp_name"], $rawPath)) {
        error_log("Upload failed: {$file['tmp_name']} -> $rawPath");

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to store raw file',
            'debug' => [
                'tmp' => $file["tmp_name"],
                'target' => $rawPath
            ]
        ]);
        exit;
    }

    /* =====================================================
       STEP 2: PDF PROTECTION (OPTIONAL)
    ===================================================== */

    $sourcePath = $rawPath;
    $tempPdf = null;

    if ($mimeType === 'application/pdf' && !empty($_POST['pdf_password'])) {

        $protected = PdfSecurity::protectPdfWithPassword($rawPath, $_POST['pdf_password']);

        if ($protected === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'PDF protection failed']);
            exit;
        }

        $sourcePath = $protected;
        $tempPdf = $protected;
    }

    /* =====================================================
       STEP 3: ENCRYPT FILE
    ===================================================== */

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $encFilename = uniqid('enc_', true) . '.' . $extension;
    $encPath = $encryptedDir . $encFilename;

    if (!Encryption::encryptFile($sourcePath, $encPath)) {

        if ($tempPdf && file_exists($tempPdf)) unlink($tempPdf);
        if (file_exists($rawPath)) unlink($rawPath);

        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Encryption failed']);
        exit;
    }

    if ($tempPdf && file_exists($tempPdf)) unlink($tempPdf);
    if (file_exists($rawPath)) unlink($rawPath);

    /* =====================================================
       STEP 4: DATABASE INSERT
    ===================================================== */

    $fileSize = filesize($encPath);
    $relativePath = 'uploads/encrypted/' . $encFilename;

    $requireWatermark = (isset($_POST['require_watermark']) && $_POST['require_watermark'] === '1') ? 1 : 0;

    $stmt = $db->prepare("
        INSERT INTO files 
        (user_id, filename, original_name, file_path, file_size, mime_type, watermarked, upload_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        'isssisi',
        $userId,
        $encFilename,
        $originalName,
        $relativePath,
        $fileSize,
        $mimeType,
        $requireWatermark
    );

    if (!$stmt->execute()) {
        unlink($encPath);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit;
    }

    $fileId = $stmt->insert_id;

    /* =====================================================
       STEP 5: PROJECT LINK
    ===================================================== */

    if ($projectId !== null || $folderId !== null) {
        $upd = $db->prepare("UPDATE files SET project_id=?, folder_id=? WHERE id=?");
        $upd->bind_param('iii', $projectId, $folderId, $fileId);
        $upd->execute();
    }

    /* =====================================================
       STEP 6: AUTO-COMPLETE MATCHING TASK
    ===================================================== */

    if ($projectId && $uploaderRole !== 'admin') {
        $fileCategory = 'document';
        if (strpos($mimeType, 'image/') === 0) $fileCategory = 'image';
        elseif (strpos($mimeType, 'video/') === 0) $fileCategory = 'video';

        $taskStmt = $db->prepare("
            SELECT id FROM project_tasks
            WHERE project_id = ? AND assigned_to = ?
              AND status != 'completed'
              AND (required_file_type = 'any' OR required_file_type IS NULL OR required_file_type = ?)
            ORDER BY
                CASE WHEN required_file_type = ? THEN 0 ELSE 1 END,
                created_at ASC
            LIMIT 1
        ");
        $taskStmt->bind_param('iiss', $projectId, $userId, $fileCategory, $fileCategory);
        $taskStmt->execute();
        $taskRow = $taskStmt->get_result()->fetch_assoc();
        if ($taskRow) {
            $updTask = $db->prepare("UPDATE project_tasks SET status = 'completed', progress = 100 WHERE id = ?");
            $updTask->bind_param('i', $taskRow['id']);
            $updTask->execute();
        }
    }

    /* =====================================================
       SUCCESS
    ===================================================== */

    echo json_encode([
        'success' => true,
        'data' => [
            'file' => [
                'id' => $fileId,
                'filename' => $encFilename,
                'original_name' => $originalName,
                'path' => $relativePath
            ],
            'message' => 'Upload successful'
        ]
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);