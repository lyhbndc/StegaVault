<?php
/**
 * Emergency Account Unlock & Password Reset
 */
require_once __DIR__ . '/includes/db.php';

$email = 'kuznets.calleja@gmail.com';
$newPassword = 'Password123!';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

echo "<h3>Fixing account: $email</h3>";

try {
    // 1. Update status and password
    $stmt = $db->prepare("UPDATE users SET status = 'active', password_hash = ? WHERE email = ?");
    $stmt->execute([$newHash, $email]);

    if ($stmt->rowCount() > 0) {
        echo "✅ <b>Account Unlocked!</b> Status is now 'active'.<br>";
        echo "✅ <b>Password Reset!</b> Your new password is: <code>$newPassword</code><br>";
        echo "<p>Try logging in now at <a href='/'>localhost:8000</a></p>";
    }
    else {
        echo "❌ <b>FAILED</b>. User not found. Did you import the data yet?";
    }

}
catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}