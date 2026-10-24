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
    public static function periksa(string $path, int $tpId, int $guruId, int $paketId, int $versi, string $jenis, string $kunci, ?string $kodeTp = null): array
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
            if (!is_string($document) || strlen($document) > 8 * 1024 * 1024) {
                throw new DomainException('Isi dokumen Word tidak terbaca atau terlalu besar.');
            }
            $doc = self::xml($document);
            $xp = new DOMXPath($doc);
            $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $rows = $xp->query('//w:body/w:tbl[1]/w:tr');
            if ($rows === false || $rows->length < 7 || ($rows->length - 1) % 6 !== 0) {
                throw new DomainException('Struktur tabel soal berubah. Pastikan setiap soal memiliki pilihan A–E.');
            }
            $n = (int) (($rows->length - 1) / 6);
            if ($n > 200) {
                throw new DomainException('Jumlah soal pada dokumen terlalu banyak.');
            }

            if (is_string($meta)) {
                // Jika tanda tangan masih ada, periksa dengan ketat agar tak menerima identitas yang diubah.
                $xml = self::xml($meta);
                $metaXp = new DOMXPath($xml);
                $payload = trim($metaXp->evaluate('string(//*[local-name()="payload"])'));
                $signature = trim($metaXp->evaluate('string(//*[local-name()="signature"])'));
                if ($payload === '' || !hash_equals(hash_hmac('sha256', $payload, $kunci), $signature)) {
                    throw new DomainException('Identitas dokumen tidak cocok. Gunakan template dari TP ini.');
                }
                $id = json_decode($payload, true, 16, JSON_THROW_ON_ERROR);
                if (!is_array($id) || ($id['format'] ?? '') !== 'SLA-SKS-WORD-V1'
                    || (int) ($id['tp_id'] ?? 0) !== $tpId || (int) ($id['guru_id'] ?? 0) !== $guruId
                    || (int) ($id['paket_id'] ?? 0) !== $paketId
                    || (int) ($id['struktur_versi'] ?? 0) !== $versi
                    || ($id['jenis'] ?? '') !== $jenis || (int) ($id['jumlah'] ?? 0) !== $n) {
                    throw new DomainException('Identitas TP atau jumlah soal pada Word tidak cocok.');
                }
                $identitasDiperiksa = 'tanda_tangan';
            } else {
                // Microsoft Word dapat membuang customXml yang tak diregistrasikan oleh generator lama.
                // Jangan menghukum Guru atas Save biasa: otorisasi TP/Guru sudah dicek dari server,
                // dan kita pastikan judul + kode TP dalam bagian identitas dokumen sesuai halaman.
                if ($kodeTp === null || trim($kodeTp) === '') {
                    throw new DomainException('Identitas TP belum dapat diperiksa oleh server.');
                }
                $namaUjianBenar = false;
                $kodeTpBenar = false;
                $paragraf = $xp->query('//w:body/w:p');
                if ($paragraf !== false) {
                    foreach ($paragraf as $p) {
                        $teks = preg_replace('/\\s+/u', ' ', trim(self::teks($xp, $p)));
                        if ($teks === null) {
                            continue;
                        }
                        if (str_contains(mb_strtoupper($teks), 'BANK SOAL ' . strtoupper($jenis))) {
                            $namaUjianBenar = true;
                        }
                        if (preg_match('/^TP\\s*:\\s*' . preg_quote($kodeTp, '/') . '(?:\\s|—|-|$)/iu', $teks)) {
                            $kodeTpBenar = true;
                        }
                    }
                }
                if (!$namaUjianBenar || !$kodeTpBenar) {
                    throw new DomainException('Jenis ujian atau kode TP dalam dokumen tidak sesuai. Pilih Word dari TP ini.');
                }
                $identitasDiperiksa = 'identitas_terlihat';
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
                'pemeriksaan_identitas' => $identitasDiperiksa,
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
