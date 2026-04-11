<?php

/**
 * StegaVault - Reset Password
 */

session_start();
require_once 'includes/config.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: employee/dashboard.php');
    }
    exit;
}

$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <link rel="icon" type="image/png" href="Assets/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - StegaVault</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    },
                    colors: {
                        primary: {
                            DEFAULT: '#3b82f6',
                            hover: '#2563eb',
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .dark .glass-panel {
            background: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        body {
            background-image:
                radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.08), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(99, 102, 241, 0.08), transparent 25%);
            background-color: #f8fafc;
        }

        .dark body {
            background-image:
                radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.05), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(99, 102, 241, 0.05), transparent 25%);
            background-color: #0f172a;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 transition-colors duration-300">

    <!-- Theme Toggle -->
    <button id="themeToggle" class="fixed top-6 right-6 p-2 rounded-xl bg-white/10 text-slate-500 dark:text-slate-400 hover:bg-white border border-slate-200 dark:border-slate-800 dark:hover:bg-slate-800 transition-all shadow-sm">
        <span class="material-symbols-outlined dark:hidden">dark_mode</span>
        <span class="material-symbols-outlined hidden dark:block text-amber-400">light_mode</span>
    </button>

    <div class="w-full max-w-md animate-[fadeIn_0.5s_ease-out]">
        <!-- Form Container -->
        <div class="glass-panel rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">
            <!-- Decorative gradient blur -->
            <div class="absolute -top-20 -right-20 w-40 h-40 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center size-16 rounded-2xl bg-primary/10 text-primary mb-4 shadow-inner">
                        <span class="material-symbols-outlined text-[32px]">key</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Create New Password</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm">Please set a new, strong password below.</p>
                </div>

                <div id="alertContainer" class="hidden mb-6 p-4 rounded-xl flex items-start gap-3 text-sm">
                    <span id="alertIcon" class="material-symbols-outlined text-lg shrink-0 mt-0.5"></span>
                    <p id="alertMessage" class="leading-relaxed"></p>
                </div>

                <?php if (empty($token)): ?>

                    <div class="bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400 p-4 rounded-xl flex items-start gap-3 text-sm mb-6">
                        <span class="material-symbols-outlined text-lg shrink-0 mt-0.5">error</span>
                        <p class="leading-relaxed">Invalid reset link. The reset token is missing.</p>
                    </div>
                    <div class="text-center">
                        <a href="forgot-password.php" class="text-sm font-semibold text-primary hover:text-primary-hover transition-colors inline-flex items-center gap-1">
                            Request a new link
                            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </a>
                    </div>

                <?php else: ?>

                    <form id="resetForm" class="space-y-5" onsubmit="submitForm(event)">
                        <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">New Password</label>
                            <div class="relative group">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400 group-focus-within:text-primary transition-colors">lock</span>
                                <input type="password" id="password" required minlength="8"
                                    class="w-full pl-11 pr-10 py-3 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <button type="button" class="toggle-password absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors focus:outline-none" data-target="password">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Confirm New Password</label>
                            <div class="relative group">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400 group-focus-within:text-primary transition-colors">lock</span>
                                <input type="password" id="confirm_password" required minlength="8"
                                    class="w-full pl-11 pr-10 py-3 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <button type="button" class="toggle-password absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors focus:outline-none" data-target="confirm_password">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </button>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn"
                            class="w-full py-3.5 px-4 bg-primary hover:bg-primary-hover text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                            <span>Reset Password</span>
                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                        </button>

                        <div id="loginLinkContainer" class="text-center mt-6 hidden">
                            <a href="login.php" class="text-sm font-semibold text-primary hover:text-primary-hover transition-colors inline-flex items-center gap-1">
                                Go to Login
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Theme Management
        function initTheme() {
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        document.getElementById('themeToggle').addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        });

        initTheme();

        // Password Visibility Toggle
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = document.getElementById(this.dataset.target);
                const icon = this.querySelector('span');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.textContent = 'visibility_off';
                } else {
                    input.type = 'password';
                    icon.textContent = 'visibility';
                }
            });
        });

        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const icon = document.getElementById('alertIcon');
            const msg = document.getElementById('alertMessage');

            container.classList.remove('hidden', 'bg-red-50', 'text-red-600', 'dark:bg-red-500/10', 'dark:text-red-400',
                'bg-emerald-50', 'text-emerald-600', 'dark:bg-emerald-500/10', 'dark:text-emerald-400');

            if (type === 'error') {
                container.classList.add('bg-red-50', 'text-red-600', 'dark:bg-red-500/10', 'dark:text-red-400');
                icon.textContent = 'error';
            } else {
                container.classList.add('bg-emerald-50', 'text-emerald-600', 'dark:bg-emerald-500/10', 'dark:text-emerald-400');
                icon.textContent = 'check_circle';

                // Show login link on success
                document.getElementById('loginLinkContainer').classList.remove('hidden');
                document.getElementById('submitBtn').classList.add('hidden');
            }

            msg.textContent = message;
        }

        async function submitForm(e) {
            e.preventDefault();

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                showAlert('Passwords do not match.', 'error');
                return;
            }

            if (password.length < 8) {
                showAlert('Password must be at least 8 characters.', 'error');
                return;
            }

            const btn = document.getElementById('submitBtn');
            const originalBtnHtml = btn.innerHTML;

            btn.innerHTML = '<span class="material-symbols-outlined animate-spin align-middle mr-2">progress_activity</span> Resetting...';
            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-not-allowed');

            document.getElementById('alertContainer').classList.add('hidden');

            const formData = new FormData();
            formData.append('token', document.getElementById('token').value);
            formData.append('password', password);
            formData.append('confirm_password', confirmPassword);

            try {
                const response = await fetch('api/reset-password.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    // We don't hide the form entirely, just the button, so they can still see it if they want
                    document.getElementById('password').disabled = true;
                    document.getElementById('confirm_password').disabled = true;
                } else {
                    showAlert(data.message || 'An error occurred. Please try again.', 'error');
                }
            } catch (error) {
                showAlert('Network error. Please check your connection and try again.', 'error');
            } finally {
                if (!document.getElementById('alertContainer').classList.contains('bg-emerald-50')) {
                    btn.innerHTML = originalBtnHtml;
                    btn.disabled = false;
                    btn.classList.remove('opacity-80', 'cursor-not-allowed');
                }
            }
        }
    </script>
</body>

</html>