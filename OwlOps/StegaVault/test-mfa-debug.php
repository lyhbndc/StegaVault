<?php
/**
 * MFA Debug Test
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/vendor/autoload.php';

echo "<h2>MFA System Diagnostic</h2>";

// 1. Check database columns
echo "<h3>1. Database Schema Check:</h3>";
$result = $db->query("SHOW COLUMNS FROM users LIKE 'mfa%'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color:green'>✓ MFA columns exist in users table</p>";
    while ($row = $result->fetch_assoc()) {
        echo "<pre>" . json_encode($row, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "<p style='color:red'>✗ MFA columns NOT FOUND. Run migration!</p>";
}

// 2. Check Google Authenticator
echo "<h3>2. Google Authenticator Library Check:</h3>";
try {
    $ga = new PHPGangsta_GoogleAuthenticator();
    $secret = $ga->createSecret();
    echo "<p style='color:green'>✓ Google Authenticator working</p>";
    echo "<p>Generated test secret: <code>$secret</code></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}

// 3. Check user with MFA enabled
echo "<h3>3. Users with MFA Status:</h3>";
$result = $db->query("SELECT id, email, name, is_mfa_enabled, mfa_secret FROM users LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>MFA Enabled</th><th>Has Secret</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $hasSec = !empty($row['mfa_secret']) ? 'Yes' : 'No';
        $mfaEn = $row['is_mfa_enabled'] ? 'Yes' : 'No';
        echo "<tr><td>{$row['id']}</td><td>{$row['email']}</td><td>{$row['name']}</td><td>{$mfaEn}</td><td>{$hasSec}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>✗ No users found</p>";
}

// 4. Check MFA API
echo "<h3>4. Testing MFA API Endpoints:</h3>";
echo "<p>Current session ID: " . session_id() . "</p>";
echo "<p>Session may be new - open browser console and call APIs directly to test.</p>";
