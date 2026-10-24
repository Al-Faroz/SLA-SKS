<?php
/**
 * Field bersama formulir Tambah dan Sunting Materi.
 * $nilai: input lama pada error atau baris Materi tersimpan.
 * $galatKolom: satu kolom yang perlu diperiksa; validasi final selalu server.
 */
$nilai = is_array($nilai ?? null) ? $nilai : [];
$galatKolom = (string) ($galatKolom ?? '');
$jenisMateri = (string) ($nilai['jenis'] ?? 'youtube');
if (! in_array($jenisMateri, ['youtube', 'pdf', 'gambar', 'audio'], true)) {
    $jenisMateri = 'youtube';
}
$berkasAda = ! empty($nilai['berkas_id']);
$judulMateri = (string) ($nilai['judul'] ?? '');
$menitMateri = isset($nilai['min_waktu_menit'])
    ? (string) $nilai['min_waktu_menit']
    : (isset($nilai['min_waktu_detik'])
        ? number_format((int) $nilai['min_waktu_detik'] / 60, 2, '.', '')
        : '');
$phpBytes = static function (string $nilai): int {
    if (! preg_match('/^\\s*(\\d+(?:\\.\\d+)?)\\s*([KMG]?)\\s*$/i', $nilai, $bagian)) {
        return 0;
    }
    return (int) ((float) $bagian[1] * match (strtoupper($bagian[2])) {
        'G' => 1073741824, 'M' => 1048576, 'K' => 1024, default => 1,
    });
};
$batasFilePhp = $phpBytes((string) ini_get('upload_max_filesize'));
$batasPostPhp = $phpBytes((string) ini_get('post_max_size'));
$batasServer = min(
    $batasFilePhp > 0 ? $batasFilePhp : PHP_INT_MAX,
    $batasPostPhp > 0 ? max(1, $batasPostPhp - 65536) : PHP_INT_MAX
);
?>
<div class="a-field">
    <label class="a-label" for="materi-judul">Judul Materi/Submateri <span aria-hidden="true">*</span></label>
    <input class="a-input" id="materi-judul" name="judul" maxlength="180" required
        autocomplete="off" value="<?= esc($judulMateri, 'attr') ?>"
        <?= $galatKolom === 'judul' ? 'aria-invalid="true" aria-describedby="materi-judul-bantu"' : '' ?>>
    <p id="materi-judul-bantu" class="a-help">Judul maksimal 180 karakter.</p>
</div>
<div class="a-field">
    <label class="a-label" for="materi-jenis">Jenis media pembelajaran <span aria-hidden="true">*</span></label>
    <select id="materi-jenis" name="jenis" class="a-input" data-materi-jenis required
        <?= $galatKolom === 'jenis' ? 'aria-invalid="true"' : '' ?>>
        <?php foreach (['youtube' => 'YouTube', 'pdf' => 'PDF', 'gambar' => 'Gambar', 'audio' => 'Audio'] as $kode => $label): ?>
            <option value="<?= esc($kode, 'attr') ?>"
                <?= $jenisMateri === $kode ? 'selected' : '' ?>
                <?= $berkasAda && $jenisMateri !== $kode ? 'disabled' : '' ?>>
                <?= esc($label) ?>
            </option>
        <?php endforeach ?>
    </select>
    <?php if ($berkasAda): ?>
        <p class="a-help">Media sudah terpasang; jenis media terkunci. Anda dapat mengganti file dengan jenis yang sama.</p>
    <?php endif ?>
</div>
<div class="a-field" data-materi-youtube <?= $jenisMateri === 'youtube' ? '' : 'hidden' ?>>
    <label class="a-label" for="materi-url">Alamat YouTube <span aria-hidden="true">*</span></label>
    <input class="a-input" id="materi-url" name="url_youtube" type="url" maxlength="255"
        placeholder="https://www.youtube.com/watch?v=..."
        value="<?= esc((string) ($nilai['url_youtube'] ?? ''), 'attr') ?>"
        <?= $jenisMateri === 'youtube' ? 'required' : 'disabled' ?>
        <?= $galatKolom === 'url_youtube' ? 'aria-invalid="true" aria-describedby="materi-url-bantu"' : '' ?>>
    <p id="materi-url-bantu" class="a-help">Gunakan URL video YouTube HTTPS yang valid.</p>
</div>
<div class="a-field" data-materi-berkas <?= $jenisMateri === 'youtube' ? 'hidden' : '' ?>>
    <label class="a-label" for="materi-berkas">Upload file media <?= ! $berkasAda ? '(wajib)' : '(opsional)' ?></label>
    <input class="a-input" id="materi-berkas" name="media" type="file"
        accept="<?= esc($jenisMateri === 'gambar' ? '.jpg,.jpeg,.png,.webp' : ($jenisMateri === 'audio' ? '.mp3,.m4a' : '.pdf'), 'attr') ?>"
        data-materi-batas-server="<?= (int) $batasServer ?>"
        <?= $jenisMateri === 'youtube' ? 'disabled' : '' ?>
        <?= $jenisMateri !== 'youtube' && ! $berkasAda ? 'required' : '' ?>
        <?= $galatKolom === 'media' ? 'aria-invalid="true"' : '' ?>
        aria-describedby="materi-file-info materi-file-server materi-file-error">
    <p class="a-help" id="materi-file-info" data-materi-file-info>
        <?= esc(match ($jenisMateri) {
            'pdf' => 'PDF maksimal 15 MB, 40 halaman; dokumen berisi fitur aktif/struktur tak didukung ditolak.',
            'gambar' => 'Gambar JPG, PNG atau WebP maksimal 10 MB.',
            'audio' => 'Audio MP3 atau M4A maksimal 10 MB.',
            default => 'Pilih media untuk menampilkan batas berkas.',
        }) ?>
    </p>
    <p class="a-help" id="materi-file-server">
        Batas server saat ini: upload_max_filesize <?= esc((string) ini_get('upload_max_filesize')) ?>,
        post_max_size <?= esc((string) ini_get('post_max_size')) ?>.
        <?php if ($berkasAda): ?>Kosongkan kolom file untuk mempertahankan berkas saat ini.<?php endif ?>
    </p>
    <p class="a-error" id="materi-file-error" data-materi-file-error aria-live="polite"></p>
</div>
<div class="a-field">
    <label class="a-label" for="materi-waktu">Minimal waktu belajar (menit) <span aria-hidden="true">*</span></label>
    <input class="a-input" id="materi-waktu" name="min_waktu_menit" type="number"
        min="0" max="1092.25" step="0.01" required
        value="<?= esc($menitMateri, 'attr') ?>"
        <?= $galatKolom === 'min_waktu_menit' ? 'aria-invalid="true"' : '' ?>>
    <p class="a-help">Masukkan waktu dalam menit; contoh 1.5. Nilai disimpan sebagai detik.</p>
</div>
<div class="a-field">
    <label class="a-label" for="materi-resume">Petunjuk Resume <span aria-hidden="true">*</span></label>
    <textarea class="a-input" id="materi-resume" name="petunjuk_resume" rows="4"
        maxlength="20000" required <?= $galatKolom === 'petunjuk_resume' ? 'aria-invalid="true"' : '' ?>><?= esc((string) ($nilai['petunjuk_resume'] ?? '')) ?></textarea>
</div>
<div class="a-field">
    <label class="a-label" for="materi-hots">Petunjuk tugas HOTS <span aria-hidden="true">*</span></label>
    <textarea class="a-input" id="materi-hots" name="petunjuk_hots" rows="4"
        maxlength="20000" required <?= $galatKolom === 'petunjuk_hots' ? 'aria-invalid="true"' : '' ?>><?= esc((string) ($nilai['petunjuk_hots'] ?? '')) ?></textarea>
</div>
