'use strict';

/**
 * Pembaca media siswa (fullscreen PDF/gambar, zoom gambar).
 * Zoom 100% = gambar utuh yang muat di area baca, tidak diperbesar paksa
 * melebihi resolusi asli. 50-300% relatif terhadap ukuran muat tersebut.
 * Perubahan zoom tidak mengirim heartbeat, nilai, atau data ke server.
 */
(() => {
    document.querySelectorAll('[data-pembaca-fullscreen]').forEach(root => {
        const $ = selector => root.querySelector(selector);
        const toggle = $('[data-pembaca-toggle]');
        const feedback = $('[data-pembaca-pesan]');
        const gambar = $('[data-gambar-materi]');
        const area = $('[data-gambar-observer]');
        const stage = $('[data-gambar-stage]');
        const bisaFullscreen = () => document.fullscreenEnabled === true
            && typeof root.requestFullscreen === 'function';
        const aktifFullscreen = () => document.fullscreenElement === root;
        const pesan = message => { if (feedback) feedback.textContent = message; };

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

        if (!gambar || !area || !stage) return;
        const zoomTambah = $('[data-zoom-tambah]');
        const zoomKurang = $('[data-zoom-kurang]');
        const zoomReset = $('[data-zoom-reset]');
        const zoomNilai = $('[data-zoom-nilai]');
        const MIN = 50;
        const MAX = 300;
        const STEP = 25;
        let zoom = 100;
        let terukur = false;
        let lebarGambar = 0;
        let tinggiGambar = 0;
        let lebarStage = 0;
        let tinggiStage = 0;

        const clamp = (value, low, high) => Math.min(high, Math.max(low, value));
        const siap = () => gambar.complete && gambar.naturalWidth > 0
            && gambar.naturalHeight > 0 && area.clientWidth > 0 && area.clientHeight > 0;

        function kontrol() {
            if (zoomNilai) zoomNilai.textContent = zoom + '%';
            if (zoomKurang) zoomKurang.disabled = !terukur || zoom <= MIN;
            if (zoomTambah) zoomTambah.disabled = !terukur || zoom >= MAX;
            if (zoomReset) zoomReset.disabled = !terukur || zoom === 100;
            area.classList.toggle('is-zoomed', terukur
                && (lebarGambar > area.clientWidth || tinggiGambar > area.clientHeight));
        }

        // Kunci titik tengah gambar yang sedang terlihat, termasuk ketika
        // ukuran viewport berubah setelah fullscreen/resize. Tanpa ini zoom
        // cenderung meloncat ke pojok kiri atas dan kehilangan posisi baca.
        function tengahSebelumnya() {
            if (!terukur || lebarGambar <= 0 || tinggiGambar <= 0) {
                return {x: 0.5, y: 0.5};
            }
            const kiri = (lebarStage - lebarGambar) / 2;
            const atas = (tinggiStage - tinggiGambar) / 2;
            return {
                x: clamp((area.scrollLeft + area.clientWidth / 2 - kiri) / lebarGambar, 0, 1),
                y: clamp((area.scrollTop + area.clientHeight / 2 - atas) / tinggiGambar, 0, 1)
            };
        }

        function gambarUlang() {
            if (!siap()) {
                terukur = false;
                kontrol();
                return;
            }
            const pusat = tengahSebelumnya();
            const lebarArea = area.clientWidth;
            const tinggiArea = area.clientHeight;
            const lebarAsli = gambar.naturalWidth;
            const tinggiAsli = gambar.naturalHeight;
            // Muat seluruh gambar (portrait/landscape), tanpa upscaling pada 100%.
            const skalaMuat = Math.min(1, lebarArea / lebarAsli, tinggiArea / tinggiAsli);
            lebarGambar = Math.max(1, lebarAsli * skalaMuat * zoom / 100);
            tinggiGambar = Math.max(1, tinggiAsli * skalaMuat * zoom / 100);
            lebarStage = Math.max(lebarArea, lebarGambar);
            tinggiStage = Math.max(tinggiArea, tinggiGambar);
            stage.style.width = lebarStage + 'px';
            stage.style.height = tinggiStage + 'px';
            gambar.style.width = lebarGambar + 'px';
            gambar.style.height = tinggiGambar + 'px';
            gambar.style.maxWidth = 'none';
            gambar.style.maxHeight = 'none';
            terukur = true;
            const kiri = (lebarStage - lebarGambar) / 2;
            const atas = (tinggiStage - tinggiGambar) / 2;
            area.scrollLeft = clamp(kiri + pusat.x * lebarGambar - lebarArea / 2,
                0, Math.max(0, lebarStage - lebarArea));
            area.scrollTop = clamp(atas + pusat.y * tinggiGambar - tinggiArea / 2,
                0, Math.max(0, tinggiStage - tinggiArea));
            kontrol();
        }

        function setZoom(baru) {
            if (!terukur) return;
            const tujuan = clamp(baru, MIN, MAX);
            if (tujuan === zoom) return;
            zoom = tujuan;
            gambarUlang();
        }
        zoomTambah?.addEventListener('click', () => setZoom(zoom + STEP));
        zoomKurang?.addEventListener('click', () => setZoom(zoom - STEP));
        zoomReset?.addEventListener('click', () => setZoom(100));
        area.addEventListener('keydown', event => {
            if (event.ctrlKey || event.altKey || event.metaKey) return;
            if (event.key === '+' || event.key === '=') {
                event.preventDefault(); setZoom(zoom + STEP);
            } else if (event.key === '-') {
                event.preventDefault(); setZoom(zoom - STEP);
            } else if (event.key === '0') {
                event.preventDefault(); setZoom(100);
            }
        });
        // Ctrl+scroll untuk zoom; scroll biasa tetap dapat menjelajah gambar.
        area.addEventListener('wheel', event => {
            if (!event.ctrlKey) return;
            event.preventDefault();
            setZoom(zoom + (event.deltaY < 0 ? STEP : -STEP));
        }, {passive: false});

        // Pada desktop, gambar besar dapat digeser dengan drag mouse.
        // Sentuhan HP tetap memakai scroll asli browser agar pinch/scroll bekerja.
        let drag = null;
        area.addEventListener('pointerdown', event => {
            if (event.pointerType !== 'mouse' || event.button !== 0
                || !area.classList.contains('is-zoomed')) return;
            drag = {x: event.clientX, y: event.clientY,
                kiri: area.scrollLeft, atas: area.scrollTop, id: event.pointerId};
            area.setPointerCapture?.(event.pointerId);
        });
        area.addEventListener('pointermove', event => {
            if (!drag || event.pointerId !== drag.id) return;
            area.scrollLeft = drag.kiri - (event.clientX - drag.x);
            area.scrollTop = drag.atas - (event.clientY - drag.y);
        });
        const stopDrag = () => { drag = null; };
        area.addEventListener('pointerup', stopDrag);
        area.addEventListener('pointercancel', stopDrag);
        area.addEventListener('lostpointercapture', stopDrag);

        function siapGambar() {
            gambarUlang();
            if (terukur) pesan('');
        }
        gambar.addEventListener('load', siapGambar);
        gambar.addEventListener('error', () => {
            terukur = false;
            kontrol();
            pesan('Gambar gagal dimuat. Periksa sambungan dan muat ulang halaman.');
        });
        // Perhitungan otomatis saat fullscreen masuk/keluar dan lebar viewport
        // berubah. Observer hanya pada viewport, tidak pada gambar/stage.
        if ('ResizeObserver' in window) {
            new window.ResizeObserver(() => gambarUlang()).observe(area);
        } else {
            window.addEventListener('resize', gambarUlang);
        }
        document.addEventListener('fullscreenchange', () => {
            window.requestAnimationFrame?.(gambarUlang);
        });
        kontrol();
        if (gambar.complete) siapGambar();
    });
})();
