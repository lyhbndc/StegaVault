<?php

/**
 * StegaVault - Admin Login (Security Design)
 * File: admin/login.php
 */

session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin') {
        header('Location: ../employee/login.php');
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
    <title>Admin Secure Login - StegaVault</title>
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
                        "primary": "#667eea",
                        "background-dark": "#0f172a",
                        "slate-card": "#1e293b",
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"]
                    },
                    boxShadow: {
                        'glow': '0 0 15px -3px rgba(102, 126, 234, 0.5)',
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
                    class="absolute -inset-1 bg-gradient-to-r from-primary to-blue-400 rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-1000">
                </div>
                <div class="relative w-full h-32 rounded-xl overflow-hidden border border-white/10 bg-slate-card">
                    <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-purple-500/10"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="material-symbols-outlined text-6xl text-primary/30">admin_panel_settings</span>
                    </div>
                    <div class="absolute bottom-4 left-4">
                        <span
                            class="text-[10px] text-primary font-bold uppercase tracking-widest px-2 py-0.5 bg-primary/10 border border-primary/20 rounded">Admin
                            Access</span>
                    </div>
                </div>
            </div>

            <!-- Heading -->
            <div class="text-center mb-10">
                <h1 class="text-white text-3xl font-bold tracking-tight mb-2" id="pageTitle">Admin Login</h1>
                <p class="text-slate-400 text-sm">Authorized Personnel Only</p>
            </div>

            <!-- Login Form Card -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-8 rounded-2xl shadow-2xl">
                <!-- Error Message -->
                <div id="errorMsg" style="display: none;"
                    class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm"></div>

                <form id="authForm" class="space-y-6">
                    <!-- Name Field (hidden by default) -->
                    <div id="nameField" style="display: none;" class="space-y-2">
                        <label class="block text-white text-sm font-medium">Full Name</label>
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">person</span>
                            <input id="name"
                                class="w-full pl-12 pr-4 py-4 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                                placeholder="John Doe" type="text" />
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label class="block text-white text-sm font-medium">Admin Email</label>
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">alternate_email</span>
                            <input id="email" required
                                class="w-full pl-12 pr-4 py-4 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                                placeholder="admin@company.com" type="email" />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label class="block text-white text-sm font-medium">Password</label>
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">lock</span>
                            <input id="password" required
                                class="w-full pl-12 pr-12 py-4 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                                placeholder="••••••••" type="password" oninput="checkPwPolicy()" />
                            <button type="button" onclick="togglePassword()"
                                class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">visibility_off</button>
                        </div>

                        <!-- Password Policy Panel (shown only during registration) -->
                        <div id="pwPolicyPanel"
                            class="hidden mt-2 p-4 bg-white/5 border border-white/10 rounded-xl space-y-2">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Password
                                Requirements</p>

                            <!-- Strength bar -->
                            <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden mb-2">
                                <div id="pwStrengthBar"
                                    class="h-full rounded-full transition-all duration-300 w-0 bg-red-500"></div>
                            </div>

                            <div id="pwRule-len"
                                class="flex items-center gap-2 text-xs text-slate-500 transition-colors"><span
                                    class="material-symbols-outlined text-[14px]">radio_button_unchecked</span> 12–25
                                characters</div>
                            <div id="pwRule-upper"
                                class="flex items-center gap-2 text-xs text-slate-500 transition-colors"><span
                                    class="material-symbols-outlined text-[14px]">radio_button_unchecked</span> At least
                                one uppercase letter</div>
                            <div id="pwRule-lower"
                                class="flex items-center gap-2 text-xs text-slate-500 transition-colors"><span
                                    class="material-symbols-outlined text-[14px]">radio_button_unchecked</span> At least
                                one lowercase letter</div>
                            <div id="pwRule-num"
                                class="flex items-center gap-2 text-xs text-slate-500 transition-colors"><span
                                    class="material-symbols-outlined text-[14px]">radio_button_unchecked</span> At least
                                one number</div>
                            <div id="pwRule-sym"
                                class="flex items-center gap-2 text-xs text-slate-500 transition-colors"><span
                                    class="material-symbols-outlined text-[14px]">radio_button_unchecked</span> At least
                                one special character (!@#$%…)</div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button id="submitBtn"
                        class="w-full py-4 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold text-base transition-all shadow-glow flex items-center justify-center gap-2 group"
                        type="submit">
                        <span id="submitText">Sign In to Dashboard</span>
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

                <!-- Employee Login Link -->
                <div class="mt-6 text-center">
                    <a href="../employee/login.php"
                        class="text-xs text-slate-500 hover:text-primary transition-colors flex items-center justify-center gap-1">
                        Are you an employee? <span class="font-semibold">Employee Login →</span>
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
        <p>© <?php echo date('Y'); ?> Peanut Gallery Media Inc. All rights reserved.</p>
        <div class="flex items-center gap-6">
            <a href="privacy-policy.php" class="hover:text-primary transition-colors">Privacy Policy</a>
            <a href="terms-of-service.php" class="hover:text-primary transition-colors">Terms of Service</a>
        </div>
    </footer>

    <script>
        let isLogin = true;

        function toggleMode(e) {
            e.preventDefault();
            isLogin = !isLogin;

            const pageTitle = document.getElementById('pageTitle');
            const submitText = document.getElementById('submitText');
            const nameField = document.getElementById('nameField');
            const toggleText = document.getElementById('toggleText');
            const toggleLink = document.getElementById('toggleLink');
            const errorMsg = document.getElementById('errorMsg');

            if (isLogin) {
                pageTitle.textContent = 'Admin Login';
                submitText.textContent = 'Sign In to Dashboard';
                nameField.style.display = 'none';
                toggleText.textContent = 'Need to create admin account?';
                toggleLink.textContent = 'Register';
                document.getElementById('pwPolicyPanel').classList.add('hidden');
                document.getElementById('password').removeAttribute('oninput');
                document.getElementById('password').removeAttribute('minlength');
                document.getElementById('password').removeAttribute('maxlength');
            } else {
                pageTitle.textContent = 'Admin Registration';
                submitText.textContent = 'Create Admin Account';
                nameField.style.display = 'block';
                toggleText.textContent = 'Already have an account?';
                toggleLink.textContent = 'Login';
                document.getElementById('pwPolicyPanel').classList.remove('hidden');
                document.getElementById('password').setAttribute('oninput', 'checkPwPolicy()');
                document.getElementById('password').setAttribute('minlength', '12');
                document.getElementById('password').setAttribute('maxlength', '25');
            }

            errorMsg.style.display = 'none';
            document.getElementById('authForm').reset();
        }

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
                password: document.getElementById('password').value,
                portal: 'admin'
            };

            if (!isLogin) {
                formData.name = document.getElementById('name').value;
                if (!formData.name) {
                    errorMsg.textContent = 'Name is required';
                    errorMsg.style.display = 'block';
                    return;
                }
            }

            const action = isLogin ? 'login' : 'register';
            submitBtn.disabled = true;
            submitText.textContent = 'Processing...';

            try {
                const response = await fetch(`../api/auth.php?action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    if (data.data && data.data.require_mfa) {
                        window.location.href = '../mfa-verify.php?redirect=admin/dashboard.php';
                        return;
                    }

                    if (!data.data || !data.data.user) {
                        throw new Error('Unexpected login response');
                    }

                    // Check if user is admin
                    if (data.data.user.role !== 'admin' && data.data.user.role !== 'super_admin') {
                        errorMsg.textContent = 'This login is for administrators only. Redirecting to employee login...';
                        errorMsg.style.display = 'block';
                        setTimeout(() => {
                            window.location.href = '../employee/login.php';
                        }, 2000);
                    } else if (data.data.user.role === 'super_admin') {
                        errorMsg.textContent = 'Super Admins should use the Global Admin Dashboard';
                        errorMsg.style.display = 'block';
                        submitBtn.disabled = false;
                        submitText.textContent = isLogin ? 'Sign In to Dashboard' : 'Create Admin Account';

                        // Optionally redirect to super admin dashboard
                        setTimeout(() => {
                            window.location.href = '../../OwlOps_superadmin/login.php';
                        }, 2000);
                    } else {
                        // Admin login successful
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    errorMsg.textContent = data.error || 'Login failed';
                    errorMsg.style.display = 'block';
                    submitBtn.disabled = false;
                    submitText.textContent = isLogin ? 'Sign In to Dashboard' : 'Create Admin Account';
                }
            } catch (error) {
                console.error('Error:', error);
                errorMsg.textContent = 'Connection error. Please try again.';
                errorMsg.style.display = 'block';
                submitBtn.disabled = false;
                submitText.textContent = isLogin ? 'Sign In to Dashboard' : 'Create Admin Account';
            }
        });
        // ── Password Policy Live Checker ─────────────────────────
        const rules = [{
            id: 'pwRule-len',
            test: p => p.length >= 12 && p.length <= 25
        },
        {
            id: 'pwRule-upper',
            test: p => /[A-Z]/.test(p)
        },
        {
            id: 'pwRule-lower',
            test: p => /[a-z]/.test(p)
        },
        {
            id: 'pwRule-num',
            test: p => /[0-9]/.test(p)
        },
        {
            id: 'pwRule-sym',
            test: p => /[\W_]/.test(p)
        },
        ];
        const strengthColors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-yellow-400', 'bg-emerald-500'];
        const strengthWidths = ['w-1/5', 'w-2/5', 'w-3/5', 'w-4/5', 'w-full'];

        function checkPwPolicy() {
            const p = document.getElementById('password').value;
            let passed = 0;
            rules.forEach(r => {
                const ok = r.test(p);
                const el = document.getElementById(r.id);
                if (ok) {
                    passed++;
                    el.className = 'flex items-center gap-2 text-xs text-emerald-400 transition-colors';
                    el.querySelector('span').textContent = 'check_circle';
                } else {
                    el.className = 'flex items-center gap-2 text-xs text-slate-500 transition-colors';
                    el.querySelector('span').textContent = 'radio_button_unchecked';
                }
            });
            const bar = document.getElementById('pwStrengthBar');
            // Remove all width/color classes
            bar.className = 'h-full rounded-full transition-all duration-300 ' +
                strengthWidths[passed - 1 < 0 ? 0 : passed - 1] + ' ' +
                strengthColors[passed - 1 < 0 ? 0 : passed - 1];
            if (passed === 0) bar.className = 'h-full rounded-full transition-all duration-300 w-0 bg-red-500';
        }
        // ─────────────────────────────────────────────────────────
    </script>
</body>

</html>