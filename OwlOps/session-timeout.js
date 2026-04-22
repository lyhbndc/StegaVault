(function () {
    const TIMEOUT_MS  = 30 * 60 * 1000; // 30 min
    const WARNING_MS  =  5 * 60 * 1000; // warn 5 min before
    const LOGIN_URL   = 'login.php?reason=timeout';
    const LOGOUT_API  = '../StegaVault/api/super_admin_auth.php?action=logout';

    let warnTimer, expireTimer;

    function resetTimers() {
        clearTimeout(warnTimer);
        clearTimeout(expireTimer);
        warnTimer   = setTimeout(showWarning, TIMEOUT_MS - WARNING_MS);
        expireTimer = setTimeout(doLogout,    TIMEOUT_MS);
    }

    function showWarning() {
        const banner = document.getElementById('sa-timeout-banner');
        if (banner) banner.classList.remove('hidden');
    }

    function doLogout() {
        fetch(LOGOUT_API, { method: 'POST' }).finally(() => {
            window.location.href = LOGIN_URL;
        });
    }

    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => {
        document.addEventListener(evt, function () {
            const banner = document.getElementById('sa-timeout-banner');
            if (banner) banner.classList.add('hidden');
            resetTimers();
        }, { passive: true });
    });

    // Inject warning banner
    document.addEventListener('DOMContentLoaded', function () {
        const banner = document.createElement('div');
        banner.id = 'sa-timeout-banner';
        banner.className = 'hidden fixed bottom-6 right-6 z-[9999] bg-orange-500/10 border border-orange-500/30 text-orange-300 rounded-2xl px-5 py-4 text-sm font-medium flex items-center gap-3 shadow-xl backdrop-blur-sm';
        banner.innerHTML =
            '<span class="material-symbols-outlined text-orange-400 text-xl">timer</span>' +
            '<span>Session expiring in <strong>5 minutes</strong> due to inactivity.</span>' +
            '<button onclick="document.getElementById(\'sa-timeout-banner\').classList.add(\'hidden\')" class="ml-2 text-orange-400 hover:text-white transition-colors">' +
            '<span class="material-symbols-outlined text-base">close</span></button>';
        document.body.appendChild(banner);
        resetTimers();
    });
})();
