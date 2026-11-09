<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
<header class="a-page__heading a-proto-heading">
    <div>
        <p class="a-proto-eyebrow">Beranda · <?= esc($peranAktif) ?></p>
        <h1>Selamat datang, <?= esc($namaPengguna) ?></h1>
        <p class="a-proto-muted">Pilih layanan sesuai peran aktif.</p>
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
        <p class="a-help">Gunakan Peta ATP, Antrean Penilaian, Pra-Semester dan Rekap Nilai untuk menjalankan tugas Guru pada Gen–Mapel yang diampu.</p>
    <?php elseif ($peran === 'siswa'): ?>
        <p class="a-help">Buka Pembelajaran untuk mengikuti TP sesuai urutan, kemudian Laporan Akademik untuk memantau progres dan nilai milik Anda. Hasil Pra-Semester ditampilkan terpisah dari Nilai TP.</p>
    <?php elseif ($peran === 'pimpinan'): ?>
        <p class="a-help">Gunakan Monitoring Akademik untuk meninjau progres TP NonEsensial dan hasil siswa pada Gen–Mapel–Semester yang ditugaskan. Semua laporan bersifat baca-saja.</p>
    <?php else: ?>
        <p class="a-help">Akses layanan akademik tersedia melalui menu sesuai peran aktif.</p>
    <?php endif ?>
    <?php if ($peran === 'guru'): ?>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Lihat peta ATP</a>
    <?php elseif ($peran === 'siswa'): ?>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Lihat mata pelajaran</a>
    <?php elseif ($peran === 'pimpinan'): ?>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('pimpinan/laporan'), 'attr') ?>">Buka Monitoring Akademik</a>
    <?php else: ?>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('profil'), 'attr') ?>">Lihat profil dan peran</a>
    <?php endif ?>
</section>
</div>
<?= $this->endSection() ?>
