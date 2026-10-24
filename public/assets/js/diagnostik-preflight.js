'use strict';

/*
 * S05: menguji Fullscreen API secara nyata saat klik.
 * Tidak mengirim request untuk membuat percobaan maupun memulai timer ujian.
 */
(() => {
    const root = document.querySelector('[data-diagnostik-preflight]');
    if (!root) return;

    const enter = root.querySelector('[data-uji-fullscreen]');
    const leave = root.querySelector('[data-keluar-fullscreen]');
    const result = root.querySelector('[data-hasil-preflight]');
    if (!enter || !leave || !result) return;

    const report = (message, kind) => {
        result.textContent = message;
        result.dataset.status = kind;
    };
    const supported = () => document.fullscreenEnabled === true
        && typeof document.documentElement.requestFullscreen === 'function';

    if (!supported()) {
        enter.disabled = true;
        report(
            'Perangkat/browser ini tidak mendukung fullscreen. Buka ujian di desktop/laptop yang mendukungnya.',
            'failed'
        );
    }

    document.addEventListener('fullscreenchange', () => {
        const open = document.fullscreenElement === document.documentElement;
        leave.hidden = !open;
        if (!open && result.dataset.status === 'checking') {
            report('Fullscreen terhenti sebelum pemeriksaan berhasil. Coba lagi.', 'failed');
        }
    });

    enter.addEventListener('click', async () => {
        if (!supported()) {
            report(
                'Perangkat/browser ini tidak mendukung fullscreen. Buka ujian di desktop/laptop yang mendukungnya.',
                'failed'
            );
            return;
        }
        if (navigator.onLine === false) {
            report('Perangkat sedang offline. Sambungkan internet sebelum menguji perangkat.', 'failed');
            return;
        }
        if (document.visibilityState !== 'visible') {
            report('Buka tab ini sebelum melakukan uji fullscreen.', 'failed');
            return;
        }
        enter.disabled = true;
        report('Meminta izin fullscreen dari browser…', 'checking');
        try {
            await document.documentElement.requestFullscreen();
            if (document.fullscreenElement !== document.documentElement) {
                throw new Error('Fullscreen belum aktif.');
            }
            report(
                'Fullscreen berhasil diaktifkan. Pemeriksaan perangkat lulus. '
                    + 'Ini belum memulai ujian; tekan "Keluar dari fullscreen pemeriksaan" untuk kembali.',
                'success'
            );
            leave.hidden = false;
        } catch (_) {
            report(
                'Browser menolak fullscreen atau perangkat tidak mendukungnya. '
                    + 'Gunakan desktop/laptop dengan browser yang mendukung fullscreen.',
                'failed'
            );
        } finally {
            enter.disabled = !supported();
        }
    });

    leave.addEventListener('click', async () => {
        if (!document.fullscreenElement || typeof document.exitFullscreen !== 'function') {
            leave.hidden = true;
            return;
        }
        try {
            await document.exitFullscreen();
        } catch (_) {
            report('Tidak dapat keluar dari fullscreen; coba tekan Esc.', 'failed');
        }
    });
})();
