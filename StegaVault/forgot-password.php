<?php

/**
 * StegaVault - Forgot Password
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

// Check for system messages
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <link rel="icon" type="image/png" href="icon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - StegaVault</title>
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
                        <span class="material-symbols-outlined text-[32px]">lock_reset</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Forgot Password</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm">Enter your email address and we'll send you a link to reset your password.</p>
                </div>

                <div id="alertContainer" class="hidden mb-6 p-4 rounded-xl flex items-start gap-3 text-sm">
                    <span id="alertIcon" class="material-symbols-outlined text-lg shrink-0 mt-0.5"></span>
                    <p id="alertMessage" class="leading-relaxed"></p>
                </div>

                <form id="forgotForm" class="space-y-5" onsubmit="submitForm(event)">
                    <div id="emailGroup">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Email Address</label>
                        <div class="relative group">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400 group-focus-within:text-primary transition-colors">mail</span>
                            <input type="email" id="email" required
                                class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                    </div>

                    <button type="submit" id="submitBtn"
                        class="w-full py-3.5 px-4 bg-primary hover:bg-primary-hover text-white rounded-xl font-bold shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                        <span>Send Reset Link</span>
                        <span class="material-symbols-outlined text-[20px]">send</span>
                    </button>

                    <div class="text-center mt-6">
                        <a href="login.php" class="text-sm font-semibold text-slate-500 hover:text-primary dark:text-slate-400 dark:hover:text-primary transition-colors inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                            Back to sign in
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Check exact parameter matches from redirect
        const urlParams = new URLSearchParams(window.location.search);

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

                // Hide the form on success
                document.getElementById('forgotForm').style.display = 'none';
            }

            msg.textContent = message;
        }

        async function submitForm(e) {
            e.preventDefault();

            const btn = document.getElementById('submitBtn');
            const originalBtnHtml = btn.innerHTML;

            btn.innerHTML = '<span class="material-symbols-outlined animate-spin align-middle mr-2">progress_activity</span> Sending...';
            btn.disabled = true;
            btn.classList.add('opacity-80', 'cursor-not-allowed');

            document.getElementById('alertContainer').classList.add('hidden');

            const formData = new FormData();
            formData.append('email', document.getElementById('email').value);

            try {
                const response = await fetch('api/forgot-password.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
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