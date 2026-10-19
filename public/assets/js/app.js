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


/* Form identitas: tombol Simpan aktif hanya setelah isi berubah. Tanpa JS tetap bisa submit. */
(() => {
    'use strict';
    const form = document.querySelector('[data-dirty-form]');
    if (!form) return;
    const button = form.querySelector('[data-dirty-submit]');
    const status = form.querySelector('[data-dirty-status]');
    if (!button || !status) return;
    const fields = [...form.querySelectorAll('input[name], textarea[name], select[name]')]
        .filter(field => field.name !== 'csrf_test_name');
    const awal = fields.map(field => field.value);
    const perbarui = () => {
        const berubah = fields.some((field, index) => field.value !== awal[index]);
        button.disabled = !berubah;
        status.textContent = berubah
            ? 'Perubahan belum disimpan. Klik Simpan identitas untuk menyimpan.'
            : 'Belum ada perubahan untuk disimpan.';
    };
    form.addEventListener('input', perbarui);
    form.addEventListener('change', perbarui);
    perbarui();
})();
