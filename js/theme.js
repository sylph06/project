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
        document.querySelectorAll('[data-theme-set]').forEach(function (button) {
            var selected = button.getAttribute('data-theme-set') === theme;
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            button.classList.toggle('is-active', selected);
        });
    }

    function setTheme(nextTheme) {
        if (nextTheme !== 'dark' && nextTheme !== 'light') return;
        theme = nextTheme;
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.documentElement.classList.toggle('dark-mode', theme === 'dark');
        try { localStorage.setItem(key, theme); } catch (e) {}
        updateButtons();
    }

    window.kkToggleTheme = function () {
        setTheme(theme === 'dark' ? 'light' : 'dark');
    };

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-theme-set]');
        if (button) setTheme(button.getAttribute('data-theme-set'));
    });

    document.addEventListener('DOMContentLoaded', updateButtons);
})();
