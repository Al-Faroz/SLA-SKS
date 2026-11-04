<?php

declare(strict_types=1);

use App\Services\DashboardGuruService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** Semua pengujian memakai SQLite sementara, bukan DB siswa XAMPP. */
final class DashboardGuruDatabaseTest extends TestCase
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
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT,semester_mulai INTEGER)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,guru_id INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,status TEXT)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,jenis_tp TEXT,status TEXT)',
            'CREATE TABLE materi(id INTEGER PRIMARY KEY,tp_id INTEGER,aktif INTEGER)',
            'CREATE TABLE versi_konten_tp(id INTEGER PRIMARY KEY,tp_id INTEGER)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,angkatan_id INTEGER)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,tp_id INTEGER,siswa_id INTEGER,versi_konten_tp_id INTEGER,status TEXT)',
            'CREATE TABLE resume_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,materi_id INTEGER,versi_konten_tp_id INTEGER,status TEXT,nilai REAL,dinilai_oleh INTEGER)',
            'CREATE TABLE jawaban_hots_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,materi_id INTEGER,versi_konten_tp_id INTEGER,status TEXT,nilai REAL,dinilai_oleh INTEGER)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jenis TEXT,status TEXT)',
        ] as $sql) $this->db->query($sql);
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'G1','nama'=>'Gen A','semester_mulai'=>4],
            ['id'=>2,'kode'=>'G2','nama'=>'Gen B','semester_mulai'=>4],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>1,'kode'=>'SIM','nama'=>'Mapel Simulasi']);
        $this->db->table('penugasan_guru_gen')->insertBatch([
            ['id'=>1,'angkatan_id'=>1,'mapel_id'=>1,'guru_id'=>99],
            ['id'=>2,'angkatan_id'=>2,'mapel_id'=>1,'guru_id'=>88],
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'status'=>'aktif'],
            ['id'=>12,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>5,'status'=>'aktif'],
            ['id'=>13,'angkatan_id'=>2,'mapel_id'=>1,'semester'=>4,'status'=>'aktif'],
            ['id'=>14,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>6,'status'=>'draf'],
        ]);
        $this->db->table('bab')->insertBatch([
            ['id'=>1,'paket_semester_id'=>11],
            ['id'=>2,'paket_semester_id'=>11],
            ['id'=>3,'paket_semester_id'=>12],
            ['id'=>4,'paket_semester_id'=>13],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>301,'paket_semester_id'=>11,'jenis_tp'=>'non_esensial','status'=>'draf'],
            ['id'=>302,'paket_semester_id'=>11,'jenis_tp'=>'non_esensial','status'=>'siap'],
            ['id'=>303,'paket_semester_id'=>11,'jenis_tp'=>'esensial','status'=>'siap'],
            ['id'=>304,'paket_semester_id'=>11,'jenis_tp'=>'non_esensial','status'=>'siap'],
            ['id'=>305,'paket_semester_id'=>12,'jenis_tp'=>'esensial','status'=>'draf'],
            ['id'=>306,'paket_semester_id'=>12,'jenis_tp'=>'non_esensial','status'=>'arsip'],
            ['id'=>307,'paket_semester_id'=>13,'jenis_tp'=>'non_esensial','status'=>'draf'],
        ]);
        $this->db->table('materi')->insertBatch([
            ['id'=>501,'tp_id'=>302,'aktif'=>1],
            ['id'=>502,'tp_id'=>303,'aktif'=>1],
            ['id'=>503,'tp_id'=>304,'aktif'=>1],
            ['id'=>504,'tp_id'=>304,'aktif'=>1],
            ['id'=>505,'tp_id'=>301,'aktif'=>0],
            ['id'=>506,'tp_id'=>307,'aktif'=>1],
        ]);
        $this->db->table('versi_konten_tp')->insertBatch([
            ['id'=>701,'tp_id'=>302],['id'=>702,'tp_id'=>304],['id'=>703,'tp_id'=>307],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>101,'angkatan_id'=>1],
            ['id'=>102,'angkatan_id'=>1],
            ['id'=>201,'angkatan_id'=>2],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>401,'paket_semester_id'=>11,'tp_id'=>302,'siswa_id'=>101,'versi_konten_tp_id'=>701,'status'=>'perlu_bantuan'],
            ['id'=>402,'paket_semester_id'=>11,'tp_id'=>304,'siswa_id'=>101,'versi_konten_tp_id'=>702,'status'=>'belajar'],
            ['id'=>403,'paket_semester_id'=>11,'tp_id'=>302,'siswa_id'=>102,'versi_konten_tp_id'=>701,'status'=>'perlu_bantuan'],
            ['id'=>404,'paket_semester_id'=>13,'tp_id'=>307,'siswa_id'=>201,'versi_konten_tp_id'=>703,'status'=>'perlu_bantuan'],
            ['id'=>405,'paket_semester_id'=>11,'tp_id'=>302,'siswa_id'=>201,'versi_konten_tp_id'=>701,'status'=>'perlu_bantuan'],
        ]);
        $this->db->table('resume_materi')->insertBatch([
            ['id'=>801,'progres_tp_id'=>401,'materi_id'=>501,'versi_konten_tp_id'=>701,'status'=>'final','nilai'=>null,'dinilai_oleh'=>null],
            ['id'=>802,'progres_tp_id'=>402,'materi_id'=>503,'versi_konten_tp_id'=>702,'status'=>'final','nilai'=>0,'dinilai_oleh'=>99],
            ['id'=>803,'progres_tp_id'=>403,'materi_id'=>501,'versi_konten_tp_id'=>701,'status'=>'draf','nilai'=>null,'dinilai_oleh'=>null],
            ['id'=>804,'progres_tp_id'=>405,'materi_id'=>501,'versi_konten_tp_id'=>701,'status'=>'final','nilai'=>null,'dinilai_oleh'=>null],
            ['id'=>805,'progres_tp_id'=>401,'materi_id'=>501,'versi_konten_tp_id'=>702,'status'=>'final','nilai'=>null,'dinilai_oleh'=>null],
        ]);
        $this->db->table('jawaban_hots_materi')->insertBatch([
            ['id'=>811,'progres_tp_id'=>401,'materi_id'=>501,'versi_konten_tp_id'=>701,'status'=>'final','nilai'=>null,'dinilai_oleh'=>null],
            ['id'=>812,'progres_tp_id'=>402,'materi_id'=>503,'versi_konten_tp_id'=>702,'status'=>'final','nilai'=>80,'dinilai_oleh'=>99],
        ]);
        $this->db->table('percobaan_ujian')->insertBatch([
            ['id'=>901,'progres_tp_id'=>401,'jenis'=>'diagnostik','status'=>'berjalan'],
            ['id'=>902,'progres_tp_id'=>401,'jenis'=>'diagnostik','status'=>'selesai_sah'],
            ['id'=>903,'progres_tp_id'=>402,'jenis'=>'sumatif','status'=>'berjalan'],
            ['id'=>904,'progres_tp_id'=>404,'jenis'=>'sumatif','status'=>'berjalan'],
            ['id'=>905,'progres_tp_id'=>405,'jenis'=>'sumatif','status'=>'berjalan'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private static function akun(int $id=99,string $peran='guru',string $status='aktif'): callable
    {
        return static fn (): array => [
            'id'=>$id,'peran_aktif'=>$peran,'status'=>$status,
            'wajib_ganti_sandi'=>0,
        ];
    }

    private function servis(?callable $auth=null): DashboardGuruService
    {
        return new DashboardGuruService($this->db,$auth??self::akun());
    }

    public function testRingkasanG1TidakMembocorkanGenLain(): void
    {
        $d=$this->servis()->ringkasan(0);
        self::assertSame(1,count($d['pilihan']));
        self::assertSame(2,$d['ringkasan']['paket']);
        self::assertSame(3,$d['ringkasan']['bab']);
        self::assertSame(5,$d['ringkasan']['tp_total']);
        self::assertSame(3,$d['ringkasan']['tp_siap']);
        self::assertSame(2,$d['ringkasan']['tp_draf']);
        self::assertSame(3,$d['ringkasan']['tp_tanpa_materi']);
        self::assertSame(1,$d['ringkasan']['antrean_resume']);
        self::assertSame(1,$d['ringkasan']['antrean_hots']);
        self::assertSame(2,$d['ringkasan']['perlu_bantuan']);
        self::assertSame(2,$d['ringkasan']['ujian_berjalan']);
        self::assertCount(2,$d['baris']);
    }

    public function testFilterSemesterDanTugasBerstatusNolTidakJadiAntrean(): void
    {
        $d=$this->servis()->ringkasan(0,5);
        self::assertSame(1,$d['ringkasan']['paket']);
        self::assertSame(1,$d['ringkasan']['tp_total']);
        self::assertSame(0,$d['ringkasan']['antrean_resume']);
        self::assertSame(0,$d['ringkasan']['ujian_berjalan']);
        self::assertSame(5,$d['baris'][0]['semester']);
    }

    public function testTidakAdaPaketAktifPadaSemesterEnam(): void
    {
        $d=$this->servis()->ringkasan(0,6);
        self::assertSame([],$d['baris']);
        self::assertSame(0,$d['ringkasan']['paket']);
    }

    public function testPenugasanLainTidakBisaDipilihDenganIndexPalsu(): void
    {
        $this->expectException(\DomainException::class);
        $this->servis()->ringkasan(1);
    }

    public function testBukanGuruAtauGuruNonaktifDitolak(): void
    {
        foreach ([self::akun(99,'pimpinan'),self::akun(99,'guru','nonaktif')] as $user) {
            try {
                $this->servis($user)->ringkasan();
                self::fail('Peran tidak sah mesti ditolak.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('Guru aktif',$e->getMessage());
            }
        }
    }

    public function testPenggantianPenugasanTidakMembukaDataGuruSebelumnya(): void
    {
        $this->db->table('penugasan_guru_gen')->where('id',1)->update(['guru_id'=>88]);
        $data=$this->servis()->ringkasan(0);
        self::assertSame([], $data['pilihan']);
        self::assertSame([], $data['baris']);
        self::assertNull($data['terpilih']);
    }
}
