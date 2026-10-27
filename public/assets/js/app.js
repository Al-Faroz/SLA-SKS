/* Navigasi panel HP: drawer fokus-terjaga, Escape, backdrop & scroll lock.
   Tidak memodifikasi rute/peran/menu yang disiapkan server. */
(() => {
    'use strict';

    const shell = document.querySelector('[data-shell="panel"]');
    if (!shell) return;

    const toggle = shell.querySelector('[data-menu-toggle]');
    const backdrop = shell.querySelector('[data-menu-close]');
    const side = shell.querySelector('.a-side');
    const main = shell.querySelector('.a-main');
    if (!toggle || !backdrop || !side || !main) return;

    const mobile = window.matchMedia('(max-width: 767px)');
    let locked = false;
    let previousOverflow = '';

    const focusableMenu = () => [...side.querySelectorAll(
        'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )].filter((element) => !element.hidden && element.getClientRects().length > 0);

    function setOpen(open, restoreFocus = false) {
        const shouldOpen = Boolean(open && mobile.matches);
        const wasOpen = shell.classList.contains('is-menu-open');

        if (shouldOpen && !locked) {
            previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            locked = true;
        }
        if (!shouldOpen && locked) {
            document.body.style.overflow = previousOverflow;
            locked = false;
        }

        shell.classList.toggle('is-menu-open', shouldOpen);
        toggle.setAttribute('aria-expanded', String(shouldOpen));
        toggle.setAttribute('aria-label', shouldOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        backdrop.hidden = !shouldOpen;

        // Inert mencegah Tab dan pembaca layar masuk ke konten di balik drawer.
        // Pastikan dilepas sebelum fokus dikembalikan ke toggle.
        main.inert = shouldOpen;
        if (shouldOpen && !wasOpen) {
            const first = focusableMenu()[0];
            if (first) first.focus();
        } else if (!shouldOpen && restoreFocus && mobile.matches) {
            toggle.focus();
        }
    }

    toggle.addEventListener('click', () => setOpen(!shell.classList.contains('is-menu-open')));
    backdrop.addEventListener('click', () => setOpen(false, true));
    side.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) setOpen(false, false);
    });

    document.addEventListener('keydown', (event) => {
        if (!shell.classList.contains('is-menu-open')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false, true);
            return;
        }
        if (event.key !== 'Tab') return;

        const elements = focusableMenu();
        if (!elements.length) {
            event.preventDefault();
            return;
        }

        const first = elements[0];
        const last = elements[elements.length - 1];
        if (event.shiftKey && (document.activeElement === first || !side.contains(document.activeElement))) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || !side.contains(document.activeElement))) {
            event.preventDefault();
            first.focus();
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


/* Reset akun K2B: sandi hanya disalin setelah Admin menekan tombol. */
(() => {
    'use strict';
    const tombol = document.querySelector('[data-salin-sandi]');
    const input = document.getElementById('sandi-baru-sekali');
    const status = document.querySelector('[data-status-salin]');
    if (!tombol || !input || !status) return;
    tombol.addEventListener('click', async () => {
        if (!navigator.clipboard || !navigator.clipboard.writeText) {
            input.focus();
            input.select();
            status.textContent = 'Salin teks yang telah dipilih secara manual.';
            return;
        }
        try {
            await navigator.clipboard.writeText(input.value);
            status.textContent = 'Sandi telah disalin. Sampaikan melalui saluran privat.';
        } catch {
            input.focus();
            input.select();
            status.textContent = 'Penyalinan otomatis tidak diizinkan. Salin teks terpilih secara manual.';
        }
    });
})();

/* Form Materi bersama Tambah/Sunting. Server selalu memvalidasi ulang. */
(() => {
    'use strict';
    document.querySelectorAll('[data-materi-form]').forEach((form) => {
        const jenis = form.querySelector('[data-materi-jenis]');
        const youtube = form.querySelector('[data-materi-youtube]');
        const berkas = form.querySelector('[data-materi-berkas]');
        const url = youtube?.querySelector('input');
        const file = berkas?.querySelector('input');
        const info = form.querySelector('[data-materi-file-info]');
        const fileError = form.querySelector('[data-materi-file-error]');
        const submit = form.querySelector('[data-materi-submit]');
        const status = form.querySelector('[data-materi-status]');
        if (!jenis || !youtube || !berkas || !url || !file || !submit) return;

        const sudahAdaBerkas = form.dataset.materiSudahUpload === '1';
        const awal = form.dataset.materiJenisAwal || '';
        const batasServer = Number(file.dataset.materiBatasServer || 0);
        const labelSubmit = submit.textContent;
        let sedangMengirim = false;

        function pesanJenis() {
            switch (jenis.value) {
                case 'pdf':
                    return 'PDF maksimal 15 MB dan 40 halaman. File PDF harus dapat dibaca.';
                case 'gambar':
                    return 'JPG, PNG atau WebP maksimal 10 MB.';
                case 'audio':
                    return 'MP3 atau M4A maksimal 10 MB.';
                default:
                    return '';
            }
        }

        function batasJenis() {
            return jenis.value === 'pdf' ? 15 * 1024 * 1024 : 10 * 1024 * 1024;
        }

        function periksaFile() {
            file.setCustomValidity('');
            if (fileError) fileError.textContent = '';
            if (!file.files || !file.files.length || jenis.value === 'youtube') {
                return true;
            }

            const terpilih = file.files[0];
            let kesalahan = '';
            const batasAplikasi = batasJenis();
            if (terpilih.size > batasAplikasi) {
                kesalahan = 'File melebihi batas aplikasi ' + (jenis.value === 'pdf' ? '15 MB.' : '10 MB.');
            } else if (batasServer > 0 && terpilih.size > batasServer) {
                kesalahan = 'File melebihi batas PHP server saat ini. Periksa upload_max_filesize dan post_max_size yang ditampilkan di bawah kolom file.';
            }
            if (kesalahan) {
                file.setCustomValidity(kesalahan);
                file.setAttribute('aria-invalid', 'true');
                if (fileError) fileError.textContent = kesalahan;
                return false;
            }
            file.removeAttribute('aria-invalid');
            return true;
        }

        function ubahJenis() {
            const pilihYoutube = jenis.value === 'youtube';
            const pilihPdf = jenis.value === 'pdf';
            youtube.hidden = !pilihYoutube;
            berkas.hidden = pilihYoutube;
            url.disabled = !pilihYoutube;
            url.required = pilihYoutube;
            file.disabled = pilihYoutube;
            file.required = !pilihYoutube && !sudahAdaBerkas;
            file.accept = pilihPdf ? '.pdf' : jenis.value === 'gambar'
                ? '.jpg,.jpeg,.png,.webp' : '.mp3,.m4a';
            if (info) info.textContent = pesanJenis();
            if (awal && sudahAdaBerkas && jenis.value !== awal) {
                jenis.value = awal;
                ubahJenis();
                return;
            }
            periksaFile();
        }

        form.querySelectorAll('[aria-invalid="true"]').forEach((field) => {
            field.addEventListener('input', () => {
                if (field !== file) field.removeAttribute('aria-invalid');
            });
        });
        jenis.addEventListener('change', () => {
            file.value = '';
            ubahJenis();
        });
        file.addEventListener('change', () => {
            if (!periksaFile()) file.reportValidity();
        });
        form.addEventListener('submit', (event) => {
            if (sedangMengirim) {
                event.preventDefault();
                return;
            }
            if (!periksaFile()) {
                event.preventDefault();
                file.reportValidity();
                return;
            }
            sedangMengirim = true;
            submit.disabled = true;
            submit.textContent = jenis.value === 'youtube'
                ? 'Menyimpan Materi...' : 'Mengunggah dan menyimpan...';
            if (status) {
                status.textContent = 'Permintaan sedang diproses. File dan data Materi baru akan tersimpan setelah validasi server berhasil.';
            }
        });
        window.addEventListener('pageshow', () => {
            if (sedangMengirim) {
                sedangMengirim = false;
                submit.disabled = false;
                submit.textContent = labelSubmit;
            }
        });
        ubahJenis();
    });
})();

/* Konfirmasi eksplisit sebelum menghapus Materi draf; server tetap validasi. */
(() => {
    'use strict';
    document.querySelectorAll('[data-materi-hapus-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const judul = form.querySelector('[data-materi-hapus]')?.dataset.judul || 'Materi ini';
            if (!window.confirm('Hapus Materi "' + judul + '" dari TP draf? Tindakan ini tidak bisa dibatalkan. Berkas historis tidak dihapus.')) {
                event.preventDefault();
            }
        });
    });
})();

/* G03 — Modal BAB/TP berbasis dialog native, fokus dan Escape bawaan browser. */
(() => {
    'use strict';
    const openButtons = document.querySelectorAll('[data-atp-dialog-open]');
    if (!openButtons.length) return;

    for (const trigger of openButtons) {
        trigger.addEventListener('click', () => {
            const dialog = document.getElementById(trigger.dataset.atpDialogOpen || '');
            if (!(dialog instanceof HTMLDialogElement) || typeof dialog.showModal !== 'function') return;
            if (dialog.open) return;
            dialog.showModal();
            const first = dialog.querySelector('[name="nama_bab"],[name="nama_tp"]');
            first?.focus();
        });
    }
    document.querySelectorAll('.a-modal--atp').forEach((dialog) => {
        dialog.querySelectorAll('[data-atp-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close(); // Klik bidang backdrop.
        });
        dialog.querySelectorAll('[data-atp-dialog-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.sedangKirim === '1') {
                    event.preventDefault();
                    return;
                }
                if (!form.reportValidity()) {
                    event.preventDefault();
                    return;
                }
                form.dataset.sedangKirim = '1';
                const submit = form.querySelector('[type="submit"]');
                if (submit) {
                    submit.disabled = true;
                    submit.textContent = 'Menyimpan…';
                }
            });
        });
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-atp-dialog-form][data-sedang-kirim="1"]').forEach((form) => {
            delete form.dataset.sedangKirim;
            const submit = form.querySelector('[type="submit"]');
            if (submit) {
                submit.disabled = false;
                submit.textContent = submit.closest('dialog')?.id.startsWith('dialog-bab-') ? 'Simpan BAB' : 'Simpan TP Draf';
            }
        });
    });
})();
