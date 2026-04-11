<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Terms of Service - StegaVault</title>
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
                        'glow': '0 0 15px -3px rgba(102, 126, 234, 0.5)'
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

        .prose-dark h2 {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 0.5rem;
        }

        .prose-dark h3 {
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 700;
            margin-top: 1.25rem;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .prose-dark p {
            color: #94a3b8;
            font-size: 0.875rem;
            line-height: 1.75;
            margin-bottom: 0.75rem;
        }

        .prose-dark ul {
            color: #94a3b8;
            font-size: 0.875rem;
            line-height: 1.75;
            list-style: disc;
            padding-left: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .prose-dark li {
            margin-bottom: 0.25rem;
        }

        .prose-dark strong {
            color: #e2e8f0;
        }
    </style>
</head>

<body class="bg-background-dark min-h-screen flex flex-col font-display">
    <!-- Background -->
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
        <a href="login.php" class="flex items-center gap-2 text-slate-400 hover:text-primary text-sm font-medium transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to Login
        </a>
    </header>

    <!-- Main -->
    <main class="relative z-10 flex-1 px-4 py-12">
        <div class="max-w-3xl mx-auto">
            <!-- Page Header -->
            <div class="mb-10">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary/10 border border-primary/20 rounded-full mb-4">
                    <span class="material-symbols-outlined text-primary text-[16px]">gavel</span>
                    <span class="text-primary text-xs font-bold uppercase tracking-wider">Legal</span>
                </div>
                <h1 class="text-white text-3xl font-bold tracking-tight mb-2">Terms of Service</h1>
                <p class="text-slate-400 text-sm">Last updated: February 2026 &nbsp;·&nbsp; Effective: February 2026</p>
            </div>

            <!-- Card -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-8 md:p-12 prose-dark">

                <p>These Terms of Service ("Terms") govern your access to and use of <strong>StegaVault</strong>, a digital watermarking and forensic file-security platform operated by <strong>Peanut Gallery Media Inc.</strong> ("PGMN", "we", "our", or "us"). By accessing or using StegaVault you agree to be bound by these Terms.</p>

                <h2>1. Acceptance of Terms</h2>
                <p>By registering an account, logging in, or otherwise using StegaVault, you confirm that you have read, understood, and agree to these Terms. If you do not agree, you must not use the platform.</p>

                <h2>2. Eligible Users</h2>
                <p>StegaVault is an internal business tool. Access is granted solely to <strong>authorized personnel</strong> of PGMN Inc. Accounts are created and managed by system administrators. Sharing credentials or providing unauthorized access to third parties is strictly prohibited.</p>

                <h2>3. Acceptable Use</h2>
                <p>You agree to use StegaVault only for lawful purposes and in a manner that does not infringe the rights of others. You must not:</p>
                <ul>
                    <li>Upload files that contain malware, illegal content, or content that violates third-party rights.</li>
                    <li>Attempt to bypass, circumvent, or reverse-engineer the platform's watermarking or encryption mechanisms.</li>
                    <li>Share, publish, or redistribute watermarked files obtained from StegaVault without explicit authorization.</li>
                    <li>Conduct unauthorized access attempts against any part of the system.</li>
                    <li>Use the platform to facilitate IP theft, fraud, or any illegal activity.</li>
                </ul>

                <h2>4. Watermarking & Digital Forensics</h2>
                <p>Files downloaded through StegaVault are embedded with <strong>invisible forensic watermarks</strong> that uniquely identify the downloading user. These watermarks are used to:</p>
                <ul>
                    <li>Trace unauthorized distribution of confidential materials.</li>
                    <li>Support internal and legal investigations related to data leaks.</li>
                    <li>Verify the authenticity and integrity of documents.</li>
                </ul>
                <p>By downloading files from StegaVault, you acknowledge and consent to the embedded forensic watermark, including the recording of your user ID, IP address, and timestamp.</p>

                <h2>5. File Uploads & Content</h2>
                <p>You retain ownership of files you upload. By uploading content, you grant PGMN Inc. a limited license to process, store, and encrypt the file for the purpose of providing the StegaVault service. You are solely responsible for ensuring you have the right to upload and process any content submitted to the platform.</p>

                <h2>6. Data Retention</h2>
                <p>Files and associated metadata are stored securely for a period determined by your organization's data retention policy. Administrators may delete files at any time. Forensic watermark records may be retained indefinitely for audit and compliance purposes.</p>

                <h2>7. Account Security</h2>
                <p>You are responsible for maintaining the confidentiality of your account credentials. You must notify your administrator immediately if you suspect unauthorized access to your account. PGMN Inc. is not liable for losses resulting from unauthorized account access caused by your failure to safeguard credentials.</p>

                <h2>8. Intellectual Property</h2>
                <p>All software, algorithms, designs, and features of StegaVault are the exclusive property of PGMN Inc. and are protected by applicable intellectual property laws. Nothing in these Terms grants you a right to use PGMN trademarks, logos, or proprietary marks.</p>

                <h2>9. Limitation of Liability</h2>
                <p>To the fullest extent permitted by law, PGMN Inc. shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising out of your use of StegaVault, even if advised of the possibility of such damages.</p>

                <h2>10. Modifications</h2>
                <p>We reserve the right to update these Terms at any time. Continued use of StegaVault after changes are posted constitutes acceptance of the revised Terms. Material changes will be communicated through administrative notices.</p>

                <h2>11. Governing Law</h2>
                <p>These Terms are governed by the laws of the jurisdiction in which PGMN Inc. is incorporated, without regard to conflict-of-law principles.</p>

                <h2>12. Contact</h2>
                <p>For questions regarding these Terms, contact your system administrator or reach out to PGMN Inc. through official internal channels.</p>
            </div>

            <!-- Also see -->
            <div class="mt-6 flex items-center justify-center gap-4 text-sm">
                <a href="privacy.php" class="flex items-center gap-1.5 text-primary hover:text-primary/80 font-semibold transition-colors">
                    <span class="material-symbols-outlined text-[16px]">privacy_tip</span>
                    Privacy Policy
                </a>
                <span class="text-slate-700">·</span>
                <a href="login.php" class="text-slate-400 hover:text-white transition-colors">Back to Login</a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 w-full px-6 py-6 border-t border-white/5 flex flex-col md:flex-row items-center justify-between gap-3 text-[11px] text-slate-500">
        <p>© <?php echo date('Y'); ?> Peanut Gallery Media Inc. All rights reserved.</p>
        <div class="flex items-center gap-6">
            <a href="terms.php" class="hover:text-primary transition-colors font-semibold text-primary/70">Terms of Service</a>
            <a href="privacy.php" class="hover:text-primary transition-colors">Privacy Policy</a>
        </div>
    </footer>
</body>

</html>