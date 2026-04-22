<?php
session_start();
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Terms of Service - OwlOps</title>
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
                        "primary": "#ffffff",
                        "primary-hover": "#e2e8f0",
                        "background-dark": "#000000",
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
        body { font-family: 'Inter', sans-serif; background-color: #000000; }
        h1, h2, h3, h4, h5, h6, .font-display { font-family: 'Space Grotesk', sans-serif; }
        .bg-grid-pattern {
            background-image: radial-gradient(#ffffff 0.1px, transparent 0.1px);
            background-size: 40px 40px;
        }
    </style>
</head>

<body class="text-slate-200 min-h-screen flex flex-col relative">

    <!-- Background Decor -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
        <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] bg-primary/5 rounded-full blur-[140px]"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 w-full px-8 py-6 flex items-center justify-between border-b border-white/5 bg-background-dark/80 backdrop-blur-md">
        <div class="flex items-center gap-4">
            <div class="bg-primary p-2.5 rounded-2xl shadow-lg shadow-white/10">
                <span class="material-symbols-outlined text-black text-2xl">policy</span>
            </div>
            <div>
                <h2 class="text-white text-2xl font-bold tracking-tight font-display">OwlOps</h2>
                <div class="flex items-center gap-2">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase">Global Control Node</p>
                </div>
            </div>
        </div>
        <a href="javascript:history.back()" class="text-sm text-slate-400 hover:text-primary transition-colors">← Back</a>
    </header>

    <!-- Content -->
    <main class="relative z-10 flex-1 max-w-4xl w-full mx-auto px-8 py-16">

        <div class="bg-slate-card border border-white/5 rounded-[2.5rem] p-12 backdrop-blur-xl shadow-2xl">

            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold mb-4 text-primary font-display">OwlOps Terms of Service</h1>
                <p class="text-slate-400 text-sm">
                    Effective Date: [Insert Date] <br>
                    Peanut Gallery Media Network — OwlOps System
                </p>
            </div>

            <div class="space-y-10 text-sm text-slate-300 leading-relaxed font-body">

                <!-- 1 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">1. Agreement to Terms</h2>
                    <p>
                        By accessing or using OwlOps, you agree to be bound by these Terms of Service.
                        If you do not agree, you must discontinue use of the system immediately.
                    </p>
                </section>

                <!-- 2 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">2. Description of Service</h2>
                    <p>
                        OwlOps is a comprehensive super administration and infrastructure management platform that provides
                        system-wide control, application management, user administration, security monitoring, and automated
                        backup services for Peanut Gallery Media Network.
                    </p>
                </section>

                <!-- 3 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">3. Eligibility</h2>
                    <p>
                        Access is restricted to authorized Super Administrators only.
                        Unauthorized users are strictly prohibited from accessing the system.
                    </p>
                </section>

                <!-- 4 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">4. User Accounts</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Users must provide accurate and complete account information</li>
                        <li>Users are responsible for maintaining confidentiality of credentials</li>
                        <li>Any activity under an account is the responsibility of the account holder</li>
                        <li>Multi-factor authentication is required for enhanced security</li>
                    </ul>
                </section>

                <!-- 5 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">5. Acceptable Use Policy</h2>
                    <ul class="list-disc ml-6 space-y-2">
                        <li>Use the system only for authorized administrative purposes</li>
                        <li>Do not upload illegal, harmful, or unauthorized content</li>
                        <li>Do not attempt to bypass security controls or audit systems</li>
                        <li>Do not interfere with system integrity or performance</li>
                        <li>Do not share access credentials with unauthorized personnel</li>
                    </ul>
                </section>

                <!-- 6 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">6. Security and Data Protection</h2>
                    <p>
                        OwlOps uses advanced encryption, multi-factor authentication, secure session management, and
                        comprehensive audit logging to protect administrative data and system integrity.
                        However, no system is completely immune to cyber threats.
                    </p>
                </section>

                <!-- 7 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">7. Intellectual Property</h2>
                    <p>
                        All system designs, architecture, and branding belong to Peanut Gallery Media Network.
                        Users retain ownership of their administrative data but grant the system rights to process and
                        store it for operational purposes.
                    </p>
                </section>

                <!-- 8 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">8. Termination</h2>
                    <p>
                        We reserve the right to suspend or terminate access at any time for violations of these Terms,
                        security risks, or legal compliance requirements.
                    </p>
                </section>

                <!-- 9 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">9. Limitation of Liability</h2>
                    <p>
                        OwlOps and its developers are not liable for any damages, data loss, unauthorized access,
                        or service interruptions resulting from system use or misuse.
                    </p>
                </section>

                <!-- 10 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">10. Privacy Policy</h2>
                    <p>
                        Your use of OwlOps is also governed by our Privacy Policy,
                        which complies with the Data Privacy Act of 2012 (RA 10173) and international security
                        principles.
                    </p>
                </section>

                <!-- 11 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">11. Modifications to Service</h2>
                    <p>
                        We reserve the right to modify, suspend, or discontinue any part of the system at any time
                        without prior notice.
                    </p>
                </section>

                <!-- 12 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">12. Changes to Terms</h2>
                    <p>
                        These Terms may be updated periodically. Continued use of the system constitutes acceptance of
                        any changes.
                    </p>
                </section>

                <!-- 13 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">13. Governing Law</h2>
                    <p>
                        These Terms shall be governed by the laws of the Republic of the Philippines,
                        including the Data Privacy Act of 2012 and applicable cybersecurity regulations.
                    </p>
                </section>

                <!-- 14 -->
                <section>
                    <h2 class="text-white font-semibold mb-4 text-lg">14. Contact Information</h2>
                    <p>
                        For questions regarding these Terms of Service:
                    </p>
                    <p class="mt-4 text-primary font-semibold">
                        Peanut Gallery Media Network<br>
                        Email: [Insert Email Here]
                    </p>
                </section>

            </div>

            <div class="mt-16 text-xs text-slate-500 border-t border-white/10 pt-6 text-center">
                © OwlOps Systems — All Rights Reserved
            </div>

        </div>

    </main>

</body>

</html>
