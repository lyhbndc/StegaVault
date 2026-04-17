<?php

/**
 * StegaVault - Profile Settings Modal
 * Include this file at the bottom of every admin page (before </body>).
 * Requires: $user['name'], $user['email'], $user['role'] to be set.
 */
?>
<!-- ═══════════════════════════════════════
     PROFILE SETTINGS SLIDE-OUT
═══════════════════════════════════════ -->
<div id="settingsBackdrop" class="hidden fixed inset-0 z-[200]">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSettings()"></div>
    <!-- Panel slides in from the left (over the sidebar) -->
    <div id="settingsPanel"
        class="absolute inset-y-0 left-0 w-80 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col transform -translate-x-full transition-transform duration-300 ease-in-out">

        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="bg-primary rounded-full size-10 flex items-center justify-center text-white font-bold text-sm" id="settingsAvatar">
                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                </div>
                <div>
                    <p class="text-slate-900 dark:text-white text-sm font-bold" id="settingsNameDisplay"><?php echo htmlspecialchars($user['name']); ?></p>
                    <p class="text-slate-500 dark:text-slate-400 text-xs"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            <button onclick="closeSettings()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">

            <!-- Toast -->
            <div id="settingsToast" class="hidden text-xs font-semibold px-3 py-2 rounded-lg border text-center"></div>

            <!-- Update Name -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-[16px]">badge</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Display Name</h3>
                </div>
                <input id="settingsNameInput" type="text" value="<?php echo htmlspecialchars($user['name']); ?>"
                    class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" />
                <button onclick="saveDisplayName()"
                    class="mt-2 w-full py-2 px-4 bg-primary hover:bg-primary/90 text-white text-xs font-bold rounded-xl transition-all">
                    Update Name
                </button>
            </div>

            <hr class="border-slate-200 dark:border-slate-800" />

            <?php if (isset($user['role']) && $user['role'] !== 'admin' && $user['role'] !== 'super_admin'): ?>
                <!-- Dashboard Color -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-symbols-outlined text-primary text-[16px]">palette</span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Dashboard Color</h3>
                    </div>
                    <div class="grid grid-cols-4 gap-2 mb-2" id="colorSwatchGrid">
                        <!-- Swatches inserted by JS -->
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <input id="customColorInput" type="color" value="#667eea"
                            class="h-8 w-10 rounded cursor-pointer border border-slate-200 dark:border-slate-700 bg-transparent p-0.5"
                            oninput="_svColor.preview(this.value)" />
                        <span class="text-xs text-slate-500 dark:text-slate-400">Custom color</span>
                    </div>
                    <button onclick="_svColor.save()"
                        class="mt-3 w-full py-2 px-4 bg-primary hover:bg-primary/90 text-white text-xs font-bold rounded-xl transition-all">
                        Save Color
                    </button>
                </div>

                <hr class="border-slate-200 dark:border-slate-800" />
            <?php endif; ?>

            <!-- Change Password -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-[16px]">lock</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Change Password</h3>
                </div>
                <div class="space-y-2">
                    <div class="relative">
                        <input id="settingsCurPass" type="password" placeholder="Current password"
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 pr-10 text-slate-900 dark:text-white text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" />
                        <button type="button" onclick="toggleSettingsPassword('settingsCurPass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">visibility_off</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input id="settingsNewPass" type="password" placeholder="New password (min 12 chars)"
                            oninput="checkPasswordPolicy(this.value)"
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 pr-10 text-slate-900 dark:text-white text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" />
                        <button type="button" onclick="toggleSettingsPassword('settingsNewPass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">visibility_off</span>
                        </button>
                    </div>
                    <div id="passwordPolicyChecklist" class="hidden mt-1.5 grid grid-cols-2 gap-x-3 gap-y-0.5 text-[11px]">
                        <span id="pc_len"  class="flex items-center gap-1 text-slate-400"><span class="material-symbols-outlined text-[13px]">cancel</span>12+ characters</span>
                        <span id="pc_upper" class="flex items-center gap-1 text-slate-400"><span class="material-symbols-outlined text-[13px]">cancel</span>Uppercase (A-Z)</span>
                        <span id="pc_lower" class="flex items-center gap-1 text-slate-400"><span class="material-symbols-outlined text-[13px]">cancel</span>Lowercase (a-z)</span>
                        <span id="pc_num"  class="flex items-center gap-1 text-slate-400"><span class="material-symbols-outlined text-[13px]">cancel</span>Number (0-9)</span>
                        <span id="pc_spec" class="flex items-center gap-1 text-slate-400"><span class="material-symbols-outlined text-[13px]">cancel</span>Special character</span>
                    </div>
                    <div class="relative">
                        <input id="settingsConfPass" type="password" placeholder="Confirm new password"
                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 pr-10 text-slate-900 dark:text-white text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" />
                        <button type="button" onclick="toggleSettingsPassword('settingsConfPass', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">visibility_off</span>
                        </button>
                    </div>
                    <button onclick="changePassword()"
                        class="w-full py-2 px-4 bg-slate-800 dark:bg-slate-700 hover:bg-primary text-white text-xs font-bold rounded-xl transition-all">
                        Change Password
                    </button>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-800" />

            <!-- Two Factor Auth - Link to Dedicated Page -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-[16px]">phonelink_lock</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Multi-Factor Auth</h3>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-4">Manage your two-factor authentication settings on a dedicated page.</p>
                    <a href="mfa-settings.php" class="w-full inline-flex items-center justify-center gap-2 py-2 px-4 bg-primary hover:bg-primary/90 text-white text-xs font-bold rounded-lg transition-all">
                        <span class="material-symbols-outlined text-sm">settings</span>
                        <span>Manage MFA</span>
                    </a>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-800" />

            <!-- Role / Info -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-[16px]">info</span>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Account Info</h3>
                </div>
                <div class="space-y-2 text-xs text-slate-500 dark:text-slate-400">
                    <div class="flex justify-between">
                        <span>Email</span>
                        <span class="text-slate-900 dark:text-white font-semibold"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Role</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-primary/10 text-primary border border-primary/20 capitalize"><?php echo htmlspecialchars($user['role']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex-shrink-0">
            <a href="logout.php" class="flex items-center justify-center gap-2 w-full px-3 py-2.5 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors text-sm font-semibold">
                <span class="material-symbols-outlined text-[18px]">logout</span>
                Sign Out
            </a>
        </div>
    </div>
</div>

<script>
    /* ── Accent Color Manager ──────────────────────────────────── */
    (function() {
        const PALETTE = [{
                hex: '#667eea',
                label: 'Indigo'
            },
            {
                hex: '#7c3aed',
                label: 'Violet'
            },
            {
                hex: '#ec4899',
                label: 'Pink'
            },
            {
                hex: '#ef4444',
                label: 'Red'
            },
            {
                hex: '#f97316',
                label: 'Orange'
            },
            {
                hex: '#10b981',
                label: 'Emerald'
            },
            {
                hex: '#06b6d4',
                label: 'Cyan'
            },
            {
                hex: '#64748b',
                label: 'Slate'
            },
        ];

        function applyAccentColor(hex) {
            document.documentElement.style.setProperty('--sv-primary', hex);
            // Update the custom color input
            const input = document.getElementById('customColorInput');
            if (input) input.value = hex;
            // Highlight active swatch
            document.querySelectorAll('.sv-swatch').forEach(el => {
                el.classList.toggle('ring-2', el.dataset.color === hex);
                el.classList.toggle('ring-offset-2', el.dataset.color === hex);
                el.classList.toggle('ring-white', el.dataset.color === hex);
            });
        }

        let _pendingColor = null;

        function previewAccentColor(hex) {
            if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return;
            _pendingColor = hex;
            applyAccentColor(hex);
        }

        async function saveThemeColor() {
            const hex = _pendingColor ||
                (document.getElementById('customColorInput') && document.getElementById('customColorInput').value) ||
                '#667eea';
            if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return;
            applyAccentColor(hex);
            try {
                const res = await fetch('../api/settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_theme_color',
                        color: hex
                    })
                });
                const data = await res.json();
                if (data.success) {
                    showSettingsToast('Dashboard color updated!');
                    try {
                        const currentUid = '<?php echo (int)($user["id"] ?? 0); ?>';
                        localStorage.setItem('sv_accent_' + currentUid, hex);
                    } catch (e) {}
                } else {
                    showSettingsToast(data.error || 'Failed to save color', false);
                }
            } catch {
                showSettingsToast('Network error', false);
            }
        }

        function buildSwatches() {
            const grid = document.getElementById('colorSwatchGrid');
            if (!grid) return;
            const current = getComputedStyle(document.documentElement)
                .getPropertyValue('--sv-primary').trim() || '#667eea';
            grid.innerHTML = PALETTE.map(p => `
                <button
                    class="sv-swatch size-8 rounded-lg border border-black/10 transition-all duration-150 hover:scale-110 ${p.hex === current ? 'ring-2 ring-offset-2 ring-white' : ''}"
                    data-color="${p.hex}"
                    title="${p.label}"
                    style="background-color:${p.hex};"
                    onclick="_svColor.preview('${p.hex}')"
                ></button>
            `).join('');
        }

        // Expose globally so PHP pages can call applyAccentColor on load
        window._svColor = {
            apply: applyAccentColor,
            preview: previewAccentColor,
            save: saveThemeColor,
            buildSwatches
        };

        // Apply from localStorage immediately (before server data arrives) for perceived speed
        try {
            const currentUid = '<?php echo (int)($user["id"] ?? 0); ?>';
            const cached = localStorage.getItem('sv_accent_' + currentUid);
            if (cached && /^#[0-9a-fA-F]{6}$/.test(cached)) applyAccentColor(cached);
        } catch (e) {}

        // Build swatches when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', buildSwatches);
        } else {
            buildSwatches();
        }
    })();
</script>

<script>
    /* ── Dark / Light Mode Manager ──────────────────────────────── */
    (function() {
        const STORAGE_KEY = 'sv_theme';

        function applyTheme(dark) {
            const html = document.documentElement;
            if (dark) {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
            // Sync toggle button icon (may not yet exist on early call)
            const icon = document.getElementById('themeToggleIcon');
            if (icon) icon.textContent = dark ? 'light_mode' : 'dark_mode';
        }

        function isDark() {
            return document.documentElement.classList.contains('dark');
        }

        function toggle() {
            const next = !isDark();
            try {
                localStorage.setItem(STORAGE_KEY, next ? 'dark' : 'light');
            } catch (e) {}
            applyTheme(next);
        }

        // Expose globally so the button onclick works
        window._svTheme = {
            toggle,
            applyTheme,
            isDark
        };

        // Load saved preference (fall back to dark which is the app default)
        let saved = 'dark';
        try {
            saved = localStorage.getItem(STORAGE_KEY) || 'dark';
        } catch (e) {}
        applyTheme(saved === 'dark');

        // Sync icon once DOM is ready (it may already be ready here)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => applyTheme(isDark()));
        } else {
            applyTheme(isDark());
        }

        // Cross-tab sync
        window.addEventListener('storage', (e) => {
            if (e.key === STORAGE_KEY && e.newValue) applyTheme(e.newValue === 'dark');
        });
    })();
</script>

<script>
    function openSettings() {
        // Reset password fields and visibility on open
        document.getElementById('settingsCurPass').value = '';
        document.getElementById('settingsNewPass').value = '';
        document.getElementById('settingsConfPass').value = '';
        
        ['settingsCurPass', 'settingsNewPass', 'settingsConfPass'].forEach(id => {
            const el = document.getElementById(id);
            el.type = 'password';
            const btn = el.nextElementSibling;
            if (btn && btn.querySelector('span')) {
                btn.querySelector('span').textContent = 'visibility_off';
            }
        });

        document.getElementById('settingsBackdrop').classList.remove('hidden');
        setTimeout(() => document.getElementById('settingsPanel').classList.replace('-translate-x-full', 'translate-x-0'), 10);
    }

    function closeSettings() {
        document.getElementById('settingsPanel').classList.replace('translate-x-0', '-translate-x-full');
        setTimeout(() => document.getElementById('settingsBackdrop').classList.add('hidden'), 300);
    }

    function showSettingsToast(msg, ok = true) {
        const t = document.getElementById('settingsToast');
        t.textContent = msg;
        t.className = 'text-xs font-semibold px-3 py-2 rounded-lg border text-center ' +
            (ok ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400' :
                'bg-red-500/10 border-red-500/20 text-red-500');
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 4000);
    }

    async function saveDisplayName() {
        const name = document.getElementById('settingsNameInput').value.trim();
        if (!name) return showSettingsToast('Name cannot be empty', false);
        try {
            const res = await fetch('../api/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update_name',
                    name
                })
            });
            const data = await res.json();
            if (data.success) {
                showSettingsToast(data.message);
                // Update visible name in sidebar + avatar
                document.getElementById('settingsNameDisplay').textContent = data.name;
                document.getElementById('settingsAvatar').textContent = data.name.substring(0, 2).toUpperCase();
                // Update the sidebar profile text if it exists on this page
                const sidebarName = document.getElementById('sidebarProfileName');
                if (sidebarName) sidebarName.textContent = data.name;
                const sidebarAvatar = document.getElementById('sidebarProfileAvatar');
                if (sidebarAvatar) sidebarAvatar.textContent = data.name.substring(0, 2).toUpperCase();
            } else {
                showSettingsToast(data.error || 'Failed to update name', false);
            }
        } catch {
            showSettingsToast('Network error', false);
        }
    }

    function checkPasswordPolicy(val) {
        const checklist = document.getElementById('passwordPolicyChecklist');
        checklist.classList.toggle('hidden', val.length === 0);
        const rules = {
            pc_len:   val.length >= 12,
            pc_upper: /[A-Z]/.test(val),
            pc_lower: /[a-z]/.test(val),
            pc_num:   /[0-9]/.test(val),
            pc_spec:  /[\W_]/.test(val),
        };
        for (const [id, pass] of Object.entries(rules)) {
            const el = document.getElementById(id);
            const icon = el.querySelector('span');
            if (pass) {
                el.classList.replace('text-slate-400', 'text-emerald-500');
                icon.textContent = 'check_circle';
            } else {
                el.classList.replace('text-emerald-500', 'text-slate-400');
                icon.textContent = 'cancel';
            }
        }
    }

    async function changePassword() {
        const cur = document.getElementById('settingsCurPass').value;
        const nw = document.getElementById('settingsNewPass').value;
        const conf = document.getElementById('settingsConfPass').value;

        if (nw.length < 12 || !/[A-Z]/.test(nw) || !/[a-z]/.test(nw) || !/[0-9]/.test(nw) || !/[\W_]/.test(nw)) {
            showSettingsToast('Password does not meet the policy requirements', false);
            return;
        }
        try {
            const res = await fetch('../api/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'change_password',
                    current_password: cur,
                    new_password: nw,
                    confirm_password: conf
                })
            });
            const data = await res.json();
            if (data.success) {
                showSettingsToast(data.message);
                document.getElementById('settingsCurPass').value = '';
                document.getElementById('settingsNewPass').value = '';
                document.getElementById('settingsConfPass').value = '';
            } else {
                showSettingsToast(data.error || 'Failed to change password', false);
            }
        } catch {
            showSettingsToast('Network error', false);
        }
    }

    function toggleSettingsPassword(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('span');
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility_off';
        }
    }

    (function initIdleAutoLogout() {
        const IDLE_LIMIT_MS = 15 * 60 * 1000;
        let idleTimer = null;
        let lastResetAt = 0;

        const forceLogout = () => {
            window.location.href = 'logout.php?timeout=1';
        };

        const resetIdleTimer = () => {
            const now = Date.now();
            if (now - lastResetAt < 300) return;
            lastResetAt = now;

            if (idleTimer) clearTimeout(idleTimer);
            idleTimer = setTimeout(forceLogout, IDLE_LIMIT_MS);
        };

        ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach((eventName) => {
            window.addEventListener(eventName, resetIdleTimer, {
                passive: true
            });
        });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) resetIdleTimer();
        });

        resetIdleTimer();
    })();
</script>