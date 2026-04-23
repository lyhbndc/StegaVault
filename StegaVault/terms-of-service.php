<?php
session_start();
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../Assets/favicon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Terms of Service - StegaVault</title>

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
                        primary: "#10b981",
                        "background-dark": "#0f172a",
                        "slate-card": "#1e293b",
                    },
                    fontFamily: {
                        display: ["Space Grotesk", "sans-serif"]
                    },
                    boxShadow: {
                        glow: "0 0 15px -3px rgba(16, 185, 129, 0.5)",
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

        /* Light mode overrides */
        html:not(.dark) body                    { background-color: #f1f5f9 !important; color: #1e293b !important; }
        html:not(.dark) .bg-background-dark     { background-color: #f1f5f9 !important; }
        html:not(.dark) header                  { background-color: rgba(255,255,255,0.85) !important; border-color: rgba(0,0,0,0.08) !important; }
        html:not(.dark) .bg-white\/5            { background-color: #ffffff !important; border-color: rgba(0,0,0,0.1) !important; box-shadow: 0 4px 24px rgba(0,0,0,0.06) !important; }
        html:not(.dark) .border-white\/10       { border-color: rgba(0,0,0,0.1) !important; }
        html:not(.dark) .border-white\/5        { border-color: rgba(0,0,0,0.08) !important; }
        html:not(.dark) .text-white             { color: #1e293b !important; }
        html:not(.dark) .text-slate-300         { color: #475569 !important; }
        html:not(.dark) .text-slate-400         { color: #64748b !important; }
        html:not(.dark) .text-slate-500         { color: #94a3b8 !important; }
        html:not(.dark) .bg-background-dark\/50 { background-color: rgba(241,245,249,0.85) !important; }
    </style>
</head>

<body class="bg-background-dark min-h-screen text-white">

    <!-- Background -->
    <div class="fixed inset-0 pointer-events-none">
        <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
    </div>

    <!-- Header -->
    <header
        class="relative z-10 w-full px-6 py-6 border-b border-white/5 bg-background-dark/50 backdrop-blur-md flex items-center justify-between">
        <h2 class="text-xl font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">policy</span>
            Terms of Service
        </h2>
        <div class="flex items-center gap-3">
            <button id="themeToggle" onclick="toggleTheme()"
                class="w-9 h-9 flex items-center justify-center rounded-full bg-white/5 border border-white/10 text-slate-400 hover:text-primary transition-colors">
                <span id="themeIcon" class="material-symbols-outlined text-[18px]">light_mode</span>
            </button>
            <a href="javascript:history.back()" class="text-sm text-slate-400 hover:text-primary">← Back</a>
        </div>
    </header>

    <!-- Content -->
    <main class="relative z-10 max-w-4xl mx-auto px-6 py-12">

        <div class="bg-white/5 border border-white/10 rounded-2xl p-8 backdrop-blur-xl shadow-2xl">

            <h1 class="text-3xl font-bold mb-2 text-primary">StegaVault Terms of Service</h1>

            <p class="text-slate-400 mb-8 text-sm">
                Effective Date: [Insert Date] <br>
                Peanut Gallery Media Network — StegaVault System
            </p>

            <div class="space-y-8 text-sm text-slate-300 leading-relaxed">

                <!-- 1 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">1. Agreement to Terms</h2>
                    <p>
                        By accessing or using StegaVault, you agree to be bound by these Terms of Service.
                        If you do not agree, you must discontinue use of the system immediately.
                    </p>
                </section>

                <!-- 2 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">2. Description of Service</h2>
                    <p>
                        StegaVault is a secure multimedia management platform that provides encryption, steganography,
                        secure storage, and cloud-based backup services for authorized users of Peanut Gallery Media
                        Network.
                    </p>
                </section>

                <!-- 3 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">3. Eligibility</h2>
                    <p>
                        Access is restricted to authorized personnel only, including Super Admins, Admins, Employees,
                        and Collaborators.
                        Unauthorized users are strictly prohibited from accessing the system.
                    </p>
                </section>

                <!-- 4 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">4. User Accounts</h2>
                    <ul class="list-disc ml-6 space-y-1">
                        <li>Users must provide accurate and complete account information</li>
                        <li>Users are responsible for maintaining confidentiality of credentials</li>
                        <li>Any activity under an account is the responsibility of the account holder</li>
                    </ul>
                </section>

                <!-- 5 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">5. Acceptable Use Policy</h2>
                    <ul class="list-disc ml-6 space-y-1">
                        <li>Use the system only for authorized business purposes</li>
                        <li>Do not upload illegal, harmful, or unauthorized content</li>
                        <li>Do not attempt to bypass security controls</li>
                        <li>Do not interfere with system integrity or performance</li>
                    </ul>
                </section>

                <!-- 6 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">6. Security and Data Protection</h2>
                    <p>
                        StegaVault uses encryption, steganography, secure authentication, and role-based access control
                        to protect user data.
                        However, no system is completely immune to cyber threats.
                    </p>
                </section>

                <!-- 7 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">7. Intellectual Property</h2>
                    <p>
                        All system designs, architecture, and branding belong to Peanut Gallery Media Network.
                        Users retain ownership of their uploaded content but grant the system rights to process and
                        store it.
                    </p>
                </section>

                <!-- 8 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">8. Termination</h2>
                    <p>
                        We reserve the right to suspend or terminate access at any time for violations of these Terms,
                        security risks, or legal compliance requirements.
                    </p>
                </section>

                <!-- 9 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">9. Limitation of Liability</h2>
                    <p>
                        StegaVault and its developers are not liable for any damages, data loss, unauthorized access,
                        or service interruptions resulting from system use or misuse.
                    </p>
                </section>

                <!-- 10 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">10. Privacy Policy</h2>
                    <p>
                        Your use of StegaVault is also governed by our Privacy Policy,
                        which complies with the Data Privacy Act of 2012 (RA 10173) and international security
                        principles.
                    </p>
                </section>

                <!-- 11 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">11. Modifications to Service</h2>
                    <p>
                        We reserve the right to modify, suspend, or discontinue any part of the system at any time
                        without prior notice.
                    </p>
                </section>

                <!-- 12 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">12. Changes to Terms</h2>
                    <p>
                        These Terms may be updated periodically. Continued use of the system constitutes acceptance of
                        any changes.
                    </p>
                </section>

                <!-- 13 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">13. Governing Law</h2>
                    <p>
                        These Terms shall be governed by the laws of the Republic of the Philippines,
                        including the Data Privacy Act of 2012 and applicable cybersecurity regulations.
                    </p>
                </section>

                <!-- 14 -->
                <section>
                    <h2 class="text-white font-semibold mb-2">14. Contact Information</h2>
                    <p>
                        For questions regarding these Terms of Service:
                    </p>
                    <p class="mt-2 text-primary font-semibold">
                        Peanut Gallery Media Network<br>
                        Email: [Insert Email Here]
                    </p>
                </section>

            </div>

            <div class="mt-10 text-xs text-slate-500 border-t border-white/10 pt-4">
                © StegaVault Systems — All Rights Reserved
            </div>

        </div>

    </main>

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

        // ── Anti-Inspect ─────────────────────────────────────────
        document.addEventListener('contextmenu', e => e.preventDefault());

        document.addEventListener('keydown', e => {
            if (e.key === 'F12') { e.preventDefault(); return; }
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && ['i','I','j','J','c','C'].includes(e.key)) {
                e.preventDefault(); return;
            }
            if ((e.ctrlKey || e.metaKey) && (e.key === 'u' || e.key === 'U')) {
                e.preventDefault(); return;
            }
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                e.preventDefault(); return;
            }
        });

        (function detectDevTools() {
            const threshold = 160;
            setInterval(() => {
                if (window.outerWidth - window.innerWidth > threshold ||
                    window.outerHeight - window.innerHeight > threshold) {
                    document.body.innerHTML = '';
                    window.location.reload();
                }
            }, 1000);
        })();
        // ─────────────────────────────────────────────────────────
    </script>
</body>

</html>