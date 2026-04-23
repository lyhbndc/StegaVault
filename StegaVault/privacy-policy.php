<?php
session_start();
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../Assets/favicon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Privacy Policy - StegaVault</title>

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
        html:not(.dark) body                { background-color: #f1f5f9 !important; color: #1e293b !important; }
        html:not(.dark) .bg-background-dark { background-color: #f1f5f9 !important; }
        html:not(.dark) header              { background-color: rgba(255,255,255,0.85) !important; border-color: rgba(0,0,0,0.08) !important; }
        html:not(.dark) .bg-white\/5        { background-color: #ffffff !important; border-color: rgba(0,0,0,0.1) !important; box-shadow: 0 4px 24px rgba(0,0,0,0.06) !important; }
        html:not(.dark) .border-white\/10   { border-color: rgba(0,0,0,0.1) !important; }
        html:not(.dark) .border-white\/5    { border-color: rgba(0,0,0,0.08) !important; }
        html:not(.dark) .text-white         { color: #1e293b !important; }
        html:not(.dark) .text-slate-300     { color: #475569 !important; }
        html:not(.dark) .text-slate-400     { color: #64748b !important; }
        html:not(.dark) .text-slate-500     { color: #94a3b8 !important; }
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
            <span class="material-symbols-outlined text-primary">shield</span>
            Privacy Policy
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

            <h1 class="text-3xl font-bold mb-2 text-primary">StegaVault Privacy Policy</h1>

            <p class="text-slate-400 mb-8 text-sm">
                Effective Date: [Insert Date] <br>
                Peanut Gallery Media Network — StegaVault System
            </p>

            <div class="space-y-8 text-sm text-slate-300 leading-relaxed">

                <!-- INTRO -->
                <section>
                    <h2 class="text-white font-semibold mb-2">1. Introduction</h2>
                    <p>
                        StegaVault is a secure web-based multimedia encryption and steganography system developed for
                        Peanut Gallery Media Network.
                        We are committed to protecting the confidentiality, integrity, and availability of all user data
                        in compliance with applicable
                        data protection laws including the Data Privacy Act of 2012 (Republic Act 10173).
                    </p>
                </section>

                <!-- DATA COLLECTION -->
                <section>
                    <h2 class="text-white font-semibold mb-2">2. Information We Collect</h2>
                    <ul class="list-disc ml-6 space-y-1">
                        <li>Account Information (name, email, encrypted password)</li>
                        <li>User Role Information (Super Admin, Admin, Employee, Collaborator)</li>
                        <li>Uploaded multimedia files (images, videos, audio, documents)</li>
                        <li>System logs (IP address, timestamps, device activity)</li>
                    </ul>
                </section>

                <!-- PURPOSE -->
                <section>
                    <h2 class="text-white font-semibold mb-2">3. Purpose of Data Collection</h2>
                    <ul class="list-disc ml-6 space-y-1">
                        <li>User authentication and secure access control</li>
                        <li>Encryption, steganography, and secure file processing</li>
                        <li>Cloud backup and file recovery</li>
                        <li>Monitoring system integrity and preventing unauthorized access</li>
                        <li>Collaboration between authorized users</li>
                    </ul>
                </section>

                <!-- LEGAL BASIS -->
                <section>
                    <h2 class="text-white font-semibold mb-2">4. Legal Basis for Processing</h2>
                    <p>
                        Data processing is based on user consent, system functionality requirements, and compliance with
                        applicable laws such as the Data Privacy Act of 2012 and international security principles
                        similar to GDPR.
                    </p>
                </section>

                <!-- DATA PROTECTION -->
                <section>
                    <h2 class="text-white font-semibold mb-2">5. Data Protection and Security</h2>
                    <ul class="list-disc ml-6 space-y-1">
                        <li>AES-level encryption for sensitive data</li>
                        <li>Steganography-based hidden data embedding for secure media</li>
                        <li>Role-based access control (RBAC)</li>
                        <li>Secure cloud storage (e.g., Supabase / AWS integration)</li>
                        <li>Audit logs and activity monitoring</li>
                    </ul>
                </section>

                <!-- DATA SHARING -->
                <section>
                    <h2 class="text-white font-semibold mb-2">6. Data Sharing and Disclosure</h2>
                    <p>
                        We do not sell or trade personal data. Data may only be shared under the following conditions:
                    </p>
                    <ul class="list-disc ml-6 space-y-1 mt-2">
                        <li>Legal compliance or court orders</li>
                        <li>Security investigations or system abuse prevention</li>
                        <li>Authorized internal system operations only</li>
                    </ul>
                </section>

                <!-- DATA RETENTION -->
                <section>
                    <h2 class="text-white font-semibold mb-2">7. Data Retention</h2>
                    <p>
                        Data is retained only as long as necessary for system operation, legal compliance, and
                        organizational requirements.
                        Users may request deletion of their data subject to administrative approval.
                    </p>
                </section>

                <!-- USER RIGHTS -->
                <section>
                    <h2 class="text-white font-semibold mb-2">8. User Rights</h2>
                    <ul class="list-disc ml-6 space-y-1">
                        <li>Right to access stored personal data</li>
                        <li>Right to correct inaccurate information</li>
                        <li>Right to request deletion of data</li>
                        <li>Right to withdraw consent</li>
                        <li>Right to be informed about data usage</li>
                    </ul>
                    <p class="mt-2">
                        These rights are aligned with the Data Privacy Act of 2012.
                    </p>
                </section>

                <!-- COOKIES -->
                <section>
                    <h2 class="text-white font-semibold mb-2">9. Cookies and Tracking</h2>
                    <p>
                        StegaVault may use session cookies for authentication, security, and performance monitoring.
                        No advertising or third-party tracking cookies are used.
                    </p>
                </section>

                <!-- SECURITY LIMITATION -->
                <section>
                    <h2 class="text-white font-semibold mb-2">10. Security Limitations</h2>
                    <p>
                        While we implement strong security measures including encryption and steganography,
                        no system is completely immune to threats. Users acknowledge inherent risks in digital systems.
                    </p>
                </section>

                <!-- CHANGES -->
                <section>
                    <h2 class="text-white font-semibold mb-2">11. Policy Updates</h2>
                    <p>
                        This Privacy Policy may be updated periodically. Continued use of the system constitutes
                        acceptance of any changes.
                    </p>
                </section>

                <!-- CONTACT -->
                <section>
                    <h2 class="text-white font-semibold mb-2">12. Contact Information</h2>
                    <p>
                        For questions regarding this Privacy Policy, contact:
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