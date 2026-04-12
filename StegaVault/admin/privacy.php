<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Privacy Policy - StegaVault</title>
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

        .prose-dark table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }

        .prose-dark th {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.5rem 0.75rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .prose-dark td {
            color: #94a3b8;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: top;
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
                    <span class="material-symbols-outlined text-primary text-[16px]">privacy_tip</span>
                    <span class="text-primary text-xs font-bold uppercase tracking-wider">Legal</span>
                </div>
                <h1 class="text-white text-3xl font-bold tracking-tight mb-2">Privacy Policy</h1>
                <p class="text-slate-400 text-sm">Last updated: February 2026 &nbsp;·&nbsp; Effective: February 2026</p>
            </div>

            <!-- Card -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl p-8 md:p-12 prose-dark">

                <p>This Privacy Policy describes how <strong>Peanut Gallery Media Inc.</strong> ("PGMN", "we", "our", or "us") collects, uses, stores, and protects personal information in connection with <strong>StegaVault</strong>. By using StegaVault, you agree to the practices described below.</p>

                <h2>1. Information We Collect</h2>
                <p>We collect the following categories of information when you use StegaVault:</p>

                <h3>Account Information</h3>
                <ul>
                    <li>Full name and email address (provided at registration).</li>
                    <li>Role and permission level (admin/employee).</li>
                    <li>Password (stored as a one-way bcrypt hash — never in plain text).</li>
                </ul>

                <h3>Activity & Forensic Data</h3>
                <ul>
                    <li><strong>IP address</strong> — captured at login and embedded in file watermarks at download time.</li>
                    <li><strong>Timestamps</strong> — date and time of logins, uploads, downloads, and other actions.</li>
                    <li><strong>File metadata</strong> — original filename, file size, MIME type, project association.</li>
                    <li><strong>Download records</strong> — every file download is logged and linked to your account and IP.</li>
                    <li><strong>Watermark audit trail</strong> — forensic signatures embedded in files and stored in our database for leak-tracing purposes.</li>
                </ul>

                <h3>System Logs</h3>
                <ul>
                    <li>Action logs (uploads, deletions, member changes, folder operations).</li>
                    <li>Browser and session metadata as standard web server logs.</li>
                </ul>

                <h2>2. How We Use Your Information</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Purpose</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Platform Operation</td>
                            <td>Authenticate users, manage sessions, and provide file management features.</td>
                        </tr>
                        <tr>
                            <td>Forensic Tracking</td>
                            <td>Embed watermarks in downloaded files to enable leak detection and investigation.</td>
                        </tr>
                        <tr>
                            <td>Security & Auditing</td>
                            <td>Monitor for unauthorized access, investigate incidents, and maintain audit logs.</td>
                        </tr>
                        <tr>
                            <td>Administration</td>
                            <td>Allow admins to manage users, projects, folders, and file permissions.</td>
                        </tr>
                        <tr>
                            <td>Legal Compliance</td>
                            <td>Respond to lawful requests from regulatory or law enforcement authorities.</td>
                        </tr>
                    </tbody>
                </table>

                <h2>3. Forensic Watermarks</h2>
                <p>A core function of StegaVault is the embedding of <strong>invisible forensic watermarks</strong> in files at download time. Each watermark encodes:</p>
                <ul>
                    <li>Your user ID and display name.</li>
                    <li>Your IP address at the time of download.</li>
                    <li>A cryptographic timestamp and unique file reference ID.</li>
                    <li>An AES-256 + HMAC-SHA256 encrypted signature for tamper detection.</li>
                </ul>
                <p>This information is stored in our database and may be used to trace unauthorized redistribution of files. <strong>By downloading files, you consent to this watermark being embedded.</strong></p>

                <h2>4. Data Sharing</h2>
                <p>We do <strong>not</strong> sell or share your personal data with third parties for marketing purposes. Data may be shared in the following limited circumstances:</p>
                <ul>
                    <li><strong>Legal obligations:</strong> When required by law, court order, or government authority.</li>
                    <li><strong>Internal investigations:</strong> Forensic data may be shared internally to investigate data leaks or security incidents.</li>
                    <li><strong>Service providers:</strong> We may use trusted infrastructure providers (e.g., hosting, backup) bound by data processing agreements.</li>
                </ul>

                <h2>5. Data Security</h2>
                <p>We take data security seriously and implement the following measures:</p>
                <ul>
                    <li>All uploaded files are <strong>AES-256 encrypted</strong> at rest.</li>
                    <li>Passwords are stored using <strong>bcrypt hashing</strong>.</li>
                    <li>Watermark signatures use <strong>HMAC-SHA256</strong> for tamper detection.</li>
                    <li>All data is transmitted over HTTPS.</li>
                    <li>Access to the platform is restricted to authenticated, role-based users.</li>
                </ul>

                <h2>6. Data Retention</h2>
                <p>We retain data for as long as necessary to fulfill the purposes described in this policy. Specifically:</p>
                <ul>
                    <li><strong>Account data</strong> — retained while the account is active and up to 90 days after deletion.</li>
                    <li><strong>Uploaded files</strong> — retained until manually deleted by an administrator.</li>
                    <li><strong>Forensic watermark records and audit logs</strong> — retained indefinitely for legal and compliance purposes.</li>
                    <li><strong>Activity logs</strong> — retained for a minimum of 12 months.</li>
                </ul>

                <h2>7. Your Rights</h2>
                <p>As an authorized user of StegaVault, you have the following rights subject to applicable law:</p>
                <ul>
                    <li><strong>Access:</strong> Request a copy of personal data held about you.</li>
                    <li><strong>Correction:</strong> Update your display name via the profile settings panel.</li>
                    <li><strong>Deletion:</strong> Request account deletion through your system administrator (subject to retention obligations).</li>
                    <li><strong>Portability:</strong> Request an export of your file activity data.</li>
                </ul>
                <p>To exercise these rights, contact your system administrator.</p>

                <h2>8. Cookies & Sessions</h2>
                <p>StegaVault uses server-side PHP sessions to maintain your authenticated state. No third-party tracking cookies are used. Session data is invalidated upon logout.</p>

                <h2>9. Changes to This Policy</h2>
                <p>We may update this Privacy Policy from time to time. Material changes will be communicated by your system administrator. Continued use of StegaVault after updates constitutes acceptance of the revised policy.</p>

                <h2>10. Contact</h2>
                <p>For privacy-related inquiries, please contact your system administrator or reach PGMN Inc. through official internal channels.</p>
            </div>

            <!-- Also see -->
            <div class="mt-6 flex items-center justify-center gap-4 text-sm">
                <a href="terms.php" class="flex items-center gap-1.5 text-primary hover:text-primary/80 font-semibold transition-colors">
                    <span class="material-symbols-outlined text-[16px]">gavel</span>
                    Terms of Service
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
            <a href="terms.php" class="hover:text-primary transition-colors">Terms of Service</a>
            <a href="privacy.php" class="hover:text-primary transition-colors font-semibold text-primary/70">Privacy Policy</a>
        </div>
    </footer>
    <script src="../js/security-shield.js"></script>
</body>
</html>