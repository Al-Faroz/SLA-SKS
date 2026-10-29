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
                Setelah diperiksa, perubahan dapat disimpan melalui konfirmasi eksplisit di bawah.
            </div>
        <?php endif ?>
        <p class="a-proto-muted">Rencana judul/media Materi pada Excel bukan unggah berkas dan tidak mengubah materi siswa.
            Kode TP pada daftar berikut dibentuk otomatis oleh sistem, bukan dibaca dari kolom Excel.</p>
        <?php
        $ringkasanSemester = [];
        for ($semesterKe = 1; $semesterKe <= 6; $semesterKe++) {
            $ringkasanSemester[$semesterKe] = ['BAB' => 0, 'TP' => 0];
        }
        foreach ($hasil['baris'] as $item) {
            $semesterKe = (int) $item['semester'];
            if (isset($ringkasanSemester[$semesterKe][$item['jenis']])) {
                $ringkasanSemester[$semesterKe][$item['jenis']]++;
            }
        }
        ?>
        <h3>Ringkasan semester pada file</h3>
        <div class="a-tabel-gulir">
            <table class="a-tbl">
                <thead><tr>
                    <th scope="col">Semester</th>
                    <th scope="col">Jumlah BAB</th>
                    <th scope="col">Jumlah TP</th>
                    <th scope="col">Keputusan</th>
                </tr></thead>
                <tbody>
                <?php for ($semesterKe = 1; $semesterKe <= 6; $semesterKe++): ?>
                    <?php $mode = $hasil['mode_semester'][$semesterKe] ?? ''; ?>
                    <tr>
                        <th scope="row">Semester <?= $semesterKe ?></th>
                        <td><?= (int) $ringkasanSemester[$semesterKe]['BAB'] ?></td>
                        <td><?= (int) $ringkasanSemester[$semesterKe]['TP'] ?></td>
                        <td><?= esc(match ($mode) {
                            'baru' => 'Akan ditambahkan',
                            'tetap' => 'Sudah sesuai — tanpa perubahan',
                            'galat' => 'Ditolak — periksa rincian',
                            default => 'Tidak diisi di Excel',
                        }) ?></td>
                    </tr>
                <?php endfor ?>
                </tbody>
            </table>
        </div>
        <div class="a-tabel-gulir">
            <table class="a-tbl">
                <thead><tr>
                    <th scope="col">Sheet</th>
                    <th scope="col">Baris</th>
                    <th scope="col">Jenis</th>
                    <th scope="col">Kode TP otomatis / Nomor BAB</th>
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
            <a class="a-btn a-btn--sekunder" href="<?= esc($kembali, 'attr') ?>">Batal / Kembali</a>
            <?php if (! empty($hasil['boleh_konfirmasi']) && ! empty($hasil['stage_token'])): ?>
                <form method="post" data-atp-confirm-form
                    action="<?= esc(site_url('guru/peta-atp/konfirmasi/' . (int) $hasil['paket_id']), 'attr') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="stage_token" value="<?= esc($hasil['stage_token'], 'attr') ?>">
                    <input type="hidden" name="pilihan" value="<?= (int) $hasil['pilihan'] ?>">
                    <input type="hidden" name="semester" value="<?= (int) $hasil['semester_asal'] ?>">
                    <label class="a-proto-muted">
                        <input type="checkbox" name="periksa_rencana" value="1" required>
                        Saya telah memeriksa semua semester dan memahami bahwa hanya BAB/TP
                        baru pada semester kosong yang akan disimpan.
                    </label>
                    <button class="a-btn a-btn--utama" type="submit" data-atp-confirm-submit>
                        Konfirmasi dan Simpan BAB / TP
                    </button>
                    <p class="a-proto-muted" data-atp-confirm-status role="status" aria-live="polite"></p>
                </form>
            <?php elseif (! $hasil['kosong'] && $hasil['jumlah']['galat'] === 0): ?>
                <p class="a-proto-muted">Seluruh baris yang diisi sudah sesuai database. Tidak ada BAB/TP baru untuk disimpan; unggahan ulang tidak menggandakan data.</p>
            <?php endif ?>
        </div>
        <?php if (! empty($hasil['boleh_konfirmasi'])): ?>
            <p class="a-proto-muted">Konfirmasi berlaku 15 menit pada sesi Guru ini.
                Sistem memeriksa kembali semua perubahan sebelum menyimpan; jika data berubah, unggah ulang.</p>
        <?php endif ?>
    </section>
</div>
<script src="<?= esc(base_url('public/assets/js/atp-konfirmasi.js'), 'attr') ?>" defer></script>
<?= $this->endSection() ?>
