<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
$ring=$dashboard;
$lanjut=$ring['lanjut'];
?>
<div class="a-page a-dashboard-siswa">
    <header class="a-page__heading a-proto-heading">
        <div>
            <h1>Dashboard Pembelajaran Saya</h1>
            </div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('siswa/pembelajaran'),'attr') ?>">
            Daftar Mata Pelajaran
        </a>
    </header>

    <section class="a-card a-dashboard-siswa__hero a-jarak-atas"
        aria-labelledby="siswa-lanjut-judul">
        <div>
            <p class="a-proto-eyebrow">Gen <?= esc($ring['siswa']['gen']) ?>
                · Mulai dari Semester <?= (int)$ring['siswa']['semester_mulai'] ?></p>
            <h2 id="siswa-lanjut-judul">Langkah Pembelajaran Berikutnya</h2>
            <p><?= esc($lanjut['keterangan']) ?></p>
            <?php if (!empty($lanjut['tp_berikutnya'])): ?>
                <div class="a-dashboard-siswa__next" aria-label="Tujuan pembelajaran berikutnya">
                    <span>TP yang dapat dibuka</span>
                    <strong><?= esc($lanjut['tp_berikutnya']['kode']) ?> —
                        <?= esc($lanjut['tp_berikutnya']['judul']) ?></strong>
                </div>
            <?php endif ?>
            <a class="a-btn a-btn--utama"
                href="<?= esc(site_url($lanjut['tautan']),'attr') ?>">
                <?= esc($lanjut['label']) ?>
            </a>
        </div>
        <div class="a-dashboard-siswa__hero-stats" aria-label="Pencapaian TP wajib">
            <span>TP selesai sah</span>
            <strong><?= (int)$ring['tp_tuntas'] ?> /
                <?= (int)$ring['tp_wajib'] ?></strong>
            <?php if ($ring['persen']===null): ?>
                <small>Belum ada target TP NonEsensial yang terbuka</small>
            <?php else: ?>
                <small><?= esc(number_format((float)$ring['persen'],1,',','.')) ?>%</small>
            <?php endif ?>
        </div>
    </section>

    <section class="a-jarak-atas" aria-labelledby="siswa-ringkasan-judul">
        <h2 id="siswa-ringkasan-judul">Ringkasan Akademik</h2>
        <div class="a-dashboard-siswa__metrics">
            <article class="a-card a-dashboard-siswa__metric">
                <span>Mata Pelajaran</span>
                <strong><?= (int)$ring['mapel'] ?></strong>
                
            </article>
            <article class="a-card a-dashboard-siswa__metric">
                <span>Semester dapat diakses</span>
                <strong><?= (int)$ring['paket_terbuka'] ?></strong>
                
            </article>
            <article class="a-card a-dashboard-siswa__metric">
                <span>Nilai TP Final</span>
                <strong><?= (int)$ring['nilai_final'] ?></strong>
                
            </article>
            <article class="a-card a-dashboard-siswa__metric">
                <span>TP perlu bantuan</span>
                <strong><?= (int)$ring['perlu_bantuan'] ?></strong>
                
            </article>
            <article class="a-card a-dashboard-siswa__metric">
                <span>Sertifikat berlaku</span>
                <strong><?= (int)$ring['sertifikat_berlaku'] ?></strong>
                
            </article>
        </div>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="siswa-progres-judul">
        <h2 id="siswa-progres-judul">Progres per Mata Pelajaran dan Semester</h2>
        
        <?php if ($ring['paket']===[]): ?>
            <div class="a-alert" role="status">
                Belum ada semester yang dapat dibuka. Periksa daftar pembelajaran
                atau hubungi Guru jika paket belum disiapkan.
            </div>
        <?php else: ?>
            <div class="a-dashboard-siswa__paket-grid">
                <?php foreach ($ring['paket'] as $paket): ?>
                    <article class="a-dashboard-siswa__paket">
                        <div class="a-dashboard-siswa__paket-info">
                            <div class="a-dashboard-siswa__paket-head">
                            <span class="a-badge">Semester <?= (int)$paket['semester'] ?></span>
                            <span class="a-dashboard-siswa__status">
                                <?= esc($paket['status']) ?>
                            </span>
                        </div>
                        <h3><?= esc($paket['nama_mapel']) ?></h3>
                        <?php if (!empty($paket['tp_berikutnya'])): ?>
                            <p class="a-dashboard-siswa__tp-hint">
                                <span>TP berikutnya:</span>
                                <strong><?= esc($paket['tp_berikutnya']['kode']) ?> —
                                    <?= esc($paket['tp_berikutnya']['judul']) ?></strong>
                            </p>
                        <?php endif ?>
                        </div>
                        <div class="a-dashboard-siswa__paket-activity">
                        <p class="a-dashboard-siswa__count">
                            <strong><?= (int)$paket['tp_tuntas'] ?> /
                                <?= (int)$paket['tp_wajib'] ?></strong>
                            <span>TP selesai sah</span>
                        </p>
                        <?php if ($paket['tp_wajib']===0): ?>
                            <p class="a-help">Belum ada TP wajib.</p>
                        <?php elseif ($paket['tp_wajib']<=12): ?>
                            <div class="a-dashboard-siswa__segments"
                                role="img" aria-label="<?= esc((string)$paket['tp_tuntas']
                                    .' dari '.(string)$paket['tp_wajib']
                                    .' TP NonEsensial selesai','attr') ?>">
                                <?php for ($i=0;$i<$paket['tp_wajib'];$i++): ?>
                                    <span class="a-dashboard-siswa__segment
                                        <?= $i<$paket['tp_tuntas']?'a-dashboard-siswa__segment--selesai':'' ?>"
                                        aria-hidden="true"></span>
                                <?php endfor ?>
                            </div>
                        <?php else: ?>
                            <progress class="a-dashboard-siswa__progress"
                                max="<?= (int)$paket['tp_wajib'] ?>"
                                value="<?= (int)$paket['tp_tuntas'] ?>"
                                aria-label="<?= esc('Progres '.$paket['nama_mapel']
                                    .' Semester '.$paket['semester'],'attr') ?>"></progress>
                        <?php endif ?>
                        <p class="a-help">
                            <?php if ($paket['persen']!==null): ?>
                                <?= esc(number_format((float)$paket['persen'],1,',','.')) ?>%
                                · <?= (int)$paket['nilai_final'] ?> nilai TP Final
                            <?php endif ?>
                            <?php if ($paket['perlu_bantuan']>0): ?>
                                · <?= (int)$paket['perlu_bantuan'] ?> TP memerlukan bantuan
                            <?php endif ?>
                        </p>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc(site_url($paket['tautan']),'attr') ?>">
                            <?= esc($paket['label_aksi']) ?>
                        </a>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="siswa-hasil-judul">
        <h2 id="siswa-hasil-judul">Hasil dan Dokumen Akademik</h2>
        <div class="a-dashboard-siswa__links">
            <div>
                <h3>Laporan Akademik Saya</h3>
                
                <a class="a-btn a-btn--sekunder"
                    href="<?= esc(site_url('siswa/laporan'),'attr') ?>">
                    Buka Laporan Akademik
                </a>
            </div>
            <div>
                <h3>Sertifikat Saya</h3>
                
                <a class="a-btn a-btn--sekunder"
                    href="<?= esc(site_url('siswa/sertifikat'),'attr') ?>">
                    Lihat Sertifikat
                </a>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
