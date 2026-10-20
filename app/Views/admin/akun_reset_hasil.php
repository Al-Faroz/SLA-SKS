<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<section class="a-card a-baca-panel a-akun-reset" aria-labelledby="judul-hasil">
    <h1 id="judul-hasil">Reset sandi berhasil</h1>
    <div class="a-alert" role="status">Sandi lama dinonaktifkan, semua sesi sebelumnya dibatalkan, dan pengguna wajib mengganti sandi pada login berikutnya.</div>
    <p>Salin sandi sementara akun <strong><?= esc($target['nama']) ?></strong> (<?= esc($target['username']) ?>). Sandi ini hanya muncul pada respons sekarang dan tidak dapat dilihat kembali melalui halaman daftar.</p>
    <div class="a-akun-reset__rahasia">
        <label class="a-label" for="sandi-baru-sekali">Sandi sementara (tampil sekali)</label>
        <input id="sandi-baru-sekali" type="text" readonly value="<?= esc($sandiSementara, 'attr') ?>" autocomplete="off" spellcheck="false">
        <button type="button" class="a-btn a-btn--sekunder" data-salin-sandi>Salin sandi sementara</button>
        <p class="a-help" data-status-salin role="status"></p>
    </div>
    <p class="a-help">Jangan membagikan sandi melalui grup publik, URL, atau screenshot. Halaman ini tidak disimpan pada flash session.</p>
    <a class="a-btn a-btn--utama" href="<?= esc(site_url('admin/' . $jenis), 'attr') ?>">Kembali ke daftar <?= esc(ucfirst($jenis)) ?></a>
</section>
<?= $this->endSection() ?>
