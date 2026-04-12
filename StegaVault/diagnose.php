<?php
// StegaVault - Server Diagnostic
// Access this page on EC2 to diagnose connection issues
// DELETE THIS FILE AFTER DIAGNOSIS

// Basic security: check if it's a local/admin request
header('Content-Type: text/plain');

echo "=== StegaVault Server Diagnostic ===\n\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Active php.ini: " . php_ini_loaded_file() . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n\n";

// --- Extensions Check ---
echo "--- Extensions ---\n";
$extensions = ['openssl', 'curl', 'gd', 'mbstring', 'finfo', 'pdo_pgsql'];
foreach ($extensions as $ext) {
    if ($ext === 'finfo') {
        echo "finfo: " . (function_exists('finfo_open') ? 'YES' : 'NO') . "\n";
    } else {
        echo "$ext: " . (extension_loaded($ext) ? 'YES' : 'NO') . "\n";
    }
}

if (!extension_loaded('curl')) {
    echo ">>> ALERT: PHP-CURL is MISSING. This causes the 'Undefined constant CURLOPT_CONNECTTIMEOUT' error. <<<\n";
    echo ">>> FIX: Run 'sudo apt-get install php-curl' on your EC2 and restart Apache/Nginx. <<<\n";
}
if (!extension_loaded('gd')) {
    echo ">>> ALERT: PHP-GD is MISSING. Image watermarking will fail. <<<\n";
    echo ">>> FIX: Run 'sudo apt-get install php-gd' on your EC2. <<<\n";
}
echo "\n";

// --- OpenSSL Check ---
echo "--- OpenSSL ---\n";
echo "Loaded: " . (extension_loaded('openssl') ? 'YES' : 'NO') . "\n";
if (extension_loaded('openssl')) {
    echo "Version: " . OPENSSL_VERSION_TEXT . "\n";
    echo "AES-256-CBC supported: " . (in_array('aes-256-cbc', openssl_get_cipher_methods()) ? 'YES' : 'NO') . "\n";
}

// --- Uploads Directory ---
echo "\n--- Uploads Directory ---\n";
$uploadDir = __DIR__ . '/uploads/';
echo "Path: $uploadDir\n";
echo "Exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Readable: " . (is_readable($uploadDir) ? 'YES' : 'NO') . "\n";

// Test write
$testFile = $uploadDir . 'write_test_' . time() . '.tmp';
$writeResult = file_put_contents($testFile, 'test');
echo "Write test: " . ($writeResult !== false ? 'PASS' : 'FAIL') . "\n";
if ($writeResult !== false) @unlink($testFile);

// --- Database Connection ---
echo "\n--- Database Connection ---\n";
require_once __DIR__ . '/includes/config.php';
echo "Host: " . DB_HOST . "\n";
echo "Port: " . DB_PORT . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";

// Test TCP connection first
echo "\nTCP socket test to " . DB_HOST . ":" . DB_PORT . "...\n";
$sock = @fsockopen(DB_HOST, (int)DB_PORT, $errno, $errstr, 5);
if ($sock) {
    echo "TCP: CONNECTED\n";
    fclose($sock);
} else {
    echo "TCP: FAILED - Error $errno: $errstr\n";
    echo ">>> This means the EC2 security group/firewall is blocking outbound port " . DB_PORT . " <<<\n";
}

// Try PDO connection
echo "\nPDO connection test...\n";
try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require;connect_timeout=8";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
    echo "PDO: CONNECTED\n";
    $r = $pdo->query("SELECT COUNT(*) FROM files")->fetchColumn();
    echo "Files in DB: $r\n";
    $pdo = null;
} catch (Exception $e) {
    echo "PDO: FAILED - " . $e->getMessage() . "\n";
}

// --- Encryption Test ---
echo "\n--- Encryption Test ---\n";
require_once __DIR__ . '/includes/Encryption.php';

$tmpSrc = tempnam(sys_get_temp_dir(), 'sv_test_');
file_put_contents($tmpSrc, 'test content for encryption');

$tmpDst = tempnam($uploadDir ?: sys_get_temp_dir(), 'sv_enc_test_');
$result = Encryption::encryptFile($tmpSrc, $tmpDst);
echo "Encryption test: " . ($result ? 'PASS' : 'FAIL') . "\n";

if ($result) {
    $decrypted = Encryption::decryptFileContent($tmpDst);
    echo "Decryption test: " . ($decrypted === 'test content for encryption' ? 'PASS' : 'FAIL') . "\n";
    @unlink($tmpDst);
}
@unlink($tmpSrc);

echo "\n=== End of Diagnostic ===\n";
