<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-atp-detail">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc($kembali, 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span><span aria-current="page">Pratinjau Excel</span>
    </nav>
    <header class="a-atp-detail__header a-atp-warna-<?= (int) $hasil['semester_paket'] ?>">
        <div>
            <p class="a-atp-detail__eyebrow"><?= esc($hasil['gen'] . ' · ' . $hasil['mapel']) ?></p>
            <h1>Pratinjau ATP Excel</h1>
            <span class="a-atp-detail__status">Belum disimpan · hanya pemeriksaan</span>
        </div>
        <div class="a-atp-detail__metrik" aria-label="Jumlah hasil perbandingan">
            <span><strong><?= (int) $hasil['jumlah']['baru'] ?></strong> Baru</span>
            <span><strong><?= (int) $hasil['jumlah']['ubah'] ?></strong> Berubah</span>
            <span><strong><?= (int) $hasil['jumlah']['tetap'] ?></strong> Tetap</span>
            <span><strong><?= (int) $hasil['jumlah']['galat'] ?></strong> Galat</span>
        </div>
    </header>
    <section class="a-card" aria-labelledby="judul-pratinjau">
        <h2 id="judul-pratinjau">Pemeriksaan sebelum impor</h2>
        <p>Pratinjau ini memeriksa struktur Excel, hak penugasan Gen–Mapel, BAB, TP, dan perbedaannya
            dengan data database. <strong>Tidak ada BAB atau TP yang disimpan atau dihapus.</strong>
            Baris yang tidak tercantum di Excel tidak dianggap sebagai perintah hapus.</p>
        <?php if ($hasil['kosong']): ?>
            <div class="a-empty" role="status">
                <h3>File belum berisi BAB atau TP</h3>
                <p>Isi tabel Bagian A dan Bagian B pada sheet semester, lalu unggah kembali.</p>
            </div>
        <?php elseif ($hasil['jumlah']['galat'] > 0): ?>
            <div class="a-alert a-alert--bahaya" role="alert">
                Terdapat <?= (int) $hasil['jumlah']['galat'] ?> baris bermasalah.
                Perbaiki file Excel sebelum melanjutkan.
            </div>
        <?php else: ?>
            <div class="a-alert a-alert--info" role="status">
                Struktur dan baris yang dibaca tidak mengandung masalah terdeteksi.
                Tahap konfirmasi transaksi impor belum diaktifkan.
            </div>
        <?php endif ?>
        <p class="a-proto-muted">Rencana judul/media Materi pada Excel bukan unggah berkas dan tidak mengubah materi siswa.</p>
        <div class="a-tabel-gulir">
            <table class="a-tbl">
                <thead><tr>
                    <th scope="col">Sheet</th>
                    <th scope="col">Baris</th>
                    <th scope="col">Jenis</th>
                    <th scope="col">Kode / Nomor</th>
                    <th scope="col">Nama</th>
                    <th scope="col">Hasil</th>
                    <th scope="col">Keterangan</th>
                </tr></thead>
                <tbody>
                <?php foreach ($hasil['baris'] as $baris): ?>
                    <tr>
                        <td><?= esc('Semester ' . $baris['semester']) ?></td>
                        <td><?= (int) $baris['baris'] ?></td>
                        <td><?= esc($baris['jenis']) ?></td>
                        <td><strong><?= esc($baris['kode']) ?></strong></td>
                        <td><?= esc($baris['nama']) ?></td>
                        <td><?= esc(match ($baris['status']) {
                            'baru' => 'Baru', 'ubah' => 'Berubah', 'tetap' => 'Tetap',
                            default => 'Galat'
                        }) ?></td>
                        <td><?= esc($baris['catatan'] ?: '—') ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <div class="a-atp-detail__toolbar-actions">
            <a class="a-btn a-btn--sekunder" href="<?= esc($kembali, 'attr') ?>">Kembali ke Semester</a>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
