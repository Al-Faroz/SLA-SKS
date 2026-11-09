<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
$nilaiTampil = static fn (mixed $v): string =>
    $v === null ? '—' : number_format((float)$v,2,',','.');
$urlFilter = static fn (int $page): string =>
    site_url('guru/rekap-nilai/pra-semester') . '?' . http_build_query([
        'pilihan'=>$pilihan_aktif,'semester'=>$semester,'halaman'=>$page,
    ]);
?>
<div class="a-page a-rekap-page a-rekap-page--pra">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'),'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <span>Rekap Nilai</span>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Nilai Pra-Semester</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Rekap Ujian Terjadwal</p>
            <h1>Rekap Nilai Pra-Semester</h1>
            <p class="a-proto-muted">Nilai Pra-Semester terpisah dari Nilai TP.</p>
        </div>
    </header>
    <?php if ($pesan=session()->getFlashdata('rekap_galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>
    <nav class="a-rekap-switch" aria-label="Jenis rekap nilai">
        <a class="a-rekap-switch__link"
            href="<?= esc(site_url('guru/rekap-nilai'),'attr') ?>">Nilai TP</a>
        <a class="a-rekap-switch__link is-aktif" aria-current="page"
            href="<?= esc(site_url('guru/rekap-nilai/pra-semester'),'attr') ?>">Nilai Pra-Semester</a>
    </nav>
    <section class="a-card a-jarak-atas" aria-labelledby="filter-pra">
        <h2 id="filter-pra">Filter Nilai Pra-Semester</h2>
        <?php if ($penugasan === []): ?>
            <div class="a-alert" role="status">Belum ada penugasan Gen–Mapel aktif.</div>
        <?php else: ?>
            <form method="get" action="<?= esc(site_url('guru/rekap-nilai/pra-semester'),'attr') ?>"
                class="a-form">
                <div class="a-filter-rekap-grid a-filter-rekap-grid--dua">
                    <div class="a-field">
                        <label class="a-label" for="pra-gen">Gen – Mata Pelajaran</label>
                        <select class="a-input" id="pra-gen" name="pilihan" required>
                            <?php foreach ($penugasan as $i=>$p): ?>
                                <option value="<?= (int)$i ?>" <?= $pilihan_aktif === $i ? 'selected' : '' ?>>
                                    <?= esc($p['nama_gen'].' — '.$p['nama_mapel']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="pra-semester">Semester</label>
                        <select class="a-input" id="pra-semester" name="semester" required>
                            <?php for ($s=1;$s<=6;$s++): ?>
                                <option value="<?= $s ?>" <?= $semester === $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                            <?php endfor ?>
                        </select>
                    </div>
                </div>
                <div class="a-penilaian-aksi">
                    <button class="a-btn a-btn--utama" type="submit">Tampilkan Rekap</button>
                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/rekap-nilai/pra-semester'),'attr') ?>">Reset Filter</a>
                </div>
            </form>
        <?php endif ?>
    </section>
    <?php if ($dipilih !== null): ?>
        <section class="a-card a-jarak-atas" aria-labelledby="tabel-pra">
            <h2 id="tabel-pra">Nilai Pra-Semester · Semester <?= (int)$semester ?></h2>
            <p class="a-help"><strong><?= esc($dipilih['nama_gen']) ?></strong> ·
                <?= esc($dipilih['nama_mapel']) ?>.
                <?= (int)$rekap['total'] ?> siswa · Halaman <?= (int)$rekap['halaman'] ?>
                dari <?= (int)$rekap['jumlah_halaman'] ?>.</p>
            <?php if ($rekap['ujian_id'] === null): ?>
                <div class="a-alert" role="status">Paket ini belum mempunyai ujian Pra-Semester.
                    Semua siswa tetap tercantum tanpa nilai.</div>
            <?php endif ?>
            <div class="a-penilaian-aksi">
                <a class="a-btn a-btn--utama"
                    href="<?= esc(site_url('guru/rekap-nilai/pra-semester/unduh').'?'.http_build_query([
                        'pilihan'=>$pilihan_aktif,'semester'=>$semester,
                    ]),'attr') ?>">Unduh Nilai Pra-Semester (.xlsx)</a>
            </div>
            <?php if ($rekap['baris'] === []): ?>
                <div class="a-alert" role="status">Tidak ada siswa pada Gen ini.</div>
            <?php else: ?>
                <p class="a-rekap-scroll-hint">Geser tabel ke samping untuk melihat seluruh kolom nilai.</p>
                <div class="a-tabel-gulir a-rekap-scroll" tabindex="0" role="region"
                    aria-label="Tabel nilai Pra-Semester dapat digulir horizontal">
                    <table class="a-tbl a-rekap-table a-rekap-table--pra">
                        <thead><tr>
                            <th scope="col">Siswa</th>
                            <th scope="col">Semester</th>
                            <th scope="col">KKM</th>
                            <th scope="col">Normal</th>
                            <th scope="col">Remedial</th>
                            <th scope="col">Nilai Terbaik</th>
                            <th scope="col">Status</th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($rekap['baris'] as $r): ?>
                                <tr>
                                    <th scope="row" class="a-rekap-table__siswa"><?= esc($r['nama_siswa']) ?></th>
                                    <td class="a-rekap-table__angka"><?= (int)$r['semester'] ?></td>
                                    <td class="a-rekap-table__angka"><?= $r['kkm'] === null ? '—' : (int)$r['kkm'] ?></td>
                                    <?php foreach (['skor_normal','skor_remedial','nilai_terbaik'] as $kolom): ?>
                                        <td class="a-rekap-table__angka">
                                            <?php if ($r[$kolom] === null): ?>
                                                <span class="a-rekap-table__kosong">—</span>
                                            <?php else: ?>
                                                <strong><?= esc($nilaiTampil($r[$kolom])) ?></strong>
                                            <?php endif ?>
                                        </td>
                                    <?php endforeach ?>
                                    <td>
                                        <span class="a-rekap-status <?= ($r['status_nilai'] ?? '') === 'final'
                                            ? 'a-rekap-status--final' : (($r['status_nilai'] ?? '') === 'sementara'
                                            ? 'a-rekap-status--sementara' : 'a-rekap-status--netral') ?>">
                                            <?= esc($r['status_label']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
            <p class="a-help">Tanda — berarti belum memiliki skor sah, bukan angka 0.
                Nilai terbaik 0,00 tetap sah. Tidak ada Nilai Akhir Semester.</p>
            <?php if ($rekap['jumlah_halaman'] > 1): ?>
                <nav class="a-penilaian-pager" aria-label="Halaman rekap Pra-Semester">
                    <?php if ($rekap['halaman'] > 1): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc($urlFilter((int)$rekap['halaman']-1),'attr') ?>">Sebelumnya</a>
                    <?php endif ?>
                    <strong>Halaman <?= (int)$rekap['halaman'] ?></strong>
                    <?php if ($rekap['halaman'] < $rekap['jumlah_halaman']): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc($urlFilter((int)$rekap['halaman']+1),'attr') ?>">Berikutnya</a>
                    <?php endif ?>
                </nav>
            <?php endif ?>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
