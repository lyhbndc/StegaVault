<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/png" href="icon.png">
    <title>StegaVault - Login Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test {
            background: white;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { border-left: 4px solid #4caf50; }
        .error { border-left: 4px solid #f44336; }
        .info { border-left: 4px solid #2196f3; }
        h2 { margin-top: 0; }
        pre { 
            background: #f9f9f9; 
            padding: 10px; 
            border-radius: 4px;
            overflow-x: auto;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px 10px 0;
        }
        button:hover { background: #5568d3; }
        #result { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🔐 StegaVault Login Test</h1>
    
    <?php
    require_once 'includes/db.php';
    
    echo '<div class="test info">';
    echo '<h2>Database Connection</h2>';
    
    try {
        $conn = $db->getConnection();
        if ($conn) {
            echo '<p style="color: green;">✅ Database connected successfully!</p>';
            echo '<p>Database: <strong>' . DB_NAME . '</strong></p>';
        }
    } catch (Exception $e) {
        echo '<p style="color: red;">❌ Database connection failed!</p>';
        echo '<p>Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    // Check if users table exists
    echo '<div class="test info">';
    echo '<h2>Users Table Check</h2>';
    
    $result = $db->query("SHOW TABLES LIKE 'users'");
    if ($result && $result->num_rows > 0) {
        echo '<p style="color: green;">✅ Users table exists</p>';
        
        // Count users
        $count = $db->query("SELECT COUNT(*) as total FROM users");
        $row = $count->fetch_assoc();
        echo '<p>Total users: <strong>' . $row['total'] . '</strong></p>';
        
    } else {
        echo '<p style="color: red;">❌ Users table NOT found!</p>';
        echo '<p><strong>Fix:</strong> Run the SQL script in phpMyAdmin</p>';
    }
    
    echo '</div>';
    
    // Check admin user
    echo '<div class="test info">';
    echo '<h2>Admin User Check</h2>';
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = 'admin@test.com'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo '<p style="color: green;">✅ Admin user found!</p>';
        echo '<pre>';
        echo 'Email: ' . $user['email'] . "\n";
        echo 'Name: ' . $user['name'] . "\n";
        echo 'Role: ' . $user['role'] . "\n";
        echo 'Password hash: ' . substr($user['password_hash'], 0, 30) . '...';
        echo '</pre>';
        
        // Test password
        echo '<h3>Password Verification Test</h3>';
        $testPassword = 'admin123';
        if (password_verify($testPassword, $user['password_hash'])) {
            echo '<p style="color: green;">✅ Password "admin123" is CORRECT!</p>';
        } else {
            echo '<p style="color: red;">❌ Password "admin123" does NOT match!</p>';
            echo '<p><strong>Fix:</strong> Run this in phpMyAdmin:</p>';
            echo '<pre>';
            echo "DELETE FROM users WHERE email = 'admin@test.com';\n";
            echo "INSERT INTO users (email, password_hash, name, role) VALUES\n";
            echo "('admin@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin');";
            echo '</pre>';
        }
        
    } else {
        echo '<p style="color: red;">❌ Admin user NOT found!</p>';
        echo '<p><strong>Fix:</strong> Run this in phpMyAdmin SQL tab:</p>';
        echo '<pre>';
        echo "INSERT INTO users (email, password_hash, name, role) VALUES\n";
        echo "('admin@test.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin');";
        echo '</pre>';
    }
    
    echo '</div>';
    ?>
    
    <div class="test info">
        <h2>JavaScript Login Test</h2>
        <p>Click the button to test the login API:</p>
        
        <button onclick="testLogin()">Test Login API</button>
        <button onclick="testRegister()">Test Register API</button>
        
        <div id="result"></div>
    </div>
    
    <script>
        async function testLogin() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Testing login...</p>';
            
            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: 'admin@test.com',
                        password: 'admin123'
                    })
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="test ${data.success ? 'success' : 'error'}">
                        <h3>${data.success ? '✅ Login Successful!' : '❌ Login Failed'}</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                        ${data.success ? '<p><a href="admin/dashboard.php">Go to Dashboard →</a></p>' : ''}
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="test error">
                        <h3>❌ Error</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
        
        async function testRegister() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Testing registration...</p>';
            
            const testEmail = 'test' + Date.now() + '@test.com';
            
            try {
                const response = await fetch('api/auth.php?action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: testEmail,
                        password: 'password123',
                        name: 'Test User'
                    })
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="test ${data.success ? 'success' : 'error'}">
                        <h3>${data.success ? '✅ Registration Successful!' : '❌ Registration Failed'}</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                        ${data.success ? '<p>New user created with email: ' + testEmail + '</p>' : ''}
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="test error">
                        <h3>❌ Error</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
    
    <div class="test info">
        <h2>Next Steps</h2>
        <ol>
            <li>Make sure all tests above show ✅ green checkmarks</li>
            <li>If admin user missing, run the SQL fix above</li>
            <li>Click "Test Login API" button - should show success</li>
            <li>If successful, go to <a href="admin/login.php">Login Page</a></li>
        </ol>
    </div>
</body>
</html>
