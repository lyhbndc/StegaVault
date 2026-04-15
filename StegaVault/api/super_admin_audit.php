<?php
/**
 * StegaVault - Super Admin Audit Log API
 * File: api/super_admin_audit.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

function sendResponse($success, $data = null, $error = null, $code = 200)
{
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

// ── LIST AUDIT LOGS ───────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $category = $_GET['category'] ?? '';   // auth | backup | admin | mfa | ''
    $search   = trim($_GET['search'] ?? '');
    $page     = max(1, (int) ($_GET['page'] ?? 1));
    $perPage  = 50;
    $offset   = ($page - 1) * $perPage;

    $pdo = $db->getConnection();

    // Build WHERE clause
    $conditions = [];
    $params     = [];

    if ($category !== '') {
        $conditions[] = 'category = ?';
        $params[]     = $category;
    }

    if ($search !== '') {
        $conditions[] = '(super_admin_name ILIKE ? OR super_admin_email ILIKE ? OR action ILIKE ? OR details::text ILIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM super_admin_audit_log $where");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Paginated results
    $params[] = $perPage;
    $params[] = $offset;
    $dataStmt = $pdo->prepare(
        "SELECT id, super_admin_id, super_admin_name, super_admin_email,
                action, category, details, ip_address, created_at
         FROM super_admin_audit_log
         $where
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?"
    );
    $dataStmt->execute($params);
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse JSON details field
    foreach ($rows as &$row) {
        $row['details'] = json_decode($row['details'] ?? '{}', true) ?? [];
    }

    sendResponse(true, [
        'logs'       => $rows,
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $perPage,
        'pages'      => (int) ceil($total / $perPage),
    ]);
}

// ── CATEGORY SUMMARY ─────────────────────────────────
if ($method === 'GET' && $action === 'summary') {
    $pdo = $db->getConnection();

    $summary = $pdo->query(
        "SELECT category, COUNT(*) as total,
                MAX(created_at) as last_event
         FROM super_admin_audit_log
         GROUP BY category
         ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $recent = $pdo->query(
        "SELECT action, super_admin_name, created_at
         FROM super_admin_audit_log
         ORDER BY created_at DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, ['summary' => $summary, 'recent' => $recent]);
}

sendResponse(false, null, 'Unknown action', 400);
