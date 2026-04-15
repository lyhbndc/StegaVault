<?php

/**
 * StegaVault - Upload API (Video Support + Project Link)
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
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['file'];
    $projectId = isset($_POST['project_id']) && is_numeric($_POST['project_id']) ? (int) $_POST['project_id'] : null;
    $folderId = isset($_POST['folder_id']) && is_numeric($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;

    /* =====================================================
       VALIDATION (PROJECT / FOLDER)
    ===================================================== */
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
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'folder_id requires project_id']);
        exit;
    }

    if ($projectId) {
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

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Upload failed']);
        exit;
    }

    /* =====================================================
       FILE TYPE + SIZE VALIDATION
    ===================================================== */
    $maxSize = 50 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'File too large']);
        exit;
    }

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
       🔐 STEGAVAULT STORAGE STRUCTURE
    ===================================================== */

    $baseStorage = __DIR__ . "/storage/";

    $rawDir = $baseStorage . "raw/";
    $encryptedDir = $baseStorage . "encrypted/";
    $stegoDir = $baseStorage . "stego/";
    $backupDir = $baseStorage . "backups/";

    foreach ([$rawDir, $encryptedDir, $stegoDir, $backupDir] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /* =====================================================
       STEP 1: SAVE RAW FILE
    ===================================================== */

    $rawFilename = time() . "_" . basename($file["name"]);
    $rawPath = $rawDir . $rawFilename;

    if (!move_uploaded_file($file["tmp_name"], $rawPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to store raw file']);
        exit;
    }

    /* =====================================================
       STEP 2: OPTIONAL PDF PASSWORD PROTECTION
    ===================================================== */

    $sourcePath = $rawPath;
    $tempPdf = null;

    if ($mimeType === 'application/pdf' && !empty($_POST['pdf_password'])) {

        $pdfPassword = $_POST['pdf_password'];

        $protected = PdfSecurity::protectPdfWithPassword($rawPath, $pdfPassword);

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
    $relativePath = 'storage/encrypted/' . $encFilename;

    $requireWatermark = true;

    $stmt = $db->prepare("
        INSERT INTO files 
        (user_id, filename, original_name, file_path, file_size, mime_type, watermarked, upload_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        'isssisb',
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
       STEP 5: ASSIGN PROJECT/FOLDER
    ===================================================== */

    if ($projectId !== null || $folderId !== null) {
        $upd = $db->prepare("UPDATE files SET project_id=?, folder_id=? WHERE id=?");
        $upd->bind_param('iii', $projectId, $folderId, $fileId);
        $upd->execute();
    }

    /* =====================================================
       SUCCESS RESPONSE
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
            'message' => 'File uploaded, encrypted, and stored successfully'
        ]
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);