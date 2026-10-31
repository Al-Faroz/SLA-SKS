<?php

declare(strict_types=1);

use App\Services\AtpExcelParserService;
use App\Services\AtpTemplateLayoutService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

final class AtpExcelParserServiceTest extends TestCase
{
    private function buku(): Spreadsheet
    {
        $buku = IOFactory::load(dirname(__DIR__, 2)
            . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx');
        AtpTemplateLayoutService::terapkan($buku);
        \App\Services\AtpOtomatisTpService::terapkan($buku);
        $s = $buku->getSheetByName('RINGKASAN');
        $s->setCellValue('B5', 'GEN2025');
        $s->setCellValue('B9', 'SKI');
        return $buku;
    }

    private function bab(Spreadsheet $buku, int $semester, int $baris, int $nomor): void
    {
        $s = $buku->getSheetByName('SEMESTER ' . $semester);
        $s->setCellValue('A' . $baris, $nomor);
        $s->setCellValue('B' . $baris, 'Sejarah Perkembangan Islam');
        $s->setCellValue('C' . $baris, 'Contoh deskripsi BAB akademik');
    }

    private function tp(Spreadsheet $buku, int $semester, string $kode, string $jenis = 'NonEsensial',
        int $urutan = 1, int $nomorBab = 1): void
    {
        $s = $buku->getSheetByName('SEMESTER ' . $semester);
        $s->setCellValue('A39', $nomorBab);
        $s->setCellValue('C39', $jenis);
        $s->setCellValue('D39', 'Menganalisis peristiwa');
        $s->setCellValue('E39', 'Mengenali sebab dan akibat peristiwa penting');
        $s->setCellValue('F39', 'Materi pembelajaran lengkap');
        $s->setCellValue('G39', 'PDF');
    }

    public function testWorkbookKosongMemilikiHasilNol(): void
    {
        $buku = $this->buku();
        try {
            $r = AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
            self::assertSame(0, $r['jumlah_bab']);
            self::assertSame(0, $r['jumlah_tp']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testSatuBabSatuTpDibacaDanContohTidakDiimpor(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->tp($buku, 4, 'SKI.8.1.1');
            $r = AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025', ['Pemahaman Konsep']);
            self::assertSame(1, $r['jumlah_bab']);
            self::assertSame(1, $r['jumlah_tp']);
            self::assertSame('SKI.8.1.1', $r['tp'][0]['kode']);
            self::assertSame('non_esensial', $r['tp'][0]['jenis']);
            self::assertSame(39, $r['tp'][0]['baris']);
            self::assertSame(4, $r['bab'][0]['semester']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testNomorBabSemesterGenapBolehMelanjutkanYangAdaDiDb(): void
    {
        // Parser tidak mengetahui DB: BAB 2 dalam sheet Semester 4
        // valid secara struktur. Service impor memverifikasi BAB 1 yang
        // sudah dibuat di Semester 3 sebelum memberi izin commit.
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 2);
            $this->tp($buku, 4, 'SKI.8.2.1', 'NonEsensial', 1, 2);
            $r = AtpExcelParserService::bacaBuku($buku, 'SKI', 3, 'GEN2025');
            self::assertSame(2, $r['bab'][0]['nomor']);
            self::assertSame(1, $r['jumlah_tp']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testKodeTpSelaluDibentukServerWalaupunGuruTidakMengisinya(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->tp($buku, 4, 'TIDAK-DIGUNAKAN');
            $result = AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
            self::assertSame('SKI.8.1.1', $result['tp'][0]['kode']);
            self::assertSame('Menganalisis peristiwa', $result['tp'][0]['nama']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testTpTanpaBabDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->tp($buku, 4, 'SKI.8.1.1');
            $this->expectExceptionMessage('nomor BAB tidak ada');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testDuplikatBabDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->bab($buku, 4, 14, 1);
            $this->expectExceptionMessage('nomor BAB ganda');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testGuruBolehIsiSemesterSebelumSemesterMulaiGen(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 3, 13, 1);
            $this->tp($buku, 3, 'SKI.8.1.1');
            $hasil = AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
            self::assertSame(1, $hasil['jumlah_bab']);
            self::assertSame(1, $hasil['jumlah_tp']);
            self::assertSame(3, $hasil['tp'][0]['semester']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testIdentitasGenLainDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->expectExceptionMessage('Identitas Gen/Mapel');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN-LAIN');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testHeaderYangDiubahDitolak(): void
    {
        $buku = $this->buku();
        try {
            $buku->getSheetByName('SEMESTER 4')->setCellValue('C38', 'Kolom Lain');
            $this->expectExceptionMessage('SEMESTER 4!C38');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testRumusTersembunyiDitolak(): void
    {
        $buku = $this->buku();
        try {
            $buku->getSheetByName('CONTOH MAT VII')->setCellValue('J20', '=1+1');
            $this->expectExceptionMessage('Rumus tidak diizinkan');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testTpEsensialDenganRencanaMateriDanMediaDiterima(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->tp($buku, 4, 'SKI.8.1.1', 'Esensial');
            $hasil = AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
            self::assertSame(1, $hasil['jumlah_tp']);
            self::assertSame('esensial', $hasil['tp'][0]['jenis']);
            self::assertSame('Materi pembelajaran lengkap', $hasil['tp'][0]['materi']);
            self::assertSame('PDF', $hasil['tp'][0]['media']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testSemuaJenisTpTanpaJudulMateriDitolak(): void
    {
        foreach (['Esensial', 'NonEsensial'] as $jenis) {
            $buku = $this->buku();
            try {
                $this->bab($buku, 4, 13, 1);
                $this->tp($buku, 4, 'SKI.8.1.1', $jenis);
                $buku->getSheetByName('SEMESTER 4')->setCellValue('F39', null);
                try {
                    AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
                    self::fail('Judul Materi kosong harus ditolak: ' . $jenis);
                } catch (\DomainException $e) {
                    self::assertStringContainsString('judul satu Materi (F)', $e->getMessage());
                }
            } finally {
                $buku->disconnectWorksheets();
            }
        }
    }

    public function testSemuaJenisTpTanpaJenisMediaDitolak(): void
    {
        foreach (['Esensial', 'NonEsensial'] as $jenis) {
            $buku = $this->buku();
            try {
                $this->bab($buku, 4, 13, 1);
                $this->tp($buku, 4, 'SKI.8.1.1', $jenis);
                $buku->getSheetByName('SEMESTER 4')->setCellValue('G39', null);
                try {
                    AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
                    self::fail('Jenis media kosong harus ditolak: ' . $jenis);
                } catch (\DomainException $e) {
                    self::assertStringContainsString('jenis media (G)', $e->getMessage());
                }
            } finally {
                $buku->disconnectWorksheets();
            }
        }
    }

    public function testKolomElemenCpLamaYangTersembunyiDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->tp($buku, 4, 'SKI.8.1.1');
            $buku->getSheetByName('SEMESTER 4')->setCellValue('I39', 'Keterampilan Proses');
            $this->expectExceptionMessage('kolom I/J tidak digunakan');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testUrutanTpDihitungPerBabOlehServerMeskiBarisDiselingi(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->bab($buku, 4, 14, 2);
            $sh = $buku->getSheetByName('SEMESTER 4');
            foreach ([39=>1, 40=>2, 41=>1, 42=>2] as $row=>$nomorBab) {
                $sh->setCellValue('A' . $row, $nomorBab);
                $sh->setCellValue('C' . $row, 'Esensial');
                $sh->setCellValue('D' . $row, 'Tujuan belajar ' . $row);
                $sh->setCellValue('E' . $row, 'Uraian akademik ' . $row);
                $sh->setCellValue('F' . $row, 'Materi sejarah ' . $row);
                $sh->setCellValue('G' . $row, 'PDF');
            }
            $hasil = AtpExcelParserService::bacaBuku($buku,'SKI',4,'GEN2025');
            self::assertSame(['SKI.8.1.1','SKI.8.2.1','SKI.8.1.2','SKI.8.2.2'],
                array_column($hasil['tp'], 'kode'));
            self::assertSame([1,1,2,2], array_column($hasil['tp'], 'urutan'));
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testRumusTpDigantiNilaiAtauRumusAsingDitolak(): void
    {
        foreach (['2', '=1+1'] as $palsu) {
            $buku = $this->buku();
            try {
                $buku->getSheetByName('SEMESTER 5')->setCellValue('B41', $palsu);
                AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
            } catch (\DomainException $e) {
                self::assertStringContainsString('SEMESTER 5', $e->getMessage());
                $this->addToAssertionCount(1);
            } finally {
                $buku->disconnectWorksheets();
            }
        }
    }

    public function testFileBukanZipDitolak(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'atp_invalid_');
        self::assertIsString($path);
        try {
            file_put_contents($path, 'bukan file Excel');
            $this->expectExceptionMessage('bukan XLSX');
            AtpExcelParserService::baca($path, 'SKI', 4, 'GEN2025');
        } finally {
            @unlink($path);
        }
    }

    public function testReferensiCpBerversiSamaDenganServer(): void
    {
        $buku = $this->buku();
        try {
            $ref = $buku->getSheetByName('REFERENSI CP');
            foreach (['A' => '44', 'B' => 'Pemahaman Konsep',
                      'C' => 'Rumusan baru Fase D yang tepat', 'D' => 'D',
                      'E' => '1'] as $kolom => $teks) {
                $ref->setCellValue($kolom . '7', $teks);
            }
            $rows = [[
                'id' => 44, 'elemen' => 'Pemahaman Konsep',
                'rumusan' => 'Rumusan baru Fase D yang tepat', 'nomor_versi' => 1,
            ]];
            $r = AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025',
                ['Pemahaman Konsep'], 0, $rows);
            self::assertSame(0, $r['jumlah_tp']);

            $rows[0]['rumusan'] = 'Rumusan terbaru yang menggantikan unduhan lama';
            $this->expectExceptionMessage('Unduh ulang ATP Excel');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025',
                ['Pemahaman Konsep'], 0, $rows);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testReferensiCpLamaLebihBanyakDitolak(): void
    {
        $buku = $this->buku();
        try {
            $ref = $buku->getSheetByName('REFERENSI CP');
            foreach (['A' => '44', 'B' => 'Pemahaman Konsep',
                      'C' => 'CP terbaru', 'D' => 'D', 'E' => '1'] as $kolom => $teks) {
                $ref->setCellValue($kolom . '7', $teks);
            }
            $ref->setCellValue('A8', '99');
            $this->expectExceptionMessage('CP lama/ekstra');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025',
                ['Pemahaman Konsep'], 0, [[
                    'id' => 44, 'elemen' => 'Pemahaman Konsep',
                    'rumusan' => 'CP terbaru', 'nomor_versi' => 1,
                ]]);
        } finally {
            $buku->disconnectWorksheets();
        }
    }


    public function testRumusanCpSamaTetapValidSaatDatabaseCrLfDanExcelLf(): void
    {
        $buku = $this->buku();
        try {
            // Sintetis, tanpa menyalin CP atau data privat dari dump.
            $butir = [
                'Pada akhir Fase D, murid mampu :',
                '1. Merefleksikan sejarah dan keteladanan;',
                '2. Menganalisis perkembangan peradaban;',
                "3. Memahami nilai rahmatan lil 'alamin.",
            ];
            $nilaiExcel = implode("\n", $butir);
            $nilaiServer = implode("\r\n", $butir);
            $referensi = $buku->getSheetByName('REFERENSI CP');
            foreach ([
                'A7' => 4,
                'B7' => 'Pemahaman Konsep',
                'C7' => $nilaiExcel,
                'D7' => 'D',
                'E7' => 1,
            ] as $sel => $nilai) {
                $referensi->setCellValue($sel, $nilai);
            }
            $rows = [[
                'id' => 4, 'elemen' => 'Pemahaman Konsep',
                'rumusan' => $nilaiServer, 'nomor_versi' => 1,
            ]];

            $hasil = AtpExcelParserService::bacaBuku(
                $buku, 'SKI', 4, 'GEN2025', ['Pemahaman Konsep'], 0, $rows
            );
            self::assertSame(0, $hasil['jumlah_tp']);
            self::assertSame(0, $hasil['jumlah_bab']);

            // Perubahan isi akademik TETAP ditolak meskipun newline berbeda.
            $rows[0]['rumusan'] = str_replace(
                'keteladanan', 'perjuangan', $nilaiServer
            );
            $this->expectExceptionMessage(
                'REFERENSI CP!C7: daftar/rumusan CP sudah berubah'
            );
            AtpExcelParserService::bacaBuku(
                $buku, 'SKI', 4, 'GEN2025', ['Pemahaman Konsep'], 0, $rows
            );
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testPerbedaanVersiCpTetapDitolakWalauBarisSama(): void
    {
        $buku = $this->buku();
        try {
            $ref = $buku->getSheetByName('REFERENSI CP');
            foreach ([
                'A7' => 4, 'B7' => 'Pemahaman Konsep',
                'C7' => "CP baris satu\nCP baris dua",
                'D7' => 'D', 'E7' => 1,
            ] as $cell => $value) {
                $ref->setCellValue($cell, $value);
            }
            $this->expectExceptionMessage('REFERENSI CP!E7');
            AtpExcelParserService::bacaBuku(
                $buku, 'SKI', 4, 'GEN2025', ['Pemahaman Konsep'], 0, [[
                    'id' => 4, 'elemen' => 'Pemahaman Konsep',
                    'rumusan' => "CP baris satu\r\nCP baris dua",
                    'nomor_versi' => 2,
                ]]
            );
        } finally {
            $buku->disconnectWorksheets();
        }
    }

}
