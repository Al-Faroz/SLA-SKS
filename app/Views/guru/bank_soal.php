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
        <section class="a-soal-daftar" aria-labelledby="judul-daftar-bank">
            <h3 id="judul-daftar-bank">Daftar soal yang tersimpan</h3>
            <nav class="a-soal-tabs" aria-label="Jenis bank soal">
                <?php foreach (['diagnostik' => 'Diagnostik', 'sumatif' => 'Sumatif'] as $jenisTab => $namaTab): ?>
                    <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal?jenis=' . $jenisTab), 'attr') ?>"
                        class="a-btn a-btn--sekunder <?= $jenis_dipilih === $jenisTab ? 'is-aktif' : '' ?>"
                        <?= $jenis_dipilih === $jenisTab ? 'aria-current="page"' : '' ?>><?= esc($namaTab) ?></a>
                <?php endforeach ?>
            </nav>
            <?php if ($tp['status'] === 'draf'): ?>
                <a class="a-btn a-btn--utama a-jarak-atas"
                    href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/soal/' . $jenis_dipilih . '/baru'), 'attr') ?>">
                    + Tambah Soal <?= esc(ucfirst($jenis_dipilih)) ?>
                </a>
            <?php else: ?>
                <p class="a-help">TP sudah Siap. Penambahan dan koreksi memerlukan proses khusus.</p>
            <?php endif ?>
            <?php if (empty($daftar_soal)): ?>
                <p class="a-help a-jarak-atas">Belum ada soal. Tambahkan langsung melalui aplikasi atau impor Word.</p>
            <?php else: ?>
                <div class="a-tabel-gulir a-jarak-atas"><table class="a-tbl">
                    <thead><tr><th scope="col">Urutan</th><th scope="col">Pertanyaan</th><th scope="col">Status</th>
                        <th scope="col">Asal</th><th scope="col">Tindakan</th></tr></thead>
                    <tbody>
                    <?php foreach ($daftar_soal as $item): ?>
                        <?php $sumber = json_decode((string) ($item['konten_terstruktur_json'] ?? ''), true); ?>
                        <tr>
                            <th scope="row"><?= (int) $item['urutan'] ?></th>
                            <td><?= esc(mb_strimwidth((string) $item['pertanyaan'], 0, 140, '…', 'UTF-8')) ?></td>
                            <td><?= (int) $item['aktif'] === 1 ? 'Aktif' : 'Nonaktif' ?></td>
                            <td><?= is_array($sumber) && ($sumber['origin'] ?? '') === 'manual' ? 'Editor aplikasi' : 'Word' ?></td>
                            <td>
                                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/soal/' . (int) $item['id']), 'attr') ?>">Lihat</a>
                                <?php if ($tp['status'] === 'draf'): ?>
                                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/soal/' . (int) $item['id'] . '/edit'), 'attr') ?>">Sunting</a>
                                    <form class="a-soal-status-form" method="post"
                                        onsubmit="return confirm('<?= (int) $item['aktif'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?> soal ini?');"
                                        action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/soal/' . (int) $item['id'] . '/status'), 'attr') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="aktif" value="<?= (int) $item['aktif'] === 1 ? '0' : '1' ?>">
                                        <input type="hidden" name="versi" value="<?= esc(\App\Services\KelolaSoalGuruService::versi($item), 'attr') ?>">
                                        <button class="a-btn a-btn--sekunder" type="submit"><?= (int) $item['aktif'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                    </form>
                                <?php else: ?>Lihat saja<?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table></div>
            <?php endif ?>
        </section>
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
        <?php if ($pesan = session()->getFlashdata('soal_galat') ?? session()->getFlashdata('word_galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php if ($pesan = session()->getFlashdata('soal_sukses') ?? session()->getFlashdata('word_sukses')): ?>
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
