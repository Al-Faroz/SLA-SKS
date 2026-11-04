<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<div class="a-halaman-awal a-proto-login">
    <section class="a-proto-login-hero" aria-labelledby="judul-halaman">
        <div class="a-proto-eyebrow"><span class="a-proto-dot" aria-hidden="true"></span> LAYANAN PEMBELAJARAN DIGITAL MADRASAH</div>
        <h1 id="judul-halaman">Pembelajaran terarah, <span>capaian terukur.</span></h1>
        <p class="a-proto-login-lead">SLA-SKS mendukung pembelajaran mandiri siswa MTs Fase D melalui materi, asesmen bertahap, penilaian, dan pemantauan capaian pembelajaran selama enam semester.</p>

        <figure class="a-proto-login-illustration a-proto-login-illustration--madrasah">
            <img src="<?= esc(base_url('public/assets/img/branding/login-banner-mtsn4-jombang.webp'), 'attr') ?>"
                 alt="Identitas MTsN 4 Jombang dengan elemen teknologi pendidikan berwarna hijau"
                 width="800" height="298" decoding="async" fetchpriority="high">
        </figure>

        <div class="a-proto-login-caption">
            <span class="a-proto-caption-line" aria-hidden="true"></span>
            Portal akademik MTsN 4 Jombang untuk Siswa, Guru, Admin, dan Pimpinan. Akses dikelola oleh madrasah.
        </div>
    </section>

    <section class="a-card a-akses a-proto-login-card" aria-labelledby="judul-masuk">
        <div class="a-proto-login-icon a-proto-login-icon--madrasah" aria-hidden="true">
            <img src="<?= esc(base_url('public/assets/img/branding/logo-mtsn4-jombang.webp'), 'attr') ?>" alt="" width="128" height="127">
        </div>
        <span class="a-proto-login-label">PORTAL AKADEMIK · MTsN 4 JOMBANG</span>
        <h2 id="judul-masuk">Masuk ke SLA-SKS</h2>
        <p class="a-proto-login-sub">Gunakan akun madrasah untuk mengakses layanan pembelajaran sesuai peran Anda.</p>

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
            <button type="submit" class="a-btn a-btn--utama">Masuk ke Sistem <span aria-hidden="true">→</span></button>
        </form>
        <p class="a-proto-login-help">Memerlukan bantuan akses atau pemulihan sandi? Hubungi pengelola akun madrasah.</p>
        <div class="a-proto-login-foot">
            <span>Hanya untuk pengguna yang diberi kewenangan.</span>
            <a href="<?= esc(site_url('kebijakan-privasi'), 'attr') ?>">Kebijakan Privasi</a>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
