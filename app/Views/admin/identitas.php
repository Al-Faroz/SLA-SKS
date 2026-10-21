<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
<nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url('admin'), 'attr') ?>">Beranda Admin</a><span aria-hidden="true">/</span><span aria-current="page">Identitas Madrasah</span></nav>
<header class="a-page__heading a-proto-heading">
    <div>
        <p class="a-proto-eyebrow">Admin · Pengaturan</p>
        <h1>Identitas Madrasah</h1>
        <p class="a-proto-muted">Lengkapi informasi resmi madrasah untuk administrasi dan sertifikat. Setiap perubahan menyimpan riwayat; tidak ada data contoh yang digunakan sebagai identitas resmi.</p>
    </div>
</header>
<?php if ($pesan = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
<?php if ($pesan = session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
<?php if (in_array('', $identitas, true)): ?><p class="a-alert a-alert--info" role="status">Data resmi belum lengkap. Isilah seluruh kolom wajib sebelum menyimpan.</p><?php endif ?>
<section class="a-card a-page__editor a-page__editor--luas" aria-labelledby="judul-form-identitas">
<h2 id="judul-form-identitas">Form identitas resmi</h2>
<form method="post" action="<?= esc(site_url('admin/identitas'), 'attr') ?>" class="a-form" data-dirty-form>
<?= csrf_field() ?>
<?php foreach ($labelIdentitas as $kode => $label): ?>
<?php
    $nilai = old($kode, $identitas[$kode] ?? '');
    $opsional = $kode === 'website';
    $tipe = $kode === 'email' ? 'email' : ($kode === 'website' ? 'url' : 'text');
    $panjang = $kode === 'alamat_madrasah' ? 1000 : 200;
?>
<div class="a-field">
    <label class="a-label" for="i-<?= esc($kode, 'attr') ?>"><?= esc($label) ?><?= $opsional ? ' (opsional)' : '' ?></label>
    <?php if ($kode === 'alamat_madrasah'): ?>
        <textarea id="i-<?= esc($kode, 'attr') ?>" name="<?= esc($kode, 'attr') ?>" rows="3" maxlength="<?= $panjang ?>" <?= $opsional ? '' : 'required' ?>><?= esc($nilai) ?></textarea>
    <?php else: ?>
        <input id="i-<?= esc($kode, 'attr') ?>" name="<?= esc($kode, 'attr') ?>" type="<?= $tipe ?>" value="<?= esc($nilai, 'attr') ?>" maxlength="<?= $panjang ?>" <?= $opsional ? '' : 'required' ?>
        <?= $kode === 'npsn' ? 'inputmode="numeric" pattern="[0-9]{8}" title="8 angka"' : '' ?>
        <?= $kode === 'nsm' ? 'inputmode="numeric" pattern="[0-9]{12}" title="12 angka"' : '' ?>>
    <?php endif ?>
</div>
<?php endforeach ?>
<p class="a-help" data-dirty-status role="status">Ubah data yang diperlukan, lalu simpan perubahan.</p>
<button class="a-btn a-btn--utama" type="submit" data-dirty-submit>Simpan identitas</button>
</form>
</section>
</div>
<?= $this->endSection() ?>
