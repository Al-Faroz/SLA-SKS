<?php

declare(strict_types=1);

use App\Services\DashboardAdminService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** Dashboard A12-4: fixture SQLite anonim, bukan database XAMPP. */
final class DashboardAdminDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db=\Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,
            'foreignKeys'=>false,'busyTimeout'=>1000,'synchronous'=>null,
            'failover'=>[],
        ],false);
        foreach ([
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,guru_id INTEGER)',
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,status TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,status TEXT)',
            'CREATE TABLE profil_pengelola(pengguna_id INTEGER,foto_menunggu_id INTEGER)',
            'CREATE TABLE foto_profil(id INTEGER PRIMARY KEY,status TEXT)',
            'CREATE TABLE sertifikat(id INTEGER PRIMARY KEY,status TEXT)',
            'CREATE TABLE sertifikat_aktif(id INTEGER PRIMARY KEY,sertifikat_id INTEGER)',
            'CREATE TABLE sertifikat_aset(id INTEGER PRIMARY KEY,jenis TEXT,nama_acak TEXT,mime TEXT,sha256 TEXT)',
            'CREATE TABLE sertifikat_aset_aktif(jenis TEXT,aset_id INTEGER)',
        ] as $sql) $this->db->query($sql);
        $this->db->table('ref_angkatan')->insertBatch([['id'=>1],['id'=>2]]);
        $this->db->table('ref_mapel')->insertBatch([['id'=>1],['id'=>2]]);
        $this->db->table('penugasan_guru_gen')->insertBatch([
            ['id'=>1,'angkatan_id'=>1,'mapel_id'=>1,'guru_id'=>12],
            ['id'=>2,'angkatan_id'=>1,'mapel_id'=>2,'guru_id'=>null],
            ['id'=>3,'angkatan_id'=>2,'mapel_id'=>1,'guru_id'=>13],
        ]);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>11,'status'=>'aktif'],['id'=>12,'status'=>'aktif'],
            ['id'=>13,'status'=>'aktif'],['id'=>14,'status'=>'nonaktif'],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>101,'pengguna_id'=>11],['id'=>102,'pengguna_id'=>14],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>201,'status'=>'siap'],['id'=>202,'status'=>'draf'],
            ['id'=>203,'status'=>'draf'],['id'=>204,'status'=>'arsip'],
        ]);
        $this->db->table('foto_profil')->insertBatch([
            ['id'=>301,'status'=>'menunggu'],
            ['id'=>302,'status'=>'menunggu'],
            ['id'=>303,'status'=>'disetujui'],
        ]);
        $this->db->table('profil_pengelola')->insertBatch([
            ['pengguna_id'=>12,'foto_menunggu_id'=>301],
            ['pengguna_id'=>13,'foto_menunggu_id'=>303],
        ]);
        $this->db->table('sertifikat')->insertBatch([
            ['id'=>401,'status'=>'berlaku'],
            ['id'=>402,'status'=>'digantikan'],
            ['id'=>403,'status'=>'berlaku'],
        ]);
        $this->db->table('sertifikat_aktif')->insertBatch([
            ['id'=>1,'sertifikat_id'=>401],
            ['id'=>2,'sertifikat_id'=>402],
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private static function akun(string $role='admin',string $status='aktif'): callable
    {
        return static fn (): array => [
            'id'=>99,'peran_aktif'=>$role,'status'=>$status,
            'wajib_ganti_sandi'=>0,
        ];
    }

    private function service(?callable $auth=null): DashboardAdminService
    {
        return new DashboardAdminService($this->db,$auth??self::akun());
    }

    public function testRingkasanJumlahDanAntreanTidakMenghitungYangSudahDiproses(): void
    {
        $d=$this->service()->ringkasan();
        self::assertSame(2,$d['gen']);
        self::assertSame(2,$d['mapel']);
        self::assertSame(2,$d['guru_ditugaskan']);
        self::assertSame(2,$d['belum_ditugaskan']);
        self::assertSame(3,$d['akun_aktif']);
        self::assertSame(2,$d['siswa']);
        self::assertSame(1,$d['tp_siap']);
        self::assertSame(2,$d['tp_draf']);
        self::assertSame(1,$d['foto_menunggu']);
        self::assertSame(1,$d['sertifikat_berlaku']);
        self::assertSame('belum_aktif',$d['ttd_status']);
        self::assertSame('belum_diperiksa',$d['cron_status']);
        self::assertSame('belum_diperiksa',$d['backup_status']);
        self::assertCount(3,$d['perhatian']);
    }

    public function testTandaTanganTercatatTapiBerkasHilangDiberiPeringatan(): void
    {
        $this->db->table('sertifikat_aset')->insert([
            'id'=>501,'jenis'=>'ttd',
            'nama_acak'=>str_repeat('a',48),
            'mime'=>'image/png','sha256'=>str_repeat('b',64),
        ]);
        $this->db->table('sertifikat_aset_aktif')->insert([
            'jenis'=>'ttd','aset_id'=>501,
        ]);
        $d=$this->service()->ringkasan();
        self::assertSame('berkas_tidak_valid',$d['ttd_status']);
        self::assertStringContainsString('checksum',$d['perhatian'][2]['pesan']);
    }

    public function testTandaTanganDenganChecksumCocokDinilaiSiap(): void
    {
        $dir=\App\Services\SertifikatAsetService::direktori();
        if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) {
            self::fail('Folder privat sementara tidak tersedia.');
        }
        $nama=bin2hex(random_bytes(24));
        $file=$dir.DIRECTORY_SEPARATOR.$nama.'.png';
        $bytes="fixture-anonim-ttd\x0a";
        self::assertSame(strlen($bytes),file_put_contents($file,$bytes));
        try {
            $this->db->table('sertifikat_aset')->insert([
                'id'=>502,'jenis'=>'ttd','nama_acak'=>$nama,
                'mime'=>'image/png','sha256'=>hash('sha256',$bytes),
            ]);
            $this->db->table('sertifikat_aset_aktif')->insert([
                'jenis'=>'ttd','aset_id'=>502,
            ]);
            $d=$this->service()->ringkasan();
            self::assertSame('siap',$d['ttd_status']);
            self::assertCount(2,$d['perhatian']);
        } finally {
            @unlink($file);
        }
    }

    public function testSemuaTindakanAdminTuntasTidakMengarangKesehatanServer(): void
    {
        $this->db->table('penugasan_guru_gen')->where('id',2)
            ->update(['guru_id'=>14]);
        $this->db->table('penugasan_guru_gen')->insert([
            'id'=>4,'angkatan_id'=>2,'mapel_id'=>2,'guru_id'=>14,
        ]);
        $this->db->table('profil_pengelola')->where('pengguna_id',12)
            ->update(['foto_menunggu_id'=>null]);
        // TTD belum aktif tetap menjadi peringatan meski angka lain beres.
        $d=$this->service()->ringkasan();
        self::assertSame(0,$d['belum_ditugaskan']);
        self::assertSame(0,$d['foto_menunggu']);
        self::assertSame('belum_aktif',$d['ttd_status']);
        self::assertCount(1,$d['perhatian']);
        self::assertSame('belum_diperiksa',$d['cron_status']);
    }

    public function testRoleLainDanAdminNonaktifDitolak(): void
    {
        foreach ([self::akun('guru'),self::akun('pimpinan'),
            self::akun('siswa'),self::akun('admin','nonaktif')] as $akun) {
            try {
                $this->service($akun)->ringkasan();
                self::fail('Role harus ditolak.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('Admin aktif',$e->getMessage());
            }
        }
    }

    public function testDashboardTidakMengubahData(): void
    {
        $sebelum=$this->db->table('foto_profil')->countAllResults();
        $this->service()->ringkasan();
        self::assertSame($sebelum,$this->db->table('foto_profil')->countAllResults());
        self::assertSame('menunggu',$this->db->table('foto_profil')
            ->where('id',301)->get()->getRow('status'));
    }
}
