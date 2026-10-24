<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;
use RuntimeException;
use ZipArchive;

/**
 * Pembuat DOCX kosong soal PG A–E. Satu format untuk Diagnostik/Sumatif.
 * Impor Word belum diaktifkan sampai parser yang memverifikasi tanda tangan siap.
 */
final class TemplateWordSoalService
{
    public static function buat(array $identitas, string $jenis, int $jumlah, string $kunci): string
    {
        if (! in_array($jenis, ['diagnostik', 'sumatif'], true) || $jumlah < 1 || $jumlah > 200) {
            throw new DomainException('Jenis atau jumlah soal tidak valid (1–200).');
        }
        if (strlen($kunci) < 32) {
            throw new RuntimeException('Kunci tanda tangan template belum dikonfigurasi dengan aman.');
        }

        $meta = [
            'format' => 'SLA-SKS-WORD-V1',
            'tp_id' => (int) ($identitas['tp_id'] ?? 0),
            'guru_id' => (int) ($identitas['guru_id'] ?? 0),
            'paket_id' => (int) ($identitas['paket_id'] ?? 0),
            'jenis' => $jenis,
            'jumlah' => $jumlah,
            'struktur_versi' => (int) ($identitas['struktur_versi'] ?? 0),
        ];
        if (min($meta['tp_id'], $meta['guru_id'], $meta['paket_id'], $meta['struktur_versi']) < 1) {
            throw new DomainException('Identitas template tidak lengkap.');
        }
        $payload = json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payload, $kunci);

        // Desain dokumen hanya membentuk OOXML statis; struktur baris tetap 1 + 6 per soal.
        $esc = static fn (string $t): string => htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $run = static function (string $text, bool $bold = false, string $color = '243746', int $size = 21) use ($esc): string {
            return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>' .
                ($bold ? '<w:b/>' : '') . '<w:color w:val="' . $color . '"/><w:sz w:val="' . $size .
                '"/><w:szCs w:val="' . $size . '"/></w:rPr><w:t xml:space="preserve">' .
                $esc($text) . '</w:t></w:r>';
        };
        $p = static function (string $text, string $kind = 'normal') use ($run): string {
            $style = [
                'eyebrow' => [true, '127457', 18, 80, 30, ''],
                'title' => [true, '143E34', 38, 25, 70, '<w:pBdr><w:bottom w:val="single" w:sz="18" w:color="1B8A65" w:space="8"/></w:pBdr>'],
                'subtitle' => [false, '52687A', 19, 45, 160, ''],
                'heading' => [true, '145942', 24, 200, 95, '<w:shd w:val="clear" w:fill="E7F2ED"/><w:ind w:left="110" w:right="110"/>'],
                'identity' => [false, '243746', 20, 45, 40, '<w:ind w:left="110"/>'],
                'hint' => [false, '314E5B', 19, 40, 60, '<w:shd w:val="clear" w:fill="F0F6F3"/><w:ind w:left="150" w:right="100"/>'],
                'normal' => [false, '243746', 20, 45, 55, ''],
            ];
            [$bold, $color, $size, $before, $after, $extra] = $style[$kind] ?? $style['normal'];
            return '<w:p><w:pPr><w:spacing w:before="' . $before . '" w:after="' . $after .
                '"/>' . $extra . '</w:pPr>' . $run($text, $bold, $color, $size) . '</w:p>';
        };
        $cell = static function (string $t, int $width, string $mode = 'normal', bool $keepNext = false) use ($run): string {
            $backgrounds = [
                'header' => '17533F', 'question' => 'E4F2EC',
                'option-a' => 'FFFFFF', 'option-b' => 'F4F8FA', 'answer' => 'EDF7F0',
                'normal' => 'FFFFFF',
            ];
            $background = $backgrounds[$mode] ?? 'FFFFFF';
            $textColor = $mode === 'header' ? 'FFFFFF' : '243746';
            $bold = $mode === 'header' || $mode === 'question';
            $font = $mode === 'header' ? 21 : 20;
            return '<w:tc><w:tcPr><w:tcW w:w="' . $width .
                '" w:type="dxa"/><w:shd w:val="clear" w:fill="' . $background . '"/>' .
                '<w:tcMar><w:top w:w="105" w:type="dxa"/><w:start w:w="115" w:type="dxa"/>' .
                '<w:bottom w:w="105" w:type="dxa"/><w:end w:w="115" w:type="dxa"/></w:tcMar>' .
                '<w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:spacing w:after="0" w:before="0"/>' .
                ($keepNext ? '<w:keepNext/>' : '') . '</w:pPr>' .
                $run($t, $bold, $textColor, $font) . '</w:p></w:tc>';
        };
        $row = static function (string $a, string $b, string $c, string $mode = 'normal', bool $keepNext = false) use ($cell): string {
            $first = $mode === 'option-a' || $mode === 'option-b' ? 'question' : $mode;
            return '<w:tr><w:trPr><w:cantSplit/>' .
                ($mode === 'header' ? '<w:tblHeader w:val="true"/>' : '') .
                '</w:trPr>' . $cell($a, 1900, $first, $keepNext) . $cell($b, 6300, $mode, $keepNext) .
                $cell($c, 1000, $mode === 'header' ? 'header' : 'answer', $keepNext) . '</w:tr>';
        };

        $body = $p('SLA-SKS  /  FORMULIR BANK SOAL', 'eyebrow') .
            $p('BANK SOAL ' . strtoupper($jenis), 'title') .
            $p('Learning Management System · MTs Fase D', 'subtitle') .
            $p('IDENTITAS PEMBELAJARAN', 'heading');
        foreach ([
            'Gen' => (string) ($identitas['gen'] ?? ''),
            'Guru' => (string) ($identitas['guru'] ?? ''),
            'Mata Pelajaran' => (string) ($identitas['mapel'] ?? ''),
            'Kurikulum' => (string) ($identitas['kurikulum'] ?? ''),
            'Semester' => (string) ($identitas['semester'] ?? ''),
            'TP' => (string) ($identitas['kode_tp'] ?? '') . ' — ' . (string) ($identitas['judul_tp'] ?? ''),
            'Jumlah soal' => (string) $jumlah,
        ] as $label => $nilai) {
            $body .= $p($label . '  :  ' . $nilai, 'identity');
        }
        $body .= $p('PETUNJUK PENGISIAN', 'heading');
        foreach ([
            '1. Identitas Gen, Guru, Mapel, Kurikulum, Semester, TP dan jenis ujian telah diisi otomatis oleh sistem.',
            '2. Jangan mengubah label Bagian / Soal n / A–E atau urutan baris. Isi hanya kolom Isi dan Benar.',
            '3. Setiap soal wajib mempunyai lima pilihan A–E yang terisi. Beri tanda ✓ pada tepat satu pilihan benar.',
            '4. Gambar, teks Arab/RTL, aksara Jawa/Unicode, Equation Word (Insert → Equation) dan tabel termasuk tabel bersarang boleh ditempatkan pada kolom Isi soal maupun pilihan.',
            '5. Audio dan video menggunakan tautan Google Drive dengan izin Viewer: tulis Audio: atau Video: di sel Isi, diikuti URL Google Drive. Jangan menyisipkan file audio/video langsung atau tautan YouTube/Vimeo.',
            '6. Periksa keutuhan gambar, rumus, tabel dan pemutaran media pada pratinjau sebelum konfirmasi impor.',
            '7. Dokumen ini adalah template kosong; jangan menyalin soal contoh dari dokumen referensi. Simpan sebagai .docx sebelum unggah.',
        ] as $petunjuk) {
            $body .= $p($petunjuk, 'hint');
        }
        $body .= $p('DAFTAR SOAL PILIHAN GANDA', 'heading') .
            $p('Isi kolom Isi dan berikan satu tanda ✓ pada kolom Benar untuk setiap nomor.');
        $table = '<w:tbl><w:tblPr><w:tblW w:w="9200" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders>' .
            '<w:top w:val="single"/><w:left w:val="single"/><w:bottom w:val="single"/>' .
            '<w:right w:val="single"/><w:insideH w:val="single"/><w:insideV w:val="single"/>' .
            '</w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="1900"/><w:gridCol w:w="6300"/><w:gridCol w:w="1000"/></w:tblGrid>' . $row('Bagian', 'Isi', 'Benar', 'header');
        for ($n = 1; $n <= $jumlah; $n++) {
            $table .= $row('Soal ' . $n, '', '', 'question', true);
            foreach (['A', 'B', 'C', 'D', 'E'] as $pilihan) {
                $table .= $row($pilihan, '', '', $n % 2 === 0 ? 'option-b' : 'option-a', $pilihan !== 'E');
            }
        }
        $body .= $table . '</w:tbl>';
        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' .
            $body . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>' .
            '<w:pgMar w:top="1120" w:right="1250" w:bottom="1120" w:left="1250"' .
            ' w:header="600" w:footer="600" w:gutter="0"/></w:sectPr></w:body></w:document>';

        $file = tempnam(sys_get_temp_dir(), 'sla_word_');
        if ($file === false) {
            throw new RuntimeException('Gagal menyiapkan berkas sementara.');
        }
        $zip = new ZipArchive();
        try {
            if ($zip->open($file, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Gagal menyiapkan DOCX.');
            }
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>' .
                '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
                '<Default Extension="xml" ContentType="application/xml"/>' .
                '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
                '</Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>' .
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
                '</Relationships>');
            $zip->addFromString('word/document.xml', $document);
            // Identitas terikat konteks dan bertanda tangan; wajib dicek ulang saat impor nanti.
            $zip->addFromString('customXml/sla-sks.xml', '<?xml version="1.0" encoding="UTF-8"?>' .
                '<template xmlns="urn:sla-sks:word:v1"><payload>' . $esc($payload) .
                '</payload><signature>' . $signature . '</signature></template>');
            if (! $zip->close()) {
                throw new RuntimeException('Gagal menyelesaikan DOCX.');
            }
            $data = file_get_contents($file);
            if (! is_string($data) || $data === '') {
                throw new RuntimeException('DOCX kosong.');
            }
            return $data;
        } finally {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
