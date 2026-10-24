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

        $esc = static fn (string $t): string => htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $p = static fn (string $t): string => '<w:p><w:r><w:t xml:space="preserve">' .
            htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</w:t></w:r></w:p>';
        $cell = static fn (string $t): string => '<w:tc><w:tcPr><w:tcW w:w="0" w:type="auto"/></w:tcPr>' .
            '<w:p><w:r><w:t xml:space="preserve">' . htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8') .
            '</w:t></w:r></w:p></w:tc>';
        $row = static fn (string $a, string $b, string $c): string =>
            '<w:tr>' . $cell($a) . $cell($b) . $cell($c) . '</w:tr>';

        $body = $p('SLA-SKS — BANK SOAL ' . strtoupper($jenis));
        foreach ([
            'Gen' => (string) ($identitas['gen'] ?? ''),
            'Guru' => (string) ($identitas['guru'] ?? ''),
            'Mata Pelajaran' => (string) ($identitas['mapel'] ?? ''),
            'Kurikulum' => (string) ($identitas['kurikulum'] ?? ''),
            'Semester' => (string) ($identitas['semester'] ?? ''),
            'TP' => (string) ($identitas['kode_tp'] ?? '') . ' — ' . (string) ($identitas['judul_tp'] ?? ''),
            'Jumlah soal' => (string) $jumlah,
        ] as $label => $nilai) {
            $body .= $p($label . ': ' . $nilai);
        }
        $body .= $p('Isi setiap Soal, pilihan A–E dan tepat satu tanda ✓ di kolom Benar. Jangan mengubah identitas template.');
        $table = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders>' .
            '<w:top w:val="single"/><w:left w:val="single"/><w:bottom w:val="single"/>' .
            '<w:right w:val="single"/><w:insideH w:val="single"/><w:insideV w:val="single"/>' .
            '</w:tblBorders></w:tblPr>' . $row('Bagian', 'Isi', 'Benar');
        for ($n = 1; $n <= $jumlah; $n++) {
            $table .= $row('Soal ' . $n, '', '');
            foreach (['A', 'B', 'C', 'D', 'E'] as $pilihan) {
                $table .= $row($pilihan, '', '');
            }
        }
        $body .= $table . '</w:tbl>';
        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' .
            $body . '<w:sectPr/></w:body></w:document>';

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
