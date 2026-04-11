# Cryptographic Watermark System - Documentation

## Overview

The StegaVault Cryptographic Watermark System provides military-grade security for digital asset protection through:

- **AES-256 Encryption**: Sensitive watermark data is encrypted
- **HMAC-SHA256 Signatures**: Ensures authenticity and prevents forgery
- **User-Specific Keys**: Derived using PBKDF2 for enhanced security
- **Tamper Detection**: Any modification invalidates the watermark
- **Forensic Traceability**: Complete audit trail with cryptographic proof

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Download Request                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              CryptoWatermark::generateWatermark()           │
│  • Derives user-specific encryption key (PBKDF2)            │
│  • Encrypts payload with AES-256-CBC                        │
│  • Generates HMAC-SHA256 signature                          │
│  • Creates unique nonce                                     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Watermark::embedWatermark()                    │
│  • Embeds encrypted data using LSB steganography            │
│  • Preserves image quality                                  │
│  • Invisible to human eye                                   │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Database Logging                               │
│  • watermark_mappings (file tracking)                       │
│  • watermark_crypto_log (forensic audit)                    │
└─────────────────────────────────────────────────────────────┘
```

## Watermark Data Structure

### Encrypted Payload
```json
{
  "version": "2.0",
  "user": {
    "id": 123,
    "name": "John Doe",
    "role": "employee",
    "email_hash": "sha256_hash_of_email"
  },
  "file": {
    "id": 456,
    "original_hash": "sha256_of_original_file",
    "mime_type": "image/png"
  },
  "metadata": {
    "timestamp": 1234567890,
    "ip_address": "192.168.1.1",
    "session_id": "unique_session_identifier",
    "download_count": 1,
    "nonce": "random_nonce_32_chars"
  }
}
```

### Cryptographic Wrapper
```json
{
  "version": "2.0",
  "key_id": "user_key_identifier",
  "nonce": "unique_nonce",
  "timestamp": 1234567890,
  "signature": "hmac_sha256_signature",
  "encrypted_payload": "base64_encoded_aes_encrypted_data",
  "public": {
    "user_id": 123,
    "file_id": 456,
    "timestamp": 1234567890
  }
}
```

## API Reference

### CryptoWatermark Class

#### `generateWatermark($userData, $fileData, $metadata)`
Generates a cryptographically signed and encrypted watermark.

**Parameters:**
- `$userData` (array): User information
  - `id` (int): User ID
  - `name` (string): User name
  - `email` (string): User email
  - `role` (string): User role
- `$fileData` (array): File information
  - `id` (int): File ID
  - `path` (string): File path
  - `mime_type` (string): MIME type
- `$metadata` (array): Additional metadata
  - `ip` (string): IP address
  - `session` (string): Session ID
  - `download_count` (int): Download count

**Returns:** Array containing cryptographic watermark or false on failure

**Example:**
```php
$cryptoWatermark = CryptoWatermark::generateWatermark(
    ['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'role' => 'admin'],
    ['id' => 5, 'path' => '/path/to/file.png', 'mime_type' => 'image/png'],
    ['ip' => '127.0.0.1', 'session' => 'abc123', 'download_count' => 1]
);
```

#### `verifyWatermark($watermark, $userData)`
Verifies the authenticity and integrity of a watermark.

**Parameters:**
- `$watermark` (array): Extracted watermark data
- `$userData` (array): User data for key derivation
  - `id` (int): User ID
  - `email` (string): User email

**Returns:** Array with verification result or false on failure

**Example:**
```php
$result = CryptoWatermark::verifyWatermark(
    $extractedWatermark,
    ['id' => 1, 'email' => 'john@example.com']
);

if ($result && $result['valid'] === true) {
    echo "Watermark is authentic!";
}
```

#### `logWatermark($db, $watermark, $fileId, $userId, $watermarkId)`
Logs watermark generation to database for audit trail.

#### `logVerification($db, $signature)`
Logs watermark verification attempt.

#### `generateReport($verificationResult)`
Generates human-readable verification report.

## Database Schema

### watermark_crypto_log
```sql
CREATE TABLE watermark_crypto_log (
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
    INDEX idx_watermark_id (watermark_id)
);
```

## Usage Guide

### 1. Setup
Run the migration script to create the necessary database tables:
```bash
php migrations/add_crypto_watermark_table.php
```

### 2. Automatic Watermarking
Cryptographic watermarks are automatically applied when users download files through `api/download.php`. No additional configuration needed.

### 3. Verification (Web Interface)
Navigate to: `admin/verify_watermark.php`
1. Upload a watermarked image
2. System automatically extracts and verifies the watermark
3. View detailed forensic report

### 4. Verification (API)
```bash
curl -X POST http://localhost/stegavault/api/verify_watermark.php \
  -F "file=@watermarked_image.png" \
  -H "Cookie: PHPSESSID=your_session_id"
```

### 5. Testing
Run the test suite:
```
http://localhost/stegavault/test-crypto-watermark.php
```

## Security Considerations

### Key Management
- Server secret key is defined in `config.php` (JWT_SECRET)
- **Production**: Use environment variables instead of hardcoded secrets
- User keys are derived using PBKDF2 with 10,000 iterations

### Encryption
- Algorithm: AES-256-CBC
- IV: Randomly generated for each watermark
- IV is prepended to ciphertext

### Signatures
- Algorithm: HMAC-SHA256
- Key: Server secret
- Payload: JSON data + nonce + timestamp

### Tamper Detection
Any modification to:
- Encrypted payload
- Signature
- Timestamp
- Nonce

Will cause verification to fail.

### Timestamp Validation
- Watermarks from the future are rejected (5-minute tolerance)
- Prevents backdating attacks
- Ensures temporal integrity

## Forensic Analysis

### What Can Be Proven?
1. **Who** downloaded the file (user ID, name, email hash)
2. **When** it was downloaded (timestamp)
3. **Where** it was downloaded from (IP address)
4. **What** file was downloaded (file ID, hash)
5. **How many times** it was verified

### Non-Repudiation
Users cannot deny downloading a file because:
- Signature requires server secret (only server can generate)
- Encryption requires user-specific key (tied to user credentials)
- Timestamp prevents backdating
- Nonce ensures uniqueness

### Chain of Custody
Complete audit trail in `watermark_crypto_log`:
- Original generation timestamp
- All verification attempts
- Verification count
- Last verification timestamp

## Performance

### Generation
- ~50-100ms for typical watermark generation
- Includes encryption, hashing, and signature

### Embedding
- Same as legacy watermark (LSB steganography)
- ~200-500ms for typical images

### Verification
- ~50-100ms for decryption and signature verification
- Extraction time depends on image size

## Troubleshooting

### "Failed to generate cryptographic watermark"
- Check OpenSSL extension is enabled
- Verify JWT_SECRET is defined in config.php
- Check error logs for details

### "Signature verification failed"
- Watermark may be tampered
- Server secret may have changed
- Check if watermark is from different installation

### "Failed to decrypt watermark payload"
- User credentials may have changed
- Watermark may be corrupted
- Check if correct user data is provided

## Migration from Legacy Watermarks

The system is backward compatible:
- Legacy watermarks can still be extracted
- New downloads automatically use crypto watermarks
- Both types can coexist in the database

To identify crypto watermarks:
```php
if (isset($watermark['crypto'])) {
    // Cryptographic watermark
} else {
    // Legacy watermark
}
```

## Best Practices

1. **Regular Backups**: Backup `watermark_crypto_log` table regularly
2. **Secret Rotation**: Plan for periodic server secret rotation
3. **Monitoring**: Monitor verification failures for security incidents
4. **Audit**: Regularly review watermark logs for anomalies
5. **Testing**: Test verification after any system updates

## Support

For issues or questions:
- Check error logs in `logs/` directory
- Run test suite: `test-crypto-watermark.php`
- Review database logs in `watermark_crypto_log`

## License

Part of StegaVault - Secure Digital Asset Management System
