<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<section class="a-card a-baca" aria-labelledby="judul-404">
    <span class="a-badge a-badge--bahaya">404</span>
    <h1 id="judul-404">Halaman tidak ditemukan</h1>
    <p>Alamat yang Anda buka tidak tersedia. Periksa kembali tautannya atau kembali ke halaman awal.</p>
    <a class="a-btn a-btn--utama" href="<?= esc(site_url('masuk'), 'attr') ?>">Kembali ke beranda</a>
</section>
<?= $this->endSection() ?>
