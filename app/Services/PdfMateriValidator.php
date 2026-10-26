<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * PDF bahan belajar: batas biaya penyimpanan 15 MiB / 40 halaman.
 *
 * Pemeriksaan keamanan hanya menolak aksi aktif, enkripsi dan file yang
 * tidak dapat dibaca. Revisi inkremental, navigasi halaman, hyperlink,
 * anotasi, metadata Content Credentials serta filter gambar yang normal
 * bukan alasan penolakan.
 *
 * Parser terbatas ke pohon halaman yang tercatat sebagai objek eksplisit.
 * Objek terkompresi (ObjStm) tidak dilonggarkan tanpa parser yang aman.
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
        if (! is_string($pdf) || ! preg_match('/^%PDF-(?:1\.[0-7]|2\.0)(?:\r\n|\r|\n)/', $pdf)) {
            throw new DomainException('File bukan PDF yang dapat dibaca.');
        }

        // Baca trailer TERAKHIR; EOF sebelumnya dapat berasal dari revisi sah.
        if (preg_match('/(?:\A|[\r\n])startxref[ \t]*[\r\n]+(\d+)[ \t]*[\r\n]+%%EOF[ \t]*(?:\r?\n)?\z/D', $pdf, $akhir) !== 1) {
            throw new DomainException('PDF tidak lengkap atau rusak. Pilih file PDF yang dapat dibuka.');
        }
        $xref = (int) $akhir[1];
        if ($xref < 1 || $xref >= $size) {
            throw new DomainException('PDF tidak lengkap atau rusak.');
        }
        if (substr($pdf, $xref, 4) !== 'xref'
            && ! preg_match('/^\d+\s+\d+\s+obj\b.{0,500}\/Type\s*\/XRef\b/s', substr($pdf, $xref, 700))) {
            throw new DomainException('Struktur file PDF tidak dapat dibaca.');
        }

        // Jangan lakukan penyisiran aksi pada byte stream gambar/audio
        // yang bisa berisi token seperti nama PDF secara kebetulan.
        $tanpaStream = preg_replace('/\bstream\r?\n.*?\r?\nendstream\b/s', ' ', $pdf);
        if (! is_string($tanpaStream)) {
            throw new DomainException('File PDF tidak dapat dibaca.');
        }
        $namaTerurai = preg_replace_callback(
            '~/(?:[^\x00-\x20<>\[\](){}%/])+~',
            static function (array $m): string {
                return (string) preg_replace_callback('/#([0-9a-fA-F]{2})/',
                    static fn (array $h): string => chr(hexdec($h[1])), $m[0]);
            },
            $tanpaStream
        );
        if (! is_string($namaTerurai)) {
            throw new DomainException('File PDF tidak dapat dibaca.');
        }

        // Enkripsi/skrip dan instruksi aksi aktif tetap tidak diizinkan.
        // URI, bookmark, anotasi, metadata C2PA dan revisi biasa diperbolehkan.
        if (preg_match('~/(?:Encrypt|JavaScript|JS|AA|Launch|SubmitForm|ImportData|GoToR|RichMedia|Rendition|XFA)\b~i', $namaTerurai)) {
            throw new DomainException('PDF memuat skrip atau aksi otomatis yang tidak diizinkan.');
        }
        if (preg_match('~/ObjStm\b~', $namaTerurai)) {
            // Tanpa decoder ObjStm, aksi tersembunyi tak dapat diaudit.
            throw new DomainException('Halaman PDF tidak dapat diperiksa. Gunakan PDF biasa yang dapat dibaca.');
        }

        preg_match_all('/(?:^|[\r\n])\s*(\d+)\s+(\d+)\s+obj\b(.*?)\bendobj\b/s',
            $tanpaStream, $hasil, PREG_SET_ORDER);
        if (count($hasil) < 3 || count($hasil) > 20000) {
            throw new DomainException('Struktur halaman PDF tidak dapat diperiksa.');
        }
        $objects = [];
        foreach ($hasil as $obj) {
            // Revisi inkremental sah dapat memperbarui objek yang sama.
            // Objek pada revisi terakhir adalah versi yang berlaku.
            $objects[$obj[1] . ':' . $obj[2]] = $obj[3];
        }
        preg_match_all('~/Root\s+(\d+)\s+(\d+)\s+R\b~', $namaTerurai, $roots, PREG_SET_ORDER);
        if ($roots === []) {
            throw new DomainException('PDF tidak memiliki katalog halaman yang dapat dibaca.');
        }
        $root = end($roots);
        $idKatalog = $root[1] . ':' . $root[2];
        $katalog = $objects[$idKatalog] ?? '';
        if (! preg_match('~/Type\s*/Catalog\b~', $katalog)
            || ! preg_match('~/Pages\s+(\d+)\s+(\d+)\s+R\b~', $katalog, $pages)) {
            throw new DomainException('PDF tidak memiliki katalog halaman yang dapat dibaca.');
        }

        $sudahDikunjungi = [];
        $halamanDitemukan = [];
        $jumlah = self::hitungHalaman($pages[1] . ':' . $pages[2], $objects,
            $sudahDikunjungi, $halamanDitemukan, 0);
        if ($jumlah < 1 || $jumlah > 40) {
            throw new DomainException('PDF harus berisi 1–40 halaman.');
        }

        // OpenAction berbentuk tujuan tampilan saja (Fit / XYZ) tidak
        // menjalankan kode. Dictionary tindakan tetap ditolak.
        preg_match_all('~/OpenAction\b~', $namaTerurai, $semuaOpen);
        preg_match_all(
            '~(/OpenAction\s*\[\s*(\d+)\s+(\d+)\s+R\s+/(FitBH|FitBV|FitH|FitV|FitB|Fit|XYZ)\b([^\]]*)\])~',
            $namaTerurai, $tujuan, PREG_SET_ORDER
        );
        if (count($semuaOpen[0]) !== count($tujuan)) {
            throw new DomainException('PDF memuat aksi otomatis yang tidak diizinkan.');
        }
        foreach ($tujuan as $t) {
            $referensi = $t[2] . ':' . $t[3];
            $argumen = trim($t[5]);
            $arg = $argumen === '' ? [] : preg_split('/\s+/', $argumen);
            $angkaAtauNull = static fn (string $x): bool =>
                $x === 'null' || preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/D', $x) === 1;
            $jumlahArg = match ($t[4]) {
                'Fit', 'FitB' => 0,
                'XYZ' => 3,
                default => 1,
            };
            if (! isset($halamanDitemukan[$referensi])
                || count($arg) !== $jumlahArg
                || array_filter($arg, static fn ($x): bool => ! $angkaAtauNull($x)) !== []) {
                throw new DomainException('Navigasi awal PDF tidak dapat dibaca.');
            }
        }
        return $jumlah;
    }

    /**
     * Hitung LEAF halaman berdasarkan /Kids aktif, bukan jumlah marker
     * biner atau Count yang bisa saja keliru pada revisi lama.
     *
     * @param array<string,string> $objects
     * @param array<string,bool> $visited
     * @param array<string,bool> $pages
     */
    private static function hitungHalaman(
        string $ref,
        array $objects,
        array &$visited,
        array &$pages,
        int $depth
    ): int {
        if ($depth > 32 || isset($visited[$ref]) || ! isset($objects[$ref])) {
            throw new DomainException('Struktur halaman PDF tidak dapat diperiksa.');
        }
        $visited[$ref] = true;
        $body = $objects[$ref];
        if (preg_match('~/Type\s*/Page(?![A-Za-z])~', $body)) {
            if (! preg_match('~/Parent\s+\d+\s+\d+\s+R\b~', $body)) {
                throw new DomainException('Struktur halaman PDF tidak lengkap.');
            }
            $pages[$ref] = true;
            return 1;
        }
        if (! preg_match('~/Type\s*/Pages\b~', $body)
            || ! preg_match('~/Kids\s*\[(.*?)\]~s', $body, $kids)) {
            throw new DomainException('Daftar halaman PDF tidak dapat dibaca.');
        }
        preg_match_all('/(\d+)\s+(\d+)\s+R\b/', $kids[1], $children, PREG_SET_ORDER);
        if ($children === [] || count($children) > 10000) {
            throw new DomainException('Daftar halaman PDF tidak valid.');
        }
        $total = 0;
        foreach ($children as $child) {
            $total += self::hitungHalaman($child[1] . ':' . $child[2],
                $objects, $visited, $pages, $depth + 1);
            if ($total > 40) {
                throw new DomainException('PDF melebihi batas 40 halaman.');
            }
        }
        if (preg_match('~/Count\s+(\d+)\b~', $body, $jumlahTercatat)
            && (int) $jumlahTercatat[1] !== $total) {
            throw new DomainException('Jumlah halaman PDF tidak konsisten.');
        }
        return $total;
    }
}
