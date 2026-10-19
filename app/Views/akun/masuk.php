<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<div class="a-halaman-awal">
    <section class="a-intro" aria-labelledby="judul-halaman">
        <span class="a-kicker">Layanan Akademik · Sistem Kredit Semester</span>
        <h1 id="judul-halaman">Belajar mandiri, terarah, dan bertahap.</h1>
        <p>Masuk menggunakan akun yang disiapkan madrasah untuk mengakses pembelajaran dan layanan akademik.</p>
        <p class="a-help">Akun baru hanya dibuat Admin. Pendaftaran publik tidak tersedia.</p>
    </section>
    <section class="a-card a-akses" aria-labelledby="judul-masuk">
        <h2 id="judul-masuk">Masuk SLA-SKS</h2>
        <?php if (session()->getFlashdata('galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc(session()->getFlashdata('galat')) ?></div>
        <?php endif ?>
        <?php if (session()->getFlashdata('sukses')): ?>
            <div class="a-alert" role="status"><?= esc(session()->getFlashdata('sukses')) ?></div>
        <?php endif ?>
        <form method="post" action="<?= esc(site_url('masuk'), 'attr') ?>" class="a-form">
            <?= csrf_field() ?>
            <div class="a-field">
                <label class="a-label" for="username">Username / NISN</label>
                <input id="username" name="username" type="text" autocomplete="username" maxlength="80" required autofocus>
            </div>
            <div class="a-field">
                <label class="a-label" for="sandi">Sandi</label>
                <input id="sandi" name="sandi" type="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="a-btn a-btn--utama">Masuk</button>
        </form>
        <p class="a-help">Jika lupa sandi atau akun terkunci, hubungi Admin madrasah.</p>
        <a href="<?= esc(site_url('kebijakan-privasi'), 'attr') ?>">Kebijakan Privasi</a>
    </section>
</div>
<?= $this->endSection() ?>
