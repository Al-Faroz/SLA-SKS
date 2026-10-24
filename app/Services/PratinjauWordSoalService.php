<?php
declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMXPath;
use DomainException;
use ZipArchive;

/**
 * Validasi pratinjau DOCX asli, tanpa mutasi DB atau mengekspos kunci ke siswa.
 * Konten kaya dihitung dan dilaporkan; belum disimpan ke bank soal.
 */
final class PratinjauWordSoalService
{
    public static function periksa(string $path, int $tpId, int $guruId, int $paketId, int $versi, string $jenis, string $kunci): array
    {
        if (!is_file($path) || filesize($path) > 15 * 1024 * 1024) {
            throw new DomainException('Word tidak tersedia atau melebihi 15 MB.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new DomainException('Dokumen tidak dapat dibuka sebagai DOCX.');
        }
        try {
            if ($zip->locateName('word/vbaProject.bin') !== false || $zip->numFiles > 2000) {
                throw new DomainException('Dokumen mengandung macro atau terlalu kompleks.');
            }
            $meta = $zip->getFromName('customXml/sla-sks.xml');
            $document = $zip->getFromName('word/document.xml');
            if (!is_string($meta) || !is_string($document) || strlen($document) > 8 * 1024 * 1024) {
                throw new DomainException('Metadata atau dokumen Word tidak lengkap.');
            }
            $xml = self::xml($meta);
            $xp = new DOMXPath($xml);
            $payload = trim($xp->evaluate('string(//*[local-name()="payload"])'));
            $signature = trim($xp->evaluate('string(//*[local-name()="signature"])'));
            if ($payload === '' || !hash_equals(hash_hmac('sha256', $payload, $kunci), $signature)) {
                throw new DomainException('Identitas template tidak sah. Unduh ulang dari aplikasi.');
            }
            $id = json_decode($payload, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($id) || ($id['format'] ?? '') !== 'SLA-SKS-WORD-V1'
                || (int) ($id['tp_id'] ?? 0) !== $tpId || (int) ($id['guru_id'] ?? 0) !== $guruId
                || (int) ($id['paket_id'] ?? 0) !== $paketId
                || (int) ($id['struktur_versi'] ?? 0) !== $versi
                || ($id['jenis'] ?? '') !== $jenis) {
                throw new DomainException('Identitas atau versi TP pada Word tidak cocok. Unduh ulang.');
            }
            $doc = self::xml($document);
            $xp = new DOMXPath($doc);
            $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $rows = $xp->query('//w:body/w:tbl[1]/w:tr');
            $n = (int) ($id['jumlah'] ?? 0);
            if ($n < 1 || $n > 200 || $rows === false || $rows->length !== 1 + 6 * $n) {
                throw new DomainException('Jumlah baris soal tidak sesuai identitas template.');
            }
            $hasil = [];
            for ($i = 0; $i < $n; ++$i) {
                $index = 1 + $i * 6;
                $soal = '';
                $opsi = [];
                $benar = [];
                $media = ['gambar' => 0, 'rumus' => 0, 'tabel' => 0, 'arab' => 0];
                for ($j = 0; $j < 6; ++$j) {
                    $row = $rows->item($index + $j);
                    $cells = $xp->query('./w:tc', $row);
                    if ($cells === false || $cells->length !== 3) {
                        throw new DomainException('Soal ' . ($i + 1) . ': format kolom berubah.');
                    }
                    $expected = $j === 0 ? 'Soal ' . ($i + 1) : chr(64 + $j);
                    if (trim(self::teks($xp, $cells->item(0))) !== $expected) {
                        throw new DomainException('Soal ' . ($i + 1) . ': label baris ' . $expected . ' berubah.');
                    }
                    $cell = $cells->item(1);
                    $isi = trim(self::teks($xp, $cell));
                    if ($isi === '' && self::jumlah($xp, './/*[local-name()="drawing" or local-name()="pict" or local-name()="oMath" or local-name()="tbl"]', $cell) === 0) {
                        throw new DomainException('Soal ' . ($i + 1) . ': ' . $expected . ' masih kosong.');
                    }
                    foreach (['gambar' => 'drawing', 'rumus' => 'oMath', 'tabel' => 'tbl'] as $tipe => $tag) {
                        $media[$tipe] += self::jumlah($xp, './/*[local-name()="' . $tag . '"]', $cell);
                    }
                    if (preg_match('/[\x{0600}-\x{06FF}]/u', $isi)) {
                        ++$media['arab'];
                    }
                    if ($j === 0) {
                        $soal = $isi;
                    } else {
                        $opsi[$expected] = $isi;
                        if (trim(self::teks($xp, $cells->item(2))) === '✓') {
                            $benar[] = $expected;
                        } elseif (trim(self::teks($xp, $cells->item(2))) !== '') {
                            throw new DomainException('Soal ' . ($i + 1) . ': tanda kunci harus ✓.');
                        }
                    }
                }
                if (count($benar) !== 1) {
                    throw new DomainException('Soal ' . ($i + 1) . ': wajib tepat satu kunci ✓.');
                }
                $hasil[] = ['nomor' => $i + 1, 'pertanyaan' => $soal,
                    'opsi' => $opsi, 'kunci' => $benar[0], 'konten' => $media];
            }
            return ['jenis' => $jenis, 'jumlah' => $n, 'soal' => $hasil,
                'catatan' => 'Validasi struktur berhasil. Konten kaya belum diimpor/disimpan ke database.'];
        } finally {
            $zip->close();
        }
    }

    private static function xml(string $source): DOMDocument
    {
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$doc->loadXML($source, LIBXML_NONET)) {
                throw new DomainException('XML dalam Word tidak valid.');
            }
            return $doc;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function teks(DOMXPath $xp, \DOMNode $node): string
    {
        $texts = $xp->query('.//*[local-name()="t"]', $node);
        $result = '';
        if ($texts !== false) {
            foreach ($texts as $t) {
                $result .= $t->textContent;
            }
        }
        return $result;
    }

    private static function jumlah(DOMXPath $xp, string $query, \DOMNode $node): int
    {
        $n = $xp->query($query, $node);
        return $n === false ? 0 : $n->length;
    }
}
