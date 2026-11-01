<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * PDF Materi: hanya batas ukuran (15 MiB) dan jumlah halaman (6–40).
 * Format internal, metadata, navigasi, revisi, dan ObjStm bukan syarat
 * penerimaan tambahan. Identitas jenis PDF diperiksa BerkasMateriService.
 */
final class PdfMateriValidator
{
    public static function periksa(string $path): int
    {
        $size = @filesize($path);
        if ($size === false || $size === 0 || $size > 15 * 1024 * 1024) {
            throw new DomainException('PDF Materi maksimal 15 MB dan tidak boleh kosong.');
        }
        $pdf = @file_get_contents($path);
        if (! is_string($pdf)) {
            throw new DomainException('Berkas PDF tidak dapat dibaca.');
        }

        $objects = [];
        preg_match_all('/(?:^|[\r\n])\s*(\d+)\s+(\d+)\s+obj\b(.*?)\bendobj\b/s',
            $pdf, $allObjects, PREG_SET_ORDER);
        foreach ($allObjects as $obj) {
            // Revisi terakhir menggantikan objek lama dengan nomor sama.
            $objects[$obj[1] . ':' . $obj[2]] = $obj[3];
        }

        // ObjStm hanya didekompresi jika perlu membaca jumlah halaman.
        foreach ($objects as $body) {
            if (! preg_match('~/Type\s*/ObjStm\b~', $body)
                || ! preg_match('~/Filter\s*/FlateDecode\b~', $body)
                || ! preg_match('~/N\s+(\d+)\b~', $body, $n)
                || ! preg_match('~/First\s+(\d+)\b~', $body, $first)
                || ! preg_match('/\bstream\r?\n/', $body, $start, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $count = (int) $n[1];
            $firstByte = (int) $first[1];
            if ($count < 1 || $count > 10000 || $firstByte < 0) {
                continue;
            }
            $startByte = $start[0][1] + strlen($start[0][0]);
            $endByte = strpos($body, 'endstream', $startByte);
            if ($endByte === false) {
                continue;
            }
            $compressed = substr($body, $startByte, $endByte - $startByte);
            if (preg_match('~/Length\s+(\d+)\b~', $body, $length)) {
                $compressed = substr($compressed, 0, (int) $length[1]);
            } else {
                $compressed = rtrim($compressed, "\r\n");
            }
            $plain = @gzuncompress($compressed, 8 * 1024 * 1024);
            if (! is_string($plain) || $firstByte >= strlen($plain)) {
                continue;
            }
            preg_match_all('/(\d+)\s+(\d+)/', substr($plain, 0, $firstByte), $index, PREG_SET_ORDER);
            if (count($index) !== $count) {
                continue;
            }
            foreach ($index as $i => $entry) {
                $offset = $firstByte + (int) $entry[2];
                $end = isset($index[$i + 1])
                    ? $firstByte + (int) $index[$i + 1][2]
                    : strlen($plain);
                if ($offset < $firstByte || $end <= $offset || $end > strlen($plain)) {
                    continue;
                }
                $key = $entry[1] . ':0';
                // Objek eksplisit dari revisi lebih baru tetap diprioritaskan.
                if (! isset($objects[$key])) {
                    $objects[$key] = substr($plain, $offset, $end - $offset);
                }
            }
        }

        $jumlah = null;
        preg_match_all('~/Root\s+(\d+)\s+(\d+)\s+R\b~', $pdf, $roots, PREG_SET_ORDER);
        if ($roots !== []) {
            $root = end($roots);
            $katalog = $objects[$root[1] . ':' . $root[2]] ?? '';
            if (preg_match('~/Pages\s+(\d+)\s+(\d+)\s+R\b~', $katalog, $pageRef)) {
                $pages = $objects[$pageRef[1] . ':' . $pageRef[2]] ?? '';
                if (preg_match('~/Type\s*/Pages\b~', $pages)
                    && preg_match('~/Count\s+(\d+)\b~', $pages, $found)) {
                    $jumlah = (int) $found[1];
                }
            }
        }

        // Fallback: node Pages terbesar adalah akar hitungan halaman,
        // bukan penjumlahan semua node bertingkat.
        if ($jumlah === null) {
            foreach ($objects as $body) {
                $dictionary = explode('stream', $body, 2)[0];
                if (preg_match('~/Type\s*/Pages\b~', $dictionary)
                    && preg_match('~/Count\s+(\d+)\b~', $dictionary, $found)) {
                    $jumlah = max($jumlah ?? 0, (int) $found[1]);
                }
            }
        }
        if ($jumlah === null) {
            throw new DomainException('Jumlah halaman PDF tidak dapat dibaca.');
        }
        if ($jumlah < 6 || $jumlah > 40) {
            throw new DomainException('PDF Materi harus memiliki 6–40 halaman, maksimal 15 MB.');
        }
        return $jumlah;
    }
}
