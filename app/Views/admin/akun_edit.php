<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $siswa = $jenis === 'siswa'; $kembali = site_url('admin/' . $jenis); ?>
<nav class="a-breadcrumb" aria-label="Breadcrumb">
    <a href="<?= esc($kembali, 'attr') ?>"><?= esc(ucfirst($jenis)) ?></a>
    <span aria-hidden="true">/</span><span aria-current="page">Ubah identitas</span>
</nav>
<section class="a-card a-akun-edit" aria-labelledby="judul-edit-akun">
    <h1 id="judul-edit-akun">Ubah identitas <?= $siswa ? 'Siswa' : 'Pengelola' ?></h1>
    <p class="a-help">Ubah hanya data resmi yang diperlukan. Setiap perubahan tercatat dengan identitas Admin dan data sebelum/sesudah.</p>

    <?php if ($galat = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>

    <form class="a-form" method="post" action="<?= esc(site_url('admin/' . $jenis . '/' . (int) $target['id'] . '/edit'), 'attr') ?>">
        <?= csrf_field() ?>
        <div class="a-akun-edit__grid">
            <div class="a-field">
                <label class="a-label" for="akun-edit-nama">Nama lengkap</label>
                <input id="akun-edit-nama" name="nama" type="text" required minlength="3" maxlength="150"
                    value="<?= esc($target['nama'], 'attr') ?>" autocomplete="off">
            </div>
            <div class="a-field">
                <label class="a-label" for="akun-edit-username"><?= $siswa ? 'NISN (username login)' : 'Username / NIP' ?></label>
                <input id="akun-edit-username" name="username" type="text" required
                    <?= $siswa ? 'inputmode="numeric" pattern="[0-9]{10}" maxlength="10"' : 'pattern="[A-Za-z0-9_.-]{3,80}" maxlength="80"' ?>
                    value="<?= esc($target['username'], 'attr') ?>" autocomplete="off">
            </div>
        </div>

        <?php if ($siswa): ?>
            <div class="a-akun-edit__grid">
                <div class="a-akun-edit__tetap">
                    <strong>Gen (baca saja)</strong>
                    <?= esc($target['nama_gen'] ?? 'Belum ditempatkan') ?>
                </div>
                <div class="a-akun-edit__tetap">
                    <strong>Semester mulai (baca saja)</strong>
                    <?= $target['siswa']['semester_mulai'] === null ? 'Mengikuti semester bawaan Gen' : 'Semester ' . (int) $target['siswa']['semester_mulai'] ?>
                </div>
            </div>
            <p class="a-help">Perubahan NISN akan memperbarui username siswa dalam transaksi yang sama. Gen, semester, ID siswa, nilai dan histori tidak berubah. Perpindahan Gen siswa yang sudah ditempatkan belum tersedia agar progres tetap aman.</p>
        <?php else: ?>
            <fieldset class="a-fieldset">
                <legend class="a-label">Peran Pengelola</legend>
                <?php foreach (['admin' => 'Admin', 'guru' => 'Guru', 'pimpinan' => 'Pimpinan'] as $kode => $label): ?>
                    <label>
                        <input type="checkbox" name="peran[]" value="<?= esc($kode, 'attr') ?>"
                            <?= in_array($kode, $target['daftar_peran'], true) ? 'checked' : '' ?>>
                        <?= esc($label) ?>
                    </label>
                <?php endforeach ?>
                <span class="a-help">Minimal satu dan maksimal dua peran sesuai kombinasi yang diizinkan. Peran Guru tidak dapat dicabut selama masih mengampu Gen–Mapel. Peran Admin sendiri tidak dapat diubah.</span>
            </fieldset>
        <?php endif ?>

        <div class="a-alert a-alert--info" role="status">Sesi aktif akun ini akan berakhir setelah identitas diperbarui. Akun perlu masuk kembali menggunakan username/NISN terbaru bila diubah.</div>
        <div class="a-akun-edit__footer">
            <button type="submit" class="a-btn a-btn--utama">Simpan perubahan</button>
            <a class="a-btn a-btn--sekunder" href="<?= esc($kembali, 'attr') ?>">Batal</a>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
