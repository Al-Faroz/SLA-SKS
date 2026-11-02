<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin'), 'attr') ?>">Beranda Admin</a>
        <span aria-hidden="true">/</span><span aria-current="page">Pengaturan KKM</span>
    </nav>
    <header class="a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Admin · Pengaturan Ujian</p>
            <h1>KKM Default</h1>
            <p class="a-proto-muted">Nilai batas yang dipakai TP NonEsensial jika Guru belum menetapkan KKM khusus.</p>
        </div>
    </header>
    <section class="a-card a-kkm-admin" aria-labelledby="judul-kkm-admin">
        <h2 id="judul-kkm-admin">Pengaturan batas kelulusan</h2>
        <?php if ($pesan = session()->getFlashdata('galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php if ($pesan = session()->getFlashdata('sukses')): ?>
            <div class="a-alert" role="status"><?= esc($pesan) ?></div>
        <?php endif ?>
        <p>KKM saat ini: <strong><?= $kkm_default === null ? 'Belum ditetapkan' : (int) $kkm_default ?></strong></p>
        <form method="post" action="<?= esc(site_url('admin/kkm'), 'attr') ?>" class="a-form">
            <?= csrf_field() ?>
            <div class="a-field">
                <label for="kkm-default" class="a-label">KKM default Admin (1–100)</label>
                <input class="a-input" type="number" inputmode="numeric" id="kkm-default" name="kkm_default"
                       min="1" max="100" step="1" value="<?= esc((string) old('kkm_default', $kkm_default ?? ''), 'attr') ?>"
                       placeholder="Isi KKM default">
                <p class="a-help">Tidak ada nilai bawaan yang dipaksakan. Kosongkan dan simpan jika pengaturan belum akan ditetapkan.</p>
            </div>
            <button class="a-btn a-btn--utama" type="submit">Simpan KKM Default</button>
        </form>
        <p class="a-help a-jarak-atas">Perubahan berlaku untuk TP yang masih draf dan mengikuti default Admin; KKM khusus Guru tidak ditimpa. TP yang sudah Siap tetap menggunakan KKM pada snapshot terbitannya.</p>
    </section>
</div>
<?= $this->endSection() ?>
