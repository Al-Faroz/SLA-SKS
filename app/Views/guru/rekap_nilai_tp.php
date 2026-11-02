<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
$formatNilai = static fn (array $baris, string $kolom): string =>
    \App\Services\RekapNilaiTpGuruService::teksKomponen($baris, $kolom);
$statusNilai = static fn (array $baris): string =>
    \App\Services\RekapNilaiTpGuruService::labelNilai(
        $baris['status_nilai'] ?? null, $baris['jenis_tp'] === 'esensial');
?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Rekap Nilai TP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Rekap Pembelajaran</p>
            <h1>Rekap Nilai Tujuan Pembelajaran</h1>
            <p class="a-proto-muted">Seluruh siswa dalam Gen–Mapel yang Anda ampu.
                Status selesai TP berbeda dari status nilai sementara/final;
                nilai yang belum tersedia bukan nol.</p>
        </div>
    </header>

    <?php if ($pesan = session()->getFlashdata('rekap_galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>

    <section class="a-card" aria-labelledby="filter-rekap">
        <h2 id="filter-rekap">Pilih Gen–Mapel dan Semester</h2>
        <?php if ($penugasan === []): ?>
            <div class="a-alert" role="status">Belum ada penugasan Gen–Mapel aktif untuk akun Guru ini.
                Hubungi Admin untuk pemeriksaan penugasan.</div>
        <?php else: ?>
            <form method="get" action="<?= esc(site_url('guru/rekap-nilai'), 'attr') ?>" class="a-form">
                <div class="a-grid a-grid--dua">
                    <div class="a-field">
                        <label class="a-label" for="pilih-rekap-gen">Gen – Mata Pelajaran</label>
                        <select class="a-input" id="pilih-rekap-gen" name="pilihan" required>
                            <?php foreach ($penugasan as $i => $p): ?>
                                <option value="<?= (int) $i ?>" <?= $pilihan_aktif === $i ? 'selected' : '' ?>>
                                    <?= esc((string) $p['nama_gen']) ?> · <?= esc((string) $p['nama_mapel']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="pilih-rekap-semester">Semester</label>
                        <select class="a-input" id="pilih-rekap-semester" name="semester" required>
                            <?php for ($s = 1; $s <= 6; $s++): ?>
                                <option value="<?= $s ?>" <?= $semester === $s ? 'selected' : '' ?>>
                                    Semester <?= $s ?>
                                </option>
                            <?php endfor ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="a-btn a-btn--utama">Tampilkan rekap</button>
            </form>
        <?php endif ?>
    </section>

    <?php if ($dipilih !== null): ?>
        <section class="a-card a-jarak-atas" aria-labelledby="ringkasan-rekap">
            <h2 id="ringkasan-rekap">Ringkasan Semester <?= (int) $semester ?></h2>
            <p class="a-help"><strong><?= esc((string) $dipilih['nama_gen']) ?></strong> ·
                <?= esc((string) $dipilih['nama_mapel']) ?>.
                Hasil berdasarkan data yang telah tersimpan di server, tanpa menghitung ulang nilai historis.</p>
            <dl class="a-siswa-materi-ringkas">
                <div><dt>Siswa Gen</dt><dd><?= (int) $rekap['jumlah_siswa'] ?></dd></div>
                <div><dt>Nilai Final</dt><dd><?= (int) $rekap['jumlah_final'] ?></dd></div>
                <div><dt>Nilai Sementara</dt><dd><?= (int) $rekap['jumlah_sementara'] ?></dd></div>
                <div><dt>Belum Ada Nilai</dt><dd><?= (int) $rekap['jumlah_belum'] ?></dd></div>
            </dl>
            <p class="a-help">Angka Final/Sementara/Belum Ada menghitung pasangan siswa–TP
                NonEsensial, bukan jumlah siswa unik. TP Esensial ditampilkan tanpa nilai.
                Tidak ada perhitungan nilai akhir semester.</p>
        </section>

        <section class="a-card a-jarak-atas" aria-labelledby="daftar-rekap">
            <h2 id="daftar-rekap">Daftar Nilai per Siswa dan TP</h2>
            <p class="a-help">Jumlah pasangan siswa–TP: <?= (int) $rekap['total'] ?>.
                Halaman <?= (int) $rekap['halaman'] ?> dari <?= (int) $rekap['jumlah_halaman'] ?>.
                Nilai 0,00 adalah skor sah; “Belum ada” berarti belum tersimpan.</p>
            <?php if ($rekap['baris'] === []): ?>
                <div class="a-alert" role="status">
                    Belum ada pasangan siswa dan TP pada Gen–Mapel Semester <?= (int) $semester ?>.
                    Pastikan paket, TP dan data siswa telah tersedia.
                </div>
            <?php else: ?>
                <div class="a-table-wrap" tabindex="0" role="region"
                    aria-label="Tabel nilai TP dapat digulir secara horizontal">
                    <table class="a-table">
                        <thead><tr>
                            <th scope="col">Siswa</th>
                            <th scope="col">BAB / TP</th>
                            <th scope="col">Jenis TP</th>
                            <th scope="col">Progres</th>
                            <th scope="col">Jalur</th>
                            <th scope="col">Resume</th>
                            <th scope="col">HOTS</th>
                            <th scope="col">Sumatif Terbaik</th>
                            <th scope="col">Nilai TP</th>
                            <th scope="col">Status Nilai</th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($rekap['baris'] as $r): ?>
                                <tr>
                                    <th scope="row"><?= esc((string) $r['nama_siswa']) ?></th>
                                    <td>
                                        <span class="a-help">BAB <?= (int) ($r['nomor_bab'] ?? 0) ?> ·
                                            <?= esc((string) ($r['nama_bab'] ?? 'Belum ditetapkan')) ?></span>
                                        <a href="<?= esc(site_url('guru/tp/' . (int) $r['tp_id']), 'attr') ?>">
                                            <?= esc((string) $r['kode_tp']) ?></a>
                                        <span class="a-help"><?= esc((string) $r['nama_tp']) ?></span>
                                    </td>
                                    <td><?= $r['jenis_tp'] === 'esensial' ? 'Esensial' : 'NonEsensial' ?></td>
                                    <td><?= $r['jenis_tp'] === 'esensial' ? 'Informasi saja'
                                        : esc(str_replace('_', ' ', (string) ($r['status_progres'] ?? 'Belum mulai'))) ?></td>
                                    <td><?= $r['jenis_tp'] === 'esensial' ? '—'
                                        : esc(match ($r['jalur'] ?? null) {
                                            'lulus_diagnostik' => 'Lulus Diagnostik',
                                            'belajar_wajib' => 'Belajar wajib',
                                            default => 'Belum diketahui',
                                        }) ?></td>
                                    <?php foreach (['nilai_resume','nilai_hots','nilai_sumatif','nilai_tp'] as $kolom): ?>
                                        <td><?= esc($formatNilai($r, $kolom)) ?></td>
                                    <?php endforeach ?>
                                    <td><?= esc($statusNilai($r)) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>

            <?php if ($rekap['jumlah_halaman'] > 1): ?>
                <?php
                $urlHalaman = static function (int $n) use ($pilihan_aktif, $semester): string {
                    return site_url('guru/rekap-nilai') . '?' . http_build_query([
                        'pilihan'=>$pilihan_aktif,'semester'=>$semester,'halaman'=>$n,
                    ]);
                };
                ?>
                <nav class="a-penilaian-pager" aria-label="Navigasi halaman rekap nilai">
                    <?php if ($rekap['halaman'] > 1): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc($urlHalaman((int) $rekap['halaman'] - 1), 'attr') ?>">Sebelumnya</a>
                    <?php endif ?>
                    <strong>Halaman <?= (int) $rekap['halaman'] ?> dari <?= (int) $rekap['jumlah_halaman'] ?></strong>
                    <?php if ($rekap['halaman'] < $rekap['jumlah_halaman']): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc($urlHalaman((int) $rekap['halaman'] + 1), 'attr') ?>">Berikutnya</a>
                    <?php endif ?>
                </nav>
            <?php endif ?>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
