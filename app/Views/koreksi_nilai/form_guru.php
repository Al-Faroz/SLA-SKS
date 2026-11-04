<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $alamat='guru/koreksi-nilai/'.$tugas['jenis'].'/'.(int)$tugas['id']; ?>
<div class="a-page a-informasi">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'),'attr') ?>">Beranda Guru</a><span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/koreksi-nilai'),'attr') ?>">Koreksi Nilai</a><span aria-hidden="true">/</span>
        <span aria-current="page">Ajukan Koreksi</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru G1 · <?= esc((string)$tugas['nama_gen']) ?> · <?= esc((string)$tugas['nama_mapel']) ?></p>
            <h1>Ajukan Koreksi Nilai <?= esc(strtoupper((string)$tugas['jenis'])) ?></h1>
            <p class="a-proto-muted"><?= esc((string)$tugas['kode_tp']) ?> · <?= esc((string)$tugas['nama_siswa']) ?>.
                Nilai sumber dan nilai TP tidak berubah sampai Admin menyetujui.</p>
        </div>
    </header>
    <?php if ($pesan=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <section class="a-card" aria-labelledby="konteks-koreksi">
        <h2 id="konteks-koreksi">Nilai dan snapshot yang dinilai</h2>
        <p class="a-help">Materi: <?= esc((string)$tugas['materi_judul_snapshot']) ?> · Snapshot TP #<?= (int)$tugas['versi_konten_tp_id'] ?></p>
        <p>Nilai <?= esc(strtoupper((string)$tugas['jenis'])) ?> saat ini:
            <strong><?= (int)$tugas['nilai'] ?>/100</strong>.</p>
        <p>Nilai TP saat ini: <strong><?= $konteks['nilai_tp']===null?'Sementara':esc(number_format((float)$konteks['nilai_tp'],2,',','.')) ?></strong>.</p>
        <p class="a-help">Penilai awal ID #<?= (int)$tugas['dinilai_oleh'] ?>; jejak penilai awal tetap dipertahankan.</p>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/penilaian/'.$tugas['jenis'].'/'.(int)$tugas['id']),'attr') ?>">Lihat jawaban dan petunjuk asli</a>
    </section>
    <section class="a-card" aria-labelledby="pratinjau-koreksi">
        <h2 id="pratinjau-koreksi">1. Pratinjau nilai usulan</h2>
        <form method="get" action="<?= esc(site_url($alamat),'attr') ?>" class="a-form">
            <div class="a-field">
                <label class="a-label" for="usulan-pratinjau">Nilai usulan (0–100)</label>
                <input class="a-input" type="number" id="usulan-pratinjau" name="nilai_usulan"
                    min="0" max="100" step="1" required value="<?= (int)$usulan ?>">
            </div>
            <button class="a-btn a-btn--sekunder" type="submit">Hitung Pratinjau</button>
        </form>
        <div class="a-alert" role="status">
            <strong>Pratinjau dampak:</strong>
            <?= $dampak['nilai_tp_lama']===null?'TP sementara':esc(number_format((float)$dampak['nilai_tp_lama'],2,',','.')) ?>
            →
            <?= $dampak['nilai_tp_usulan']===null?'TP sementara':esc(number_format((float)$dampak['nilai_tp_usulan'],2,',','.')) ?>.
            <?php if ($dampak['perlu_tinjau_sertifikat']): ?>
                Jika sertifikat sudah terbit, Admin harus memeriksa kebutuhan revisi versi dokumen.
            <?php endif ?>
        </div>
    </section>
    <section class="a-card" aria-labelledby="simpan-koreksi">
        <h2 id="simpan-koreksi">2. Kirim permohonan ke Admin</h2>
        <?php if (!$dampak['perubahan_sumber']): ?>
            <div class="a-empty" role="status">Nilai usulan sama dengan nilai sekarang. Ubah nilai pada Pratinjau dahulu.</div>
        <?php else: ?>
            <form method="post" action="<?= esc(site_url($alamat),'attr') ?>" class="a-form">
                <?= csrf_field() ?>
                <input type="hidden" name="nilai_usulan" value="<?= (int)$usulan ?>">
                <div class="a-field">
                    <label class="a-label" for="alasan-koreksi">Alasan akademik koreksi (10–500 karakter)</label>
                    <textarea id="alasan-koreksi" name="alasan" rows="5" minlength="10"
                        maxlength="500" required><?= esc((string)old('alasan')) ?></textarea>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Ajukan Koreksi Nilai</button>
                <p class="a-help">Guru tidak dapat menyetujui permohonannya sendiri. Admin memeriksa ulang nilai sumber, penugasan Gen–Mapel, dan snapshot saat memutuskan.</p>
            </form>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
