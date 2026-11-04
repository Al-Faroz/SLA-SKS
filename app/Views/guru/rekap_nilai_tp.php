<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
$formatNilai = static fn (array $baris, string $kolom): string =>
    \App\Services\RekapNilaiTpGuruService::teksKomponen($baris, $kolom);
$statusNilai = static fn (array $baris): string =>
    \App\Services\RekapNilaiTpGuruService::labelNilai(
        $baris['status_nilai'] ?? null, $baris['jenis_tp'] === 'esensial');
?>
<div class="a-page a-rekap-page a-rekap-page--tp">
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

    <nav class="a-rekap-switch" aria-label="Jenis rekap nilai">
        <a class="a-rekap-switch__link is-aktif" aria-current="page"
            href="<?= esc(site_url('guru/rekap-nilai'), 'attr') ?>">Nilai TP</a>
        <a class="a-rekap-switch__link"
            href="<?= esc(site_url('guru/rekap-nilai/pra-semester'), 'attr') ?>">Nilai Pra-Semester</a>
    </nav>

    <section class="a-card" aria-labelledby="filter-rekap">
        <h2 id="filter-rekap">Filter Rekap Nilai TP</h2>
        <?php if ($penugasan === []): ?>
            <div class="a-alert" role="status">Belum ada penugasan Gen–Mapel aktif untuk akun Guru ini.
                Hubungi Admin untuk pemeriksaan penugasan.</div>
        <?php else: ?>
            <form method="get" action="<?= esc(site_url('guru/rekap-nilai'), 'attr') ?>" class="a-form">
                <div class="a-filter-rekap-grid">
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
                    <div class="a-field">
                        <label class="a-label" for="pilih-jenis-tp">Jenis TP</label>
                        <select class="a-input" id="pilih-jenis-tp" name="jenis_tp">
                            <option value="non_esensial" <?= $jenisTp === 'non_esensial' ? 'selected' : '' ?>>NonEsensial (bernilai)</option>
                            <option value="esensial" <?= $jenisTp === 'esensial' ? 'selected' : '' ?>>Esensial</option>
                            <option value="semua" <?= $jenisTp === 'semua' ? 'selected' : '' ?>>Semua jenis TP</option>
                        </select>
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="pilih-rekap-bab">BAB</label>
                        <select class="a-input" id="pilih-rekap-bab" name="bab">
                            <option value="0" <?= $bab === 0 ? 'selected' : '' ?>>Semua BAB</option>
                            <?php foreach ($daftarBab as $b): ?>
                                <option value="<?= (int)$b['id'] ?>" <?= $bab === (int)$b['id'] ? 'selected' : '' ?>>
                                    <?= esc('BAB '.$b['nomor_bab'].' — '.$b['nama_bab']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="a-penilaian-aksi">
                    <button type="submit" class="a-btn a-btn--utama">Tampilkan Rekap</button>
                    <a href="<?= esc(site_url('guru/rekap-nilai'), 'attr') ?>" class="a-btn a-btn--sekunder">Reset Filter</a>
                </div>
                <script>
                (() => {
                    const form=document.getElementById('pilih-rekap-gen')?.form;
                    if (!form) return;
                    for (const id of ['pilih-rekap-gen','pilih-rekap-semester']) {
                        form.querySelector('#'+id)?.addEventListener('change',()=>{
                            form.querySelector('#pilih-rekap-bab').value='0';
                            form.submit();
                        });
                    }
                })();
                </script>
            </form>
        <?php endif ?>
    </section>

    <?php if ($dipilih !== null): ?>
        <section class="a-card a-jarak-atas" aria-labelledby="ringkasan-rekap">
            <h2 id="ringkasan-rekap">Ringkasan Semester <?= (int) $semester ?></h2>
            <p class="a-help"><strong><?= esc((string) $dipilih['nama_gen']) ?></strong> ·
                <?= esc((string) $dipilih['nama_mapel']) ?>.
                Hasil berdasarkan data yang telah tersimpan di server, tanpa menghitung ulang nilai historis.</p>
            <dl class="a-rekap-metrik" aria-label="Ringkasan nilai semester">
                <div class="a-rekap-metrik__item">
                    <dt>Siswa dalam Gen</dt><dd><?= (int) $rekap['jumlah_siswa'] ?></dd>
                </div>
                <div class="a-rekap-metrik__item a-rekap-metrik__item--final">
                    <dt>Nilai Final</dt><dd><?= (int) $rekap['jumlah_final'] ?></dd>
                </div>
                <div class="a-rekap-metrik__item a-rekap-metrik__item--sementara">
                    <dt>Nilai Sementara</dt><dd><?= (int) $rekap['jumlah_sementara'] ?></dd>
                </div>
                <div class="a-rekap-metrik__item a-rekap-metrik__item--belum">
                    <dt>Belum Ada Nilai</dt><dd><?= (int) $rekap['jumlah_belum'] ?></dd>
                </div>
            </dl>
            <p class="a-help">Angka Final/Sementara/Belum Ada menghitung pasangan siswa–TP
                NonEsensial sesuai filter, bukan jumlah siswa unik. TP Esensial tidak memiliki nilai.
                Tidak ada perhitungan nilai akhir semester.</p>
        </section>

        <section class="a-card a-jarak-atas" aria-labelledby="ekspor-nilai-tp">
            <h2 id="ekspor-nilai-tp">Unduh Nilai Excel (.xlsx)</h2>
            <p class="a-help">Unduhan mencakup <strong>semua siswa Gen</strong> dan nilai TP sesuai
                filter Jenis TP serta BAB untuk semester ini. Nilai yang belum tersedia tetap kosong,
                bukan 0. Unduhan tidak dibatasi 25 baris halaman ini.</p>
            <?php
                $urlEkspor = static fn (int $cakupan): string =>
                    site_url('guru/rekap-nilai/unduh') . '?' . http_build_query([
                        'pilihan' => $pilihan_aktif,
                        'semester' => $cakupan,
                        'jenis_tp' => $jenisTp,
                        'bab' => $cakupan === 0 ? 0 : $bab,
                    ]);
            ?>
            <div class="a-penilaian-aksi">
                <a class="a-btn a-btn--utama"
                    href="<?= esc($urlEkspor((int) $semester), 'attr') ?>">
                    <span aria-hidden="true">↓</span> Unduh Semester <?= (int) $semester ?>
                </a>
                <a class="a-btn a-btn--sekunder"
                    href="<?= esc($urlEkspor(0), 'attr') ?>">
                    Unduh Semua Semester 1–6
                </a>
            </div>
            <p class="a-help">Unduh Semua Semester mengikuti filter Jenis TP, tanpa batasan BAB
                yang hanya berlaku pada semester terpilih. Nilai Pra-Semester akan memakai
                halaman dan file unduhan tersendiri, tidak dicampur dengan Nilai TP.</p>
        </section>

        <section class="a-card a-jarak-atas" aria-labelledby="daftar-rekap">
            <h2 id="daftar-rekap">Daftar Nilai per Siswa dan TP</h2>
            <p class="a-help">Jumlah pasangan siswa–TP: <?= (int) $rekap['total'] ?>.
                Halaman <?= (int) $rekap['halaman'] ?> dari <?= (int) $rekap['jumlah_halaman'] ?>.
                Nilai 0,00 adalah skor sah; “Belum ada” berarti belum tersimpan.</p>
            <?php if ($rekap['baris'] === []): ?>
                <div class="a-alert" role="status">
                    Tidak ada pasangan siswa dan TP yang sesuai dengan filter ini.
                    Pilih jenis TP atau BAB lain, atau pastikan data telah tersedia.
                </div>
            <?php else: ?>
                <p class="a-rekap-scroll-hint">Geser tabel ke samping untuk melihat seluruh komponen nilai.</p>
                <div class="a-tabel-gulir a-rekap-scroll" tabindex="0" role="region"
                    aria-label="Tabel nilai TP dapat digulir secara horizontal">
                    <table class="a-tbl a-rekap-table a-rekap-table--tp">
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
                                    <th scope="row" class="a-rekap-table__siswa">
                                        <?= esc((string) $r['nama_siswa']) ?>
                                    </th>
                                    <td class="a-rekap-table__tp">
                                        <span class="a-rekap-table__meta">BAB <?= (int) ($r['nomor_bab'] ?? 0) ?> ·
                                            <?= esc((string) ($r['nama_bab'] ?? 'Belum ditetapkan')) ?></span>
                                        <a href="<?= esc(site_url('guru/tp/' . (int) $r['tp_id']), 'attr') ?>">
                                            <?= esc((string) $r['kode_tp']) ?></a>
                                        <span class="a-rekap-table__nama-tp"><?= esc((string) $r['nama_tp']) ?></span>
                                    </td>
                                    <td><span class="a-rekap-status a-rekap-status--netral"><?= $r['jenis_tp'] === 'esensial' ? 'Esensial' : 'NonEsensial' ?></span></td>
                                    <td><?= $r['jenis_tp'] === 'esensial' ? 'Informasi saja'
                                        : esc(str_replace('_', ' ', (string) ($r['status_progres'] ?? 'Belum mulai'))) ?></td>
                                    <td><?= $r['jenis_tp'] === 'esensial' ? '—'
                                        : esc(match ($r['jalur'] ?? null) {
                                            'lulus_diagnostik' => 'Lulus Diagnostik',
                                            'belajar_wajib' => 'Belajar wajib',
                                            default => 'Belum diketahui',
                                        }) ?></td>
                                    <?php foreach (['nilai_resume','nilai_hots','nilai_sumatif','nilai_tp'] as $kolom): ?>
                                        <?php $angkaNilai = $formatNilai($r, $kolom); ?>
                                        <td class="a-rekap-table__angka">
                                            <?php if (in_array($angkaNilai, ['Belum ada', 'Dilewati', '—'], true)): ?>
                                                <span class="a-rekap-table__kosong"><?= esc($angkaNilai) ?></span>
                                            <?php else: ?>
                                                <strong><?= esc($angkaNilai) ?></strong>
                                            <?php endif ?>
                                        </td>
                                    <?php endforeach ?>
                                    <td>
                                        <span class="a-rekap-status <?= ($r['status_nilai'] ?? '') === 'final'
                                            ? 'a-rekap-status--final' : (($r['status_nilai'] ?? '') === 'sementara'
                                            ? 'a-rekap-status--sementara' : 'a-rekap-status--netral') ?>">
                                            <?= esc($statusNilai($r)) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>

            <?php if ($rekap['jumlah_halaman'] > 1): ?>
                <?php
                $urlHalaman = static function (int $n) use ($pilihan_aktif, $semester, $jenisTp, $bab): string {
                    return site_url('guru/rekap-nilai') . '?' . http_build_query([
                        'pilihan'=>$pilihan_aktif,'semester'=>$semester,
                        'jenis_tp'=>$jenisTp,'bab'=>$bab,'halaman'=>$n,
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
