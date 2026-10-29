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

    <?php $galat = session()->getFlashdata('galat'); $sukses = session()->getFlashdata('sukses'); ?>
    <?php if ($galat): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div><?php endif ?>
    <?php if ($sukses): ?><div class="a-alert" role="status"><?= esc($sukses) ?></div><?php endif ?>

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
                    Mulai Semester <?= (int) $dipilih['semester_mulai'] ?>.
                    Tahun Ajaran selalu mengikuti paket masing-masing semester.</p>
            <?php endif ?>
        </section>

        <section class="a-card" aria-labelledby="judul-paket-atp">
            <h2 id="judul-paket-atp">2. Kesiapan enam semester</h2>
            <p class="a-proto-muted">Paket semester harus sudah dibuat lebih dahulu melalui halaman
                Semester. Semester yang belum memiliki paket tidak dapat menjadi sasaran impor.
                Paket acuan digunakan hanya untuk memastikan Kurikulum dan CP workbook.</p>
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
                                <?php if ($dipilih !== null && $n < (int) $dipilih['semester_mulai']): ?>
                                    Dilewati Gen
                                <?php elseif ($st['paket'] === 0): ?>
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
        </section>

        <section class="a-card" aria-labelledby="judul-atp-file">
            <h2 id="judul-atp-file">3. Unduh dan pratinjau ATP</h2>
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
                <p class="a-proto-muted">Template versi V25-2 menggunakan delapan kolom TP tanpa Kode TP dan Elemen CP. Jika file bernama tanpa penanda V25-2, jangan digunakan. Isi hanya semester sasaran yang masih kosong. Workbook sama
                    boleh diunggah ulang untuk pemeriksaan, tetapi tidak akan menulis data duplikat.</p>
                <form class="a-form" method="post" enctype="multipart/form-data"
                    action="<?= esc(site_url('guru/peta-atp/pratinjau/' . (int) $paketId), 'attr') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="pilihan" value="<?= (int) $nomorPilihan ?>">
                    <div class="a-field">
                        <label class="a-label" for="atp-excel-pratinjau">File ATP resmi (.xlsx, maksimal 5 MB)</label>
                        <input id="atp-excel-pratinjau" name="atp_excel" type="file"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                    </div>
                    <div class="a-atp-detail__toolbar-actions">
                        <button class="a-btn a-btn--utama" type="submit">Periksa &amp; Pratinjau Excel</button>
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
<?= $this->endSection() ?>
