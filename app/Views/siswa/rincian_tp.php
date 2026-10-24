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
                Rincian TP dan status aktivasi versi konten Anda.</p>
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
        <div class="a-siswa-aktivasi" aria-labelledby="status-aktivasi-tp">
            <h3 id="status-aktivasi-tp">Status pembelajaran TP</h3>
            <?php if ($pesan = session()->getFlashdata('sukses_mulai_tp')): ?>
                <div class="a-alert" role="status"><?= esc($pesan) ?></div>
            <?php endif ?>
            <?php if ($pesan = session()->getFlashdata('galat_mulai_tp')): ?>
                <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
            <?php endif ?>
            <?php if ($progres === null): ?>
                <p class="a-help">TP ini belum diaktifkan untuk akun Anda. Aktivasi menyimpan
                    satu progres TP dan mengikat versi konten yang akan digunakan kelak.
                    Tidak ada jawaban, nilai, atau percobaan ujian yang dibuat.</p>
                <form action="<?= esc(site_url('siswa/tp/' . (int) $tp['id'] . '/mulai'), 'attr') ?>"
                    method="post"
                    onsubmit="return confirm('Aktifkan TP dan kunci versi konten untuk akun ini? Aktivasi tidak memulai ujian.');">
                    <?= csrf_field() ?>
                    <button class="a-btn a-btn--utama" type="submit">Aktifkan TP &amp; simpan versi</button>
                </form>
            <?php else: ?>
                <div class="a-alert" role="status">
                    <strong>TP telah diaktifkan.</strong>
                    Status: <?= esc((string) $progres['status']) ?>.
                    Snapshot konten #<?= (int) $progres['versi_konten_tp_id'] ?>
                    dipakai tetap untuk progres ini sejak
                    <?= esc((string) $progres['dimulai_pada']) ?> (UTC).
                    Memuat ulang halaman tidak membuat progres baru.
                </div>
            <?php endif ?>
            <?php if ($progres !== null && $progres['status'] === 'diagnostik'): ?>
                <p><a class="a-btn a-btn--sekunder"
                    href="<?= esc(site_url('siswa/tp/' . (int) $tp['id'] . '/diagnostik/petunjuk'), 'attr') ?>">
                    Baca petunjuk &amp; uji kesiapan perangkat
                </a></p>
            <?php endif ?>
            <p class="a-help">
                <strong>Diagnostik wajib adalah langkah ujian pertama.</strong>
                Halaman petunjuk dan uji kemampuan fullscreen kini tersedia bagi
                siswa dengan progres Diagnostik. Mesin percobaan, pengacakan soal,
                timer server dan penyimpanan jawaban masih dalam tahap berikutnya.
                <strong>Belum ada tombol menjalankan ujian.</strong> Materi tetap terkunci
                sampai alur ujian menghasilkan keputusan sah.
            </p>
        </div>
    </section>
    <p class="a-kesiapan-kembali">
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('siswa/semester/' . (int) $paket['id']), 'attr') ?>">Kembali ke daftar TP</a>
    </p>
</div>
<?= $this->endSection() ?>
