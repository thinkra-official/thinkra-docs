(function () {
    const storageKey = 'thinkra-docs-theme';

    function applyTheme(theme) {
        document.documentElement.classList.toggle('docs-dark', theme === 'dark');
    }

    const saved = localStorage.getItem(storageKey);
    if (saved === 'dark' || saved === 'light') {
        applyTheme(saved);
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        applyTheme('dark');
    }

    document.querySelectorAll('[data-docs-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const isDark = document.documentElement.classList.toggle('docs-dark');
            const theme = isDark ? 'dark' : 'light';
            localStorage.setItem(storageKey, theme);
        });
    });

    const progressBar = document.getElementById('docs-progress-bar');
    if (progressBar) {
        function updateProgress() {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const pct = docHeight > 0 ? Math.min(100, (scrollTop / docHeight) * 100) : 0;
            progressBar.style.width = pct + '%';
        }
        window.addEventListener('scroll', updateProgress, { passive: true });
        updateProgress();
    }

    const menuBtn = document.getElementById('docs-menu-toggle');
    const sidebarPanel = document.getElementById('docs-sidebar-panel');
    if (menuBtn && sidebarPanel) {
        menuBtn.addEventListener('click', function () {
            sidebarPanel.classList.toggle('is-open');
        });
    }
})();
