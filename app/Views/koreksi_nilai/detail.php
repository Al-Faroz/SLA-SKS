<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $url=$role==='admin'?'admin/koreksi-nilai':'guru/koreksi-nilai'; ?>
<div class="a-page a-informasi">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url($role),'attr') ?>">Beranda <?= esc(ucfirst($role)) ?></a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url($url),'attr') ?>">Koreksi Nilai</a>
        <span aria-hidden="true">/</span><span aria-current="page">Permohonan #<?= (int)$baris['id'] ?></span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow"><?= $role==='admin'?'Admin · Keputusan Akademik':'Guru · Riwayat Pengajuan' ?></p>
            <h1>Permohonan Koreksi #<?= (int)$baris['id'] ?></h1>
            <p class="a-proto-muted"><?= esc((string)$baris['kode_tp']) ?> · <?= esc((string)$baris['nama_siswa']) ?>.
                <?= esc(strtoupper((string)$baris['jenis'])) ?> · Semester <?= (int)$baris['semester'] ?></p>
        </div>
    </header>
    <?php if ($pesan=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
    <?php if ($pesan=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <section class="a-card" aria-labelledby="rincian-koreksi">
        <h2 id="rincian-koreksi">Rincian Pengajuan</h2>
        <p><strong>Status:</strong> <span class="a-badge"><?= esc(ucfirst((string)$baris['status'])) ?></span></p>
        <p>Nilai sumber: <strong><?= (int)$baris['nilai_awal'] ?> → <?= (int)$baris['nilai_usulan'] ?></strong></p>
        <p>Nilai TP: <strong><?= $baris['nilai_tp_awal']===null?'Sementara':esc((string)$baris['nilai_tp_awal']) ?>
            → <?= $baris['nilai_tp_usulan']===null?'Sementara':esc((string)$baris['nilai_tp_usulan']) ?></strong></p>
        <p class="a-help">Versi koreksi: <?= (int)$baris['versi_koreksi'] ?> · Diajukan: <?= esc((string)$baris['dibuat_pada']) ?> UTC · Guru ID #<?= (int)$baris['guru_id'] ?></p>
        <h3>Alasan pengajuan</h3>
        <p style="white-space:pre-wrap"><?= esc((string)$baris['alasan']) ?></p>
        <?php if ($baris['status']!=='diajukan'): ?>
            <h3>Keputusan Admin</h3>
            <p><?= esc((string)$baris['catatan_keputusan']) ?></p>
            <p class="a-help">Admin ID #<?= (int)$baris['admin_id'] ?> · <?= esc((string)$baris['diputus_pada']) ?> UTC</p>
        <?php endif ?>
        <?php if ((int)$baris['perlu_tinjau_sertifikat']===1): ?>
            <div class="a-alert a-alert--bahaya" role="alert">
                Nilai TP tercetak berubah dan sertifikat aktif sudah ada. Admin wajib memeriksa
                revisi sertifikat melalui mekanisme penerbitan versi baru.
                <?php if ($role==='admin'): ?>
                    <a href="<?= esc(site_url('admin/sertifikat'),'attr') ?>">Buka Kelola Sertifikat</a>
                <?php endif ?>
            </div>
        <?php endif ?>
    </section>
    <?php if ($role==='admin' && $baris['status']==='diajukan'): ?>
        <section class="a-card" aria-labelledby="keputusan-koreksi">
            <h2 id="keputusan-koreksi">Keputusan Admin</h2>
            <p class="a-alert a-alert--info">Keputusan disimpan bersama audit. Saat disetujui, sumber skor dan nilai TP diperbarui atomik; bukan dengan mengubah kunci soal/TP. Akun yang sama dengan Guru pengusul tidak boleh menyetujui.</p>
            <form class="a-form" method="post" action="<?= esc(site_url($url.'/'.(int)$baris['id'].'/keputusan'),'attr') ?>">
                <?= csrf_field() ?>
                <div class="a-field">
                    <label class="a-label" for="catatan-keputusan">Catatan keputusan (10–500 karakter)</label>
                    <textarea id="catatan-keputusan" name="catatan_keputusan" rows="5"
                        minlength="10" maxlength="500" required><?= esc((string)old('catatan_keputusan')) ?></textarea>
                </div>
                <div class="a-aksi a-aksi--kiri">
                    <button class="a-btn a-btn--utama" name="keputusan" type="submit" value="disetujui"
                        onclick="return confirm('Terapkan koreksi nilai secara resmi?');">Setujui &amp; Terapkan</button>
                    <button class="a-btn a-btn--sekunder" name="keputusan" type="submit" value="ditolak"
                        onclick="return confirm('Tolak permohonan? Nilai tidak berubah.');">Tolak Permohonan</button>
                </div>
            </form>
        </section>
    <?php endif ?>
    <section class="a-card" aria-labelledby="riwayat-koreksi">
        <h2 id="riwayat-koreksi">Jejak Perubahan</h2>
        <div class="a-tabel-gulir" tabindex="0" aria-label="Riwayat koreksi nilai">
            <table class="a-tabel">
                <thead><tr><th>Waktu (UTC)</th><th>Tindakan</th><th>Peran</th><th>Nilai sebelum → usulan</th><th>Versi</th></tr></thead>
                <tbody>
                <?php foreach ($riwayat as $h): ?>
                    <tr>
                        <td><?= esc((string)$h['waktu']) ?></td>
                        <td><?= esc(ucfirst((string)$h['aksi'])) ?></td>
                        <td><?= esc(ucfirst((string)$h['peran_aktif'])) ?></td>
                        <td><?= (int)$h['nilai_awal'] ?> → <?= (int)$h['nilai_usulan'] ?></td>
                        <td><?= (int)$h['versi_koreksi'] ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
