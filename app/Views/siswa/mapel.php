<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'), 'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span><span aria-current="page">Pembelajaran</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · Gen <?= esc((string) $siswa['gen_kode']) ?></p>
            <h1>Mata Pelajaran</h1>
            <p class="a-proto-muted">Pilih mata pelajaran yang ditugaskan untuk Gen Anda.
                Pembelajaran mengikuti enam semester sesuai semester mulai Anda.</p>
        </div>
    </header>
    <section class="a-card">
        <h2>Mata pelajaran Gen <?= esc((string) $siswa['gen_nama']) ?></h2>
        <p class="a-help">Semester mulai: <?= (int) $siswa['mulai_efektif'] ?>. Mata pelajaran
            tanpa paket aktif belum dapat dipilih.</p>
        <?php if ($mapel === []): ?>
            <div class="a-empty" role="status">
                <strong>Belum ada paket pembelajaran aktif.</strong>
                <p>Guru atau Admin masih menyiapkan paket untuk Gen Anda. Silakan periksa kembali nanti.</p>
            </div>
        <?php else: ?>
            <div class="a-siswa-kartu-grid">
                <?php foreach ($mapel as $item): ?>
                    <article class="a-siswa-kartu">
                        <p class="a-proto-eyebrow"><?= esc((string) $item['kode']) ?></p>
                        <h3><?= esc((string) $item['nama']) ?></h3>
                        <p class="a-help"><?= count($item['semester_tersedia']) ?> paket semester aktif</p>
                        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('siswa/mapel/' . (int) $item['id']), 'attr') ?>">Lihat semester</a>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
