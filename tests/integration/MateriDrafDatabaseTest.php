<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\MateriDrafService;
use Config\Database;
use DomainException;
use PHPUnit\Framework\TestCase;

final class MateriDrafDatabaseTest extends TestCase
{
    private static $db;

    public static function setUpBeforeClass(): void
    {
        if (getenv('SLA_QA_DB') !== 'sla_sks_qa') {
            self::markTestSkipped('Hanya database QA sementara sla_sks_qa.');
        }
        self::$db = Database::connect([
            'DSN' => '', 'hostname' => '127.0.0.1', 'username' => 'root',
            'password' => '', 'database' => 'sla_sks_qa', 'DBDriver' => 'MySQLi',
            'DBPrefix' => '', 'pConnect' => false, 'DBDebug' => true,
            'charset' => 'utf8mb4', 'DBCollat' => 'utf8mb4_unicode_ci',
            'swapPre' => '', 'encrypt' => false, 'compress' => false,
            'strictOn' => false, 'failover' => [], 'port' => 3306,
        ], false);
    }

    private function svc(int $id = 1): MateriDrafService
    {
        return new MateriDrafService(self::$db, static fn (): array => [
            'id' => $id, 'status' => 'aktif', 'peran_aktif' => 'guru', 'wajib_ganti_sandi' => 0,
        ]);
    }

    private function data(): array
    {
        return [
            'judul' => 'Materi tambahan QA',
            'jenis' => 'youtube',
            'petunjuk_resume' => 'Tuliskan resume singkat dari media.',
            'petunjuk_hots' => 'Analisis dan jelaskan alasan pilihanmu.',
            'min_waktu_menit' => '1.5',
            'url_youtube' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ];
    }

    public function testV25SatuTpMenolakMateriKeduaTanpaMengubahData(): void
    {
        // Fixture QA sudah memiliki satu Materi pada TP 1.
        $awal = self::$db->table('tp')->where('id', 1)->get()->getRowArray();
        $jumlah = self::$db->table('materi')->where('tp_id', 1)->countAllResults();
        self::assertSame(1, $jumlah);
        try {
            $this->svc()->tambah(1, (int) $awal['struktur_versi'], $this->data());
            self::fail('Materi kedua tidak boleh disisipkan dalam kontrak v2.5.');
        } catch (DomainException $e) {
            self::assertStringContainsString('satu Materi', $e->getMessage());
        }
        self::assertSame($jumlah, self::$db->table('materi')->where('tp_id', 1)->countAllResults());
        self::assertSame((int) $awal['struktur_versi'],
            (int) self::$db->table('tp')->where('id', 1)->get()->getRow('struktur_versi'));
    }

    public function testMenitDesimalMelebihiKapasitasDitolak(): void
    {
        $data = $this->data();
        $data['min_waktu_menit'] = '1092.26';
        $this->expectException(DomainException::class);
        $this->svc()->tambah(1, 1, $data);
    }

    public function testNilaiMenitTidakValidDitolak(): void
    {
        $data = $this->data();
        $data['min_waktu_menit'] = '-1';
        $this->expectException(DomainException::class);
        $this->svc()->tambah(1, 1, $data);
    }

    public function testVersiLamaDitolakTanpaMenyisipkanMateri(): void
    {
        $this->expectException(DomainException::class);
        $this->svc()->tambah(1, 9999, $this->data());
    }

    public function testTidakBolehMenambahMateriGenLain(): void
    {
        $this->expectException(DomainException::class);
        $this->svc()->tambah(2, 1, $this->data());
    }

    public function testTidakBolehMengubahMateriMilikTpLain(): void
    {
        $this->expectException(DomainException::class);
        $this->svc()->simpan(1, 2, 1, $this->data());
    }

    public function testTpSiapMenolakPerubahanMateri(): void
    {
        $asli = self::$db->table('tp')->where('id', 1)->get()->getRowArray();
        try {
            self::$db->table('tp')->where('id', 1)->update(['status' => 'siap']);
            $this->expectException(DomainException::class);
            $this->svc()->tambah(1, 1, $this->data());
        } finally {
            self::$db->table('tp')->where('id', 1)->update(['status' => $asli['status']]);
        }
    }

    public function testUrlPalsuDitolak(): void
    {
        $data = $this->data();
        $data['url_youtube'] = 'https://youtube.com.example.org/watch?v=x';
        $this->expectException(DomainException::class);
        $this->svc()->tambah(1, 1, $data);
    }

    public function testUrlYoutubeTidakBolehUntukJenisPdf(): void
    {
        $data = $this->data();
        $data['jenis'] = 'pdf';
        $this->expectException(DomainException::class);
        $this->svc()->tambah(1, 1, $data);
    }

    public function testArahUrutTidakSahDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->svc()->geser(1, 1, 1, 'hapus');
    }

    public function testMateriPertamaTidakDapatNaik(): void
    {
        $this->expectException(DomainException::class);
        $this->svc()->geser(1, 1, 1, 'naik');
    }
}
