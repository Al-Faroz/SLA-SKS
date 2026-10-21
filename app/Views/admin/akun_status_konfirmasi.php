<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
<?php
    $aktif = $target['status'] === 'aktif';
    $statusBaru = $aktif ? 'nonaktif' : 'aktif';
    $namaAksi = $aktif ? 'Nonaktifkan' : 'Aktifkan kembali';
?>
<nav class="a-breadcrumb" aria-label="Breadcrumb">
    <a href="<?= esc(site_url('admin/' . $jenis), 'attr') ?>"><?= esc(ucfirst($jenis)) ?></a>
    <span aria-hidden="true">/</span><span aria-current="page">Status akun</span>
</nav>
<section class="a-card a-baca-panel a-akun-reset" aria-labelledby="judul-status">
    <h1 id="judul-status"><?= esc($namaAksi) ?> akun?</h1>
    <p>Pastikan akun yang dituju sudah benar. Proses ini tidak menghapus biodata, penugasan, riwayat nilai atau data siswa.</p>
    <?php if ($galat = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>
    <dl class="a-rincian">
        <dt>Nama</dt><dd><?= esc($target['nama']) ?></dd>
        <dt>Username / NISN</dt><dd><?= esc($target['username']) ?></dd>
        <dt>Status saat ini</dt><dd><?= esc(ucfirst($target['status'])) ?></dd>
        <dt>Perubahan</dt><dd><?= esc(ucfirst($statusBaru)) ?></dd>
    </dl>
    <?php if ($aktif): ?>
        <div class="a-alert a-alert--info">Akun yang dinonaktifkan tidak dapat login. Sesi yang sudah terbuka akan dibatalkan. Admin aktif terakhir dan Guru yang masih ditugaskan pada Gen–Mapel dilindungi.</div>
    <?php else: ?>
        <div class="a-alert a-alert--info">Pengaktifan kembali mempertahankan hash sandi lama dan catatan histori. Jika pemilik lupa sandi, gunakan menu Reset sandi per akun.</div>
    <?php endif ?>
    <?php if ($akunSendiri && $aktif): ?>
        <div class="a-alert a-alert--bahaya" role="alert">Anda tidak dapat menonaktifkan akun Admin yang sedang digunakan.</div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/' . $jenis), 'attr') ?>">Kembali</a>
    <?php else: ?>
        <form class="a-akun-reset__aksi" method="post"
            action="<?= esc(site_url('admin/' . $jenis . '/' . (int) $target['id'] . '/status'), 'attr') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="<?= esc($statusBaru, 'attr') ?>">
            <button type="submit" class="a-btn <?= $aktif ? 'a-btn--bahaya' : 'a-btn--utama' ?>"><?= esc($namaAksi) ?> akun ini</button>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/' . $jenis), 'attr') ?>">Batal</a>
        </form>
    <?php endif ?>
</section>
</div>
<?= $this->endSection() ?>
