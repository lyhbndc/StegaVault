<?php
/**
 * Watermark Extraction Diagnostic Tool
 * File: diagnose-watermark.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/db.php';
require_once 'includes/watermark.php';

echo "<!DOCTYPE html><html><head>
    <link rel="icon" type="image/png" href="icon.png"><title>Watermark Diagnostic</title>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .test { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; }
    .success { border-left: 4px solid #4caf50; }
    .error { border-left: 4px solid #f44336; }
    .info { border-left: 4px solid #2196f3; }
    .warning { border-left: 4px solid #ff9800; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    h1 { color: #333; }
    h2 { color: #666; margin-top: 0; }
</style></head><body>";

echo "<h1>🔍 StegaVault Watermark Diagnostic</h1>";

// ============================================
// MAIN ISSUE CHECK
// ============================================
echo "<div class='test warning'>";
echo "<h2>⚠️ IMPORTANT: Understanding Watermarks</h2>";
echo "<p><strong>Common Mistake:</strong> Trying to extract from ORIGINAL uploaded files</p>";
echo "<ul>";
echo "<li>❌ ORIGINAL files (in uploads/) = NO watermark</li>";
echo "<li>✅ DOWNLOADED files (via download.php) = HAS watermark</li>";
echo "</ul>";
echo "<p><strong>Watermarks are added DURING download, not during upload!</strong></p>";
echo "</div>";

// ============================================
// TEST 1: Quick Watermark Test
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 1: Quick Watermark Test</h2>";

try {
    // Create test image
    $testImg = imagecreatetruecolor(100, 100);
    $white = imagecolorallocate($testImg, 255, 255, 255);
    imagefill($testImg, 0, 0, $white);

    $testOriginal = 'uploads/diagnostic_original.png';
    $testWatermarked = 'uploads/diagnostic_watermarked.png';

    imagepng($testImg, $testOriginal);
    imagedestroy($testImg);

    echo "<p>✅ Created test image: $testOriginal</p>";

    // Test data
    $testData = [
        'user_id' => 999,
        'user_name' => 'Diagnostic Test',
        'file_id' => 888,
        'timestamp' => time(),
        'ip' => '127.0.0.1',
        'date' => date('Y-m-d H:i:s')
    ];

    // Embed
    $embedded = Watermark::embedWatermark($testOriginal, $testWatermarked, $testData);

    if ($embedded) {
        echo "<p style='color: green;'><strong>✅ EMBED SUCCESSFUL</strong></p>";

        // Extract
        $extracted = Watermark::extractWatermark($testWatermarked);

        if ($extracted) {
            echo "<p style='color: green;'><strong>✅ EXTRACT SUCCESSFUL</strong></p>";
            echo "<pre>" . json_encode($extracted, JSON_PRETTY_PRINT) . "</pre>";

            // Verify
            if ($extracted['user_id'] == $testData['user_id']) {
                echo "<p style='color: green; font-size: 18px;'><strong>🎉 WATERMARKING IS WORKING PERFECTLY!</strong></p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Data mismatch detected</p>";
            }
        } else {
            echo "<p style='color: red;'><strong>❌ EXTRACTION FAILED</strong></p>";
            echo "<p>This means there's a problem with the extraction logic</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ EMBED FAILED</strong></p>";
        echo "<p>Check watermark.php code</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ============================================
// TEST 2: Check Real Files
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 2: Check Your Uploaded Files</h2>";

$files = $db->query("SELECT * FROM files ORDER BY id DESC LIMIT 5");

if ($files && $files->num_rows > 0) {
    echo "<p>Checking your recent files:</p>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Filename</th><th>Path</th><th>Has Watermark?</th></tr>";

    while ($file = $files->fetch_assoc()) {
        $hasWatermark = false;

        if (file_exists($file['file_path'])) {
            try {
                $extracted = Watermark::extractWatermark($file['file_path']);
                $hasWatermark = ($extracted !== false);
            } catch (Exception $e) {
                // Ignore
            }
        }

        $status = $hasWatermark ? "✅ YES" : "❌ NO (Original file)";
        echo "<tr>";
        echo "<td>{$file['original_name']}</td>";
        echo "<td>{$file['file_path']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<div style='margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;'>";
    echo "<p><strong>📌 Why do original files say 'NO'?</strong></p>";
    echo "<p>Because watermarks are only added when someone DOWNLOADS the file!</p>";
    echo "<p><strong>To test extraction:</strong></p>";
    echo "<ol>";
    echo "<li>Go to Files page</li>";
    echo "<li>Click 'Download with Watermark' on any file</li>";
    echo "<li>Upload that DOWNLOADED file to Extract page</li>";
    echo "<li>It will show the watermark!</li>";
    echo "</ol>";
    echo "</div>";

} else {
    echo "<p>No files uploaded yet</p>";
}

echo "</div>";

// ============================================
// TEST 3: Download.php Check
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 3: Download API Check</h2>";

$downloadFile = 'api/download.php';
if (file_exists($downloadFile)) {
    echo "<p style='color: green;'>✅ download.php exists</p>";

    // Check if it uses watermarking
    $downloadCode = file_get_contents($downloadFile);

    if (strpos($downloadCode, 'embedWatermark') !== false) {
        echo "<p style='color: green;'>✅ download.php calls embedWatermark()</p>";
    } else {
        echo "<p style='color: red;'>❌ download.php doesn't call embedWatermark()</p>";
        echo "<p>This is the problem! Downloads aren't being watermarked!</p>";
    }

    if (strpos($downloadCode, 'watermark_mappings') !== false) {
        echo "<p style='color: green;'>✅ download.php logs to watermark_mappings table</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ download.php doesn't log watermarks</p>";
    }

} else {
    echo "<p style='color: red;'>❌ download.php NOT FOUND</p>";
}

echo "</div>";

// ============================================
// SOLUTION
// ============================================
echo "<div class='test " . (isset($extracted) && $extracted ? 'success' : 'warning') . "'>";
echo "<h2>💡 Solution & Next Steps</h2>";

if (isset($extracted) && $extracted) {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ Your watermark system is WORKING!</strong></p>";
    echo "<p><strong>To test extraction in the UI:</strong></p>";
    echo "<ol>";
    echo "<li>Upload a file (admin/upload.php or employee/upload.php)</li>";
    echo "<li>Go to Files page</li>";
    echo "<li>Click <strong>'Download with Watermark'</strong> on that file</li>";
    echo "<li>Go to Extract page (admin/extract.php)</li>";
    echo "<li>Upload the <strong>DOWNLOADED</strong> file (not the original)</li>";
    echo "<li>It will extract and show who downloaded it! 🎉</li>";
    echo "</ol>";
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>❌ Watermarking is NOT working</strong></p>";
    echo "<p><strong>The problem is likely:</strong></p>";
    echo "<ol>";
    echo "<li>watermark.php code has errors</li>";
    echo "<li>GD library issues</li>";
    echo "<li>download.php not calling watermark functions</li>";
    echo "</ol>";
    echo "<p>Show me your includes/watermark.php file and I'll fix it!</p>";
}

echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'>Test completed: " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>