<?php

/**
 * StegaVault - Collaborator Workspace (Redesigned)
 * File: collaborator/workspace.php
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

$selectedProjectId = isset($_GET['project']) ? (int) $_GET['project'] : null;
$userId = $user['id'];
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Workspace - StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
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
    <?php include '../includes/theme_color.php'; ?>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex">

    <!-- ═══════════════════════════════════════
         FIXED LEFT SIDEBAR
    ═══════════════════════════════════════ -->
    <aside
        class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10">
                <img src="../PGMN%20LOGOS%20white.png" alt="PGMN Inc. Logo" class="h-12 w-auto object-contain dark:invert-0 invert" />
                <div class="flex flex-col justify-center">
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">PGMN Inc.</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Collaborator Portal</p>
                </div>
            </div>

            <!-- Static Nav -->
            <nav class="flex flex-col gap-1 mb-6">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="dashboard.php">
                    <span class="material-symbols-outlined text-[22px]">dashboard</span>
                    <p class="text-sm font-medium">Dashboard</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white" href="workspace.php">
                    <span class="material-symbols-outlined text-[22px]"
                        style="font-variation-settings: 'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">folder_open</span>
                    <p class="text-sm font-medium">Workspace</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="activity.php">
                    <span class="material-symbols-outlined text-[22px]">history</span>
                    <p class="text-sm font-medium">Activity Log</p>
                </a>
            </nav>

            <!-- My Projects (dynamic list) -->
            <div class="flex-1 min-h-0 flex flex-col">
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest px-3 mb-2">
                    My Projects</p>
                <div id="projectsList" class="flex flex-col gap-1 overflow-y-auto flex-1 pr-1">
                    <p class="text-xs text-slate-400 dark:text-slate-500 px-3 py-2">Loading...</p>
                </div>
            </div>

            <!-- User Profile (click to open settings) -->
            <div class="pt-6 border-t border-slate-200 dark:border-slate-800 mt-4">
                <button onclick="openSettings()"
                    class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group text-left">
                    <div id="sidebarProfileAvatar"
                        class="bg-primary rounded-full size-10 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p id="sidebarProfileName"
                            class="text-slate-900 dark:text-white text-sm font-semibold truncate">
                            <?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-slate-500 dark:text-slate-400 text-xs capitalize">
                            <?php echo htmlspecialchars($user['role']); ?></p>
                    </div>
                    <span
                        class="material-symbols-outlined text-slate-400 group-hover:text-primary text-[18px] transition-colors">settings</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- ═══════════════════════════════════════
         MAIN CONTENT AREA
    ═══════════════════════════════════════ -->
    <div class="flex-1 ml-64 flex flex-col min-h-screen">

        <!-- Sticky Top Header -->
        <header
            class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center gap-6">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight flex-shrink-0">Project Workspace
            </h2>
            <?php include '../includes/search_bar.php'; ?>
            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-500 text-xs font-semibold flex-shrink-0">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                System: Operational
            </div>
        </header>

        <!-- Main scrollable content -->
        <main class="flex-1 overflow-y-auto p-8">
            <div id="mainContent" class="max-w-5xl mx-auto">
                <!-- Empty state -->
                <div class="flex flex-col items-center justify-center h-[60vh] text-center">
                    <div
                        class="size-16 mx-auto mb-4 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
                        <span
                            class="material-symbols-outlined text-3xl text-slate-400 dark:text-slate-500">folder_open</span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 font-medium">Select a project to view contents</p>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">Choose a project from the sidebar</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const currentUserId = <?php echo $user['id']; ?>;
        let myProjects = [];
        let selectedProjectId = <?php echo $selectedProjectId ? $selectedProjectId : 'null'; ?>;

        loadMyProjects();

        async function loadMyProjects() {
            try {
                const response = await fetch('../api/projects.php?action=my-projects');
                const data = await response.json();

                if (data.success) {
                    myProjects = data.data.projects;
                    renderSidebar();

                    if (selectedProjectId) {
                        loadProjectContent(selectedProjectId);
                    }
                }
            } catch (error) {
                console.error(error);
            }
        }

        function renderSidebar() {
            const list = document.getElementById('projectsList');
            if (myProjects.length === 0) {
                list.innerHTML = `<p class="text-xs text-slate-400 dark:text-slate-500 px-3 py-2">No projects assigned</p>`;
                return;
            }

            list.innerHTML = myProjects.map(p => `
                <button onclick="selectProject(${p.id})"
                    class="w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors ${p.id == selectedProjectId
                    ? 'bg-primary/10 text-primary border border-primary/20'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-transparent'}">
                    <span class="size-2 rounded-full flex-shrink-0" style="background-color: ${p.color}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">${p.name}</div>
                        <div class="text-[10px] text-slate-400 dark:text-slate-500">${p.file_count} file${p.file_count !== 1 ? 's' : ''}</div>
                    </div>
                </button>
            `).join('');
        }

        function selectProject(id) {
            selectedProjectId = id;
            window.history.pushState({}, '', `?project=${id}`);
            renderSidebar();
            loadProjectContent(id);
        }

        // ─── Unified File Browser State ───────────────────────────────
        let _currentFolderId = null;
        let _folderTrail = []; // [{id, name}, ...]
        let _paneFolders = [];
        let _paneFiles = [];
        let _fileFilter = 'all';
        let _fileSort = 'date-desc';
        let _fileView = 'list';
        let _uploadPollTimer = null;
        const _uploadNotifyState = {
            key: '',
            knownIds: new Set(),
            initialized: false
        };

        // Add format helpers before loadProjectContent
        function avatarColor(name) {
            let h = 0;
            for (let i = 0; i < (name || '').length; i++) h = name.charCodeAt(i) + ((h << 5) - h);
            return 'hsl(' + (Math.abs(h) % 360) + ',55%,48%)';
        }

        function avatarInitials(name) {
            return (name || '?').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
        }

        function fmtDate(d) {
            if (!d) return '';
            const dt = new Date(d);
            return dt.toLocaleDateString('en-US', {
                    month: '2-digit',
                    day: '2-digit',
                    year: 'numeric'
                }) +
                ' at ' + dt.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
        }

        function fmtSize(bytes) {
            if (!bytes) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0,
                v = bytes;
            while (v >= 1024 && i < units.length - 1) {
                v /= 1024;
                i++;
            }
            return v.toFixed(1) + ' ' + units[i];
        }

        const _IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];
        const _VIDEO_TYPES = ['video/mp4'];
        const _DOC_TYPES = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel'
        ];

        function isImageType(mt) {
            return _IMAGE_TYPES.includes(mt);
        }

        function isVideoType(mt) {
            return _VIDEO_TYPES.includes(mt);
        }

        function isDocType(mt) {
            return _DOC_TYPES.includes(mt);
        }

        function paneScopeKey() {
            return `${selectedProjectId || 0}:${_currentFolderId === null ? 'root' : _currentFolderId}`;
        }

        function showWorkspaceUploadToast(message) {
            let host = document.getElementById('workspaceUploadToastHost');
            if (!host) {
                host = document.createElement('div');
                host.id = 'workspaceUploadToastHost';
                host.className = 'fixed top-4 right-4 z-[250] flex flex-col gap-2 pointer-events-none';
                document.body.appendChild(host);
            }

            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto min-w-[260px] max-w-[360px] rounded-xl border border-emerald-500/30 bg-slate-900/95 text-emerald-300 px-4 py-3 shadow-xl backdrop-blur-sm';
            toast.innerHTML =
                '<div class="flex items-start gap-2">' +
                '<span class="material-symbols-outlined text-[18px] text-emerald-400">upload_file</span>' +
                '<p class="text-xs font-semibold leading-5">' + escapeHtml(message) + '</p>' +
                '</div>';

            host.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-4px)';
                toast.style.transition = 'all .2s ease';
                setTimeout(() => toast.remove(), 220);
            }, 3400);
        }

        function detectAndNotifyNewUploads(files) {
            if (!selectedProjectId) return false;

            const key = paneScopeKey();
            const currentIds = new Set((files || []).map(f => String(f.id)));

            console.log('[Upload Notify]', {
                key,
                currentIds: Array.from(currentIds),
                knownIds: _uploadNotifyState.knownIds ? Array.from(_uploadNotifyState.knownIds) : null,
                initialized: _uploadNotifyState.initialized
            });

            if (_uploadNotifyState.key !== key || !_uploadNotifyState.initialized) {
                console.log('[Upload Notify] Initializing for scope:', key);
                _uploadNotifyState.key = key;
                _uploadNotifyState.knownIds = currentIds;
                _uploadNotifyState.initialized = true;
                return false;
            }

            const newFiles = (files || []).filter(f => !_uploadNotifyState.knownIds.has(String(f.id)));
            _uploadNotifyState.knownIds = currentIds;

            console.log('[Upload Notify] New files detected:', newFiles.length);

            if (newFiles.length > 0) {
                const first = newFiles[0];
                if (newFiles.length === 1) {
                    const who = first.user_id == currentUserId ? 'you' : (first.uploader_name || 'a teammate');
                    console.log('[Upload Notify] Showing toast for single file:', first.original_name);
                    showWorkspaceUploadToast(`New upload: ${first.original_name} by ${who}`);
                } else {
                    console.log('[Upload Notify] Showing toast for multiple files:', newFiles.length);
                    showWorkspaceUploadToast(`${newFiles.length} new files uploaded in this workspace`);
                }
                return true;
            }

            return false;
        }

        async function fetchPaneFilesSnapshot(folderId) {
            if (!selectedProjectId) return [];

            if (folderId === null) {
                const res = await fetch('../api/projects.php?action=files&project_id=' + selectedProjectId);
                const data = await res.json();
                return data.success ? (data.data.files || []) : [];
            }

            const res = await fetch('../api/projects.php?action=get-folder-files&folder_id=' + folderId + '&project_id=' + selectedProjectId);
            const data = await res.json();
            return data.success ? (data.data.files || []) : [];
        }

        function startWorkspaceUploadPolling() {
            if (_uploadPollTimer) clearInterval(_uploadPollTimer);
            _uploadPollTimer = setInterval(async () => {
                console.log('[Upload Polling] Checking...', {
                    selectedProjectId,
                    folderId: _currentFolderId,
                    hidden: document.hidden
                });

                if (!selectedProjectId || document.hidden) {
                    console.log('[Upload Polling] Skipped - no project or hidden');
                    return;
                }

                const uploadModal = document.getElementById('folderUploadModal');
                if (uploadModal && !uploadModal.classList.contains('hidden')) {
                    console.log('[Upload Polling] Skipped - upload modal open');
                    return;
                }

                try {
                    const latestFiles = await fetchPaneFilesSnapshot(_currentFolderId);
                    console.log('[Upload Polling] Fetched files:', latestFiles.length);
                    const hasNew = detectAndNotifyNewUploads(latestFiles);
                    if (hasNew) {
                        console.log('[Upload Polling] New files detected, reloading...');
                        await loadPane(_currentFolderId);
                    }
                } catch (e) {
                    console.error('[Upload Polling] Error:', e);
                }
            }, 15000);
            console.log('[Upload Polling] Started with 15s interval');
        }


        async function loadProjectContent(id) {
            const project = myProjects.find(p => p.id == id);
            if (!project) return;

            // Reset pane state on project change
            _currentFolderId = null;
            _folderTrail = [];
            _fileFilter = 'all';
            _uploadNotifyState.initialized = false;

            const content = document.getElementById('mainContent');
            content.innerHTML = '<div class="text-center py-16 text-slate-400 dark:text-slate-500 text-sm">Loading project...</div>';

            try {
                const membersRes = await fetch('../api/projects.php?action=members&project_id=' + id);
                const membersData = await membersRes.json();
                const members = membersData.data?.members || [];

                // Draw skeleton
                displayProjectContent(project, members);

                // Then load the file pane
                await loadPane(null);

            } catch (error) {
                console.error(error);
                content.innerHTML = '<div class="text-center text-red-500 text-sm py-8">Error loading project data</div>';
            }
        }

        function displayProjectContent(project, members) {
            const content = document.getElementById('mainContent');

            let html = '';
            html += '<!-- Project Header -->\n';
            html += '<div class="flex items-start justify-between mb-6">\n';
            html += '    <div>\n';
            html += '        <div class="flex items-center gap-2 mb-1">\n';
            html += '            <span class="size-3 rounded-full" style="background-color: ' + project.color + '"></span>\n';
            html += '            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wider">Active Project</span>\n';
            html += '        </div>\n';
            html += '        <h1 class="text-slate-900 dark:text-white text-2xl font-bold">' + project.name + '</h1>\n';
            html += '        <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">' + (project.description || 'No description') + '</p>\n';
            html += '    </div>\n';
            html += '    <div class="flex items-center gap-2">\n';
            html += '        <button onclick="openCreateFolderModal(' + project.id + ')" class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-lg transition-all shadow-sm border border-slate-200 dark:border-slate-700">\n';
            html += '            <span class="material-symbols-outlined text-[18px]">create_new_folder</span>New Folder\n';
            html += '        </button>\n';
            html += '        <button onclick="openFolderUploadModal()" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-all shadow-sm">\n';
            html += '            <span class="material-symbols-outlined text-[18px]">cloud_upload</span>Upload Files\n';
            html += '        </button>\n';
            html += '    </div>\n';
            html += '</div>\n';

            html += '<!-- Main Grid -->\n';
            html += '<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">\n';
            html += '    <!-- Files + Folders (2/3 width) -->\n';
            html += '    <div class="xl:col-span-2 space-y-4">\n';
            html += '        <!-- Unified File Browser Pane -->\n';
            html += '        <div class="flex items-center justify-between px-1 mb-2">\n';
            html += '            <div class="flex items-center gap-1.5 flex-wrap min-w-0">\n';
            html += '                <h2 class="text-slate-900 dark:text-white text-base font-bold flex-shrink-0">Files & Folders</h2>\n';
            html += '                <div id="paneBreadcrumb" class="hidden items-center gap-1 text-xs text-slate-400 flex-wrap"></div>\n';
            html += '            </div>\n';
            html += '        </div>\n';

            html += '        <!-- Toolbar -->\n';
            html += '        <div class="flex items-center gap-2 mb-3 flex-wrap">\n';
            html += '            <!-- Filter chips -->\n';
            html += '            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">\n';
            html += '                <button id="fChip-all" onclick="setFileFilter(\'all\')" class="px-3 py-1 rounded-md text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm transition-all">All</button>\n';
            html += '                <button id="fChip-image" onclick="setFileFilter(\'image\')" class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Images</button>\n';
            html += '                <button id="fChip-video" onclick="setFileFilter(\'video\')" class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Videos</button>\n';
            html += '                <button id="fChip-doc" onclick="setFileFilter(\'doc\')" class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Docs</button>\n';
            html += '            </div>\n';
            html += '            <!-- Sort dropdown -->\n';
            html += '            <div class="relative">\n';
            html += '                <select id="fileSortSelect" onchange="setFileSort(this.value)" class="appearance-none pl-3 pr-8 py-1.5 text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 cursor-pointer">\n';
            html += '                    <option value="date-desc">Newest first</option>\n';
            html += '                    <option value="date-asc">Oldest first</option>\n';
            html += '                    <option value="name-asc">Name A&rarr;Z</option>\n';
            html += '                    <option value="name-desc">Name Z&rarr;A</option>\n';
            html += '                    <option value="size-desc">Largest first</option>\n';
            html += '                    <option value="size-asc">Smallest first</option>\n';
            html += '                </select>\n';
            html += '                <span class="material-symbols-outlined text-[14px] text-slate-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">unfold_more</span>\n';
            html += '            </div>\n';
            html += '            <!-- View toggles -->\n';
            html += '            <div class="ml-auto flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">\n';
            html += '                <button id="vBtn-list" onclick="setFileView(\'list\')" title="List view" class="p-1.5 rounded-md bg-white dark:bg-slate-700 text-slate-700 dark:text-white shadow-sm transition-all">\n';
            html += '                    <span class="material-symbols-outlined text-[16px]">view_list</span>\n';
            html += '                </button>\n';
            html += '                <button id="vBtn-grid" onclick="setFileView(\'grid\')" title="Grid view" class="p-1.5 rounded-md text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition-all">\n';
            html += '                    <span class="material-symbols-outlined text-[16px]">grid_view</span>\n';
            html += '                </button>\n';
            html += '            </div>\n';
            html += '        </div>\n';

            html += '        <!-- Drop Zone / Main Area -->\n';
            html += '        <div id="filesPaneWrapper" class="relative">\n';
            html += '            <div id="filesPane" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm transition-all">\n';
            html += '                <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">Loading workspace...</div>\n';
            html += '            </div>\n';
            html += '            <!-- Drop overlay mapping to folder drop upload -->\n';
            html += '            <div id="dropOverlay" class="hidden absolute inset-0 z-20 rounded-xl border-2 border-dashed border-primary bg-primary/10 backdrop-blur-sm flex flex-col items-center justify-center gap-3 pointer-events-none">\n';
            html += '                <div class="size-16 rounded-2xl bg-primary/20 flex items-center justify-center">\n';
            html += '                    <span class="material-symbols-outlined text-4xl text-primary" style="font-variation-settings:\'FILL\' 1">cloud_upload</span>\n';
            html += '                </div>\n';
            html += '                <p class="text-primary font-bold text-base">Drop files to upload</p>\n';
            html += '                <p class="text-primary/70 text-xs">Images, Videos, PDFs, Docs</p>\n';
            html += '            </div>\n';
            html += '            <div id="paneFooter" class="hidden mt-0 px-4 py-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-400 dark:text-slate-500 font-medium"></div>\n';
            html += '        </div>\n';
            html += '    </div>\n';

            html += '    <!-- Team Members (1/3 width) -->\n';
            html += '    <div class="space-y-4">\n';
            html += '        <div class="flex items-center justify-between px-1">\n';
            html += '            <h2 class="text-slate-900 dark:text-white text-base font-bold">Team Members</h2>\n';
            html += '        </div>\n';
            html += '        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">\n';

            if (members.length > 0) {
                html += '            <div class="divide-y divide-slate-100 dark:divide-slate-800">\n';
                members.forEach(m => {
                    html += '                <div class="px-5 py-3.5 flex items-center gap-3">\n';
                    html += '                    <div class="size-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">\n';
                    html += '                        ' + m.name.substring(0, 2).toUpperCase() + '\n';
                    html += '                    </div>\n';
                    html += '                    <div class="flex-1 min-w-0">\n';
                    html += '                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">' + m.name + '</p>\n';
                    html += '                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-primary/10 text-primary border border-primary/20 uppercase">' + m.project_role + '</span>\n';
                    html += '                    </div>\n';
                    html += '                </div>\n';
                });
                html += '            </div>\n';
            } else {
                html += '            <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">No team members</div>\n';
            }

            html += '        </div>\n';

            html += '        <!-- System Info Card (matching dashboard Quick Actions style) -->\n';
            html += '        <div class="bg-gradient-to-br from-primary/10 to-purple-500/10 border border-primary/20 rounded-xl p-5">\n';
            html += '            <div class="flex items-start gap-3">\n';
            html += '                <div class="p-2 bg-primary/20 rounded-lg">\n';
            html += '                    <span class="material-symbols-outlined text-primary text-[18px]">info</span>\n';
            html += '                </div>\n';
            html += '                <div>\n';
            html += '                    <h4 class="text-slate-900 dark:text-white font-bold text-sm">About This Project</h4>\n';
            html += '                    <p class="text-slate-600 dark:text-slate-400 text-xs mt-1.5 leading-relaxed">\n';
            html += '                        Files uploaded here are watermarked and tracked. Download the protected version to share securely.\n';
            html += '                    </p>\n';
            html += '                </div>\n';
            html += '            </div>\n';
            html += '        </div>\n';
            html += '    </div>\n';
            html += '</div>\n';

            content.innerHTML = html;

            // Global drag-and-drop removed per user request
        }

        async function folderDuplicate() {
            closeFolderMenu();
            try {
                const fd = new FormData();
                fd.append('action', 'duplicate-folder');
                fd.append('folder_id', _menuFolderId);
                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    loadPane(_currentFolderId);
                } else {
                    alert(data.error || 'Failed to duplicate');
                }
            } catch {
                alert('Network error');
            }
        }

        async function folderDelete() {
            closeFolderMenu();
            if (!confirm("Delete folder \"" + _menuFolderName + "\" and all its contents? This cannot be undone.")) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete-folder');
                fd.append('folder_id', _menuFolderId);
                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    loadPane(_currentFolderId);
                } else {
                    alert(data.error || 'Failed to delete');
                }
            } catch {
                alert('Network error');
            }
        }

        // ─── File context menu ─────────────────────────────────
        let _fileMenuId = null;
        let _fileMenuName = null;

        function openFileMenu(event, fileId, fileName, isMine) {
            event.preventDefault();
            event.stopPropagation();

            const menu = document.getElementById('fileContextMenu');

            // If clicking the same item that's already open, toggle it off
            if (_fileMenuId === fileId && !menu.classList.contains('hidden') && event.type !== 'contextmenu') {
                closeFileMenu();
                return;
            }

            _fileMenuId = fileId;
            _fileMenuName = fileName;

            const btnDelete = document.getElementById('cmFileDeleteBtn');
            const divDelete = document.getElementById('cmFileDivider');
            if (btnDelete && divDelete) {
                if (isMine) {
                    btnDelete.style.display = 'flex';
                    divDelete.style.display = 'block';
                } else {
                    btnDelete.style.display = 'none';
                    divDelete.style.display = 'none';
                }
            }

            menu.classList.remove('hidden');

            const rect = event.currentTarget.getBoundingClientRect();
            // Fixed positioning means coords are viewport-relative
            let top = rect.bottom + 4;
            let left = rect.left;

            // If it was a right click, place menu at the mouse cursor
            if (event.type === 'contextmenu') {
                top = event.clientY;
                left = event.clientX;
            }

            const menuW = 176,
                menuH = 150;
            if (left + menuW > window.innerWidth) left = window.innerWidth - menuW - 8;
            if (top + menuH > window.innerHeight) top = window.innerHeight - menuH - 8;

            menu.style.top = top + 'px';
            menu.style.left = left + 'px';
        }

        function closeFileMenu() {
            document.getElementById('fileContextMenu').classList.add('hidden');
        }

        function previewFile(fileId) {
            window.location.href = 'preview.php?id=' + fileId + '&project_id=' + selectedProjectId;
        }

        async function fileMenuDelete() {
            closeFileMenu();
            if (!confirm("Delete file \"" + _fileMenuName + "\"? This cannot be undone.")) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete-file');
                fd.append('file_id', _fileMenuId);
                fd.append('project_id', selectedProjectId);
                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    loadPane(_currentFolderId);
                } else {
                    alert(data.error || 'Failed to delete file');
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred');
            }
        }

        function fileRename() {
            closeFileMenu();
            document.getElementById('renameFileInput').value = _fileMenuName;
            document.getElementById('renameFileError').textContent = '';
            document.getElementById('renameFileModal').classList.remove('hidden');
            document.getElementById('renameFileInput').focus();
        }

        function closeRenameFileModal() {
            document.getElementById('renameFileModal').classList.add('hidden');
        }

        async function submitRenameFile() {
            const name = document.getElementById('renameFileInput').value.trim();
            const errEl = document.getElementById('renameFileError');
            const btn = document.getElementById('renameFileBtn');

            if (!name) {
                errEl.textContent = 'Please enter a name.';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Saving...';
            try {
                const fd = new FormData();
                fd.append('action', 'rename-file');
                fd.append('file_id', _fileMenuId);
                fd.append('project_id', selectedProjectId);
                fd.append('name', name);
                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    closeRenameFileModal();
                    loadPane(_currentFolderId);
                } else {
                    errEl.textContent = data.error || 'Failed to rename file.';
                }
            } catch {
                errEl.textContent = 'Network error.';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Rename';
            }
        }

        // Close menus on click or right-click outside
        function closeMenusIfOutside(e) {
            const fMenu = document.getElementById('folderContextMenu');
            if (fMenu && typeof closeFolderMenu === 'function' && !fMenu.contains(e.target)) closeFolderMenu();
            const fileMenu = document.getElementById('fileContextMenu');
            if (fileMenu && !fileMenu.contains(e.target)) closeFileMenu();
        }
        document.addEventListener('click', closeMenusIfOutside);
        document.addEventListener('contextmenu', closeMenusIfOutside);

        // ─── Unified Pane Logic ─────────────────────────────────

        function renderBreadcrumb() {
            const bc = document.getElementById('paneBreadcrumb');
            if (!bc) return;
            if (_folderTrail.length === 0) {
                bc.classList.add('hidden');
                bc.classList.remove('flex');
                bc.innerHTML = '';
                return;
            }
            bc.classList.remove('hidden');
            bc.classList.add('flex');
            let html = '<span class="text-slate-300 dark:text-slate-600 mx-0.5">/</span>' +
                '<button onclick="showFolderGrid()" class="hover:text-primary transition-colors flex-shrink-0">All Files</button>';
            _folderTrail.forEach((crumb, i) => {
                const isLast = i === _folderTrail.length - 1;
                html += '<span class="material-symbols-outlined text-[13px] flex-shrink-0 text-slate-300 dark:text-slate-600">chevron_right</span>';
                if (isLast) {
                    html += '<span class="text-slate-800 dark:text-slate-200 font-semibold truncate max-w-[140px]" title="' + escapeHtml(crumb.name) + '">' + escapeHtml(crumb.name) + '</span>';
                } else {
                    html += '<button onclick="navToTrailIndex(' + i + ')" class="hover:text-primary transition-colors truncate max-w-[100px]" title="' + escapeHtml(crumb.name) + '">' + escapeHtml(crumb.name) + '</button>';
                }
            });
            bc.innerHTML = html;
        }

        function navToTrailIndex(index) {
            _folderTrail = _folderTrail.slice(0, index + 1);
            const target = _folderTrail[index];
            _currentFolderId = target.id;
            renderBreadcrumb();
            loadPane(target.id);
        }

        async function loadPane(folderId) {
            _currentFolderId = folderId;
            window._rootFoldersPage = 1;
            window._rootFilesPage = 1;
            const pane = document.getElementById('filesPane');
            pane.innerHTML = '<div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">' +
                '<span class="material-symbols-outlined text-3xl block mb-2 animate-pulse">folder_open</span>Loading...</div>';

            try {
                if (folderId === null) {
                    const [fRes, fileRes] = await Promise.all([
                        fetch('../api/projects.php?action=get-folders&project_id=' + selectedProjectId),
                        fetch('../api/projects.php?action=files&project_id=' + selectedProjectId)
                    ]);
                    const [fData, fileData] = await Promise.all([fRes.json(), fileRes.json()]);
                    _paneFolders = fData.success ? (fData.data.folders || []) : [];
                    _paneFiles = fileData.success ? (fileData.data.files || []) : [];
                    detectAndNotifyNewUploads(_paneFiles);
                } else {
                    const res = await fetch('../api/projects.php?action=get-folder-files&folder_id=' + folderId + '&project_id=' + selectedProjectId);
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error);
                    _paneFolders = data.data.subfolders || [];
                    _paneFiles = data.data.files || [];
                    detectAndNotifyNewUploads(_paneFiles);
                }
                renderPane();
            } catch (e) {
                pane.innerHTML = '<div class="p-8 text-center text-red-400 text-sm">Failed to load. Please try again.</div>';
                console.error(e);
            }
        }

        function renderPane() {
            const pane = document.getElementById('filesPane');
            const footer = document.getElementById('paneFooter');
            if (!pane) return;

            // Apply filter + sort to files
            let files = [..._paneFiles];
            if (_fileFilter === 'image') files = files.filter(f => isImageType(f.file_type || f.mime_type || ''));
            else if (_fileFilter === 'video') files = files.filter(f => isVideoType(f.file_type || f.mime_type || ''));
            else if (_fileFilter === 'doc') files = files.filter(f => isDocType(f.file_type || f.mime_type || ''));

            files.sort((a, b) => {
                switch (_fileSort) {
                    case 'date-asc':
                        return new Date(a.upload_date) - new Date(b.upload_date);
                    case 'date-desc':
                        return new Date(b.upload_date) - new Date(a.upload_date);
                    case 'name-asc':
                        return (a.original_name || '').localeCompare(b.original_name || '');
                    case 'name-desc':
                        return (b.original_name || '').localeCompare(a.original_name || '');
                    case 'size-asc':
                        return (a.file_size || 0) - (b.file_size || 0);
                    case 'size-desc':
                        return (b.file_size || 0) - (a.file_size || 0);
                    default:
                        return 0;
                }
            });

            const totalItems = _paneFolders.length + files.length;
            const isEmpty = totalItems === 0;
            const noMatch = _paneFolders.length === 0 && _paneFiles.length > 0 && files.length === 0;

            if (footer) {
                if (isEmpty || noMatch) {
                    footer.classList.add('hidden');
                } else {
                    footer.classList.remove('hidden');
                    footer.textContent = totalItems + ' item' + (totalItems !== 1 ? 's' : '');
                }
            }

            if (isEmpty) {
                pane.innerHTML = '<div class="p-12 text-center">' +
                    '<div class="inline-flex size-16 rounded-2xl bg-slate-100 dark:bg-slate-800 items-center justify-center mb-4">' +
                    '<span class="material-symbols-outlined text-3xl text-slate-400">cloud_upload</span>' +
                    '</div>' +
                    '<p class="text-slate-500 dark:text-slate-400 font-semibold text-sm mb-1">' +
                    (_currentFolderId ? 'This folder is empty' : 'No files yet') +
                    '</p>' +
                    '<p class="text-slate-400 dark:text-slate-500 text-xs mb-4">Ask an admin to upload files</p>' +
                    '</div>';
                return;
            }
            if (noMatch) {
                pane.innerHTML = '<div class="p-10 text-center text-slate-400 dark:text-slate-500 text-sm">' +
                    '<span class="material-symbols-outlined text-2xl block mb-2">search_off</span>No files match this filter</div>';
                return;
            }

            let html = '';

            // Ensure globals
            window._rootFoldersPage = window._rootFoldersPage || 1;
            window._rootFilesPage = window._rootFilesPage || 1;
            const rFolderLimit = typeof _foldersPerPage !== 'undefined' ? _foldersPerPage : 4;
            const rFileLimit = typeof _filesPerPage !== 'undefined' ? _filesPerPage : 10;

            // Folder Pagination Logic
            const totalPaneFolders = _paneFolders.length;
            const totalPaneFolderPages = Math.ceil(totalPaneFolders / rFolderLimit);
            if (_rootFoldersPage > totalPaneFolderPages) _rootFoldersPage = Math.max(1, totalPaneFolderPages);
            const rFolderStart = (_rootFoldersPage - 1) * rFolderLimit;
            const pagePaneFolders = _paneFolders.slice(rFolderStart, rFolderStart + rFolderLimit);

            // File Pagination Logic
            const totalPaneFiles = files.length;
            const totalPaneFilePages = Math.ceil(totalPaneFiles / rFileLimit);
            if (_rootFilesPage > totalPaneFilePages) _rootFilesPage = Math.max(1, totalPaneFilePages);
            const rFileStart = (_rootFilesPage - 1) * rFileLimit;
            const pagePaneFiles = files.slice(rFileStart, rFileStart + rFileLimit);

            // Folders Grid
            if (_paneFolders.length > 0) {
                html += '<div class="' + (files.length > 0 ? 'p-4 border-b border-slate-100 dark:border-slate-800' : 'p-4') + '">';
                html += '    <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Folders</p>';
                html += '    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3' + (totalPaneFolderPages > 1 ? ' mb-4' : '') + '">';
                pagePaneFolders.forEach(f => {
                    html += renderFolderCard(f, selectedProjectId);
                });
                html += '    </div>';

                // Folders Pagination UI
                if (totalPaneFolderPages > 1) {
                    html += `<div class="flex justify-between items-center select-none mt-2">`;
                    html += `<div class="flex items-center gap-2">`;
                    html += `<button onclick="changeRootFoldersPage(${_rootFoldersPage - 1})" ${(_rootFoldersPage === 1) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>`;
                    html += `<span class="text-xs text-slate-600 dark:text-slate-400 font-medium px-2">Page ${_rootFoldersPage} of ${totalPaneFolderPages}</span>`;
                    html += `<button onclick="changeRootFoldersPage(${_rootFoldersPage + 1})" ${(_rootFoldersPage === totalPaneFolderPages) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>`;
                    html += `</div></div>`;
                }

                html += '</div>';
            }

            // Files
            if (files.length > 0) {
                if (_fileView === 'grid') {
                    if (_paneFolders.length > 0) {
                        html += '<div class="px-4 pt-4 pb-1"><p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Files</p></div>';
                    }
                    html += '<div class="p-4"><div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">';
                    pagePaneFiles.forEach(file => {
                        const mt = file.file_type || file.mime_type || '';
                        const isImage = isImageType(mt),
                            isVideo = isVideoType(mt);
                        const icon = isImage ? 'image' : (isVideo ? 'movie' : 'description');
                        const iconBg = isImage ? 'bg-purple-500/10' : (isVideo ? 'bg-red-500/10' : 'bg-blue-500/10');
                        const iconClr = isImage ? 'text-purple-500' : (isVideo ? 'text-red-500' : 'text-blue-500');
                        const sfn = (file.original_name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                        const isMine = file.user_id == currentUserId;
                        const isWatermarked = Number(file.watermarked) === 1;
                        const watermarkChip = isWatermarked ?
                            '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30"><span class="material-symbols-outlined text-[12px]">verified</span>Watermarked</span>' :
                            '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/30"><span class="material-symbols-outlined text-[12px]">warning</span>Not Watermarked</span>';
                        const mbId = 'pm-' + file.id;
                        const uploader = file.uploader_name || '';
                        const avatarBg = avatarColor(uploader);
                        const avatarTxt = avatarInitials(uploader);
                        const thumbHtml = isImage ?
                            '<img src="../api/view.php?id=' + file.id + '&thumb=1" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.innerHTML=\'<span class=\\\'material-symbols-outlined text-[32px]\\\' >' + icon + '</span>\'">' :
                            '<span class="material-symbols-outlined text-[32px] ' + iconClr + '">' + icon + '</span>';
                        html += '<div class="group/gc relative flex flex-col bg-slate-900 border border-slate-700/60 rounded-xl overflow-hidden hover:border-primary/50 hover:shadow-lg transition-all cursor-pointer ' + (isMine ? 'ring-1 ring-primary/30' : '') + '"';
                        html += '    onclick="previewFile(' + file.id + ')"';
                        html += '    oncontextmenu="openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ');return false;"';
                        html += '    onmouseenter="document.getElementById(\'' + mbId + '\').style.opacity=\'1\'"';
                        html += '    onmouseleave="document.getElementById(\'' + mbId + '\').style.opacity=\'0\'">';
                        html += '    <div class="relative h-36 ' + (isImage ? 'bg-black' : iconBg + ' flex items-center justify-center') + ' overflow-hidden flex-shrink-0">';
                        html += '        ' + thumbHtml;
                        html += '        <div class="absolute inset-0 flex items-center justify-center bg-black/0 hover:bg-black/40 transition-all opacity-0 group-hover/gc:opacity-100 pointer-events-none">';
                        html += '            <span class="material-symbols-outlined text-white text-3xl drop-shadow">play_circle</span>';
                        html += '        </div>';
                        html += '    </div>';
                        html += '    <div class="p-3 flex flex-col gap-1.5 bg-slate-800/80">';
                        html += '        <p class="text-[13px] font-semibold text-white truncate leading-tight">' + escapeHtml(file.original_name) + '</p>';
                        html += '        <div class="mt-0.5">' + watermarkChip + '</div>';
                        html += '        <div class="flex items-center gap-2">';
                        html += '            <div class="size-5 rounded-full flex-shrink-0 flex items-center justify-center text-[9px] font-bold text-white" style="background:' + avatarBg + '">' + avatarTxt + '</div>';
                        html += '            <span class="text-[11px] text-slate-400 truncate">' + (isMine ? 'You' : (escapeHtml(uploader) || '\u2014')) + '</span>';
                        html += '            <span class="text-slate-600 text-[10px] ml-auto flex-shrink-0">' + fmtDate(file.upload_date).split(' at ')[0] + '</span>';
                        html += '        </div>';
                        html += '    </div>';
                        html += '    <button id="' + mbId + '" onclick="event.stopPropagation();openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ')" style="opacity:0;transition:opacity .15s"';
                        html += '        class="absolute top-1.5 right-1.5 p-1 rounded-md bg-black/50 text-white hover:bg-black/80" title="Options">';
                        html += '        <span class="material-symbols-outlined text-[15px]">more_horiz</span>';
                        html += '    </button>';
                        html += '</div>';
                    });
                    html += '</div></div>';
                } else {
                    if (_paneFolders.length > 0) {
                        html += '<div class="px-4 pt-4 pb-2"><p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Files</p></div>';
                    }
                    html += '<div class="grid gap-0" style="grid-template-columns:minmax(0,1fr) 180px 140px">';
                    html += '    <div class="contents">';
                    html += '        <div class="px-4 py-2 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">Name</div>';
                    html += '        <div class="px-3 py-2 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">Date Uploaded</div>';
                    html += '        <div class="px-3 py-2 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">Uploader</div>';
                    html += '    </div>';
                    pagePaneFiles.forEach(file => {
                        const mt = file.file_type || file.mime_type || '';
                        const isImage = isImageType(mt),
                            isVideo = isVideoType(mt);
                        const icon = isImage ? 'image' : (isVideo ? 'movie' : 'description');
                        const iconBg = isImage ? 'bg-purple-500/10' : (isVideo ? 'bg-red-500/10' : 'bg-blue-500/10');
                        const iconClr = isImage ? 'text-purple-500' : (isVideo ? 'text-red-500' : 'text-blue-500');
                        const isMine = file.user_id == currentUserId;
                        const isWatermarked = Number(file.watermarked) === 1;
                        const watermarkChip = isWatermarked ?
                            '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-500 border border-emerald-500/30"><span class="material-symbols-outlined text-[12px]">verified</span>Watermarked</span>' :
                            '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-500 border border-amber-500/30"><span class="material-symbols-outlined text-[12px]">warning</span>Not Watermarked</span>';
                        const sfn = (file.original_name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                        const mbId = 'pm-' + file.id;
                        const uploader = file.uploader_name || '';
                        const avatarBg = avatarColor(uploader);
                        const avatarTxt = avatarInitials(uploader);
                        const thumbHtml = isImage ?
                            '<img src="../api/view.php?id=' + file.id + '&thumb=1" alt="" class="size-full object-cover rounded" loading="lazy" onerror="this.outerHTML=\'<span class=\\\'material-symbols-outlined text-[16px]\\\' >' + icon + '</span>\'">' :
                            '<span class="material-symbols-outlined text-[16px]">' + icon + '</span>';
                        const rowBg = 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors cursor-pointer ' + (isMine ? 'bg-primary/[0.03]' : '');
                        html += '    <div class="' + rowBg + ' border-b border-slate-100 dark:border-slate-800 last:border-0 contents">';
                        html += '        <div class="px-4 py-3 flex items-center gap-3 min-w-0 ' + rowBg + '"';
                        html += '            onclick="previewFile(' + file.id + ')"';
                        html += '            oncontextmenu="openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ');return false;"';
                        html += '            onmouseenter="document.getElementById(\'' + mbId + '\').style.opacity=\'1\'"';
                        html += '            onmouseleave="document.getElementById(\'' + mbId + '\').style.opacity=\'0\'">';
                        html += '            <div class="size-9 rounded-lg flex-shrink-0 ' + (isImage ? 'overflow-hidden' : iconBg + ' ' + iconClr + ' flex items-center justify-center') + '">';
                        html += '                ' + thumbHtml;
                        html += '            </div>';
                        html += '            <div class="min-w-0 flex-1">';
                        html += '                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">' + escapeHtml(file.original_name) + '</p>';
                        html += '                <p class="text-[11px] text-slate-400 mt-0.5">' + fmtSize(file.file_size) + '</p>';
                        html += '                <div class="mt-1">' + watermarkChip + '</div>';
                        html += '            </div>';
                        html += '            <div class="flex items-center gap-0.5 flex-shrink-0">';
                        html += '                <button id="' + mbId + '" onclick="event.stopPropagation();openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ')" style="opacity:0;transition:opacity .15s"';
                        html += '                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800" title="Options">';
                        html += '                    <span class="material-symbols-outlined text-[17px]">more_vert</span>';
                        html += '                </button>';
                        html += '            </div>';
                        html += '        </div>';
                        html += '        <div class="px-3 py-3 text-xs text-slate-500 dark:text-slate-400 ' + rowBg + ' cursor-pointer"';
                        html += '            onclick="previewFile(' + file.id + ')"';
                        html += '            oncontextmenu="openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ');return false;">' + fmtDate(file.upload_date) + '</div>';
                        html += '        <div class="px-3 py-3 flex items-center gap-2 ' + rowBg + ' cursor-pointer"';
                        html += '            onclick="previewFile(' + file.id + ')"';
                        html += '            oncontextmenu="openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ');return false;">';
                        html += '            <div class="size-6 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold text-white shadow-sm" style="background:' + avatarBg + '">' + avatarTxt + '</div>';
                        html += '            <span class="text-xs text-slate-600 dark:text-slate-300 font-medium truncate">' + (isMine ? 'You' : (escapeHtml(uploader) || '\u2014')) + '</span>';
                        html += '        </div>';
                        html += '    </div>';
                    });
                    html += '</div>';
                }

                // Files Pagination UI
                if (totalPaneFilePages > 1) {
                    html += `<div class="p-4 flex justify-between items-center select-none border-t border-slate-100 dark:border-slate-800">`;
                    html += `<div class="flex items-center gap-2">`;
                    html += `<button onclick="changeRootFilesPage(${_rootFilesPage - 1})" ${(_rootFilesPage === 1) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>`;
                    html += `<span class="text-xs text-slate-600 dark:text-slate-400 font-medium px-2">Page ${_rootFilesPage} of ${totalPaneFilePages}</span>`;
                    html += `<button onclick="changeRootFilesPage(${_rootFilesPage + 1})" ${(_rootFilesPage === totalPaneFilePages) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>`;
                    html += `</div></div>`;
                }

            }
            pane.innerHTML = html;
        }

        window.changeRootFoldersPage = function(page) {
            window._rootFoldersPage = page;
            renderPane();
        };
        window.changeRootFilesPage = function(page) {
            window._rootFilesPage = page;
            renderPane();
        };

        function paneOpenFolder(folderId, folderName) {
            _folderTrail.push({
                id: folderId,
                name: folderName
            });
            renderBreadcrumb();
            loadPane(folderId);
        }

        function showFolderGrid() {
            _folderTrail = [];
            _currentFolderId = null;
            renderBreadcrumb();
            loadPane(null);
        }

        function setFileFilter(f) {
            _fileFilter = f;
            ['all', 'image', 'video', 'doc'].forEach(k => {
                const el = document.getElementById('fChip-' + k);
                if (!el) return;
                el.className = k === f ?
                    'px-3 py-1 rounded-md text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm transition-all' :
                    'px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all';
            });
            renderPane();
        }

        function setFileSort(s) {
            _fileSort = s;
            renderPane();
        }

        function renderFolderCard(f, projectId) {
            const count = f.file_count ?? 0;
            const sfn = (f.name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            let html = '        <div class="relative group/fc">';
            html += '            <button onclick="paneOpenFolder(' + f.id + ', \'' + sfn + '\')"';
            html += '                class="w-full text-left bg-slate-50 dark:bg-slate-800/60 hover:bg-amber-50 dark:hover:bg-amber-500/10 border border-slate-200 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-500/40 rounded-xl p-3 transition-all">';
            html += '                <div class="size-9 rounded-lg bg-amber-400/15 flex items-center justify-center mb-2">';
            html += '                    <span class="material-symbols-outlined text-amber-500 text-[20px]" style="font-variation-settings:\'FILL\' 1">folder</span>';
            html += '                </div>';
            html += '                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate pr-6">' + escapeHtml(f.name) + '</p>';
            html += '                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">' + count + ' file' + (count !== 1 ? 's' : '') + '</p>';
            html += '            </button>';
            html += '            <button onclick="event.stopPropagation(); openFolderMenu(event, ' + f.id + ', \'' + sfn + '\', ' + projectId + ')" class="absolute top-2 right-2 p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/50 opacity-0 group-hover/fc:opacity-100 transition-all shadow-sm">';
            html += '                <span class="material-symbols-outlined text-[17px]">more_vert</span>';
            html += '            </button>';
            html += '        </div>';
            return html;
        }

        function openFolderMenu(event, folderId, folderName, projectId) {
            event.preventDefault();
            event.stopPropagation();

            const menu = document.getElementById('folderContextMenu');
            if (_menuFolderId === folderId && !menu.classList.contains('hidden') && event.type !== 'contextmenu') {
                closeFolderMenu();
                return;
            }

            _menuFolderId = folderId;
            _menuFolderName = folderName;
            if (typeof projectId !== 'undefined') _menuProjectId = projectId;

            menu.classList.remove('hidden');

            const rect = event.currentTarget.getBoundingClientRect();
            let top = rect.bottom + 4;
            let left = rect.left;

            if (event.type === 'contextmenu') {
                top = event.clientY;
                left = event.clientX;
            }

            const menuW = 176,
                menuH = 150;
            if (left + menuW > window.innerWidth) left = window.innerWidth - menuW - 8;
            if (top + menuH > window.innerHeight) top = window.innerHeight - menuH - 8;

            menu.style.top = top + 'px';
            menu.style.left = left + 'px';
        }

        function closeFolderMenu() {
            const menu = document.getElementById('folderContextMenu');
            if (menu) menu.classList.add('hidden');
        }

        function setFileView(v) {
            _fileView = v;
            const active = 'p-1.5 rounded-md bg-white dark:bg-slate-700 text-slate-700 dark:text-white shadow-sm transition-all';
            const inactive = 'p-1.5 rounded-md text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition-all';
            const lb = document.getElementById('vBtn-list'),
                gb = document.getElementById('vBtn-grid');
            if (lb) lb.className = v === 'list' ? active : inactive;
            if (gb) gb.className = v === 'grid' ? active : inactive;
            renderPane();
        }

        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // ─── Rename ───────────────────────────────────────────
        let _menuProjectId = null;
        let _menuFolderId = null;
        let _menuFolderName = null;

        function folderRename() {
            closeFolderMenu();
            document.getElementById('renameFolderInput').value = _menuFolderName;
            document.getElementById('renameFolderError').textContent = '';
            document.getElementById('renameFolderModal').classList.remove('hidden');
            document.getElementById('renameFolderInput').focus();
        }

        function closeRenameFolderModal() {
            document.getElementById('renameFolderModal').classList.add('hidden');
        }

        async function submitRenameFolder() {
            const name = document.getElementById('renameFolderInput').value.trim();
            const errEl = document.getElementById('renameFolderError');
            const btn = document.getElementById('renameFolderBtn');

            if (!name) {
                errEl.textContent = 'Please enter a name.';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Saving...';
            try {
                const fd = new FormData();
                fd.append('action', 'rename-folder');
                fd.append('folder_id', _menuFolderId);
                fd.append('project_id', _menuProjectId);
                fd.append('name', name);
                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    closeRenameFolderModal();
                    if (_currentFolderId === _menuFolderId) _currentFolderName = name;
                    reloadCurrentView();
                } else {
                    errEl.textContent = data.error || 'Failed to rename.';
                }
            } catch {
                errEl.textContent = 'Network error.';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Rename';
            }
        }

        function reloadCurrentView() {
            if (_currentFolderId) {
                loadPane(_currentFolderId);
            } else if (selectedProjectId) {
                loadProjectContent(selectedProjectId);
            }
        }

        // ─── Delete ───────────────────────────────────────────
        async function folderDelete() {
            closeFolderMenu();
            if (!confirm('Delete "' + _menuFolderName + '"? \n\nSubfolders are removed. Files stay in the project.')) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete-folder');
                fd.append('folder_id', _menuFolderId);
                fd.append('project_id', _menuProjectId);
                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    if (_currentFolderId === _menuFolderId) {
                        _currentFolderId = null;
                        _currentFolderProjectId = null;
                        _currentFolderName = null;
                        loadProjectContent(_menuProjectId);
                    } else {
                        reloadCurrentView();
                    }
                } else {
                    alert(data.error || 'Failed to delete folder.');
                }
            } catch {
                alert('Network error.');
            }
        }

        // ─── Duplicate ────────────────────────────────────────
        async function folderDuplicate() {
            closeFolderMenu();
            try {
                const fd = new FormData();
                fd.append('action', 'duplicate-folder');
                fd.append('folder_id', _menuFolderId);
                fd.append('project_id', _menuProjectId);
                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    reloadCurrentView();
                } else {
                    alert(data.error || 'Failed to duplicate folder.');
                }
            } catch {
                alert('Network error.');
            }
        }

        // ─── Open a folder (drill-down) ───────────────────────
        async function openFolder(folderId, projectId, folderName, trail = null) {
            // trail=null means append this folder to existing trail; trail=[] means start fresh
            if (trail !== null) {
                _folderTrail = trail;
            } else {
                _folderTrail.push({
                    id: folderId,
                    name: folderName
                });
            }
            const content = document.getElementById('mainContent');
            content.innerHTML = '<div class="text-center py-16 text-slate-400 dark:text-slate-500 text-sm">Loading folder...</div>';

            try {
                const res = await fetch('../api/projects.php?action=get-folder-files&folder_id=' + folderId + '&project_id=' + projectId);
                const data = await res.json();

                if (!data.success) {
                    content.innerHTML = '<div class="text-center text-red-500 text-sm py-8">' + (data.error || 'Error loading folder') + '</div>';
                    return;
                }

                const subfolders = data.data?.subfolders || [];
                displayFolderContent(folderId, projectId, folderName, data.data.files, subfolders);
            } catch (e) {
                console.error(e);
                content.innerHTML = `<div class="text-center text-red-500 text-sm py-8">Network error loading folder.</div>`;
            }
        }

        function renderFileRow(file) {
            const mt = file.file_type || file.mime_type || '';
            const isImage = isImageType(mt),
                isVideo = isVideoType(mt);
            const icon = isImage ? 'image' : (isVideo ? 'movie' : 'description');
            const iconBg = isImage ? 'bg-purple-500/10' : (isVideo ? 'bg-red-500/10' : 'bg-blue-500/10');
            const iconClr = isImage ? 'text-purple-500' : (isVideo ? 'text-red-500' : 'text-blue-500');
            const isMine = file.user_id == currentUserId;
            const isWatermarked = Number(file.watermarked) === 1;
            const watermarkChip = isWatermarked ?
                `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-500 border border-emerald-500/30"><span class="material-symbols-outlined text-[12px]">verified</span>Watermarked</span>` :
                `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-500 border border-amber-500/30"><span class="material-symbols-outlined text-[12px]">warning</span>Not Watermarked</span>`;
            const sfn = (file.original_name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            const mbId = 'pm-' + file.id;
            const uploader = file.uploader_name || '';
            const avatarBg = avatarColor(uploader);
            const avatarTxt = avatarInitials(uploader);

            const thumbHtml = isImage ?
                `<img src="../api/view.php?id=${file.id}&thumb=1" alt="" class="size-full object-cover rounded" loading="lazy" onerror="this.outerHTML='<span class=\\'material-symbols-outlined text-[16px]\\'>${icon}</span>'">` :
                `<span class="material-symbols-outlined text-[16px]">${icon}</span>`;

            const rowBg = `hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors cursor-pointer ${isMine ? 'bg-primary/[0.03]' : ''}`;

            return `
                <div class="flex items-center px-4 py-3 gap-3 ${rowBg} border-b border-slate-100 dark:border-slate-800 last:border-0 contents">
                    <div class="px-4 py-3 flex items-center gap-3 min-w-0 ${rowBg}" 
                         onclick="previewFile(${file.id})" 
                         oncontextmenu="openFileMenu(event, ${file.id}, '${sfn}', ${isMine}); return false;" 
                         onmouseenter="document.getElementById('${mbId}').style.opacity='1'" 
                         onmouseleave="document.getElementById('${mbId}').style.opacity='0'">
                        <div class="size-9 rounded-lg flex-shrink-0 ${isImage ? 'overflow-hidden' : `${iconBg} ${iconClr} flex items-center justify-center`}">${thumbHtml}</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">${escapeHtml(file.original_name)}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">${fmtSize(file.file_size)}</p>
                            <div class="mt-1">${watermarkChip}</div>
                        </div>
                        <div class="flex items-center gap-0.5 flex-shrink-0">
                            <button id="${mbId}" onclick="event.stopPropagation(); openFileMenu(event, ${file.id}, '${sfn}', ${isMine})" style="opacity:0;transition:opacity .15s" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800" title="Options">
                                <span class="material-symbols-outlined text-[17px]">more_vert</span>
                            </button>
                        </div>
                    </div>
                    <div class="px-3 py-3 text-xs text-slate-500 dark:text-slate-400 ${rowBg}" onclick="previewFile(${file.id})" oncontextmenu="openFileMenu(event, ${file.id}, '${sfn}', ${isMine}); return false;">${fmtDate(file.upload_date)}</div>
                    <div class="px-3 py-3 flex items-center gap-2 ${rowBg}" onclick="previewFile(${file.id})" oncontextmenu="openFileMenu(event, ${file.id}, '${sfn}', ${isMine}); return false;">
                        <div class="size-6 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold text-white shadow-sm" style="background:${avatarBg}">${avatarTxt}</div>
                        <span class="text-xs text-slate-600 dark:text-slate-300 font-medium truncate">${isMine ? 'You' : (escapeHtml(uploader) || '\\u2014')}</span>
                    </div>
                </div>
            `;
        }

        function displayFolderContent(folderId, projectId, folderName, files, subfolders = []) {
            const content = document.getElementById('mainContent');
            // Store context for action buttons
            _currentFolderId = folderId;
            _currentFolderProjectId = projectId;
            _currentFolderName = folderName;

            const hasContent = files.length > 0 || subfolders.length > 0;

            // Build full breadcrumb path from _folderTrail
            let _bcHtml = '';
            _folderTrail.forEach((crumb, i) => {
                const isLast = i === _folderTrail.length - 1;
                _bcHtml += '<span class="text-slate-300 dark:text-slate-600">/</span>';
                if (isLast) {
                    _bcHtml += '<div class="flex items-center gap-1.5">';
                    _bcHtml += '<span class="material-symbols-outlined text-amber-500 text-[18px]" style="font-variation-settings:\'FILL\' 1,\'wght\' 400,\'GRAD\' 0,\'opsz\' 24">folder</span>';
                    _bcHtml += '<span class="text-sm font-semibold text-slate-900 dark:text-white">' + escapeHtml(crumb.name) + '</span>';
                    _bcHtml += '</div>';
                } else {
                    _bcHtml += '<button onclick="navToFolderTrailIndex(' + i + ', ' + projectId + ')" class="text-sm text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium">' + escapeHtml(crumb.name) + '</button>';
                }
            });

            let html = '';
            html += '<!-- Breadcrumb -->\n';
            html += '<div class="flex items-center flex-wrap gap-1.5 mb-6">\n';
            html += '<button onclick="_folderTrail=[]; loadProjectContent(' + projectId + ')" class="flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium flex-shrink-0">\n';
            html += '<span class="material-symbols-outlined text-[18px]">arrow_back</span>\n';
            html += 'Back to Project\n';
            html += '</button>\n';
            html += _bcHtml + '\n';
            html += '</div>\n';


            html += '<!-- Folder header + actions -->\n';
            html += '<div class="flex items-center justify-between mb-4 px-1">\n';
            html += '<h2 class="text-slate-900 dark:text-white text-base font-bold">\n';
            html += 'Contents\n';
            html += '<span class="ml-2 text-xs font-semibold text-slate-400 dark:text-slate-500">' + (files.length + subfolders.length) + '</span>\n';
            html += '</h2>\n';
            html += '<div class="flex items-center gap-2">\n';
            html += '<button onclick="openCreateFolderModal(' + projectId + ', ' + folderId + ')" class="flex items-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-lg transition-all shadow-sm border border-slate-200 dark:border-slate-700">\n';
            html += '<span class="material-symbols-outlined text-[17px]">create_new_folder</span>\n';
            html += 'New Folder\n';
            html += '</button>\n';
            html += '<button onclick="openFolderUploadModal()" class="flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-all shadow-sm">\n';
            html += '<span class="material-symbols-outlined text-[17px]">cloud_upload</span>\n';
            html += 'Upload File\n';
            html += '</button>\n';
            html += '</div>\n';
            html += '</div>\n';

            // Subfolders grid
            if (subfolders.length > 0) {
                html += '<div id="foldersListContainer" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-2"></div>\n';
                html += '<div id="foldersPaginationContainer" class="mb-6 flex justify-between items-center"></div>\n';
            }

            // File list or empty state
            if (hasContent) {
                if (files.length > 0) {
                    html += '<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">\n';
                    html += '<div id="filesListContainer" class="divide-y divide-slate-100 dark:divide-slate-800"></div>\n';
                    html += '</div>\n';
                    html += '<div id="filesPaginationContainer" class="mt-4 flex justify-between items-center"></div>\n';
                }
            } else {
                html += '<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-14 text-center shadow-sm">\n';
                html += '<div class="size-14 mx-auto mb-4 bg-amber-400/10 rounded-full flex items-center justify-center">\n';
                html += '<span class="material-symbols-outlined text-amber-400 text-3xl" style="font-variation-settings:\'FILL\' 1,\'wght\' 400,\'GRAD\' 0,\'opsz\' 24">folder_open</span>\n';
                html += '</div>\n';
                html += '<p class="text-slate-500 dark:text-slate-400 text-sm font-medium">This folder is empty</p>\n';
                html += '<p class="text-slate-400 dark:text-slate-500 text-xs mt-1 mb-5">Upload files or create a subfolder</p>\n';
                html += '<button onclick="openFolderUploadModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-all shadow-sm">\n';
                html += '<span class="material-symbols-outlined text-[17px]">cloud_upload</span>\n';
                html += 'Upload File\n';
                html += '</button>\n';
                html += '</div>\n';
            }

            content.innerHTML = html;

            _currentSubfolders = subfolders;
            _currentFoldersPage = 1;
            if (subfolders.length > 0) {
                renderPaginatedFolders();
            }

            _currentFolderFiles = files;
            _currentFilesPage = 1;
            if (files.length > 0) {
                renderPaginatedFiles();
            }
        }

        // ─── Pagination Logic ──────────────────────────
        let _currentFolderFiles = [];
        let _currentFilesPage = 1;
        const _filesPerPage = 10;

        function renderPaginatedFiles() {
            const container = document.getElementById('filesListContainer');
            const pagContainer = document.getElementById('filesPaginationContainer');
            if (!container || !pagContainer) return;

            const totalFiles = _currentFolderFiles.length;
            const totalPages = Math.ceil(totalFiles / _filesPerPage);

            if (_currentFilesPage > totalPages) _currentFilesPage = totalPages;
            if (_currentFilesPage < 1) _currentFilesPage = 1;

            const startIndex = (_currentFilesPage - 1) * _filesPerPage;
            const endIndex = Math.min(startIndex + _filesPerPage, totalFiles);
            const pageFiles = _currentFolderFiles.slice(startIndex, endIndex);

            container.innerHTML = pageFiles.map(f => renderFileRow(f)).join('');

            // Build pagination controls
            if (totalPages > 1) {
                let pagHtml = `<div class="flex items-center gap-2 select-none">`;
                pagHtml += `<button onclick="changeFilesPage(${_currentFilesPage - 1})" ${(_currentFilesPage === 1) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>`;
                pagHtml += `<span class="text-xs text-slate-600 dark:text-slate-400 font-medium px-2">Page ${_currentFilesPage} of ${totalPages}</span>`;
                pagHtml += `<button onclick="changeFilesPage(${_currentFilesPage + 1})" ${(_currentFilesPage === totalPages) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>`;
                pagHtml += `</div>`;
                pagContainer.innerHTML = pagHtml;
            } else {
                pagContainer.innerHTML = '';
            }
        }

        function changeFilesPage(newPage) {
            _currentFilesPage = newPage;
            renderPaginatedFiles();
        }

        let _currentSubfolders = [];
        let _currentFoldersPage = 1;
        const _foldersPerPage = 4;

        function renderPaginatedFolders() {
            const container = document.getElementById('foldersListContainer');
            const pagContainer = document.getElementById('foldersPaginationContainer');
            if (!container || !pagContainer) return;

            const totalFolders = _currentSubfolders.length;
            const totalPages = Math.ceil(totalFolders / _foldersPerPage);

            if (_currentFoldersPage > totalPages) _currentFoldersPage = totalPages;
            if (_currentFoldersPage < 1) _currentFoldersPage = 1;

            const startIndex = (_currentFoldersPage - 1) * _foldersPerPage;
            const endIndex = Math.min(startIndex + _foldersPerPage, totalFolders);
            const pageFolders = _currentSubfolders.slice(startIndex, endIndex);

            container.innerHTML = pageFolders.map(sf => renderFolderCard(sf, _currentFolderProjectId)).join('');

            if (totalPages > 1) {
                let pagHtml = `<div class="flex items-center gap-2 select-none">`;
                pagHtml += `<button onclick="changeFoldersPage(${_currentFoldersPage - 1})" ${(_currentFoldersPage === 1) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>`;
                pagHtml += `<span class="text-xs text-slate-600 dark:text-slate-400 font-medium px-2">Page ${_currentFoldersPage} of ${totalPages}</span>`;
                pagHtml += `<button onclick="changeFoldersPage(${_currentFoldersPage + 1})" ${(_currentFoldersPage === totalPages) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>`;
                pagHtml += `</div>`;
                pagContainer.innerHTML = pagHtml;
            } else {
                pagContainer.innerHTML = '';
            }
        }

        function changeFoldersPage(newPage) {
            _currentFoldersPage = newPage;
            renderPaginatedFolders();
        }

        // ─── Folder-scoped upload state ──────────────────────────
        let _currentFolderProjectId = null;
        let _currentFolderName = null;

        function navToFolderTrailIndex(index, projectId) {
            const target = _folderTrail[index];
            _folderTrail = _folderTrail.slice(0, index + 1);
            _currentFolderId = target.id;
            openFolder(target.id, projectId, target.name, [..._folderTrail]);
        }

        function openFolderUploadModal() {
            _stagedUploadFiles = null;
            if (document.getElementById('folderDropZoneText')) {
                document.getElementById('folderDropZoneText').textContent = 'Drag & drop or click to browse';
                document.getElementById('folderDropZoneTextSub').classList.remove('hidden');
            }
            document.getElementById('folderFileInput').value = '';
            const p1 = document.getElementById('folderPdfPassword');
            const p2 = document.getElementById('folderPdfPasswordConfirm');
            if (p1) {
                p1.value = '';
                p1.type = 'password';
                p1.nextElementSibling.querySelector('span').textContent = 'visibility';
            }
            if (p2) {
                p2.value = '';
                p2.type = 'password';
                p2.nextElementSibling.querySelector('span').textContent = 'visibility';
            }

            document.getElementById('folderUploadError').textContent = '';
            document.getElementById('folderUploadStatus').textContent = '';
            document.getElementById('folderUploadProgress').classList.add('hidden');
            document.getElementById('folderUploadProgressBar').style.width = '0%';
            if (_currentFolderName) {
                document.getElementById('folderUploadFolderName').textContent = `Into: ${_currentFolderName}`;
            }
            document.getElementById('folderUploadModal').classList.remove('hidden');
        }

        function closeFolderUploadModal() {
            document.getElementById('folderUploadModal').classList.add('hidden');
        }

        // Drag & drop wiring (runs once)
        let _folderDropWired = false;
        let _stagedUploadFiles = null;

        function wireFolderDrop() {
            if (_folderDropWired) return;
            _folderDropWired = true;
            const dz = document.getElementById('folderDropZone');
            dz.addEventListener('dragover', e => {
                e.preventDefault();
                dz.classList.add('border-primary', 'bg-primary/5');
            });
            dz.addEventListener('dragleave', () => dz.classList.remove('border-primary', 'bg-primary/5'));
            dz.addEventListener('drop', e => {
                e.preventDefault();
                dz.classList.remove('border-primary', 'bg-primary/5');
                stageUploadFiles(e.dataTransfer.files);
            });
            document.getElementById('folderFileInput').addEventListener('change', e => stageUploadFiles(e.target.files));
        }

        function stageUploadFiles(files) {
            if (!files || files.length === 0) return;
            _stagedUploadFiles = files;
            const cnt = files.length;
            document.getElementById('folderDropZoneText').textContent = `${cnt} file${cnt !== 1 ? 's' : ''} selected. Ready to upload.`;
            document.getElementById('folderDropZoneTextSub').classList.add('hidden');
        }

        async function submitFolderUpload() {
            if (!_stagedUploadFiles || _stagedUploadFiles.length === 0) {
                document.getElementById('folderUploadError').textContent = 'Please select a file to upload.';
                return;
            }

            const pwd1 = document.getElementById('folderPdfPassword') ? document.getElementById('folderPdfPassword').value.trim() : '';
            if (pwd1 !== '') {
                // Check if any non-PDF file is selected
                let hasNonPdf = false;
                for (let i = 0; i < _stagedUploadFiles.length; i++) {
                    if (_stagedUploadFiles[i].type !== 'application/pdf') {
                        hasNonPdf = true;
                        break;
                    }
                }
                if (hasNonPdf) {
                    document.getElementById('folderUploadError').textContent = 'Passwords can only be applied when exclusively uploading PDF files.';
                    return;
                }

                const pwd2 = document.getElementById('folderPdfPasswordConfirm') ? document.getElementById('folderPdfPasswordConfirm').value.trim() : '';
                if (pwd1 !== pwd2) {
                    document.getElementById('folderUploadError').textContent = 'PDF passwords do not match.';
                    return;
                }
            }

            document.getElementById('folderUploadError').textContent = '';
            document.getElementById('folderUploadTriggerBtn').disabled = true;
            await handleFolderUpload(_stagedUploadFiles);
            document.getElementById('folderUploadTriggerBtn').disabled = false;
        }

        async function handleFolderUpload(files) {
            if (!files || files.length === 0) return;
            if (!selectedProjectId) return;

            const progressEl = document.getElementById('folderUploadProgress');
            const bar = document.getElementById('folderUploadProgressBar');
            const status = document.getElementById('folderUploadStatus');
            const errEl = document.getElementById('folderUploadError');

            progressEl.classList.remove('hidden');
            errEl.textContent = '';
            let successCount = 0;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                status.textContent = `Uploading ${file.name} (${i + 1}/${files.length})...`;
                bar.style.width = `${(i / files.length) * 100}%`;

                const fd = new FormData();
                fd.append('file', file);
                fd.append('project_id', selectedProjectId);
                if (_currentFolderId) {
                    fd.append('folder_id', _currentFolderId);
                }
                const wmCheckbox = document.getElementById('requireFolderWatermark');
                if (wmCheckbox) fd.append('require_watermark', wmCheckbox.checked ? '1' : '0');

                const pdfPasswordInput = document.getElementById('folderPdfPassword');
                const pdfPassword = pdfPasswordInput ? pdfPasswordInput.value.trim() : '';
                const pdfPasswordConfirmInput = document.getElementById('folderPdfPasswordConfirm');
                const pdfPasswordConfirm = pdfPasswordConfirmInput ? pdfPasswordConfirmInput.value.trim() : '';

                // Validate password confirmation
                if (pdfPassword && pdfPassword !== pdfPasswordConfirm) {
                    errEl.textContent = 'PDF passwords do not match. Please verify and try again.';
                    bar.parentElement.parentElement.classList.add('hidden');
                    return;
                }

                const isPdf = (file.type === 'application/pdf') || ((file.name || '').toLowerCase().endsWith('.pdf'));
                if (isPdf && pdfPassword) {
                    fd.append('pdf_password', pdfPassword);
                }

                try {
                    const res = await fetch('../api/upload.php', {
                        method: 'POST',
                        credentials: 'include',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) successCount++;
                    else errEl.textContent = data.error || 'Upload failed';
                } catch (e) {
                    errEl.textContent = 'Network error';
                }

                bar.style.width = `${((i + 1) / files.length) * 100}%`;
            }

            status.textContent = `Done! ${successCount}/${files.length} file(s) uploaded.`;
            setTimeout(() => {
                closeFolderUploadModal();
                loadPane(_currentFolderId);
            }, 800);
        }

        document.addEventListener('DOMContentLoaded', wireFolderDrop);
        document.addEventListener('DOMContentLoaded', startWorkspaceUploadPolling);

        // ─── Create Folder Modal ───────────────────────────────
        let _createFolderProjectId = null;
        let _createFolderParentId = null; // null = top-level folder

        function openCreateFolderModal(projectId, parentFolderId = null) {
            _createFolderProjectId = projectId;
            _createFolderParentId = parentFolderId || null;
            document.getElementById('folderNameInput').value = '';
            document.getElementById('folderError').textContent = '';
            // Update modal subtitle to reflect context
            const subtitle = document.getElementById('createFolderSubtitle');
            if (subtitle) {
                subtitle.textContent = _createFolderParentId ?
                    `Subfolder of: ${_currentFolderName || 'current folder'}` :
                    'Organise project files into a folder';
            }
            document.getElementById('createFolderModal').classList.remove('hidden');
            document.getElementById('folderNameInput').focus();
        }

        function closeCreateFolderModal() {
            document.getElementById('createFolderModal').classList.add('hidden');
        }

        async function submitCreateFolder() {
            const name = document.getElementById('folderNameInput').value.trim();
            const errEl = document.getElementById('folderError');
            const btn = document.getElementById('createFolderBtn');

            if (!name) {
                errEl.textContent = 'Please enter a folder name.';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Creating...';
            errEl.textContent = '';

            try {
                const fd = new FormData();
                fd.append('action', 'create-folder');
                fd.append('project_id', _createFolderProjectId);
                fd.append('name', name);
                if (_createFolderParentId) fd.append('parent_id', _createFolderParentId);

                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.success) {
                    closeCreateFolderModal();
                    // If we were inside a folder, reload that folder view; otherwise refresh project
                    if (_createFolderParentId) {
                        openFolder(_createFolderParentId, _createFolderProjectId, _currentFolderName);
                    } else {
                        loadProjectContent(_createFolderProjectId);
                    }
                } else {
                    errEl.textContent = data.error || 'Failed to create folder.';
                }
            } catch (e) {
                errEl.textContent = 'Network error, please try again.';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Create Folder';
            }
        }

        // Allow Enter key to submit
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('folderNameInput')?.addEventListener('keydown', e => {
                if (e.key === 'Enter') submitCreateFolder();
            });
        });
    </script>

    <!-- ═══════════════════════════════════════
         FOLDER CONTEXT MENU (floating dropdown)
    ═══════════════════════════════════════ -->
    <div id="folderContextMenu"
        class="hidden fixed z-[200] w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden"
        style="top:0;left:0">
        <button onclick="folderRename()"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            <span class="material-symbols-outlined text-[18px] text-slate-400">edit</span>
            Rename
        </button>
        <button onclick="folderDelete()"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
            <span class="material-symbols-outlined text-[18px]">delete</span>
            Delete
        </button>
    </div>

    <!-- ═══════════════════════════════════════
         RENAME FOLDER MODAL
    ═══════════════════════════════════════ -->
    <div id="renameFolderModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRenameFolderModal()"></div>
        <div
            class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">edit</span>
                </div>
                <div>
                    <h3 class="text-slate-900 dark:text-white font-bold text-base">Rename Folder</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-xs">Enter a new name for this folder</p>
                </div>
                <button onclick="closeRenameFolderModal()"
                    class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Folder Name</label>
            <input id="renameFolderInput" type="text" placeholder="Folder name..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all"
                onkeydown="if(event.key==='Enter') submitRenameFolder()" />
            <p id="renameFolderError" class="text-red-500 text-xs mt-2 min-h-[1rem]"></p>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button onclick="closeRenameFolderModal()"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                <button id="renameFolderBtn" onclick="submitRenameFolder()"
                    class="px-5 py-2 rounded-xl text-sm font-bold bg-primary hover:bg-primary/90 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">Rename</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         CREATE FOLDER MODAL
    ═══════════════════════════════════════ -->
    <div id="createFolderModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCreateFolderModal()"></div>

        <!-- Modal Card -->
        <div
            class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-5">
                <div class="size-10 rounded-xl bg-amber-400/15 flex items-center justify-center">
                    <span class="material-symbols-outlined text-amber-500"
                        style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">create_new_folder</span>
                </div>
                <div>
                    <h3 class="text-slate-900 dark:text-white font-bold text-base">New Folder</h3>
                    <p id="createFolderSubtitle" class="text-slate-500 dark:text-slate-400 text-xs">Organise project
                        files into a folder</p>
                </div>
                <button onclick="closeCreateFolderModal()"
                    class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <!-- Input -->
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Folder Name</label>
            <input id="folderNameInput" type="text" placeholder="e.g. Q1 Reports, Assets, Docs..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" />

            <p id="folderError" class="text-red-500 text-xs mt-2 min-h-[1rem]"></p>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2 mt-4">
                <button onclick="closeCreateFolderModal()"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    Cancel
                </button>
                <button id="createFolderBtn" onclick="submitCreateFolder()"
                    class="px-5 py-2 rounded-xl text-sm font-bold bg-primary hover:bg-primary/90 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                    Create Folder
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         FILE CONTEXT MENU
    ═══════════════════════════════════════ -->
    <div id="fileContextMenu"
        class="hidden fixed z-[200] w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden"
        style="top:0;left:0">
        <button onclick="fileRename()"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            <span class="material-symbols-outlined text-[18px] text-slate-400">edit</span>
            Rename
        </button>
        <a href="javascript:void(0)" onclick="window.location.href='../api/download.php?file_id='+_fileMenuId"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            <span class="material-symbols-outlined text-[18px] text-slate-400">download</span>
            Download
        </a>
        <div id="cmFileDivider" class="border-t border-slate-100 dark:border-slate-800 mx-2 hidden"></div>
        <button id="cmFileDeleteBtn" onclick="fileMenuDelete()"
            class="hidden w-full items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
            <span class="material-symbols-outlined text-[18px]">delete</span>
            Delete
        </button>
    </div>

    <!-- ═══════════════════════════════════════
         RENAME FILE MODAL
    ═══════════════════════════════════════ -->
    <div id="renameFileModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRenameFileModal()"></div>
        <div
            class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">edit</span>
                </div>
                <div>
                    <h3 class="text-slate-900 dark:text-white font-bold text-base">Rename File</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-xs">Enter a new name for this file</p>
                </div>
                <button onclick="closeRenameFileModal()"
                    class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">File Name</label>
            <input id="renameFileInput" type="text" placeholder="File name..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all"
                onkeydown="if(event.key==='Enter') submitRenameFile()" />
            <p id="renameFileError" class="text-red-500 text-xs mt-2 min-h-[1rem]"></p>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button onclick="closeRenameFileModal()"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                <button id="renameFileBtn" onclick="submitRenameFile()"
                    class="px-5 py-2 rounded-xl text-sm font-bold bg-primary hover:bg-primary/90 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">Rename</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         FOLDER UPLOAD MODAL
    ═══════════════════════════════════════ -->
    <div id="folderUploadModal" class="hidden fixed inset-0 z-[110] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeFolderUploadModal()"></div>

        <!-- Card -->
        <div
            class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-5">
                <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">cloud_upload</span>
                </div>
                <div>
                    <h3 class="text-slate-900 dark:text-white font-bold text-base">Upload to Folder</h3>
                    <p id="folderUploadFolderName" class="text-slate-500 dark:text-slate-400 text-xs">Uploading into
                        current folder</p>
                </div>
                <button onclick="closeFolderUploadModal()"
                    class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <!-- Drop zone -->
            <div id="folderDropZone" onclick="document.getElementById('folderFileInput').click()"
                class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 text-center cursor-pointer hover:border-primary/50 transition-all mb-4">
                <div class="size-12 mx-auto mb-3 bg-primary/10 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-2xl">upload_file</span>
                </div>
                <p id="folderDropZoneText" class="text-slate-700 dark:text-slate-200 text-sm font-semibold">Drag & drop or click to browse</p>
                <p id="folderDropZoneTextSub" class="text-slate-400 dark:text-slate-500 text-xs mt-1">Images, Videos, PDFs, Docs · Max 50MB</p>
                <input type="file" id="folderFileInput" class="hidden" multiple
                    accept="image/png,video/mp4,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.pdf,.doc,.docx,.xls,.xlsx" />
            </div>

            <!-- Watermark Option -->
            <div
                class="flex items-start gap-3 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center h-5 mt-0.5">
                    <input id="requireFolderWatermark" type="checkbox" checked
                        class="w-3.5 h-3.5 text-primary bg-white border-slate-300 rounded focus:ring-primary cursor-pointer">
                </div>
                <div class="flex flex-col cursor-pointer"
                    onclick="document.getElementById('requireFolderWatermark').click();">
                    <label class="text-xs font-bold text-slate-700 dark:text-slate-200 cursor-pointer select-none">Apply
                        Invisible Watermark</label>
                    <span
                        class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight mt-0.5 cursor-pointer select-none">Embeds
                        tracking data into the file pixels when downloaded.</span>
                </div>
            </div>

            <div class="mt-3">
                <label for="folderPdfPassword" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">PDF Password (Optional)</label>
                <div class="relative">
                    <input id="folderPdfPassword" type="password" maxlength="64" placeholder="Applied only to PDF files"
                        class="w-full pl-3 pr-10 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40" />
                    <button type="button" onclick="togglePasswordVisibility('folderPdfPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 outline-none">
                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                    </button>
                </div>

                <label for="folderPdfPasswordConfirm" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1 mt-2">Confirm Password</label>
                <div class="relative">
                    <input id="folderPdfPasswordConfirm" type="password" maxlength="64" placeholder="Confirm PDF password"
                        class="w-full pl-3 pr-10 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40" />
                    <button type="button" onclick="togglePasswordVisibility('folderPdfPasswordConfirm', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 outline-none">
                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                    </button>
                </div>
            </div>

            <!-- Progress -->
            <div id="folderUploadProgress" class="hidden mt-4">
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div id="folderUploadProgressBar" class="bg-primary h-full transition-all duration-300 rounded-full"
                        style="width:0%"></div>
                </div>
                <p id="folderUploadStatus" class="text-xs text-slate-500 dark:text-slate-400 mt-2"></p>
            </div>
            <p id="folderUploadError" class="text-red-500 text-xs mt-2 min-h-[1rem]"></p>

            <!-- Close/cancel -->
            <div class="flex justify-end gap-2 mt-4">
                <button onclick="closeFolderUploadModal()"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    Cancel
                </button>
                <button id="folderUploadTriggerBtn" onclick="submitFolderUpload()"
                    class="px-5 py-2 rounded-xl text-sm font-bold bg-primary hover:bg-primary/90 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                    Upload
                </button>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(id, btn) {
            const el = document.getElementById(id);
            const icon = btn.querySelector('span');
            if (el.type === 'password') {
                el.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                el.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>

    <script>
        try {
            localStorage.setItem('sv_new_uploads_seen_employee_<?php echo (int)$user['id']; ?>', String(Date.now()));
        } catch (e) {}

        window.currentUser = {
            name: "<?php echo htmlspecialchars($user['name']); ?>",
            email: "<?php echo htmlspecialchars($user['email']); ?>"
        };
    </script>
    <?php include '../includes/settings_modal.php'; ?>
    <script>
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
    <script src="../js/security-shield.js"></script>
</body>
</html>