<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-laporan">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'),'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Laporan Akademik</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · Hasil Pembelajaran</p>
            <h1>Laporan Akademik Saya</h1>
            <p class="a-proto-muted">Lihat perkembangan TP, nilai yang sudah sah,
                serta hasil Pra-Semester pada bagian tersendiri.</p>
        </div>
    </header>
    <section class="a-card a-jarak-atas" aria-labelledby="pilih-laporan">
        <h2 id="pilih-laporan">Pilih Mata Pelajaran dan Semester</h2>
        <?php if ($daftar === []): ?>
            <div class="a-alert" role="status">Belum ada semester yang dapat dibuka.
                Pastikan Gen serta paket pembelajaran sudah ditetapkan.</div>
        <?php else: ?>
            <div class="a-laporan__daftar">
                <?php foreach ($daftar as $item): ?>
                    <article class="a-laporan__pilihan">
                        <span class="a-badge">Semester <?= (int)$item['semester'] ?></span>
                        <h3><?= esc($item['mapel']) ?></h3>
                        <p class="a-help">Progres dan nilai TP, serta checkpoint Pra-Semester.</p>
                        <a class="a-btn a-btn--utama"
                            href="<?= esc(site_url('siswa/laporan/'.(int)$item['paket_id']),'attr') ?>">
                            Buka Laporan
                        </a>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
