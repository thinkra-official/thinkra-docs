(function () {
    document.documentElement.classList.remove('docs-dark');
    try {
        localStorage.removeItem('thinkra-docs-theme');
    } catch (e) {
        /* ignore */
    }

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
