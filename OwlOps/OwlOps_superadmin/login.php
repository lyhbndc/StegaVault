<?php

/**
 * StegaVault - Super Admin Login
 * File: super_admin/login.php
 */

session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'super_admin') {
        header('Location: dashboard.php');
        exit;
    } else if ($_SESSION['role'] === 'admin') {
        header('Location: ../StegaVault/admin/dashboard.php');
        exit;
    } else {
        header('Location: ../StegaVault/employee/login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Super Admin Login - StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#ffffff", // White for OwlOps
                        "background-dark": "#000000",
                        "slate-card": "#111111",
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"]
                    },
                    boxShadow: {
                        'glow': '0 0 15px -3px rgba(255, 255, 255, 0.3)',
                    }
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
        }

        .bg-grid-pattern {
            background-image: radial-gradient(#ffffff 0.5px, transparent 0.5px);
            background-size: 24px 24px;
        }
    </style>
</head>

<body class="bg-background-dark min-h-screen flex flex-col font-display">
    <!-- Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px]"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 w-full px-6 py-6 lg:px-12 flex items-center justify-between border-b border-white/5 bg-background-dark/50 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <div class="bg-primary p-2 rounded-lg shadow-glow">
                <span class="material-symbols-outlined text-black text-2xl">local_police</span>
            </div>
            <h2 class="text-white text-xl font-bold tracking-tight">OwlOps <span class="text-white/80 font-medium">Super Admin</span></h2>
        </div>
        <div class="flex items-center gap-4">
            <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 border border-primary/20">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                </span>
                <span class="text-[10px] uppercase tracking-widest font-bold text-primary">Global Network Active</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-1 flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[440px]">
            <!-- Hero Visual -->
            <div class="mb-8 relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-primary to-gray-400 rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-1000"></div>
                <div class="relative w-full h-32 rounded-xl overflow-hidden border border-white/10 bg-slate-card">
                    <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-gray-500/10"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="material-symbols-outlined text-6xl text-primary/30">globe</span>
                    </div>
                    <div class="absolute bottom-4 left-4">
                        <span class="text-[10px] text-primary font-bold uppercase tracking-widest px-2 py-0.5 bg-primary/10 border border-primary/20 rounded">Super Admin</span>
                    </div>
                </div>
            </div>

            <!-- Heading -->
            <div class="text-center mb-10">
                <h1 class="text-white text-3xl font-bold tracking-tight mb-2">Super Admin Access</h1>
                <p class="text-slate-400 text-sm">System level administration only</p>
            </div>

            <!-- Login Form Card -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-8 rounded-2xl shadow-2xl">
                <!-- Error Message -->
                <div id="errorMsg" style="display: none;" class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm"></div>

                <form id="authForm" class="space-y-6">
                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label class="block text-white text-sm font-medium">Super Admin Email</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">alternate_email</span>
                            <input id="email" required class="w-full pl-12 pr-4 py-4 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none" placeholder="superadmin@stegavault.com" type="email" />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label class="block text-white text-sm font-medium">Master Password</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">key</span>
                            <input id="password" required class="w-full pl-12 pr-12 py-4 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none" placeholder="••••••••••••" type="password" />
                            <button type="button" onclick="togglePassword()" class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">visibility_off</button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button id="submitBtn" class="w-full py-4 bg-primary hover:bg-white/90 text-black rounded-xl font-bold text-base transition-all shadow-glow flex items-center justify-center gap-2 group" type="submit">
                        <span id="submitText">Access Global Dashboard</span>
                        <span class="material-symbols-outlined text-xl group-hover:translate-x-1 transition-transform">rocket_launch</span>
                    </button>
                </form>

                <!-- Normal Admin Link -->

            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 w-full px-6 py-8 flex items-center justify-center border-t border-white/5 text-[12px] text-slate-500">
        <p>© <?php echo date('Y'); ?> StegaVault. Global Administration System.</p>
    </footer>

    <script>
        function togglePassword() {
            const passInput = document.getElementById('password');
            const icon = event.target;

            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.textContent = 'visibility';
            } else {
                passInput.type = 'password';
                icon.textContent = 'visibility_off';
            }
        }

        document.getElementById('authForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const errorMsg = document.getElementById('errorMsg');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');

            errorMsg.style.display = 'none';

            const formData = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };

            submitBtn.disabled = true;
            submitText.textContent = 'Authenticating...';

            try {
                const response = await fetch(`../StegaVault/api/super_admin_auth.php?action=login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    if (data.data.user.role !== 'super_admin') {
                        errorMsg.innerHTML = '<span class="flex items-center gap-2"><span class="material-symbols-outlined text-lg">gpp_bad</span> Security Alert: Unauthorized role detected. Validating...</span>';
                        errorMsg.style.display = 'block';

                        setTimeout(() => {
                            if (data.data.user.role === 'admin') {
                                window.location.href = '../StegaVault/admin/login.php';
                            } else {
                                window.location.href = '../StegaVault/employee/login.php';
                            }
                        }, 2000);
                    } else {
                        // Success! Redirect to global dashboard
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    errorMsg.textContent = data.error || 'Authentication failed.';
                    errorMsg.style.display = 'block';
                    submitBtn.disabled = false;
                    submitText.textContent = 'Access Global Dashboard';
                }
            } catch (error) {
                console.error('Error:', error);
                errorMsg.textContent = 'System connection error. Please try again.';
                errorMsg.style.display = 'block';
                submitBtn.disabled = false;
                submitText.textContent = 'Access Global Dashboard';
            }
        });
    </script>
</body>

</html>