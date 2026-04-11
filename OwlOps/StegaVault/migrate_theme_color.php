<?php

/**
 * One-time migration: adds theme_color column to users table.
 * Run once via browser, then delete this file.
 */
require_once __DIR__ . '/includes/db.php';

$result = $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS theme_color VARCHAR(7) DEFAULT '#667eea' AFTER is_verified");
$dbError = $db->error;

if ($result) {
    echo "<p style='font-family:sans-serif; color:green;'>✅ Migration successful! <code>theme_color</code> column added to <code>users</code> table.</p>";
} else {
    echo "<p style='font-family:sans-serif; color:red;'>❌ Migration failed: " . htmlspecialchars($dbError) . "</p>";
}
echo "<p style='font-family:sans-serif;'>You can now delete this file.</p>";
