<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'), 'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Pembelajaran</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/tp/' . (int) $tp['id']), 'attr') ?>">Rincian TP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Materi <?= (int) $materi['urutan'] ?></span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · <?= esc((string) $tp['kode_tp']) ?> · Materi <?= (int) $materi['urutan'] ?></p>
            <h1><?= esc($materi['judul']) ?></h1>
            <p class="a-proto-muted">
                <?= $sukarela ? 'Belajar sukarela setelah lulus Diagnostik.' :
                    'Pembelajaran wajib setelah Diagnostik belum memenuhi KKM.' ?>
            </p>
        </div>
    </header>

    <section class="a-card a-siswa-media" aria-labelledby="konten-media">
        <h2 id="konten-media">Media pembelajaran · <?= esc(strtoupper($materi['jenis'])) ?></h2>
        <?php if ($materi['jenis'] === 'youtube'): ?>
            <div class="a-media-video">
                <iframe src="<?= esc($video_url, 'attr') ?>"
                    title="Video pembelajaran: <?= esc($materi['judul'], 'attr') ?>"
                    loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                    allow="encrypted-media; picture-in-picture; fullscreen"
                    allowfullscreen></iframe>
            </div>
            <p class="a-help">Tonton video pembelajaran ini. Pencatatan durasi putar
                sebagai syarat ketuntasan akan tersedia pada tahap berikutnya.</p>
        <?php elseif ($materi['jenis'] === 'pdf'): ?>
            <div class="a-materi-pdf">
                <iframe src="<?= esc($media_url, 'attr') ?>#toolbar=1"
                    title="PDF pembelajaran: <?= esc($materi['judul'], 'attr') ?>"
                    loading="lazy"></iframe>
            </div>
            <p class="a-help">Dokumen PDF tersedia untuk dibaca. Pembaca PDF.js beserta
                pencatatan halaman dan waktu aktif belum diaktifkan pada tahap S08A.</p>
        <?php elseif ($materi['jenis'] === 'audio'): ?>
            <div class="a-materi-audio">
                <audio controls preload="metadata"
                    src="<?= esc($media_url, 'attr') ?>"
                    aria-label="Pemutar audio: <?= esc($materi['judul'], 'attr') ?>">
                    Browser tidak mendukung pemutar audio.
                </audio>
            </div>
            <p class="a-help">Putar audio dengan pemutar di atas.
                Status durasi putar belum menjadi ketuntasan resmi.</p>
        <?php elseif ($materi['jenis'] === 'gambar'): ?>
            <div class="a-materi-gambar">
                <img src="<?= esc($media_url, 'attr') ?>"
                    alt="Gambar materi <?= esc($materi['judul'], 'attr') ?>"
                    loading="lazy">
            </div>
            <p class="a-help">Amati gambar ini. Waktu pengamatan aktif belum dicatat
                sebagai ketuntasan Materi.</p>
        <?php endif ?>
        <div class="a-alert" role="status">
            <strong>Media dapat dipelajari, tetapi belum dinyatakan selesai otomatis.</strong>
            <?= $sukarela
                ? 'Karena TP sudah Tuntas melalui Diagnostik, kegiatan ini sukarela dan tidak menambah nilai.'
                : 'Urutan wajib berikutnya adalah menyelesaikan media, Kirim Final Resume, lalu Kirim Final HOTS.' ?>
            Fitur pencatatan aktivitas media serta pengiriman tugas resmi akan dibuat pada tahap berikutnya.
        </div>
    </section>

    <section class="a-card a-jarak-atas a-materi-kegiatan" aria-labelledby="petunjuk-kegiatan">
        <h2 id="petunjuk-kegiatan">Petunjuk kegiatan</h2>
        <div class="a-siswa-materi-instruksi">
            <section>
                <h3>Resume Materi <?= (int) $materi['urutan'] ?></h3>
                <p class="a-teks-pratinjau"><?= nl2br(esc($materi['petunjuk_resume'])) ?></p>
            </section>
            <section>
                <h3>HOTS Materi <?= (int) $materi['urutan'] ?></h3>
                <p class="a-teks-pratinjau"><?= nl2br(esc($materi['petunjuk_hots'])) ?></p>
            </section>
        </div>
        <p class="a-help"><?= $sukarela
            ? 'Resume dan HOTS resmi tidak diwajibkan atau dinilai pada jalur lulus Diagnostik.'
            : 'Simpan Draf dan Kirim Final belum tersedia pada S08A; tidak ada pengiriman tugas palsu.' ?></p>
    </section>
    <p class="a-kesiapan-kembali">
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('siswa/tp/' . (int) $tp['id']), 'attr') ?>">
            Kembali ke Rincian TP
        </a>
    </p>
</div>
<?= $this->endSection() ?>
