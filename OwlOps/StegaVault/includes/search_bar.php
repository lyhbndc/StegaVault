<?php

/**
 * Global search bar include
 * Usage: <?php include '../includes/search_bar.php'; ?>
 * Place inside the <header> element.
 * $pageTitle must be set before including this file.
 */
?>
<!-- Global Search Bar -->
<div class="flex-1 max-w-xl relative" id="searchWrapper">
    <div class="relative flex items-center">
        <span class="material-symbols-outlined absolute left-3 text-slate-400 text-[20px] pointer-events-none">search</span>
        <input id="globalSearch" type="text" placeholder="Search projects, files, folders…"
            autocomplete="off"
            class="w-full pl-10 pr-8 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all">
        <button id="searchClear" onclick="_svSearch.clear()" class="hidden absolute right-3 p-0.5 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
    <!-- Results dropdown -->
    <div id="searchResults"
        class="hidden absolute top-full mt-2 left-0 right-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl overflow-hidden z-[999] max-h-[420px] overflow-y-auto">
        <div id="searchResultsInner" class="py-1"></div>
    </div>
</div>

<!-- Dark / Light Mode Toggle -->
<button id="themeToggleBtn"
    onclick="window._svTheme && window._svTheme.toggle()"
    title="Toggle dark / light mode"
    class="flex-shrink-0 size-9 flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-primary transition-all">
    <span id="themeToggleIcon" class="material-symbols-outlined text-[20px]">dark_mode</span>
</button>

<script>
    (function() {
        // Detect which portal is active and set base paths accordingly
        const _path = window.location.pathname;
        const _isAdmin = _path.includes('/admin/');
        const _isEmployee = _path.includes('/employee/');
        const _isCollaborator = _path.includes('/collaborator/');
        const _base = (_isAdmin || _isEmployee || _isCollaborator) ? '../' : './';

        // Navigation link builders per context
        function projectHref(id) {
            if (_isEmployee) return `${_base}employee/workspace.php?project=${id}`;
            if (_isCollaborator) return `${_base}collaborator/workspace.php?project=${id}`;
            return `${_base}admin/projects.php?id=${id}`;
        }

        function fileHref(id, projectId) {
            if (_isEmployee || _isCollaborator) return `${_base}api/view.php?id=${id}`;
            return `${_base}admin/preview.php?id=${id}&project_id=${projectId||''}`;
        }

        const input = document.getElementById('globalSearch');
        const results = document.getElementById('searchResults');
        const inner = document.getElementById('searchResultsInner');
        const clear = document.getElementById('searchClear');
        let timer;

        function esc(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function group(icon, label, color) {
            return `<div class="flex items-center gap-2 px-4 pt-3 pb-1">
            <span class="material-symbols-outlined text-[13px]" style="color:${color}">${icon}</span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">${label}</span>
        </div>`;
        }

        function item(href, icon, name, sub, color) {
            return `<a href="${href}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group/sr">
            <div class="size-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:${color}1a">
                <span class="material-symbols-outlined text-[15px]" style="color:${color}">${icon}</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">${name}</p>
                <p class="text-[11px] text-slate-400 truncate">${sub}</p>
            </div>
            <span class="material-symbols-outlined text-[14px] text-slate-300 opacity-0 group-hover/sr:opacity-100 flex-shrink-0">arrow_forward</span>
        </a>`;
        }

        function render(data, q) {
            const {
                projects = [], folders = [], files = []
            } = data;
            const total = projects.length + folders.length + files.length;
            if (!total) {
                inner.innerHTML = `<div class="px-4 py-6 text-center text-sm text-slate-400">
                <span class="material-symbols-outlined text-2xl block mb-1">search_off</span>
                No results for "<strong>${esc(q)}</strong>"
            </div>`;
                return;
            }
            let h = '';
            if (projects.length) {
                h += group('folder_managed', 'Projects', '#667eea');
                projects.forEach(p => h += item(
                    projectHref(p.id), 'folder', esc(p.name),
                    `${p.file_count ?? 0} files`, '#667eea'
                ));
            }
            if (folders.length) {
                h += group('folder_open', 'Folders', '#f59e0b');
                folders.forEach(f => h += item(
                    projectHref(f.project_id), 'folder_open', esc(f.name),
                    `in ${esc(f.project_name)}`, '#f59e0b'
                ));
            }
            if (files.length) {
                h += group('description', 'Files', '#6366f1');
                files.forEach(f => {
                    const ext = (f.original_name || '').split('.').pop().toLowerCase();
                    const isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
                    const isVid = ['mp4', 'webm', 'mov', 'ogg'].includes(ext);
                    const icon = isImg ? 'image' : (isVid ? 'movie' : 'description');
                    const clr = isImg ? '#a855f7' : (isVid ? '#ef4444' : '#3b82f6');
                    h += item(
                        fileHref(f.id, f.project_id),
                        icon, esc(f.original_name),
                        `${esc(f.project_name||'')}${f.folder_name ? ' / '+esc(f.folder_name) : ''}`, clr
                    );
                });
            }
            inner.innerHTML = h;
        }

        async function search(q) {
            inner.innerHTML = '<div class="px-4 py-3 text-sm text-slate-400">Searching…</div>';
            results.classList.remove('hidden');
            try {
                const r = await fetch(`${_base}api/search.php?q=${encodeURIComponent(q)}`);
                render(await r.json(), q);
            } catch {
                inner.innerHTML = '<div class="px-4 py-3 text-sm text-red-400">Search failed.</div>';
            }
        }

        input.addEventListener('input', () => {
            const q = input.value.trim();
            clear.classList.toggle('hidden', !q);
            clearTimeout(timer);
            if (!q) {
                results.classList.add('hidden');
                return;
            }
            timer = setTimeout(() => search(q), 220);
        });
        input.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                results.classList.add('hidden');
                input.blur();
            }
        });
        input.addEventListener('focus', () => {
            if (input.value.trim()) results.classList.remove('hidden');
        });
        document.addEventListener('click', e => {
            if (!document.getElementById('searchWrapper').contains(e.target))
                results.classList.add('hidden');
        });

        window._svSearch = {
            clear() {
                input.value = '';
                clear.classList.add('hidden');
                results.classList.add('hidden');
            }
        };
    })();
</script>