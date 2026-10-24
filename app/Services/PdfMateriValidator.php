<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * PDF konservatif untuk hosting PHP tanpa binary/extension tambahan.
 * Hanya PDF klasik dengan tabel xref dan objek eksplisit diterima;
 * PDF terkompresi ObjStm, revisi inkremental, aksi aktif, dan formulir ditolak.
 * OpenAction tujuan tampilan halaman yang tervalidasi bukan aksi aktif.
 * Fail-closed: format yang tidak bisa diaudit tidak boleh dipublikasikan.
 */
final class PdfMateriValidator
{
    public static function periksa(string $path): int
    {
        $size = @filesize($path);
        if ($size === false || $size < 100 || $size > 15 * 1024 * 1024) {
            throw new DomainException('PDF maksimal 15 MB dan tidak boleh kosong.');
        }
        $pdf = @file_get_contents($path);
        if (! is_string($pdf) || ! preg_match('/^%PDF-1\.[0-7](?:\r\n|\n|\r)/', $pdf)) {
            throw new DomainException('Header PDF tidak dikenali.');
        }
        if (substr_count($pdf, '%%EOF') !== 1 || substr_count($pdf, 'startxref') !== 1) {
            throw new DomainException('PDF dengan revisi inkremental atau penutup tidak jelas ditolak.');
        }
        if (! preg_match('/startxref\s+(\d+)\s+%%EOF\s*$/D', $pdf, $matches)) {
            throw new DomainException('Struktur akhir PDF tidak valid.');
        }
        $xref = (int) $matches[1];
        if ($xref < 1 || $xref >= $size || substr($pdf, $xref, 4) !== 'xref') {
            throw new DomainException('Tabel indeks PDF tidak valid atau format PDF belum didukung.');
        }
        // PDF buatan jsPDF/Word dapat memuat /OpenAction berupa tujuan tampilan
        // halaman saat dibuka (mis. [3 0 R /FitH null]). Ini BUKAN skrip.
        // Hanya whitelist yang menunjuk langsung ke objek /Page diterima;
        // dictionary action, JavaScript, URL dan instruksi lain tetap ditolak.
        $openActionPage = null;
        $untukPemeriksaan = $pdf;
        if (preg_match_all('~/OpenAction\\b~', $pdf, $openActions) > 0) {
            if (count($openActions[0]) !== 1
                || preg_match(
                    '~/OpenAction\\s*\\[\\s*(\\d+)\\s+(\\d+)\\s+R\\s+/(FitH|FitV|FitBH|FitBV|Fit|FitB)(?=\\s|\\])(?:(\\s+)null)?\\s*\\]~',
                    $pdf,
                    $destinasi,
                    PREG_OFFSET_CAPTURE
                ) !== 1) {
                throw new DomainException('PDF menggunakan aksi saat dibuka yang tidak didukung.');
            }
            $mode = $destinasi[3][0];
            $memakaiNull = isset($destinasi[4]) && $destinasi[4][1] >= 0;
            if ($memakaiNull !== in_array($mode, ['FitH', 'FitV', 'FitBH', 'FitBV'], true)) {
                throw new DomainException('Tujuan tampilan awal PDF tidak valid.');
            }
            $openActionPage = $destinasi[1][0] . ':' . $destinasi[2][0];
            $offset = $destinasi[0][1];
            // Hilangkan hanya token aman dari pemindaian larangan aksi.
            // File asli TIDAK diubah; disimpan utuh setelah pemeriksaan.
            $untukPemeriksaan = substr_replace(
                $pdf,
                str_repeat(' ', strlen($destinasi[0][0])),
                $offset,
                strlen($destinasi[0][0])
            );
        }

        // Semua struktur di luar stream diperiksa sebagai objek eksplisit.
        // Bentuk ini dengan sengaja menolak ObjStm/XRef stream dan filter ambigu.
        if (preg_match('~/\\s*(?:ObjStm|XRef|Encrypt|Prev|JavaScript|JS|OpenAction|AA|Launch|EmbeddedFile|Filespec|AcroForm|XFA|RichMedia|Movie|Sound|Rendition|GoToR|SubmitForm|ImportData|URI|Annots|Names|Collection|Perms|3D)\\b~i', $untukPemeriksaan)
            || preg_match('~/[A-Za-z0-9]*\\#[0-9a-fA-F]{2}~', $untukPemeriksaan)
            || preg_match('~/Filter\\s*\\[(?!\\s*/FlateDecode\\s*\\])~', $untukPemeriksaan)
            || str_contains($untukPemeriksaan, '/ObjStm')
            || str_contains($untukPemeriksaan, '/XRef')) {
            throw new DomainException('PDF mengandung fitur aktif atau struktur yang belum diizinkan.');
        }
        $tanpaStream = preg_replace('/\bstream\r?\n.*?\r?\nendstream\b/s', '\n', $pdf);
        if (! is_string($tanpaStream)) {
            throw new DomainException('Isi PDF tidak dapat diperiksa.');
        }
        preg_match_all('/(?:^|\r?\n)(\d+)\s+(\d+)\s+obj\b(.*?)\bendobj\b/s', $tanpaStream, $objects, PREG_SET_ORDER);
        if (count($objects) < 3 || count($objects) > 10000) {
            throw new DomainException('Struktur objek PDF tidak dikenali.');
        }

        $halaman = 0;
        $jumlahRoot = false;
        $ids = [];
        $halamanIds = [];
        foreach ($objects as $o) {
            $id = $o[1] . ':' . $o[2];
            if (isset($ids[$id])) {
                throw new DomainException('Objek PDF memiliki versi ganda.');
            }
            $ids[$id] = true;
            $dict = $o[3];
            if (preg_match('~/Type\s*/Page(?![A-Za-z])~', $dict)) {
                if (! preg_match('~/Parent\s+\d+\s+\d+\s+R\b~', $dict)) {
                    throw new DomainException('Halaman PDF tidak memiliki induk.');
                }
                ++$halaman;
                $halamanIds[$id] = true;
            } elseif (preg_match('~/Type\s*/Pages\b~', $dict)
                && preg_match('~/Count\s+(\d+)\b~', $dict, $cnt)) {
                $jumlahRoot = $jumlahRoot || (int) $cnt[1] === $halaman;
                // Verified separately after full scan.
                $counts[] = (int) $cnt[1];
            }
        }
        if ($openActionPage !== null && ! isset($halamanIds[$openActionPage])) {
            throw new DomainException('Tujuan tampilan awal PDF tidak mengarah pada halaman dokumen.');
        }
        $counts = $counts ?? [];
        if ($halaman < 1 || $halaman > 40 || ! in_array($halaman, $counts, true)) {
            throw new DomainException('PDF harus berisi 1–40 halaman dengan daftar halaman konsisten.');
        }
        if (! preg_match('~/Root\s+\d+\s+\d+\s+R\b~', substr($pdf, $xref))) {
            throw new DomainException('PDF tidak memiliki katalog dokumen yang valid.');
        }
        return $halaman;
    }
}
