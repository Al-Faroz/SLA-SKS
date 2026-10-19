/* Navigasi panel responsif. Tidak memproses hak akses atau menyimpan data. */
(() => {
    'use strict';

    const shell = document.querySelector('[data-shell="panel"]');
    if (!shell) return;

    const toggle = shell.querySelector('[data-menu-toggle]');
    const backdrop = shell.querySelector('[data-menu-close]');
    const side = shell.querySelector('.a-side');
    if (!toggle || !backdrop || !side) return;

    const mobile = window.matchMedia('(max-width: 767px)');

    function setOpen(open, restoreFocus = false) {
        const shouldOpen = open && mobile.matches;
        shell.classList.toggle('is-menu-open', shouldOpen);
        toggle.setAttribute('aria-expanded', String(shouldOpen));
        backdrop.hidden = !shouldOpen;
        if (restoreFocus && !shouldOpen) toggle.focus();
        if (shouldOpen) {
            const firstLink = side.querySelector('a');
            if (firstLink) firstLink.focus();
        }
    }

    toggle.addEventListener('click', () => {
        setOpen(!shell.classList.contains('is-menu-open'));
    });
    backdrop.addEventListener('click', () => setOpen(false, true));
    side.addEventListener('click', (event) => {
        if (event.target.closest('a')) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && shell.classList.contains('is-menu-open')) {
            setOpen(false, true);
        }
    });
    mobile.addEventListener('change', () => setOpen(false));
})();
