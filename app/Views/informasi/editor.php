<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
    $nama = $jenis === 'faq' ? 'FAQ resmi' : 'Pengumuman';
    $baru = $baris === null;
    $kunciJudul = $jenis === 'faq' ? 'pertanyaan' : 'judul';
    $kunciIsi = $jenis === 'faq' ? 'jawaban' : 'isi';
    $status = old('status', $baris['status'] ?? 'draf');
?>
<div class="a-page a-informasi">
<nav class="a-breadcrumb" aria-label="Breadcrumb">
    <a href="<?= esc(site_url($peran),'attr') ?>">Beranda <?= esc($peranAktif) ?></a>
    <span aria-hidden="true">/</span>
    <a href="<?= esc(site_url($prefix),'attr') ?>">Kelola <?= esc($nama) ?></a>
    <span aria-hidden="true">/</span><span aria-current="page"><?= $baru ? 'Tambah' : 'Sunting' ?></span>
</nav>
<header class="a-page__heading a-proto-heading">
    <div>
        <p class="a-proto-eyebrow">Informasi · Editor</p>
        <h1><?= $baru ? 'Tambah' : 'Sunting' ?> <?= esc($nama) ?></h1>
        <p class="a-proto-muted">Simpan sebagai Draf untuk menyiapkan isi, atau Terbit untuk menampilkan kepada semua akun login. Semua perubahan tercatat di audit.</p>
    </div>
    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($prefix),'attr') ?>">Kembali ke daftar</a>
</header>
<?php if ($pesan=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
<section class="a-card a-page__editor a-page__editor--luas a-informasi__editor">
    <h2>Isi <?= esc($nama) ?></h2>
    <form class="a-form" method="post" data-dirty-form
          action="<?= esc(site_url($prefix.($baru?'':'/'.(int)$baris['id'].'/sunting')),'attr') ?>">
        <?= csrf_field() ?>
        <?php if (!$baru): ?><input type="hidden" name="versi" value="<?= (int)$baris['versi'] ?>"><?php endif ?>
        <div class="a-field">
            <label class="a-label" for="info-judul"><?= $jenis === 'faq' ? 'Pertanyaan' : 'Judul pengumuman' ?></label>
            <input id="info-judul" name="<?= esc($kunciJudul,'attr') ?>" type="text"
                   value="<?= esc(old($kunciJudul, $baris[$kunciJudul] ?? ''),'attr') ?>"
                   maxlength="<?= $jenis === 'faq' ? 240 : 150 ?>" required>
        </div>
        <div class="a-field">
            <label class="a-label" for="info-isi"><?= $jenis === 'faq' ? 'Jawaban resmi' : 'Isi pengumuman' ?></label>
            <textarea id="info-isi" name="<?= esc($kunciIsi,'attr') ?>"
                      rows="8" maxlength="5000" required><?= esc(old($kunciIsi, $baris[$kunciIsi] ?? '')) ?></textarea>
            <p class="a-help">Teks biasa, maksimal 5.000 karakter. HTML tidak dijalankan agar informasi tetap aman.</p>
        </div>
        <?php if ($jenis === 'faq'): ?>
            <div class="a-field">
                <label class="a-label" for="info-urutan">Urutan tampil</label>
                <input id="info-urutan" name="urutan" type="number" min="0" max="65535"
                       value="<?= esc((string)old('urutan', $baris['urutan'] ?? '0'),'attr') ?>" required>
            </div>
        <?php endif ?>
        <div class="a-field">
            <label class="a-label" for="info-status">Status</label>
            <select id="info-status" name="status" required>
                <?php if ($baru || $baris['status'] === 'draf'): ?>
                    <option value="draf" <?= $status === 'draf' ? 'selected' : '' ?>>Draf — hanya pengelola</option>
                <?php endif ?>
                <option value="terbit" <?= $status === 'terbit' ? 'selected' : '' ?>>Terbit — semua akun login</option>
            </select>
        </div>
        <?php if (!$baru): ?><p class="a-help">Versi <?= (int)$baris['versi'] ?>. Jika informasi diubah lewat tab lain, simpan akan ditolak agar tidak menimpa perubahan baru.</p><?php endif ?>
        <p class="a-help" data-dirty-status role="status">Periksa isi sebelum menyimpan.</p>
        <div class="a-informasi__aksi">
            <button class="a-btn a-btn--utama" data-dirty-submit type="submit">Simpan <?= esc($nama) ?></button>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($prefix),'attr') ?>">Batal</a>
        </div>
    </form>
</section>
</div>
<?= $this->endSection() ?>
