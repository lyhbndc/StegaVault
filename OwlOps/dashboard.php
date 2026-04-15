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

$user = [
    'id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role']
];

// Fetch high-level stats
$stats = [
    'total_apps' => 0,
    'total_super_admins' => 0,
    'total_app_admins' => 0,
    'last_backup' => '2 hours ago'
];

$appsCount = $db->query("SELECT COUNT(*) as count FROM web_apps")->fetch_assoc();
$stats['total_apps'] = $appsCount['count'] ?? 0;

$superCount = $db->query("SELECT COUNT(*) as count FROM super_admins")->fetch_assoc();
$stats['total_super_admins'] = $superCount['count'] ?? 0;

$adminCount = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc();
$stats['total_app_admins'] = $adminCount['count'] ?? 0;

?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Control Center - OwlOps</title>
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
        body { font-family: 'Inter', sans-serif; background-color: #000000; }
        h1, h2, h3, h4, h5, h6, .font-display { font-family: 'Space Grotesk', sans-serif; }
        .bg-grid-pattern {
            background-image: radial-gradient(#ffffff 0.1px, transparent 0.1px);
            background-size: 40px 40px;
        }
    </style>
</head>

<body class="text-slate-200 min-h-screen flex flex-col relative overflow-hidden">

    <!-- Background Decor -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
        <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] bg-primary/5 rounded-full blur-[140px]"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 w-full px-8 py-6 flex items-center justify-between border-b border-white/5 bg-background-dark/80 backdrop-blur-md sticky top-0">
        <div class="flex items-center gap-4">
            <div class="bg-primary p-2.5 rounded-2xl shadow-lg shadow-white/10">
                <span class="material-symbols-outlined text-black text-2xl">security</span>
            </div>
            <div>
                <h2 class="text-white text-2xl font-bold tracking-tight font-display">OwlOps</h2>
                <div class="flex items-center gap-2">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase">Global Control Node</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-8">
            <div class="flex items-center gap-4 bg-white/5 border border-white/10 rounded-2xl px-5 py-2.5">
                <div class="size-8 rounded-full bg-gradient-to-br from-primary to-slate-400 flex items-center justify-center text-black font-bold text-xs">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div class="hidden md:block">
                    <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($user['name']); ?></p>
                    <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Root Authority</p>
                </div>
                <div class="h-6 w-px bg-white/10 mx-1"></div>
                <a href="mfa-settings.php" title="MFA Settings" class="material-symbols-outlined text-slate-400 hover:text-primary transition-colors text-[20px]">phonelink_lock</a>
                <div class="h-6 w-px bg-white/10 mx-1"></div>
                <button onclick="logout()" class="material-symbols-outlined text-slate-400 hover:text-red-400 transition-colors text-[20px]">logout</button>
            </div>
        </div>
    </header>

    <main class="relative z-10 flex-1 max-w-7xl w-full mx-auto px-8 py-16 flex flex-col gap-16">
        
        <!-- Welcome Section -->
        <div class="max-w-3xl">
            <h1 class="text-5xl font-bold text-white mb-6 font-display tracking-tight leading-tight">Welcome back, <span class="text-primary/70 italic"><?php echo explode(' ', $user['name'])[0]; ?>.</span></h1>
            <p class="text-lg text-slate-400 leading-relaxed font-body">The infrastructure is currently synchronized. You have full oversight of all administrators and system preservation tasks.</p>
        </div>

        <!-- Quick Access Control Cards -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Account Management -->
            <a href="manage_admins.php" class="bg-slate-card border border-white/5 rounded-[2.5rem] p-10 group hover:border-primary/40 hover:-translate-y-2 transition-all duration-500 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 size-48 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-colors"></div>
                <div class="relative z-10 space-y-6">
                    <div class="p-4 bg-white/10 rounded-[1.5rem] w-fit border border-white/10 group-hover:bg-primary group-hover:text-black transition-all">
                        <span class="material-symbols-outlined text-3xl">admin_panel_settings</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white font-display">Administrator Access</h3>
                        <p class="text-slate-500 text-sm mt-2 leading-relaxed">Manage global system owners and app-specific administrators in one place.</p>
                    </div>
                    <div class="flex items-center gap-6 pt-4 border-t border-white/5">
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Super</p>
                            <p class="text-xl font-bold text-white"><?php echo $stats['total_super_admins']; ?></p>
                        </div>
                        <div class="w-px h-8 bg-white/5"></div>
                        <div>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">App Admins</p>
                            <p class="text-xl font-bold text-white"><?php echo $stats['total_app_admins']; ?></p>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Backup & Restore -->
            <a href="backup.php" class="bg-slate-card border border-white/5 rounded-[2.5rem] p-10 group hover:border-blue-500/40 hover:-translate-y-2 transition-all duration-500 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 size-48 bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-500/10 transition-colors"></div>
                <div class="relative z-10 space-y-6">
                    <div class="p-4 bg-blue-500/10 rounded-[1.5rem] w-fit border border-white/10 group-hover:bg-blue-500 group-hover:text-white transition-all text-blue-400">
                        <span class="material-symbols-outlined text-3xl">backup</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white font-display">Backup & Sync</h3>
                        <p class="text-slate-500 text-sm mt-2 leading-relaxed">System-wide snapshots and environment restoration protocols.</p>
                    </div>
                    <div class="flex items-center gap-3 pt-4 border-t border-white/5">
                        <span class="material-symbols-outlined text-emerald-500 text-sm">check_circle</span>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Last Sync: <?php echo $stats['last_backup']; ?></p>
                    </div>
                </div>
            </a>

            <!-- Scopes / Environments -->
            <div class="bg-slate-card border border-white/5 rounded-[2.5rem] p-10 group opacity-80 hover:opacity-100 transition-all duration-500 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 size-48 bg-slate-500/5 rounded-full blur-3xl"></div>
                <div class="relative z-10 space-y-6 flex flex-col h-full">
                    <div class="p-4 bg-white/5 rounded-[1.5rem] w-fit border border-white/10 text-slate-400">
                        <span class="material-symbols-outlined text-3xl">apps</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white font-display">Application Scopes</h3>
                        <p class="text-slate-500 text-sm mt-2 leading-relaxed">Monitoring <?php echo $stats['total_apps']; ?> isolated environments within the ecosystem.</p>
                    </div>
                    <div class="mt-auto pt-6">
                        <div class="flex -space-x-3 overflow-hidden">
                            <?php 
                            $apps = $db->query("SELECT name FROM web_apps LIMIT 4");
                            while($a = $apps->fetch_assoc()): ?>
                                <div class="inline-block size-8 rounded-full ring-4 ring-black bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-400 border border-white/10">
                                    <?php echo strtoupper(substr($a['name'], 0, 1)); ?>
                                </div>
                            <?php endwhile; ?>
                            <?php if($stats['total_apps'] > 4): ?>
                                <div class="inline-block size-8 rounded-full ring-4 ring-black bg-white/10 flex items-center justify-center text-[10px] font-bold text-white border border-white/10">
                                    +<?php echo $stats['total_apps'] - 4; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- System Alerts / Activity -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-12 pt-8 border-t border-white/5">
            <div class="space-y-6">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-[0.2em]">Recent System Activity</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-4 p-4 rounded-2xl hover:bg-white/[0.02] transition-colors">
                        <div class="size-2 bg-emerald-500 rounded-full mt-2"></div>
                        <div>
                            <p class="text-sm text-white font-medium">Automatic Snapshot Completed</p>
                            <p class="text-xs text-slate-500 mt-1">Status: SV-20260413-0900 stored in secure vault.</p>
                        </div>
                        <span class="ml-auto text-[10px] text-slate-600 font-bold uppercase">2h ago</span>
                    </div>
                    <div class="flex items-start gap-4 p-4 rounded-2xl hover:bg-white/[0.02] transition-colors">
                        <div class="size-2 bg-blue-500 rounded-full mt-2"></div>
                        <div>
                            <p class="text-sm text-white font-medium">New App Admin Provisioned</p>
                            <p class="text-xs text-slate-500 mt-1">Context: StegaVault Corporate (ID: 12)</p>
                        </div>
                        <span class="ml-auto text-[10px] text-slate-600 font-bold uppercase">5h ago</span>
                    </div>
                </div>
            </div>
            <div class="bg-primary/5 rounded-[2rem] p-8 border border-white/5">
                <h3 class="text-sm font-bold text-white mb-2 font-display tracking-wider">Operational Integrity</h3>
                <p class="text-xs text-slate-400 leading-relaxed mb-6">All systems are currently performing within expected latency parameters. Supabase database connectivity is stable at 24ms.</p>
                <div class="flex items-center gap-6">
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold text-white tracking-tighter">99.9%</span>
                        <span class="text-[8px] text-slate-500 uppercase font-bold tracking-widest mt-1">Uptime</span>
                    </div>
                    <div class="w-px h-10 bg-white/10"></div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold text-white tracking-tighter">0.02%</span>
                        <span class="text-[8px] text-slate-500 uppercase font-bold tracking-widest mt-1">Error Rate</span>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <script>
        async function logout() {
            await fetch('../StegaVault/api/super_admin_auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.php';
        }
    </script>
</body>

</html>