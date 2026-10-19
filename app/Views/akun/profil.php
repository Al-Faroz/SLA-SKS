<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<h1>Profil akun</h1>
<?php if (session()->getFlashdata('galat')): ?>
    <div class="a-alert a-alert--bahaya" role="alert"><?= esc(session()->getFlashdata('galat')) ?></div>
<?php endif ?>
<section class="a-card a-baca-panel" aria-labelledby="judul-data">
    <h2 id="judul-data">Identitas akun</h2>
    <dl class="a-rincian">
        <dt>Nama</dt><dd><?= esc($namaPengguna) ?></dd>
        <dt>Username</dt><dd><?= esc($username) ?></dd>
        <dt>Peran aktif</dt><dd><?= esc($peranAktif) ?></dd>
    </dl>
    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('ganti-sandi'), 'attr') ?>">Ganti sandi</a>
</section>
<?php if (count($daftarPeran) > 1): ?>
<section class="a-card a-baca-panel a-jarak-atas" aria-labelledby="judul-peran">
    <h2 id="judul-peran">Ganti peran aktif</h2>
    <p class="a-help">Sistem hanya memberi izin dari peran yang sedang aktif. Pergantian peran memperbarui sesi keamanan.</p>
    <form method="post" action="<?= esc(site_url('peran/ganti'), 'attr') ?>" class="a-form">
        <?= csrf_field() ?>
        <div class="a-field">
            <label class="a-label" for="peran">Peran</label>
            <select id="peran" name="peran" required>
                <?php foreach ($daftarPeran as $peran): ?>
                    <option value="<?= esc($peran, 'attr') ?>" <?= $peran === $peranAktifKode ? 'selected' : '' ?>><?= esc(ucfirst($peran)) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <button type="submit" class="a-btn a-btn--utama">Aktifkan peran</button>
    </form>
</section>
<?php endif ?>
<?= $this->endSection() ?>
