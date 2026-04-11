<?php

/**
 * Migration: Add Reset Tokens
 * Run this from the command line or browser to add reset_token and reset_expires columns
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

echo "<h1>Database Migration: Add Reset Tokens</h1>";
echo "<pre>";

try {
    $dbInstance = new Database();
    $db = $dbInstance->getConnection();

    // Check if columns already exist
    $check = $db->query("SHOW COLUMNS FROM users LIKE 'reset_token'");

    if ($check && $check->num_rows > 0) {
        echo "Columns already exist. Nothing to do.\n";
    } else {
        // Add columns
        $sql = "ALTER TABLE users 
                ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL AFTER activation_token,
                ADD COLUMN reset_expires DATETIME DEFAULT NULL AFTER reset_token";

        if ($db->query($sql)) {
            echo "Successfully added reset_token and reset_expires columns to users table.\n";
        } else {
            echo "Error adding columns: " . $db->error . "\n";
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='../admin/index.php'>Return to Admin Dashboard</a></p>";
