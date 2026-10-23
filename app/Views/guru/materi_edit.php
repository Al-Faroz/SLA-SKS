<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
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
        <?php if ($materi['berkas_id'] !== null): ?>
            <div class="a-alert" role="status">Materi sudah memiliki berkas. Jenis media tidak dapat diganti dari formulir ini.</div>
        <?php endif ?>
        <form method="post" class="a-form" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $materi['id']), 'attr') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
            <?= view('guru/_materi_fields', ['nilai' => $materi]) ?>
            <button class="a-btn a-btn--utama" type="submit">Simpan Materi draf</button>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Batal dan kembali</a>
        </form>
    </section>
    <?php if (in_array($materi['jenis'], ['gambar', 'audio'], true)): ?>
        <section class="a-card a-jarak-atas" aria-labelledby="unggah-media">
            <h2 id="unggah-media">Media pembelajaran <?= esc($materi['jenis']) ?></h2>
            <p class="a-help">Berkas tersimpan privat. Gambar JPG, PNG, WebP (maksimal 10 MB, diperkecil hingga 1600 px); audio MP3/M4A (maksimal 10 MB). Media lama dipertahankan agar riwayat tidak rusak.</p>
            <?php if ($materi['berkas_id'] !== null): ?>
                <div class="a-alert" role="status">Media sudah terpasang. Mengunggah lagi akan mengganti referensi Materi draf, bukan menghapus file historis.</div>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $materi['id'] . '/media'), 'attr') ?>" target="_blank" rel="noopener">Pratinjau media tersimpan</a>
            <?php endif ?>
            <form class="a-form" method="post" enctype="multipart/form-data" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/materi/' . (int) $materi['id'] . '/media'), 'attr') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="struktur_versi" value="<?= (int) $tp['struktur_versi'] ?>">
                <div class="a-field">
                    <label class="a-label" for="media-berkas">Pilih <?= esc($materi['jenis']) ?></label>
                    <input class="a-input" type="file" id="media-berkas" name="media"
                        accept="<?= $materi['jenis'] === 'gambar' ? '.jpg,.jpeg,.png,.webp' : '.mp3,.m4a' ?>" required>
                </div>
                <button type="submit" class="a-btn a-btn--utama">Unggah media privat</button>
            </form>
        </section>
    <?php elseif ($materi['jenis'] === 'pdf'): ?>
        <section class="a-card a-jarak-atas">
            <h2>Media PDF</h2>
            <p class="a-help">Unggah PDF belum diaktifkan sampai pemeriksaan aman jumlah halaman (maksimal 40) tersedia. Tidak ada berkas yang akan diunggah melalui formulir ini.</p>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
