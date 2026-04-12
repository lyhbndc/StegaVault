<?php

/**
 * StegaVault - Theme Color CSS Variable Injector
 * Include this in <head>
    <link rel="icon" type="image/png" href="../icon.png"> of every employee page that uses tailwind primary color.
 * Requires: $db, $userId to be set.
 */

$_tcStmt = $db->prepare("SELECT theme_color FROM users WHERE id = ?");
$_tcStmt->bind_param('i', $userId);
$_tcStmt->execute();
$_tcRow = $_tcStmt->get_result()->fetch_assoc();
$themeColor = $_tcRow['theme_color'] ?? '#667eea';
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) $themeColor = '#667eea';
?>
<style>
    /* ── Accent color CSS variable (overrideable per-employee) ── */
    :root {
        --sv-primary: <?php echo $themeColor; ?>;
    }

    .bg-primary {
        background-color: var(--sv-primary) !important;
    }

    .text-primary {
        color: var(--sv-primary) !important;
    }

    .border-primary {
        border-color: var(--sv-primary) !important;
    }

    .hover\:text-primary:hover {
        color: var(--sv-primary) !important;
    }

    .bg-primary\/10 {
        background-color: color-mix(in srgb, var(--sv-primary) 10%, transparent) !important;
    }

    .bg-primary\/20 {
        background-color: color-mix(in srgb, var(--sv-primary) 20%, transparent) !important;
    }

    .ring-primary\/50 {
        --tw-ring-color: color-mix(in srgb, var(--sv-primary) 50%, transparent) !important;
    }

    .focus\:ring-primary\/50:focus {
        --tw-ring-color: color-mix(in srgb, var(--sv-primary) 50%, transparent) !important;
    }

    .focus\:border-primary:focus {
        border-color: var(--sv-primary) !important;
    }

    .hover\:bg-primary\/90:hover {
        background-color: color-mix(in srgb, var(--sv-primary) 90%, #000) !important;
    }

    .from-primary {
        --tw-gradient-from: var(--sv-primary) !important;
    }

    .border-primary\/20 {
        border-color: color-mix(in srgb, var(--sv-primary) 20%, transparent) !important;
    }
</style>
<script>
    // Apply from localStorage immediately for zero-FOUC experience
    (function() {
        try {
            const uid = '<?php echo (int)($userId ?? $user["id"] ?? 0); ?>';
            const c = localStorage.getItem('sv_accent_' + uid);
            if (c && /^#[0-9a-fA-F]{6}$/.test(c)) {
                document.documentElement.style.setProperty('--sv-primary', c);
            }
        } catch (e) {}
    })();
</script>