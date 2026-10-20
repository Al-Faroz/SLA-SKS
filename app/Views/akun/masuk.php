<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<div class="a-halaman-awal a-proto-login">
    <section class="a-proto-login-hero" aria-labelledby="judul-halaman">
        <div class="a-proto-eyebrow"><span class="a-proto-dot" aria-hidden="true"></span> PLATFORM PEMBELAJARAN MANDIRI</div>
        <h1 id="judul-halaman">Belajar dengan arah yang <span>lebih jelas.</span></h1>
        <p class="a-proto-login-lead">Sistem Layanan Akademik – Sistem Kredit Semester untuk pembelajaran terstruktur siswa MTs Fase D.</p>

        <div class="a-proto-login-illustration" aria-hidden="true">
            <span class="a-proto-orbit a-proto-orbit--one"></span>
            <span class="a-proto-orbit a-proto-orbit--two"></span>
            <span class="a-proto-illustration-card a-proto-illustration-card--main">
                <span class="a-proto-book-icon">▤</span>
                <span class="a-proto-illustration-lines"><i></i><i></i><i></i></span>
            </span>
            <span class="a-proto-illustration-satellite a-proto-illustration-satellite--a">✓</span>
            <span class="a-proto-illustration-satellite a-proto-illustration-satellite--b">✦</span>
        </div>

        <div class="a-proto-login-caption">
            <span class="a-proto-caption-line" aria-hidden="true"></span>
            Akun disiapkan oleh madrasah. Tidak ada pendaftaran publik.
        </div>
    </section>

    <section class="a-card a-akses a-proto-login-card" aria-labelledby="judul-masuk">
        <div class="a-proto-login-icon" aria-hidden="true">S</div>
        <span class="a-proto-login-label">SELAMAT DATANG</span>
        <h2 id="judul-masuk">Masuk SLA-SKS</h2>
        <p class="a-proto-login-sub">Gunakan akun resmi Anda untuk melanjutkan.</p>

        <?php if ($galat = session()->getFlashdata('galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
        <?php endif ?>
        <?php if ($sukses = session()->getFlashdata('sukses')): ?>
            <div class="a-alert" role="status"><?= esc($sukses) ?></div>
        <?php endif ?>

        <form method="post" action="<?= esc(site_url('masuk'), 'attr') ?>" class="a-form a-proto-login-form">
            <?= csrf_field() ?>
            <div class="a-field">
                <label class="a-label" for="username">Username / NISN</label>
                <input id="username" name="username" type="text" autocomplete="username" maxlength="80" placeholder="Masukkan username atau NISN" required autofocus>
            </div>
            <div class="a-field">
                <label class="a-label" for="sandi">Sandi</label>
                <input id="sandi" name="sandi" type="password" autocomplete="current-password" placeholder="Masukkan sandi" required>
            </div>
            <button type="submit" class="a-btn a-btn--utama">Masuk ke akun <span aria-hidden="true">→</span></button>
        </form>
        <p class="a-proto-login-help">Lupa sandi atau akun terkunci? Hubungi Admin madrasah.</p>
        <div class="a-proto-login-foot">
            <span>Akses dilindungi untuk pengguna terdaftar.</span>
            <a href="<?= esc(site_url('kebijakan-privasi'), 'attr') ?>">Kebijakan Privasi</a>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
