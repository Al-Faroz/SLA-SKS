'use strict';

/**
 * S08C: pembaca PDF/gambar siswa. Hanya mengubah PRESENTASI media, bukan
 * penghitungan waktu, nilai, atau gate penyelesaian Materi.
 */
(() => {
    document.querySelectorAll('[data-pembaca-fullscreen]').forEach(root => {
        const $ = selector => root.querySelector(selector);
        const toggle = $('[data-pembaca-toggle]');
        const feedback = $('[data-pembaca-pesan]');
        const gambar = $('[data-gambar-materi]');
        const bisaFullscreen = () => document.fullscreenEnabled === true
            && typeof root.requestFullscreen === 'function';
        const aktifFullscreen = () => document.fullscreenElement === root;

        function pesan(message) {
            if (feedback) feedback.textContent = message;
        }
        function perbaruiFullscreen() {
            if (!toggle) return;
            const masuk = aktifFullscreen();
            toggle.textContent = masuk ? 'Keluar layar penuh' : 'Layar penuh';
            toggle.setAttribute('aria-pressed', String(masuk));
            toggle.setAttribute('aria-label', masuk
                ? 'Keluar dari mode layar penuh' : 'Aktifkan mode layar penuh');
        }
        if (toggle) {
            if (!bisaFullscreen()) {
                toggle.disabled = true;
                pesan('Layar penuh tidak didukung browser/perangkat ini. Gunakan browser lain.');
            }
            toggle.addEventListener('click', async () => {
                try {
                    if (aktifFullscreen()) {
                        await document.exitFullscreen();
                    } else if (bisaFullscreen()) {
                        await root.requestFullscreen();
                    } else {
                        pesan('Fitur layar penuh tidak tersedia di browser ini.');
                        return;
                    }
                    perbaruiFullscreen();
                    pesan(aktifFullscreen() ? 'Mode layar penuh aktif. Tekan Esc atau tombol keluar.'
                        : 'Kembali ke tampilan biasa.');
                } catch (_) {
                    pesan('Permintaan layar penuh ditolak browser. Periksa izin perangkat.');
                }
            });
            document.addEventListener('fullscreenchange', perbaruiFullscreen);
            document.addEventListener('fullscreenerror', () => {
                if (document.fullscreenElement !== root) {
                    pesan('Browser tidak dapat membuka layar penuh.');
                }
            });
        }

        if (!gambar) return;
        const zoomTambah = $('[data-zoom-tambah]');
        const zoomKurang = $('[data-zoom-kurang]');
        const zoomReset = $('[data-zoom-reset]');
        const zoomNilai = $('[data-zoom-nilai]');
        const area = $('[data-gambar-observer]');
        const MIN = 50, MAX = 300, STEP = 25;
        let zoom = 100;

        function setZoom(baru) {
            zoom = Math.max(MIN, Math.min(MAX, baru));
            gambar.style.width = zoom + '%';
            gambar.style.maxWidth = 'none';
            gambar.style.height = 'auto';
            if (zoomNilai) zoomNilai.textContent = zoom + '%';
            if (zoomKurang) zoomKurang.disabled = zoom === MIN;
            if (zoomTambah) zoomTambah.disabled = zoom === MAX;
            if (zoomReset) zoomReset.disabled = zoom === 100;
            if (area && zoom === 100) {
                area.scrollLeft = 0;
                area.scrollTop = 0;
            }
        }
        zoomTambah?.addEventListener('click', () => setZoom(zoom + STEP));
        zoomKurang?.addEventListener('click', () => setZoom(zoom - STEP));
        zoomReset?.addEventListener('click', () => setZoom(100));
        if (area) {
            area.addEventListener('keydown', event => {
                if ((event.key === '+' || event.key === '=') && !event.ctrlKey) {
                    event.preventDefault(); setZoom(zoom + STEP);
                } else if (event.key === '-' && !event.ctrlKey) {
                    event.preventDefault(); setZoom(zoom - STEP);
                } else if (event.key === '0' && !event.ctrlKey) {
                    event.preventDefault(); setZoom(100);
                }
            });
        }
        setZoom(100);
    });
})();
