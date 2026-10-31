<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span><span aria-current="page">Peta ATP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Peta Pembelajaran</p>
            <h1>Peta ATP enam semester</h1>
            <p class="a-proto-muted">Pilih semester untuk menyusun BAB dan TP. Ringkasan selalu mengikuti Gen–Mapel yang sedang diampu.</p>
        </div>
    </header>

    <?php $galat = session()->getFlashdata('galat'); $sukses = session()->getFlashdata('sukses'); ?>
    <?php if ($galat): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div><?php endif ?>
    <?php if ($sukses): ?><div class="a-alert" role="status"><?= esc($sukses) ?></div><?php endif ?>

    <?php if ($pilihan === []): ?>
        <section class="a-card"><div class="a-empty" role="status">
            <h2>Belum ada penugasan Guru</h2>
            <p>Hubungi Admin untuk menentukan Gen–Mapel sebelum mengakses Peta ATP.</p>
        </div></section>
    <?php else: ?>
        <section class="a-card a-atp-pilihan" aria-labelledby="judul-pilihan">
            <h2 id="judul-pilihan">Gen dan mata pelajaran</h2>
            <form method="get" action="<?= esc(site_url('guru/peta-atp'), 'attr') ?>" class="a-filter">
                <div class="a-field">
                    <label class="a-label" for="pilih-g1">Penugasan Anda</label>
                    <select id="pilih-g1" name="pilihan">
                        <?php foreach ($pilihan as $i => $item): ?>
                            <option value="<?= (int) $i ?>" <?= (int) $nomorPilihan === (int) $i ? 'selected' : '' ?>>
                                <?= esc($item['kode_gen'] . ' — ' . $item['nama_mapel']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Tampilkan peta</button>
            </form>
        </section>
        <section class="a-card" aria-labelledby="judul-draf-enam">
            <h2 id="judul-draf-enam">Pembuatan Draf · 6 Semester</h2>
            <p class="a-proto-muted">Siapkan seluruh paket ATP untuk Gen–Mapel yang dipilih,
                termasuk semester sebelum awal Gen. Penyiapan ini tidak membuka akses siswa.
                Paket draf/aktif yang sudah ada tidak akan diubah atau dibuat ulang.</p>
            <?php if (($opsiPaket['tahun'] ?? []) === [] || ($opsiPaket['kurikulum'] ?? []) === []): ?>
                <div class="a-empty" role="status">
                    <p>Referensi Tahun Ajaran atau Kurikulum dengan CP aktif belum tersedia.
                        Periksa pengaturan Admin dan skema database sebelum membuat draf.</p>
                </div>
            <?php elseif (count($paketSemester) === 6): ?>
                <div class="a-alert" role="status">Enam paket semester sudah tersedia.
                    Lanjutkan pengisian BAB/TP pada kartu semester atau kelola ATP Excel.</div>
            <?php else: ?>
                <form class="a-form" method="post"
                    action="<?= esc(site_url('guru/paket-semester/buat-enam'), 'attr') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="gen_id" value="<?= (int) $dipilih['angkatan_id'] ?>">
                    <input type="hidden" name="mapel_id" value="<?= (int) $dipilih['mapel_id'] ?>">
                    <input type="hidden" name="pilihan" value="<?= (int) $nomorPilihan ?>">
                    <div class="a-field">
                        <label class="a-label" for="draf-kurikulum">Kurikulum untuk enam semester</label>
                        <select id="draf-kurikulum" name="kurikulum_id" required>
                            <option value="">Pilih kurikulum</option>
                            <?php foreach ($opsiPaket['kurikulum'] as $kurikulum): ?>
                                <option value="<?= (int) $kurikulum['id'] ?>"
                                    <?= count($opsiPaket['kurikulum']) === 1 ? 'selected' : '' ?>>
                                    <?= esc($kurikulum['nama'] . ' — ' . $kurikulum['versi']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="a-atp-kartu-grid">
                        <?php for ($n = 1; $n <= 6; ++$n): ?>
                            <?php
                                $ada = $paketSemester[$n] ?? null;
                                $tahunAda = $ada === null ? 0 : (int) $ada['tahun_ajaran_id'];
                            ?>
                            <div class="a-field">
                                <label class="a-label" for="draf-tahun-<?= $n ?>">
                                    Semester <?= $n ?> · Kelas <?= esc(match ($n) { 1,2 => 'VII', 3,4 => 'VIII', default => 'IX' }) ?>
                                    <?= $ada !== null ? '(sudah ada)' : '(draf baru)' ?>
                                </label>
                                <?php if ($ada !== null): ?>
                                    <input type="hidden" name="tahun_id[<?= $n ?>]" value="<?= $tahunAda ?>">
                                    <p class="a-help" id="draf-info-<?= $n ?>">
                                        Paket <?= esc($ada['status']) ?> dengan Tahun Ajaran yang sudah tersimpan
                                        akan dipertahankan tanpa perubahan.
                                    </p>
                                <?php else: ?>
                                    <select id="draf-tahun-<?= $n ?>" name="tahun_id[<?= $n ?>]" required>
                                        <option value="">Pilih Tahun Ajaran</option>
                                        <?php foreach ($opsiPaket['tahun'] as $tahun): ?>
                                            <option value="<?= (int) $tahun['id'] ?>"
                                                <?= count($opsiPaket['tahun']) === 1 ? 'selected' : '' ?>>
                                                <?= esc($tahun['nama']) ?>
                                            </option>
                                        <?php endforeach ?>
                                    </select>
                                <?php endif ?>
                            </div>
                        <?php endfor ?>
                    </div>
                    <p class="a-help">Setiap semester menggunakan Tahun Ajaran pilihannya sendiri.
                        Paket yang baru dibuat selalu berstatus draf dan hanya milik Gen–Mapel ini.</p>
                    <button class="a-btn a-btn--utama" type="submit">
                        Buat Draf untuk 6 Semester
                    </button>
                </form>
            <?php endif ?>
        </section>
        <section class="a-card" aria-labelledby="judul-atp-excel">
            <h2 id="judul-atp-excel">Kelola ATP Excel · enam semester</h2>
            <p class="a-proto-muted">Unduh template, unggah, periksa kesesuaian tiap semester,
                lalu konfirmasi penyusunan awal. BAB/TP yang sudah dikerjakan
                tidak dapat diubah lewat Excel.</p>
            <a class="a-btn a-btn--utama"
                href="<?= esc(site_url('guru/peta-atp/impor')
                    . '?pilihan=' . (int) $nomorPilihan, 'attr') ?>">
                Buka Kelola ATP Excel
            </a>
        </section>
        <?php foreach ([['judul' => 'Semester 1–3', 'nomor' => [1, 2, 3]], ['judul' => 'Semester 4–6', 'nomor' => [4, 5, 6]]] as $kelompok): ?>
            <section class="a-atp-kelompok" aria-label="<?= esc($kelompok['judul'], 'attr') ?>">
                <div class="a-atp-kelompok__heading">
                    <h2><?= esc($kelompok['judul']) ?></h2>
                    <p class="a-proto-muted">Klik kartu semester untuk melihat dan mengelola BAB serta TP.</p>
                </div>
                <div class="a-atp-kartu-grid">
                    <?php foreach ($kelompok['nomor'] as $n): ?>
                        <?php
                            $r = $ringkasanSemester[$n];
                            $sebelumGen = $n < (int) $dipilih['semester_mulai'];
                            $status = match ($r['status']) {
                                'aktif' => 'Paket aktif', 'draf' => 'Paket draf',
                                'arsip' => 'Paket arsip', default => 'Belum ada paket',
                            };
                        ?>
                        <a class="a-atp-kartu a-atp-warna-<?= (int) $n ?>"
                            href="<?= esc(site_url('guru/peta-atp/semester/' . $n) . '?pilihan=' . (int) $nomorPilihan, 'attr') ?>"
                            aria-label="Semester <?= $n ?>, Kelas <?= esc(match ($n) {1,2 => 'VII',3,4 => 'VIII',default => 'IX'}) ?>, <?= esc($status) ?>, <?= (int) $r['bab'] ?> BAB, <?= (int) $r['tp'] ?> TP. Buka semester">
                            <span class="a-atp-kartu__atas">
                                <span class="a-atp-kartu__indeks">SEMESTER <?= $n ?></span>
                                <span aria-hidden="true">↗</span>
                            </span>
                            <strong class="a-atp-kartu__judul">Kelas <?= esc(match ($n) {1,2 => 'VII',3,4 => 'VIII',default => 'IX'}) ?></strong>
                            <span class="a-atp-kartu__status"><?= esc($status) ?><?= $sebelumGen ? ' · Hanya persiapan Guru' : '' ?></span>
                            <span class="a-atp-kartu__angka">
                                <span><strong><?= (int) $r['bab'] ?></strong><small>BAB</small></span>
                                <span><strong><?= (int) $r['tp'] ?></strong><small>TP</small></span>
                            </span>
                            <span class="a-atp-kartu__rincian">
                                <?= (int) $r['esensial'] ?> Esensial · <?= (int) $r['non_esensial'] ?> NonEsensial
                            </span>
                        </a>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
