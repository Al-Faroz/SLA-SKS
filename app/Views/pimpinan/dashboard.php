<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
$ring=$dashboard['ringkasan'];
$filter=$dashboard['filter'];
$gen=[]; $mapel=[];
foreach ($dashboard['pilihan'] as $pilihan) {
    $gen[(int)$pilihan['angkatan_id']] = $pilihan['nama_gen'];
    $mapel[(int)$pilihan['mapel_id']] = $pilihan['nama_mapel'];
}
asort($gen,SORT_NATURAL | SORT_FLAG_CASE);
asort($mapel,SORT_NATURAL | SORT_FLAG_CASE);
$urlHalaman=static fn (int $n): string => site_url('pimpinan').'?'.http_build_query(
    array_filter([
        'gen'=>$filter['gen'],'mapel'=>$filter['mapel'],
        'semester'=>$filter['semester'],'halaman'=>$n,
    ],static fn ($x): bool => $x !== null)
);
?>
<div class="a-page a-dashboard-pimpinan">
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Pimpinan · Monitoring Akademik Baca-Saja</p>
            <h1>Dashboard Akademik</h1>
            <p class="a-proto-muted">Ringkasan Gen–Mapel–Semester dari kegiatan siswa yang sah.
                Angka bersumber dari data tersimpan dan tidak mengubah progres atau nilai.</p>
        </div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('pimpinan/laporan'),'attr') ?>">
            Buka Monitoring Individual
        </a>
    </header>

    <?php if ($galat=session()->getFlashdata('galat_dashboard')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>

    <section class="a-card a-jarak-atas" aria-labelledby="judul-filter-dasbor">
        <h2 id="judul-filter-dasbor">Filter Ringkasan Akademik</h2>
        <?php if ($dashboard['pilihan'] === []): ?>
            <div class="a-alert" role="status">Belum ada paket akademik aktif yang ditugaskan.
                Ringkasan siswa dan TP belum dapat dihitung.</div>
        <?php else: ?>
            <form method="get" action="<?= esc(site_url('pimpinan'),'attr') ?>"
                class="a-form a-dashboard-pimpinan__filter">
                <div class="a-field">
                    <label class="a-label" for="dash-gen">Gen</label>
                    <select class="a-input" id="dash-gen" name="gen">
                        <option value="">Semua Gen</option>
                        <?php foreach ($gen as $id=>$nama): ?>
                            <option value="<?= (int)$id ?>" <?= $filter['gen']===$id?'selected':'' ?>>
                                <?= esc($nama) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="a-field">
                    <label class="a-label" for="dash-mapel">Mata Pelajaran</label>
                    <select class="a-input" id="dash-mapel" name="mapel">
                        <option value="">Semua Mata Pelajaran</option>
                        <?php foreach ($mapel as $id=>$nama): ?>
                            <option value="<?= (int)$id ?>" <?= $filter['mapel']===$id?'selected':'' ?>>
                                <?= esc($nama) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="a-field">
                    <label class="a-label" for="dash-semester">Semester</label>
                    <select class="a-input" id="dash-semester" name="semester">
                        <option value="">Semua Semester</option>
                        <?php for ($sm=1; $sm<=6; $sm++): ?>
                            <option value="<?= $sm ?>" <?= $filter['semester']===$sm?'selected':'' ?>>
                                Semester <?= $sm ?>
                            </option>
                        <?php endfor ?>
                    </select>
                </div>
                <div class="a-dashboard-pimpinan__filter-actions">
                    <button class="a-btn a-btn--utama" type="submit">Terapkan Filter</button>
                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('pimpinan'),'attr') ?>">
                        Reset
                    </a>
                </div>
            </form>
        <?php endif ?>
    </section>

    <section class="a-jarak-atas" aria-labelledby="judul-kpi-pimpinan">
        <h2 id="judul-kpi-pimpinan">Indikator Utama</h2>
        <div class="a-dashboard-pimpinan__metrics">
            <article class="a-card a-dashboard-pimpinan__metric">
                <span>Siswa terdaftar</span>
                <strong><?= (int)$ring['siswa'] ?></strong>
                <small>Unik pada Gen yang tercakup filter, termasuk semester dilewati</small>
            </article>
            <article class="a-card a-dashboard-pimpinan__metric">
                <span>Paket aktif</span>
                <strong><?= (int)$ring['paket'] ?></strong>
                <small>Gen–Mapel–Semester yang ditugaskan</small>
            </article>
            <article class="a-card a-dashboard-pimpinan__metric">
                <span>TP selesai sah</span>
                <strong><?= (int)$ring['tuntas_sah'] ?> / <?= (int)$ring['pasangan_wajib'] ?></strong>
                <small>Pasangan siswa×TP NonEsensial dengan Sumatif selesai sah</small>
            </article>
            <article class="a-card a-dashboard-pimpinan__metric">
                <span>Nilai TP Final</span>
                <strong><?= (int)$ring['nilai_final'] ?></strong>
                <small>Pasangan tuntas sah dengan nilai Final, termasuk skor 0</small>
            </article>
            <article class="a-card a-dashboard-pimpinan__metric">
                <span>Sertifikat berlaku</span>
                <strong><?= (int)$ring['sertifikat_berlaku'] ?></strong>
                <small>Versi aktif; arsip digantikan tidak dihitung</small>
            </article>
        </div>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="judul-cakupan">
        <h2 id="judul-cakupan">Cakupan Pembelajaran</h2>
        <?php if ($ring['persen'] === null): ?>
            <p class="a-help">Belum ada pasangan siswa×TP NonEsensial yang wajib
                diikuti dalam cakupan filter ini. Tidak ditampilkan sebagai 0% atau 100%.</p>
        <?php else: ?>
            <div class="a-dashboard-pimpinan__meter">
                <progress max="100" value="<?= esc((string)$ring['persen'],'attr') ?>"
                    aria-label="Persentase TP selesai sah pada seluruh paket yang difilter"></progress>
                <strong><?= esc(number_format((float)$ring['persen'],1,',','.')) ?>%</strong>
            </div>
            <p class="a-help"><?= (int)$ring['tuntas_sah'] ?> dari
                <?= (int)$ring['pasangan_wajib'] ?> pasangan siswa×TP NonEsensial.
                Angka ini bukan rata-rata nilai maupun Nilai Akhir Semester.</p>
        <?php endif ?>
        <?php if ($ring['paket_kosong']>0): ?>
            <p class="a-dashboard-pimpinan__note">
                <?= (int)$ring['paket_kosong'] ?> paket aktif belum memiliki
                TP NonEsensial; paket tersebut tidak dianggap 100% selesai.
            </p>
        <?php endif ?>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="judul-tindak-lanjut">
        <h2 id="judul-tindak-lanjut">Penugasan Guru dan Antrean Penilaian</h2>
        <div class="a-dashboard-pimpinan__operasional">
            <div>
                <strong><?= (int)$ring['guru_ditugaskan'] ?> /
                    <?= (int)$ring['pasangan_gen_mapel'] ?></strong>
                <span>Pasangan Gen–Mapel memiliki Guru yang ditugaskan</span>
            </div>
            <div>
                <strong><?= (int)$ring['antrean_penilaian'] ?></strong>
                <span>Jawaban final Resume/HOTS yang belum dinilai Guru</span>
            </div>
        </div>
        <p class="a-help">Angka antrean berasal dari jawaban final yang belum berskor,
            bukan semua draf siswa. Pimpinan hanya memantau; penilaian dilakukan
            Guru melalui menu Antrean Penilaian.</p>
    </section>

    <section class="a-card a-jarak-atas" aria-labelledby="judul-paket-monitor">
        <h2 id="judul-paket-monitor">Ringkasan per Gen–Mapel–Semester</h2>
        <p class="a-help">Nilai Pra-Semester berdiri sendiri sebagai checkpoint,
            tidak dihitung dalam progres TP maupun sertifikat.
            Halaman <?= (int)$dashboard['halaman'] ?> dari
            <?= (int)$dashboard['jumlah_halaman'] ?>.</p>
        <?php if ($dashboard['baris'] === []): ?>
            <div class="a-alert" role="status">Tidak ada paket aktif untuk
                kombinasi filter yang dipilih.</div>
        <?php else: ?>
            <div class="a-tabel-gulir a-dashboard-pimpinan__scroll" tabindex="0"
                role="region" aria-label="Tabel ringkasan akademik dapat digulir">
                <table class="a-tbl a-dashboard-pimpinan__table">
                    <thead><tr>
                        <th scope="col">Gen / Mapel</th>
                        <th scope="col">Semester</th>
                        <th scope="col">Siswa Wajib</th>
                        <th scope="col">TP NonEsensial</th>
                        <th scope="col">TP Selesai Sah</th>
                        <th scope="col">Nilai Final</th>
                        <th scope="col">Sertifikat</th>
                        <th scope="col">Antrean Resume/HOTS</th>
                        <th scope="col">Pra-Semester</th>
                        <th scope="col">Aksi</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($dashboard['baris'] as $p): ?>
                        <tr>
                            <th scope="row"><strong><?= esc($p['nama_gen']) ?></strong>
                                <small><?= esc($p['nama_mapel']) ?></small>
                            </th>
                            <td><?= (int)$p['semester'] ?></td>
                            <td><?= (int)$p['siswa'] ?></td>
                            <td><?= (int)$p['tp_wajib'] ?></td>
                            <td>
                                <?= (int)$p['tuntas_sah'] ?> / <?= (int)$p['pasangan_wajib'] ?>
                                <?php if ($p['persen']!==null): ?>
                                    <span class="a-dashboard-pimpinan__percentage">
                                        <?= esc(number_format((float)$p['persen'],1,',','.')) ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="a-help">Belum ada target</span>
                                <?php endif ?>
                            </td>
                            <td><?= (int)$p['nilai_final'] ?></td>
                            <td><?= (int)$p['sertifikat_berlaku'] ?></td>
                            <td><?= (int)$p['antrean_penilaian'] ?></td>
                            <td>
                                <?php $status=$p['pra_status']; ?>
                                <?= esc(match ($status) {
                                    'draf'=>'Draf',
                                    'siap'=>'Siap',
                                    'ditutup'=>'Ditutup',
                                    default=>'Belum tersedia',
                                }) ?>
                            </td>
                            <td><a class="a-btn a-btn--sekunder"
                                href="<?= esc(site_url('pimpinan/laporan').'?'.http_build_query([
                                    'paket'=>$p['id'],
                                ]),'attr') ?>">Lihat Monitoring</a></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        <?php if ($dashboard['jumlah_halaman']>1): ?>
            <nav class="a-dashboard-pimpinan__pager" aria-label="Halaman ringkasan paket">
                <?php if ($dashboard['halaman']>1): ?>
                    <a class="a-btn a-btn--sekunder"
                        href="<?= esc($urlHalaman($dashboard['halaman']-1),'attr') ?>">Sebelumnya</a>
                <?php endif ?>
                <span>Halaman <?= (int)$dashboard['halaman'] ?></span>
                <?php if ($dashboard['halaman']<$dashboard['jumlah_halaman']): ?>
                    <a class="a-btn a-btn--sekunder"
                        href="<?= esc($urlHalaman($dashboard['halaman']+1),'attr') ?>">Berikutnya</a>
                <?php endif ?>
            </nav>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
