<?php
/**
 * StegaVault - MFA API
 * File: api/mfa.php
 */

session_start();

// Only suppress specific notices, but log errors
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/mfa_errors.log');

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

$ga = new PHPGangsta_GoogleAuthenticator();

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

// Generate random recovery codes
function generateRecoveryCodes($count = 10) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        // Format: XXXX-XXXX (4 hex digits - dash - 4 hex digits)
        $code = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        $codes[] = $code;
    }
    return $codes;
}

// Store recovery codes for user
function storeRecoveryCodes($userId, $codes) {
    global $db;
    
    // Clear old unused codes
    $stmt = $db->prepare("DELETE FROM mfa_recovery_codes WHERE user_id = ? AND used = 0");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    
    // Insert new recovery codes
    $stmt = $db->prepare("INSERT INTO mfa_recovery_codes (user_id, code) VALUES (?, ?)");
    foreach ($codes as $code) {
        $stmt->bind_param('is', $userId, $code);
        $stmt->execute();
    }
}

// 1. Generate new MFA secret and QR code for setup
if ($method === 'GET' && $action === 'setup') {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, null, 'Not authenticated', 401);
    }
    
    // Get user email for QR code
    $stmt = $db->prepare("SELECT email, is_mfa_enabled FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        sendResponse(false, null, 'User not found', 401);
    }
    
    if ($user['is_mfa_enabled']) {
        sendResponse(false, null, 'MFA is already enabled.', 400);
    }

    $secret = $ga->createSecret();
    $_SESSION['temp_mfa_secret'] = $secret; // Store temporarily
    
    $qrCodeUrl = $ga->getQRCodeGoogleUrl('StegaVault - ' . $user['email'], $secret);
    
    sendResponse(true, [
        'secret' => $secret,
        'qr_url' => $qrCodeUrl
    ]);
}

// 2. Verify MFA setup (saving the generated secret)
if ($method === 'POST' && $action === 'verify_setup') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['temp_mfa_secret'])) {
        sendResponse(false, null, 'Not setup started', 400);
    }
    
    $code = $input['code'] ?? '';
    if (empty($code)) {
        sendResponse(false, null, 'Code is required', 400);
    }
    
    $secret = $_SESSION['temp_mfa_secret'];
    $checkResult = $ga->verifyCode($secret, $code, 2); // 2 = 2*30sec clock tolerance
    
    if ($checkResult) {
        $stmt = $db->prepare("UPDATE users SET mfa_secret = ?, is_mfa_enabled = 1 WHERE id = ?");
        $stmt->bind_param('si', $secret, $_SESSION['user_id']);
        if ($stmt->execute()) {
            // Generate and store recovery codes
            $recoveryCodes = generateRecoveryCodes(10);
            storeRecoveryCodes($_SESSION['user_id'], $recoveryCodes);
            
            unset($_SESSION['temp_mfa_secret']);
            sendResponse(true, [
                'message' => 'MFA successfully enabled',
                'recovery_codes' => $recoveryCodes
            ]);
        } else {
            sendResponse(false, null, 'Database error', 500);
        }
    } else {
        sendResponse(false, null, 'Invalid verification code', 400);
    }
}

// 3. Disable MFA
if ($method === 'POST' && $action === 'disable') {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, null, 'Not authenticated', 401);
    }
    
    $stmt = $db->prepare("UPDATE users SET mfa_secret = NULL, is_mfa_enabled = 0 WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    if ($stmt->execute()) {
        sendResponse(true, ['message' => 'MFA disabled']);
    } else {
        sendResponse(false, null, 'Database error', 500);
    }
}

// 4. Verify MFA during login
if ($method === 'POST' && $action === 'verify_login') {
    if (!isset($_SESSION['pending_mfa_user_id'])) {
        sendResponse(false, null, 'No pending MFA challenge', 400);
    }
    
    $code = $input['code'] ?? '';
    if (empty($code)) {
        sendResponse(false, null, 'Code is required', 400);
    }
    
    $pendingUserId = $_SESSION['pending_mfa_user_id'];
    
    $stmt = $db->prepare("SELECT id, email, name, role, mfa_secret FROM users WHERE id = ?");
    $stmt->bind_param('i', $pendingUserId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user || empty($user['mfa_secret'])) {
        sendResponse(false, null, 'Invalid user or MFA not setup', 400);
    }
    
    $checkResult = $ga->verifyCode($user['mfa_secret'], $code, 2);
    
    if ($checkResult) {
        // Complete the login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];
        
        unset($_SESSION['pending_mfa_user_id']);
        
        sendResponse(true, [
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role']
            ],
            'message' => 'Login successful',
            'session_id' => session_id()
        ]);
    } else {
        sendResponse(false, null, 'Invalid verification code', 400);
    }
}

// 5. Get recovery codes (for backup/display)
if ($method === 'GET' && $action === 'recovery_codes') {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, null, 'Not authenticated', 401);
    }
    
    $stmt = $db->prepare("SELECT code, used FROM mfa_recovery_codes WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $codes = [];
    while ($row = $result->fetch_assoc()) {
        $codes[] = [
            'code' => $row['code'],
            'used' => $row['used'] == 1
        ];
    }
    
    sendResponse(true, [
        'codes' => $codes,
        'total' => count($codes),
        'unused' => array_sum(array_map(fn($c) => !$c['used'] ? 1 : 0, $codes))
    ]);
}

// 6. Regenerate recovery codes
if ($method === 'POST' && $action === 'regenerate_recovery_codes') {
    if (!isset($_SESSION['user_id'])) {
        sendResponse(false, null, 'Not authenticated', 401);
    }
    
    $newCodes = generateRecoveryCodes(10);
    storeRecoveryCodes($_SESSION['user_id'], $newCodes);
    
    sendResponse(true, [
        'message' => 'Recovery codes regenerated',
        'recovery_codes' => $newCodes
    ]);
}

sendResponse(false, null, 'Unknown action', 400);
