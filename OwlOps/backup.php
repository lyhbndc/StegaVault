<?php

/**
 * StegaVault - System Backup & Restore Layout
 * File: OwlOps/backup.php
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
    'name' => $_SESSION['name']
];

?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>System Maintenance - OwlOps</title>
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
                        "accent-blue": "#3b82f6",
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

        h1, h2, h3, h4, h5, h6, .font-display {
            font-family: 'Space Grotesk', sans-serif;
        }

        .bg-grid-pattern {
            background-image: radial-gradient(#ffffff 0.1px, transparent 0.1px);
            background-size: 30px 30px;
        }

        .glow-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>
</head>

<body class="text-slate-200 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 border-r border-white/5 bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full gap-8">
            <div>
                <h1 class="text-white text-base font-bold leading-tight font-display">OwlOps</h1>
                <p class="text-primary text-[10px] font-bold uppercase tracking-widest mt-1">Super Admin Mode</p>
            </div>

            <nav class="flex flex-col gap-2 flex-1">
                <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Systems</p>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="dashboard.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <p class="text-sm font-medium">Control Center</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="manage_admins.php">
                    <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                    <p class="text-sm font-medium">Manage Admins</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-white border border-white/10" href="backup.php">
                    <span class="material-symbols-outlined text-[20px] text-primary">backup</span>
                    <p class="text-sm font-medium">Backup & Restore</p>
                </a>
            </nav>

            <div class="pt-6 border-t border-white/5">
                <button onclick="logout()" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-red-400 hover:bg-red-400/10 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <p class="text-sm font-medium">Sign Out</p>
                </button>
            </div>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-12 relative overflow-x-hidden">
        <!-- Background Decor -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
            <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
            <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-accent-blue/5 rounded-full blur-[120px]"></div>
        </div>

        <div class="relative z-10 max-w-6xl mx-auto space-y-10">
            <!-- Header -->
            <header class="flex items-end justify-between">
                <div>
                    <h2 class="text-4xl font-bold text-white font-display">System Maintenance</h2>
                    <p class="text-slate-400 mt-2">Manage infrastructure snapshots, database backups, and environment restoration.</p>
                </div>
                <div class="flex items-center gap-3 px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full">
                    <span class="size-2 rounded-full bg-emerald-500 glow-pulse"></span>
                    <span class="text-[10px] text-emerald-400 font-bold uppercase tracking-widest">Infra: Operational</span>
                </div>
            </header>

            <!-- Status Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Database Status -->
                <div class="bg-slate-card border border-white/10 p-6 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-blue-500/10 rounded-xl">
                            <span class="material-symbols-outlined text-blue-400">database</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Supabase DB</span>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs font-medium">Last Successful Backup</p>
                        <h3 class="text-xl font-bold text-white"><?php echo date('M d, Y - H:i'); ?></h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-1 flex-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full w-[85%] bg-blue-500 rounded-full"></div>
                        </div>
                        <span class="text-[10px] text-slate-500 font-mono">85% Capacity</span>
                    </div>
                </div>

                <!-- File System Status -->
                <div class="bg-slate-card border border-white/10 p-6 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-purple-500/10 rounded-xl">
                            <span class="material-symbols-outlined text-purple-400">folder_zip</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Docker Volume</span>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs font-medium">Assets Snapshot</p>
                        <h3 class="text-xl font-bold text-white">2.4 GB Protected</h3>
                    </div>
                    <div class="flex items-center gap-2 text-emerald-400">
                        <span class="material-symbols-outlined text-sm">verified</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest">Integrity Verified</span>
                    </div>
                </div>

                <!-- Snapshot Timer -->
                <div class="bg-slate-card border border-white/10 p-6 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-orange-500/10 rounded-xl">
                            <span class="material-symbols-outlined text-orange-400">timer</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Scheduled Task</span>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs font-medium">Next Automated Sync</p>
                        <h3 class="text-xl font-bold text-white">In 4h 12m</h3>
                    </div>
                    <p class="text-[10px] text-slate-500">Cron: <span class="font-mono text-slate-400">0 0 * * *</span> (Daily Midnight)</p>
                </div>
            </div>

            <!-- Primary Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Backup Panel -->
                <div class="bg-white/5 border border-white/10 rounded-3xl p-10 space-y-8 group hover:border-primary/30 transition-all duration-500 overflow-hidden relative">
                    <div class="absolute -right-20 -top-20 size-64 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-colors"></div>
                    <div class="relative z-10 space-y-4">
                        <h3 class="text-2xl font-bold text-white font-display">Create Snapshot</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Instantly generate a complete restorable snapshot of the current system state, including database records, configuration, and encrypted user assets.</p>
                        <ul class="space-y-3 pb-6 border-b border-white/5">
                            <li class="flex items-center gap-3 text-xs text-slate-300">
                                <span class="material-symbols-outlined text-emerald-400 text-lg">check_circle</span> Includes `super_admins` and `users` tables
                            </li>
                            <li class="flex items-center gap-3 text-xs text-slate-300">
                                <span class="material-symbols-outlined text-emerald-400 text-lg">check_circle</span> Encrypted Asset Volumes (Docker)
                            </li>
                        </ul>
                        <button class="w-full py-4 bg-white hover:bg-slate-200 text-black rounded-2xl font-bold flex items-center justify-center gap-3 shadow-[0_0_30px_rgba(255,255,255,0.1)] transition-all">
                            <span class="material-symbols-outlined">backup</span> Run Manual Backup Now
                        </button>
                    </div>
                </div>

                <!-- Restore Panel -->
                <div class="bg-slate-card border border-white/10 rounded-3xl p-10 space-y-8 group hover:border-red-500/30 transition-all duration-500 overflow-hidden relative">
                    <div class="absolute -right-20 -top-20 size-64 bg-red-500/5 rounded-full blur-3xl group-hover:bg-red-500/10 transition-colors"></div>
                    <div class="relative z-10 space-y-4">
                        <h3 class="text-2xl font-bold text-white font-display text-red-400">Emergency Restore</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Roll back the entire infrastructure to a previous stable state. <span class="text-red-400/80">Caution: This action will overwrite all current data.</span></p>
                        <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-4 flex items-start gap-4">
                            <span class="material-symbols-outlined text-red-400 mt-1">warning</span>
                            <p class="text-[10px] text-red-300/60 leading-relaxed uppercase tracking-widest font-bold">Access restricted to Primary Super Admins. Secondary Auth/MFA required for execution.</p>
                        </div>
                        <button class="w-full py-4 border border-red-500/30 hover:bg-red-500 text-red-400 hover:text-white rounded-2xl font-bold flex items-center justify-center gap-3 transition-all">
                            <span class="material-symbols-outlined">restart_alt</span> Open Restore Wizard
                        </button>
                    </div>
                </div>
            </div>

            <!-- Historical Backups -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white font-display">Backup History</h3>
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Retention Policy: 30 Days</p>
                </div>
                <div class="bg-slate-card border border-white/10 rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white/5 border-b border-white/10">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Snapshot ID</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Type</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Size</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Completed</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4"><p class="text-white font-mono text-xs">SV-20260413-0900</p></td>
                                <td class="px-6 py-4"><span class="px-2 py-0.5 bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-bold uppercase rounded-full">Automatic</span></td>
                                <td class="px-6 py-4"><p class="text-slate-400 text-xs">412 MB</p></td>
                                <td class="px-6 py-4"><p class="text-slate-400 text-xs">Today, 09:00 AM</p></td>
                                <td class="px-6 py-4 text-right"><span class="text-emerald-400 text-[10px] font-bold uppercase">Stored</span></td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4"><p class="text-white font-mono text-xs">SV-20260412-1422</p></td>
                                <td class="px-6 py-4"><span class="px-2 py-0.5 bg-white/5 border border-white/10 text-slate-400 text-[10px] font-bold uppercase rounded-full">Manual</span></td>
                                <td class="px-6 py-4"><p class="text-slate-400 text-xs">410 MB</p></td>
                                <td class="px-6 py-4"><p class="text-slate-400 text-xs">Yesterday, 02:22 PM</p></td>
                                <td class="px-6 py-4 text-right"><span class="text-emerald-400 text-[10px] font-bold uppercase">Stored</span></td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors opacity-50">
                                <td class="px-6 py-4"><p class="text-white font-mono text-xs">SV-20260412-0900</p></td>
                                <td class="px-6 py-4"><span class="px-2 py-0.5 bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-bold uppercase rounded-full">Automatic</span></td>
                                <td class="px-6 py-4"><p class="text-slate-400 text-xs">411 MB</p></td>
                                <td class="px-6 py-4"><p class="text-slate-400 text-xs">Yesterday, 09:00 AM</p></td>
                                <td class="px-6 py-4 text-right"><span class="text-slate-600 text-[10px] font-bold uppercase">Pruned</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        async function logout() {
            await fetch('../StegaVault/api/super_admin_auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.php';
        }
    </script>
</body>

</html>
