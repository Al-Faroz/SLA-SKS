<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<p class="a-kicker">SLA-SKS · <?= esc($peranAktif) ?></p>
<h1>Selamat datang, <?= esc($namaPengguna) ?></h1>
<?php if (session()->getFlashdata('sukses')): ?>
    <div class="a-alert" role="status"><?= esc(session()->getFlashdata('sukses')) ?></div>
<?php endif ?>
<section class="a-card a-baca-panel" aria-labelledby="judul-status">
    <h2 id="judul-status">Akun Anda sudah aktif</h2>
    <p>Anda sedang menggunakan peran <strong><?= esc($peranAktif) ?></strong>. Sistem memeriksa peran aktif Anda pada server setiap kali membuka halaman yang dilindungi.</p>
    <p class="a-help">Modul pembelajaran, penilaian, dan laporan akan ditambahkan setelah fondasi data masing-masing selesai. Menu yang belum tersedia tidak ditampilkan.</p>
    <a class="a-btn a-btn--utama" href="<?= esc(site_url('profil'), 'attr') ?>">Lihat profil dan peran</a>
</section>
<?= $this->endSection() ?>
