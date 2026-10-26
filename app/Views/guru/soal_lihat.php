<?php
$rich = json_decode((string) ($soal['konten_terstruktur_json'] ?? ''), true);
$source = is_array($rich) && is_string($rich['word_source_id'] ?? null) ? $rich['word_source_id'] : null;
$isi = is_array($rich) && !empty($rich['html_pertanyaan'])
    ? \App\Services\HtmlSoalGuruService::untukGuru($rich['html_pertanyaan'], (int) $tp['id'], $source)
    : '<p>' . esc($soal['pertanyaan']) . '</p>';
$opts = [];
foreach (['A','B','C','D','E'] as $huruf) {
    $name = 'opsi_' . strtolower($huruf);
    $opts[$huruf] = is_array($rich) && !empty($rich['html_opsi'][$huruf])
        ? \App\Services\HtmlSoalGuruService::untukGuru($rich['html_opsi'][$huruf], (int) $tp['id'], $source)
        : '<p>' . esc($soal[$name]) . '</p>';
}
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span>/</span>
        <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal?jenis=' . $soal['jenis']), 'attr') ?>">Bank Soal</a>
        <span>/</span><span aria-current="page">Lihat Soal</span>
    </nav>
    <header class="a-page__heading"><div>
        <p class="a-proto-eyebrow">Guru · <?= esc(ucfirst($soal['jenis'])) ?> · TP <?= esc($tp['kode_tp']) ?></p>
        <h1>Soal <?= (int) $soal['urutan'] ?></h1>
        <p class="a-proto-muted"><?= (int) $soal['aktif'] === 1 ? 'Aktif' : 'Nonaktif' ?> — hanya Guru yang ditugaskan dapat melihat kunci.</p>
    </div></header>
    <section class="a-card">
        <h2>Pertanyaan</h2>
        <div class="a-word-preview"><?= $isi ?></div>
        <div class="a-tabel-gulir a-jarak-atas"><table class="a-tbl">
            <thead><tr><th scope="col">Pilihan</th><th scope="col">Isi</th><th scope="col">Jawaban</th></tr></thead>
            <tbody>
            <?php foreach ($opts as $huruf => $html): ?>
                <tr><th scope="row"><?= $huruf ?></th>
                    <td><div class="a-word-preview"><?= $html ?></div></td>
                    <td><?= $soal['kunci'] === $huruf ? '✓ Benar' : '—' ?></td></tr>
            <?php endforeach ?>
            </tbody>
        </table></div>
        <?php if ($tp['status'] === 'draf' && empty($soal['versi_konten_tp_id'])): ?>
            <a class="a-btn a-btn--utama" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal/soal/' . (int) $soal['id'] . '/edit'), 'attr') ?>">Sunting Soal</a>
        <?php endif ?>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/bank-soal?jenis=' . $soal['jenis']), 'attr') ?>">Kembali</a>
    </section>
</div>
<?= $this->endSection() ?>
