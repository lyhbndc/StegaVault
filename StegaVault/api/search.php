<?php

/**
 * StegaVault - Global Search API
 * GET api/search.php?q=keyword
 * Returns matching projects, folders, and files (admin only)
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$q = trim($_GET['q'] ?? '');
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$userId = (int)$_SESSION['user_id'];

if (strlen($q) < 1) {
    echo json_encode(['projects' => [], 'folders' => [], 'files' => []]);
    exit;
}

$like = '%' . $q . '%';

// ── Projects ──────────────────────────────────────────────────────────────────
$projects = [];
if ($isAdmin) {
    $stmt = $db->prepare("
        SELECT p.id, p.name,
               (SELECT COUNT(*) FROM files f WHERE f.project_id = p.id) AS file_count
        FROM projects p
        WHERE p.name LIKE ?
        ORDER BY p.name
        LIMIT 8
    ");
    $stmt->bind_param('s', $like);
}
else {
    $stmt = $db->prepare("
        SELECT p.id, p.name,
               (SELECT COUNT(*) FROM files f WHERE f.project_id = p.id) AS file_count
        FROM projects p
        JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE p.name LIKE ?
        ORDER BY p.name
        LIMIT 8
    ");
    $stmt->bind_param('is', $userId, $like);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc())
    $projects[] = $row;

// ── Folders ───────────────────────────────────────────────────────────────────
$folders = [];
if ($isAdmin) {
    $stmt = $db->prepare("
        SELECT pf.id, pf.name, pf.project_id, p.name AS project_name
        FROM project_folders pf
        JOIN projects p ON p.id = pf.project_id
        WHERE pf.name LIKE ?
        ORDER BY pf.name
        LIMIT 8
    ");
    $stmt->bind_param('s', $like);
}
else {
    $stmt = $db->prepare("
        SELECT pf.id, pf.name, pf.project_id, p.name AS project_name
        FROM project_folders pf
        JOIN projects p ON p.id = pf.project_id
        JOIN project_members pm ON pm.project_id = pf.project_id AND pm.user_id = ?
        WHERE pf.name LIKE ?
        ORDER BY pf.name
        LIMIT 8
    ");
    $stmt->bind_param('is', $userId, $like);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc())
    $folders[] = $row;

// ── Files ─────────────────────────────────────────────────────────────────────
$files = [];
if ($isAdmin) {
    $stmt = $db->prepare("
        SELECT f.id, f.original_name, f.mime_type, f.project_id,
               p.name AS project_name,
               pf.name AS folder_name
        FROM files f
        LEFT JOIN projects p ON p.id = f.project_id
        LEFT JOIN project_folders pf ON pf.id = f.folder_id
        WHERE f.original_name LIKE ?
        ORDER BY f.upload_date DESC
        LIMIT 10
    ");
    $stmt->bind_param('s', $like);
}
else {
    $stmt = $db->prepare("
        SELECT f.id, f.original_name, f.mime_type, f.project_id,
               p.name AS project_name,
               pf.name AS folder_name
        FROM files f
        LEFT JOIN projects p ON p.id = f.project_id
        LEFT JOIN project_folders pf ON pf.id = f.folder_id
        LEFT JOIN project_members pm ON pm.project_id = f.project_id
        WHERE f.original_name LIKE ?
          AND (f.user_id = ? OR pm.user_id = ?)
        GROUP BY f.id, f.original_name, f.mime_type, f.project_id, p.name, pf.name, f.upload_date
        ORDER BY f.upload_date DESC
        LIMIT 10
    ");
    $stmt->bind_param('sii', $like, $userId, $userId);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc())
    $files[] = $row;

echo json_encode([
    'projects' => $projects,
    'folders' => $folders,
    'files' => $files,
]);