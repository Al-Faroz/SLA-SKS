<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
    $base = site_url('admin/' . $jenis);
    $applied = $batch['status'] === 'diterapkan';
    $bisa = ! $kedaluwarsa && ! $applied
        && (int) $batch['jumlah_tolak'] === 0
        && ((int) $batch['jumlah_baru'] + (int) $batch['jumlah_ubah'] > 0);
?>
<div class="a-admin-akun a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc($base, 'attr') ?>"><?= esc(ucfirst($jenis)) ?></a>
        <span aria-hidden="true">/</span><a href="<?= esc($base . '/impor', 'attr') ?>">Impor XLSX</a>
        <span aria-hidden="true">/</span><span aria-current="page">Pratinjau</span>
    </nav>
    <section class="a-card a-impor-akun" aria-labelledby="judul-pratinjau">
        <p class="a-proto-eyebrow">Administrasi · <?= esc(ucfirst($jenis)) ?></p>
        <h1 id="judul-pratinjau">Pratinjau impor akun</h1>
        <p class="a-help">Template <?= esc($batch['versi_template']) ?>. Pratinjau tidak membuat, mengubah atau menghapus akun. Data divalidasi kembali saat konfirmasi.</p>

        <?php if ($galat = session()->getFlashdata('galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
        <?php endif ?>
        <?php if ($sukses = session()->getFlashdata('sukses')): ?>
            <div class="a-alert" role="status"><?= esc($sukses) ?></div>
        <?php endif ?>

        <div class="a-impor-akun__ringkasan" aria-label="Ringkasan baris">
            <div><strong><?= (int) $batch['jumlah_baru'] ?></strong><span>Akun baru</span></div>
            <div><strong><?= (int) $batch['jumlah_ubah'] ?></strong><span>Perubahan</span></div>
            <div><strong><?= (int) $batch['jumlah_tetap'] ?></strong><span>Tidak berubah</span></div>
            <div><strong><?= (int) $batch['jumlah_tolak'] ?></strong><span>Ditolak</span></div>
        </div>
        <?php if ($applied): ?>
            <div class="a-alert" role="status">Impor telah diterapkan. Mengirim konfirmasi ulang tidak akan membuat akun duplikat.</div>
        <?php elseif ($kedaluwarsa): ?>
            <div class="a-alert a-alert--bahaya" role="alert">Pratinjau kedaluwarsa. Silakan unggah file kembali.</div>
        <?php elseif ((int) $batch['jumlah_tolak'] > 0): ?>
            <div class="a-alert a-alert--bahaya" role="alert">Ada <?= (int) $batch['jumlah_tolak'] ?> baris ditolak. Tidak satu pun perubahan akan disimpan. Perbaiki file dan unggah ulang.</div>
        <?php elseif (! $bisa): ?>
            <div class="a-alert a-alert--info" role="status">Tidak ada perubahan yang perlu disimpan. Semua akun sudah sesuai.</div>
        <?php else: ?>
            <div class="a-alert a-alert--info">Periksa nama, username/NISN dan perubahan akun di bawah. Konfirmasi akan menerapkan seluruh perubahan dalam satu transaksi.</div>
        <?php endif ?>

        <div class="a-tabel-gulir">
            <table class="a-tbl a-impor-akun__tabel">
                <thead><tr>
                    <th scope="col">Baris</th><th scope="col">Aksi</th><th scope="col"><?= $jenis === 'siswa' ? 'NISN' : 'Username' ?></th>
                    <th scope="col">Nama</th><th scope="col">Peran</th><th scope="col">Keterangan</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($baris as $item): ?>
                        <tr>
                            <td><?= (int) $item['nomor_baris'] ?></td>
                            <td><span class="a-badge <?= $item['tindakan'] === 'ditolak' ? 'a-badge--bahaya' : 'a-badge--siap' ?>"><?= esc(ucfirst($item['tindakan'])) ?></span></td>
                            <td><?= esc($item['username']) ?></td>
                            <td>
                                <?php if ($item['nama_sebelum'] !== null && $item['nama_sebelum'] !== $item['nama']): ?>
                                    <small class="a-impor-akun__sebelum"><?= esc($item['nama_sebelum']) ?> →</small>
                                <?php endif ?>
                                <?= esc($item['nama']) ?>
                            </td>
                            <td>
                                <?php if ($item['peran_sebelum'] !== null && $item['peran_sebelum'] !== $item['peran']): ?>
                                    <small class="a-impor-akun__sebelum"><?= esc(str_replace(',', ' + ', $item['peran_sebelum'])) ?> →</small>
                                <?php endif ?>
                                <?= esc(str_replace(',', ' + ', $item['peran'])) ?>
                            </td>
                            <td><?= $item['galat'] === null ? '—' : esc($item['galat']) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div class="a-impor-akun__aksi">
            <?php if ($bisa): ?>
                <form method="post" action="<?= esc($base . '/impor/' . $batch['id'] . '/konfirmasi', 'attr') ?>">
                    <?= csrf_field() ?>
                    <button class="a-btn a-btn--utama" type="submit">Konfirmasi dan simpan <?= (int) $batch['jumlah_baru'] + (int) $batch['jumlah_ubah'] ?> akun</button>
                </form>
            <?php endif ?>
            <?php if ((int) $batch['jumlah_tolak'] > 0): ?>
                <a class="a-btn a-btn--sekunder" href="<?= esc($base . '/impor/' . $batch['id'] . '/galat', 'attr') ?>">Unduh laporan galat XLSX</a>
            <?php endif ?>
            <a class="a-btn a-btn--sekunder" href="<?= esc($base . '/impor', 'attr') ?>">Kembali / Unggah ulang</a>
            <a class="a-btn a-btn--sekunder" href="<?= esc($base, 'attr') ?>">Daftar akun</a>
        </div>
        <p class="a-help">Sandi awal tidak ditampilkan dalam pratinjau. Untuk akun yang sudah ada, sandi dan status aktif/nonaktif tidak berubah.</p>
    </section>
</div>
<?= $this->endSection() ?>
