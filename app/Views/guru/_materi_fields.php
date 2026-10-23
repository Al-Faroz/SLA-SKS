<?php
/** @var array<string,mixed> $nilai */
$nilai = $nilai ?? [];
$judulMateri = (string) ($nilai['judul'] ?? '');
$jenisMateri = (string) ($nilai['jenis'] ?? 'youtube');
$berkasAda = ! empty($nilai['berkas_id']);
?>
<div class="a-field">
    <label class="a-label" for="materi-judul">Judul Materi/Submateri</label>
    <input class="a-input" id="materi-judul" name="judul" maxlength="180" required value="<?= esc($judulMateri, 'attr') ?>">
</div>
<div class="a-field">
    <label class="a-label" for="materi-jenis">Jenis media pembelajaran</label>
    <select id="materi-jenis" name="jenis" data-materi-jenis required>
        <?php foreach (['youtube' => 'YouTube', 'pdf' => 'PDF', 'gambar' => 'Gambar', 'audio' => 'Audio'] as $k => $label): ?>
            <option value="<?= esc($k, 'attr') ?>" <?= $jenisMateri === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach ?>
    </select>
</div>
<div class="a-field" data-materi-youtube <?= $jenisMateri === 'youtube' ? '' : 'hidden' ?>>
    <label class="a-label" for="materi-url">Alamat YouTube</label>
    <input class="a-input" id="materi-url" name="url_youtube" type="url" maxlength="255" placeholder="https://www.youtube.com/watch?v=..." value="<?= esc((string) ($nilai['url_youtube'] ?? ''), 'attr') ?>" <?= $jenisMateri === 'youtube' ? 'required' : 'disabled' ?>>
</div>
<div class="a-field" data-materi-berkas <?= $jenisMateri === 'youtube' ? 'hidden' : '' ?>>
    <label class="a-label" for="materi-berkas">Upload file</label>
    <input class="a-input" id="materi-berkas" name="media" type="file"
        accept="<?= $jenisMateri === 'gambar' ? '.jpg,.jpeg,.png,.webp' : ($jenisMateri === 'audio' ? '.mp3,.m4a' : '.pdf') ?>"
        <?= $jenisMateri === 'youtube' || $jenisMateri === 'pdf' ? 'disabled' : '' ?>
        <?= ! $berkasAda && in_array($jenisMateri, ['gambar','audio'], true) ? 'required' : '' ?>>
    <p class="a-help" data-materi-file-info>Gambar JPG/PNG/WebP dan audio MP3/M4A maksimal 10 MB.</p>
</div>
<p class="a-help" data-materi-pdf-info <?= $jenisMateri === 'pdf' ? '' : 'hidden' ?>>Form upload PDF disiapkan, tetapi pengunggahan belum aktif sampai pemeriksaan keamanan dan batas 40 halaman tersedia.</p>
<div class="a-field">
    <label class="a-label" for="materi-waktu">Minimal waktu belajar (menit)</label>
    <input class="a-input" id="materi-waktu" name="min_waktu_menit" type="number" min="0" max="1092.25" step="0.01" required value="<?= esc(number_format(((int) ($nilai['min_waktu_detik'] ?? 0)) / 60, 2, '.', ''), 'attr') ?>">
    <p class="a-help">Contoh: 1.5 menit. Database tetap menyimpan durasi dalam detik.</p>
</div>
<div class="a-field">
    <label class="a-label" for="materi-resume">Petunjuk Resume</label>
    <textarea id="materi-resume" name="petunjuk_resume" rows="4" maxlength="20000" required><?= esc((string) ($nilai['petunjuk_resume'] ?? '')) ?></textarea>
</div>
<div class="a-field">
    <label class="a-label" for="materi-hots">Petunjuk tugas HOTS</label>
    <textarea id="materi-hots" name="petunjuk_hots" rows="4" maxlength="20000" required><?= esc((string) ($nilai['petunjuk_hots'] ?? '')) ?></textarea>
</div>
