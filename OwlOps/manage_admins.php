<?php

/**
 * StegaVault - Super Admin Management (Super Admins & App Admins)
 * File: OwlOps/manage_admins.php
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

// Fetch all web apps for context when creating App Admins
$webApps = [];
$appsResult = $db->query("SELECT id, name FROM web_apps ORDER BY name ASC");
if ($appsResult) {
    while ($row = $appsResult->fetch_assoc()) {
        $webApps[] = $row;
    }
}

?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Manage Administrators - OwlOps</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
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
            background-image: radial-gradient(#ffffff 0.1px, transparent 0.1px);
            background-size: 30px 30px;
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
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors"
                    href="dashboard.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <p class="text-sm font-medium">Control Center</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-white border border-white/10"
                    href="manage_admins.php">
                    <span class="material-symbols-outlined text-[20px] text-primary">admin_panel_settings</span>
                    <p class="text-sm font-medium">Manage Admins</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors"
                    href="backup.php">
                    <span class="material-symbols-outlined text-[20px]">backup</span>
                    <p class="text-sm font-medium">Backup & Restore</p>
                </a>
            </nav>

            <div class="pt-6 border-t border-white/5">
                <div class="flex items-center gap-3 px-3 py-2">
                    <div
                        class="size-8 rounded-full bg-primary flex items-center justify-center text-black font-bold text-xs">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-xs font-bold truncate"><?php echo htmlspecialchars($user['name']); ?>
                        </p>
                        <p class="text-slate-500 text-[10px] truncate">Super Admin</p>
                    </div>
                </div>
                <button onclick="logout()"
                    class="w-full mt-4 flex items-center gap-3 px-3 py-2 rounded-lg text-red-400 hover:bg-red-400/10 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <p class="text-sm font-medium">Sign Out</p>
                </button>
            </div>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-12 transition-all duration-500 relative overflow-x-hidden">
        <!-- Background Decor -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
            <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
            <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px]"></div>
        </div>

        <div class="relative z-10 max-w-6xl mx-auto space-y-10">
            <!-- Header -->
            <header class="flex items-end justify-between">
                <div>
                    <h2 class="text-4xl font-bold text-white font-display">Administrator Management</h2>
                    <p class="text-slate-400 mt-2">Oversee global system owners and application-level administrators.
                    </p>
                </div>
                <div class="flex gap-4">
                    <button onclick="openCreateModal('super')"
                        class="px-5 py-2.5 bg-white hover:bg-slate-200 text-black rounded-xl font-bold text-sm transition-all shadow-lg flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">shield_person</span> New Super Admin
                    </button>
                    <button onclick="openCreateModal('app')"
                        class="px-5 py-2.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-xl font-bold text-sm transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">person_add</span> New App Admin
                    </button>
                </div>
            </header>

            <!-- Tabs -->
            <div class="flex border-b border-white/5 gap-8">
                <button onclick="switchTab('super')" id="tab-super"
                    class="pb-4 px-2 text-sm font-bold border-b-2 border-primary text-white transition-all">Super
                    Admins</button>
                <button onclick="switchTab('app')" id="tab-app"
                    class="pb-4 px-2 text-sm font-bold border-b-2 border-transparent text-slate-500 hover:text-white transition-all">App
                    Administrators</button>
            </div>

            <!-- Content Area -->
            <div id="content-super" class="space-y-6">
                <div class="bg-slate-card border border-white/10 rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white/5 border-b border-white/10">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Name</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Email</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Added Date</th>
                                <th
                                    class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="superAdminTable" class="divide-y divide-white/5">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="content-app" class="hidden space-y-6">
                <div class="bg-slate-card border border-white/10 rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-white/5 border-b border-white/10">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Name</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Email</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Scope / App</th>
                                <th
                                    class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="appAdminTable" class="divide-y divide-white/5">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Create/Edit Modal -->
    <div id="adminModal"
        class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/80 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div
            class="bg-slate-card border border-white/10 rounded-3xl w-full max-w-lg shadow-2xl transform scale-95 transition-transform duration-300">
            <div class="p-8 border-b border-white/5 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-2xl">
                        <span class="material-symbols-outlined text-primary text-2xl"
                            id="modalIcon">shield_person</span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white font-display" id="modalTitle">New Super Admin</h3>
                        <p class="text-slate-500 text-sm" id="modalSubtitle">Grant global administrative privileges.</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="p-2 text-slate-400 hover:text-white transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form id="adminForm" onsubmit="handleFormSubmit(event)" class="p-8 space-y-6">
                <input type="hidden" id="adminType" name="type" value="super">
                <input type="hidden" id="adminId" name="id" value="">

                <div class="space-y-2">
                    <label class="block text-slate-400 text-xs font-bold uppercase tracking-widest">Full Name</label>
                    <input type="text" id="adminName" required
                        class="w-full px-5 py-4 rounded-xl bg-background-dark border border-white/10 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                        placeholder="e.g. Alexander Pierce" />
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-400 text-xs font-bold uppercase tracking-widest">Email
                        Address</label>
                    <input type="email" id="adminEmail" required
                        class="w-full px-5 py-4 rounded-xl bg-background-dark border border-white/10 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                        placeholder="name@company.com" />
                </div>

                <div id="appScopeField" class="hidden space-y-2">
                    <label class="block text-slate-400 text-xs font-bold uppercase tracking-widest">Application
                        Scope</label>
                    <select id="adminWebAppId"
                        class="w-full px-5 py-4 rounded-xl bg-background-dark border border-white/10 text-white focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none">
                        <?php foreach ($webApps as $app): ?>
                            <?php if (stripos($app['name'], 'stegavault') !== false): ?>
                                <option value="<?php echo $app['id']; ?>"><?php echo htmlspecialchars($app['name']); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-400 text-xs font-bold uppercase tracking-widest">
                        <span id="passwordLabel">Initial Password</span>
                        <span id="passwordHint"
                            class="hidden font-normal text-slate-600 lowercase tracking-normal">(Leave blank to keep
                            current)</span>
                    </label>
                    <div class="relative group">
                        <input type="password" id="adminPassword"
                            class="w-full px-5 py-4 rounded-xl bg-background-dark border border-white/10 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                            placeholder="Min 12 characters" />
                        <button type="button" onclick="togglePasswordVisibility('adminPassword')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-slate-400 text-xs font-bold uppercase tracking-widest">Confirm
                        Password</label>
                    <div class="relative group">
                        <input type="password" id="adminConfirmPassword"
                            class="w-full px-5 py-4 rounded-xl bg-background-dark border border-white/10 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                            placeholder="••••••••••••" />
                        <button type="button" onclick="togglePasswordVisibility('adminConfirmPassword')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-[20px]">visibility</span>
                        </button>
                    </div>
                    <p id="passwordRequirementText" class="text-[10px] text-slate-500 italic mt-2">
                        Requires: 12+ chars, uppercase, lowercase, number, and special character.
                    </p>
                </div>

                <div class="pt-4 flex gap-4">
                    <button type="button" onclick="closeModal()"
                        class="flex-1 px-5 py-4 rounded-xl font-bold text-slate-400 hover:text-white hover:bg-white/5 transition-all text-sm">Cancel</button>
                    <button type="submit" id="submitBtn"
                        class="flex-1 px-5 py-4 bg-primary hover:bg-slate-200 text-black rounded-xl font-bold transition-all shadow-lg text-sm">Create
                        Admin</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentTab = 'super';
        const webAppNameMap = <?php echo json_encode(array_column($webApps, 'name', 'id')); ?>;
        let superAdminsList = [];
        let appAdminsList = [];

        async function fetchAdmins() {
            try {
                const superRes = await fetch('../StegaVault/api/super_management.php?action=list_super_admins');
                const appRes = await fetch('../StegaVault/api/super_management.php?action=list_app_admins');

                const superData = await superRes.json();
                const appData = await appRes.json();

                if (superData.success) {
                    superAdminsList = superData.data.admins;
                    renderSuperAdmins(superAdminsList);
                }
                if (appData.success) {
                    appAdminsList = appData.data.admins;
                    renderAppAdmins(appAdminsList);
                }
            } catch (err) {
                console.error('Failed to fetch admins:', err);
            }
        }

        function renderSuperAdmins(admins) {
            const table = document.getElementById('superAdminTable');
            table.innerHTML = admins.length ? admins.map(a => `
                <tr class="hover:bg-white/[0.02] transition-colors group">
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="size-9 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-primary font-bold text-xs">
                                ${a.name.substr(0, 2).toUpperCase()}
                            </div>
                            <p class="text-white font-bold text-sm">${a.name}</p>
                        </div>
                    </td>
                    <td class="px-6 py-5">
                        <p class="text-slate-400 text-xs font-mono">${a.email}</p>
                    </td>
                    <td class="px-6 py-5">
                        <p class="text-slate-500 text-xs">${new Date(a.created_at).toLocaleDateString()}</p>
                    </td>
                    <td class="px-6 py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editAdmin('super', ${a.id})" class="p-2 text-slate-600 hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-xl">edit</span>
                            </button>
                            ${a.id != <?php echo $user['id']; ?> ? `
                                <button onclick="deleteAdmin('super', ${a.id})" class="p-2 text-slate-600 hover:text-red-400 transition-colors">
                                    <span class="material-symbols-outlined text-xl">delete</span>
                                </button>
                            ` : '<span class="text-[10px] text-primary/50 font-bold uppercase tracking-widest italic pr-2">You</span>'}
                        </div>
                    </td>
                </tr>
            `).join('') : `
                <tr><td colspan="4" class="px-6 py-12 text-center text-slate-600">No super administrators found.</td></tr>
            `;
        }

        function renderAppAdmins(admins) {
            const table = document.getElementById('appAdminTable');
            table.innerHTML = admins.length ? admins.map(a => `
                <tr class="hover:bg-white/[0.02] transition-colors group">
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="size-9 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-slate-300 font-bold text-xs">
                                ${a.name.substr(0, 2).toUpperCase()}
                            </div>
                            <p class="text-white font-bold text-sm">${a.name}</p>
                        </div>
                    </td>
                    <td class="px-6 py-5">
                        <p class="text-slate-400 text-xs font-mono">${a.email}</p>
                    </td>
                    <td class="px-6 py-5">
                        <div class="inline-flex items-center gap-2 px-2.5 py-1 bg-white/5 border border-white/10 rounded-full">
                            <span class="size-1.5 rounded-full bg-blue-500"></span>
                            <span class="text-[10px] text-slate-300 font-bold uppercase tracking-widest">${webAppNameMap[a.web_app_id] || 'Global / Common'}</span>
                        </div>
                    </td>
                    <td class="px-6 py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="editAdmin('app', ${a.id})" class="p-2 text-slate-600 hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-xl">edit</span>
                            </button>
                            <button onclick="deleteAdmin('app', ${a.id})" class="p-2 text-slate-600 hover:text-red-400 transition-colors">
                                <span class="material-symbols-outlined text-xl">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('') : `
                <tr><td colspan="4" class="px-6 py-12 text-center text-slate-600">No application administrators found.</td></tr>
            `;
        }

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('border-primary', 'text-white');
                el.classList.add('border-transparent', 'text-slate-500');
            });
            document.getElementById('tab-' + tab).classList.add('border-primary', 'text-white');
            document.getElementById('tab-' + tab).classList.remove('border-transparent', 'text-slate-500');

            document.getElementById('content-super').classList.toggle('hidden', tab !== 'super');
            document.getElementById('content-app').classList.toggle('hidden', tab !== 'app');
        }

        function openCreateModal(type, existingData = null) {
            const modal = document.getElementById('adminModal');
            const inner = modal.children[0];
            const form = document.getElementById('adminForm');

            form.reset();
            document.getElementById('adminType').value = type;
            document.getElementById('adminId').value = existingData ? existingData.id : '';

            const isEdit = !!existingData;

            document.getElementById('modalTitle').textContent = isEdit ? `Edit ${type === 'super' ? 'Super' : 'App'} Admin` : `New ${type === 'super' ? 'Super' : 'App'} Admin`;
            document.getElementById('modalSubtitle').textContent = isEdit ? 'Update account details and permissions.' : (type === 'super' ? 'Grant global administrative privileges.' : 'Assign admin rights to a specific environment.');
            document.getElementById('modalIcon').textContent = type === 'super' ? 'shield_person' : 'person_add';
            document.getElementById('appScopeField').classList.toggle('hidden', type !== 'app');
            document.getElementById('submitBtn').textContent = isEdit ? 'Save Changes' : `Create ${type === 'super' ? 'Super Admin' : 'Admin'}`;

            document.getElementById('passwordLabel').textContent = isEdit ? 'New Password' : 'Initial Password';
            document.getElementById('passwordHint').classList.toggle('hidden', !isEdit);
            document.getElementById('adminPassword').required = !isEdit;

            if (isEdit) {
                document.getElementById('adminName').value = existingData.name;
                document.getElementById('adminEmail').value = existingData.email;
                if (type === 'app' && existingData.web_app_id) {
                    document.getElementById('adminWebAppId').value = existingData.web_app_id;
                }
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.classList.add('flex');
                inner.classList.remove('scale-95');
                inner.classList.add('scale-100');
            }, 10);
        }

        function editAdmin(type, id) {
            const list = type === 'super' ? superAdminsList : appAdminsList;
            const admin = list.find(a => a.id == id);
            if (admin) openCreateModal(type, admin);
        }

        function closeModal() {
            const modal = document.getElementById('adminModal');
            const inner = modal.children[0];
            modal.classList.add('opacity-0');
            inner.classList.add('scale-95');
            inner.classList.remove('scale-100');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.getElementById('adminForm').reset();
                // Reset password input types
                document.getElementById('adminPassword').type = 'password';
                document.getElementById('adminConfirmPassword').type = 'password';
                document.querySelectorAll('.group span.material-symbols-outlined').forEach(s => {
                    if (s.textContent === 'visibility_off') s.textContent = 'visibility';
                });
            }, 300);
        }

        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            const btn = event.currentTarget.querySelector('.material-symbols-outlined');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                btn.textContent = 'visibility';
            }
        }

        async function handleFormSubmit(e) {
            e.preventDefault();
            const type = document.getElementById('adminType').value;
            const id = document.getElementById('adminId').value;
            const isEdit = !!id;

            let action = '';
            if (isEdit) {
                action = type === 'super' ? 'update_super_admin' : 'update_app_admin';
            } else {
                action = type === 'super' ? 'create_super_admin' : 'create_app_admin';
            }

            const btn = document.getElementById('submitBtn');
            const originalText = btn.textContent;

            const payload = {
                id: id,
                name: document.getElementById('adminName').value,
                email: document.getElementById('adminEmail').value,
                password: document.getElementById('adminPassword').value,
            };

            if (type === 'app') {
                payload.web_app_id = document.getElementById('adminWebAppId').value || null;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="animate-spin material-symbols-outlined">progress_activity</span>';

            const password = document.getElementById('adminPassword').value;
            const confirm = document.getElementById('adminConfirmPassword').value;

            // Password Complexity Validation (only if password is not empty - password might be optional for edits)
            if (password || !isEdit) {
                if (password !== confirm) {
                    alert('Passwords do not match.');
                    btn.disabled = false;
                    btn.textContent = originalText;
                    return;
                }

                // Regex: 12+ chars, 1 uppercase, 1 lowercase, 1 number, 1 special
                const complexityRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/;
                if (!complexityRegex.test(password)) {
                    alert('Password does not meet complexity requirements:\n- Minimum 12 characters\n- Uppercase, lowercase, number, and special character required.');
                    btn.disabled = false;
                    btn.textContent = originalText;
                    return;
                }
            }

            try {
                const res = await fetch(`../StegaVault/api/super_management.php?action=${action}`, {
                    method: 'POST', // API supports POST for updates too
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    closeModal();
                    fetchAdmins();
                } else {
                    alert(data.error || 'Operation failed');
                }
            } catch (err) {
                alert('Connection error. Check console.');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }

        async function deleteAdmin(type, id) {
            if (!confirm(`Are you sure you want to remove this ${type} administrator? This action is permanent.`)) return;

            const action = type === 'super' ? 'delete_super_admin' : 'delete_app_admin';
            try {
                const res = await fetch(`../StegaVault/api/super_management.php?action=${action}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) fetchAdmins();
                else alert(data.error || 'Deletion failed');
            } catch (err) {
                alert('Network error');
            }
        }

        async function logout() {
            await fetch('../StegaVault/api/super_admin_auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.php';
        }

        // Initialize
        fetchAdmins();
    </script>
</body>

</html>