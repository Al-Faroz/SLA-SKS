<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
<nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url('admin/' . $jenis), 'attr') ?>"><?= esc(ucfirst($jenis)) ?></a><span aria-hidden="true">/</span><span aria-current="page">Reset sandi</span></nav>
<section class="a-card a-baca-panel a-akun-reset" aria-labelledby="judul-reset">
    <h1 id="judul-reset">Konfirmasi reset sandi</h1>
    <p>Reset hanya berlaku untuk akun yang dipilih. Semua sesi lama dibatalkan dan aktivitas reset dicatat tanpa menyimpan kata sandi.</p>
    <dl class="a-rincian">
        <dt>Nama pengguna</dt><dd><?= esc($target['nama']) ?></dd>
        <dt>Username / NISN</dt><dd><?= esc($target['username']) ?></dd>
        <dt>Status</dt><dd><?= esc(ucfirst($target['status'])) ?></dd>
    </dl>
    <?php if ($akunSendiri): ?>
        <div class="a-alert a-alert--info" role="status">Untuk akun Admin sendiri, gunakan Ganti Sandi. Reset mandiri tidak tersedia.</div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('ganti-sandi'), 'attr') ?>">Ganti sandi sendiri</a>
    <?php else: ?>
        <?php if ($akunAdmin): ?>
            <div class="a-alert a-alert--info" role="status">Akun yang memiliki peran Admin memperoleh sandi sementara <strong>acak</strong>, hanya ditampilkan sekali setelah reset. Admin wajib menggantinya saat masuk kembali, termasuk akun dengan peran rangkap Admin.</div>
        <?php else: ?>
            <div class="a-alert a-alert--info" role="status">Guru, Pimpinan, dan Siswa menggunakan sandi <strong>default yang Anda tetapkan di bawah</strong>, bukan sandi acak. Pengguna boleh langsung masuk dan dianjurkan mengganti sandi melalui Profil. Sandi awal lama tidak dapat dipulihkan dari hash.</div>
        <?php endif ?>
        <form class="a-form a-akun-reset__aksi" method="post" action="<?= esc(site_url('admin/' . $jenis . '/' . (int) $target['id'] . '/reset-sandi'), 'attr') ?>">
            <?= csrf_field() ?>
            <?php if (!$akunAdmin): ?>
                <div class="a-field">
                    <label class="a-label" for="sandi-default">Sandi default akun (8–200 karakter)</label>
                    <input id="sandi-default" name="sandi_default" type="password" minlength="8" maxlength="200" autocomplete="new-password" required>
                </div>
                <div class="a-field">
                    <label class="a-label" for="sandi-default-konfirmasi">Ulangi sandi default</label>
                    <input id="sandi-default-konfirmasi" name="sandi_default_konfirmasi" type="password" minlength="8" maxlength="200" autocomplete="new-password" required>
                </div>
                <p class="a-help">Gunakan sandi default resmi yang berlaku di madrasah. Jangan gunakan identitas siswa yang mudah ditebak sebagai sandi.</p>
            <?php endif ?>
            <button class="a-btn a-btn--bahaya" type="submit"><?= $akunAdmin ? 'Ya, buat sandi acak Admin' : 'Ya, reset ke sandi default' ?></button>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/' . $jenis), 'attr') ?>">Batal</a>
        </form>
    <?php endif ?>
</section>
</div>
<?= $this->endSection() ?>
