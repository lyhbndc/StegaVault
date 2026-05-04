<?php

/**
 * StegaVault - Projects API (FIXED FOR FORMDATA)
 * File: api/projects.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable HTML error output

// Custom error handler to return JSON errors
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Server Error: $errstr"]);
    exit;
});

// Handle fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => "Fatal Error: " . $error['message']]);
    }
});

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';

// Check auth
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// CREATE PROJECT
// ============================================
if ($action === 'create') {
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = $_POST['color'] ?? '#667eea';
        $membersJson = $_POST['members'] ?? '[]';
        $members = json_decode($membersJson, true);

        if (!is_array($members)) {
            $members = [];
        }

        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Project name required']);
            exit;
        }

        // Duplicate name check
        $dup = $db->prepare("SELECT id FROM projects WHERE name = ?");
        $dup->bind_param('s', $name);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'A project with this name already exists']);
            exit;
        }

        // Insert project
        $stmt = $db->prepare("INSERT INTO projects (name, description, color, created_by, status) VALUES (?, ?, ?, ?, 'active')");

        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $db->getConnection()->error]);
            exit;
        }

        $stmt->bind_param('sssi', $name, $description, $color, $userId);

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
            exit;
        }

        $projectId = $db->lastInsertId();

        if (!$projectId) {
            throw new Exception("Failed to retrieve new project ID");
        }

        // Add creator as owner
        $ownerStmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')");

        if (!$ownerStmt) {
            error_log("Owner prepare failed: " . $db->getConnection()->error);
        }
        else {
            $ownerStmt->bind_param('ii', $projectId, $userId);
            if (!$ownerStmt->execute()) {
                error_log("Failed to add owner member: " . $ownerStmt->error);
            }
        }

        // Add members
        $memberCount = 1;
        if (count($members) > 0) {
            $memberStmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'member')");

            foreach ($members as $memberId) {
                $memberId = (int)$memberId;
                if ($memberId > 0 && $memberId != $userId) {
                    $memberStmt->bind_param('ii', $projectId, $memberId);
                    if ($memberStmt->execute()) {
                        $memberCount++;
                    }
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Project created successfully!',
            'project_id' => $projectId,
            'member_count' => $memberCount
        ]);
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// DELETE PROJECT
// ============================================
if ($action === 'delete') {
    try {
        $projectId = (int)($_POST['project_id'] ?? 0);

        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid project ID']);
            exit;
        }

        // Delete members
        $delMembers = $db->prepare("DELETE FROM project_members WHERE project_id = ?");
        $delMembers->bind_param('i', $projectId);
        $delMembers->execute();

        // Update files (set project_id to NULL)
        $updateFiles = $db->prepare("UPDATE files SET project_id = NULL WHERE project_id = ?");
        $updateFiles->bind_param('i', $projectId);
        $updateFiles->execute();

        // Delete project
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->bind_param('i', $projectId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete project']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// RENAME PROJECT
// ============================================
if ($action === 'rename') {
    try {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $newName = trim($_POST['name'] ?? '');

        if ($projectId <= 0 || $newName === '') {
            echo json_encode(['success' => false, 'error' => 'project_id and name are required']);
            exit;
        }

        // Admin only
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // Duplicate name check (excluding this project)
        $dup = $db->prepare("SELECT id FROM projects WHERE name = ? AND id != ?");
        $dup->bind_param('si', $newName, $projectId);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'A project with that name already exists']);
            exit;
        }

        $stmt = $db->prepare("UPDATE projects SET name = ? WHERE id = ?");
        $stmt->bind_param('si', $newName, $projectId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Project renamed']);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to rename project']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// REMOVE MEMBER
// ============================================
if ($action === 'remove_member') {
    try {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $memberId = (int)($_POST['user_id'] ?? 0);

        if ($projectId <= 0 || $memberId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $projectId, $memberId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Member removed successfully']);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to remove member']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// ADD MEMBER
// ============================================
if ($action === 'add_member') {
    try {
        // Admin only
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $memberId = (int)($_POST['user_id'] ?? 0);
        $role = in_array($_POST['role'] ?? '', ['member', 'owner']) ? $_POST['role'] : 'member';

        if ($projectId <= 0 || $memberId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }

        // Check the project exists
        $proj = $db->prepare("SELECT id FROM projects WHERE id = ?");
        $proj->bind_param('i', $projectId);
        $proj->execute();
        if ($proj->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Project not found']);
            exit;
        }

        // Check user exists
        $usr = $db->prepare("SELECT id, name FROM users WHERE id = ?");
        $usr->bind_param('i', $memberId);
        $usr->execute();
        $userRow = $usr->get_result()->fetch_assoc();
        if (!$userRow) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }

        // Already a member?
        $check = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $check->bind_param('ii', $projectId, $memberId);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => $userRow['name'] . ' is already a member of this project']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $projectId, $memberId, $role);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Member added successfully']);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to add member']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// GET MY PROJECTS (For Employee Workspace)
// ============================================
if ($action === 'my-projects') {
    try {
        $stmt = $db->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM files f WHERE f.project_id = p.id) as file_count,
                   (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as member_count,
                   pm.role as user_role,
                   COALESCE((SELECT ROUND(AVG(progress)) FROM project_tasks WHERE project_id = p.id), 0) as avg_progress,
                   (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id) as task_count,
                   (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
            FROM projects p
            JOIN project_members pm ON p.id = pm.project_id
            WHERE pm.user_id = ? " . (($_SESSION['role'] ?? '') === 'admin' ? "" : "AND p.status = 'active'") . "
            ORDER BY p.updated_at DESC
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $projects = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => ['projects' => $projects]
        ]);
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// GET DASHBOARD PROJECTS (Admin: all, Others: own)
// ============================================
if ($action === 'dashboard-projects') {
    try {
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

        if ($isAdmin) {
            $stmt = $db->prepare("
                SELECT p.*,
                       (SELECT COUNT(*) FROM files f WHERE f.project_id = p.id) as file_count,
                       (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as member_count,
                       'admin' as user_role,
                       COALESCE((SELECT ROUND(AVG(progress)) FROM project_tasks WHERE project_id = p.id), 0) as avg_progress,
                       (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id) as task_count,
                       (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                FROM projects p
                WHERE p.created_by = ?
                ORDER BY p.updated_at DESC
            ");
            $stmt->bind_param('i', $userId);
        }
        else {
            $stmt = $db->prepare("
                SELECT p.*,
                       (SELECT COUNT(*) FROM files f WHERE f.project_id = p.id) as file_count,
                       (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as member_count,
                       pm.role as user_role,
                       COALESCE((SELECT ROUND(AVG(progress)) FROM project_tasks WHERE project_id = p.id), 0) as avg_progress,
                       (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id) as task_count,
                       (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                FROM projects p
                JOIN project_members pm ON p.id = pm.project_id
                WHERE pm.user_id = ? AND (p.status = 'active' OR ? = 'admin')
                ORDER BY p.updated_at DESC
            ");
            $role = $_SESSION['role'] ?? 'user';
            $stmt->bind_param('is', $userId, $role);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $projects = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => ['projects' => $projects]
        ]);
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// GET PROJECT FILES
// ============================================
if ($action === 'files') {
    try {
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$projectId) throw new Exception('Project ID is required');

        // Enforcement: Non-admins cannot access inactive projects
        $checkStmt = $db->prepare("SELECT status FROM projects WHERE id = ?");
        $checkStmt->bind_param('i', $projectId);
        $checkStmt->execute();
        $projStatus = $checkStmt->get_result()->fetch_assoc()['status'] ?? 'active';
        if ($projStatus === 'inactive' && ($_SESSION['role'] ?? '') !== 'admin') {
            throw new Exception('This project is currently inactive.');
        }

        // Basic access check: Must be member or admin
        $stmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $projectId, $userId);
        $stmt->execute();
        $isMember = $stmt->get_result()->num_rows > 0;

        // Check if user is admin (role in users table)
        // We need to fetch user role from session or DB. Session is faster.
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

        if (!$isMember && !$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // Fetch files (exclude files that belong to a folder)
        $query = "
            SELECT f.id, f.filename, f.original_name, f.file_path, f.file_size, f.mime_type as file_type, 
                                     f.upload_date, f.user_id, f.watermarked, u.name as uploader_name
            FROM files f
            JOIN users u ON f.user_id = u.id
            WHERE f.project_id = ?
              AND f.folder_id IS NULL
            ORDER BY f.upload_date DESC";

        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => ['files' => $files]
        ]);
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// GET PROJECT MEMBERS
// ============================================
if ($action === 'members') {
    try {
        $projectId = (int)($_GET['project_id'] ?? 0);

        // Enforcement: Non-admins cannot access inactive projects
        $checkStmt = $db->prepare("SELECT status FROM projects WHERE id = ?");
        $checkStmt->bind_param('i', $projectId);
        $checkStmt->execute();
        $projStatus = $checkStmt->get_result()->fetch_assoc()['status'] ?? 'active';
        if ($projStatus === 'inactive' && ($_SESSION['role'] ?? '') !== 'admin') {
            throw new Exception('This project is currently inactive.');
        }

        // Validate access
        $stmt = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $projectId, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0 && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.role as user_role, pm.role as project_role, pm.joined_at
            FROM project_members pm
            JOIN users u ON pm.user_id = u.id
            WHERE pm.project_id = ?
            ORDER BY u.name ASC
        ");

        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => ['members' => $members]
        ]);
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// CREATE FOLDER
// ============================================
if ($action === 'create-folder') {
    try {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $parentId = isset($_POST['parent_id']) && is_numeric($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        if ($projectId <= 0 || empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Project ID and folder name are required']);
            exit;
        }

        // Must be a project member
        $check = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $check->bind_param('ii', $projectId, $userId);
        $check->execute();
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($check->get_result()->num_rows === 0 && !$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // If parent_id provided, validate it belongs to this project
        if ($parentId) {
            $pcheck = $db->prepare("SELECT id FROM project_folders WHERE id = ? AND project_id = ?");
            $pcheck->bind_param('ii', $parentId, $projectId);
            $pcheck->execute();
            if ($pcheck->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'error' => 'Parent folder not found in this project']);
                exit;
            }
        }

        // Check for duplicate folder name under the same parent (or project root)
        if ($parentId) {
            $dup = $db->prepare("SELECT id FROM project_folders WHERE project_id = ? AND parent_id = ? AND name = ?");
            $dup->bind_param('iis', $projectId, $parentId, $name);
        }
        else {
            $dup = $db->prepare("SELECT id FROM project_folders WHERE project_id = ? AND parent_id IS NULL AND name = ?");
            $dup->bind_param('is', $projectId, $name);
        }
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'A folder with that name already exists here']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO project_folders (project_id, parent_id, name, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iisi', $projectId, $parentId, $name, $userId);

        if ($stmt->execute()) {
            $newId = $db->lastInsertId();
            echo json_encode([
                'success' => true,
                'message' => 'Folder created successfully',
                'folder' => [
                    'id' => $newId,
                    'name' => $name,
                    'project_id' => $projectId,
                    'parent_id' => $parentId,
                    'created_by' => $userId,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to create folder']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// GET FOLDERS FOR A PROJECT
// ============================================
if ($action === 'get-folders') {
    try {
        $projectId = (int)($_GET['project_id'] ?? 0);

        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid project ID']);
            exit;
        }

        // Must be a project member or admin
        $check = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $check->bind_param('ii', $projectId, $userId);
        $check->execute();
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($check->get_result()->num_rows === 0 && !$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        $stmt = $db->prepare("
            SELECT pf.id, pf.name, pf.created_at,
                   u.name as created_by_name,
                   COUNT(f.id) as file_count
            FROM project_folders pf
            JOIN users u ON pf.created_by = u.id
            LEFT JOIN files f ON f.folder_id = pf.id
            WHERE pf.project_id = ? AND pf.parent_id IS NULL
            GROUP BY pf.id, pf.name, pf.created_at, u.name
            ORDER BY pf.name ASC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $folders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'data' => ['folders' => $folders]]);
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// GET FILES INSIDE A FOLDER
// ============================================
if ($action === 'get-folder-files') {
    try {
        $folderId = (int)($_GET['folder_id'] ?? 0);
        $projectId = (int)($_GET['project_id'] ?? 0);

        if ($folderId <= 0 || $projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid folder or project ID']);
            exit;
        }

        // Must be a project member or admin
        $check = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $check->bind_param('ii', $projectId, $userId);
        $check->execute();
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($check->get_result()->num_rows === 0 && !$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // Verify folder belongs to the project
        $folderCheck = $db->prepare("SELECT id, name FROM project_folders WHERE id = ? AND project_id = ?");
        $folderCheck->bind_param('ii', $folderId, $projectId);
        $folderCheck->execute();
        $folderRow = $folderCheck->get_result()->fetch_assoc();
        if (!$folderRow) {
            echo json_encode(['success' => false, 'error' => 'Folder not found']);
            exit;
        }

        $stmt = $db->prepare("
            SELECT f.id, f.filename, f.original_name, f.file_path, f.file_size,
                 f.mime_type as file_type, f.upload_date, f.user_id, f.watermarked,
                   u.name as uploader_name
            FROM files f
            JOIN users u ON f.user_id = u.id
            WHERE f.folder_id = ? AND f.project_id = ?
            ORDER BY f.upload_date DESC
        ");
        $stmt->bind_param('ii', $folderId, $projectId);
        $stmt->execute();
        $files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Also return direct subfolders of this folder
        $sfStmt = $db->prepare("
            SELECT pf.id, pf.name, pf.parent_id, pf.created_at,
                   u.name as created_by_name,
                   COUNT(f.id) as file_count
            FROM project_folders pf
            JOIN users u ON pf.created_by = u.id
            LEFT JOIN files f ON f.folder_id = pf.id
            WHERE pf.parent_id = ? AND pf.project_id = ?
            GROUP BY pf.id, pf.name, pf.parent_id, pf.created_at, u.name
            ORDER BY pf.name ASC
        ");
        $sfStmt->bind_param('ii', $folderId, $projectId);
        $sfStmt->execute();
        $subfolders = $sfStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'files' => $files,
                'subfolders' => $subfolders,
                'folder' => $folderRow
            ]
        ]);
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// RENAME FOLDER
// ============================================
if ($action === 'rename-folder') {
    try {
        $folderId = (int)($_POST['folder_id'] ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 0);
        $newName = trim($_POST['name'] ?? '');

        if ($folderId <= 0 || $projectId <= 0 || $newName === '') {
            echo json_encode(['success' => false, 'error' => 'folder_id, project_id and name are required']);
            exit;
        }

        // Access check
        $check = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $check->bind_param('ii', $projectId, $userId);
        $check->execute();
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($check->get_result()->num_rows === 0 && !$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // Verify folder belongs to project
        $fi = $db->prepare("SELECT id, parent_id FROM project_folders WHERE id = ? AND project_id = ?");
        $fi->bind_param('ii', $folderId, $projectId);
        $fi->execute();
        $folderRow = $fi->get_result()->fetch_assoc();
        if (!$folderRow) {
            echo json_encode(['success' => false, 'error' => 'Folder not found']);
            exit;
        }

        // Duplicate name check within same parent scope
        if ($folderRow['parent_id']) {
            $dup = $db->prepare("SELECT id FROM project_folders WHERE project_id = ? AND parent_id = ? AND name = ? AND id != ?");
            $dup->bind_param('iisi', $projectId, $folderRow['parent_id'], $newName, $folderId);
        }
        else {
            $dup = $db->prepare("SELECT id FROM project_folders WHERE project_id = ? AND parent_id IS NULL AND name = ? AND id != ?");
            $dup->bind_param('isi', $projectId, $newName, $folderId);
        }
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'A folder with that name already exists here']);
            exit;
        }

        $stmt = $db->prepare("UPDATE project_folders SET name = ? WHERE id = ? AND project_id = ?");
        $stmt->bind_param('sii', $newName, $folderId, $projectId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Folder renamed']);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to rename folder']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// DELETE FOLDER
// ============================================
if ($action === 'delete-folder') {
    try {
        $folderId = (int)($_POST['folder_id'] ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 0);

        if ($folderId <= 0 || $projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'folder_id and project_id are required']);
            exit;
        }

        // Access check
        $check = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $check->bind_param('ii', $projectId, $userId);
        $check->execute();
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($check->get_result()->num_rows === 0 && !$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // Verify folder belongs to project
        $fi = $db->prepare("SELECT id FROM project_folders WHERE id = ? AND project_id = ?");
        $fi->bind_param('ii', $folderId, $projectId);
        $fi->execute();
        if ($fi->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'Folder not found']);
            exit;
        }

        // Unlink files from this folder (set folder_id = NULL so files stay in the project)
        $unlink = $db->prepare("UPDATE files SET folder_id = NULL WHERE folder_id = ? AND project_id = ?");
        $unlink->bind_param('ii', $folderId, $projectId);
        $unlink->execute();

        // Delete folder (subfolders cascade via FK ON DELETE CASCADE)
        $del = $db->prepare("DELETE FROM project_folders WHERE id = ? AND project_id = ?");
        $del->bind_param('ii', $folderId, $projectId);
        if ($del->execute()) {
            echo json_encode(['success' => true, 'message' => 'Folder deleted']);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete folder']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// DUPLICATE FOLDER
// ============================================
if ($action === 'duplicate-folder') {
    try {
        $folderId = (int)($_POST['folder_id'] ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 0);

        if ($folderId <= 0 || $projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'folder_id and project_id are required']);
            exit;
        }

        // Access check
        $check = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $check->bind_param('ii', $projectId, $userId);
        $check->execute();
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($check->get_result()->num_rows === 0 && !$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // Get original folder
        $fi = $db->prepare("SELECT id, name, parent_id FROM project_folders WHERE id = ? AND project_id = ?");
        $fi->bind_param('ii', $folderId, $projectId);
        $fi->execute();
        $original = $fi->get_result()->fetch_assoc();
        if (!$original) {
            echo json_encode(['success' => false, 'error' => 'Folder not found']);
            exit;
        }

        // Generate a unique copy name: "Name (Copy)", "Name (Copy 2)", etc.
        $baseName = $original['name'] . ' (Copy)';
        $copyName = $baseName;
        $suffix = 2;
        while (true) {
            if ($original['parent_id']) {
                $dup = $db->prepare("SELECT id FROM project_folders WHERE project_id = ? AND parent_id = ? AND name = ?");
                $dup->bind_param('iis', $projectId, $original['parent_id'], $copyName);
            }
            else {
                $dup = $db->prepare("SELECT id FROM project_folders WHERE project_id = ? AND parent_id IS NULL AND name = ?");
                $dup->bind_param('is', $projectId, $copyName);
            }
            $dup->execute();
            if ($dup->get_result()->num_rows === 0)
                break;
            $copyName = $baseName . ' ' . $suffix++;
        }

        $ins = $db->prepare("INSERT INTO project_folders (project_id, parent_id, name, created_by) VALUES (?, ?, ?, ?)");
        $ins->bind_param('iisi', $projectId, $original['parent_id'], $copyName, $userId);
        if ($ins->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Folder duplicated',
                'folder' => [
                    'id' => $db->lastInsertId(),
                    'name' => $copyName,
                    'parent_id' => $original['parent_id'],
                    'project_id' => $projectId,
                ]
            ]);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to duplicate folder']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// RENAME FILE
// ============================================
if ($action === 'rename-file') {
    try {
        $fileId = (int)($_POST['file_id'] ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 0);
        $newName = trim($_POST['name'] ?? '');

        if ($fileId <= 0 || $projectId <= 0 || $newName === '') {
            echo json_encode(['success' => false, 'error' => 'file_id, project_id and name are required']);
            exit;
        }

        // Access check
        $check = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $check->bind_param('ii', $projectId, $userId);
        $check->execute();
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($check->get_result()->num_rows === 0 && !$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // Verify file belongs to the project
        $fi = $db->prepare("SELECT id FROM files WHERE id = ? AND project_id = ?");
        $fi->bind_param('ii', $fileId, $projectId);
        $fi->execute();
        if ($fi->get_result()->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => 'File not found']);
            exit;
        }

        // Get old name and owner for logging
        $old = $db->prepare("SELECT original_name, user_id AS owner_id FROM files WHERE id = ?");
        $old->bind_param('i', $fileId);
        $old->execute();
        $oldRow  = $old->get_result()->fetch_assoc();
        $oldName = $oldRow ? $oldRow['original_name'] : 'Unknown';
        $fileOwnerId = (int)($oldRow['owner_id'] ?? 0);

        // Prevent duplicate filenames within this project only (case-insensitive)
        $dup = $db->prepare("SELECT id FROM files WHERE LOWER(TRIM(original_name)) = LOWER(TRIM(?)) AND project_id = ? AND id != ? LIMIT 1");
        $dup->bind_param('sii', $newName, $projectId, $fileId);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'A file with this name already exists in this project. Please choose a different name.']);
            exit;
        }

        $stmt = $db->prepare("UPDATE files SET original_name = ? WHERE id = ? AND project_id = ?");
        $stmt->bind_param('sii', $newName, $fileId, $projectId);
        if ($stmt->execute()) {
            // Log rename activity
            try {
                $userName   = $_SESSION['name'] ?? 'Unknown User';
                $userRole   = $_SESSION['role'] ?? 'unknown';
                $renameIp   = $_SERVER['REMOTE_ADDR'] ?? null;
                $logDesc    = "Renamed file from '{$oldName}' to '{$newName}' by {$userName}";
                logActivityEvent($db, (int)$userId, 'file_renamed', $logDesc, $renameIp, $userRole, false);

                // Notify file owner if actor is different
                if ($fileOwnerId > 0 && $fileOwnerId !== (int)$userId) {
                    $ownerRole  = getUserRoleForActivityLog($db, $fileOwnerId);
                    $ownerTable = getRoleActivityTable($ownerRole);
                    insertActivityRow($db, $ownerTable, $fileOwnerId, 'file_renamed', "Your file '{$oldName}' was renamed to '{$newName}' by {$userName} ({$userRole})", $renameIp);
                }
            }
            catch (Exception $e) {
            }

            echo json_encode(['success' => true, 'message' => 'File renamed']);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to rename file']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// DELETE FILE
// ============================================
if ($action === 'delete-file') {
    try {
        $fileId = (int)($_POST['file_id'] ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 0);

        if ($fileId <= 0 || $projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'file_id and project_id are required']);
            exit;
        }

        // Admin only
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Only admins can delete files.']);
            exit;
        }

        // Get file path before deleting
        $fi = $db->prepare("SELECT id, original_name, file_path FROM files WHERE id = ? AND project_id = ?");
        $fi->bind_param('ii', $fileId, $projectId);
        $fi->execute();
        $fileRow = $fi->get_result()->fetch_assoc();
        if (!$fileRow) {
            echo json_encode(['success' => false, 'error' => 'File not found']);
            exit;
        }

        // Delete the DB record
        $del = $db->prepare("DELETE FROM files WHERE id = ? AND project_id = ?");
        $del->bind_param('ii', $fileId, $projectId);
        if ($del->execute()) {
            // Attempt to remove physical file (non-fatal if missing)
            if ($fileRow['file_path'] && file_exists(__DIR__ . '/../' . $fileRow['file_path'])) {
                @unlink(__DIR__ . '/../' . $fileRow['file_path']);
            }

            // Log delete activity
            try {
                $userName = $_SESSION['name'] ?? 'Unknown User';
                $logDesc = "Deleted file: " . ($fileRow['original_name'] ?? 'Unknown') . " by " . $userName;
                logActivityEvent($db, (int)$userId, 'file_deleted', $logDesc, $_SERVER['REMOTE_ADDR'] ?? null, $_SESSION['role'] ?? null, false);
            }
            catch (Exception $e) {
            }

            echo json_encode(['success' => true, 'message' => 'File deleted']);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// UPDATE PROJECT DESCRIPTION
// ============================================
if ($action === 'update-description') {
    try {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        $projectId = (int)($_POST['project_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid project ID']);
            exit;
        }
        $stmt = $db->prepare("UPDATE projects SET description = ? WHERE id = ?");
        $stmt->bind_param('si', $description, $projectId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Description updated']);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to update description']);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// UPDATE PROJECT STATUS
// ============================================
if ($action === 'update-status') {
    try {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
        $projectId = (int)($_POST['project_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if (!in_array($status, ['active', 'inactive'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit;
        }
        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid project ID']);
            exit;
        }
        $stmt = $db->prepare("UPDATE projects SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $projectId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated to ' . $status]);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Failed to update status: ' . $db->error]);
        }
        exit;
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}
// ============================================
// CREATE TASK (Admin only)
// ============================================
if ($action === 'create-task') {
    try {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        $projectId        = (int)($_POST['project_id'] ?? 0);
        $title            = trim($_POST['title'] ?? '');
        $description      = trim($_POST['description'] ?? '');
        $assignedTo       = isset($_POST['assigned_to']) && is_numeric($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $priority         = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high']) ? $_POST['priority'] : 'medium';
        $dueDate          = trim($_POST['due_date'] ?? '') ?: null;
        $requiredFileType = in_array($_POST['required_file_type'] ?? '', ['image', 'document', 'video', 'any']) ? $_POST['required_file_type'] : 'any';

        if ($projectId <= 0 || $title === '') {
            echo json_encode(['success' => false, 'error' => 'project_id and title are required']);
            exit;
        }

        if ($assignedTo !== null && $assignedTo === (int)$userId) {
            echo json_encode(['success' => false, 'error' => 'You cannot assign a task to yourself.']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO project_tasks (project_id, title, description, assigned_to, created_by, priority, due_date, required_file_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssiiss', $projectId, $title, $description, $assignedTo, $userId, $priority, $dueDate, $requiredFileType);

        if ($stmt->execute()) {
            $newId = $db->lastInsertId();

            // Notify assigned user
            if ($assignedTo && $assignedTo !== (int)$userId) {
                try {
                    $assignedRole = getUserRoleForActivityLog($db, $assignedTo);
                    $assignedTable = getRoleActivityTable($assignedRole);
                    $adminName = $_SESSION['name'] ?? 'Admin';
                    insertActivityRow($db, $assignedTable, $assignedTo, 'task_assigned', "Task assigned: \"{$title}\" by {$adminName}", $_SERVER['REMOTE_ADDR'] ?? null);
                } catch (Exception $e) {}
            }

            echo json_encode(['success' => true, 'task_id' => $newId, 'message' => 'Task created']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create task']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// GET TASKS (Members + Admin)
// ============================================
if ($action === 'get-tasks') {
    try {
        $projectId = (int)($_GET['project_id'] ?? 0);

        if ($projectId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid project ID']);
            exit;
        }

        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

        if (!$isAdmin) {
            $check = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
            $check->bind_param('ii', $projectId, $userId);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                exit;
            }
        }

        $stmt = $db->prepare("
            SELECT t.id, t.title, t.description, t.priority, t.status, t.progress, t.due_date,
                   t.required_file_type,
                   t.created_at, t.updated_at,
                   t.assigned_to,
                   u.name AS assigned_name,
                   cb.name AS created_by_name
            FROM project_tasks t
            LEFT JOIN users u  ON t.assigned_to = u.id
            LEFT JOIN users cb ON t.created_by  = cb.id
            WHERE t.project_id = ?
            ORDER BY
                CASE t.status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END,
                CASE t.priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END,
                t.due_date ASC NULLS LAST,
                t.created_at DESC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'data' => ['tasks' => $tasks]]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// UPDATE TASK (Assigned user updates progress/status; admin can update anything)
// ============================================
if ($action === 'update-task') {
    try {
        $taskId  = (int)($_POST['task_id'] ?? 0);
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
            exit;
        }

        // Fetch task to verify ownership
        $tk = $db->prepare("SELECT * FROM project_tasks WHERE id = ?");
        $tk->bind_param('i', $taskId);
        $tk->execute();
        $task = $tk->get_result()->fetch_assoc();
        if (!$task) {
            echo json_encode(['success' => false, 'error' => 'Task not found']);
            exit;
        }

        $isAssigned = ((int)$task['assigned_to'] === (int)$userId);
        if (!$isAdmin && !$isAssigned) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        // Fields employees can update
        $progress = isset($_POST['progress']) ? max(0, min(100, (int)$_POST['progress'])) : (int)$task['progress'];
        $status   = in_array($_POST['status'] ?? '', ['pending', 'in_progress', 'completed']) ? $_POST['status'] : $task['status'];

        // Auto-sync: 100% => completed, 0% pending => pending
        if ($progress === 100) $status = 'completed';
        if ($progress === 0 && $status !== 'completed') $status = 'pending';
        if ($progress > 0 && $progress < 100 && $status === 'pending') $status = 'in_progress';

        // Admin-only fields
        $title            = $isAdmin ? (trim($_POST['title'] ?? '') ?: $task['title']) : $task['title'];
        $description      = $isAdmin ? (trim($_POST['description'] ?? '')) : $task['description'];
        $priority         = $isAdmin && in_array($_POST['priority'] ?? '', ['low', 'medium', 'high']) ? $_POST['priority'] : $task['priority'];
        $assignedTo       = $isAdmin && isset($_POST['assigned_to']) && is_numeric($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : $task['assigned_to'];
        $dueDate          = $isAdmin ? (trim($_POST['due_date'] ?? '') ?: null) : $task['due_date'];
        $requiredFileType = $isAdmin && in_array($_POST['required_file_type'] ?? '', ['image', 'document', 'video', 'any']) ? $_POST['required_file_type'] : ($task['required_file_type'] ?? 'any');

        if ($isAdmin && $assignedTo === (int)$userId) {
            echo json_encode(['success' => false, 'error' => 'You cannot assign a task to yourself.']);
            exit;
        }

        $stmt = $db->prepare("UPDATE project_tasks SET title=?, description=?, assigned_to=?, priority=?, status=?, progress=?, due_date=?, required_file_type=? WHERE id=?");
        $stmt->bind_param('ssiisissi', $title, $description, $assignedTo, $priority, $status, $progress, $dueDate, $requiredFileType, $taskId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Task updated', 'status' => $status, 'progress' => $progress]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update task']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

// ============================================
// DELETE TASK (Admin only)
// ============================================
if ($action === 'delete-task') {
    try {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM project_tasks WHERE id = ?");
        $stmt->bind_param('i', $taskId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Task deleted']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete task']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action, 'received_post' => $_POST]);