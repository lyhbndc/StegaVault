<?php
/**
 * StegaVault - Database Migration for Cryptographic Watermarks
 * File: migrations/add_crypto_watermark_table.php
 */

require_once __DIR__ . '/../includes/db.php';

echo "StegaVault - Cryptographic Watermark Migration\n";
echo "=============================================\n\n";

// Get the mysqli connection from the Database object
$mysqli = $db->getConnection();

// Create watermark_crypto_log table
$sql = "
CREATE TABLE IF NOT EXISTS watermark_crypto_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    watermark_id VARCHAR(100) NOT NULL,
    file_id INT NOT NULL,
    user_id INT NOT NULL,
    signature VARCHAR(255) NOT NULL,
    key_id VARCHAR(64) NOT NULL,
    nonce VARCHAR(64) NOT NULL,
    timestamp BIGINT NOT NULL,
    ip_address VARCHAR(45),
    verified BOOLEAN DEFAULT FALSE,
    verification_count INT DEFAULT 0,
    last_verified TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_signature (signature),
    INDEX idx_watermark_id (watermark_id),
    INDEX idx_file_user (file_id, user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

echo "Creating watermark_crypto_log table...\n";

if ($mysqli->query($sql)) {
    echo "✅ Table created successfully!\n\n";
} else {
    echo "❌ Error creating table: " . $mysqli->error . "\n\n";
    exit(1);
}

// Add additional columns to watermark_mappings if needed
$alterSql = "
ALTER TABLE watermark_mappings 
ADD COLUMN IF NOT EXISTS crypto_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS signature VARCHAR(255) DEFAULT NULL,
ADD INDEX IF NOT EXISTS idx_signature (signature);
";

echo "Updating watermark_mappings table...\n";

if ($mysqli->multi_query($alterSql)) {
    // Clear results
    while ($mysqli->next_result()) {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    }
    echo "✅ Table updated successfully!\n\n";
} else {
    // This might fail if columns already exist, which is fine
    echo "⚠️  Note: " . $mysqli->error . "\n\n";
}

echo "Migration completed!\n";
echo "=============================================\n";
echo "You can now use cryptographic watermarks.\n";
echo "The system will automatically use crypto watermarks for all new downloads.\n";

$mysqli->close();
?>
