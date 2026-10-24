<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Rincian TP</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Bank Soal</span>
    </nav>
    <header class="a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Bank Soal TP</p>
            <h1>Bank Soal Diagnostik dan Sumatif</h1>
            <p class="a-proto-muted"><?= esc($tp['kode_tp'] . ' — ' . $tp['judul']) ?></p>
        </div>
    </header>
    <section class="a-card" aria-labelledby="bank-soal-status">
        <h2 id="bank-soal-status">Ketersediaan bank soal</h2>
        <p class="a-help">Ringkasan hanya menampilkan jumlah soal aktif, tanpa pertanyaan atau kunci jawaban. Unduhan template Word kosong tersedia untuk TP draf. Setelah unggah, Guru meninjau isi Word kemudian mengonfirmasi penyimpanan pada TP draf.</p>
        <div class="a-tabel-gulir">
            <table class="a-tbl">
                <thead><tr><th scope="col">Jenis ujian</th><th scope="col">Soal aktif</th><th scope="col">Jumlah tayang</th><th scope="col">Status jumlah</th></tr></thead>
                <tbody>
                    <?php foreach ($ringkasan_bank as $jenis => $data): ?>
                        <tr>
                            <th scope="row"><?= esc(ucfirst($jenis)) ?></th>
                            <td><?= (int) $data['aktif'] ?></td>
                            <td><?= (int) $data['target'] ?></td>
                            <td><?= $data['memadai'] ? 'Jumlah mencukupi' : 'Belum mencukupi' ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <p class="a-help">Jumlah yang mencukupi belum berarti soal siap dipublikasikan: isi soal, opsi A–E, kunci dan aset tetap harus diperiksa.</p>
        <?php if ($tp['status'] === 'draf'): ?>
            <h3>Unduh template Word kosong</h3>
            <p class="a-help">Pilih jumlah soal yang akan dibuat. Setiap soal memiliki satu bagian pertanyaan dan lima opsi A–E, seluruhnya kosong. Pilih jumlah soal, unduh, isi, lalu unggah untuk review dan konfirmasi.</p>
            <?php foreach ($ringkasan_bank as $jenis => $data): ?>
                <form class="a-form a-jarak-atas" method="get" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/word/' . $jenis), 'attr') ?>">
                    <div class="a-field">
                        <label class="a-label" for="word-jumlah-<?= esc($jenis, 'attr') ?>">Jumlah soal <?= esc(ucfirst($jenis)) ?></label>
                        <input class="a-input" id="word-jumlah-<?= esc($jenis, 'attr') ?>" name="jumlah" type="number" min="<?= (int) $data['minimal'] ?>" max="200" required value="<?= max((int) $data['minimal'], (int) $data['target']) ?>">
                    </div>
                    <button class="a-btn a-btn--sekunder" type="submit">Unduh Word <?= esc(ucfirst($jenis)) ?></button>
                </form>
            <?php endforeach ?>
            <h3 class="a-jarak-atas">Unggah Word untuk validasi</h3>
            <p class="a-help">Unggahan diperiksa dan disimpan sementara secara privat. Bank soal baru berubah setelah Anda memeriksa seluruh soal dan menekan Konfirmasi Simpan.</p>
            <?php foreach (['diagnostik' => 'Diagnostik', 'sumatif' => 'Sumatif'] as $jenis => $namaJenis): ?>
                <form method="post" enctype="multipart/form-data" class="a-form a-jarak-atas" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/word/' . $jenis . '/pratinjau'), 'attr') ?>">
                    <?= csrf_field() ?>
                    <div class="a-field">
                        <label class="a-label" for="unggah-word-<?= esc($jenis, 'attr') ?>">Word <?= esc($namaJenis) ?> (.docx)</label>
                        <input class="a-input" type="file" id="unggah-word-<?= esc($jenis, 'attr') ?>" name="word_soal" accept=".docx" required>
                    </div>
                    <button type="submit" class="a-btn a-btn--sekunder">Periksa Word <?= esc($namaJenis) ?></button>
                </form>
            <?php endforeach ?>
        <?php endif ?>
        <?php if ($pesan = session()->getFlashdata('word_galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php if ($pesan = session()->getFlashdata('word_sukses')): ?>
            <div class="a-alert" role="status"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php if (!empty($word_riwayat)): ?>
            <h3 class="a-jarak-atas">Riwayat unggah dan review</h3>
            <p class="a-help">Buka pratinjau tersimpan untuk melihat ulang pertanyaan, opsi, gambar, tabel, dan kunci. Arsip Word disimpan privat di server.</p>
            <div class="a-tabel-gulir">
                <table class="a-tbl">
                    <thead><tr><th scope="col">Jenis</th><th scope="col">Dibuat</th><th scope="col">Status</th><th scope="col">Tindakan</th></tr></thead>
                    <tbody>
                    <?php foreach ($word_riwayat as $riwayat): ?>
                        <tr>
                            <td><?= esc(ucfirst($riwayat['jenis'])) ?></td>
                            <td><?= esc($riwayat['dibuat_pada']) ?></td>
                            <td><?= $riwayat['status'] === 'diterapkan' ? 'Sudah disimpan' : 'Dalam review' ?></td>
                            <td>
                                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/review/' . $riwayat['id']), 'attr') ?>">Lihat review</a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke Rincian TP</a>
    </section>
</div>
<?= $this->endSection() ?>
