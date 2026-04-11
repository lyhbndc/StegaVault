<?php

/**
 * StegaVault - Super Admin Create Admin
 * File: super_admin/create_admin.php
 */

session_start();
require_once '../StegaVault/includes/db.php';

// Check if user is logged in as Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}

// Check if a web app context is selected
if (!isset($_SESSION['manage_web_app_id'])) {
    header('Location: dashboard.php');
    exit;
}

$webAppId = $_SESSION['manage_web_app_id'];
$webAppName = $_SESSION['manage_web_app_name'];

?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Create Admin - <?php echo htmlspecialchars($webAppName); ?></title>
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
    </style>
</head>

<body class="text-slate-200 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 border-r border-white/5 bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50 shadow-xl shadow-black/50">
        <div class="p-6 flex flex-col h-full gap-8">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-white text-base font-bold leading-tight font-display">Environment Admin</h1>
                    <p class="text-primary text-xs font-semibold mt-1">Super Admin Mode</p>
                </div>
            </div>

            <!-- Context Banner -->
            <div class="px-4 py-3 bg-primary/10 border border-primary/20 rounded-xl relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-primary/10 to-transparent"></div>
                <div class="relative z-10">
                    <p class="text-[10px] text-primary font-bold uppercase tracking-widest mb-1">Active Context</p>
                    <p class="text-white text-sm font-semibold truncate" title="<?php echo htmlspecialchars($webAppName); ?>">
                        <?php echo htmlspecialchars($webAppName); ?>
                    </p>
                </div>
            </div>

            <nav class="flex flex-col gap-2 flex-1 relative z-10">
                <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Management</p>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="app_dashboard.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <p class="text-sm font-medium">Overview</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/20 text-white border border-primary/30 shadow-[inset_0_1px_0_rgba(255,255,255,0.1)]" href="admins.php">
                    <span class="material-symbols-outlined text-[20px] text-primary">admin_panel_settings</span>
                    <p class="text-sm font-medium">App Admins</p>
                </a>

                <div class="mt-8">
                    <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Global</p>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/5 transition-colors" href="dashboard.php">
                        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                        <p class="text-sm font-medium">Exit Environment</p>
                    </a>
                </div>
            </nav>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-10 flex flex-col gap-8 relative overflow-hidden flex items-center justify-center">

        <div class="w-full max-w-2xl">
            <!-- Header -->
            <div class="mb-8">
                <a href="admins.php" class="inline-flex flex items-center gap-1 text-slate-400 hover:text-white text-sm font-medium transition-colors mb-4">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Back to Admins
                </a>
                <h2 class="text-3xl font-bold text-white font-display mb-2">Create Application Admin</h2>
                <p class="text-slate-400">Add a new administrator to govern the <span class="text-white font-medium"><?php echo htmlspecialchars($webAppName); ?></span> environment.</p>
            </div>

            <!-- Form Card -->
            <div class="bg-slate-card border border-white/10 p-8 rounded-2xl shadow-xl shadow-black/20 relative">

                <!-- Notice Banner -->
                <div class="mb-8 p-4 bg-primary/10 border border-primary/20 rounded-xl flex items-start gap-4">
                    <div class="p-2 bg-primary/20 rounded-lg shrink-0">
                        <span class="material-symbols-outlined text-primary">mail</span>
                    </div>
                    <div>
                        <h4 class="text-white text-sm font-bold mb-1">Email Delivery Info</h4>
                        <p class="text-slate-400 text-xs leading-relaxed">The new administrator will automatically receive a welcome email with their temporary credentials and login instructions.</p>
                    </div>
                </div>

                <!-- Error & Success Messages -->
                <div id="errorMsg" style="display: none;" class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm font-medium flex items-center gap-2">
                    <span class="material-symbols-outlined">error</span>
                    <span id="errorText"></span>
                </div>
                <div id="successMsg" style="display: none;" class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm font-medium flex items-center gap-2">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span id="successText"></span>
                </div>

                <form id="createAdminForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Full Name -->
                        <div class="space-y-2">
                            <label class="block text-white text-sm font-medium">Full Name</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">badge</span>
                                <input id="adminName" required class="w-full pl-12 pr-4 py-3 rounded-xl bg-background-dark border border-white/10 text-white placeholder:text-slate-500 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none" placeholder="Jane Doe" type="text" />
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div class="space-y-2">
                            <label class="block text-white text-sm font-medium">Email Address</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">alternate_email</span>
                                <input id="adminEmail" required class="w-full pl-12 pr-4 py-3 rounded-xl bg-background-dark border border-white/10 text-white placeholder:text-slate-500 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none" placeholder="jane@stegavault.com" type="email" />
                            </div>
                        </div>
                    </div>

                    <!-- Password Setup -->
                    <div class="space-y-4 pt-4 border-t border-white/5">
                        <label class="text-white text-sm font-medium flex items-center justify-between">
                            <span>Admin Password</span>
                        </label>

                        <!-- Toggle for manual/auto password -->
                        <div class="flex items-center gap-4 bg-background-dark border border-white/10 rounded-xl p-3">
                            <label class="flex items-center gap-2 cursor-pointer flex-1">
                                <input type="radio" name="passwordMode" value="auto" checked class="text-primary border-slate-600 bg-slate-800 focus:ring-primary focus:ring-offset-background-dark" onchange="togglePasswordMode()">
                                <span class="text-sm text-slate-300 font-medium">Auto-generate password</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer flex-1 border-l border-white/5 pl-4">
                                <input type="radio" name="passwordMode" value="manual" class="text-primary border-slate-600 bg-slate-800 focus:ring-primary focus:ring-offset-background-dark" onchange="togglePasswordMode()">
                                <span class="text-sm text-slate-300 font-medium">Set manual password</span>
                            </label>
                        </div>

                        <!-- Manual Password Input (Hidden default) -->
                        <div id="manualPasswordGroup" style="display: none;" class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">password</span>
                            <input id="adminPassword" class="w-full pl-12 pr-12 py-3 rounded-xl bg-background-dark border border-white/10 text-white placeholder:text-slate-500 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none" placeholder="Strong password..." type="password" />
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                                <span class="material-symbols-outlined text-[20px]" id="visibilityIcon">visibility_off</span>
                            </button>
                        </div>
                    </div>

                    <div class="pt-8 flex justify-end">
                        <button id="submitBtn" type="submit" class="px-8 py-3 bg-primary hover:bg-primary-hover text-black rounded-xl font-bold shadow-glow transition-all flex items-center gap-2 group">
                            <span id="submitText">Create Administrator</span>
                            <span class="material-symbols-outlined text-lg group-hover:block transition-transform">person_add</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function togglePasswordMode() {
            const mode = document.querySelector('input[name="passwordMode"]:checked').value;
            const manualGroup = document.getElementById('manualPasswordGroup');
            const passInput = document.getElementById('adminPassword');

            if (mode === 'manual') {
                manualGroup.style.display = 'block';
                passInput.required = true;
            } else {
                manualGroup.style.display = 'none';
                passInput.required = false;
                passInput.value = ''; // clear
            }
        }

        function togglePasswordVisibility() {
            const passInput = document.getElementById('adminPassword');
            const icon = document.getElementById('visibilityIcon');

            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.textContent = 'visibility';
            } else {
                passInput.type = 'password';
                icon.textContent = 'visibility_off';
            }
        }

        document.getElementById('createAdminForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const name = document.getElementById('adminName').value;
            const email = document.getElementById('adminEmail').value;
            const mode = document.querySelector('input[name="passwordMode"]:checked').value;
            const password = document.getElementById('adminPassword').value;

            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const errorMsg = document.getElementById('errorMsg');
            const errorText = document.getElementById('errorText');
            const successMsg = document.getElementById('successMsg');
            const successText = document.getElementById('successText');

            // Reset states
            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            submitBtn.disabled = true;
            submitText.textContent = 'Creating...';

            try {
                const response = await fetch('../StegaVault/api/super_admin_api.php?action=create_admin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        email,
                        mode,
                        password: mode === 'manual' ? password : ''
                    })
                });

                const data = await response.json();

                if (data.success) {
                    successText.textContent = 'Administrator successfully created! Emal notification sent.';
                    successMsg.style.display = 'flex';
                    document.getElementById('createAdminForm').reset();
                    togglePasswordMode(); // reset back to auto visibility
                } else {
                    errorText.textContent = data.error || 'Failed to create administrator.';
                    errorMsg.style.display = 'flex';
                }
            } catch (error) {
                console.error('Error:', error);
                errorText.textContent = 'Connection error. Could not reach server.';
                errorMsg.style.display = 'flex';
            } finally {
                submitBtn.disabled = false;
                submitText.textContent = 'Create Administrator';
            }
        });
    </script>
</body>

</html>