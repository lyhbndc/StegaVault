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
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title><?php echo htmlspecialchars($webAppName); ?> - Environment Admin</title>
    <script>if(localStorage.getItem('owlops-theme')==='dark')document.documentElement.classList.add('dark');</script>
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
                        "primary": "#2563eb",
                        "primary-hover": "#1e40af",
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
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; }
        html.dark body { background-color: #000000; }
        h1, h2, h3, h4, h5, h6, .font-display { font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>

<body class="bg-white dark:bg-black text-slate-900 dark:text-slate-200 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 border-r border-slate-200 dark:border-white/5 bg-white dark:bg-black flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full gap-8">
            <div class="flex items-center gap-2">
                <img src="OwlOps.png" alt="OwlOps Logo" class="h-8 w-auto">
                <div>
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight font-display">OwlOps</h1>
                    <p class="text-primary text-[10px] font-bold uppercase tracking-widest mt-1">Super Admin Mode</p>
                </div>
            </div>

            <!-- Context Banner -->
            <div class="px-4 py-3 bg-primary/10 border border-primary/20 rounded-xl relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-primary/10 to-transparent"></div>
                <div class="relative z-10">
                    <p class="text-[10px] text-primary font-bold uppercase tracking-widest mb-1">Active Context</p>
                    <p class="text-slate-900 dark:text-white text-sm font-semibold truncate" title="<?php echo htmlspecialchars($webAppName); ?>">
                        <?php echo htmlspecialchars($webAppName); ?>
                    </p>
                </div>
            </div>

            <nav class="flex flex-col gap-2 flex-1 relative z-10">
                <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Systems</p>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-400 hover:text-primary dark:hover:text-white hover:bg-primary/5 dark:hover:bg-white/5 transition-colors" href="dashboard.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <p class="text-sm font-medium">Control Center</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-400 hover:text-primary dark:hover:text-white hover:bg-primary/5 dark:hover:bg-white/5 transition-colors" href="manage_admins.php">
                    <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                    <p class="text-sm font-medium">Manage Admins</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-400 hover:text-primary dark:hover:text-white hover:bg-primary/5 dark:hover:bg-white/5 transition-colors" href="backup.php">
                    <span class="material-symbols-outlined text-[20px]">backup</span>
                    <p class="text-sm font-medium">Backup &amp; Restore</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-400 hover:text-primary dark:hover:text-white hover:bg-primary/5 dark:hover:bg-white/5 transition-colors" href="mfa-settings.php">
                    <span class="material-symbols-outlined text-[20px]">phonelink_lock</span>
                    <p class="text-sm font-medium">MFA Settings</p>
                </a>

                <div class="mt-8">
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Environment</p>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 dark:bg-primary/20 text-primary border border-primary/20 dark:border-primary/30" href="app_dashboard.php">
                        <span class="material-symbols-outlined text-[20px] text-primary">monitoring</span>
                        <p class="text-sm font-medium">App Overview</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-400 hover:text-primary dark:hover:text-white hover:bg-primary/5 dark:hover:bg-white/5 transition-colors" href="admins.php">
                        <span class="material-symbols-outlined text-[20px]">group</span>
                        <p class="text-sm font-medium">App Admins</p>
                    </a>
                </div>
            </nav>

            <div class="pt-6 border-t border-slate-200 dark:border-white/5 space-y-1 relative z-10">
                <div class="flex items-center gap-3 px-3 py-2">
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-xs">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-slate-900 dark:text-white text-xs font-bold truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-slate-500 text-[10px] truncate">Super Admin</p>
                    </div>
                </div>
                <button onclick="toggleTheme()" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-[20px]" id="themeIcon">dark_mode</span>
                    <p class="text-sm font-medium" id="themeLabel">Dark Mode</p>
                </button>
                <button onclick="logout()" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <p class="text-sm font-medium">Sign Out</p>
                </button>
            </div>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-10 flex flex-col gap-8 relative overflow-hidden">

        <!-- Header -->
        <header class="flex items-end justify-between">
            <div>
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 font-display">Overview</h2>
                <p class="text-slate-600 dark:text-slate-400">Environment statistics and quick actions for <span class="text-slate-900 dark:text-white font-medium"><?php echo htmlspecialchars($webAppName); ?></span>.</p>
            </div>

            <a href="dashboard.php" class="px-4 py-2 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                <span class="material-symbols-outlined text-sm">exit_to_app</span> Switch App
            </a>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-50 dark:bg-slate-card border border-slate-200 dark:border-white/10 rounded-2xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 bg-primary/10 rounded-xl border border-primary/20">
                        <span class="material-symbols-outlined text-primary">admin_panel_settings</span>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-slate-900 dark:text-white mb-1 font-display"><?php echo number_format($stats['total_admins']); ?></h3>
                <p class="text-slate-400 text-sm">Application Admins</p>
            </div>

            <div class="bg-slate-50 dark:bg-slate-card border border-slate-200 dark:border-white/10 rounded-2xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 bg-blue-500/10 rounded-xl border border-blue-500/20">
                        <span class="material-symbols-outlined text-blue-400">group</span>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-slate-900 dark:text-white mb-1 font-display"><?php echo number_format($stats['total_employees']); ?></h3>
                <p class="text-slate-400 text-sm">Active Employees</p>
            </div>

            <div class="bg-slate-50 dark:bg-slate-card border border-slate-200 dark:border-white/10 rounded-2xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                        <span class="material-symbols-outlined text-emerald-400">folder_open</span>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-slate-900 dark:text-white mb-1 font-display"><?php echo number_format($stats['total_files']); ?></h3>
                <p class="text-slate-400 text-sm">Secure Files Hosted</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 font-display">Environment Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="admins.php" class="bg-slate-50 dark:bg-slate-card border border-slate-200 dark:border-white/10 rounded-xl p-5 hover:border-primary/50 transition-colors group flex items-start gap-4">
                    <div class="p-3 bg-slate-100 dark:bg-white/5 rounded-lg group-hover:bg-primary/10 dark:group-hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-slate-500 dark:text-slate-300 group-hover:text-primary transition-colors">person_add</span>
                    </div>
                    <div>
                        <h4 class="text-slate-900 dark:text-white font-bold mb-1">Manage Admins</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Add or remove administrators who govern this web application.</p>
                    </div>
                </a>

                <div class="bg-slate-50 dark:bg-slate-card border border-slate-200 dark:border-white/10 rounded-xl p-5 opacity-50 pointer-events-none flex items-start gap-4">
                    <div class="p-3 bg-slate-100 dark:bg-white/5 rounded-lg">
                        <span class="material-symbols-outlined text-slate-400 dark:text-slate-300">security_update_good</span>
                    </div>
                    <div>
                        <h4 class="text-slate-900 dark:text-white font-bold mb-1">Security Policies</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Configure watermark protocols and access limits (Coming Soon).</p>
                    </div>
                </div>
            </div>
        </div>

    </main>
    <script>
        async function logout() {
            await fetch('../StegaVault/api/super_admin_auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.php';
        }

        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('owlops-theme', isDark ? 'dark' : 'light');
            const icon = document.getElementById('themeIcon');
            const label = document.getElementById('themeLabel');
            if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
            if (label) label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        }
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            const icon = document.getElementById('themeIcon');
            const label = document.getElementById('themeLabel');
            if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
            if (label) label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        });
    </script>
    <script src="session-timeout.js"></script>
</body>

</html>