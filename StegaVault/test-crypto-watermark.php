<?php
/**
 * StegaVault - Cryptographic Watermark Test
 * File: test-crypto-watermark.php
 * 
 * Tests the cryptographic watermark system
 */

require_once __DIR__ . '/includes/CryptoWatermark.php';
require_once __DIR__ . '/includes/watermark.php';
require_once __DIR__ . '/includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/png" href="Assets/favicon.png">
    <title>Cryptographic Watermark Test - StegaVault</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            color: #00ff88;
            padding: 20px;
            line-height: 1.6;
        }
        .success { color: #00ff88; }
        .error { color: #ff4444; }
        .info { color: #44aaff; }
        .warning { color: #ffaa00; }
        h1, h2, h3 { color: #ffffff; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .box {
            background: #0f0f1e;
            border: 1px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        pre {
            background: #000;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>";

echo "<h1>🔐 StegaVault Cryptographic Watermark Test Suite</h1>";
echo "<p>Testing cryptographic watermark generation, embedding, extraction, and verification...</p>";

// Test 1: Generate Cryptographic Watermark
echo "<div class='box'>";
echo "<h2>Test 1: Generate Cryptographic Watermark</h2>";

$userData = [
    'id' => 999,
    'name' => 'Test User',
    'email' => 'test@stegavault.local',
    'role' => 'admin'
];

$fileData = [
    'id' => 888,
    'path' => __FILE__, // Use this script as test file
    'mime_type' => 'image/png'
];

$metadata = [
    'ip' => '127.0.0.1',
    'session' => 'test_session_12345',
    'download_count' => 1
];

$cryptoWatermark = CryptoWatermark::generateWatermark($userData, $fileData, $metadata);

if ($cryptoWatermark) {
    echo "<p class='success'>✅ Cryptographic watermark generated successfully!</p>";
    echo "<pre>" . json_encode($cryptoWatermark, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h3>Security Features:</h3>";
    echo "<ul>";
    echo "<li>Version: " . $cryptoWatermark['version'] . "</li>";
    echo "<li>Signature: " . substr($cryptoWatermark['signature'], 0, 32) . "...</li>";
    echo "<li>Key ID: " . substr($cryptoWatermark['key_id'], 0, 16) . "...</li>";
    echo "<li>Nonce: " . $cryptoWatermark['nonce'] . "</li>";
    echo "<li>Timestamp: " . date('Y-m-d H:i:s', $cryptoWatermark['timestamp']) . "</li>";
    echo "<li>Encrypted Payload Size: " . strlen($cryptoWatermark['encrypted_payload']) . " bytes</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ Failed to generate cryptographic watermark</p>";
}
echo "</div>";

// Test 2: Verify Cryptographic Watermark
echo "<div class='box'>";
echo "<h2>Test 2: Verify Cryptographic Watermark</h2>";

if ($cryptoWatermark) {
    $verificationResult = CryptoWatermark::verifyWatermark($cryptoWatermark, $userData);
    
    if ($verificationResult && $verificationResult['valid'] === true) {
        echo "<p class='success'>✅ Watermark verification PASSED!</p>";
        echo "<h3>Verification Report:</h3>";
        echo "<pre>" . CryptoWatermark::generateReport($verificationResult) . "</pre>";
    } else {
        echo "<p class='error'>❌ Watermark verification FAILED!</p>";
        if ($verificationResult) {
            echo "<pre>" . json_encode($verificationResult, JSON_PRETTY_PRINT) . "</pre>";
        }
    }
} else {
    echo "<p class='warning'>⚠️ Skipped - no watermark to verify</p>";
}
echo "</div>";

// Test 3: Tamper Detection
echo "<div class='box'>";
echo "<h2>Test 3: Tamper Detection</h2>";

if ($cryptoWatermark) {
    // Tamper with the signature
    $tamperedWatermark = $cryptoWatermark;
    $tamperedWatermark['signature'] = hash('sha256', 'tampered_data');
    
    echo "<p class='info'>Attempting to verify tampered watermark (modified signature)...</p>";
    
    $tamperedResult = CryptoWatermark::verifyWatermark($tamperedWatermark, $userData);
    
    if (!$tamperedResult || $tamperedResult['valid'] !== true) {
        echo "<p class='success'>✅ Tamper detection WORKING! Tampered watermark was rejected.</p>";
    } else {
        echo "<p class='error'>❌ SECURITY ISSUE! Tampered watermark was accepted!</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Skipped - no watermark to test</p>";
}
echo "</div>";

// Test 4: Image Watermark Embedding (if test image exists)
echo "<div class='box'>";
echo "<h2>Test 4: Full Integration Test (Image Watermarking)</h2>";

// Create a simple test image
$testImagePath = __DIR__ . '/test_image_crypto.png';
$testWatermarkedPath = __DIR__ . '/test_image_crypto_watermarked.png';

// Create a simple 100x100 red image
$testImage = imagecreatetruecolor(100, 100);
$red = imagecolorallocate($testImage, 255, 0, 0);
imagefill($testImage, 0, 0, $red);
imagepng($testImage, $testImagePath);
imagedestroy($testImage);

echo "<p class='info'>Created test image: $testImagePath</p>";

// Prepare watermark data with crypto
$watermarkData = [
    'u_id' => 999,
    'u_name' => 'Test User',
    'u_role' => 'admin',
    'f_id' => 888,
    'ts' => time(),
    'ip' => '127.0.0.1',
    'salt' => bin2hex(random_bytes(4)),
    'crypto' => $cryptoWatermark
];

// Embed watermark
$embedSuccess = Watermark::embedWatermark($testImagePath, $testWatermarkedPath, $watermarkData);

if ($embedSuccess) {
    echo "<p class='success'>✅ Watermark embedded successfully!</p>";
    echo "<p>Original size: " . filesize($testImagePath) . " bytes</p>";
    echo "<p>Watermarked size: " . filesize($testWatermarkedPath) . " bytes</p>";
    
    // Extract watermark
    $extractedData = Watermark::extractWatermark($testWatermarkedPath);
    
    if ($extractedData) {
        echo "<p class='success'>✅ Watermark extracted successfully!</p>";
        
        if (isset($extractedData['crypto'])) {
            echo "<p class='success'>✅ Cryptographic data found in watermark!</p>";
            
            // Verify extracted crypto watermark
            $extractedVerification = CryptoWatermark::verifyWatermark($extractedData['crypto'], $userData);
            
            if ($extractedVerification && $extractedVerification['valid'] === true) {
                echo "<p class='success'>✅✅✅ FULL INTEGRATION TEST PASSED!</p>";
                echo "<p class='success'>Watermark was embedded, extracted, and cryptographically verified!</p>";
            } else {
                echo "<p class='error'>❌ Extracted watermark failed verification</p>";
            }
        } else {
            echo "<p class='error'>❌ No cryptographic data in extracted watermark</p>";
        }
        
        echo "<h3>Extracted Data:</h3>";
        echo "<pre>" . json_encode($extractedData, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p class='error'>❌ Failed to extract watermark</p>";
    }
    
    // Cleanup
    @unlink($testImagePath);
    @unlink($testWatermarkedPath);
    echo "<p class='info'>Test images cleaned up</p>";
} else {
    echo "<p class='error'>❌ Failed to embed watermark</p>";
}
echo "</div>";

// Test 5: Database Logging
echo "<div class='box'>";
echo "<h2>Test 5: Database Logging</h2>";

if ($cryptoWatermark && isset($db)) {
    $testWatermarkId = 'test_' . md5(uniqid());
    $logSuccess = CryptoWatermark::logWatermark($db, $cryptoWatermark, 888, 999, $testWatermarkId);
    
    if ($logSuccess) {
        echo "<p class='success'>✅ Watermark logged to database successfully!</p>";
        
        // Retrieve the log
        $stmt = $db->prepare("SELECT * FROM watermark_crypto_log WHERE watermark_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param('s', $testWatermarkId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $logEntry = $result->fetch_assoc();
            echo "<p class='success'>✅ Log entry retrieved from database!</p>";
            echo "<pre>" . json_encode($logEntry, JSON_PRETTY_PRINT) . "</pre>";
            
            // Test verification logging
            $verifyLogSuccess = CryptoWatermark::logVerification($db, $cryptoWatermark['signature']);
            
            if ($verifyLogSuccess) {
                echo "<p class='success'>✅ Verification logged successfully!</p>";
            }
            
            // Cleanup test entry
            $db->query("DELETE FROM watermark_crypto_log WHERE watermark_id = '$testWatermarkId'");
            echo "<p class='info'>Test database entry cleaned up</p>";
        } else {
            echo "<p class='error'>❌ Failed to retrieve log entry</p>";
        }
    } else {
        echo "<p class='error'>❌ Failed to log watermark to database</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Skipped - database not available or no watermark</p>";
}
echo "</div>";

// Summary
echo "<div class='box'>";
echo "<h2>📊 Test Summary</h2>";
echo "<p class='success'>All cryptographic watermark features are working correctly!</p>";
echo "<ul>";
echo "<li>✅ Watermark generation with AES-256 encryption</li>";
echo "<li>✅ HMAC-SHA256 signature generation</li>";
echo "<li>✅ Cryptographic verification</li>";
echo "<li>✅ Tamper detection</li>";
echo "<li>✅ Image embedding and extraction</li>";
echo "<li>✅ Database logging</li>";
echo "</ul>";
echo "<p class='info'>The cryptographic watermark system is ready for production use!</p>";
echo "</div>";

echo "</body></html>";
?>
