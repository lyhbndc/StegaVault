<?php
/**
 * StegaVault - API Diagnostic Tool
 * File: test-projects.php
 * 
 * This will show you EXACTLY what's wrong
 */

// Enable ALL error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>StegaVault Projects API Diagnostic</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .test { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; }
    .success { border-left: 4px solid #4caf50; }
    .error { border-left: 4px solid #f44336; }
    .info { border-left: 4px solid #2196f3; }
    h2 { margin-top: 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .status { font-weight: bold; }
</style>";

// ============================================
// TEST 1: Session Check
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 1: Session Status</h2>";

session_start();

if (isset($_SESSION['user_id'])) {
    echo "<p class='status' style='color: green;'>✅ Session Active</p>";
    echo "<pre>";
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Email: " . ($_SESSION['email'] ?? 'Not set') . "\n";
    echo "Name: " . ($_SESSION['name'] ?? 'Not set') . "\n";
    echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
    echo "</pre>";
} else {
    echo "<p class='status' style='color: red;'>❌ No Active Session</p>";
    echo "<p>You need to login first!</p>";
    echo "<a href='admin/login.php'>Go to Login</a>";
    exit;
}
echo "</div>";

// ============================================
// TEST 2: Database Connection
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 2: Database Connection</h2>";

try {
    require_once 'includes/db.php';
    
    if ($db) {
        echo "<p class='status' style='color: green;'>✅ Database Connected</p>";
        echo "<pre>Database object exists and is ready</pre>";
    } else {
        echo "<p class='status' style='color: red;'>❌ Database connection is NULL</p>";
    }
} catch (Exception $e) {
    echo "<p class='status' style='color: red;'>❌ Database Error</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
echo "</div>";

// ============================================
// TEST 3: Check Tables Exist
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 3: Check Required Tables</h2>";

$tables = ['projects', 'project_members', 'users', 'files'];
$allTablesExist = true;

foreach ($tables as $table) {
    $result = $db->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' MISSING!</p>";
        $allTablesExist = false;
    }
}

if (!$allTablesExist) {
    echo "<p><strong>You need to run the SQL file to create missing tables!</strong></p>";
}
echo "</div>";

// ============================================
// TEST 4: Check Users Table
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 4: Check Users</h2>";

$usersResult = $db->query("SELECT id, name, email, role FROM users");

if ($usersResult) {
    echo "<p style='color: green;'>✅ Found " . $usersResult->num_rows . " users</p>";
    echo "<pre>";
    while ($user = $usersResult->fetch_assoc()) {
        echo "ID: {$user['id']} | Name: {$user['name']} | Email: {$user['email']} | Role: {$user['role']}\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Error reading users: " . $db->error . "</p>";
}
echo "</div>";

// ============================================
// TEST 5: Try Creating a Test Project
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 5: Try Creating Test Project</h2>";

$testName = "Diagnostic Test Project " . date('H:i:s');
$testDesc = "This is a test project created by the diagnostic tool";
$testColor = "#6366f1";
$userId = $_SESSION['user_id'];

echo "<p>Attempting to create project: <strong>$testName</strong></p>";

try {
    // Check if we can prepare statement
    $stmt = $db->prepare("INSERT INTO projects (name, description, color, created_by, status) VALUES (?, ?, ?, ?, 'active')");
    
    if (!$stmt) {
        echo "<p style='color: red;'>❌ Prepare failed: " . $db->error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Prepare statement successful</p>";
        
        // Try to bind parameters
        $stmt->bind_param('sssi', $testName, $testDesc, $testColor, $userId);
        echo "<p style='color: green;'>✅ Bind parameters successful</p>";
        
        // Try to execute
        if ($stmt->execute()) {
            $projectId = $db->insert_id;
            echo "<p style='color: green;'>✅ Project created! ID: $projectId</p>";
            
            // Try to add owner
            $ownerStmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')");
            $ownerStmt->bind_param('ii', $projectId, $userId);
            
            if ($ownerStmt->execute()) {
                echo "<p style='color: green;'>✅ Owner added to project</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to add owner: " . $ownerStmt->error . "</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Execute failed: " . $stmt->error . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// ============================================
// TEST 6: Check Existing Projects
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 6: Check Existing Projects</h2>";

$projectsResult = $db->query("SELECT * FROM projects ORDER BY id DESC LIMIT 5");

if ($projectsResult) {
    echo "<p style='color: green;'>✅ Found " . $projectsResult->num_rows . " projects</p>";
    
    if ($projectsResult->num_rows > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Color</th><th>Created By</th><th>Status</th></tr>";
        
        while ($project = $projectsResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$project['id']}</td>";
            echo "<td>{$project['name']}</td>";
            echo "<td>{$project['description']}</td>";
            echo "<td style='background: {$project['color']}; color: white;'>{$project['color']}</td>";
            echo "<td>{$project['created_by']}</td>";
            echo "<td>{$project['status']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No projects found yet.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Error: " . $db->error . "</p>";
}
echo "</div>";

// ============================================
// TEST 7: Test API Endpoint Directly
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 7: Test API Endpoint</h2>";

echo "<p>Testing: <a href='api/projects.php?action=all' target='_blank'>api/projects.php?action=all</a></p>";
echo "<p>Click the link above to see the raw API response</p>";

echo "<p>Expected response format:</p>";
echo "<pre>";
echo json_encode([
    'success' => true,
    'data' => [
        'projects' => [
            [
                'id' => 1,
                'name' => 'Example Project',
                'file_count' => 0,
                'member_count' => 1
            ]
        ]
    ]
], JSON_PRETTY_PRINT);
echo "</pre>";

echo "</div>";

// ============================================
// TEST 8: Check Project Members
// ============================================
echo "<div class='test info'>";
echo "<h2>Test 8: Check Project Members</h2>";

$membersResult = $db->query("
    SELECT pm.*, p.name as project_name, u.name as user_name 
    FROM project_members pm
    JOIN projects p ON pm.project_id = p.id
    JOIN users u ON pm.user_id = u.id
    LIMIT 10
");

if ($membersResult) {
    echo "<p style='color: green;'>✅ Found " . $membersResult->num_rows . " project memberships</p>";
    
    if ($membersResult->num_rows > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Project</th><th>User</th><th>Role</th></tr>";
        
        while ($member = $membersResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$member['project_name']}</td>";
            echo "<td>{$member['user_name']}</td>";
            echo "<td>{$member['role']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>❌ Error: " . $db->error . "</p>";
}
echo "</div>";

// ============================================
// SUMMARY
// ============================================
echo "<div class='test " . ($allTablesExist ? "success" : "error") . "'>";
echo "<h2>Summary & Next Steps</h2>";

if ($allTablesExist) {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ All tests passed!</strong></p>";
    echo "<p>Your database is set up correctly. If you're still having issues:</p>";
    echo "<ol>";
    echo "<li>Clear your browser cache (Ctrl + Shift + Delete)</li>";
    echo "<li>Make sure you're logged in as admin</li>";
    echo "<li>Check the browser console (F12) for JavaScript errors</li>";
    echo "<li>Try creating a project from <a href='admin/projects.php'>admin/projects.php</a></li>";
    echo "</ol>";
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>❌ Some tests failed</strong></p>";
    echo "<p>Please fix the issues above first:</p>";
    echo "<ol>";
    echo "<li>Run the SQL file in phpMyAdmin to create missing tables</li>";
    echo "<li>Make sure database credentials in includes/db.php are correct</li>";
    echo "<li>Refresh this page after fixing</li>";
    echo "</ol>";
}

echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; color: #666;'>Diagnostic completed at " . date('Y-m-d H:i:s') . "</p>";
?>
