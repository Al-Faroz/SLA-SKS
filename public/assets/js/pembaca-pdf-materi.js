'use strict';

/* S08D PDF.js lokal. Satu halaman yang berhasil dirender dinyatakan aktif;
   waktu aktivitas dihitung di SERVER lewat endpoint S08B, bukan dari JS ini. */
(() => {
    const root = document.querySelector('[data-pdf-pembaca]');
    if (!root) return;
    const area = root.querySelector('[data-pdf-baca-area]');
    const canvas = root.querySelector('[data-pdf-kanvas]');
    const info = root.querySelector('[data-pdf-pesan]');
    const count = root.querySelector('[data-pdf-jumlah]');
    const prev = root.querySelector('[data-pdf-sebelum]');
    const next = root.querySelector('[data-pdf-berikut]');
    const plus = root.querySelector('[data-pdf-perbesar]');
    const minus = root.querySelector('[data-pdf-perkecil]');
    const zoomText = root.querySelector('[data-pdf-zoom]');
    const source = root.dataset.pdfUrl;
    const worker = root.dataset.pdfWorker;
    const expected = Number(root.dataset.pdfExpected);
    let pdf = null, current = 1, zoom = 100, sequence = 0, pending = null;
    let lastRequested = 0;
    const status = message => { info.textContent = message; };
    const ready = value => { root.dataset.pdfReady = value ? 'ya' : 'tidak'; };

    function controls() {
        const yes = !!pdf;
        prev.disabled = !yes || current <= 1;
        next.disabled = !yes || current >= pdf.numPages;
        minus.disabled = !yes || zoom <= 50;
        plus.disabled = !yes || zoom >= 200;
        count.textContent = yes ? 'Halaman ' + current + ' dari ' + pdf.numPages
            : 'Memuat PDF…';
        zoomText.textContent = zoom + '%';
    }

    async function render() {
        if (!pdf) return;
        const seq = ++sequence;
        ready(false);
        controls();
        if (pending) {
            try { pending.cancel(); } catch (_) { /* render already finished */ }
            // PDF.js tidak dapat memulai render baru pada canvas yang sama
            // sampai render sebelumnya selesai dibatalkan.
            try { await pending.promise; } catch (_) { /* cancellation expected */ }
        }
        if (seq !== sequence) return;
        pending = null;
        status('Menampilkan halaman ' + current + '…');
        try {
            const page = await pdf.getPage(current);
            if (seq !== sequence) return;
            const natural = page.getViewport({scale: 1});
            const width = Math.max(220, area.clientWidth - 28);
            const scale = Math.min(4, Math.max(.25, width / natural.width * zoom / 100));
            const viewport = page.getViewport({scale});
            const pixelRatio = Math.min(2, window.devicePixelRatio || 1);
            const maxPixels = 16000000;
            const sizeFactor = Math.min(pixelRatio,
                Math.sqrt(maxPixels / (viewport.width * viewport.height)));
            const factor = Math.max(.1, sizeFactor);
            canvas.width = Math.max(1, Math.floor(viewport.width * factor));
            canvas.height = Math.max(1, Math.floor(viewport.height * factor));
            canvas.style.width = Math.floor(viewport.width) + 'px';
            canvas.style.height = Math.floor(viewport.height) + 'px';
            const ctx = canvas.getContext('2d', {alpha: false});
            if (!ctx) throw new Error('Browser tidak menyediakan Canvas.');
            const task = page.render({
                canvasContext: ctx, viewport,
                transform: [factor, 0, 0, factor, 0, 0],
                annotationMode: 0,
            });
            pending = task;
            await task.promise;
            if (seq !== sequence) return;
            pending = null;
            area.scrollTop = 0;
            area.scrollLeft = 0;
            root.dataset.pdfHalaman = String(current);
            root.dataset.pdfTotal = String(pdf.numPages);
            ready(true);
            status('Halaman ' + current + ' tampil. Waktu baca dicatat saat halaman aktif.');
            // Event ke aktivitas-materi.js untuk memulai ulang sesi halaman.
            root.dispatchEvent(new CustomEvent('pdf-halaman-siap', {bubbles: true}));
        } catch (error) {
            if (seq !== sequence) return;
            pending = null;
            ready(false);
            if (error?.name === 'RenderingCancelledException') return;
            status('PDF gagal ditampilkan: ' + (error?.message || 'Muat ulang halaman.'));
        }
    }

    async function load() {
        ready(false);
        controls();
        if (!window.pdfjsLib || !window.pdfjsLib.getDocument || !source
            || !worker || !Number.isInteger(expected) || expected < 1 || expected > 40) {
            status('Pembaca PDF tidak lengkap. Muat ulang halaman atau hubungi pengelola.');
            return;
        }
        try {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = worker;
            // Materi privat: request same-origin dengan cookie sesi;
            // tidak mengekspor media atau memanggil CDN.
            const task = window.pdfjsLib.getDocument({
                url: source, withCredentials: true, disableStream: true, disableRange: true,
            });
            pdf = await task.promise;
            if (pdf.numPages !== expected) {
                pdf = null;
                status('Jumlah halaman PDF berbeda dari data materi. Hubungi Guru.');
                return;
            }
            current = 1;
            await render();
        } catch (error) {
            ready(false);
            pdf = null;
            controls();
            status('PDF gagal dibuka: ' + (error?.message || 'Periksa koneksi lalu muat ulang.'));
        }
    }

    function go(target) {
        if (!pdf) return;
        const newPage = Math.max(1, Math.min(pdf.numPages, target));
        if (newPage === current) return;
        current = newPage;
        ready(false);
        render();
    }

    prev.addEventListener('click', () => go(current - 1));
    next.addEventListener('click', () => go(current + 1));
    plus.addEventListener('click', () => {
        if (zoom >= 200) return;
        zoom += 25;
        render();
    });
    minus.addEventListener('click', () => {
        if (zoom <= 50) return;
        zoom -= 25;
        render();
    });
    area.addEventListener('keydown', event => {
        if (event.ctrlKey || event.altKey || event.metaKey) return;
        if (event.key === 'PageDown' || event.key === 'ArrowRight') {
            event.preventDefault(); go(current + 1);
        }
        if (event.key === 'PageUp' || event.key === 'ArrowLeft') {
            event.preventDefault(); go(current - 1);
        }
    });
    if ('ResizeObserver' in window) {
        new ResizeObserver(() => {
            if (!pdf) return;
            const width = area.clientWidth;
            if (Math.abs(width - lastRequested) < 12) return;
            lastRequested = width;
            render();
        }).observe(area);
    }
    load();
})();
