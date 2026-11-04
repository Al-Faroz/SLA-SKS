<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
$paket=$laporan['paket'];
$siswa=$laporan['siswa'];
$ringkasan=$laporan['ringkasan'];
$pra=$laporan['pra'];
$nilai=static fn (mixed $angka): string => $angka===null
    ? '—' : number_format((float)$angka,2,',','.');
$jalur=static fn (?string $v): string => match ($v) {
    'lulus_diagnostik'=>'Lulus Diagnostik',
    'belajar_wajib'=>'Belajar Wajib',
    default=>'—',
};
?>
<div class="a-page a-laporan">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url($pratinjau ? 'pimpinan' : 'siswa'),'attr') ?>">
            Beranda <?= $pratinjau ? 'Pimpinan' : 'Siswa' ?>
        </a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url($kembali),'attr') ?>">Laporan Akademik</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Semester <?= (int)$paket['semester'] ?></span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow"><?= $pratinjau ? 'Pimpinan · Pratinjau Baca-Saja'
                : 'Siswa · Laporan Saya' ?></p>
            <h1>Laporan Akademik · Semester <?= (int)$paket['semester'] ?></h1>
            <p class="a-proto-muted"><?= esc($siswa['nama_siswa']) ?> ·
                <?= esc($paket['nama_gen']) ?> · <?= esc($paket['nama_mapel']) ?></p>
        </div>
    </header>
    <section class="a-card a-jarak-atas" aria-labelledby="ringkasan-laporan">
        <h2 id="ringkasan-laporan">Progres Pembelajaran</h2>
        <div class="a-laporan__ringkasan">
            <div class="a-laporan__metrik">
                <span>TP NonEsensial</span>
                <strong><?= (int)$ringkasan['jumlah_tp'] ?></strong>
            </div>
            <div class="a-laporan__metrik">
                <span>Selesai dengan Sumatif sah</span>
                <strong><?= (int)$ringkasan['selesai'] ?></strong>
            </div>
            <div class="a-laporan__metrik">
                <span>Nilai TP Final</span>
                <strong><?= (int)$ringkasan['final'] ?></strong>
            </div>
            <div class="a-laporan__metrik">
                <span>Nilai TP Sementara</span>
                <strong><?= (int)$ringkasan['sementara'] ?></strong>
            </div>
        </div>
        <?php if ($ringkasan['persen'] === null): ?>
            <div class="a-alert" role="status"><?= $ringkasan['dilewati']
                ? 'Semester ini dilewati karena berada sebelum semester mulai siswa.'
                : 'Belum ada TP NonEsensial untuk menghitung progres.' ?></div>
        <?php else: ?>
            <div class="a-laporan__progres a-laporan__progres--besar">
                <progress max="100" value="<?= esc((string)$ringkasan['persen'],'attr') ?>"
                    aria-label="Progres penyelesaian semester"></progress>
                <strong><?= esc(number_format((float)$ringkasan['persen'],1,',','.')) ?>%</strong>
            </div>
        <?php endif ?>
        <p class="a-help">Progres dihitung dari TP NonEsensial yang tuntas
            dan mempunyai Sumatif selesai sah. Ini bukan rata-rata nilai atau Nilai Akhir Semester.</p>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="detail-tp">
        <h2 id="detail-tp">Rincian TP dan Nilai</h2>
        <p class="a-help">Urutan mengikuti ATP semester, termasuk TP Esensial tanpa nilai.
            Tanda — berarti belum memiliki skor sah atau tidak berlaku, bukan angka 0.</p>
        <?php if ($laporan['tp'] === []): ?>
            <div class="a-alert" role="status">Belum ada TP yang tercatat pada semester ini.</div>
        <?php else: ?>
            <p class="a-laporan__hint">Geser tabel ke samping untuk melihat seluruh komponen.</p>
            <div class="a-tabel-gulir a-laporan__scroll" tabindex="0"
                role="region" aria-label="Tabel laporan TP dapat digulir secara horizontal">
                <table class="a-tbl a-laporan__tabel a-laporan__tabel--detail">
                    <thead><tr>
                        <th scope="col">BAB / TP</th>
                        <th scope="col">Jenis</th>
                        <th scope="col">Progres</th>
                        <th scope="col">Diagnostik</th>
                        <th scope="col">Jalur</th>
                        <th scope="col">Resume</th>
                        <th scope="col">HOTS</th>
                        <th scope="col">Sumatif Awal</th>
                        <th scope="col">Sumatif Remedial</th>
                        <th scope="col">Sumatif Terbaik</th>
                        <th scope="col">Nilai TP</th>
                        <th scope="col">Status Nilai</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($laporan['tp'] as $r): ?>
                        <tr>
                            <th scope="row" class="a-laporan__identitas-tp">
                                <small>BAB <?= (int)($r['nomor_bab'] ?? 0) ?> ·
                                    <?= esc((string)($r['nama_bab'] ?? 'Belum ditetapkan')) ?></small>
                                <strong><?= esc((string)$r['kode_tp']) ?></strong>
                                <span><?= esc((string)($r['nama_tp'] ?: $r['judul'])) ?></span>
                            </th>
                            <td><?= $r['jenis_tp']==='esensial' ? 'Esensial' : 'NonEsensial' ?></td>
                            <td><?= esc(str_replace('_',' ',(string)$r['status_laporan'])) ?></td>
                            <td class="a-laporan__angka"><?= esc($nilai($r['skor_diagnostik'])) ?></td>
                            <td><?= esc($jalur($r['jalur'])) ?></td>
                            <?php foreach (['nilai_resume','nilai_hots','skor_sumatif_awal',
                                'skor_sumatif_remedial','nilai_sumatif','nilai_tp'] as $kolom): ?>
                                <td class="a-laporan__angka">
                                    <?= esc($nilai($r[$kolom])) ?>
                                </td>
                            <?php endforeach ?>
                            <td>
                                <?php if ($r['jenis_tp']==='esensial'): ?>
                                    <span class="a-badge">Tanpa Nilai</span>
                                <?php elseif ($r['status_nilai']==='final'): ?>
                                    <span class="a-badge">Final</span>
                                <?php elseif ($r['status_nilai']==='sementara'): ?>
                                    <span class="a-badge">Sementara</span>
                                <?php else: ?>
                                    <span class="a-help">Belum Ada Nilai</span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>

    <section class="a-card a-jarak-atas a-laporan__pra" aria-labelledby="pra-laporan">
        <h2 id="pra-laporan">Hasil Pra-Semester</h2>
        <p class="a-help">Checkpoint akademik sebelum Ujian Semester yang sesungguhnya.
            Nilai ini terpisah dari Nilai TP, bukan komponen sertifikat atau
            Nilai Akhir Semester.</p>
        <dl class="a-laporan__ringkasan">
            <div class="a-laporan__metrik">
                <dt>KKM</dt>
                <dd><?= $pra['kkm']===null ? '—' : (int)$pra['kkm'] ?></dd>
            </div>
            <div class="a-laporan__metrik">
                <dt>Nilai Normal</dt>
                <dd><?= esc($nilai($pra['skor_normal'])) ?></dd>
            </div>
            <div class="a-laporan__metrik">
                <dt>Nilai Remedial</dt>
                <dd><?= esc($nilai($pra['skor_remedial'])) ?></dd>
            </div>
            <div class="a-laporan__metrik">
                <dt>Nilai Terbaik</dt>
                <dd><?= esc($nilai($pra['nilai_terbaik'])) ?></dd>
            </div>
        </dl>
        <p class="a-laporan__status">Status: <strong><?= esc($pra['label']) ?></strong></p>
    </section>
    <div class="a-penilaian-aksi a-jarak-atas">
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($kembali),'attr') ?>">
            Kembali ke Daftar Laporan
        </a>
    </div>
</div>
<?= $this->endSection() ?>
