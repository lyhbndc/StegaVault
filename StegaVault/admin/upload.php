<?php
//DON'T INCLUDE THIS PART


/**
 * StegaVault - Secure File Upload
 * File: admin/upload.php
 */

session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit;
}

$user = [
    'id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role']
];
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Upload Files - StegaVault</title>
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

        .drop-zone.dragover {
            border-color: #667eea;
            background-color: rgba(102, 126, 234, 0.08);
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .dark .progress-bar {
            background: #1e293b;
        }

        .progress-fill {
            height: 100%;
            background: #667eea;
            width: 0%;
            transition: width 0.3s;
        }

        /* Custom Scrollbar */
        #filesListContainer::-webkit-scrollbar {
            width: 6px;
        }

        #filesListContainer::-webkit-scrollbar-track {
            background: transparent;
        }

        #filesListContainer::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .dark #filesListContainer::-webkit-scrollbar-thumb {
            background: #334155;
        }

        #filesListContainer::-webkit-scrollbar-thumb:hover {
            background: #667eea;
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
                    href="analysis.php">
                    <span class="material-symbols-outlined text-[22px]">policy</span>
                    <p class="text-sm font-medium">Forensic Analysis</p>
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        href="users.php">
                        <span class="material-symbols-outlined text-[22px]">group</span>
                        <p class="text-sm font-medium">User Management</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        href="projects.php">
                        <span class="material-symbols-outlined text-[22px]">folder_managed</span>
                        <p class="text-sm font-medium">Projects</p>
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
                <?php endif; ?>
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
                            <?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-slate-500 dark:text-slate-400 text-xs capitalize">
                            <?php echo htmlspecialchars($user['role']); ?></p>
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
            <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight flex-shrink-0">Secure Vault</h2>
            <?php include '../includes/search_bar.php'; ?>
            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-500 text-xs font-semibold flex-shrink-0">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                System: Operational
            </div>
        </header>


        <div class="p-8 space-y-8">

            <!-- Upload Section -->
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <span class="material-symbols-outlined text-primary">cloud_upload</span>
                    </div>
                    <div>
                        <h1 class="text-slate-900 dark:text-white text-xl font-bold">Upload to Vault</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">Securely upload images for watermarking
                            and tracking.</p>
                    </div>
                </div>

                <!-- Watermark Option -->
                <div
                    class="mb-6 flex items-start gap-3 bg-white dark:bg-slate-900 p-4 border border-slate-200 dark:border-slate-800 rounded-xl max-w-sm mx-auto md:mx-0">
                    <div class="flex items-center h-6">
                        <input id="requireWatermark" type="checkbox" checked
                            class="w-4 h-4 text-primary bg-slate-100 border-slate-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-slate-900 focus:ring-2 dark:bg-slate-800 dark:border-slate-700 cursor-pointer">
                    </div>
                    <div class="flex flex-col cursor-pointer"
                        onclick="document.getElementById('requireWatermark').click();">
                        <label class="text-sm font-bold text-slate-900 dark:text-white cursor-pointer select-none">Apply
                            Invisible Watermark</label>
                        <span class="text-xs text-slate-500 dark:text-slate-400 select-none">Embeds tracking data into
                            the file pixels when downloaded. Uncheck to download as normal.</span>
                    </div>
                </div>

                <div class="mb-6 max-w-sm mx-auto md:mx-0">
                    <label for="pdfPassword" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">PDF Password (Optional)</label>
                    <input id="pdfPassword" type="password" maxlength="64" placeholder="Applied only to PDF uploads"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <label for="pdfPasswordConfirm" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5 mt-3">Confirm Password</label>
                    <input id="pdfPasswordConfirm" type="password" maxlength="64" placeholder="Confirm PDF password"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>

                <!-- Drop Zone -->
                <div class="bg-white dark:bg-slate-900 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-10 text-center drop-zone transition-colors cursor-pointer"
                    id="dropZone">
                    <div class="max-w-sm mx-auto">
                        <div class="size-16 mx-auto mb-4 bg-primary/10 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-4xl text-primary">upload_file</span>
                        </div>
                        <h3 class="text-slate-900 dark:text-white text-lg font-bold mb-1">Drag & Drop Files Here</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Supported: PNG, JPG, MP4, PDF, Office Docs (Word, Excel, PPT) &bull; Max 50MB</p>
                        <input type="file" id="fileInput" class="hidden"
                            accept="image/png,image/jpeg,image/jpg,image/webp,video/mp4,video/quicktime,video/x-msvideo,video/mpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain"
                            multiple>
                        <button id="browseBtn"
                            class="px-6 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors">
                            Browse Files
                        </button>
                    </div>
                </div>

                <!-- Progress -->
                <div id="uploadProgressContainer"
                    class="mt-4 hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-900 dark:text-white font-semibold text-sm">Uploading...</span>
                        <span class="text-primary font-bold text-sm" id="progressPercent">0%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 text-xs mt-2 text-center" id="uploadStatusText">
                        Preparing files...</p>
                </div>

                <!-- Message Box -->
                <div id="messageBox" class="mt-4 hidden p-4 rounded-xl border text-sm font-semibold text-center"></div>
            </section>

            <!-- Vault Contents -->
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-slate-900 dark:text-white text-xl font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">folder_open</span>
                        Vault Contents
                    </h2>
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                        Total: <span id="totalFilesCount" class="text-slate-900 dark:text-white font-bold">0</span>
                        files
                    </span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm min-h-[260px] max-h-[520px] overflow-y-auto"
                    id="filesListContainer">
                    <div class="p-10 text-center text-slate-400 dark:text-slate-500" id="emptyState">
                        <span class="material-symbols-outlined text-4xl mb-2 block">folder_off</span>
                        <p class="text-sm">No files in vault yet.</p>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const browseBtn = document.getElementById('browseBtn');
        const messageBox = document.getElementById('messageBox');
        const progressContainer = document.getElementById('uploadProgressContainer');
        const progressFill = document.getElementById('progressFill');
        const progressPercent = document.getElementById('progressPercent');
        const statusText = document.getElementById('uploadStatusText');
        const filesListContainer = document.getElementById('filesListContainer');
        const emptyState = document.getElementById('emptyState');
        const totalFilesCount = document.getElementById('totalFilesCount');

        // Load files immediately
        loadFiles();

        // Event Listeners
        browseBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fileInput.click();
        });
        dropZone.addEventListener('click', (e) => {
            if (e.target !== browseBtn) fileInput.click();
        });
        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        async function handleFiles(files) {
            if (files.length === 0) return;

            progressContainer.classList.remove('hidden');
            progressFill.style.width = '0%';
            progressPercent.textContent = '0%';

            let successCount = 0;
            const totalFiles = files.length;

            for (let i = 0; i < totalFiles; i++) {
                const file = files[i];
                
                // Client-side size check (50MB)
                const maxSizeBytes = 50 * 1024 * 1024;
                if (file.size > maxSizeBytes) {
                    showMessage(`File too large: ${file.name} (Max 50MB)`, 'error');
                    continue; // Skip this file and move to next
                }

                statusText.textContent = `Uploading ${file.name} (${i + 1}/${totalFiles})...`;

                try {
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('require_watermark', document.getElementById('requireWatermark').checked ? '1' : '0');

                    const pdfPasswordInput = document.getElementById('pdfPassword');
                    const pdfPassword = pdfPasswordInput ? pdfPasswordInput.value.trim() : '';
                    const pdfPasswordConfirmInput = document.getElementById('pdfPasswordConfirm');
                    const pdfPasswordConfirm = pdfPasswordConfirmInput ? pdfPasswordConfirmInput.value.trim() : '';

                    // Validate password confirmation
                    if (pdfPassword && pdfPassword !== pdfPasswordConfirm) {
                        showMessage('PDF passwords do not match. Please verify and try again.', 'error');
                        progressContainer.classList.add('hidden');
                        return;
                    }

                    const isPdf = (file.type === 'application/pdf') || ((file.name || '').toLowerCase().endsWith('.pdf'));
                    if (isPdf && pdfPassword) {
                        formData.append('pdf_password', pdfPassword);
                    }

                    const response = await fetch('../api/upload.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        successCount++;
                    } else {
                        showMessage(`Error uploading ${file.name}: ${data.error}`, 'error');
                    }
                } catch (error) {
                    console.error(error);
                    showMessage(`Upload failed for ${file.name}`, 'error');
                }

                const percent = Math.round(((i + 1) / totalFiles) * 100);
                progressFill.style.width = `${percent}%`;
                progressPercent.textContent = `${percent}%`;
            }

            setTimeout(() => {
                progressContainer.classList.add('hidden');
                statusText.textContent = '';
                if (successCount > 0) {
                    showMessage(`Successfully uploaded ${successCount} file${successCount > 1 ? 's' : ''}`, 'success');
                    loadFiles();
                }
            }, 1000);

            fileInput.value = '';
        }

        async function loadFiles() {
            try {
                const response = await fetch('../api/upload.php');
                const data = await response.json();

                if (data.success && data.data.files.length > 0) {
                    emptyState.classList.add('hidden');
                    renderFiles(data.data.files);
                    totalFilesCount.textContent = data.data.total;
                } else {
                    filesListContainer.innerHTML = '';
                    filesListContainer.appendChild(emptyState);
                    emptyState.classList.remove('hidden');
                    totalFilesCount.textContent = '0';
                }
            } catch (error) {
                console.error('Error loading files:', error);
            }
        }

        function renderFiles(files) {
            filesListContainer.innerHTML = '';

            // Table header
            const table = document.createElement('table');
            table.className = 'w-full text-left border-collapse';
            table.innerHTML = `
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">File</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
            `;

            const tbody = document.createElement('tbody');
            tbody.className = 'divide-y divide-slate-100 dark:divide-slate-800';

            files.forEach(file => {
                const sizeKB = (file.size / 1024).toFixed(2);
                const date = new Date(file.upload_date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });

                // Detect type from mime or extension
                const mt = file.type || '';
                const ext = (file.original_name || '').split('.').pop().toLowerCase();
                const isImg = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'].includes(mt) || ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
                const isVid = mt.startsWith('video/') || ['mp4', 'webm', 'mov', 'ogg'].includes(ext);
                const icon = isImg ? 'image' : (isVid ? 'movie' : 'description');
                const iconClr = isImg ? 'text-purple-500' : (isVid ? 'text-red-500' : 'text-blue-500');
                const iconBg = isImg ? 'bg-purple-500/10' : (isVid ? 'bg-red-500/10' : 'bg-blue-500/10');

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group cursor-pointer';
                tr.onclick = () => window.location.href = `preview.php?id=${file.id}`;
                tr.innerHTML = `
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 ${iconBg} rounded-lg">
                                <span class="material-symbols-outlined ${iconClr} text-[18px]">${icon}</span>
                            </div>
                            <p class="text-slate-900 dark:text-white font-semibold text-sm truncate max-w-[180px]">${file.original_name}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">${sizeKB} KB</td>
                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">${date}</td>
                    <td class="px-6 py-4">
                        ${file.watermarked == 1
                        ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Protected</span>`
                        : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">Unprotected</span>`
                    }
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2 justify-end opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                            <a href="../api/download.php?file_id=${file.id}" onclick="event.stopPropagation()"
                               class="flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 hover:bg-primary text-primary hover:text-white text-xs font-bold rounded-lg transition-all border border-primary/20 hover:border-primary">
                                <span class="material-symbols-outlined text-sm">water_drop</span>
                                Download
                            </a>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            filesListContainer.appendChild(table);
        }

        function showMessage(text, type) {
            messageBox.textContent = text;
            messageBox.className = `mt-4 p-4 rounded-xl border text-sm font-semibold text-center ${type === 'error'
                    ? 'bg-red-500/10 border-red-500/20 text-red-500'
                    : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400'
                }`;
            messageBox.classList.remove('hidden');
            
            // Errors stay for 15 seconds, success stays for 5 seconds
            const duration = type === 'error' ? 15000 : 5000;
            setTimeout(() => messageBox.classList.add('hidden'), duration);
        }
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