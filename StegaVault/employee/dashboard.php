<?php

/**
 * StegaVault - Employee Dashboard (Redesigned)
 * File: employee/dashboard.php
 */

session_start();
require_once '../includes/db.php';

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

$userId = $user['id'];

$filesResult    = $db->query("SELECT COUNT(*) as count FROM files WHERE user_id = $userId");
$totalFiles     = $filesResult->fetch_assoc()['count'];

$downloadsResult = $db->query("SELECT COUNT(*) as count FROM watermark_mappings WHERE user_id = $userId");
$totalDownloads  = $downloadsResult->fetch_assoc()['count'];

$storageResult = $db->query("SELECT SUM(file_size) as total FROM files WHERE user_id = $userId");
$storageUsed   = $storageResult->fetch_assoc()['total'] ?? 0;
$storageMB     = round($storageUsed / (1024 * 1024), 2);

$recentFiles = $db->query("
    SELECT * FROM files
    WHERE user_id = $userId
    ORDER BY upload_date DESC
    LIMIT 5
");

// Fetch saved theme color
$colorStmt = $db->prepare("SELECT theme_color FROM users WHERE id = ?");
$colorStmt->bind_param('i', $userId);
$colorStmt->execute();
$colorRow = $colorStmt->get_result()->fetch_assoc();
$themeColor = $colorRow['theme_color'] ?? '#667eea';
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) $themeColor = '#667eea';
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Employee Dashboard - StegaVault</title>
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

        /* ── Accent color CSS variable (overrideable) ─────────── */
        :root {
            --sv-primary: <?php echo $themeColor; ?>;
        }

        .bg-primary {
            background-color: var(--sv-primary) !important;
        }

        .text-primary {
            color: var(--sv-primary) !important;
        }

        .border-primary {
            border-color: var(--sv-primary) !important;
        }

        .hover\:text-primary:hover {
            color: var(--sv-primary) !important;
        }

        .bg-primary\/10 {
            background-color: color-mix(in srgb, var(--sv-primary) 10%, transparent) !important;
        }

        .bg-primary\/20 {
            background-color: color-mix(in srgb, var(--sv-primary) 20%, transparent) !important;
        }

        .text-primary\/10 {
            background-color: color-mix(in srgb, var(--sv-primary) 10%, transparent) !important;
        }

        .ring-primary\/50 {
            --tw-ring-color: color-mix(in srgb, var(--sv-primary) 50%, transparent) !important;
        }

        .focus\:ring-primary\/50:focus {
            --tw-ring-color: color-mix(in srgb, var(--sv-primary) 50%, transparent) !important;
        }

        .focus\:border-primary:focus {
            border-color: var(--sv-primary) !important;
        }

        .hover\:bg-primary\/90:hover {
            background-color: color-mix(in srgb, var(--sv-primary) 90%, #000) !important;
        }

        .from-primary {
            --tw-gradient-from: var(--sv-primary) !important;
        }

        .border-primary\/20 {
            border-color: color-mix(in srgb, var(--sv-primary) 20%, transparent) !important;
        }

        .group-hover\:text-primary:hover .group:hover {
            color: var(--sv-primary) !important;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex">

    <!-- ═══════════════════════════════════════
         FIXED LEFT SIDEBAR
    ═══════════════════════════════════════ -->
    <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10">
                <img src="../PGMN%20LOGOS%20white.png" alt="PGMN Inc. Logo" class="h-12 w-auto object-contain dark:invert-0 invert" />
                <div class="flex flex-col justify-center">
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">PGMN Inc.</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Employee Portal</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex flex-col gap-1 flex-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white">
                    <span class="material-symbols-outlined text-[22px]" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">dashboard</span>
                    <p class="text-sm font-medium">Dashboard</p>
                </a>
                <a href="workspace.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[22px]">folder_open</span>
                    <p class="text-sm font-medium">Workspace</p>
                </a>
                <a href="activity.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[22px]">history</span>
                    <p class="text-sm font-medium">Activity Log</p>
                </a>
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

    <!-- ═══════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════ -->
    <main class="flex-1 ml-64 flex flex-col">

        <!-- Sticky Top Header -->
        <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center gap-6">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight flex-shrink-0">Employee Dashboard</h2>
            <?php include '../includes/search_bar.php'; ?>
            <div class="flex items-center gap-3 flex-shrink-0">
                <div id="projectCountBadge" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-semibold">
                    Loading...
                </div>
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

            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-primary to-purple-600 rounded-xl p-8 text-white shadow-sm">
                <h1 class="text-2xl font-bold mb-1">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                <p class="text-white/75 text-sm">Here's what's happening in your secure workspace today.</p>
            </div>

            <!-- Stats Cards -->
            <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total Files -->
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

                <!-- Storage -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-orange-500/10 rounded-lg">
                            <span class="material-symbols-outlined text-orange-500">hard_drive</span>
                        </div>
                        <span class="text-orange-500 text-sm font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">storage</span> Used
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Storage</p>
                    <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight">
                        <?php echo $storageMB; ?><span class="text-lg text-slate-400 dark:text-slate-500 ml-1 font-medium">MB</span>
                    </h3>
                </div>

                <!-- Downloads -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-blue-500/10 rounded-lg">
                            <span class="material-symbols-outlined text-blue-500">download</span>
                        </div>
                        <span class="text-blue-500 text-sm font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">security</span> Tracked
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Downloads</p>
                    <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight"><?php echo number_format($totalDownloads); ?></h3>
                </div>

                <!-- Projects -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-purple-500/10 rounded-lg">
                            <span class="material-symbols-outlined text-purple-500">folder</span>
                        </div>
                        <span class="text-purple-500 text-sm font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">verified</span> Assigned
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Projects</p>
                    <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight" id="projectCountMain">0</h3>
                </div>
            </section>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

                <!-- Recent Uploads (2/3) -->
                <section class="xl:col-span-2 space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-slate-900 dark:text-white text-xl font-bold">Recent Uploads</h2>
                        <a href="workspace.php" class="text-primary text-sm font-semibold hover:underline">View All</a>
                    </div>

                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <?php if ($recentFiles->num_rows > 0): ?>
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">File</th>
                                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Size</th>
                                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <?php while ($file = $recentFiles->fetch_assoc()):
                                        $isImage  = strpos($file['mime_type'] ?? '', 'image/') === 0;
                                        $isVideo  = strpos($file['mime_type'] ?? '', 'video/') === 0;
                                        $icon     = $isImage ? 'image' : ($isVideo ? 'movie' : 'description');
                                        $iconColor = $isImage ? 'text-purple-500' : ($isVideo ? 'text-red-500' : 'text-blue-500');
                                        $iconBg    = $isImage ? 'bg-purple-500/10' : ($isVideo ? 'bg-red-500/10' : 'bg-blue-500/10');
                                    ?>
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="size-9 rounded-lg <?php echo $iconBg; ?> flex items-center justify-center <?php echo $iconColor; ?> flex-shrink-0">
                                                        <span class="material-symbols-outlined text-[18px]"><?php echo $icon; ?></span>
                                                    </div>
                                                    <p class="text-slate-900 dark:text-white font-semibold text-sm truncate max-w-[180px]"><?php echo htmlspecialchars($file['original_name']); ?></p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                                <?php echo number_format($file['file_size'] / 1024, 1); ?> KB
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                                <?php echo date('M d, Y', strtotime($file['upload_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-1 justify-end opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                                    <a href="preview.php?id=<?php echo $file['id']; ?>&project_id=<?php echo $file['project_id'] ?? 0; ?>" target="_blank"
                                                        class="p-1.5 rounded-lg text-slate-400 hover:text-primary hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="View">
                                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                    </a>
                                                    <a href="../api/download.php?file_id=<?php echo $file['id']; ?>"
                                                        class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Download">
                                                        <span class="material-symbols-outlined text-[18px]">download</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="p-12 text-center">
                                <div class="size-12 mx-auto mb-3 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-slate-400">inbox</span>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">No files uploaded yet</p>
                                <a href="workspace.php" class="mt-3 inline-block text-xs font-semibold text-primary hover:underline">Upload your first file →</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Quick Actions / Active Projects (1/3) -->
                <section class="space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-slate-900 dark:text-white text-xl font-bold">Active Projects</h2>
                    </div>

                    <div class="space-y-3">
                        <!-- Dynamic project list -->
                        <div id="sidebarProjects" class="space-y-3">
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm text-center text-slate-400 dark:text-slate-500 text-sm">
                                Loading projects...
                            </div>
                        </div>

                        <!-- System Info (matching admin dashboard style) -->
                        <div class="bg-gradient-to-br from-primary/10 to-purple-500/10 border border-primary/20 rounded-xl p-5 mt-2">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-primary/20 rounded-lg">
                                    <span class="material-symbols-outlined text-primary">info</span>
                                </div>
                                <div>
                                    <h4 class="text-slate-900 dark:text-white font-bold text-sm">System Information</h4>
                                    <p class="text-slate-600 dark:text-slate-400 text-xs mt-2 leading-relaxed">
                                        All files are watermarked on upload using LSB steganography. Download the protected version to share securely.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

        </div>
    </main>

    <script>
        let _projectUploadPollTimer = null;
        let _projectFileCountById = {};
        let _projectUploadNotifierReady = false;
        let _pendingNewUploads = 0;
        let _newUploadItems = [];
        let _isUploadMenuOpen = false;
        const _uploadSeenKey = 'sv_new_uploads_seen_employee_<?php echo (int)$user['id']; ?>';
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

        function renderProjects(projects) {
            document.getElementById('projectCountMain').textContent = projects.length;
            document.getElementById('projectCountBadge').textContent = `${projects.length} Active Project${projects.length !== 1 ? 's' : ''}`;

            const list = document.getElementById('sidebarProjects');
            if (projects.length === 0) {
                list.innerHTML = `
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm text-center text-slate-400 dark:text-slate-500 text-sm">
                        No projects assigned yet
                    </div>`;
                return;
            }

            list.innerHTML = projects.slice(0, 4).map(p => `
                <a href="workspace.php?project=${p.id}"
                    class="block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg" style="background-color: ${p.color}20;">
                            <span class="material-symbols-outlined text-2xl" style="color: ${p.color}">folder</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-slate-900 dark:text-white font-bold text-sm truncate">${p.name}</h4>
                            <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">${p.file_count} file${p.file_count !== 1 ? 's' : ''} &bull; ${p.member_count} member${p.member_count !== 1 ? 's' : ''}</p>
                        </div>
                        <span class="material-symbols-outlined text-slate-300 dark:text-slate-600 text-[18px]">arrow_forward_ios</span>
                    </div>
                </a>
            `).join('');

            if (projects.length > 4) {
                list.innerHTML += `
                    <a href="workspace.php" class="block text-center text-sm font-semibold text-primary hover:underline py-1">
                        +${projects.length - 4} more projects →
                    </a>`;
            }
        }

        async function fetchMyProjects() {
            const res = await fetch('../api/projects.php?action=my-projects');
            const data = await res.json();
            if (!data.success) return [];
            return data.data.projects || [];
        }

        function detectProjectUploadDeltas(projects) {
            const deltas = [];

            projects.forEach(project => {
                const projectId = String(project.id);
                const currentCount = Number(project.file_count || 0);
                const previousCount = Number(_projectFileCountById[projectId] || 0);

                if (_projectUploadNotifierReady && currentCount > previousCount) {
                    deltas.push({
                        id: projectId,
                        name: project.name || 'Project',
                        count: currentCount - previousCount
                    });
                }

                _projectFileCountById[projectId] = currentCount;
            });

            const activeIds = new Set(projects.map(p => String(p.id)));
            Object.keys(_projectFileCountById).forEach(id => {
                if (!activeIds.has(id)) delete _projectFileCountById[id];
            });

            return deltas;
        }

        function notifyProjectUploadDeltas(deltas) {
            if (!deltas.length) return;
            const totalUploads = deltas.reduce((sum, d) => sum + d.count, 0);
            _pendingNewUploads += totalUploads;
            mergeUploadDeltas(deltas);
            updateNewUploadBadge();
            if (_isUploadMenuOpen) renderNewUploadMenu();
        }

        async function refreshDashboardProjects(showNotifications = false) {
            try {
                consumeSeenMarker();
                const projects = await fetchMyProjects();
                renderProjects(projects);

                const deltas = detectProjectUploadDeltas(projects);
                if (showNotifications) notifyProjectUploadDeltas(deltas);

                _projectUploadNotifierReady = true;
            } catch (err) {
                console.error(err);
            }
        }

        function startProjectUploadPolling() {
            if (_projectUploadPollTimer) clearInterval(_projectUploadPollTimer);
            _projectUploadPollTimer = setInterval(async () => {
                if (document.hidden) return;
                await refreshDashboardProjects(true);
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
        refreshDashboardProjects(false);
        updateNewUploadBadge();
        renderNewUploadMenu();
        startProjectUploadPolling();
    </script>

    <script>
        window.currentUser = {
            name: "<?php echo htmlspecialchars($user['name']); ?>",
            email: "<?php echo htmlspecialchars($user['email']); ?>"
        };
    </script>

    <?php include '../includes/settings_modal.php'; ?>
    <script>
        // Apply authoritative server color after modal JS is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const serverColor = '<?php echo $themeColor; ?>';
            if (window._svColor) {
                window._svColor.apply(serverColor);
                window._svColor.buildSwatches();
                try {
                    const uid = '<?php echo (int)($user["id"] ?? 0); ?>';
                    localStorage.setItem('sv_accent_' + uid, serverColor);
                } catch (e) {}
            }
        });
    </script>
</body>

</html>