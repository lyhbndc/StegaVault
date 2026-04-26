<?php

/**
 * StegaVault - Project Management (FIXED)
 * File: admin/projects.php
 */

session_start();
require_once '../includes/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.html');
    exit;
}

$user = [
    'id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role']
];

// Get all projects with task progress stats
$stmt = $db->prepare("SELECT p.*, u.name as creator_name,
                      COUNT(DISTINCT pm.user_id) as member_count,
                      COALESCE((SELECT ROUND(AVG(progress)) FROM project_tasks WHERE project_id = p.id), 0) as avg_progress,
                      (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id) as task_count,
                      (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                      FROM projects p
                      LEFT JOIN users u ON p.created_by = u.id
                      LEFT JOIN project_members pm ON p.id = pm.project_id
                      GROUP BY p.id, u.name
                      ORDER BY p.created_at DESC");
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$activeProjects = [];
$inactiveProjects = [];
foreach ($projects as $proj) {
    if (($proj['status'] ?? 'active') === 'inactive') {
        $inactiveProjects[] = $proj;
    } else {
        $activeProjects[] = $proj;
    }
}

// Get all users for member selection
$usersResult = $db->query("SELECT id, name, email, role FROM users WHERE role != 'admin' ORDER BY name");
$users = $usersResult->fetch_all(MYSQLI_ASSOC);

// Get selected project details if viewing one
$selectedProject = null;
$projectMembers = [];
if (isset($_GET['project_id'])) {
    $projectId = (int)$_GET['project_id'];
    $stmt = $db->prepare("SELECT p.*,
                          COALESCE((SELECT ROUND(AVG(progress)) FROM project_tasks WHERE project_id = p.id), 0) as avg_progress,
                          (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id) as task_count,
                          (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
                          FROM projects p WHERE p.id = ?");
    $stmt->bind_param('i', $projectId);
    $stmt->execute();
    $selectedProject = $stmt->get_result()->fetch_assoc();

    if ($selectedProject) {
        $stmt = $db->prepare("SELECT pm.*, u.name, u.email, u.role FROM project_members pm 
                              JOIN users u ON pm.user_id = u.id 
                              WHERE pm.project_id = ?");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $projectMembers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Project Management - StegaVault</title>
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
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
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

        .modal-hidden {
            display: none;
        }

        .modal-visible {
            display: flex;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex">

    <!-- ═══════════════════════════════════════
         CREATE PROJECT MODAL
    ═══════════════════════════════════════ -->
    <div id="createModal" class="modal-hidden fixed inset-0 z-[100] items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-900 w-full max-w-4xl max-h-[90vh] rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col overflow-hidden">
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <span class="material-symbols-outlined text-primary text-[20px]">add_moderator</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Create New Project</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Initialize a new secure workspace</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form id="createProjectForm" onsubmit="createProject(event)" class="flex-1 overflow-y-auto p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Project Name *</label>
                        <input name="name" required type="text" placeholder="e.g. Project Cipher X"
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Description *</label>
                        <input name="description" required type="text" placeholder="Brief purpose..."
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm" />
                    </div>
                </div>

                <!-- Color Picker -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Project Color</label>
                    <div class="flex gap-2">
                        <?php $colors = ['#667eea', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#6366f1']; ?>
                        <?php foreach ($colors as $i => $color): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="<?php echo $color; ?>" <?php echo $i === 0 ? 'checked' : ''; ?> class="sr-only" />
                                <div class="size-8 rounded-lg border-2 border-transparent hover:border-slate-400 transition-all" style="background-color: <?php echo $color; ?>"></div>
                            </label>
                        <?php
endforeach; ?>
                    </div>
                </div>

                <!-- Team Members -->
                <div class="space-y-3">
                    <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary">group_add</span>
                        Team Members <span class="text-slate-400 font-normal">(Optional)</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[280px]">
                        <!-- Available Users -->
                        <div class="flex flex-col border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800/30 overflow-hidden">
                            <div class="p-3 border-b border-slate-200 dark:border-slate-700">
                                <input id="searchMembers" type="text" onkeyup="searchMembers()" placeholder="Search users..."
                                    class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs py-2 px-3 text-slate-900 dark:text-white placeholder:text-slate-400 outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" />
                            </div>
                            <div id="availableUsers" class="flex-1 overflow-y-auto p-2 space-y-1">
                                <?php foreach ($users as $u): ?>
                                    <div class="user-item flex items-center justify-between p-2 rounded-lg hover:bg-white dark:hover:bg-slate-800 cursor-pointer group transition-colors"
                                        data-name="<?php echo htmlspecialchars($u['name']); ?>"
                                        data-email="<?php echo htmlspecialchars($u['email']); ?>">
                                        <div class="flex items-center gap-2">
                                            <div class="size-7 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">
                                                <?php echo strtoupper(substr($u['name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <span class="text-xs font-semibold text-slate-900 dark:text-white block"><?php echo htmlspecialchars($u['name']); ?></span>
                                                <span class="text-[10px] text-slate-500"><?php echo htmlspecialchars($u['role']); ?></span>
                                            </div>
                                        </div>
                                        <button type="button" onclick="addMember(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(str_replace("'", "\\'", $u['name'])); ?>', '<?php echo htmlspecialchars($u['role']); ?>')"
                                            class="text-slate-300 dark:text-slate-600 hover:text-primary opacity-0 group-hover:opacity-100 transition-all">
                                            <span class="material-symbols-outlined text-[20px]">add_circle</span>
                                        </button>
                                    </div>
                                <?php
endforeach; ?>
                            </div>
                        </div>

                        <!-- Selected Members -->
                        <div class="flex flex-col border border-primary/20 rounded-xl bg-primary/5 overflow-hidden">
                            <div class="p-3 border-b border-primary/10">
                                <span class="text-xs font-bold text-primary uppercase tracking-wider">Selected (<span id="memberCount">0</span>)</span>
                            </div>
                            <div id="selectedMembers" class="flex-1 overflow-y-auto p-2 space-y-2">
                                <p class="text-center text-slate-400 dark:text-slate-500 text-xs py-4">No members selected</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Initial Tasks -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-emerald-500">add_task</span>
                            Initial Tasks <span class="text-slate-400 font-normal">(Optional)</span>
                        </h3>
                        <button type="button" onclick="addPendingTaskRow()"
                            class="flex items-center gap-1 px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-600 dark:text-emerald-400 hover:text-white text-xs font-bold rounded-lg transition-all border border-emerald-500/20 hover:border-emerald-500">
                            <span class="material-symbols-outlined text-sm">add</span>Add Task
                        </button>
                    </div>
                    <div id="pendingTasksList" class="space-y-2">
                        <p id="pendingTasksEmpty" class="text-xs text-slate-400 dark:text-slate-500 italic px-1">No tasks added yet. Tasks can be assigned to selected team members.</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-all shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">rocket_launch</span>
                        Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════
         FIXED LEFT SIDEBAR
    ═══════════════════════════════════════ -->
    <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10">
                <img src="../PGMN%20LOGOS%20white.png" alt="PGMN Inc. Logo" class="h-12 w-auto object-contain dark:invert-0 invert" />
                <div class="flex flex-col justify-center">
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">PGMN Inc.</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Security Suite</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex flex-col gap-1 flex-1">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="dashboard.php">
                    <span class="material-symbols-outlined text-[22px]">dashboard</span>
                    <p class="text-sm font-medium">Dashboard</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white" href="projects.php">
                    <span class="material-symbols-outlined text-[22px]" style="font-variation-settings: 'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">folder_managed</span>
                    <p class="text-sm font-medium">Projects</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="analysis.php">
                    <span class="material-symbols-outlined text-[22px]">policy</span>
                    <p class="text-sm font-medium">Forensic Analysis</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="users.php">
                    <span class="material-symbols-outlined text-[22px]">group</span>
                    <p class="text-sm font-medium">User Management</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="activity.php">
                    <span class="material-symbols-outlined text-[22px]">history</span>
                    <p class="text-sm font-medium">Activity Logs</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="reports.php">
                    <span class="material-symbols-outlined text-[22px]">summarize</span>
                    <p class="text-sm font-medium">Reports</p>
                </a>
            </nav>

            <!-- User Profile (click to open settings) -->
            <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                <button onclick="openSettings()" class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group text-left">
                    <div id="sidebarProfileAvatar" class="bg-primary rounded-full size-10 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p id="sidebarProfileName" class="text-slate-900 dark:text-white text-sm font-semibold truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-slate-500 dark:text-slate-400 text-xs capitalize"><?php echo htmlspecialchars($user['role']); ?></p>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 group-hover:text-primary text-[18px] transition-colors">settings</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- ═══════════════════════════════════════
         MAIN CONTENT AREA
    ═══════════════════════════════════════ -->
    <div class="flex-1 ml-64 flex flex-col min-h-screen">

        <!-- Sticky Top Header -->
        <header class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center gap-6">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight flex-shrink-0">Projects</h2>
            <?php include '../includes/search_bar.php'; ?>
            <div class="flex items-center gap-3 flex-shrink-0">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-500 text-xs font-semibold">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    System: Operational
                </div>
                <button onclick="openModal()" class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-2 px-4 rounded-lg transition-all shadow-sm text-sm">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    New Project
                </button>
            </div>
        </header>

        <!-- Two-column layout: project list + detail -->
        <div class="flex flex-1 overflow-hidden">

            <!-- Project List Panel (sidebar) -->
            <aside class="w-72 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col overflow-y-auto flex-shrink-0">
                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-slate-900 dark:text-white text-sm font-bold">All Projects</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5"><?php echo count($projects); ?> total workspaces</p>
                </div>

                <nav class="flex flex-col gap-1 p-3">
                    <?php if (count($activeProjects) === 0 && count($inactiveProjects) === 0): ?>
                        <div class="px-3 py-8 text-center">
                            <span class="material-symbols-outlined text-3xl text-slate-300 dark:text-slate-600 block mb-2">folder_off</span>
                            <p class="text-slate-400 dark:text-slate-500 text-xs">No projects yet</p>
                        </div>
                    <?php endif; ?>

                    <?php if (count($activeProjects) > 0): ?>
                        <div class="px-3 py-2 mb-1">
                            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Active Projects</h4>
                        </div>
                        <?php foreach ($activeProjects as $proj): ?>
                            <?php $isActive = $selectedProject && $selectedProject['id'] == $proj['id']; ?>
                            <div class="relative group/item">
                                <a href="?project_id=<?php echo $proj['id']; ?>"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors pr-9 <?php echo $isActive ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'; ?>">
                                    <div class="size-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                        style="background-color: <?php echo htmlspecialchars($proj['color'] ?? '#667eea'); ?>20;">
                                        <span class="material-symbols-outlined text-[18px]" style="color: <?php echo htmlspecialchars($proj['color'] ?? '#667eea'); ?>">folder</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($proj['name']); ?></p>
                                        <p class="text-xs <?php echo $isActive ? 'text-white/70' : 'text-slate-500 dark:text-slate-500'; ?>"><?php echo $proj['member_count']; ?> member<?php echo $proj['member_count'] != 1 ? 's' : ''; ?></p>
                                        <?php if ((int)$proj['task_count'] > 0): ?>
                                        <div class="flex items-center gap-1.5 mt-1.5">
                                            <div class="flex-1 <?php echo $isActive ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700'; ?> rounded-full h-1 overflow-hidden">
                                                <div class="h-full rounded-full <?php echo $isActive ? 'bg-white' : 'bg-primary'; ?> transition-all" style="width:<?php echo (int)$proj['avg_progress']; ?>%"></div>
                                            </div>
                                            <span class="text-[10px] font-semibold flex-shrink-0 <?php echo $isActive ? 'text-white/80' : 'text-slate-400 dark:text-slate-500'; ?>"><?php echo (int)$proj['avg_progress']; ?>%</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <button
                                    onclick="event.preventDefault(); event.stopPropagation(); openProjectMenu(event, <?php echo $proj['id']; ?>, '<?php echo addslashes(htmlspecialchars($proj['name'])); ?>', 'active')"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-md <?php echo $isActive ? 'text-white/70 hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700'; ?> transition-colors opacity-0 group-hover/item:opacity-100 focus:opacity-100"
                                    title="More options">
                                    <span class="material-symbols-outlined text-[16px]">more_vert</span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (count($inactiveProjects) > 0): ?>
                        <div class="px-3 py-2 mt-4 mb-1 border-t border-slate-100 dark:border-slate-800/50 pt-4">
                            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Inactive Projects</h4>
                        </div>
                        <?php foreach ($inactiveProjects as $proj): ?>
                            <?php $isActive = $selectedProject && $selectedProject['id'] == $proj['id']; ?>
                            <div class="relative group/item">
                                <a href="?project_id=<?php echo $proj['id']; ?>"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors pr-9 opacity-60 grayscale-[0.5] <?php echo $isActive ? 'bg-primary text-white grayscale-0 opacity-100' : 'text-slate-500 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:opacity-100 hover:grayscale-0'; ?>">
                                    <div class="size-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                        style="background-color: <?php echo htmlspecialchars($proj['color'] ?? '#667eea'); ?>10;">
                                        <span class="material-symbols-outlined text-[18px]" style="color: <?php echo htmlspecialchars($proj['color'] ?? '#667eea'); ?>;">folder_off</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($proj['name']); ?></p>
                                        <p class="text-xs <?php echo $isActive ? 'text-white/70' : 'text-slate-500 dark:text-slate-600'; ?>">Inactive</p>
                                    </div>
                                </a>
                                <button
                                    onclick="event.preventDefault(); event.stopPropagation(); openProjectMenu(event, <?php echo $proj['id']; ?>, '<?php echo addslashes(htmlspecialchars($proj['name'])); ?>', 'inactive')"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded-md <?php echo $isActive ? 'text-white/70 hover:bg-white/20' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700'; ?> transition-colors opacity-0 group-hover/item:opacity-100 focus:opacity-100"
                                    title="More options">
                                    <span class="material-symbols-outlined text-[16px]">more_vert</span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </nav>
            </aside>

            <!-- Main scrollable content -->
            <main class="flex-1 overflow-y-auto bg-slate-50 dark:bg-background-dark/50 p-8">
                <div class="max-w-5xl mx-auto">

                    <?php if ($selectedProject): ?>

                        <!-- Project Header -->
                        <div class="flex items-start justify-between mb-6">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="size-3 rounded-full" style="background-color: <?php echo htmlspecialchars($selectedProject['color'] ?? '#667eea'); ?>"></span>
                                    <?php if (($selectedProject['status'] ?? 'active') === 'inactive'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 uppercase tracking-wide">Inactive</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 uppercase tracking-wide">Active</span>
                                    <?php endif; ?>
                                    <span class="text-xs text-slate-400 dark:text-slate-500">Created <?php echo date('M d, Y', strtotime($selectedProject['created_at'])); ?></span>
                                </div>
                                <h1 class="text-slate-900 dark:text-white text-2xl font-bold"><?php echo htmlspecialchars($selectedProject['name']); ?></h1>
                                <!-- Description (inline editable) -->
                                <div id="descDisplay" class="group/desc flex items-start gap-1 mt-0.5">
                                    <p id="descText" class="text-slate-500 dark:text-slate-400 text-sm"><?php echo htmlspecialchars($selectedProject['description'] ?? ''); ?></p>
                                    <?php if (empty($selectedProject['description'])): ?>
                                        <span id="descPlaceholder" class="text-slate-400 dark:text-slate-600 text-sm italic">No description</span>
                                    <?php
    endif; ?>
                                    <button onclick="startEditDesc()" title="Edit description"
                                        class="ml-1 p-0.5 rounded text-slate-300 dark:text-slate-600 hover:text-primary opacity-0 group-hover/desc:opacity-100 transition-opacity flex-shrink-0 mt-0.5">
                                        <span class="material-symbols-outlined text-[15px]">edit</span>
                                    </button>
                                </div>
                                <div id="descEdit" class="hidden mt-0.5">
                                    <textarea id="descInput" rows="2"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border border-primary/40 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/30 focus:border-primary outline-none resize-none transition-all"
                                        placeholder="Add a project description…"><?php echo htmlspecialchars($selectedProject['description'] ?? ''); ?></textarea>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <button onclick="saveDesc()" class="px-3 py-1 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-primary/90 transition-colors">Save</button>
                                        <button onclick="cancelEditDesc()" class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Cancel</button>
                                    </div>
                                </div>

                                <?php
                                    $avgProgress    = (int)($selectedProject['avg_progress'] ?? 0);
                                    $taskCount      = (int)($selectedProject['task_count'] ?? 0);
                                    $completedTasks = (int)($selectedProject['completed_tasks'] ?? 0);
                                    $progressColor  = $avgProgress >= 100 ? 'bg-emerald-500' : ($avgProgress >= 50 ? 'bg-primary' : 'bg-amber-500');
                                ?>
                                <!-- Project Progress Bar -->
                                <div class="mt-4 p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm" id="projectProgressCard">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[15px] text-primary">analytics</span>
                                            Project Progress
                                        </span>
                                        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                            <?php if ($taskCount > 0): ?>
                                                <span id="projectProgressStats"><?php echo $completedTasks; ?>/<?php echo $taskCount; ?> tasks done</span>
                                            <?php else: ?>
                                                <span id="projectProgressStats" class="italic">No tasks yet</span>
                                            <?php endif; ?>
                                            <span id="projectProgressPct" class="font-bold text-slate-800 dark:text-white text-sm"><?php echo $avgProgress; ?>%</span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                        <div id="projectProgressBar" class="h-full rounded-full transition-all duration-500 <?php echo $progressColor; ?>" style="width:<?php echo $avgProgress; ?>%"></div>
                                    </div>
                                    <?php if ($taskCount > 0 && $avgProgress >= 100): ?>
                                        <p id="projectProgressNote" class="text-[11px] text-emerald-500 font-semibold mt-1.5 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[13px]">check_circle</span> All tasks completed!
                                        </p>
                                    <?php elseif ($taskCount > 0): ?>
                                        <p id="projectProgressNote" class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5"><?php echo $taskCount - $completedTasks; ?> task<?php echo ($taskCount - $completedTasks) != 1 ? 's' : ''; ?> remaining</p>
                                    <?php else: ?>
                                        <p id="projectProgressNote" class="text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 italic">No tasks yet</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <!-- Folder actions -->
                                <div id="folderActionBtns" class="flex items-center gap-2">
                                    <div id="folderBreadcrumb" class="hidden items-center gap-1 text-xs text-slate-400 flex-wrap"></div>
                                    <button onclick="openAdminCreateFolderModal(_adminCurrentFolderId)"
                                        class="flex items-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-lg transition-all shadow-sm border border-slate-200 dark:border-slate-700">
                                        <span class="material-symbols-outlined text-[17px]">create_new_folder</span>
                                        New Folder
                                    </button>
                                    <button onclick="openFolderUploadModal()" class="flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-all shadow-sm">
                                        <span class="material-symbols-outlined text-[17px]">cloud_upload</span>
                                        Upload Files
                                    </button>
                                </div>
                                <button onclick="deleteProject(<?php echo $selectedProject['id']; ?>)"
                                    class="flex items-center gap-2 px-3.5 py-2 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white text-sm font-semibold rounded-lg transition-all border border-red-500/20 hover:border-red-500">
                                    <span class="material-symbols-outlined text-[17px]">delete</span>
                                    Delete
                                </button>
                            </div>
                        </div>

                        <!-- Main Grid: 2/3 content + 1/3 sidebar -->
                        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                            <!-- Left: Unified File Browser (2/3) -->
                            <div class="xl:col-span-2">

                                <!-- Section header -->
                                <div class="flex items-center justify-between px-1 mb-2">
                                    <!-- Title + inline breadcrumb -->
                                    <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                                        <h2 class="text-slate-900 dark:text-white text-base font-bold flex-shrink-0">Files & Folders</h2>
                                        <!-- Breadcrumb (shown when inside a folder) -->
                                        <div id="paneBreadcrumb" class="hidden items-center gap-1 text-xs text-slate-400 flex-wrap"></div>
                                    </div>
                                    <!-- Sub-pane Actions (empty for unified feel) -->
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                    </div>
                                </div>

                                <!-- Toolbar -->
                                <div class="flex items-center gap-2 mb-3 flex-wrap">
                                    <!-- Filter chips (files only) -->
                                    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
                                        <button id="fChip-all" onclick="setFileFilter('all')" class="px-3 py-1 rounded-md text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm transition-all">All</button>
                                        <button id="fChip-image" onclick="setFileFilter('image')" class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Images</button>
                                        <button id="fChip-video" onclick="setFileFilter('video')" class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Videos</button>
                                        <button id="fChip-doc" onclick="setFileFilter('doc')" class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Docs</button>
                                    </div>
                                    <!-- Sort -->
                                    <div class="relative">
                                        <select id="fileSortSelect" onchange="setFileSort(this.value)"
                                            class="appearance-none pl-3 pr-8 py-1.5 text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 cursor-pointer">
                                            <option value="date-desc">Newest first</option>
                                            <option value="date-asc">Oldest first</option>
                                            <option value="name-asc">Name A→Z</option>
                                            <option value="name-desc">Name Z→A</option>
                                            <option value="size-desc">Largest first</option>
                                            <option value="size-asc">Smallest first</option>
                                        </select>
                                        <span class="material-symbols-outlined text-[14px] text-slate-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">unfold_more</span>
                                    </div>
                                    <!-- View toggle -->
                                    <div class="ml-auto flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
                                        <button id="vBtn-list" onclick="setFileView('list')" title="List view"
                                            class="p-1.5 rounded-md bg-white dark:bg-slate-700 text-slate-700 dark:text-white shadow-sm transition-all">
                                            <span class="material-symbols-outlined text-[16px]">view_list</span>
                                        </button>
                                        <button id="vBtn-grid" onclick="setFileView('grid')" title="Grid view"
                                            class="p-1.5 rounded-md text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition-all">
                                            <span class="material-symbols-outlined text-[16px]">grid_view</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Upload progress bar -->
                                <div id="uploadProgress" class="hidden mb-3 px-4 py-3 bg-primary/5 border border-primary/10 rounded-xl">
                                    <div class="flex justify-between text-xs font-bold text-primary mb-1.5">
                                        <span>Uploading...</span><span id="uploadPercent">0%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5">
                                        <div id="uploadBar" class="bg-primary h-1.5 rounded-full transition-all duration-300" style="width:0%"></div>
                                    </div>
                                </div>

                                <!-- Unified file browser pane (drop zone) -->
                                <div id="filesPaneWrapper" class="relative">
                                    <div id="filesPane" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm transition-all">
                                        <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">Loading...</div>
                                    </div>
                                    <!-- Drop overlay -->
                                    <div id="dropOverlay" class="hidden absolute inset-0 z-20 rounded-xl border-2 border-dashed border-primary bg-primary/10 backdrop-blur-sm flex flex-col items-center justify-center gap-3 pointer-events-none">
                                        <div class="size-16 rounded-2xl bg-primary/20 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-4xl text-primary" style="font-variation-settings:'FILL' 1">cloud_upload</span>
                                        </div>
                                        <p class="text-primary font-bold text-base">Drop files to upload</p>
                                        <p class="text-primary/70 text-xs">Images, Videos, PDFs, Docs</p>
                                    </div>
                                    <!-- Footer -->
                                    <div id="paneFooter" class="hidden mt-0 px-4 py-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-400 dark:text-slate-500 font-medium"></div>
                                </div>

                            </div>

                            <!-- Right: Members + Info (1/3) -->
                            <div class="space-y-4">

                                <!-- Members Header -->
                                <div class="flex items-center justify-between px-1">
                                    <h2 class="text-slate-900 dark:text-white text-base font-bold">Team Members</h2>
                                    <button onclick="openAddMemberModal()" title="Add member"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 hover:bg-primary text-primary hover:text-white text-xs font-bold rounded-lg transition-all border border-primary/20 hover:border-primary">
                                        <span class="material-symbols-outlined text-sm">person_add</span>
                                        Add
                                    </button>
                                </div>

                                <!-- Members List -->
                                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                                    <?php if (count($projectMembers) === 0): ?>
                                        <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">No members yet</div>
                                    <?php
    else: ?>
                                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <?php foreach ($projectMembers as $member): ?>
                                                <div class="px-5 py-3.5 flex items-center gap-3 group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                                    <div class="size-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">
                                                        <?php echo strtoupper(substr($member['name'], 0, 2)); ?>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($member['name']); ?></p>
                                                        <div class="flex items-center gap-1.5 mt-0.5">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-primary/10 text-primary border border-primary/20 uppercase"><?php echo $member['role']; ?></span>
                                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 truncate"><?php echo htmlspecialchars($member['email']); ?></span>
                                                        </div>
                                                    </div>
                                                    <button onclick="removeMember(<?php echo $selectedProject['id']; ?>, <?php echo $member['user_id']; ?>)"
                                                        class="p-1.5 rounded-lg text-slate-300 dark:text-slate-600 hover:text-red-500 hover:bg-red-500/10 transition-colors opacity-0 group-hover:opacity-100 flex-shrink-0" title="Remove member">
                                                        <span class="material-symbols-outlined text-[16px]">person_remove</span>
                                                    </button>
                                                </div>
                                            <?php
        endforeach; ?>
                                        </div>
                                    <?php
    endif; ?>
                                </div>

                                <!-- Tasks Section -->
                                <div class="flex items-center justify-between px-1">
                                    <h2 class="text-slate-900 dark:text-white text-base font-bold">Tasks</h2>
                                    <button onclick="openCreateTaskModal()"
                                        class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-600 dark:text-emerald-400 hover:text-white text-xs font-bold rounded-lg transition-all border border-emerald-500/20 hover:border-emerald-500">
                                        <span class="material-symbols-outlined text-sm">add_task</span>
                                        Add Task
                                    </button>
                                </div>

                                <div id="tasksPanel" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                                    <div class="p-6 text-center text-slate-400 dark:text-slate-500 text-sm">Loading tasks…</div>
                                </div>

                                <!-- Info Card -->
                                <div class="bg-gradient-to-br from-primary/10 to-purple-500/10 border border-primary/20 rounded-xl p-5">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 bg-primary/20 rounded-lg flex-shrink-0">
                                            <span class="material-symbols-outlined text-primary text-[18px]">admin_panel_settings</span>
                                        </div>
                                        <div>
                                            <h4 class="text-slate-900 dark:text-white font-bold text-sm">Admin Controls</h4>
                                            <p class="text-slate-600 dark:text-slate-400 text-xs mt-1.5 leading-relaxed">
                                                As admin you can manage folders, files, and team members. Files are watermarked and tracked automatically.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    <?php
else: ?>
                        <!-- Empty State -->
                        <div class="flex flex-col items-center justify-center h-[60vh] text-center">
                            <div class="size-16 mx-auto mb-4 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-slate-400 dark:text-slate-500">folder_open</span>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 font-medium">Select a project to view contents</p>
                            <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">Choose a project from the sidebar on the left</p>
                            <button onclick="openModal()" class="mt-4 px-4 py-2 bg-primary/10 text-primary hover:bg-primary hover:text-white text-sm font-semibold rounded-lg transition-all border border-primary/20 hover:border-primary">
                                Create Project
                            </button>
                        </div>
                    <?php
endif; ?>

                </div>
            </main>
        </div>


        <script>
            let selectedMemberIds = [];
            let selectedMemberNames = {}; // id -> {name, role} for pending task dropdowns
            let pendingTasks = [];
            let pendingTaskCounter = 0;
            const currentUserId = <?php echo $_SESSION['user_id']; ?>;
            const currentProjectId = <?php echo $selectedProject ? $selectedProject['id'] : 'null'; ?>;

            if (currentProjectId) {
                document.addEventListener('DOMContentLoaded', () => {
                    loadPane(null); // null = project root
                });
            }

            // ─── Unified File Browser State ───────────────────────────────
            let _adminCurrentFolderId = null;
            let _adminFolderTrail = []; // [{id, name}, ...]
            let _paneFolders = [];
            let _paneFiles = [];
            let _fileFilter = 'all';
            let _fileSort = 'date-desc';
            let _fileView = 'list';
            let _adminFoldersPage = 1;
            let _adminFilesPage = 1;
            const _adminFoldersLimit = 4;
            const _adminFilesLimit = 10;

            window.changeAdminFoldersPage = function(page) {
                _adminFoldersPage = page;
                renderPane();
            };
            window.changeAdminFilesPage = function(page) {
                _adminFilesPage = page;
                renderPane();
            };

            // ─── Breadcrumb ───────────────────────────────────────────────
            function adminRenderBreadcrumb() {
                const bc = document.getElementById('paneBreadcrumb');
                if (!bc) return;
                if (_adminFolderTrail.length === 0) {
                    bc.classList.add('hidden');
                    bc.classList.remove('flex');
                    bc.innerHTML = '';
                    return;
                }
                bc.classList.remove('hidden');
                bc.classList.add('flex');
                let html = `<span class="text-slate-300 dark:text-slate-600 mx-0.5">/</span>
                    <button onclick="adminShowFolderGrid()" class="hover:text-primary transition-colors flex-shrink-0">All Files</button>`;
                _adminFolderTrail.forEach((crumb, i) => {
                    const isLast = i === _adminFolderTrail.length - 1;
                    html += `<span class="material-symbols-outlined text-[13px] flex-shrink-0 text-slate-300 dark:text-slate-600">chevron_right</span>`;
                    if (isLast) {
                        html += `<span class="text-slate-800 dark:text-slate-200 font-semibold truncate max-w-[140px]" title="${escapeHtml(crumb.name)}">${escapeHtml(crumb.name)}</span>`;
                    } else {
                        html += `<button onclick="adminNavToTrailIndex(${i})" class="hover:text-primary transition-colors truncate max-w-[100px]" title="${escapeHtml(crumb.name)}">${escapeHtml(crumb.name)}</button>`;
                    }
                });
                bc.innerHTML = html;
            }

            function adminNavToTrailIndex(index) {
                _adminFolderTrail = _adminFolderTrail.slice(0, index + 1);
                const target = _adminFolderTrail[index];
                _adminCurrentFolderId = target.id;
                adminRenderBreadcrumb();
                loadPane(target.id);
            }

            // ─── Load pane (folders + files for a given folder / root) ────
            async function loadPane(folderId) {
                _adminCurrentFolderId = folderId;
                const pane = document.getElementById('filesPane');
                pane.innerHTML = `<div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                    <span class="material-symbols-outlined text-3xl block mb-2 animate-pulse">folder_open</span>Loading...</div>`;

                // Show/hide folder upload button
                const folderUpBtn = document.getElementById('adminFolderUploadBtn');
                if (folderUpBtn) folderUpBtn.classList.toggle('hidden', folderId === null);

                try {
                    if (folderId === null) {
                        // Root: fetch project-level folders AND project-level files in parallel
                        const [fRes, fileRes] = await Promise.all([
                            fetch(`../api/projects.php?action=get-folders&project_id=${currentProjectId}`),
                            fetch(`../api/projects.php?action=files&project_id=${currentProjectId}`)
                        ]);
                        const [fData, fileData] = await Promise.all([fRes.json(), fileRes.json()]);
                        _paneFolders = fData.success ? (fData.data.folders || []) : [];
                        _paneFiles = fileData.success ? (fileData.data.files || []) : [];
                    } else {
                        // Inside a folder: get-folder-files returns BOTH subfolders AND files
                        const res = await fetch(`../api/projects.php?action=get-folder-files&folder_id=${folderId}&project_id=${currentProjectId}`);
                        const data = await res.json();
                        if (!data.success) throw new Error(data.error);
                        _paneFolders = data.data.subfolders || [];
                        _paneFiles = data.data.files || [];
                    }
                    renderPane();
                } catch (e) {
                    pane.innerHTML = `<div class="p-8 text-center text-red-400 text-sm">Failed to load. Please try again.</div>`;
                    console.error(e);
                }
            }

            // ─── Helper: uploader avatar colour from name ─────────────────
            function avatarColor(name) {
                let h = 0;
                for (let i = 0; i < (name || '').length; i++) h = name.charCodeAt(i) + ((h << 5) - h);
                return `hsl(${Math.abs(h) % 360},55%,48%)`;
            }

            function avatarInitials(name) {
                return (name || '?').split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
            }

            function fmtDate(d) {
                if (!d) return '';
                const dt = new Date(d);
                return dt.toLocaleDateString('en-US', {
                        month: '2-digit',
                        day: '2-digit',
                        year: 'numeric'
                    }) +
                    ' at ' + dt.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
            }

            function fmtSize(bytes) {
                if (!bytes) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB'];
                let i = 0,
                    v = bytes;
                while (v >= 1024 && i < units.length - 1) {
                    v /= 1024;
                    i++;
                }
                return v.toFixed(1) + ' ' + units[i];
            }

            // ─── File type helpers ────────────────────────────────────────
            const _IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png'];
            const _VIDEO_TYPES = ['video/mp4'];
            const _DOC_TYPES = [
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel'
            ];

            function isImageType(mt) {
                return _IMAGE_TYPES.includes(mt);
            }

            function isVideoType(mt) {
                return _VIDEO_TYPES.includes(mt);
            }

            function isDocType(mt) {
                return _DOC_TYPES.includes(mt);
            }

            // ─── Render pane (apply sort + filter, build HTML) ────────────
            function renderPane() {
                const pane = document.getElementById('filesPane');
                const footer = document.getElementById('paneFooter');
                if (!pane) return;

                // Apply filter + sort to files
                let files = [..._paneFiles];
                if (_fileFilter === 'image') files = files.filter(f => isImageType(f.file_type || f.mime_type || ''));
                else if (_fileFilter === 'video') files = files.filter(f => isVideoType(f.file_type || f.mime_type || ''));
                else if (_fileFilter === 'doc') files = files.filter(f => isDocType(f.file_type || f.mime_type || ''));

                files.sort((a, b) => {
                    switch (_fileSort) {
                        case 'date-asc':
                            return new Date(a.upload_date) - new Date(b.upload_date);
                        case 'date-desc':
                            return new Date(b.upload_date) - new Date(a.upload_date);
                        case 'name-asc':
                            return (a.original_name || '').localeCompare(b.original_name || '');
                        case 'name-desc':
                            return (b.original_name || '').localeCompare(a.original_name || '');
                        case 'size-asc':
                            return (a.file_size || 0) - (b.file_size || 0);
                        case 'size-desc':
                            return (b.file_size || 0) - (a.file_size || 0);
                        default:
                            return 0;
                    }
                });

                const totalItems = _paneFolders.length + files.length;
                const isEmpty = totalItems === 0;
                const noMatch = _paneFolders.length === 0 && _paneFiles.length > 0 && files.length === 0;

                // Footer
                if (footer) {
                    if (isEmpty || noMatch) {
                        footer.classList.add('hidden');
                    } else {
                        footer.classList.remove('hidden');
                        footer.textContent = `${totalItems} item${totalItems !== 1 ? 's' : ''}`;
                    }
                }

                if (isEmpty) {
                    pane.innerHTML = `
                    <div class="p-12 text-center">
                        <div class="inline-flex size-16 rounded-2xl bg-slate-100 dark:bg-slate-800 items-center justify-center mb-4">
                            <span class="material-symbols-outlined text-3xl text-slate-400">cloud_upload</span>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 font-semibold text-sm mb-1">
                            ${_adminCurrentFolderId ? 'This folder is empty' : 'No files yet'}
                        </p>
                        <p class="text-slate-400 dark:text-slate-500 text-xs mb-4">Drag &amp; drop files here, or click Upload</p>
                        <button onclick="openFolderUploadModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 text-primary hover:bg-primary hover:text-white text-xs font-bold rounded-lg transition-all border border-primary/20">
                            <span class="material-symbols-outlined text-[15px]">cloud_upload</span>Browse Files
                        </button>
                    </div>`;
                    return;
                }
                if (noMatch) {
                    pane.innerHTML = `<div class="p-10 text-center text-slate-400 dark:text-slate-500 text-sm">
                        <span class="material-symbols-outlined text-2xl block mb-2">search_off</span>No files match this filter</div>`;
                    return;
                }

                let html = '';

                // Folders Pagination Logic
                const totalPaneFolders = _paneFolders.length;
                const totalPaneFolderPages = Math.ceil(totalPaneFolders / _adminFoldersLimit);
                if (_adminFoldersPage > totalPaneFolderPages) _adminFoldersPage = Math.max(1, totalPaneFolderPages);
                const rFolderStart = (_adminFoldersPage - 1) * _adminFoldersLimit;
                const pagePaneFolders = _paneFolders.slice(rFolderStart, rFolderStart + _adminFoldersLimit);

                // Files Pagination Logic
                const totalPaneFiles = files.length;
                const totalPaneFilePages = Math.ceil(totalPaneFiles / _adminFilesLimit);
                if (_adminFilesPage > totalPaneFilePages) _adminFilesPage = Math.max(1, totalPaneFilePages);
                const rFileStart = (_adminFilesPage - 1) * _adminFilesLimit;
                const pagePaneFiles = files.slice(rFileStart, rFileStart + _adminFilesLimit);

                // ── Folder cards (always grid, not view-toggle affected) ───
                if (_paneFolders.length > 0) {
                    html += `<div class="${files.length > 0 ? 'p-4 border-b border-slate-100 dark:border-slate-800' : 'p-4'}">
                        <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Folders</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 ${totalPaneFolderPages > 1 ? 'mb-4' : ''}">`;
                    pagePaneFolders.forEach(f => {
                        const count = f.file_count ?? 0;
                        const sfn = (f.name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                        html += `
                        <div class="relative group/fc">
                            <button onclick="paneOpenFolder(${f.id},'${sfn}')"
                                class="w-full text-left bg-slate-50 dark:bg-slate-800/60 hover:bg-amber-50 dark:hover:bg-amber-500/10 border border-slate-200 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-500/40 rounded-xl p-3 transition-all">
                                <div class="size-9 rounded-lg bg-amber-400/15 flex items-center justify-center mb-2">
                                    <span class="material-symbols-outlined text-amber-500 text-[20px]" style="font-variation-settings:'FILL' 1">folder</span>
                                </div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate pr-6">${escapeHtml(f.name)}</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">${count} file${count!==1?'s':''}</p>
                            </button>
                            <button onclick="event.stopPropagation();openFolderMenu(event,${f.id},'${sfn}')"
                                class="absolute top-2 right-2 p-1 rounded-md text-slate-400 hover:text-slate-700 dark:hover:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-600 opacity-0 group-hover/fc:opacity-100 focus:opacity-100 transition-opacity" title="More">
                                <span class="material-symbols-outlined text-[15px]">more_vert</span>
                            </button>
                        </div>`;
                    });
                    html += `</div>`;

                    if (totalPaneFolderPages > 1) {
                        html += `<div class="flex justify-between items-center select-none mt-2">
                                    <div class="flex items-center gap-2">
                                        <button onclick="changeAdminFoldersPage(${_adminFoldersPage - 1})" ${(_adminFoldersPage === 1) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>
                                        <span class="text-xs text-slate-600 dark:text-slate-400 font-medium px-2">Page ${_adminFoldersPage} of ${totalPaneFolderPages}</span>
                                        <button onclick="changeAdminFoldersPage(${_adminFoldersPage + 1})" ${(_adminFoldersPage === totalPaneFolderPages) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>
                                    </div>
                                </div>`;
                    }
                    html += `</div>`;
                }

                // ── Files ─────────────────────────────────────────────────
                if (files.length > 0) {

                    // ── GRID VIEW ──────────────────────────────────────────
                    if (_fileView === 'grid') {
                        if (_paneFolders.length > 0) {
                            html += `<div class="px-4 pt-4 pb-1"><p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Files</p></div>`;
                        }
                        html += `<div class="p-4"><div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">`;
                        pagePaneFiles.forEach(file => {
                            const mt = file.file_type || file.mime_type || '';
                            const isImage = isImageType(mt),
                                isVideo = isVideoType(mt);
                            const icon = isImage ? 'image' : (isVideo ? 'movie' : 'description');
                            const iconBg = isImage ? 'bg-purple-500/10' : (isVideo ? 'bg-red-500/10' : 'bg-blue-500/10');
                            const iconClr = isImage ? 'text-purple-500' : (isVideo ? 'text-red-500' : 'text-blue-500');
                            const sfn = (file.original_name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                            const isWatermarked = Number(file.watermarked) === 1;
                            const watermarkChip = isWatermarked ?
                                `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30"><span class="material-symbols-outlined text-[12px]">verified</span>Watermarked</span>` :
                                `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/30"><span class="material-symbols-outlined text-[12px]">warning</span>Not Watermarked</span>`;
                            const mbId = `pm-${file.id}`;
                            const uploader = file.uploader_name || '';
                            const avatarBg = avatarColor(uploader);
                            const avatarTxt = avatarInitials(uploader);
                            const thumbHtml = isImage ?
                                `<img src="../api/view.php?id=${file.id}&thumb=1" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.innerHTML='<span class=material-symbols-outlined text-[32px]>${icon}</span>'">` :
                                `<span class="material-symbols-outlined text-[32px] ${iconClr}">${icon}</span>`;
                            html += `
                            <div class="group/gc relative flex flex-col bg-slate-900 border border-slate-700/60 rounded-xl overflow-hidden hover:border-primary/50 hover:shadow-lg transition-all cursor-pointer"
                                onclick="previewFile(${file.id})"
                                oncontextmenu="openFileMenu(event,${file.id},'${sfn}');return false;"
                                onmouseenter="document.getElementById('${mbId}').style.opacity='1'"
                                onmouseleave="document.getElementById('${mbId}').style.opacity='0'">
                                <!-- Thumbnail -->
                                <div class="relative h-36 ${isImage ? 'bg-black' : iconBg+' flex items-center justify-center'} overflow-hidden flex-shrink-0">
                                    ${thumbHtml}
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/0 hover:bg-black/40 transition-all opacity-0 group-hover/gc:opacity-100 pointer-events-none">
                                        <span class="material-symbols-outlined text-white text-3xl drop-shadow">play_circle</span>
                                    </div>
                                </div>
                                <!-- Info -->
                                <div class="p-3 flex flex-col gap-1.5 bg-slate-800/80">
                                    <p class="text-[13px] font-semibold text-white truncate leading-tight">${escapeHtml(file.original_name)}</p>
                                    <div class="mt-0.5">${watermarkChip}</div>
                                    <div class="flex items-center gap-2">
                                        <div class="size-5 rounded-full flex-shrink-0 flex items-center justify-center text-[9px] font-bold text-white" style="background:${avatarBg}">${avatarTxt}</div>
                                        <span class="text-[11px] text-slate-400 truncate">${escapeHtml(uploader)||'—'}</span>
                                        <span class="text-slate-600 text-[10px] ml-auto flex-shrink-0">${fmtDate(file.upload_date).split(' at ')[0]}</span>
                                    </div>
                                </div>
                                <button id="${mbId}" onclick="event.stopPropagation();openFileMenu(event,${file.id},'${sfn}')" style="opacity:0;transition:opacity .15s"
                                    class="absolute top-1.5 right-1.5 p-1 rounded-md bg-black/50 text-white hover:bg-black/80" title="Options">
                                    <span class="material-symbols-outlined text-[15px]">more_horiz</span>
                                </button>
                            </div>`;
                        });
                        html += `</div></div>`;
                        if (totalPaneFilePages > 1) {
                            html += `<div class="p-4 flex justify-between items-center select-none border-t border-slate-100 dark:border-slate-800">
                                        <div class="flex items-center gap-2">
                                            <button onclick="changeAdminFilesPage(${_adminFilesPage - 1})" ${(_adminFilesPage === 1) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>
                                            <span class="text-xs text-slate-600 dark:text-slate-400 font-medium px-2">Page ${_adminFilesPage} of ${totalPaneFilePages}</span>
                                            <button onclick="changeAdminFilesPage(${_adminFilesPage + 1})" ${(_adminFilesPage === totalPaneFilePages) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>
                                        </div>
                                    </div>`;
                        }

                        // ── LIST VIEW ──────────────────────────────────────────
                    } else {
                        if (_paneFolders.length > 0) {
                            html += `<div class="px-4 pt-4 pb-2"><p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Files</p></div>`;
                        }
                        // Header row
                        html += `
                        <div class="grid gap-0" style="grid-template-columns:minmax(0,1fr) 180px 140px">
                            <div class="contents">
                                <div class="px-4 py-2 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">Name</div>
                                <div class="px-3 py-2 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">Date Uploaded</div>
                                <div class="px-3 py-2 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">Uploader</div>
                            </div>`;
                        pagePaneFiles.forEach(file => {
                            const mt = file.file_type || file.mime_type || '';
                            const isImage = isImageType(mt),
                                isVideo = isVideoType(mt);
                            const icon = isImage ? 'image' : (isVideo ? 'movie' : 'description');
                            const iconBg = isImage ? 'bg-purple-500/10' : (isVideo ? 'bg-red-500/10' : 'bg-blue-500/10');
                            const iconClr = isImage ? 'text-purple-500' : (isVideo ? 'text-red-500' : 'text-blue-500');
                            const isMine = file.user_id == currentUserId;
                            const isWatermarked = Number(file.watermarked) === 1;
                            const watermarkChip = isWatermarked ?
                                `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-500 border border-emerald-500/30"><span class="material-symbols-outlined text-[12px]">verified</span>Watermarked</span>` :
                                `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-500 border border-amber-500/30"><span class="material-symbols-outlined text-[12px]">warning</span>Not Watermarked</span>`;
                            const sfn = (file.original_name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                            const mbId = `pm-${file.id}`;
                            const uploader = file.uploader_name || '';
                            const avatarBg = avatarColor(uploader);
                            const avatarTxt = avatarInitials(uploader);
                            const thumbHtml = isImage ?
                                `<img src="../api/view.php?id=${file.id}&thumb=1" alt="" class="size-full object-cover rounded" loading="lazy" onerror="this.outerHTML='<span class=material-symbols-outlined text-[16px]>${icon}</span>'">` :
                                `<span class="material-symbols-outlined text-[16px]">${icon}</span>`;
                            const rowBg = `hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors cursor-pointer ${isMine ? 'bg-primary/[0.03]' : ''}`;
                            html += `
                            <div class="${rowBg} border-b border-slate-100 dark:border-slate-800 last:border-0 contents">
                                <!-- Name cell -->
                                <div class="px-4 py-3 flex items-center gap-3 min-w-0 ${rowBg}"
                                    onclick="previewFile(${file.id})"
                                    oncontextmenu="openFileMenu(event,${file.id},'${sfn}');return false;"
                                    onmouseenter="const el=document.getElementById('${mbId}');if(el)el.style.opacity='1'"
                                    onmouseleave="const el=document.getElementById('${mbId}');if(el)el.style.opacity='0'">
                                    <!-- Thumbnail -->
                                    <div class="size-9 rounded-lg flex-shrink-0 ${isImage ? 'overflow-hidden' : iconBg+' '+iconClr+' flex items-center justify-center'}">
                                        ${thumbHtml}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">${escapeHtml(file.original_name)}</p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">${fmtSize(file.file_size)}</p>
                                        <div class="mt-1">${watermarkChip}</div>
                                    </div>
                                    <div class="flex items-center gap-0.5 flex-shrink-0">
                                        <button id="${mbId}" onclick="event.stopPropagation();openFileMenu(event,${file.id},'${sfn}')" style="opacity:0;transition:opacity .15s"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800" title="Options">
                                            <span class="material-symbols-outlined text-[17px]">more_vert</span>
                                        </button>
                                    </div>
                                </div>
                                <!-- Date cell -->
                                <div class="px-3 py-3 text-xs text-slate-500 dark:text-slate-400 ${rowBg} cursor-pointer"
                                    onclick="previewFile(${file.id})"
                                    oncontextmenu="openFileMenu(event,${file.id},'${sfn}');return false;">${fmtDate(file.upload_date)}</div>
                                <!-- Uploader cell -->
                                <div class="px-3 py-3 flex items-center gap-2 ${rowBg} cursor-pointer"
                                    onclick="previewFile(${file.id})"
                                    oncontextmenu="openFileMenu(event,${file.id},'${sfn}');return false;">
                                    <div class="size-6 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold text-white shadow-sm" style="background:${avatarBg}">${avatarTxt}</div>
                                    <span class="text-xs text-slate-600 dark:text-slate-300 font-medium truncate">${escapeHtml(uploader)||'—'}</span>
                                </div>
                            </div>`;
                        });
                        html += `</div>`;

                        if (totalPaneFilePages > 1) {
                            html += `<div class="p-4 flex justify-between items-center select-none border-t border-slate-100 dark:border-slate-800">
                                        <div class="flex items-center gap-2">
                                            <button onclick="changeAdminFilesPage(${_adminFilesPage - 1})" ${(_adminFilesPage === 1) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>
                                            <span class="text-xs text-slate-600 dark:text-slate-400 font-medium px-2">Page ${_adminFilesPage} of ${totalPaneFilePages}</span>
                                            <button onclick="changeAdminFilesPage(${_adminFilesPage + 1})" ${(_adminFilesPage === totalPaneFilePages) ? 'disabled' : ''} class="p-1 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-primary disabled:opacity-50 transition-colors flex items-center justify-center"><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>
                                        </div>
                                    </div>`;
                        }
                    }
                }

                pane.innerHTML = html;
            }

            // ─── Navigate into a folder ───────────────────────────────────
            function paneOpenFolder(folderId, folderName) {
                _adminFolderTrail.push({
                    id: folderId,
                    name: folderName
                });
                adminRenderBreadcrumb();
                loadPane(folderId);
            }

            // Back-compat alias used by existing modal + context-menu code
            function adminOpenFolder(folderId, folderName) {
                paneOpenFolder(folderId, folderName);
            }

            function adminShowFolderGrid() {
                _adminFolderTrail = [];
                _adminCurrentFolderId = null;
                adminRenderBreadcrumb();
                loadPane(null);
            }

            // ─── Description inline edit ──────────────────────────────────
            function startEditDesc() {
                document.getElementById('descDisplay').classList.add('hidden');
                document.getElementById('descEdit').classList.remove('hidden');
                document.getElementById('descInput').focus();
            }

            function cancelEditDesc() {
                document.getElementById('descEdit').classList.add('hidden');
                document.getElementById('descDisplay').classList.remove('hidden');
            }
            async function saveDesc() {
                const val = document.getElementById('descInput').value.trim();
                const fd = new FormData();
                fd.append('action', 'update-description');
                fd.append('project_id', currentProjectId);
                fd.append('description', val);
                try {
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        document.getElementById('descText').textContent = val;
                        const ph = document.getElementById('descPlaceholder');
                        if (ph) ph.style.display = val ? 'none' : '';
                    }
                } catch (e) {
                    console.error(e);
                }
                cancelEditDesc();
            }

            // ─── Toolbar state ────────────────────────────────────────────
            function setFileFilter(f) {
                _fileFilter = f;
                ['all', 'image', 'video', 'doc'].forEach(k => {
                    const el = document.getElementById(`fChip-${k}`);
                    if (!el) return;
                    el.className = k === f ?
                        'px-3 py-1 rounded-md text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm transition-all' :
                        'px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all';
                });
                renderPane();
            }

            function setFileSort(s) {
                _fileSort = s;
                renderPane();
            }

            function setFileView(v) {
                _fileView = v;
                const active = 'p-1.5 rounded-md bg-white dark:bg-slate-700 text-slate-700 dark:text-white shadow-sm transition-all';
                const inactive = 'p-1.5 rounded-md text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition-all';
                const lb = document.getElementById('vBtn-list'),
                    gb = document.getElementById('vBtn-grid');
                if (lb) lb.className = v === 'list' ? active : inactive;
                if (gb) gb.className = v === 'grid' ? active : inactive;
                renderPane();
            }

            // ─── Refresh pane after file upload / rename / delete ─────────
            function loadFiles(projectId) {
                loadPane(_adminCurrentFolderId);
            }

            function loadFolders(projectId) {
                /* no-op, loadPane handles both */
            }


            function openFolderUploadModal() {
                _stagedUploadFiles = null;
                if (document.getElementById('folderDropZoneText')) {
                    document.getElementById('folderDropZoneText').textContent = 'Drag & drop or click to browse';
                    document.getElementById('folderDropZoneTextSub').classList.remove('hidden');
                }
                document.getElementById('folderFileInput').value = '';
                const p1 = document.getElementById('folderPdfPassword');
                const p2 = document.getElementById('folderPdfPasswordConfirm');
                if (p1) {
                    p1.value = '';
                    p1.type = 'password';
                    p1.nextElementSibling.querySelector('span').textContent = 'visibility';
                }
                if (p2) {
                    p2.value = '';
                    p2.type = 'password';
                    p2.nextElementSibling.querySelector('span').textContent = 'visibility';
                }

                document.getElementById('folderUploadError').textContent = '';
                document.getElementById('folderUploadStatus').textContent = '';
                document.getElementById('folderUploadProgress').classList.add('hidden');
                document.getElementById('folderUploadProgressBar').style.width = '0%';

                // Set folder name if in a folder
                const fnEl = document.getElementById('folderUploadFolderName');
                if (fnEl) {
                    if (_adminCurrentFolderId && window._adminFolderTrail && window._adminFolderTrail.length > 0) {
                        const tail = window._adminFolderTrail[window._adminFolderTrail.length - 1];
                        fnEl.textContent = `Into: ${tail.name}`;
                    } else {
                        fnEl.textContent = 'Uploading into project root';
                    }
                }
                document.getElementById('folderUploadModal').classList.remove('hidden');
                wireFolderDrop();
            }

            function closeFolderUploadModal() {
                document.getElementById('folderUploadModal').classList.add('hidden');
            }

            let _folderDropWired = false;
            let _stagedUploadFiles = null;

            function wireFolderDrop() {
                if (_folderDropWired) return;
                const dz = document.getElementById('folderDropZone');
                if (!dz) return;
                _folderDropWired = true;
                dz.addEventListener('dragover', e => {
                    e.preventDefault();
                    dz.classList.add('border-primary', 'bg-primary/5');
                });
                dz.addEventListener('dragleave', () => dz.classList.remove('border-primary', 'bg-primary/5'));
                dz.addEventListener('drop', e => {
                    e.preventDefault();
                    dz.classList.remove('border-primary', 'bg-primary/5');
                    stageUploadFiles(e.dataTransfer.files);
                });
                document.getElementById('folderFileInput').addEventListener('change', e => stageUploadFiles(e.target.files));
            }

            function stageUploadFiles(files) {
                if (!files || files.length === 0) return;
                _stagedUploadFiles = files;
                const cnt = files.length;
                document.getElementById('folderDropZoneText').textContent = `${cnt} file${cnt !== 1 ? 's' : ''} selected. Ready to upload.`;
                document.getElementById('folderDropZoneTextSub').classList.add('hidden');
            }

            async function submitFolderUpload() {
                if (!_stagedUploadFiles || _stagedUploadFiles.length === 0) {
                    document.getElementById('folderUploadError').textContent = 'Please select a file to upload.';
                    return;
                }

                const pwd1 = document.getElementById('folderPdfPassword') ? document.getElementById('folderPdfPassword').value.trim() : '';
                if (pwd1 !== '') {
                    // Check if any non-PDF file is selected
                    let hasNonPdf = false;
                    for (let i = 0; i < _stagedUploadFiles.length; i++) {
                        if (_stagedUploadFiles[i].type !== 'application/pdf' && !(_stagedUploadFiles[i].name || '').toLowerCase().endsWith('.pdf')) {
                            hasNonPdf = true;
                            break;
                        }
                    }
                    if (hasNonPdf) {
                        document.getElementById('folderUploadError').textContent = 'Passwords can only be applied when exclusively uploading PDF files.';
                        return;
                    }

                    const pwd2 = document.getElementById('folderPdfPasswordConfirm') ? document.getElementById('folderPdfPasswordConfirm').value.trim() : '';
                    if (pwd1 !== pwd2) {
                        document.getElementById('folderUploadError').textContent = 'PDF passwords do not match.';
                        return;
                    }
                }

                document.getElementById('folderUploadError').textContent = '';
                document.getElementById('folderUploadTriggerBtn').disabled = true;
                await handleFolderUpload(_stagedUploadFiles);
                document.getElementById('folderUploadTriggerBtn').disabled = false;
            }

            async function handleFolderUpload(files) {
                if (!files || files.length === 0) return;
                if (!currentProjectId) return;

                const progressEl = document.getElementById('folderUploadProgress');
                const bar = document.getElementById('folderUploadProgressBar');
                const status = document.getElementById('folderUploadStatus');
                const errEl = document.getElementById('folderUploadError');

                progressEl.classList.remove('hidden');
                errEl.textContent = '';
                let successCount = 0;

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    if (file.size > 100 * 1024 * 1024) {
                        errEl.textContent = `"${file.name}" exceeds the maximum upload size of 100MB`;
                        bar.style.width = `${((i + 1) / files.length) * 100}%`;
                        continue;
                    }

                    status.textContent = `Uploading ${file.name} (${i + 1}/${files.length})...`;
                    bar.style.width = `${(i / files.length) * 100}%`;

                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('project_id', currentProjectId);
                    if (_adminCurrentFolderId) {
                        fd.append('folder_id', _adminCurrentFolderId);
                    }
                    const wmCheckbox = document.getElementById('requireFolderWatermark');
                    if (wmCheckbox) fd.append('require_watermark', wmCheckbox.checked ? '1' : '0');

                    const pdfPasswordInput = document.getElementById('folderPdfPassword');
                    const pdfPassword = pdfPasswordInput ? pdfPasswordInput.value.trim() : '';

                    const isPdf = (file.type === 'application/pdf') || ((file.name || '').toLowerCase().endsWith('.pdf'));
                    if (isPdf && pdfPassword) {
                        fd.append('pdf_password', pdfPassword);
                    }

                    try {
                        const res = await fetch('../api/upload.php', {
                            method: 'POST',
                            credentials: 'include',
                            body: fd
                        });
                        if (res.status === 413) {
                            errEl.textContent = `"${file.name}" exceeds the maximum upload size of 100MB`;
                        } else {
                            const data = await res.json();
                            if (data.success) successCount++;
                            else errEl.textContent = data.error || 'Upload failed';
                        }
                    } catch (e) {
                        errEl.textContent = e.name === 'TypeError'
                            ? `"${file.name}" exceeds the maximum upload size of 100MB`
                            : 'Upload failed. Please try again.';
                    }

                    bar.style.width = `${((i + 1) / files.length) * 100}%`;
                }

                status.textContent = `Done! ${successCount}/${files.length} file(s) uploaded.`;
                if (errEl.textContent) {
                    if (successCount > 0) loadPane(_adminCurrentFolderId);
                } else {
                    setTimeout(() => {
                        closeFolderUploadModal();
                        loadPane(_adminCurrentFolderId);
                    }, 800);
                }
            }

            function togglePasswordVisibility(id, btn) {
                const el = document.getElementById(id);
                const icon = btn.querySelector('span');
                if (el.type === 'password') {
                    el.type = 'text';
                    icon.textContent = 'visibility_off';
                } else {
                    el.type = 'password';
                    icon.textContent = 'visibility';
                }
            }

            function openModal() {
                document.getElementById('createModal').classList.remove('modal-hidden');
                document.getElementById('createModal').classList.add('modal-visible');
            }

            function closeModal() {
                document.getElementById('createModal').classList.add('modal-hidden');
                document.getElementById('createModal').classList.remove('modal-visible');
                selectedMemberIds = [];
                selectedMemberNames = {};
                pendingTasks = [];
                pendingTaskCounter = 0;
                document.getElementById('selectedMembers').innerHTML = '<p class="text-center text-slate-400 dark:text-slate-500 text-xs py-4">No members selected</p>';
                document.getElementById('memberCount').textContent = '0';
                document.getElementById('pendingTasksList').innerHTML = '<p id="pendingTasksEmpty" class="text-xs text-slate-400 dark:text-slate-500 italic px-1">No tasks added yet. Tasks can be assigned to selected team members.</p>';
            }

            function searchMembers() {
                const input = document.getElementById('searchMembers').value.toLowerCase();
                document.querySelectorAll('.user-item').forEach(item => {
                    const name = item.dataset.name.toLowerCase();
                    const email = item.dataset.email.toLowerCase();
                    item.style.display = (name.includes(input) || email.includes(input)) ? '' : 'none';
                });
            }

            function addMember(id, name, role) {
                if (selectedMemberIds.includes(id)) return;
                selectedMemberIds.push(id);
                selectedMemberNames[id] = { name, role: role || '' };

                const container = document.getElementById('selectedMembers');
                if (selectedMemberIds.length === 1) container.innerHTML = '';

                const memberDiv = document.createElement('div');
                memberDiv.id = 'member-' + id;
                memberDiv.className = 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-2.5 rounded-lg flex items-center justify-between';
                memberDiv.innerHTML = `
            <span class="text-xs font-semibold text-slate-900 dark:text-white">${escapeHtml(name)}</span>
            <button type="button" onclick="removeMemberFromModal(${id})" class="text-slate-400 hover:text-red-500 transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        `;
                container.appendChild(memberDiv);
                document.getElementById('memberCount').textContent = selectedMemberIds.length;
                refreshPendingTaskAssignDropdowns();
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function removeMemberFromModal(id) {
                selectedMemberIds = selectedMemberIds.filter(mid => mid !== id);
                delete selectedMemberNames[id];
                document.getElementById('member-' + id).remove();
                document.getElementById('memberCount').textContent = selectedMemberIds.length;
                if (selectedMemberIds.length === 0) {
                    document.getElementById('selectedMembers').innerHTML = '<p class="text-center text-slate-400 dark:text-slate-500 text-xs py-4">No members selected</p>';
                }
                refreshPendingTaskAssignDropdowns();
            }

            function refreshPendingTaskAssignDropdowns() {
                document.querySelectorAll('.pending-task-assign').forEach(sel => {
                    const prev = sel.value;
                    sel.innerHTML = '<option value="">— Unassigned —</option>';
                    Object.entries(selectedMemberNames).forEach(([mid, m]) => {
                        const opt = document.createElement('option');
                        opt.value = mid;
                        opt.textContent = m.name + (m.role ? ' (' + m.role + ')' : '');
                        if (String(mid) === String(prev)) opt.selected = true;
                        sel.appendChild(opt);
                    });
                });
            }

            function addPendingTaskRow() {
                const list = document.getElementById('pendingTasksList');
                const empty = document.getElementById('pendingTasksEmpty');
                if (empty) empty.remove();

                const idx = pendingTaskCounter++;
                const row = document.createElement('div');
                row.id = 'ptask-' + idx;
                row.className = 'grid grid-cols-1 gap-2 p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl relative';

                let memberOptions = '<option value="">— Unassigned —</option>';
                Object.entries(selectedMemberNames).forEach(([mid, m]) => {
                    memberOptions += `<option value="${mid}">${escapeHtml(m.name)}${m.role ? ' (' + m.role + ')' : ''}</option>`;
                });

                row.innerHTML = `
                    <div class="flex items-center justify-between gap-2">
                        <input type="text" placeholder="Task title *" data-ptask-title="${idx}"
                            class="flex-1 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all" />
                        <button type="button" onclick="removePendingTaskRow(${idx})" class="p-1 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-500/10 transition-colors flex-shrink-0">
                            <span class="material-symbols-outlined text-[16px]">delete</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <select data-ptask-assign="${idx}" class="pending-task-assign px-2 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/40 transition-all">
                            ${memberOptions}
                        </select>
                        <select data-ptask-priority="${idx}" class="px-2 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/40 transition-all">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                        <input type="date" data-ptask-due="${idx}"
                            class="px-2 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/40 transition-all" />
                    </div>`;

                list.appendChild(row);
            }

            function removePendingTaskRow(idx) {
                const row = document.getElementById('ptask-' + idx);
                if (row) row.remove();
                if (document.getElementById('pendingTasksList').children.length === 0) {
                    document.getElementById('pendingTasksList').innerHTML =
                        '<p id="pendingTasksEmpty" class="text-xs text-slate-400 dark:text-slate-500 italic px-1">No tasks added yet. Tasks can be assigned to selected team members.</p>';
                }
            }

            function collectPendingTasks() {
                const tasks = [];
                document.querySelectorAll('[data-ptask-title]').forEach(el => {
                    const idx = el.dataset.ptaskTitle;
                    const title = el.value.trim();
                    if (!title) return;
                    tasks.push({
                        title,
                        assignedTo: document.querySelector(`[data-ptask-assign="${idx}"]`)?.value || '',
                        priority:   document.querySelector(`[data-ptask-priority="${idx}"]`)?.value || 'medium',
                        dueDate:    document.querySelector(`[data-ptask-due="${idx}"]`)?.value || '',
                    });
                });
                return tasks;
            }

            async function createProject(event) {
                event.preventDefault();
                const formData = new FormData(event.target);
                formData.append('action', 'create');
                formData.append('members', JSON.stringify(selectedMemberIds));

                const tasksToCreate = collectPendingTasks();

                try {
                    const response = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        // Create any pending tasks now that we have the project ID
                        if (tasksToCreate.length > 0) {
                            for (const task of tasksToCreate) {
                                const fd = new FormData();
                                fd.append('action', 'create-task');
                                fd.append('project_id', data.project_id);
                                fd.append('title', task.title);
                                fd.append('assigned_to', task.assignedTo);
                                fd.append('priority', task.priority);
                                fd.append('due_date', task.dueDate);
                                await fetch('../api/projects.php', { method: 'POST', body: fd });
                            }
                        }
                        showToast('Project created successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Error: ' + data.error, 'error');
                    }
                } catch (error) {
                    showToast('Error: ' + error.message, 'error');
                }
            }

            async function deleteProject(id) {
                svConfirm('Delete Project', 'Are you sure you want to permanentely delete this project and all its files?', async () => {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('project_id', id);

                    try {
                        const response = await fetch('../api/projects.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast('Project deleted successfully', 'success');
                            setTimeout(() => location.href = 'projects.php', 1000);
                        } else {
                            showToast('Error: ' + data.error, 'error');
                        }
                    } catch (error) {
                        showToast('Error: ' + error.message, 'error');
                    }
                });
            }

            async function removeMember(projectId, userId) {
                svConfirm('Remove Member', 'Are you sure you want to remove this member from the project?', async () => {
                    const formData = new FormData();
                    formData.append('action', 'remove_member');
                    formData.append('project_id', projectId);
                    formData.append('user_id', userId);

                    try {
                        const response = await fetch('../api/projects.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast('Member removed', 'success');
                            setTimeout(() => location.reload(), 800);
                        } else {
                            showToast('Error: ' + data.error, 'error');
                        }
                    } catch (error) {
                        showToast('Error: ' + error.message, 'error');
                    }
                });
            }
            // ─── Folder context menu ─────────────────────────────
            let _folderMenuId = null;
            let _folderMenuName = null;

            function openFolderMenu(event, folderId, folderName) {
                _folderMenuId = folderId;
                _folderMenuName = folderName;
                const menu = document.getElementById('folderContextMenu');
                menu.classList.remove('hidden');
                const rect = event.currentTarget.getBoundingClientRect();
                const scrollY = window.scrollY || document.documentElement.scrollTop;
                const scrollX = window.scrollX || document.documentElement.scrollLeft;
                let top = rect.bottom + scrollY + 4;
                let left = rect.left + scrollX;
                const menuW = 176,
                    menuH = 150;
                if (left + menuW > window.innerWidth) left = window.innerWidth - menuW - 8;
                if (top + menuH > window.innerHeight + scrollY) top = rect.top + scrollY - menuH - 4;
                menu.style.top = top + 'px';
                menu.style.left = left + 'px';
            }

            function closeFolderMenu() {
                document.getElementById('folderContextMenu').classList.add('hidden');
            }

            document.addEventListener('click', (e) => {
                const pm = document.getElementById('projectContextMenu');
                if (!pm.classList.contains('hidden') && !pm.contains(e.target)) closeProjectMenu();
                const fm = document.getElementById('folderContextMenu');
                if (!fm.classList.contains('hidden') && !fm.contains(e.target)) closeFolderMenu();
                const fim = document.getElementById('fileContextMenu');
                if (!fim.classList.contains('hidden') && !fim.contains(e.target)) closeFileMenu();
            });

            function folderMenuRename() {
                closeFolderMenu();
                document.getElementById('renameFolderInput').value = _folderMenuName;
                document.getElementById('renameFolderError').textContent = '';
                document.getElementById('renameFolderModal').classList.remove('hidden');
                document.getElementById('renameFolderInput').select();
            }

            function closeRenameFolderModal() {
                document.getElementById('renameFolderModal').classList.add('hidden');
            }

            async function submitRenameFolder() {
                const name = document.getElementById('renameFolderInput').value.trim();
                const errEl = document.getElementById('renameFolderError');
                const btn = document.getElementById('renameFolderBtn');
                if (!name) {
                    errEl.textContent = 'Please enter a name.';
                    return;
                }
                btn.disabled = true;
                btn.textContent = 'Saving...';
                try {
                    const fd = new FormData();
                    fd.append('action', 'rename-folder');
                    fd.append('folder_id', _folderMenuId);
                    fd.append('project_id', currentProjectId);
                    fd.append('name', name);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        closeRenameFolderModal();
                        // Refresh: if we are inside the renamed folder, update trail name; else reload grid
                        if (_adminCurrentFolderId) {
                            // Check if the renamed folder is in the trail
                            const idx = _adminFolderTrail.findIndex(c => c.id === _folderMenuId);
                            if (idx !== -1) _adminFolderTrail[idx].name = name;
                            adminRenderBreadcrumb();
                            loadPane(_adminCurrentFolderId);
                        } else {
                            loadPane(_adminCurrentFolderId);
                        }
                    } else {
                        errEl.textContent = data.error || 'Failed to rename.';
                    }
                } catch {
                    errEl.textContent = 'Network error.';
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Rename';
                }
            }

            async function folderMenuDelete() {
                closeFolderMenu();
                svConfirm('Delete Folder', `Delete folder "${_folderMenuName}"? Files inside will be moved to the project root.`, async () => {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'delete-folder');
                        fd.append('folder_id', _folderMenuId);
                        fd.append('project_id', currentProjectId);
                        const res = await fetch('../api/projects.php', {
                            method: 'POST',
                            body: fd
                        });
                        const data = await res.json();
                        if (data.success) {
                            showToast('Folder deleted', 'success');
                            // If we're inside the deleted folder (or a child), return to grid
                            setTimeout(() => {
                                const inTrail = _adminFolderTrail.some(c => c.id === _folderMenuId);
                                if (inTrail) {
                                    adminShowFolderGrid();
                                } else {
                                    loadPane(_adminCurrentFolderId);
                                }
                            }, 500);
                        } else {
                            showToast(data.error || 'Failed to delete folder.', 'error');
                        }
                    } catch {
                        showToast('Network error while deleting folder.', 'error');
                    }
                });
            }

            // ─── File context menu ─────────────────────────────
            let _fileMenuId = null;
            let _fileMenuName = null;

            function openFileMenu(event, fileId, fileName) {
                event.preventDefault();
                event.stopPropagation();
                _fileMenuId = fileId;
                _fileMenuName = fileName;
                const menu = document.getElementById('fileContextMenu');
                menu.classList.remove('hidden');

                const rect = event.currentTarget.getBoundingClientRect();
                const scrollY = window.scrollY || document.documentElement.scrollTop;
                const scrollX = window.scrollX || document.documentElement.scrollLeft;

                let top = rect.bottom + scrollY + 4;
                let left = rect.left + scrollX;

                const menuW = 176,
                    menuH = 150;
                if (left + menuW > window.innerWidth) left = window.innerWidth - menuW - 8;
                if (top + menuH > window.innerHeight + scrollY) top = rect.top + scrollY - menuH - 4;

                menu.style.top = top + 'px';
                menu.style.left = left + 'px';
            }

            function closeFileMenu() {
                document.getElementById('fileContextMenu').classList.add('hidden');
            }

            function previewFile(fileId) {
                window.location.href = `preview.php?id=${fileId}&project_id=${currentProjectId}`;
            }

            function fileMenuRename() {
                closeFileMenu();
                document.getElementById('renameFileInput').value = _fileMenuName;
                document.getElementById('renameFileError').textContent = '';
                document.getElementById('renameFileModal').classList.remove('hidden');
                document.getElementById('renameFileInput').select();
            }

            function closeRenameFileModal() {
                document.getElementById('renameFileModal').classList.add('hidden');
            }

            async function submitRenameFile() {
                const name = document.getElementById('renameFileInput').value.trim();
                const errEl = document.getElementById('renameFileError');
                const btn = document.getElementById('renameFileBtn');
                if (!name) {
                    errEl.textContent = 'Please enter a name.';
                    return;
                }
                btn.disabled = true;
                btn.textContent = 'Saving...';
                try {
                    const fd = new FormData();
                    fd.append('action', 'rename-file');
                    fd.append('file_id', _fileMenuId);
                    fd.append('project_id', currentProjectId);
                    fd.append('name', name);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        closeRenameFileModal();
                        if (_adminCurrentFolderId) {
                            loadPane(_adminCurrentFolderId);
                        } else {
                            loadFiles(currentProjectId);
                        }
                    } else {
                        errEl.textContent = data.error || 'Failed to rename.';
                    }
                } catch {
                    errEl.textContent = 'Network error.';
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Rename';
                }
            }

            async function fileMenuDelete() {
                closeFileMenu();
                if (!confirm(`Delete file "${_fileMenuName}"? This cannot be undone.`)) return;
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete-file');
                    fd.append('file_id', _fileMenuId);
                    fd.append('project_id', currentProjectId);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (_adminCurrentFolderId) {
                            loadPane(_adminCurrentFolderId);
                        } else {
                            loadFiles(currentProjectId);
                        }
                    } else {
                        alert(data.error || 'Failed to delete file.');
                    }
                } catch {
                    alert('Network error.');
                }
            }

            // ─── Custom UI Helpers (Toasts & Modals) ───────────────
            function showToast(message, type = 'info') {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = `sv-toast ${type}`;
                
                const icons = {
                    success: 'check_circle',
                    error: 'error',
                    info: 'info'
                };
                const colors = {
                    success: 'text-emerald-500',
                    error: 'text-red-500',
                    info: 'text-primary'
                };

                toast.innerHTML = `
                    <span class="material-symbols-outlined ${colors[type]}">${icons[type]}</span>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">${message}</p>
                    </div>
                `;
                
                container.appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-10px)';
                    setTimeout(() => toast.remove(), 300);
                }, 4000);
            }

            let _confirmCallback = null;
            function svConfirm(title, message, callback) {
                const modal = document.getElementById('customConfirmModal');
                document.getElementById('confirmModalTitle').textContent = title;
                document.getElementById('confirmModalMessage').textContent = message;
                modal.classList.remove('hidden');
                _confirmCallback = callback;
            }

            function closeSvConfirm(confirmed) {
                document.getElementById('customConfirmModal').classList.add('hidden');
                if (confirmed && _confirmCallback) _confirmCallback();
                _confirmCallback = null;
            }

            // ─── Project context menu ─────────────────────────────
            let _projMenuId = null;
            let _projMenuName = null;
            let _projMenuStatus = 'active';

            function openProjectMenu(event, projectId, projectName, status = 'active') {
                _projMenuId = projectId;
                _projMenuName = projectName;
                _projMenuStatus = status;
                
                const menu = document.getElementById('projectContextMenu');
                const toggleBtn = document.getElementById('projectToggleStatusBtn');
                const toggleIcon = toggleBtn.querySelector('span');
                const toggleText = toggleBtn.querySelector('p');

                if (_projMenuStatus === 'active') {
                    toggleIcon.textContent = 'visibility_off';
                    toggleText.textContent = 'Mark as Inactive';
                } else {
                    toggleIcon.textContent = 'visibility';
                    toggleText.textContent = 'Mark as Active';
                }

                menu.classList.remove('hidden');
                const btn = event.currentTarget;
                const rect = btn.getBoundingClientRect();
                const scrollY = window.scrollY || document.documentElement.scrollTop;
                let top = rect.bottom + scrollY + 4;
                let left = rect.left + (window.scrollX || 0);
                const menuW = 176;
                if (left + menuW > window.innerWidth) left = window.innerWidth - menuW - 8;
                menu.style.top = top + 'px';
                menu.style.left = left + 'px';
            }

            function closeProjectMenu() {
                document.getElementById('projectContextMenu').classList.add('hidden');
            }


            // ─── Rename project ───────────────────────────────────
            function projectRename() {
                closeProjectMenu();
                document.getElementById('renameProjectInput').value = _projMenuName;
                document.getElementById('renameProjectError').textContent = '';
                document.getElementById('renameProjectModal').classList.remove('hidden');
                document.getElementById('renameProjectInput').select();
            }

            function closeRenameProjectModal() {
                document.getElementById('renameProjectModal').classList.add('hidden');
            }

            async function submitRenameProject() {
                const name = document.getElementById('renameProjectInput').value.trim();
                const errEl = document.getElementById('renameProjectError');
                const btn = document.getElementById('renameProjectBtn');
                if (!name) {
                    errEl.textContent = 'Please enter a name.';
                    return;
                }
                btn.disabled = true;
                btn.textContent = 'Saving...';
                try {
                    const fd = new FormData();
                    fd.append('action', 'rename');
                    fd.append('project_id', _projMenuId);
                    fd.append('name', name);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        closeRenameProjectModal();
                        showToast('Project renamed successfully', 'success');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        console.error('Rename project error:', data);
                        showToast(data.error || 'Failed to rename.', 'error');
                        errEl.textContent = data.error || 'Failed to rename.';
                    }
                } catch (e) {
                    console.error('Rename project network error:', e);
                    showToast('Network error.', 'error');
                    errEl.textContent = 'Network error.';
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Rename';
                }
            }

            // ─── Toggle Project Status ────────────────────────────
            function projectToggleStatus() {
                closeProjectMenu();
                const newStatus = (_projMenuStatus === 'active') ? 'inactive' : 'active';
                const title = newStatus === 'inactive' ? 'Deactivate Project' : 'Reactivate Project';
                const message = newStatus === 'inactive' 
                    ? `Deactivate project "${_projMenuName}"? Employees will no longer see it until reactivated.`
                    : `Reactivate project "${_projMenuName}"? It will become visible to all members again.`;
                
                svConfirm(title, message, async () => {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'update-status');
                        fd.append('project_id', _projMenuId);
                        fd.append('status', newStatus);
                        const res = await fetch('../api/projects.php', {
                            method: 'POST',
                            body: fd
                        });
                        const data = await res.json();
                        console.log('[Status Update Debug]', data);
                        if (data.success) {
                            showToast(data.message || 'Updated successfully', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast(data.error || 'Failed to update status', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        showToast('Network error while updating status.', 'error');
                    }
                });
            }

            // ─── Delete project (from menu) ───────────────────────
            function projectDelete() {
                closeProjectMenu();
                deleteProject(_projMenuId);
            }

            // ─── ADMIN: Create Folder ─────────────────────────────
            let _adminCreateFolderParentId = null;

            function openAdminCreateFolderModal(parentFolderId = null) {
                _adminCreateFolderParentId = parentFolderId || null;
                document.getElementById('adminFolderNameInput').value = '';
                document.getElementById('adminFolderError').textContent = '';
                const subtitle = document.getElementById('adminCreateFolderSubtitle');
                if (subtitle) {
                    subtitle.textContent = _adminCreateFolderParentId ?
                        `Subfolder inside current folder` :
                        'Create a new folder in this project';
                }
                document.getElementById('adminCreateFolderModal').classList.remove('hidden');
                document.getElementById('adminFolderNameInput').focus();
            }

            function closeAdminCreateFolderModal() {
                document.getElementById('adminCreateFolderModal').classList.add('hidden');
            }

            async function submitAdminCreateFolder() {
                const name = document.getElementById('adminFolderNameInput').value.trim();
                const errEl = document.getElementById('adminFolderError');
                const btn = document.getElementById('adminCreateFolderBtn');

                if (!name) {
                    errEl.textContent = 'Please enter a folder name.';
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'Creating...';
                errEl.textContent = '';

                try {
                    const fd = new FormData();
                    fd.append('action', 'create-folder');
                    fd.append('project_id', currentProjectId);
                    fd.append('name', name);
                    if (_adminCreateFolderParentId) fd.append('parent_id', _adminCreateFolderParentId);

                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();

                    if (data.success) {
                        closeAdminCreateFolderModal();
                        showToast('Folder created', 'success');
                        loadPane(_adminCurrentFolderId);
                    } else {
                        console.error('Create folder error:', data);
                        showToast(data.error || 'Failed to create folder.', 'error');
                        errEl.textContent = data.error || 'Failed to create folder.';
                    }
                } catch (e) {
                    console.error('Create folder network error:', e);
                    showToast('Network error.', 'error');
                    errEl.textContent = 'Network error. Please try again.';
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Create Folder';
                }
            }
            // ─── ADMIN: Add Member ─────────────────────────────────────
            // All users available from PHP (embed as JSON)
            const _allUsers = <?php echo json_encode(array_values($users)); ?>;

            function openAddMemberModal() {
                if (!currentProjectId) return;
                document.getElementById('addMemberSearch').value = '';
                document.getElementById('addMemberError').textContent = '';
                renderAddMemberList(_allUsers);
                document.getElementById('addMemberModal').classList.remove('hidden');
                document.getElementById('addMemberSearch').focus();
            }

            function closeAddMemberModal() {
                document.getElementById('addMemberModal').classList.add('hidden');
            }

            function filterAddMemberList() {
                const q = document.getElementById('addMemberSearch').value.toLowerCase();
                const filtered = _allUsers.filter(u =>
                    u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
                );
                renderAddMemberList(filtered);
            }

            function renderAddMemberList(users) {
                const list = document.getElementById('addMemberList');
                if (users.length === 0) {
                    list.innerHTML = `<p class="text-center text-slate-400 dark:text-slate-500 text-xs py-4">No users found</p>`;
                    return;
                }
                list.innerHTML = users.map(u => `
                <div class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                    <div class="flex items-center gap-3">
                        <div class="size-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">
                            ${escapeHtml(u.name.substring(0, 2).toUpperCase())}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">${escapeHtml(u.name)}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">${escapeHtml(u.email)} &middot; <span class="capitalize">${escapeHtml(u.role)}</span></p>
                        </div>
                    </div>
                    <button onclick="submitAddMember(${u.id}, '${escapeHtml(u.name).replace(/'/g, "\\'")}')"
                        class="opacity-0 group-hover:opacity-100 focus:opacity-100 flex items-center gap-1 px-2.5 py-1 bg-primary/10 hover:bg-primary text-primary hover:text-white text-xs font-bold rounded-lg transition-all border border-primary/20 hover:border-primary">
                        <span class="material-symbols-outlined text-[15px]">add</span>
                        Add
                    </button>
                </div>
            `).join('');
            }

            async function submitAddMember(userId, userName) {
                const errEl = document.getElementById('addMemberError');
                errEl.textContent = '';
                try {
                    const fd = new FormData();
                    fd.append('action', 'add_member');
                    fd.append('project_id', currentProjectId);
                    fd.append('user_id', userId);
                    fd.append('role', 'member');
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        closeAddMemberModal();
                        showToast('Team member added', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        console.error('Add member error:', data);
                        showToast(data.error || 'Failed to add member.', 'error');
                        errEl.textContent = data.error || 'Failed to add member.';
                    }
                } catch (e) {
                    console.error('Add member network error:', e);
                    showToast('Network error.', 'error');
                    errEl.textContent = 'Network error. Please try again.';
                }
            }
            // ────────────────────────────────────────────────────────────
        </script>

        <div id="toast-container"></div>

        <!-- ═══════════════════════════════════════
             CUSTOM CONFIRM MODAL
        ═══════════════════════════════════════ -->
        <div id="customConfirmModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSvConfirm(false)"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="size-10 rounded-xl bg-amber-400/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-amber-500">help</span>
                    </div>
                    <h3 id="confirmModalTitle" class="text-slate-900 dark:text-white font-bold text-lg">Are you sure?</h3>
                </div>
                <p id="confirmModalMessage" class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed mb-6"></p>
                <div class="flex items-center justify-end gap-3">
                    <button onclick="closeSvConfirm(false)"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                    <button onclick="closeSvConfirm(true)"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-primary hover:bg-primary/90 text-white transition-colors shadow-sm">Confirm</button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
         PROJECT CONTEXT MENU
    ═══════════════════════════════════════ -->
        <div id="projectContextMenu"
            class="hidden fixed z-[200] w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden"
            style="top:0;left:0">
            <button onclick="projectRename()"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-[18px] text-slate-400">edit</span>
                Rename
            </button>
            <button id="projectToggleStatusBtn" onclick="projectToggleStatus()"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-[18px] text-slate-400">visibility_off</span>
                <p>Mark as Inactive</p>
            </button>
            <div class="border-t border-slate-100 dark:border-slate-800 mx-2"></div>
            <button onclick="projectDelete()"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                <span class="material-symbols-outlined text-[18px]">delete</span>
                Delete
            </button>
        </div>

        <!-- ═══════════════════════════════════════
         FOLDER CONTEXT MENU
    ═══════════════════════════════════════ -->
        <div id="folderContextMenu"
            class="hidden fixed z-[200] w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden"
            style="top:0;left:0">
            <button onclick="folderMenuRename()"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-[18px] text-slate-400">drive_file_rename_outline</span>
                Rename
            </button>
            <div class="border-t border-slate-100 dark:border-slate-800 mx-2"></div>
            <button onclick="folderMenuDelete()"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                <span class="material-symbols-outlined text-[18px]">delete</span>
                Delete
            </button>
        </div>

        <!-- ═══════════════════════════════════════
         FILE CONTEXT MENU
    ═══════════════════════════════════════ -->
        <div id="fileContextMenu"
            class="hidden fixed z-[200] w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden"
            style="top:0;left:0">
            <a id="cmFileDownloadBtn" href="javascript:void(0)" onclick="window.location.href='../api/download.php?file_id='+_fileMenuId"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-[18px] text-slate-400">download</span>
                Download
            </a>
            <button onclick="fileMenuRename()"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-[18px] text-slate-400">drive_file_rename_outline</span>
                Rename
            </button>
            <div class="border-t border-slate-100 dark:border-slate-800 mx-2"></div>
            <button onclick="fileMenuDelete()"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                <span class="material-symbols-outlined text-[18px]">delete</span>
                Delete
            </button>
        </div>

        <!-- ═══════════════════════════════════════
         RENAME FOLDER MODAL
    ═══════════════════════════════════════ -->
        <div id="renameFolderModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRenameFolderModal()"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-amber-400/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-amber-500">drive_file_rename_outline</span>
                    </div>
                    <div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base">Rename Folder</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs">Enter a new name for this folder</p>
                    </div>
                    <button onclick="closeRenameFolderModal()" class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Folder Name</label>
                <input id="renameFolderInput" type="text" placeholder="Folder name..."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:border-amber-400 transition-all"
                    onkeydown="if(event.key==='Enter') submitRenameFolder()" />
                <p id="renameFolderError" class="text-red-500 text-xs mt-2 min-h-[1rem]"></p>
                <div class="flex items-center justify-end gap-2 mt-4">
                    <button onclick="closeRenameFolderModal()"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                    <button id="renameFolderBtn" onclick="submitRenameFolder()"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-amber-500 hover:bg-amber-400 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">Rename</button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
         RENAME FILE MODAL
    ═══════════════════════════════════════ -->
        <div id="renameFileModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRenameFileModal()"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">drive_file_rename_outline</span>
                    </div>
                    <div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base">Rename File</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs">Enter a new display name for this file</p>
                    </div>
                    <button onclick="closeRenameFileModal()" class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">File Name</label>
                <input id="renameFileInput" type="text" placeholder="File name..."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all"
                    onkeydown="if(event.key==='Enter') submitRenameFile()" />
                <p id="renameFileError" class="text-red-500 text-xs mt-2 min-h-[1rem]"></p>
                <div class="flex items-center justify-end gap-2 mt-4">
                    <button onclick="closeRenameFileModal()"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                    <button id="renameFileBtn" onclick="submitRenameFile()"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-primary hover:bg-primary/90 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">Rename</button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
         RENAME PROJECT MODAL
    ═══════════════════════════════════════ -->
        <div id="renameProjectModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRenameProjectModal()"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">edit</span>
                    </div>
                    <div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base">Rename Project</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs">Enter a new name for this project</p>
                    </div>
                    <button onclick="closeRenameProjectModal()" class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Project Name</label>
                <input id="renameProjectInput" type="text" placeholder="Project name..."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all"
                    onkeydown="if(event.key==='Enter') submitRenameProject()" />
                <p id="renameProjectError" class="text-red-500 text-xs mt-2 min-h-[1rem]"></p>
                <div class="flex items-center justify-end gap-2 mt-4">
                    <button onclick="closeRenameProjectModal()"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                    <button id="renameProjectBtn" onclick="submitRenameProject()"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-primary hover:bg-primary/90 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">Rename</button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
         ADD MEMBER MODAL
    ═══════════════════════════════════════ -->
        <div id="addMemberModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAddMemberModal()"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                <!-- Header -->
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">person_add</span>
                    </div>
                    <div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base">Add Member</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs">Search and add a user to this project</p>
                    </div>
                    <button onclick="closeAddMemberModal()" class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <!-- Search -->
                <input id="addMemberSearch" type="text" placeholder="Search by name or email..."
                    oninput="filterAddMemberList()"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all mb-3" />
                <!-- User list -->
                <div id="addMemberList" class="max-h-60 overflow-y-auto space-y-1 mb-4"></div>
                <!-- Error -->
                <p id="addMemberError" class="text-red-500 text-xs mb-3 min-h-[1rem]"></p>
                <!-- Footer -->
                <div class="flex justify-end">
                    <button onclick="closeAddMemberModal()"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Close</button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
         ADMIN CREATE FOLDER MODAL
    ═══════════════════════════════════════ -->
        <div id="adminCreateFolderModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAdminCreateFolderModal()"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-amber-400/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-amber-500" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">create_new_folder</span>
                    </div>
                    <div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base">New Folder</h3>
                        <p id="adminCreateFolderSubtitle" class="text-slate-500 dark:text-slate-400 text-xs">Create a new folder in this project</p>
                    </div>
                    <button onclick="closeAdminCreateFolderModal()" class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Folder Name</label>
                <input id="adminFolderNameInput" type="text" placeholder="e.g. Evidence Files"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:border-amber-400 transition-all"
                    onkeydown="if(event.key==='Enter') submitAdminCreateFolder()" />
                <p id="adminFolderError" class="text-red-500 text-xs mt-2 min-h-[1rem]"></p>
                <div class="flex items-center justify-end gap-2 mt-4">
                    <button onclick="closeAdminCreateFolderModal()"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                    <button id="adminCreateFolderBtn" onclick="submitAdminCreateFolder()"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-amber-500 hover:bg-amber-400 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">Create Folder</button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
         FOLDER UPLOAD MODAL
    ═══════════════════════════════════════ -->
        <div id="folderUploadModal" class="hidden fixed inset-0 z-[300] flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeFolderUploadModal()"></div>

            <!-- Card -->
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                <!-- Header -->
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">cloud_upload</span>
                    </div>
                    <div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base">Upload Files</h3>
                        <p id="folderUploadFolderName" class="text-slate-500 dark:text-slate-400 text-xs">Uploading into current project</p>
                    </div>
                    <button onclick="closeFolderUploadModal()"
                        class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Drop zone -->
                <div id="folderDropZone" onclick="document.getElementById('folderFileInput').click()"
                    class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 text-center cursor-pointer hover:border-primary/50 transition-all mb-4">
                    <div class="size-12 mx-auto mb-3 bg-primary/10 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-2xl">upload_file</span>
                    </div>
                    <p id="folderDropZoneText" class="text-slate-700 dark:text-slate-200 text-sm font-semibold">Drag & drop or click to browse</p>
                    <p id="folderDropZoneTextSub" class="text-slate-400 dark:text-slate-500 text-xs mt-1">Images, Videos, PDFs, Docs · Max 100MB</p>
                    <input type="file" id="folderFileInput" class="hidden" multiple
                        accept="image/png,video/mp4,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.pdf,.doc,.docx,.xls,.xlsx" />
                </div>

                <!-- Watermark Option -->
                <div class="flex items-start gap-3 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center h-5 mt-0.5">
                        <input id="requireFolderWatermark" type="checkbox" checked
                            class="w-3.5 h-3.5 text-primary bg-white border-slate-300 rounded focus:ring-primary cursor-pointer">
                    </div>
                    <div class="flex flex-col cursor-pointer" onclick="document.getElementById('requireFolderWatermark').click();">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-200 cursor-pointer select-none">Apply Invisible Watermark</label>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight mt-0.5 cursor-pointer select-none">Embeds tracking data into the file pixels when downloaded.</span>
                    </div>
                </div>

                <div class="mt-3">
                    <label for="folderPdfPassword" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">PDF Password (Optional)</label>
                    <div class="relative">
                        <input id="folderPdfPassword" type="password" maxlength="64" placeholder="Applied only to PDF files"
                            class="w-full pl-3 pr-10 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40" />
                        <button type="button" onclick="togglePasswordVisibility('folderPdfPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 outline-none">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                        </button>
                    </div>

                    <label for="folderPdfPasswordConfirm" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1 mt-2">Confirm Password</label>
                    <div class="relative">
                        <input id="folderPdfPasswordConfirm" type="password" maxlength="64" placeholder="Confirm PDF password"
                            class="w-full pl-3 pr-10 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40" />
                        <button type="button" onclick="togglePasswordVisibility('folderPdfPasswordConfirm', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 outline-none">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Progress -->
                <div id="folderUploadProgress" class="hidden mt-4">
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                        <div id="folderUploadProgressBar" class="bg-primary h-full transition-all duration-300 rounded-full" style="width:0%"></div>
                    </div>
                    <p id="folderUploadStatus" class="text-xs text-slate-500 dark:text-slate-400 mt-2"></p>
                </div>
                <p id="folderUploadError" class="text-red-500 text-xs mt-2 min-h-[1rem]"></p>

                <!-- Close/cancel -->
                <div class="flex justify-end gap-2 mt-4">
                    <button onclick="closeFolderUploadModal()"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button id="folderUploadTriggerBtn" onclick="submitFolderUpload()"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-primary hover:bg-primary/90 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                        Upload
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
             CREATE TASK MODAL
        ═══════════════════════════════════════ -->
        <div id="createTaskModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCreateTaskModal()"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="size-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-emerald-500">add_task</span>
                    </div>
                    <div>
                        <h3 class="text-slate-900 dark:text-white font-bold text-base">Create Task</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs">Assign a task to a team member</p>
                    </div>
                    <button onclick="closeCreateTaskModal()" class="ml-auto p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Title *</label>
                        <input id="taskTitleInput" type="text" placeholder="e.g. Review Q3 documents"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Description</label>
                        <textarea id="taskDescInput" rows="2" placeholder="Optional details…"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Assign To</label>
                            <select id="taskAssignSelect"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all">
                                <option value="">Loading members…</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Priority</label>
                            <select id="taskPrioritySelect"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Due Date</label>
                        <input id="taskDueDateInput" type="date"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 transition-all" />
                    </div>
                </div>
                <p id="createTaskError" class="text-red-500 text-xs mt-3 min-h-[1rem]"></p>
                <div class="flex items-center justify-end gap-2 mt-4">
                    <button onclick="closeCreateTaskModal()"
                        class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                    <button id="createTaskBtn" onclick="submitCreateTask()"
                        class="px-5 py-2 rounded-xl text-sm font-bold bg-emerald-500 hover:bg-emerald-400 text-white transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">Create Task</button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
             TASK JAVASCRIPT
        ═══════════════════════════════════════ -->
        <script>
        (function () {
            const TASK_API = '../api/projects.php';
            const priorityColors = { high: 'text-red-500 bg-red-500/10 border-red-500/20', medium: 'text-amber-500 bg-amber-500/10 border-amber-500/20', low: 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20' };
            const statusColors  = { pending: 'text-slate-500 bg-slate-500/10 border-slate-500/20', in_progress: 'text-blue-500 bg-blue-500/10 border-blue-500/20', completed: 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20' };
            const statusLabels  = { pending: 'Pending', in_progress: 'In Progress', completed: 'Completed' };

            window.openCreateTaskModal = async function () {
                document.getElementById('taskTitleInput').value = '';
                document.getElementById('taskDescInput').value = '';
                document.getElementById('taskPrioritySelect').value = 'medium';
                document.getElementById('taskDueDateInput').value = '';
                document.getElementById('createTaskError').textContent = '';

                // Populate assign dropdown with project members only
                const sel = document.getElementById('taskAssignSelect');
                sel.innerHTML = '<option value="">Loading…</option>';
                document.getElementById('createTaskModal').classList.remove('hidden');

                try {
                    const res = await fetch(`${TASK_API}?action=members&project_id=${currentProjectId}`);
                    const data = await res.json();
                    const members = data.data?.members || [];
                    sel.innerHTML = '<option value="">— Unassigned —</option>';
                    members.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.name + ' (' + (m.user_role || m.project_role || '') + ')';
                        sel.appendChild(opt);
                    });
                    if (members.length === 0) {
                        sel.innerHTML = '<option value="">No members in this project</option>';
                    }
                } catch {
                    sel.innerHTML = '<option value="">— Unassigned —</option>';
                }
            };

            window.closeCreateTaskModal = function () {
                document.getElementById('createTaskModal').classList.add('hidden');
            };

            window.submitCreateTask = async function () {
                const btn = document.getElementById('createTaskBtn');
                const errEl = document.getElementById('createTaskError');
                const title = document.getElementById('taskTitleInput').value.trim();
                if (!title) { errEl.textContent = 'Title is required.'; return; }
                if (!currentProjectId) { errEl.textContent = 'No project selected.'; return; }

                btn.disabled = true;
                btn.textContent = 'Creating…';
                errEl.textContent = '';
                try {
                    const fd = new FormData();
                    fd.append('action', 'create-task');
                    fd.append('project_id', currentProjectId);
                    fd.append('title', title);
                    fd.append('description', document.getElementById('taskDescInput').value.trim());
                    fd.append('assigned_to', document.getElementById('taskAssignSelect').value);
                    fd.append('priority', document.getElementById('taskPrioritySelect').value);
                    fd.append('due_date', document.getElementById('taskDueDateInput').value);
                    const res = await fetch(TASK_API, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.success) {
                        closeCreateTaskModal();
                        loadTasks();
                    } else {
                        errEl.textContent = data.error || 'Failed to create task.';
                    }
                } catch { errEl.textContent = 'Network error.'; }
                finally { btn.disabled = false; btn.textContent = 'Create Task'; }
            };

            window.loadTasks = async function () {
                if (!currentProjectId) return;
                const panel = document.getElementById('tasksPanel');
                if (!panel) return;
                panel.innerHTML = '<div class="p-6 text-center text-slate-400 dark:text-slate-500 text-sm">Loading…</div>';
                try {
                    const res = await fetch(`${TASK_API}?action=get-tasks&project_id=${currentProjectId}`);
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error);
                    const tasks = data.data.tasks || [];
                    renderTasks(tasks);
                    updateProjectProgressBar(tasks);
                } catch (e) {
                    panel.innerHTML = '<div class="p-6 text-center text-red-400 text-sm">Failed to load tasks.</div>';
                }
            };

            function updateProjectProgressBar(tasks) {
                const barEl   = document.getElementById('projectProgressBar');
                const pctEl   = document.getElementById('projectProgressPct');
                const statsEl = document.getElementById('projectProgressStats');
                if (!barEl || !pctEl || !statsEl) return;

                if (tasks.length === 0) {
                    barEl.style.width = '0%';
                    barEl.className = barEl.className.replace(/bg-\S+/g, 'bg-slate-300 dark:bg-slate-700');
                    pctEl.textContent = '0%';
                    statsEl.textContent = 'No tasks yet';
                    statsEl.className = 'text-xs text-slate-500 dark:text-slate-400 italic';
                    return;
                }

                const avg       = Math.round(tasks.reduce((s, t) => s + parseInt(t.progress || 0), 0) / tasks.length);
                const completed = tasks.filter(t => t.status === 'completed').length;
                barEl.style.width = avg + '%';
                barEl.className = barEl.className.replace(/bg-\S+/g,
                    avg >= 100 ? 'bg-emerald-500' : avg >= 50 ? 'bg-primary' : 'bg-amber-500');
                pctEl.textContent = avg + '%';
                statsEl.textContent = `${completed}/${tasks.length} tasks done`;
                statsEl.className = 'text-xs text-slate-500 dark:text-slate-400';

                // Update remaining text note (reuse the PHP-rendered element by ID)
                const note = document.getElementById('projectProgressNote');
                if (note) {
                    if (tasks.length === 0) {
                        note.className = 'text-[11px] text-slate-400 dark:text-slate-500 mt-1.5 italic';
                        note.innerHTML = 'No tasks yet';
                    } else if (avg >= 100) {
                        note.className = 'text-[11px] text-emerald-500 font-semibold mt-1.5 flex items-center gap-1';
                        note.innerHTML = '<span class="material-symbols-outlined text-[13px]">check_circle</span> All tasks completed!';
                    } else {
                        const rem = tasks.length - completed;
                        note.className = 'text-[11px] text-slate-400 dark:text-slate-500 mt-1.5';
                        note.textContent = `${rem} task${rem !== 1 ? 's' : ''} remaining`;
                    }
                }
            };

            function renderTasks(tasks) {
                const panel = document.getElementById('tasksPanel');
                if (!panel) return;
                if (tasks.length === 0) {
                    panel.innerHTML = '<div class="p-6 text-center text-slate-400 dark:text-slate-500 text-sm">No tasks yet. Click <strong>Add Task</strong> to create one.</div>';
                    return;
                }
                let html = '<div class="divide-y divide-slate-100 dark:divide-slate-800">';
                tasks.forEach(t => {
                    const pCls = priorityColors[t.priority] || priorityColors.medium;
                    const sCls = statusColors[t.status]   || statusColors.pending;
                    const sLbl = statusLabels[t.status]   || t.status;
                    const due  = t.due_date ? new Date(t.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
                    const isOverdue = t.due_date && t.status !== 'completed' && new Date(t.due_date) < new Date();
                    const progressBar = `<div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all ${t.status === 'completed' ? 'bg-emerald-500' : 'bg-primary'}" style="width:${t.progress}%"></div>
                    </div>`;
                    html += `<div class="px-4 py-3.5 group">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">${escapeHtml(t.title)}</p>
                                ${t.assigned_name ? `<p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Assigned to: ${escapeHtml(t.assigned_name)}</p>` : '<p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Unassigned</p>'}
                            </div>
                            <button onclick="adminDeleteTask(${t.id})" class="p-1 rounded text-slate-300 dark:text-slate-600 hover:text-red-500 hover:bg-red-500/10 transition-colors opacity-0 group-hover:opacity-100 flex-shrink-0" title="Delete task">
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                            </button>
                        </div>
                        <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold border uppercase ${pCls}">${t.priority}</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold border uppercase ${sCls}">${sLbl}</span>
                            ${due ? `<span class="text-[10px] ${isOverdue ? 'text-red-500 font-semibold' : 'text-slate-400 dark:text-slate-500'}">${isOverdue ? '⚠ Overdue · ' : ''}Due ${due}</span>` : ''}
                        </div>
                        <div class="flex items-center gap-2 mt-2">
                            ${progressBar}
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 flex-shrink-0 w-8 text-right">${t.progress}%</span>
                        </div>
                    </div>`;
                });
                html += '</div>';
                panel.innerHTML = html;
            }

            window.adminDeleteTask = async function (taskId) {
                if (!confirm('Delete this task?')) return;
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete-task');
                    fd.append('task_id', taskId);
                    const res = await fetch(TASK_API, { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.success) { loadTasks(); }
                    else { alert(data.error || 'Failed to delete task'); }
                } catch { alert('Network error'); }
            };

            // Auto-load tasks when project is open
            if (typeof currentProjectId !== 'undefined' && currentProjectId) {
                document.addEventListener('DOMContentLoaded', loadTasks);
            }
        })();
        </script>

        <script>
            try {
                localStorage.setItem('sv_new_uploads_seen_admin_<?php echo (int)$user['id']; ?>', String(Date.now()));
            } catch (e) {}
        </script>

        <?php include '../includes/settings_modal.php'; ?>
    <script src="../js/security-shield.js"></script>
</body>
</html>