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
    <section class="a-card" aria-labelledby="video-materi">
        <h2 id="video-materi">Video pembelajaran YouTube</h2>
        <p class="a-help">Pratinjau ini hanya untuk Guru yang ditugaskan pada Gen–Mapel ini. Membuka pemutar menghubungi layanan YouTube; tidak menyimpan progres siswa.</p>
        <?php if ((int) $materi['aktif'] !== 1): ?>
            <div class="a-alert" role="status">Materi masih Nonaktif dan tidak ditayangkan kepada siswa.</div>
        <?php endif ?>
        <div class="a-media-video" id="a-video-area">
            <p>Video belum dimuat. Klik tombol untuk menampilkan pemutar dari YouTube.</p>
            <button type="button" class="a-btn a-btn--utama" id="a-video-buka">Tampilkan video</button>
        </div>
        <p><a href="<?= esc($urlAsli, 'attr') ?>" target="_blank" rel="noopener noreferrer">Buka video di YouTube <span aria-hidden="true">↗</span></a></p>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke rincian TP</a>
    </section>
</div>
<script>
(function () {
    'use strict';
    const tombol = document.getElementById('a-video-buka');
    const area = document.getElementById('a-video-area');
    if (!tombol || !area) return;
    tombol.addEventListener('click', function () {
        const video = document.createElement('iframe');
        video.src = <?= json_encode($urlEmbed, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
        video.title = 'Pratinjau video: ' + <?= json_encode($materi['judul'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        video.referrerPolicy = 'no-referrer';
        video.allow = 'encrypted-media; picture-in-picture; fullscreen';
        video.allowFullscreen = true;
        video.loading = 'lazy';
        area.replaceChildren(video);
    }, {once: true});
}());
</script>
<?= $this->endSection() ?>
