<?php

declare(strict_types=1);

use App\Services\DashboardPimpinanService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** A12-1: data akademik sintetis, tidak mengakses database lokal pengguna. */
final class DashboardPimpinanDatabaseTest extends TestCase
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
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,nama TEXT,semester_mulai INTEGER)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,angkatan_id INTEGER,semester_mulai INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER,status TEXT)',
            'CREATE TABLE penugasan_paket(id INTEGER PRIMARY KEY,paket_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,jenis_tp TEXT,status TEXT)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,siswa_id INTEGER,tp_id INTEGER,status TEXT)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,status_nilai TEXT,nilai_tp REAL)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jenis TEXT,status TEXT)',
            'CREATE TABLE sertifikat(id INTEGER PRIMARY KEY,status TEXT)',
            'CREATE TABLE sertifikat_aktif(siswa_id INTEGER,paket_semester_id INTEGER,sertifikat_id INTEGER)',
            'CREATE TABLE ujian_pra_semester(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,status TEXT)',
        ] as $sql) $this->db->query($sql);
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'nama'=>'Gen A','semester_mulai'=>4],
            ['id'=>2,'nama'=>'Gen B','semester_mulai'=>5],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>1,'nama'=>'Mapel Contoh']);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,'status'=>'aktif'],
            ['id'=>12,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>5,'tahun_ajaran_id'=>1,'status'=>'aktif'],
            ['id'=>13,'angkatan_id'=>2,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,'status'=>'aktif'],
            // Jangan hitung paket draf meskipun penugasan tersedia.
            ['id'=>14,'angkatan_id'=>2,'mapel_id'=>1,'semester'=>5,'tahun_ajaran_id'=>1,'status'=>'draf'],
        ]);
        foreach ([11,12,13,14] as $id) {
            $gen=$id>=13?2:1;
            $sem=$id===11||$id===13?4:5;
            $this->db->table('penugasan_paket')->insert([
                'paket_id'=>$id,'angkatan_id'=>$gen,'mapel_id'=>1,
                'semester'=>$sem,'tahun_ajaran_id'=>1,
            ]);
        }
        $this->db->table('siswa')->insertBatch([
            ['id'=>101,'angkatan_id'=>1,'semester_mulai'=>null],
            ['id'=>102,'angkatan_id'=>1,'semester_mulai'=>5],
            ['id'=>201,'angkatan_id'=>2,'semester_mulai'=>null],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>301,'paket_semester_id'=>11,'jenis_tp'=>'esensial','status'=>'siap'],
            ['id'=>302,'paket_semester_id'=>11,'jenis_tp'=>'non_esensial','status'=>'siap'],
            ['id'=>303,'paket_semester_id'=>11,'jenis_tp'=>'non_esensial','status'=>'siap'],
            ['id'=>304,'paket_semester_id'=>11,'jenis_tp'=>'non_esensial','status'=>'arsip'],
            ['id'=>305,'paket_semester_id'=>12,'jenis_tp'=>'non_esensial','status'=>'siap'],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>401,'paket_semester_id'=>11,'siswa_id'=>101,'tp_id'=>302,'status'=>'tuntas'],
            ['id'=>402,'paket_semester_id'=>11,'siswa_id'=>101,'tp_id'=>303,'status'=>'tuntas'],
            ['id'=>403,'paket_semester_id'=>12,'siswa_id'=>101,'tp_id'=>305,'status'=>'tuntas'],
            ['id'=>404,'paket_semester_id'=>12,'siswa_id'=>102,'tp_id'=>305,'status'=>'tuntas'],
            // Tuntas pada semester dilewati tidak boleh dihitung.
            ['id'=>405,'paket_semester_id'=>11,'siswa_id'=>102,'tp_id'=>302,'status'=>'tuntas'],
        ]);
        $this->db->table('percobaan_ujian')->insertBatch([
            ['id'=>501,'progres_tp_id'=>401,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>502,'progres_tp_id'=>401,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>503,'progres_tp_id'=>402,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>504,'progres_tp_id'=>403,'jenis'=>'sumatif','status'=>'berjalan'],
            ['id'=>505,'progres_tp_id'=>404,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>506,'progres_tp_id'=>405,'jenis'=>'sumatif','status'=>'selesai_sah'],
        ]);
        $this->db->table('nilai_tp')->insertBatch([
            ['id'=>601,'progres_tp_id'=>401,'status_nilai'=>'final','nilai_tp'=>0],
            ['id'=>602,'progres_tp_id'=>402,'status_nilai'=>'sementara','nilai_tp'=>70],
            ['id'=>603,'progres_tp_id'=>403,'status_nilai'=>'final','nilai_tp'=>100],
            ['id'=>604,'progres_tp_id'=>404,'status_nilai'=>'final','nilai_tp'=>88],
            ['id'=>605,'progres_tp_id'=>405,'status_nilai'=>'final','nilai_tp'=>95],
        ]);
        $this->db->table('sertifikat')->insertBatch([
            ['id'=>701,'status'=>'berlaku'],
            ['id'=>702,'status'=>'digantikan'],
        ]);
        $this->db->table('sertifikat_aktif')->insert([
            'siswa_id'=>101,'paket_semester_id'=>11,'sertifikat_id'=>701,
        ]);
        $this->db->table('ujian_pra_semester')->insertBatch([
            ['id'=>801,'paket_semester_id'=>11,'status'=>'siap'],
            ['id'=>802,'paket_semester_id'=>12,'status'=>'draf'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private static function akun(string $role='pimpinan', string $status='aktif'): callable
    {
        return static fn (): array => [
            'id'=>99,'peran_aktif'=>$role,'status'=>$status,
            'wajib_ganti_sandi'=>0,
        ];
    }

    private function service(?callable $a=null): DashboardPimpinanService
    {
        return new DashboardPimpinanService($this->db,$a??self::akun());
    }

    public function testKpiTidakMenggandakanSumatifDanNilaiNolFinalSah(): void
    {
        $d=$this->service()->ringkasan();
        self::assertSame(3,$d['ringkasan']['paket']);
        self::assertSame(3,$d['ringkasan']['siswa']);
        self::assertSame(3,$d['ringkasan']['tp_wajib']);
        self::assertSame(4,$d['ringkasan']['pasangan_wajib']);
        self::assertSame(3,$d['ringkasan']['tuntas_sah']);
        self::assertSame(2,$d['ringkasan']['nilai_final']);
        self::assertSame(1,$d['ringkasan']['sertifikat_berlaku']);
        self::assertSame(75.0,$d['ringkasan']['persen']);
        self::assertSame(1,$d['ringkasan']['paket_kosong']);
        self::assertSame('siap',$d['baris'][0]['pra_status']);
        self::assertSame(0,$d['baris'][2]['siswa']); // Gen B semester dilewati
        self::assertNull($d['baris'][2]['persen']);
    }

    public function testFilterGenSemesterDanPaginasiSah(): void
    {
        $d=$this->service()->ringkasan(['gen'=>1,'semester'=>4],1,1);
        self::assertSame(1,$d['jumlah']);
        self::assertSame(2,$d['ringkasan']['siswa']);
        self::assertSame(2,$d['ringkasan']['pasangan_wajib']);
        self::assertSame(2,$d['ringkasan']['tuntas_sah']);
        $page2=$this->service()->ringkasan([],2,1);
        self::assertSame(3,$page2['jumlah_halaman']);
        self::assertSame(2,$page2['halaman']);
        self::assertSame(12,(int)$page2['baris'][0]['id']);
    }

    public function testFilterTanpaPaketTidakDianggapTuntas(): void
    {
        $d=$this->service()->ringkasan(['semester'=>1]);
        self::assertSame([], $d['baris']);
        self::assertSame(0,$d['ringkasan']['paket']);
        self::assertNull($d['ringkasan']['persen']);
    }

    public function testPeranGuruDitolakPadaService(): void
    {
        $this->expectException(\DomainException::class);
        $this->service(self::akun('guru'))->ringkasan();
    }

    public function testPimpinanNonaktifDitolakPadaService(): void
    {
        $this->expectException(\DomainException::class);
        $this->service(self::akun('pimpinan','nonaktif'))->ringkasan();
    }

    public function testParameterFilterTidakSahDitolak(): void
    {
        $this->expectException(\DomainException::class);
        $this->service()->ringkasan(['semester'=>7]);
    }
}
