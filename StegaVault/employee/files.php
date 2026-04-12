<?php
/**
 * StegaVault - Employee Files Page
 * File: employee/files.php
 * 
 * Employees can view and download their own files
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Files - StegaVault</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .employee-header {
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
        }
        
        .files-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .file-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .file-card:hover {
            border-color: #4caf50;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.2);
        }
        
        .file-preview {
            width: 100%;
            height: 200px;
            background: #e0e0e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            word-break: break-word;
        }
        
        .file-details {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .file-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-download {
            background: #4caf50;
            color: white;
        }
        
        .btn-download:hover {
            background: #388e3c;
        }
        
        .btn-view {
            background: #2196f3;
            color: white;
        }
        
        .btn-view:hover {
            background: #1976d2;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }
        
        .protected-badge {
            display: inline-block;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <header class="dashboard-header employee-header">
        <div class="header-left">
            <div class="logo">🔐 StegaVault</div>
            <h1>My Files</h1>
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
        <div class="files-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>📂 All My Files</h2>
                <a href="upload.php" style="padding: 12px 24px; background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">➕ Upload New File</a>
            </div>
            
            <div class="file-grid" id="fileGrid">
                <div class="empty-state">
                    <div style="font-size: 64px; margin-bottom: 20px;">⏳</div>
                    <p>Loading your files...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load files on page load
        loadFiles();

        async function loadFiles() {
            try {
                const response = await fetch('../api/upload.php');
                const data = await response.json();

                if (data.success && data.data.files.length > 0) {
                    displayFiles(data.data.files);
                } else {
                    document.getElementById('fileGrid').innerHTML = `
                        <div class="empty-state" style="grid-column: 1/-1;">
                            <div style="font-size: 64px; margin-bottom: 20px;">📁</div>
                            <p>No files uploaded yet</p>
                            <a href="upload.php" style="display: inline-block; margin-top: 15px; padding: 12px 24px; background: #4caf50; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">Upload Your First File</a>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading files:', error);
                document.getElementById('fileGrid').innerHTML = `
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <div style="font-size: 64px; margin-bottom: 20px;">❌</div>
                        <p>Error loading files. Please refresh the page.</p>
                    </div>
                `;
            }
        }

        function displayFiles(files) {
            const fileGrid = document.getElementById('fileGrid');
            
            fileGrid.innerHTML = files.map(file => {
                const sizeKB = (file.size / 1024).toFixed(2);
                const date = new Date(file.upload_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
                
                return `
                    <div class="file-card">
                        <div class="file-preview">
                            ${file.type.startsWith('image/') ? 
                                `<img src="../${file.url}" alt="${file.original_name}">` : 
                                '🖼️'}
                        </div>
                        <div class="file-title">${file.original_name}</div>
                        <div class="file-details">
                            ${sizeKB} KB<br>
                            Uploaded: ${date}<br>
                            Downloads: ${file.download_count}
                            ${file.watermarked ? '<br><span class="protected-badge">🔐 Protected</span>' : ''}
                        </div>
                        <div class="file-actions">
                            <a href="../${file.url}" target="_blank" class="btn-action btn-view">View</a>
                            <a href="../api/download.php?file_id=${file.id}" class="btn-action btn-download">Download</a>
                        </div>
                    </div>
                `;
            }).join('');
        }
    </script>
</body>
</html>
