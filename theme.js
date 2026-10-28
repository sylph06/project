(function () {
    var key = 'kkTheme';
    var stored = null;
    try { stored = localStorage.getItem(key); } catch (e) {}

    var theme = stored === 'dark' || stored === 'light'
        ? stored
        : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    document.documentElement.setAttribute('data-bs-theme', theme);
    document.documentElement.classList.toggle('dark-mode', theme === 'dark');

    function updateButtons() {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            button.innerHTML = theme === 'dark'
                ? '<i class="bi bi-sun me-2"></i>Light mode'
                : '<i class="bi bi-moon-stars me-2"></i>Dark mode';
        });
    }

    window.kkToggleTheme = function () {
        theme = document.documentElement.classList.contains('dark-mode') ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.documentElement.classList.toggle('dark-mode', theme === 'dark');
        try { localStorage.setItem(key, theme); } catch (e) {}
        updateButtons();
    };

    document.addEventListener('DOMContentLoaded', updateButtons);
})();
