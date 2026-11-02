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
            <h1>Kelola Bank Soal</h1>
            <p class="a-proto-muted"><?= esc($tp['kode_tp'] . ' — ' . $tp['judul']) ?></p>
        </div>
    </header>
    <section class="a-card a-bank-soal__ringkasan" aria-labelledby="bank-soal-status">
        <h2 id="bank-soal-status">Ketersediaan Bank Soal</h2>
        <p class="a-help">Ringkasan untuk kedua jenis ujian pada TP ini. Jumlah soal aktif diperiksa terpisah dari kelengkapan isi, kunci, dan media.</p>
        <div class="a-bank-soal__ringkasan-grid">
            <?php foreach ($ringkasan_bank as $jenis => $data): ?>
                <article class="a-bank-soal__ringkasan-item">
                    <div class="a-bank-soal__ringkasan-atas">
                        <h3><?= esc(ucfirst($jenis)) ?></h3>
                        <span class="a-bank-soal__status <?= $data['memadai'] ? 'is-cukup' : 'is-kurang' ?>">
                            <?= $data['memadai'] ? 'Jumlah mencukupi' : 'Belum mencukupi' ?>
                        </span>
                    </div>
                    <p class="a-bank-soal__angka"><strong><?= (int) $data['aktif'] ?></strong> <span>soal aktif</span></p>
                    <dl class="a-bank-soal__rincian">
                        <div><dt>Minimal</dt><dd><?= (int) $data['minimal'] ?> soal</dd></div>
                        <div><dt>Jumlah tayang</dt><dd><?= (int) $data['target'] ?> soal</dd></div>
                    </dl>
                </article>
            <?php endforeach ?>
        </div>
        <p class="a-help">Status di atas hanya menunjukkan kecukupan jumlah soal. Pemeriksaan final konten dilakukan saat TP akan dijadikan Siap.</p>
    </section>

    <section class="a-card a-bank-soal__workspace a-jarak-atas" aria-labelledby="judul-kelola-bank">
        <h2 id="judul-kelola-bank">Bank Soal</h2>
        <p class="a-help">Pilih tab jenis ujian untuk mengelola soal. Semua daftar, formulir dan riwayat di bawah mengikuti tab yang sedang aktif.</p>
        <nav class="a-bank-soal__tabs" aria-label="Jenis bank soal">
            <?php foreach (['diagnostik' => 'Diagnostik', 'sumatif' => 'Sumatif'] as $jenisTab => $namaTab): ?>
                <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal?jenis=' . $jenisTab), 'attr') ?>"
                    class="a-bank-soal__tab <?= $jenis_dipilih === $jenisTab ? 'is-aktif' : '' ?>"
                    <?= $jenis_dipilih === $jenisTab ? 'aria-current="page"' : '' ?>>
                    <strong><?= esc($namaTab) ?></strong>
                    <span><?= (int) ($ringkasan_bank[$jenisTab]['aktif'] ?? 0) ?> soal aktif</span>
                </a>
            <?php endforeach ?>
        </nav>
        <div class="a-bank-soal__tab-konten">
            <?php if ($pesan = session()->getFlashdata('soal_galat') ?? session()->getFlashdata('word_galat')): ?>
                <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
            <?php endif ?>
            <?php if ($pesan = session()->getFlashdata('soal_sukses') ?? session()->getFlashdata('word_sukses')): ?>
                <div class="a-alert" role="status"><?= esc($pesan) ?></div>
            <?php endif ?>
        <section class="a-soal-daftar" aria-labelledby="judul-daftar-bank">
            <h3 id="judul-daftar-bank">Bank <?= esc(ucfirst($jenis_dipilih)) ?></h3>
            <p class="a-help">Jumlah tersimpan: <?= (int) $total_soal ?> soal (aktif maupun nonaktif). Pilih cara menyiapkan soal: satu per satu di aplikasi atau melalui Word.</p>
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
                <div class="a-tabel-gulir a-jarak-atas"><table class="a-tbl a-tbl--cards a-tbl--bank">
                    <thead><tr><th scope="col">Urutan</th><th scope="col">Pertanyaan</th><th scope="col">Status</th>
                        <th scope="col">Asal</th><th scope="col">Tindakan</th></tr></thead>
                    <tbody>
                    <?php foreach ($daftar_soal as $item): ?>
                        <?php $sumber = json_decode((string) ($item['konten_terstruktur_json'] ?? ''), true); ?>
                        <tr>
                            <th scope="row" data-label="Nomor"><?= (int) $item['urutan'] ?></th>
                            <td data-label="Pertanyaan" class="a-tbl--bank__soal"><?= esc(mb_strimwidth((string) $item['pertanyaan'], 0, 140, '…', 'UTF-8')) ?></td>
                            <td data-label="Status"><span class="a-soal-status-badge <?= (int) $item['aktif'] === 1 ? 'is-aktif' : '' ?>"><?= (int) $item['aktif'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                            <td data-label="Asal"><?= is_array($sumber) && ($sumber['origin'] ?? '') === 'manual' ? 'Editor aplikasi' : 'Word' ?></td>
                            <td data-label="Tindakan" class="a-soal-kolom-tindakan"><div class="a-soal-tindakan">
                                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/soal/' . (int) $item['id']), 'attr') ?>">Lihat</a>
                                <?php if ($tp['status'] === 'draf'): ?>
                                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/soal/' . (int) $item['id'] . '/edit'), 'attr') ?>">Sunting</a>
                                    <form class="a-soal-status-form" method="post"
                                        onsubmit="return confirm('<?= (int) $item['aktif'] === 1 ? 'Hapus soal dari bank aktif tanpa menghapus arsip' : 'Pulihkan' ?> soal ini?');"
                                        action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/soal/' . (int) $item['id'] . '/status'), 'attr') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="aktif" value="<?= (int) $item['aktif'] === 1 ? '0' : '1' ?>">
                                        <input type="hidden" name="versi" value="<?= esc(\App\Services\KelolaSoalGuruService::versi($item), 'attr') ?>">
                                        <button class="a-btn a-btn--sekunder" type="submit"><?= (int) $item['aktif'] === 1 ? 'Hapus dari Aktif' : 'Pulihkan' ?></button>
                                    </form>
                                <?php else: ?>Lihat saja<?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table></div>
            <?php endif ?>
            <?php if ($jumlah_halaman > 1): ?>
                <nav class="a-soal-tabs a-jarak-atas" aria-label="Halaman soal <?= esc(ucfirst($jenis_dipilih)) ?>">
                    <?php if ($halaman_aktif > 1): ?>
                        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal?jenis=' . $jenis_dipilih . '&halaman=' . ($halaman_aktif - 1)), 'attr') ?>">Sebelumnya</a>
                    <?php endif ?>
                    <?php for ($hal = max(1, $halaman_aktif - 2); $hal <= min($jumlah_halaman, $halaman_aktif + 2); $hal++): ?>
                        <a class="a-btn a-btn--sekunder <?= $hal === $halaman_aktif ? 'is-aktif' : '' ?>"
                           <?= $hal === $halaman_aktif ? 'aria-current="page"' : '' ?>
                           href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal?jenis=' . $jenis_dipilih . '&halaman=' . $hal), 'attr') ?>"><?= $hal ?></a>
                    <?php endfor ?>
                    <?php if ($halaman_aktif < $jumlah_halaman): ?>
                        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal?jenis=' . $jenis_dipilih . '&halaman=' . ($halaman_aktif + 1)), 'attr') ?>">Berikutnya</a>
                    <?php endif ?>
                </nav>
            <?php endif ?>
        </section>
        <?php if ($tp['status'] === 'draf'): ?>
            <h3 class="a-jarak-atas">Siapkan melalui Word — <?= esc(ucfirst($jenis_dipilih)) ?></h3>
            <p class="a-help">Pilih jumlah soal yang akan dibuat. Setiap soal memiliki satu bagian pertanyaan dan lima opsi A–E, seluruhnya kosong. Pilih jumlah soal, unduh, isi, lalu unggah untuk review dan konfirmasi.</p>
            <?php foreach ($ringkasan_bank as $jenis => $data): ?>
                <?php if ($jenis !== $jenis_dipilih) continue; ?>
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
                <?php if ($jenis !== $jenis_dipilih) continue; ?>
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

        <?php if (!empty($word_riwayat)): ?>
            <h3 class="a-jarak-atas">Riwayat unggah dan review</h3>
            <p class="a-help">Buka pratinjau tersimpan untuk melihat ulang pertanyaan, opsi, gambar, tabel, dan kunci. Arsip Word disimpan privat di server.</p>
            <div class="a-tabel-gulir">
                <table class="a-tbl a-tbl--cards">
                    <thead><tr><th scope="col">Jenis</th><th scope="col">Dibuat</th><th scope="col">Status</th><th scope="col">Tindakan</th></tr></thead>
                    <tbody>
                    <?php foreach ($word_riwayat as $riwayat): ?>
                        <tr>
                            <td data-label="Jenis"><?= esc(ucfirst($riwayat['jenis'])) ?></td>
                            <td data-label="Dibuat"><?= esc($riwayat['dibuat_pada']) ?></td>
                            <td data-label="Status"><?= esc(match ($riwayat['status']) {
                                'diterapkan' => 'Sudah disimpan',
                                'pratinjau' => 'Dalam review',
                                'kedaluwarsa' => 'Kedaluwarsa',
                                default => 'Tidak tersedia',
                            }) ?></td>
                            <td data-label="Tindakan">
                                <?php if (in_array($riwayat['status'], ['pratinjau', 'diterapkan'], true)): ?>
                                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/review/' . $riwayat['id']), 'attr') ?>">Lihat review</a>
                                <?php else: ?>
                                    <span class="a-help">Unggah ulang Word untuk review baru.</span>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        </div>
        <a class="a-btn a-btn--sekunder a-jarak-atas" href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke Rincian TP</a>
    </section>
</div>
<?= $this->endSection() ?>
