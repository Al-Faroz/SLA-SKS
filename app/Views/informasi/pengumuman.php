<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-informasi">
<nav class="a-breadcrumb" aria-label="Breadcrumb">
    <a href="<?= esc(site_url($peran),'attr') ?>">Beranda <?= esc($peranAktif) ?></a>
    <span aria-hidden="true">/</span><span aria-current="page">Pengumuman</span>
</nav>
<header class="a-page__heading a-proto-heading">
    <div>
        <p class="a-proto-eyebrow">Informasi · Sesuai Hak Akses</p>
        <h1>Pengumuman</h1>
        <p class="a-proto-muted">Pengumuman untuk akun Anda.</p>
    </div>
    <?php if (in_array($peran,['admin','guru'],true)): ?>
        <a class="a-btn a-btn--sekunder"
           href="<?= esc(site_url($peran.'/pengumuman'),'attr') ?>">Kelola Pengumuman</a>
    <?php endif ?>
</header>
<?php if (!$daftar['data']): ?>
    <section class="a-card a-informasi__kosong" role="status">
        <h2>Belum ada pengumuman</h2>
        <p class="a-help">Belum ada pengumuman Terbit yang ditujukan kepada akun atau Gen Anda.</p>
    </section>
<?php else: ?>
    <p class="a-help" role="status"><?= (int) $daftar['jumlah'] ?> pengumuman sesuai akses Anda.</p>
    <div class="a-informasi__kartu">
        <?php foreach ($daftar['data'] as $item): ?>
            <article class="a-card a-informasi__item">
                <p class="a-proto-eyebrow">Pengumuman · <?= esc((string)($item['diterbitkan_pada'] ?? '')) ?> UTC</p>
                <h2><?= esc($item['judul']) ?></h2>
                <div class="a-informasi__isi"><?= esc($item['isi']) ?></div>
            </article>
        <?php endforeach ?>
    </div>
<?php endif ?>
<?php if ($daftar['total_halaman'] > 1): ?>
    <nav class="a-informasi__pagination" aria-label="Halaman pengumuman">
        <span>Halaman <?= (int)$daftar['halaman'] ?> dari <?= (int)$daftar['total_halaman'] ?></span>
        <?php if ($daftar['halaman'] > 1): ?>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('pengumuman').'?halaman='.($daftar['halaman']-1),'attr') ?>">Sebelumnya</a>
        <?php endif ?>
        <?php if ($daftar['halaman'] < $daftar['total_halaman']): ?>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('pengumuman').'?halaman='.($daftar['halaman']+1),'attr') ?>">Selanjutnya</a>
        <?php endif ?>
    </nav>
<?php endif ?>
</div>
<?= $this->endSection() ?>
