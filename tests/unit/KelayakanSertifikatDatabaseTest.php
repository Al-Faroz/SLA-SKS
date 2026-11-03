<?php

declare(strict_types=1);

use App\Services\KelayakanSertifikatService;
use App\Services\PengaturanService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** A11-1: fixture SQLite anonim. Tidak mengakses database/dump XAMPP. */
final class KelayakanSertifikatDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,'foreignKeys'=>false,
            'busyTimeout'=>1000,'synchronous'=>null,'failover'=>[],
        ],false);
        foreach ([
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,nama TEXT,semester_mulai INTEGER)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER,semester_mulai INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER,status TEXT)',
            'CREATE TABLE penugasan_paket(id INTEGER PRIMARY KEY,paket_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,nomor_bab INTEGER,nama_bab TEXT)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,bab_id INTEGER,jenis_tp TEXT,status TEXT,kode_tp TEXT,nama_tp TEXT,judul TEXT,urutan_atp INTEGER)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,siswa_id INTEGER,paket_semester_id INTEGER,status TEXT)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jalur TEXT,nilai_resume REAL,nilai_hots REAL,nilai_sumatif REAL,nilai_tp REAL,status_nilai TEXT)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jenis TEXT,status TEXT)',
            'CREATE TABLE pengaturan(kunci TEXT PRIMARY KEY,nilai TEXT)',
        ] as $sql) $this->db->query($sql);

        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'nama'=>'Gen A','semester_mulai'=>4],
            ['id'=>2,'nama'=>'Gen B','semester_mulai'=>4],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>1,'nama'=>'Mapel Simulasi']);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,'status'=>'aktif'],
            ['id'=>12,'angkatan_id'=>2,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,'status'=>'aktif'],
            ['id'=>13,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>5,'tahun_ajaran_id'=>2,'status'=>'draf'],
        ]);
        $this->db->table('penugasan_paket')->insertBatch([
            ['id'=>11,'paket_id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1],
            ['id'=>12,'paket_id'=>12,'angkatan_id'=>2,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1],
        ]);
        $this->db->table('bab')->insert([
            'id'=>51,'paket_semester_id'=>11,'nomor_bab'=>1,'nama_bab'=>'BAB Akademik',
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>201,'paket_semester_id'=>11,'bab_id'=>51,'jenis_tp'=>'non_esensial',
                'status'=>'siap','kode_tp'=>'SIM.8.1.1','nama_tp'=>'Pertama','judul'=>'Pertama','urutan_atp'=>1],
            ['id'=>202,'paket_semester_id'=>11,'bab_id'=>51,'jenis_tp'=>'non_esensial',
                'status'=>'siap','kode_tp'=>'SIM.8.1.2','nama_tp'=>'Kedua','judul'=>'Kedua','urutan_atp'=>2],
            ['id'=>203,'paket_semester_id'=>11,'bab_id'=>51,'jenis_tp'=>'esensial',
                'status'=>'siap','kode_tp'=>'SIM.8.1.3','nama_tp'=>'Esensial','judul'=>'Esensial','urutan_atp'=>3],
            ['id'=>204,'paket_semester_id'=>12,'bab_id'=>null,'jenis_tp'=>'non_esensial',
                'status'=>'siap','kode_tp'=>'SIM.8.2.1','nama_tp'=>'Lain Gen','judul'=>'Lain Gen','urutan_atp'=>1],
        ]);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>101,'nama'=>'A Siswa Layak'],
            ['id'=>102,'nama'=>'B Siswa Belum'],
            ['id'=>103,'nama'=>'C Siswa Dilewati'],
            ['id'=>104,'nama'=>'D Siswa Gen Lain'],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>1,'pengguna_id'=>101,'angkatan_id'=>1,'semester_mulai'=>4],
            ['id'=>2,'pengguna_id'=>102,'angkatan_id'=>1,'semester_mulai'=>4],
            ['id'=>3,'pengguna_id'=>103,'angkatan_id'=>1,'semester_mulai'=>5],
            ['id'=>4,'pengguna_id'=>104,'angkatan_id'=>2,'semester_mulai'=>4],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>501,'tp_id'=>201,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'tuntas'],
            ['id'=>502,'tp_id'=>202,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'tuntas'],
            ['id'=>503,'tp_id'=>201,'siswa_id'=>2,'paket_semester_id'=>11,'status'=>'tuntas'],
            ['id'=>504,'tp_id'=>202,'siswa_id'=>2,'paket_semester_id'=>11,'status'=>'tuntas'],
        ]);
        $this->db->table('nilai_tp')->insertBatch([
            ['id'=>1,'progres_tp_id'=>501,'jalur'=>'lulus_diagnostik','nilai_resume'=>null,
                'nilai_hots'=>null,'nilai_sumatif'=>0,'nilai_tp'=>0,'status_nilai'=>'final'],
            ['id'=>2,'progres_tp_id'=>502,'jalur'=>'belajar_wajib','nilai_resume'=>80,
                'nilai_hots'=>90,'nilai_sumatif'=>70,'nilai_tp'=>76,'status_nilai'=>'final'],
            ['id'=>3,'progres_tp_id'=>503,'jalur'=>'belajar_wajib','nilai_resume'=>90,
                'nilai_hots'=>null,'nilai_sumatif'=>70,'nilai_tp'=>null,'status_nilai'=>'sementara'],
            ['id'=>4,'progres_tp_id'=>504,'jalur'=>'lulus_diagnostik','nilai_resume'=>null,
                'nilai_hots'=>null,'nilai_sumatif'=>80,'nilai_tp'=>80,'status_nilai'=>'final'],
        ]);
        $this->db->table('percobaan_ujian')->insertBatch([
            ['id'=>701,'progres_tp_id'=>501,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>702,'progres_tp_id'=>502,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>703,'progres_tp_id'=>503,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>704,'progres_tp_id'=>504,'jenis'=>'sumatif','status'=>'berjalan'],
        ]);
        foreach (array_keys(PengaturanService::IDENTITAS) as $key) {
            $this->db->table('pengaturan')->insert(['kunci'=>$key,'nilai'=>null]);
        }
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function actor(string $peran = 'admin',bool $aktif = true): callable
    {
        return static fn (): array => [
            'id'=>501,'peran_aktif'=>$peran,
            'status'=>$aktif?'aktif':'nonaktif','wajib_ganti_sandi'=>0,
        ];
    }

    public function testDaftarPaketHanyaAktifDanPenugasanYangSah(): void
    {
        $svc=new KelayakanSertifikatService($this->db,$this->actor());
        $list=$svc->daftarPaket();
        self::assertCount(2,$list);
        self::assertSame([11,12],array_map('intval',array_column($list,'id')));
    }

    public function testNilaiNolSahTanpaMemerlukanPraSemester(): void
    {
        // Tidak ada tabel Pra-Semester di fixture ini: tidak boleh menjadi gate.
        $svc=new KelayakanSertifikatService($this->db,$this->actor());
        $r=$svc->rekap(11);
        self::assertSame(3,$r['total']);
        self::assertSame(3,$r['total_tp']);
        self::assertSame(2,$r['total_non_esensial']);
        self::assertTrue($r['baris'][0]['layak_akademik']);
        self::assertSame(2,$r['baris'][0]['tp_selesai']);
        self::assertSame(2,$r['baris'][0]['nilai_final']);
        self::assertSame([],$r['baris'][0]['alasan']);
    }

    public function testTpTuntasTanpaSumatifSahDanNilaiBelumFinalDitahan(): void
    {
        $r=(new KelayakanSertifikatService($this->db,$this->actor()))->rekap(11);
        self::assertFalse($r['baris'][1]['layak_akademik']);
        self::assertSame(1,$r['baris'][1]['tp_selesai']);
        self::assertSame(0,$r['baris'][1]['nilai_final']);
        $alasan=implode(' | ',$r['baris'][1]['alasan']);
        self::assertStringContainsString('penilaian TP belum Final',$alasan);
        self::assertStringContainsString('Sumatif selesai sah',$alasan);
    }

    public function testSemesterYangDilewatiTidakMenerbitkanKelayakan(): void
    {
        $r=(new KelayakanSertifikatService($this->db,$this->actor()))->rekap(11);
        self::assertFalse($r['baris'][2]['layak_akademik']);
        self::assertTrue($r['baris'][2]['dilewati']);
        self::assertStringContainsString('Semester ini dilewati',$r['baris'][2]['alasan'][0]);
    }

    public function testPaginasiDanCakupanGen(): void
    {
        $svc=new KelayakanSertifikatService($this->db,$this->actor());
        $r=$svc->rekap(11,2,1);
        self::assertSame(3,$r['total']);
        self::assertSame(3,$r['jumlah_halaman']);
        self::assertSame(2,$r['halaman']);
        self::assertCount(1,$r['baris']);
        self::assertSame(2,$r['baris'][0]['siswa_id']);
        $this->expectException(\DomainException::class);
        $svc->rekap(13);
    }

    public function testHakAksesSelainAdminDanAdminNonaktifDitolak(): void
    {
        foreach (['guru','siswa','pimpinan'] as $peran) {
            try {
                (new KelayakanSertifikatService($this->db,$this->actor($peran)))
                    ->daftarPaket();
                self::fail('Peran di luar Admin tidak boleh mengakses sertifikat.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('Admin aktif',$e->getMessage());
            }
        }
        $this->expectException(\DomainException::class);
        (new KelayakanSertifikatService($this->db,$this->actor('admin',false)))
            ->identitas();
    }

    public function testIdentitasLengkapTidakBerartiTtdAtauPenerbitanSudahAda(): void
    {
        $svc=new KelayakanSertifikatService($this->db,$this->actor());
        self::assertFalse($svc->identitas()['lengkap']);
        foreach (array_keys(PengaturanService::IDENTITAS) as $key) {
            $this->db->table('pengaturan')->where('kunci',$key)
                ->update(['nilai'=>'Nilai Uji']);
        }
        $r=$svc->identitas();
        self::assertTrue($r['lengkap']);
        self::assertSame([],$r['kekurangan']);
        self::assertFalse($r['ttd_tersedia']);
        self::assertFalse($r['penerbitan_aktif']);
    }

    public function testFinalKomponenBelajarWajibBelumLengkapTetapDitahan(): void
    {
        $this->db->table('nilai_tp')->where('progres_tp_id',502)
            ->update(['nilai_hots'=>null]);
        $r=(new KelayakanSertifikatService($this->db,$this->actor()))->rekap(11);
        self::assertFalse($r['baris'][0]['layak_akademik']);
        self::assertSame(1,$r['baris'][0]['nilai_final']);
        self::assertStringContainsString('belum lengkap',implode(' ',$r['baris'][0]['alasan']));
    }
}
