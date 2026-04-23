<?php
/**
 * StegaVault - Super Admin MFA Verification
 * File: OwlOps/mfa-verify.php
 */

session_start();

// Must have a pending super admin MFA challenge
if (!isset($_SESSION['pending_mfa_user_id']) || !isset($_SESSION['pending_mfa_portal']) || $_SESSION['pending_mfa_portal'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MFA Verification - OwlOps</title>
    <script>if(localStorage.getItem('owlops-theme')==='dark')document.documentElement.classList.add('dark');</script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#2563eb",
                        "background-light": "#ffffff",
                        "card-light": "#f8fafc",
                        "background-dark": "#000000",
                        "slate-card": "#111111",
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"]
                    },
                    boxShadow: {
                        'glow': '0 0 15px -3px rgba(37, 99, 235, 0.2)',
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Space Grotesk', sans-serif; }
        html.dark body { background-color: #000000; }
        .bg-grid-pattern {
            background-image: radial-gradient(#cbd5e1 0.5px, transparent 0.5px);
            background-size: 24px 24px;
        }
        html.dark .bg-grid-pattern {
            background-image: radial-gradient(rgba(255,255,255,0.12) 0.5px, transparent 0.5px);
        }
    </style>
</head>

<body class="bg-background-light dark:bg-black min-h-screen flex flex-col font-display text-slate-900 dark:text-slate-100">
    <!-- Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/3 rounded-full blur-[120px]"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 w-full px-6 py-6 lg:px-12 flex items-center justify-between border-b border-slate-200 dark:border-white/10 bg-background-light/50 dark:bg-black/50 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <img src="OwlOps.png" alt="OwlOps Logo" class="h-10 w-auto">
            <h2 class="text-slate-900 dark:text-white text-xl font-bold tracking-tight">OwlOps <span class="text-slate-600 dark:text-slate-400 font-medium">Super Admin</span></h2>
        </div>
        <button onclick="toggleTheme()" class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors" title="Toggle theme">
            <span class="material-symbols-outlined text-[20px]" id="themeIcon">dark_mode</span>
        </button>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-1 flex flex-col items-center justify-center px-4 py-12">
        <div class="container mx-auto max-w-[420px]">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center size-16 bg-primary/10 rounded-full mb-4 ring-4 ring-primary/10">
                    <span class="material-symbols-outlined text-3xl text-primary">phonelink_lock</span>
                </div>
                <h1 class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight mb-2">Multi-Factor Auth</h1>
                <p class="text-slate-600 dark:text-slate-400 text-sm">Enter the 6-digit code from your Authenticator app, or a recovery code.</p>
            </div>

            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-primary/20 to-slate-400/10 rounded-2xl blur opacity-30"></div>
                <div class="relative bg-white dark:bg-[#111111] border border-slate-200 dark:border-white/10 rounded-2xl p-8 shadow-xl">
                    <div id="errorMsg" style="display:none;" class="mb-4 flex items-center gap-3 px-4 py-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400 rounded-xl text-sm"></div>

                    <!-- TOTP Form -->
                    <form id="mfaForm" class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-center text-sm font-medium text-slate-700 dark:text-slate-300">6-Digit Authenticator Code</label>
                            <input type="text" id="mfaCode" name="code" required autofocus autocomplete="one-time-code"
                                maxlength="6" inputmode="numeric" pattern="\d{6}"
                                class="w-full text-center tracking-[0.5em] text-2xl py-4 rounded-xl bg-slate-50 dark:bg-[#111111] border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                                placeholder="000000" />
                            <p class="text-center text-xs text-slate-500">Enter the code from your authenticator app</p>
                        </div>
                        <button id="submitBtn" type="submit"
                            class="w-full py-4 bg-primary hover:bg-blue-700 text-white rounded-xl font-bold text-base transition-all shadow-glow flex items-center justify-center gap-2 group">
                            <span id="submitText">Verify Identity</span>
                            <span class="material-symbols-outlined text-xl group-hover:translate-x-1 transition-transform">verified_user</span>
                        </button>
                    </form>

                    <!-- Recovery Code Form (hidden by default) -->
                    <form id="recoveryForm" style="display:none;" class="space-y-4">
                        <div class="space-y-2">
                            <label class="block text-center text-sm font-medium text-slate-700 dark:text-slate-300">Recovery Code</label>
                            <input type="text" id="recoveryCode" name="recovery_code" autocomplete="off" maxlength="17"
                                class="w-full text-center tracking-widest text-lg py-4 rounded-xl bg-slate-50 dark:bg-[#111111] border border-slate-300 dark:border-white/10 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none uppercase"
                                placeholder="XXXXXXXX-XXXXXXXX" />
                            <p class="text-center text-xs text-slate-500">Enter one of the recovery codes saved when you set up MFA</p>
                        </div>
                        <button id="recoverySubmitBtn" type="submit"
                            class="w-full py-4 bg-primary hover:bg-blue-700 text-white rounded-xl font-bold text-base transition-all shadow-glow flex items-center justify-center gap-2 group">
                            <span id="recoverySubmitText">Use Recovery Code</span>
                            <span class="material-symbols-outlined text-xl group-hover:translate-x-1 transition-transform">key</span>
                        </button>
                    </form>

                    <!-- Toggle between modes -->
                    <div class="mt-5 text-center border-t border-slate-200 dark:border-white/10 pt-5">
                        <button id="toggleRecoveryBtn" type="button" onclick="toggleRecoveryMode()"
                            class="text-xs text-slate-500 hover:text-primary transition-colors">
                            Lost access to your app? <span id="toggleLabel" class="font-semibold text-primary">Use a recovery code →</span>
                        </button>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="login.php" class="text-xs text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">Cancel &amp; Return to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 w-full px-6 py-8 flex flex-col md:flex-row items-center justify-between gap-4 border-t border-slate-200 dark:border-white/5 text-[12px] text-slate-500">
        <p>© <?php echo date('Y'); ?> StegaVault. Global Administration System.</p>
    </footer>

    <script>
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('owlops-theme', isDark ? 'dark' : 'light');
            document.getElementById('themeIcon').textContent = isDark ? 'light_mode' : 'dark_mode';
        }
        document.addEventListener('DOMContentLoaded', function() {
            const icon = document.getElementById('themeIcon');
            if (icon) icon.textContent = document.documentElement.classList.contains('dark') ? 'light_mode' : 'dark_mode';
        });
        const form              = document.getElementById('mfaForm');
        const codeInput         = document.getElementById('mfaCode');
        const submitBtn         = document.getElementById('submitBtn');
        const submitText        = document.getElementById('submitText');
        const errorMsg          = document.getElementById('errorMsg');

        const recoveryForm      = document.getElementById('recoveryForm');
        const recoveryCodeInput = document.getElementById('recoveryCode');
        const recoverySubmitBtn = document.getElementById('recoverySubmitBtn');
        const recoverySubmitText = document.getElementById('recoverySubmitText');
        const toggleLabel       = document.getElementById('toggleLabel');

        let recoveryMode = false;

        function toggleRecoveryMode() {
            recoveryMode = !recoveryMode;
            errorMsg.style.display = 'none';
            if (recoveryMode) {
                form.style.display = 'none';
                recoveryForm.style.display = 'block';
                toggleLabel.textContent = 'Use authenticator code instead →';
                recoveryCodeInput.focus();
            } else {
                recoveryForm.style.display = 'none';
                form.style.display = 'block';
                toggleLabel.textContent = 'Use a recovery code →';
                codeInput.focus();
            }
        }

        // Auto-format recovery code: uppercase, insert dash after 8 chars
        recoveryCodeInput.addEventListener('input', function () {
            let val = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            if (val.length > 8) val = val.slice(0, 8) + '-' + val.slice(8, 16);
            this.value = val;
        });

        // TOTP: digits only, auto-submit at 6
        codeInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) form.dispatchEvent(new Event('submit'));
        });

        // TOTP submit
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.style.display = 'none';

            const code = codeInput.value.trim();
            if (code.length !== 6) {
                errorMsg.textContent = 'Please enter a 6-digit code.';
                errorMsg.style.display = 'block';
                return;
            }

            submitBtn.disabled = true;
            submitText.textContent = 'Verifying...';

            try {
                const response = await fetch('../StegaVault/api/super_admin_mfa.php?action=verify_login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    errorMsg.textContent = data.error || 'Invalid code. Please try again.';
                    errorMsg.style.display = 'block';
                    submitBtn.disabled = false;
                    submitText.textContent = 'Verify Identity';
                    codeInput.value = '';
                    codeInput.focus();
                }
            } catch (error) {
                errorMsg.textContent = 'Connection error. Please try again.';
                errorMsg.style.display = 'block';
                submitBtn.disabled = false;
                submitText.textContent = 'Verify Identity';
            }
        });

        // Recovery code submit
        recoveryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.style.display = 'none';

            const recovery_code = recoveryCodeInput.value.trim();
            if (recovery_code.length < 17) {
                errorMsg.textContent = 'Please enter a valid recovery code (XXXXXXXX-XXXXXXXX).';
                errorMsg.style.display = 'block';
                return;
            }

            recoverySubmitBtn.disabled = true;
            recoverySubmitText.textContent = 'Verifying...';

            try {
                const response = await fetch('../StegaVault/api/super_admin_mfa.php?action=verify_recovery_login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ recovery_code })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    errorMsg.textContent = data.error || 'Invalid recovery code. Please try again.';
                    errorMsg.style.display = 'block';
                    recoverySubmitBtn.disabled = false;
                    recoverySubmitText.textContent = 'Use Recovery Code';
                    recoveryCodeInput.value = '';
                    recoveryCodeInput.focus();
                }
            } catch (error) {
                errorMsg.textContent = 'Connection error. Please try again.';
                errorMsg.style.display = 'block';
                recoverySubmitBtn.disabled = false;
                recoverySubmitText.textContent = 'Use Recovery Code';
            }
        });
    </script>
</body>

</html>
