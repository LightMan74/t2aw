// js/theme.js
// Applique le thème sauvegardé le plus tôt possible
(function () {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
})();

function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    updateToggleButton(next);
}

function updateToggleButton(theme) {
    const btn = document.getElementById('btn-theme-toggle');
    if (btn) {
        btn.innerHTML = theme === 'dark' ? '☀️ Clair' : '🌙 Sombre';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    updateToggleButton(current);

    const btn = document.getElementById('btn-theme-toggle');
    if (btn) {
        btn.addEventListener('click', toggleTheme);
    }
});