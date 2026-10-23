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
</div>
<?= $this->endSection() ?>
