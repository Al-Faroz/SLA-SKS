<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
<header class="a-page__heading a-proto-heading">
    <div>
        <p class="a-proto-eyebrow">Beranda · <?= esc($peranAktif) ?></p>
        <h1>Selamat datang, <?= esc($namaPengguna) ?></h1>
        <p class="a-proto-muted">Ruang kerja akun Anda untuk mengakses layanan akademik sesuai peran aktif.</p>
    </div>
</header>
<?php if ($pesan = session()->getFlashdata('galat')): ?>
    <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('sukses')): ?>
    <div class="a-alert" role="status"><?= esc(session()->getFlashdata('sukses')) ?></div>
<?php endif ?>
<section class="a-card a-page__editor" aria-labelledby="judul-status">
    <h2 id="judul-status">Akun Anda sudah aktif</h2>
    <p>Anda sedang menggunakan peran <strong><?= esc($peranAktif) ?></strong>. Sistem memeriksa peran aktif Anda pada server setiap kali membuka halaman yang dilindungi.</p>
    <?php if ($peran === 'guru'): ?>
        <p class="a-help">Peta ATP enam semester sudah tersedia sebagai tampilan baca-saja. Impor ATP, editor Materi, bank soal dan penilaian masih dalam pengembangan.</p>
    <?php else: ?>
        <p class="a-help">Modul pembelajaran, penilaian, dan laporan akan ditambahkan setelah fondasi data masing-masing selesai. Menu yang belum tersedia tidak ditampilkan.</p>
    <?php endif ?>
    <?php if ($peran === 'guru'): ?>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Lihat peta ATP</a>
    <?php else: ?>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('profil'), 'attr') ?>">Lihat profil dan peran</a>
    <?php endif ?>
</section>
</div>
<?= $this->endSection() ?>
