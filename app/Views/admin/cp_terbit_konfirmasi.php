<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-cp">
    <nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $cp['id']), 'attr') ?>">CP <?= esc($cp['kode']) ?></a><span aria-hidden="true">/</span><span aria-current="page">Terbitkan</span></nav>
    <section class="a-card a-cp__editor">
        <h1>Konfirmasi penerbitan CP</h1>
        <p><strong><?= esc($cp['kode']) ?></strong> · <?= esc($cp['mapel_nama']) ?> · <?= esc($cp['kurikulum_nama']) ?> (<?= esc($cp['kurikulum_versi']) ?>)</p>
        <?php if ($galat = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div><?php endif ?>
        <div class="a-alert a-alert--info">Penerbitan menjadikan versi <?= (int) $versi['nomor_versi'] ?> aktif untuk tautan TP baru. Versi aktif lama akan diarsipkan tanpa mengubah atau menghapus data historis. Rumusan versi terbit tidak dapat diedit.</div>
        <dl class="a-rincian">
            <dt>Elemen</dt><dd><?= esc($versi['elemen']) ?></dd>
            <dt>Rumusan resmi</dt><dd class="a-cp__rumusan"><?= nl2br(esc($versi['rumusan'])) ?></dd>
            <dt>Otoritas</dt><dd><?= esc($versi['otoritas']) ?></dd>
            <dt>Dokumen</dt><dd><?= esc($versi['nomor_dokumen']) ?></dd>
            <dt>Sumber</dt><dd><?= esc($versi['sumber_url']) ?></dd>
            <dt>Tanggal berlaku</dt><dd><?= esc($versi['tanggal_berlaku']) ?></dd>
        </dl>
        <form method="post" action="<?= esc(site_url('admin/kurikulum-cp/versi/' . (int) $versi['id'] . '/terbit'), 'attr') ?>" class="a-cp__aksi">
            <?= csrf_field() ?>
            <button class="a-btn a-btn--utama" type="submit">Ya, terbitkan versi <?= (int) $versi['nomor_versi'] ?></button>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $cp['id']), 'attr') ?>">Batal</a>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
