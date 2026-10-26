'use strict';

/* G11: konfirmasi final awal, tidak menonaktifkan submitter sebelum form POST. */
(() => {
    const form = document.querySelector('[data-nilai-guru]');
    if (!form) return;
    const status = form.querySelector('[data-nilai-status]');
    form.addEventListener('submit', event => {
        if (form.dataset.mengirim === 'ya') {
            event.preventDefault();
            return;
        }
        if (!window.confirm('Simpan nilai pertama? Perubahan selanjutnya hanya lewat koreksi akademik.')) {
            event.preventDefault();
            return;
        }
        form.dataset.mengirim = 'ya';
        form.setAttribute('aria-busy', 'true');
        if (status) status.textContent = 'Mengirim nilai ke server…';
    });
})();
