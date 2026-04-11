<?php
/**
 * Ultimate Watermark Diagnostic - Tests Everything
 * File: ultimate-test.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['name'] = 'Test User';
$_SESSION['email'] = 'test@test.com';

require_once 'includes/watermark.php';

echo "<!DOCTYPE html><html><head><title>Ultimate Watermark Test</title>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
    .box { background: #2d2d2d; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #007acc; }
    .success { border-left-color: #4caf50; }
    .error { border-left-color: #f44336; }
    pre { background: #1e1e1e; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 11px; }
    h2 { color: #4ec9b0; margin: 0 0 10px 0; }
    .step { color: #dcdcaa; }
    .data { color: #ce9178; }
</style></head><body>";

echo "<h1 style='color: #4ec9b0;'>🔬 ULTIMATE WATERMARK DIAGNOSTIC</h1>";

// ============================================
// TEST 1: Create Test Image
// ============================================
echo "<div class='box'><h2>TEST 1: Create Simple Image</h2>";

$img = imagecreatetruecolor(100, 100);
$white = imagecolorallocate($img, 255, 255, 255);
imagefill($img, 0, 0, $white);

$testOriginal = 'uploads/ultimate_original.jpg';
$result = imagejpeg($img, $testOriginal, 100);
imagedestroy($img);

if ($result && file_exists($testOriginal)) {
    echo "<p class='success'>✅ Created: $testOriginal (" . filesize($testOriginal) . " bytes)</p>";
} else {
    echo "<p class='error'>❌ Failed to create test image</p>";
    die("</div></body></html>");
}
echo "</div>";

// ============================================
// TEST 2: Prepare Data
// ============================================
echo "<div class='box'><h2>TEST 2: Prepare Watermark Data</h2>";

$testData = [
    'user_id' => 999,
    'user_name' => 'TEST USER',
    'file_id' => 888,
    'timestamp' => 1234567890,
    'ip' => '192.168.1.1',
    'date' => '2024-12-22 12:00:00'
];

echo "<pre class='data'>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";

$jsonData = json_encode($testData);
$jsonLength = strlen($jsonData);
echo "<p>JSON length: $jsonLength bytes</p>";

$checksum = md5($jsonData);
echo "<p>MD5 checksum: $checksum</p>";

$header = pack('N', $jsonLength) . $checksum;
$fullData = $header . $jsonData;
echo "<p>Total data size: " . strlen($fullData) . " bytes</p>";

// Convert to binary
$binaryData = '';
for ($i = 0; $i < strlen($fullData); $i++) {
    $binaryData .= str_pad(decbin(ord($fullData[$i])), 8, '0', STR_PAD_LEFT);
}
echo "<p>Binary data: " . strlen($binaryData) . " bits</p>";
echo "<p>First 80 bits: <span class='data'>" . substr($binaryData, 0, 80) . "</span></p>";

echo "</div>";

// ============================================
// TEST 3: Manual Embed
// ============================================
echo "<div class='box'><h2>TEST 3: Manual Embed (Bit by Bit)</h2>";

$img = imagecreatefromjpeg($testOriginal);
$width = imagesx($img);
$height = imagesy($img);

echo "<p>Image: {$width}x{$height} pixels</p>";
echo "<p>Capacity: " . ($width * $height * 3) . " bits</p>";
echo "<p>Data needed: " . strlen($binaryData) . " bits</p>";

if (strlen($binaryData) > ($width * $height * 3)) {
    echo "<p class='error'>❌ Image too small!</p>";
    die("</div></body></html>");
}

$dataIndex = 0;
$totalBits = strlen($binaryData);

for ($y = 0; $y < $height && $dataIndex < $totalBits; $y++) {
    for ($x = 0; $x < $width && $dataIndex < $totalBits; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        // Original values for first pixel
        if ($x == 0 && $y == 0) {
            echo "<p>First pixel BEFORE: R=$r, G=$g, B=$b</p>";
        }
        
        // Modify LSB
        if ($dataIndex < $totalBits) {
            $r = ($r & 0xFE) | intval($binaryData[$dataIndex++]);
        }
        if ($dataIndex < $totalBits) {
            $g = ($g & 0xFE) | intval($binaryData[$dataIndex++]);
        }
        if ($dataIndex < $totalBits) {
            $b = ($b & 0xFE) | intval($binaryData[$dataIndex++]);
        }
        
        // Modified values for first pixel
        if ($x == 0 && $y == 0) {
            echo "<p>First pixel AFTER: R=$r, G=$g, B=$b</p>";
            echo "<p>LSBs: R=" . ($r & 1) . ", G=" . ($g & 1) . ", B=" . ($b & 1) . "</p>";
        }
        
        $newColor = imagecolorallocate($img, $r, $g, $b);
        imagesetpixel($img, $x, $y, $newColor);
    }
}

echo "<p class='success'>✅ Embedded $dataIndex bits</p>";

$testWatermarked = 'uploads/ultimate_watermarked.jpg';
imagejpeg($img, $testWatermarked, 100);
imagedestroy($img);

echo "<p>Saved to: $testWatermarked (" . filesize($testWatermarked) . " bytes)</p>";
echo "</div>";

// ============================================
// TEST 4: Manual Extract
// ============================================
echo "<div class='box'><h2>TEST 4: Manual Extract (Bit by Bit)</h2>";

$img = imagecreatefromjpeg($testWatermarked);
$width = imagesx($img);
$height = imagesy($img);

echo "<p>Reading from: $testWatermarked</p>";

// Extract all bits we embedded
$extractedBits = '';
$bitsNeeded = strlen($binaryData);

for ($y = 0; $y < $height && strlen($extractedBits) < $bitsNeeded; $y++) {
    for ($x = 0; $x < $width && strlen($extractedBits) < $bitsNeeded; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        if ($x == 0 && $y == 0) {
            echo "<p>First pixel RGB: R=$r, G=$g, B=$b</p>";
            echo "<p>First pixel LSBs: R=" . ($r & 1) . ", G=" . ($g & 1) . ", B=" . ($b & 1) . "</p>";
        }
        
        $extractedBits .= ($r & 1);
        $extractedBits .= ($g & 1);
        $extractedBits .= ($b & 1);
    }
}

imagedestroy($img);

echo "<p>Extracted: " . strlen($extractedBits) . " bits</p>";
echo "<p>First 80 bits: <span class='data'>" . substr($extractedBits, 0, 80) . "</span></p>";

// Compare
$match = ($binaryData === $extractedBits);
if ($match) {
    echo "<p class='success'>✅ PERFECT MATCH! All bits extracted correctly!</p>";
} else {
    echo "<p class='error'>❌ MISMATCH! Bits don't match!</p>";
    echo "<p>Expected: " . substr($binaryData, 0, 80) . "</p>";
    echo "<p>Got:      " . substr($extractedBits, 0, 80) . "</p>";
    
    // Find first difference
    for ($i = 0; $i < min(strlen($binaryData), strlen($extractedBits)); $i++) {
        if ($binaryData[$i] != $extractedBits[$i]) {
            echo "<p>First diff at bit $i: expected '{$binaryData[$i]}', got '{$extractedBits[$i]}'</p>";
            break;
        }
    }
}

// Try to decode
echo "<h3>Decoding Header...</h3>";

$headerBits = substr($extractedBits, 0, 36 * 8);
$headerBytes = '';
for ($i = 0; $i < strlen($headerBits); $i += 8) {
    $byte = substr($headerBits, $i, 8);
    if (strlen($byte) == 8) {
        $headerBytes .= chr(bindec($byte));
    }
}

$extractedLength = unpack('N', substr($headerBytes, 0, 4))[1];
$extractedChecksum = substr($headerBytes, 4, 32);

echo "<p>Extracted length: $extractedLength bytes (expected: $jsonLength)</p>";
echo "<p>Extracted checksum: $extractedChecksum</p>";
echo "<p>Expected checksum:  $checksum</p>";
echo "<p>Checksums match: " . ($extractedChecksum === $checksum ? "✅ YES" : "❌ NO") . "</p>";

if ($extractedLength == $jsonLength && $extractedChecksum === $checksum) {
    echo "<h3>Decoding JSON Data...</h3>";
    
    $jsonBits = substr($extractedBits, 36 * 8, $jsonLength * 8);
    $jsonBytes = '';
    for ($i = 0; $i < strlen($jsonBits); $i += 8) {
        $byte = substr($jsonBits, $i, 8);
        if (strlen($byte) == 8) {
            $jsonBytes .= chr(bindec($byte));
        }
    }
    
    echo "<p>Extracted JSON: <span class='data'>$jsonBytes</span></p>";
    
    $decoded = json_decode($jsonBytes, true);
    if ($decoded) {
        echo "<pre class='success'>" . json_encode($decoded, JSON_PRETTY_PRINT) . "</pre>";
        echo "<p class='success'>✅ JSON DECODED SUCCESSFULLY!</p>";
    } else {
        echo "<p class='error'>❌ JSON decode failed</p>";
    }
}

echo "</div>";

// ============================================
// TEST 5: Use Watermark Class
// ============================================
echo "<div class='box'><h2>TEST 5: Test Watermark Class Functions</h2>";

// Test embed
echo "<h3>Testing Watermark::embedWatermark()</h3>";
$classWatermarked = 'uploads/ultimate_class_watermarked.jpg';
$embedResult = Watermark::embedWatermark($testOriginal, $classWatermarked, $testData);

if ($embedResult) {
    echo "<p class='success'>✅ embedWatermark() SUCCESS</p>";
    echo "<p>Output: $classWatermarked (" . filesize($classWatermarked) . " bytes)</p>";
} else {
    echo "<p class='error'>❌ embedWatermark() FAILED</p>";
}

// Test extract
echo "<h3>Testing Watermark::extractWatermark()</h3>";
$extractResult = Watermark::extractWatermark($classWatermarked);

if ($extractResult === false) {
    echo "<p class='error'>❌ extractWatermark() FAILED - Returned FALSE</p>";
} else {
    echo "<p class='success'>✅ extractWatermark() SUCCESS</p>";
    echo "<pre class='success'>" . json_encode($extractResult, JSON_PRETTY_PRINT) . "</pre>";
    
    // Verify data
    $allMatch = true;
    foreach ($testData as $key => $value) {
        if (!isset($extractResult[$key]) || $extractResult[$key] != $value) {
            echo "<p class='error'>❌ Field '$key': expected '$value', got '" . ($extractResult[$key] ?? 'NULL') . "'</p>";
            $allMatch = false;
        }
    }
    
    if ($allMatch) {
        echo "<p class='success' style='font-size: 20px;'>🎉 PERFECT! ALL DATA MATCHES!</p>";
    }
}

echo "</div>";

// ============================================
// FINAL VERDICT
// ============================================
echo "<div class='box " . ($extractResult !== false ? "success" : "error") . "'>";
echo "<h2>FINAL VERDICT</h2>";

if ($extractResult !== false && isset($allMatch) && $allMatch) {
    echo "<p style='font-size: 24px; color: #4caf50;'>🎉 WATERMARK SYSTEM WORKS 100%!</p>";
    echo "<p>Your watermark system is fully functional!</p>";
    echo "<h3>Why extraction might fail in your UI:</h3>";
    echo "<ol>";
    echo "<li><strong>Testing wrong files:</strong> Make sure you download via 'Download with Watermark' button</li>";
    echo "<li><strong>JPEG compression:</strong> Check quality setting in download.php (should be 95+)</li>";
    echo "<li><strong>File path issues:</strong> Check if watermarked file is being created in correct location</li>";
    echo "</ol>";
} else {
    echo "<p style='font-size: 24px; color: #f44336;'>❌ WATERMARK SYSTEM HAS ISSUES</p>";
    
    if (!$match) {
        echo "<p><strong>Problem:</strong> Bits are not being preserved through JPEG save/load</p>";
        echo "<p><strong>Solution:</strong> JPEG compression might be too high. Try quality=100</p>";
    }
    
    if ($extractedChecksum !== $checksum) {
        echo "<p><strong>Problem:</strong> Checksum mismatch - data corrupted</p>";
        echo "<p><strong>Solution:</strong> LSB modification isn't working correctly</p>";
    }
}

echo "</div>";

echo "<p style='text-align: center; color: #666; margin-top: 40px;'>Test completed: " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>
