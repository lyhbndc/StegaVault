<?php

/**
 * StegaVault - Super Admin App Admins List
 * File: super_admin/admins.php
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

// Get app admins
$admins = [];
$stmt = $db->prepare("SELECT id, name, email, created_at FROM users WHERE role = 'admin' AND web_app_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $webAppId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Admins - <?php echo htmlspecialchars($webAppName); ?></title>
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
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight font-display">OwlOps</h1>
                    <p class="text-primary text-[10px] font-bold uppercase tracking-widest mt-1">Super Admin Mode</p>
                </div>
                <button onclick="toggleTheme()" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors" title="Toggle theme">
                    <span class="material-symbols-outlined text-[18px]" id="themeIcon">dark_mode</span>
                </button>
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

                <div class="mt-8">
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Environment</p>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-400 hover:text-primary dark:hover:text-white hover:bg-primary/5 dark:hover:bg-white/5 transition-colors" href="app_dashboard.php">
                        <span class="material-symbols-outlined text-[20px]">monitoring</span>
                        <p class="text-sm font-medium">App Overview</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 dark:bg-primary/20 text-primary border border-primary/20 dark:border-primary/30" href="admins.php">
                        <span class="material-symbols-outlined text-[20px] text-primary">group</span>
                        <p class="text-sm font-medium">App Admins</p>
                    </a>
                </div>
            </nav>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-10 flex flex-col gap-8">

        <!-- Header -->
        <header class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 font-display">App Administrators</h2>
                <p class="text-slate-600 dark:text-slate-400">Manage the administrative accounts governing <span class="text-slate-900 dark:text-white font-medium"><?php echo htmlspecialchars($webAppName); ?></span>.</p>
            </div>

            <a href="create_admin.php" class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-xl font-bold text-sm transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">person_add</span> Create Admin
            </a>
        </header>

        <!-- Messages -->
        <div id="statusMessage" class="hidden px-4 py-3 rounded-xl text-sm font-medium border"></div>

        <!-- Admins Table -->
        <div class="bg-white dark:bg-slate-card border border-slate-200 dark:border-white/10 rounded-2xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
                        <th class="px-6 py-4 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Administrator</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Contact Email</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Date Added</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                    <?php if (count($admins) > 0): ?>
                        <?php foreach ($admins as $admin): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-9 rounded-full bg-gradient-to-br from-primary to-purple-600 flex items-center justify-center text-white font-bold text-xs">
                                            <?php echo strtoupper(substr($admin['name'], 0, 2)); ?>
                                        </div>
                                        <p class="text-slate-900 dark:text-white font-semibold text-sm"><?php echo htmlspecialchars($admin['name']); ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-slate-600 dark:text-slate-300 text-sm font-mono"><?php echo htmlspecialchars($admin['email']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-slate-500 dark:text-slate-400 text-sm"><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="relative inline-block text-left">
                                        <button onclick="toggleMenu(<?php echo $admin['id']; ?>)" class="p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 rounded-lg transition-colors" title="Options">
                                            <span class="material-symbols-outlined text-[20px]">more_vert</span>
                                        </button>

                                        <!-- Dropdown menu -->
                                        <div id="menu-<?php echo $admin['id']; ?>" class="hidden absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-xl bg-white dark:bg-slate-card border border-slate-200 dark:border-white/10 shadow-xl focus:outline-none">
                                            <div class="py-1" role="none">
                                                <button onclick="editAdmin(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($admin['email'], ENT_QUOTES); ?>')" class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white transition-colors">
                                                    <span class="material-symbols-outlined text-[18px]">edit</span> Edit Details
                                                </button>
                                                <button onclick="deleteAdmin(<?php echo $admin['id']; ?>)" class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span> Remove Admin
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="material-symbols-outlined text-4xl mb-3 opacity-50">admin_panel_settings</span>
                                    <p class="font-medium">No administrators have been added to this application yet.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <!-- Edit Admin Modal -->
    <div id="editModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeEditModal()"></div>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="relative bg-white dark:bg-slate-card rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full border border-slate-200 dark:border-white/10">
                <div class="px-6 py-6 border-b border-slate-200 dark:border-white/5">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white font-display">Edit Administrator</h3>
                </div>
                <div class="px-6 py-6">
                    <form id="editForm" class="space-y-4">
                        <input type="hidden" id="edit_admin_id">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name</label>
                            <input type="text" id="edit_admin_name" required class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-[#1b1f27] border border-slate-300 dark:border-[#3b4354] text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
                            <input type="email" id="edit_admin_email" required class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-[#1b1f27] border border-slate-300 dark:border-[#3b4354] text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">New Password <span class="text-xs text-slate-400 font-normal">(Leave blank to keep current)</span></label>
                            <input type="password" id="edit_admin_password" class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-[#1b1f27] border border-slate-300 dark:border-[#3b4354] text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none" placeholder="••••••••">
                        </div>
                        <div class="mt-8 flex gap-3 justify-end">
                            <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 rounded-xl font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">Cancel</button>
                            <button type="submit" id="saveEditBtn" class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle action menu
        function toggleMenu(id) {
            // Close all other menus first
            document.querySelectorAll('[id^="menu-"]').forEach(el => {
                if (el.id !== 'menu-' + id) el.classList.add('hidden');
            });
            const menu = document.getElementById('menu-' + id);
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        window.onclick = function(event) {
            if (!event.target.closest('button')) {
                document.querySelectorAll('[id^="menu-"]').forEach(el => el.classList.add('hidden'));
            }
        }

        // Edit Admin
        function editAdmin(id, name, email) {
            document.getElementById('edit_admin_id').value = id;
            document.getElementById('edit_admin_name').value = name;
            document.getElementById('edit_admin_email').value = email;
            document.getElementById('edit_admin_password').value = '';

            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('menu-' + id).classList.add('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Delete Admin
        async function deleteAdmin(id) {
            if (!confirm('Are you certain you want to remove this administrator? This action cannot be undone and will revoke their access to the environment immediately.')) {
                return;
            }

            document.getElementById('menu-' + id).classList.add('hidden');

            try {
                const response = await fetch(`../StegaVault/api/users.php?action=delete`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showStatus('Administrator removed successfully.', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showStatus(data.error || 'Failed to remove user.', 'error');
                }
            } catch (error) {
                showStatus('System connection error.', 'error');
            }
        }

        // Save Edit
        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('saveEditBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-lg">progress_activity</span> Saving...';

            const formData = {
                id: document.getElementById('edit_admin_id').value,
                name: document.getElementById('edit_admin_name').value,
                email: document.getElementById('edit_admin_email').value,
                role: 'admin' // Force role
            };

            const pw = document.getElementById('edit_admin_password').value;
            if (pw) formData.password = pw;

            try {
                const response = await fetch(`../StegaVault/api/users.php?action=update`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    closeEditModal();
                    showStatus('Administrator updated successfully.', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showStatus(data.error || 'Failed to update user.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Save Changes';
                }
            } catch (error) {
                showStatus('System connection error.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Save Changes';
            }
        });

        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('owlops-theme', isDark ? 'dark' : 'light');
            const icon = document.getElementById('themeIcon');
            if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
        }
        document.addEventListener('DOMContentLoaded', function() {
            const icon = document.getElementById('themeIcon');
            if (icon) icon.textContent = document.documentElement.classList.contains('dark') ? 'light_mode' : 'dark_mode';
        });

        // Status Message Helper
        function showStatus(message, type) {
            const el = document.getElementById('statusMessage');
            el.textContent = message;
            el.classList.remove('hidden', 'bg-red-500/10', 'text-red-500', 'border-red-500/20', 'bg-green-500/10', 'text-green-500', 'border-green-500/20');

            if (type === 'error') {
                el.classList.add('bg-red-500/10', 'text-red-500', 'border-red-500/20');
            } else {
                el.classList.add('bg-green-500/10', 'text-green-500', 'border-green-500/20');
            }
        }
    </script>
    <script src="session-timeout.js"></script>
</body>

</html>