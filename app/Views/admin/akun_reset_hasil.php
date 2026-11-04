<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
<section class="a-card a-baca-panel a-akun-reset" aria-labelledby="judul-hasil">
    <h1 id="judul-hasil">Reset sandi berhasil</h1>
    <?php if ($modeReset === 'acak'): ?>
        <div class="a-alert" role="status">Sandi sebelumnya dinonaktifkan, seluruh sesi lama dibatalkan, dan akun Admin wajib mengganti sandi ketika masuk berikutnya.</div>
        <p>Salin sandi sementara acak untuk akun <strong><?= esc($target['nama']) ?></strong> (<?= esc($target['username']) ?>). Sandi ini hanya muncul sekali.</p>
        <div class="a-akun-reset__rahasia">
            <label class="a-label" for="sandi-baru-sekali">Sandi sementara Admin (tampil sekali)</label>
            <input id="sandi-baru-sekali" type="text" readonly value="<?= esc((string)$sandiSementara, 'attr') ?>" autocomplete="off" spellcheck="false">
            <button type="button" class="a-btn a-btn--sekunder" data-salin-sandi>Salin sandi sementara</button>
            <p class="a-help" data-status-salin role="status"></p>
        </div>
    <?php else: ?>
        <div class="a-alert" role="status">Akun <strong><?= esc($target['nama']) ?></strong> berhasil direset ke sandi default yang Anda masukkan. Semua sesi lama dibatalkan.</div>
        <p>Pengguna dapat langsung masuk dengan sandi default tersebut. Penggantian sandi melalui menu Profil <strong>disarankan, tidak dipaksa</strong>.</p>
    <?php endif ?>
    <p class="a-help">Jangan membagikan sandi melalui grup publik, URL, log, atau tangkapan layar. Sistem hanya menyimpan hash sandi.</p>
    <a class="a-btn a-btn--utama" href="<?= esc(site_url('admin/' . $jenis), 'attr') ?>">Kembali ke daftar <?= esc(ucfirst($jenis)) ?></a>
</section>
</div>
<?= $this->endSection() ?>
