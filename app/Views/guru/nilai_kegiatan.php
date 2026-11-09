<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-penilaian-page a-penilaian-page--detail">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/penilaian'), 'attr') ?>">Antrean Penilaian</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Nilai <?= esc(strtoupper($tugas['jenis'])) ?></span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · <?= esc($tugas['nama_gen']) ?>
                · <?= esc($tugas['nama_mapel']) ?></p>
            <h1>Penilaian <?= esc(strtoupper($tugas['jenis'])) ?></h1>
            <p class="a-proto-muted">Siswa: <?= esc($tugas['nama_siswa']) ?> ·
                <?= esc($tugas['kode_tp'] . ' — ' . $tugas['tp_judul']) ?> ·
                Materi <?= (int) $tugas['urutan_materi'] ?></p>
        </div>
    </header>
    <?php if (session()->getFlashdata('nilai_galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc(session()->getFlashdata('nilai_galat')) ?></div>
    <?php endif ?>
    <?php if (session()->getFlashdata('nilai_sukses')): ?>
        <div class="a-alert" role="status"><?= esc(session()->getFlashdata('nilai_sukses')) ?></div>
    <?php endif ?>
    <section class="a-card a-penilaian-petunjuk" aria-labelledby="petunjuk-penilaian">
        <div class="a-penilaian-bagian__heading">
            <span class="a-badge">Acuan tugas</span>
            <h2 id="petunjuk-penilaian">Petunjuk dari Materi versi siswa</h2>
        </div>
        <p class="a-help">Materi <?= esc((string)$tugas['materi_judul_snapshot']) ?> ·
            Snapshot konten #<?= (int)$tugas['versi_konten_tp_id'] ?>.
            Petunjuk asli saat siswa mengerjakan, bukan hasil perubahan terbaru Guru.</p>
        <div class="a-penilaian-isi"><?= nl2br(esc((string) $tugas['petunjuk'])) ?></div>
    </section>
    <section class="a-card a-card--jawaban-siswa a-jarak-atas" aria-labelledby="jawaban-penilaian">
        <div class="a-penilaian-bagian__heading">
            <span class="a-badge">Jawaban final</span>
            <h2 id="jawaban-penilaian">Jawaban siswa</h2>
        </div>
        <p class="a-help">Dikirim <?= esc((string) $tugas['dikirim_pada']) ?> UTC.
            Jawaban final siswa tidak dapat diubah.</p>
        <div class="a-penilaian-isi"><?= nl2br(esc((string) $tugas['isi'])) ?></div>
        <?php if ((float) $tugas['kemiripan'] >= 60): ?>
            <div class="a-alert" role="status">
                <strong>Perlu diperiksa:</strong> kemiripan <?= esc((string)$tugas['kemiripan']) ?>%
                dengan jawaban lain pada Materi dan snapshot yang sama.
                Ini hanya indikator dan tidak mengubah nilai secara otomatis.
            </div>
        <?php endif ?>
    </section>
    <section class="a-card a-jarak-atas a-penilaian-skor-card" aria-labelledby="nilai-kegiatan">
        <h2 id="nilai-kegiatan">Nilai Guru</h2>
        <?php if ($tugas['nilai'] !== null): ?>
            <div class="a-alert" role="status">Nilai sudah tersimpan: <strong><?= (int) $tugas['nilai'] ?>/100</strong>.
                Penilai asli ID #<?= (int) $tugas['dinilai_oleh'] ?>, pada
                <?= esc((string) $tugas['dinilai_pada']) ?> UTC.
                Guru G1 dapat memperbaiki skor langsung dengan jejak audit.</div>
            <p class="a-help">Umpan balik:</p>
            <div class="a-penilaian-isi"><?= nl2br(esc((string) ($tugas['umpan_balik'] ?? ''))) ?></div>
            <div class="a-penilaian-aksi">
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/koreksi-nilai/'
                    . $tugas['jenis'] . '/' . (int)$tugas['id']),'attr') ?>">Koreksi Nilai</a>
            </div>
            <p class="a-help">Perubahan tidak dilakukan dari penilaian awal.
                Guru G1 langsung memproses koreksi; sistem menyimpan nilai lama/baru dan menghitung ulang TP.</p>
        <?php else: ?>
            <p class="a-help">Skor wajib berupa bilangan bulat 0–100.
                Umpan balik boleh kosong, maksimal 1.000 karakter.
                Setelah disimpan, skor tidak dapat diubah dari formulir ini.</p>
            <form method="post" class="a-form a-form-penilaian" data-nilai-guru
                action="<?= esc(site_url('guru/penilaian/' . $tugas['jenis']
                    . '/' . (int) $tugas['id']), 'attr') ?>">
                <?= csrf_field() ?>
                <div class="a-penilaian-form-grid">
                    <div class="a-field">
                        <label class="a-label" for="nilai">Nilai (0–100)</label>
                        <input class="a-input" type="number" name="nilai" id="nilai" min="0" max="100"
                            step="1" inputmode="numeric" required
                            value="<?= esc((string) old('nilai'), 'attr') ?>">
                        <p class="a-help">Masukkan bilangan bulat sesuai hasil penilaian.</p>
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="umpan-balik">Umpan balik Guru (opsional)</label>
                        <textarea id="umpan-balik" name="umpan_balik" maxlength="1000"
                            rows="5"><?= esc((string) old('umpan_balik')) ?></textarea>
                    </div>
                </div>
                <div class="a-penilaian-aksi">
                    <button class="a-btn a-btn--utama" type="submit" data-nilai-simpan>
                        Simpan nilai</button>
                    <button class="a-btn a-btn--sekunder" type="submit" name="lanjut" value="berikutnya"
                        data-nilai-lanjut>Simpan &amp; berikutnya</button>
                </div>
                <p class="a-help" data-nilai-status role="status">Belum disimpan.</p>
            </form>
        <?php endif ?>
        <p class="a-help">Penilaian awal ini belum menerbitkan nilai TP final atau sertifikat.
            Semua komponen wajib dan hasil Sumatif tetap harus sah sebelum hasil final.</p>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/penilaian'), 'attr') ?>">
            Kembali ke antrean</a>
    </section>
</div>
<?php if ($tugas['nilai'] === null): ?>
<script src="<?= esc(base_url('public/assets/js/penilaian-guru.js?v=20261025-g11'), 'attr') ?>" defer></script>
<?php endif ?>
<?= $this->endSection() ?>
