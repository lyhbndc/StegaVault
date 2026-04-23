<?php
/**
 * StegaVault - MFA Verification Interface
 * File: mfa-verify.php
 */

session_start();
require_once 'includes/db.php';

// Check if there is a pending MFA session
if (!isset($_SESSION['pending_mfa_user_id'])) {
    header('Location: index.html');
    exit;
}

$portal = $_SESSION['pending_mfa_portal'] ?? 'employee';
$redirectStr = '';

if ($portal === 'admin') {
    $redirectStr = 'admin/dashboard.php';
} elseif ($portal === 'collaborator') {
    $redirectStr = 'collaborator/dashboard.php';
} else {
    $redirectStr = 'employee/dashboard.php';
}

?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <link rel="icon" type="image/png" href="icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MFA Verification - StegaVault</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
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
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Space Grotesk', sans-serif; }

        /* Light mode overrides */
        html:not(.dark) body                { background-color: #f1f5f9 !important; }
        html:not(.dark) .bg-background-dark { background-color: #f1f5f9 !important; }
        html:not(.dark) .bg-white\/5        { background-color: #ffffff !important; border-color: rgba(0,0,0,0.1) !important; box-shadow: 0 4px 24px rgba(0,0,0,0.06) !important; }
        html:not(.dark) .border-white\/10   { border-color: rgba(0,0,0,0.1) !important; }
        html:not(.dark) .text-white         { color: #1e293b !important; }
        html:not(.dark) .text-slate-400     { color: #64748b !important; }
        html:not(.dark) .text-slate-500     { color: #94a3b8 !important; }
        html:not(.dark) .bg-\[#1b1f27\]    { background-color: #f8fafc !important; }
        html:not(.dark) .border-\[#3b4354\] { border-color: #cbd5e1 !important; }
        html:not(.dark) input               { color: #1e293b !important; }
        html:not(.dark) input::placeholder  { color: #94a3b8 !important; }
    </style>
</head>
<body class="bg-background-dark min-h-screen flex flex-col items-center justify-center font-display px-4">
    <!-- Theme toggle (fixed top-right) -->
    <button id="themeToggle" onclick="toggleTheme()"
        class="fixed top-5 right-5 z-50 w-9 h-9 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
        <span id="themeIcon" class="material-symbols-outlined text-[18px]">light_mode</span>
    </button>
    <div class="w-full max-w-[400px]">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center size-16 bg-primary/20 rounded-full mb-4 ring-4 ring-primary/10">
                <span class="material-symbols-outlined text-3xl text-primary">phonelink_lock</span>
            </div>
            <h1 class="text-white text-2xl font-bold tracking-tight mb-2">Multi-Factor Auth</h1>
            <p class="text-slate-400 text-sm">Enter the code from your Authenticator app.</p>
        </div>

        <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-8 rounded-2xl shadow-2xl">
            <div id="errorMsg" style="display: none;" class="mb-6 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm text-center"></div>

            <!-- TOTP Form -->
            <form id="mfaForm" class="space-y-6">
                <div class="space-y-2">
                    <label class="block text-white text-sm font-medium text-center">6-Digit Code</label>
                    <input type="text" id="mfaCode" name="code" required autofocus autocomplete="one-time-code" pattern="\d{6}" maxlength="6"
                        class="w-full text-center tracking-[0.5em] text-2xl py-4 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-600 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none"
                        placeholder="000000" />
                </div>

                <button id="submitBtn" type="submit"
                    class="w-full py-4 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold transition-all shadow-glow flex items-center justify-center gap-2">
                    <span id="submitText">Verify Identity</span>
                    <span class="material-symbols-outlined text-xl">verified_user</span>
                </button>
            </form>

            <!-- Recovery Code Form (hidden by default) -->
            <form id="recoveryForm" style="display:none;" class="space-y-6">
                <div class="space-y-2">
                    <label class="block text-white text-sm font-medium text-center">Recovery Code</label>
                    <input type="text" id="recoveryCode" name="recovery_code" autocomplete="off" maxlength="17"
                        class="w-full text-center tracking-widest text-lg py-4 rounded-xl bg-[#1b1f27] border border-[#3b4354] text-white placeholder:text-slate-600 focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all outline-none uppercase"
                        placeholder="XXXXXXXX-XXXXXXXX" />
                    <p class="text-slate-500 text-xs text-center">Enter one of the recovery codes you saved when setting up MFA.</p>
                </div>

                <button id="recoverySubmitBtn" type="submit"
                    class="w-full py-4 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold transition-all shadow-glow flex items-center justify-center gap-2">
                    <span id="recoverySubmitText">Use Recovery Code</span>
                    <span class="material-symbols-outlined text-xl">key</span>
                </button>
            </form>

            <!-- Toggle -->
            <div class="mt-5 text-center border-t border-white/10 pt-5">
                <button id="toggleRecovery" type="button" onclick="toggleRecoveryMode()"
                    class="text-xs text-slate-400 hover:text-primary transition-colors">
                    Don't have access to your authenticator? <span id="toggleLabel" class="font-semibold text-primary">Use a recovery code</span>
                </button>
            </div>

            <div class="mt-4 text-center">
                <a href="api/auth.php?action=logout" class="text-xs text-slate-500 hover:text-white transition-colors">Cancel & Return to Login</a>
            </div>
        </div>
    </div>

    <script>
        const form         = document.getElementById('mfaForm');
        const codeInput    = document.getElementById('mfaCode');
        const submitBtn    = document.getElementById('submitBtn');
        const submitText   = document.getElementById('submitText');
        const errorMsg     = document.getElementById('errorMsg');

        const recoveryForm        = document.getElementById('recoveryForm');
        const recoveryCodeInput   = document.getElementById('recoveryCode');
        const recoverySubmitBtn   = document.getElementById('recoverySubmitBtn');
        const recoverySubmitText  = document.getElementById('recoverySubmitText');
        const toggleLabel         = document.getElementById('toggleLabel');

        let recoveryMode = false;

        function toggleRecoveryMode() {
            recoveryMode = !recoveryMode;
            errorMsg.style.display = 'none';
            if (recoveryMode) {
                form.style.display = 'none';
                recoveryForm.style.display = 'block';
                toggleLabel.textContent = 'Use authenticator code instead';
                recoveryCodeInput.focus();
            } else {
                recoveryForm.style.display = 'none';
                form.style.display = 'block';
                toggleLabel.textContent = 'Use a recovery code';
                codeInput.focus();
            }
        }

        // Auto-format recovery code: uppercase, insert dash after 8 chars
        recoveryCodeInput.addEventListener('input', function() {
            let val = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            if (val.length > 8) val = val.slice(0, 8) + '-' + val.slice(8, 16);
            this.value = val;
        });

        // Auto-submit TOTP when 6 digits entered
        codeInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) form.dispatchEvent(new Event('submit'));
        });

        // TOTP submit
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorMsg.style.display = 'none';

            const code = codeInput.value;
            if (code.length !== 6) {
                errorMsg.textContent = 'Please enter a 6-digit code';
                errorMsg.style.display = 'block';
                return;
            }

            submitBtn.disabled = true;
            submitText.textContent = 'Verifying...';

            try {
                const response = await fetch('api/mfa.php?action=verify_login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code })
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = '<?php echo $redirectStr; ?>';
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
                errorMsg.textContent = 'Please enter a valid recovery code (XXXXXXXX-XXXXXXXX)';
                errorMsg.style.display = 'block';
                return;
            }

            recoverySubmitBtn.disabled = true;
            recoverySubmitText.textContent = 'Verifying...';

            try {
                const response = await fetch('api/mfa.php?action=verify_recovery_login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ recovery_code })
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = '<?php echo $redirectStr; ?>';
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
    <script src="js/security-shield.js"></script>
    <script>
        // ── Light / Dark Mode ────────────────────────────────────
        const html = document.documentElement;
        const themeIcon = document.getElementById('themeIcon');

        function applyTheme(dark) {
            if (dark) {
                html.classList.add('dark');
                themeIcon.textContent = 'light_mode';
            } else {
                html.classList.remove('dark');
                themeIcon.textContent = 'dark_mode';
            }
        }

        function toggleTheme() {
            const isDark = html.classList.contains('dark');
            localStorage.setItem('sv_theme', isDark ? 'light' : 'dark');
            applyTheme(!isDark);
        }

        (function () {
            const saved = localStorage.getItem('sv_theme');
            applyTheme(saved !== 'light');
        })();
        // ─────────────────────────────────────────────────────────
    </script>
</body>
</html>
