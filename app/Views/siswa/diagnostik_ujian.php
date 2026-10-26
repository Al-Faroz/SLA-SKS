<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Ujian Diagnostik · SLA-SKS</title>
    <link rel="stylesheet" href="<?= esc(base_url('public/assets/css/app.css'), 'attr') ?>">
</head>
<body class="a-ujian-halaman">
<main class="a-ujian" id="konten-utama"
    data-ujian-tp="<?= (int) $tp['id'] ?>"
    data-ujian-url="<?= esc(site_url('siswa/tp/' . (int) $tp['id'] . '/diagnostik'), 'attr') ?>"
    data-ujian-token="<?= esc((string) ($percobaan['token_publik'] ?? ''), 'attr') ?>"
    data-ujian-existing="<?= esc((string) ($percobaan['status'] ?? ''), 'attr') ?>"
    data-csrf-name="<?= esc($csrfName,'attr') ?>"
    data-csrf-hash="<?= esc($csrfValue,'attr') ?>">
    <header class="a-ujian-header">
        <div>
            <span class="a-ujian-logo">SLA-SKS · Diagnostik</span>
            <p class="a-ujian-subtitle"><?= esc((string) $tp['kode_tp']) ?> — <?= esc((string) $tp['judul']) ?></p>
        </div>
        <div class="a-ujian-meter">
            <span>Waktu aktif tersisa</span>
            <strong data-ujian-timer aria-live="off">--:--</strong>
        </div>
    </header>

    <section class="a-ujian-sambutan" data-ujian-sambutan aria-labelledby="judul-mulai">
        <h1 id="judul-mulai">Tes Diagnostik</h1>
        <p>Gunakan fullscreen untuk memulai atau melanjutkan percobaan. Setelah ujian berjalan,
            keluar fullscreen akan menjeda waktu dan mencatat insiden.
            Jawaban hanya dianggap tersimpan setelah server mengonfirmasi.</p>
        <?php if ($status_akhir): ?>
            <p class="a-ujian-peringatan">Percobaan Anda telah berakhir. Gunakan tombol
                <strong>Lihat hasil/status</strong> untuk memeriksa hasil yang tersimpan.</p>
            <button type="button" class="a-btn a-btn--utama" data-ujian-lihat-hasil>Lihat hasil/status</button>
        <?php else: ?>
            <button type="button" class="a-btn a-btn--utama" data-ujian-mulai>
                <?= $percobaan ? 'Kembali ke fullscreen dan lanjutkan' : 'Masuk fullscreen &amp; mulai Diagnostik' ?>
            </button>
        <?php endif ?>
        <p data-ujian-awal-pesan role="status" aria-live="polite">
            Ujian baru dibuat setelah fullscreen berhasil dimasuki melalui tombol di atas.
        </p>
        <a class="a-btn a-btn--sekunder"
            href="<?= esc(site_url('siswa/tp/' . (int) $tp['id'] . '/diagnostik/petunjuk'),'attr') ?>">
            Kembali ke petunjuk
        </a>
    </section>

    <section class="a-ujian-kerja" data-ujian-kerja hidden>
        <div class="a-ujian-statusbar">
            <span data-ujian-posisi>Soal 1</span>
            <span data-ujian-simpan role="status" aria-live="polite">Belum ada jawaban disimpan.</span>
            <span data-ujian-insiden>Insiden fullscreen: 0/3</span>
        </div>
        <article class="a-ujian-soal" aria-labelledby="a-ujian-soal-judul">
            <h1 id="a-ujian-soal-judul">Pertanyaan <span data-ujian-nomor>1</span></h1>
            <div class="a-ujian-pertanyaan" data-ujian-pertanyaan></div>
            <fieldset class="a-ujian-opsi" data-ujian-opsi>
                <legend>Pilih satu jawaban</legend>
            </fieldset>
        </article>
        <nav class="a-ujian-navigator" aria-label="Navigasi soal">
            <div data-ujian-nav></div>
            <div class="a-ujian-navaksi">
                <button class="a-btn a-btn--sekunder" type="button" data-ujian-sebelum>Sebelumnya</button>
                <button class="a-btn a-btn--sekunder" type="button" data-ujian-sesudah>Berikutnya</button>
                <button class="a-btn a-btn--utama" type="button" data-ujian-kirim>Kirim jawaban &amp; selesaikan</button>
            </div>
        </nav>
    </section>

    <section class="a-ujian-overlay" data-ujian-jeda hidden role="alertdialog"
        aria-modal="true" aria-labelledby="a-ujian-jeda-judul">
        <div class="a-ujian-overlay__isi">
            <h2 id="a-ujian-jeda-judul">Ujian sedang dijeda</h2>
            <p data-ujian-jeda-pesan>Jeda fullscreen atau koneksi sedang diperiksa oleh server.</p>
            <button class="a-btn a-btn--utama" data-ujian-pulih type="button">
                Kembali ke fullscreen dan lanjutkan
            </button>
            <p class="a-help">Jawaban tidak dapat disimpan saat jeda.
                Jika koneksi terputus, pulihkan koneksi sebelum melanjutkan.</p>
        </div>
    </section>

    <section class="a-ujian-hasil" data-ujian-hasil hidden role="status" aria-live="polite">
        <h1 data-ujian-hasil-judul>Status Diagnostik</h1>
        <p data-ujian-hasil-pesan></p>
        <p class="a-ujian-skor" data-ujian-hasil-skor></p>
        <a class="a-btn a-btn--sekunder"
            href="<?= esc(site_url('siswa/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke rincian TP</a>
    </section>
</main>
<script src="<?= esc(base_url('public/assets/js/diagnostik-ujian.js'),'attr') ?>" defer></script>
</body>
</html>
