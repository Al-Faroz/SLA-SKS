<?php

declare(strict_types=1);

use App\Services\LaporanAkademikService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** A10-1: DB SQLite sintetis, tanpa nama/identitas asli dan tanpa mutasi XAMPP. */
final class LaporanAkademikDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db=\Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,'foreignKeys'=>false,
            'busyTimeout'=>1000,'synchronous'=>null,'failover'=>[],
        ],false);
        foreach ([
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT,semester_mulai INTEGER)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT)',
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT,status TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER,semester_mulai INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER,status TEXT)',
            'CREATE TABLE penugasan_paket(id INTEGER PRIMARY KEY,paket_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,nomor_bab INTEGER,nama_bab TEXT)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,bab_id INTEGER,jenis_tp TEXT,status TEXT,kode_tp TEXT,nama_tp TEXT,judul TEXT,urutan_atp INTEGER)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,siswa_id INTEGER,paket_semester_id INTEGER,status TEXT)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jalur TEXT,nilai_resume REAL,nilai_hots REAL,nilai_sumatif REAL,nilai_tp REAL,status_nilai TEXT)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,ujian_pra_semester_id INTEGER,siswa_id INTEGER,jenis TEXT,tahap TEXT,status TEXT,skor REAL,kkm INTEGER)',
            'CREATE TABLE ujian_pra_semester(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,kkm_khusus INTEGER,status TEXT)',
            'CREATE TABLE pengaturan(kunci TEXT PRIMARY KEY,nilai TEXT)',
        ] as $sql) $this->db->query($sql);
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'GEN-A','nama'=>'Gen Akademik A','semester_mulai'=>4],
            ['id'=>2,'kode'=>'GEN-B','nama'=>'Gen Akademik B','semester_mulai'=>4],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>1,'kode'=>'SKI','nama'=>'Mapel Contoh']);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>101,'nama'=>'Siswa Fiktif A','status'=>'aktif'],
            ['id'=>102,'nama'=>'Siswa Fiktif B','status'=>'aktif'],
            ['id'=>103,'nama'=>'Siswa Fiktif C','status'=>'aktif'],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>1,'pengguna_id'=>101,'angkatan_id'=>1,'semester_mulai'=>4],
            ['id'=>2,'pengguna_id'=>102,'angkatan_id'=>1,'semester_mulai'=>5],
            ['id'=>3,'pengguna_id'=>103,'angkatan_id'=>2,'semester_mulai'=>4],
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,'status'=>'aktif'],
            ['id'=>12,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>5,'tahun_ajaran_id'=>1,'status'=>'aktif'],
            ['id'=>13,'angkatan_id'=>2,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,'status'=>'aktif'],
        ]);
        $this->db->table('penugasan_paket')->insertBatch([
            ['id'=>1,'paket_id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1],
            ['id'=>2,'paket_id'=>12,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>5,'tahun_ajaran_id'=>1],
            ['id'=>3,'paket_id'=>13,'angkatan_id'=>2,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1],
        ]);
        $this->db->table('bab')->insertBatch([
            ['id'=>31,'paket_semester_id'=>11,'nomor_bab'=>1,'nama_bab'=>'BAB Contoh'],
            ['id'=>32,'paket_semester_id'=>12,'nomor_bab'=>2,'nama_bab'=>'BAB Kedua'],
            ['id'=>33,'paket_semester_id'=>13,'nomor_bab'=>1,'nama_bab'=>'BAB Lain'],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>201,'paket_semester_id'=>11,'bab_id'=>31,'jenis_tp'=>'non_esensial','status'=>'siap','kode_tp'=>'A.1','nama_tp'=>'TP 1','judul'=>'TP 1','urutan_atp'=>1],
            ['id'=>202,'paket_semester_id'=>11,'bab_id'=>31,'jenis_tp'=>'non_esensial','status'=>'siap','kode_tp'=>'A.2','nama_tp'=>'TP 2','judul'=>'TP 2','urutan_atp'=>2],
            ['id'=>203,'paket_semester_id'=>11,'bab_id'=>31,'jenis_tp'=>'non_esensial','status'=>'siap','kode_tp'=>'A.3','nama_tp'=>'TP 3','judul'=>'TP 3','urutan_atp'=>3],
            ['id'=>204,'paket_semester_id'=>11,'bab_id'=>31,'jenis_tp'=>'esensial','status'=>'siap','kode_tp'=>'A.4','nama_tp'=>'TP Esensial','judul'=>'TP Esensial','urutan_atp'=>4],
            ['id'=>205,'paket_semester_id'=>12,'bab_id'=>32,'jenis_tp'=>'non_esensial','status'=>'siap','kode_tp'=>'B.1','nama_tp'=>'TP Baru','judul'=>'TP Baru','urutan_atp'=>1],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>51,'tp_id'=>201,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'tuntas'],
            ['id'=>52,'tp_id'=>202,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'tuntas'],
            ['id'=>53,'tp_id'=>203,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'tuntas'],
        ]);
        $this->db->table('nilai_tp')->insertBatch([
            ['id'=>1,'progres_tp_id'=>51,'jalur'=>'lulus_diagnostik','nilai_resume'=>null,'nilai_hots'=>null,
                'nilai_sumatif'=>0,'nilai_tp'=>0,'status_nilai'=>'final'],
            ['id'=>2,'progres_tp_id'=>52,'jalur'=>'belajar_wajib','nilai_resume'=>80,'nilai_hots'=>null,
                'nilai_sumatif'=>80,'nilai_tp'=>null,'status_nilai'=>'sementara'],
        ]);
        $this->db->table('percobaan_ujian')->insertBatch([
            ['id'=>101,'progres_tp_id'=>51,'ujian_pra_semester_id'=>null,'siswa_id'=>null,'jenis'=>'diagnostik','tahap'=>'normal','status'=>'selesai_sah','skor'=>0,'kkm'=>75],
            ['id'=>102,'progres_tp_id'=>51,'ujian_pra_semester_id'=>null,'siswa_id'=>null,'jenis'=>'sumatif','tahap'=>'normal','status'=>'selesai_sah','skor'=>0,'kkm'=>75],
            ['id'=>103,'progres_tp_id'=>52,'ujian_pra_semester_id'=>null,'siswa_id'=>null,'jenis'=>'sumatif','tahap'=>'normal','status'=>'selesai_sah','skor'=>20,'kkm'=>75],
            ['id'=>104,'progres_tp_id'=>52,'ujian_pra_semester_id'=>null,'siswa_id'=>null,'jenis'=>'sumatif','tahap'=>'remedial','status'=>'selesai_sah','skor'=>80,'kkm'=>75],
            ['id'=>105,'progres_tp_id'=>53,'ujian_pra_semester_id'=>null,'siswa_id'=>null,'jenis'=>'sumatif','tahap'=>'normal','status'=>'berjalan','skor'=>null,'kkm'=>75],
            ['id'=>106,'progres_tp_id'=>null,'ujian_pra_semester_id'=>301,'siswa_id'=>1,'jenis'=>'pra_semester','tahap'=>'normal','status'=>'selesai_sah','skor'=>0,'kkm'=>88],
            ['id'=>107,'progres_tp_id'=>null,'ujian_pra_semester_id'=>301,'siswa_id'=>1,'jenis'=>'pra_semester','tahap'=>'remedial','status'=>'selesai_sah','skor'=>70,'kkm'=>88],
        ]);
        $this->db->table('ujian_pra_semester')->insert([
            'id'=>301,'paket_semester_id'=>11,'kkm_khusus'=>88,'status'=>'siap',
        ]);
        $this->db->table('pengaturan')->insert(['kunci'=>'kkm_default','nilai'=>'75']);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function aktor(string $role='pimpinan',int $id=501): callable
    {
        return static fn (): array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ];
    }

    public function testMonitoringPerluSumatifSahDanMelewatiSemesterAwal(): void
    {
        $svc=new LaporanAkademikService($this->db,$this->aktor());
        self::assertCount(3,$svc->paketPimpinan());
        $r=$svc->monitoring(11);
        self::assertSame(2,$r['total']);
        self::assertSame(3,$r['total_tp']);
        self::assertSame(2,$r['siswa'][0]['selesai']);
        self::assertEqualsWithDelta(66.7,$r['siswa'][0]['persen'],0.1);
        self::assertTrue($r['siswa'][1]['dilewati']);
        self::assertNull($r['siswa'][1]['persen']);
    }

    public function testLaporanTpNolRemedialDanPraTerpisah(): void
    {
        $svc=new LaporanAkademikService($this->db,$this->aktor());
        $r=$svc->laporan(1,11);
        self::assertSame(3,$r['ringkasan']['jumlah_tp']);
        self::assertSame(2,$r['ringkasan']['selesai']);
        self::assertEqualsWithDelta(66.7,$r['ringkasan']['persen'],0.1);
        self::assertSame(1,$r['ringkasan']['final']);
        self::assertSame(1,$r['ringkasan']['sementara']);
        self::assertSame(0.0,(float)$r['tp'][0]['nilai_tp']);
        self::assertSame(0.0,$r['tp'][0]['skor_diagnostik']);
        self::assertSame(20.0,$r['tp'][1]['skor_sumatif_awal']);
        self::assertSame(80.0,$r['tp'][1]['skor_sumatif_remedial']);
        self::assertSame('Esensial — tanpa nilai',$r['tp'][3]['status_laporan']);
        self::assertNull($r['tp'][3]['nilai_tp']);
        self::assertSame(0.0,$r['pra']['skor_normal']);
        self::assertSame(70.0,$r['pra']['nilai_terbaik']);
        self::assertSame('final',$r['pra']['status_nilai']);
        self::assertSame(88,$r['pra']['kkm']);
    }

    public function testSiswaHanyaBolehMembukaLaporanSendiriDanSemesterTersedia(): void
    {
        $svc=new LaporanAkademikService($this->db,$this->aktor('siswa',101));
        $daftar=$svc->daftarLaporanSendiri();
        self::assertCount(1,$daftar);
        self::assertSame(11,$daftar[0]['paket_id']);
        self::assertSame(1,(int)$svc->laporanSendiri(11)['siswa']['id']);
        try {
            $svc->laporan(2,11);
            self::fail('Siswa tidak boleh membuka laporan siswa lain.');
        } catch (\DomainException $e) {
            self::assertSame('Laporan tidak ditemukan.',$e->getMessage());
        }
        $this->expectException(\DomainException::class);
        $svc->laporanSendiri(12);
    }

    public function testPimpinanTidakBolehMelihatSiswaGenLainDalamPaket(): void
    {
        $svc=new LaporanAkademikService($this->db,$this->aktor());
        $this->expectException(\DomainException::class);
        $svc->laporan(3,11);
    }

    public function testGuruAdminDanPeranTidakAktifTidakDapatMembaca(): void
    {
        foreach (['guru','admin'] as $role) {
            $svc=new LaporanAkademikService($this->db,$this->aktor($role));
            try {
                $svc->laporan(1,11);
                self::fail('Role lain tidak berhak membaca laporan.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('Laporan hanya tersedia',$e->getMessage());
            }
        }
        $svc=new LaporanAkademikService($this->db,static fn (): array => [
            'id'=>501,'peran_aktif'=>'pimpinan','status'=>'nonaktif','wajib_ganti_sandi'=>0,
        ]);
        $this->expectException(\DomainException::class);
        $svc->paketPimpinan();
    }

    public function testPraTanpaSkorSahBerbedaDenganNol(): void
    {
        $this->db->table('percobaan_ujian')
            ->where('ujian_pra_semester_id',301)->delete();
        $this->db->table('percobaan_ujian')->insert([
            'id'=>108,'progres_tp_id'=>null,'ujian_pra_semester_id'=>301,
            'siswa_id'=>1,'jenis'=>'pra_semester','tahap'=>'normal',
            'status'=>'dibatalkan_menunggu_guru','skor'=>null,'kkm'=>88,
        ]);
        $r=(new LaporanAkademikService($this->db,$this->aktor()))->laporan(1,11);
        self::assertNull($r['pra']['nilai_terbaik']);
        self::assertNull($r['pra']['status_nilai']);
        self::assertSame('butuh_susulan_normal',$r['pra']['fase']);
    }
}
