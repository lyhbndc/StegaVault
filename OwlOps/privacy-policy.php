<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Privacy Policy - OwlOps</title>
    <script>if(localStorage.getItem('owlops-theme')==='dark')document.documentElement.classList.add('dark');</script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#2563eb",
                        "primary-hover": "#1e40af",
                        "background-light": "#ffffff",
                        "card-light": "#f8fafc",
                        "slate-card": "#111111",
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"],
                        "body": ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; }
        html.dark body { background-color: #000000; }
        h1, h2, h3, h4, h5, h6, .font-display { font-family: 'Space Grotesk', sans-serif; }
        .bg-grid-pattern {
            background-image: radial-gradient(#cbd5e1 0.1px, transparent 0.1px);
            background-size: 40px 40px;
        }
        html.dark .bg-grid-pattern {
            background-image: radial-gradient(rgba(255,255,255,0.08) 0.1px, transparent 0.1px);
        }
    </style>
</head>

<body class="bg-white dark:bg-black text-slate-900 dark:text-slate-200 min-h-screen flex flex-col relative">

    <!-- Background Decor -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] bg-primary/5 rounded-full blur-[140px]"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 w-full px-8 py-6 flex items-center justify-between border-b border-slate-200 dark:border-white/5 bg-white/80 dark:bg-black/80 backdrop-blur-md">
        <div class="flex items-center gap-4">
            <img src="OwlOps.png" alt="OwlOps Logo" class="h-12 w-auto">
            <div>
                <h2 class="text-slate-900 dark:text-white text-2xl font-bold tracking-tight font-display">OwlOps</h2>
                <div class="flex items-center gap-2">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase">Global Control Node</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <button onclick="toggleTheme()" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors" title="Toggle theme">
                <span class="material-symbols-outlined text-[20px]" id="themeIcon">dark_mode</span>
            </button>
            <a href="javascript:history.back()" class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-white transition-colors">← Back</a>
        </div>
    </header>

    <!-- Content -->
    <main class="relative z-10 flex-1 max-w-4xl w-full mx-auto px-8 py-16">

        <div class="bg-slate-50 dark:bg-slate-card border border-slate-200 dark:border-white/10 rounded-[2.5rem] p-12 backdrop-blur-xl shadow-lg">

            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold mb-4 text-primary font-display">OwlOps Privacy Policy</h1>
                <p class="text-slate-600 dark:text-slate-400 text-sm">
                    Effective Date: [Insert Date] <br>
                    Peanut Gallery Media Network — OwlOps System
                </p>
            </div>

            <div class="space-y-10 text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-body">

                <!-- INTRO -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">1. Introduction</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Super Administrator account information (name, email, encrypted password)</li>
                        <li>Application management data and system configurations</li>
                        <li>Audit logs and activity monitoring data</li>
                        <li>Backup and system preservation information</li>
                        <li>Multi-factor authentication data</li>
                    </ul>
                </section>

                <!-- PURPOSE -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">3. Purpose of Data Collection</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Super administrator authentication and secure access control</li>
                        <li>System-wide infrastructure management and monitoring</li>
                        <li>Application administration and user management oversight</li>
                        <li>Security auditing and compliance monitoring</li>
                        <li>Automated backup and disaster recovery operations</li>
                    </ul>
                </section>

                <!-- LEGAL BASIS -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">4. Legal Basis for Processing</h2>
                    <p>
                        Data processing is based on system security requirements, administrative necessity, and compliance with
                        applicable laws such as the Data Privacy Act of 2012 and international security principles
                        similar to GDPR.
                    </p>
                </section>

                <!-- DATA PROTECTION -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">5. Data Protection and Security</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Advanced encryption for sensitive administrative data</li>
                        <li>Multi-factor authentication and secure session management</li>
                        <li>Role-based access control with super administrator privileges</li>
                        <li>Comprehensive audit logging and activity monitoring</li>
                        <li>Automated backup systems with secure storage</li>
                    </ul>
                </section>

                <!-- DATA SHARING -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">6. Data Sharing and Disclosure</h2>
                    <p>
                        We do not sell or trade personal data. Data may only be shared under the following conditions:
                    </p>
                    <ul class="list-disc ml-6 space-y-2 mt-4">
                        <li>Legal compliance or court orders</li>
                        <li>Security investigations or system abuse prevention</li>
                        <li>Authorized internal system operations only</li>
                        <li>Emergency system recovery and maintenance</li>
                    </ul>
                </section>

                <!-- DATA RETENTION -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">7. Data Retention</h2>
                    <p>
                        Administrative data is retained only as long as necessary for system operation, legal compliance, and
                        organizational requirements.
                        Super administrators may request deletion of their data subject to system integrity requirements.
                    </p>
                </section>

                <!-- USER RIGHTS -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">8. User Rights</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Right to access stored personal data</li>
                        <li>Right to correct inaccurate information</li>
                        <li>Right to request deletion of data (subject to system constraints)</li>
                        <li>Right to withdraw consent</li>
                        <li>Right to be informed about data usage</li>
                    </ul>
                    <p class="mt-4">
                        These rights are aligned with the Data Privacy Act of 2012.
                    </p>
                </section>

                <!-- COOKIES -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">9. Cookies and Tracking</h2>
                    <p>
                        OwlOps may use session cookies for authentication, security, and performance monitoring.
                        No advertising or third-party tracking cookies are used.
                    </p>
                </section>

                <!-- SECURITY LIMITATION -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">10. Security Limitations</h2>
                    <p>
                        While we implement strong security measures including encryption and multi-factor authentication,
                        no system is completely immune to threats. Users acknowledge inherent risks in digital systems.
                    </p>
                </section>

                <!-- CHANGES -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">11. Policy Updates</h2>
                    <p>
                        This Privacy Policy may be updated periodically. Continued use of the system constitutes
                        acceptance of any changes.
                    </p>
                </section>

                <!-- CONTACT -->
                <section>
                    <h2 class="text-slate-900 dark:text-white font-semibold mb-4 text-lg">12. Contact Information</h2>
                    <p>
                        For questions regarding this Privacy Policy, contact:
                    </p>
                    <p class="mt-4 text-primary font-semibold">
                        Peanut Gallery Media Network<br>
                        Email: [Insert Email Here]
                    </p>
                </section>
                    <p>
                        For questions regarding this Privacy Policy, contact:
                    </p>
                    <p class="mt-4 text-primary font-semibold">
                        Peanut Gallery Media Network<br>
                        Email: [Insert Email Here]
                    </p>
                </section>

            </div>

            <div class="mt-16 text-xs text-slate-500 dark:text-slate-600 border-t border-slate-200 dark:border-white/10 pt-6 text-center">
                © OwlOps Systems — All Rights Reserved
            </div>

        </div>

    </main>

    <script>
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('owlops-theme', isDark ? 'dark' : 'light');
            const icon = document.getElementById('themeIcon');
            if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
        }
        document.addEventListener('DOMContentLoaded', function() {
            const icon = document.getElementById('themeIcon');
            if (icon) icon.textContent = document.documentElement.classList.contains('dark') ? 'light_mode' : 'dark_mode';
        });
    </script>
</body>

</html>
