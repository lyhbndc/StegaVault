<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
    header('Location: admin/login.php');
    exit;
}

$user = [
    'id'   => $_SESSION['user_id'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role'],
];
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <link rel="icon" type="image/png" href="icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>System Guide — StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#667eea",
                        "background-dark": "#101622",
                    },
                    fontFamily: { "display": ["Inter", "sans-serif"] }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; }
        body { font-family: 'Inter', sans-serif; }
        .filled { font-variation-settings: 'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24; }
        .section-anchor { scroll-margin-top: 80px; }
        /* Flow arrow */
        .flow-arrow::after {
            content: '↓';
            display: block;
            text-align: center;
            color: #667eea;
            font-size: 1.25rem;
            margin: 4px 0;
        }
    </style>
</head>
<body class="bg-background-dark text-slate-100 min-h-screen">

<!-- ── TOP NAV ── -->
<header class="h-16 border-b border-slate-800 bg-background-dark/90 backdrop-blur-md sticky top-0 z-50 px-8 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <img src="PGMN%20LOGOS%20white.png" alt="PGMN" class="h-9 w-auto object-contain" />
        <div>
            <p class="text-white font-bold text-sm leading-tight">StegaVault</p>
            <p class="text-slate-400 text-xs">System Guide</p>
        </div>
    </div>
    <a href="admin/dashboard.php" class="flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Dashboard
    </a>
</header>

<div class="max-w-5xl mx-auto px-6 py-12 space-y-20">

    <!-- ── HERO ── -->
    <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/10 border border-primary/20 text-primary text-sm font-semibold mb-2">
            <span class="material-symbols-outlined text-[16px] filled">menu_book</span>
            Plain-English System Guide
        </div>
        <h1 class="text-4xl font-black text-white">How StegaVault Works</h1>
        <p class="text-slate-400 text-lg max-w-2xl mx-auto">A page-by-page walkthrough of every part of the system, written so anyone can understand it — no coding knowledge required.</p>
    </div>

    <!-- ── TABLE OF CONTENTS ── -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
        <h2 class="text-white font-bold text-lg mb-5 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">list</span>
            What's in this guide
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php
            $toc = [
                ['#what-is',       'shield',           'What is StegaVault?'],
                ['#how-files',     'lock',             'How Files Are Protected'],
                ['#login',         'login',            'Login Pages'],
                ['#admin',         'admin_panel_settings', 'Admin Pages'],
                ['#employee',      'badge',            'Employee Pages'],
                ['#collaborator',  'handshake',        'Collaborator Pages'],
                ['#behind',        'settings',         'Behind the Scenes (APIs)'],
                ['#roles',         'group',            'User Roles Explained'],
                ['#mfa',           'security',         'Multi-Factor Authentication'],
                ['#forensic',      'policy',           'Forensic Analysis'],
            ];
            foreach ($toc as [$href, $icon, $label]):
            ?>
            <a href="<?php echo $href; ?>" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/60 hover:bg-slate-700/60 border border-slate-700/50 transition-colors group">
                <span class="material-symbols-outlined text-primary text-[18px]"><?php echo $icon; ?></span>
                <span class="text-sm font-medium text-slate-300 group-hover:text-white transition-colors"><?php echo $label; ?></span>
                <span class="material-symbols-outlined text-slate-600 text-[14px] ml-auto">chevron_right</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── WHAT IS STEGAVAULT ── -->
    <section id="what-is" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-primary/10 rounded-xl">
                <span class="material-symbols-outlined text-primary filled">shield</span>
            </div>
            <h2 class="text-2xl font-black text-white">What is StegaVault?</h2>
        </div>
        <p class="text-slate-300 text-base leading-relaxed">StegaVault is a secure file storage and sharing system for PGMN Inc. Think of it like a private, locked file cabinet — but every time someone takes a document out of the cabinet, their fingerprint is invisibly stamped on it. If that document ever gets leaked, you can scan it and immediately know who took it.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?php
            $pillars = [
                ['lock', 'bg-blue-500/10 text-blue-400 border-blue-500/20',   'Encrypted Storage',    'Files are scrambled when stored so no one can read them without the system key — not even by looking at the server files directly.'],
                ['fingerprint', 'bg-purple-500/10 text-purple-400 border-purple-500/20', 'Invisible Watermarks', 'Every downloaded file gets a hidden signature in its pixels. It\'s invisible to the eye but readable by the system, like a secret tattoo.'],
                ['policy', 'bg-orange-500/10 text-orange-400 border-orange-500/20',    'Leak Tracing',         'If a file leaks, upload it to Forensic Analysis. The system reads the hidden signature and tells you who leaked it, when, and from which device.'],
            ];
            foreach ($pillars as [$icon, $cls, $title, $desc]):
            ?>
            <div class="bg-slate-900 border <?php echo $cls; ?> rounded-xl p-5 space-y-3">
                <span class="material-symbols-outlined text-2xl <?php echo explode(' ', $cls)[1]; ?> filled"><?php echo $icon; ?></span>
                <h3 class="font-bold text-white"><?php echo $title; ?></h3>
                <p class="text-slate-400 text-sm leading-relaxed"><?php echo $desc; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── HOW FILES ARE PROTECTED ── -->
    <section id="how-files" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-emerald-500/10 rounded-xl">
                <span class="material-symbols-outlined text-emerald-400 filled">lock</span>
            </div>
            <h2 class="text-2xl font-black text-white">How Files Are Protected</h2>
        </div>
        <p class="text-slate-300 leading-relaxed">Here is the journey of a file from the moment it's uploaded to when someone downloads it.</p>

        <div class="space-y-1">
            <?php
            $flow = [
                ['upload_file',    'blue',    'Step 1 — Upload',           'A user uploads a file (photo, video, PDF, etc.). The system checks that the file type is safe and there are no duplicates.'],
                ['save',           'indigo',  'Step 2 — Save Raw Copy',    'The original file is saved briefly to a private staging area so the system can process it.'],
                ['enhanced_encryption', 'purple', 'Step 3 — Encrypt',     'The file is scrambled using AES-256 encryption — the same standard used by banks and governments. The scrambled file is stored on the server. Even if someone broke into the server, they\'d only see meaningless data.'],
                ['delete_forever', 'slate',   'Step 4 — Delete Original',  'The original unscrambled copy is deleted. Only the encrypted version remains.'],
                ['download',       'emerald', 'Step 5 — Download Request', 'When someone wants to download a file, the system first checks they have permission.'],
                ['fingerprint',    'cyan',    'Step 6 — Stamp the File',   'Before handing over the file, the system secretly stamps the user\'s identity (name, user ID, IP address, exact time) into the file\'s pixels. This is invisible to the human eye.'],
                ['send',           'teal',    'Step 7 — Serve the File',   'The user receives the file. It looks and behaves completely normally — but it carries an invisible forensic signature.'],
            ];
            foreach ($flow as $i => [$icon, $color, $title, $desc]):
                $colors = [
                    'blue'   => ['bg-blue-500/10',   'text-blue-400',   'border-blue-500/20'],
                    'indigo' => ['bg-indigo-500/10', 'text-indigo-400', 'border-indigo-500/20'],
                    'purple' => ['bg-purple-500/10', 'text-purple-400', 'border-purple-500/20'],
                    'slate'  => ['bg-slate-700/40',  'text-slate-400',  'border-slate-600/30'],
                    'emerald'=> ['bg-emerald-500/10','text-emerald-400','border-emerald-500/20'],
                    'cyan'   => ['bg-cyan-500/10',   'text-cyan-400',   'border-cyan-500/20'],
                    'teal'   => ['bg-teal-500/10',   'text-teal-400',   'border-teal-500/20'],
                ];
                [$bg, $tc, $bc] = $colors[$color];
            ?>
            <div class="flex gap-4 p-5 bg-slate-900 border <?php echo $bc; ?> rounded-xl">
                <div class="flex-shrink-0 w-10 h-10 rounded-full <?php echo $bg; ?> flex items-center justify-center">
                    <span class="material-symbols-outlined <?php echo $tc; ?> text-[20px] filled"><?php echo $icon; ?></span>
                </div>
                <div>
                    <p class="font-bold text-white text-sm"><?php echo $title; ?></p>
                    <p class="text-slate-400 text-sm mt-1 leading-relaxed"><?php echo $desc; ?></p>
                </div>
            </div>
            <?php if ($i < count($flow) - 1): ?>
            <div class="text-center text-primary text-xl leading-none py-1">↓</div>
            <?php endif; endforeach; ?>
        </div>
    </section>

    <!-- ── LOGIN PAGES ── -->
    <section id="login" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-slate-700 rounded-xl">
                <span class="material-symbols-outlined text-slate-300 filled">login</span>
            </div>
            <h2 class="text-2xl font-black text-white">Login Pages</h2>
        </div>
        <p class="text-slate-300 leading-relaxed">There are three separate login pages — one for each type of user. This keeps portals isolated so employees can't accidentally access admin tools.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php
            $logins = [
                ['admin/login.php',         'admin_panel_settings', 'bg-primary/10 text-primary border-primary/20',       'Admin Login',         'For system administrators. Has access to everything: managing users, viewing all files, running forensic analysis.'],
                ['employee/login.php',       'badge',               'bg-blue-500/10 text-blue-400 border-blue-500/20',     'Employee Login',      'For full-time staff. Can upload files, access assigned projects, and view their own activity history.'],
                ['collaborator/login.php',   'handshake',           'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'Collaborator Login', 'For external partners or limited users. Can view and download files they\'ve been given access to.'],
            ];
            foreach ($logins as [$path, $icon, $cls, $title, $desc]):
                $tc = explode(' ', $cls)[1];
            ?>
            <div class="bg-slate-900 border <?php echo $cls; ?> rounded-xl p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="material-symbols-outlined <?php echo $tc; ?> text-2xl filled"><?php echo $icon; ?></span>
                    <span class="text-[10px] font-mono text-slate-600"><?php echo $path; ?></span>
                </div>
                <h3 class="font-bold text-white"><?php echo $title; ?></h3>
                <p class="text-slate-400 text-sm leading-relaxed"><?php echo $desc; ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-slate-900 border border-slate-700 rounded-xl p-5 space-y-3">
            <h3 class="font-semibold text-white flex items-center gap-2"><span class="material-symbols-outlined text-amber-400 text-[18px]">info</span> What happens after you enter your password</h3>
            <ul class="space-y-2 text-sm text-slate-300">
                <li class="flex gap-2"><span class="text-primary font-bold">1.</span> Your email and password are checked against the database. The password is never stored in plain text — only a scrambled version (bcrypt hash) is kept.</li>
                <li class="flex gap-2"><span class="text-primary font-bold">2.</span> If Multi-Factor Authentication (MFA) is turned on, you'll be taken to a second screen to enter the 6-digit code from your authenticator app.</li>
                <li class="flex gap-2"><span class="text-primary font-bold">3.</span> Once verified, a secure session is created and you're taken to your dashboard.</li>
                <li class="flex gap-2"><span class="text-primary font-bold">4.</span> If you're idle for 15 minutes, your session expires automatically for security.</li>
            </ul>
        </div>
    </section>

    <!-- ── ADMIN PAGES ── -->
    <section id="admin" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-primary/10 rounded-xl">
                <span class="material-symbols-outlined text-primary filled">admin_panel_settings</span>
            </div>
            <h2 class="text-2xl font-black text-white">Admin Pages</h2>
        </div>
        <p class="text-slate-300 leading-relaxed">Admins have access to a dedicated portal with tools for managing the entire system.</p>

        <div class="space-y-4">
            <?php
            $adminPages = [
                ['dashboard',        'dashboard',          'bg-primary/10 text-primary',    'Dashboard — admin/dashboard.php',
                 'The first page you see after logging in. Shows a summary of what\'s happening in the system at a glance: how many users there are, recent activity, quick links to common tasks. Think of it as the "control room."'],

                ['group',            'users',              'bg-teal-500/10 text-teal-400',  'User Management — admin/users.php',
                 'Where you create, edit, and deactivate user accounts. You can set their role (employee or collaborator), assign a temporary access period, generate a secure password for them, and resend their activation email if they haven\'t set up their account yet. New users are emailed their login credentials automatically when created.'],

                ['folder_managed',   'projects',           'bg-indigo-500/10 text-indigo-400', 'Projects — admin/projects.php',
                 'Where you create and manage projects. A project is a shared folder that you can invite multiple users into. You can organise files into subfolders, upload files on behalf of the project, and control who has access. Each project has a status: Active, Completed, or Archived.'],

                ['policy',           'analysis',           'bg-orange-500/10 text-orange-400', 'Forensic Analysis — admin/analysis.php',
                 'The leak-detection tool. If a confidential file has been leaked externally, you upload it here. The system scans the file for its hidden watermark, decrypts it, and tells you exactly who downloaded that copy — their name, role, device IP address, and the time of download. It can also detect whether the file was tampered with after download. You can export the findings as a professional PDF report.'],

                ['history',          'activity',           'bg-purple-500/10 text-purple-400', 'Activity Logs — admin/activity.php',
                 'A complete record of everything that has happened in the system. Every login, file view, file download, upload, rename, and user management action is recorded here with a timestamp and the IP address of who did it. You can filter by date, user, action type, or role. Nothing is hidden — this is your audit trail.'],

                ['summarize',        'reports',            'bg-cyan-500/10 text-cyan-400',  'Reports — admin/reports.php',
                 'Statistical summaries and usage charts. Shows things like how many files were downloaded this month, which projects are most active, and a breakdown by user or role. Useful for oversight and compliance reporting.'],

                ['security',         'mfa-settings',       'bg-emerald-500/10 text-emerald-400', 'MFA Settings — admin/mfa-settings.php',
                 'Where admins set up their own Multi-Factor Authentication (MFA). Once enabled, you need both your password and a 6-digit code from an authenticator app (like Google Authenticator) to log in. You also get 10 one-time backup codes in case you lose your phone.'],
            ];
            foreach ($adminPages as [$icon, $id, $cls, $title, $desc]):
                $tc = explode(' ', $cls)[1];
            ?>
            <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-800 <?php echo $cls; ?> bg-opacity-30">
                    <span class="material-symbols-outlined <?php echo $tc; ?> text-[20px] filled"><?php echo $icon; ?></span>
                    <h3 class="font-bold text-white text-sm"><?php echo $title; ?></h3>
                </div>
                <p class="px-5 py-4 text-slate-300 text-sm leading-relaxed"><?php echo $desc; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── EMPLOYEE PAGES ── -->
    <section id="employee" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-blue-500/10 rounded-xl">
                <span class="material-symbols-outlined text-blue-400 filled">badge</span>
            </div>
            <h2 class="text-2xl font-black text-white">Employee Pages</h2>
        </div>
        <p class="text-slate-300 leading-relaxed">Employees see a simplified portal focused on their own files and the projects they've been assigned to.</p>

        <div class="space-y-4">
            <?php
            $empPages = [
                ['dashboard',    'Dashboard — employee/dashboard.php',
                 'The employee\'s home screen. Shows a welcome message, a summary of their recent file activity, and quick access to their workspace. Also shows storage usage and any recent downloads.'],

                ['folder_open',  'Workspace — employee/workspace.php',
                 'The main file management area. Employees can browse the projects they\'ve been added to, open folders, preview files directly in the browser, download files, upload new files to a project, and rename files. Every action here is logged automatically. When a file is downloaded, the invisible watermark is stamped at that moment.'],

                ['history',      'Activity Log — employee/activity.php',
                 'A personal timeline showing only the employee\'s own activity: logins, files they viewed, files they downloaded, and files that were renamed (including by an admin). This helps employees keep track of their own actions and be aware when an admin has accessed their files.'],

                ['person',       'Profile / Settings — employee/profile.php',
                 'Where employees update their own password and manage account preferences. The password must meet the security policy: at least 12 characters, with uppercase, lowercase, a number, and a special character. A live checklist shows which requirements are met as they type.'],

                ['security',     'MFA Settings — employee/mfa-settings.php',
                 'Where employees turn on Multi-Factor Authentication for their account. Once enabled, they\'ll need a code from their authenticator app on every login, in addition to their password.'],
            ];
            foreach ($empPages as [$icon, $title, $desc]):
            ?>
            <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-800 bg-blue-500/5">
                    <span class="material-symbols-outlined text-blue-400 text-[20px] filled"><?php echo $icon; ?></span>
                    <h3 class="font-bold text-white text-sm"><?php echo $title; ?></h3>
                </div>
                <p class="px-5 py-4 text-slate-300 text-sm leading-relaxed"><?php echo $desc; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── COLLABORATOR PAGES ── -->
    <section id="collaborator" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-emerald-500/10 rounded-xl">
                <span class="material-symbols-outlined text-emerald-400 filled">handshake</span>
            </div>
            <h2 class="text-2xl font-black text-white">Collaborator Pages</h2>
        </div>
        <p class="text-slate-300 leading-relaxed">Collaborators are external partners or guests. They have the same basic layout as employees but are typically given more limited access — only to the specific projects an admin has assigned them.</p>
        <div class="bg-slate-900 border border-emerald-500/20 rounded-xl p-5">
            <p class="text-slate-300 text-sm leading-relaxed">Collaborators have the same pages as employees: <strong class="text-white">Dashboard</strong>, <strong class="text-white">Workspace</strong>, <strong class="text-white">Activity Log</strong>, <strong class="text-white">Profile</strong>, and <strong class="text-white">MFA Settings</strong>. The difference is in what they're allowed to do within projects — that's controlled by the admin when assigning them to a project.</p>
        </div>
    </section>

    <!-- ── BEHIND THE SCENES ── -->
    <section id="behind" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-slate-700 rounded-xl">
                <span class="material-symbols-outlined text-slate-300 filled">settings</span>
            </div>
            <h2 class="text-2xl font-black text-white">Behind the Scenes</h2>
        </div>
        <p class="text-slate-300 leading-relaxed">The <code class="bg-slate-800 px-1.5 py-0.5 rounded text-primary text-xs">api/</code> folder contains the "engine room" — scripts that do the actual work when buttons are clicked on screen. You never visit these pages directly; they run quietly in the background.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php
            $apis = [
                ['api/auth.php',            'login',          'Login & Logout',        'Checks your email/password, creates your session, and destroys it when you log out.'],
                ['api/upload.php',           'upload_file',    'File Upload',           'Receives the file, validates it, encrypts it, and saves it to the database.'],
                ['api/download.php',         'download',       'File Download',         'Decrypts the file, stamps the watermark invisibly, and sends it to your browser.'],
                ['api/view.php',             'visibility',     'File Preview',          'Decrypts and shows a file in the browser without downloading it. Logs the view in the activity records.'],
                ['api/projects.php',         'folder_managed', 'Projects & Files',      'Handles creating projects, folders, renaming files, adding members, and deleting things.'],
                ['api/users.php',            'group',          'User Management',       'Creates new accounts, sends activation emails, resets statuses, and manages user data.'],
                ['api/mfa.php',              'security',       'MFA Verification',      'Checks the 6-digit code from your authenticator app, or validates a recovery code if you\'ve lost access to the app.'],
                ['api/settings.php',         'tune',           'Account Settings',      'Handles password changes and theme/colour preferences.'],
                ['api/search.php',           'search',         'Global Search',         'Powers the search bar. Searches across projects, files, and users.'],
                ['api/backup_cron.php',      'backup',         'Automated Backups',     'Runs on a schedule (not from the browser). Makes a full copy of the database and stores it securely.'],
                ['api/forgot-password.php',  'lock_reset',     'Forgot Password',       'Sends a password reset link to your email with a secure one-time token.'],
                ['api/verify.php',           'mark_email_read','Email Verification',    'Confirms your email address is real when you first set up your account.'],
            ];
            foreach ($apis as [$path, $icon, $title, $desc]):
            ?>
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex gap-4">
                <span class="material-symbols-outlined text-primary text-[22px] flex-shrink-0 mt-0.5 filled"><?php echo $icon; ?></span>
                <div>
                    <p class="font-bold text-white text-sm"><?php echo $title; ?></p>
                    <p class="text-[10px] font-mono text-slate-600 mb-1"><?php echo $path; ?></p>
                    <p class="text-slate-400 text-sm leading-relaxed"><?php echo $desc; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── USER ROLES ── -->
    <section id="roles" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-teal-500/10 rounded-xl">
                <span class="material-symbols-outlined text-teal-400 filled">group</span>
            </div>
            <h2 class="text-2xl font-black text-white">User Roles Explained</h2>
        </div>
        <p class="text-slate-300 leading-relaxed">StegaVault has four user types. Each one sees a different version of the system based on what they're allowed to do.</p>

        <div class="space-y-4">
            <?php
            $roles = [
                ['super_admin', 'Super Admin', 'bg-red-500/10 text-red-400 border-red-500/20', 'The top level. Can manage admins, restore backups, view system-wide audit logs, and control the entire platform. Only used for system maintenance.', [
                    'Manage admin accounts',
                    'Create and restore database backups',
                    'View super admin audit logs',
                    'System-level configuration',
                ]],
                ['admin', 'Admin', 'bg-primary/10 text-primary border-primary/20', 'Manages the day-to-day operation of the system. Creates users, runs forensic investigations, and has full visibility over all files and activity.', [
                    'Create and manage users',
                    'Access all projects and files',
                    'Run forensic analysis on leaked files',
                    'View full activity logs for all users',
                    'Generate reports',
                ]],
                ['employee', 'Employee', 'bg-blue-500/10 text-blue-400 border-blue-500/20', 'Regular staff member. Can upload and download files within their assigned projects. Can see their own activity history.', [
                    'Upload and download files',
                    'Access assigned projects',
                    'View personal activity log',
                    'Change own password and MFA',
                ]],
                ['collaborator', 'Collaborator', 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'External partner or guest. Has the same basic access as an employee but is typically given more restricted project permissions by the admin.', [
                    'Access assigned project files',
                    'Download files (with watermark)',
                    'View personal activity log',
                ]],
            ];
            foreach ($roles as [$roleKey, $roleName, $cls, $desc, $perms]):
                $tc = explode(' ', $cls)[1];
            ?>
            <div class="bg-slate-900 border <?php echo $cls; ?> rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold <?php echo $cls; ?> uppercase"><?php echo $roleName; ?></span>
                        <p class="text-slate-300 text-sm"><?php echo $desc; ?></p>
                    </div>
                </div>
                <div class="px-5 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">Can do:</p>
                    <ul class="space-y-1">
                        <?php foreach ($perms as $perm): ?>
                        <li class="flex items-center gap-2 text-sm text-slate-300">
                            <span class="material-symbols-outlined text-[14px] <?php echo $tc; ?>">check_circle</span>
                            <?php echo $perm; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── MFA ── -->
    <section id="mfa" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-emerald-500/10 rounded-xl">
                <span class="material-symbols-outlined text-emerald-400 filled">security</span>
            </div>
            <h2 class="text-2xl font-black text-white">Multi-Factor Authentication (MFA)</h2>
        </div>
        <p class="text-slate-300 leading-relaxed">MFA is an optional extra security step. Instead of just a password, you also need a 6-digit code that changes every 30 seconds. This means even if someone steals your password, they still can't log in without your phone.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-slate-900 border border-emerald-500/20 rounded-xl p-5 space-y-3">
                <h3 class="font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-400 text-[18px]">phone_android</span>
                    Setting it up
                </h3>
                <ol class="space-y-2 text-sm text-slate-300">
                    <li class="flex gap-2"><span class="text-emerald-400 font-bold">1.</span> Go to your MFA Settings page.</li>
                    <li class="flex gap-2"><span class="text-emerald-400 font-bold">2.</span> A QR code appears. Scan it with an app like Google Authenticator or Authy on your phone.</li>
                    <li class="flex gap-2"><span class="text-emerald-400 font-bold">3.</span> The app generates a 6-digit code. Enter it to confirm setup.</li>
                    <li class="flex gap-2"><span class="text-emerald-400 font-bold">4.</span> Save your 10 backup recovery codes somewhere safe.</li>
                </ol>
            </div>
            <div class="bg-slate-900 border border-amber-500/20 rounded-xl p-5 space-y-3">
                <h3 class="font-bold text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-400 text-[18px]">key_off</span>
                    Lost access to your app?
                </h3>
                <p class="text-slate-300 text-sm leading-relaxed">On the MFA login screen, click "Don't have access to your authenticator?" and enter one of your recovery codes. Each code can only be used once. Recovery code format: <code class="bg-slate-800 px-1.5 py-0.5 rounded text-primary text-xs">XXXXXXXX-XXXXXXXX</code></p>
                <p class="text-slate-400 text-xs">If you've used all your recovery codes, contact an admin to disable MFA on your account.</p>
            </div>
        </div>
    </section>

    <!-- ── FORENSIC ANALYSIS ── -->
    <section id="forensic" class="section-anchor space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-orange-500/10 rounded-xl">
                <span class="material-symbols-outlined text-orange-400 filled">policy</span>
            </div>
            <h2 class="text-2xl font-black text-white">Forensic Analysis — Tracing a Leak</h2>
        </div>
        <p class="text-slate-300 leading-relaxed">This is StegaVault's most powerful feature. If a confidential file has been shared outside the organisation without permission, here's how you investigate it.</p>

        <div class="bg-slate-900 border border-orange-500/20 rounded-xl overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-800">
                <h3 class="font-bold text-white">Step-by-step: How to catch a leak</h3>
            </div>
            <div class="divide-y divide-slate-800">
                <?php
                $steps = [
                    ['Obtain the leaked file',    'Get a copy of the file that was leaked — screenshot, forwarded email, found online, etc.'],
                    ['Go to Forensic Analysis',   'Log in as admin and navigate to Forensic Analysis from the sidebar.'],
                    ['Upload the suspect file',   'Click "Select File" and upload the leaked copy. The system will immediately begin scanning it.'],
                    ['Read the results',          'If the file was downloaded from StegaVault, the system will show you: who downloaded it (name, role, user ID), the IP address of the device they used, and the exact date and time. If the file was tampered with after download, the system will flag that too.'],
                    ['Export the report',         'Click "Export Forensic Report (PDF)" to generate a professional, printable document with all the findings, suitable for HR, legal, or compliance use.'],
                ];
                foreach ($steps as $i => [$title, $desc]):
                ?>
                <div class="px-6 py-4 flex gap-4">
                    <div class="flex-shrink-0 w-7 h-7 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-400 font-bold text-xs"><?php echo $i + 1; ?></div>
                    <div>
                        <p class="font-semibold text-white text-sm"><?php echo $title; ?></p>
                        <p class="text-slate-400 text-sm mt-0.5"><?php echo $desc; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-slate-900 border border-slate-700 rounded-xl p-5">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">What it can tell you</p>
                <ul class="space-y-2 text-sm text-slate-300">
                    <li class="flex gap-2"><span class="text-emerald-400">✓</span> Who downloaded the file</li>
                    <li class="flex gap-2"><span class="text-emerald-400">✓</span> Their role in the organisation</li>
                    <li class="flex gap-2"><span class="text-emerald-400">✓</span> The IP address of their device</li>
                    <li class="flex gap-2"><span class="text-emerald-400">✓</span> Exact date and time of download</li>
                    <li class="flex gap-2"><span class="text-emerald-400">✓</span> Whether the file was edited after download</li>
                    <li class="flex gap-2"><span class="text-emerald-400">✓</span> Cryptographic proof (signature verification)</li>
                </ul>
            </div>
            <div class="bg-slate-900 border border-slate-700 rounded-xl p-5">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-3">Limitations</p>
                <ul class="space-y-2 text-sm text-slate-300">
                    <li class="flex gap-2"><span class="text-amber-400">!</span> Only works on PNG image files (watermark is embedded in pixels)</li>
                    <li class="flex gap-2"><span class="text-amber-400">!</span> If the image was heavily compressed or converted to another format, the watermark may be destroyed</li>
                    <li class="flex gap-2"><span class="text-amber-400">!</span> Files not originally from StegaVault will show "External / Unknown" origin</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ── FOOTER ── -->
    <div class="border-t border-slate-800 pt-10 text-center space-y-2">
        <p class="text-slate-500 text-sm">StegaVault Security Suite — PGMN Inc.</p>
        <p class="text-slate-600 text-xs">© 2026 Peanut Gallery Media Network. For technical documentation, see the README.md file.</p>
        <a href="admin/dashboard.php" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-primary hover:bg-primary/90 text-white text-sm font-semibold rounded-lg transition-colors">
            <span class="material-symbols-outlined text-[18px]">dashboard</span>
            Back to Dashboard
        </a>
    </div>

</div>

<script src="js/security-shield.js"></script>
</body>
</html>
