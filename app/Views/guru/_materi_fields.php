<?php
/** @var array<string,mixed> $nilai */
$nilai = $nilai ?? [];
$judulMateri = (string) ($nilai['judul'] ?? '');
$jenisMateri = (string) ($nilai['jenis'] ?? 'youtube');
?>
<div class="a-field">
    <label class="a-label" for="materi-judul">Judul Materi/Submateri</label>
    <input class="a-input" id="materi-judul" name="judul" maxlength="180" required value="<?= esc($judulMateri, 'attr') ?>">
</div>
<div class="a-field">
    <label class="a-label" for="materi-jenis">Jenis media pembelajaran</label>
    <select id="materi-jenis" name="jenis" required>
        <?php foreach (['youtube' => 'YouTube', 'pdf' => 'PDF', 'gambar' => 'Gambar', 'audio' => 'Audio'] as $k => $label): ?>
            <option value="<?= esc($k, 'attr') ?>" <?= $jenisMateri === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach ?>
    </select>
    <p class="a-help">Tautan HTTPS YouTube wajib untuk jenis YouTube. PDF, gambar dan audio memerlukan unggahan berkas pada tahap berikutnya; Materi baru tetap nonaktif.</p>
</div>
<div class="a-field">
    <label class="a-label" for="materi-url">URL YouTube (hanya untuk jenis YouTube)</label>
    <input class="a-input" id="materi-url" name="url_youtube" type="url" maxlength="255" placeholder="https://www.youtube.com/watch?v=..." value="<?= esc((string) ($nilai['url_youtube'] ?? ''), 'attr') ?>">
</div>
<div class="a-field">
    <label class="a-label" for="materi-waktu">Minimal waktu belajar (menit)</label>
    <input class="a-input" id="materi-waktu" name="min_waktu_menit" type="number" min="0" max="1092.25" step="0.01" required value="<?= esc(number_format(((int) ($nilai['min_waktu_detik'] ?? 0)) / 60, 2, '.', ''), 'attr') ?>">
    <p class="a-help">Isi dalam menit, misalnya 1.5 untuk satu setengah menit. Nilai disimpan sebagai detik agar kompatibel dengan database.</p>
</div>
<div class="a-field">
    <label class="a-label" for="materi-resume">Petunjuk Resume</label>
    <textarea id="materi-resume" name="petunjuk_resume" rows="4" maxlength="20000" required><?= esc((string) ($nilai['petunjuk_resume'] ?? '')) ?></textarea>
</div>
<div class="a-field">
    <label class="a-label" for="materi-hots">Petunjuk tugas HOTS</label>
    <textarea id="materi-hots" name="petunjuk_hots" rows="4" maxlength="20000" required><?= esc((string) ($nilai['petunjuk_hots'] ?? '')) ?></textarea>
</div>
