<?php

/**
 * StegaVault - Secure Download with Cryptographic Watermark
 * Works on macOS (XAMPP) and Windows (XAMPP/WAMP)
 */

session_start();
ob_start(); // Buffer all output to prevent "Headers already sent" corruption
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';
require_once __DIR__ . '/../includes/Encryption.php';
require_once __DIR__ . '/../includes/watermark.php';
require_once __DIR__ . '/../includes/CryptoWatermark.php';
require_once __DIR__ . '/../includes/PdfWatermark.php';
require_once __DIR__ . '/../includes/VisibleWatermark.php';

// ── Auth ─────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Not authenticated');
}

$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'Unknown';
$fileId = isset($_GET['file_id']) ? (int) $_GET['file_id'] : 0;

if ($fileId <= 0) {
    http_response_code(400);
    die('Invalid file ID');
}

// ── Fetch file record ────────────────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
$stmt->bind_param('i', $fileId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die('File not found');
}

$file = $result->fetch_assoc();
$originalPath = __DIR__ . '/../' . $file['file_path'];

if (!file_exists($originalPath)) {
    http_response_code(404);
    die('File not found on disk');
}

// ── Detect file type (MIME + extension — works on macOS & Windows) ────────────
$storedMime = $file['mime_type'] ?? '';
$ext = strtolower(pathinfo($file['original_name'] ?? '', PATHINFO_EXTENSION));

$isImage = in_array($storedMime, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])
    || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
$isVideo = (strpos($storedMime, 'video/') === 0)
    || in_array($ext, ['mp4', 'webm', 'mov', 'ogg']);
$isPdf = ($storedMime === 'application/pdf') || ($ext === 'pdf');

// Normalise the MIME used for response headers
if ($isImage && !$storedMime)
    $storedMime = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
if ($isVideo && !$storedMime)
    $storedMime = 'video/mp4';
$serveMime = $storedMime ?: 'application/octet-stream';

// ── Serve encrypted (unreadable) file if NO watermark required ───────────────
if ((int) $file['watermarked'] === 0) {
    try {
        $updF = $db->prepare("UPDATE files SET download_count = download_count + 1 WHERE id = ?");
        $updF->bind_param('i', $fileId);
        $updF->execute();
    } catch (Exception $e) {
    }

    ob_end_clean();
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file['original_name']) . '"');
    header('Content-Length: ' . filesize($originalPath));
    header('Cache-Control: private');
    readfile($originalPath);
    exit;
}

// ── Decrypt to memory (only needed for watermarked files) ────────────────────
$decryptedContent = Encryption::decryptFileContent($originalPath);
if ($decryptedContent === false) {
    http_response_code(500);
    die('Decryption failed');
}

// sys_get_temp_dir() works on both macOS (/tmp) and Windows (C:\Windows\Temp)
$tmpBase = tempnam(sys_get_temp_dir(), 'sv_');
// On some systems appending an extension creates a path we can't write to.
// We'll write directly to the generated temp file.
$tempDecryptedPath = $tmpBase;

if (file_put_contents($tempDecryptedPath, $decryptedContent) === false) {
    @unlink($tempDecryptedPath);
    http_response_code(500);
    die('Failed to write temp file');
}

// ── Watermark dirs ────────────────────────────────────────────────────────────
$watermarkedDir = __DIR__ . '/../uploads/watermarked/';
$watermarkedFilename = 'wm_' . $userId . '_' . $fileId . '_' . time() . '.png';
$watermarkedPath = $watermarkedDir . $watermarkedFilename;

if (!is_dir($watermarkedDir)) {
    mkdir($watermarkedDir, 0755, true);
}

// ── Build crypto watermark ────────────────────────────────────────────────────
$userData = [
    'id' => $userId,
    'name' => $userName,
    'email' => $_SESSION['email'] ?? 'unknown@stegavault.local',
    'role' => $_SESSION['role'] ?? 'unknown',
];
$fileData = [
    'id' => $fileId,
    'path' => $originalPath,
    'mime_type' => $serveMime,
];
$metaData = [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'session' => session_id(),
    'download_count' => 1,
];

$cryptoWatermark = CryptoWatermark::generateWatermark($userData, $fileData, $metaData);
if (!$cryptoWatermark) {
    @unlink($tempDecryptedPath);
    http_response_code(500);
    die('Failed to generate cryptographic watermark');
}

$ownerId = $file['user_id'] ?? 0;
$ownerName = 'Unknown';
$ownerRole = 'unknown';

if ($ownerId > 0) {
    $ownerStmt = $db->prepare("SELECT name, role FROM users WHERE id = ?");
    $ownerStmt->bind_param('i', $ownerId);
    $ownerStmt->execute();
    $ownerResult = $ownerStmt->get_result();
    if ($ownerResult->num_rows > 0) {
        $ownerData = $ownerResult->fetch_assoc();
        $ownerName = $ownerData['name'];
        $ownerRole = $ownerData['role'];
    }
}

$wmTs = time();

// For images: apply the visible watermark BEFORE computing content_hash and
// embedding LSB data. This ensures content_hash reflects the visibly-watermarked
// pixels, so forensic tamper-detection works correctly on the final file.
if ($isImage) {
    VisibleWatermark::applyToImage($tempDecryptedPath, $tempDecryptedPath, [
        'u_name' => $userName,
        'u_role' => $_SESSION['role'] ?? 'unknown',
        'ts'     => $wmTs,
    ]);
}

$watermarkData = [
    'u_id' => $userId,
    'u_name' => $userName,
    'u_role' => $_SESSION['role'] ?? 'unknown',
    'f_id' => $fileId,
    'f_owner_id' => $ownerId,
    'f_owner_name' => $ownerName,
    'f_owner_role' => $ownerRole,
    'ts' => $wmTs,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'salt' => bin2hex(random_bytes(4)),
    'crypto' => $cryptoWatermark,
    'content_hash' => Watermark::calculateImageHash($tempDecryptedPath)
];

// ── Log Download Activity ──────────────────────────────────────────────────────
try {
    $logDesc = "Downloaded file: " . $file['original_name'] . " by " . $userName;
    logActivityEvent(
        $db,
        $userId,
        'file_downloaded',
        $logDesc,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SESSION['role'] ?? null,
        false
    );
} catch (Exception $e) {
    error_log('Activity log failed: ' . $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
if ($isImage) {
    // ── IMAGE: LSB watermark + serve as PNG ──────────────────────────────────────

    $success = Watermark::embedWatermark($tempDecryptedPath, $watermarkedPath, $watermarkData);
    @unlink($tempDecryptedPath); // always clean up temp

    if (!$success) {
        http_response_code(500);
        die('Failed to create watermarked image');
    }

    // Update DB — wrapped so missing tables don't crash the download
    try {
        $watermarkId = md5($userId . '_' . $fileId . '_' . time());
        $relativePath = 'uploads/watermarked/' . $watermarkedFilename;

        $chk = $db->prepare("SELECT id FROM watermark_mappings WHERE file_id = ? AND user_id = ?");
        $chk->bind_param('ii', $fileId, $userId);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            $upd = $db->prepare("UPDATE watermark_mappings SET download_count = download_count + 1, last_download = NOW() WHERE file_id = ? AND user_id = ?");
            $upd->bind_param('ii', $fileId, $userId);
            $upd->execute();
        } else {
            $ins = $db->prepare("INSERT INTO watermark_mappings (file_id, user_id, watermark_id, watermarked_path, download_count, last_download, crypto_enabled, signature) VALUES (?, ?, ?, ?, 1, NOW(), TRUE, ?)");
            $ins->bind_param('iisss', $fileId, $userId, $watermarkId, $relativePath, $cryptoWatermark['signature']);
            $ins->execute();
        }

        // Log crypto watermark (table may not exist yet — non-fatal)
        try {
            CryptoWatermark::logWatermark($db, $cryptoWatermark, $fileId, $userId, $watermarkId);
        } catch (Exception $e) {
            error_log('CryptoWatermark log skipped: ' . $e->getMessage());
        }

        $updF = $db->prepare("UPDATE files SET download_count = download_count + 1, watermarked = TRUE WHERE id = ?");
        $updF->bind_param('i', $fileId);
        $updF->execute();
    } catch (Exception $e) {
        error_log('Watermark DB error (non-fatal): ' . $e->getMessage());
    }

    $baseName = pathinfo($file['original_name'], PATHINFO_FILENAME);
    $safeName = str_replace('"', '', $baseName . '_watermarked.png');

    ob_end_clean(); // Discard any whitespace/output from includes
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Content-Length: ' . filesize($watermarkedPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    readfile($watermarkedPath);
    exit;
} elseif ($isPdf) {
    // ── PDF: Visible diagonal watermark overlay ───────────────────────────────────

    $pdfWmError = null;
    $watermarkedPdfPath = PdfWatermark::applyWatermark($tempDecryptedPath, $watermarkData, $pdfWmError);
    @unlink($tempDecryptedPath);

    if (!$watermarkedPdfPath) {
        http_response_code(500);
        die('Failed to watermark PDF: ' . ($pdfWmError ?? 'unknown error'));
    }

    // Recompute content_hash from the TCPDF-rendered output (before appending
    // forensic tag) so analysis hashes the same bytes that are stored on disk.
    $watermarkData['content_hash'] = Watermark::calculateDocumentHash($watermarkedPdfPath);

    // Embed forensic signature after %%EOF — PDF readers ignore trailing bytes.
    // Use \n[STEGAVAULT_DOC_WM] (no % prefix) so calculateDocumentHash can
    // detect and strip the tag when computing the tamper-detection hash.
    $forensicPayload = "\n[STEGAVAULT_DOC_WM]"
        . base64_encode(json_encode($watermarkData))
        . "[/STEGAVAULT_DOC_WM]\n";
    file_put_contents($watermarkedPdfPath, $forensicPayload, FILE_APPEND);

    try {
        $watermarkId  = md5($userId . '_' . $fileId . '_' . time());
        $relativePath = 'tmp/' . basename($watermarkedPdfPath);

        $chk = $db->prepare("SELECT id FROM watermark_mappings WHERE file_id = ? AND user_id = ?");
        $chk->bind_param('ii', $fileId, $userId);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            $upd = $db->prepare("UPDATE watermark_mappings SET download_count = download_count + 1, last_download = NOW() WHERE file_id = ? AND user_id = ?");
            $upd->bind_param('ii', $fileId, $userId);
            $upd->execute();
        } else {
            $ins = $db->prepare("INSERT INTO watermark_mappings (file_id, user_id, watermark_id, watermarked_path, download_count, last_download, crypto_enabled, signature) VALUES (?, ?, ?, ?, 1, NOW(), TRUE, ?)");
            $ins->bind_param('iisss', $fileId, $userId, $watermarkId, $relativePath, $cryptoWatermark['signature']);
            $ins->execute();
        }

        try {
            CryptoWatermark::logWatermark($db, $cryptoWatermark, $fileId, $userId, $watermarkId);
        } catch (Exception $e) {
            error_log('CryptoWatermark log skipped: ' . $e->getMessage());
        }

        $updF = $db->prepare("UPDATE files SET download_count = download_count + 1, watermarked = TRUE WHERE id = ?");
        $updF->bind_param('i', $fileId);
        $updF->execute();
    } catch (Exception $e) {
        error_log('PDF watermark DB error (non-fatal): ' . $e->getMessage());
    }

    $safePdfName = str_replace('"', '', pathinfo($file['original_name'], PATHINFO_FILENAME) . '_watermarked.pdf');

    ob_end_clean();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $safePdfName . '"');
    header('Content-Length: ' . filesize($watermarkedPdfPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    readfile($watermarkedPdfPath);
    @unlink($watermarkedPdfPath);
    exit;
} else {
    // ── VIDEO / OTHER: Embed Document Watermark & Serve ──────────────────────────

    $watermarkedDir = __DIR__ . '/../uploads/watermarked/';
    $watermarkedFilename = 'wm_doc_' . $userId . '_' . $fileId . '_' . time() . '.' . $ext;
    $watermarkedPath = $watermarkedDir . $watermarkedFilename;

    if (!is_dir($watermarkedDir)) {
        mkdir($watermarkedDir, 0755, true);
    }

    // Apply visible watermark for xlsx/docx before the forensic append
    $visibleExts = ['xlsx', 'docx'];
    if (in_array($ext, $visibleExts)) {
        $visTmp = tempnam(sys_get_temp_dir(), 'sv_vis_');
        $visOk  = VisibleWatermark::apply($tempDecryptedPath, $visTmp, $watermarkData, $ext);
        $sourceForForensic = ($visOk && file_exists($visTmp) && filesize($visTmp) > 0)
            ? $visTmp
            : $tempDecryptedPath;
        $success = Watermark::embedDocumentWatermark($sourceForForensic, $watermarkedPath, $watermarkData);
        @unlink($tempDecryptedPath);
        if (isset($visTmp)) @unlink($visTmp);
    } else {
        $success = Watermark::embedDocumentWatermark($tempDecryptedPath, $watermarkedPath, $watermarkData);
        @unlink($tempDecryptedPath); // always clean up temp
    }

    if (!$success) {
        http_response_code(500);
        die('Failed to create watermarked document');
    }

    // Update DB — wrapped so missing tables don't crash the download
    try {
        $watermarkId = md5($userId . '_' . $fileId . '_' . time());
        $relativePath = 'uploads/watermarked/' . $watermarkedFilename;

        $chk = $db->prepare("SELECT id FROM watermark_mappings WHERE file_id = ? AND user_id = ?");
        $chk->bind_param('ii', $fileId, $userId);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            $upd = $db->prepare("UPDATE watermark_mappings SET download_count = download_count + 1, last_download = NOW() WHERE file_id = ? AND user_id = ?");
            $upd->bind_param('ii', $fileId, $userId);
            $upd->execute();
        } else {
            $ins = $db->prepare("INSERT INTO watermark_mappings (file_id, user_id, watermark_id, watermarked_path, download_count, last_download, crypto_enabled, signature) VALUES (?, ?, ?, ?, 1, NOW(), TRUE, ?)");
            $ins->bind_param('iisss', $fileId, $userId, $watermarkId, $relativePath, $cryptoWatermark['signature']);
            $ins->execute();
        }

        try {
            CryptoWatermark::logWatermark($db, $cryptoWatermark, $fileId, $userId, $watermarkId);
        } catch (Exception $e) {
            error_log('CryptoWatermark log skipped: ' . $e->getMessage());
        }

        $updF = $db->prepare("UPDATE files SET download_count = download_count + 1, watermarked = TRUE WHERE id = ?");
        $updF->bind_param('i', $fileId);
        $updF->execute();
    } catch (Exception $e) {
        error_log('Watermark DB error (non-fatal): ' . $e->getMessage());
    }

    ob_end_clean(); // Discard any whitespace/output from includes
    header('Content-Type: ' . $serveMime);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file['original_name']) . '"');
    header('Content-Length: ' . filesize($watermarkedPath));
    header('Cache-Control: private');
    readfile($watermarkedPath);
    exit;
}
