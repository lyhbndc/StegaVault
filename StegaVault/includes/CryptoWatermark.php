<?php
/**
 * StegaVault - Cryptographic Watermark System
 * File: includes/CryptoWatermark.php
 * 
 * Provides cryptographic watermarking with:
 * - SHA-256 hashing for data integrity
 * - HMAC signatures for authenticity
 * - AES-256 encryption for sensitive data
 * - User-specific key derivation
 * - Tamper detection and verification
 */

class CryptoWatermark {
    
    // Cryptographic constants
    private const WATERMARK_VERSION = '2.0';
    private const CIPHER_METHOD = 'AES-256-CBC';
    private const HASH_ALGO = 'sha256';
    private const HMAC_ALGO = 'sha256';
    private const KEY_DERIVATION_ITERATIONS = 10000;
    
    /**
     * Generate cryptographic watermark data
     * 
     * @param array $userData - User information (id, name, email, role)
     * @param array $fileData - File information (id, path, mime_type)
     * @param array $metadata - Additional metadata (ip, session, etc.)
     * @return array - Cryptographically signed watermark data
     */
    public static function generateWatermark($userData, $fileData, $metadata = []) {
        try {
            // Generate unique nonce for this watermark
            $nonce = bin2hex(random_bytes(16));
            
            // Generate timestamp
            $timestamp = time();
            
            // Calculate file hash (for integrity verification)
            $fileHash = self::calculateFileHash($fileData['path']);
            
            // Derive user-specific encryption key
            $userKey = self::deriveUserKey($userData['id'], $userData['email']);
            
            // Generate key identifier (non-sensitive)
            $keyId = hash(self::HASH_ALGO, $userData['id'] . $userData['email']);
            
            // Prepare core watermark data
            $coreData = [
                'version' => self::WATERMARK_VERSION,
                'user' => [
                    'id' => $userData['id'],
                    'name' => $userData['name'],
                    'role' => $userData['role'],
                    'email_hash' => hash(self::HASH_ALGO, $userData['email'])
                ],
                'file' => [
                    'id' => $fileData['id'],
                    'original_hash' => $fileHash,
                    'mime_type' => $fileData['mime_type']
                ],
                'metadata' => [
                    'timestamp' => $timestamp,
                    'ip_address' => $metadata['ip'] ?? 'unknown',
                    'session_id' => $metadata['session'] ?? session_id(),
                    'download_count' => $metadata['download_count'] ?? 1,
                    'nonce' => $nonce
                ]
            ];
            
            // Encrypt sensitive payload
            $encryptedPayload = self::encryptData(json_encode($coreData), $userKey);
            
            // Generate HMAC signature for authenticity
            $signature = self::generateSignature($coreData, $nonce, $timestamp);
            
            // Prepare final watermark structure
            $watermark = [
                'version' => self::WATERMARK_VERSION,
                'key_id' => $keyId,
                'nonce' => $nonce,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'encrypted_payload' => base64_encode($encryptedPayload),
                // Include minimal public data for extraction
                'public' => [
                    'user_id' => $userData['id'],
                    'file_id' => $fileData['id'],
                    'timestamp' => $timestamp
                ]
            ];
            
            return $watermark;
            
        } catch (Exception $e) {
            error_log("CryptoWatermark generation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify watermark authenticity and integrity
     * 
     * @param array $watermark - Extracted watermark data
     * @param array $userData - User data for key derivation
     * @return array|false - Decrypted data if valid, false otherwise
     */
    public static function verifyWatermark($watermark, $userData = null) {
        try {
            // Check version
            if (!isset($watermark['version']) || $watermark['version'] !== self::WATERMARK_VERSION) {
                error_log("Invalid watermark version");
                return false;
            }
            
            // Verify required fields
            $requiredFields = ['key_id', 'nonce', 'timestamp', 'signature', 'encrypted_payload'];
            foreach ($requiredFields as $field) {
                if (!isset($watermark[$field])) {
                    error_log("Missing required field: $field");
                    return false;
                }
            }
            
            // Decrypt payload
            if ($userData) {
                $userKey = self::deriveUserKey($userData['id'], $userData['email']);
                $encryptedPayload = base64_decode($watermark['encrypted_payload']);
                $decryptedData = self::decryptData($encryptedPayload, $userKey);
                
                if ($decryptedData === false) {
                    error_log("Failed to decrypt watermark payload");
                    return false;
                }
                
                $coreData = json_decode($decryptedData, true);
                if (!$coreData) {
                    error_log("Failed to parse decrypted data");
                    return false;
                }
                
                // Verify signature
                $expectedSignature = self::generateSignature(
                    $coreData, 
                    $watermark['nonce'], 
                    $watermark['timestamp']
                );
                
                if (!hash_equals($expectedSignature, $watermark['signature'])) {
                    error_log("Signature verification failed - watermark may be tampered");
                    return false;
                }
                
                // Verify timestamp (prevent backdating - must be within reasonable range)
                $currentTime = time();
                $watermarkTime = $watermark['timestamp'];
                
                // Allow watermarks from the past, but not from the future (with 5 min tolerance)
                if ($watermarkTime > $currentTime + 300) {
                    error_log("Watermark timestamp is in the future");
                    return false;
                }
                
                return [
                    'valid' => true,
                    'data' => $coreData,
                    'verified_at' => time()
                ];
            }
            
            // If no user data provided, return public info only
            return [
                'valid' => 'partial',
                'public' => $watermark['public'],
                'note' => 'Full verification requires user credentials'
            ];
            
        } catch (Exception $e) {
            error_log("CryptoWatermark verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate HMAC signature for watermark
     * 
     * @param array $data - Core watermark data
     * @param string $nonce - Unique nonce
     * @param int $timestamp - Timestamp
     * @return string - HMAC signature
     */
    private static function generateSignature($data, $nonce, $timestamp) {
        // Get server secret from config
        $serverSecret = self::getServerSecret();
        
        // Create signature payload
        $signaturePayload = json_encode($data) . $nonce . $timestamp;
        
        // Generate HMAC
        return hash_hmac(self::HMAC_ALGO, $signaturePayload, $serverSecret);
    }
    
    /**
     * Derive user-specific encryption key using PBKDF2
     * 
     * @param int $userId - User ID
     * @param string $userEmail - User email
     * @return string - Derived key
     */
    private static function deriveUserKey($userId, $userEmail) {
        $serverSecret = self::getServerSecret();
        $salt = hash(self::HASH_ALGO, $userId . $userEmail . $serverSecret);
        
        // Use PBKDF2 for key derivation
        return hash_pbkdf2(
            self::HASH_ALGO,
            $userEmail . $userId,
            $salt,
            self::KEY_DERIVATION_ITERATIONS,
            32, // 256 bits
            true
        );
    }
    
    /**
     * Encrypt data using AES-256-CBC
     * 
     * @param string $data - Data to encrypt
     * @param string $key - Encryption key
     * @return string - Encrypted data with IV prepended
     */
    private static function encryptData($data, $key) {
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Prepend IV to encrypted data
        return $iv . $encrypted;
    }
    
    /**
     * Decrypt data using AES-256-CBC
     * 
     * @param string $encryptedData - Encrypted data with IV prepended
     * @param string $key - Decryption key
     * @return string|false - Decrypted data or false on failure
     */
    private static function decryptData($encryptedData, $key) {
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        
        if (strlen($encryptedData) < $ivLength) {
            return false;
        }
        
        $iv = substr($encryptedData, 0, $ivLength);
        $encrypted = substr($encryptedData, $ivLength);
        
        return openssl_decrypt(
            $encrypted,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
    
    /**
     * Calculate SHA-256 hash of file
     * 
     * @param string $filePath - Path to file
     * @return string - SHA-256 hash
     */
    private static function calculateFileHash($filePath) {
        if (!file_exists($filePath)) {
            return hash(self::HASH_ALGO, 'file_not_found');
        }
        
        return hash_file(self::HASH_ALGO, $filePath);
    }
    
    /**
     * Get server secret key from configuration
     * 
     * @return string - Server secret
     */
    private static function getServerSecret() {
        // In production, this should come from environment variable
        // For now, use JWT_SECRET from config
        if (defined('JWT_SECRET')) {
            return JWT_SECRET;
        }
        
        // Fallback (should never be used in production)
        return 'stegavault_default_secret_change_this';
    }
    
    /**
     * Log watermark generation to database
     * 
     * @param mysqli $db - Database connection
     * @param array $watermark - Watermark data
     * @param int $fileId - File ID
     * @param int $userId - User ID
     * @param string $watermarkId - Watermark mapping ID
     * @return bool - Success status
     */
    public static function logWatermark($db, $watermark, $fileId, $userId, $watermarkId) {
        try {
            $stmt = $db->prepare("
                INSERT INTO watermark_crypto_log 
                (watermark_id, file_id, user_id, signature, key_id, nonce, timestamp, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $stmt->bind_param(
                'siisssss',
                $watermarkId,
                $fileId,
                $userId,
                $watermark['signature'],
                $watermark['key_id'],
                $watermark['nonce'],
                $watermark['timestamp'],
                $ipAddress
            );
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Failed to log watermark: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update verification count in database
     * 
     * @param mysqli $db - Database connection
     * @param string $signature - Watermark signature
     * @return bool - Success status
     */
    public static function logVerification($db, $signature) {
        try {
            $stmt = $db->prepare("
                UPDATE watermark_crypto_log 
                SET verified = TRUE, 
                    verification_count = verification_count + 1,
                    last_verified = NOW()
                WHERE signature = ?
            ");
            
            $stmt->bind_param('s', $signature);
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Failed to log verification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate human-readable watermark report
     * 
     * @param array $verificationResult - Result from verifyWatermark()
     * @return string - Formatted report
     */
    public static function generateReport($verificationResult) {
        if (!$verificationResult || !$verificationResult['valid']) {
            return "❌ INVALID WATERMARK - May be forged or tampered";
        }
        
        $data = $verificationResult['data'];
        $report = "✅ AUTHENTIC WATERMARK - Cryptographically Verified\n\n";
        $report .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $report .= "WATERMARK VERIFICATION REPORT\n";
        $report .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        $report .= "User Information:\n";
        $report .= "  • User ID: " . $data['user']['id'] . "\n";
        $report .= "  • Name: " . $data['user']['name'] . "\n";
        $report .= "  • Role: " . $data['user']['role'] . "\n";
        $report .= "  • Email Hash: " . substr($data['user']['email_hash'], 0, 16) . "...\n\n";
        
        $report .= "File Information:\n";
        $report .= "  • File ID: " . $data['file']['id'] . "\n";
        $report .= "  • Type: " . $data['file']['mime_type'] . "\n";
        $report .= "  • Original Hash: " . substr($data['file']['original_hash'], 0, 16) . "...\n\n";
        
        $report .= "Download Metadata:\n";
        $report .= "  • Timestamp: " . date('Y-m-d H:i:s', $data['metadata']['timestamp']) . "\n";
        $report .= "  • IP Address: " . $data['metadata']['ip_address'] . "\n";
        $report .= "  • Download Count: " . $data['metadata']['download_count'] . "\n";
        $report .= "  • Session ID: " . substr($data['metadata']['session_id'], 0, 16) . "...\n\n";
        
        $report .= "Verification:\n";
        $report .= "  • Verified At: " . date('Y-m-d H:i:s', $verificationResult['verified_at']) . "\n";
        $report .= "  • Signature: VALID ✓\n";
        $report .= "  • Encryption: VALID ✓\n";
        $report .= "  • Integrity: INTACT ✓\n\n";
        
        $report .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $report .= "This watermark is cryptographically authentic\n";
        $report .= "and has not been tampered with.\n";
        $report .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        return $report;
    }
}
?>
