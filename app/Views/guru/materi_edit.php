<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
$pesanSuntingGalat = session()->getFlashdata('materi_edit_galat');
$pesanSuntingSukses = session()->getFlashdata('materi_edit_sukses');
$nilaiSuntingLama = session()->getFlashdata('materi_edit_input_lama');
$kolomSuntingGalat = (string) (session()->getFlashdata('materi_edit_field_galat') ?? '');
$nilaiSunting = is_array($nilaiSuntingLama) ? array_merge($materi, $nilaiSuntingLama) : $materi;
$nilaiSunting['berkas_id'] = $materi['berkas_id'];
if ($materi['berkas_id'] !== null) {
    // Jenis media yang sudah memiliki file historis tidak bisa diganti.
    $nilaiSunting['jenis'] = $materi['jenis'];
}
?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Rincian TP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Sunting Materi</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Materi/Submateri</p>
            <h1>Sunting Materi draf</h1>
            <p class="a-proto-muted"><?= esc($tp['kode_tp'] . ' — ' . $tp['judul']) ?></p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="sunting-materi">
        <h2 id="sunting-materi"><?= esc($materi['judul']) ?></h2>
        <?php if (is_string($pesanSuntingGalat) && $pesanSuntingGalat !== ''): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><strong>Perubahan belum disimpan:</strong> <?= esc($pesanSuntingGalat) ?></div>
        <?php endif ?>
        <?php if (is_string($pesanSuntingSukses) && $pesanSuntingSukses !== ''): ?>
            <div class="a-alert" role="status"><?= esc($pesanSuntingSukses) ?></div>
        <?php endif ?>
        <?php if ($materi['berkas_id'] !== null): ?>
            <div class="a-alert" role="status">
                Berkas sudah tersedia. Jenis media terkunci. Anda boleh mengubah judul, waktu,
                petunjuk Resume/HOTS atau mengunggah berkas pengganti dari jenis yang sama.
                File lama tetap tersimpan sebagai riwayat.
            </div>
        <?php else: ?>
            <p class="a-help">Materi ini belum memiliki file tersimpan. Pilih PDF, Gambar atau Audio dan unggah file untuk melengkapinya, atau gunakan URL YouTube.</p>
        <?php endif ?>
        <?php if (is_string($pesanSuntingGalat) && $pesanSuntingGalat !== ''
            && in_array(($nilaiSunting['jenis'] ?? ''), ['pdf', 'gambar', 'audio'], true)): ?>
            <p class="a-help"><strong>Pilih ulang file:</strong> Browser tidak mempertahankan file yang gagal dikirim. Isian teks Anda tetap dipulihkan.</p>
        <?php endif ?>
        <form method="post" class="a-form" enctype="multipart/form-data"
            data-materi-form data-materi-jenis-awal="<?= esc($materi['jenis'], 'attr') ?>"
            data-materi-sudah-upload="<?= $materi['berkas_id'] !== null ? '1' : '0' ?>"
            action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $materi['id']), 'attr') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
            <?= view('guru/_materi_fields', ['nilai' => $nilaiSunting, 'galatKolom' => $kolomSuntingGalat]) ?>
            <button class="a-btn a-btn--utama" type="submit" data-materi-submit>Simpan perubahan Materi</button>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke daftar Materi</a>
            <p class="a-help" data-materi-status aria-live="polite">Belum ada perubahan dikirim.</p>
        </form>
    </section>
    <?php if ($materi['berkas_id'] !== null && in_array($materi['jenis'], ['pdf', 'gambar', 'audio'], true)): ?>
        <section class="a-card a-jarak-atas" aria-label="Media tersimpan">
            <h2>Media tersimpan</h2>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $materi['id'] . '/pratinjau'), 'attr') ?>">Buka pratinjau Materi</a>
            <p class="a-help">Gunakan kolom Upload file di atas untuk mengganti media pada Materi draf.</p>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
