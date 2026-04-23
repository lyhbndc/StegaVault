<?php

/**
 * StegaVault - Generate Report
 * File: admin/reports.php
 */

session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.html');
    exit;
}

$period = in_array($_GET['period'] ?? '', ['daily', 'weekly', 'monthly']) ? $_GET['period'] : 'all';
$periodLabel = match($period) {
    'daily'   => 'Daily Report (Last 24 Hours)',
    'weekly'  => 'Weekly Report (Last 7 Days)',
    'monthly' => 'Monthly Report (Last 30 Days)',
    default   => 'All-Time Report'
};
$filterDays  = match($period) { 'daily' => 1, 'weekly' => 7, 'monthly' => 30, default => null };
$trendDays   = match($period) { 'monthly' => 30, default => 7 };
$fileDateSQL = $filterDays !== null ? "AND f.upload_date >= DATE_SUB(NOW(), INTERVAL {$filterDays} DAY)" : '';
$actDateSQL  = $filterDays !== null ? "AND al.created_at >= DATE_SUB(NOW(), INTERVAL {$filterDays} DAY)" : '';

$user = [
    'id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role']
];

// ── Summary Statistics ──────────────────────────────────────────
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$activeUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active' OR status IS NULL")->fetch_row()[0];
$pendingUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending_activation'")->fetch_row()[0];
$disabledUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'disabled'")->fetch_row()[0];
$expiredUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'expired'")->fetch_row()[0];
$adminCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetch_row()[0];
$employeeCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetch_row()[0];
$collaboratorCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'collaborator'")->fetch_row()[0];

$totalProjects = $db->query("SELECT COUNT(*) FROM projects")->fetch_row()[0];
$activeProjects = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'")->fetch_row()[0];

$totalFiles = $db->query("SELECT COUNT(*) FROM files")->fetch_row()[0];
$totalFileSize = (int) $db->query("SELECT COALESCE(SUM(file_size), 0) FROM files")->fetch_row()[0];
$watermarkedFiles = $db->query("SELECT COUNT(*) FROM files WHERE watermarked IS TRUE")->fetch_row()[0];
$nonWatermarkedFiles = max(0, $totalFiles - $watermarkedFiles);
$totalDownloads = (int) $db->query("SELECT COALESCE(SUM(download_count), 0) FROM files")->fetch_row()[0];

$totalFolders = $db->query("SELECT COUNT(*) FROM project_folders")->fetch_row()[0];
$totalMembers = $db->query("SELECT COUNT(*) FROM project_members")->fetch_row()[0];

// ── Project Breakdown ──────────────────────────────────────────
$stmt = $db->prepare("
    SELECT p.id, p.name, p.color, p.status, p.created_at,
           u.name AS creator_name,
           COUNT(DISTINCT pm.user_id) AS member_count,
           COUNT(DISTINCT f.id)       AS file_count,
           COALESCE(SUM(f.file_size), 0) AS total_size
    FROM projects p
    LEFT JOIN users u            ON p.created_by = u.id
    LEFT JOIN project_members pm ON pm.project_id = p.id
    LEFT JOIN files f            ON f.project_id  = p.id
    GROUP BY p.id, u.name
    ORDER BY p.created_at DESC
");
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── User List ──────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT u.id, u.name, u.email, u.role, u.status,
           u.created_at,
           COUNT(DISTINCT f.id)       AS file_count,
           COUNT(DISTINCT pm.project_id) AS project_count
    FROM users u
    LEFT JOIN files f           ON f.user_id = u.id
    LEFT JOIN project_members pm ON pm.user_id = u.id
    GROUP BY u.id, u.name, u.email, u.role, u.status, u.created_at
    ORDER BY u.created_at ASC
");
$stmt->execute();
$userRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Recent Uploads ─────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT f.original_name, f.file_size, f.mime_type, f.upload_date,
           u.name AS uploader, p.name AS project_name
    FROM files f
    LEFT JOIN users u    ON f.user_id    = u.id
    LEFT JOIN projects p ON f.project_id = p.id
    WHERE 1=1 {$fileDateSQL}
    ORDER BY f.upload_date DESC
    LIMIT 50
");
$stmt->execute();
$recentFiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Activity Log ───────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT al.action, al.description, al.created_at, u.name AS actor
        FROM (SELECT * FROM activity_log_admin UNION ALL SELECT * FROM activity_log_employee UNION ALL SELECT * FROM activity_log_collaborator) al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1 {$actDateSQL}
    ORDER BY al.created_at DESC
    LIMIT 50
");
$stmt->execute();
$activityLog = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Upload Trend ─────────────────────────────────────────────
$uploadTrendMap = [];
$trendStmt = $db->prepare("
    SELECT DATE(upload_date) AS d, COUNT(*) AS c
    FROM files
    WHERE upload_date >= DATE_SUB(NOW(), INTERVAL {$trendDays} DAY)
    GROUP BY DATE(upload_date)
    ORDER BY d ASC
");
$trendStmt->execute();
$trendRows = $trendStmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($trendRows as $row) {
    $uploadTrendMap[$row['d']] = (int) $row['c'];
}

$uploadTrendLabels = [];
$uploadTrendCounts = [];
for ($i = $trendDays - 1; $i >= 0; $i--) {
    $dateKey = date('Y-m-d', strtotime("-$i days"));
    $uploadTrendLabels[] = date('M d', strtotime($dateKey));
    $uploadTrendCounts[] = $uploadTrendMap[$dateKey] ?? 0;
}

$statusChart = [
    'labels' => ['Active', 'Pending', 'Locked', 'Expired'],
    'values' => [(int) $activeUsers, (int) $pendingUsers, (int) $disabledUsers, (int) $expiredUsers]
];

$roleChart = [
    'labels' => ['Admin', 'Employee', 'Collaborator'],
    'values' => [(int) $adminCount, (int) $employeeCount, (int) $collaboratorCount]
];

$watermarkChart = [
    'labels' => ['Watermarked', 'Not Watermarked'],
    'values' => [(int) $watermarkedFiles, (int) $nonWatermarkedFiles]
];

// ── Helpers ────────────────────────────────────────────────────
function fmtSize(int $bytes): string
{
    if ($bytes >= 1048576)
        return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)
        return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// ── Forensic Analysis Log ──────────────────────────────────────
$forensicLogs = [];
@$db->query("CREATE TABLE IF NOT EXISTS forensic_analysis_log (
    id SERIAL PRIMARY KEY,
    analyzed_by INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size BIGINT DEFAULT 0,
    mime_type VARCHAR(100) DEFAULT NULL,
    integrity_status VARCHAR(20) NOT NULL CHECK (integrity_status IN ('VALID','TAMPERED','NO_WATERMARK')),
    watermark_found SMALLINT DEFAULT 0,
    content_hash VARCHAR(64) DEFAULT NULL,
    extracted_user_id INT DEFAULT NULL,
    extracted_user_name VARCHAR(255) DEFAULT NULL,
    extracted_user_role VARCHAR(50) DEFAULT NULL,
    extracted_ip VARCHAR(45) DEFAULT NULL,
    crypto_verified SMALLINT DEFAULT NULL,
    analysis_time_ms DOUBLE PRECISION DEFAULT NULL,
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$falDateSQL = $filterDays !== null ? "AND fal.analyzed_at >= DATE_SUB(NOW(), INTERVAL {$filterDays} DAY)" : '';
$falStmt = $db->prepare("
    SELECT fal.file_name, fal.file_size, fal.integrity_status, fal.watermark_found,
           fal.extracted_user_name, fal.extracted_user_role, fal.extracted_ip,
           fal.crypto_verified, fal.analysis_time_ms, fal.analyzed_at,
           u.name AS analyst_name
    FROM forensic_analysis_log fal
    LEFT JOIN users u ON fal.analyzed_by = u.id
    WHERE 1=1 {$falDateSQL}
    ORDER BY fal.analyzed_at DESC
    LIMIT 50
");
if ($falStmt) {
    $falStmt->execute();
    $forensicLogs = $falStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$totalForensicScans   = (int)($db->query("SELECT COUNT(*) FROM forensic_analysis_log")->fetch_row()[0] ?? 0);
$tamperedCount        = (int)($db->query("SELECT COUNT(*) FROM forensic_analysis_log WHERE integrity_status='TAMPERED'")->fetch_row()[0] ?? 0);
$noWatermarkCount     = (int)($db->query("SELECT COUNT(*) FROM forensic_analysis_log WHERE integrity_status='NO_WATERMARK'")->fetch_row()[0] ?? 0);

$generatedAt = date('F j, Y \a\t g:i A');

// ── PDF data arrays (plain text, no HTML) ─────────────────────
$projectsPDF = array_map(fn($p) => [
    $p['name'],
    ucfirst($p['status']),
    $p['creator_name'] ?? '—',
    (string) $p['member_count'],
    (string) $p['file_count'],
    fmtSize((int) $p['total_size']),
    date('M d, Y', strtotime($p['created_at'])),
], $projects);

$usersPDF = array_map(fn($u) => [
    'name'     => $u['name'],
    'email'    => $u['email'],
    'role'     => $u['role'] ?? 'employee',
    'status'   => match ($u['status'] ?? 'active') {
        'active'              => 'Active',
        'pending_activation'  => 'Pending',
        'disabled'            => 'Locked',
        'expired'             => 'Expired',
        default               => ucfirst($u['status'] ?? 'Active'),
    },
    'projects' => (int) $u['project_count'],
    'files'    => (int) $u['file_count'],
    'joined'   => date('M d, Y', strtotime($u['created_at'])),
], $userRows);

$filesPDF = array_map(fn($f) => [
    $f['original_name'],
    $f['mime_type'] ?: '—',
    fmtSize((int) $f['file_size']),
    $f['uploader'] ?? '—',
    $f['project_name'] ?? '—',
    date('M d, Y', strtotime($f['upload_date'])),
], $recentFiles);

$activityPDF = array_map(fn($a) => [
    $a['action'],
    $a['description'],
    $a['actor'] ?? 'System',
    date('M d, Y H:i', strtotime($a['created_at'])),
], $activityLog);

$forensicPDF = array_map(fn($fl) => [
    $fl['file_name'],
    fmtSize((int) $fl['file_size']),
    match ($fl['integrity_status']) {
        'VALID'        => 'Valid',
        'TAMPERED'     => 'Tampered',
        'NO_WATERMARK' => 'No Watermark',
        default        => $fl['integrity_status'],
    },
    $fl['analyst_name'] ?? '—',
    $fl['extracted_user_name'] ?? '—',
    $fl['extracted_ip'] ?? '—',
    $fl['crypto_verified'] === null ? '—' : ($fl['crypto_verified'] ? 'Valid' : 'Invalid'),
    date('M d, Y H:i', strtotime($fl['analyzed_at'])),
], $forensicLogs);
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Generate Report - StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <!-- PDF Generation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#667eea",
                        "background-light": "#f5f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        @media print {
            @page {
                size: landscape;
                margin: 8mm 12mm;
            }

            * {
                margin: 0 !important;
                padding: 0 !important;
            }

            aside,
            header,
            #printBtn,
            #downloadPdfBtn,
            #reportFilters {
                display: none !important;
            }

            .flex-1.ml-64 {
                margin-left: 0 !important;
            }

            body {
                background: white !important;
                color: #000 !important;
            }

            #printableReport {
                background: white !important;
                border: none !important;
                box-shadow: none !important;
                padding: 8mm !important;
                border-radius: 0 !important;
            }

            .dark\:bg-background-dark {
                background: white !important;
            }

            .dark\:bg-slate-900 {
                background: white !important;
            }

            .dark\:text-white {
                color: #000 !important;
            }

            .dark\:text-slate-400,
            .dark\:text-slate-300 {
                color: #555 !important;
            }

            .dark\:border-slate-800,
            .dark\:border-slate-700 {
                border-color: #ccc !important;
            }

            .bg-white {
                background: white !important;
            }

            .shadow-sm,
            .shadow-2xl {
                box-shadow: none !important;
            }

            .p-8 {
                padding: 0 !important;
            }

            .space-y-8>*+* {
                margin-top: 0 !important;
            }
        }

        /* Document Styling for Report */
        .report-header {
            border-bottom: 2px solid #334155;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .report-section-title {
            background-color: #f1f5f9;
            padding: 0.5rem 1rem;
            border-left: 4px solid #667eea;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
            margin-bottom: 1rem;
        }

        .dark .report-section-title {
            background-color: #1e293b;
            color: #f1f5f9;
        }

        .table-doc {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            border-radius: 0.75rem;
            overflow: hidden;
            font-size: 0.875rem;
        }

        .table-doc th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            padding: 1rem;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }

        .table-doc td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        .table-doc tbody tr {
            transition: background-color 0.2s ease;
        }

        .table-doc tbody tr:hover {
            background-color: #f8fafc;
        }

        .table-doc tbody tr:nth-child(even) {
            background-color: rgba(248, 250, 252, 0.5);
        }

        .dark .table-doc th {
            background-color: #1e293b;
            color: #94a3b8;
            border-color: #334155;
        }

        .dark .table-doc td {
            border-color: #334155;
            color: #cbd5e1;
        }

        .dark .table-doc tbody tr:hover {
            background-color: rgba(30, 41, 59, 0.6);
        }

        .dark .table-doc tbody tr:nth-child(even) {
            background-color: rgba(30, 41, 59, 0.3);
        }

        .section-table-wrap {
            overflow-x: auto;
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }

        .section-table-wrap:hover {
            border-color: rgba(148, 163, 184, 0.4);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .dark .section-table-wrap {
            border-color: rgba(51, 65, 85, 0.9);
        }

        .dark .section-table-wrap:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .metric-card {
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 0.85rem;
            padding: 1.25rem;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.9));
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            border-color: rgba(102, 126, 234, 0.5);
            background: linear-gradient(145deg, rgba(248, 250, 252, 0.98), rgba(255, 255, 255, 0.95));
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.1);
        }

        .dark .metric-card {
            border-color: rgba(51, 65, 85, 0.8);
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.7));
        }

        .dark .metric-card:hover {
            border-color: rgba(102, 126, 234, 0.6);
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.95));
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
        }

        .chart-panel {
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 0.9rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.75);
            transition: all 0.3s ease;
        }

        .dark .chart-panel {
            border-color: rgba(51, 65, 85, 0.9);
            background: rgba(15, 23, 42, 0.75);
        }

        .chart-panel:hover {
            border-color: rgba(148, 163, 184, 0.5);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        .dark .chart-panel:hover {
            border-color: rgba(148, 163, 184, 0.4);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
        }

        .chart-canvas-wrap {
            position: relative;
            height: 240px;
        }

        /* Hide elements during PDF generation */
        .pdf-hidden {
            display: none !important;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex">

    <!-- ═══════════════════════════════════════
         FIXED LEFT SIDEBAR
    ═══════════════════════════════════════ -->
    <aside
        class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10">
                <img src="../PGMN%20LOGOS%20white.png" alt="PGMN Inc. Logo"
                    class="h-12 w-auto object-contain dark:invert-0 invert" />
                <div class="flex flex-col justify-center">
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">PGMN Inc.</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Security Suite</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex flex-col gap-1 flex-1">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="dashboard.php">
                    <span class="material-symbols-outlined text-[22px]">dashboard</span>
                    <p class="text-sm font-medium">Dashboard</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="projects.php">
                    <span class="material-symbols-outlined text-[22px]">folder_managed</span>
                    <p class="text-sm font-medium">Projects</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="analysis.php">
                    <span class="material-symbols-outlined text-[22px]">policy</span>
                    <p class="text-sm font-medium">Forensic Analysis</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="users.php">
                    <span class="material-symbols-outlined text-[22px]">group</span>
                    <p class="text-sm font-medium">User Management</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="activity.php">
                    <span class="material-symbols-outlined text-[22px]">history</span>
                    <p class="text-sm font-medium">Activity Logs</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white" href="reports.php">
                    <span class="material-symbols-outlined text-[22px]"
                        style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">summarize</span>
                    <p class="text-sm font-medium">Reports</p>
                </a>
            </nav>

            <!-- User Profile (click to open settings) -->
            <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                <button onclick="openSettings()"
                    class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group text-left">
                    <div id="sidebarProfileAvatar"
                        class="bg-primary rounded-full size-10 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p id="sidebarProfileName"
                            class="text-slate-900 dark:text-white text-sm font-semibold truncate">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400 text-xs capitalize">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </p>
                    </div>
                    <span
                        class="material-symbols-outlined text-slate-400 group-hover:text-primary text-[18px] transition-colors">settings</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- ═══════════════════════════════════════
         MAIN CONTENT AREA
    ═══════════════════════════════════════ -->
    <div class="flex-1 ml-64 flex flex-col min-h-screen">

        <!-- Sticky Top Header -->
        <header
            class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center gap-6">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight flex-shrink-0">Generate Report
            </h2>
            <?php include '../includes/search_bar.php'; ?>
            <div id="reportFilters" class="flex items-center gap-1.5 flex-shrink-0">
                <?php foreach (['all' => 'All Time', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $val => $label): ?>
                <a href="?period=<?php echo $val; ?>"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border
                    <?php echo $period === $val
                        ? 'bg-primary text-white border-primary shadow-sm'
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700'; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
                <div
                    class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-500 text-xs font-semibold">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    System: Operational
                </div>
                <!-- <button id="downloadPdfBtn" onclick="generateProfessionalPDF()"
                    class="flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-all shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                    Generate PDF Report
                </button> -->
                <button id="downloadPdfBtn" onclick="generateProfessionalPDF()"
                    class="flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-bold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-all border border-slate-200 dark:border-slate-700">
                    <span class="material-symbols-outlined text-[18px]">print</span>
                    Generate Report
                </button>
            </div>
        </header>

        <div class="p-8 space-y-8 max-w-[1400px] mx-auto w-full">

            <!-- Report Header -->
            <div id="printableReport"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 md:p-10 shadow-sm">

                <!-- Professional Header -->
                <div class="report-header flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="bg-primary rounded-lg p-2.5">
                                <span class="material-symbols-outlined text-white text-[24px]">shield</span>
                            </div>
                            <div>
                                <h1
                                    class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">
                                    StegaVault</h1>
                                <p class="text-[10px] text-primary font-bold uppercase tracking-[0.3em]">Secure Asset
                                    Management System</p>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-200 mt-6">System Status & Forensic
                            Audit Report</h2>
                        <p class="text-slate-500 text-sm">Document ID: SV-REP-
                            <?php echo date('Ymd-Hi'); ?>
                            &nbsp;&mdash;&nbsp;
                            <span class="font-semibold text-primary"><?php echo htmlspecialchars($periodLabel); ?></span>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="mb-4">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Generated On</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                <?php echo $generatedAt; ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Confidentiality
                                Level</p>
                            <span
                                class="px-2 py-1 bg-red-500/10 text-red-600 border border-red-500/20 rounded text-[10px] font-bold">TOP
                                SECRET / ADMIN ONLY</span>
                        </div>
                    </div>
                </div>

                <!-- ─ SECTION 1: Executive Summary - Dashboard Layout ───────────────────── -->
                <div id="sec-overview" class="mb-12">
                    <div class="report-section-title mb-6">I. Executive Summary & Key Metrics</div>

                    <!-- KPI Cards - More Prominent Layout -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
                        <!-- Total Users Card -->
                        <div
                            class="metric-card border-2 border-slate-200 dark:border-slate-700 hover:border-primary/40 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p
                                        class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400 font-bold">
                                        Total Users</p>
                                    <p class="text-3xl font-black text-slate-900 dark:text-white mt-2">
                                        <?php echo $totalUsers; ?>
                                    </p>
                                </div>
                                <div
                                    class="flex items-center justify-center size-12 rounded-lg bg-blue-500/10 text-blue-600">
                                    <span class="material-symbols-outlined text-[20px]">groups</span>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div><span class="text-slate-500">Active:</span> <span
                                            class="font-bold text-emerald-600">
                                            <?php echo $activeUsers; ?>
                                        </span></div>
                                    <div><span class="text-slate-500">Locked:</span> <span
                                            class="font-bold text-rose-600">
                                            <?php echo $disabledUsers; ?>
                                        </span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Files Card -->
                        <div
                            class="metric-card border-2 border-slate-200 dark:border-slate-700 hover:border-primary/40 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p
                                        class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400 font-bold">
                                        Total Files</p>
                                    <p class="text-3xl font-black text-slate-900 dark:text-white mt-2">
                                        <?php echo $totalFiles; ?>
                                    </p>
                                </div>
                                <div
                                    class="flex items-center justify-center size-12 rounded-lg bg-purple-500/10 text-purple-600">
                                    <span class="material-symbols-outlined text-[20px]">document_scanner</span>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                                <p class="text-xs text-slate-500">Storage: <span
                                        class="font-semibold text-slate-900 dark:text-slate-100">
                                        <?php echo fmtSize($totalFileSize); ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Watermarked Card -->
                        <div
                            class="metric-card border-2 border-slate-200 dark:border-slate-700 hover:border-primary/40 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p
                                        class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400 font-bold">
                                        Watermarked</p>
                                    <p class="text-3xl font-black text-slate-900 dark:text-white mt-2">
                                        <?php echo $watermarkedFiles; ?>
                                    </p>
                                </div>
                                <div
                                    class="flex items-center justify-center size-12 rounded-lg bg-indigo-500/10 text-indigo-600">
                                    <span class="material-symbols-outlined text-[20px]">verified</span>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                                <p class="text-xs text-slate-500">Coverage: <span
                                        class="font-semibold text-slate-900 dark:text-slate-100">
                                        <?php echo $totalFiles > 0 ? round(($watermarkedFiles / $totalFiles) * 100) : 0; ?>%
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Projects Card -->
                        <div
                            class="metric-card border-2 border-slate-200 dark:border-slate-700 hover:border-primary/40 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p
                                        class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400 font-bold">
                                        Projects</p>
                                    <p class="text-3xl font-black text-slate-900 dark:text-white mt-2">
                                        <?php echo $totalProjects; ?>
                                    </p>
                                </div>
                                <div
                                    class="flex items-center justify-center size-12 rounded-lg bg-orange-500/10 text-orange-600">
                                    <span class="material-symbols-outlined text-[20px]">folder_open</span>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                                <p class="text-xs text-slate-500">Active: <span
                                        class="font-semibold text-slate-900 dark:text-slate-100">
                                        <?php echo $activeProjects; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section - Organized Layout -->
                    <div id="chartsSection">
                        <!-- Top Row: Large Charts -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
                            <!-- User Status Chart - Wider -->
                            <div class="lg:col-span-1 chart-panel">
                                <p
                                    class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                                    User Status Distribution
                                </p>
                                <div class="chart-canvas-wrap" style="height: 220px;">
                                    <canvas id="userStatusChart"></canvas>
                                </div>
                            </div>

                            <!-- Role Distribution Chart -->
                            <div class="lg:col-span-1 chart-panel">
                                <p
                                    class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">people</span>
                                    Role Breakdown
                                </p>
                                <div class="chart-canvas-wrap" style="height: 220px;">
                                    <canvas id="roleDistributionChart"></canvas>
                                </div>
                            </div>

                            <!-- Watermark Coverage -->
                            <div class="lg:col-span-1 chart-panel">
                                <p
                                    class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">water_damage</span>
                                    Watermark Coverage
                                </p>
                                <div class="chart-canvas-wrap" style="height: 220px;">
                                    <canvas id="watermarkCoverageChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Row: Upload Trend (Full Width) -->
                        <div class="chart-panel mb-2">
                            <p
                                class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">trending_up</span>
                                Upload Activity (<?php echo $trendDays; ?> Days)
                            </p>
                            <div class="chart-canvas-wrap" style="height: 200px;">
                                <canvas id="uploadTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="border-slate-200 dark:border-slate-800 my-10" />

                <!-- ─ SECTION 2: Project Inventory ─────────────────── -->
                <div id="sec-projects" class="mb-12">
                    <div class="report-section-title mb-6">II. Project Inventory</div>
                    <div class="section-table-wrap">
                        <table class="table-doc">
                            <thead>
                                <tr
                                    class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Project</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Status</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Creator</th>
                                    <th
                                        class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">
                                        Members</th>
                                    <th
                                        class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">
                                        Files</th>
                                    <th
                                        class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">
                                        Storage</th>
                                    <th
                                        class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">
                                        Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php if (count($projects) === 0): ?>
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500">No
                                        projects found</td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($projects as $p): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="size-3 rounded-sm flex-shrink-0"
                                                style="background-color:<?php echo htmlspecialchars($p['color'] ?? '#667eea'); ?>">
                                            </div>
                                            <span class="font-semibold text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($p['name']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <?php
                                            $sc = match ($p['status']) {
                                                'active' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                                'archived' => 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700',
                                                'completed' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
                                                default => 'bg-slate-100 text-slate-500 border-slate-200'
                                            };
                                            ?>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border <?php echo $sc; ?> capitalize">
                                            <?php echo $p['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                                        <?php echo htmlspecialchars($p['creator_name'] ?? '—'); ?>
                                    </td>
                                    <td class="px-5 py-3 text-center font-semibold text-slate-700 dark:text-slate-300">
                                        <?php echo $p['member_count']; ?>
                                    </td>
                                    <td class="px-5 py-3 text-center font-semibold text-slate-700 dark:text-slate-300">
                                        <?php echo $p['file_count']; ?>
                                    </td>
                                    <td class="px-5 py-3 text-right text-slate-500 dark:text-slate-400">
                                        <?php echo fmtSize((int) $p['total_size']); ?>
                                    </td>
                                    <td class="px-5 py-3 text-right text-slate-500 dark:text-slate-400">
                                        <?php echo date('M d, Y', strtotime($p['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ─ SECTION 3: Personnel Registry ───────────────────── -->
                <div id="sec-users" class="mb-12">
                    <div class="report-section-title mb-6">III. Personnel Registry</div>

                    <?php
                    $groupedUsers = ['admin' => [], 'employee' => [], 'collaborator' => []];
                    foreach ($userRows as $u) {
                        $role = $u['role'] ?? 'employee';
                        if (!isset($groupedUsers[$role])) {
                            $groupedUsers[$role] = [];
                        }
                        $groupedUsers[$role][] = $u;
                    }
                    ?>

                    <?php foreach ($groupedUsers as $role => $users): ?>
                    <?php if (count($users) > 0): ?>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-3 ml-1 capitalize">
                        <?php echo htmlspecialchars($role); ?> Accounts
                    </h3>
                    <div class="section-table-wrap mb-8">
                        <table class="table-doc" id="table-users-<?php echo htmlspecialchars($role); ?>">
                            <thead>
                                <tr
                                    class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        User</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">
                                        Projects</th>
                                    <th
                                        class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">
                                        Files</th>
                                    <th
                                        class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">
                                        Joined</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="size-8 rounded-full flex items-center justify-center font-bold text-xs <?php echo $role === 'admin' ? 'bg-purple-500/10 text-purple-500' : 'bg-blue-500/10 text-blue-500'; ?>">
                                                <?php echo strtoupper(substr($u['name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-900 dark:text-white">
                                                    <?php echo htmlspecialchars($u['name']); ?>
                                                </p>
                                                <p class="text-xs text-slate-400 dark:text-slate-500">
                                                    <?php echo htmlspecialchars($u['email']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <?php
                                                    $sclass = match ($u['status'] ?? 'active') {
                                                        'active' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                                        'pending_activation' => 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-500/20',
                                                        'disabled' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                                                        'expired' => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
                                                        default => 'bg-slate-100 text-slate-500 border-slate-200'
                                                    };
                                                    $slabel = match ($u['status'] ?? 'active') {
                                                        'active' => 'Active',
                                                        'pending_activation' => 'Pending',
                                                        'disabled' => 'Locked',
                                                        'expired' => 'Expired',
                                                        default => ucfirst($u['status'] ?? 'Active')
                                                    };
                                                    ?>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border <?php echo $sclass; ?>">
                                            <?php echo $slabel; ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-center font-semibold text-slate-700 dark:text-slate-300">
                                        <?php echo $u['project_count']; ?>
                                    </td>
                                    <td class="px-5 py-3 text-center font-semibold text-slate-700 dark:text-slate-300">
                                        <?php echo $u['file_count']; ?>
                                    </td>
                                    <td class="px-5 py-3 text-right text-slate-500 dark:text-slate-400">
                                        <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- ─ SECTION 4: Recent Data Uploads ──────────────────── -->
                <div id="sec-files" class="mb-12">
                    <div class="report-section-title mb-6">IV. Recent Data Uploads</div>
                    <div class="section-table-wrap">
                        <table class="table-doc">
                            <thead>
                                <tr
                                    class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        File</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Type</th>
                                    <th
                                        class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">
                                        Size</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Uploaded By</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Project</th>
                                    <th
                                        class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">
                                        Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php if (count($recentFiles) === 0): ?>
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500">No
                                        files uploaded yet</td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($recentFiles as $f): ?>
                                <?php
                                    $mime = $f['mime_type'] ?? '';
                                    $isImg = str_starts_with($mime, 'image/');
                                    $isVid = str_starts_with($mime, 'video/');
                                    $icon = $isImg ? 'image' : ($isVid ? 'movie' : 'description');
                                    $ic = $isImg ? 'text-purple-500 bg-purple-500/10' : ($isVid ? 'text-red-500 bg-red-500/10' : 'text-blue-500 bg-blue-500/10');
                                    ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="size-7 rounded-md <?php echo $ic; ?> flex items-center justify-center flex-shrink-0">
                                                <span class="material-symbols-outlined text-[14px]">
                                                    <?php echo $icon; ?>
                                                </span>
                                            </div>
                                            <span
                                                class="font-medium text-slate-900 dark:text-white truncate max-w-[200px]">
                                                <?php echo htmlspecialchars($f['original_name']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-400 dark:text-slate-500">
                                        <?php echo htmlspecialchars($mime ?: '—'); ?>
                                    </td>
                                    <td class="px-5 py-3 text-right text-slate-500 dark:text-slate-400">
                                        <?php echo fmtSize((int) $f['file_size']); ?>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                                        <?php echo htmlspecialchars($f['uploader'] ?? '—'); ?>
                                    </td>
                                    <td class="px-5 py-3">
                                        <?php if ($f['project_name']): ?>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-primary/10 text-primary border border-primary/20">
                                            <?php echo htmlspecialchars($f['project_name']); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-xs text-slate-400 dark:text-slate-500">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3 text-right text-slate-500 dark:text-slate-400">
                                        <?php echo date('M d, Y', strtotime($f['upload_date'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ─ SECTION 5: Forensic Audit Trail ────────────────── -->
                <div id="sec-activity" class="mb-12">
                    <div class="report-section-title mb-6">V. Forensic Audit Trail (Recent Activity)</div>
                    <div class="section-table-wrap">
                        <table class="table-doc">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Actor</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activityLog as $log): ?>
                                <tr>
                                    <td>
                                        <p class="font-bold text-slate-900 dark:text-white">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            <?php echo htmlspecialchars($log['description']); ?>
                                        </p>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($log['actor'] ?? 'System'); ?>
                                    </td>
                                    <td class="text-right text-xs">
                                        <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ─ SECTION 6: Forensic Analysis History ───────────── -->
                <div id="sec-forensic" class="mb-12">
                    <div class="report-section-title mb-6">VI. Forensic File Analysis History</div>

                    <!-- KPI strip -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="section-table-wrap text-center py-4">
                            <p class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $totalForensicScans; ?></p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Total Scans</p>
                        </div>
                        <div class="section-table-wrap text-center py-4">
                            <p class="text-2xl font-black text-rose-500"><?php echo $tamperedCount; ?></p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">Tampered Files</p>
                        </div>
                        <div class="section-table-wrap text-center py-4">
                            <p class="text-2xl font-black text-amber-500"><?php echo $noWatermarkCount; ?></p>
                            <p class="text-xs text-slate-400 uppercase tracking-wider mt-1">No Watermark</p>
                        </div>
                    </div>

                    <!-- Forensic Status Filter -->
                    <div class="flex items-center gap-2 mb-4 flex-wrap">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mr-1">Filter by result:</span>
                        <button id="ffilter-all" onclick="filterForensic('all')"
                            class="ffilter-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border bg-primary text-white border-primary shadow-sm">
                            All
                        </button>
                        <button id="ffilter-VALID" onclick="filterForensic('VALID')"
                            class="ffilter-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border bg-slate-100 dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
                            Valid
                        </button>
                        <button id="ffilter-TAMPERED" onclick="filterForensic('TAMPERED')"
                            class="ffilter-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border bg-slate-100 dark:bg-slate-800 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-50 dark:hover:bg-rose-900/20">
                            Tampered
                        </button>
                        <button id="ffilter-NO_WATERMARK" onclick="filterForensic('NO_WATERMARK')"
                            class="ffilter-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border bg-slate-100 dark:bg-slate-800 text-amber-600 dark:text-amber-400 border-amber-500/20 hover:bg-amber-50 dark:hover:bg-amber-900/20">
                            No Watermark
                        </button>
                    </div>

                    <div class="section-table-wrap">
                        <table class="table-doc">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">File Analyzed</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Result</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Analyst</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Leaked To</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Leak IP</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Crypto</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Analyzed At</th>
                                </tr>
                            </thead>
                            <tbody id="forensic-tbody" class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php if (empty($forensicLogs)): ?>
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-slate-400 dark:text-slate-500">No forensic analyses have been run yet</td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach ($forensicLogs as $fl): ?>
                                <?php
                                    $rs = $fl['integrity_status'];
                                    $rsClass = match($rs) {
                                        'VALID'         => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                        'TAMPERED'      => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                                        'NO_WATERMARK'  => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                                        default         => 'bg-slate-100 text-slate-500 border-slate-200',
                                    };
                                    $rsLabel = match($rs) {
                                        'VALID'        => 'Valid',
                                        'TAMPERED'     => 'Tampered',
                                        'NO_WATERMARK' => 'No Watermark',
                                        default        => $rs,
                                    };
                                    $cryptoVal = $fl['crypto_verified'];
                                    if ($cryptoVal === null) {
                                        $cryptoLabel = '—'; $cryptoClass = 'text-slate-400';
                                    } elseif ($cryptoVal) {
                                        $cryptoLabel = 'Valid'; $cryptoClass = 'text-emerald-500';
                                    } else {
                                        $cryptoLabel = 'Invalid'; $cryptoClass = 'text-rose-500';
                                    }
                                    $leakedTo = $fl['extracted_user_name']
                                        ? htmlspecialchars($fl['extracted_user_name']) . ($fl['extracted_user_role'] ? ' <span class="text-slate-400">(' . htmlspecialchars($fl['extracted_user_role']) . ')</span>' : '')
                                        : '<span class="text-slate-400">—</span>';
                                    $leakIp = $fl['extracted_ip'] ?? null;
                                    $leakIpDisplay = $leakIp ? htmlspecialchars($leakIp) : '—';
                                ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors" data-status="<?php echo $rs; ?>">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-slate-900 dark:text-white truncate max-w-[180px]"><?php echo htmlspecialchars($fl['file_name']); ?></p>
                                        <p class="text-xs text-slate-400"><?php echo fmtSize((int)$fl['file_size']); ?></p>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border <?php echo $rsClass; ?>"><?php echo $rsLabel; ?></span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($fl['analyst_name'] ?? '—'); ?></td>
                                    <td class="px-5 py-3 text-sm text-slate-500 dark:text-slate-400"><?php echo $leakedTo; ?></td>
                                    <td class="px-5 py-3 font-mono text-xs text-slate-500 dark:text-slate-400"><?php echo $leakIpDisplay; ?></td>
                                    <td class="px-5 py-3 text-center text-xs font-semibold <?php echo $cryptoClass; ?>"><?php echo $cryptoLabel; ?></td>
                                    <td class="px-5 py-3 text-right text-xs text-slate-400"><?php echo date('M d, Y H:i', strtotime($fl['analyzed_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Report Footer -->
                <div class="border-t-2 border-slate-100 pt-8 mt-12 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-slate-400 text-[18px]">verified</span>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">End of Forensic
                            Document SV-REP-
                            <?php echo date('Ymd'); ?>
                        </p>
                    </div>
                    <p class="text-[10px] text-slate-400 font-medium">Page 1 of 1</p>
                </div>
            </div> <!-- End of printableReport -->
        </div>
    </div>

    <script>
        // ── Chart data ───────────────────────────────────────────────
        const statusChartData   = <?= json_encode($statusChart); ?>;
        const roleChartData     = <?= json_encode($roleChart); ?>;
        const watermarkChartData= <?= json_encode($watermarkChart); ?>;
        const uploadTrendLabels = <?= json_encode($uploadTrendLabels); ?>;
        const uploadTrendCounts = <?= json_encode($uploadTrendCounts); ?>;

        // ── PDF data arrays (plain text, no HTML tags) ───────────────
        const projectsPDF  = <?= json_encode($projectsPDF); ?>;
        const usersPDF     = <?= json_encode($usersPDF); ?>;
        const filesPDF     = <?= json_encode($filesPDF); ?>;
        const activityPDF  = <?= json_encode($activityPDF); ?>;
        const forensicPDF  = <?= json_encode($forensicPDF); ?>;

        function initReportCharts() {
            const isDark    = document.documentElement.classList.contains('dark');
            const axisColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(100,116,139,0.2)' : 'rgba(148,163,184,0.25)';
            const legendCfg = { position: 'bottom', labels: { color: axisColor, boxWidth: 10, usePointStyle: true, pointStyle: 'circle' } };

            // User Status — doughnut (was bar, which looked stretched in narrow column)
            new Chart(document.getElementById('userStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: statusChartData.labels,
                    datasets: [{ data: statusChartData.values, backgroundColor: ['#10b981','#f59e0b','#f43f5e','#ef4444'], borderWidth: 0 }]
                },
                options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: legendCfg } }
            });

            // Role Breakdown
            new Chart(document.getElementById('roleDistributionChart'), {
                type: 'doughnut',
                data: {
                    labels: roleChartData.labels,
                    datasets: [{ data: roleChartData.values, backgroundColor: ['#8b5cf6','#3b82f6','#f97316'], borderWidth: 0 }]
                },
                options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: legendCfg } }
            });

            // Watermark Coverage
            new Chart(document.getElementById('watermarkCoverageChart'), {
                type: 'doughnut',
                data: {
                    labels: watermarkChartData.labels,
                    datasets: [{ data: watermarkChartData.values, backgroundColor: ['#6366f1','#94a3b8'], borderWidth: 0 }]
                },
                options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: legendCfg } }
            });

            // Upload Trend
            new Chart(document.getElementById('uploadTrendChart'), {
                type: 'line',
                data: {
                    labels: uploadTrendLabels,
                    datasets: [{ label: 'Uploads', data: uploadTrendCounts, borderColor: '#667eea', backgroundColor: 'rgba(102,126,234,0.15)', fill: true, tension: 0.35, pointRadius: 3, pointBackgroundColor: '#667eea' }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: axisColor }, grid: { color: gridColor } },
                        y: { beginAtZero: true, ticks: { color: axisColor, precision: 0 }, grid: { color: gridColor } }
                    }
                }
            });
        }

        function scrollTo(id) {
            document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // ── Forensic table filter ─────────────────────────────────────
        function filterForensic(status) {
            document.querySelectorAll('.ffilter-btn').forEach(btn => {
                btn.className = 'ffilter-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700';
            });
            const active = document.getElementById('ffilter-' + status);
            if (active) active.className = 'ffilter-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border bg-primary text-white border-primary shadow-sm';
            document.querySelectorAll('#forensic-tbody tr[data-status]').forEach(row => {
                row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
            });
        }

        document.addEventListener('DOMContentLoaded', initReportCharts);

        async function generateProfessionalPDF() {
            const btn = document.getElementById('downloadPdfBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span> Generating PDF...';
            btn.disabled = true;

            try {
                await new Promise(r => setTimeout(r, 400));

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                const pW = doc.internal.pageSize.getWidth();   // 297
                const pH = doc.internal.pageSize.getHeight();  // 210
                const lm = 14, rm = 14, cW = pW - lm - rm;
                const SAFE_BOTTOM = pH - 10; // footer starts at pH-9

                const PERIOD = '<?php echo addslashes($periodLabel); ?>';
                const GEN_AT = '<?php echo $generatedAt; ?>';
                const DOC_ID = 'SV-REP-<?php echo date('Ymd-Hi'); ?>';
                const TREND_D = <?php echo $trendDays; ?>;

                const C = {
                    primary:[102,126,234], dark:[15,23,42],    navy:[30,41,59],
                    slate:[71,85,105],     muted:[148,163,184], border:[226,232,240],
                    bg:[248,250,252],      white:[255,255,255],
                    purple:[139,92,246],   orange:[249,115,22], indigo:[99,102,241],
                    rose:[244,63,94],      amber:[245,158,11],  emerald:[16,185,129],
                };

                const drawHeader = (title) => {
                    doc.setFillColor(...C.dark);   doc.rect(0, 0, pW, 20, 'F');
                    doc.setFillColor(...C.primary); doc.rect(0, 20, pW, 1.8, 'F');
                    doc.setFontSize(12); doc.setFont(undefined,'bold'); doc.setTextColor(...C.white);
                    doc.text('StegaVault', lm, 10);
                    doc.setFontSize(6.5); doc.setFont(undefined,'normal'); doc.setTextColor(...C.muted);
                    doc.text('SECURE ASSET MANAGEMENT SYSTEM', lm, 16);
                    doc.setDrawColor(50,65,90); doc.line(lm+58, 4, lm+58, 18);
                    doc.setFontSize(10); doc.setFont(undefined,'bold'); doc.setTextColor(220,228,255);
                    doc.text(title, pW/2, 10, {align:'center'});
                    doc.setFontSize(7); doc.setFont(undefined,'normal'); doc.setTextColor(160,178,240);
                    doc.text(PERIOD, pW/2, 16.5, {align:'center'});
                    doc.setFontSize(6.5); doc.setTextColor(...C.muted);
                    doc.text(DOC_ID, pW-rm, 9, {align:'right'});
                    doc.text(GEN_AT, pW-rm, 15.5, {align:'right'});
                };

                const drawFooter = (n, tot) => {
                    doc.setFillColor(...C.bg); doc.rect(0, pH-9, pW, 9, 'F');
                    doc.setDrawColor(...C.border); doc.line(lm, pH-9, pW-rm, pH-9);
                    doc.setFontSize(6.5); doc.setFont(undefined,'normal'); doc.setTextColor(...C.muted);
                    doc.text('StegaVault Security Suite  ·  '+DOC_ID+'  ·  CONFIDENTIAL — ADMIN ONLY', lm, pH-3.5);
                    doc.text('Page '+n+' of '+tot, pW-rm, pH-3.5, {align:'right'});
                };

                const drawSection = (label, y) => {
                    doc.setFillColor(237,241,255); doc.rect(lm, y, cW, 7.5, 'F');
                    doc.setFillColor(...C.primary); doc.rect(lm, y, 3.5, 7.5, 'F');
                    doc.setFontSize(7.5); doc.setFont(undefined,'bold'); doc.setTextColor(...C.navy);
                    doc.text(label.toUpperCase(), lm+7, y+5.2);
                    return y + 12;
                };

                const drawKpiCard = (x, y, w, h, label, value, sub, sv, accent) => {
                    doc.setFillColor(...C.white); doc.rect(x, y, w, h, 'F');
                    doc.setDrawColor(...C.border); doc.rect(x, y, w, h, 'S');
                    doc.setFillColor(...accent); doc.rect(x, y, w, 2.5, 'F');
                    doc.setFontSize(6.5); doc.setFont(undefined,'bold'); doc.setTextColor(...C.muted);
                    doc.text(label.toUpperCase(), x+4, y+8.5);
                    doc.setFontSize(20); doc.setFont(undefined,'bold'); doc.setTextColor(...C.dark);
                    doc.text(String(value), x+4, y+21);
                    doc.setFontSize(7); doc.setFont(undefined,'normal'); doc.setTextColor(...C.slate);
                    const sw = doc.getTextWidth(sub+': ');
                    doc.text(sub+': ', x+4, y+h-4);
                    doc.setFont(undefined,'bold'); doc.setTextColor(...accent);
                    doc.text(String(sv), x+4+sw, y+h-4);
                };

                const tbl = {
                    margin: {left:lm, right:rm}, theme:'grid',
                    styles: {fontSize:7.5, cellPadding:2.8, textColor:C.slate, lineColor:C.border, lineWidth:0.15},
                    headStyles: {fillColor:C.primary, textColor:C.white, fontStyle:'bold', fontSize:7.5, cellPadding:3},
                    alternateRowStyles: {fillColor:C.bg},
                };

                // ── PAGE 1: Executive Summary + Charts ───────────────
                drawHeader('System Status & Forensic Audit Report');
                let y = 26;

                // KPI cards (4 across)
                const gap=4, cardW=(cW-gap*3)/4, cardH=34;
                [
                    {label:'Total Users',  v:'<?php echo $totalUsers; ?>',       sub:'Active',   sv:'<?php echo $activeUsers; ?>',    acc:C.primary},
                    {label:'Total Files',  v:'<?php echo $totalFiles; ?>',       sub:'Storage',  sv:'<?php echo fmtSize($totalFileSize); ?>', acc:C.purple},
                    {label:'Watermarked',  v:'<?php echo $watermarkedFiles; ?>', sub:'Coverage', sv:'<?php echo $totalFiles>0?round(($watermarkedFiles/$totalFiles)*100):0; ?>%', acc:C.indigo},
                    {label:'Projects',     v:'<?php echo $totalProjects; ?>',    sub:'Active',   sv:'<?php echo $activeProjects; ?>', acc:C.orange},
                ].forEach((c,i) => drawKpiCard(lm+i*(cardW+gap), y, cardW, cardH, c.label, c.v, c.sub, c.sv, c.acc));
                y += cardH + 6;

                // 3 doughnut charts side-by-side (matches HTML layout)
                const sm3W=(cW-gap*2)/3, sm3H=60;
                [{id:'userStatusChart',title:'User Status'},{id:'roleDistributionChart',title:'Role Breakdown'},{id:'watermarkCoverageChart',title:'Watermark Coverage'}]
                .forEach((ch,i) => {
                    const cx=lm+i*(sm3W+gap), cy=y;
                    doc.setFillColor(...C.white); doc.rect(cx,cy,sm3W,sm3H,'F');
                    doc.setDrawColor(...C.border); doc.rect(cx,cy,sm3W,sm3H,'S');
                    doc.setFillColor(...C.primary); doc.rect(cx,cy,sm3W,1.5,'F');
                    doc.setFontSize(7); doc.setFont(undefined,'bold'); doc.setTextColor(...C.navy);
                    doc.text(ch.title, cx+4, cy+8);
                    const cvs = document.getElementById(ch.id);
                    if (cvs) doc.addImage(cvs.toDataURL('image/png',1.0),'PNG',cx+3,cy+10,sm3W-6,sm3H-13);
                });
                y += sm3H + 4;

                // Upload trend full-width (y=130, height=44 → bottom=174 < 201 ✓)
                const tH=44;
                doc.setFillColor(...C.white); doc.rect(lm,y,cW,tH,'F');
                doc.setDrawColor(...C.border); doc.rect(lm,y,cW,tH,'S');
                doc.setFillColor(...C.primary); doc.rect(lm,y,cW,1.5,'F');
                doc.setFontSize(7); doc.setFont(undefined,'bold'); doc.setTextColor(...C.navy);
                doc.text('Upload Activity ('+TREND_D+' Days)', lm+4, y+8);
                const tCvs = document.getElementById('uploadTrendChart');
                if (tCvs) doc.addImage(tCvs.toDataURL('image/png',1.0),'PNG',lm+3,y+10,cW-6,tH-13);

                // ── PAGE 2: Project Inventory + Personnel Registry ───
                doc.addPage(); drawHeader('Project Inventory & Personnel Registry'); y=26;

                y = drawSection('I.  Project Inventory', y);
                doc.autoTable({
                    head:[['Project','Status','Creator','Members','Files','Storage','Created']],
                    body: projectsPDF.length ? projectsPDF : [['No projects found in this period','','','','','','']],
                    startY:y, ...tbl, columnStyles:{0:{fontStyle:'bold',textColor:C.dark}}
                });
                y = doc.lastAutoTable.finalY + 10;

                if (y > SAFE_BOTTOM-50) { doc.addPage(); drawHeader('Personnel Registry'); y=26; }
                y = drawSection('II.  Personnel Registry', y);

                ['admin','employee','collaborator'].forEach(role => {
                    const members = usersPDF.filter(u => u.role === role);
                    if (!members.length) return;
                    if (y > SAFE_BOTTOM-40) { doc.addPage(); drawHeader('Personnel Registry (cont.)'); y=26; }
                    doc.setFontSize(8); doc.setFont(undefined,'bold'); doc.setTextColor(...C.slate);
                    doc.text(role.charAt(0).toUpperCase()+role.slice(1)+' Accounts', lm, y); y+=5;
                    doc.autoTable({
                        head:[['Name','Email','Status','Projects','Files','Joined']],
                        body: members.map(u=>[u.name,u.email,u.status,String(u.projects),String(u.files),u.joined]),
                        startY:y, ...tbl, columnStyles:{0:{fontStyle:'bold',textColor:C.dark}}
                    });
                    y = doc.lastAutoTable.finalY + 8;
                });

                // ── PAGE 3: Recent Uploads + Activity Log ────────────
                doc.addPage(); drawHeader('Data Uploads & Forensic Audit Trail'); y=26;

                y = drawSection('III.  Recent Data Uploads', y);
                doc.autoTable({
                    head:[['File','Type','Size','Uploaded By','Project','Date']],
                    body: filesPDF.length ? filesPDF : [['No files uploaded in this period','','','','','']],
                    startY:y, ...tbl
                });
                y = doc.lastAutoTable.finalY + 10;

                if (y > SAFE_BOTTOM-50) { doc.addPage(); drawHeader('Forensic Audit Trail'); y=26; }
                y = drawSection('IV.  Forensic Audit Trail (Recent Activity)', y);
                doc.autoTable({
                    head:[['Action','Description','Actor','Timestamp']],
                    body: activityPDF.length ? activityPDF : [['No activity recorded in this period','','','']],
                    startY:y, ...tbl
                });

                // ── PAGE 4: Forensic File Analysis History ───────────
                doc.addPage(); drawHeader('Forensic File Analysis History'); y=26;

                // Forensic KPI strip
                const fkW=(cW-gap*2)/3, fkH=22;
                [
                    {label:'Total Scans',    v:'<?php echo $totalForensicScans; ?>', color:C.primary},
                    {label:'Tampered Files', v:'<?php echo $tamperedCount; ?>',      color:C.rose},
                    {label:'No Watermark',   v:'<?php echo $noWatermarkCount; ?>',   color:C.amber},
                ].forEach((k,i) => {
                    const kx=lm+i*(fkW+gap);
                    doc.setFillColor(...C.white); doc.rect(kx,y,fkW,fkH,'F');
                    doc.setDrawColor(...C.border); doc.rect(kx,y,fkW,fkH,'S');
                    doc.setFillColor(...k.color); doc.rect(kx,y,fkW,2,'F');
                    doc.setFontSize(16); doc.setFont(undefined,'bold'); doc.setTextColor(...k.color);
                    doc.text(k.v, kx+fkW/2, y+13, {align:'center'});
                    doc.setFontSize(6.5); doc.setFont(undefined,'normal'); doc.setTextColor(...C.muted);
                    doc.text(k.label.toUpperCase(), kx+fkW/2, y+19, {align:'center'});
                });
                y += fkH + 8;

                y = drawSection('V.  Forensic File Analysis History', y);
                doc.autoTable({
                    head:[['File','Size','Result','Analyst','Leaked To','Leak IP','Crypto','Analyzed At']],
                    body: forensicPDF.length ? forensicPDF : [['No forensic analyses have been run yet','','','','','','','']],
                    startY:y, ...tbl,
                    columnStyles: {2:{cellWidth:22}},
                    didParseCell: (data) => {
                        if (data.section==='body' && data.column.index===2) {
                            const v = data.cell.raw;
                            if (v==='Valid')          data.cell.styles.textColor=C.emerald;
                            else if (v==='Tampered')  data.cell.styles.textColor=C.rose;
                            else if (v==='No Watermark') data.cell.styles.textColor=C.amber;
                        }
                    }
                });

                // ── Footers on all pages ─────────────────────────────
                const totalPages = doc.internal.getNumberOfPages();
                for (let p=1; p<=totalPages; p++) { doc.setPage(p); drawFooter(p, totalPages); }

                doc.save('StegaVault_'+DOC_ID+'.pdf');

            } catch (err) {
                console.error('PDF Error:', err);
                alert('PDF generation failed: ' + err.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>

    <?php include '../includes/settings_modal.php'; ?>
    <script src="../js/security-shield.js"></script>
</body>
</html>
