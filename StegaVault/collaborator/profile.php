<?php
/**
 * StegaVault - Collaborator Profile Page
 * File: collaborator/profile.php
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
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters';
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
    <link rel="icon" type="image/png" href="../Assets/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - StegaVault</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .collaborator-header {
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
    </style>
</head>
<body>
    <header class="dashboard-header collaborator-header">
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
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" minlength="6" required>
                        <small style="color: #666;">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-submit">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
