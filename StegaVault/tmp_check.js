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
    <script>
        const currentUserId = 1;
        let myProjects = [];
        let selectedProjectId = null;

        loadMyProjects();

        async function loadMyProjects() {
            try {
                const response = await fetch('../api/projects.php?action=my-projects');
                const data = await response.json();

                if (data.success) {
                    myProjects = data.data.projects;
                    renderSidebar();

                    if (selectedProjectId) {
                        loadProjectContent(selectedProjectId);
                    }
                }
            } catch (error) {
                console.error(error);
            }
        }

        function renderSidebar() {
            const list = document.getElementById('projectsList');
            if (myProjects.length === 0) {
                list.innerHTML = `<p class="text-xs text-slate-400 dark:text-slate-500 px-3 py-2">No projects assigned</p>`;
                return;
            }

            list.innerHTML = myProjects.map(p => `
                <button onclick="selectProject(${p.id})"
                    class="w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors ${p.id == selectedProjectId
                        ? 'bg-primary/10 text-primary border border-primary/20'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-transparent'}">
                    <span class="size-2 rounded-full flex-shrink-0" style="background-color: ${p.color}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">${p.name}</div>
                        <div class="text-[10px] text-slate-400 dark:text-slate-500">${p.file_count} file${p.file_count !== 1 ? 's' : ''}</div>
                    </div>
                </button>
            `).join('');
        }

        function selectProject(id) {
            selectedProjectId = id;
            window.history.pushState({}, '', `?project=${id}`);
            renderSidebar();
            loadProjectContent(id);
        }

        // ─── Unified File Browser State ───────────────────────────────
        let _currentFolderId = null;
        let _folderTrail = []; // [{id, name}, ...]
        let _paneFolders = [];
        let _paneFiles = [];
        let _fileFilter = 'all';
        let _fileSort = 'date-desc';
        let _fileView = 'list';

        // Add format helpers before loadProjectContent
        function avatarColor(name) {
            let h = 0;
            for (let i = 0; i < (name || '').length; i++) h = name.charCodeAt(i) + ((h << 5) - h);
            return 'hsl(' + (Math.abs(h) % 360) + ',55%,48%)';
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


        async function loadProjectContent(id) {
            const project = myProjects.find(p => p.id == id);
            if (!project) return;

            // Reset pane state on project change
            _currentFolderId = null;
            _folderTrail = [];
            _fileFilter = 'all';

            const content = document.getElementById('mainContent');
            content.innerHTML = '<div class="text-center py-16 text-slate-400 dark:text-slate-500 text-sm">Loading project...</div>';

            try {
                const membersRes = await fetch('../api/projects.php?action=members&project_id=' + id);
                const membersData = await membersRes.json();
                const members = membersData.data?.members || [];

                // Draw skeleton
                displayProjectContent(project, members);

                // Then load the file pane
                await loadPane(null);

            } catch (error) {
                console.error(error);
                content.innerHTML = '<div class="text-center text-red-500 text-sm py-8">Error loading project data</div>';
            }
        }

        function displayProjectContent(project, members) {
            const content = document.getElementById('mainContent');

            let html = '';
            html += '<!-- Project Header -->\n';
            html += '<div class="flex items-start justify-between mb-6">\n';
            html += '    <div>\n';
            html += '        <div class="flex items-center gap-2 mb-1">\n';
            html += '            <span class="size-3 rounded-full" style="background-color: ' + project.color + '"></span>\n';
            html += '            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wider">Active Project</span>\n';
            html += '        </div>\n';
            html += '        <h1 class="text-slate-900 dark:text-white text-2xl font-bold">' + project.name + '</h1>\n';
            html += '        <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">' + (project.description || 'No description') + '</p>\n';
            html += '    </div>\n';
            html += '    <div class="flex items-center gap-2">\n';
            html += '        <button onclick="openCreateFolderModal(' + project.id + ')" class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-lg transition-all shadow-sm border border-slate-200 dark:border-slate-700">\n';
            html += '            <span class="material-symbols-outlined text-[18px]">create_new_folder</span>New Folder\n';
            html += '        </button>\n';
            html += '        <a href="upload.php?project=' + project.id + '" class="flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-all shadow-sm">\n';
            html += '            <span class="material-symbols-outlined text-[18px]">cloud_upload</span>Upload Files\n';
            html += '        </a>\n';
            html += '    </div>\n';
            html += '</div>\n';

            html += '<!-- Main Grid -->\n';
            html += '<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">\n';
            html += '    <!-- Files + Folders (2/3 width) -->\n';
            html += '    <div class="xl:col-span-2 space-y-4">\n';
            html += '        <!-- Unified File Browser Pane -->\n';
            html += '        <div class="flex items-center justify-between px-1 mb-2">\n';
            html += '            <div class="flex items-center gap-1.5 flex-wrap min-w-0">\n';
            html += '                <h2 class="text-slate-900 dark:text-white text-base font-bold flex-shrink-0">Files & Folders</h2>\n';
            html += '                <div id="paneBreadcrumb" class="hidden items-center gap-1 text-xs text-slate-400 flex-wrap"></div>\n';
            html += '            </div>\n';
            html += '        </div>\n';

            html += '        <!-- Toolbar -->\n';
            html += '        <div class="flex items-center gap-2 mb-3 flex-wrap">\n';
            html += '            <!-- Filter chips -->\n';
            html += '            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">\n';
            html += '                <button id="fChip-all" onclick="setFileFilter(\'all\')" class="px-3 py-1 rounded-md text-xs font-semibold bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm transition-all">All</button>\n';
            html += '                <button id="fChip-image" onclick="setFileFilter(\'image\')" class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Images</button>\n';
            html += '                <button id="fChip-video" onclick="setFileFilter(\'video\')" class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Videos</button>\n';
            html += '                <button id="fChip-doc" onclick="setFileFilter(\'doc\')" class="px-3 py-1 rounded-md text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all">Docs</button>\n';
            html += '            </div>\n';
            html += '            <!-- Sort dropdown -->\n';
            html += '            <div class="relative">\n';
            html += '                <select id="fileSortSelect" onchange="setFileSort(this.value)" class="appearance-none pl-3 pr-8 py-1.5 text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/30 cursor-pointer">\n';
            html += '                    <option value="date-desc">Newest first</option>\n';
            html += '                    <option value="date-asc">Oldest first</option>\n';
            html += '                    <option value="name-asc">Name A&rarr;Z</option>\n';
            html += '                    <option value="name-desc">Name Z&rarr;A</option>\n';
            html += '                    <option value="size-desc">Largest first</option>\n';
            html += '                    <option value="size-asc">Smallest first</option>\n';
            html += '                </select>\n';
            html += '                <span class="material-symbols-outlined text-[14px] text-slate-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none">unfold_more</span>\n';
            html += '            </div>\n';
            html += '            <!-- View toggles -->\n';
            html += '            <div class="ml-auto flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-1">\n';
            html += '                <button id="vBtn-list" onclick="setFileView(\'list\')" title="List view" class="p-1.5 rounded-md bg-white dark:bg-slate-700 text-slate-700 dark:text-white shadow-sm transition-all">\n';
            html += '                    <span class="material-symbols-outlined text-[16px]">view_list</span>\n';
            html += '                </button>\n';
            html += '                <button id="vBtn-grid" onclick="setFileView(\'grid\')" title="Grid view" class="p-1.5 rounded-md text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition-all">\n';
            html += '                    <span class="material-symbols-outlined text-[16px]">grid_view</span>\n';
            html += '                </button>\n';
            html += '            </div>\n';
            html += '        </div>\n';

            html += '        <!-- Drop Zone / Main Area -->\n';
            html += '        <div id="filesPaneWrapper" class="relative">\n';
            html += '            <div id="filesPane" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm transition-all">\n';
            html += '                <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">Loading workspace...</div>\n';
            html += '            </div>\n';
            html += '            <!-- Drop overlay mapping to folder drop upload -->\n';
            html += '            <div id="dropOverlay" class="hidden absolute inset-0 z-20 rounded-xl border-2 border-dashed border-primary bg-primary/10 backdrop-blur-sm flex flex-col items-center justify-center gap-3 pointer-events-none">\n';
            html += '                <div class="size-16 rounded-2xl bg-primary/20 flex items-center justify-center">\n';
            html += '                    <span class="material-symbols-outlined text-4xl text-primary" style="font-variation-settings:\'FILL\' 1">cloud_upload</span>\n';
            html += '                </div>\n';
            html += '                <p class="text-primary font-bold text-base">Drop files to upload</p>\n';
            html += '                <p class="text-primary/70 text-xs">Images, Videos, PDFs, Docs</p>\n';
            html += '            </div>\n';
            html += '            <div id="paneFooter" class="hidden mt-0 px-4 py-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-400 dark:text-slate-500 font-medium"></div>\n';
            html += '        </div>\n';
            html += '    </div>\n';

            html += '    <!-- Team Members (1/3 width) -->\n';
            html += '    <div class="space-y-4">\n';
            html += '        <div class="flex items-center justify-between px-1">\n';
            html += '            <h2 class="text-slate-900 dark:text-white text-base font-bold">Team Members</h2>\n';
            html += '        </div>\n';
            html += '        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">\n';

            if (members.length > 0) {
                html += '            <div class="divide-y divide-slate-100 dark:divide-slate-800">\n';
                members.forEach(m => {
                    html += '                <div class="px-5 py-3.5 flex items-center gap-3">\n';
                    html += '                    <div class="size-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">\n';
                    html += '                        ' + m.name.substring(0, 2).toUpperCase() + '\n';
                    html += '                    </div>\n';
                    html += '                    <div class="flex-1 min-w-0">\n';
                    html += '                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">' + m.name + '</p>\n';
                    html += '                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-primary/10 text-primary border border-primary/20 uppercase">' + m.project_role + '</span>\n';
                    html += '                    </div>\n';
                    html += '                </div>\n';
                });
                html += '            </div>\n';
            } else {
                html += '            <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">No team members</div>\n';
            }

            html += '        </div>\n';

            html += '        <!-- System Info Card (matching dashboard Quick Actions style) -->\n';
            html += '        <div class="bg-gradient-to-br from-primary/10 to-purple-500/10 border border-primary/20 rounded-xl p-5">\n';
            html += '            <div class="flex items-start gap-3">\n';
            html += '                <div class="p-2 bg-primary/20 rounded-lg">\n';
            html += '                    <span class="material-symbols-outlined text-primary text-[18px]">info</span>\n';
            html += '                </div>\n';
            html += '                <div>\n';
            html += '                    <h4 class="text-slate-900 dark:text-white font-bold text-sm">About This Project</h4>\n';
            html += '                    <p class="text-slate-600 dark:text-slate-400 text-xs mt-1.5 leading-relaxed">\n';
            html += '                        Files uploaded here are watermarked and tracked. Download the protected version to share securely.\n';
            html += '                    </p>\n';
            html += '                </div>\n';
            html += '            </div>\n';
            html += '        </div>\n';
            html += '    </div>\n';
            html += '</div>\n';

            content.innerHTML = html;

            async function folderDuplicate() {
                closeFolderMenu();
                try {
                    const fd = new FormData();
                    fd.append('action', 'duplicate-folder');
                    fd.append('folder_id', _menuFolderId);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        loadPane(_currentFolderId);
                    } else {
                        alert(data.error || 'Failed to duplicate');
                    }
                } catch {
                    alert('Network error');
                }
            }

            async function folderDelete() {
                closeFolderMenu();
                if (!confirm("Delete folder \"" + _menuFolderName + "\" and all its contents? This cannot be undone.")) return;
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete-folder');
                    fd.append('folder_id', _menuFolderId);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        loadPane(_currentFolderId);
                    } else {
                        alert(data.error || 'Failed to delete');
                    }
                } catch {
                    alert('Network error');
                }
            }

            // ─── File context menu ─────────────────────────────────
            let _fileMenuId = null;
            let _fileMenuName = null;

            function openFileMenu(event, fileId, fileName, isMine) {
                event.preventDefault();
                event.stopPropagation();
                _fileMenuId = fileId;
                _fileMenuName = fileName;

                const btnDelete = document.getElementById('cmFileDeleteBtn');
                const divDelete = document.getElementById('cmFileDivider');
                if (btnDelete && divDelete) {
                    if (isMine) {
                        btnDelete.style.display = 'flex';
                        divDelete.style.display = 'block';
                    } else {
                        btnDelete.style.display = 'none';
                        divDelete.style.display = 'none';
                    }
                }

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
                window.location.href = 'preview.php?id=' + fileId + '&project_id=' + selectedProjectId;
            }

            async function fileMenuDelete() {
                closeFileMenu();
                if (!confirm("Delete file \"" + _fileMenuName + "\"? This cannot be undone.")) return;
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete-file');
                    fd.append('file_id', _fileMenuId);
                    fd.append('project_id', selectedProjectId);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        loadPane(_currentFolderId);
                    } else {
                        alert(data.error || 'Failed to delete file');
                    }
                } catch (e) {
                    console.error(e);
                    alert('An error occurred');
                }
            }

            // Close menus on click outside
            document.addEventListener('click', (e) => {
                const fMenu = document.getElementById('folderContextMenu');
                if (fMenu && !fMenu.contains(e.target)) closeFolderMenu();
                const fileMenu = document.getElementById('fileContextMenu');
                if (fileMenu && !fileMenu.contains(e.target)) closeFileMenu();
            });

            // ─── Unified Pane Logic ─────────────────────────────────

            function renderBreadcrumb() {
                const bc = document.getElementById('paneBreadcrumb');
                if (!bc) return;
                if (_folderTrail.length === 0) {
                    bc.classList.add('hidden');
                    bc.classList.remove('flex');
                    bc.innerHTML = '';
                    return;
                }
                bc.classList.remove('hidden');
                bc.classList.add('flex');
                let html = '<span class="text-slate-300 dark:text-slate-600 mx-0.5">/</span>' +
                    '<button onclick="showFolderGrid()" class="hover:text-primary transition-colors flex-shrink-0">All Files</button>';
                _folderTrail.forEach((crumb, i) => {
                    const isLast = i === _folderTrail.length - 1;
                    html += '<span class="material-symbols-outlined text-[13px] flex-shrink-0 text-slate-300 dark:text-slate-600">chevron_right</span>';
                    if (isLast) {
                        html += '<span class="text-slate-800 dark:text-slate-200 font-semibold truncate max-w-[140px]" title="' + escapeHtml(crumb.name) + '">' + escapeHtml(crumb.name) + '</span>';
                    } else {
                        html += '<button onclick="navToTrailIndex(' + i + ')" class="hover:text-primary transition-colors truncate max-w-[100px]" title="' + escapeHtml(crumb.name) + '">' + escapeHtml(crumb.name) + '</button>';
                    }
                });
                bc.innerHTML = html;
            }

            function navToTrailIndex(index) {
                _folderTrail = _folderTrail.slice(0, index + 1);
                const target = _folderTrail[index];
                _currentFolderId = target.id;
                renderBreadcrumb();
                loadPane(target.id);
            }

            async function loadPane(folderId) {
                _currentFolderId = folderId;
                const pane = document.getElementById('filesPane');
                pane.innerHTML = '<div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">' +
                    '<span class="material-symbols-outlined text-3xl block mb-2 animate-pulse">folder_open</span>Loading...</div>';

                try {
                    if (folderId === null) {
                        const [fRes, fileRes] = await Promise.all([
                            fetch('../api/projects.php?action=get-folders&project_id=' + selectedProjectId),
                            fetch('../api/projects.php?action=files&project_id=' + selectedProjectId)
                        ]);
                        const [fData, fileData] = await Promise.all([fRes.json(), fileRes.json()]);
                        _paneFolders = fData.success ? (fData.data.folders || []) : [];
                        _paneFiles = fileData.success ? (fileData.data.files || []) : [];
                    } else {
                        const res = await fetch('../api/projects.php?action=get-folder-files&folder_id=' + folderId + '&project_id=' + selectedProjectId);
                        const data = await res.json();
                        if (!data.success) throw new Error(data.error);
                        _paneFolders = data.data.subfolders || [];
                        _paneFiles = data.data.files || [];
                    }
                    renderPane();
                } catch (e) {
                    pane.innerHTML = '<div class="p-8 text-center text-red-400 text-sm">Failed to load. Please try again.</div>';
                    console.error(e);
                }
            }

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

                if (footer) {
                    if (isEmpty || noMatch) {
                        footer.classList.add('hidden');
                    } else {
                        footer.classList.remove('hidden');
                        footer.textContent = totalItems + ' item' + (totalItems !== 1 ? 's' : '');
                    }
                }

                if (isEmpty) {
                    pane.innerHTML = '<div class="p-12 text-center">' +
                        '<div class="inline-flex size-16 rounded-2xl bg-slate-100 dark:bg-slate-800 items-center justify-center mb-4">' +
                        '<span class="material-symbols-outlined text-3xl text-slate-400">cloud_upload</span>' +
                        '</div>' +
                        '<p class="text-slate-500 dark:text-slate-400 font-semibold text-sm mb-1">' +
                        (_currentFolderId ? 'This folder is empty' : 'No files yet') +
                        '</p>' +
                        '<p class="text-slate-400 dark:text-slate-500 text-xs mb-4">Ask an admin to upload files</p>' +
                        '</div>';
                    return;
                }
                if (noMatch) {
                    pane.innerHTML = '<div class="p-10 text-center text-slate-400 dark:text-slate-500 text-sm">' +
                        '<span class="material-symbols-outlined text-2xl block mb-2">search_off</span>No files match this filter</div>';
                    return;
                }

                let html = '';

                // Folders Grid
                if (_paneFolders.length > 0) {
                    html += '<div class="' + (files.length > 0 ? 'p-4 border-b border-slate-100 dark:border-slate-800' : 'p-4') + '">';
                    html += '    <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Folders</p>';
                    html += '    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">';
                    _paneFolders.forEach(f => {
                        const count = f.file_count ?? 0;
                        const sfn = (f.name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                        html += '        <div class="relative group/fc">';
                        html += '            <button onclick="paneOpenFolder(' + f.id + ', \'' + sfn + '\')"';
                        html += '                class="w-full text-left bg-slate-50 dark:bg-slate-800/60 hover:bg-amber-50 dark:hover:bg-amber-500/10 border border-slate-200 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-500/40 rounded-xl p-3 transition-all">';
                        html += '                <div class="size-9 rounded-lg bg-amber-400/15 flex items-center justify-center mb-2">';
                        html += '                    <span class="material-symbols-outlined text-amber-500 text-[20px]" style="font-variation-settings:\'FILL\' 1">folder</span>';
                        html += '                </div>';
                        html += '                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate pr-6">' + escapeHtml(f.name) + '</p>';
                        html += '                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">' + count + ' file' + (count !== 1 ? 's' : '') + '</p>';
                        html += '            </button>';
                        html += '        </div>';
                    });
                    html += '    </div></div>';
                }

                // Files
                if (files.length > 0) {
                    if (_fileView === 'grid') {
                        if (_paneFolders.length > 0) {
                            html += '<div class="px-4 pt-4 pb-1"><p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Files</p></div>';
                        }
                        html += '<div class="p-4"><div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">';
                        files.forEach(file => {
                            const mt = file.file_type || file.mime_type || '';
                            const isImage = isImageType(mt),
                                isVideo = isVideoType(mt);
                            const icon = isImage ? 'image' : (isVideo ? 'movie' : 'description');
                            const iconBg = isImage ? 'bg-purple-500/10' : (isVideo ? 'bg-red-500/10' : 'bg-blue-500/10');
                            const iconClr = isImage ? 'text-purple-500' : (isVideo ? 'text-red-500' : 'text-blue-500');
                            const sfn = (file.original_name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                            const isMine = file.user_id == currentUserId;
                            const mbId = 'pm-' + file.id;
                            const uploader = file.uploader_name || '';
                            const avatarBg = avatarColor(uploader);
                            const avatarTxt = avatarInitials(uploader);
                            const thumbHtml = isImage ?
                                '<img src="../api/view.php?id=' + file.id + '&thumb=1" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.innerHTML=\'<span class=\\\'material-symbols-outlined text-[32px]\\\' >' + icon + '</span>\'">' :
                                '<span class="material-symbols-outlined text-[32px] ' + iconClr + '">' + icon + '</span>';
                            html += '<div class="group/gc relative flex flex-col bg-slate-900 border border-slate-700/60 rounded-xl overflow-hidden hover:border-primary/50 hover:shadow-lg transition-all cursor-pointer ' + (isMine ? 'ring-1 ring-primary/30' : '') + '"';
                            html += '    onclick="previewFile(' + file.id + ')"';
                            html += '    oncontextmenu="openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ');return false;"';
                            html += '    onmouseenter="document.getElementById(\'' + mbId + '\').style.opacity=\'1\'"';
                            html += '    onmouseleave="document.getElementById(\'' + mbId + '\').style.opacity=\'0\'">';
                            html += '    <div class="relative h-36 ' + (isImage ? 'bg-black' : iconBg + ' flex items-center justify-center') + ' overflow-hidden flex-shrink-0">';
                            html += '        ' + thumbHtml;
                            html += '        <div class="absolute inset-0 flex items-center justify-center bg-black/0 hover:bg-black/40 transition-all opacity-0 group-hover/gc:opacity-100 pointer-events-none">';
                            html += '            <span class="material-symbols-outlined text-white text-3xl drop-shadow">play_circle</span>';
                            html += '        </div>';
                            html += '    </div>';
                            html += '    <div class="p-3 flex flex-col gap-1.5 bg-slate-800/80">';
                            html += '        <p class="text-[13px] font-semibold text-white truncate leading-tight">' + escapeHtml(file.original_name) + '</p>';
                            html += '        <div class="flex items-center gap-2">';
                            html += '            <div class="size-5 rounded-full flex-shrink-0 flex items-center justify-center text-[9px] font-bold text-white" style="background:' + avatarBg + '">' + avatarTxt + '</div>';
                            html += '            <span class="text-[11px] text-slate-400 truncate">' + (isMine ? 'You' : (escapeHtml(uploader) || '\u2014')) + '</span>';
                            html += '            <span class="text-slate-600 text-[10px] ml-auto flex-shrink-0">' + fmtDate(file.upload_date).split(' at ')[0] + '</span>';
                            html += '        </div>';
                            html += '    </div>';
                            html += '    <button id="' + mbId + '" onclick="event.stopPropagation();openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ')" style="opacity:0;transition:opacity .15s"';
                            html += '        class="absolute top-1.5 right-1.5 p-1 rounded-md bg-black/50 text-white hover:bg-black/80" title="Options">';
                            html += '        <span class="material-symbols-outlined text-[15px]">more_horiz</span>';
                            html += '    </button>';
                            html += '</div>';
                        });
                        html += '</div></div>';
                    } else {
                        if (_paneFolders.length > 0) {
                            html += '<div class="px-4 pt-4 pb-2"><p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Files</p></div>';
                        }
                        html += '<div class="grid gap-0" style="grid-template-columns:minmax(0,1fr) 180px 140px">';
                        html += '    <div class="contents">';
                        html += '        <div class="px-4 py-2 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">Name</div>';
                        html += '        <div class="px-3 py-2 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">Date Uploaded</div>';
                        html += '        <div class="px-3 py-2 text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/30">Uploader</div>';
                        html += '    </div>';
                        files.forEach(file => {
                            const mt = file.file_type || file.mime_type || '';
                            const isImage = isImageType(mt),
                                isVideo = isVideoType(mt);
                            const icon = isImage ? 'image' : (isVideo ? 'movie' : 'description');
                            const iconBg = isImage ? 'bg-purple-500/10' : (isVideo ? 'bg-red-500/10' : 'bg-blue-500/10');
                            const iconClr = isImage ? 'text-purple-500' : (isVideo ? 'text-red-500' : 'text-blue-500');
                            const isMine = file.user_id == currentUserId;
                            const sfn = (file.original_name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                            const mbId = 'pm-' + file.id;
                            const uploader = file.uploader_name || '';
                            const avatarBg = avatarColor(uploader);
                            const avatarTxt = avatarInitials(uploader);
                            const thumbHtml = isImage ?
                                '<img src="../api/view.php?id=' + file.id + '&thumb=1" alt="" class="size-full object-cover rounded" loading="lazy" onerror="this.outerHTML=\'<span class=\\\'material-symbols-outlined text-[16px]\\\' >' + icon + '</span>\'">' :
                                '<span class="material-symbols-outlined text-[16px]">' + icon + '</span>';
                            const rowBg = 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors cursor-pointer ' + (isMine ? 'bg-primary/[0.03]' : '');
                            html += '    <div class="' + rowBg + ' border-b border-slate-100 dark:border-slate-800 last:border-0 contents">';
                            html += '        <div class="px-4 py-3 flex items-center gap-3 min-w-0 ' + rowBg + '"';
                            html += '            onclick="previewFile(' + file.id + ')"';
                            html += '            oncontextmenu="openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ');return false;"';
                            html += '            onmouseenter="[\'' + mbId + '_d\', \'' + mbId + '\'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.opacity=\'1\'})"';
                            html += '            onmouseleave="[\'' + mbId + '_d\', \'' + mbId + '\'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.opacity=\'0\'})">';
                            html += '            <div class="size-9 rounded-lg flex-shrink-0 ' + (isImage ? 'overflow-hidden' : iconBg + ' ' + iconClr + ' flex items-center justify-center') + '">';
                            html += '                ' + thumbHtml;
                            html += '            </div>';
                            html += '            <div class="min-w-0 flex-1">';
                            html += '                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">' + escapeHtml(file.original_name) + '</p>';
                            html += '                <p class="text-[11px] text-slate-400 mt-0.5">' + fmtSize(file.file_size) + '</p>';
                            html += '            </div>';
                            html += '            <div class="flex items-center gap-0.5 flex-shrink-0">';
                            html += '                <a id="' + mbId + '_d" href="../api/download.php?file_id=' + file.id + '" onclick="event.stopPropagation()" style="opacity:0;transition:opacity .15s"';
                            html += '                    class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-500 hover:bg-slate-100 dark:hover:bg-slate-800" title="Download">';
                            html += '                    <span class="material-symbols-outlined text-[17px]">download</span>';
                            html += '                </a>';
                            html += '                <button id="' + mbId + '" onclick="event.stopPropagation();openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ')" style="opacity:0;transition:opacity .15s"';
                            html += '                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800" title="Options">';
                            html += '                    <span class="material-symbols-outlined text-[17px]">more_vert</span>';
                            html += '                </button>';
                            html += '            </div>';
                            html += '        </div>';
                            html += '        <div class="px-3 py-3 text-xs text-slate-500 dark:text-slate-400 ' + rowBg + ' cursor-pointer"';
                            html += '            onclick="previewFile(' + file.id + ')"';
                            html += '            oncontextmenu="openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ');return false;">' + fmtDate(file.upload_date) + '</div>';
                            html += '        <div class="px-3 py-3 flex items-center gap-2 ' + rowBg + ' cursor-pointer"';
                            html += '            onclick="previewFile(' + file.id + ')"';
                            html += '            oncontextmenu="openFileMenu(event,' + file.id + ',\'' + sfn + '\',' + isMine + ');return false;">';
                            html += '            <div class="size-6 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold text-white shadow-sm" style="background:' + avatarBg + '">' + avatarTxt + '</div>';
                            html += '            <span class="text-xs text-slate-600 dark:text-slate-300 font-medium truncate">' + (isMine ? 'You' : (escapeHtml(uploader) || '\u2014')) + '</span>';
                            html += '        </div>';
                            html += '    </div>';
                        });
                        html += '</div>';
                    }
                }
                pane.innerHTML = html;
            }

            function paneOpenFolder(folderId, folderName) {
                _folderTrail.push({
                    id: folderId,
                    name: folderName
                });
                renderBreadcrumb();
                loadPane(folderId);
            }

            function showFolderGrid() {
                _folderTrail = [];
                _currentFolderId = null;
                renderBreadcrumb();
                loadPane(null);
            }

            function setFileFilter(f) {
                _fileFilter = f;
                ['all', 'image', 'video', 'doc'].forEach(k => {
                    const el = document.getElementById(`
            fChip - $ {
                k
            }
            `);
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

            function escapeHtml(unsafe) {
                if (!unsafe) return '';
                return unsafe
                    .toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            // ─── Rename ───────────────────────────────────────────
            let _menuProjectId = null;

            function folderRename() {
                closeFolderMenu();
                document.getElementById('renameFolderInput').value = _menuFolderName;
                document.getElementById('renameFolderError').textContent = '';
                document.getElementById('renameFolderModal').classList.remove('hidden');
                document.getElementById('renameFolderInput').focus();
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
                    fd.append('folder_id', _menuFolderId);
                    fd.append('project_id', _menuProjectId);
                    fd.append('name', name);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        closeRenameFolderModal();
                        if (_currentFolderId === _menuFolderId) _currentFolderName = name;
                        reloadCurrentView();
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

            function reloadCurrentView() {
                if (_currentFolderId) {
                    loadPane(_currentFolderId);
                } else if (selectedProjectId) {
                    loadProjectContent(selectedProjectId);
                }
            }

            // ─── Delete ───────────────────────────────────────────
            async function folderDelete() {
                closeFolderMenu();
                if (!confirm('Delete "' + _menuFolderName + '"? \n\nSubfolders are removed. Files stay in the project.')) return;
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete-folder');
                    fd.append('folder_id', _menuFolderId);
                    fd.append('project_id', _menuProjectId);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (_currentFolderId === _menuFolderId) {
                            _currentFolderId = null;
                            _currentFolderProjectId = null;
                            _currentFolderName = null;
                            loadProjectContent(_menuProjectId);
                        } else {
                            reloadCurrentView();
                        }
                    } else {
                        alert(data.error || 'Failed to delete folder.');
                    }
                } catch {
                    alert('Network error.');
                }
            }

            // ─── Duplicate ────────────────────────────────────────
            async function folderDuplicate() {
                closeFolderMenu();
                try {
                    const fd = new FormData();
                    fd.append('action', 'duplicate-folder');
                    fd.append('folder_id', _menuFolderId);
                    fd.append('project_id', _menuProjectId);
                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.success) {
                        reloadCurrentView();
                    } else {
                        alert(data.error || 'Failed to duplicate folder.');
                    }
                } catch {
                    alert('Network error.');
                }
            }

            // ─── Open a folder (drill-down) ───────────────────────
            async function openFolder(folderId, projectId, folderName, trail = null) {
                // trail=null means append this folder to existing trail; trail=[] means start fresh
                if (trail !== null) {
                    _folderTrail = trail;
                } else {
                    _folderTrail.push({
                        id: folderId,
                        name: folderName
                    });
                }
                const content = document.getElementById('mainContent');
                content.innerHTML = '<div class="text-center py-16 text-slate-400 dark:text-slate-500 text-sm">Loading folder...</div>';

                try {
                    const res = await fetch('../api/projects.php?action=get-folder-files&folder_id=' + folderId + '&project_id=' + projectId);
                    const data = await res.json();

                    if (!data.success) {
                        content.innerHTML = '<div class="text-center text-red-500 text-sm py-8">' + (data.error || 'Error loading folder') + '</div>';
                        return;
                    }

                    const subfolders = data.data?.subfolders || [];
                    displayFolderContent(folderId, projectId, folderName, data.data.files, subfolders);
                } catch (e) {
                    console.error(e);
                    content.innerHTML = `<div class="text-center text-red-500 text-sm py-8">Network error loading folder.</div>`;
                }
            }

            function displayFolderContent(folderId, projectId, folderName, files, subfolders = []) {
                const content = document.getElementById('mainContent');
                // Store context for action buttons
                _currentFolderId = folderId;
                _currentFolderProjectId = projectId;
                _currentFolderName = folderName;

                const hasContent = files.length > 0 || subfolders.length > 0;

                // Build full breadcrumb path from _folderTrail
                let _bcHtml = '';
                _folderTrail.forEach((crumb, i) => {
                    const isLast = i === _folderTrail.length - 1;
                    _bcHtml += '<span class="text-slate-300 dark:text-slate-600">/</span>';
                    if (isLast) {
                        _bcHtml += '<div class="flex items-center gap-1.5">';
                        _bcHtml += '<span class="material-symbols-outlined text-amber-500 text-[18px]" style="font-variation-settings:\'FILL\' 1,\'wght\' 400,\'GRAD\' 0,\'opsz\' 24">folder</span>';
                        _bcHtml += '<span class="text-sm font-semibold text-slate-900 dark:text-white">' + escapeHtml(crumb.name) + '</span>';
                        _bcHtml += '</div>';
                    } else {
                        _bcHtml += '<button onclick="navToFolderTrailIndex(' + i + ', ' + projectId + ')" class="text-sm text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium">' + escapeHtml(crumb.name) + '</button>';
                    }
                });

                let html = '';
                html += '<!-- Breadcrumb -->\n';
                html += '<div class="flex items-center flex-wrap gap-1.5 mb-6">\n';
                html += '<button onclick="_folderTrail=[]; loadProjectContent(' + projectId + ')" class="flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium flex-shrink-0">\n';
                html += '<span class="material-symbols-outlined text-[18px]">arrow_back</span>\n';
                html += 'Back to Project\n';
                html += '</button>\n';
                html += _bcHtml + '\n';
                html += '</div>\n';


                html += '<!-- Folder header + actions -->\n';
                html += '<div class="flex items-center justify-between mb-4 px-1">\n';
                html += '<h2 class="text-slate-900 dark:text-white text-base font-bold">\n';
                html += 'Contents\n';
                html += '<span class="ml-2 text-xs font-semibold text-slate-400 dark:text-slate-500">' + (files.length + subfolders.length) + '</span>\n';
                html += '</h2>\n';
                html += '<div class="flex items-center gap-2">\n';
                html += '<button onclick="openCreateFolderModal(' + projectId + ', ' + folderId + ')" class="flex items-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-lg transition-all shadow-sm border border-slate-200 dark:border-slate-700">\n';
                html += '<span class="material-symbols-outlined text-[17px]">create_new_folder</span>\n';
                html += 'New Folder\n';
                html += '</button>\n';
                html += '<button onclick="openFolderUploadModal()" class="flex items-center gap-2 px-3.5 py-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-all shadow-sm">\n';
                html += '<span class="material-symbols-outlined text-[17px]">cloud_upload</span>\n';
                html += 'Upload File\n';
                html += '</button>\n';
                html += '</div>\n';
                html += '</div>\n';

                // Subfolders grid
                if (subfolders.length > 0) {
                    html += '<div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">\n';
                    html += subfolders.map(sf => renderFolderCard(sf, projectId)).join('');
                    html += '</div>\n';
                }

                // File list or empty state
                if (hasContent) {
                    if (files.length > 0) {
                        html += '<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">\n';
                        html += '<div class="divide-y divide-slate-100 dark:divide-slate-800">' + files.map(f => renderFileRow(f)).join('') + '</div>\n';
                        html += '</div>\n';
                    }
                } else {
                    html += '<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-14 text-center shadow-sm">\n';
                    html += '<div class="size-14 mx-auto mb-4 bg-amber-400/10 rounded-full flex items-center justify-center">\n';
                    html += '<span class="material-symbols-outlined text-amber-400 text-3xl" style="font-variation-settings:\'FILL\' 1,\'wght\' 400,\'GRAD\' 0,\'opsz\' 24">folder_open</span>\n';
                    html += '</div>\n';
                    html += '<p class="text-slate-500 dark:text-slate-400 text-sm font-medium">This folder is empty</p>\n';
                    html += '<p class="text-slate-400 dark:text-slate-500 text-xs mt-1 mb-5">Upload files or create a subfolder</p>\n';
                    html += '<button onclick="openFolderUploadModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold rounded-lg transition-all shadow-sm">\n';
                    html += '<span class="material-symbols-outlined text-[17px]">cloud_upload</span>\n';
                    html += 'Upload File\n';
                    html += '</button>\n';
                    html += '</div>\n';
                }

                content.innerHTML = html;
            }

            // ─── Folder-scoped upload state ──────────────────────────
            let _currentFolderProjectId = null;
            let _currentFolderName = null;

            function navToFolderTrailIndex(index, projectId) {
                const target = _folderTrail[index];
                _folderTrail = _folderTrail.slice(0, index + 1);
                _currentFolderId = target.id;
                openFolder(target.id, projectId, target.name, [..._folderTrail]);
            }

            function openFolderUploadModal() {
                document.getElementById('folderUploadError').textContent = '';
                document.getElementById('folderUploadStatus').textContent = '';
                document.getElementById('folderUploadProgress').classList.add('hidden');
                document.getElementById('folderUploadProgressBar').style.width = '0%';
                if (_currentFolderName) {
                    document.getElementById('folderUploadFolderName').textContent = `Into: ${_currentFolderName}`;
                }
                document.getElementById('folderUploadModal').classList.remove('hidden');
            }

            function closeFolderUploadModal() {
                document.getElementById('folderUploadModal').classList.add('hidden');
            }

            // Drag & drop wiring (runs once)
            let _folderDropWired = false;

            function wireFolderDrop() {
                if (_folderDropWired) return;
                _folderDropWired = true;
                const dz = document.getElementById('folderDropZone');
                dz.addEventListener('dragover', e => {
                    e.preventDefault();
                    dz.classList.add('border-primary', 'bg-primary/5');
                });
                dz.addEventListener('dragleave', () => dz.classList.remove('border-primary', 'bg-primary/5'));
                dz.addEventListener('drop', e => {
                    e.preventDefault();
                    dz.classList.remove('border-primary', 'bg-primary/5');
                    handleFolderUpload(e.dataTransfer.files);
                });
                document.getElementById('folderFileInput').addEventListener('change', e => handleFolderUpload(e.target.files));
            }

            async function handleFolderUpload(files) {
                if (!files || files.length === 0) return;
                if (!_currentFolderId || !_currentFolderProjectId) return;

                const progressEl = document.getElementById('folderUploadProgress');
                const bar = document.getElementById('folderUploadProgressBar');
                const status = document.getElementById('folderUploadStatus');
                const errEl = document.getElementById('folderUploadError');

                progressEl.classList.remove('hidden');
                errEl.textContent = '';
                let successCount = 0;

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    status.textContent = `Uploading ${file.name} (${i + 1}/${files.length})...`;
                    bar.style.width = `${(i / files.length) * 100}%`;

                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('project_id', _currentFolderProjectId);
                    fd.append('folder_id', _currentFolderId);

                    try {
                        const res = await fetch('../api/upload.php', {
                            method: 'POST',
                            body: fd
                        });
                        const data = await res.json();
                        if (data.success) successCount++;
                        else errEl.textContent = data.error || 'Upload failed';
                    } catch (e) {
                        errEl.textContent = 'Network error';
                    }

                    bar.style.width = `${((i + 1) / files.length) * 100}%`;
                }

                status.textContent = `Done! ${successCount}/${files.length} file(s) uploaded.`;
                setTimeout(() => {
                    closeFolderUploadModal();
                    openFolder(_currentFolderId, _currentFolderProjectId, _currentFolderName, [..._folderTrail]);
                }, 800);
            }

            document.addEventListener('DOMContentLoaded', wireFolderDrop);

            // ─── Create Folder Modal ───────────────────────────────
            let _createFolderProjectId = null;
            let _createFolderParentId = null; // null = top-level folder

            function openCreateFolderModal(projectId, parentFolderId = null) {
                _createFolderProjectId = projectId;
                _createFolderParentId = parentFolderId || null;
                document.getElementById('folderNameInput').value = '';
                document.getElementById('folderError').textContent = '';
                // Update modal subtitle to reflect context
                const subtitle = document.getElementById('createFolderSubtitle');
                if (subtitle) {
                    subtitle.textContent = _createFolderParentId ?
                        `Subfolder of: ${_currentFolderName || 'current folder'}` :
                        'Organise project files into a folder';
                }
                document.getElementById('createFolderModal').classList.remove('hidden');
                document.getElementById('folderNameInput').focus();
            }

            function closeCreateFolderModal() {
                document.getElementById('createFolderModal').classList.add('hidden');
            }

            async function submitCreateFolder() {
                const name = document.getElementById('folderNameInput').value.trim();
                const errEl = document.getElementById('folderError');
                const btn = document.getElementById('createFolderBtn');

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
                    fd.append('project_id', _createFolderProjectId);
                    fd.append('name', name);
                    if (_createFolderParentId) fd.append('parent_id', _createFolderParentId);

                    const res = await fetch('../api/projects.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();

                    if (data.success) {
                        closeCreateFolderModal();
                        // If we were inside a folder, reload that folder view; otherwise refresh project
                        if (_createFolderParentId) {
                            openFolder(_createFolderParentId, _createFolderProjectId, _currentFolderName);
                        } else {
                            loadProjectContent(_createFolderProjectId);
                        }
                    } else {
                        errEl.textContent = data.error || 'Failed to create folder.';
                    }
                } catch (e) {
                    errEl.textContent = 'Network error, please try again.';
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Create Folder';
                }
            }

            // Allow Enter key to submit
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('folderNameInput')?.addEventListener('keydown', e => {
                    if (e.key === 'Enter') submitCreateFolder();
                });
            });
    </script>
    <script>
        window.currentUser = {
            name: "<?php echo htmlspecialchars($user['name']); ?>",
            email: "<?php echo htmlspecialchars($user['email']); ?>"
        };
    </script>
