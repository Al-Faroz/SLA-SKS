<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'), 'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span><a href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Mata Pelajaran</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/mapel/' . (int) $paket['mapel_id']), 'attr') ?>">Semester</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/semester/' . (int) $paket['id']), 'attr') ?>">Daftar TP</a>
        <span aria-hidden="true">/</span><span aria-current="page">Rincian TP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · Semester <?= (int) $paket['semester'] ?> · <?= esc((string) $tp['kode_tp']) ?></p>
            <h1><?= esc((string) $tp['judul']) ?></h1>
            <p class="a-proto-muted">TP Siap untuk Gen <?= esc((string) $siswa['gen_nama']) ?>.
                Ini halaman pengenalan; proses belajar dimulai setelah Diagnostik wajib tersedia.</p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="rincian-tp-siswa">
        <h2 id="rincian-tp-siswa">Tujuan dan indikator</h2>
        <h3>Rumusan TP</h3>
        <p><?= nl2br(esc((string) $tp['rumusan'])) ?></p>
        <h3>Indikator ketercapaian</h3>
        <p><?= nl2br(esc((string) $tp['indikator'])) ?></p>
    </section>
    <section class="a-card a-jarak-atas">
        <h2>Materi pada TP ini</h2>
        <?php if ($materi === []): ?>
            <p class="a-help" role="status">Materi belum dapat ditampilkan.</p>
        <?php else: ?>
            <ol class="a-siswa-materi-ringkas">
                <?php foreach ($materi as $item): ?>
                    <li><strong><?= esc((string) $item['judul']) ?></strong>
                        <span class="a-siswa-status terkunci">Belum dibuka</span>
                    </li>
                <?php endforeach ?>
            </ol>
        <?php endif ?>
        <div class="a-alert" role="status">
            <strong>Diagnostik wajib adalah langkah pertama.</strong>
            Mesin ujian dan pencatatan progres belum diaktifkan pada tahap ini,
            sehingga belum ada tombol mulai ujian atau akses media. Tidak ada nilai
            atau progres belajar yang diubah saat Anda melihat rincian TP.
        </div>
    </section>
    <p class="a-kesiapan-kembali">
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('siswa/semester/' . (int) $paket['id']), 'attr') ?>">Kembali ke daftar TP</a>
    </p>
</div>
<?= $this->endSection() ?>
