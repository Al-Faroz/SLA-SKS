'use strict';

/**
 * S08B: sinyal browser bukan jam otoritatif. Server menghitung delta 5–20 dtk,
 * playback position, dan menyimpan progres. Tidak ada autosave Resume/HOTS.
 */
(() => {
    const root = document.querySelector('[data-media-progres]');
    if (!root) return;
    const url = root.dataset.mediaUrl;
    const jenis = root.dataset.mediaJenis;
    const tokenName = root.dataset.csrfName;
    let token = root.dataset.csrfHash;
    let sedangKirim = false;
    let selesai = false;
    let yt = null;
    let ytMemutar = false;
    let gambarTerlihat = false;
    let resetPdf = true;
    const statusEl = root.querySelector('[data-media-status]');
    const waktuEl = root.querySelector('[data-media-waktu]');
    const bilah = root.querySelector('[data-media-bilah]');

    const statusText = message => { statusEl.textContent = message; };
    const amanDilihat = () => document.visibilityState === 'visible'
        && document.hasFocus() && navigator.onLine !== false;

    async function request(method, body = null) {
        const opts = {method, credentials: 'same-origin', cache: 'no-store',
            headers: {'Accept': 'application/json'}};
        if (method === 'POST') {
            const data = new FormData();
            data.append(tokenName, token);
            Object.entries(body || {}).forEach(([key, value]) => data.append(key, value));
            opts.body = data;
        }
        const response = await fetch(url, opts);
        const raw = await response.json();
        if (typeof raw.csrf === 'string') token = raw.csrf;
        if (!response.ok || !raw.ok) throw new Error(raw.pesan || 'Aktivitas belum tersimpan.');
        return raw.data;
    }

    function tampil(data) {
        selesai = data.media_selesai === true;
        const secs = Number(data.waktu_aktif_detik) || 0;
        const required = Math.max(1, Number(data.minimal_detik) || 1);
        bilah.value = Math.min(100, Math.floor(secs * 100 / required));
        waktuEl.textContent = 'Waktu aktif tersimpan: ' + secs
            + ' detik. Minimal: ' + required + ' detik.'
            + (data.persen_putar_minimal ? ' Pemutaran minimal: 90% durasi.' : '');
        if (jenis === 'pdf' && Number(data.halaman_total) > 0) {
            waktuEl.textContent += ' Halaman selesai: ' + Number(data.halaman_selesai)
                + '/' + Number(data.halaman_total) + '.';
        }
        if (selesai) {
            bilah.value = 100;
            statusText('Media selesai dan tersimpan di server. Perbarui halaman untuk membuka Resume dan HOTS.');
        } else {
            statusText(data.pesan || 'Menunggu aktivitas media.');
        }
    }

    function sinyal() {
        if (!amanDilihat()) return null;
        if (jenis === 'gambar') {
            const image = document.querySelector('[data-gambar-materi]');
            if (!image || !image.complete || image.naturalWidth === 0 || !gambarTerlihat) {
                return null;
            }
            return { aktif: 'ya', posisi: '', durasi: '' };
        }
        if (jenis === 'pdf') {
            const pdf = document.querySelector('[data-pdf-pembaca]');
            if (!pdf || pdf.dataset.pdfReady !== 'ya') return null;
            const halaman = Number(pdf.dataset.pdfHalaman);
            const total = Number(pdf.dataset.pdfTotal);
            if (!Number.isInteger(halaman) || !Number.isInteger(total)
                || halaman < 1 || total < 1 || halaman > total) return null;
            return {aktif:'ya', posisi:'', durasi:'',
                halaman:String(halaman), jumlah_halaman:String(total),
                reset: resetPdf ? 'ya' : 'tidak'};
        }
        if (jenis === 'audio') {
            const player = document.querySelector('[data-audio-materi]');
            if (!player || player.paused || player.ended || player.seeking
                || Math.abs(player.playbackRate - 1) > .001
                || !Number.isFinite(player.duration) || player.duration < 5) {
                return null;
            }
            return { aktif: 'ya', posisi: String(Math.floor(player.currentTime)),
                durasi: String(Math.round(player.duration)) };
        }
        if (jenis === 'youtube' && yt && ytMemutar) {
            try {
                const duration = yt.getDuration();
                const position = yt.getCurrentTime();
                const rate = yt.getPlaybackRate();
                if (Number.isFinite(duration) && duration >= 5
                    && Number.isFinite(position) && Math.abs(rate - 1) <= .001) {
                    return { aktif:'ya', posisi:String(Math.floor(position)),
                        durasi:String(Math.round(duration)) };
                }
            } catch (_) { return null; }
        }
        return null;
    }

    async function detak() {
        if (selesai || sedangKirim) return;
        const active = sinyal();
        if (!active) return;
        sedangKirim = true;
        try {
            const result = await request('POST', active);
            if (jenis === 'pdf') {
                const frame = document.querySelector('[data-pdf-pembaca]');
                if (active.reset === 'ya' && frame?.dataset.pdfReady === 'ya'
                    && frame.dataset.pdfHalaman === active.halaman && amanDilihat()) {
                    resetPdf = false;
                }
            }
            tampil(result);
        } catch (err) {
            statusText('Belum tersimpan: ' + err.message + ' Pastikan koneksi aktif.');
        } finally {
            sedangKirim = false;
        }
    }

    if (jenis === 'gambar') {
        const image = document.querySelector('[data-gambar-materi]');
        const viewport = document.querySelector('[data-gambar-observer]');
        if (image && viewport && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                // Ukur area pembaca yang terlihat, bukan rasio gambar:
                // zoom dapat membuat gambar 300% sehingga sebagian besar
                // pixels berada di luar viewport walau area baca aktif.
                gambarTerlihat = entries.some(entry => entry.isIntersecting
                    && entry.intersectionRatio >= .5);
            }, {threshold:[0, .5, 1]});
            observer.observe(viewport);
        } else if (image) {
            // Browser tanpa IntersectionObserver tidak mengklaim area dibaca.
            statusText('Browser tidak mendukung pemeriksaan tampilan gambar.');
        }
    }
    if (jenis === 'youtube') {
        const iframe = document.getElementById('a-siswa-video-pemutar');
        if (iframe) {
            const ready = () => {
                if (!window.YT || !window.YT.Player) return;
                yt = new window.YT.Player(iframe, {
                    events: {
                        onStateChange: evt => {
                            ytMemutar = evt.data === window.YT.PlayerState.PLAYING;
                        },
                        onError: () => statusText('Video YouTube tidak dapat diputar.')
                    }
                });
            };
            if (window.YT && window.YT.Player) ready();
            else {
                window.onYouTubeIframeAPIReady = ready;
                const script = document.createElement('script');
                script.src = 'https://www.youtube.com/iframe_api';
                script.async = true;
                script.onerror = () => statusText('API YouTube gagal dimuat. Putar media bila jaringan pulih.');
                document.head.appendChild(script);
            }
        }
    }
    request('GET').then(tampil).catch(err => statusText(
        'Progres belum dapat dimuat: ' + err.message + '.'));
    if (jenis === 'pdf') {
        document.addEventListener('visibilitychange', () => {
            resetPdf = true;
            if (document.visibilityState === 'visible') detak();
        });
        window.addEventListener('blur', () => { resetPdf = true; });
        window.addEventListener('focus', () => { resetPdf = true; detak(); });
        document.querySelector('[data-pdf-pembaca]')?.addEventListener(
            'pdf-halaman-siap', () => {
                resetPdf = true;
                // Detak langsung pada halaman baru tidak menambah kredit,
                // hanya mencatat nomor halaman terakhir dari klien yang aktif.
                detak();
            });
    }
    window.setInterval(detak, 10000);
})();
