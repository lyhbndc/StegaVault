<?php
/**
 * GD Library Checker and Fix Guide
 * File: check-gd.php
 */

echo "<!DOCTYPE html><html><head>
    <link rel="icon" type="image/png" href="Assets/favicon.png"><title>GD Library Check</title>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; max-width: 800px; margin: 0 auto; }
    .box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { border-left: 5px solid #4caf50; }
    .error { border-left: 5px solid #f44336; }
    .warning { border-left: 5px solid #ff9800; }
    .info { border-left: 5px solid #2196f3; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: monospace; }
    h1 { color: #333; }
    h2 { color: #666; margin-top: 0; }
    .step { background: #e3f2fd; padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid #2196f3; }
    .step h3 { margin-top: 0; color: #1976d2; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; color: #d32f2f; }
</style></head><body>";

echo "<h1>🔍 GD Library Diagnostic</h1>";

// ============================================
// CHECK 1: Is GD Loaded?
// ============================================
echo "<div class='box " . (extension_loaded('gd') ? "success" : "error") . "'>";
echo "<h2>Check 1: GD Library Status</h2>";

if (extension_loaded('gd')) {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ GD Library is INSTALLED and LOADED</strong></p>";
    
    $gdInfo = gd_info();
    echo "<h3>GD Information:</h3>";
    echo "<pre>";
    foreach ($gdInfo as $key => $value) {
        echo str_pad($key . ":", 30) . ($value === true ? 'Yes' : ($value === false ? 'No' : $value)) . "\n";
    }
    echo "</pre>";
    
    // Check specific functions
    echo "<h3>Required Functions:</h3>";
    $functions = [
        'imagecreatefromjpeg' => 'Create image from JPEG',
        'imagecreatefrompng' => 'Create image from PNG',
        'imagecreatetruecolor' => 'Create true color image',
        'imagecolorat' => 'Get pixel color',
        'imagesetpixel' => 'Set pixel color',
        'imagejpeg' => 'Save as JPEG',
        'imagepng' => 'Save as PNG'
    ];
    
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    foreach ($functions as $func => $desc) {
        $exists = function_exists($func);
        $icon = $exists ? '✅' : '❌';
        $color = $exists ? 'green' : 'red';
        echo "<tr style='border-bottom: 1px solid #eee;'>";
        echo "<td style='padding: 8px; width: 30px; color: $color;'>$icon</td>";
        echo "<td style='padding: 8px;'><code>$func()</code></td>";
        echo "<td style='padding: 8px; color: #666;'>$desc</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>❌ GD Library is NOT INSTALLED!</strong></p>";
    echo "<p>This is why watermarking doesn't work. GD Library is required for image manipulation.</p>";
}

echo "</div>";

// ============================================
// CHECK 2: PHP Configuration
// ============================================
echo "<div class='box info'>";
echo "<h2>Check 2: PHP Configuration</h2>";

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px; font-weight: bold;'>PHP Version:</td><td style='padding: 8px;'>" . phpversion() . "</td></tr>";
echo "<tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px; font-weight: bold;'>PHP Config File:</td><td style='padding: 8px;'>" . php_ini_loaded_file() . "</td></tr>";
echo "<tr style='border-bottom: 1px solid #eee;'><td style='padding: 8px; font-weight: bold;'>Extensions Directory:</td><td style='padding: 8px;'>" . ini_get('extension_dir') . "</td></tr>";
echo "</table>";

// List all loaded extensions
$extensions = get_loaded_extensions();
sort($extensions);

echo "<h3>All Loaded Extensions (" . count($extensions) . "):</h3>";
echo "<pre>" . implode(", ", $extensions) . "</pre>";

echo "</div>";

// ============================================
// FIX GUIDE
// ============================================
if (!extension_loaded('gd')) {
    echo "<div class='box error'>";
    echo "<h2>🔧 How to Enable GD Library in XAMPP</h2>";
    
    echo "<div class='step'>";
    echo "<h3>Step 1: Locate php.ini File</h3>";
    echo "<p>Find your php.ini file at:</p>";
    echo "<pre>C:\\xampp\\php\\php.ini</pre>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>Step 2: Open php.ini in Notepad</h3>";
    echo "<p>Right-click the file → <strong>Open with Notepad</strong></p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>Step 3: Find GD Extension Line</h3>";
    echo "<p>Press <code>Ctrl + F</code> and search for:</p>";
    echo "<pre>;extension=gd</pre>";
    echo "<p>OR</p>";
    echo "<pre>;extension=php_gd.dll</pre>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>Step 4: Remove the Semicolon (;)</h3>";
    echo "<p><strong>BEFORE:</strong></p>";
    echo "<pre>;extension=gd</pre>";
    echo "<p><strong>AFTER:</strong></p>";
    echo "<pre>extension=gd</pre>";
    echo "<p>The semicolon (;) means the line is commented out (disabled).<br>";
    echo "Removing it enables the extension.</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>Step 5: Save and Close</h3>";
    echo "<p>Save the file (<code>Ctrl + S</code>) and close Notepad.</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>Step 6: Restart Apache</h3>";
    echo "<p>Open <strong>XAMPP Control Panel</strong></p>";
    echo "<p>Click <strong>Stop</strong> next to Apache</p>";
    echo "<p>Wait 2 seconds</p>";
    echo "<p>Click <strong>Start</strong> next to Apache</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>Step 7: Verify It Works</h3>";
    echo "<p>Refresh this page and check if GD shows as ✅ INSTALLED</p>";
    echo "</div>";
    
    echo "<div style='margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 6px;'>";
    echo "<h3 style='margin-top: 0;'>⚠️ Important Notes:</h3>";
    echo "<ul>";
    echo "<li>Make sure you edit the CORRECT php.ini file (the one shown above)</li>";
    echo "<li>Remove the semicolon, don't just add spaces</li>";
    echo "<li>MUST restart Apache for changes to take effect</li>";
    echo "<li>If you can't find <code>;extension=gd</code>, look for <code>;extension=php_gd.dll</code></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    
} else {
    echo "<div class='box success'>";
    echo "<h2>✅ GD Library is Working!</h2>";
    echo "<p>Your watermark system should work now.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Run the complete watermark test: <code>test-complete-watermark.php</code></li>";
    echo "<li>If that passes, try the real upload → download → extract flow</li>";
    echo "</ol>";
    echo "</div>";
}

// ============================================
// QUICK TEST
// ============================================
if (extension_loaded('gd')) {
    echo "<div class='box info'>";
    echo "<h2>🧪 Quick GD Test</h2>";
    
    try {
        // Create a simple test image
        $testImg = imagecreatetruecolor(100, 100);
        $red = imagecolorallocate($testImg, 255, 0, 0);
        imagefill($testImg, 0, 0, $red);
        
        ob_start();
        imagejpeg($testImg, null, 95);
        $imageData = ob_get_clean();
        imagedestroy($testImg);
        
        $size = strlen($imageData);
        
        if ($size > 0) {
            echo "<p style='color: green;'>✅ Successfully created a test image ($size bytes)</p>";
            echo "<p>GD library is fully functional!</p>";
        } else {
            echo "<p style='color: red;'>❌ GD loaded but image creation failed</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error during test: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
}

echo "<hr style='margin: 30px 0;'>";
echo "<p style='text-align: center; color: #999;'>Check completed at " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>
