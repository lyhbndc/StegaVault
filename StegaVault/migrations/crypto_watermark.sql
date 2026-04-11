-- StegaVault - Cryptographic Watermark Migration SQL
-- Run this in phpMyAdmin or MySQL command line

-- Create watermark_crypto_log table
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

-- Add columns to watermark_mappings table
ALTER TABLE watermark_mappings 
ADD COLUMN crypto_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN signature VARCHAR(255) DEFAULT NULL;

-- Add index for signature
ALTER TABLE watermark_mappings 
ADD INDEX idx_signature (signature);
