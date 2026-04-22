<?php

/**
 * StegaVault - Super Admin App Dashboard
 * File: super_admin/app_dashboard.php
 */

session_start();
require_once '../StegaVault/includes/db.php';

// Check if user is logged in as Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/auth_guard.php';

// Check if a web app context is selected
if (!isset($_SESSION['manage_web_app_id'])) {
    header('Location: dashboard.php');
    exit;
}

$webAppId = $_SESSION['manage_web_app_id'];
$webAppName = $_SESSION['manage_web_app_name'];

$user = [
    'id' => $_SESSION['user_id'],
    'name' => $_SESSION['name']
];

// Get some stats for this specific app
$stats = [
    'total_admins' => 0,
    'total_employees' => 0,
    'total_files' => 0
];

// Count admins
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND web_app_id = ?");
$stmt->bind_param('i', $webAppId);
$stmt->execute();
$stats['total_admins'] = $stmt->get_result()->fetch_assoc()['count'];

// Count employees (assuming same web_app_id structure or linked via created_by - for simplicity we just count same web_app_id)
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'employee' AND web_app_id = ?");
$stmt->bind_param('i', $webAppId);
$stmt->execute();
$stats['total_employees'] = $stmt->get_result()->fetch_assoc()['count'];

// Count files (files belonging to users in this app)
$stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM files f
    JOIN users u ON f.user_id = u.id
    WHERE u.web_app_id = ?
");
$stmt->bind_param('i', $webAppId);
$stmt->execute();
$stats['total_files'] = $stmt->get_result()->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title><?php echo htmlspecialchars($webAppName); ?> - Environment Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#ffffff",
                        "primary-hover": "#e2e8f0",
                        "background-dark": "#000000",
                        "slate-card": "#111111",
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
        body {
            font-family: 'Inter', sans-serif;
            background-color: #000000;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .font-display {
            font-family: 'Space Grotesk', sans-serif;
        }
    </style>
</head>

<body class="text-slate-200 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 border-r border-white/5 bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50 shadow-xl shadow-black/50">
        <div class="p-6 flex flex-col h-full gap-8">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-white text-base font-bold leading-tight font-display">OwlOps</h1>
                    <p class="text-primary text-[10px] font-bold uppercase tracking-widest mt-1">Super Admin Mode</p>
                </div>
            </div>

            <!-- Context Banner -->
            <div class="px-4 py-3 bg-primary/10 border border-primary/20 rounded-xl relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-primary/10 to-transparent"></div>
                <div class="relative z-10">
                    <p class="text-[10px] text-primary font-bold uppercase tracking-widest mb-1">Active Context</p>
                    <p class="text-white text-sm font-semibold truncate" title="<?php echo htmlspecialchars($webAppName); ?>">
                        <?php echo htmlspecialchars($webAppName); ?>
                    </p>
                </div>
            </div>

            <nav class="flex flex-col gap-2 flex-1 relative z-10">
                <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Systems</p>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="dashboard.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <p class="text-sm font-medium">Control Center</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="manage_admins.php">
                    <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                    <p class="text-sm font-medium">Manage Admins</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="backup.php">
                    <span class="material-symbols-outlined text-[20px]">backup</span>
                    <p class="text-sm font-medium">Backup & Restore</p>
                </a>

                <div class="mt-8">
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Environment</p>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/20 text-white border border-primary/30 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]" href="app_dashboard.php">
                        <span class="material-symbols-outlined text-[20px] text-primary">monitoring</span>
                        <p class="text-sm font-medium">App Overview</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="admins.php">
                        <span class="material-symbols-outlined text-[20px]">group</span>
                        <p class="text-sm font-medium">App Admins</p>
                    </a>
                </div>
            </nav>

            <div class="pt-6 border-t border-white/5 relative z-10 text-xs text-slate-500 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">security</span>
                <span>OwlOps Core v2.0</span>
            </div>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-10 flex flex-col gap-8 relative overflow-hidden">

        <!-- Header -->
        <header class="flex items-end justify-between">
            <div>
                <h2 class="text-3xl font-bold text-white mb-2 font-display">Overview</h2>
                <p class="text-slate-400">Environment statistics and quick actions for <span class="text-white font-medium"><?php echo htmlspecialchars($webAppName); ?></span>.</p>
            </div>

            <a href="dashboard.php" class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 text-slate-300 hover:text-white">
                <span class="material-symbols-outlined text-sm">exit_to_app</span> Switch App
            </a>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-card border border-white/10 rounded-2xl p-6 shadow-lg shadow-black/20">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 bg-primary/10 rounded-xl border border-primary/20">
                        <span class="material-symbols-outlined text-primary">admin_panel_settings</span>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-white mb-1 font-display"><?php echo number_format($stats['total_admins']); ?></h3>
                <p class="text-slate-400 text-sm">Application Admins</p>
            </div>

            <div class="bg-slate-card border border-white/10 rounded-2xl p-6 shadow-lg shadow-black/20">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 bg-blue-500/10 rounded-xl border border-blue-500/20">
                        <span class="material-symbols-outlined text-blue-400">group</span>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-white mb-1 font-display"><?php echo number_format($stats['total_employees']); ?></h3>
                <p class="text-slate-400 text-sm">Active Employees</p>
            </div>

            <div class="bg-slate-card border border-white/10 rounded-2xl p-6 shadow-lg shadow-black/20">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                        <span class="material-symbols-outlined text-emerald-400">folder_open</span>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-white mb-1 font-display"><?php echo number_format($stats['total_files']); ?></h3>
                <p class="text-slate-400 text-sm">Secure Files Hosted</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div>
            <h3 class="text-lg font-bold text-white mb-4 font-display">Environment Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="admins.php" class="bg-slate-card border border-white/10 rounded-xl p-5 shadow-lg shadow-black/20 hover:border-primary/50 transition-colors group flex items-start gap-4">
                    <div class="p-3 bg-white/5 rounded-lg group-hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors">person_add</span>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-1">Manage Admins</h4>
                        <p class="text-sm text-slate-400">Add or remove administrators who govern this web application.</p>
                    </div>
                </a>

                <div class="bg-slate-card border border-white/10 rounded-xl p-5 shadow-lg shadow-black/20 opacity-50 pointer-events-none flex items-start gap-4">
                    <div class="p-3 bg-white/5 rounded-lg">
                        <span class="material-symbols-outlined text-slate-300">security_update_good</span>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-1">Security Policies</h4>
                        <p class="text-sm text-slate-400">Configure watermark protocols and access limits (Coming Soon).</p>
                    </div>
                </div>
            </div>
        </div>

    </main>
    <script src="session-timeout.js"></script>
</body>

</html>