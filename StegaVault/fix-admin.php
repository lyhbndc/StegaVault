<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin User - StegaVault</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { border-left: 4px solid #4caf50; }
        .error { border-left: 4px solid #f44336; }
        .info { border-left: 4px solid #2196f3; }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        button:hover { background: #5568d3; }
        pre {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔧 Fix Admin User Password</h1>
    
    <?php
    require_once 'includes/db.php';
    
    if (isset($_POST['fix_admin'])) {
        echo '<div class="box info">';
        echo '<h2>Fixing Admin User...</h2>';
        
        // Generate fresh password hash
        $email = 'admin@test.com';
        $password = 'admin123';
        $name = 'Admin User';
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        
        echo '<p>Generated new password hash for: <strong>admin123</strong></p>';
        echo '<pre>' . $newHash . '</pre>';
        
        // Delete old admin
        $db->query("DELETE FROM users WHERE email = 'admin@test.com'");
        echo '<p>✅ Deleted old admin user (if existed)</p>';
        
        // Insert new admin with fresh hash
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param('sss', $email, $newHash, $name);
        
        if ($stmt->execute()) {
            echo '<p style="color: green; font-size: 18px; font-weight: bold;">✅ SUCCESS! Admin user created!</p>';
            
            // Verify it works
            $verify = $db->query("SELECT * FROM users WHERE email = 'admin@test.com'");
            $user = $verify->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                echo '<p style="color: green; font-weight: bold;">✅ Password verification PASSED!</p>';
                echo '<p style="background: #e7f4e7; padding: 15px; border-radius: 8px; margin: 20px 0;">';
                echo '<strong>✅ Login Credentials:</strong><br>';
                echo 'Email: <strong>admin@test.com</strong><br>';
                echo 'Password: <strong>admin123</strong>';
                echo '</p>';
                
                echo '<h3>Next Steps:</h3>';
                echo '<ol>';
                echo '<li>Go to: <a href="admin/login.php">Login Page</a></li>';
                echo '<li>Use: admin@test.com / admin123</li>';
                echo '<li>Should work perfectly now!</li>';
                echo '</ol>';
            } else {
                echo '<p style="color: red;">❌ Password verification still failed! This is very unusual.</p>';
            }
        } else {
            echo '<p style="color: red;">❌ Failed to insert admin user!</p>';
            echo '<p>Error: ' . $stmt->error . '</p>';
        }
        
        echo '</div>';
        
    } else {
        // Show current status
        echo '<div class="box info">';
        echo '<h2>Current Admin User Status</h2>';
        
        $result = $db->query("SELECT * FROM users WHERE email = 'admin@test.com'");
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo '<p>Admin user exists with:</p>';
            echo '<pre>';
            echo 'Email: ' . $user['email'] . "\n";
            echo 'Name: ' . $user['name'] . "\n";
            echo 'Hash: ' . substr($user['password_hash'], 0, 40) . '...';
            echo '</pre>';
            
            // Test current password
            if (password_verify('admin123', $user['password_hash'])) {
                echo '<p style="color: green;">✅ Current password works!</p>';
                echo '<p><strong>You can login now!</strong> Use admin@test.com / admin123</p>';
            } else {
                echo '<p style="color: red;">❌ Current password does NOT work!</p>';
                echo '<p><strong>Click the button below to fix it:</strong></p>';
            }
        } else {
            echo '<p style="color: red;">❌ No admin user found!</p>';
            echo '<p><strong>Click the button below to create one:</strong></p>';
        }
        
        echo '</div>';
        
        echo '<div class="box success">';
        echo '<h2>Fix Admin User</h2>';
        echo '<p>This will:</p>';
        echo '<ul>';
        echo '<li>Delete the old admin@test.com user</li>';
        echo '<li>Create a fresh admin user with a new password hash</li>';
        echo '<li>Set password to: <strong>admin123</strong></li>';
        echo '</ul>';
        
        echo '<form method="POST">';
        echo '<button type="submit" name="fix_admin">🔧 Fix Admin User Now</button>';
        echo '</form>';
        echo '</div>';
    }
    ?>
    
    <div class="box info">
        <h3>Alternative: Manual SQL Fix</h3>
        <p>If the button doesn't work, copy this SQL and run it in phpMyAdmin:</p>
        <pre>
DELETE FROM users WHERE email = 'admin@test.com';

INSERT INTO users (email, password_hash, name, role) VALUES (
    'admin@test.com',
    '<?php echo password_hash('admin123', PASSWORD_BCRYPT); ?>',
    'Admin User',
    'admin'
);

SELECT * FROM users WHERE email = 'admin@test.com';
        </pre>
    </div>
    
</body>
</html>
