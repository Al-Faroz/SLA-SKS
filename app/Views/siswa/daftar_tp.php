<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'), 'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span><a href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Mata Pelajaran</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/mapel/' . (int) $paket['mapel_id']), 'attr') ?>">Semester</a>
        <span aria-hidden="true">/</span><span aria-current="page">Daftar TP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · <?= esc((string) $paket['kode_mapel']) ?> · Gen <?= esc((string) $siswa['gen_kode']) ?></p>
            <h1>Semester <?= (int) $paket['semester'] ?> — <?= esc((string) $paket['nama_mapel']) ?></h1>
            <p class="a-proto-muted">TP dibuka sesuai urutan ATP. TP berikutnya menunggu
                penyelesaian TP sebelumnya; TP Draf menunggu Guru menyiapkannya.</p>
        </div>
    </header>
    <section class="a-card">
        <h2>Tujuan Pembelajaran</h2>
        <?php if ($tp === []): ?>
            <div class="a-empty" role="status">
                <strong>Belum ada TP pada semester ini.</strong>
                <p>Guru sedang menyiapkan struktur pembelajaran.</p>
            </div>
        <?php else: ?>
            <div class="a-siswa-daftar-tp">
                <?php foreach ($bab as $kelompok): ?>
                    <section class="a-siswa-bab" aria-labelledby="bab-siswa-<?= (int) $kelompok['id'] ?>">
                        <header class="a-siswa-bab__judul">
                            <div>
                                <p class="a-proto-eyebrow">BAB <?= (int) $kelompok['nomor'] ?></p>
                                <h3 id="bab-siswa-<?= (int) $kelompok['id'] ?>"><?= esc($kelompok['nama']) ?></h3>
                                <?php if ($kelompok['deskripsi'] !== ''): ?>
                                    <p class="a-help"><?= esc($kelompok['deskripsi']) ?></p>
                                <?php endif ?>
                            </div>
                            <span class="a-badge"><?= count($kelompok['tp']) ?> TP</span>
                        </header>
                        <div class="a-siswa-bab__tp">
                            <?php foreach ($kelompok['tp'] as $item): ?>
                                <article class="a-siswa-kartu">
                                    <div class="a-siswa-tp-heading">
                                        <div>
                                            <p class="a-proto-eyebrow">Urutan <?= (int) $item['urutan_atp'] ?> · <?= esc((string) $item['kode_tp']) ?></p>
                                            <h4><?= esc((string) $item['judul']) ?></h4>
                                        </div>
                                        <span class="a-siswa-status <?= esc($item['akses']['kode'], 'attr') ?>"><?= esc($item['akses']['label']) ?></span>
                                    </div>
                                    <?php if ($item['akses']['buka']): ?>
                                        <a class="a-btn a-btn--utama" href="<?= esc(site_url('siswa/tp/' . (int) $item['id']), 'attr') ?>">Lihat rincian TP</a>
                                    <?php elseif ($item['akses']['kode'] === 'esensial'): ?>
                                        <p class="a-help">TP Esensial tercatat dalam ATP tanpa aktivitas atau nilai wajib siswa.</p>
                                    <?php else: ?>
                                        <p class="a-help"><?= esc($item['akses']['label']) ?></p>
                                    <?php endif ?>
                                </article>
                            <?php endforeach ?>
                        </div>
                    </section>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <p class="a-help a-jarak-atas">Daftar ini menunjukkan urutan TP. Buka Rincian TP
            untuk menjalankan Diagnostik, melihat hasil, atau membaca Materi yang sudah diizinkan.
            Materi berikutnya tetap menunggu persyaratan pembelajaran sebelumnya.</p>
    </section>
    <section class="a-card a-jarak-atas" aria-labelledby="pra-siswa">
        <h2 id="pra-siswa">Ujian Pra-Semester</h2>
        <p class="a-help">Ujian gabungan seluruh BAB semester. Hanya dapat dimulai setelah
            semua TP NonEsensial selesai dan dalam jadwal yang dibuka Admin.
            Durasi dihitung sejak masing-masing siswa mulai mengerjakan.</p>
        <a class="a-btn a-btn--utama"
            href="<?= esc(site_url('siswa/pra-semester/' . (int)$paket['id']),'attr') ?>">
            Lihat Jadwal &amp; Status Pra-Semester
        </a>
    </section>
</div>
<?= $this->endSection() ?>
