<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $dasar=$role==='admin'?'admin/koreksi-nilai':'guru/koreksi-nilai'; ?>
<div class="a-page a-informasi">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url($role),'attr') ?>">Beranda <?= esc(ucfirst($role)) ?></a>
        <span aria-hidden="true">/</span><span aria-current="page">Koreksi Nilai</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow"><?= $role==='admin'?'Admin · Persetujuan':'Guru · Pengajuan' ?></p>
            <h1><?= $role==='admin'?'Persetujuan Koreksi Nilai':'Pengajuan Koreksi Nilai' ?></h1>
            <p class="a-proto-muted">Koreksi nilai siswa berbeda dengan perubahan isi TP.
                <?= $role==='admin'
                    ?'Nilai hanya berubah setelah persetujuan sah dari akun Admin yang berbeda dengan pengusul.'
                    :'Ajukan koreksi melalui detail tugas Resume/HOTS yang telah dinilai. Nilai tidak langsung berubah.' ?></p>
        </div>
    </header>
    <?php if ($pesan=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
    <?php if ($pesan=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <section class="a-card" aria-labelledby="daftar-koreksi">
        <h2 id="daftar-koreksi">Riwayat Permohonan</h2>
        <p class="a-help">Total <?= (int)$data['total'] ?> permohonan. Nilai sumber dan keputusan memiliki jejak perubahan terpisah dari konten TP.</p>
        <?php if (!$data['baris']): ?>
            <div class="a-empty" role="status">
                <strong>Belum ada pengajuan koreksi nilai.</strong>
                <p><?= $role==='guru'?'Buka tugas yang sudah dinilai pada halaman Penilaian Guru untuk mengajukan koreksi.':'Permohonan Guru yang sah akan muncul di sini.' ?></p>
            </div>
        <?php else: ?>
        <div class="a-tabel-gulir" tabindex="0" aria-label="Daftar koreksi nilai">
            <table class="a-tabel">
                <thead><tr><th>Waktu (UTC)</th><th>TP dan siswa</th><th>Komponen</th><th>Nilai awal → usulan</th><th>Status</th><th>Tindakan</th></tr></thead>
                <tbody>
                <?php foreach ($data['baris'] as $r): ?>
                    <tr>
                        <td><?= esc((string)$r['dibuat_pada']) ?></td>
                        <td><?= esc((string)$r['kode_tp']) ?> · <?= esc((string)$r['nama_siswa']) ?>
                            <div class="a-help">Semester <?= (int)$r['semester'] ?></div>
                        </td>
                        <td><?= esc(strtoupper((string)$r['jenis'])) ?></td>
                        <td><?= (int)$r['nilai_awal'] ?> → <?= (int)$r['nilai_usulan'] ?>
                            <?php if ($r['nilai_tp_awal']!==null): ?>
                                <div class="a-help">TP <?= esc((string)$r['nilai_tp_awal']) ?> → <?= esc((string)$r['nilai_tp_usulan']) ?></div>
                            <?php endif ?>
                        </td>
                        <td><span class="a-badge"><?= esc(ucfirst((string)$r['status'])) ?></span></td>
                        <td>
                            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($dasar.'/'.(int)$r['id']),'attr') ?>">Lihat</a>
                            <?php if ((int)$r['perlu_tinjau_sertifikat']===1): ?>
                                <div class="a-help">Periksa revisi sertifikat</div>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </section>
    <?php if ($data['jumlah_halaman']>1): ?>
        <nav class="a-aksi" aria-label="Navigasi halaman koreksi">
            <span>Halaman <?= (int)$data['halaman'] ?> dari <?= (int)$data['jumlah_halaman'] ?></span>
            <?php if ($data['halaman']>1): ?>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($dasar).'?halaman='.($data['halaman']-1),'attr') ?>">Sebelumnya</a>
            <?php endif ?>
            <?php if ($data['halaman']<$data['jumlah_halaman']): ?>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($dasar).'?halaman='.($data['halaman']+1),'attr') ?>">Selanjutnya</a>
            <?php endif ?>
        </nav>
    <?php endif ?>
    <?php if ($role==='guru'): ?><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/penilaian'),'attr') ?>">Buka Antrean Penilaian</a><?php endif ?>
</div>
<?= $this->endSection() ?>
