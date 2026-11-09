<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
$peran = $kontak['peran'];
$adaKontak = $kontak['alamat'] !== '' || $kontak['telepon'] !== ''
    || $kontak['email'] !== '' || $kontak['website'] !== '';
?>
<div class="a-page a-informasi a-hubungi-admin">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url($peran), 'attr') ?>">Beranda <?= esc(ucfirst($peran)) ?></a>
        <span aria-hidden="true">/</span><span aria-current="page">Hubungi Admin</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Bantuan · Kontak Resmi</p>
            <h1>Hubungi Admin</h1>
            <p class="a-proto-muted">Kontak resmi madrasah.</p>
        </div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('bantuan'), 'attr') ?>">Kembali ke Bantuan</a>
    </header>

    <section class="a-card a-jarak-atas" aria-labelledby="judul-kontak-resmi">
        <p class="a-proto-eyebrow">Identitas Madrasah</p>
        <h2 id="judul-kontak-resmi"><?= $kontak['nama_madrasah'] !== ''
            ? esc($kontak['nama_madrasah']) : 'Kontak Madrasah' ?></h2>
        <?php if (!$adaKontak): ?>
            <p class="a-informasi__kosong" role="status">Informasi kontak resmi belum tersedia.
                Mintalah Admin memperbarui Identitas Madrasah.</p>
        <?php else: ?>
            <dl class="a-hubungi-admin__daftar">
                <?php if ($kontak['alamat'] !== ''): ?>
                <div class="a-hubungi-admin__butir">
                    <dt>Alamat Madrasah</dt>
                    <dd><?= esc($kontak['alamat']) ?></dd>
                </div>
                <?php endif ?>
                <?php if ($kontak['telepon'] !== ''): ?>
                <div class="a-hubungi-admin__butir">
                    <dt>Telepon Resmi</dt>
                    <dd><?php if ($kontak['tautan_telepon'] !== ''): ?>
                        <a href="<?= esc($kontak['tautan_telepon'], 'attr') ?>"><?= esc($kontak['telepon']) ?></a>
                        <?php else: ?><?= esc($kontak['telepon']) ?><?php endif ?></dd>
                </div>
                <?php endif ?>
                <?php if ($kontak['email'] !== ''): ?>
                <div class="a-hubungi-admin__butir">
                    <dt>Email Madrasah</dt>
                    <dd><?php if ($kontak['tautan_email'] !== ''): ?>
                        <a href="<?= esc($kontak['tautan_email'], 'attr') ?>"><?= esc($kontak['email']) ?></a>
                        <?php else: ?><?= esc($kontak['email']) ?><?php endif ?></dd>
                </div>
                <?php endif ?>
                <?php if ($kontak['website'] !== ''): ?>
                <div class="a-hubungi-admin__butir">
                    <dt>Situs Resmi</dt>
                    <dd><?php if ($kontak['tautan_website'] !== ''): ?>
                        <a href="<?= esc($kontak['tautan_website'], 'attr') ?>"
                            rel="noopener noreferrer" target="_blank"><?= esc($kontak['website']) ?></a>
                        <?php else: ?><?= esc($kontak['website']) ?><?php endif ?></dd>
                </div>
                <?php endif ?>
            </dl>
        <?php endif ?>
        </section>
</div>
<?= $this->endSection() ?>
