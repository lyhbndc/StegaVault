<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

$results = [
    'projects' => [],
    'project_members' => [],
    'users' => []
];

// Check projects
$p_stmt = $db->prepare("SELECT * FROM projects");
if ($p_stmt) {
    $p_stmt->execute();
    $results['projects'] = $p_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Check project members
$pm_stmt = $db->prepare("SELECT * FROM project_members");
if ($pm_stmt) {
    $pm_stmt->execute();
    $results['project_members'] = $pm_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Check users
$u_stmt = $db->prepare("SELECT id, email, role FROM users");
if ($u_stmt) {
    $u_stmt->execute();
    $results['users'] = $u_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($results, JSON_PRETTY_PRINT);