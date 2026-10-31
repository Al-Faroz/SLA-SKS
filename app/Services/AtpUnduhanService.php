<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * Unduhan ATP manual v2.5 berdasarkan satu paket milik Guru G1.
 * Read-only: tidak menambah/menyunting BAB, TP, CP maupun database.
 */
final class AtpUnduhanService
{
    public const VERSI = AtpOtomatisTpService::VERSI;
    private const TEMPLATE = 'docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx';

    private BaseConnection $db;

    /** @var callable():?array */
    private $akunAktif;

    /** @param (callable():?array)|null $akunAktif */
    public function __construct(?BaseConnection $db = null, ?callable $akunAktif = null)
    {
        $this->db = $db ?? db_connect();
        $this->akunAktif = $akunAktif ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    /** @return array{nama:string,isi:string} */
    public function unduh(int $paketId): array
    {
        $akun = ($this->akunAktif)();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Guru aktif yang dapat mengunduh ATP.');
        }
        if ($paketId < 1) {
            throw new DomainException('Paket semester tidak valid.');
        }
        if (! (new PaketSemesterGuruService($this->db, $this->akunAktif))->skemaV4Siap()) {
            throw new DomainException('Unduhan ATP memerlukan database SLA-SKS v4 R2.');
        }

        // ID paket berasal dari URL, bukan izin. Cocokkan langsung ke G1 aktif.
        $paket = $this->db->table('paket_semester p')
            ->select('p.id,p.semester,p.kurikulum_id,p.angkatan_id,p.mapel_id,'
                . 'a.kode AS kode_gen,a.nama AS nama_gen,a.semester_mulai,'
                . 'm.kode AS kode_mapel,m.nama AS nama_mapel,'
                . 'k.nama AS nama_kurikulum,k.versi AS versi_kurikulum,'
                . 'ta.nama AS nama_tahun')
            ->join('penugasan_guru_gen g', 'g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id')
            ->join('ref_angkatan a', 'a.id=p.angkatan_id')
            ->join('ref_mapel m', 'm.id=p.mapel_id')
            ->join('ref_kurikulum k', 'k.id=p.kurikulum_id')
            ->join('ref_tahun_ajaran ta', 'ta.id=p.tahun_ajaran_id')
            ->where('p.id', $paketId)
            ->where('p.status !=', 'arsip')
            ->where('g.guru_id', (int) $akun['id'])
            ->get()->getRowArray();

        if ($paket === null) {
            throw new DomainException('Paket tidak ditemukan dalam penugasan Gen–Mapel Anda.');
        }

        // Hanya CP operasional tunggal Fase D dan versi elemen yang sudah aktif.
        $cp = $this->db->table('cp_mapel c')
            ->select('c.id')
            ->join('ref_fase f', 'f.id=c.fase_id')
            ->where('c.kurikulum_id', (int) $paket['kurikulum_id'])
            ->where('c.mapel_id', (int) $paket['mapel_id'])
            ->where('f.kode', 'D')
            ->get()->getResultArray();
        if (count($cp) !== 1) {
            throw new DomainException('CP operasional Fase D belum tersedia secara tunggal untuk paket ini.');
        }

        $elemen = $this->db->table('cp_mapel_elemen e')
            ->select('v.id,v.elemen,v.rumusan,v.nomor_versi')
            ->join('ref_cp_versi v', 'v.id=e.cp_versi_id')
            ->where('e.cp_mapel_id', (int) $cp[0]['id'])
            ->where('v.status', 'aktif')
            ->orderBy('v.id', 'ASC')
            ->get()->getResultArray();
        if ($elemen === [] || count($elemen) > 100) {
            throw new DomainException('CP operasional belum memiliki elemen aktif atau jumlah elemennya perlu ditinjau Admin.');
        }

        $path = ROOTPATH . self::TEMPLATE;
        if (! is_file($path)) {
            throw new RuntimeException('Berkas template ATP resmi tidak ditemukan di docs/.');
        }

        $buku = IOFactory::load($path);
        try {
            self::isiBuku($buku, [
                'kode_gen' => (string) $paket['kode_gen'],
                'nama_gen' => (string) $paket['nama_gen'],
                'guru_id' => (string) $akun['id'],
                'nama_guru' => (string) $akun['nama'],
                'kode_mapel' => (string) $paket['kode_mapel'],
                'nama_mapel' => (string) $paket['nama_mapel'],
                'kurikulum' => (string) $paket['nama_kurikulum'] . ' — ' . (string) $paket['versi_kurikulum'],
                'tahun_ajaran' => (string) $paket['nama_tahun'] . ' (paket acuan; paket tiap semester di aplikasi)',
                'cp_id' => (string) $cp[0]['id'],
                'semester_mulai' => (int) $paket['semester_mulai'],
                'semester_paket' => (int) $paket['semester'],
            ], $elemen);

            // Pemeriksaan final V25-3 sebelum workbook dikirim. Jika
            // template/konversi terlewat, jangan pernah mengirim ulang format
            // lama yang masih memiliki kolom Kode TP atau Elemen CP.
            self::periksaTemplateV25Tiga($buku);

            $temp = tempnam(WRITEPATH, 'atp_unduh_');
            if ($temp === false) {
                throw new RuntimeException('Berkas sementara ATP tidak dapat dibuat.');
            }
            try {
                (new Xlsx($buku))->save($temp);
                $isi = file_get_contents($temp);
                if ($isi === false || strlen($isi) < 100) {
                    throw new RuntimeException('Pembuatan XLSX ATP gagal.');
                }
            } finally {
                @unlink($temp);
            }
        } finally {
            $buku->disconnectWorksheets();
        }

        // Catat metadata teknis saja; tidak memuat identitas siswa ataupun rumusan CP.
        log_message('info', 'Unduh ATP manual v2.5; pengguna_id={id}; paket_id={paket}', [
            'id' => (int) $akun['id'], 'paket' => $paketId,
        ]);

        $namaGen = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $paket['kode_gen']) ?: 'gen';
        $kodeMapel = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $paket['kode_mapel']) ?: 'mapel';
        return [
            'nama' => 'ATP-V25-3-' . $namaGen . '-' . $kodeMapel . '-6-SEMESTER.xlsx',
            'isi' => $isi,
        ];
    }


    /**
     * Guard kontrak unduhan: enam sheet menggunakan delapan kolom input TP.
     * Dijalankan sebelum serialize XLSX sehingga kegagalan tidak diam-diam
     * menghasilkan file V25-1 dengan nama V25-3.
     */
    public static function periksaTemplateV25Tiga(Spreadsheet $buku): void
    {
        $summary = $buku->getSheetByName('RINGKASAN');
        if ($summary === null
            || (string) $summary->getCell('B4')->getValue() !== self::VERSI) {
            throw new RuntimeException('Versi template ATP tidak sesuai V25-3.');
        }
        for ($n = 1; $n <= 6; ++$n) {
            $sheet = $buku->getSheetByName('SEMESTER ' . $n);
            if ($sheet === null
                || (string) $sheet->getCell('A2')->getValue() !== self::VERSI) {
                throw new RuntimeException('Marker semester ATP tidak sesuai V25-3.');
            }
            foreach (AtpTemplateLayoutService::HEADER_TP as $i => $header) {
                $cell = chr(65 + $i) . '38';
                if ((string) $sheet->getCell($cell)->getValue() !== $header) {
                    throw new RuntimeException('Header ATP V25-3 tidak sesuai pada Semester ' . $n . '.');
                }
            }
            for ($row = 39; $row <= 118; ++$row) {
                if (! AtpOtomatisTpService::formulaSah($sheet, 'B' . $row)) {
                    throw new RuntimeException('Rumus urutan TP B' . $row
                        . ' pada Semester ' . $n . ' tidak sesuai format V25-3.');
                }
            }
            if (! AtpOtomatisTpService::daftarBabSah($buku, $n)) {
                throw new RuntimeException('Dropdown dan Defined Name BAB pada Semester '
                    . $n . ' tidak sesuai V25-3.');
            }
            // Jangan menganggap dropdown lengkap jika opsi Jenis TP/Media
            // tidak tampil di Excel atau daftar pilihannya hilang.
            foreach ([
                'C39' => ['C39:C118', '"Esensial,NonEsensial"'],
                'G39' => ['G39:G118', '"YouTube,PDF,Audio,Gambar"'],
            ] as $alamat => [$rentang, $pilihan]) {
                $dv = $sheet->getDataValidation($alamat);
                if ($dv->getType() !== 'list' || $dv->getSqref() !== $rentang
                    || $dv->getFormula1() !== $pilihan
                    || $dv->getShowDropDown() || ! $dv->getShowErrorMessage()) {
                    throw new RuntimeException('Validasi ' . $alamat . ' pada Semester '
                        . $n . ' tidak aktif atau tidak terlihat di Excel.');
                }
            }
            foreach (['I38', 'J38'] as $cell) {
                if (trim((string) ($sheet->getCell($cell)->getValue() ?? '')) !== '') {
                    throw new RuntimeException('Kolom TP lama masih ada pada Semester ' . $n . '.');
                }
            }
        }
    }

    /** Hilangkan kontrol XML dan awal formula dari data referensi sekalipun ditulis sebagai STRING. */
    private static function teksAman(string $nilai): string
    {
        $nilai = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $nilai) ?? '';
        if (preg_match('/^[\x00-\x20]*[=+\-@]/', $nilai) === 1) {
            return "'" . $nilai;
        }
        return $nilai;
    }

    /**
     * Isi hanya identitas dan CP; area BAB/TP tetap kosong dan editable.
     *
     * @param array<string,string|int> $konteks
     * @param list<array<string,mixed>> $elemen
     */
    public static function isiBuku(Spreadsheet $buku, array $konteks, array $elemen): void
    {
        // Toleransi template dasar V25-1 yang masih berada dalam paket CI:
        // hasil unduhan selalu V25-3 dan parser hanya menerima V25-3.
        AtpTemplateLayoutService::terapkan($buku);
        AtpOtomatisTpService::terapkan($buku);
        $namaSheet = [
            'MULAI DI SINI', 'RINGKASAN', 'REFERENSI CP',
            'SEMESTER 1', 'SEMESTER 2', 'SEMESTER 3',
            'SEMESTER 4', 'SEMESTER 5', 'SEMESTER 6', 'CONTOH MAT VII',
        ];
        if ($buku->getSheetNames() !== $namaSheet
            || (string) $buku->getSheetByName('RINGKASAN')->getCell('B4')->getValue() !== self::VERSI) {
            throw new DomainException('Struktur atau versi template ATP resmi tidak sesuai.');
        }

        $label = [
            5 => 'kode_gen', 6 => 'nama_gen', 7 => 'guru_id', 8 => 'nama_guru',
            9 => 'kode_mapel', 10 => 'nama_mapel', 11 => 'kurikulum',
            13 => 'tahun_ajaran', 14 => 'cp_id',
        ];
        foreach ($label as $baris => $kunci) {
            if (! isset($konteks[$kunci]) || trim((string) $konteks[$kunci]) === '') {
                throw new DomainException('Konteks ATP belum lengkap: ' . $kunci . '.');
            }
        }
        if (! isset($konteks['semester_mulai'], $konteks['semester_paket'])
            || (int) $konteks['semester_mulai'] < 1 || (int) $konteks['semester_mulai'] > 6
            || (int) $konteks['semester_paket'] < 1
            || (int) $konteks['semester_paket'] > 6 || $elemen === []) {
            throw new DomainException('Semester atau referensi CP untuk ATP tidak valid.');
        }

        $ringkasan = $buku->getSheetByName('RINGKASAN');
        foreach ($label as $baris => $kunci) {
            $ringkasan->setCellValueExplicit('B' . $baris, self::teksAman((string) $konteks[$kunci]),
                DataType::TYPE_STRING);
        }
        $ringkasan->setCellValueExplicit('B12', 'D', DataType::TYPE_STRING);
        $ringkasan->setCellValueExplicit('B15',
            'Semester 1–6; mulai Gen S' . (int) $konteks['semester_mulai']
            . '; konteks paket S' . (int) $konteks['semester_paket'], DataType::TYPE_STRING);

        $cp = $buku->getSheetByName('REFERENSI CP');
        foreach ($elemen as $i => $baris) {
            foreach (['id', 'elemen', 'rumusan', 'nomor_versi'] as $kunci) {
                if (! isset($baris[$kunci]) || (string) $baris[$kunci] === '') {
                    throw new DomainException('Data elemen CP aktif tidak lengkap.');
                }
            }
            if ($i >= 100 || mb_strlen((string) $baris['rumusan']) > 30000) {
                throw new DomainException('Daftar/rumusan CP melebihi kapasitas template Excel.');
            }
            $nilai = [
                (string) $baris['id'],
                (string) $baris['elemen'],
                (string) $baris['rumusan'],
                'D',
                (string) $baris['nomor_versi'],
            ];
            foreach ($nilai as $kolom => $teks) {
                $cp->setCellValueExplicit([$kolom + 1, $i + 7], self::teksAman($teks),
                    DataType::TYPE_STRING);
            }
        }
        // Samakan keluaran Excel dengan standar visual Peta Pembelajaran lama.
        // Hanya style dan teks panduan; sel input BAB/TP tetap tidak diisi.
        AtpDesainWorkbookService::terapkan($buku);
        $buku->setActiveSheetIndex(0);
    }
}
