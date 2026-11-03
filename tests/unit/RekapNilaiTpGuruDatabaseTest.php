<?php

declare(strict_types=1);

use App\Services\RekapNilaiTpGuruService;
use PHPUnit\Framework\TestCase;
use CodeIgniter\Database\BaseConnection;

/** Fixture sintetis in-memory; tidak mengambil data siswa dan nilai XAMPP. */
final class RekapNilaiTpGuruDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,
            'foreignKeys'=>false,'busyTimeout'=>1000,'synchronous'=>null,'failover'=>[],
        ], false);
        foreach ([
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT,semester_mulai INTEGER)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,guru_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,nomor_bab INTEGER,nama_bab TEXT)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,bab_id INTEGER,kode_tp TEXT,judul TEXT,nama_tp TEXT,jenis_tp TEXT,status TEXT,urutan_dalam_bab INTEGER)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,siswa_id INTEGER,tp_id INTEGER,paket_semester_id INTEGER,status TEXT)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY,progres_tp_id INTEGER UNIQUE,jalur TEXT,nilai_resume REAL,nilai_hots REAL,nilai_sumatif REAL,nilai_tp REAL,status_nilai TEXT)',
        ] as $ddl) $this->db->query($ddl);
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'GENA','nama'=>'Gen A','semester_mulai'=>1],
            ['id'=>2,'kode'=>'GENB','nama'=>'Gen B','semester_mulai'=>1],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>7,'kode'=>'SKI','nama'=>'SKI']);
        $this->db->table('penugasan_guru_gen')->insertBatch([
            ['id'=>1,'guru_id'=>4,'angkatan_id'=>1,'mapel_id'=>7],
            ['id'=>2,'guru_id'=>5,'angkatan_id'=>2,'mapel_id'=>7],
        ]);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>101,'nama'=>'Siswa A1'],['id'=>102,'nama'=>'Siswa A2'],
            ['id'=>201,'nama'=>'Siswa B1'],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>11,'pengguna_id'=>101,'angkatan_id'=>1],
            ['id'=>12,'pengguna_id'=>102,'angkatan_id'=>1],
            ['id'=>21,'pengguna_id'=>201,'angkatan_id'=>2],
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>31,'angkatan_id'=>1,'mapel_id'=>7,'semester'=>1],
            ['id'=>41,'angkatan_id'=>2,'mapel_id'=>7,'semester'=>1],
        ]);
        $this->db->table('bab')->insertBatch([
            ['id'=>61,'paket_semester_id'=>31,'nomor_bab'=>1,'nama_bab'=>'Periode A'],
            ['id'=>71,'paket_semester_id'=>41,'nomor_bab'=>1,'nama_bab'=>'Periode B'],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>1,'paket_semester_id'=>31,'bab_id'=>61,'kode_tp'=>'SKI.7.1.1',
                'judul'=>'TP A1','nama_tp'=>'TP A1','jenis_tp'=>'non_esensial','status'=>'siap','urutan_dalam_bab'=>1],
            ['id'=>2,'paket_semester_id'=>31,'bab_id'=>61,'kode_tp'=>'SKI.7.1.2',
                'judul'=>'TP A2','nama_tp'=>'TP A2','jenis_tp'=>'non_esensial','status'=>'siap','urutan_dalam_bab'=>2],
            ['id'=>3,'paket_semester_id'=>31,'bab_id'=>61,'kode_tp'=>'SKI.7.1.3',
                'judul'=>'TP A3','nama_tp'=>'TP A3','jenis_tp'=>'esensial','status'=>'siap','urutan_dalam_bab'=>3],
            ['id'=>4,'paket_semester_id'=>41,'bab_id'=>71,'kode_tp'=>'SKI.7.1.1',
                'judul'=>'TP B1','nama_tp'=>'TP B1','jenis_tp'=>'non_esensial','status'=>'siap','urutan_dalam_bab'=>1],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>501,'siswa_id'=>11,'tp_id'=>1,'paket_semester_id'=>31,'status'=>'tuntas'],
            ['id'=>502,'siswa_id'=>11,'tp_id'=>2,'paket_semester_id'=>31,'status'=>'tuntas'],
            ['id'=>503,'siswa_id'=>11,'tp_id'=>3,'paket_semester_id'=>31,'status'=>'tuntas'],
            ['id'=>601,'siswa_id'=>21,'tp_id'=>4,'paket_semester_id'=>41,'status'=>'tuntas'],
        ]);
        $this->db->table('nilai_tp')->insertBatch([
            ['id'=>1001,'progres_tp_id'=>501,'jalur'=>'lulus_diagnostik',
                'nilai_resume'=>null,'nilai_hots'=>null,'nilai_sumatif'=>0.0,
                'nilai_tp'=>0.0,'status_nilai'=>'final'],
            ['id'=>1002,'progres_tp_id'=>502,'jalur'=>'belajar_wajib',
                'nilai_resume'=>80.0,'nilai_hots'=>null,'nilai_sumatif'=>70.0,
                'nilai_tp'=>null,'status_nilai'=>'sementara'],
            ['id'=>1003,'progres_tp_id'=>503,'jalur'=>'belajar_wajib',
                'nilai_resume'=>95.0,'nilai_hots'=>95.0,'nilai_sumatif'=>95.0,
                'nilai_tp'=>95.0,'status_nilai'=>'final'],
            ['id'=>2001,'progres_tp_id'=>601,'jalur'=>'belajar_wajib',
                'nilai_resume'=>99.0,'nilai_hots'=>99.0,'nilai_sumatif'=>99.0,
                'nilai_tp'=>99.0,'status_nilai'=>'final'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function guru(int $id=4,string $role='guru'): RekapNilaiTpGuruService
    {
        return new RekapNilaiTpGuruService($this->db, static fn (): array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    public function testSeluruhSiswaGenDanTpTermasukEsensialTanpaBocorGenLain(): void
    {
        $svc = $this->guru();
        $pilihan = $svc->penugasan();
        self::assertCount(1, $pilihan);
        self::assertSame('Gen A', $pilihan[0]['nama_gen']);
        $hasil = $svc->rekap($pilihan[0],1);
        self::assertSame(6,$hasil['total']);
        self::assertSame(2,$hasil['jumlah_siswa']);
        self::assertSame(1,$hasil['jumlah_final']);
        self::assertSame(1,$hasil['jumlah_sementara']);
        self::assertSame(2,$hasil['jumlah_belum']);
        self::assertCount(6,$hasil['baris']);
        foreach ($hasil['baris'] as $row) {
            self::assertNotSame('Siswa B1',$row['nama_siswa']);
            self::assertNotSame(4,(int)$row['tp_id']);
        }
        $esensial = array_values(array_filter($hasil['baris'],
            static fn (array $r): bool => $r['jenis_tp'] === 'esensial'));
        self::assertCount(2,$esensial);
        foreach ($esensial as $r) {
            self::assertNull($r['nilai_tp']);
            self::assertSame('Tanpa nilai (Esensial)',
                RekapNilaiTpGuruService::labelNilai($r['status_nilai'],true));
            self::assertSame('—',RekapNilaiTpGuruService::teksKomponen($r,'nilai_tp'));
        }
    }

    public function testNilaiNolBukanBelumAdaDanNilaiSementaraTetapNull(): void
    {
        $hasil = $this->guru()->rekap(['angkatan_id'=>1,'mapel_id'=>7],1);
        $nilai0 = array_values(array_filter($hasil['baris'],
            static fn (array $r): bool => $r['siswa_id']==11 && $r['tp_id']==1))[0];
        self::assertSame('0,00',RekapNilaiTpGuruService::teksKomponen($nilai0,'nilai_tp'));
        self::assertSame('Dilewati',RekapNilaiTpGuruService::teksKomponen($nilai0,'nilai_hots'));
        self::assertSame('Final',RekapNilaiTpGuruService::labelNilai($nilai0['status_nilai'],false));

        $sementara = array_values(array_filter($hasil['baris'],
            static fn (array $r): bool => $r['siswa_id']==11 && $r['tp_id']==2))[0];
        self::assertSame('Sementara',
            RekapNilaiTpGuruService::labelNilai($sementara['status_nilai'],false));
        self::assertSame('Belum ada',RekapNilaiTpGuruService::teksKomponen($sementara,'nilai_hots'));
        self::assertSame('Belum ada',RekapNilaiTpGuruService::teksKomponen($sementara,'nilai_tp'));
        self::assertSame('70,00',RekapNilaiTpGuruService::teksKomponen($sementara,'nilai_sumatif'));
    }

    public function testAksesGenAsingDanPeranAdminDitolak(): void
    {
        try {
            $this->guru()->rekap(['angkatan_id'=>2,'mapel_id'=>7],1);
            self::fail('Tidak boleh membaca Gen B dari Guru Gen A.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('tidak ditugaskan',$e->getMessage());
        }
        $this->expectException(\DomainException::class);
        $this->guru(4,'admin')->rekap(['angkatan_id'=>1,'mapel_id'=>7],1);
    }

    public function testSemesterKosongDanValidasiHalaman(): void
    {
        $data=$this->guru()->rekap(['angkatan_id'=>1,'mapel_id'=>7],6);
        self::assertSame(0,$data['total']);
        self::assertSame([],$data['baris']);
        self::assertSame(2,$data['jumlah_siswa']);
        $this->expectException(\DomainException::class);
        $this->guru()->rekap(['angkatan_id'=>1,'mapel_id'=>7],7);
    }

    public function testPaginasiMaksimalDuaPuluhLimaBarisDanTidakMelintasiGen(): void
    {
        $names = [];
        $students = [];
        for ($i=1;$i<=12;$i++) {
            $names[] = ['id'=>300+$i,'nama'=>'Siswa Tambahan '.$i];
            $students[] = ['id'=>400+$i,'pengguna_id'=>300+$i,'angkatan_id'=>1];
        }
        $this->db->table('pengguna')->insertBatch($names);
        $this->db->table('siswa')->insertBatch($students);
        $svc=$this->guru();
        $halaman1=$svc->rekap(['angkatan_id'=>1,'mapel_id'=>7],1,1);
        $halaman2=$svc->rekap(['angkatan_id'=>1,'mapel_id'=>7],1,2);
        self::assertSame(42,$halaman1['total']);
        self::assertSame(2,$halaman1['jumlah_halaman']);
        self::assertCount(25,$halaman1['baris']);
        self::assertCount(17,$halaman2['baris']);
        self::assertSame(14,$halaman2['jumlah_siswa']);
        self::assertSame(42,$halaman1['jumlah_final']+$halaman1['jumlah_sementara']
            +$halaman1['jumlah_belum']+14); // setiap siswa punya 1 TP Esensial
    }

    public function testRouteNavbarDanViewBenarBenarTerhubung(): void
    {
        $root=dirname(__DIR__,2);
        $route=file_get_contents($root.'/app/Config/Routes.php');
        $nav=file_get_contents($root.'/app/Services/NavigasiService.php');
        $view=file_get_contents($root.'/app/Views/guru/rekap_nilai_tp.php');
        self::assertIsString($route);
        self::assertIsString($nav);
        self::assertIsString($view);
        self::assertStringContainsString("'GuruRekapNilai::index'", $route);
        self::assertStringContainsString("'auth:guru'", $route);
        self::assertStringContainsString("'label'=>'Rekap Nilai'", $nav);
        self::assertStringContainsString("'label'=>'Nilai TP'", $nav);
        self::assertStringContainsString("'label'=>'Nilai Pra-Semester'", $nav);
        self::assertStringContainsString('teksKomponen', $view);
        self::assertStringNotContainsString('Nilai Akhir Semester', $view);
    }
}
