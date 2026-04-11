<?php
/**
 * Standalone Supabase Connection Test
 * This script tries to connect directly without using any includes.
 */
$host = 'db.scghhuzaphvmilbcpqsm.supabase.co';
$port = '5432';
$user = 'postgres';
$pass = '@OwlopsCo432';
$dbname = 'postgres';

echo "<h3>Supabase Connection Test</h3>";

try {
    echo "Connecting to $host...<br>";
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

    $start = microtime(true);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    $end = microtime(true);

    echo "✅ <b>SUCCESS!</b> Connected in " . round($end - $start, 3) . "s<br>";

    $stmt = $pdo->query("SELECT version()");
    $ver = $stmt->fetchColumn();
    echo "Server Version: $ver<br>";

}
catch (PDOException $e) {
    echo "❌ <b>FAILED</b><br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "Message: " . $e->getMessage() . "<br>";

    if (strpos($e->getMessage(), 'timeout') !== false) {
        echo "<p><i>Tip: This looks like a network timeout. Check if your firewall or ISP is blocking port 5432.</i></p>";
    }
}