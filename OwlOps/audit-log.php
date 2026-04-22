<?php
/**
 * StegaVault - Super Admin Audit Log
 * File: OwlOps/audit-log.php
 */

session_start();
require_once '../StegaVault/includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/auth_guard.php';

$user = ['id' => $_SESSION['user_id'], 'name' => $_SESSION['name']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Audit Log - OwlOps</title>
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
                        "background-light": "#ffffff",
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
        h1,h2,h3,h4,h5,h6,.font-display { font-family: 'Space Grotesk', sans-serif; }
        .bg-grid-pattern { background-image: radial-gradient(#cbd5e1 0.5px, transparent 0.5px); background-size: 24px 24px; }
        html.dark .bg-grid-pattern { background-image: radial-gradient(rgba(255,255,255,0.12) 0.5px, transparent 0.5px); }
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
            <nav class="flex flex-col gap-2 flex-1">
                <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-600 mb-2">Systems</p>
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
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 dark:bg-primary/20 text-primary border border-primary/20 dark:border-primary/30" href="audit-log.php">
                    <span class="material-symbols-outlined text-[20px] text-primary">manage_search</span>
                    <p class="text-sm font-medium">Audit Log</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-400 hover:text-primary dark:hover:text-white hover:bg-primary/5 dark:hover:bg-white/5 transition-colors" href="reports.php">
                    <span class="material-symbols-outlined text-[20px]">assessment</span>
                    <p class="text-sm font-medium">System Report</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-400 hover:text-primary dark:hover:text-white hover:bg-primary/5 dark:hover:bg-white/5 transition-colors" href="mfa-settings.php">
                    <span class="material-symbols-outlined text-[20px]">phonelink_lock</span>
                    <p class="text-sm font-medium">MFA Settings</p>
                </a>
            </nav>
            <div class="pt-6 border-t border-slate-200 dark:border-white/5 space-y-1">
                <button onclick="toggleTheme()" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-[20px]" id="themeIcon">dark_mode</span>
                    <p class="text-sm font-medium" id="themeLabel">Dark Mode</p>
                </button>
                <button onclick="logout()" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <p class="text-sm font-medium">Sign Out</p>
                </button>
            </div>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-12 relative overflow-x-hidden">
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
            <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto space-y-8">

            <!-- Header -->
            <header class="flex items-end justify-between">
                <div>
                    <h2 class="text-4xl font-bold text-slate-900 dark:text-white font-display">Audit Log</h2>
                    <p class="text-slate-600 dark:text-slate-400 mt-2">Full record of all super admin actions — logins, backups, admin changes, and MFA events.</p>
                </div>
                <button onclick="loadLogs()" class="flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-sm font-medium transition-colors">
                    <span class="material-symbols-outlined text-base">refresh</span> Refresh
                </button>
            </header>

            <!-- Summary Cards -->
            <div id="summaryCards" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Category tabs -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-1">
                    <button onclick="setCategory('')"    id="cat-all"    class="cat-btn active-cat px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all">All</button>
                    <button onclick="setCategory('auth')"   id="cat-auth"   class="cat-btn px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">Auth</button>
                    <button onclick="setCategory('backup')" id="cat-backup" class="cat-btn px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">Backup</button>
                    <button onclick="setCategory('admin')"  id="cat-admin"  class="cat-btn px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">Admin</button>
                    <button onclick="setCategory('mfa')"    id="cat-mfa"    class="cat-btn px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">MFA</button>
                </div>

                <!-- Search -->
                <div class="relative flex-1 min-w-[220px]">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-600 text-lg">search</span>
                    <input id="searchInput" type="text" placeholder="Search by name, email, action…"
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white placeholder:text-slate-500 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary/50 outline-none transition-all" />
                </div>
            </div>

            <!-- Log Table -->
            <div class="bg-white dark:bg-slate-card border border-slate-200 dark:border-white/10 rounded-2xl overflow-hidden">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
                            <th class="px-5 py-4 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">Timestamp</th>
                            <th class="px-5 py-4 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">Who</th>
                            <th class="px-5 py-4 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">Action</th>
                            <th class="px-5 py-4 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">Category</th>
                            <th class="px-5 py-4 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">Details</th>
                            <th class="px-5 py-4 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest">IP</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody" class="divide-y divide-slate-100 dark:divide-white/5">
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500 text-sm">Loading...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div id="pagination" class="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400"></div>

        </div>
    </main>

    <style>
        .active-cat { background: rgba(37,99,235,0.12); color: #2563eb; }
        html.dark .active-cat { background: rgba(255,255,255,0.12); color: white; }
    </style>

    <script>
        const API = '../StegaVault/api/super_admin_audit.php';
        let currentCategory = '';
        let currentPage     = 1;
        let searchTimer     = null;

        const ACTION_META = {
            // Auth
            login_success:       { label: 'Login',              icon: 'login',            color: 'text-emerald-400' },
            login_mfa_challenged:{ label: 'MFA Required',       icon: 'phonelink_lock',   color: 'text-yellow-400' },
            login_mfa_success:   { label: 'MFA Verified',       icon: 'verified_user',    color: 'text-emerald-400' },
            login_failed:        { label: 'Login Failed',        icon: 'gpp_bad',          color: 'text-red-400' },
            logout:              { label: 'Logout',              icon: 'logout',           color: 'text-slate-400' },
            // Backup
            backup_db_created:   { label: 'DB Backup Created',  icon: 'database',         color: 'text-blue-400' },
            backup_files_created:{ label: 'Files Backup Created',icon: 'folder_zip',      color: 'text-purple-400' },
            backup_db_restored:  { label: 'DB Restored',        icon: 'restart_alt',      color: 'text-orange-400' },
            backup_files_restored:{ label: 'Files Restored',    icon: 'unarchive',        color: 'text-orange-400' },
            backup_deleted:      { label: 'Backup Deleted',     icon: 'delete',           color: 'text-red-400' },
            // Admin
            super_admin_created: { label: 'Super Admin Created',icon: 'person_add',       color: 'text-emerald-400' },
            super_admin_deleted: { label: 'Super Admin Deleted',icon: 'person_remove',    color: 'text-red-400' },
            super_admin_updated: { label: 'Super Admin Updated',icon: 'manage_accounts',  color: 'text-yellow-400' },
            app_admin_created:   { label: 'App Admin Created',  icon: 'person_add',       color: 'text-emerald-400' },
            app_admin_deleted:   { label: 'App Admin Deleted',  icon: 'person_remove',    color: 'text-red-400' },
            app_admin_updated:   { label: 'App Admin Updated',  icon: 'manage_accounts',  color: 'text-yellow-400' },
            // MFA
            mfa_enabled:         { label: 'MFA Enabled',        icon: 'shield_lock',      color: 'text-emerald-400' },
            mfa_disabled:        { label: 'MFA Disabled',       icon: 'no_encryption',    color: 'text-red-400' },
        };

        const CATEGORY_STYLE = {
            auth:   'bg-blue-500/10 text-blue-400 border-blue-500/20',
            backup: 'bg-purple-500/10 text-purple-400 border-purple-500/20',
            admin:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
            mfa:    'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        };

        async function loadSummary() {
            try {
                const res  = await fetch(`${API}?action=summary`);
                const data = await res.json();
                if (!data.success) return;

                const cards = document.getElementById('summaryCards');
                const catMap = { auth: 'Auth', backup: 'Backup', admin: 'Admin', mfa: 'MFA' };
                const catIcon = { auth: 'key', backup: 'backup', admin: 'admin_panel_settings', mfa: 'phonelink_lock' };
                const catColor = { auth: 'text-blue-400 bg-blue-500/10', backup: 'text-purple-400 bg-purple-500/10', admin: 'text-yellow-400 bg-yellow-500/10', mfa: 'text-emerald-400 bg-emerald-500/10' };

                const allCats = ['auth', 'backup', 'admin', 'mfa'];
                const summaryMap = {};
                (data.data.summary || []).forEach(s => summaryMap[s.category] = s);

                cards.innerHTML = allCats.map(cat => {
                    const s = summaryMap[cat] || { total: 0, last_event: null };
                    return `
                    <div onclick="setCategory('${cat}')" class="bg-slate-50 dark:bg-slate-card border border-slate-200 dark:border-white/10 rounded-2xl p-5 cursor-pointer hover:border-slate-300 dark:hover:border-white/20 transition-all space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="p-2 rounded-xl ${catColor[cat]}">
                                <span class="material-symbols-outlined text-xl">${catIcon[cat]}</span>
                            </div>
                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">${catMap[cat]}</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">${s.total}</p>
                            <p class="text-xs text-slate-500 mt-1">${s.last_event ? 'Last: ' + formatDate(s.last_event) : 'No events yet'}</p>
                        </div>
                    </div>`;
                }).join('');
            } catch(e) { console.error(e); }
        }

        function setCategory(cat) {
            currentCategory = cat;
            currentPage = 1;

            document.querySelectorAll('.cat-btn').forEach(btn => {
                btn.classList.remove('active-cat');
                btn.classList.add('text-slate-400');
            });
            const active = document.getElementById('cat-' + (cat || 'all'));
            if (active) { active.classList.add('active-cat'); active.classList.remove('text-slate-400'); }

            loadLogs();
        }

        async function loadLogs() {
            const search = document.getElementById('searchInput').value.trim();
            const params = new URLSearchParams({
                action:   'list',
                category: currentCategory,
                search,
                page:     currentPage,
            });

            try {
                const res  = await fetch(`${API}?${params}`);
                const data = await res.json();
                if (!data.success) { setTableEmpty('Failed to load logs: ' + data.error); return; }

                renderTable(data.data.logs || []);
                renderPagination(data.data);
            } catch (e) {
                setTableEmpty('Connection error: ' + e.message);
            }
        }

        function renderTable(logs) {
            const tbody = document.getElementById('logTableBody');
            if (logs.length === 0) { setTableEmpty('No audit log entries found.'); return; }

            tbody.innerHTML = logs.map(log => {
                const meta    = ACTION_META[log.action] || { label: log.action, icon: 'info', color: 'text-slate-400' };
                const catStyle = CATEGORY_STYLE[log.category] || 'bg-white/5 text-slate-400 border-white/10';
                const details  = formatDetails(log.action, log.details || {});

                return `
                <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                    <td class="px-5 py-3.5 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap font-mono">${formatDate(log.created_at)}</td>
                    <td class="px-5 py-3.5">
                        <p class="text-slate-900 dark:text-white text-xs font-semibold">${escHtml(log.super_admin_name)}</p>
                        <p class="text-slate-500 text-[10px]">${escHtml(log.super_admin_email)}</p>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined ${meta.color} text-base">${meta.icon}</span>
                            <span class="text-slate-900 dark:text-white text-xs font-medium">${meta.label}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-bold uppercase tracking-wider ${catStyle}">
                            ${log.category}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-slate-500 dark:text-slate-400 max-w-xs truncate" title="${escHtml(JSON.stringify(log.details))}">${details}</td>
                    <td class="px-5 py-3.5 text-xs text-slate-500 font-mono">${escHtml(log.ip_address || '—')}</td>
                </tr>`;
            }).join('');
        }

        function formatDetails(action, details) {
            if (!details || Object.keys(details).length === 0) return '—';
            const parts = [];
            if (details.target_name)  parts.push(escHtml(details.target_name));
            if (details.target_email) parts.push(escHtml(details.target_email));
            if (details.backup_id)    parts.push('Backup: ' + escHtml(details.backup_id));
            if (details.tables)       parts.push(details.tables + ' tables');
            if (details.files)        parts.push(details.files + ' files');
            if (details.size)         parts.push(escHtml(details.size));
            if (details.statements)   parts.push(details.statements + ' statements');
            return parts.length ? parts.join(' · ') : Object.entries(details).map(([k,v]) => `${k}: ${escHtml(String(v))}`).join(' · ');
        }

        function renderPagination(data) {
            const el = document.getElementById('pagination');
            if (data.pages <= 1) { el.innerHTML = ''; return; }

            const start = ((data.page - 1) * data.per_page) + 1;
            const end   = Math.min(data.page * data.per_page, data.total);

            el.innerHTML = `
                <span class="text-slate-500 dark:text-slate-400">${start}–${end} of ${data.total} entries</span>
                <div class="flex items-center gap-2">
                    <button onclick="goPage(${data.page - 1})" ${data.page <= 1 ? 'disabled' : ''}
                        class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-sm disabled:opacity-30 hover:bg-slate-200 dark:hover:bg-white/10 transition-colors">
                        <span class="material-symbols-outlined text-base">chevron_left</span>
                    </button>
                    <span class="text-xs font-mono">Page ${data.page} / ${data.pages}</span>
                    <button onclick="goPage(${data.page + 1})" ${data.page >= data.pages ? 'disabled' : ''}
                        class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-sm disabled:opacity-30 hover:bg-slate-200 dark:hover:bg-white/10 transition-colors">
                        <span class="material-symbols-outlined text-base">chevron_right</span>
                    </button>
                </div>`;
        }

        function goPage(page) { currentPage = page; loadLogs(); }

        function setTableEmpty(msg) {
            document.getElementById('logTableBody').innerHTML =
                `<tr><td colspan="6" class="px-5 py-8 text-center text-slate-500 text-sm">${escHtml(msg)}</td></tr>`;
        }

        function formatDate(str) {
            if (!str) return '—';
            return new Date(str).toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
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

            loadSummary();
            loadLogs();

            document.getElementById('searchInput').addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => { currentPage = 1; loadLogs(); }, 400);
            });
        });
    </script>
    <script src="session-timeout.js"></script>
</body>
</html>
