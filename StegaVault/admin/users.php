<?php

/**
 * StegaVault - User Management
 * File: admin/users.php
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

// Get all users with dynamic status check for expiration
$stmt = $db->prepare("SELECT id, username, name, email, role, 
    CASE 
        WHEN expiration_date IS NOT NULL AND expiration_date < CURRENT_DATE THEN 'expired' 
        ELSE status 
    END AS status, 
    expiration_date, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count by role
$adminCount = 0;
$employeeCount = 0;
$collaboratorCount = 0;
foreach ($users as $u) {
    if ($u['role'] === 'admin')
        $adminCount++;
    if ($u['role'] === 'employee')
        $employeeCount++;
    if ($u['role'] === 'collaborator')
        $collaboratorCount++;
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>User Management - StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
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
                        "display": ["Inter", "sans-serif"],
                        "mono": ["ui-monospace", "monospace"]
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

        .drawer-hidden {
            transform: translateX(100%);
        }

        .drawer-visible {
            transform: translateX(0);
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex">

    <!-- Sidebar -->
    <aside
        class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50">
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
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="dashboard.php">
                    <span class="material-symbols-outlined text-[22px]">dashboard</span>
                    <p class="text-sm font-medium">Dashboard</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="projects.php">
                    <span class="material-symbols-outlined text-[22px]">folder_managed</span>
                    <p class="text-sm font-medium">Projects</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="analysis.php">
                    <span class="material-symbols-outlined text-[22px]">policy</span>
                    <p class="text-sm font-medium">Forensic Analysis</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white" href="users.php">
                    <span class="material-symbols-outlined text-[22px]"
                        style="font-variation-settings: 'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">group</span>
                    <p class="text-sm font-medium">User Management</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="activity.php">
                    <span class="material-symbols-outlined text-[22px]">history</span>
                    <p class="text-sm font-medium">Activity Logs</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="reports.php">
                    <span class="material-symbols-outlined text-[22px]">summarize</span>
                    <p class="text-sm font-medium">Reports</p>
                </a>
            </nav>

            <!-- User Profile (click to open settings) -->
            <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                <button onclick="openSettings()"
                    class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group text-left">
                    <div id="sidebarProfileAvatar"
                        class="bg-primary rounded-full size-10 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p id="sidebarProfileName"
                            class="text-slate-900 dark:text-white text-sm font-semibold truncate">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400 text-xs capitalize">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </p>
                    </div>
                    <span
                        class="material-symbols-outlined text-slate-400 group-hover:text-primary text-[18px] transition-colors">settings</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64 flex flex-col">
        <!-- Sticky Top Header -->
        <header
            class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center gap-6">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight flex-shrink-0">User Management
            </h2>
            <?php include '../includes/search_bar.php'; ?>
            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-500 text-xs font-semibold flex-shrink-0">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                System: Operational
            </div>
        </header>


        <div class="p-8 space-y-6">

            <!-- Page Header & Action -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-slate-900 dark:text-white text-xl font-bold">System Users</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Manage access privileges and security
                        roles.</p>
                </div>
                <button onclick="openDrawer()"
                    class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-2.5 px-5 rounded-lg transition-all shadow-sm text-sm">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Add New User
                </button>
            </div>

            <!-- Stats Cards -->
            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <span class="material-symbols-outlined text-primary">group</span>
                        </div>
                        <span class="text-primary text-sm font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">trending_up</span> All
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Total Users</p>
                    <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight">
                        <?php echo count($users); ?>
                    </h3>
                </div>
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-purple-500/10 rounded-lg">
                            <span class="material-symbols-outlined text-purple-500">admin_panel_settings</span>
                        </div>
                        <span class="text-purple-500 text-sm font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">shield</span> Privileged
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Admins</p>
                    <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight">
                        <?php echo $adminCount; ?>
                    </h3>
                </div>
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-blue-500/10 rounded-lg">
                            <span class="material-symbols-outlined text-blue-500">badge</span>
                        </div>
                        <span class="text-blue-500 text-sm font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">verified</span> Standard
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Employees</p>
                    <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight">
                        <?php echo $employeeCount; ?>
                    </h3>
                </div>
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-orange-500/10 rounded-lg">
                            <span class="material-symbols-outlined text-orange-500">handshake</span>
                        </div>
                        <span class="text-orange-500 text-sm font-bold flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">groups</span> Restricted
                        </span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Collaborators</p>
                    <h3 class="text-slate-900 dark:text-white text-3xl font-bold mt-1 tracking-tight">
                        <?php echo $collaboratorCount; ?>
                    </h3>
                </div>
            </section>

            <!-- Filter Bar -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm p-4 space-y-3">

                <!-- Search row -->
                <div class="flex items-center gap-3">
                    <div class="relative flex-1">
                        <span
                            class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-400 text-[18px]">search</span>
                        <input id="searchInput" oninput="filterUsers()" type="text"
                            placeholder="Search by name or email…"
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-2.5 pl-10 pr-4 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm" />
                    </div>
                    <button id="clearFiltersBtn" onclick="clearFilters()"
                        class="hidden items-center gap-1.5 px-3 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 text-xs font-semibold rounded-lg transition-colors">
                        <span class="material-symbols-outlined text-[14px]">filter_list_off</span>
                        Clear
                    </button>
                </div>

                <!-- Filter chips row -->
                <div class="flex items-center gap-4 flex-wrap">

                    <!-- Role filter -->
                    <div class="flex items-center gap-1.5">
                        <span
                            class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Role</span>
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
                            <button onclick="setRoleFilter('all')" id="role-all"
                                class="px-3 py-1 rounded-md text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm transition-all">All</button>
                            <button onclick="setRoleFilter('admin')" id="role-admin"
                                class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Admin</button>
                            <button onclick="setRoleFilter('employee')" id="role-employee"
                                class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Employee</button>
                            <button onclick="setRoleFilter('collaborator')" id="role-collaborator"
                                class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Collaborator</button>
                        </div>
                    </div>

                    <!-- Status filter -->
                    <div class="flex items-center gap-1.5">
                        <span
                            class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Status</span>
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
                            <button onclick="setStatusFilter('all')" id="status-all"
                                class="px-3 py-1 rounded-md text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm transition-all">All</button>
                            <button onclick="setStatusFilter('active')" id="status-active"
                                class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Active</button>
                            <button onclick="setStatusFilter('pending_activation')" id="status-pending_activation"
                                class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Pending</button>
                            <button onclick="setStatusFilter('disabled')" id="status-disabled"
                                class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Locked</button>
                            <button onclick="setStatusFilter('expired')" id="status-expired"
                                class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Expired</button>
                        </div>
                    </div>

                    <!-- Result count -->
                    <span id="filterCount" class="ml-auto text-xs text-slate-400 dark:text-slate-500"></span>
                </div>
            </div>

            <!-- Message Box -->
            <div id="messageBox" style="display: none;" class="p-4 rounded-xl font-semibold text-sm text-center border">
            </div>

            <!-- Users Table -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">User
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Username
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Role
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Joined
                            </th>
                            <th
                                class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody" class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors user-row group"
                                data-name="<?php echo htmlspecialchars($u['name']); ?>"
                                data-email="<?php echo htmlspecialchars($u['email']); ?>"
                                data-username="<?php echo htmlspecialchars($u['username'] ?? ''); ?>"
                                data-role="<?php echo $u['role']; ?>" data-status="<?php echo $u['status'] ?? 'active'; ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="size-9 rounded-full flex items-center justify-center font-bold text-xs
                                        <?php echo $u['role'] === 'admin' ? 'bg-purple-500/10 text-purple-500' : 'bg-blue-500/10 text-blue-500'; ?>">
                                            <?php echo strtoupper(substr($u['name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($u['name']); ?>
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                                <?php echo htmlspecialchars($u['email']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-500 dark:text-slate-400">
                                    <?php echo htmlspecialchars($u['username'] ?? ''); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">Admin</span>
                                    <?php elseif ($u['role'] === 'collaborator'): ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/20">Collaborator</span>
                                    <?php else: ?>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">Employee</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusClass = match ($u['status'] ?? 'active') {
                                        'active' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                        'pending_activation' => 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border-yellow-500/20',
                                        'disabled' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                                        'expired' => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
                                        default => 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700'
                                    };
                                    $statusLabel = match ($u['status'] ?? 'active') {
                                        'active' => 'Active',
                                        'pending_activation' => 'Pending',
                                        'disabled' => 'Locked',
                                        'expired' => 'Expired',
                                        default => ucfirst($u['status'] ?? 'active')
                                    };
                                    ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                                    <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex justify-end gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)"
                                            class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-400 hover:text-primary transition-colors"
                                            title="Edit">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <?php if ($u['id'] != $user['id']): ?>
                                            <button
                                                onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?>')"
                                                class="p-1.5 hover:bg-red-500/10 rounded-lg text-slate-400 hover:text-red-500 transition-colors"
                                                title="Delete">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="paginationWrapper"
                    class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/30 flex items-center justify-between gap-3">
                    <p id="pageInfo" class="text-xs text-slate-500 dark:text-slate-400">Showing 0-0 of 0</p>
                    <div class="flex items-center gap-2">
                        <button id="prevPageBtn" type="button" onclick="prevPage()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-slate-500">
                            <span class="material-symbols-outlined text-[14px]">chevron_left</span>
                            Prev
                        </button>
                        <span id="pageNumber"
                            class="text-xs font-semibold text-slate-600 dark:text-slate-300 min-w-[70px] text-center">Page
                            1 / 1</span>
                        <button id="nextPageBtn" type="button" onclick="nextPage()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-slate-500">
                            Next
                            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Slide-out Drawer -->
    <div id="drawerBackdrop" onclick="closeDrawer()" class="fixed inset-0 z-50 overflow-hidden hidden" role="dialog"
        aria-modal="true">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
        <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div id="drawer"
                    class="pointer-events-auto w-screen max-w-md transform transition duration-300 ease-in-out drawer-hidden">
                    <div onclick="event.stopPropagation()"
                        class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl">
                        <!-- Drawer Header -->
                        <div
                            class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white" id="drawerTitle">Create
                                        New User</h2>
                                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Add a new team member
                                        to StegaVault.</p>
                                </div>
                                <button onclick="closeDrawer()"
                                    class="ml-3 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                    <span class="material-symbols-outlined text-[20px]">close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Drawer Form -->
                        <form id="userForm" onsubmit="saveUser(event)" class="flex-1 py-6 px-6 space-y-6">
                            <input type="hidden" id="userId" name="user_id" />

                            <!-- Identity Details -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="material-symbols-outlined text-[18px] text-primary">account_circle</span>
                                    <h3
                                        class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Identity Details</h3>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Full
                                        Name *</label>
                                    <input id="userName" name="name" required type="text" placeholder="e.g. John Smith"
                                        oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm" />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Username
                                        *</label>
                                    <input id="userUsername" name="username" required type="text"
                                        placeholder="e.g. jsmith"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm" />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email
                                        Address *</label>
                                    <input id="userEmail" name="email" required type="email"
                                        placeholder="e.g. john@umak.edu.ph"
                                        pattern="^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$"
                                        title="Please provide a valid email address (e.g., @gmail.com or @umak.edu.ph)"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm" />
                                </div>
                                <div id="passwordField" class="space-y-2">
                                    <label
                                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Password
                                        *</label>
                                    <div class="relative">
                                        <input id="userPassword" name="password" type="password"
                                            placeholder="Min. 12 characters" minlength="12"
                                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 pr-12 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm"
                                            oninput="checkPwPolicy()" />
                                        <button type="button" onclick="togglePassword()"
                                            class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">visibility_off</button>
                                    </div>
                                    <!-- Password Policy Panel -->
                                    <div id="pwPolicyPanel"
                                        class="hidden mt-2 p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl space-y-2">
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">
                                            Password Requirements</p>

                                        <!-- Strength bar -->
                                        <div
                                            class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden mb-2">
                                            <div id="pwStrengthBar"
                                                class="h-full rounded-full transition-all duration-300 w-0 bg-red-500">
                                            </div>
                                        </div>

                                        <div id="pwRule-len"
                                            class="flex items-center gap-2 text-xs text-slate-500 transition-colors">
                                            <span
                                                class="material-symbols-outlined text-[14px]">radio_button_unchecked</span>
                                            12–25 characters
                                        </div>
                                        <div id="pwRule-upper"
                                            class="flex items-center gap-2 text-xs text-slate-500 transition-colors">
                                            <span
                                                class="material-symbols-outlined text-[14px]">radio_button_unchecked</span>
                                            At least one uppercase letter
                                        </div>
                                        <div id="pwRule-lower"
                                            class="flex items-center gap-2 text-xs text-slate-500 transition-colors">
                                            <span
                                                class="material-symbols-outlined text-[14px]">radio_button_unchecked</span>
                                            At least one lowercase letter
                                        </div>
                                        <div id="pwRule-num"
                                            class="flex items-center gap-2 text-xs text-slate-500 transition-colors">
                                            <span
                                                class="material-symbols-outlined text-[14px]">radio_button_unchecked</span>
                                            At least one number
                                        </div>
                                        <div id="pwRule-sym"
                                            class="flex items-center gap-2 text-xs text-slate-500 transition-colors">
                                            <span
                                                class="material-symbols-outlined text-[14px]">radio_button_unchecked</span>
                                            At least one special character (!@#$%…)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Role Selection -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px] text-primary">badge</span>
                                    <h3
                                        class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        System Role</h3>
                                </div>
                                <select id="userRole" name="role" required
                                    class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm">
                                    <option value="employee">Employee (Standard Access)</option>
                                    <option value="admin">Admin (Full Access)</option>
                                    <option value="collaborator">Collaborator (Restricted)</option>
                                </select>
                            </div>

                            <!-- Account Settings -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px] text-primary">schedule</span>
                                    <h3
                                        class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Account Settings</h3>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Account
                                        Status</label>
                                    <select id="userStatus" name="status"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm">
                                        <option value="active">Active</option>
                                        <option value="pending_activation">Pending Activation</option>
                                        <option value="disabled">Locked</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Expiration
                                        Date (Optional)</label>
                                    <input id="userExpiration" name="expiration_date" type="date"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all text-sm" />
                                </div>
                            </div>

                            <!-- Footer Buttons -->
                            <div class="border-t border-slate-200 dark:border-slate-800 pt-6 flex justify-end gap-3">
                                <button type="button" onclick="closeDrawer()"
                                    class="px-4 py-2 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="bg-primary hover:bg-primary/90 text-white px-6 py-2 rounded-lg text-sm font-bold transition-all shadow-sm">
                                    <span id="submitText">Create User</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const STATUS_OPTIONS = [{
                value: 'active',
                label: 'Active'
            },
            {
                value: 'pending_activation',
                label: 'Pending Activation'
            },
            {
                value: 'disabled',
                label: 'Locked'
            },
            {
                value: 'expired',
                label: 'Expired'
            }
        ];

        function setStatusMode(mode, selectedValue = 'pending_activation') {
            const statusSelect = document.getElementById('userStatus');
            if (!statusSelect) return;

            const options = mode === 'create' ? [{
                value: 'pending_activation',
                label: 'Pending Activation'
            }] : STATUS_OPTIONS;

            statusSelect.innerHTML = options.map(option =>
                `<option value="${option.value}">${option.label}</option>`
            ).join('');

            const hasSelected = options.some(option => option.value === selectedValue);
            statusSelect.value = hasSelected ? selectedValue : options[0].value;
        }

        function openDrawer(reset = true) {
            document.getElementById('drawerBackdrop').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('drawer').classList.remove('drawer-hidden');
                document.getElementById('drawer').classList.add('drawer-visible');
            }, 10);
            if (reset) {
                resetForm();
            }
        }

        function closeDrawer() {
            document.getElementById('drawer').classList.add('drawer-hidden');
            document.getElementById('drawer').classList.remove('drawer-visible');
            setTimeout(() => {
                document.getElementById('drawerBackdrop').classList.add('hidden');
            }, 300);
        }

        function resetForm() {
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('drawerTitle').textContent = 'Create New User';
            document.getElementById('submitText').textContent = 'Create User';
            document.getElementById('userPassword').required = true;
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('pwPolicyPanel').classList.remove('hidden');
            setStatusMode('create', 'pending_activation');

            const passInput = document.getElementById('userPassword');
            passInput.type = 'password';
            passInput.nextElementSibling.textContent = 'visibility_off';

            checkPwPolicy();
        }

        function editUser(u) {
            openDrawer(false);

            document.getElementById('userId').value = u.id;
            document.getElementById('userName').value = u.name;
            document.getElementById('userUsername').value = u.username || '';
            document.getElementById('userEmail').value = u.email;
            document.getElementById('userRole').value = u.role;
            setStatusMode('edit', u.status || 'active');
            document.getElementById('userExpiration').value = u.expiration_date ? u.expiration_date.split(' ')[0] : '';

            document.getElementById('drawerTitle').textContent = 'Edit User';
            document.getElementById('submitText').textContent = 'Update User';
            document.getElementById('userPassword').required = false;
            document.getElementById('passwordField').style.display = 'none';
        }

        async function saveUser(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            const userId = document.getElementById('userId').value;

            const method = userId ? 'PUT' : 'POST';
            const action = userId ? 'update' : 'create';

            if (userId) data.id = userId;

            try {
                const response = await fetch(`../api/users.php?action=${action}`, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const resData = await response.json();

                if (resData.success) {
                    showMessage(resData.message || 'User saved successfully!', 'success');
                    closeDrawer();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(resData.error || 'Failed to save user', 'error');
                }
            } catch (error) {
                showMessage('Error: ' + error.message, 'error');
            }
        }

        async function deleteUser(id, name) {
            if (!confirm(`Are you sure you want to delete ${name}?`)) return;

            try {
                const response = await fetch('../api/users.php?action=delete', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('User deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.error || 'Failed to delete user', 'error');
                }
            } catch (error) {
                showMessage('Error: ' + error.message, 'error');
            }
        }

        // ── Filter state ──────────────────────────────────────────────────
        let _roleFilter = 'all';
        let _statusFilter = 'all';
        let _currentPage = 1;
        const _usersPerPage = 10;

        function filterUsers(resetPage = true) {
            if (resetPage) _currentPage = 1;

            const search = document.getElementById('searchInput').value.toLowerCase().trim();
            const rows = Array.from(document.querySelectorAll('.user-row'));
            const filteredRows = [];

            rows.forEach(row => {
                const name = row.dataset.name.toLowerCase();
                const email = row.dataset.email.toLowerCase();
                const username = (row.dataset.username || '').toLowerCase();
                const role = row.dataset.role.toLowerCase();
                const status = row.dataset.status.toLowerCase();

                const matchSearch = !search || name.includes(search) || email.includes(search) || username.includes(search);
                const matchRole = _roleFilter === 'all' || role === _roleFilter;
                const matchStatus = _statusFilter === 'all' || status === _statusFilter;

                const show = matchSearch && matchRole && matchStatus;
                if (show) filteredRows.push(row);
                row.style.display = 'none';
            });

            const total = rows.length;
            const visible = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(visible / _usersPerPage));

            if (_currentPage > totalPages) _currentPage = totalPages;

            const startIndex = (_currentPage - 1) * _usersPerPage;
            const endIndex = startIndex + _usersPerPage;
            const pageRows = filteredRows.slice(startIndex, endIndex);
            pageRows.forEach(row => {
                row.style.display = '';
            });

            // Update result count
            const countEl = document.getElementById('filterCount');
            if (countEl) countEl.textContent = visible === total ? `${total} users` : `${visible} of ${total} users`;

            // Update pagination ui
            const pageInfo = document.getElementById('pageInfo');
            const pageNumber = document.getElementById('pageNumber');
            const prevBtn = document.getElementById('prevPageBtn');
            const nextBtn = document.getElementById('nextPageBtn');
            const paginationWrapper = document.getElementById('paginationWrapper');

            if (pageInfo) {
                if (visible === 0) {
                    pageInfo.textContent = 'Showing 0-0 of 0';
                } else {
                    pageInfo.textContent = `Showing ${startIndex + 1}-${Math.min(endIndex, visible)} of ${visible}`;
                }
            }

            if (pageNumber) pageNumber.textContent = `Page ${_currentPage} / ${totalPages}`;
            if (prevBtn) prevBtn.disabled = _currentPage <= 1;
            if (nextBtn) nextBtn.disabled = _currentPage >= totalPages || visible === 0;
            if (paginationWrapper) paginationWrapper.style.display = visible <= _usersPerPage ? 'none' : 'flex';

            // Show/hide clear button
            const hasFilter = search || _roleFilter !== 'all' || _statusFilter !== 'all';
            const clearBtn = document.getElementById('clearFiltersBtn');
            if (clearBtn) clearBtn.classList.toggle('hidden', !hasFilter);
            if (clearBtn) clearBtn.classList.toggle('flex', hasFilter);
        }

        function prevPage() {
            if (_currentPage <= 1) return;
            _currentPage--;
            filterUsers(false);
        }

        function nextPage() {
            _currentPage++;
            filterUsers(false);
        }

        function setRoleFilter(val) {
            _roleFilter = val;
            // Update chip styles
            ['all', 'admin', 'employee', 'collaborator'].forEach(r => {
                const btn = document.getElementById('role-' + r);
                if (!btn) return;
                if (r === val) {
                    btn.className = 'px-3 py-1 rounded-md text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm transition-all';
                } else {
                    btn.className = 'px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all';
                }
            });
            filterUsers();
        }

        function setStatusFilter(val) {
            _statusFilter = val;
            ['all', 'active', 'pending_activation', 'disabled', 'expired'].forEach(s => {
                const btn = document.getElementById('status-' + s);
                if (!btn) return;
                if (s === val) {
                    btn.className = 'px-3 py-1 rounded-md text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm transition-all';
                } else {
                    btn.className = 'px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all';
                }
            });
            filterUsers();
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            setRoleFilter('all');
            setStatusFilter('all');
        }

        // Init on load
        document.addEventListener('DOMContentLoaded', () => {
            filterUsers();
            
            // Set minimum expiration date to tomorrow
            const expirationInput = document.getElementById('userExpiration');
            if (expirationInput) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                const tomorrowStr = tomorrow.toISOString().split('T')[0];
                expirationInput.setAttribute('min', tomorrowStr);
            }
        });

        function showMessage(message, type) {
            const box = document.getElementById('messageBox');
            box.textContent = message;
            box.className = 'p-4 rounded-xl font-semibold text-sm text-center border ' + (
                type === 'success' ?
                'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400' :
                'bg-red-500/10 border-red-500/20 text-red-500'
            );
            box.style.display = 'block';
            setTimeout(() => {
                box.style.display = 'none';
            }, 5000);
        }

        function togglePassword() {
            const passInput = document.getElementById('userPassword');
            const icon = event.target;

            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.textContent = 'visibility';
            } else {
                passInput.type = 'password';
                icon.textContent = 'visibility_off';
            }
        }

        // ── Password Policy Live Checker ─────────────────────────
        const rules = [{
                id: 'pwRule-len',
                test: p => p.length >= 12 && p.length <= 25
            },
            {
                id: 'pwRule-upper',
                test: p => /[A-Z]/.test(p)
            },
            {
                id: 'pwRule-lower',
                test: p => /[a-z]/.test(p)
            },
            {
                id: 'pwRule-num',
                test: p => /[0-9]/.test(p)
            },
            {
                id: 'pwRule-sym',
                test: p => /[\W_]/.test(p)
            },
        ];
        const strengthColors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-yellow-400', 'bg-emerald-500'];
        const strengthWidths = ['w-1/5', 'w-2/5', 'w-3/5', 'w-4/5', 'w-full'];

        function checkPwPolicy() {
            const p = document.getElementById('userPassword').value;
            let passed = 0;
            if (p.length === 0) {
                // reset everything if empty
                rules.forEach(r => {
                    const el = document.getElementById(r.id);
                    el.className = 'flex items-center gap-2 text-xs text-slate-500 transition-colors';
                    el.querySelector('span').textContent = 'radio_button_unchecked';
                });
                const bar = document.getElementById('pwStrengthBar');
                bar.className = 'h-full rounded-full transition-all duration-300 w-0 bg-red-500';
                return;
            }

            rules.forEach(r => {
                const ok = r.test(p);
                const el = document.getElementById(r.id);
                if (ok) {
                    passed++;
                    el.className = 'flex items-center gap-2 text-xs text-emerald-400 transition-colors';
                    el.querySelector('span').textContent = 'check_circle';
                } else {
                    el.className = 'flex items-center gap-2 text-xs text-slate-500 transition-colors';
                    el.querySelector('span').textContent = 'radio_button_unchecked';
                }
            });
            const bar = document.getElementById('pwStrengthBar');
            bar.className = 'h-full rounded-full transition-all duration-300 ' +
                strengthWidths[passed - 1 < 0 ? 0 : passed - 1] + ' ' +
                strengthColors[passed - 1 < 0 ? 0 : passed - 1];
            if (passed === 0) bar.className = 'h-full rounded-full transition-all duration-300 w-0 bg-red-500';
        }
        // ─────────────────────────────────────────────────────────
    </script>

    <!-- Security Shield -->
    <script>
        window.currentUser = {
            name: "<?php echo htmlspecialchars($user['name']); ?>",
            email: "<?php echo htmlspecialchars($user['email']); ?>"
        };
    </script>
    <script src="../js/security-shield.js"></script>
    <?php include '../includes/settings_modal.php'; ?>
</body>

</html>