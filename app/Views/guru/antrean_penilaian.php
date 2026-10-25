<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Antrean Penilaian</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Penilaian per Gen–Mapel</p>
            <h1>Antrean Penilaian Resume &amp; HOTS</h1>
            <p class="a-proto-muted">Daftar final yang belum mendapat skor Guru, urutan pengiriman tertua.
                Nilai yang sudah tersimpan tidak dapat diubah dari halaman ini.</p>
        </div>
    </header>
    <?php if (session()->getFlashdata('nilai_galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc(session()->getFlashdata('nilai_galat')) ?></div>
    <?php endif ?>
    <?php if (session()->getFlashdata('nilai_sukses')): ?>
        <div class="a-alert" role="status"><?= esc(session()->getFlashdata('nilai_sukses')) ?></div>
    <?php endif ?>
    <section class="a-card" aria-labelledby="filter-penilaian">
        <h2 id="filter-penilaian">Filter penugasan Guru</h2>
        <?php if ($penugasan === []): ?>
            <div class="a-alert" role="status">Belum ada penugasan Gen–Mapel aktif.
                Hubungi Admin untuk menetapkan Guru pengampu.</div>
        <?php else: ?>
            <form method="get" action="<?= esc(site_url('guru/penilaian'), 'attr') ?>"
                class="a-penilaian-filter">
                <div class="a-field">
                    <label class="a-label" for="pilihan">Gen – Mata Pelajaran</label>
                    <select class="a-input" id="pilihan" name="pilihan">
                        <?php foreach ($penugasan as $i => $p): ?>
                            <option value="<?= (int) $i ?>" <?= (int) $pilihan_aktif === $i ? 'selected' : '' ?>>
                                <?= esc($p['nama_gen'] . ' (' . $p['kode_gen'] . ') — ' . $p['nama_mapel']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button class="a-btn a-btn--sekunder" type="submit">Terapkan filter</button>
            </form>
        <?php endif ?>
    </section>
    <section class="a-card a-jarak-atas" aria-labelledby="antrean-pengumpulan">
        <h2 id="antrean-pengumpulan">Jawaban final belum dinilai</h2>
        <p class="a-help"><?= (int) $antrean['total'] ?> tugas menunggu penilaian.
            Halaman <?= (int) $antrean['halaman'] ?> dari <?= (int) $antrean['jumlah_halaman'] ?>.</p>
        <?php if ($antrean['baris'] === []): ?>
            <div class="a-alert" role="status">Belum ada Resume atau HOTS yang perlu dinilai
                untuk penugasan ini.</div>
        <?php else: ?>
            <div class="a-tabel-gulir">
                <table class="a-tbl a-penilaian-tabel">
                    <thead><tr>
                        <th scope="col">Dikirim (UTC)</th>
                        <th scope="col">Siswa</th>
                        <th scope="col">TP / Materi</th>
                        <th scope="col">Jenis</th>
                        <th scope="col">Aksi</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($antrean['baris'] as $r): ?>
                            <tr>
                                <td><?= esc((string) $r['dikirim_pada']) ?></td>
                                <td><?= esc((string) $r['nama_siswa']) ?></td>
                                <td>
                                    <strong><?= esc($r['kode_tp'] . ' — ' . $r['tp_judul']) ?></strong>
                                    <span class="a-penilaian-terpisah">Materi <?= (int) $r['urutan_materi'] ?>:
                                        <?= esc($r['materi_judul']) ?></span>
                                </td>
                                <td><span class="a-badge"><?= esc(strtoupper($r['jenis'])) ?></span>
                                    <?php if ((float) $r['kemiripan'] >= 60): ?>
                                        <span class="a-badge" title="Indikasi kesamaan teks, perlu pemeriksaan Guru">
                                            Perlu dicek <?= esc((string)$r['kemiripan']) ?>%</span>
                                    <?php endif ?>
                                </td>
                                <td><a class="a-btn a-btn--sekunder"
                                    href="<?= esc(site_url('guru/penilaian/' . $r['jenis']
                                        . '/' . (int) $r['id']), 'attr') ?>">Nilai jawaban</a></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        <?php if ($antrean['jumlah_halaman'] > 1): ?>
            <nav class="a-penilaian-pager" aria-label="Halaman antrean penilaian">
                <?php $query = '?pilihan=' . (int) $pilihan_aktif . '&halaman='; ?>
                <?php if ($antrean['halaman'] > 1): ?>
                    <a class="a-btn a-btn--sekunder"
                        href="<?= esc(site_url('guru/penilaian') . $query . ($antrean['halaman'] - 1), 'attr') ?>">Sebelumnya</a>
                <?php else: ?>
                    <span aria-disabled="true">Halaman pertama</span>
                <?php endif ?>
                <strong>Halaman <?= (int) $antrean['halaman'] ?></strong>
                <?php if ($antrean['halaman'] < $antrean['jumlah_halaman']): ?>
                    <a class="a-btn a-btn--sekunder"
                        href="<?= esc(site_url('guru/penilaian') . $query . ($antrean['halaman'] + 1), 'attr') ?>">Berikutnya</a>
                <?php else: ?>
                    <span aria-disabled="true">Halaman terakhir</span>
                <?php endif ?>
            </nav>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
