<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use ZipArchive;

/**
 * Parser ATP manual v2.5: murni baca, tanpa query maupun mutasi database.
 * Semua enam semester diproses; CONTOH MAT VII sama sekali tidak menjadi data.
 */
final class AtpExcelParserService
{
    private const SHEETS = [
        'MULAI DI SINI', 'RINGKASAN', 'REFERENSI CP',
        'SEMESTER 1', 'SEMESTER 2', 'SEMESTER 3',
        'SEMESTER 4', 'SEMESTER 5', 'SEMESTER 6', 'CONTOH MAT VII',
    ];
    private const HEADER_BAB = ['Nomor BAB', 'Nama BAB (maks. 50)', 'Deskripsi BAB (maks. 225)'];
    private const HEADER_TP = AtpTemplateLayoutService::HEADER_TP;

    /** Periksa ZIP sebelum PhpSpreadsheet memproses XML. */
    private static function periksaZip(string $path): void
    {
        if (! is_file($path) || filesize($path) === false || filesize($path) > 5 * 1024 * 1024) {
            throw new DomainException('File ATP harus XLSX berukuran paling besar 5 MB.');
        }
        $head = file_get_contents($path, false, null, 0, 4);
        if ($head === false || substr($head, 0, 2) !== 'PK') {
            throw new DomainException('Berkas bukan XLSX yang sah.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new DomainException('Struktur XLSX tidak dapat dibaca.');
        }
        try {
            if ($zip->numFiles < 6 || $zip->numFiles > 300) {
                throw new DomainException('Jumlah berkas di dalam XLSX tidak wajar.');
            }
            $total = 0;
            $nama = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $info = $zip->statIndex($i);
                $file = (string) ($info['name'] ?? '');
                $key = strtolower(str_replace('\\', '/', $file));
                if ($file === '' || str_contains($key, '../') || str_starts_with($key, '/')
                    || isset($nama[$key])) {
                    throw new DomainException('Struktur berkas XLSX tidak aman.');
                }
                $nama[$key] = true;
                $total += (int) ($info['size'] ?? 0);
                if ($total > 25 * 1024 * 1024 || (int) ($info['size'] ?? 0) > 12 * 1024 * 1024) {
                    throw new DomainException('Isi XLSX terlalu besar untuk diproses.');
                }
                foreach (['vba', 'externallinks', 'connections', 'querytables', 'activex',
                          'embeddings/', 'customxml/', 'webextensions/'] as $dilarang) {
                    if (str_contains($key, $dilarang)) {
                        throw new DomainException('File ATP tidak boleh berisi macro atau koneksi eksternal.');
                    }
                }
            }
            if (! isset($nama['xl/workbook.xml']) || ! isset($nama['[content_types].xml'])) {
                throw new DomainException('Struktur workbook XLSX tidak lengkap.');
            }
        } finally {
            $zip->close();
        }
    }

    private static function nilai(Worksheet $sheet, string $alamat): string
    {
        $sel = $sheet->getCell($alamat);
        if ($sel->getDataType() === DataType::TYPE_FORMULA) {
            throw new DomainException('Rumus Excel tidak diizinkan: ' . $sheet->getTitle() . '!' . $alamat . '.');
        }
        return trim((string) ($sel->getValue() ?? ''));
    }

    private static function nomor(string $teks, string $lokasi): int
    {
        if (! preg_match('/\A[1-9][0-9]{0,3}\z/D', $teks)) {
            throw new DomainException($lokasi . ': nomor wajib bilangan bulat positif.');
        }
        return (int) $teks;
    }

    /**
     * @param list<string> $elemenDiizinkan Nama elemen CP aktif dari SERVER, bukan metadata file.
     * @return array{bab:list<array<string,mixed>>,tp:list<array<string,mixed>>,jumlah_bab:int,jumlah_tp:int}
     */
    public static function baca(string $path, string $kodeMapel, int $semesterMulai,
        string $kodeGen, array $elemenDiizinkan = [], int $cpId = 0,
        array $referensiCpAktif = []): array
    {
        if ($semesterMulai < 1 || $semesterMulai > 6 || $kodeGen === '') {
            throw new DomainException('Konteks Gen tidak valid.');
        }
        self::periksaZip($path);
        try {
            $buku = IOFactory::load($path);
        } catch (\Throwable $e) {
            throw new DomainException('File Excel ATP gagal dibaca. Gunakan template resmi .xlsx.', 0, $e);
        }
        try {
            return self::bacaBuku($buku, $kodeMapel, $semesterMulai,
                $kodeGen, $elemenDiizinkan, $cpId, $referensiCpAktif);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    /**
     * Metode terpisah untuk pengujian workbook sintetis tanpa IO dan tanpa DB.
     *
     * @param list<string> $elemenDiizinkan
     * @return array{bab:list<array<string,mixed>>,tp:list<array<string,mixed>>,jumlah_bab:int,jumlah_tp:int}
     */
    public static function bacaBuku(Spreadsheet $buku, string $kodeMapel, int $semesterMulai,
        string $kodeGen, array $elemenDiizinkan = [], int $cpId = 0,
        array $referensiCpAktif = []): array
    {
        if ($buku->getSheetNames() !== self::SHEETS
            || (string) $buku->getSheetByName('RINGKASAN')->getCell('B4')->getValue()
                !== AtpUnduhanService::VERSI) {
            throw new DomainException('Versi atau susunan 10 sheet ATP tidak sesuai template resmi.');
        }
        // Identitas berkas sekadar pengecekan konsistensi, bukan sumber otorisasi.
        $ringkasan = $buku->getSheetByName('RINGKASAN');
        if (self::nilai($ringkasan, 'B5') !== $kodeGen
            || self::nilai($ringkasan, 'B9') !== $kodeMapel
            || ($cpId > 0 && self::nilai($ringkasan, 'B14') !== (string) $cpId)) {
            throw new DomainException('Identitas Gen/Mapel/CP pada file tidak sesuai penugasan yang dipilih.');
        }
        // V2.5: stale Excel hasil unduhan sebelum revisi CP wajib ditolak.
        // A7:E... di sheet referensi harus identik dengan versi/teks server.
        // Baris CP bukan isian Guru; cek ulang sebelum membandingkan BAB/TP.
        if ($referensiCpAktif !== []) {
            $cpSheet = $buku->getSheetByName('REFERENSI CP');
            $cpRows = [];
            foreach ($referensiCpAktif as $cpRow) {
                $cpRows[] = [
                    (string) $cpRow['id'],
                    (string) $cpRow['elemen'],
                    (string) $cpRow['rumusan'],
                    'D',
                    (string) $cpRow['nomor_versi'],
                ];
            }
            if (count($cpRows) > 100) {
                throw new DomainException('Referensi CP aktif melebihi kapasitas Excel.');
            }
            foreach ($cpRows as $i => $values) {
                $baris = $i + 7;
                foreach (['A','B','C','D','E'] as $j => $kolom) {
                    if (self::nilai($cpSheet, $kolom . $baris) !== trim($values[$j])) {
                        throw new DomainException('REFERENSI CP!'. $kolom . $baris
                            . ': daftar/rumusan CP sudah berubah. Unduh ulang ATP Excel.');
                    }
                }
            }
            $last = count($cpRows) + 7;
            if (self::nilai($cpSheet, 'A' . $last) !== '') {
                throw new DomainException(
                    'REFERENSI CP: terdapat CP lama/ekstra. Unduh ulang ATP Excel.');
            }
        }

        foreach ($buku->getAllSheets() as $sheet) {
            // Formula tersembunyi pada sheet panduan, CP atau di luar area tabel juga ditolak.
            foreach ($sheet->getCellCollection()->getCoordinates() as $alamat) {
                if ($sheet->getCell($alamat)->getDataType() === DataType::TYPE_FORMULA) {
                    throw new DomainException('Rumus tidak diizinkan pada ' . $sheet->getTitle()
                        . '!' . $alamat . '.');
                }
            }
        }

        $babSem = [];
        $tpSem = [];
        $bab = [];
        $tp = [];
        for ($s = 1; $s <= 6; $s++) {
            $sheet = $buku->getSheetByName('SEMESTER ' . $s);
            if (self::nilai($sheet, 'A2') !== AtpUnduhanService::VERSI) {
                throw new DomainException('SEMESTER ' . $s . '!A2: versi sheet tidak sah.');
            }
            if ($sheet->getHighestDataRow() > 118
                || self::nilai($sheet, 'A33') !== ''
                || self::nilai($sheet, 'B33') !== ''
                || self::nilai($sheet, 'C33') !== '') {
                throw new DomainException('SEMESTER ' . $s . ': data di luar rentang input BAB/TP resmi.');
            }
            foreach (self::HEADER_BAB as $idx => $judul) {
                $kolom = chr(65 + $idx) . '12';
                if (self::nilai($sheet, $kolom) !== $judul) {
                    throw new DomainException('SEMESTER ' . $s . '!' . $kolom . ': header BAB berubah.');
                }
            }
            foreach (self::HEADER_TP as $idx => $judul) {
                $kolom = chr(65 + $idx) . '38';
                if (self::nilai($sheet, $kolom) !== $judul) {
                    throw new DomainException('SEMESTER ' . $s . '!' . $kolom . ': header TP berubah.');
                }
            }
            if (self::nilai($sheet, 'I38') !== '' || self::nilai($sheet, 'J38') !== '') {
                throw new DomainException('SEMESTER ' . $s
                    . ': kolom I/J bukan area input; unduh template V25-2 yang baru.');
            }

            for ($row = 13; $row <= 32; $row++) {
                $kolom = [];
                foreach (['A','B','C'] as $k) {
                    $kolom[] = self::nilai($sheet, $k . $row);
                }
                if ($kolom === ['', '', '']) {
                    continue;
                }
                $lokasi = 'SEMESTER ' . $s . ' baris ' . $row;
                if ($s < $semesterMulai) {
                    throw new DomainException($lokasi . ': Gen ini belum dimulai pada semester tersebut.');
                }
                $nomor = self::nomor($kolom[0], $lokasi . ' kolom A');
                if ($nomor > 999 || $kolom[1] === '' || mb_strlen($kolom[1]) > 50
                    || $kolom[2] === '' || mb_strlen($kolom[2]) > 225) {
                    throw new DomainException($lokasi . ': nama BAB wajib 1–50 dan deskripsi 1–225 karakter.');
                }
                if (isset($babSem[$s][$nomor])) {
                    throw new DomainException($lokasi . ': nomor BAB ganda.');
                }
                $babSem[$s][$nomor] = true;
                $bab[] = [
                    'semester' => $s, 'baris' => $row, 'nomor' => $nomor,
                    'nama' => $kolom[1], 'deskripsi' => $kolom[2],
                ];
            }
            for ($row = 39; $row <= 118; $row++) {
                $kolom = [];
                foreach (range('A', 'H') as $k) {
                    $kolom[] = self::nilai($sheet, $k . $row);
                }
                // Jangan diam-diam mengabaikan masukan tersembunyi atau kolom
                // format lama yang masih diisi oleh Guru.
                if (self::nilai($sheet, 'I' . $row) !== ''
                    || self::nilai($sheet, 'J' . $row) !== '') {
                    throw new DomainException('SEMESTER ' . $s . ' baris ' . $row
                        . ': kolom I/J tidak digunakan. Unduh template ATP terbaru.');
                }
                if (count(array_filter($kolom, static fn (string $v): bool => $v !== '')) === 0) {
                    continue;
                }
                $lokasi = 'SEMESTER ' . $s . ' baris ' . $row;
                if ($s < $semesterMulai) {
                    throw new DomainException($lokasi . ': Gen ini belum dimulai pada semester tersebut.');
                }
                $nomorBab = self::nomor($kolom[0], $lokasi . ' kolom A');
                $urutan = self::nomor($kolom[1], $lokasi . ' kolom B');
                if (! isset($babSem[$s][$nomorBab])) {
                    throw new DomainException($lokasi . ': nomor BAB tidak ada di Bagian A semester ini.');
                }
                $kelas = $s <= 2 ? 7 : ($s <= 4 ? 8 : 9);
                // Identitas TP dibangkitkan server, tidak dibaca dari
                // sel Excel dan tidak bergantung pada formula/macro.
                $kodeTp = KodeTpService::bentuk($kodeMapel, $kelas, $nomorBab, $urutan);
                $jenis = match ($kolom[2]) {
                    'Esensial' => 'esensial',
                    'NonEsensial' => 'non_esensial',
                    default => throw new DomainException($lokasi . ': jenis TP wajib Esensial/NonEsensial.'),
                };
                if ($kolom[3] === '' || mb_strlen($kolom[3]) > 50
                    || $kolom[4] === '' || mb_strlen($kolom[4]) > 225
                    || mb_strlen($kolom[5]) > 180 || mb_strlen($kolom[7]) > 225) {
                    throw new DomainException($lokasi . ': nama/deskripsi TP wajib dan batas panjang kolom terlampaui.');
                }
                if ($jenis === 'esensial' && ($kolom[5] !== '' || $kolom[6] !== '')) {
                    throw new DomainException($lokasi . ': TP Esensial tidak boleh memiliki rencana Materi/media.');
                }
                if ($kolom[6] !== '' && ! in_array($kolom[6], ['YouTube','PDF','Audio','Gambar'], true)) {
                    throw new DomainException($lokasi . ': jenis media tidak dikenal.');
                }
                if (isset($tpSem[$s][$nomorBab][$urutan])) {
                    throw new DomainException($lokasi . ': urutan TP ganda dalam BAB.');
                }
                $tpSem[$s][$nomorBab][$urutan] = true;
                $tp[] = [
                    'semester' => $s, 'baris' => $row, 'nomor_bab' => $nomorBab,
                    'urutan' => $urutan, 'kode' => $kodeTp, 'jenis' => $jenis,
                    'nama' => $kolom[3], 'deskripsi' => $kolom[4],
                    'materi' => $kolom[5], 'media' => $kolom[6],
                    'catatan' => $kolom[7],
                ];
            }
        }

        // BAB berlanjut pada semester yang sekelas (1-2/3-4/5-6), reset di kelas baru.
        for ($awal = 1; $awal <= 5; $awal += 2) {
            $nomor = [];
            foreach ($bab as $b) {
                if ($b['semester'] === $awal || $b['semester'] === $awal + 1) {
                    $nomor[] = $b['nomor'];
                }
            }
            if ($nomor === []) {
                continue;
            }
            // Parser hanya memeriksa berurutan dalam file. Angka awal
            // dapat lebih dari 1 untuk impor parsial semester genap saat
            // semester ganjil sudah dibuat lewat aplikasi. Service pratinjau
            // memvalidasi gabungan dengan seluruh BAB di DB.
            foreach ($nomor as $i => $n) {
                if ($n !== $nomor[0] + $i) {
                    throw new DomainException('Kelas semester ' . $awal . '–' . ($awal + 1)
                        . ': nomor BAB Excel tidak berurutan.');
                }
            }
        }
        foreach ($tpSem as $s => $perBab) {
            foreach ($perBab as $n => $urutan) {
                $k = array_keys($urutan);
                sort($k, SORT_NUMERIC);
                foreach ($k as $i => $no) {
                    if ($no !== $i + 1) {
                        throw new DomainException('SEMESTER ' . $s . ' BAB ' . $n
                            . ': urutan TP harus mulai 1 dan tanpa jeda.');
                    }
                }
            }
        }
        return ['bab' => $bab, 'tp' => $tp, 'jumlah_bab' => count($bab), 'jumlah_tp' => count($tp)];
    }
}
