<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/peta-atp') . '?pilihan=' . (int) $nomorPilihan, 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span><span aria-current="page">Kelola Excel</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Peta ATP enam semester</p>
            <h1>Kelola ATP Excel</h1>
            <p class="a-proto-muted">Satu workbook untuk Semester 1–6. Excel hanya untuk
                <strong>penyusunan awal</strong>; setelah BAB/TP tersimpan, kelola melalui aplikasi.</p>
        </div>
    </header>

    <?php
        // CSRF CI4 menggunakan key "error", Service akademik memakai "galat".
        // Keduanya WAJIB terlihat; sebelumnya penolakan CSRF diam-diam hilang.
        $galat = session()->getFlashdata('galat') ?: session()->getFlashdata('error');
        $sukses = session()->getFlashdata('sukses');
    ?>
    <?php if ($galat && $pilihan === []): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>
    <?php if ($sukses && $pilihan === []): ?>
        <div class="a-alert" role="status"><?= esc($sukses) ?></div>
    <?php endif ?>

    <?php if ($pilihan === []): ?>
        <section class="a-card"><div class="a-empty" role="status">
            <h2>Belum ada penugasan Gen–Mapel</h2>
            <p>Hubungi Admin sebelum mengunduh atau mengimpor ATP.</p>
        </div></section>
    <?php else: ?>
        <section class="a-card" aria-labelledby="pilih-konteks">
            <h2 id="pilih-konteks">1. Tentukan Gen–Mapel dan paket acuan</h2>
            <form class="a-filter" method="get" action="<?= esc(site_url('guru/peta-atp/impor'), 'attr') ?>">
                <div class="a-field">
                    <label class="a-label" for="impor-pilih-g1">Gen–Mapel</label>
                    <select id="impor-pilih-g1" name="pilihan" required>
                        <?php foreach ($pilihan as $i => $data): ?>
                            <option value="<?= (int) $i ?>" <?= (int) $nomorPilihan === (int) $i ? 'selected' : '' ?>>
                                <?= esc($data['kode_gen'] . ' — ' . $data['nama_mapel']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button class="a-btn a-btn--sekunder" type="submit">Tampilkan</button>
            </form>
            <?php if ($dipilih !== null): ?>
                <p class="a-proto-muted">Gen: <strong><?= esc($dipilih['nama_gen']) ?></strong> ·
                    Mapel: <strong><?= esc($dipilih['nama_mapel']) ?></strong> ·
                    Akses siswa mulai Semester <?= (int) $dipilih['semester_mulai'] ?>.
                    Guru boleh menyusun ATP Semester 1–6; Tahun Ajaran mengikuti paket tiap semester.</p>
            <?php endif ?>
        </section>

        <section class="a-card" aria-labelledby="judul-paket-atp">
            <h2 id="judul-paket-atp">2. Kesiapan enam semester</h2>
            <p class="a-proto-muted">Semua semester 1–6 dapat disusun Guru, termasuk semester
                sebelum awal belajar Gen. Untuk penyimpanan, setiap semester memerlukan paket draf
                dengan Tahun Ajaran yang Anda tentukan. Paket tidak dibuat otomatis dari Excel
                agar tidak salah kurikulum atau tahun. Paket acuan hanya untuk verifikasi CP.</p>
            <div class="a-tabel-gulir">
                <table class="a-tbl">
                    <thead><tr>
                        <th scope="col">Semester</th>
                        <th scope="col">Paket / Tahun Ajaran</th>
                        <th scope="col">BAB</th>
                        <th scope="col">TP</th>
                        <th scope="col">Status</th>
                        <th scope="col">Tindakan</th>
                    </tr></thead>
                    <tbody>
                    <?php for ($n = 1; $n <= 6; ++$n): ?>
                        <?php $st = $statusSemester[$n] ?? ['bab'=>0,'tp'=>0,'paket'=>0]; ?>
                        <tr>
                            <th scope="row">Semester <?= $n ?></th>
                            <td>
                                <?php if (($st['tahun_ids'] ?? []) === []): ?>
                                    —
                                <?php else: ?>
                                    <?= esc(implode(', ', array_map(
                                        static fn (int $id): string =>
                                            (string) ($namaTahun[$id] ?? 'Tahun Ajaran #' . $id),
                                        $st['tahun_ids']
                                    ))) ?>
                                <?php endif ?>
                            </td>
                            <td><?= (int) $st['bab'] ?></td>
                            <td><?= (int) $st['tp'] ?></td>
                            <td>
                                <?php if ($st['paket'] === 0): ?>
                                    Belum ada paket
                                <?php elseif ($st['paket'] > 1): ?>
                                    Paket ganda — perlu diperiksa
                                <?php elseif ($st['bab'] || $st['tp']): ?>
                                    Sudah diisi — impor terkunci
                                <?php else: ?>
                                    Kosong — dapat diimpor
                                <?php endif ?>
                            </td>
                            <td><a href="<?= esc(site_url('guru/peta-atp/semester/' . $n)
                                . '?pilihan=' . (int) $nomorPilihan, 'attr') ?>">Kelola semester</a></td>
                        </tr>
                    <?php endfor ?>
                    </tbody>
                </table>
            </div>
            <?php
            $tanpaPaket = [];
            for ($n = 1; $n <= 6; $n++) {
                if (($statusSemester[$n]['paket'] ?? 0) === 0) {
                    $tanpaPaket[] = $n;
                }
            }
            ?>
            <?php if ($tanpaPaket !== [] && $dipilih !== null
                && $opsiTahunPaket !== [] && $opsiKurikulumPaket !== []): ?>
                <div class="a-atp-detail__paket-persiapan">
                    <h3>Siapkan paket semester yang belum ada</h3>
                    <p class="a-proto-muted">Pilih semester dan Tahun Ajaran yang benar.
                        Paket disimpan sebagai <strong>draf</strong> dan tidak membuka akses siswa.
                        Ulangi untuk setiap semester yang akan Anda impor.</p>
                    <form class="a-form" method="post"
                        action="<?= esc(site_url('guru/paket-semester/baru'), 'attr') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="kembali_atp" value="1">
                        <input type="hidden" name="pilihan" value="<?= (int) $nomorPilihan ?>">
                        <input type="hidden" name="gen_id" value="<?= (int) $dipilih['angkatan_id'] ?>">
                        <input type="hidden" name="mapel_id" value="<?= (int) $dipilih['mapel_id'] ?>">
                        <div class="a-field">
                            <label class="a-label" for="atp-paket-semester">Semester</label>
                            <select id="atp-paket-semester" name="semester" required>
                                <option value="">Pilih semester tanpa paket</option>
                                <?php foreach ($tanpaPaket as $semesterBaru): ?>
                                    <option value="<?= (int) $semesterBaru ?>">
                                        Semester <?= (int) $semesterBaru ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="a-field">
                            <label class="a-label" for="atp-paket-tahun">Tahun Ajaran</label>
                            <select id="atp-paket-tahun" name="tahun_id" required>
                                <option value="">Pilih Tahun Ajaran</option>
                                <?php foreach ($opsiTahunPaket as $tahunBaru): ?>
                                    <option value="<?= (int) $tahunBaru['id'] ?>">
                                        <?= esc($tahunBaru['nama']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="a-field">
                            <label class="a-label" for="atp-paket-kurikulum">Kurikulum</label>
                            <select id="atp-paket-kurikulum" name="kurikulum_id" required>
                                <option value="">Pilih kurikulum sesuai paket acuan</option>
                                <?php foreach ($opsiKurikulumPaket as $kbaru): ?>
                                    <option value="<?= (int) $kbaru['id'] ?>">
                                        <?= esc($kbaru['nama'] . ' — ' . $kbaru['versi']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="a-atp-detail__toolbar-actions">
                            <button class="a-btn a-btn--sekunder" type="submit">
                                + Buat Paket Draf Semester
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif ?>
        </section>

        <section class="a-card" aria-labelledby="judul-atp-file" id="atp-upload-area">
            <h2 id="judul-atp-file">3. Unduh dan pratinjau ATP</h2>
            <?php if ($galat): ?>
                <div class="a-alert a-alert--bahaya" role="alert"
                    data-atp-flash-error tabindex="-1"><?= esc($galat) ?></div>
            <?php endif ?>
            <?php if ($sukses): ?>
                <div class="a-alert" role="status" data-atp-flash-success><?= esc($sukses) ?></div>
            <?php endif ?>
            <?php if ($opsiPaket === []): ?>
                <div class="a-empty" role="status">
                    <h3>Belum tersedia paket sebagai acuan</h3>
                    <p>Buat paket draf pada salah satu semester yang dapat diisi,
                        kemudian kembali ke halaman ini.</p>
                </div>
            <?php else: ?>
                <form class="a-filter" method="get" action="<?= esc(site_url('guru/peta-atp/impor'), 'attr') ?>">
                    <input type="hidden" name="pilihan" value="<?= (int) $nomorPilihan ?>">
                    <div class="a-field">
                        <label class="a-label" for="pilih-paket-atp">Paket acuan / Kurikulum</label>
                        <select id="pilih-paket-atp" name="paket_id" required>
                            <?php foreach ($opsiPaket as $paket): ?>
                                <option value="<?= (int) $paket['id'] ?>" <?= (int) $paketId === (int) $paket['id'] ? 'selected' : '' ?>>
                                    <?= esc('Semester ' . $paket['semester']
                                        . ' · ' . ($namaTahun[$paket['tahun_ajaran_id']] ?? 'Tahun Ajaran ' . $paket['tahun_ajaran_id'])
                                        . ' · ' . ($namaKurikulum[$paket['kurikulum_id']] ?? 'Kurikulum ' . $paket['kurikulum_id'])) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <button type="submit" class="a-btn a-btn--sekunder">Pilih paket</button>
                </form>
                <div class="a-atp-detail__toolbar-actions">
                    <a class="a-btn a-btn--sekunder"
                        href="<?= esc(site_url('guru/peta-atp/unduh/' . (int) $paketId), 'attr') ?>">
                        Unduh Template ATP V25-2 · 6 Semester
                    </a>
                </div>
                <p class="a-proto-muted">Template versi V25-2 menggunakan delapan kolom TP tanpa Kode TP dan Elemen CP. Jika file bernama tanpa penanda V25-2, jangan digunakan. Guru boleh mengisi enam semester sekaligus; pastikan paket dan Tahun Ajaran tersedia. Isi semester yang masih kosong. Workbook sama
                    boleh diunggah ulang untuk pemeriksaan, tetapi tidak akan menulis data duplikat.</p>
                <form class="a-form" method="post" enctype="multipart/form-data"
                    data-atp-upload-form
                    action="<?= esc(site_url('guru/peta-atp/pratinjau/' . (int) $paketId), 'attr') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="pilihan" value="<?= (int) $nomorPilihan ?>">
                    <div class="a-field">
                        <label class="a-label" for="atp-excel-pratinjau">File ATP resmi (.xlsx, maksimal 5 MB)</label>
                        <input id="atp-excel-pratinjau" name="atp_excel" type="file"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                        <p class="a-proto-muted" data-atp-upload-status role="status" aria-live="polite">
                            Pilih file Excel ATP V25-2 (.xlsx). Pratinjau tidak menyimpan data.
                        </p>
                    </div>
                    <div class="a-atp-detail__toolbar-actions">
                        <button class="a-btn a-btn--utama" type="submit" data-atp-upload-submit>
                            Periksa &amp; Pratinjau Excel
                        </button>
                    </div>
                </form>
            <?php endif ?>
        </section>
        <section class="a-card" aria-labelledby="judul-impor-aturan">
            <h2 id="judul-impor-aturan">Aturan impor</h2>
            <p>Impor hanya menambahkan BAB dan TP pada semester dengan paket sah yang
                <strong>belum mempunyai satu pun BAB atau TP</strong>. Jika sudah diisi manual,
                terdapat Materi, atau sudah digunakan siswa, perubahan lewat Excel ditolak.
                File yang identik dengan seluruh data tersimpan hanya menghasilkan status
                <strong>Tetap</strong> tanpa penyimpanan ulang.</p>
            <p>Pratinjau tidak menyimpan data. Konfirmasi menyimpan semua semester sasaran
                dalam satu transaksi; satu galat membatalkan semuanya. Excel tidak
                mengunggah media, Materi, soal, atau hasil pembelajaran.</p>
        </section>
    <?php endif ?>
</div>
<?php $atpJsPath = FCPATH . 'public/assets/js/atp-upload.js'; ?>
<script src="<?= esc(base_url('public/assets/js/atp-upload.js')
    . '?v=' . (is_file($atpJsPath) ? (string) filemtime($atpJsPath) : '1'), 'attr') ?>" defer></script>
<?= $this->endSection() ?>
