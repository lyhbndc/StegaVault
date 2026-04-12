<?php

/**
 * StegaVault - Admin Dashboard (Security Design)
 * File: admin/dashboard.php
 */

session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit;
}

$user = [
    'id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role']
];

// Get statistics
$totalUsers = 0;
$totalFiles = 0;
$totalDownloads = 0;
$securityEvents = 0;

// Count users (admin only)
if ($user['role'] === 'admin') {
    $result = $db->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $result->fetch_assoc()['count'];
}

// Count files
$stmt = $db->prepare("SELECT COUNT(*) as count FROM files WHERE user_id = ?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$totalFiles = $stmt->get_result()->fetch_assoc()['count'];

// Count downloads
$stmt = $db->prepare("SELECT SUM(download_count) as total FROM files WHERE user_id = ?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$totalDownloads = $result['total'] ? $result['total'] : 0;

// Get recent activity logs
$activityLogs = [];
$stmt = $db->prepare("SELECT * FROM (SELECT * FROM activity_log_admin UNION ALL SELECT * FROM activity_log_employee UNION ALL SELECT * FROM activity_log_collaborator) al ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$activityResult = $stmt->get_result();
while ($row = $activityResult->fetch_assoc()) {
    $activityLogs[] = $row;
}

// Get projects (if you have projects table)
$projects = [];
$projectsResult = $db->query("SELECT * FROM projects ORDER BY created_at DESC LIMIT 3");
if ($projectsResult) {
    while ($row = $projectsResult->fetch_assoc()) {
        $projects[] = $row;
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>StegaVault - Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
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
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full">
            <div class="flex items-center gap-3 mb-10">
                <img src="../PGMN%20LOGOS%20white.png" alt="PGMN Inc. Logo" class="h-12 w-auto object-contain dark:invert-0 invert" />
                <div class="flex flex-col justify-center">
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">PGMN Inc.</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Security Suite</p>
                </div>
            </div>

            <nav class="flex flex-col gap-1 flex-1">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white" href="dashboard.php">
                    <span class="material-symbols-outlined text-[22px]">dashboard</span>
                    <p class="text-sm font-medium">Dashboard</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="projects.php">
                    <span class="material-symbols-outlined text-[22px]">folder_managed</span>
                    <p class="text-sm font-medium">Projects</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="analysis.php">
                    <span class="material-symbols-outlined text-[22px]">policy</span>
                    <p class="text-sm font-medium">Forensic Analysis</p>
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="users.php">
                        <span class="material-symbols-outlined text-[22px]">group</span>
                        <p class="text-sm font-medium">User Management</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="activity.php">
                        <span class="material-symbols-outlined text-[22px]">history</span>
                        <p class="text-sm font-medium">Activity Logs</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="reports.php">
                        <span class="material-symbols-outlined text-[22px]">summarize</span>
                        <p class="text-sm font-medium">Reports</p>
                    </a>
                <?php endif; ?>
            </nav>

            <!-- User Profile (click to open settings) -->
            <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                <button onclick="openSettings()" class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group text-left">
                    <div id="sidebarProfileAvatar" class="bg-primary rounded-full size-10 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p id="sidebarProfileName" class="text-slate-900 dark:text-white text-sm font-semibold truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-slate-500 dark:text-slate-400 text-xs capitalize"><?php echo htmlspecialchars($user['role']); ?></p>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 group-hover:text-primary text-[18px] transition-colors">settings</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64 flex flex-col">
        <!-- Top Navigation -->
        <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center gap-6">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight flex-shrink-0">Admin Dashboard</h2>
            <?php include '../includes/search_bar.php'; ?>

            <div class="flex items-center gap-3 flex-shrink-0">
                <div id="newUploadWrap" class="relative">
                    <div id="newUploadBadge" onclick="toggleNewUploadMenu()" title="View new uploads" class="hidden items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/10 text-amber-500 text-xs font-semibold cursor-pointer hover:bg-amber-500/20 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">notifications</span>
                        <span id="newUploadBadgeText">0 New Uploads</span>
                    </div>
                    <div id="newUploadMenu" class="hidden absolute right-0 top-full mt-2 w-80 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl z-[80] overflow-hidden">
                        <div class="px-3 py-2 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">New Uploads</p>
                            <button onclick="clearNewUploadBadge()" class="text-[11px] font-semibold text-primary hover:underline">Mark all as seen</button>
                        </div>
                        <div id="newUploadMenuList" class="max-h-64 overflow-y-auto"></div>
                    </div>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-500 text-xs font-semibold">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    System: Operational
                </div>
            </div>
        </header>



        <div class="p-8 space-y-8">
            <!-- Stats Cards -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <span class="material-symbols-outlined text-primary">description</span>
                        </div>
                        <span class="text-emerald-500 text-sm font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">trending_up</span> Active
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Total Files</p>
                    <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight"><?php echo number_format($totalFiles); ?></h3>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-blue-500/10 rounded-lg">
                            <span class="material-symbols-outlined text-blue-500">download</span>
                        </div>
                        <span class="text-blue-500 text-sm font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">security</span> Tracked
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Total Downloads</p>
                    <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight"><?php echo number_format($totalDownloads); ?></h3>
                </div>

                <?php if ($user['role'] === 'admin'): ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-2 bg-emerald-500/10 rounded-lg">
                                <span class="material-symbols-outlined text-emerald-500">person_check</span>
                            </div>
                            <span class="text-emerald-500 text-sm font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">verified</span> Active
                            </span>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Active Users</p>
                        <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight"><?php echo number_format($totalUsers); ?></h3>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-2 bg-purple-500/10 rounded-lg">
                                <span class="material-symbols-outlined text-purple-500">verified_user</span>
                            </div>
                            <span class="text-purple-500 text-sm font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">shield</span> Protected
                            </span>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Watermarked Files</p>
                        <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight"><?php echo number_format($totalDownloads); ?></h3>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Main Grid Layout -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Recent Activity Logs -->
                <section class="xl:col-span-2 space-y-4">
                    <div class="flex items-center justify-between px-2">
                        <h2 class="text-slate-900 dark:text-white text-xl font-bold">Recent Activity Logs</h2>
                        <a href="activity.php" class="text-primary text-sm font-semibold hover:underline">View All Logs</a>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Timestamp</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php if (count($activityLogs) > 0): ?>
                                    <?php foreach ($activityLogs as $log): ?>
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                                <?php echo date('M d, H:i', strtotime($log['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary border border-primary/20">
                                                    <?php echo htmlspecialchars($log['action']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                                <?php echo htmlspecialchars(substr($log['description'] ?? '', 0, 60)); ?>...
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                            No activity logs yet
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Quick Actions -->
                <section class="space-y-4">
                    <div class="flex items-center justify-between px-2">
                        <h2 class="text-slate-900 dark:text-white text-xl font-bold">Quick Actions</h2>
                    </div>

                    <div class="space-y-3">
                        <a href="upload.php" class="block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-primary/10 rounded-lg">
                                    <span class="material-symbols-outlined text-primary text-2xl">upload_file</span>
                                </div>
                                <div>
                                    <h4 class="text-slate-900 dark:text-white font-bold text-base">Upload Files</h4>
                                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Add files to secure vault</p>
                                </div>
                            </div>
                        </a>

                        <a href="analysis.php" class="block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-red-500/10 rounded-lg">
                                    <span class="material-symbols-outlined text-red-500 text-2xl">policy</span>
                                </div>
                                <div>
                                    <h4 class="text-slate-900 dark:text-white font-bold text-base">Forensic Analysis</h4>
                                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Verify integrity & trace leaks</p>
                                </div>
                            </div>
                        </a>

                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="users.php" class="block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-4">
                                    <div class="p-3 bg-emerald-500/10 rounded-lg">
                                        <span class="material-symbols-outlined text-emerald-500 text-2xl">group</span>
                                    </div>
                                    <div>
                                        <h4 class="text-slate-900 dark:text-white font-bold text-base">Manage Users</h4>
                                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Add or edit team members</p>
                                    </div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <!-- System Status -->
                        <div class="bg-gradient-to-br from-primary/10 to-purple-500/10 border border-primary/20 rounded-xl p-5 mt-6">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-primary/20 rounded-lg">
                                    <span class="material-symbols-outlined text-primary">info</span>
                                </div>
                                <div>
                                    <h4 class="text-slate-900 dark:text-white font-bold text-sm">System Information</h4>
                                    <p class="text-slate-600 dark:text-slate-400 text-xs mt-2 leading-relaxed">
                                        All systems operational. Watermarking using LSB steganography with PNG format for maximum data integrity.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
    <!-- Security Shield -->
    <script>
        let _adminProjectUploadPollTimer = null;
        let _adminProjectFileCountById = {};
        let _adminProjectUploadNotifierReady = false;
        let _pendingNewUploads = 0;
        let _newUploadItems = [];
        let _isUploadMenuOpen = false;
        const _uploadSeenKey = 'sv_new_uploads_seen_admin_<?php echo (int)$user['id']; ?>';
        let _lastSeenToken = '';

        function updateNewUploadBadge() {
            const badge = document.getElementById('newUploadBadge');
            const text = document.getElementById('newUploadBadgeText');
            if (!badge || !text) return;

            if (_pendingNewUploads > 0) {
                badge.classList.remove('hidden');
                badge.classList.add('flex');
                text.textContent = `${_pendingNewUploads} New Upload${_pendingNewUploads !== 1 ? 's' : ''}`;
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('flex');
                closeNewUploadMenu();
            }
        }

        function renderNewUploadMenu() {
            const list = document.getElementById('newUploadMenuList');
            if (!list) return;

            if (!_newUploadItems.length) {
                list.innerHTML = '<p class="px-3 py-3 text-xs text-slate-500 dark:text-slate-400">No new uploads.</p>';
                return;
            }

            list.innerHTML = _newUploadItems
                .sort((a, b) => b.updatedAt - a.updatedAt)
                .map(item => `
                    <div class="px-3 py-2 border-b last:border-b-0 border-slate-100 dark:border-slate-800">
                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">${item.name}</p>
                        <p class="text-[11px] text-amber-600 dark:text-amber-400">+${item.count} new upload${item.count !== 1 ? 's' : ''}</p>
                    </div>
                `).join('');
        }

        function toggleNewUploadMenu() {
            const menu = document.getElementById('newUploadMenu');
            if (!menu || _pendingNewUploads <= 0) return;
            _isUploadMenuOpen = !_isUploadMenuOpen;
            menu.classList.toggle('hidden', !_isUploadMenuOpen);
            if (_isUploadMenuOpen) renderNewUploadMenu();
        }

        function closeNewUploadMenu() {
            const menu = document.getElementById('newUploadMenu');
            _isUploadMenuOpen = false;
            if (menu) menu.classList.add('hidden');
        }

        function clearNewUploadBadge(syncSeenState = true) {
            _pendingNewUploads = 0;
            _newUploadItems = [];
            updateNewUploadBadge();
            renderNewUploadMenu();
            closeNewUploadMenu();

            if (syncSeenState) {
                try {
                    const token = String(Date.now());
                    localStorage.setItem(_uploadSeenKey, token);
                    _lastSeenToken = token;
                } catch (e) {}
            }
        }

        function consumeSeenMarker() {
            try {
                const marker = localStorage.getItem(_uploadSeenKey) || '';
                if (marker && marker !== _lastSeenToken) {
                    _lastSeenToken = marker;
                    clearNewUploadBadge(false);
                }
            } catch (e) {}
        }

        function mergeUploadDeltas(deltas) {
            deltas.forEach(delta => {
                const existing = _newUploadItems.find(item => item.id === delta.id);
                if (existing) {
                    existing.count += delta.count;
                    existing.updatedAt = Date.now();
                } else {
                    _newUploadItems.push({
                        id: delta.id,
                        name: delta.name,
                        count: delta.count,
                        updatedAt: Date.now()
                    });
                }
            });
        }

        async function fetchAdminDashboardProjects() {
            const res = await fetch('../api/projects.php?action=dashboard-projects');
            const data = await res.json();
            if (!data.success) return [];
            return data.data.projects || [];
        }

        function detectAdminProjectUploadDeltas(projects) {
            const deltas = [];

            projects.forEach(project => {
                const projectId = String(project.id);
                const currentCount = Number(project.file_count || 0);
                const previousCount = Number(_adminProjectFileCountById[projectId] || 0);

                if (_adminProjectUploadNotifierReady && currentCount > previousCount) {
                    deltas.push({
                        id: projectId,
                        name: project.name || 'Project',
                        count: currentCount - previousCount
                    });
                }

                _adminProjectFileCountById[projectId] = currentCount;
            });

            const activeIds = new Set(projects.map(p => String(p.id)));
            Object.keys(_adminProjectFileCountById).forEach(id => {
                if (!activeIds.has(id)) delete _adminProjectFileCountById[id];
            });

            return deltas;
        }

        function notifyAdminProjectUploadDeltas(deltas) {
            if (!deltas.length) return;
            const totalUploads = deltas.reduce((sum, d) => sum + d.count, 0);
            _pendingNewUploads += totalUploads;
            mergeUploadDeltas(deltas);
            updateNewUploadBadge();
            if (_isUploadMenuOpen) renderNewUploadMenu();
        }

        async function refreshAdminProjectUploadNotifications(showNotifications = false) {
            try {
                consumeSeenMarker();
                const projects = await fetchAdminDashboardProjects();
                const deltas = detectAdminProjectUploadDeltas(projects);
                if (showNotifications) notifyAdminProjectUploadDeltas(deltas);
                _adminProjectUploadNotifierReady = true;
            } catch (err) {
                console.error(err);
            }
        }

        function startAdminProjectUploadPolling() {
            if (_adminProjectUploadPollTimer) clearInterval(_adminProjectUploadPollTimer);
            _adminProjectUploadPollTimer = setInterval(async () => {
                if (document.hidden) return;
                await refreshAdminProjectUploadNotifications(true);
            }, 15000);
        }

        window.addEventListener('storage', (event) => {
            if (event.key === _uploadSeenKey) consumeSeenMarker();
        });

        document.addEventListener('click', (event) => {
            const wrap = document.getElementById('newUploadWrap');
            if (!wrap) return;
            if (!wrap.contains(event.target)) closeNewUploadMenu();
        });

        consumeSeenMarker();
        refreshAdminProjectUploadNotifications(false);
        updateNewUploadBadge();
        renderNewUploadMenu();
        startAdminProjectUploadPolling();
    </script>
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