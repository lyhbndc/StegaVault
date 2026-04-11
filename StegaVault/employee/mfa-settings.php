<?php
/**
 * StegaVault - MFA Settings Page (Employee)
 * File: employee/mfa-settings.php
 */

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect if not employee
if ($_SESSION['role'] !== 'employee') {
    header('Location: ../admin/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

// Get user info
$stmt = $db->prepare("SELECT id, email, name, role, is_mfa_enabled FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$mfaEnabled = $user['is_mfa_enabled'] ?? false;
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <link rel="icon" type="image/png" href="../Assets/favicon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Two-Factor Authentication - StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
    </style>
</head>
<body class="bg-background-dark text-white min-h-screen font-display">
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px]"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 w-full px-6 py-6 lg:px-12 border-b border-white/5 bg-background-dark/50 backdrop-blur-md">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="p-2 rounded-lg hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined text-2xl">arrow_back</span>
                </a>
                <div>
                    <h1 class="text-2xl font-bold">Two-Factor Authentication</h1>
                    <p class="text-slate-400 text-sm">Secure your account with MFA</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-sm font-semibold"><?php echo htmlspecialchars($user['name']); ?></p>
                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="bg-primary rounded-full size-10 flex items-center justify-center font-bold text-sm">
                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 max-w-4xl mx-auto px-6 py-12 lg:px-12">
        <!-- Status Card -->
        <div class="mb-8 rounded-2xl border <?php echo $mfaEnabled ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-slate-700 bg-slate-800/30'; ?> p-6 lg:p-8">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="material-symbols-outlined text-4xl <?php echo $mfaEnabled ? 'text-emerald-500' : 'text-slate-500'; ?>">
                            <?php echo $mfaEnabled ? 'verified' : 'phonelink_lock'; ?>
                        </span>
                        <h2 class="text-3xl font-bold">
                            <?php echo $mfaEnabled ? 'MFA Enabled' : 'MFA Disabled'; ?>
                        </h2>
                    </div>
                    <p class="text-slate-400 ml-14">
                        <?php 
                        if ($mfaEnabled) {
                            echo 'Your account is protected with two-factor authentication.';
                        } else {
                            echo 'Add an extra layer of security to your account.';
                        }
                        ?>
                    </p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider <?php echo $mfaEnabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-slate-700/50 text-slate-400 border border-slate-600'; ?>">
                        <span class="material-symbols-outlined text-sm"><?php echo $mfaEnabled ? 'check_circle' : 'radio_button_unchecked'; ?></span>
                        <?php echo $mfaEnabled ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Setup/Disable Section -->
        <div class="rounded-2xl border border-slate-700 bg-slate-800/30 p-6 lg:p-8">
            <?php if (!$mfaEnabled): ?>
                <!-- SETUP SECTION -->
                <h3 class="text-lg font-bold mb-4">Enable Two-Factor Authentication</h3>
                
                <div class="space-y-6">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-300 mb-3">Step 1: Install an Authenticator App</h4>
                        <p class="text-sm text-slate-400 mb-4">
                            Download one of these authenticator apps on your phone:
                        </p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="border border-slate-700 rounded-lg p-4 text-center hover:border-primary/50 hover:bg-primary/5 transition-all cursor-pointer">
                                <p class="font-semibold">Google Authenticator</p>
                            </div>
                            <div class="border border-slate-700 rounded-lg p-4 text-center hover:border-primary/50 hover:bg-primary/5 transition-all cursor-pointer">
                                <p class="font-semibold">Microsoft Authenticator</p>
                            </div>
                            <div class="border border-slate-700 rounded-lg p-4 text-center hover:border-primary/50 hover:bg-primary/5 transition-all cursor-pointer">
                                <p class="font-semibold">Authy</p>
                            </div>
                            <div class="border border-slate-700 rounded-lg p-4 text-center hover:border-primary/50 hover:bg-primary/5 transition-all cursor-pointer">
                                <p class="font-semibold">FreeOTP</p>
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-700" />

                    <div>
                        <h4 class="text-sm font-semibold text-slate-300 mb-3">Step 2: Scan QR Code</h4>
                        <p class="text-sm text-slate-400 mb-4">
                            Scan this QR code with your authenticator app:
                        </p>
                        <div id="mfaQrContainer" class="flex justify-center p-6 bg-white rounded-xl mb-6"></div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-300 mb-3">Step 3: Verify</h4>
                        <p class="text-sm text-slate-400 mb-4">
                            Enter the 6-digit code from your authenticator app:
                        </p>
                        <div class="flex gap-3">
                            <input id="mfaVerifyCode" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000"
                                class="flex-1 max-w-xs text-center text-2xl tracking-[0.5em] bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all" />
                            <button onclick="verifyMfaSetup()" class="px-6 py-3 bg-primary hover:bg-primary/90 text-white font-bold rounded-xl transition-all flex items-center gap-2">
                                <span>Verify</span>
                                <span class="material-symbols-outlined">check_circle</span>
                            </button>
                        </div>
                    </div>

                    <div id="mfaError" class="hidden p-4 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm"></div>
                    <div id="mfaSuccess" class="hidden p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-lg text-emerald-400 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined">check_circle</span>
                        <span>MFA has been successfully enabled!</span>
                    </div>

                    <!-- Recovery Codes Display -->
                    <div id="recoveryCodesSection" class="hidden mt-6 p-6 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="material-symbols-outlined text-yellow-500 text-2xl">warning</span>
                            <div>
                                <h4 class="font-bold text-yellow-400 mb-1">Save Your Recovery Codes</h4>
                                <p class="text-sm text-yellow-300/80">
                                    Store these codes in a safe place. You can use them to access your account if you lose your authenticator app.
                                </p>
                            </div>
                        </div>
                        <div id="recoveryCodesList" class="bg-slate-900 border border-slate-700 rounded-lg p-4 mb-4 font-mono text-sm text-slate-300 max-h-64 overflow-y-auto"></div>
                        <div class="flex gap-2">
                            <button onclick="copyRecoveryCodes()" class="flex-1 py-2 px-4 bg-primary hover:bg-primary/90 text-white font-semibold rounded-lg transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">content_copy</span>
                                <span>Copy Codes</span>
                            </button>
                            <button onclick="printRecoveryCodes()" class="flex-1 py-2 px-4 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">print</span>
                                <span>Print</span>
                            </button>
                        </div>
                    </div>
                </div>

                <button onclick="generateQRCode()" class="mt-6 w-full py-3 bg-primary/20 hover:bg-primary/30 text-primary font-bold rounded-xl transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">refresh</span>
                    <span id="generateBtnText">Generate QR Code</span>
                </button>

            <?php else: ?>
                <!-- DISABLE SECTION -->
                <div class="space-y-6">
                    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-6">
                        <div class="flex items-start gap-4">
                            <span class="material-symbols-outlined text-3xl text-emerald-500 flex-shrink-0">check_circle</span>
                            <div>
                                <p class="font-semibold text-emerald-400 mb-1">Two-Factor Authentication Active</p>
                                <p class="text-sm text-slate-300">
                                    Your account is now protected. You'll need your authenticator app code to sign in.
                                </p>
                            </div>
                        </div>
                    </div>

                    <button onclick="disableMfa()" class="w-full py-4 px-6 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold rounded-xl border border-red-500/30 transition-all flex items-center justify-center gap-3 text-lg">
                        <span class="material-symbols-outlined">lock_open</span>
                        <span>Disable Two-Factor Authentication</span>
                    </button>

                    <div class="border border-slate-700 rounded-xl p-6">
                        <h3 class="font-semibold text-white mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-400">backup</span>
                            Recovery Codes
                        </h3>
                        <p class="text-sm text-slate-400 mb-4">
                            If you lose access to your authenticator app, use recovery codes to regain access. Keep these safe.
                        </p>
                        <button onclick="generateRecoveryCodes()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold rounded-lg transition-all">
                            Generate Recovery Codes
                        </button>
                    </div>
                </div>

                <div id="mfaError" class="hidden mt-6 p-4 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm"></div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        async function generateQRCode() {
            const btn = document.getElementById('generateBtnText');
            const errorDiv = document.getElementById('mfaError');
            const successDiv = document.getElementById('mfaSuccess');
            
            errorDiv.classList.add('hidden');
            successDiv.classList.add('hidden');
            btn.textContent = 'Generating...';

            try {
                const res = await fetch('../api/mfa.php?action=setup');
                const data = await res.json();

                if (data.success) {
                    const qrUrl = data.data.qr_url;
                    document.getElementById('mfaQrContainer').innerHTML = `<img src="${qrUrl}" alt="MFA QR Code" class="max-w-sm" />`;
                    btn.textContent = 'QR Code Generated';
                } else {
                    errorDiv.textContent = data.error || 'Failed to generate QR code';
                    errorDiv.classList.remove('hidden');
                    btn.textContent = 'Generate QR Code';
                }
            } catch (e) {
                errorDiv.textContent = 'Network error: ' + e.message;
                errorDiv.classList.remove('hidden');
                btn.textContent = 'Generate QR Code';
            }
        }

        async function verifyMfaSetup() {
            const code = document.getElementById('mfaVerifyCode').value;
            const errorDiv = document.getElementById('mfaError');
            const successDiv = document.getElementById('mfaSuccess');
            const recoverySection = document.getElementById('recoveryCodesSection');

            errorDiv.classList.add('hidden');
            successDiv.classList.add('hidden');
            recoverySection.classList.add('hidden');

            if (code.length !== 6) {
                errorDiv.textContent = 'Please enter a 6-digit code';
                errorDiv.classList.remove('hidden');
                return;
            }

            try {
                const res = await fetch('../api/mfa.php?action=verify_setup', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code })
                });

                const data = await res.json();

                if (data.success) {
                    successDiv.classList.remove('hidden');
                    document.getElementById('mfaVerifyCode').value = '';
                    
                    // Display recovery codes
                    if (data.data && data.data.recovery_codes) {
                        displayRecoveryCodes(data.data.recovery_codes);
                        recoverySection.classList.remove('hidden');
                    }
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 5000);
                } else {
                    errorDiv.textContent = data.error || 'Invalid code. Please try again.';
                    errorDiv.classList.remove('hidden');
                    document.getElementById('mfaVerifyCode').value = '';
                }
            } catch (e) {
                errorDiv.textContent = 'Network error: ' + e.message;
                errorDiv.classList.remove('hidden');
            }
        }

        function displayRecoveryCodes(codes) {
            const codesList = document.getElementById('recoveryCodesList');
            codesList.innerHTML = codes.map(code => `<div class="py-1">${code}</div>`).join('');
            window.currentRecoveryCodes = codes;
        }

        function copyRecoveryCodes() {
            if (!window.currentRecoveryCodes) return;
            const text = window.currentRecoveryCodes.join('\n');
            navigator.clipboard.writeText(text).then(() => {
                alert('Recovery codes copied to clipboard!');
            });
        }

        function printRecoveryCodes() {
            if (!window.currentRecoveryCodes) return;
            const printWindow = window.open('', '', 'height=400,width=600');
            const content = `
                <h2>StegaVault Recovery Codes</h2>
                <p>Save these codes in a safe place. Each code can be used once to access your account.</p>
                <pre>${window.currentRecoveryCodes.join('\n')}</pre>
                <p>Generated: ${new Date().toLocaleString()}</p>
            `;
            printWindow.document.write(content);
            printWindow.document.close();
            printWindow.print();
        }

        async function disableMfa() {
            if (!confirm('Are you sure you want to disable Two-Factor Authentication? This will remove the extra layer of security from your account.')) {
                return;
            }

            const errorDiv = document.getElementById('mfaError');
            errorDiv.classList.add('hidden');

            try {
                const res = await fetch('../api/mfa.php?action=disable', {
                    method: 'POST'
                });

                const data = await res.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    errorDiv.textContent = data.error || 'Failed to disable MFA';
                    errorDiv.classList.remove('hidden');
                }
            } catch (e) {
                errorDiv.textContent = 'Network error: ' + e.message;
                errorDiv.classList.remove('hidden');
            }
        }

        async function generateRecoveryCodes() {
            if (!confirm('This will invalidate your old recovery codes. Continue?')) {
                return;
            }

            try {
                const res = await fetch('../api/mfa.php?action=regenerate_recovery_codes', {
                    method: 'POST'
                });

                const data = await res.json();

                if (data.success && data.data && data.data.recovery_codes) {
                    // Show recovery codes modal
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm';
                    modal.innerHTML = `
                        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-8 max-w-xl w-full mx-4">
                            <h3 class="text-xl font-bold text-white mb-2">New Recovery Codes</h3>
                            <p class="text-slate-400 text-sm mb-4">Save these new codes. Your old codes are no longer valid.</p>
                            <div id="newCodesDisplay" class="bg-slate-800 border border-slate-700 rounded-lg p-4 mb-4 font-mono text-sm text-slate-300 max-h-64 overflow-y-auto"></div>
                            <div class="flex gap-2">
                                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 text-white font-semibold rounded-lg">Close</button>
                                <button onclick="copyNewCodes('${data.data.recovery_codes.join('|')}')" class="flex-1 py-2 bg-primary hover:bg-primary/90 text-white font-semibold rounded-lg">Copy Codes</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    
                    const display = modal.querySelector('#newCodesDisplay');
                    display.innerHTML = data.data.recovery_codes.map(code => `<div class="py-1">${code}</div>`).join('');
                } else {
                    alert('Failed to regenerate recovery codes');
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        function copyNewCodes(codes) {
            navigator.clipboard.writeText(codes.replace(/\|/g, '\n')).then(() => {
                alert('Recovery codes copied to clipboard!');
            });
        }

        // Auto-generate QR on load if MFA is disabled
        document.addEventListener('DOMContentLoaded', () => {
            const isMfaEnabled = <?php echo $mfaEnabled ? 'true' : 'false'; ?>;
            if (!isMfaEnabled) {
                generateQRCode();
            }
        });
    </script>
</body>
</html>
