<?php
/**
 * StegaVault - Super Admin MFA Settings
 * File: OwlOps/mfa-settings.php
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/auth_guard.php';

require_once '../StegaVault/includes/db.php';

$stmt = $db->prepare("SELECT id, email, name, is_mfa_enabled FROM super_admins WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$mfaEnabled = $user['is_mfa_enabled'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Two-Factor Authentication - OwlOps</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
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
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; }
        h1,h2,h3,h4,h5,h6,.font-display { font-family: 'Space Grotesk', sans-serif; }
        .bg-grid-pattern {
            background-image: radial-gradient(#cbd5e1 0.1px, transparent 0.1px);
            background-size: 30px 30px;
        }
    </style>
</head>

<body class="text-slate-900 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 border-r border-slate-200 bg-background-light flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full gap-8">
            <div>
                <h1 class="text-slate-900 text-base font-bold leading-tight font-display">OwlOps</h1>
                <p class="text-primary text-[10px] font-bold uppercase tracking-widest mt-1">Super Admin Mode</p>
            </div>
            <nav class="flex flex-col gap-2 flex-1">
                <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Systems</p>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:text-primary hover:bg-primary/5 transition-colors" href="dashboard.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <p class="text-sm font-medium">Control Center</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:text-primary hover:bg-primary/5 transition-colors" href="manage_admins.php">
                    <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                    <p class="text-sm font-medium">Manage Admins</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:text-primary hover:bg-primary/5 transition-colors" href="backup.php">
                    <span class="material-symbols-outlined text-[20px]">backup</span>
                    <p class="text-sm font-medium">Backup &amp; Restore</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:text-primary hover:bg-primary/5 transition-colors" href="audit-log.php">
                    <span class="material-symbols-outlined text-[20px]">manage_search</span>
                    <p class="text-sm font-medium">Audit Log</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 hover:text-primary hover:bg-primary/5 transition-colors" href="reports.php">
                    <span class="material-symbols-outlined text-[20px]">assessment</span>
                    <p class="text-sm font-medium">System Report</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary border border-primary/20" href="mfa-settings.php">
                    <span class="material-symbols-outlined text-[20px] text-primary">phonelink_lock</span>
                    <p class="text-sm font-medium">MFA Settings</p>
                </a>
            </nav>
            <div class="pt-6 border-t border-slate-200">
                <div class="flex items-center gap-3 px-3 py-2">
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-xs">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-slate-900 text-xs font-bold truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-slate-500 text-[10px] truncate">Super Admin</p>
                    </div>
                </div>
                <button onclick="logout()" class="w-full mt-4 flex items-center gap-3 px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <p class="text-sm font-medium">Sign Out</p>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64 p-12 relative overflow-x-hidden">
        <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
            <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
            <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-[120px]"></div>
        </div>

        <div class="relative z-10 max-w-4xl mx-auto space-y-8">

        <!-- Header -->
        <header>
            <h2 class="text-4xl font-bold text-white font-display">MFA Settings</h2>
            <p class="text-slate-400 mt-2">Manage two-factor authentication for your super admin account.</p>
        </header>

        <!-- Status Card -->
        <div class="mb-8 rounded-2xl border <?php echo $mfaEnabled ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-white/10 bg-white/5'; ?> p-6 lg:p-8">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="material-symbols-outlined text-4xl <?php echo $mfaEnabled ? 'text-emerald-500' : 'text-slate-400'; ?>">
                            <?php echo $mfaEnabled ? 'verified' : 'phonelink_lock'; ?>
                        </span>
                        <h2 class="text-3xl font-bold">
                            <?php echo $mfaEnabled ? 'MFA Enabled' : 'MFA Disabled'; ?>
                        </h2>
                    </div>
                    <p class="text-slate-400 ml-14">
                        <?php
                        if ($mfaEnabled) {
                            echo 'Your super admin account is protected with two-factor authentication.';
                        } else {
                            echo 'Add an extra layer of security to your super admin account.';
                        }
                        ?>
                    </p>
                </div>
                <div class="text-right flex-shrink-0 ml-4">
                    <span class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider <?php echo $mfaEnabled ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-white/5 text-slate-400 border border-white/10'; ?>">
                        <span class="material-symbols-outlined text-sm"><?php echo $mfaEnabled ? 'check_circle' : 'radio_button_unchecked'; ?></span>
                        <?php echo $mfaEnabled ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Setup / Manage Section -->
        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 lg:p-8">
            <?php if (!$mfaEnabled): ?>
                <!-- SETUP SECTION -->
                <h3 class="text-lg font-bold mb-6">Enable Two-Factor Authentication</h3>

                <div class="space-y-8">
                    <!-- Step 1 -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-300 mb-3">Step 1: Install an Authenticator App</h4>
                        <p class="text-sm text-slate-400 mb-4">Download one of these authenticator apps on your phone:</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="border border-white/10 rounded-lg p-4 text-center hover:border-primary/40 hover:bg-primary/5 transition-all">
                                <p class="font-semibold text-sm">Google Authenticator</p>
                            </div>
                            <div class="border border-white/10 rounded-lg p-4 text-center hover:border-primary/40 hover:bg-primary/5 transition-all">
                                <p class="font-semibold text-sm">Microsoft Authenticator</p>
                            </div>
                            <div class="border border-white/10 rounded-lg p-4 text-center hover:border-primary/40 hover:bg-primary/5 transition-all">
                                <p class="font-semibold text-sm">Authy</p>
                            </div>
                            <div class="border border-white/10 rounded-lg p-4 text-center hover:border-primary/40 hover:bg-primary/5 transition-all">
                                <p class="font-semibold text-sm">FreeOTP</p>
                            </div>
                        </div>
                    </div>

                    <hr class="border-white/10" />

                    <!-- Step 2 -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-300 mb-3">Step 2: Scan QR Code</h4>
                        <p class="text-sm text-slate-400 mb-4">Scan this QR code with your authenticator app:</p>
                        <div id="mfaQrContainer" class="flex justify-center p-6 bg-white rounded-xl mb-4 min-h-[160px] items-center">
                            <span class="text-slate-400 text-sm">Generating QR code...</span>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-300 mb-3">Step 3: Enter Verification Code</h4>
                        <p class="text-sm text-slate-400 mb-4">Enter the 6-digit code shown in your authenticator app:</p>
                        <div class="flex gap-3">
                            <input id="mfaVerifyCode" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="000000"
                                class="flex-1 max-w-xs text-center text-2xl tracking-[0.5em] bg-[#1b1f27] border border-[#3b4354] rounded-xl px-4 py-3 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all" />
                            <button onclick="verifyMfaSetup()" class="px-6 py-3 bg-primary hover:bg-white/90 text-black font-bold rounded-xl transition-all flex items-center gap-2">
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

                    <!-- Recovery Codes -->
                    <div id="recoveryCodesSection" class="hidden p-6 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="material-symbols-outlined text-yellow-500 text-2xl flex-shrink-0">warning</span>
                            <div>
                                <h4 class="font-bold text-yellow-400 mb-1">Save Your Recovery Codes</h4>
                                <p class="text-sm text-yellow-300/80">Store these codes in a safe place. Each code can be used once to access your account if you lose your authenticator app.</p>
                            </div>
                        </div>
                        <div id="recoveryCodesList" class="bg-black/30 border border-white/10 rounded-lg p-4 mb-4 font-mono text-sm text-slate-300 max-h-64 overflow-y-auto"></div>
                        <div class="flex gap-2">
                            <button onclick="copyRecoveryCodes()" class="flex-1 py-2 px-4 bg-primary hover:bg-white/90 text-black font-semibold rounded-lg transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-sm">content_copy</span>
                                <span>Copy Codes</span>
                            </button>
                            <button onclick="printRecoveryCodes()" class="flex-1 py-2 px-4 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-lg transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-sm">print</span>
                                <span>Print</span>
                            </button>
                        </div>
                    </div>
                </div>

                <button onclick="generateQRCode()" class="mt-8 w-full py-3 bg-primary/10 hover:bg-primary/20 text-primary font-bold rounded-xl transition-all flex items-center justify-center gap-2 border border-primary/20">
                    <span class="material-symbols-outlined">refresh</span>
                    <span id="generateBtnText">Regenerate QR Code</span>
                </button>

            <?php else: ?>
                <!-- ENABLED SECTION -->
                <div class="space-y-6">
                    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-6">
                        <div class="flex items-start gap-4">
                            <span class="material-symbols-outlined text-3xl text-emerald-500 flex-shrink-0">check_circle</span>
                            <div>
                                <p class="font-semibold text-emerald-400 mb-1">Two-Factor Authentication is Active</p>
                                <p class="text-sm text-slate-300">Your super admin account is protected. You'll need your authenticator app code each time you log in.</p>
                            </div>
                        </div>
                    </div>

                    <div class="border border-white/10 rounded-xl p-6">
                        <h3 class="font-semibold text-white mb-3 flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-400">backup</span>
                            Recovery Codes
                        </h3>
                        <p class="text-sm text-slate-400 mb-4">
                            If you lose access to your authenticator app, use a recovery code to sign in. Each code can only be used once.
                        </p>
                        <button onclick="regenerateRecoveryCodes()" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-semibold rounded-lg transition-all">
                            Regenerate Recovery Codes
                        </button>
                    </div>

                    <button onclick="disableMfa()" class="w-full py-4 px-6 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold rounded-xl border border-red-500/30 transition-all flex items-center justify-center gap-3 text-lg">
                        <span class="material-symbols-outlined">lock_open</span>
                        <span>Disable Two-Factor Authentication</span>
                    </button>
                </div>

                <div id="mfaError" class="hidden mt-6 p-4 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm"></div>
            <?php endif; ?>
        </div>

        </div><!-- end z-10 wrapper -->
    </main>

    <script>
        async function logout() {
            await fetch('../StegaVault/api/super_admin_auth.php?action=logout', { method: 'POST' });
            window.location.href = 'login.php';
        }

        async function generateQRCode() {
            const btn = document.getElementById('generateBtnText');
            const errorDiv = document.getElementById('mfaError');
            const successDiv = document.getElementById('mfaSuccess');

            if (errorDiv) errorDiv.classList.add('hidden');
            if (successDiv) successDiv.classList.add('hidden');
            btn.textContent = 'Generating...';

            try {
                const res = await fetch('../StegaVault/api/super_admin_mfa.php?action=setup');
                const data = await res.json();

                if (data.success) {
                    const qrUrl = data.data.qr_url;
                    document.getElementById('mfaQrContainer').innerHTML = `<img src="${qrUrl}" alt="MFA QR Code" class="max-w-[200px]" />`;
                    btn.textContent = 'Regenerate QR Code';
                } else {
                    if (errorDiv) {
                        errorDiv.textContent = data.error || 'Failed to generate QR code';
                        errorDiv.classList.remove('hidden');
                    }
                    btn.textContent = 'Regenerate QR Code';
                }
            } catch (e) {
                if (errorDiv) {
                    errorDiv.textContent = 'Network error: ' + e.message;
                    errorDiv.classList.remove('hidden');
                }
                btn.textContent = 'Regenerate QR Code';
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
                const res = await fetch('../StegaVault/api/super_admin_mfa.php?action=verify_setup', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code })
                });

                const data = await res.json();

                if (data.success) {
                    successDiv.classList.remove('hidden');
                    document.getElementById('mfaVerifyCode').value = '';

                    if (data.data && data.data.recovery_codes) {
                        displayRecoveryCodes(data.data.recovery_codes);
                        recoverySection.classList.remove('hidden');
                    }

                    setTimeout(() => { window.location.reload(); }, 6000);
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
            codesList.innerHTML = codes.map(code => `<div class="py-0.5">${code}</div>`).join('');
            window.currentRecoveryCodes = codes;
        }

        function copyRecoveryCodes() {
            if (!window.currentRecoveryCodes) return;
            navigator.clipboard.writeText(window.currentRecoveryCodes.join('\n')).then(() => {
                alert('Recovery codes copied to clipboard!');
            });
        }

        function printRecoveryCodes() {
            if (!window.currentRecoveryCodes) return;
            const printWindow = window.open('', '', 'height=400,width=600');
            printWindow.document.write(`
                <h2>OwlOps Super Admin Recovery Codes</h2>
                <p>Save these codes in a safe place. Each code can only be used once.</p>
                <pre>${window.currentRecoveryCodes.join('\n')}</pre>
                <p>Generated: ${new Date().toLocaleString()}</p>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        async function disableMfa() {
            if (!confirm('Are you sure you want to disable Two-Factor Authentication? This reduces the security of your super admin account.')) {
                return;
            }

            const errorDiv = document.getElementById('mfaError');
            errorDiv.classList.add('hidden');

            try {
                const res = await fetch('../StegaVault/api/super_admin_mfa.php?action=disable', {
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

        async function regenerateRecoveryCodes() {
            if (!confirm('This will invalidate all your old recovery codes. Continue?')) {
                return;
            }

            try {
                const res = await fetch('../StegaVault/api/super_admin_mfa.php?action=regenerate_recovery_codes', {
                    method: 'POST'
                });

                const data = await res.json();

                if (data.success && data.data && data.data.recovery_codes) {
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm';
                    modal.innerHTML = `
                        <div class="bg-[#111111] border border-white/10 rounded-2xl p-8 max-w-xl w-full mx-4">
                            <h3 class="text-xl font-bold text-white mb-2">New Recovery Codes</h3>
                            <p class="text-slate-400 text-sm mb-4">Save these new codes. Your old codes are no longer valid.</p>
                            <div id="newCodesDisplay" class="bg-black/40 border border-white/10 rounded-lg p-4 mb-4 font-mono text-sm text-slate-300 max-h-64 overflow-y-auto"></div>
                            <div class="flex gap-2">
                                <button onclick="this.closest('.fixed').remove()" class="flex-1 py-2 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-lg">Close</button>
                                <button onclick="copyNewCodes('${data.data.recovery_codes.join('|')}')" class="flex-1 py-2 bg-white hover:bg-white/90 text-black font-semibold rounded-lg">Copy Codes</button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                    modal.querySelector('#newCodesDisplay').innerHTML = data.data.recovery_codes.map(c => `<div class="py-0.5">${c}</div>`).join('');
                } else {
                    alert('Failed to regenerate recovery codes.');
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

        document.addEventListener('DOMContentLoaded', () => {
            const isMfaEnabled = <?php echo $mfaEnabled ? 'true' : 'false'; ?>;
            if (!isMfaEnabled) {
                generateQRCode();
            }
        });
    </script>
    <script src="session-timeout.js"></script>
</body>

</html>
