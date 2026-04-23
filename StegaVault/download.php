<?php
/**
 * StegaVault - Download & Install Page
 * File: download.php
 */

define('GITHUB_REPO', 'lyhbndc/StegaVault');
define('APP_VERSION', '1.0.0');
define('DMG_FILE', 'https://github.com/' . GITHUB_REPO . '/releases/download/v' . APP_VERSION . '/StegaVault-Setup.dmg');
define('EXE_FILE', 'https://github.com/' . GITHUB_REPO . '/releases/download/v' . APP_VERSION . '/StegaVault-Setup.exe');
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Download StegaVault</title>
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
                        'glow-blue': '0 0 15px -3px rgba(59, 130, 246, 0.5)',
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

        .os-card-active {
            border-color: rgba(16, 185, 129, 0.5) !important;
            background: rgba(16, 185, 129, 0.08) !important;
        }

        .os-card-active .badge-detected {
            display: flex !important;
        }

        .download-btn-primary {
            animation: pulse-glow 2.5s infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 15px -3px rgba(16, 185, 129, 0.5); }
            50%       { box-shadow: 0 0 30px -3px rgba(16, 185, 129, 0.8); }
        }

        .step-line::after {
            content: '';
            position: absolute;
            left: 19px;
            top: 40px;
            bottom: -16px;
            width: 2px;
            background: linear-gradient(to bottom, rgba(16,185,129,0.3), transparent);
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
                <span class="material-symbols-outlined text-white text-2xl">shield</span>
            </div>
            <h2 class="text-white text-xl font-bold tracking-tight">Peanut Gallery Media <span class="text-primary/80 font-medium">Inc.</span></h2>
        </div>
        <div class="flex items-center gap-4">
            <span id="osDetectedBadge" class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20">
                <span class="material-symbols-outlined text-emerald-400 text-sm">computer</span>
                <span id="osDetectedLabel" class="text-[10px] uppercase tracking-widest font-bold text-emerald-500">Detecting OS...</span>
            </span>
        </div>
    </header>

    <!-- Main -->
    <main class="relative z-10 flex-1 flex flex-col items-center px-4 py-14">
        <div class="w-full max-w-3xl">

            <!-- Hero -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-primary/10 border border-primary/20 rounded-full mb-5">
                    <span class="material-symbols-outlined text-primary text-sm">download</span>
                    <span class="text-[10px] text-primary font-bold uppercase tracking-widest">Version <?= htmlspecialchars(APP_VERSION) ?></span>
                </div>
                <h1 class="text-white text-4xl font-bold tracking-tight mb-3">Install StegaVault</h1>
                <p class="text-slate-400 text-base max-w-md mx-auto">Your OS has been detected automatically. Download the right installer below.</p>
            </div>

            <!-- OS Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-10">

                <!-- macOS Card -->
                <div id="macCard" class="relative bg-white/5 border border-white/10 rounded-2xl p-6 flex flex-col gap-4 transition-all duration-300">
                    <!-- Detected Badge -->
                    <div class="badge-detected hidden items-center gap-1.5 absolute top-4 right-4 px-2 py-1 bg-primary/10 border border-primary/20 rounded-full">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                        </span>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-primary">Your OS</span>
                    </div>

                    <!-- Icon + Title -->
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-slate-card border border-white/10 flex items-center justify-center flex-shrink-0">
                            <!-- Apple logo SVG -->
                            <svg class="w-8 h-8 text-white fill-current" viewBox="0 0 814 1000" xmlns="http://www.w3.org/2000/svg">
                                <path d="M788.1 340.9c-5.8 4.5-108.2 62.2-108.2 190.5 0 148.4 130.3 200.9 134.2 202.2-.6 3.2-20.7 71.9-68.7 141.9-42.8 61.6-87.5 123.1-155.5 123.1s-85.5-39.5-164-39.5c-76 0-103.7 40.8-165.9 40.8s-105-47.4-155.5-127.4C46 790.4 0 663.5 0 541.8c0-207.3 134.8-316.8 267.2-316.8 99.8 0 162.9 65.4 220.1 65.4 54.7 0 127.5-69.3 240.6-69.3zm-264.6-25.9c5.5-36.7 30.7-76.7 53.9-100.5 27.9-28.6 68.3-50.9 106.2-50.9 3.2 30.1-9 60.6-29.7 83.7-18.5 21.2-55.6 46.4-130.4 67.7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg leading-tight">macOS</h3>
                            <p class="text-slate-400 text-xs mt-0.5">Disk Image (.dmg)</p>
                        </div>
                    </div>

                    <!-- Requirements -->
                    <ul class="space-y-1.5">
                        <li class="flex items-center gap-2 text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                            macOS 12 Monterey or later
                        </li>
                        <li class="flex items-center gap-2 text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                            Apple Silicon & Intel supported
                        </li>
                        <li class="flex items-center gap-2 text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                            ~120 MB disk space required
                        </li>
                    </ul>

                    <!-- Download Button -->
                    <a id="macDownloadBtn" href="<?= htmlspecialchars(DMG_FILE) ?>"
                        class="mt-auto w-full py-3.5 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 group">
                        <span class="material-symbols-outlined text-lg group-hover:-translate-y-0.5 transition-transform">download</span>
                        Download for macOS
                    </a>
                </div>

                <!-- Windows Card -->
                <div id="winCard" class="relative bg-white/5 border border-white/10 rounded-2xl p-6 flex flex-col gap-4 transition-all duration-300">
                    <!-- Detected Badge -->
                    <div class="badge-detected hidden items-center gap-1.5 absolute top-4 right-4 px-2 py-1 bg-primary/10 border border-primary/20 rounded-full">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                        </span>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-primary">Your OS</span>
                    </div>

                    <!-- Icon + Title -->
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-slate-card border border-white/10 flex items-center justify-center flex-shrink-0">
                            <!-- Windows logo SVG -->
                            <svg class="w-8 h-8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801" fill="#00adef"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg leading-tight">Windows</h3>
                            <p class="text-slate-400 text-xs mt-0.5">Installer (.exe)</p>
                        </div>
                    </div>

                    <!-- Requirements -->
                    <ul class="space-y-1.5">
                        <li class="flex items-center gap-2 text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                            Windows 10 / 11 (64-bit)
                        </li>
                        <li class="flex items-center gap-2 text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                            .NET 6 Runtime included
                        </li>
                        <li class="flex items-center gap-2 text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                            ~150 MB disk space required
                        </li>
                    </ul>

                    <!-- Download Button -->
                    <a id="winDownloadBtn" href="<?= htmlspecialchars(EXE_FILE) ?>"
                        class="mt-auto w-full py-3.5 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 group">
                        <span class="material-symbols-outlined text-lg group-hover:-translate-y-0.5 transition-transform">download</span>
                        Download for Windows
                    </a>
                </div>

            </div>

            <!-- Installation Steps (shown per OS) -->
            <div id="stepsSection" class="bg-white/5 border border-white/10 rounded-2xl p-6 mb-8">
                <h2 class="text-white font-bold text-base mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">list_alt</span>
                    Installation Steps
                </h2>

                <!-- macOS Steps -->
                <ol id="macSteps" class="hidden space-y-5">
                    <li class="relative flex gap-4 step-line">
                        <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-primary font-bold text-sm">1</span>
                        </div>
                        <div class="pt-2">
                            <p class="text-white text-sm font-semibold">Open the .dmg file</p>
                            <p class="text-slate-400 text-xs mt-0.5">Double-click <span class="text-primary font-mono">StegaVault-Setup.dmg</span> from your Downloads folder.</p>
                        </div>
                    </li>
                    <li class="relative flex gap-4 step-line">
                        <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-primary font-bold text-sm">2</span>
                        </div>
                        <div class="pt-2">
                            <p class="text-white text-sm font-semibold">Drag to Applications</p>
                            <p class="text-slate-400 text-xs mt-0.5">Drag the StegaVault icon into your <span class="text-primary font-mono">Applications</span> folder in the installer window.</p>
                        </div>
                    </li>
                    <li class="relative flex gap-4 step-line">
                        <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-primary font-bold text-sm">3</span>
                        </div>
                        <div class="pt-2">
                            <p class="text-white text-sm font-semibold">Allow the app to run</p>
                            <p class="text-slate-400 text-xs mt-0.5">If prompted by Gatekeeper, go to <span class="text-primary font-mono">System Settings → Privacy &amp; Security</span> and click <strong class="text-white">Open Anyway</strong>.</p>
                        </div>
                    </li>
                    <li class="relative flex gap-4">
                        <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-primary font-bold text-sm">4</span>
                        </div>
                        <div class="pt-2">
                            <p class="text-white text-sm font-semibold">Launch &amp; Log In</p>
                            <p class="text-slate-400 text-xs mt-0.5">Open StegaVault from your Applications folder and log in with your credentials.</p>
                        </div>
                    </li>
                </ol>

                <!-- Windows Steps -->
                <ol id="winSteps" class="hidden space-y-5">
                    <li class="relative flex gap-4 step-line">
                        <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-primary font-bold text-sm">1</span>
                        </div>
                        <div class="pt-2">
                            <p class="text-white text-sm font-semibold">Run the installer</p>
                            <p class="text-slate-400 text-xs mt-0.5">Double-click <span class="text-primary font-mono">StegaVault-Setup.exe</span> from your Downloads folder.</p>
                        </div>
                    </li>
                    <li class="relative flex gap-4 step-line">
                        <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-primary font-bold text-sm">2</span>
                        </div>
                        <div class="pt-2">
                            <p class="text-white text-sm font-semibold">Allow UAC prompt</p>
                            <p class="text-slate-400 text-xs mt-0.5">Click <strong class="text-white">Yes</strong> on the Windows User Account Control dialog to allow installation.</p>
                        </div>
                    </li>
                    <li class="relative flex gap-4 step-line">
                        <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-primary font-bold text-sm">3</span>
                        </div>
                        <div class="pt-2">
                            <p class="text-white text-sm font-semibold">Follow the setup wizard</p>
                            <p class="text-slate-400 text-xs mt-0.5">Accept the license agreement, choose your install location, and click <strong class="text-white">Install</strong>.</p>
                        </div>
                    </li>
                    <li class="relative flex gap-4">
                        <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-primary font-bold text-sm">4</span>
                        </div>
                        <div class="pt-2">
                            <p class="text-white text-sm font-semibold">Launch &amp; Log In</p>
                            <p class="text-slate-400 text-xs mt-0.5">Open StegaVault from the Start Menu or Desktop shortcut and log in with your credentials.</p>
                        </div>
                    </li>
                </ol>

                <!-- Unknown OS fallback -->
                <p id="unknownSteps" class="text-slate-400 text-sm text-center py-4">Select your platform above to see installation steps.</p>
            </div>

            <!-- Support Notice -->
            <div class="text-center">
                <p class="text-slate-500 text-xs flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">help_outline</span>
                    Having trouble? Contact your system administrator for assistance.
                </p>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 w-full px-6 py-8 flex flex-col md:flex-row items-center justify-between gap-4 border-t border-white/5 text-[12px] text-slate-500">
        <p>© 2026 StegaVault Systems. All rights reserved.</p>
        <div class="flex items-center gap-6">
            <a href="privacy-policy.php" class="hover:text-primary transition-colors">Privacy Policy</a>
            <a href="terms-of-service.php" class="hover:text-primary transition-colors">Terms of Service</a>
        </div>
    </footer>

    <script>
        const ua = navigator.userAgent.toLowerCase();
        const isMac = /macintosh|mac os x/.test(ua) && !/iphone|ipad/.test(ua);
        const isWin = /windows/.test(ua);

        const macCard = document.getElementById('macCard');
        const winCard = document.getElementById('winCard');
        const macSteps = document.getElementById('macSteps');
        const winSteps = document.getElementById('winSteps');
        const unknownSteps = document.getElementById('unknownSteps');
        const macBtn = document.getElementById('macDownloadBtn');
        const winBtn = document.getElementById('winDownloadBtn');
        const osLabel = document.getElementById('osDetectedLabel');
        const osBadge = document.getElementById('osDetectedBadge');

        function highlightCard(card, btn) {
            card.classList.add('os-card-active');
            btn.classList.add('download-btn-primary');
        }

        if (isMac) {
            highlightCard(macCard, macBtn);
            macSteps.classList.remove('hidden');
            unknownSteps.classList.add('hidden');
            osLabel.textContent = 'macOS detected';
            osBadge.classList.remove('hidden');
        } else if (isWin) {
            highlightCard(winCard, winBtn);
            winSteps.classList.remove('hidden');
            unknownSteps.classList.add('hidden');
            osLabel.textContent = 'Windows detected';
            osBadge.classList.remove('hidden');
        } else {
            // Unknown OS — show both steps toggled by card click
            unknownSteps.classList.remove('hidden');
        }

        // Allow manual toggle by clicking the other card
        macCard.addEventListener('click', () => {
            macCard.classList.add('os-card-active');
            winCard.classList.remove('os-card-active');
            macSteps.classList.remove('hidden');
            winSteps.classList.add('hidden');
            unknownSteps.classList.add('hidden');
        });

        winCard.addEventListener('click', () => {
            winCard.classList.add('os-card-active');
            macCard.classList.remove('os-card-active');
            winSteps.classList.remove('hidden');
            macSteps.classList.add('hidden');
            unknownSteps.classList.add('hidden');
        });
    </script>

</body>
</html>
