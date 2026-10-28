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
                            $belumMulai = $n < (int) $dipilih['semester_mulai'];
                            $status = $belumMulai ? 'Dilewati oleh Gen' : match ($r['status']) {
                                'aktif' => 'Paket aktif', 'draf' => 'Paket draf',
                                'arsip' => 'Paket arsip', default => 'Belum ada paket',
                            };
                        ?>
                        <a class="a-atp-kartu a-atp-warna-<?= (int) $n ?><?= $belumMulai ? ' is-dilewati' : '' ?>"
                            href="<?= esc(site_url('guru/peta-atp/semester/' . $n) . '?pilihan=' . (int) $nomorPilihan, 'attr') ?>"
                            aria-label="Semester <?= $n ?>, Kelas <?= esc(match ($n) {1,2 => 'VII',3,4 => 'VIII',default => 'IX'}) ?>, <?= esc($status) ?>, <?= (int) $r['bab'] ?> BAB, <?= (int) $r['tp'] ?> TP. Buka semester">
                            <span class="a-atp-kartu__atas">
                                <span class="a-atp-kartu__indeks">SEMESTER <?= $n ?></span>
                                <span aria-hidden="true">↗</span>
                            </span>
                            <strong class="a-atp-kartu__judul">Kelas <?= esc(match ($n) {1,2 => 'VII',3,4 => 'VIII',default => 'IX'}) ?></strong>
                            <span class="a-atp-kartu__status"><?= esc($status) ?></span>
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
