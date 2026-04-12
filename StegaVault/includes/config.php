<?php

/**
 * StegaVault - Database Configuration
 * File: includes/config.php
 */

// Database Configuration (Supabase PostgreSQL via Transaction Pooler)
// Port 6543 = Transaction Pooler (works better with restrictive EC2 firewalls)
// Port 5432 = Session Pooler (requires outbound port 5432 open in AWS Security Group)
define('DB_HOST', 'aws-1-ap-southeast-2.pooler.supabase.com');
define('DB_PORT', '6543');
define('DB_USER', 'postgres.dknxptrhnjpcymvvmdpj');
define('DB_PASS', 'OwlOpsCo432');
define('DB_NAME', 'postgres');

// Site Configuration
define('SITE_URL', 'http://localhost/stegavault');
define('SITE_NAME', 'StegaVault');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes
define('SESSION_IDLE_TIMEOUT_SECONDS', 900); // 15 minutes

// JWT Secret (change this to something random!)
define('JWT_SECRET', 'your_secret_key_change_this_12345');

// Error reporting (production: hide errors from output)
error_reporting(0);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('Asia/Manila');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isApiRequest(): bool
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    return strpos($scriptName, '/api/') !== false;
}

function getLoginRedirectByRole(string $role): string
{
    return match ($role) {
        'admin' => '/StegaVault/admin/login.php',
        'collaborator' => '/StegaVault/collaborator/login.php',
        default => '/StegaVault/employee/login.php',
    };
}

function destroyCurrentSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

if (isset($_SESSION['user_id'])) {
    $now = time();
    $lastActivity = isset($_SESSION['last_activity']) ? (int) $_SESSION['last_activity'] : $now;

    if (($now - $lastActivity) > SESSION_IDLE_TIMEOUT_SECONDS) {
        $role = $_SESSION['role'] ?? 'employee';
        destroyCurrentSession();

        if (isApiRequest()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Session expired due to inactivity. Please login again.',
                'session_expired' => true
            ]);
            exit;
        }

        header('Location: ' . getLoginRedirectByRole($role) . '?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = $now;
}