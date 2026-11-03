<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Ujian Pra-Semester · SLA-SKS</title>
    <link rel="stylesheet" href="<?= esc(base_url('public/assets/css/app.css'), 'attr') ?>">
</head>
<body class="a-ujian-halaman">
<main class="a-ujian" id="konten-utama"
    data-ujian-tp="<?= (int)$paket['id'] ?>"
    data-ujian-jenis="pra_semester"
    data-ujian-url="<?= esc(site_url('siswa/pra-semester/' . (int)$paket['id']), 'attr') ?>"
    data-ujian-token="<?= esc((string)$token_mulai,'attr') ?>"
    data-ujian-existing="<?= esc((string)($percobaan['status'] ?? ''),'attr') ?>"
    data-csrf-name="<?= esc($csrfName,'attr') ?>"
    data-csrf-hash="<?= esc($csrfValue,'attr') ?>">
    <header class="a-ujian-header">
        <div>
            <span class="a-ujian-logo">SLA-SKS · Pra-Semester</span>
            <p class="a-ujian-subtitle">Gen <?= esc((string)$paket['nama_gen']) ?> ·
                <?= esc((string)$paket['nama_mapel']) ?> · Semester <?= (int)$paket['semester'] ?></p>
        </div>
        <div class="a-ujian-meter">
            <span>Waktu pengerjaan tersisa</span>
            <strong data-ujian-timer aria-live="off">--:--</strong>
        </div>
    </header>
    <?php $aktif = $percobaan && in_array($percobaan['status'],
        ['berjalan','jeda_fullscreen','jeda_koneksi'],true);
        $akhir = $percobaan && in_array($percobaan['status'],
            ['selesai_sah','dibatalkan_menunggu_guru'],true); ?>
    <section class="a-ujian-sambutan" data-ujian-sambutan aria-labelledby="judul-mulai">
        <h1 id="judul-mulai">Ujian Pra-Semester</h1>
        <p>Ujian terjadwal oleh Admin, menggunakan desktop/laptop dan fullscreen.
            Waktu pengerjaan dihitung sejak Anda menekan Mulai dan mulai bekerja,
            bukan sejak jendela jadwal dibuka. Jawaban dan timer tersimpan di server.</p>
        <p class="a-help">
            <?= $jadwal_aktif === null ? 'Jadwal utama' :
                ($jadwal_aktif['jenis'] === 'remedial' ? 'Jadwal remedial' :
                    ($jadwal_aktif['jenis'] === 'susulan' ? 'Jadwal susulan' : 'Jadwal utama')) ?>
            (WIB):
            <strong><?= esc((string)($jadwal_aktif['mulai'] ?? $ujian['mulai_pada'] ?? 'Belum dijadwalkan')) ?></strong>
            sampai <strong><?= esc((string)($jadwal_aktif['akhir'] ?? $ujian['selesai_pada'] ?? 'Belum dijadwalkan')) ?></strong>.
            Durasi: <strong><?= $jadwal_aktif !== null
                ? (int)$jadwal_aktif['durasi_menit'].' menit'
                : ($ujian === null ? '—' : (int)$ujian['durasi_menit'].' menit') ?></strong>.
            KKM: <strong><?= $kkm === null ? 'Belum tersedia' : (int)$kkm ?></strong>.
        </p>
        <?php if ($nilai_terbaik !== null): ?>
            <p class="a-help">Nilai Pra-Semester saat ini:
                <strong><?= number_format((float)$nilai_terbaik,2,',','.') ?></strong>
                (<?= $status_nilai === 'final' ? 'Final' : 'Sementara' ?>).</p>
        <?php endif ?>
        <?php if (!$desktop): ?>
            <div class="a-alert" role="status">
                Jadwal dapat dilihat di HP/tablet, tetapi pengerjaan Pra-Semester
                wajib menggunakan desktop/laptop yang mendukung fullscreen.
            </div>
        <?php elseif ($aktif || $boleh_mulai): ?>
            <button type="button" class="a-btn a-btn--utama" data-ujian-mulai>
                <?= $aktif ? 'Masuk fullscreen &amp; lanjutkan ujian'
                    : ($jadwal_aktif !== null && $jadwal_aktif['jenis'] !== 'utama'
                        ? 'Masuk fullscreen &amp; mulai '.ucfirst((string)$jadwal_aktif['jenis'])
                        : 'Masuk fullscreen &amp; mulai Pra-Semester') ?>
            </button>
        <?php elseif ($akhir): ?>
            <p class="a-ujian-peringatan"><?= $percobaan['status'] === 'dibatalkan_menunggu_guru'
                ? 'Ujian dibatalkan. Tunggu jadwal susulan dari Admin; tidak ada akses ulang mandiri.'
                : ($fase === 'menunggu_remedial' || $fase === 'butuh_susulan_remedial'
                    ? 'Nilai normal tersimpan. Tunggu jadwal remedial/susulan Admin.'
                    : 'Ujian Pra-Semester telah selesai sah.') ?></p>
            <button type="button" class="a-btn a-btn--utama" data-ujian-lihat-hasil>Lihat hasil/status</button>
        <?php else: ?>
            <div class="a-alert" role="status">
                <strong>Ujian belum dapat dimulai.</strong>
                <?php foreach ($gerbang['alasan'] as $alasan): ?>
                    <p><?= esc((string)$alasan) ?></p>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <p data-ujian-awal-pesan role="status" aria-live="polite">
            Percobaan baru hanya dibuat setelah siswa berhasil memasuki fullscreen.
        </p>
        <a class="a-btn a-btn--sekunder"
            href="<?= esc(site_url('siswa/semester/' . (int)$paket['id']), 'attr') ?>">
            Kembali ke daftar TP semester
        </a>
    </section>
    <section class="a-ujian-kerja" data-ujian-kerja hidden>
        <div class="a-ujian-statusbar">
            <span data-ujian-posisi>Soal 1</span>
            <span data-ujian-simpan role="status" aria-live="polite">Belum ada jawaban tersimpan.</span>
            <span data-ujian-insiden>Insiden fullscreen: 0/3</span>
        </div>
        <article class="a-ujian-soal" aria-labelledby="soal-judul">
            <h1 id="soal-judul">Pertanyaan <span data-ujian-nomor>1</span></h1>
            <div class="a-ujian-pertanyaan" data-ujian-pertanyaan></div>
            <fieldset class="a-ujian-opsi" data-ujian-opsi>
                <legend>Pilih satu jawaban</legend>
            </fieldset>
        </article>
        <nav class="a-ujian-navigator" aria-label="Navigasi soal">
            <div data-ujian-nav></div>
            <div class="a-ujian-navaksi">
                <button type="button" class="a-btn a-btn--sekunder" data-ujian-sebelum>Sebelumnya</button>
                <button type="button" class="a-btn a-btn--sekunder" data-ujian-sesudah>Berikutnya</button>
                <button type="button" class="a-btn a-btn--utama" data-ujian-kirim>Kirim jawaban &amp; selesaikan</button>
            </div>
        </nav>
    </section>
    <section class="a-ujian-overlay" data-ujian-jeda hidden role="alertdialog"
        aria-modal="true" aria-labelledby="jeda-judul">
        <div class="a-ujian-overlay__isi">
            <h2 id="jeda-judul">Ujian dijeda</h2>
            <p data-ujian-jeda-pesan>Fullscreen atau koneksi sedang diperiksa server.</p>
            <button class="a-btn a-btn--utama" data-ujian-pulih type="button">
                Kembali ke fullscreen dan lanjutkan
            </button>
        </div>
    </section>
    <section class="a-ujian-hasil" data-ujian-hasil hidden role="status" aria-live="polite">
        <h1 data-ujian-hasil-judul>Status Pra-Semester</h1>
        <p data-ujian-hasil-pesan></p>
        <p class="a-ujian-skor" data-ujian-hasil-skor></p>
        <p class="a-help">Pembatalan memerlukan jadwal susulan Admin. Remedial nilai juga hanya
            berlangsung pada jadwal tersendiri dari Admin; tidak ada tombol ulang mandiri.</p>
        <a class="a-btn a-btn--sekunder"
            href="<?= esc(site_url('siswa/semester/' . (int)$paket['id']), 'attr') ?>">
            Kembali ke daftar TP semester
        </a>
    </section>
</main>
<script src="<?= esc(base_url('public/assets/js/diagnostik-ujian.js'),'attr') ?>" defer></script>
</body>
</html>
