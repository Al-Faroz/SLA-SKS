<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $url='guru/koreksi-nilai/'.$tugas['jenis'].'/'.(int)$tugas['id']; ?>
<div class="a-page a-informasi">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'),'attr') ?>">Beranda Guru</a><span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/penilaian'),'attr') ?>">Penilaian</a><span aria-hidden="true">/</span>
        <span aria-current="page">Koreksi Nilai</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div><p class="a-proto-eyebrow">Guru · <?= esc((string)$tugas['nama_gen']) ?> · <?= esc((string)$tugas['nama_mapel']) ?></p>
            <h1>Koreksi Nilai <?= esc(strtoupper((string)$tugas['jenis'])) ?></h1>
            <p class="a-proto-muted"><?= esc((string)$tugas['nama_siswa']) ?> ·
                <?= esc((string)$tugas['kode_tp']) ?>.
                Perubahan dilakukan langsung oleh Guru pengampu, tanpa persetujuan Admin.</p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="form-koreksi">
        <h2 id="form-koreksi">Perbaiki skor</h2>
        <p>Nilai saat ini: <strong><?= (int)$tugas['nilai'] ?>/100</strong>.</p>
        <p class="a-help">Penilai historis #<?= (int)$tugas['dinilai_oleh'] ?>
            · Snapshot TP #<?= (int)$tugas['versi_konten_tp_id'] ?>.
            Jejak penilai dan jawaban siswa tidak dihapus.</p>
        <?php if ($dampak['nilai_tp_lama']!==null): ?>
            <p class="a-help">Nilai TP saat ini:
                <?= esc(number_format((float)$dampak['nilai_tp_lama'],2,',','.')) ?>.
                Sistem menghitung ulang otomatis setelah diproses.</p>
        <?php else: ?>
            <p class="a-help">Nilai TP belum Final. Skor koreksi tetap disimpan;
                penghitungan TP akan mengikuti kesiapan komponen penilaian.</p>
        <?php endif ?>
        <form class="a-form" method="post" action="<?= esc(site_url($url),'attr') ?>">
            <?= csrf_field() ?>
            <div class="a-field">
                <label class="a-label" for="nilai-koreksi">Nilai koreksi (0–100)</label>
                <input class="a-input" type="number" id="nilai-koreksi" name="nilai"
                    min="0" max="100" step="1" inputmode="numeric" required
                    value="<?= (int)$tugas['nilai'] ?>">
            </div>
            <p class="a-help">Simpan sekali dengan tombol Proses. Versi perubahan, nilai
                sebelum/sesudah, pelaku dan waktu dicatat otomatis. Jika ada sertifikat,
                pembaruan dilakukan saat siswa mengaksesnya berikutnya.</p>
            <div class="a-penilaian-aksi">
                <button class="a-btn a-btn--utama" type="submit">Proses</button>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/penilaian/'
                    .$tugas['jenis'].'/'.(int)$tugas['id']),'attr') ?>">Batal</a>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
