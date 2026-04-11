<?php
require_once 'includes/db.php';

function executeQuery($db, $sql, $description) {
    echo "Executing: $description... ";
    try {
        if ($db->query($sql)) {
            echo "OK\n";
        } else {
            // Check if error is "Duplicate column name" which is fine (already run)
            if ($db->errno == 1060) {
                echo "Skipped (Column exists)\n";
            } else {
                echo "Error: " . $db->error . "\n";
            }
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

// Add username
executeQuery($db, "ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER id", "Adding username column");

// Add status
executeQuery($db, "ALTER TABLE users ADD COLUMN status ENUM('active', 'pending_activation', 'disabled', 'expired') DEFAULT 'pending_activation' AFTER role", "Adding status column");

// Add expiration_date
executeQuery($db, "ALTER TABLE users ADD COLUMN expiration_date DATETIME DEFAULT NULL AFTER status", "Adding expiration_date column");

// Add activation_token
executeQuery($db, "ALTER TABLE users ADD COLUMN activation_token VARCHAR(64) DEFAULT NULL AFTER expiration_date", "Adding activation_token column");

// Set default status for existing users
executeQuery($db, "UPDATE users SET status = 'active' WHERE status IS NULL OR status = ''", "Setting default status for existing users");

// Update existing users to have a username if empty (use name or part of email)
$result = $db->query("SELECT id, email, name FROM users WHERE username IS NULL");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $username = strtolower(explode(' ', $row['name'])[0]) . $row['id']; // Simple generation
        // Or cleaner: email prefix
        $username = explode('@', $row['email'])[0];
        // Ensure uniqueness by appending ID if needed? For now just try email prefix
        
        $db->query("UPDATE users SET username = '$username', status = 'active' WHERE id = " . $row['id']);
        echo "Updated user {$row['id']} with username '$username'\n";
    }
}

echo "Database schema update completed.\n";
?>
