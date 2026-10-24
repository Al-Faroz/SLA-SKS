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
        <p class="a-help">Ringkasan hanya menampilkan jumlah soal aktif, tanpa pertanyaan atau kunci jawaban. Unduhan template Word kosong tersedia untuk TP draf. Impor Word dan pengelolaan bank soal belum tersedia.</p>
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
            <p class="a-help">Pilih jumlah soal yang akan dibuat. Setiap soal memiliki satu bagian pertanyaan dan lima opsi A–E, seluruhnya kosong. Impor Word belum tersedia pada tahap ini.</p>
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
            <p class="a-help">Pratinjau validasi hanya memeriksa dokumen; tidak menambah atau mengubah bank soal. Gunakan template yang diunduh dari TP ini.</p>
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
        <?php if (! empty($word_pratinjau) && is_array($word_pratinjau)): ?>
            <div class="a-alert" role="status"><?= esc($word_pesan ?? 'Pratinjau tersedia') ?></div>
            <h3>Pratinjau soal dengan tabel dan gambar</h3>
            <p class="a-help">Gambar dan tabel ditampilkan dari DOCX pada permintaan ini. Equation Word masih ditampilkan dalam bentuk teks sederhana. Belum ada soal yang disimpan ke database.</p>
            <?php foreach ($word_pratinjau as $soalItem): ?>
                <section class="a-jarak-atas" aria-label="Soal <?= (int) $soalItem['nomor'] ?>">
                    <h4>Soal <?= (int) $soalItem['nomor'] ?></h4>
                    <div class="a-word-preview"><?= $soalItem['html_pertanyaan'] ?></div>
                    <div class="a-tabel-gulir"><table class="a-tbl">
                        <thead><tr><th>Opsi</th><th>Isi jawaban</th><th>Kunci</th></tr></thead>
                        <tbody>
                        <?php foreach ($soalItem['html_opsi'] as $huruf => $html): ?>
                            <tr><th><?= esc($huruf) ?></th><td><div class="a-word-preview"><?= $html ?></div></td>
                                <td><?= $soalItem['kunci'] === $huruf ? 'Benar' : '—' ?></td></tr>
                        <?php endforeach ?>
                        </tbody>
                    </table></div>
                </section>
            <?php endforeach ?>
        <?php endif ?>
        <?php if ($pesan = session()->getFlashdata('word_galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php if ($pesan = session()->getFlashdata('word_sukses')): ?>
            <div class="a-alert" role="status"><?= esc($pesan) ?></div>
            <?php $rincianWord = session()->getFlashdata('word_rincian'); ?>
            <?php if (is_array($rincianWord)): ?>
                <div class="a-tabel-gulir"><table class="a-tbl"><thead><tr><th>Deteksi konten Word tiap soal</th></tr></thead><tbody>
                    <?php foreach ($rincianWord as $item): ?><tr><td><?= esc($item) ?></td></tr><?php endforeach ?>
                </tbody></table></div>
            <?php endif ?>
            <?php $soalWord = session()->getFlashdata('word_soal'); ?>
            <?php if (is_array($soalWord)): ?>
                <h3 class="a-jarak-atas">Pratinjau teks soal dan pilihan jawaban</h3>
                <p class="a-help">Hanya untuk Guru. Pratinjau ini menampilkan teks dan kunci; gambar, rumus Word, serta tabel bersarang belum dirender secara visual dan belum disimpan. Periksa dokumen Word asli untuk membandingkan tata letaknya.</p>
                <?php foreach ($soalWord as $soalItem): ?>
                    <section class="a-jarak-atas" aria-label="Soal <?= (int) $soalItem['nomor'] ?>">
                        <h4>Soal <?= (int) $soalItem['nomor'] ?></h4>
                        <p><?= nl2br(esc($soalItem['pertanyaan'])) ?></p>
                        <div class="a-tabel-gulir"><table class="a-tbl">
                            <thead><tr><th scope="col">Pilihan</th><th scope="col">Isi teks</th><th scope="col">Kunci</th></tr></thead>
                            <tbody>
                            <?php foreach ($soalItem['opsi'] as $huruf => $isiOpsi): ?>
                                <tr><th scope="row"><?= esc($huruf) ?></th><td><?= nl2br(esc($isiOpsi)) ?></td><td><?= $soalItem['kunci'] === $huruf ? 'Benar' : '—' ?></td></tr>
                            <?php endforeach ?>
                            </tbody>
                        </table></div>
                        <p class="a-help">Konten terdeteksi: gambar <?= (int) $soalItem['konten']['gambar'] ?>,
                            rumus <?= (int) $soalItem['konten']['rumus'] ?>,
                            tabel <?= (int) $soalItem['konten']['tabel'] ?>,
                            Arab <?= (int) $soalItem['konten']['arab'] ?>.</p>
                    </section>
                <?php endforeach ?>
            <?php endif ?>
        <?php endif ?>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Kembali ke Rincian TP</a>
    </section>
</div>
<?= $this->endSection() ?>
