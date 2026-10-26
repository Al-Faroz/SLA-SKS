<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<section class="a-card a-baca">
    <span class="a-badge a-badge--bahaya"><?= (int) ($kodeGalat ?? 500) ?></span>
    <h1><?= esc($judul ?? 'Halaman belum dapat dimuat') ?></h1>
    <p><?= esc($pesan ?? 'Terjadi kendala sistem. Hak akses akun Anda tidak berubah. Silakan hubungi pengelola dan periksa catatan galat aplikasi.') ?></p>
    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru'), 'attr') ?>">Kembali ke Beranda Guru</a>
</section>
<?= $this->endSection() ?>
