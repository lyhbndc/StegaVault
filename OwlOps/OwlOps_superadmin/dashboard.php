<?php

/**
 * StegaVault - Super Admin Dashboard
 * File: super_admin/dashboard.php
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

// Fetch all web apps
$webApps = [];
$stats = [
    'total_apps' => 0,
    'active_apps' => 0,
    'total_admins' => 0
];

$appsResult = $db->query("SELECT * FROM web_apps ORDER BY created_at DESC");
if ($appsResult) {
    while ($row = $appsResult->fetch_assoc()) {
        $webApps[] = $row;
        $stats['total_apps']++;
        if ($row['status'] === 'active') {
            $stats['active_apps']++;
        }
    }
}

// Get total admins across all apps
$adminResult = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND web_app_id IS NOT NULL");
if ($adminResult) {
    $stats['total_admins'] = $adminResult->fetch_assoc()['count'];
}

?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Global Dashboard - StegaVault</title>
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
            /* background-dark */
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

        .bg-grid-pattern {
            background-image: radial-gradient(#ffffff 0.5px, transparent 0.5px);
            background-size: 24px 24px;
        }
    </style>
</head>

<body class="text-slate-200 min-h-screen flex flex-col relative overflow-x-hidden">

    <!-- Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] bg-primary/10 rounded-full blur-[120px]"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 w-full px-6 py-4 flex items-center justify-between border-b border-white/5 bg-background-dark/80 backdrop-blur-md sticky top-0">
        <div class="flex items-center gap-3">
            <div class="bg-primary p-2 rounded-lg shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined text-black">public</span>
            </div>
            <div>
                <h2 class="text-white text-xl font-bold tracking-tight font-display">OwlOps <span class="text-white/90 font-medium">Super Admin</span></h2>
                <p class="text-[10px] text-slate-400 font-medium tracking-widest uppercase">Super Admin Environment</p>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-full px-4 py-2">
                <span class="material-symbols-outlined text-slate-400 text-sm">account_circle</span>
                <span class="text-sm font-semibold text-white"><?php echo htmlspecialchars($user['name']); ?></span>
                <div class="h-4 w-px bg-white/20 mx-1"></div>
                <button onclick="logout()" class="text-xs text-red-400 hover:text-red-300 font-medium transition-colors">Sign Out</button>
            </div>
        </div>
    </header>

    <main class="relative z-10 flex-1 max-w-7xl w-full mx-auto px-6 py-10 flex flex-col gap-10">

        <!-- Welcome & Stats -->
        <div>
            <h1 class="text-3xl font-bold text-white mb-2 font-display">Global Dashboard</h1>
            <p class="text-slate-400">Manage all web applications and organizational scopes across the OwlOps ecosystem.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-card border border-white/10 rounded-2xl p-6 relative overflow-hidden group hover:border-primary/50 transition-colors">
                <div class="absolute -right-4 -bottom-4 bg-primary/10 size-32 rounded-full blur-2xl group-hover:bg-primary/20 transition-colors"></div>
                <div class="relative z-10 flex items-start justify-between">
                    <div>
                        <p class="text-slate-400 text-sm font-medium mb-1">Total Web Apps</p>
                        <h3 class="text-4xl font-bold text-white font-display"><?php echo $stats['total_apps']; ?></h3>
                    </div>
                    <div class="p-3 bg-white/5 rounded-xl border border-white/10">
                        <span class="material-symbols-outlined text-primary text-2xl">apps</span>
                    </div>
                </div>
            </div>

            <div class="bg-slate-card border border-white/10 rounded-2xl p-6 relative overflow-hidden group hover:border-emerald-500/50 transition-colors">
                <div class="absolute -right-4 -bottom-4 bg-emerald-500/10 size-32 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-colors"></div>
                <div class="relative z-10 flex items-start justify-between">
                    <div>
                        <p class="text-slate-400 text-sm font-medium mb-1">Active Environments</p>
                        <h3 class="text-4xl font-bold text-white font-display"><?php echo $stats['active_apps']; ?></h3>
                    </div>
                    <div class="p-3 bg-white/5 rounded-xl border border-white/10">
                        <span class="material-symbols-outlined text-emerald-400 text-2xl">check_circle</span>
                    </div>
                </div>
            </div>

            <div class="bg-slate-card border border-white/10 rounded-2xl p-6 relative overflow-hidden group hover:border-blue-500/50 transition-colors">
                <div class="absolute -right-4 -bottom-4 bg-blue-500/10 size-32 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-colors"></div>
                <div class="relative z-10 flex items-start justify-between">
                    <div>
                        <p class="text-slate-400 text-sm font-medium mb-1">Total App Admins</p>
                        <h3 class="text-4xl font-bold text-white font-display"><?php echo $stats['total_admins']; ?></h3>
                    </div>
                    <div class="p-3 bg-white/5 rounded-xl border border-white/10">
                        <span class="material-symbols-outlined text-blue-400 text-2xl">shield_person</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Web Apps List -->
        <div>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-white font-display">Web Applications</h2>
                    <p class="text-sm text-slate-400">Select an application to manage its environment and administrators.</p>
                </div>
                <button onclick="openNewAppModal()" class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-black rounded-xl font-bold text-sm transition-all shadow-glow flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">add</span> New App
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (count($webApps) > 0): ?>
                    <?php foreach ($webApps as $app): ?>
                        <div class="bg-slate-card border border-white/10 rounded-2xl p-6 flex flex-col h-full hover:-translate-y-1 hover:shadow-[0_10px_40px_-10px_rgba(139,92,246,0.2)] transition-all duration-300">

                            <div class="flex items-start justify-between mb-4">
                                <div class="size-12 bg-gradient-to-br from-slate-700 to-slate-800 rounded-xl border border-white/10 flex items-center justify-center text-white font-bold text-lg">
                                    <?php echo strtoupper(substr($app['name'], 0, 1)); ?>
                                </div>
                                <div class="flex items-center gap-2 relative">
                                    <?php if ($app['status'] === 'active'): ?>
                                        <span class="px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold uppercase tracking-widest rounded-full flex items-center gap-1.5">
                                            <span class="size-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 bg-slate-500/10 border border-slate-500/20 text-slate-400 text-[10px] font-bold uppercase tracking-widest rounded-full flex items-center gap-1.5">
                                            <span class="size-1.5 rounded-full bg-slate-400"></span> Inactive
                                        </span>
                                    <?php endif; ?>

                                    <button onclick="toggleAppMenu(<?php echo $app['id']; ?>)" class="p-1 hover:bg-white/10 rounded-lg text-slate-400 transition-colors">
                                        <span class="material-symbols-outlined text-sm">more_vert</span>
                                    </button>
                                    <div id="appMenu-<?php echo $app['id']; ?>" class="hidden absolute top-full right-0 mt-1 w-32 bg-slate-800 border border-white/10 rounded-xl shadow-xl overflow-hidden z-20">
                                        <button onclick="openRenameModal(<?php echo $app['id']; ?>, '<?php echo addslashes(htmlspecialchars($app['name'], ENT_QUOTES)); ?>')" class="w-full text-left px-4 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white transition-colors flex items-center gap-2">
                                            <span class="material-symbols-outlined text-sm">edit</span> Rename
                                        </button>
                                        <button onclick="openDeleteModal(<?php echo $app['id']; ?>, '<?php echo addslashes(htmlspecialchars($app['name'], ENT_QUOTES)); ?>')" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-red-400/10 hover:text-red-300 transition-colors flex items-center gap-2 border-t border-white/5">
                                            <span class="material-symbols-outlined text-sm">delete</span> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-lg font-bold text-white mb-1 font-display"><?php echo htmlspecialchars($app['name']); ?></h3>
                            <p class="text-xs text-slate-500 font-mono mb-6">ID: <?php echo $app['id']; ?> • Created <?php echo date('M Y', strtotime($app['created_at'])); ?></p>

                            <div class="mt-auto pt-4 border-t border-white/5">
                                <form action="context.php" method="POST">
                                    <input type="hidden" name="web_app_id" value="<?php echo $app['id']; ?>">
                                    <button type="submit" class="w-full py-3 bg-primary hover:bg-primary-hover text-black rounded-xl font-bold text-sm transition-all shadow-glow flex items-center justify-center gap-2 group">
                                        Manage Environment
                                        <span class="material-symbols-outlined text-lg group-hover:translate-x-1 transition-transform">arrow_forward</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full py-12 flex flex-col items-center justify-center text-slate-500 border border-dashed border-white/10 rounded-2xl bg-white/5">
                        <span class="material-symbols-outlined text-4xl mb-4 opacity-50">dns</span>
                        <p class="font-medium">No web applications found in the global network.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- New App Modal -->
        <div id="newAppModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300">
            <div class="bg-slate-card border border-white/10 rounded-2xl w-full max-w-md shadow-2xl transform scale-95 transition-transform duration-300">
                <div class="p-6 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white font-display">Create New Application</h3>
                    <button type="button" onclick="closeNewAppModal()" class="text-slate-400 hover:text-white transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="newAppForm" onsubmit="createNewApp(event)" class="p-6">
                    <div id="newAppError" class="hidden mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm"></div>
                    <div id="newAppSuccess" class="hidden mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-400 text-sm"></div>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-white text-sm font-medium">Application Name</label>
                            <input type="text" id="appNameInput" required class="w-full px-4 py-3 rounded-xl bg-background-dark border border-white/10 text-white placeholder:text-slate-500 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none" placeholder="e.g. Acme Corporation" />
                            <p class="text-xs text-slate-500 mt-1">A dedicated portal folder will automatically be generated.</p>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3 justify-end">
                        <button type="button" onclick="closeNewAppModal()" class="px-5 py-2.5 rounded-xl font-medium text-slate-300 hover:text-white hover:bg-white/5 transition-colors">Cancel</button>
                        <button type="submit" id="createAppBtn" class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-black rounded-xl font-bold transition-shadow shadow-glow flex items-center justify-center gap-2">
                            <span>Initialize App</span>
                            <span class="material-symbols-outlined text-sm">rocket_launch</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rename App Modal -->
        <div id="renameAppModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300">
            <div class="bg-slate-card border border-white/10 rounded-2xl w-full max-w-md shadow-2xl transform scale-95 transition-transform duration-300">
                <div class="p-6 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white font-display">Rename Application</h3>
                    <button type="button" onclick="closeRenameModal()" class="text-slate-400 hover:text-white transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form id="renameAppForm" onsubmit="renameApp(event)" class="p-6">
                    <div id="renameAppError" class="hidden mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm"></div>
                    <div id="renameAppSuccess" class="hidden mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-400 text-sm"></div>

                    <input type="hidden" id="renameAppId" />
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-white text-sm font-medium">New Name</label>
                            <input type="text" id="renameAppNameInput" required class="w-full px-4 py-3 rounded-xl bg-background-dark border border-white/10 text-white placeholder:text-slate-500 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none" />
                        </div>
                    </div>
                    <div class="mt-8 flex gap-3 justify-end">
                        <button type="button" onclick="closeRenameModal()" class="px-5 py-2.5 rounded-xl font-medium text-slate-300 hover:text-white hover:bg-white/5 transition-colors">Cancel</button>
                        <button type="submit" id="renameAppBtn" class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-black rounded-xl font-bold transition-shadow shadow-glow flex items-center justify-center gap-2">
                            <span>Rename App</span>
                            <span class="material-symbols-outlined text-sm">save</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete App Modal -->
        <div id="deleteAppModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300">
            <div class="bg-slate-card border border-white/10 border-t-red-500/50 rounded-2xl w-full max-w-md shadow-2xl transform scale-95 transition-transform duration-300">
                <div class="p-6 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-red-400 font-display flex items-center gap-2">
                        <span class="material-symbols-outlined">warning</span> Delete Application
                    </h3>
                    <button type="button" onclick="closeDeleteModal()" class="text-slate-400 hover:text-white transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-6">
                    <div id="deleteAppError" class="hidden mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm"></div>
                    <div id="deleteAppSuccess" class="hidden mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-400 text-sm"></div>

                    <p class="text-slate-300 mb-6">Are you sure you want to permanently delete <strong id="deleteAppNameDisplay" class="text-white"></strong>? All associated environment folders will be destroyed.</p>

                    <div class="flex gap-3 justify-end">
                        <button type="button" onclick="closeDeleteModal()" class="px-5 py-2.5 rounded-xl font-medium text-slate-300 hover:text-white hover:bg-white/5 transition-colors">Cancel</button>
                        <button type="button" onclick="deleteApp()" id="deleteAppBtn" class="px-5 py-2.5 bg-red-500 hover:bg-red-400 text-white rounded-xl font-bold transition-shadow shadow-[0_0_15px_rgba(239,68,68,0.3)] flex items-center justify-center gap-2">
                            <span>Delete Permanently</span>
                            <span class="material-symbols-outlined text-sm">delete_forever</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Modal Logic
        const modal = document.getElementById('newAppModal');
        const modalInner = modal.querySelector('div');

        // Dropdown menu logic
        let openMenuId = null;

        function toggleAppMenu(id) {
            if (openMenuId && openMenuId !== id) {
                document.getElementById('appMenu-' + openMenuId).classList.add('hidden');
            }
            const menu = document.getElementById('appMenu-' + id);
            menu.classList.toggle('hidden');
            if (!menu.classList.contains('hidden')) {
                openMenuId = id;
            } else {
                openMenuId = null;
            }
        }

        document.addEventListener('click', (e) => {
            if (openMenuId && !e.target.closest('.relative')) {
                const openMenu = document.getElementById('appMenu-' + openMenuId);
                if (openMenu) openMenu.classList.add('hidden');
                openMenuId = null;
            }
        });

        function openNewAppModal() {
            document.getElementById('appNameInput').value = '';
            document.getElementById('newAppError').classList.add('hidden');
            document.getElementById('newAppSuccess').classList.add('hidden');

            modal.classList.remove('opacity-0', 'pointer-events-none');
            modalInner.classList.remove('scale-95');
            modalInner.classList.add('scale-100');
            setTimeout(() => document.getElementById('appNameInput').focus(), 100);
        }

        function closeNewAppModal() {
            modal.classList.add('opacity-0', 'pointer-events-none');
            modalInner.classList.remove('scale-100');
            modalInner.classList.add('scale-95');
        }

        // Close modal on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeNewAppModal();
        });

        // App Creation
        async function createNewApp(e) {
            e.preventDefault();
            const btn = document.getElementById('createAppBtn');
            const errorDiv = document.getElementById('newAppError');
            const successDiv = document.getElementById('newAppSuccess');
            const name = document.getElementById('appNameInput').value.trim();

            if (!name) return;

            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span>Building Environment...</span><span class="material-symbols-outlined animate-spin">refresh</span>';
            errorDiv.classList.add('hidden');

            try {
                const res = await fetch('../StegaVault/api/super_admin_api.php?action=create_app', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name
                    })
                });

                const data = await res.json();

                if (data.success) {
                    successDiv.textContent = 'Application initialized! Reloading dashboard...';
                    successDiv.classList.remove('hidden');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    errorDiv.textContent = data.error || 'Failed to create application';
                    errorDiv.classList.remove('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-70', 'cursor-not-allowed');
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                console.error(err);
                errorDiv.textContent = 'Network or server error occurred.';
                errorDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                btn.innerHTML = originalText;
            }
        }

        // Rename Modal Logic
        const renameModal = document.getElementById('renameAppModal');
        const renameModalInner = renameModal.querySelector('div');

        function openRenameModal(id, currentName) {
            document.getElementById('renameAppId').value = id;
            document.getElementById('renameAppNameInput').value = currentName;
            document.getElementById('renameAppError').classList.add('hidden');
            document.getElementById('renameAppSuccess').classList.add('hidden');

            renameModal.classList.remove('opacity-0', 'pointer-events-none');
            renameModalInner.classList.remove('scale-95');
            renameModalInner.classList.add('scale-100');
            if (openMenuId) toggleAppMenu(openMenuId);
        }

        function closeRenameModal() {
            renameModal.classList.add('opacity-0', 'pointer-events-none');
            renameModalInner.classList.remove('scale-100');
            renameModalInner.classList.add('scale-95');
        }

        async function renameApp(e) {
            e.preventDefault();
            const btn = document.getElementById('renameAppBtn');
            const id = document.getElementById('renameAppId').value;
            const name = document.getElementById('renameAppNameInput').value.trim();
            const errorDiv = document.getElementById('renameAppError');
            const successDiv = document.getElementById('renameAppSuccess');

            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span>';
            errorDiv.classList.add('hidden');

            try {
                const res = await fetch('../StegaVault/api/super_admin_api.php?action=rename_app', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id,
                        name
                    })
                });
                const data = await res.json();
                if (data.success) {
                    successDiv.textContent = 'Application renamed! Reloading...';
                    successDiv.classList.remove('hidden');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    errorDiv.textContent = data.error || 'Failed to rename application';
                    errorDiv.classList.remove('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-70', 'cursor-not-allowed');
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                errorDiv.textContent = 'Network error occurred.';
                errorDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                btn.innerHTML = originalText;
            }
        }

        // Delete Modal Logic
        const deleteModal = document.getElementById('deleteAppModal');
        const deleteModalInner = deleteModal.querySelector('div');
        let appToDelete = null;

        function openDeleteModal(id, name) {
            appToDelete = id;
            document.getElementById('deleteAppNameDisplay').textContent = name;
            document.getElementById('deleteAppError').classList.add('hidden');
            document.getElementById('deleteAppSuccess').classList.add('hidden');

            deleteModal.classList.remove('opacity-0', 'pointer-events-none');
            deleteModalInner.classList.remove('scale-95');
            deleteModalInner.classList.add('scale-100');
            if (openMenuId) toggleAppMenu(openMenuId);
        }

        function closeDeleteModal() {
            deleteModal.classList.add('opacity-0', 'pointer-events-none');
            deleteModalInner.classList.remove('scale-100');
            deleteModalInner.classList.add('scale-95');
            appToDelete = null;
        }

        async function deleteApp() {
            if (!appToDelete) return;
            const btn = document.getElementById('deleteAppBtn');
            const errorDiv = document.getElementById('deleteAppError');
            const successDiv = document.getElementById('deleteAppSuccess');

            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span>';
            errorDiv.classList.add('hidden');

            try {
                const res = await fetch('../StegaVault/api/super_admin_api.php?action=delete_app', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: appToDelete
                    })
                });
                const data = await res.json();
                if (data.success) {
                    successDiv.textContent = 'Application deleted! Reloading...';
                    successDiv.classList.remove('hidden');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    errorDiv.textContent = data.error || 'Failed to delete application';
                    errorDiv.classList.remove('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-70', 'cursor-not-allowed');
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                errorDiv.textContent = 'Network error occurred.';
                errorDiv.classList.remove('hidden');
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
                btn.innerHTML = originalText;
            }
        }

        async function logout() {
            try {
                const response = await fetch('../StegaVault/api/auth.php?action=logout', {
                    method: 'POST'
                });
                if (response.ok) {
                    window.location.href = 'login.php';
                }
            } catch (error) {
                console.error('Logout failed:', error);
            }
        }
    </script>
</body>

</html>