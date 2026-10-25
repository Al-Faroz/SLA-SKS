<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<section class="a-card a-baca">
    <span class="a-badge a-badge--bahaya">403</span>
    <h1>Akses ditolak</h1>
    <p><?= esc($pesan ?? 'Akun atau peran aktif Anda tidak memiliki izin membuka halaman ini.') ?></p>
    <a class="a-btn a-btn--sekunder"
       href="<?= esc(site_url($tautanPath ?? 'masuk'), 'attr') ?>"><?= esc($labelTautan ?? 'Kembali') ?></a>
</section>
<?= $this->endSection() ?>
