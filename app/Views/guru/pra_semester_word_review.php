<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-review-word-page">
    <?php $detailUrl=site_url('guru/pra-semester/'.(int)$paket['id']).$filterGen; ?>
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'),'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc($detailUrl,'attr') ?>">Kelola Soal Pra-Semester</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Review Word</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Review DOCX</p>
            <h1>Periksa Word Pra-Semester</h1>
            <p class="a-proto-muted"><?= esc((string)$paket['nama_mapel']) ?> · Gen
                <?= esc((string)$paket['nama_gen']) ?> · Semester <?= (int)$paket['semester'] ?></p>
        </div>
        <a class="a-btn a-btn--sekunder"
            href="<?= esc($detailUrl,'attr') ?>">Kembali ke Kelola Soal</a>
    </header>
    <?php if ($pesan=session()->getFlashdata('word_galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>
    <?php $telahDiterapkan=$stage['status']==='diterapkan';
        $expired=strtotime((string)$stage['kedaluwarsa_pada']) < time(); ?>
    <section class="a-card">
        <h2>Review <?= (int)$hasil['jumlah'] ?> soal Pra-Semester</h2>
        <p class="a-help">1. Isi Word → 2. Unggah → 3. Tinjau BAB, soal, opsi dan kunci →
            4. Konfirmasi simpan. Rumus dan media kompleks perlu dicek kembali.</p>
        <?php if ($telahDiterapkan): ?>
            <div class="a-alert">Word sudah diterapkan; konfirmasi ulang tidak diperlukan.</div>
        <?php elseif ($expired || $sudah_dipakai): ?>
            <div class="a-alert a-alert--bahaya" role="alert">
                Review telah kedaluwarsa atau bank sudah digunakan siswa. Unggah ulang bila masih berhak.
            </div>
        <?php else: ?>
            <div class="a-alert">Validasi struktur serta cakupan BAB berhasil. Belum ada soal disimpan.</div>
        <?php endif ?>
        <div class="a-word-review-list">
            <?php foreach ($hasil['soal'] as $item): ?>
                <details class="a-word-review-item" <?= (int)$item['nomor']===1?'open':'' ?>>
                    <summary>Soal <?= (int)$item['nomor'] ?> · BAB <?= (int)$item['nomor_bab'] ?>
                        <span class="a-help"> · <?= (int)$item['konten']['gambar'] ?> gambar,
                            <?= (int)$item['konten']['tabel'] ?> tabel,
                            <?= (int)$item['konten']['rumus'] ?> rumus</span>
                    </summary>
                    <div class="a-word-preview"><?= $item['html_pertanyaan'] ?></div>
                    <div class="a-tabel-gulir">
                        <table class="a-tbl">
                            <thead><tr><th>Opsi</th><th>Isi</th><th>Kunci</th></tr></thead>
                            <tbody>
                            <?php foreach ($item['html_opsi'] as $huruf=>$html): ?>
                                <tr>
                                    <th scope="row"><?= esc($huruf) ?></th>
                                    <td><div class="a-word-preview"><?= $html ?></div></td>
                                    <td><?= $item['kunci']===$huruf?'✓ Benar':'—' ?></td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endforeach ?>
        </div>
        <?php if (!$telahDiterapkan && !$expired && !$sudah_dipakai): ?>
            <form method="post" class="a-form a-jarak-atas"
                action="<?= esc(site_url('guru/pra-semester/'.(int)$paket['id'].
                    '/word/review/'.$token_review.'/konfirmasi').$filterGen,'attr') ?>">
                <?= csrf_field() ?>
                <label class="a-label"><input type="checkbox" name="tinjau" value="ya" required>
                    Saya sudah memeriksa seluruh BAB, soal, opsi A–E, kunci, gambar, tabel dan rumus.
                </label>
                <p class="a-help">Konfirmasi menambah soal baru. Soal lama tidak ditimpa.</p>
                <button class="a-btn a-btn--utama" type="submit">
                    Konfirmasi &amp; Simpan <?= (int)$hasil['jumlah'] ?> Soal
                </button>
            </form>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
