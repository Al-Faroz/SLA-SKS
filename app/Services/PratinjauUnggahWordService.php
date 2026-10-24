<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;
use DOMDocument;
use DOMXPath;
use ZipArchive;

/**
 * Pratinjau validasi unggahan Word. Tidak menulis data bank soal.
 * Komponen OOXML kaya dideteksi tetapi belum dikonversi menjadi konten ujian.
 */
final class PratinjauUnggahWordService
{
    /**
     * @return array{jumlah:int,soal:list<array{nomor:int,pertanyaan:string,pilihan:array<string,string>,kunci:string}>,kaya:array<string,int>,siap_impor:bool}
     */
    public static function baca(string $lokasi, string $jenis, array $konteks, string $kunci): array
    {
        if (! is_file($lokasi) || filesize($lokasi) === false || filesize($lokasi) > 15 * 1024 * 1024) {
            throw new DomainException('Berkas Word tidak tersedia atau melebihi 15 MB.');
        }
        if (! in_array($jenis, ['diagnostik','sumatif'], true)) {
            throw new DomainException('Jenis bank soal tidak valid.');
        }
        $zip = new ZipArchive();
        if ($zip->open($lokasi, ZipArchive::RDONLY) !== true) {
            throw new DomainException('Berkas bukan DOCX yang valid.');
        }
        try {
            if ($zip->numFiles > 1000) {
                throw new DomainException('DOCX mengandung terlalu banyak komponen.');
            }
            $ukuran = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $nama = (string) ($stat['name'] ?? '');
                if (str_contains($nama, '..') || str_starts_with($nama, '/')
                    || preg_match('/\\.(bin|exe|vbs|js|bat|ps1)$/i', $nama)
                    || str_contains($nama, 'vbaProject')) {
                    throw new DomainException('DOCX memuat bagian yang tidak diizinkan.');
                }
                $ukuran += (int) ($stat['size'] ?? 0);
                if ($ukuran > 30 * 1024 * 1024) {
                    throw new DomainException('Isi DOCX terlalu besar setelah dibuka.');
                }
            }

            $dokumen = $zip->getFromName('word/document.xml');
            $metadata = $zip->getFromName('customXml/sla-sks.xml');
            if (! is_string($dokumen) || ! is_string($metadata)) {
                throw new DomainException('Template tidak memiliki struktur atau identitas SLA-SKS.');
            }
            $metaXml = self::xml($metadata);
            $metaXPath = new DOMXPath($metaXml);
            $metaXPath->registerNamespace('s', 'urn:sla-sks:word:v1');
            $payload = trim((string) $metaXPath->evaluate('string(/s:template/s:payload)'));
            $signature = trim((string) $metaXPath->evaluate('string(/s:template/s:signature)'));
            if ($payload === '' || ! hash_equals(hash_hmac('sha256', $payload, $kunci), $signature)) {
                throw new DomainException('Identitas template tidak sah atau telah berubah.');
            }
            $meta = json_decode($payload, true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($meta) || ($meta['format'] ?? '') !== 'SLA-SKS-WORD-V1'
                || ($meta['jenis'] ?? '') !== $jenis
                || (int) ($meta['tp_id'] ?? 0) !== (int) ($konteks['tp_id'] ?? 0)
                || (int) ($meta['paket_id'] ?? 0) !== (int) ($konteks['paket_id'] ?? 0)
                || (int) ($meta['struktur_versi'] ?? 0) !== (int) ($konteks['struktur_versi'] ?? 0)
                || (int) ($meta['guru_id'] ?? 0) !== (int) ($konteks['guru_id'] ?? 0)) {
                throw new DomainException('Template tidak cocok dengan TP, Guru, jenis ujian, atau versi saat ini.');
            }

            $xml = self::xml($dokumen);
            $xp = new DOMXPath($xml);
            $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $xp->registerNamespace('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math');
            $tabel = $xp->query('/w:document/w:body/w:tbl')->item(0);
            if ($tabel === null) {
                throw new DomainException('Tabel soal Bagian–Isi–Benar tidak ditemukan.');
            }
            $baris = $xp->query('./w:tr', $tabel);
            $jumlah = (int) ($meta['jumlah'] ?? 0);
            if ($jumlah < 1 || $jumlah > 200 || $baris->length !== 1 + 6 * $jumlah) {
                throw new DomainException('Jumlah blok soal tidak sesuai template bertanda tangan.');
            }
            $kaya = [
                'rumus' => $xp->query('//m:oMath')->length,
                'gambar' => $xp->query('//w:drawing')->length,
                'tabel_bersarang' => $xp->query('//w:tc/w:tbl')->length,
                'teks_arab' => preg_match_all('/[\\x{0600}-\\x{06FF}]/u', $dokumen) ?: 0,
            ];
            $hasil = [];
            for ($i = 0; $i < $jumlah; $i++) {
                $pilihan = [];
                $benar = [];
                $q = 1 + $i * 6;
                $utama = self::cells($xp, $baris->item($q));
                if (count($utama) !== 3 || trim(self::teks($xp, $utama[0])) !== 'Soal ' . ($i + 1)) {
                    throw new DomainException('Penomoran Soal ' . ($i + 1) . ' tidak sesuai.');
                }
                $pertanyaan = trim(self::teks($xp, $utama[1]));
                if ($pertanyaan === '') {
                    throw new DomainException('Pertanyaan Soal ' . ($i + 1) . ' kosong.');
                }
                foreach (['A','B','C','D','E'] as $j => $opsi) {
                    $kolom = self::cells($xp, $baris->item($q + $j + 1));
                    if (count($kolom) !== 3 || trim(self::teks($xp, $kolom[0])) !== $opsi) {
                        throw new DomainException('Soal ' . ($i + 1) . ': urutan pilihan ' . $opsi . ' salah.');
                    }
                    $isi = trim(self::teks($xp, $kolom[1]));
                    if ($isi === '') {
                        throw new DomainException('Soal ' . ($i + 1) . ': pilihan ' . $opsi . ' kosong.');
                    }
                    $pilihan[$opsi] = $isi;
                    $tanda = trim(self::teks($xp, $kolom[2]));
                    if ($tanda !== '' && $tanda !== '✓') {
                        throw new DomainException('Soal ' . ($i + 1) . ': kolom Benar hanya boleh ✓.');
                    }
                    if ($tanda === '✓') {
                        $benar[] = $opsi;
                    }
                }
                if (count($benar) !== 1) {
                    throw new DomainException('Soal ' . ($i + 1) . ' harus memiliki tepat satu tanda ✓.');
                }
                $hasil[] = ['nomor'=>$i + 1, 'pertanyaan'=>$pertanyaan,'pilihan'=>$pilihan,'kunci'=>$benar[0]];
            }
            // Konten kaya belum direkonstruksi untuk mesin ujian; jangan lakukan konfirmasi impor.
            return ['jumlah'=>$jumlah,'soal'=>$hasil,'kaya'=>$kaya,'siap_impor'=>false];
        } finally {
            $zip->close();
        }
    }

    private static function xml(string $isi): DOMDocument
    {
        $doc = new DOMDocument();
        if (strlen($isi) > 12 * 1024 * 1024 || ! $doc->loadXML($isi, LIBXML_NONET)) {
            throw new DomainException('XML dalam DOCX tidak valid atau terlalu besar.');
        }
        return $doc;
    }

    /** @return list<\DOMNode> */
    private static function cells(DOMXPath $xp, \DOMNode $row): array
    {
        $nodes = $xp->query('./w:tc', $row);
        $out = [];
        foreach ($nodes as $node) {
            $out[] = $node;
        }
        return $out;
    }

    private static function teks(DOMXPath $xp, \DOMNode $cell): string
    {
        $out = [];
        foreach ($xp->query('.//w:t | .//m:t', $cell) as $node) {
            $out[] = $node->textContent;
        }
        return implode(' ', $out);
    }
}
