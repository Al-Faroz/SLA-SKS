<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Kontrak lokasi G05 dan larangan pintu impor yang tersebar di halaman semester.
 * Pengujian rendering/otorisasi browser dilakukan lagi pada XAMPP pengguna.
 */
final class GuruAtpImporGlobalUiTest extends TestCase
{
    private function file(string $path): string
    {
        $value = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($value);
        return $value;
    }

    public function testG05MenjadiSatuSatunyaPintuExcel(): void
    {
        $r = $this->file('app/Config/Routes.php');
        self::assertStringContainsString(
            "'guru/peta-atp/impor', 'GuruAtpImpor::index'", $r
        );
        $index = $this->file('app/Views/guru/peta_tp.php');
        self::assertStringContainsString("guru/peta-atp/impor", $index);
        $semester = $this->file('app/Views/guru/semester_atp.php');
        self::assertStringNotContainsString('judul-impor-atp', $semester);
        self::assertStringNotContainsString('Unduh Template ATP Excel', $semester);
        self::assertStringNotContainsString('atp_excel', $semester);
        $halaman = $this->file('app/Views/guru/impor_atp.php');
        self::assertStringContainsString('csrf_field()', $halaman);
        self::assertStringContainsString('guru/peta-atp/unduh/', $halaman);
        self::assertStringContainsString('guru/peta-atp/pratinjau/', $halaman);
    }

    public function testKonfirmasiMenggunakanHalamanG05DanTidakMemakaiTahunAcuan(): void
    {
        $konfirmasi = $this->file('app/Services/AtpImporKonfirmasiService.php');
        self::assertStringContainsString("['tahun_semester']", $konfirmasi);
        self::assertStringNotContainsString("\$g1['tahun_ajaran_id']", $konfirmasi);
        self::assertStringNotContainsString("->update(\$data)", $konfirmasi);
        $pratinjau = $this->file('app/Services/AtpImporPratinjauService.php');
        self::assertStringContainsString("modeSem", $pratinjau);
        self::assertStringContainsString("tahunSem", $pratinjau);
        self::assertStringNotContainsString("->where('tahun_ajaran_id', \$paket['tahun_ajaran_id'])", $pratinjau);
        $r = $this->file('app/Controllers/GuruAtpKonfirmasi.php');
        self::assertStringContainsString('guru/peta-atp/impor', $r);
    }
}
