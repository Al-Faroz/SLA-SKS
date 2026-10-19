<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<section class="a-card a-baca">
    <span class="a-badge a-badge--bahaya">403</span>
    <h1>Akses ditolak</h1>
    <p>Akun atau peran aktif Anda tidak memiliki izin untuk membuka halaman tersebut. Kembali ke halaman akun yang tersedia.</p>
    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('masuk'), 'attr') ?>">Kembali</a>
</section>
<?= $this->endSection() ?>
