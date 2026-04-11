/**
 * StegaVault - Security Shield
 * File: js/security-shield.js
 * 
 * Provides client-side security mitigation:
 * 1. Dynamic Visual Watermarking (Overlay)
 * 2. Privacy Blur on Window Focus Loss
 * 3. Anti-Copy/Context Menu
 * 4. Print Screen Detection (Attempt)
 */

(function () {
    console.log("StegaVault Security Shield Initialized");

    const SecurityShield = {
        config: {
            text: "CONFIDENTIAL",
            opacity: 0.04, // Very faint
            density: 6, // Grid size
        },

        init: function () {
            // this.createOverlay(); // Visual watermark removed per request
            this.createPrivacyFilter();
            this.attachEvents();
            this.disableRightClick();
        },

        // Create the blur filter for when window is inactive
        createPrivacyFilter: function () {
            const filter = document.createElement('div');
            filter.id = 'privacy-filter';
            Object.assign(filter.style, {
                position: 'fixed',
                top: '0',
                left: '0',
                width: '100vw',
                height: '100vh',
                zIndex: '10000',
                backgroundColor: 'rgba(0, 0, 0, 0.85)',
                backdropFilter: 'blur(15px)',
                display: 'none',
                alignItems: 'center',
                justifyContent: 'center',
                color: 'white',
                flexDirection: 'column',
                gap: '20px'
            });

            filter.innerHTML = `
                <div style="font-size: 64px; color: #667eea;">🛡️</div>
                <h2 style="font-family: sans-serif; font-size: 24px; font-weight: bold;">Security</h2>
                <p style="font-family: sans-serif; color: #aaa;">Application hidden while inactive to prevent unauthorized viewing.</p>
                <div style="margin-top: 10px; font-size: 12px; color: #555;">Focus window to resume</div>
            `;

            document.body.appendChild(filter);
        },

        attachEvents: function () {
            // Blur on focus loss (Tab switching, clicking outside)
            window.addEventListener('blur', () => this.enableProtection());

            // Blur on mouse leaving the window (e.g. going to taskbar/snipping tool)
            document.documentElement.addEventListener('mouseleave', () => this.enableProtection());

            // Blur on visibility change (switching tabs/minimizing)
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this.enableProtection();
            });

            // Restore on focus/mouseenter
            window.addEventListener('focus', () => this.disableProtection());
            document.documentElement.addEventListener('mouseenter', () => this.disableProtection());

            // Detect PrintScreen
            document.addEventListener('keyup', (e) => {
                if (e.key === 'PrintScreen') {
                    this.enableProtection();
                    this.flashWarning();
                    // Keep protected for a moment to ruin the shot
                    setTimeout(() => this.disableProtection(), 2000);
                }
            });

            // Prevent printing
            const style = document.createElement('style');
            style.innerHTML = `@media print { body { display: none !important; } }`;
            document.head.appendChild(style);
        },

        enableProtection: function () {
            const filter = document.getElementById('privacy-filter');
            if (filter) filter.style.display = 'flex';
            document.title = "🔒 Secured";
        },

        disableProtection: function () {
            const filter = document.getElementById('privacy-filter');
            if (filter) filter.style.display = 'none';
            document.title = "StegaVault - Dashboard";
        },

        disableRightClick: function () {
            document.addEventListener('contextmenu', event => event.preventDefault());

            // Disable specific key combos (Ctrl+P, Ctrl+S, Ctrl+Shift+I)
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey && ['p', 's', 'u'].includes(e.key.toLowerCase())) ||
                    (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'i')) {
                    e.preventDefault();
                    this.flashWarning();
                }
            });
        },

        flashWarning: function () {
            const warning = document.createElement('div');
            Object.assign(warning.style, {
                position: 'fixed',
                top: '20px',
                left: '50%',
                transform: 'translateX(-50%)',
                backgroundColor: '#ef4444',
                color: 'white',
                padding: '12px 24px',
                borderRadius: '8px',
                zIndex: '10001',
                fontWeight: 'bold',
                fontFamily: 'sans-serif',
                boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1)'
            });
            warning.textContent = "⚠️ Security Alert: Action Prohibited";
            document.body.appendChild(warning);
            setTimeout(() => warning.remove(), 2000);
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => SecurityShield.init());
    } else {
        SecurityShield.init();
    }
})();
