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
    let resetGambar = true;
    let versiSesiGambar = 0;
    let detakGambarTertunda = false;
    let resetPdf = true;
    let memuatUlang = false;
    let akhirAudioTertunda = false;
    let halamanPdfTertunda = false;
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
        const secs = Math.max(0, Number(data.waktu_aktif_detik) || 0);
        const required = Math.max(1, Number(data.minimal_detik) || 1);
        const persentase = [];
        let rincian = '';

        if (data.persen_putar_minimal) {
            const target = Number(data.batas_efektif_detik);
            const putar = Math.max(0, Number(data.waktu_putar_detik) || 0);
            const durasi = Number(data.durasi_media_detik);
            const target90 = Number(data.pemutaran_minimal_detik);
            if (Number.isFinite(target) && target > 0) {
                persentase.push(putar / target, secs / target);
                const asal = required <= target90
                    ? 'waktu minimum Guru' : '90% durasi media';
                rincian = 'Batas yang berlaku: ' + target + ' detik (' + asal
                    + '). Waktu putar diakui: ' + putar + '/' + target
                    + ' detik. Durasi media: ' + durasi + ' detik.'
                    + ' Guru menetapkan ' + required
                    + ' detik; 90% media: ' + target90 + ' detik.';
            } else {
                persentase.push(0);
                rincian = 'Menunggu metadata durasi video/audio'
                    + ' agar batas yang lebih pendek dapat dihitung.';
            }
        } else {
            persentase.push(secs / required);
            rincian = 'Waktu belajar: ' + secs + '/' + required
                + ' detik (' + (secs >= required ? 'Terpenuhi' : 'Belum terpenuhi')
                + ').';
        }
        if (jenis === 'pdf' && Number(data.halaman_total) > 0) {
            const total = Math.max(1, Number(data.halaman_total));
            const sudah = Math.max(0, Number(data.halaman_selesai) || 0);
            persentase.push(sudah / total);
            rincian += ' Halaman selesai: ' + sudah + '/' + total + '.';
            const belum = Array.isArray(data.halaman_belum_selesai)
                ? data.halaman_belum_selesai : [];
            if (belum.length > 0) {
                rincian += ' Halaman belum tuntas: '
                    + belum.slice(0, 8).join(', ')
                    + (belum.length > 8
                        ? ' dan ' + (belum.length - 8) + ' lainnya.' : '.');
            }
            const frame = document.querySelector('[data-pdf-pembaca]');
            const nomor = Number(frame?.dataset.pdfHalaman);
            if (Number.isInteger(nomor) && nomor >= 1 && nomor <= total) {
                const detik = Number(data.waktu_per_halaman?.[nomor]) || 0;
                rincian += ' Halaman ' + nomor + ': '
                    + Math.max(0, detik) + '/15 detik tercatat.';
            }
        }
        if (!data.persen_putar_minimal && !selesai) {
            const sisa = Math.max(0, Number(data.waktu_tersisa_detik) || 0);
            rincian += ' Sisa waktu aktif: ' + sisa + ' detik.';
        }
        // Bilah menunjukkan syarat yang paling tertinggal, bukan waktu saja.
        bilah.value = selesai ? 100 : Math.min(99,
            Math.max(0, Math.floor(100 * Math.min(...persentase))));
        waktuEl.textContent = rincian;

        if (selesai) {
            statusText('Seluruh syarat media terpenuhi dan tersimpan di server.');
            // Form dikendalikan server; buka melalui GET baru, bukan melewati gate via DOM.
            if (!memuatUlang && document.querySelector('[data-kegiatan-tunggu-media]')) {
                memuatUlang = true;
                statusText('Media selesai. Membuka formulir Resume…');
                window.location.reload();
            }
        } else if (data.persen_putar_minimal) {
            statusText('Lanjutkan pemutaran hingga batas yang lebih cepat tercapai: '
                + 'waktu Guru atau 90% durasi media. Jangan lompat posisi video/audio.');
        } else {
            statusText(data.pesan || 'Menunggu aktivitas media.');
        }
    }

    function sinyal(audioBerakhir = false) {
        if (!amanDilihat()) return null;
        if (jenis === 'gambar') {
            const image = document.querySelector('[data-gambar-materi]');
            if (!image || !image.complete || image.naturalWidth === 0 || !gambarTerlihat) {
                return null;
            }
            return { aktif: 'ya', posisi: '', durasi: '',
                reset: resetGambar ? 'ya' : 'tidak' };
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
            if (!player || (player.paused && !audioBerakhir)
                || (player.ended && !audioBerakhir) || player.seeking
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

    async function detak(audioBerakhir = false) {
        if (selesai) return;
        if (sedangKirim) {
            if (jenis === 'gambar') detakGambarTertunda = true;
            if (jenis === 'audio' && audioBerakhir) akhirAudioTertunda = true;
            if (jenis === 'pdf' && resetPdf) halamanPdfTertunda = true;
            return;
        }
        const active = sinyal(audioBerakhir);
        if (!active) return;
        const versiSaatKirim = versiSesiGambar;
        sedangKirim = true;
        try {
            const result = await request('POST', active);
            if (jenis === 'gambar' && active.reset === 'ya'
                && versiSesiGambar === versiSaatKirim
                && gambarTerlihat && amanDilihat()) {
                resetGambar = false;
            }
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
            if (!selesai && detakGambarTertunda) {
                detakGambarTertunda = false;
                if (gambarTerlihat && amanDilihat()) detak();
            } else if (!selesai && akhirAudioTertunda) {
                akhirAudioTertunda = false;
                detak(true);
            } else if (!selesai && halamanPdfTertunda) {
                halamanPdfTertunda = false;
                detak();
            }
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
                const sebelumnya = gambarTerlihat;
                gambarTerlihat = entries.some(entry => entry.isIntersecting
                    && entry.intersectionRatio >= .5);
                if (gambarTerlihat !== sebelumnya) {
                    // Segmen di luar viewport tidak boleh dikreditkan saat masuk lagi.
                    resetGambar = true;
                    versiSesiGambar++;
                    if (gambarTerlihat) detak();
                }
            }, {threshold:[0, .5, 1]});
            observer.observe(viewport);
            image.addEventListener('load', () => {
                resetGambar = true;
                versiSesiGambar++;
                if (gambarTerlihat) detak();
            });
        } else if (image) {
            // Browser tanpa IntersectionObserver tidak mengklaim area dibaca.
            statusText('Browser tidak mendukung pemeriksaan tampilan gambar.');
        }
    }
    if (jenis === 'audio') {
        const player = document.querySelector('[data-audio-materi]');
        if (player) {
            player.addEventListener('play', () => detak());
            player.addEventListener('playing', () => detak());
            player.addEventListener('loadedmetadata', () => {
                if (!player.paused) detak();
            });
            player.addEventListener('seeked', () => {
                if (!player.paused) detak();
            });
            // Kredit interval terakhir tetap diverifikasi menggunakan jam server.
            player.addEventListener('ended', () => detak(true));
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
    if (jenis === 'gambar' || jenis === 'audio') {
        document.addEventListener('visibilitychange', () => {
            if (jenis === 'gambar') {
                resetGambar = true;
                versiSesiGambar++;
            }
            if (document.visibilityState === 'visible') detak();
        });
        window.addEventListener('blur', () => {
            if (jenis === 'gambar') {
                resetGambar = true;
                versiSesiGambar++;
            }
        });
        window.addEventListener('focus', () => {
            if (jenis === 'gambar') {
                resetGambar = true;
                versiSesiGambar++;
            }
            detak();
        });
    }
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
    // 5 detik untuk gambar/PDF membuat 15 detik per halaman benar-benar
    // tercapai sekitar detak ketiga; YouTube/audio tidak diubah (10 detik).
    window.setInterval(detak, ['gambar', 'pdf'].includes(jenis) ? 5000 : 10000);
})();
