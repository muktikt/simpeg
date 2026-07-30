import './bootstrap';

// Script sidebar (buka/tutup grup menu)
document.addEventListener('DOMContentLoaded', () => {
    window.toggleGroup = function (btn) {
        const group = btn.parentElement;
        group.classList.toggle('open');
    };
});
