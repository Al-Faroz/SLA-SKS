<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal'), 'attr') ?>">Bank Soal</a>
        <span aria-hidden="true">/</span><span aria-current="page">Review Word</span>
    </nav>
    <header class="a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Review Bank Soal</p>
            <h1>Periksa Soal Sebelum Disimpan</h1>
            <p class="a-proto-muted"><?= esc($tp['kode_tp'] . ' — ' . $tp['judul']) ?></p>
        </div>
    </header>

    <section class="a-card">
        <h2>Alur unggah bank soal</h2>
        <p class="a-help">1. Unggah Word ✓ &nbsp; → &nbsp; 2. Review isi, gambar, tabel, rumus &nbsp; → &nbsp; 3. Konfirmasi simpan</p>
        <?php if ($pesan = session()->getFlashdata('word_galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
        <?php endif ?>
        <?php if ($word_sudah_disimpan): ?>
            <div class="a-alert" role="status">Bank soal ini sudah disimpan. Review tetap dapat dibuka, tetapi konfirmasi ulang tidak diperlukan.</div>
        <?php elseif ($word_expired): ?>
            <div class="a-alert a-alert--bahaya" role="alert">Waktu review habis. Silakan unggah ulang Word.</div>
        <?php else: ?>
            <div class="a-alert" role="status">Validasi struktur berhasil. <strong><?= (int) $word_review['jumlah'] ?> soal <?= esc(ucfirst($word_review['jenis'])) ?></strong> siap diperiksa. Belum ada soal yang disimpan.</div>
        <?php endif ?>
        <p class="a-help">Periksa pertanyaan, pilihan A–E, jawaban benar, gambar dan tabel. Equation Word dalam pratinjau masih dapat tampil sebagai teks sederhana; berkas Word asli dipertahankan untuk ketelitian konten.</p>
        <div class="a-word-review-list">
            <?php foreach ($word_review['soal'] as $soalItem): ?>
                <details class="a-word-review-item" open>
                    <summary>Soal <?= (int) $soalItem['nomor'] ?> <span class="a-help">· <?= (int) $soalItem['konten']['gambar'] ?> gambar, <?= (int) $soalItem['konten']['tabel'] ?> tabel, <?= (int) $soalItem['konten']['rumus'] ?> rumus</span></summary>
                    <div class="a-word-preview"><?= $soalItem['html_pertanyaan'] ?></div>
                    <div class="a-tabel-gulir">
                        <table class="a-tbl">
                            <thead><tr><th scope="col">Pilihan</th><th scope="col">Isi jawaban</th><th scope="col">Kunci</th></tr></thead>
                            <tbody>
                                <?php foreach ($soalItem['html_opsi'] as $huruf => $html): ?>
                                    <tr>
                                        <th scope="row"><?= esc($huruf) ?></th>
                                        <td><div class="a-word-preview"><?= $html ?></div></td>
                                        <td><?= $soalItem['kunci'] === $huruf ? '✓ Benar' : '—' ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endforeach ?>
        </div>
        <?php if (!$word_sudah_disimpan && !$word_expired): ?>
            <form class="a-form a-jarak-atas" method="post" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/review/' . $word_import_id . '/konfirmasi'), 'attr') ?>">
                <?= csrf_field() ?>
                <label><input type="checkbox" name="tinjau" value="ya" required> Saya sudah memeriksa isi, kunci, gambar, tabel, dan rumus seluruh soal.</label>
                <p class="a-help">Konfirmasi akan menambah atau memperbarui soal pada TP draf. Soal lama yang tidak tercantum dalam dokumen tidak dihapus.</p>
                <button type="submit" class="a-btn a-btn--utama">Konfirmasi &amp; Simpan <?= (int) $word_review['jumlah'] ?> Soal</button>
            </form>
        <?php endif ?>
        <a class="a-btn a-btn--sekunder a-jarak-atas" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal'), 'attr') ?>">Kembali ke Bank Soal</a>
    </section>
</div>
<?= $this->endSection() ?>
