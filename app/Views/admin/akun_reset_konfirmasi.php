<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url('admin/' . $jenis), 'attr') ?>"><?= esc(ucfirst($jenis)) ?></a><span aria-hidden="true">/</span><span aria-current="page">Reset sandi</span></nav>
<section class="a-card a-baca-panel a-akun-reset" aria-labelledby="judul-reset">
    <h1 id="judul-reset">Konfirmasi reset sandi</h1>
    <p>Periksa akun tujuan. Tindakan ini membatalkan seluruh sesi lama dan mewajibkan pengguna mengganti sandi saat login berikutnya.</p>
    <dl class="a-rincian">
        <dt>Nama pengguna</dt><dd><?= esc($target['nama']) ?></dd>
        <dt>Username / NISN</dt><dd><?= esc($target['username']) ?></dd>
        <dt>Status</dt><dd><?= esc(ucfirst($target['status'])) ?></dd>
    </dl>
    <?php if ($akunSendiri): ?>
        <div class="a-alert a-alert--info" role="status">Untuk akun Admin sendiri, gunakan menu Ganti Sandi. Reset mandiri tidak tersedia.</div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('ganti-sandi'), 'attr') ?>">Ganti sandi sendiri</a>
    <?php else: ?>
        <div class="a-alert a-alert--info">Sandi sementara akan ditampilkan <strong>satu kali saja</strong> setelah reset berhasil. Salin dan sampaikan secara privat kepada pemilik akun.</div>
        <form class="a-form a-akun-reset__aksi" method="post" action="<?= esc(site_url('admin/' . $jenis . '/' . (int) $target['id'] . '/reset-sandi'), 'attr') ?>">
            <?= csrf_field() ?>
            <button class="a-btn a-btn--bahaya" type="submit">Ya, reset sandi akun ini</button>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/' . $jenis), 'attr') ?>">Batal</a>
        </form>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
