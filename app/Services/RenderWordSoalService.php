<?php
declare(strict_types=1);

namespace App\Services;

use DOMNode;
use DOMXPath;
use DomainException;
use ZipArchive;

/**
 * Konversi terbatas OOXML menjadi HTML tepercaya; tidak membaca HTML dari dokumen.
 * Hanya img PNG/JPEG dan struktur paragraf/tabel yang diizinkan.
 */
final class RenderWordSoalService
{
    public static function hasil(string $path, array $hasil, ?callable $imageSrc = null): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new DomainException('Dokumen Word tidak dapat dibuka.');
        }
        try {
            $source = $zip->getFromName('word/document.xml');
            if (!is_string($source)) {
                throw new DomainException('Isi Word tidak tersedia.');
            }
            $doc = new \DOMDocument();
            if (! $doc->loadXML($source, LIBXML_NONET)) {
                throw new DomainException('XML Word tidak valid.');
            }
            $xp = new DOMXPath($doc);
            $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $rows = $xp->query('//w:body/w:tbl[1]/w:tr');
            $relations = self::relations($zip);
            $totalBytes = 0;
            foreach ($hasil['soal'] as $i => &$soal) {
                $soal['html_pertanyaan'] = '';
                $soal['html_opsi'] = [];
                for ($j = 0; $j < 6; $j++) {
                    $cells = $xp->query('./w:tc', $rows->item(1 + $i * 6 + $j));
                    $html = self::children($xp, $zip, $relations, $cells->item(1), $totalBytes, $imageSrc);
                    if ($j === 0) {
                        $soal['html_pertanyaan'] = $html;
                    } else {
                        $soal['html_opsi'][chr(64 + $j)] = $html;
                    }
                }
            }
            unset($soal);
            return $hasil;
        } finally {
            $zip->close();
        }
    }

    private static function relations(ZipArchive $zip): array
    {
        $content = $zip->getFromName('word/_rels/document.xml.rels');
        if (!is_string($content)) {
            return [];
        }
        $doc = new \DOMDocument();
        if (! $doc->loadXML($content, LIBXML_NONET)) {
            return [];
        }
        $result = [];
        foreach ($doc->getElementsByTagName('Relationship') as $rel) {
            if ($rel->getAttribute('TargetMode') === 'External') {
                continue;
            }
            $target = $rel->getAttribute('Target');
            if (preg_match('~^media/[A-Za-z0-9_.-]+$~D', $target)) {
                $result[$rel->getAttribute('Id')] = 'word/' . $target;
            }
        }
        return $result;
    }

    private static function children(DOMXPath $xp, ZipArchive $zip, array $relations, DOMNode $node, int &$bytes, ?callable $imageSrc = null): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            switch ($child->localName) {
                case 'p':
                    $html .= '<p>' . self::inline($xp, $zip, $relations, $child, $bytes, $imageSrc) . '</p>';
                    break;
                case 'tbl':
                    $html .= '<div class="a-tabel-gulir"><table class="a-tbl a-word-table"><tbody>';
                    foreach ($xp->query('./w:tr', $child) as $row) {
                        $html .= '<tr>';
                        foreach ($xp->query('./w:tc', $row) as $cell) {
                            $html .= '<td>' . self::children($xp, $zip, $relations, $cell, $bytes, $imageSrc) . '</td>';
                        }
                        $html .= '</tr>';
                    }
                    $html .= '</tbody></table></div>';
                    break;
            }
        }
        return $html;
    }

    private static function inline(DOMXPath $xp, ZipArchive $zip, array $relations, DOMNode $node, int &$bytes, ?callable $imageSrc = null): string
    {
        $html = '';
        foreach ($node->childNodes as $part) {
            if ($part->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            if ($part->localName === 't') {
                $html .= htmlspecialchars($part->textContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } elseif ($part->localName === 'drawing') {
                foreach ($xp->query('.//*[local-name()="blip"]', $part) as $blip) {
                    $rid = $blip->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
                    $path = $relations[$rid] ?? null;
                    if ($path === null) {
                        $html .= '<span>[Gambar tidak tersedia]</span>';
                        continue;
                    }
                    $info = $zip->statName($path);
                    $size = (int) ($info['size'] ?? 0);
                    if ($size < 1 || $size > 2 * 1024 * 1024 || $bytes + $size > 8 * 1024 * 1024) {
                        throw new DomainException('Ukuran gambar Word melebihi batas pratinjau.');
                    }
                    $raw = $zip->getFromName($path);
                    if (!is_string($raw) || !in_array((new \finfo(FILEINFO_MIME_TYPE))->buffer($raw), ['image/png', 'image/jpeg'], true)) {
                        throw new DomainException('Gambar Word harus PNG atau JPEG.');
                    }
                    $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($raw);
                    $bytes += strlen($raw);
                    $src = $imageSrc !== null ? $imageSrc($rid) : ('data:' . $mime . ';base64,' . base64_encode($raw));
                    $html .= '<img class="a-word-image" alt="Ilustrasi soal" src="' .
                        htmlspecialchars((string) $src, ENT_QUOTES, 'UTF-8') . '">';
                }
            } elseif ($part->localName === 'oMath') {
                $html .= '<span class="a-word-math" title="Equation Word">' .
                    htmlspecialchars(trim($xp->evaluate('string(.//*[local-name()="t"])', $part)), ENT_QUOTES, 'UTF-8') . '</span>';
            } else {
                $html .= self::inline($xp, $zip, $relations, $part, $bytes, $imageSrc);
            }
        }
        return $html;
    }
    /** @return array{data:string,mime:string} */
    public static function gambar(string $path, string $rid): array
    {
        if (!preg_match('/^rId[0-9]+$/D', $rid)) {
            throw new DomainException('ID gambar tidak valid.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new DomainException('Berkas Word tidak tersedia.');
        }
        try {
            $relasi = self::relations($zip);
            $lokasi = $relasi[$rid] ?? null;
            if ($lokasi === null) {
                throw new DomainException('Gambar tidak ditemukan.');
            }
            $stat = $zip->statName($lokasi);
            if ((int) ($stat['size'] ?? 0) > 2 * 1024 * 1024) {
                throw new DomainException('Gambar melebihi 2 MB.');
            }
            $bin = $zip->getFromName($lokasi);
            if (!is_string($bin) || $bin === '') {
                throw new DomainException('Gambar tidak dapat dibaca.');
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bin);
            if (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
                throw new DomainException('Format gambar tidak didukung.');
            }
            return ['data' => $bin, 'mime' => $mime];
        } finally {
            $zip->close();
        }
    }

}
