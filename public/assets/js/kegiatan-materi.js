'use strict';

/* S09/S10: draf manual, final permanen. Anti-paste adalah mitigasi browser,
 * bukan bukti keaslian tulisan; validasi server tetap sumber kebenaran. */
(() => {
    const forms = Array.from(document.querySelectorAll('[data-kegiatan-form]'));
    let mengirim = false;

    const berubah = form => {
        const textarea = form.querySelector('textarea[name="jawaban"]');
        return !!textarea && textarea.value !== textarea.defaultValue;
    };
    const panjang = isi => Array.from(isi.replace(/\r\n?/g, '\n').trim()).length;

    forms.forEach(form => {
        const textarea = form.querySelector('textarea[name="jawaban"]');
        const status = form.querySelector('[data-kegiatan-indikator]');
        const validasi = form.querySelector('[data-kegiatan-validasi]');
        if (!textarea || !status || !validasi) return;

        const minimal = Number(form.dataset.minimal);
        const batas = Number.isInteger(minimal) && minimal > 0 ? minimal : null;
        const update = () => {
            status.textContent = berubah(form)
                ? 'Perubahan belum disimpan.'
                : 'Tidak ada perubahan yang belum disimpan.';
            validasi.textContent = panjang(textarea.value) + ' karakter ditulis.'
                + (batas !== null ? ' Minimal ' + batas + ' untuk Kirim final.' : '');
        };
        const larangClipboard = event => {
            event.preventDefault();
            validasi.textContent = 'Salin/tempel dan tarik-lepas tidak diizinkan'
                + ' pada jawaban ini. Tuliskan jawaban sendiri.';
        };

        // Membatasi cara tempel umum tanpa menghalangi keyboard biasa,
        // keyboard virtual, input method editor/IME maupun pembaca layar.
        ['paste', 'copy', 'cut', 'drop'].forEach(type => {
            textarea.addEventListener(type, larangClipboard);
        });
        textarea.addEventListener('beforeinput', event => {
            if (['insertFromPaste', 'insertFromPasteAsQuotation',
                'insertFromDrop'].includes(event.inputType)) {
                larangClipboard(event);
            }
        });
        textarea.addEventListener('keydown', event => {
            if ((event.ctrlKey || event.metaKey)
                && ['v', 'c', 'x'].includes((event.key || '').toLowerCase())) {
                larangClipboard(event);
            }
        });
        textarea.addEventListener('dragover', event => event.preventDefault());
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
            if (aksi === 'kirim') {
                const jumlah = panjang(textarea.value);
                if (batas === null || jumlah < batas || jumlah > 12000) {
                    event.preventDefault();
                    delete form.dataset.aksi;
                    validasi.textContent = batas === null
                        ? 'Konfigurasi batas karakter tidak tersedia. Muat ulang halaman.'
                        : 'Belum dapat Kirim final: ' + jumlah
                            + ' karakter. Minimal ' + batas
                            + ', maksimal 12.000 karakter.';
                    textarea.focus();
                    return;
                }
                if (!window.confirm('Kirim jawaban final? Setelah dikirim tidak dapat diubah.')) {
                    event.preventDefault();
                    delete form.dataset.aksi;
                    return;
                }
            }
            mengirim = true;
            form.dataset.mengirim = 'ya';
            form.setAttribute('aria-busy', 'true');
            status.textContent = 'Mengirim jawaban ke server…';
            // Jangan disable submitter: HTML harus mengirim name="aksi" utuh.
        });
        update();
    });

    window.addEventListener('beforeunload', event => {
        if (!mengirim && forms.some(berubah)) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
})();
