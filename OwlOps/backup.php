<?php
/**
 * StegaVault - System Backup & Restore
 * File: OwlOps/backup.php
 */

session_start();
require_once '../StegaVault/includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}

$user = [
    'id'   => $_SESSION['user_id'],
    'name' => $_SESSION['name'],
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
        body { font-family: 'Inter', sans-serif; background-color: #000000; }
        h1, h2, h3, h4, h5, h6, .font-display { font-family: 'Space Grotesk', sans-serif; }
        .bg-grid-pattern {
            background-image: radial-gradient(#ffffff 0.1px, transparent 0.1px);
            background-size: 30px 30px;
        }
        .glow-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
        .spinner { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .active-filter { background: rgba(255,255,255,0.12); color: white; }
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
                    <p class="text-sm font-medium">Backup &amp; Restore</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="audit-log.php">
                    <span class="material-symbols-outlined text-[20px]">manage_search</span>
                    <p class="text-sm font-medium">Audit Log</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="mfa-settings.php">
                    <span class="material-symbols-outlined text-[20px]">phonelink_lock</span>
                    <p class="text-sm font-medium">MFA Settings</p>
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
                <div id="dockerStatusBadge" class="flex items-center gap-3 px-4 py-2 bg-slate-800/50 border border-white/10 rounded-full">
                    <span class="size-2 rounded-full bg-slate-500"></span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Checking Docker...</span>
                </div>
            </header>

            <!-- Status Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-slate-card border border-white/10 p-6 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-blue-500/10 rounded-xl">
                            <span class="material-symbols-outlined text-blue-400">database</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Supabase DB</span>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs font-medium">Backup Count</p>
                        <h3 id="statBackupCount" class="text-xl font-bold text-white">—</h3>
                    </div>
                    <p class="text-[10px] text-slate-500">Retention: 30 most recent snapshots</p>
                </div>

                <div class="bg-slate-card border border-white/10 p-6 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-purple-500/10 rounded-xl">
                            <span class="material-symbols-outlined text-purple-400">folder_zip</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Docker Volumes</span>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs font-medium">Detected Volumes</p>
                        <h3 id="statDockerVolumes" class="text-xl font-bold text-white">—</h3>
                    </div>
                    <div id="dockerVolumeNames" class="text-[10px] text-slate-500 font-mono truncate">Checking...</div>
                </div>

                <div class="bg-slate-card border border-white/10 p-6 rounded-2xl space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="p-2 bg-orange-500/10 rounded-xl">
                            <span class="material-symbols-outlined text-orange-400">history</span>
                        </div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Last Backup</span>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs font-medium">Most Recent</p>
                        <h3 id="statLastBackup" class="text-xl font-bold text-white">—</h3>
                    </div>
                    <p id="statLastBackupBy" class="text-[10px] text-slate-500">—</p>
                </div>
            </div>

            <!-- Global Error/Success Banner -->
            <div id="globalMsg" class="hidden p-4 rounded-xl text-sm font-medium"></div>

            <!-- Primary Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Database Backup Panel -->
                <div class="bg-white/5 border border-white/10 rounded-3xl p-8 space-y-5 group hover:border-primary/30 transition-all duration-500 overflow-hidden relative">
                    <div class="absolute -right-20 -top-20 size-64 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-colors"></div>
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-blue-500/10 rounded-xl">
                                <span class="material-symbols-outlined text-blue-400">database</span>
                            </div>
                            <h3 class="text-xl font-bold text-white font-display">Database Backup</h3>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">Export all Supabase tables as a SQL file. Safe upsert — won't overwrite unrelated rows.</p>
                        <ul class="space-y-1.5 pb-4 border-b border-white/5 text-xs text-slate-300">
                            <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-400 text-sm">check_circle</span> All public schema tables</li>
                            <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-400 text-sm">check_circle</span> INSERT … ON CONFLICT upsert</li>
                            <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-400 text-sm">check_circle</span> Downloads as .sql file</li>
                        </ul>
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" id="includeDocker" class="rounded bg-white/10 border-white/20 text-primary focus:ring-primary/50" />
                            <span class="text-xs text-slate-400">Include Docker volume backup</span>
                        </label>
                        <button id="runBackupBtn" onclick="runBackup()" class="w-full py-3.5 bg-white hover:bg-slate-200 text-black rounded-2xl font-bold flex items-center justify-center gap-2 shadow-[0_0_30px_rgba(255,255,255,0.1)] transition-all text-sm">
                            <span class="material-symbols-outlined text-xl" id="backupBtnIcon">backup</span>
                            <span id="backupBtnText">Backup Database</span>
                        </button>
                    </div>
                </div>

                <!-- Files Backup Panel -->
                <div class="bg-white/5 border border-white/10 rounded-3xl p-8 space-y-5 group hover:border-purple-500/30 transition-all duration-500 overflow-hidden relative">
                    <div class="absolute -right-20 -top-20 size-64 bg-purple-500/5 rounded-full blur-3xl group-hover:bg-purple-500/10 transition-colors"></div>
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-purple-500/10 rounded-xl">
                                <span class="material-symbols-outlined text-purple-400">folder_zip</span>
                            </div>
                            <h3 class="text-xl font-bold text-white font-display">Files Backup</h3>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">ZIP the entire <span class="font-mono text-slate-300">uploads/</span> folder — all encrypted files, PDFs, images, and watermarked assets.</p>
                        <ul class="space-y-1.5 pb-4 border-b border-white/5 text-xs text-slate-300">
                            <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-400 text-sm">check_circle</span> Encrypted uploads + watermarked</li>
                            <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-400 text-sm">check_circle</span> All file types (PNG, PDF, MP4, XLSX)</li>
                            <li class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-400 text-sm">check_circle</span> Downloads as .zip archive</li>
                        </ul>
                        <div class="pb-4 border-b border-white/5">
                            <p id="uploadsSize" class="text-xs text-slate-500">Calculating folder size...</p>
                        </div>
                        <button id="runFilesBackupBtn" onclick="runFilesBackup()" class="w-full py-3.5 bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 hover:text-white border border-purple-500/30 rounded-2xl font-bold flex items-center justify-center gap-2 transition-all text-sm">
                            <span class="material-symbols-outlined text-xl" id="filesBtnIcon">folder_zip</span>
                            <span id="filesBtnText">Backup Uploads Folder</span>
                        </button>
                    </div>
                </div>

                <!-- Restore Panel -->
                <div class="bg-slate-card border border-white/10 rounded-3xl p-8 space-y-5 group hover:border-red-500/30 transition-all duration-500 overflow-hidden relative">
                    <div class="absolute -right-20 -top-20 size-64 bg-red-500/5 rounded-full blur-3xl group-hover:bg-red-500/10 transition-colors"></div>
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-red-500/10 rounded-xl">
                                <span class="material-symbols-outlined text-red-400">restart_alt</span>
                            </div>
                            <h3 class="text-xl font-bold text-red-400 font-display">Restore</h3>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">Roll back to a previous snapshot. Existing rows are overwritten via upsert. <span class="text-red-400/80">Use with caution.</span></p>
                        <div class="bg-red-500/5 border border-red-500/20 rounded-xl p-3 flex items-start gap-3">
                            <span class="material-symbols-outlined text-red-400 mt-0.5 flex-shrink-0 text-base">warning</span>
                            <p class="text-[10px] text-red-300/60 leading-relaxed uppercase tracking-widest font-bold">Rows matching primary keys will be overwritten. A full wipe requires manual truncation in Supabase first.</p>
                        </div>
                        <div class="pb-4 border-b border-white/5 space-y-1">
                            <p class="text-xs text-slate-500 flex items-center gap-1.5"><span class="material-symbols-outlined text-blue-400 text-sm">database</span> Database (.sql) — replays SQL via upsert</p>
                            <p class="text-xs text-slate-500 flex items-center gap-1.5"><span class="material-symbols-outlined text-purple-400 text-sm">folder_zip</span> Files (.zip) — extracts back to uploads/</p>
                        </div>
                        <button onclick="openRestoreModal()" class="w-full py-3.5 border border-red-500/30 hover:bg-red-500 text-red-400 hover:text-white rounded-2xl font-bold flex items-center justify-center gap-2 transition-all text-sm">
                            <span class="material-symbols-outlined">restart_alt</span> Open Restore Wizard
                        </button>
                    </div>
                </div>
            </div>

            <!-- Backup History -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white font-display">All Backups</h3>
                    <button onclick="loadBackups()" class="text-[10px] text-slate-500 hover:text-white font-bold uppercase tracking-widest flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-sm">refresh</span> Refresh
                    </button>
                </div>
                <!-- Type filter -->
                <div class="flex items-center gap-2">
                    <button onclick="setFilter('all')"      id="filter-all"      class="filter-btn active-filter px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all">All</button>
                    <button onclick="setFilter('database')" id="filter-database" class="filter-btn px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all text-slate-400 hover:text-white bg-white/5 border border-white/10">Database</button>
                    <button onclick="setFilter('files')"    id="filter-files"    class="filter-btn px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all text-slate-400 hover:text-white bg-white/5 border border-white/10">Files</button>
                </div>
                <div class="bg-slate-card border border-white/10 rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white/5 border-b border-white/10">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Backup ID</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Type</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tables / Rows</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Size</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Created</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="backupTableBody" class="divide-y divide-white/5">
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500 text-sm">Loading backups...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- ── Restore Modal ─────────────────────────────── -->
    <div id="restoreModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm px-4">
        <div class="bg-[#111111] border border-red-500/20 rounded-2xl p-8 max-w-lg w-full">
            <h3 class="text-xl font-bold text-red-400 mb-2 font-display">Restore from Backup</h3>
            <p class="text-slate-400 text-sm mb-6">Select a backup to restore. Matching rows will be overwritten via upsert.</p>

            <div id="restoreBackupList" class="space-y-2 max-h-64 overflow-y-auto mb-6">
                <p class="text-slate-500 text-sm text-center py-4">Loading...</p>
            </div>

            <div id="restoreConfirmBox" class="hidden space-y-4">
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 text-sm text-red-300">
                    <strong>Selected:</strong> <span id="restoreSelectedLabel" class="font-mono"></span>
                </div>

                <!-- Full Restore toggle — only shown for database backups -->
                <div id="fullRestoreToggleWrap" class="hidden">
                    <label class="flex items-start gap-3 cursor-pointer p-3 rounded-xl border border-orange-500/20 bg-orange-500/5 hover:bg-orange-500/10 transition-all">
                        <input id="fullRestoreCheckbox" type="checkbox" class="mt-0.5 accent-orange-500 w-4 h-4 flex-shrink-0" onchange="onFullRestoreToggle()" />
                        <div>
                            <p class="text-sm font-semibold text-orange-400">Full Restore (wipe &amp; reload)</p>
                            <p class="text-xs text-slate-400 mt-0.5">Truncates ALL tables first, then replays the backup. Existing data will be permanently deleted. Use for a clean slate.</p>
                        </div>
                    </label>
                </div>

                <div id="fullRestoreWarning" class="hidden bg-orange-500/10 border border-orange-500/40 rounded-xl p-3 text-xs text-orange-300">
                    <strong>Warning:</strong> All current data will be wiped before the backup is loaded. This cannot be undone.
                </div>

                <p class="text-xs text-slate-400">Type <strong class="text-white" id="restoreConfirmWord">RESTORE</strong> to confirm:</p>
                <input id="restoreConfirmInput" type="text" placeholder="RESTORE"
                    class="w-full px-4 py-3 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-600 focus:ring-2 focus:ring-red-500/50 focus:border-red-500 outline-none transition-all" />
            </div>

            <div id="restoreMsg" class="hidden mt-4 p-3 rounded-lg text-sm"></div>

            <div class="flex gap-3 mt-6">
                <button onclick="closeRestoreModal()" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white font-semibold rounded-xl transition-all">Cancel</button>
                <button id="restoreExecBtn" onclick="executeRestore()" class="flex-1 py-3 bg-red-500/20 hover:bg-red-500 text-red-400 hover:text-white font-bold rounded-xl border border-red-500/30 transition-all">
                    Restore
                </button>
            </div>
        </div>
    </div>

    <script>
        const API = '../StegaVault/api/super_admin_backup.php';
        let selectedRestoreFile = null;
        let selectedRestoreType = 'database'; // 'database' | 'files'
        let allBackups = [];
        let currentFilter = 'all';

        // ── Init ──────────────────────────────────────
        document.addEventListener('DOMContentLoaded', () => {
            loadBackups();
            checkDockerStatus();
            checkUploadsSize();
        });

        // ── Uploads Folder Size ───────────────────────
        async function checkUploadsSize() {
            try {
                const res  = await fetch(`${API}?action=uploads_size`);
                const data = await res.json();
                const el   = document.getElementById('uploadsSize');
                if (data.success) {
                    el.textContent = `${data.data.size} across ${data.data.files} files`;
                } else {
                    el.textContent = 'Could not read uploads folder';
                }
            } catch (e) {
                document.getElementById('uploadsSize').textContent = 'Could not read uploads folder';
            }
        }

        // ── Docker Status ─────────────────────────────
        async function checkDockerStatus() {
            try {
                const res  = await fetch(`${API}?action=docker_status`);
                const data = await res.json();
                const badge = document.getElementById('dockerStatusBadge');
                const volEl = document.getElementById('statDockerVolumes');
                const volNames = document.getElementById('dockerVolumeNames');

                if (data.success && data.data.available) {
                    badge.className = 'flex items-center gap-3 px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full';
                    badge.innerHTML = `<span class="size-2 rounded-full bg-emerald-500 glow-pulse"></span><span class="text-[10px] text-emerald-400 font-bold uppercase tracking-widest">Docker: Connected</span>`;
                    volEl.textContent = data.data.total + (data.data.total === 1 ? ' volume' : ' volumes');
                    volNames.textContent = data.data.volumes.join(', ') || 'None found';
                } else {
                    badge.className = 'flex items-center gap-3 px-4 py-2 bg-yellow-500/10 border border-yellow-500/20 rounded-full';
                    badge.innerHTML = `<span class="size-2 rounded-full bg-yellow-500"></span><span class="text-[10px] text-yellow-400 font-bold uppercase tracking-widest">Docker: Unavailable</span>`;
                    volEl.textContent = 'N/A';
                    volNames.textContent = data.data?.reason || 'Docker not accessible';
                    document.getElementById('includeDocker').disabled = true;
                }
            } catch (e) {
                console.error('Docker status check failed', e);
            }
        }

        // ── Load Backup List ──────────────────────────
        async function loadBackups() {
            try {
                const res  = await fetch(`${API}?action=list`);
                const data = await res.json();

                if (!data.success) {
                    setTableEmpty('Failed to load backups: ' + data.error);
                    return;
                }

                allBackups = data.data.backups || [];
                setFilter(currentFilter); // re-apply current filter

                // Update stats
                document.getElementById('statBackupCount').textContent = allBackups.length + ' stored';
                if (allBackups.length > 0) {
                    const latest = allBackups[0];
                    document.getElementById('statLastBackup').textContent = formatDate(latest.created_at);
                    document.getElementById('statLastBackupBy').textContent = 'By ' + (latest.created_by || 'System');
                } else {
                    document.getElementById('statLastBackup').textContent = 'No backups yet';
                    document.getElementById('statLastBackupBy').textContent = '—';
                }
            } catch (e) {
                setTableEmpty('Connection error: ' + e.message);
            }
        }

        function setFilter(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('active-filter');
                b.classList.add('text-slate-400', 'bg-white/5', 'border', 'border-white/10');
            });
            const active = document.getElementById('filter-' + filter);
            if (active) { active.classList.add('active-filter'); active.classList.remove('text-slate-400', 'bg-white/5', 'border', 'border-white/10'); }

            const filtered = filter === 'all' ? allBackups
                : filter === 'files' ? allBackups.filter(b => b.type === 'files')
                : allBackups.filter(b => b.type !== 'files');
            renderBackupTable(filtered);
        }

        function renderBackupTable(backups) {
            const tbody = document.getElementById('backupTableBody');
            if (backups.length === 0) {
                setTableEmpty('No backups found. Run your first backup above.');
                return;
            }

            tbody.innerHTML = backups.map(b => {
                const isFiles = b.type === 'files';
                const typeBadge = isFiles
                    ? 'bg-purple-500/10 border-purple-500/20 text-purple-400'
                    : b.type === 'automatic'
                        ? 'bg-blue-500/10 border-blue-500/20 text-blue-400'
                        : 'bg-white/5 border-white/10 text-slate-400';
                const typeIcon = isFiles ? 'folder_zip' : 'database';
                const detail = isFiles
                    ? `<p class="text-slate-300 text-xs">${(b.files || 0).toLocaleString()} files</p><p class="text-slate-500 text-[10px]">uploads/ folder</p>`
                    : `<p class="text-slate-300 text-xs">${b.tables || '—'} tables</p><p class="text-slate-500 text-[10px]">${(b.rows || 0).toLocaleString()} rows</p>`;
                return `
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-4">
                        <p class="text-white font-mono text-xs">${escHtml(b.id)}</p>
                        <p class="text-slate-600 text-[10px] font-mono mt-0.5">${escHtml(b.filename)}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 ${typeBadge} border text-[10px] font-bold uppercase rounded-full">
                            <span class="material-symbols-outlined text-[11px]">${typeIcon}</span>
                            ${isFiles ? 'files' : b.type}
                        </span>
                    </td>
                    <td class="px-6 py-4">${detail}</td>
                    <td class="px-6 py-4"><p class="text-slate-400 text-xs">${b.size_label || b.size || '—'}</p></td>
                    <td class="px-6 py-4">
                        <p class="text-slate-300 text-xs">${formatDate(b.created_at)}</p>
                        <p class="text-slate-500 text-[10px]">by ${escHtml(b.created_by || 'System')}</p>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="${API}?action=download&file=${encodeURIComponent(b.filename)}" title="Download"
                               class="p-1.5 rounded-lg bg-white/5 hover:bg-white/15 text-slate-400 hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-base">download</span>
                            </a>
                            <button onclick="deleteBackup('${escHtml(b.filename)}', '${escHtml(b.id)}')" title="Delete"
                                class="p-1.5 rounded-lg bg-white/5 hover:bg-red-500/20 text-slate-400 hover:text-red-400 transition-colors">
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        function setTableEmpty(msg) {
            document.getElementById('backupTableBody').innerHTML =
                `<tr><td colspan="6" class="px-6 py-8 text-center text-slate-500 text-sm">${escHtml(msg)}</td></tr>`;
        }

        // ── Run Backup ────────────────────────────────
        async function runBackup() {
            const btn     = document.getElementById('runBackupBtn');
            const icon    = document.getElementById('backupBtnIcon');
            const label   = document.getElementById('backupBtnText');
            const docker  = document.getElementById('includeDocker').checked;

            btn.disabled  = true;
            icon.className = 'material-symbols-outlined spinner';
            label.textContent = 'Creating backup...';
            showGlobalMsg('', '');

            try {
                const res  = await fetch(`${API}?action=create`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ include_docker: docker })
                });
                const data = await res.json();

                if (data.success) {
                    const d = data.data;
                    let msg = `Backup created: ${d.tables} tables, ${(d.rows || 0).toLocaleString()} rows, ${d.size}.`;
                    if (d.docker) {
                        msg += d.docker.success
                            ? ` Docker: ${d.docker.volumes?.length || 0} volume(s) backed up.`
                            : ` Docker: ${d.docker.reason}`;
                    }
                    showGlobalMsg('success', msg);

                    // Auto-download the file
                    window.location.href = `${API}?action=download&file=${encodeURIComponent(d.filename)}`;

                    await loadBackups();
                } else {
                    showGlobalMsg('error', 'Backup failed: ' + data.error);
                }
            } catch (e) {
                showGlobalMsg('error', 'Connection error: ' + e.message);
            } finally {
                btn.disabled = false;
                icon.className = 'material-symbols-outlined';
                label.textContent = 'Run Manual Backup Now';
            }
        }

        // ── Run Files Backup ──────────────────────────
        async function runFilesBackup() {
            const btn   = document.getElementById('runFilesBackupBtn');
            const icon  = document.getElementById('filesBtnIcon');
            const label = document.getElementById('filesBtnText');

            btn.disabled      = true;
            icon.className    = 'material-symbols-outlined spinner';
            label.textContent = 'Creating ZIP...';
            showGlobalMsg('', '');

            try {
                const res  = await fetch(`${API}?action=create_files`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();

                if (data.success) {
                    const d = data.data;
                    showGlobalMsg('success', `Files backup created: ${d.files} files, ${d.size}. Downloading…`);
                    window.location.href = `${API}?action=download&file=${encodeURIComponent(d.filename)}`;
                    await loadBackups();
                } else {
                    showGlobalMsg('error', 'Files backup failed: ' + data.error);
                }
            } catch (e) {
                showGlobalMsg('error', 'Connection error: ' + e.message);
            } finally {
                btn.disabled      = false;
                icon.className    = 'material-symbols-outlined';
                label.textContent = 'Backup Uploads Folder';
            }
        }

        // ── Delete Backup ─────────────────────────────
        async function deleteBackup(filename, id) {
            if (!confirm(`Delete backup ${id}?\n\nThis cannot be undone.`)) return;

            try {
                const res  = await fetch(`${API}?action=delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filename })
                });
                const data = await res.json();

                if (data.success) {
                    showGlobalMsg('success', 'Backup deleted.');
                    await loadBackups();
                } else {
                    showGlobalMsg('error', 'Delete failed: ' + data.error);
                }
            } catch (e) {
                showGlobalMsg('error', 'Connection error: ' + e.message);
            }
        }

        // ── Restore Modal ─────────────────────────────
        function openRestoreModal() {
            selectedRestoreFile = null;
            selectedRestoreType = 'database';
            document.getElementById('restoreConfirmBox').classList.add('hidden');
            document.getElementById('restoreConfirmInput').value = '';
            document.getElementById('restoreMsg').classList.add('hidden');

            const listEl = document.getElementById('restoreBackupList');
            if (allBackups.length === 0) {
                listEl.innerHTML = '<p class="text-slate-500 text-sm text-center py-4">No backups available.</p>';
            } else {
                const dbBackups    = allBackups.filter(b => b.type !== 'files');
                const filesBackups = allBackups.filter(b => b.type === 'files');

                const renderGroup = (label, icon, color, items) => {
                    if (items.length === 0) return '';
                    return `<p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 px-1 mb-1 mt-3">${label}</p>` +
                        items.map(b => {
                            const isFiles = b.type === 'files';
                            const detail  = isFiles
                                ? `${(b.files||0).toLocaleString()} files · ${b.size_label||'—'}`
                                : `${b.tables||'—'} tables · ${b.size_label||'—'}`;
                            return `
                            <div onclick="selectRestoreFile('${escHtml(b.filename)}', '${escHtml(b.id)}', '${isFiles ? 'files' : 'database'}')"
                                 class="restore-item flex items-center justify-between p-3 rounded-xl border border-white/10 hover:border-red-500/40 hover:bg-red-500/5 cursor-pointer transition-all mb-1"
                                 data-file="${escHtml(b.filename)}">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined ${color} text-base">${icon}</span>
                                    <div>
                                        <p class="text-white text-xs font-mono">${escHtml(b.id)}</p>
                                        <p class="text-slate-500 text-[10px]">${formatDate(b.created_at)} · ${detail}</p>
                                    </div>
                                </div>
                                <span class="material-symbols-outlined text-slate-600 text-lg">radio_button_unchecked</span>
                            </div>`;
                        }).join('');
                };

                listEl.innerHTML =
                    renderGroup('Database Backups', 'database', 'text-blue-400', dbBackups) +
                    renderGroup('Files Backups', 'folder_zip', 'text-purple-400', filesBackups);
            }

            document.getElementById('restoreModal').classList.remove('hidden');
        }

        function selectRestoreFile(filename, label, type) {
            selectedRestoreFile = filename;
            selectedRestoreType = type;

            document.querySelectorAll('.restore-item').forEach(el => {
                const isSelected = el.dataset.file === filename;
                el.classList.toggle('border-red-500/50', isSelected);
                el.classList.toggle('bg-red-500/10', isSelected);
                el.querySelector('.material-symbols-outlined:last-child').textContent =
                    isSelected ? 'radio_button_checked' : 'radio_button_unchecked';
            });

            const typeLabel = type === 'files' ? ' (Files)' : ' (Database)';
            document.getElementById('restoreSelectedLabel').textContent = label + typeLabel;

            // Show Full Restore toggle only for database backups
            const toggleWrap = document.getElementById('fullRestoreToggleWrap');
            const checkbox   = document.getElementById('fullRestoreCheckbox');
            if (type === 'database') {
                toggleWrap.classList.remove('hidden');
            } else {
                toggleWrap.classList.add('hidden');
                checkbox.checked = false;
                document.getElementById('fullRestoreWarning').classList.add('hidden');
                document.getElementById('restoreConfirmWord').textContent = 'RESTORE';
                document.getElementById('restoreConfirmInput').placeholder = 'RESTORE';
            }

            document.getElementById('restoreConfirmBox').classList.remove('hidden');
            document.getElementById('restoreConfirmInput').value = '';
            document.getElementById('restoreConfirmInput').focus();
        }

        function onFullRestoreToggle() {
            const checked = document.getElementById('fullRestoreCheckbox').checked;
            document.getElementById('fullRestoreWarning').classList.toggle('hidden', !checked);
            const word = checked ? 'FULL RESTORE' : 'RESTORE';
            document.getElementById('restoreConfirmWord').textContent = word;
            document.getElementById('restoreConfirmInput').placeholder = word;
            document.getElementById('restoreConfirmInput').value = '';
        }

        function closeRestoreModal() {
            document.getElementById('restoreModal').classList.add('hidden');
            // Reset state
            selectedRestoreFile = null;
            selectedRestoreType = 'database';
            document.getElementById('restoreConfirmBox').classList.add('hidden');
            document.getElementById('restoreConfirmInput').value = '';
            document.getElementById('fullRestoreCheckbox').checked = false;
            document.getElementById('fullRestoreToggleWrap').classList.add('hidden');
            document.getElementById('fullRestoreWarning').classList.add('hidden');
            document.getElementById('restoreConfirmWord').textContent = 'RESTORE';
            document.getElementById('restoreConfirmInput').placeholder = 'RESTORE';
            setRestoreMsg('', '');
        }

        async function executeRestore() {
            if (!selectedRestoreFile) {
                setRestoreMsg('error', 'Please select a backup first.');
                return;
            }

            const isFullRestore = selectedRestoreType === 'database'
                && document.getElementById('fullRestoreCheckbox').checked;
            const requiredWord  = isFullRestore ? 'FULL RESTORE' : 'RESTORE';
            const confirmVal    = document.getElementById('restoreConfirmInput').value.trim();

            if (confirmVal !== requiredWord) {
                setRestoreMsg('error', `Type ${requiredWord} (uppercase) to confirm.`);
                return;
            }

            const btn = document.getElementById('restoreExecBtn');
            btn.disabled    = true;
            btn.textContent = 'Restoring...';
            setRestoreMsg('', '');

            let apiAction;
            if (selectedRestoreType === 'files') {
                apiAction = 'restore_files';
            } else if (isFullRestore) {
                apiAction = 'full_restore';
            } else {
                apiAction = 'restore';
            }

            try {
                const res  = await fetch(`${API}?action=${apiAction}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filename: selectedRestoreFile })
                });
                const data = await res.json();

                if (data.success) {
                    let detail;
                    if (selectedRestoreType === 'files') {
                        detail = 'Files restored to uploads/ folder.';
                    } else if (isFullRestore) {
                        detail = `Full restore complete. ${data.data.tables} tables wiped & ${data.data.statements} statements executed.`;
                    } else {
                        detail = `Database restored. ${data.data.statements} statements executed.`;
                    }
                    setRestoreMsg('success', detail);
                    setTimeout(closeRestoreModal, 3500);
                } else {
                    setRestoreMsg('error', 'Restore failed: ' + data.error);
                }
            } catch (e) {
                setRestoreMsg('error', 'Connection error: ' + e.message);
            } finally {
                btn.disabled    = false;
                btn.textContent = 'Restore';
            }
        }

        function setRestoreMsg(type, msg) {
            const el = document.getElementById('restoreMsg');
            if (!msg) { el.classList.add('hidden'); return; }
            el.className = type === 'success'
                ? 'mt-4 p-3 rounded-lg text-sm bg-emerald-500/10 border border-emerald-500/30 text-emerald-400'
                : 'mt-4 p-3 rounded-lg text-sm bg-red-500/10 border border-red-500/30 text-red-400';
            el.textContent = msg;
            el.classList.remove('hidden');
        }

        // ── Helpers ───────────────────────────────────
        function showGlobalMsg(type, msg) {
            const el = document.getElementById('globalMsg');
            if (!msg) { el.classList.add('hidden'); return; }
            el.className = type === 'success'
                ? 'p-4 rounded-xl text-sm font-medium bg-emerald-500/10 border border-emerald-500/30 text-emerald-400'
                : 'p-4 rounded-xl text-sm font-medium bg-red-500/10 border border-red-500/30 text-red-400';
            el.textContent = msg;
            el.classList.remove('hidden');
            if (type === 'success') setTimeout(() => el.classList.add('hidden'), 8000);
        }

        function formatDate(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }

        function escHtml(str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(String(str ?? '')));
            return d.innerHTML;
        }

        async function logout() {
            await fetch('../StegaVault/api/super_admin_auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.php';
        }

        // Close modal on backdrop click
        document.getElementById('restoreModal').addEventListener('click', function (e) {
            if (e.target === this) closeRestoreModal();
        });
    </script>
</body>

</html>
