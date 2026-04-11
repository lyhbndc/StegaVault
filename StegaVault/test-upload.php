<?php
/**
 * Upload Diagnostic
 * File: test-upload.php
 */

session_start();

// Force login for testing
$_SESSION['user_id'] = 3; // Admin user ID from your database
$_SESSION['name'] = 'Test User';
$_SESSION['email'] = 'admin@test.com';

require_once 'includes/db.php';

echo "<!DOCTYPE html><html><head><title>Upload Diagnostic</title>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #2196f3; }
    .success { border-left-color: #4caf50; }
    .error { border-left-color: #f44336; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    h2 { margin-top: 0; color: #333; }
</style></head><body>";

echo "<h1>🔍 Upload System Diagnostic</h1>";

// ============================================
// TEST 1: Check Session
// ============================================
echo "<div class='box'>";
echo "<h2>Test 1: Check Session</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ Session active: User ID = " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ No session</p>";
}
echo "</div>";

// ============================================
// TEST 2: Check Database Connection
// ============================================
echo "<div class='box'>";
echo "<h2>Test 2: Database Connection</h2>";
if ($db) {
    echo "<p style='color: green;'>✅ Database connected</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}
echo "</div>";

// ============================================
// TEST 3: Check Files Table
// ============================================
echo "<div class='box'>";
echo "<h2>Test 3: Check Files in Database</h2>";

$result = $db->query("SELECT COUNT(*) as count FROM files");
$row = $result->fetch_assoc();
$totalFiles = $row['count'];

echo "<p>Total files in database: <strong>$totalFiles</strong></p>";

if ($totalFiles > 0) {
    echo "<p style='color: green;'>✅ Files exist in database</p>";
    
    // Show all files
    $filesResult = $db->query("SELECT * FROM files ORDER BY id DESC LIMIT 10");
    
    echo "<h3>Recent Files:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Filename</th><th>Original Name</th><th>Path</th><th>Size</th><th>Date</th></tr>";
    
    while ($file = $filesResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$file['id']}</td>";
        echo "<td>{$file['user_id']}</td>";
        echo "<td>{$file['filename']}</td>";
        echo "<td>{$file['original_name']}</td>";
        echo "<td>{$file['file_path']}</td>";
        echo "<td>" . round($file['file_size']/1024, 2) . " KB</td>";
        echo "<td>{$file['upload_date']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No files in database yet</p>";
}
echo "</div>";

// ============================================
// TEST 4: Check uploads directory
// ============================================
echo "<div class='box'>";
echo "<h2>Test 4: Check Uploads Directory</h2>";

$uploadsDir = __DIR__ . '/uploads/';
echo "<p>Uploads directory: <code>$uploadsDir</code></p>";

if (is_dir($uploadsDir)) {
    echo "<p style='color: green;'>✅ Directory exists</p>";
    
    // List files in directory
    $files = scandir($uploadsDir);
    $fileCount = count($files) - 2; // Exclude . and ..
    
    echo "<p>Files in directory: <strong>$fileCount</strong></p>";
    
    if ($fileCount > 0) {
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filePath = $uploadsDir . $file;
                $fileSize = filesize($filePath);
                echo "<li>$file (" . round($fileSize/1024, 2) . " KB)</li>";
            }
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>❌ Directory does not exist</p>";
}
echo "</div>";

// ============================================
// TEST 5: Test API Directly
// ============================================
echo "<div class='box'>";
echo "<h2>Test 5: Test Upload API</h2>";

echo "<p><a href='api/upload.php' target='_blank'>Click to test API directly</a></p>";
echo "<p>Should return JSON with your files</p>";
echo "</div>";

// ============================================
// TEST 6: Check for user's files
// ============================================
echo "<div class='box'>";
echo "<h2>Test 6: Check Files for User ID 3</h2>";

$userId = 3;
$stmt = $db->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$userFileCount = $result->num_rows;

echo "<p>Files for user ID $userId: <strong>$userFileCount</strong></p>";

if ($userFileCount > 0) {
    echo "<p style='color: green;'>✅ User has files</p>";
    echo "<pre>";
    while ($file = $result->fetch_assoc()) {
        echo "ID: {$file['id']}\n";
        echo "Filename: {$file['filename']}\n";
        echo "Original: {$file['original_name']}\n";
        echo "Path: {$file['file_path']}\n";
        echo "Size: " . round($file['file_size']/1024, 2) . " KB\n";
        echo "---\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ No files for this user</p>";
}
echo "</div>";

// ============================================
// TEST 7: JavaScript Console Test
// ============================================
echo "<div class='box'>";
echo "<h2>Test 7: Test JavaScript API Call</h2>";

echo "<button onclick='testAPI()' style='padding: 10px 20px; background: #4caf50; color: white; border: none; border-radius: 4px; cursor: pointer;'>Test API Call</button>";
echo "<pre id='apiResult' style='margin-top: 10px;'></pre>";

echo "<script>
async function testAPI() {
    const result = document.getElementById('apiResult');
    result.textContent = 'Loading...';
    
    try {
        const response = await fetch('api/upload.php');
        const data = await response.json();
        result.textContent = JSON.stringify(data, null, 2);
        
        if (data.success) {
            result.style.color = 'green';
        } else {
            result.style.color = 'red';
        }
    } catch (error) {
        result.textContent = 'Error: ' + error.message;
        result.style.color = 'red';
    }
}
</script>";

echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'>Diagnostic completed</p>";
echo "</body></html>";
?>
