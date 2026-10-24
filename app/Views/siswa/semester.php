<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'), 'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span><a href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Mata Pelajaran</a>
        <span aria-hidden="true">/</span><span aria-current="page">Semester</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · <?= esc((string) $mapel['kode_mapel']) ?></p>
            <h1><?= esc((string) $mapel['nama_mapel']) ?></h1>
            <p class="a-proto-muted">Pilih semester sesuai urutan pembelajaran Gen Anda.
                Semester sebelum semester mulai dilewati tanpa nilai.</p>
        </div>
    </header>
    <section class="a-card">
        <h2>Enam semester pembelajaran</h2>
        <p class="a-help">Semester mulai: <?= (int) $siswa['mulai_efektif'] ?>.
            Semester lanjutan terbuka setelah progres semester sebelumnya selesai.</p>
        <div class="a-siswa-kartu-grid">
            <?php foreach ($semester as $item): ?>
                <article class="a-siswa-kartu">
                    <p class="a-proto-eyebrow">Semester <?= (int) $item['nomor'] ?></p>
                    <h3><?= esc((string) $item['label_status']) ?></h3>
                    <?php if ($item['buka']): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc(site_url('siswa/semester/' . (int) $item['paket_id']), 'attr') ?>">Lihat daftar TP</a>
                    <?php else: ?>
                        <p class="a-help" role="status">Belum tersedia untuk dibuka.</p>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
        </div>
    </section>
    <p class="a-kesiapan-kembali">
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Kembali ke mata pelajaran</a>
    </p>
</div>
<?= $this->endSection() ?>
