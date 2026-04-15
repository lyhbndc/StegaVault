<?php
/**
 * StegaVault - Super Admin MFA API
 * File: api/super_admin_mfa.php
 */

session_start();

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/mfa_errors.log');

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/SuperAdminLogger.php';
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

function generateRecoveryCodes($count = 10) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $code = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        $codes[] = $code;
    }
    return $codes;
}

function storeRecoveryCodes($superAdminId, $codes) {
    global $db;

    $stmt = $db->prepare("DELETE FROM super_admin_mfa_recovery_codes WHERE super_admin_id = ? AND used = FALSE");
    $stmt->bind_param('i', $superAdminId);
    $stmt->execute();

    $stmt = $db->prepare("INSERT INTO super_admin_mfa_recovery_codes (super_admin_id, code) VALUES (?, ?)");
    foreach ($codes as $code) {
        $stmt->bind_param('is', $superAdminId, $code);
        $stmt->execute();
    }
}

// 1. Generate MFA secret and QR code for setup
if ($method === 'GET' && $action === 'setup') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
        sendResponse(false, null, 'Not authenticated', 401);
    }

    $stmt = $db->prepare("SELECT email, is_mfa_enabled FROM super_admins WHERE id = ?");
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
    $_SESSION['temp_mfa_secret'] = $secret;

    $qrCodeUrl = $ga->getQRCodeGoogleUrl('OwlOps - ' . $user['email'], $secret);

    sendResponse(true, [
        'secret' => $secret,
        'qr_url' => $qrCodeUrl
    ]);
}

// 2. Verify MFA setup and save secret
if ($method === 'POST' && $action === 'verify_setup') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin' || !isset($_SESSION['temp_mfa_secret'])) {
        sendResponse(false, null, 'MFA setup not started', 400);
    }

    $code = $input['code'] ?? '';
    if (empty($code)) {
        sendResponse(false, null, 'Code is required', 400);
    }

    $secret = $_SESSION['temp_mfa_secret'];
    $checkResult = $ga->verifyCode($secret, $code, 2);

    if ($checkResult) {
        $stmt = $db->prepare("UPDATE super_admins SET mfa_secret = ?, is_mfa_enabled = TRUE WHERE id = ?");
        $stmt->bind_param('si', $secret, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $recoveryCodes = generateRecoveryCodes(10);
            storeRecoveryCodes($_SESSION['user_id'], $recoveryCodes);
            unset($_SESSION['temp_mfa_secret']);
            SuperAdminLogger::log('mfa_enabled', 'mfa');
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
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
        sendResponse(false, null, 'Not authenticated', 401);
    }

    $stmt = $db->prepare("UPDATE super_admins SET mfa_secret = NULL, is_mfa_enabled = FALSE WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    if ($stmt->execute()) {
        SuperAdminLogger::log('mfa_disabled', 'mfa');
        sendResponse(true, ['message' => 'MFA disabled']);
    } else {
        sendResponse(false, null, 'Database error', 500);
    }
}

// 4. Verify MFA during login
if ($method === 'POST' && $action === 'verify_login') {
    if (!isset($_SESSION['pending_mfa_user_id']) || !isset($_SESSION['pending_mfa_portal']) || $_SESSION['pending_mfa_portal'] !== 'super_admin') {
        sendResponse(false, null, 'No pending MFA challenge', 400);
    }

    $code = $input['code'] ?? '';
    if (empty($code)) {
        sendResponse(false, null, 'Code is required', 400);
    }

    $pendingUserId = $_SESSION['pending_mfa_user_id'];

    $stmt = $db->prepare("SELECT id, email, name, mfa_secret FROM super_admins WHERE id = ?");
    $stmt->bind_param('i', $pendingUserId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || empty($user['mfa_secret'])) {
        sendResponse(false, null, 'Invalid user or MFA not configured', 400);
    }

    // Try TOTP code first
    $checkResult = $ga->verifyCode($user['mfa_secret'], $code, 2);

    // If TOTP fails, try recovery code
    if (!$checkResult) {
        $recoveryCode = strtoupper(trim($code));
        $stmt = $db->prepare("SELECT id FROM super_admin_mfa_recovery_codes WHERE super_admin_id = ? AND code = ? AND used = FALSE");
        $stmt->bind_param('is', $pendingUserId, $recoveryCode);
        $stmt->execute();
        $recoveryResult = $stmt->get_result();

        if ($recoveryResult->num_rows > 0) {
            $recoveryRow = $recoveryResult->fetch_assoc();
            $updateStmt = $db->prepare("UPDATE super_admin_mfa_recovery_codes SET used = TRUE, used_at = NOW() WHERE id = ?");
            $updateStmt->bind_param('i', $recoveryRow['id']);
            $updateStmt->execute();
            $checkResult = true;
        }
    }

    if ($checkResult) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = 'super_admin';

        unset($_SESSION['pending_mfa_user_id']);
        unset($_SESSION['pending_mfa_portal']);
        SuperAdminLogger::log('login_mfa_success', 'auth');

        sendResponse(true, [
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => 'super_admin'
            ],
            'message' => 'Login successful'
        ]);
    } else {
        sendResponse(false, null, 'Invalid verification code', 400);
    }
}

// 5. Regenerate recovery codes
if ($method === 'POST' && $action === 'regenerate_recovery_codes') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
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
