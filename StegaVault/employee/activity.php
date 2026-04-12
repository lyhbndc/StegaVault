<?php

/**
 * StegaVault - Employee Activity Log
 * File: employee/activity.php
 */

session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'employee') {
    header('Location: ../index.html');
    exit;
}

$user = [
    'id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role']
];

$userId = $user['id'];

// Get activity logs for this user
$stmt = $db->prepare("
    SELECT action, description, created_at 
        FROM activity_log_employee 
    WHERE user_id = ?
      AND action IN ('login_success', 'file_downloaded')
    ORDER BY created_at DESC 
    LIMIT 100
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$activities = $stmt->get_result();

function fmtDate($dateStr)
{
    if (!$dateStr) return '';
    $ts = strtotime($dateStr);
    return date('M j, Y g:i A', $ts);
}

function getActionIcon($action)
{
    switch ($action) {
        case 'login_success':
            return ['login', 'text-emerald-500', 'bg-emerald-500/10'];
        case 'file_downloaded':
            return ['download', 'text-blue-500', 'bg-blue-500/10'];
        default:
            return ['info', 'text-slate-500', 'bg-slate-500/10'];
    }
}

function formatActionText($action, $desc)
{
    switch ($action) {
        case 'login_success':
            return "<b>Logged in</b><br/><span class='text-xs text-slate-500 dark:text-slate-400'>$desc</span>";
        case 'file_downloaded':
            return "<b>Downloaded file</b><br/><span class='text-xs text-slate-500 dark:text-slate-400'>$desc</span>";
        default:
            return "<b>Activity</b><br/><span class='text-xs text-slate-500 dark:text-slate-400'>$desc</span>";
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Activity Log - StegaVault</title>
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
                        "background-light": "#f5f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <?php include '../includes/theme_color.php'; ?>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex">

    <!-- ═══════════════════════════════════════
         FIXED LEFT SIDEBAR
    ═══════════════════════════════════════ -->
    <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full">
            <div class="flex items-center gap-3 mb-10">
                <img src="../PGMN%20LOGOS%20white.png" alt="PGMN Inc. Logo" class="h-12 w-auto object-contain dark:invert-0 invert" />
                <div class="flex flex-col justify-center">
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">PGMN Inc.</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Employee Portal</p>
                </div>
            </div>

            <nav class="flex flex-col gap-1 flex-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[22px]">dashboard</span>
                    <p class="text-sm font-medium">Dashboard</p>
                </a>
                <a href="workspace.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[22px]">folder_open</span>
                    <p class="text-sm font-medium">Workspace</p>
                </a>
                <a href="activity.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white">
                    <span class="material-symbols-outlined text-[22px]" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">history</span>
                    <p class="text-sm font-medium">Activity Log</p>
                </a>
            </nav>

            <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                <button onclick="openSettings()" class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group text-left">
                    <div class="bg-primary rounded-full size-10 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-slate-900 dark:text-white text-sm font-semibold truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-slate-500 dark:text-slate-400 text-xs capitalize"><?php echo htmlspecialchars($user['role']); ?></p>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 group-hover:text-primary text-[18px] transition-colors">settings</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- ═══════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════ -->
    <main class="flex-1 ml-64 flex flex-col">
        <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center gap-6">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight">Activity Log</h2>
            <?php include '../includes/search_bar.php'; ?>
        </header>

        <div class="p-8 max-w-4xl">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Recent Activity</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">A timeline of your login and file download activity.</p>
                </div>
                <div class="p-6">
                    <div class="relative border-l-2 border-slate-100 dark:border-slate-800/60 ml-3 md:ml-4 space-y-8 pb-4">
                        <?php if ($activities->num_rows > 0): ?>
                            <?php while ($log = $activities->fetch_assoc()):
                                list($icon, $color, $bg) = getActionIcon($log['action']);
                            ?>
                                <div class="relative pl-8 md:pl-10">
                                    <div class="absolute -left-[17px] top-1 rounded-full size-8 flex items-center justify-center ring-4 ring-white dark:ring-slate-900 <?php echo $bg; ?>">
                                        <span class="material-symbols-outlined text-[16px] <?php echo $color; ?>"><?php echo $icon; ?></span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                                        <div>
                                            <p class="text-sm text-slate-800 dark:text-slate-200">
                                                <?php echo formatActionText($log['action'], htmlspecialchars($log['description'])); ?>
                                            </p>
                                        </div>
                                        <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 whitespace-nowrap">
                                            <?php echo fmtDate($log['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="pl-8 py-8 text-center text-slate-400 text-sm">No recent activity.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?php include '../includes/settings_modal.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serverColor = '<?php echo $themeColor; ?>';
            if (window._svColor) {
                window._svColor.apply(serverColor);
                window._svColor.buildSwatches();
                try {
                    const uid = '<?php echo (int)($user["id"] ?? 0); ?>';
                    localStorage.setItem('sv_accent_' + uid, serverColor);
                } catch (e) {}
            }
        });
    </script>
</body>

</html>