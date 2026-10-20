<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;
use ZipArchive;

/**
 * Parser terisolasi. Tidak mengeksekusi rumus, tidak mengevaluasi nilai sel, tidak memindah
 * unggahan ke public atau writable. Data sandi awal hanya berada di memori request.
 */
final class AkunImporXlsxParser
{
    public const MAKS_BARIS = 300;
    public const MAKS_BYTE = 1048576;
    public const MAKS_ZIP_UNCOMPRESSED = 8388608;

    public static function peranDariTeks(string $raw): array
    {
        $parts = array_map('trim', explode('+', mb_strtolower($raw, 'UTF-8')));
        if ($parts === [] || count($parts) !== count(array_unique($parts))) {
            return [];
        }
        sort($parts);
        return AuthService::peranValid($parts) ? $parts : [];
    }

    public static function semesterDariTeks(string $raw): int|null
    {
        if ($raw === '') {
            return null;
        }
        return preg_match('/^[1-6]$/D', $raw) === 1 ? (int) $raw : -1;
    }

    /**
     * @return list<array{nomor:int,username:string,nama:string,peran:array,sandi:string,kode_gen:string,semester:?int,galat:string}>
     */
    public static function parse(UploadedFile $file, string $jenis): array
    {
        if (! PenggunaAdminService::validasiJenis($jenis)) {
            throw new DomainException('Jenis impor tidak valid.');
        }
        if (! $file->isValid()) {
            throw new DomainException('Unggahan tidak berhasil. Periksa ukuran dan pilih file XLSX lagi.');
        }

        $ukuran = $file->getSize();
        if (! is_int($ukuran) || $ukuran < 100 || $ukuran > self::MAKS_BYTE) {
            throw new DomainException('Ukuran XLSX harus maksimal 1 MB.');
        }
        if (strtolower((string) $file->getClientExtension()) !== 'xlsx') {
            throw new DomainException('File harus berekstensi .xlsx (bukan .xls atau CSV).');
        }

        $tmp = $file->getTempName();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (! in_array($mime, [
            'application/zip',
            'application/octet-stream',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ], true)) {
            throw new DomainException('Isi file bukan XLSX yang didukung.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            throw new DomainException('Berkas XLSX rusak atau tidak dapat dibaca.');
        }
        try {
            if ($zip->numFiles > 100 || $zip->locateName('xl/workbook.xml') === false) {
                throw new DomainException('Struktur file XLSX tidak sesuai template SLA-SKS.');
            }
            $ukuranTerbuka = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $meta = $zip->statIndex($i);
                if ($meta === false) {
                    throw new DomainException('Struktur ZIP tidak dapat diperiksa.');
                }
                $ukuranTerbuka += (int) $meta['size'];
                if ($ukuranTerbuka > self::MAKS_ZIP_UNCOMPRESSED) {
                    throw new DomainException('XLSX terlalu besar setelah diekstraksi.');
                }
            }
        } finally {
            $zip->close();
        }

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        try {
            $workbook = $reader->load($tmp);
        } catch (Throwable $e) {
            throw new DomainException('XLSX tidak dapat dibaca. Gunakan template terbaru.');
        }
        try {
            return self::bacaBuku($workbook, $jenis);
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    /** @return list<array{nomor:int,username:string,nama:string,peran:array,sandi:string,kode_gen:string,semester:?int,galat:string}> */
    public static function bacaBuku(Spreadsheet $buku, string $jenis): array
    {
        if (! PenggunaAdminService::validasiJenis($jenis)) {
            throw new DomainException('Jenis impor tidak dikenal.');
        }

        $namaSheet = ucfirst($jenis);
        if ($buku->getSheetCount() !== 2 || $buku->getSheetByName($namaSheet) === null
            || $buku->getSheetByName('Petunjuk') === null) {
            throw new DomainException('Sheet tidak sesuai. Gunakan template ' . $namaSheet . ' terbaru.');
        }
        $petunjuk = (string) $buku->getSheetByName('Petunjuk')->getCell('A1')->getValue();
        if (! str_contains($petunjuk, AkunXlsxService::VERSI_TEMPLATE)) {
            throw new DomainException('Versi template salah. Unduh template AKUN-V1 dari aplikasi.');
        }

        $sheet = $buku->getSheetByName($namaSheet);
        $header = AkunXlsxService::kolom($jenis, true);
        $jumlahKolom = count($header);
        if (\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()) > $jumlahKolom) {
            throw new DomainException('Terdapat kolom tambahan di luar template.');
        }

        foreach ($header as $c => $judul) {
            if ((string) $sheet->getCell([$c + 1, 1])->getValue() !== $judul) {
                throw new DomainException('Header kolom ' . ($c + 1) . ' harus "' . $judul . '".');
            }
        }

        $terakhir = $sheet->getHighestDataRow();
        if ($terakhir > self::MAKS_BARIS + 1) {
            throw new DomainException('Maksimal 300 baris data per file. Pisahkan impor menjadi beberapa berkas.');
        }

        $rows = [];
        for ($r = 2; $r <= $terakhir; $r++) {
            $values = [];
            $formula = false;
            foreach ($header as $i => $col) {
                $cell = $sheet->getCell([$i + 1, $r]);
                if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                    $formula = true;
                }
                $val = $cell->getValue();
                $values[$col] = is_scalar($val) ? trim((string) $val) : '';
            }
            if (count(array_filter($values, static fn ($v) => $v !== '')) === 0) {
                continue;
            }

            $username = $jenis === 'siswa' ? $values['nisn'] : $values['username'];
            $peran = $jenis === 'siswa' ? ['siswa'] : self::peranDariTeks($values['peran']);
            $nama = $values['nama'];
            $galat = $formula ? 'Sel formula tidak diperbolehkan; isi sebagai teks biasa.' : '';

            if ($galat === '' && ! MutasiPenggunaService::identitasValid($jenis, $username, $nama, $peran)) {
                $galat = 'Periksa ' . ($jenis === 'siswa' ? 'NISN (10 angka)' : 'username/peran')
                    . ' dan nama (3–150 karakter).';
            }

            $semester = $jenis === 'siswa' ? self::semesterDariTeks($values['semester_mulai']) : null;
            if ($galat === '' && $semester === -1) {
                $galat = 'Semester mulai harus kosong atau angka 1–6.';
            }

            $rows[] = [
                'nomor' => $r,
                'username' => mb_substr($username, 0, 80),
                'nama' => mb_substr($nama, 0, 150),
                'peran' => $peran,
                'sandi' => $values['sandi_awal'],
                'kode_gen' => $jenis === 'siswa' ? $values['kode_gen'] : '',
                'semester' => $semester,
                'galat' => $galat,
            ];
        }

        if ($rows === []) {
            throw new DomainException('File kosong. Tambahkan minimal satu baris akun.');
        }

        return $rows;
    }
}
