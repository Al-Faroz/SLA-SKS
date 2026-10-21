<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-cp a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>">Referensi CP</a><span aria-hidden="true">/</span><span aria-current="page"><?= esc($cp['kode']) ?></span></nav>
    <header class="a-proto-heading a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Admin · Riwayat Referensi</p>
            <h1>CP <?= esc($cp['kode']) ?></h1>
            <p class="a-proto-muted"><?= esc($cp['mapel_nama']) ?> · Fase D · <?= esc($cp['kurikulum_nama']) ?> (<?= esc($cp['kurikulum_versi']) ?>)</p>
        </div>
    </header>
    <?php if ($galat = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div><?php endif ?>
    <?php if ($sukses = session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($sukses) ?></div><?php endif ?>
    <?php $adaDraf = count(array_filter($versi, static fn (array $item): bool => $item['status'] === 'draf')) > 0; ?>
    <section class="a-card">
        <h2>Riwayat versi dan naskah resmi</h2>
        <p class="a-help">Versi aktif dapat ditautkan ke TP pada modul Guru berikutnya. Versi historis tetap tersedia dan tidak dapat diedit atau dihapus.</p>
        <?php if (! $adaDraf): ?>
            <a class="a-btn a-btn--utama" href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $cp['id'] . '/versi-baru'), 'attr') ?>">Buat draf versi baru</a>
        <?php else: ?>
            <div class="a-alert a-alert--info">Masih ada draf versi CP. Perbaiki dan terbitkan terlebih dahulu sebelum menambah versi lain.</div>
        <?php endif ?>
        <?php foreach ($versi as $v): ?>
            <article class="a-cp__versi">
                <div class="a-cp__versi-atas">
                    <h3>Versi <?= (int) $v['nomor_versi'] ?> · <?= esc($v['elemen']) ?></h3>
                    <span class="a-badge <?= $v['status'] === 'aktif' ? 'a-badge--siap' : 'a-badge--tunggu' ?>"><?= esc(ucfirst($v['status'])) ?></span>
                </div>
                <p class="a-cp__rumusan"><?= nl2br(esc($v['rumusan'])) ?></p>
                <dl class="a-rincian">
                    <dt>Otoritas</dt><dd><?= esc($v['otoritas']) ?></dd>
                    <dt>Dokumen</dt><dd><?= esc($v['nomor_dokumen']) ?></dd>
                    <dt>Sumber URL</dt><dd><span class="a-cp__url"><?= esc($v['sumber_url']) ?></span></dd>
                    <dt>Berlaku sejak</dt><dd><?= esc($v['tanggal_berlaku']) ?></dd>
                    <dt>Ditulis</dt><dd><?= esc($v['dibuat_pada']) ?> UTC</dd>
                    <dt>Diterbitkan</dt><dd><?= $v['diterbitkan_pada'] === null ? 'Belum diterbitkan' : esc($v['diterbitkan_pada']) . ' UTC' ?></dd>
                </dl>
                <?php if ($v['status'] === 'draf'): ?>
                    <div class="a-cp__aksi">
                        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kurikulum-cp/versi/' . (int) $v['id'] . '/edit'), 'attr') ?>">Perbaiki draf</a>
                        <a class="a-btn a-btn--utama" href="<?= esc(site_url('admin/kurikulum-cp/versi/' . (int) $v['id'] . '/terbit'), 'attr') ?>">Tinjau dan terbitkan</a>
                    </div>
                <?php endif ?>
            </article>
        <?php endforeach ?>
    </section>
</div>
<?= $this->endSection() ?>
