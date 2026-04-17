<?php
/**
 * StegaVault - Employee Profile Page
 * File: employee/profile.php
 */

session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit;
}

$user = [
    'id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role']
];

// Get full user info from database
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$userDetails = $stmt->get_result()->fetch_assoc();

$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = 'All fields are required';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 12) {
        $message = 'Password must be at least 12 characters';
        $messageType = 'error';
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $message = 'Password must contain at least one uppercase letter';
        $messageType = 'error';
    } elseif (!preg_match('/[a-z]/', $newPassword)) {
        $message = 'Password must contain at least one lowercase letter';
        $messageType = 'error';
    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $message = 'Password must contain at least one number';
        $messageType = 'error';
    } elseif (!preg_match('/[\W_]/', $newPassword)) {
        $message = 'Password must contain at least one special character';
        $messageType = 'error';
    } elseif (!password_verify($currentPassword, $userDetails['password_hash'])) {
        $message = 'Current password is incorrect';
        $messageType = 'error';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param('si', $newHash, $user['id']);
        
        if ($stmt->execute()) {
            $message = 'Password changed successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to change password';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - StegaVault</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <style>
        .employee-header {
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
        }
        
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .profile-header {
            text-align: center;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white;
            font-size: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-weight: 700;
        }
        
        .profile-name {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .profile-email {
            color: #666;
            font-size: 16px;
        }
        
        .info-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            width: 200px;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .role-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4caf50;
        }
        
        .btn-submit {
            padding: 12px 30px;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            cursor: pointer;
            color: #888;
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }
        
        .password-toggle-btn:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <header class="dashboard-header employee-header">
        <div class="header-left">
            <div class="logo">🔐 StegaVault</div>
            <h1>My Profile</h1>
        </div>
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($user['role']); ?></div>
            </div>
            <a href="dashboard.php" class="btn-logout" style="background: #4caf50;">Dashboard</a>
            <a href="../admin/logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <div class="dashboard-content">
        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <div class="profile-name"><?php echo htmlspecialchars($userDetails['name']); ?></div>
                    <div class="profile-email"><?php echo htmlspecialchars($userDetails['email']); ?></div>
                </div>
                
                <h3 style="margin-bottom: 20px;">Account Information</h3>
                
                <div class="info-row">
                    <div class="info-label">User ID</div>
                    <div class="info-value"><?php echo $userDetails['id']; ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($userDetails['email']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($userDetails['name']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Role</div>
                    <div class="info-value">
                        <span class="role-badge"><?php echo strtoupper($userDetails['role']); ?></span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Account Created</div>
                    <div class="info-value"><?php echo date('F d, Y', strtotime($userDetails['created_at'])); ?></div>
                </div>
            </div>
            
            <div class="profile-card">
                <h3 style="margin-bottom: 20px;">🔒 Change Password</h3>
                
                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="password-toggle-btn material-symbols-outlined" onclick="togglePasswordVisibility('current_password', this)">visibility_off</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="new_password" name="new_password" minlength="12" required oninput="checkPolicy(this.value)">
                            <button type="button" class="password-toggle-btn material-symbols-outlined" onclick="togglePasswordVisibility('new_password', this)">visibility_off</button>
                        </div>
                        <div id="policyChecklist" style="display:none; margin-top:8px; display:grid; grid-template-columns:1fr 1fr; gap:4px 12px; font-size:12px;">
                            <span id="pc_len"   style="display:flex;align-items:center;gap:4px;color:#888;"><span class="material-symbols-outlined" style="font-size:14px;">cancel</span>12+ characters</span>
                            <span id="pc_upper" style="display:flex;align-items:center;gap:4px;color:#888;"><span class="material-symbols-outlined" style="font-size:14px;">cancel</span>Uppercase (A-Z)</span>
                            <span id="pc_lower" style="display:flex;align-items:center;gap:4px;color:#888;"><span class="material-symbols-outlined" style="font-size:14px;">cancel</span>Lowercase (a-z)</span>
                            <span id="pc_num"   style="display:flex;align-items:center;gap:4px;color:#888;"><span class="material-symbols-outlined" style="font-size:14px;">cancel</span>Number (0-9)</span>
                            <span id="pc_spec"  style="display:flex;align-items:center;gap:4px;color:#888;"><span class="material-symbols-outlined" style="font-size:14px;">cancel</span>Special character</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" minlength="12" required>
                            <button type="button" class="password-toggle-btn material-symbols-outlined" onclick="togglePasswordVisibility('confirm_password', this)">visibility_off</button>
                        </div>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-submit">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    <script src="../js/security-shield.js"></script>
    <script>
        function checkPolicy(val) {
            const list = document.getElementById('policyChecklist');
            list.style.display = val.length > 0 ? 'grid' : 'none';
            const rules = {
                pc_len:   val.length >= 12,
                pc_upper: /[A-Z]/.test(val),
                pc_lower: /[a-z]/.test(val),
                pc_num:   /[0-9]/.test(val),
                pc_spec:  /[\W_]/.test(val),
            };
            for (const [id, pass] of Object.entries(rules)) {
                const el = document.getElementById(id);
                const icon = el.querySelector('span');
                el.style.color = pass ? '#4caf50' : '#888';
                icon.textContent = pass ? 'check_circle' : 'cancel';
            }
        }

        function togglePasswordVisibility(fieldId, btnElement) {
            const input = document.getElementById(fieldId);
            if (input.type === 'password') {
                input.type = 'text';
                btnElement.textContent = 'visibility';
            } else {
                input.type = 'password';
                btnElement.textContent = 'visibility_off';
            }
        }
    </script>
</body>
</html>

