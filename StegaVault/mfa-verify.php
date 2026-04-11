<?php
/**
 * StegaVault - MFA Verification Gate
 * File: mfa-verify.php
 */
session_start();

// If no pending MFA, redirect to login
if (!isset($_SESSION['pending_mfa_user_id'])) {
    header('Location: admin/login.php');
    exit;
}

$redirectStr = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'admin/dashboard.php';
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <link rel="icon" type="image/png" href="Assets/favicon.png">
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
    </style>
</head>
<body class="bg-background-dark min-h-screen flex flex-col items-center justify-center font-display px-4">
    <div class="w-full max-w-[400px]">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center size-16 bg-primary/20 rounded-full mb-4 ring-4 ring-primary/10">
                <span class="material-symbols-outlined text-3xl text-primary">phonelink_lock</span>
            </div>
            <h1 class="text-white text-2xl font-bold tracking-tight mb-2">Two-Factor Auth</h1>
            <p class="text-slate-400 text-sm">Enter the code from your Authenticator app.</p>
        </div>

        <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-8 rounded-2xl shadow-2xl">
            <div id="errorMsg" style="display: none;" class="mb-6 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm text-center"></div>

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
            
            <div class="mt-6 text-center">
                <a href="api/auth.php?action=logout" class="text-xs text-slate-500 hover:text-white transition-colors">Cancel & Return to Login</a>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('mfaForm');
        const codeInput = document.getElementById('mfaCode');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const errorMsg = document.getElementById('errorMsg');

        // Automatically format code entry
        codeInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if(this.value.length === 6) {
                // Auto submit when 6 digits are typed
                form.dispatchEvent(new Event('submit'));
            }
        });

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
                    // Redirect back to original target
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
    </script>
</body>
</html>
