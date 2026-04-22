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
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#2563eb",
                        "background-light": "#ffffff",
                        "slate-card": "#f8fafc",
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
        body {
            font-family: 'Space Grotesk', sans-serif;
        }

        .bg-grid-pattern {
            background-image: radial-gradient(#cbd5e1 0.5px, transparent 0.5px);
            background-size: 24px 24px;
        }
    </style>
</head>

<body class="bg-background-light min-h-screen flex flex-col font-display text-slate-900">
    <!-- Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/3 rounded-full blur-[120px]"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 w-full px-6 py-6 lg:px-12 flex items-center justify-between border-b border-slate-200 bg-background-light/50 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <img src="OwlOps.png" alt="OwlOps Logo" class="h-10 w-auto">
            <h2 class="text-slate-900 text-xl font-bold tracking-tight">OwlOps <span class="text-slate-600 font-medium">Super Admin</span></h2>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-1 flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-[420px]">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center size-16 bg-primary/10 rounded-full mb-4 ring-4 ring-primary/10">
                    <span class="material-symbols-outlined text-3xl text-primary">phonelink_lock</span>
                </div>
                <h1 class="text-slate-900 text-3xl font-bold tracking-tight mb-2">Two-Factor Auth</h1>
                <p class="text-slate-600 text-sm">Enter the 6-digit code from your Authenticator app, or a recovery code.</p>
                        <input type="text" id="mfaCode" name="code" required autofocus autocomplete="one-time-code"
                            maxlength="20"
                            class="w-full text-center tracking-[0.4em] text-2xl py-4 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                            placeholder="000000" />
                        <p class="text-center text-xs text-slate-500">6-digit TOTP code or XXXX-XXXX recovery code</p>
                    </div>

                    <button id="submitBtn" type="submit"
                        class="w-full py-4 bg-primary hover:bg-blue-700 text-white rounded-xl font-bold text-base transition-all shadow-glow flex items-center justify-center gap-2 group">
                        <span id="submitText">Verify Identity</span>
                        <span class="material-symbols-outlined text-xl group-hover:translate-x-1 transition-transform">verified_user</span>
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="login.php" class="text-xs text-slate-500 hover:text-white transition-colors">Cancel &amp; Return to Login</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 w-full px-6 py-8 flex flex-col md:flex-row items-center justify-between gap-4 border-t border-white/5 text-[12px] text-slate-500">
        <p>© <?php echo date('Y'); ?> StegaVault. Global Administration System.</p>
    </footer>

    <script>
        const form = document.getElementById('mfaForm');
        const codeInput = document.getElementById('mfaCode');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const errorMsg = document.getElementById('errorMsg');

        codeInput.addEventListener('input', function () {
            // Strip non-digit/non-dash for recovery codes; allow digits only for TOTP
            this.value = this.value.replace(/[^0-9A-Fa-f\-]/g, '').toUpperCase();
            // Auto-submit when exactly 6 digits typed (TOTP)
            if (/^\d{6}$/.test(this.value)) {
                form.dispatchEvent(new Event('submit'));
            }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.style.display = 'none';

            const code = codeInput.value.trim();
            if (!code) {
                errorMsg.textContent = 'Please enter a verification code.';
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
    </script>
</body>

</html>
