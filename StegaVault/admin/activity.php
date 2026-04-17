<?php

/**
 * StegaVault - Activity Logs
 * File: admin/activity.php
 */

session_start();
require_once '../includes/db.php';

// Admin-only page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.html');
    exit;
}

$user = [
    'id'    => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name'  => $_SESSION['name'],
    'role'  => $_SESSION['role'],
];

// ── Pagination ─────────────────────────────────────────────────
$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ── Filters ────────────────────────────────────────────────────
$search    = trim($_GET['search']    ?? '');
$actionFilter = trim($_GET['action'] ?? '');
$userFilter   = (int)($_GET['user_id'] ?? 0);
$roleFilter   = trim($_GET['role'] ?? '');
$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to']   ?? '');

// Build WHERE clauses
$where  = [];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(al.action LIKE ? OR al.description LIKE ? OR u.name LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

if ($actionFilter !== '') {
    $where[]  = 'al.action = ?';
    $params[] = $actionFilter;
    $types   .= 's';
}

if (in_array($roleFilter, ['admin', 'employee', 'collaborator'], true)) {
    // role filtering is handled by selecting the corresponding activity table below
}

if ($userFilter > 0) {
    $where[]  = 'al.user_id = ?';
    $params[] = $userFilter;
    $types   .= 'i';
}

if ($dateFrom !== '') {
    $where[]  = 'CAST(al.created_at AS DATE) >= ?';
    $params[] = $dateFrom;
    $types   .= 's';
}

if ($dateTo !== '') {
    $where[]  = 'CAST(al.created_at AS DATE) <= ?';
    $params[] = $dateTo;
    $types   .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$activitySourceRaw = match ($roleFilter) {
    'admin' => 'activity_log_admin',
    'employee' => 'activity_log_employee',
    'collaborator' => 'activity_log_collaborator',
    default => '(SELECT * FROM activity_log_admin UNION ALL SELECT * FROM activity_log_employee UNION ALL SELECT * FROM activity_log_collaborator)'
};

// ── Total count ────────────────────────────────────────────────
$countSQL  = "SELECT COUNT(*) FROM {$activitySourceRaw} al LEFT JOIN users u ON al.user_id = u.id $whereSQL";
$countStmt = $db->prepare($countSQL);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalLogs = $countStmt->get_result()->fetch_row()[0];
$totalPages = max(1, (int)ceil($totalLogs / $perPage));
$page = min($page, $totalPages);

// ── Fetch logs ─────────────────────────────────────────────────
$logsSQL = "
    SELECT al.id, al.action, al.description, al.ip_address, al.created_at,
           u.name AS actor_name, u.role AS actor_role, u.email AS actor_email
    FROM {$activitySourceRaw} al
    LEFT JOIN users u ON al.user_id = u.id
    $whereSQL
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
";
$logsStmt = $db->prepare($logsSQL);
$logsParams = array_merge($params, [$perPage, $offset]);
$logsTypes  = $types . 'ii';
$logsStmt->bind_param($logsTypes, ...$logsParams);
$logsStmt->execute();
$logs = $logsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Distinct actions for filter dropdown ───────────────────────
$actionsResult = $db->query("SELECT DISTINCT al.action FROM {$activitySourceRaw} al ORDER BY al.action ASC");
$allActions = [];
while ($row = $actionsResult->fetch_assoc()) {
    $allActions[] = $row['action'];
}

// ── Users list for filter dropdown ────────────────────────────
$usersResult = $db->query("SELECT id, name FROM users ORDER BY name ASC");
$allUsers = [];
while ($row = $usersResult->fetch_assoc()) {
    $allUsers[] = $row;
}

// ── Summary stats ──────────────────────────────────────────────
$r = $db->query("SELECT COUNT(*) FROM {$activitySourceRaw} al");
$totalAll   = $r ? (int)$r->fetch_row()[0] : 0;
$r = $db->query("SELECT COUNT(*) FROM {$activitySourceRaw} al WHERE CAST(al.created_at AS DATE) = CURDATE()");
$todayCount = $r ? (int)$r->fetch_row()[0] : 0;
$r = $db->query("SELECT COUNT(*) FROM {$activitySourceRaw} al WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$weekCount  = $r ? (int)$r->fetch_row()[0] : 0;

// ── Action color map ───────────────────────────────────────────
function actionBadge(string $action): string
{
    $map = [
        'login'          => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
        'logout'         => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
        'upload'         => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
        'download'       => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
        'view'           => 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
        'delete'         => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
        'rename'         => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
        'user_created'   => 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/20',
        'user_updated'   => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
        'user_deleted'   => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
        'project_created' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
        'project_updated' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
        'project_deleted' => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
        'watermark'      => 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20',
        'analysis'       => 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/20',
    ];
    // Fuzzy match
    foreach ($map as $key => $cls) {
        if (str_contains(strtolower($action), $key)) return $cls;
    }
    return 'bg-primary/10 text-primary border-primary/20';
}

function actionIcon(string $action): string
{
    $map = [
        'login'    => 'login',
        'logout'   => 'logout',
        'upload'   => 'upload_file',
        'download' => 'download',
        'view'     => 'visibility',
        'delete'   => 'delete',
        'rename'   => 'edit',
        'user'     => 'person',
        'project'  => 'folder_managed',
        'watermark' => 'verified',
        'analysis' => 'policy',
    ];
    foreach ($map as $key => $icon) {
        if (str_contains(strtolower($action), $key)) return $icon;
    }
    return 'history';
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Activity Logs - StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
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

        .filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex">

    <!-- ═══ SIDEBAR ═══ -->
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
                <!-- Active: Activity Logs -->
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white" href="activity.php">
                    <span class="material-symbols-outlined text-[22px] filled">history</span>
                    <p class="text-sm font-medium">Activity Logs</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="reports.php">
                    <span class="material-symbols-outlined text-[22px]">summarize</span>
                    <p class="text-sm font-medium">Reports</p>
                </a>
            </nav>

            <!-- User Profile -->
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

    <!-- ═══ MAIN CONTENT ═══ -->
    <div class="flex-1 ml-64 flex flex-col min-h-screen">

        <!-- Top Header -->
        <header
            class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center gap-6">
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="material-symbols-outlined text-primary text-[20px]">history</span>
                <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight">Activity Logs</h2>
            </div>
            <?php include '../includes/search_bar.php'; ?>
            <div class="flex items-center gap-3 flex-shrink-0">
                <div
                    class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-500 text-xs font-semibold">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    System: Operational
                </div>
                <!-- Clear filters button (shown only when filters active) -->
                <?php if ($search || $actionFilter || $roleFilter || $userFilter || $dateFrom || $dateTo): ?>
                <a href="activity.php"
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-[14px]">filter_list_off</span>
                    Clear Filters
                </a>
                <?php endif; ?>
            </div>
        </header>

        <div class="p-8 space-y-6">

            <!-- ── Stats Row ── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <?php
                $statCards = [
                    ['label' => 'Total Events',    'value' => number_format($totalAll),   'icon' => 'history',         'color' => 'text-primary bg-primary/10'],
                    ['label' => 'Today',            'value' => number_format($todayCount), 'icon' => 'today',           'color' => 'text-emerald-500 bg-emerald-500/10'],
                    ['label' => 'Last 7 Days',      'value' => number_format($weekCount),  'icon' => 'date_range',      'color' => 'text-blue-500 bg-blue-500/10'],
                ];
                foreach ($statCards as $card): ?>
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm flex items-center gap-4">
                    <div
                        class="size-10 rounded-lg <?php echo $card['color']; ?> flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[20px]">
                            <?php echo $card['icon']; ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                            <?php echo $card['label']; ?>
                        </p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                            <?php echo $card['value']; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Filter Bar ── -->
            <form method="get"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <!-- Search -->
                    <div class="lg:col-span-2 relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                        <input type="text" name="search" placeholder="Search action, description, user…"
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>
                    <!-- Action filter -->
                    <select name="action"
                        class="px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">All Actions</option>
                        <?php foreach ($allActions as $a): ?>
                        <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $actionFilter===$a ? 'selected'
                            : '' ; ?>>
                            <?php echo htmlspecialchars($a); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Role filter -->
                    <select name="role"
                        class="px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $roleFilter==='admin' ? 'selected' : '' ; ?>>Admin</option>
                        <option value="employee" <?php echo $roleFilter==='employee' ? 'selected' : '' ; ?>>Employee
                        </option>
                        <option value="collaborator" <?php echo $roleFilter==='collaborator' ? 'selected' : '' ; ?>
                            >Collaborator</option>
                    </select>
                    <!-- User filter -->
                    <select name="user_id"
                        class="px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="">All Users</option>
                        <?php foreach ($allUsers as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $userFilter===(int)$u['id'] ? 'selected' : ''
                            ; ?>>
                            <?php echo htmlspecialchars($u['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Apply button -->
                    <button type="submit"
                        class="flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-white text-sm font-semibold rounded-lg transition-colors">
                        <span class="material-symbols-outlined text-[16px]">filter_list</span>
                        Apply
                    </button>
                </div>
                <!-- Date range row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <div class="relative">
                        <label class="text-xs text-slate-500 dark:text-slate-400 font-medium block mb-1.5">From
                            Date</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 dark:text-slate-400 font-medium block mb-1.5">To
                            Date</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>
                </div>
            </form>

            <!-- ── Logs Table ── -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden">

                <!-- Table header row -->
                <div
                    class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base">Event Log</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">
                            Showing
                            <?php echo number_format(count($logs)); ?> of
                            <?php echo number_format($totalLogs); ?> entries
                            <?php if ($search || $actionFilter || $roleFilter || $userFilter || $dateFrom || $dateTo): ?>
                            <span class="text-primary font-semibold">(filtered)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-44">
                                Timestamp</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-40">
                                Action</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Description</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-40">
                                User</th>
                            <th class="px-6 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-36">
                                IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if (count($logs) === 0): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <span
                                    class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-700 block mb-3">manage_search</span>
                                <p class="text-slate-500 dark:text-slate-400 font-medium">No activity logs found</p>
                                <?php if ($search || $actionFilter || $roleFilter || $userFilter || $dateFrom || $dateTo): ?>
                                <p class="text-slate-400 dark:text-slate-500 text-xs mt-1">Try adjusting your filters
                                </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <?php
                                $badgeClass = actionBadge($log['action']);
                                $iconName   = actionIcon($log['action']);
                                $actorInitials = $log['actor_name'] ? strtoupper(substr($log['actor_name'], 0, 2)) : '?';
                                $actorColorClass = match ($log['actor_role'] ?? '') {
                                    'admin'    => 'bg-purple-500/10 text-purple-500',
                                    'employee' => 'bg-blue-500/10 text-blue-500',
                                    'collaborator' => 'bg-amber-500/10 text-amber-500',
                                    default    => 'bg-slate-200 dark:bg-slate-700 text-slate-500',
                                };
                                ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                            <!-- Timestamp -->
                            <td class="px-6 py-4">
                                <div class="text-slate-500 dark:text-slate-400 text-xs font-mono">
                                    <p class="text-slate-700 dark:text-slate-300 font-semibold text-[13px]">
                                        <?php echo date('M d, Y', strtotime($log['created_at'])); ?>
                                    </p>
                                    <p class="mt-0.5">
                                        <?php echo date('g:i:s A', strtotime($log['created_at'])); ?>
                                    </p>
                                </div>
                            </td>
                            <!-- Action badge -->
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border <?php echo $badgeClass; ?>">
                                    <span class="material-symbols-outlined text-[13px]">
                                        <?php echo $iconName; ?>
                                    </span>
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <!-- Description -->
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-300 max-w-xs">
                                <p class="truncate" title="<?php echo htmlspecialchars($log['description'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($log['description'] ?? '—'); ?>
                                </p>
                            </td>
                            <!-- User -->
                            <td class="px-6 py-4">
                                <?php if ($log['actor_name']): ?>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="size-7 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0 <?php echo $actorColorClass; ?>">
                                        <?php echo $actorInitials; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-slate-900 dark:text-white text-xs font-semibold truncate">
                                            <?php echo htmlspecialchars($log['actor_name']); ?>
                                        </p>
                                        <p class="text-slate-400 dark:text-slate-500 text-[11px] capitalize">
                                            <?php echo htmlspecialchars($log['actor_role'] ?? ''); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="text-slate-400 dark:text-slate-500 text-xs italic">System</span>
                                <?php endif; ?>
                            </td>
                            <!-- IP -->
                            <td class="px-6 py-4">
                                <?php if ($log['ip_address']): ?>
                                <span
                                    class="font-mono text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">
                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-300 dark:text-slate-600 text-xs">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div
                    class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Page <span class="font-semibold text-slate-700 dark:text-slate-300">
                            <?php echo $page; ?>
                        </span>
                        of <span class="font-semibold text-slate-700 dark:text-slate-300">
                            <?php echo $totalPages; ?>
                        </span>
                    </p>
                    <div class="flex items-center gap-1">
                        <?php
                            // Build query string preserving filters
                            $qs = http_build_query(array_filter([
                                'search'    => $search,
                                'action'    => $actionFilter,
                                'role'      => $roleFilter ?: null,
                                'user_id'   => $userFilter ?: null,
                                'date_from' => $dateFrom,
                                'date_to'   => $dateTo,
                            ]));
                            $qs = $qs ? '&' . $qs : '';

                            // Prev
                            if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $qs; ?>"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            <span class="material-symbols-outlined text-[14px]">chevron_left</span> Prev
                        </a>
                        <?php endif;

                            // Page numbers
                            $start = max(1, $page - 2);
                            $end   = min($totalPages, $page + 2);
                            for ($p = $start; $p <= $end; $p++): ?>
                        <a href="?page=<?php echo $p; ?><?php echo $qs; ?>"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors <?php echo $p === $page ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700'; ?>">
                            <?php echo $p; ?>
                        </a>
                        <?php endfor;

                            // Next
                            if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $qs; ?>"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                            Next <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /p-8 -->
    </div><!-- /main -->

    <script>
        window.currentUser = {
            name: "<?php echo htmlspecialchars($user['name']); ?>",
            email: "<?php echo htmlspecialchars($user['email']); ?>"
        };
    </script>
    <script src="../js/security-shield.js"></script>
    <?php include '../includes/settings_modal.php'; ?>
</body>

</html>