<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Rincian TP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Pratinjau media</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Pratinjau Media</p>
            <h1><?= esc($materi['judul']) ?></h1>
            <p class="a-proto-muted"><?= esc($tp['kode_tp'] . ' — ' . $tp['judul']) ?></p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="materi-pratinjau-heading">
        <h2 id="materi-pratinjau-heading">
            <?= esc(match ($materi['jenis']) {
                'youtube' => 'Video pembelajaran YouTube',
                'pdf' => 'Dokumen pembelajaran PDF',
                'audio' => 'Audio pembelajaran',
                'gambar' => 'Gambar pembelajaran',
                default => 'Media pembelajaran',
            }) ?>
        </h2>
        <p class="a-help">Pratinjau hanya untuk Guru yang ditugaskan pada Gen–Mapel ini. Halaman ini tidak mengubah progres siswa.</p>
        <?php if ((int) $materi['aktif'] !== 1): ?>
            <div class="a-alert" role="status">Materi masih Nonaktif dan belum ditayangkan kepada siswa.</div>
        <?php endif ?>
        <?php if ($materi['jenis'] === 'youtube'): ?>
            <details class="a-media-details">
                <summary class="a-btn a-btn--utama">Tampilkan video</summary>
                <div class="a-media-video">
                    <iframe src="<?= esc($urlEmbed, 'attr') ?>"
                        title="Pratinjau video: <?= esc($materi['judul'], 'attr') ?>"
                        loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                        allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
                </div>
            </details>
            <p class="a-help">Jika video tidak dapat diputar di sini, buka melalui YouTube.</p>
            <p><a href="<?= esc($urlAsli, 'attr') ?>" target="_blank" rel="noopener noreferrer">Buka video di YouTube ↗</a></p>
        <?php elseif ($materi['jenis'] === 'pdf'): ?>
            <details class="a-media-details">
                <summary class="a-btn a-btn--utama">Tampilkan PDF</summary>
                <div class="a-materi-pdf">
                    <iframe src="<?= esc($urlMedia, 'attr') ?>#toolbar=1"
                        title="Pratinjau PDF: <?= esc($materi['judul'], 'attr') ?>" loading="lazy"></iframe>
                </div>
            </details>
            <p class="a-help">Pembaca PDF mengikuti kemampuan browser. Apabila tidak tersedia, gunakan tautan buka dokumen.</p>
            <p><a href="<?= esc($urlMedia, 'attr') ?>" target="_blank" rel="noopener noreferrer">Buka PDF di tab baru ↗</a></p>
        <?php elseif ($materi['jenis'] === 'audio'): ?>
            <div class="a-materi-audio">
                <audio controls preload="metadata" src="<?= esc($urlMedia, 'attr') ?>" aria-label="Pemutar audio Materi">
                    Browser Anda tidak mendukung pemutar audio.
                </audio>
            </div>
            <p class="a-help">Audio diputar dari penyimpanan privat melalui layanan Guru, dengan dukungan pemindahan posisi pemutaran.</p>
        <?php elseif ($materi['jenis'] === 'gambar'): ?>
            <div class="a-materi-gambar">
                <img src="<?= esc($urlMedia, 'attr') ?>" alt="Gambar materi <?= esc($materi['judul'], 'attr') ?>" loading="lazy">
            </div>
        <?php endif ?>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke rincian TP</a>
    </section>
</div>
<?= $this->endSection() ?>
