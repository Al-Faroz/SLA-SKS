<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<section class="a-card a-baca" aria-labelledby="judul-sandi">
    <?php if ($wajib): ?><span class="a-badge a-badge--tunggu">Wajib pada login pertama</span><?php endif ?>
    <h1 id="judul-sandi">Ganti sandi</h1>
    <p><?= $wajib ? 'Untuk keamanan akun, buat sandi baru sebelum menggunakan menu aplikasi.' : 'Perbarui sandi akun Anda.' ?></p>
    <?php if (session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc(session()->getFlashdata('galat')) ?></div>
    <?php endif ?>
    <form method="post" action="<?= esc(site_url('ganti-sandi'), 'attr') ?>" class="a-form">
        <?= csrf_field() ?>
        <div class="a-field">
            <label class="a-label" for="sandi-lama">Sandi saat ini</label>
            <input id="sandi-lama" name="sandi_lama" type="password" autocomplete="current-password" required>
        </div>
        <div class="a-field">
            <label class="a-label" for="sandi-baru">Sandi baru (minimal 8 karakter)</label>
            <input id="sandi-baru" name="sandi_baru" type="password" autocomplete="new-password" minlength="8" maxlength="200" required>
        </div>
        <div class="a-field">
            <label class="a-label" for="sandi-konfirmasi">Ulangi sandi baru</label>
            <input id="sandi-konfirmasi" name="sandi_konfirmasi" type="password" autocomplete="new-password" minlength="8" maxlength="200" required>
        </div>
        <button class="a-btn a-btn--utama" type="submit">Simpan sandi baru</button>
    </form>
    <form method="post" action="<?= esc(site_url('keluar'), 'attr') ?>" class="a-form">
        <?= csrf_field() ?>
        <button type="submit" class="a-btn a-btn--sekunder">Keluar tanpa mengganti sandi</button>
    </form>
</section>
<?= $this->endSection() ?>
