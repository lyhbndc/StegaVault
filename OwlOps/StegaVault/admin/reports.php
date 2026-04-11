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
    ORDER BY f.upload_date DESC
    LIMIT 10
");
$stmt->execute();
$recentFiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Activity Log ───────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT al.action, al.description, al.created_at, u.name AS actor
        FROM (SELECT * FROM activity_log_admin UNION ALL SELECT * FROM activity_log_employee UNION ALL SELECT * FROM activity_log_collaborator) al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute();
$activityLog = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Upload Trend (Last 7 Days) ───────────────────────────────
$uploadTrendMap = [];
$trendStmt = $db->prepare("
    SELECT upload_date::date AS d, COUNT(*) AS c
    FROM files
    WHERE upload_date >= CURRENT_DATE - INTERVAL '6 days'
    GROUP BY upload_date::date
    ORDER BY d ASC
");
$trendStmt->execute();
$trendRows = $trendStmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($trendRows as $row) {
    $uploadTrendMap[$row['d']] = (int) $row['c'];
}

$uploadTrendLabels = [];
$uploadTrendCounts = [];
for ($i = 6; $i >= 0; $i--) {
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

$generatedAt = date('F j, Y \a\t g:i A');
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
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
            min-height: 240px;
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
                                <div class="chart-canvas-wrap" style="height: 280px;">
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
                                <div class="chart-canvas-wrap" style="height: 280px;">
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
                                <div class="chart-canvas-wrap" style="height: 280px;">
                                    <canvas id="watermarkCoverageChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Row: Upload Trend (Full Width) -->
                        <div class="chart-panel mb-2">
                            <p
                                class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">trending_up</span>
                                Upload Activity (Last 7 Days)
                            </p>
                            <div class="chart-canvas-wrap" style="height: 240px;">
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
        const statusChartData = <?= json_encode($statusChart); ?>;
        const roleChartData = <?= json_encode($roleChart); ?>;
        const watermarkChartData = <?= json_encode($watermarkChart); ?>;
        const uploadTrendLabels = <?= json_encode($uploadTrendLabels); ?>;
        const uploadTrendCounts = <?= json_encode($uploadTrendCounts); ?>;

        function initReportCharts() {
            const isDark = document.documentElement.classList.contains('dark');
            const axisColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(100,116,139,0.2)' : 'rgba(148,163,184,0.25)';

            new Chart(document.getElementById('userStatusChart'), {
                type: 'bar',
                data: {
                    labels: statusChartData.labels,
                    datasets: [{
                        label: 'Users',
                        data: statusChartData.values,
                        backgroundColor: ['#10b981', '#f59e0b', '#f43f5e', '#ef4444'],
                        borderRadius: 6,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: axisColor
                            },
                            grid: {
                                color: gridColor
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: axisColor,
                                precision: 0
                            },
                            grid: {
                                color: gridColor
                            }
                        }
                    }
                }
            });

            new Chart(document.getElementById('roleDistributionChart'), {
                type: 'doughnut',
                data: {
                    labels: roleChartData.labels,
                    datasets: [{
                        data: roleChartData.values,
                        backgroundColor: ['#8b5cf6', '#3b82f6', '#f97316'],
                        borderWidth: 0
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: axisColor,
                                boxWidth: 10,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    }
                }
            });

            new Chart(document.getElementById('watermarkCoverageChart'), {
                type: 'doughnut',
                data: {
                    labels: watermarkChartData.labels,
                    datasets: [{
                        data: watermarkChartData.values,
                        backgroundColor: ['#6366f1', '#94a3b8'],
                        borderWidth: 0
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: axisColor,
                                boxWidth: 10,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    }
                }
            });

            new Chart(document.getElementById('uploadTrendChart'), {
                type: 'line',
                data: {
                    labels: uploadTrendLabels,
                    datasets: [{
                        label: 'Uploads',
                        data: uploadTrendCounts,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.15)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointBackgroundColor: '#667eea'
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: axisColor
                            },
                            grid: {
                                color: gridColor
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: axisColor,
                                precision: 0
                            },
                            grid: {
                                color: gridColor
                            }
                        }
                    }
                }
            });
        }

        function scrollTo(id) {
            document.getElementById(id)?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        document.addEventListener('DOMContentLoaded', initReportCharts);

        async function generateProfessionalPDF() {
            const btn = document.getElementById('downloadPdfBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span> Processing...';
            btn.disabled = true;

            try {
                // Ensure charts are rendered completely
                await new Promise(r => setTimeout(r, 200));

                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                let yPos = 20;
                const leftMargin = 15;
                const pageWidth = doc.internal.pageSize.getWidth();

                // ── HEADER ──
                doc.setFontSize(22);
                doc.setTextColor(15, 23, 42);
                doc.text("StegaVault System Report", leftMargin, yPos);

                doc.setFontSize(10);
                doc.setTextColor(100, 116, 139);
                doc.text("Generated: <?php echo $generatedAt; ?>", pageWidth - 15, yPos, {
                    align: "right"
                });

                yPos += 12;
                doc.setDrawColor(226, 232, 240);
                doc.line(leftMargin, yPos, pageWidth - 15, yPos);
                yPos += 12;

                // ── I. EXECUTIVE SUMMARY ──
                doc.setFontSize(14);
                doc.setTextColor(30, 41, 59);
                doc.text("I. Executive Summary", leftMargin, yPos);
                yPos += 8;

                doc.setFontSize(10);
                doc.setTextColor(71, 85, 105);
                const summaryText = `Total Users: <?php echo $totalUsers; ?> (Active: <?php echo $activeUsers; ?>)
Total Files: <?php echo $totalFiles; ?> (Storage: <?php echo fmtSize($totalFileSize); ?>)
Watermarked Files: <?php echo $watermarkedFiles; ?>
Active Projects: <?php echo $activeProjects; ?>`;
                doc.text(summaryText, leftMargin, yPos);
                yPos += 25;

                // ── II. CHARTS ──
                doc.setFontSize(14);
                doc.setTextColor(30, 41, 59);
                doc.text("II. Activity Charts", leftMargin, yPos);
                yPos += 10;

                // Capture canvas to images
                const userCanvas = document.getElementById('userStatusChart');
                const roleCanvas = document.getElementById('roleDistributionChart');
                if (userCanvas && roleCanvas) {
                    const userImg = userCanvas.toDataURL('image/png', 1.0);
                    const roleImg = roleCanvas.toDataURL('image/png', 1.0);
                    doc.addImage(userImg, 'PNG', leftMargin, yPos, 80, 50);
                    doc.addImage(roleImg, 'PNG', leftMargin + 90, yPos, 80, 50);
                    yPos += 55;
                }

                const wmCanvas = document.getElementById('watermarkCoverageChart');
                const trendCanvas = document.getElementById('uploadTrendChart');
                if (wmCanvas && trendCanvas) {
                    const wmImg = wmCanvas.toDataURL('image/png', 1.0);
                    const trendImg = trendCanvas.toDataURL('image/png', 1.0);
                    doc.addImage(wmImg, 'PNG', leftMargin, yPos, 80, 50);
                    doc.addImage(trendImg, 'PNG', leftMargin + 90, yPos, 80, 50);
                    yPos += 60;
                }

                // ── III. PROJECT INVENTORY ──
                doc.addPage();
                yPos = 20;
                doc.setFontSize(14);
                doc.setTextColor(30, 41, 59);
                doc.text("III. Project Inventory", leftMargin, yPos);
                yPos += 5;

                doc.autoTable({
                    html: '#sec-projects table',
                    startY: yPos,
                    theme: 'grid',
                    styles: {
                        fontSize: 8
                    },
                    headStyles: {
                        fillColor: [102, 126, 234]
                    }
                });
                yPos = doc.lastAutoTable.finalY + 15;

                if (yPos > 240) {
                    doc.addPage();
                    yPos = 20;
                }

                // ── IV. PERSONNEL REGISTRY ──
                doc.setFontSize(14);
                doc.setTextColor(30, 41, 59);
                doc.text("IV. Personnel Registry", leftMargin, yPos);
                yPos += 8;

                const userTables = document.querySelectorAll('#sec-users table');
                userTables.forEach((table) => {
                    const titleElem = table.closest('.section-tablap').sElementSibling;
                    const roleTitle = titleElem ? titleElem.innerText : '';

                    if (roleTitle) {
                        doc.setFontSize(11);
                        doc.setTextColor(71, 85, 105);
                        doc.text(roleTitle, leftMargin, yPos);
                        yPos += 4;
                    }

                    doc.autoTable({
                        html: table,
                        startY: yPos,
                        theme: 'grid',
                        styles: {
                            fontSize: 8
                        },
                        headStyles: {
                            fillColor: [102, 126, 234]
                        }
                    });

                    yPos = doc.lastAutoTable.finalY + 12;
                    if (yPos > 240) {
                        doc.addPage();
                        yPos = 20;
                    }
                });

                // ── FOOTER PAGE NUMBERS ──
                const pageCount = doc.internal.getNumberOfPages();
                for (let i = 1;= pag i++) {
                    doc.setPage(i);
                    doc.setFontSize(8);
                    doc.setTextColor(148, 163, 184);
                    doc.text("StegaVault Security Suite | Audit Report SV-REP-<?php echo date('Ymd'); ?> - Page " + i + " of " + pageCount, doc.internal.pageSize.getWidth() / 2, doc.internal.pageSize.getHeight() - 10, {
                        align: 'center'
                    });
                }

                doc.save('StegaVault_System_Report.pdf');

            } catch (error) {
                console.error('PDF Error:', error);
                alert('An error occurred while generating the PDF.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }

    </script>

    <?php include '../includes/settings_modal.php'; ?>
</body>

</html>