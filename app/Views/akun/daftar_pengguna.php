<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
<header class="a-page__heading a-proto-heading">
    <div>
        <p class="a-proto-eyebrow">Admin · Administrasi Akun</p>
        <h1>Pengguna</h1>
        <p class="a-proto-muted">Admin membuat akun secara tertutup. Siswa memakai NISN 10 angka, sedangkan Pengelola dapat memiliki dua peran yang sah.</p>
    </div>
</header>
<?php if (session()->getFlashdata('galat')): ?>
    <div class="a-alert a-alert--bahaya" role="alert"><?= esc(session()->getFlashdata('galat')) ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('sukses')): ?>
    <div class="a-alert" role="status"><?= esc(session()->getFlashdata('sukses')) ?></div>
<?php endif ?>
<section class="a-card a-page__editor" aria-labelledby="judul-buat">
    <h2 id="judul-buat">Buat akun</h2>
    <form method="post" action="<?= esc(site_url('admin/pengguna'), 'attr') ?>" class="a-form">
        <?= csrf_field() ?>
        <div class="a-field">
            <label class="a-label" for="username-baru">Username / NISN</label>
            <input type="text" name="username" id="username-baru" required maxlength="80" autocomplete="off" pattern="[A-Za-z0-9_.-]{3,80}" aria-describedby="bantu-username">
            <span class="a-help" id="bantu-username">Siswa: NISN 10 angka. Staf: NIP atau username resmi.</span>
        </div>
        <div class="a-field">
            <label class="a-label" for="nama-baru">Nama lengkap</label>
            <input type="text" name="nama" id="nama-baru" required maxlength="150" autocomplete="off">
        </div>
        <div class="a-field">
            <label class="a-label" for="sandi-awal">Sandi sementara</label>
            <input type="password" name="sandi" id="sandi-awal" minlength="8" maxlength="200" required autocomplete="new-password" aria-describedby="bantu-sandi">
            <span class="a-help" id="bantu-sandi">Minimal 8 karakter. Berikan secara privat, dan pengguna wajib menggantinya pada login pertama.</span>
        </div>
        <fieldset class="a-field a-fieldset">
            <legend class="a-label">Peran akun</legend>
            <label><input type="checkbox" name="peran[]" value="admin"> Admin</label>
            <label><input type="checkbox" name="peran[]" value="guru"> Guru</label>
            <label><input type="checkbox" name="peran[]" value="siswa"> Siswa</label>
            <label><input type="checkbox" name="peran[]" value="pimpinan"> Pimpinan</label>
            <span class="a-help">Siswa tidak boleh merangkap. Staf maksimal dua peran.</span>
        </fieldset>
        <button type="submit" class="a-btn a-btn--utama">Buat akun</button>
    </form>
</section>
<section class="a-card a-jarak-atas" aria-labelledby="judul-daftar">
    <h2 id="judul-daftar">Akun terdaftar</h2>
    <?php if ($pengguna === []): ?>
        <div class="a-empty" role="status">Belum ada akun pengguna.</div>
    <?php else: ?>
    <div class="a-tabel-gulir"><table class="a-tbl">
        <thead><tr><th scope="col">Nama</th><th scope="col">Username</th><th scope="col">Peran</th><th scope="col">Status</th><th scope="col">Sandi</th></tr></thead>
        <tbody>
        <?php foreach ($pengguna as $akun): ?>
            <tr><td><?= esc($akun['nama']) ?></td><td><?= esc($akun['username']) ?></td><td><?= esc(implode(', ', $akun['daftar_peran'])) ?></td>
                <td><?= esc(ucfirst($akun['status'])) ?></td>
                <td><?= (int) $akun['wajib_ganti_sandi'] !== 0 ? 'Wajib diganti' : 'Sudah diganti' ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table></div>
    <?php endif ?>
</section>
</div>
<?= $this->endSection() ?>
