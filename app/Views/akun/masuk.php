<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<div class="a-clean-login">
    <section class="a-card a-akses a-clean-login__card" aria-labelledby="judul-masuk">
        <div class="a-clean-login__brand">
            <img src="<?= esc(base_url('public/assets/img/branding/logo-mtsn4-jombang.webp'), 'attr') ?>"
                 alt="Logo MTsN 4 Jombang" width="96" height="95">
            <h1 id="judul-masuk">SLA-SKS</h1>
            <p>MTsN 4 Jombang</p>
        </div>
        <?php if ($galat = session()->getFlashdata('galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
        <?php endif ?>
        <?php if ($sukses = session()->getFlashdata('sukses')): ?>
            <div class="a-alert" role="status"><?= esc($sukses) ?></div>
        <?php endif ?>
        <form method="post" action="<?= esc(site_url('masuk'), 'attr') ?>" class="a-form a-clean-login__form">
            <?= csrf_field() ?>
            <div class="a-field">
                <label class="a-label" for="username">Username / NISN</label>
                <input id="username" name="username" type="text" autocomplete="username" maxlength="80"
                       placeholder="Username / NISN" required autofocus>
            </div>
            <div class="a-field">
                <label class="a-label" for="sandi">Kata Sandi</label>
                <input id="sandi" name="sandi" type="password" autocomplete="current-password"
                       placeholder="Kata sandi" required>
            </div>
            <button type="submit" class="a-btn a-btn--utama">Masuk</button>
        </form>
        <div class="a-clean-login__foot">
            <span>Butuh akses? Hubungi Admin.</span>
            <a href="<?= esc(site_url('kebijakan-privasi'), 'attr') ?>">Kebijakan Privasi</a>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
