<?php

/**
 * StegaVault - Super Admin Control Center
 * File: OwlOps/dashboard.php
 */

session_start();
require_once '../StegaVault/includes/db.php';

// Check if user is logged in as Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/auth_guard.php';

$user = [
    'id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role']
];

// Fetch high-level stats
$stats = [
    'total_apps'          => 0,
    'total_super_admins'  => 0,
    'total_app_admins'    => 0,
    'total_users'         => 0,
    'total_audit_events'  => 0,
    'total_backups'       => 0,
    'file_backups'        => 0,
    'last_backup'         => null,
    'last_backup_by'      => null,
];

$appsCount = $db->query("SELECT COUNT(*) as count FROM web_apps")->fetch_assoc();
$stats['total_apps'] = $appsCount['count'] ?? 0;

$superCount = $db->query("SELECT COUNT(*) as count FROM super_admins")->fetch_assoc();
$stats['total_super_admins'] = $superCount['count'] ?? 0;

$adminCount = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc();
$stats['total_app_admins'] = $adminCount['count'] ?? 0;

$userCount = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc();
$stats['total_users'] = $userCount['count'] ?? 0;

try {
    $auditCount = $db->query("SELECT COUNT(*) as count FROM super_admin_audit_log")->fetch_assoc();
    $stats['total_audit_events'] = $auditCount['count'] ?? 0;
} catch (Exception $e) { $stats['total_audit_events'] = 0; }

// Backup count from meta file
$stats['total_backups'] = 0;
$backupMetaPath = '/opt/backups/backups_meta.json';
if (file_exists($backupMetaPath)) {
    $backupMeta = json_decode(file_get_contents($backupMetaPath), true) ?? [];
    $stats['total_backups'] = count($backupMeta);
    $stats['file_backups']  = count(array_filter($backupMeta, fn($b) => ($b['type'] ?? '') === 'files'));
    if (!empty($backupMeta)) {
        $stats['last_backup'] = $backupMeta[0]['created_at'] ?? '—';
        $stats['last_backup_by'] = $backupMeta[0]['created_by'] ?? '—';
    }
}

// Real recent audit activity (last 6)
$recentEvents = [];
try {
    $auditQuery = $db->query(
        "SELECT action, category, actor_name, created_at
         FROM super_admin_audit_log
         ORDER BY created_at DESC
         LIMIT 6"
    );
    if ($auditQuery) {
        while ($row = $auditQuery->fetch_assoc()) $recentEvents[] = $row;
    }
} catch (Exception $e) { $recentEvents = []; }

// Action label + icon + colour map
$actionMeta = [
    'login_success'          => ['Login',              'login',            'emerald'],
    'login_failed'           => ['Failed Login',       'warning',          'red'],
    'login_mfa_challenged'   => ['MFA Challenge',      'shield_lock',      'blue'],
    'login_mfa_success'      => ['MFA Login',          'verified_user',    'emerald'],
    'logout'                 => ['Logout',              'logout',           'slate'],
    'mfa_enabled'            => ['MFA Enabled',        'phonelink_lock',   'blue'],
    'mfa_disabled'           => ['MFA Disabled',       'no_encryption',    'orange'],
    'backup_db_created'      => ['DB Backup',          'database',         'blue'],
    'backup_files_created'   => ['Files Backup',       'folder_zip',       'purple'],
    'backup_db_restored'     => ['DB Restored',        'restore',          'orange'],
    'backup_db_full_restored'=> ['Full Restore',       'restart_alt',      'red'],
    'backup_files_restored'  => ['Files Restored',     'folder_open',      'purple'],
    'backup_deleted'         => ['Backup Deleted',     'delete',           'red'],
    'super_admin_created'    => ['Super Admin Added',  'person_add',       'emerald'],
    'super_admin_deleted'    => ['Super Admin Removed','person_remove',    'red'],
    'app_admin_created'      => ['App Admin Added',    'manage_accounts',  'emerald'],
    'app_admin_deleted'      => ['App Admin Removed',  'manage_accounts',  'red'],
];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Control Center - OwlOps</title>
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
                        "background-light": "#ffffff",
                        "card-light": "#f8fafc",
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
        .bg-grid-pattern {
            background-image: radial-gradient(#cbd5e1 0.1px, transparent 0.1px);
            background-size: 40px 40px;
        }
        html.dark .bg-grid-pattern {
            background-image: radial-gradient(rgba(255,255,255,0.12) 0.1px, transparent 0.1px);
        }
    </style>
</head>

<body class="text-slate-900 dark:text-slate-100 min-h-screen flex flex-col relative">

    <!-- Background Decor -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] bg-primary/5 rounded-full blur-[140px]"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 w-full px-8 py-6 flex items-center justify-between border-b border-slate-200 dark:border-white/10 bg-background-light/80 dark:bg-black/80 backdrop-blur-md sticky top-0">
        <div class="flex items-center gap-4">
            <img src="OwlOps.png" alt="OwlOps Logo" class="h-12 w-auto">
            <div>
                <h2 class="text-slate-900 dark:text-white text-2xl font-bold tracking-tight font-display">OwlOps</h2>
                <div class="flex items-center gap-2">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase">Global Control Node</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button onclick="toggleTheme()" class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors" title="Toggle theme">
                <span class="material-symbols-outlined text-[20px]" id="themeIcon">dark_mode</span>
            </button>
            <div class="flex items-center gap-4 bg-slate-100 dark:bg-[#111111] border border-slate-200 dark:border-white/10 rounded-2xl px-5 py-2.5">
                <div class="size-8 rounded-full bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-white font-bold text-xs">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div class="hidden md:block">
                    <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['name']); ?></p>
                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Root Authority</p>
                </div>
                <div class="h-6 w-px bg-slate-200 dark:bg-white/10 mx-1"></div>
                <a href="mfa-settings.php" title="MFA Settings" class="material-symbols-outlined text-slate-500 hover:text-primary transition-colors text-[20px]">phonelink_lock</a>
                <div class="h-6 w-px bg-slate-200 dark:bg-white/10 mx-1"></div>
                <button onclick="logout()" class="material-symbols-outlined text-slate-500 hover:text-red-500 transition-colors text-[20px]">logout</button>
            </div>
        </div>
    </header>

    <main class="relative z-10 flex-1 max-w-7xl w-full mx-auto px-8 py-16 flex flex-col gap-16">
        
        <!-- Welcome Section -->
        <div class="max-w-3xl">
            <h1 class="text-5xl font-bold text-slate-900 dark:text-white mb-6 font-display tracking-tight leading-tight">Welcome back, <span class="text-primary/70 italic"><?php echo explode(' ', $user['name'])[0]; ?>.</span></h1>
            <p class="text-lg text-slate-600 dark:text-slate-400 leading-relaxed font-body">The infrastructure is currently synchronized. You have full oversight of all administrators and system preservation tasks.</p>
        </div>

        <!-- Quick Access Control Cards -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Account Management -->
            <a href="manage_admins.php" class="bg-white dark:bg-slate-card border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-10 group hover:border-primary/40 hover:-translate-y-2 transition-all duration-500 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 size-48 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-colors"></div>
                <div class="relative z-10 space-y-6">
                    <div class="p-4 bg-slate-100 dark:bg-white/10 rounded-[1.5rem] w-fit border border-slate-200 dark:border-white/10 group-hover:bg-primary group-hover:text-white transition-all text-slate-600 dark:text-white">
                        <span class="material-symbols-outlined text-3xl">admin_panel_settings</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white font-display">Administrator Access</h3>
                        <p class="text-slate-500 text-sm mt-2 leading-relaxed">Manage global system owners and app-specific administrators in one place.</p>
                    </div>
                    <div class="flex items-center gap-6 pt-4 border-t border-slate-100 dark:border-white/5">
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Super</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $stats['total_super_admins']; ?></p>
                        </div>
                        <div class="w-px h-8 bg-slate-200 dark:bg-white/5"></div>
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">App Admins</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $stats['total_app_admins']; ?></p>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Backup & Restore -->
            <a href="backup.php" class="bg-white dark:bg-slate-card border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-10 group hover:border-blue-500/40 hover:-translate-y-2 transition-all duration-500 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 size-48 bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-500/10 transition-colors"></div>
                <div class="relative z-10 space-y-6">
                    <div class="p-4 bg-blue-500/10 rounded-[1.5rem] w-fit border border-slate-200 dark:border-white/10 group-hover:bg-blue-500 group-hover:text-white transition-all text-blue-500 dark:text-blue-400">
                        <span class="material-symbols-outlined text-3xl">backup</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white font-display">Backup & Sync</h3>
                        <p class="text-slate-500 text-sm mt-2 leading-relaxed">System-wide snapshots and environment restoration protocols.</p>
                    </div>
                    <div class="pt-4 border-t border-slate-100 dark:border-white/5 space-y-2">
                        <?php if ($stats['total_backups'] > 0): ?>
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-emerald-500 text-sm">check_circle</span>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest truncate">Last: <?php echo htmlspecialchars($stats['last_backup'] ?? '—'); ?></p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">DB</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo $stats['total_backups'] - $stats['file_backups']; ?></p>
                                </div>
                                <div class="w-px h-6 bg-slate-200 dark:bg-white/5"></div>
                                <div>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Files</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo $stats['file_backups']; ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-slate-400 text-sm">radio_button_unchecked</span>
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">No backups yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>

            <!-- System Report -->
            <a href="audit-log.php" class="bg-white dark:bg-slate-card border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-10 group hover:border-emerald-500/40 hover:-translate-y-2 transition-all duration-500 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 size-48 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-colors"></div>
                <div class="relative z-10 space-y-6">
                    <div class="p-4 bg-emerald-500/10 rounded-[1.5rem] w-fit border border-slate-200 dark:border-white/10 group-hover:bg-emerald-500 group-hover:text-white transition-all text-emerald-600 dark:text-emerald-400">
                        <span class="material-symbols-outlined text-3xl">assessment</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white font-display">System Report</h3>
                        <p class="text-slate-500 text-sm mt-2 leading-relaxed">Live overview of users, backups, and audit activity across the platform.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4 pt-4 border-t border-slate-100 dark:border-white/5">
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Total Users</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo number_format($stats['total_users']); ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Audit Events</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo number_format($stats['total_audit_events']); ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Backups Stored</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $stats['total_backups']; ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Web Apps</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $stats['total_apps']; ?></p>
                        </div>
                    </div>
                </div>
            </a>
        </section>


    </main>

    <script>
        async function logout() {
            await fetch('../StegaVault/api/super_admin_auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.php';
        }
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('owlops-theme', isDark ? 'dark' : 'light');
            document.getElementById('themeIcon').textContent = isDark ? 'light_mode' : 'dark_mode';
        }
        document.addEventListener('DOMContentLoaded', function() {
            const icon = document.getElementById('themeIcon');
            if (icon) icon.textContent = document.documentElement.classList.contains('dark') ? 'light_mode' : 'dark_mode';
        });
    </script>
    <script src="session-timeout.js"></script>
</body>

</html>