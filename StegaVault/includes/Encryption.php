<?php

/**
 * StegaVault - Encryption Helper
 * File: includes/Encryption.php
 * 
 * Handles 'Encryption at Rest' for uploaded files.
 * Uses AES-256-CBC with a secure header to identify encrypted files.
 */

class Encryption
{
    // 32-byte secure key (In production, move this to environment variables)
    // Generated for this specific instance
    private const KEY = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';
    private const CIPHER = 'aes-256-cbc';
    private const HEADER = 'SVENC'; // StegaVault Encrypted

    /**
     * Encrypt a file and save to destination
     */
    public static function encryptFile($sourcePath, $destPath)
    {
        try {
            if (!file_exists($sourcePath)) {
                error_log("Encryption failed: source file does not exist ($sourcePath)");
                return false;
            }

            $content = file_get_contents($sourcePath);
            if ($content === false) {
                error_log("Encryption failed: file_get_contents returned false ($sourcePath)");
                return false;
            }

            $key = hex2bin(self::KEY);
            $ivLen = openssl_cipher_iv_length(self::CIPHER);
            $iv = openssl_random_pseudo_bytes($ivLen);

            // Encrypt content
            $encrypted = openssl_encrypt($content, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
            if ($encrypted === false) {
                error_log("Encryption failed: openssl_encrypt returned false. " . openssl_error_string());
                return false;
            }

            // Format: Header + IV + EncryptedData
            $data = self::HEADER . $iv . $encrypted;

            $putResult = file_put_contents($destPath, $data);
            if ($putResult === false) {
                error_log("Encryption failed: file_put_contents returned false ($destPath)");
                return false;
            }
            return true;
        } catch (Exception $e) {
            error_log("Encryption exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrypt content from a file path
     * Returns the raw binary string of the decrypted file
     */
    public static function decryptFileContent($filePath)
    {
        try {
            if (!file_exists($filePath)) return false;

            $content = file_get_contents($filePath);
            if ($content === false) return false;

            // Check if file has our encryption header
            $headerLen = strlen(self::HEADER);
            if (substr($content, 0, $headerLen) !== self::HEADER) {
                // Not encrypted or using old format -> Return original content (Backward Compatibility)
                return $content;
            }

            $key = hex2bin(self::KEY);
            $ivLen = openssl_cipher_iv_length(self::CIPHER);

            // Extract IV
            $iv = substr($content, $headerLen, $ivLen);

            // Extract Encrypted Data
            $encryptedData = substr($content, $headerLen + $ivLen);

            // Decrypt
            $decrypted = openssl_decrypt($encryptedData, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

            return $decrypted;
        } catch (Exception $e) {
            error_log("Decryption failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrypt a file and save to a temporary path (for libraries that need a file path)
     */
    public static function decryptToTemp($sourcePath)
    {
        $content = self::decryptFileContent($sourcePath);
        if ($content === false) return false;

        $tmpDir = __DIR__ . '/../tmp/';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0777, true);

        $tempPath = tempnam($tmpDir, 'sv_dec_');
        if (file_put_contents($tempPath, $content) === false) {
            return false;
        }

        return $tempPath;
    }
}
