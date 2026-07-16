import './bootstrap';

// Script sidebar (buka/tutup grup menu)
document.addEventListener('DOMContentLoaded', () => {
    window.toggleGroup = function (btn) {
        const group = btn.parentElement;
        const wasOpen = group.classList.contains('open');
        document.querySelectorAll('.nav-group.open').forEach((g) => g.classList.remove('open'));
        if (!wasOpen) {
            group.classList.add('open');
        }
    };
});
