'use strict';

/* S09/S10: tidak autosave. Semua perubahan hanya tersimpan melalui POST manual. */
(() => {
    const forms = Array.from(document.querySelectorAll('[data-kegiatan-form]'));
    let mengirim = false;
    const berubah = form => {
        const textarea = form.querySelector('textarea[name="jawaban"]');
        return !!textarea && textarea.value !== textarea.defaultValue;
    };
    forms.forEach(form => {
        const textarea = form.querySelector('textarea[name="jawaban"]');
        const status = form.querySelector('[data-kegiatan-indikator]');
        if (!textarea || !status) return;
        const update = () => {
            status.textContent = berubah(form)
                ? 'Perubahan belum disimpan.'
                : 'Tidak ada perubahan yang belum disimpan.';
        };
        textarea.addEventListener('input', update);
        form.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('click', () => {
                form.dataset.aksi = button.value;
            });
        });
        form.addEventListener('submit', event => {
            if (form.dataset.mengirim === 'ya') {
                event.preventDefault();
                return;
            }
            const aksi = event.submitter?.value || form.dataset.aksi || 'simpan';
            if (aksi === 'kirim'
                && !window.confirm('Kirim jawaban final? Setelah dikirim tidak dapat diubah.')) {
                event.preventDefault();
                return;
            }
            mengirim = true;
            form.dataset.mengirim = 'ya';
            form.setAttribute('aria-busy', 'true');
            status.textContent = 'Mengirim jawaban ke server…';
            // Jangan men-disable submitter sekarang: HTML belum membentuk
            // payload dan name="aksi" akan hilang dari POST jika disabled.
        });
    });
    window.addEventListener('beforeunload', event => {
        if (!mengirim && forms.some(berubah)) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
})();
