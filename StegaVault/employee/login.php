<?php

/**
 * StegaVault - Employee Login Page
 * File: employee/login.php
 */

session_start();
require_once '../includes/config.php';

// If already logged in, redirect to employee dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/login.php');
        exit;
    } else if ($_SESSION['role'] === 'super_admin') {
        header('Location: ../../OwlOps_superadmin/dashboard.php');
        exit;
    }
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Employee Login - StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#10b981",
                        "background-dark": "#0f172a",
                        "slate-card": "#1e293b",
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"]
                    },
                    boxShadow: {
                        'glow': '0 0 15px -3px rgba(16, 185, 129, 0.5)',
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
            background-image: radial-gradient(#667eea 0.5px, transparent 0.5px);
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
    <header
        class="relative z-10 w-full px-6 py-6 lg:px-12 flex items-center justify-between border-b border-white/5 bg-background-dark/50 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <div class="bg-primary p-2 rounded-lg shadow-glow">
                <span class="material-symbols-outlined text-white text-2xl">shield</span>
            </div>
            <h2 class="text-white text-xl font-bold tracking-tight">Peanut Gallery Media <span
                    class="text-primary/80 font-medium">Inc.</span></h2>
        </div>
        <div class="flex items-center gap-4">
            <div
                class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20">
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-[10px] uppercase tracking-widest font-bold text-emerald-500">Systems Nominal</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-1 flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[440px]">
            <!-- Hero Visual -->
            <div class="mb-8 relative group">
                <div
                    class="absolute -inset-1 bg-gradient-to-r from-primary to-teal-400 rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-1000">
                </div>
                <div class="relative w-full h-32 rounded-xl overflow-hidden border border-white/10 bg-slate-card">
                    <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-teal-500/10"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="material-symbols-outlined text-6xl text-primary/30">person</span>
                    </div>
                    <div class="absolute bottom-4 left-4">
                        <span
                            class="text-[10px] text-primary font-bold uppercase tracking-widest px-2 py-0.5 bg-primary/10 border border-primary/20 rounded">Employee
                            Portal</span>
                    </div>
                </div>
            </div>

            <!-- Heading -->
            <div class="text-center mb-10">
                <h1 class="text-white text-3xl font-bold tracking-tight mb-2">Employee Login</h1>
                <p class="text-slate-400 text-sm">StegaVault — Employee Portal</p>
            </div>

            <!-- Login Form Card -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-8 rounded-2xl shadow-2xl">
                <!-- Error Message -->
                <div id="errorMsg" style="display: none;"
                    class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm"></div>

                <form id="loginForm" class="space-y-6">
                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label class="block text-white text-sm font-medium">Email Address</label>
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">alternate_email</span>
                            <input type="email" id="email" name="email" required autofocus
                                class="w-full pl-12 pr-4 py-4 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                                placeholder="your@email.com" />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label class="block text-white text-sm font-medium">Password</label>
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">lock</span>
                            <input type="password" id="password" name="password" required
                                class="w-full pl-12 pr-12 py-4 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                                placeholder="••••••••" />
                            <button type="button" onclick="togglePassword()"
                                class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">visibility_off</button>
                        </div>

                    </div>

                    <!-- Submit Button -->
                    <button id="submitBtn" type="submit"
                        class="w-full py-4 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold text-base transition-all shadow-glow flex items-center justify-center gap-2 group">
                        <span id="submitText">Login to Portal</span>
                        <span
                            class="material-symbols-outlined text-xl group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </button>
                </form>

                <!-- Footer Info -->
                <div class="mt-8 pt-8 border-t border-white/5">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">verified_user</span>
                            AES-256 Encrypted
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">vpn_lock</span>
                            Secure Connection
                        </span>
                    </div>
                </div>

                <!-- Demo Note -->
                <div class="mt-4 text-center">
                    <p class="text-xs text-slate-500 flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-sm">info</span>
                        Employee credentials provided by admin
                    </p>
                </div>

                <!-- Admin Login Link -->
                <div class="mt-6 text-center">
                    <a href="../collaborator/login.php"
                        class="text-xs text-slate-500 hover:text-primary transition-colors flex items-center justify-center gap-1">
                        Are you a Collaborator? <span class="font-semibold">Collaborator Login →</span>
                    </a>
                </div>
            </div>

            <!-- Compliance Notice -->
            <div class="mt-12 text-center max-w-sm mx-auto">
                <div
                    class="inline-flex items-center justify-center px-3 py-1 bg-red-500/10 border border-red-500/20 rounded-full mb-4">
                    <span class="text-[10px] text-red-500 font-bold uppercase tracking-wider flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">warning</span> Restricted Access
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed uppercase tracking-widest opacity-60">
                    This system is for authorized personnel only. All activities are logged and monitored.
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer
        class="relative z-10 w-full px-6 py-8 flex flex-col md:flex-row items-center justify-between gap-4 border-t border-white/5 text-[12px] text-slate-500">
        <p>© 2024 StegaVault Systems. All rights reserved.</p>
        <div class="flex items-center gap-6">
            <a href="privacy-policy.php" class="hover:text-primary transition-colors">Privacy Policy</a>
            <a href="terms-of-service.php" class="hover:text-primary transition-colors">Terms of Service</a>
        </div>
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

        const form = document.getElementById('loginForm');
        const errorMsg = document.getElementById('errorMsg');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.style.display = 'none';

            const formData = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                portal: 'employee'
            };

            submitBtn.disabled = true;
            submitText.textContent = 'Logging in...';

            try {
                const response = await fetch('../api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    if (data.data && data.data.require_mfa) {
                        window.location.href = '../mfa-verify.php?redirect=employee/dashboard.php';
                        return;
                    }

                    if (!data.data || !data.data.user) {
                        throw new Error('Unexpected login response');
                    }

                    if (data.data.user.role === 'admin') {
                        errorMsg.textContent = 'Admins should use the Admin Login page';
                        errorMsg.style.display = 'block';
                        submitBtn.disabled = false;
                        submitText.textContent = 'Login to Portal';

                        // Optionally redirect to admin login
                        setTimeout(() => {
                            window.location.href = '../admin/login.php';
                        }, 2000);
                    } else if (data.data.user.role === 'super_admin') {
                        errorMsg.textContent = 'Super Admins should use the Global Admin Dashboard';
                        errorMsg.style.display = 'block';
                        submitBtn.disabled = false;
                        submitText.textContent = 'Login to Portal';

                        // Optionally redirect to super admin dashboard
                        setTimeout(() => {
                            window.location.href = '../../OwlOps_superadmin/login.php';
                        }, 2000);
                    } else {
                        // Employee login successful
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    errorMsg.textContent = data.error || 'Invalid credentials';
                    errorMsg.style.display = 'block';
                    submitBtn.disabled = false;
                    submitText.textContent = 'Login to Portal';
                }
            } catch (error) {
                console.error('Error:', error);
                errorMsg.textContent = 'Connection error. Please try again.';
                errorMsg.style.display = 'block';
                submitBtn.disabled = false;
                submitText.textContent = 'Login to Portal';
            }
        });
    </script>
</body>

</html>