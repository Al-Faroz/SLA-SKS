<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
    $peran = $panduan['peran'];
    $kelompok = $panduan['kelompok'];
?>
<div class="a-page a-bantuan">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url($peran),'attr') ?>">Beranda <?= esc($panduan['nama_peran']) ?></a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Bantuan</span>
    </nav>

    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow"><?= esc($panduan['nama_peran']) ?> · Panduan Penggunaan</p>
            <h1>Pusat Bantuan</h1>
            <p class="a-proto-muted">Petunjuk penggunaan SLA-SKS sesuai peran aktif dan alur akademik yang berlaku.
                Pertanyaan di bawah ini mengarah ke halaman yang sudah tersedia.</p>
        </div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($peran),'attr') ?>">Kembali ke Beranda</a>
    </header>

    <section class="a-card a-bantuan__pengenalan a-jarak-atas" aria-label="Cara menggunakan panduan">
        <div>
            <h2>Panduan untuk <?= esc($panduan['nama_peran']) ?></h2>
            <p class="a-help">Pilih pertanyaan untuk melihat jawabannya, kemudian buka menu yang ditautkan.
                Hak akses tetap diperiksa oleh server. Halaman ini berisi panduan penggunaan,
                bukan pengumuman atau notifikasi dari sekolah.</p>
        </div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('profil'),'attr') ?>">Profil &amp; Peran</a>
    </section>

    <div class="a-bantuan__kelompok a-jarak-atas">
        <?php foreach ($kelompok as $ke => $bagian): ?>
            <section class="a-card a-bantuan__panel"
                aria-labelledby="bantuan-kelompok-<?= (int) $ke ?>">
                <h2 id="bantuan-kelompok-<?= (int) $ke ?>"><?= esc($bagian['judul']) ?></h2>
                <p class="a-help"><?= esc($bagian['keterangan']) ?></p>
                <div class="a-bantuan__pertanyaan">
                    <?php foreach ($bagian['pertanyaan'] as $i => $item): ?>
                        <details class="a-bantuan__butir" <?= $ke === 0 && $i === 0 ? 'open' : '' ?>>
                            <summary><?= esc($item['tanya']) ?></summary>
                            <div class="a-bantuan__jawaban">
                                <p><?= esc($item['jawab']) ?></p>
                                <a class="a-btn a-btn--sekunder"
                                   href="<?= esc(site_url($item['path']), 'attr') ?>">
                                    <?= esc($item['aksi']) ?>
                                </a>
                            </div>
                        </details>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
