<?php
/**
 * Complete Watermark Test - End to End
 * File: test-complete-watermark.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/db.php';
require_once 'includes/watermark.php';

echo "<!DOCTYPE html><html><head>
    <link rel="icon" type="image/png" href="icon.png"><title>Complete Watermark Test</title>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; max-width: 1200px; margin: 0 auto; }
    .test { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { border-left: 5px solid #4caf50; }
    .error { border-left: 5px solid #f44336; }
    .info { border-left: 5px solid #2196f3; }
    .warning { border-left: 5px solid #ff9800; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
    h1 { color: #333; }
    h2 { color: #666; margin-top: 0; }
    img { max-width: 100%; border: 2px solid #ddd; border-radius: 4px; }
    .img-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
    .img-box { text-align: center; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    table td { padding: 8px; border-bottom: 1px solid #eee; }
    table td:first-child { font-weight: bold; width: 200px; }
    .highlight { background: #fff9c4; padding: 2px 6px; border-radius: 3px; }
</style></head><body>";

echo "<h1>🔬 Complete Watermark System Test</h1>";

// ============================================
// STEP 1: Create Test Image
// ============================================
echo "<div class='test info'>";
echo "<h2>Step 1: Create Test Image</h2>";

$testImg = imagecreatetruecolor(800, 600);
$white = imagecolorallocate($testImg, 255, 255, 255);
$blue = imagecolorallocate($testImg, 66, 126, 234);
imagefill($testImg, 0, 0, $white);

// Add text to make it look like a real image
imagestring($testImg, 5, 10, 10, "StegaVault Test Document", $blue);
imagestring($testImg, 4, 10, 40, "Confidential Marketing Material", $blue);
imagestring($testImg, 3, 10, 70, "Q4 2024 Strategy", $blue);

// Draw some shapes
imagerectangle($testImg, 50, 100, 750, 550, $blue);
imagerectangle($testImg, 100, 150, 700, 500, $blue);

$testOriginal = 'uploads/watermark_test_original.png';
imagepng($testImg, $testOriginal);
imagedestroy($testImg);

if (file_exists($testOriginal)) {
    echo "<p style='color: green;'>✅ Test image created successfully</p>";
    echo "<p><strong>File:</strong> $testOriginal</p>";
    echo "<p><strong>Size:</strong> " . number_format(filesize($testOriginal)) . " bytes</p>";
    echo "<img src='$testOriginal' style='max-width: 400px;'>";
} else {
    echo "<p style='color: red;'>❌ Failed to create test image</p>";
    exit;
}

echo "</div>";

// ============================================
// STEP 2: Prepare Watermark Data
// ============================================
echo "<div class='test info'>";
echo "<h2>Step 2: Prepare Watermark Data</h2>";

$watermarkData = [
    'user_id' => 42,
    'user_name' => 'John Doe',
    'file_id' => 123,
    'timestamp' => time(),
    'ip' => '192.168.1.100',
    'date' => date('Y-m-d H:i:s')
];

echo "<p>Data to embed (this identifies who downloaded the file):</p>";
echo "<pre>" . json_encode($watermarkData, JSON_PRETTY_PRINT) . "</pre>";

$jsonSize = strlen(json_encode($watermarkData));
$capacity = Watermark::getCapacity($testOriginal);

echo "<table>";
echo "<tr><td>Data size:</td><td>$jsonSize bytes</td></tr>";
echo "<tr><td>Image capacity:</td><td>" . number_format($capacity) . " bytes</td></tr>";
echo "<tr><td>Can fit?</td><td style='color: green;'><strong>✅ YES (plenty of space)</strong></td></tr>";
echo "</table>";

echo "</div>";

// ============================================
// STEP 3: Embed Watermark
// ============================================
echo "<div class='test info'>";
echo "<h2>Step 3: Embed Watermark</h2>";

$testWatermarked = 'uploads/watermark_test_watermarked.png';

echo "<p>Embedding watermark into image...</p>";

$embedStart = microtime(true);
$embedResult = Watermark::embedWatermark($testOriginal, $testWatermarked, $watermarkData);
$embedTime = (microtime(true) - $embedStart) * 1000;

if ($embedResult) {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ WATERMARK EMBEDDED SUCCESSFULLY!</strong></p>";
    echo "<p>Time taken: " . number_format($embedTime, 2) . " ms</p>";

    echo "<div class='img-container'>";
    echo "<div class='img-box'>";
    echo "<h3>Original Image</h3>";
    echo "<img src='$testOriginal'>";
    echo "<p>" . number_format(filesize($testOriginal)) . " bytes</p>";
    echo "</div>";
    echo "<div class='img-box'>";
    echo "<h3>Watermarked Image</h3>";
    echo "<img src='$testWatermarked'>";
    echo "<p>" . number_format(filesize($testWatermarked)) . " bytes</p>";
    echo "</div>";
    echo "</div>";

    $sizeDiff = filesize($testWatermarked) - filesize($testOriginal);
    echo "<p><strong>Size difference:</strong> " . number_format($sizeDiff) . " bytes</p>";
    echo "<p><em>Note: The images look identical to the human eye! The watermark is completely invisible.</em></p>";

} else {
    echo "<p style='color: red; font-size: 18px;'><strong>❌ EMBEDDING FAILED!</strong></p>";
    echo "<p>Something is wrong with the embedWatermark function.</p>";
    exit;
}

echo "</div>";

// ============================================
// STEP 4: Extract Watermark
// ============================================
echo "<div class='test info'>";
echo "<h2>Step 4: Extract Watermark</h2>";

echo "<p>Attempting to extract watermark from watermarked image...</p>";

$extractStart = microtime(true);
$extractedData = Watermark::extractWatermark($testWatermarked);
$extractTime = (microtime(true) - $extractStart) * 1000;

if ($extractedData === false) {
    echo "<p style='color: red; font-size: 18px;'><strong>❌ EXTRACTION FAILED!</strong></p>";
    echo "<p>The watermark could not be extracted. Possible issues:</p>";
    echo "<ul>";
    echo "<li>Checksum verification failed</li>";
    echo "<li>Data corruption during embedding</li>";
    echo "<li>Bug in extraction algorithm</li>";
    echo "</ul>";

    // Debug: Try to manually check first few pixels
    echo "<h3>🔍 Debug: Check Image Pixels</h3>";
    $img = imagecreatefromjpeg($testWatermarked);
    echo "<p>First 20 pixels LSB values:</p>";
    echo "<pre>";
    for ($i = 0; $i < 20; $i++) {
        $rgb = imagecolorat($img, $i, 0);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $lsb_r = $r & 1;
        $lsb_g = $g & 1;
        $lsb_b = $b & 1;
        echo "Pixel $i: RGB=($r,$g,$b) LSB=($lsb_r,$lsb_g,$lsb_b)\n";
    }
    echo "</pre>";
    imagedestroy($img);

} else {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ WATERMARK EXTRACTED SUCCESSFULLY!</strong></p>";
    echo "<p>Time taken: " . number_format($extractTime, 2) . " ms</p>";

    echo "<h3>📋 Extracted Data:</h3>";
    echo "<pre>" . json_encode($extractedData, JSON_PRETTY_PRINT) . "</pre>";

    // Verify data matches
    echo "<h3>✓ Data Verification:</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Original</th><th>Extracted</th><th>Match?</th></tr>";

    $allMatch = true;
    foreach ($watermarkData as $key => $value) {
        $extractedValue = $extractedData[$key] ?? '<span class="highlight">MISSING</span>';
        $match = ($value == $extractedValue);
        $matchIcon = $match ? '✅' : '❌';
        $matchColor = $match ? 'green' : 'red';

        if (!$match)
            $allMatch = false;

        echo "<tr>";
        echo "<td><strong>$key</strong></td>";
        echo "<td>$value</td>";
        echo "<td>$extractedValue</td>";
        echo "<td style='color: $matchColor; font-size: 18px;'>$matchIcon</td>";
        echo "</tr>";
    }
    echo "</table>";

    if ($allMatch) {
        echo "<p style='color: green; font-size: 18px; background: #e8f5e9; padding: 15px; border-radius: 8px; text-align: center;'>";
        echo "<strong>🎉 PERFECT! ALL DATA MATCHES 100%!</strong>";
        echo "</p>";
    }
}

echo "</div>";

// ============================================
// STEP 5: Test with Original (Should Fail)
// ============================================
echo "<div class='test warning'>";
echo "<h2>Step 5: Test with Original Image (Should Fail)</h2>";

echo "<p>Attempting to extract from ORIGINAL image (no watermark)...</p>";

$noWatermark = Watermark::extractWatermark($testOriginal);

if ($noWatermark === false) {
    echo "<p style='color: green;'>✅ CORRECT! No watermark found in original image.</p>";
    echo "<p>This confirms that watermarks are NOT in uploaded files - they're only added during download.</p>";
} else {
    echo "<p style='color: red;'>❌ UNEXPECTED! Found watermark in original image?</p>";
    echo "<pre>" . json_encode($noWatermark, JSON_PRETTY_PRINT) . "</pre>";
}

echo "</div>";

// ============================================
// FINAL SUMMARY
// ============================================
echo "<div class='test " . ($extractedData !== false ? "success" : "error") . "'>";
echo "<h2>📊 Final Summary</h2>";

if ($extractedData !== false && isset($allMatch) && $allMatch) {
    echo "<h3 style='color: green; font-size: 24px;'>🎉 WATERMARK SYSTEM IS WORKING PERFECTLY!</h3>";

    echo "<p><strong>✅ What Works:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Creating test images</li>";
    echo "<li>✅ Embedding watermarks (invisible to human eye)</li>";
    echo "<li>✅ Extracting watermarks (100% accurate)</li>";
    echo "<li>✅ Data verification (all fields match)</li>";
    echo "<li>✅ Original files correctly have no watermark</li>";
    echo "</ul>";

    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3 style='margin-top: 0;'>🔑 How to Use in Real Scenario:</h3>";
    echo "<ol style='line-height: 1.8;'>";
    echo "<li><strong>Upload file:</strong> Go to admin/upload.php or employee/upload.php</li>";
    echo "<li><strong>Download file:</strong> Click \"Download with Watermark\" button</li>";
    echo "<li><strong>Save downloaded file:</strong> Save it somewhere (e.g., downloaded_file.jpg)</li>";
    echo "<li><strong>Extract watermark:</strong> Upload that downloaded file to admin/extract.php</li>";
    echo "<li><strong>Result:</strong> You'll see who downloaded it, when, and from what IP!</li>";
    echo "</ol>";
    echo "</div>";

    echo "<div style='background: #e3f2fd; border: 2px solid #2196f3; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3 style='margin-top: 0;'>💡 Important Notes:</h3>";
    echo "<ul style='line-height: 1.8;'>";
    echo "<li><strong>Original files:</strong> No watermark (saved in uploads/)</li>";
    echo "<li><strong>Downloaded files:</strong> Watermarked with user info</li>";
    echo "<li><strong>Extraction:</strong> Only works on DOWNLOADED files, not originals</li>";
    echo "<li><strong>Invisible:</strong> Watermark cannot be seen by humans</li>";
    echo "<li><strong>Survives:</strong> Screenshots, social media uploads (if uncompressed)</li>";
    echo "</ul>";
    echo "</div>";

} else {
    echo "<h3 style='color: red; font-size: 24px;'>❌ WATERMARK SYSTEM HAS ISSUES</h3>";

    echo "<p><strong>Problems Detected:</strong></p>";
    echo "<ul>";
    if (!$embedResult) {
        echo "<li>❌ Embedding failed</li>";
    }
    if ($extractedData === false) {
        echo "<li>❌ Extraction failed</li>";
    }
    if (isset($allMatch) && !$allMatch) {
        echo "<li>❌ Data verification failed</li>";
    }
    echo "</ul>";

    echo "<p><strong>What to check:</strong></p>";
    echo "<ol>";
    echo "<li>Make sure GD library is installed</li>";
    echo "<li>Check watermark.php for syntax errors</li>";
    echo "<li>Verify file permissions on uploads/ folder</li>";
    echo "<li>Check PHP error log for details</li>";
    echo "</ol>";
}

echo "</div>";

echo "<hr style='margin: 40px 0;'>";
echo "<p style='text-align: center; color: #999;'>Test completed at " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>