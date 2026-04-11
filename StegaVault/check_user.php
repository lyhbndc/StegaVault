<?php
/**
 * User Existence Check
 */
require_once __DIR__ . '/includes/db.php';

$email = 'kuznets.calleja@gmail.com';

echo "<h3>Searching for user: $email</h3>";

try {
    $stmt = $db->prepare("SELECT id, email, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch_assoc();

    if ($user) {
        echo "✅ <b>USER FOUND!</b><br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Status: " . $user['status'] . "<br>";
    }
    else {
        echo "❌ <b>USER NOT FOUND</b> in the current database.<br>";
        echo "<p>This means you might need to run the SQL import of <b>stegavault_for_supabase.sql</b> in the Supabase SQL Editor.</p>";
    }
}
catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}