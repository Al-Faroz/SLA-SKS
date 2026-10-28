<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\CpDaftarService;
use App\Services\CpMapelOperasionalService;
use App\Services\CpReferensiService;
use Config\Database;
use DomainException;
use PHPUnit\Framework\TestCase;

final class CpOperasionalMysqlTest extends TestCase
{
    private static $db;

    public static function setUpBeforeClass(): void
    {
        if (getenv('SLA_CP_QA_DB') !== 'sla_sks_qa') {
            self::markTestSkipped('Hanya untuk database QA sintetis.');
        }
        self::$db = Database::connect([
            'DSN'=>'','hostname'=>'127.0.0.1','username'=>'root','password'=>'',
            'database'=>'sla_sks_qa','DBDriver'=>'MySQLi','DBPrefix'=>'',
            'pConnect'=>false,'DBDebug'=>true,'charset'=>'utf8mb4',
            'DBCollat'=>'utf8mb4_unicode_ci','failover'=>[],'port'=>3306,
        ], false);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db !== null) {
            self::$db->close();
        }
    }

    private function akun(int $id = 101, string $role = 'admin'): callable
    {
        return static fn (): array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ];
    }

    private function svc(): CpReferensiService
    {
        return new CpReferensiService(self::$db, $this->akun());
    }

    private function bahan(string $elemen = 'Pemahaman Konsep'): array
    {
        return [
            'elemen'=>$elemen,
            'rumusan'=>'Peserta didik dapat menjelaskan fenomena sejarah dengan benar.',
            'otoritas'=>'Kementerian Agama',
            'nomor_dokumen'=>'Nomor Uji 123',
            'sumber_url'=>'https://example.org/cp-resmi',
            'tanggal_berlaku'=>'2026-07-01',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['log_aktivitas','cp_mapel_elemen','ref_cp_versi','ref_cp','cp_mapel'] as $table) {
            self::$db->table($table)->emptyTable();
        }
    }

    public function testDuaElemenTerbitTetapSatuCpOperasional(): void
    {
        $svc = $this->svc();
        $satu = $svc->buatCp(1, 1, 'SKI.D-KONSEP', $this->bahan('Pemahaman Konsep'));
        $dua = $svc->buatCp(1, 1, 'SKI.D-PROSES', $this->bahan('Keterampilan Proses'));
        self::assertNotSame($satu, $dua, 'Sumber/elemen berbeda mempunyai riwayat sendiri.');
        self::assertSame(1, self::$db->table('cp_mapel')->countAllResults());

        $v1 = self::$db->table('ref_cp_versi')->where('cp_id', $satu)->get()->getRowArray();
        $v2 = self::$db->table('ref_cp_versi')->where('cp_id', $dua)->get()->getRowArray();
        $svc->terbitkan((int) $v1['id']);
        $svc->terbitkan((int) $v2['id']);

        self::assertSame(2, self::$db->table('cp_mapel_elemen')->countAllResults());
        self::assertSame(2, self::$db->table('ref_cp_versi')->where('status','aktif')->countAllResults());

        $daftar = (new CpDaftarService(self::$db, $this->akun()))
            ->halaman(CpDaftarService::filter([]));
        self::assertSame(1, $daftar['cpTotal']);
        self::assertCount(1, $daftar['cp']);
        self::assertSame(2, (int) $daftar['cp'][0]['jumlah_elemen_aktif']);
        self::assertSame(0, (int) $daftar['cp'][0]['jumlah_elemen_draf']);

        $detail = (new CpMapelOperasionalService(self::$db, $this->akun()))
            ->detail((int) $daftar['cp'][0]['id']);
        self::assertCount(2, $detail['elemen']);
        self::assertNotNull($detail['elemen'][0]['id_tautan_aktif']);
        self::assertNotNull($detail['elemen'][1]['id_tautan_aktif']);
    }

    public function testVersiBaruSatuElemenTidakMengarsipElemenLain(): void
    {
        $svc = $this->svc();
        $satu = $svc->buatCp(1, 1, 'SKI.D-KONSEP', $this->bahan('Pemahaman Konsep'));
        $dua = $svc->buatCp(1, 1, 'SKI.D-PROSES', $this->bahan('Keterampilan Proses'));
        $v1 = (int) self::$db->table('ref_cp_versi')->where('cp_id',$satu)->get()->getRow('id');
        $v2 = (int) self::$db->table('ref_cp_versi')->where('cp_id',$dua)->get()->getRow('id');
        $svc->terbitkan($v1);
        $svc->terbitkan($v2);

        $svc->versiBaru($satu, $this->bahan('Pemahaman Konsep'));
        $baru = self::$db->table('ref_cp_versi')
            ->where('cp_id',$satu)->where('status','draf')->get()->getRowArray();
        $svc->terbitkan((int) $baru['id']);

        self::assertSame('diarsipkan',self::$db->table('ref_cp_versi')
            ->where('id',$v1)->get()->getRow('status'));
        self::assertSame('aktif',self::$db->table('ref_cp_versi')
            ->where('id',$v2)->get()->getRow('status'));
        self::assertSame(1, self::$db->table('cp_mapel')->countAllResults());
        self::assertSame(2, (int) (new CpDaftarService(self::$db, $this->akun()))
            ->halaman(CpDaftarService::filter([]))['cp'][0]['jumlah_elemen_aktif']);
        self::assertSame(3, self::$db->table('cp_mapel_elemen')->countAllResults(),
            'Tautan versi historis tidak dihapus.');
    }

    public function testDrafTerlihatWalauBelumDiterbitkan(): void
    {
        $this->svc()->buatCp(1, 1, 'SKI.D-KONSEP', $this->bahan());
        $daftar = (new CpDaftarService(self::$db, $this->akun()))
            ->halaman(CpDaftarService::filter(['status'=>'draf']));
        self::assertSame(1, $daftar['cpTotal']);
        self::assertSame(1, (int) $daftar['cp'][0]['jumlah_elemen_draf']);
        self::assertSame(0, (int) $daftar['cp'][0]['jumlah_elemen_aktif']);
    }

    public function testGagalDuplikatKodeTidakMenambahCpAtauSumber(): void
    {
        $svc = $this->svc();
        $svc->buatCp(1, 1, 'SKI.D-KONSEP', $this->bahan());
        try {
            $svc->buatCp(1, 1, 'SKI.D-KONSEP', $this->bahan('Keterampilan Proses'));
            self::fail('Duplikat sumber diizinkan.');
        } catch (DomainException $e) {
            self::assertStringContainsString('Kode CP sudah ada', $e->getMessage());
        }
        self::assertSame(1, self::$db->table('cp_mapel')->countAllResults());
        self::assertSame(1, self::$db->table('ref_cp')->countAllResults());
    }

    public function testBukanAdminDitolakMembacaOperasional(): void
    {
        $this->svc()->buatCp(1, 1, 'SKI.D-KONSEP', $this->bahan());
        $id = (int) self::$db->table('cp_mapel')->get()->getRow('id');
        $this->expectException(DomainException::class);
        (new CpMapelOperasionalService(self::$db, $this->akun(102,'guru')))->detail($id);
    }
}
