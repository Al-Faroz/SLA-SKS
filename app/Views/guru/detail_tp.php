<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Rincian TP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Pembelajaran · Semester <?= (int) $paket['semester'] ?></p>
            <h1><?= esc($tp['judul']) ?></h1>
            <p class="a-proto-muted">Rincian TP dalam paket semester versi <?= (int) $paket['nomor_versi'] ?>. Pembacaan dibatasi pada Gen–Mapel yang Anda ampu.</p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="identitas-tp">
        <h2 id="identitas-tp">Identitas TP</h2>
        <div class="a-tabel-gulir">
            <table class="a-tbl">
                <tbody>
                    <tr><th scope="row">Kode TP</th><td><?= esc($tp['kode_tp']) ?></td></tr>
                    <tr><th scope="row">Urutan ATP</th><td><?= (int) $tp['urutan_atp'] ?></td></tr>
                    <tr><th scope="row">Semester</th><td><?= (int) $paket['semester'] ?></td></tr>
                    <tr><th scope="row">Status TP</th><td><span class="a-badge"><?= esc($tp['status']) ?></span></td></tr>
                    <tr><th scope="row">Jumlah Materi/Submateri</th><td><?= (int) $jumlah_materi ?></td></tr>
                </tbody>
            </table>
        </div>
    </section>
    <section class="a-card a-jarak-atas" aria-labelledby="rumusan-tp">
        <h2 id="rumusan-tp">Rumusan dan indikator</h2>
        <h3>Rumusan TP</h3>
        <p><?= nl2br(esc($tp['rumusan'] ?? '')) ?></p>
        <h3>Indikator</h3>
        <p><?= nl2br(esc($tp['indikator'] ?? '')) ?></p>
        <?php if ($jumlah_materi === 0): ?>
            <div class="a-empty" role="status">
                <strong>Belum ada Materi/Submateri.</strong>
                <p>Struktur TP sudah terdaftar, tetapi belum memiliki materi. Penyusunan materi akan tersedia dalam tahap berikutnya.</p>
            </div>
        <?php endif ?>
        <p class="a-help">Halaman ini hanya menampilkan data. Tidak ada perubahan pada TP atau Materi.</p>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Kembali ke Peta ATP</a>
    </section>
</div>
<?= $this->endSection() ?>
