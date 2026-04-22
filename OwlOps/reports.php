<?php
/**
 * StegaVault - Super Admin System Report
 * File: OwlOps/reports.php
 */

session_start();
require_once '../StegaVault/includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/auth_guard.php';

$user = [
    'id'   => $_SESSION['user_id'],
    'name' => $_SESSION['name'],
    'email'=> $_SESSION['email'],
];

$period      = in_array($_GET['period'] ?? '', ['daily', 'weekly', 'monthly']) ? $_GET['period'] : 'all';
$periodLabel = match($period) {
    'daily'   => 'Daily Report (Last 24 Hours)',
    'weekly'  => 'Weekly Report (Last 7 Days)',
    'monthly' => 'Monthly Report (Last 30 Days)',
    default   => 'All-Time Report'
};
$filterDays  = match($period) { 'daily' => 1, 'weekly' => 7, 'monthly' => 30, default => null };
$auditDateSQL = $filterDays !== null ? "AND sal.created_at >= DATE_SUB(NOW(), INTERVAL {$filterDays} DAY)" : '';

$generatedAt = date('F j, Y \a\t g:i A');

// ── User Stats ────────────────────────────────────────────────────
$totalUsers        = (int) $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$activeUsers       = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'active' OR status IS NULL")->fetch_row()[0];
$pendingUsers      = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending_activation'")->fetch_row()[0];
$disabledUsers     = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'disabled'")->fetch_row()[0];
$expiredUsers      = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'expired'")->fetch_row()[0];
$adminCount        = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetch_row()[0];
$employeeCount     = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetch_row()[0];
$collaboratorCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'collaborator'")->fetch_row()[0];
$mfaEnabledCount   = (int) $db->query("SELECT COUNT(*) FROM users WHERE is_mfa_enabled = TRUE")->fetch_row()[0];

// ── Super Admins ──────────────────────────────────────────────────
$superAdminCount = 0;
$superAdmins = [];
try {
    $superAdminCount = (int) $db->query("SELECT COUNT(*) FROM super_admins")->fetch_row()[0];
    $saResult = $db->query("SELECT name, email, created_at FROM super_admins ORDER BY created_at ASC");
    if ($saResult) {
        while ($row = $saResult->fetch_assoc()) $superAdmins[] = $row;
    }
} catch (Exception $e) {}

// ── File Stats ────────────────────────────────────────────────────
$totalFiles        = (int) $db->query("SELECT COUNT(*) FROM files")->fetch_row()[0];
$totalFileSize     = (int) $db->query("SELECT COALESCE(SUM(file_size), 0) FROM files")->fetch_row()[0];
$watermarkedFiles  = (int) $db->query("SELECT COUNT(*) FROM files WHERE watermarked IS TRUE")->fetch_row()[0];
$totalDownloads    = (int) $db->query("SELECT COALESCE(SUM(download_count), 0) FROM files")->fetch_row()[0];

function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return number_format($bytes / 1048576,    2) . ' MB';
    if ($bytes >= 1024)       return number_format($bytes / 1024,       2) . ' KB';
    return $bytes . ' B';
}

// ── Project Stats ─────────────────────────────────────────────────
$totalProjects     = (int) $db->query("SELECT COUNT(*) FROM projects")->fetch_row()[0];
$activeProjects    = (int) $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'")->fetch_row()[0];
$archivedProjects  = (int) $db->query("SELECT COUNT(*) FROM projects WHERE status = 'archived'")->fetch_row()[0];
$completedProjects = (int) $db->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetch_row()[0];

// ── Audit Log Stats ───────────────────────────────────────────────
$auditStats = [];
try {
    $auditResult = $db->query(
        "SELECT category, COUNT(*) as total FROM super_admin_audit_log WHERE 1=1 {$auditDateSQL} GROUP BY category ORDER BY total DESC"
    );
    if ($auditResult) {
        while ($row = $auditResult->fetch_assoc()) $auditStats[] = $row;
    }
} catch (Exception $e) {}

$totalAuditEvents = array_sum(array_column($auditStats, 'total'));

// ── Recent Audit Events (last 10) ─────────────────────────────────
$recentAudit = [];
try {
    $r = $db->query(
        "SELECT sal.action, sal.category, sal.created_at, sal.ip_address,
                sa.name AS actor_name, sa.email AS actor_email
         FROM super_admin_audit_log sal
         LEFT JOIN super_admins sa ON sal.super_admin_id = sa.id
         WHERE 1=1 {$auditDateSQL}
         ORDER BY sal.created_at DESC LIMIT 10"
    );
    if ($r) while ($row = $r->fetch_assoc()) $recentAudit[] = $row;
} catch (Exception $e) {}

// ── Backup Stats ──────────────────────────────────────────────────
$backups = [];
$totalBackups = 0;
$lastBackup = null;
$backupMetaPath = '/opt/backups/backups_meta.json';
if (file_exists($backupMetaPath)) {
    $backups = json_decode(file_get_contents($backupMetaPath), true) ?? [];
    $totalBackups = count($backups);
    $lastBackup = $backups[0] ?? null;
}

// ── Web Apps ──────────────────────────────────────────────────────
$totalApps = (int) $db->query("SELECT COUNT(*) FROM web_apps")->fetch_row()[0];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>System Report - OwlOps</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#2563eb",
                        "background-light": "#ffffff",
                        "card-light": "#f8fafc",
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"],
                        "body": ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; }
        h1,h2,h3,h4,h5,h6,.font-display { font-family: 'Space Grotesk', sans-serif; }
        .bg-grid-pattern { background-image: radial-gradient(#cbd5e1 0.1px, transparent 0.1px); background-size: 30px 30px; }

        @media print {
            aside, .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; }
            main { margin-left: 0 !important; padding: 2rem !important; }
            .print-card { border: 1px solid #e5e7eb !important; background: #f9fafb !important; }
            .text-white, .text-slate-200 { color: #111827 !important; }
            .text-slate-400, .text-slate-500 { color: #6b7280 !important; }
            .bg-slate-card { background: #f9fafb !important; }
            canvas { max-height: 200px; }
        }
    </style>
</head>

<body class="text-slate-900 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 border-r border-slate-200 bg-background-light flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full gap-8">
            <div class="flex items-center gap-2">
                <img src="OwlOps.png" alt="OwlOps Logo" class="h-8 w-auto">
                <div>
                    <h1 class="text-slate-900 text-base font-bold leading-tight font-display">OwlOps</h1>
                    <p class="text-primary text-[10px] font-bold uppercase tracking-widest mt-1">Super Admin Mode</p>
                </div>
            </div>
            <nav class="flex flex-col gap-2 flex-1">
                <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-600 mb-2">Systems</p>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:text-primary hover:bg-primary/5 transition-colors" href="dashboard.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <p class="text-sm font-medium">Control Center</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:text-primary hover:bg-primary/5 transition-colors" href="manage_admins.php">
                    <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                    <p class="text-sm font-medium">Manage Admins</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:text-primary hover:bg-primary/5 transition-colors" href="backup.php">
                    <span class="material-symbols-outlined text-[20px]">backup</span>
                    <p class="text-sm font-medium">Backup &amp; Restore</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:text-primary hover:bg-primary/5 transition-colors" href="audit-log.php">
                    <span class="material-symbols-outlined text-[20px]">manage_search</span>
                    <p class="text-sm font-medium">Audit Log</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary border border-primary/20" href="reports.php">
                    <span class="material-symbols-outlined text-[20px] text-primary">assessment</span>
                    <p class="text-sm font-medium">System Report</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:text-primary hover:bg-primary/5 transition-colors" href="mfa-settings.php">
                    <span class="material-symbols-outlined text-[20px]">phonelink_lock</span>
                    <p class="text-sm font-medium">MFA Settings</p>
                </a>
            </nav>
            <div class="pt-6 border-t border-white/5">
                <div class="flex items-center gap-3 px-3 py-2">
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center text-black font-bold text-xs">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-xs font-bold truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-slate-500 text-[10px] truncate">Super Admin</p>
                    </div>
                </div>
                <button onclick="logout()" class="w-full mt-4 flex items-center gap-3 px-3 py-2 rounded-lg text-red-400 hover:bg-red-400/10 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <p class="text-sm font-medium">Sign Out</p>
                </button>
            </div>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-12 relative overflow-x-hidden">
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
            <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto space-y-10">

            <!-- Header -->
            <header class="flex items-end justify-between">
                <div>
                    <h2 class="text-4xl font-bold text-white font-display">System Report</h2>
                    <p class="text-slate-400 mt-2">
                        Full platform snapshot across users, files, projects, backups, and audit activity.
                    </p>
                    <p class="text-[10px] text-slate-600 font-mono mt-1 uppercase tracking-widest">
                        <?php echo $periodLabel; ?> &mdash; Generated <?php echo $generatedAt; ?>
                    </p>
                </div>
                <div class="flex items-center gap-3 no-print">
                    <button onclick="window.location.reload()" class="flex items-center gap-2 px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-slate-400 hover:text-white text-sm font-medium transition-colors">
                        <span class="material-symbols-outlined text-base">refresh</span> Refresh
                    </button>
                    <button id="downloadPdfBtn" onclick="generatePDF()" class="flex items-center gap-2 px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-slate-400 hover:text-white text-sm font-medium transition-colors">
                        <span class="material-symbols-outlined text-base">download</span> Download PDF
                    </button>
                    <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-primary text-black rounded-xl text-sm font-bold transition-colors hover:bg-primary/80">
                        <span class="material-symbols-outlined text-base">print</span> Print Report
                    </button>
                </div>
            </header>

            <!-- Period Filter Tabs -->
            <div class="flex items-center gap-2 no-print">
                <?php
                $tabs = ['all' => 'All Time', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
                foreach ($tabs as $key => $label):
                    $isActive = $period === $key;
                ?>
                <a href="?period=<?php echo $key; ?>"
                   class="px-4 py-2 rounded-xl text-sm font-bold transition-colors <?php echo $isActive ? 'bg-primary/10 text-white border border-white/10' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?>">
                    <?php echo $label; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- ── KPI Cards ───────────────────────────────────────── -->
            <section>
                <p class="px-1 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 mb-4">Platform Overview</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php
                    $kpis = [
                        ['label'=>'Total Users',    'value'=>number_format($totalUsers),      'icon'=>'group',           'color'=>'text-white',         'bg'=>'bg-white/5'],
                        ['label'=>'Active Users',   'value'=>number_format($activeUsers),     'icon'=>'check_circle',    'color'=>'text-emerald-400',   'bg'=>'bg-emerald-500/10'],
                        ['label'=>'App Admins',     'value'=>number_format($adminCount),      'icon'=>'manage_accounts', 'color'=>'text-blue-400',      'bg'=>'bg-blue-500/10'],
                        ['label'=>'Super Admins',   'value'=>number_format($superAdminCount), 'icon'=>'shield_person',   'color'=>'text-purple-400',    'bg'=>'bg-purple-500/10'],
                        ['label'=>'Total Files',    'value'=>number_format($totalFiles),      'icon'=>'description',     'color'=>'text-yellow-400',    'bg'=>'bg-yellow-500/10'],
                        ['label'=>'Storage Used',   'value'=>formatBytes($totalFileSize),     'icon'=>'storage',         'color'=>'text-orange-400',    'bg'=>'bg-orange-500/10'],
                        ['label'=>'Total Projects', 'value'=>number_format($totalProjects),   'icon'=>'folder_open',     'color'=>'text-cyan-400',      'bg'=>'bg-cyan-500/10'],
                        ['label'=>'Audit Events',   'value'=>number_format($totalAuditEvents),'icon'=>'policy',          'color'=>'text-rose-400',      'bg'=>'bg-rose-500/10'],
                    ];
                    foreach ($kpis as $kpi): ?>
                    <div class="print-card bg-slate-card border border-white/5 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="p-2 rounded-xl <?php echo $kpi['bg']; ?>">
                                <span class="material-symbols-outlined text-xl <?php echo $kpi['color']; ?>"><?php echo $kpi['icon']; ?></span>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-white"><?php echo $kpi['value']; ?></p>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1"><?php echo $kpi['label']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- ── Charts Row ─────────────────────────────────────── -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- User Roles Donut -->
                <div class="print-card bg-slate-card border border-white/5 rounded-2xl p-6 space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-white font-display">Users by Role</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Distribution across account types</p>
                    </div>
                    <div class="flex justify-center">
                        <canvas id="roleChart" width="180" height="180"></canvas>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-blue-500 inline-block"></span> Admins</span>
                            <span class="font-bold text-white"><?php echo $adminCount; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-emerald-500 inline-block"></span> Employees</span>
                            <span class="font-bold text-white"><?php echo $employeeCount; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-purple-500 inline-block"></span> Collaborators</span>
                            <span class="font-bold text-white"><?php echo $collaboratorCount; ?></span>
                        </div>
                    </div>
                </div>

                <!-- User Status Donut -->
                <div class="print-card bg-slate-card border border-white/5 rounded-2xl p-6 space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-white font-display">Users by Status</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Account activation and access state</p>
                    </div>
                    <div class="flex justify-center">
                        <canvas id="statusChart" width="180" height="180"></canvas>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-emerald-500 inline-block"></span> Active</span>
                            <span class="font-bold text-white"><?php echo $activeUsers; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-yellow-500 inline-block"></span> Pending</span>
                            <span class="font-bold text-white"><?php echo $pendingUsers; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-slate-500 inline-block"></span> Disabled</span>
                            <span class="font-bold text-white"><?php echo $disabledUsers; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2"><span class="size-2 rounded-full bg-red-500 inline-block"></span> Expired</span>
                            <span class="font-bold text-white"><?php echo $expiredUsers; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Audit Events Bar -->
                <div class="print-card bg-slate-card border border-white/5 rounded-2xl p-6 space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-white font-display">Audit Events by Category</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Breakdown of system audit actions</p>
                    </div>
                    <div class="flex justify-center items-center h-[180px]">
                        <?php if (empty($auditStats)): ?>
                            <p class="text-xs text-slate-600">No audit data yet</p>
                        <?php else: ?>
                            <canvas id="auditChart" width="240" height="180"></canvas>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-2">
                        <?php
                        $catColors = ['auth'=>'bg-blue-500','backup'=>'bg-purple-500','admin'=>'bg-yellow-500','mfa'=>'bg-emerald-500'];
                        foreach ($auditStats as $as):
                            $dot = $catColors[$as['category']] ?? 'bg-slate-500';
                        ?>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2">
                                <span class="size-2 rounded-full <?php echo $dot; ?> inline-block"></span>
                                <?php echo ucfirst(htmlspecialchars($as['category'])); ?>
                            </span>
                            <span class="font-bold text-white"><?php echo number_format($as['total']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- ── Security & Files Row ───────────────────────────── -->
            <section class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Security Snapshot -->
                <div class="print-card bg-slate-card border border-white/5 rounded-2xl p-6 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-500/10 rounded-xl">
                            <span class="material-symbols-outlined text-emerald-400 text-xl">verified_user</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white font-display">Security Snapshot</h3>
                            <p class="text-xs text-slate-500">MFA adoption and access controls</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/5 rounded-xl p-4">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">MFA Enabled</p>
                            <p class="text-2xl font-bold text-white mt-1"><?php echo $mfaEnabledCount; ?></p>
                            <p class="text-xs text-slate-500 mt-0.5"><?php echo $totalUsers > 0 ? round($mfaEnabledCount / $totalUsers * 100) : 0; ?>% of users</p>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">MFA Not Set</p>
                            <p class="text-2xl font-bold text-white mt-1"><?php echo $totalUsers - $mfaEnabledCount; ?></p>
                            <p class="text-xs text-slate-500 mt-0.5"><?php echo $totalUsers > 0 ? round(($totalUsers - $mfaEnabledCount) / $totalUsers * 100) : 0; ?>% of users</p>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Pending Acct</p>
                            <p class="text-2xl font-bold text-white mt-1"><?php echo $pendingUsers; ?></p>
                            <p class="text-xs text-slate-500 mt-0.5">Awaiting activation</p>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Disabled</p>
                            <p class="text-2xl font-bold text-white mt-1"><?php echo $disabledUsers + $expiredUsers; ?></p>
                            <p class="text-xs text-slate-500 mt-0.5">Disabled + expired</p>
                        </div>
                    </div>
                </div>

                <!-- File & Project Stats -->
                <div class="print-card bg-slate-card border border-white/5 rounded-2xl p-6 space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-yellow-500/10 rounded-xl">
                            <span class="material-symbols-outlined text-yellow-400 text-xl">folder_open</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white font-display">Files &amp; Projects</h3>
                            <p class="text-xs text-slate-500">Storage and project health overview</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/5 rounded-xl p-4">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Total Files</p>
                            <p class="text-2xl font-bold text-white mt-1"><?php echo number_format($totalFiles); ?></p>
                            <p class="text-xs text-slate-500 mt-0.5"><?php echo formatBytes($totalFileSize); ?> stored</p>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Watermarked</p>
                            <p class="text-2xl font-bold text-white mt-1"><?php echo number_format($watermarkedFiles); ?></p>
                            <p class="text-xs text-slate-500 mt-0.5"><?php echo $totalFiles > 0 ? round($watermarkedFiles / $totalFiles * 100) : 0; ?>% of files</p>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Total Downloads</p>
                            <p class="text-2xl font-bold text-white mt-1"><?php echo number_format($totalDownloads); ?></p>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4">
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Projects</p>
                            <p class="text-2xl font-bold text-white mt-1"><?php echo number_format($totalProjects); ?></p>
                            <p class="text-xs text-slate-500 mt-0.5"><?php echo $activeProjects; ?> active</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Project Status Breakdown ───────────────────────── -->
            <section class="print-card bg-slate-card border border-white/5 rounded-2xl p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white font-display">Project Status</h3>
                        <p class="text-xs text-slate-500 mt-0.5">All projects across the platform by lifecycle stage</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <?php
                    $pStatuses = [
                        ['label'=>'Active',    'value'=>$activeProjects,    'icon'=>'play_circle',   'color'=>'text-emerald-400 bg-emerald-500/10'],
                        ['label'=>'Archived',  'value'=>$archivedProjects,  'icon'=>'inventory_2',   'color'=>'text-slate-400 bg-white/5'],
                        ['label'=>'Completed', 'value'=>$completedProjects, 'icon'=>'task_alt',      'color'=>'text-blue-400 bg-blue-500/10'],
                    ];
                    foreach ($pStatuses as $ps): ?>
                    <div class="bg-white/5 rounded-2xl p-5 flex items-center gap-4">
                        <div class="p-3 rounded-xl <?php echo explode(' ', $ps['color'])[1]; ?>">
                            <span class="material-symbols-outlined <?php echo explode(' ', $ps['color'])[0]; ?> text-2xl"><?php echo $ps['icon']; ?></span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-white"><?php echo $ps['value']; ?></p>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5"><?php echo $ps['label']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- ── Backup Status ──────────────────────────────────── -->
            <section class="print-card bg-slate-card border border-white/5 rounded-2xl p-6 space-y-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-500/10 rounded-xl">
                        <span class="material-symbols-outlined text-blue-400 text-xl">backup</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white font-display">Backup Status</h3>
                        <p class="text-xs text-slate-500"><?php echo $totalBackups; ?> backup<?php echo $totalBackups !== 1 ? 's' : ''; ?> stored</p>
                    </div>
                </div>

                <?php if (empty($backups)): ?>
                <p class="text-sm text-slate-600 px-1">No backups have been created yet.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Backup ID</th>
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Type</th>
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Created By</th>
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Created At</th>
                                <th class="pb-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Size</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach (array_slice($backups, 0, 8) as $bk): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="py-3 pr-6 font-mono text-slate-400 truncate max-w-[140px]"><?php echo htmlspecialchars($bk['id'] ?? '—'); ?></td>
                                <td class="py-3 pr-6">
                                    <?php
                                    $btype = $bk['type'] ?? 'unknown';
                                    $btypeClass = match($btype) {
                                        'database' => 'bg-blue-500/10 text-blue-400',
                                        'files'    => 'bg-purple-500/10 text-purple-400',
                                        'full'     => 'bg-emerald-500/10 text-emerald-400',
                                        default    => 'bg-white/5 text-slate-400'
                                    };
                                    ?>
                                    <span class="inline-flex px-2 py-0.5 rounded-full border border-white/10 <?php echo $btypeClass; ?> text-[10px] font-bold uppercase tracking-wider">
                                        <?php echo htmlspecialchars(ucfirst($btype)); ?>
                                    </span>
                                </td>
                                <td class="py-3 pr-6 text-white"><?php echo htmlspecialchars($bk['created_by'] ?? '—'); ?></td>
                                <td class="py-3 pr-6 text-slate-400 font-mono"><?php echo htmlspecialchars($bk['created_at'] ?? '—'); ?></td>
                                <td class="py-3 text-slate-400"><?php echo isset($bk['size']) ? formatBytes((int)$bk['size']) : '—'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalBackups > 8): ?>
                <p class="text-[10px] text-slate-600 text-right">Showing 8 of <?php echo $totalBackups; ?> backups. <a href="backup.php" class="text-slate-400 hover:text-white underline transition-colors">View all</a></p>
                <?php endif; ?>
                <?php endif; ?>
            </section>

            <!-- ── Super Admin Accounts ───────────────────────────── -->
            <section class="print-card bg-slate-card border border-white/5 rounded-2xl p-6 space-y-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-500/10 rounded-xl">
                        <span class="material-symbols-outlined text-purple-400 text-xl">shield_person</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white font-display">Super Admin Accounts</h3>
                        <p class="text-xs text-slate-500"><?php echo $superAdminCount; ?> account<?php echo $superAdminCount !== 1 ? 's' : ''; ?> with root authority</p>
                    </div>
                </div>
                <?php if (empty($superAdmins)): ?>
                <p class="text-sm text-slate-600 px-1">No super admins found.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Name</th>
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Email</th>
                                <th class="pb-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Added</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($superAdmins as $sa): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="py-3 pr-6">
                                    <div class="flex items-center gap-3">
                                        <div class="size-7 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-400 font-bold text-[10px] flex-shrink-0">
                                            <?php echo strtoupper(substr($sa['name'], 0, 1)); ?>
                                        </div>
                                        <span class="text-white font-semibold"><?php echo htmlspecialchars($sa['name']); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 pr-6 text-slate-400 font-mono"><?php echo htmlspecialchars($sa['email']); ?></td>
                                <td class="py-3 text-slate-500"><?php echo date('M j, Y', strtotime($sa['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </section>

            <!-- ── Recent Audit Activity ──────────────────────────── -->
            <section class="print-card bg-slate-card border border-white/5 rounded-2xl p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-rose-500/10 rounded-xl">
                            <span class="material-symbols-outlined text-rose-400 text-xl">policy</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white font-display">Recent Audit Activity</h3>
                            <p class="text-xs text-slate-500">Last 10 super admin actions</p>
                        </div>
                    </div>
                    <a href="audit-log.php" class="no-print text-[10px] text-slate-500 hover:text-white font-bold uppercase tracking-widest flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-sm">open_in_new</span> Full Log
                    </a>
                </div>

                <?php if (empty($recentAudit)): ?>
                <p class="text-sm text-slate-600 px-1">No audit events recorded yet.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Timestamp</th>
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Actor</th>
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Action</th>
                                <th class="pb-3 pr-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Category</th>
                                <th class="pb-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php
                            $actionLabels = [
                                'login_success'         => ['Login',              'login',           'text-emerald-400'],
                                'login_failed'          => ['Login Failed',        'gpp_bad',         'text-red-400'],
                                'login_mfa_challenged'  => ['MFA Challenge',       'phonelink_lock',  'text-yellow-400'],
                                'login_mfa_success'     => ['MFA Verified',        'verified_user',   'text-emerald-400'],
                                'logout'                => ['Logout',              'logout',          'text-slate-400'],
                                'backup_db_created'     => ['DB Backup Created',   'database',        'text-blue-400'],
                                'backup_files_created'  => ['Files Backup Created','folder_zip',      'text-purple-400'],
                                'backup_db_restored'    => ['DB Restored',         'restart_alt',     'text-orange-400'],
                                'backup_files_restored' => ['Files Restored',      'unarchive',       'text-orange-400'],
                                'backup_deleted'        => ['Backup Deleted',      'delete',          'text-red-400'],
                                'super_admin_created'   => ['Super Admin Created', 'person_add',      'text-emerald-400'],
                                'super_admin_deleted'   => ['Super Admin Deleted', 'person_remove',   'text-red-400'],
                                'app_admin_created'     => ['App Admin Created',   'person_add',      'text-emerald-400'],
                                'app_admin_deleted'     => ['App Admin Deleted',   'person_remove',   'text-red-400'],
                                'mfa_enabled'           => ['MFA Enabled',         'shield_lock',     'text-emerald-400'],
                                'mfa_disabled'          => ['MFA Disabled',        'no_encryption',   'text-red-400'],
                            ];
                            $catBadge = [
                                'auth'   => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                'backup' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                                'admin'  => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
                                'mfa'    => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                            ];
                            foreach ($recentAudit as $ev):
                                $meta  = $actionLabels[$ev['action']] ?? [ucwords(str_replace('_',' ',$ev['action'])), 'info', 'text-slate-400'];
                                $badge = $catBadge[$ev['category']] ?? 'bg-white/5 text-slate-400 border-white/10';
                            ?>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="py-3 pr-6 text-slate-500 font-mono whitespace-nowrap">
                                    <?php echo date('M j, Y H:i', strtotime($ev['created_at'])); ?>
                                </td>
                                <td class="py-3 pr-6">
                                    <p class="text-white font-semibold"><?php echo htmlspecialchars($ev['actor_name'] ?? '—'); ?></p>
                                    <p class="text-slate-500 text-[10px]"><?php echo htmlspecialchars($ev['actor_email'] ?? ''); ?></p>
                                </td>
                                <td class="py-3 pr-6">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined <?php echo $meta[2]; ?> text-base"><?php echo $meta[1]; ?></span>
                                        <span class="text-white"><?php echo htmlspecialchars($meta[0]); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 pr-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full border <?php echo $badge; ?> text-[10px] font-bold uppercase tracking-wider">
                                        <?php echo htmlspecialchars($ev['category']); ?>
                                    </span>
                                </td>
                                <td class="py-3 text-slate-500 font-mono"><?php echo htmlspecialchars($ev['ip_address'] ?? '—'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </section>

            <!-- Footer -->
            <footer class="border-t border-white/5 pt-6 pb-4 flex items-center justify-between">
                <p class="text-[10px] text-slate-600 font-mono uppercase tracking-widest">
                    OwlOps &mdash; StegaVault Super Admin Report
                </p>
                <p class="text-[10px] text-slate-600 font-mono uppercase tracking-widest">
                    <?php echo $generatedAt; ?>
                </p>
            </footer>

        </div>
    </main>

    <script>
        // Chart defaults for dark theme
        Chart.defaults.color = '#64748b';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.05)';

        // User Roles Donut
        new Chart(document.getElementById('roleChart'), {
            type: 'doughnut',
            data: {
                labels: ['Admins', 'Employees', 'Collaborators'],
                datasets: [{
                    data: [<?php echo $adminCount; ?>, <?php echo $employeeCount; ?>, <?php echo $collaboratorCount; ?>],
                    backgroundColor: ['#3b82f6', '#10b981', '#a855f7'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }]
            },
            options: {
                cutout: '70%',
                plugins: { legend: { display: false }, tooltip: { callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed}`
                }}},
                animation: { animateScale: true }
            }
        });

        // User Status Donut
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Pending', 'Disabled', 'Expired'],
                datasets: [{
                    data: [<?php echo $activeUsers; ?>, <?php echo $pendingUsers; ?>, <?php echo $disabledUsers; ?>, <?php echo $expiredUsers; ?>],
                    backgroundColor: ['#10b981', '#eab308', '#64748b', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }]
            },
            options: {
                cutout: '70%',
                plugins: { legend: { display: false }, tooltip: { callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed}`
                }}},
                animation: { animateScale: true }
            }
        });

        <?php if (!empty($auditStats)): ?>
        // Audit Events Bar
        new Chart(document.getElementById('auditChart'), {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(fn($a) => '"' . ucfirst($a['category']) . '"', $auditStats)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($auditStats, 'total')); ?>],
                    backgroundColor: <?php
                        $barColors = ['auth'=>'"#3b82f6"','backup'=>'"#a855f7"','admin'=>'"#eab308"','mfa'=>'"#10b981"'];
                        $colors = array_map(fn($a) => $barColors[$a['category']] ?? '"#64748b"', $auditStats);
                        echo '[' . implode(',', $colors) . ']';
                    ?>,
                    borderRadius: 6,
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { font: { size: 10 }, precision: 0 } }
                }
            }
        });
        <?php endif; ?>

        async function logout() {
            await fetch('../StegaVault/api/super_admin_auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.php';
        }

        async function generatePDF() {
            const btn = document.getElementById('downloadPdfBtn');
            btn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">progress_activity</span> Generating...';
            btn.disabled = true;

            await new Promise(r => setTimeout(r, 300));

            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                const pW  = doc.internal.pageSize.getWidth();
                const pH  = doc.internal.pageSize.getHeight();
                const lm  = 14, rm = 14;
                const cW  = pW - lm - rm;

                const PERIOD_LABEL = '<?php echo addslashes($periodLabel); ?>';
                const GEN_AT       = '<?php echo $generatedAt; ?>';
                const DOC_ID       = 'OWL-REP-<?php echo date('Ymd-Hi'); ?>';

                const C = {
                    primary: [255, 255, 255], dark: [10, 10, 10], navy: [20, 20, 30],
                    slate: [71, 85, 105], muted: [148, 163, 184], border: [50, 60, 80],
                    bg: [20, 22, 28], bg2: [26, 31, 46], white: [255, 255, 255],
                    emerald: [16, 185, 129], purple: [139, 92, 246], blue: [59, 130, 246],
                    rose: [244, 63, 94], yellow: [234, 179, 8],
                };

                function drawHeader(subtitle) {
                    doc.setFillColor(...C.dark);
                    doc.rect(0, 0, pW, 20, 'F');
                    doc.setFillColor(80, 80, 80);
                    doc.rect(0, 20, pW, 1.5, 'F');
                    doc.setFontSize(12); doc.setFont(undefined, 'bold');
                    doc.setTextColor(...C.white);
                    doc.text('OwlOps', lm, 10);
                    doc.setFontSize(6.5); doc.setFont(undefined, 'normal');
                    doc.setTextColor(...C.muted);
                    doc.text('STEGAVAULT SUPER ADMIN SYSTEM', lm, 16);
                    doc.setFontSize(10); doc.setFont(undefined, 'bold');
                    doc.setTextColor(220, 220, 255);
                    doc.text(subtitle, pW / 2, 10, { align: 'center' });
                    doc.setFontSize(7); doc.setFont(undefined, 'normal');
                    doc.setTextColor(160, 160, 200);
                    doc.text(PERIOD_LABEL, pW / 2, 16.5, { align: 'center' });
                    doc.setFontSize(6.5); doc.setTextColor(...C.muted);
                    doc.text(DOC_ID, pW - rm, 9, { align: 'right' });
                    doc.text(GEN_AT, pW - rm, 15.5, { align: 'right' });
                }

                function drawFooter(num, total) {
                    doc.setFillColor(15, 15, 20);
                    doc.rect(0, pH - 9, pW, 9, 'F');
                    doc.setFontSize(6.5); doc.setFont(undefined, 'normal');
                    doc.setTextColor(...C.muted);
                    doc.text('OwlOps Super Admin Report  ·  ' + DOC_ID + '  ·  CONFIDENTIAL', lm, pH - 3.5);
                    doc.text('Page ' + num + ' of ' + total, pW - rm, pH - 3.5, { align: 'right' });
                }

                function drawSection(label, y) {
                    doc.setFillColor(30, 32, 45);
                    doc.rect(lm, y, cW, 7.5, 'F');
                    doc.setFillColor(100, 100, 120);
                    doc.rect(lm, y, 3.5, 7.5, 'F');
                    doc.setFontSize(7.5); doc.setFont(undefined, 'bold');
                    doc.setTextColor(200, 200, 220);
                    doc.text(label.toUpperCase(), lm + 7, y + 5.2);
                    return y + 12;
                }

                const tblStyles = {
                    margin: { left: lm, right: rm },
                    theme: 'grid',
                    styles: { fontSize: 7.5, cellPadding: 2.8, textColor: [180, 190, 210], lineColor: [40, 50, 70], lineWidth: 0.15, fillColor: [18, 20, 28] },
                    headStyles: { fillColor: [35, 40, 60], textColor: [200, 210, 240], fontStyle: 'bold', fontSize: 7.5, cellPadding: 3 },
                    alternateRowStyles: { fillColor: [22, 25, 35] },
                };

                // ── Page 1: Overview ──
                drawHeader('System Status & Audit Report');
                let y = 26;

                y = drawSection('Platform Overview', y);
                doc.autoTable({
                    ...tblStyles,
                    startY: y,
                    head: [['Metric', 'Value', 'Metric', 'Value']],
                    body: [
                        ['Total Users', '<?php echo number_format($totalUsers); ?>', 'Total Files', '<?php echo number_format($totalFiles); ?>'],
                        ['Active Users', '<?php echo number_format($activeUsers); ?>', 'Storage Used', '<?php echo formatBytes($totalFileSize); ?>'],
                        ['App Admins', '<?php echo number_format($adminCount); ?>', 'Watermarked', '<?php echo number_format($watermarkedFiles); ?>'],
                        ['Super Admins', '<?php echo number_format($superAdminCount); ?>', 'Total Projects', '<?php echo number_format($totalProjects); ?>'],
                        ['MFA Enabled', '<?php echo $mfaEnabledCount; ?> (<?php echo $totalUsers > 0 ? round($mfaEnabledCount / $totalUsers * 100) : 0; ?>%)', 'Audit Events', '<?php echo number_format($totalAuditEvents); ?>'],
                        ['Pending Accounts', '<?php echo $pendingUsers; ?>', 'Total Backups', '<?php echo $totalBackups; ?>'],
                    ],
                });
                y = doc.lastAutoTable.finalY + 8;

                <?php if (!empty($recentAudit)): ?>
                y = drawSection('Recent Audit Activity', y);
                doc.autoTable({
                    ...tblStyles,
                    startY: y,
                    head: [['Timestamp', 'Actor', 'Action', 'Category', 'IP Address']],
                    body: [
                        <?php
                        $actionLabelsJS = [
                            'login_success' => 'Login', 'login_failed' => 'Login Failed',
                            'logout' => 'Logout', 'backup_db_created' => 'DB Backup',
                            'backup_files_created' => 'Files Backup', 'backup_db_restored' => 'DB Restored',
                            'backup_deleted' => 'Backup Deleted', 'super_admin_created' => 'Super Admin Created',
                            'super_admin_deleted' => 'Super Admin Deleted', 'mfa_enabled' => 'MFA Enabled',
                            'mfa_disabled' => 'MFA Disabled',
                        ];
                        foreach ($recentAudit as $ev):
                            $label = $actionLabelsJS[$ev['action']] ?? ucwords(str_replace('_', ' ', $ev['action']));
                        ?>
                        ['<?php echo addslashes(date('M j H:i', strtotime($ev['created_at']))); ?>',
                         '<?php echo addslashes($ev['actor_name'] ?? '—'); ?>',
                         '<?php echo addslashes($label); ?>',
                         '<?php echo ucfirst(addslashes($ev['category'])); ?>',
                         '<?php echo addslashes($ev['ip_address'] ?? '—'); ?>'],
                        <?php endforeach; ?>
                    ],
                });
                y = doc.lastAutoTable.finalY + 8;
                <?php endif; ?>

                <?php if (!empty($backups)): ?>
                if (y > pH - 50) { doc.addPage(); drawHeader('System Status & Audit Report'); y = 26; }
                y = drawSection('Backup History', y);
                doc.autoTable({
                    ...tblStyles,
                    startY: y,
                    head: [['Backup ID', 'Type', 'Created By', 'Created At', 'Size']],
                    body: [
                        <?php foreach (array_slice($backups, 0, 10) as $bk): ?>
                        ['<?php echo addslashes($bk['id'] ?? '—'); ?>',
                         '<?php echo ucfirst(addslashes($bk['type'] ?? 'unknown')); ?>',
                         '<?php echo addslashes($bk['created_by'] ?? '—'); ?>',
                         '<?php echo addslashes($bk['created_at'] ?? '—'); ?>',
                         '<?php echo isset($bk['size']) ? formatBytes((int)$bk['size']) : '—'; ?>'],
                        <?php endforeach; ?>
                    ],
                });
                <?php endif; ?>

                <?php if (!empty($superAdmins)): ?>
                doc.addPage(); drawHeader('System Status & Audit Report'); y = 26;
                y = drawSection('Super Admin Accounts', y);
                doc.autoTable({
                    ...tblStyles,
                    startY: y,
                    head: [['Name', 'Email', 'Added']],
                    body: [
                        <?php foreach ($superAdmins as $sa): ?>
                        ['<?php echo addslashes($sa['name']); ?>',
                         '<?php echo addslashes($sa['email']); ?>',
                         '<?php echo date('M j, Y', strtotime($sa['created_at'])); ?>'],
                        <?php endforeach; ?>
                    ],
                });
                <?php endif; ?>

                // Add footers
                const pageCount = doc.internal.getNumberOfPages();
                for (let i = 1; i <= pageCount; i++) {
                    doc.setPage(i);
                    drawFooter(i, pageCount);
                }

                doc.save('OwlOps-Report-<?php echo date('Ymd'); ?>.pdf');
            } catch (err) {
                console.error('PDF error:', err);
                alert('Failed to generate PDF. Please try again.');
            } finally {
                btn.innerHTML = '<span class="material-symbols-outlined text-base">download</span> Download PDF';
                btn.disabled = false;
            }
        }
    </script>
    <script src="session-timeout.js"></script>
</body>
</html>
