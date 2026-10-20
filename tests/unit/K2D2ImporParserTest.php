<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AkunImporXlsxParser;
use App\Services\AkunXlsxService;
use CodeIgniter\Test\CIUnitTestCase;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

final class K2D2ImporParserTest extends CIUnitTestCase
{
    public function testTemplateSiswaMenjagaNisnBerawalanNolDanSemester(): void
    {
        $buku = AkunXlsxService::buatBuku('siswa', [[
            'nisn' => '0123456789', 'nama' => 'Siswa Contoh',
            'kode_gen' => 'G01', 'semester_mulai' => '2', 'sandi_awal' => 'rahasia1234',
        ]], true);
        try {
            $rows = AkunImporXlsxParser::bacaBuku($buku, 'siswa');
            $this->assertCount(1, $rows);
            $this->assertSame(2, $rows[0]['nomor']);
            $this->assertSame('0123456789', $rows[0]['username']);
            $this->assertSame(['siswa'], $rows[0]['peran']);
            $this->assertSame(2, $rows[0]['semester']);
            $this->assertSame('', $rows[0]['galat']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testPeranYangBolehDanTidakBoleh(): void
    {
        $this->assertSame(['admin', 'guru'], AkunImporXlsxParser::peranDariTeks('guru+admin'));
        $this->assertSame(['guru', 'pimpinan'], AkunImporXlsxParser::peranDariTeks('guru + pimpinan'));
        $this->assertSame([], AkunImporXlsxParser::peranDariTeks('admin+siswa'));
        $this->assertSame([], AkunImporXlsxParser::peranDariTeks('guru+guru'));
        $this->assertSame([], AkunImporXlsxParser::peranDariTeks('superadmin'));
    }

    public function testSandiTidakTerpaparDalamLaporanParserYangValid(): void
    {
        $buku = AkunXlsxService::buatBuku('pengelola', [[
            'username' => 'guru.tes', 'nama' => 'Guru Contoh',
            'peran' => 'guru', 'sandi_awal' => 'sementara8765',
        ]], true);
        try {
            $rows = AkunImporXlsxParser::bacaBuku($buku, 'pengelola');
            $this->assertSame('sementara8765', $rows[0]['sandi']);
            $this->assertSame('guru.tes', $rows[0]['username']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testFormulaDitolakTanpaDievaluasi(): void
    {
        $buku = AkunXlsxService::buatBuku('siswa', [[
            'nisn' => '0123456789', 'nama' => 'Siswa Contoh',
            'kode_gen' => 'G01', 'semester_mulai' => '', 'sandi_awal' => 'sementara123',
        ]], true);
        try {
            $sheet = $buku->getActiveSheet();
            $sheet->setCellValue('B2', '=1+2');
            $this->assertSame(DataType::TYPE_FORMULA, $sheet->getCell('B2')->getDataType());
            $rows = AkunImporXlsxParser::bacaBuku($buku, 'siswa');
            $this->assertStringContainsString('formula', $rows[0]['galat']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testTemplateSalahDitolak(): void
    {
        $buku = AkunXlsxService::buatBuku('pengelola', [], true);
        try {
            $this->expectException(DomainException::class);
            AkunImporXlsxParser::bacaBuku($buku, 'siswa');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testBarisDenganNisnTidakValidDiberiAlasan(): void
    {
        $buku = AkunXlsxService::buatBuku('siswa', [[
            'nisn' => '123', 'nama' => 'Siswa Contoh', 'kode_gen' => 'G01',
            'semester_mulai' => '9', 'sandi_awal' => 'contoh8765',
        ]], true);
        try {
            $rows = AkunImporXlsxParser::bacaBuku($buku, 'siswa');
            $this->assertCount(1, $rows);
            $this->assertNotSame('', $rows[0]['galat']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testPatchImporTidakMengubahTabelProduksi(): void
    {
        $sql = file_get_contents(ROOTPATH . 'database/sql/updates/0006_impor_akun.sql');
        $this->assertIsString($sql);
        $bersih = preg_replace('/^\s*--.*$/m', '', $sql);
        $this->assertSame(2, preg_match_all('/CREATE TABLE\s+impor_akun/i', $bersih));
        $this->assertDoesNotMatchRegularExpression('/\b(DROP|TRUNCATE|DELETE\s+FROM|UPDATE\s+pengguna|ALTER\s+TABLE)\b/i', $bersih);
        $this->assertStringContainsString("VALUES ('K2-0006')", $sql);
    }
}
