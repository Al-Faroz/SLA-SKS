<?php
$rich = $soal ? json_decode((string) ($soal['konten_terstruktur_json'] ?? ''), true) : null;
$wordSource = is_array($rich) ? ($rich['word_source_id'] ?? null) : null;
$raw = ['pertanyaan' => $soal['pertanyaan'] ?? ''];
foreach (['A','B','C','D','E'] as $huruf) {
    $raw['opsi_' . strtolower($huruf)] = $soal['opsi_' . strtolower($huruf)] ?? '';
}
if (is_array($rich) && !empty($rich['html_pertanyaan'])) {
    $raw['pertanyaan'] = \App\Services\HtmlSoalGuruService::untukGuru(
        $rich['html_pertanyaan'], (int) $tp['id'], is_string($wordSource) ? $wordSource : null
    );
    foreach (['A','B','C','D','E'] as $huruf) {
        if (!empty($rich['html_opsi'][$huruf])) {
            $raw['opsi_' . strtolower($huruf)] = \App\Services\HtmlSoalGuruService::untukGuru(
                $rich['html_opsi'][$huruf], (int) $tp['id'], is_string($wordSource) ? $wordSource : null
            );
        }
    }
} else {
    foreach ($raw as $k => $value) { $raw[$k] = '<p>' . esc((string) $value) . '</p>'; }
}
$jenisLabel = ucfirst($jenis);
$uri = 'guru/tp/' . (int) $tp['id'] . '/bank-soal';
$tujuan = $soal === null ? $uri . '/soal/' . $jenis : $uri . '/soal/' . $jenis . '/' . (int) $soal['id'];
?>
<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-page__editor--luas">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru/peta-atp'), 'attr') ?>">Peta ATP</a>
        <span>/</span><a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Rincian TP</a>
        <span>/</span><a href="<?= esc(site_url($uri . '?jenis=' . $jenis), 'attr') ?>">Bank Soal</a>
        <span>/</span><span aria-current="page"><?= $soal ? 'Sunting' : 'Tambah' ?></span>
    </nav>
    <header class="a-page__heading"><div>
        <p class="a-proto-eyebrow">Guru · <?= esc($jenisLabel) ?> · TP <?= esc($tp['kode_tp']) ?></p>
        <h1><?= $soal ? 'Sunting Soal' : 'Tambah Soal Baru' ?></h1>
        <p class="a-proto-muted">Soal dapat ditulis langsung di aplikasi; format tetap pilihan ganda A–E dengan satu jawaban benar.</p>
    </div></header>
    <section class="a-card">
        <?php if ($pesan = session()->getFlashdata('soal_galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
        <?php endif ?>
        <p class="a-help">Gunakan format teks, tabel, dan bahasa Arab pada editor. Klik tombol Gambar pada toolbar SunEditor, pilih file PNG/JPEG, lalu gambar otomatis masuk pada posisi kursor. Untuk rumus kompleks dari Word, pastikan tampilannya tetap benar sebelum menyimpan.</p>
        <form id="form-soal-guru" class="a-form" method="post" action="<?= esc(site_url($tujuan), 'attr') ?>">
            <?= csrf_field() ?>
            <?php if ($soal): ?><input type="hidden" name="versi" value="<?= esc($soal_versi, 'attr') ?>"><?php endif ?>
            <?php foreach ($raw as $name => $default): ?>
                <div class="a-field a-soal-editor-field">
                    <label class="a-label" for="isi-<?= esc($name, 'attr') ?>">
                        <?= $name === 'pertanyaan' ? 'Pertanyaan' : 'Pilihan ' . strtoupper(substr($name, -1)) ?>
                    </label>
                    <textarea class="a-input a-rich-field" name="<?= esc($name, 'attr') ?>"
                        id="isi-<?= esc($name, 'attr') ?>" rows="<?= $name === 'pertanyaan' ? 6 : 3 ?>"
                        maxlength="100000" required><?= esc(old($name, (string) $default, false)) ?></textarea>
                </div>
            <?php endforeach ?>
            <div class="a-field">
                <label class="a-label" for="kunci-a">Jawaban benar (wajib pilih satu)</label>
                <div class="a-soal-kunci" role="radiogroup" aria-label="Jawaban benar">
                    <?php foreach (['A','B','C','D','E'] as $huruf): ?>
                        <label>
                            <input id="kunci-<?= strtolower($huruf) ?>" type="radio" name="kunci" value="<?= $huruf ?>"
                                <?= old('kunci', $soal['kunci'] ?? '', false) === $huruf ? 'checked' : '' ?> required>
                            <?= $huruf ?>
                        </label>
                    <?php endforeach ?>
                </div>
            </div>
            <p class="a-help a-soal-gambar-info">Untuk gambar: klik tombol <strong>Gambar</strong> di toolbar pertanyaan atau opsi yang sedang Anda sunting, pilih file PNG/JPEG (maksimal 2 MB). Tidak perlu langkah unggah terpisah.</p>
            <p class="a-help" id="status-gambar-soal" role="status" aria-live="polite"></p>
            <div class="a-soal-aksi">
                <button class="a-btn a-btn--utama" type="submit">Simpan Soal</button>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($uri . '?jenis=' . $jenis), 'attr') ?>">Batal</a>
            </div>
        </form>
    </section>
</div>
<link rel="stylesheet" href="<?= esc(base_url('public/assets/vendor/suneditor/suneditor.min.css'), 'attr') ?>">
<script src="<?= esc(base_url('public/assets/vendor/suneditor/suneditor.min.js'), 'attr') ?>"></script>
<script src="<?= esc(base_url('public/assets/js/soal-editor.js'), 'attr') ?>"
    data-upload-url="<?= esc(site_url($uri . '/gambar'), 'attr') ?>"></script>
<?= $this->endSection() ?>
