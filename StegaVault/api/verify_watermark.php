<?php
/**
 * StegaVault - Watermark Verification API
 * File: api/verify_watermark.php
 * 
 * API endpoint for programmatic watermark verification
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/watermark.php';
require_once __DIR__ . '/../includes/CryptoWatermark.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Only admins can verify watermarks via API
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions']);
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$uploadedFile = $_FILES['file'];

if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'File upload error: ' . $uploadedFile['error']]);
    exit;
}

try {
    $tempPath = $uploadedFile['tmp_name'];
    
    // Extract watermark
    $extractedWatermark = Watermark::extractWatermark($tempPath);
    
    if (!$extractedWatermark) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'No watermark found in file',
            'watermark_present' => false
        ]);
        exit;
    }
    
    // Check if it's a cryptographic watermark
    if (!isset($extractedWatermark['crypto'])) {
        echo json_encode([
            'success' => true,
            'watermark_present' => true,
            'crypto_enabled' => false,
            'type' => 'legacy',
            'data' => $extractedWatermark,
            'message' => 'Legacy watermark detected (no cryptographic protection)'
        ]);
        exit;
    }
    
    // Get user data for verification
    $userId = $extractedWatermark['crypto']['public']['user_id'];
    
    $stmt = $db->prepare("SELECT id, email FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found in database',
            'watermark_present' => true,
            'crypto_enabled' => true,
            'user_id' => $userId
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    $userData = [
        'id' => $user['id'],
        'email' => $user['email']
    ];
    
    // Verify cryptographic watermark
    $verificationResult = CryptoWatermark::verifyWatermark($extractedWatermark['crypto'], $userData);
    
    if (!$verificationResult) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Watermark verification failed',
            'watermark_present' => true,
            'crypto_enabled' => true,
            'valid' => false,
            'message' => 'Cryptographic verification failed - watermark may be tampered or forged'
        ]);
        exit;
    }
    
    // Log verification
    if ($verificationResult['valid'] === true) {
        CryptoWatermark::logVerification($db, $extractedWatermark['crypto']['signature']);
    }
    
    // Get verification history
    $historyStmt = $db->prepare("
        SELECT verification_count, last_verified, created_at 
        FROM watermark_crypto_log 
        WHERE signature = ?
    ");
    $historyStmt->bind_param('s', $extractedWatermark['crypto']['signature']);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    $history = $historyResult->fetch_assoc();
    
    // Success response
    echo json_encode([
        'success' => true,
        'watermark_present' => true,
        'crypto_enabled' => true,
        'valid' => $verificationResult['valid'] === true,
        'verification' => $verificationResult,
        'history' => $history,
        'file_info' => [
            'name' => $uploadedFile['name'],
            'size' => $uploadedFile['size'],
            'type' => $uploadedFile['type']
        ],
        'verified_by' => [
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'],
            'timestamp' => time()
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
