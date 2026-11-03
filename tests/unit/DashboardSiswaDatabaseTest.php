<?php

declare(strict_types=1);

use App\Services\DashboardSiswaService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** Fixture anonim, database SQLite in-memory terisolasi dari SQL XAMPP. */
final class DashboardSiswaDatabaseTest extends TestCase
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
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER,semester_mulai INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER,nomor_versi INTEGER,status TEXT)',
            'CREATE TABLE penugasan_paket(id INTEGER PRIMARY KEY,paket_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,nomor_bab INTEGER,nama_bab TEXT,deskripsi_bab TEXT)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,bab_id INTEGER,kode_tp TEXT,judul TEXT,urutan_atp INTEGER,status TEXT,jenis_tp TEXT,rumusan TEXT,indikator TEXT)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,siswa_id INTEGER,tp_id INTEGER,paket_semester_id INTEGER,status TEXT)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jenis TEXT,status TEXT)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,status_nilai TEXT,nilai_tp REAL)',
            'CREATE TABLE versi_konten_tp(id INTEGER PRIMARY KEY,tp_id INTEGER)',
            'CREATE TABLE sertifikat(id INTEGER PRIMARY KEY,status TEXT)',
            'CREATE TABLE sertifikat_aktif(id INTEGER PRIMARY KEY,siswa_id INTEGER,paket_semester_id INTEGER,sertifikat_id INTEGER)',
        ] as $sql) $this->db->query($sql);
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'GEN-A','nama'=>'Gen Contoh A','semester_mulai'=>4],
            ['id'=>2,'kode'=>'GEN-B','nama'=>'Gen Contoh B','semester_mulai'=>4],
            ['id'=>3,'kode'=>'GEN-C','nama'=>'Gen Belum Ada Paket','semester_mulai'=>1],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>1,'kode'=>'SIM','nama'=>'Mapel Simulasi']);
        $this->db->table('siswa')->insertBatch([
            ['id'=>1,'pengguna_id'=>101,'angkatan_id'=>1,'semester_mulai'=>null],
            ['id'=>2,'pengguna_id'=>102,'angkatan_id'=>1,'semester_mulai'=>5],
            ['id'=>3,'pengguna_id'=>103,'angkatan_id'=>2,'semester_mulai'=>null],
            ['id'=>4,'pengguna_id'=>104,'angkatan_id'=>3,'semester_mulai'=>null],
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,'nomor_versi'=>1,'status'=>'aktif'],
            ['id'=>12,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>5,'tahun_ajaran_id'=>1,'nomor_versi'=>1,'status'=>'aktif'],
            ['id'=>13,'angkatan_id'=>2,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,'nomor_versi'=>1,'status'=>'aktif'],
            ['id'=>14,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>6,'tahun_ajaran_id'=>1,'nomor_versi'=>1,'status'=>'draf'],
        ]);
        $this->db->table('penugasan_paket')->insertBatch([
            ['id'=>1,'paket_id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1],
            ['id'=>2,'paket_id'=>12,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>5,'tahun_ajaran_id'=>1],
            ['id'=>3,'paket_id'=>13,'angkatan_id'=>2,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1],
            ['id'=>4,'paket_id'=>14,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>6,'tahun_ajaran_id'=>1],
        ]);
        $this->db->table('bab')->insertBatch([
            ['id'=>31,'paket_semester_id'=>11,'nomor_bab'=>1,'nama_bab'=>'BAB A','deskripsi_bab'=>'Contoh A'],
            ['id'=>32,'paket_semester_id'=>12,'nomor_bab'=>2,'nama_bab'=>'BAB B','deskripsi_bab'=>'Contoh B'],
            ['id'=>33,'paket_semester_id'=>13,'nomor_bab'=>1,'nama_bab'=>'BAB C','deskripsi_bab'=>'Contoh C'],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>201,'paket_semester_id'=>11,'bab_id'=>31,'kode_tp'=>'SIM.8.1.1','judul'=>'TP pertama','urutan_atp'=>1,'status'=>'siap','jenis_tp'=>'non_esensial','rumusan'=>'Contoh','indikator'=>''],
            ['id'=>202,'paket_semester_id'=>11,'bab_id'=>31,'kode_tp'=>'SIM.8.1.2','judul'=>'TP kedua','urutan_atp'=>2,'status'=>'siap','jenis_tp'=>'non_esensial','rumusan'=>'Contoh','indikator'=>''],
            ['id'=>203,'paket_semester_id'=>11,'bab_id'=>31,'kode_tp'=>'SIM.8.1.3','judul'=>'TP ketiga','urutan_atp'=>3,'status'=>'siap','jenis_tp'=>'non_esensial','rumusan'=>'Contoh','indikator'=>''],
            ['id'=>204,'paket_semester_id'=>11,'bab_id'=>31,'kode_tp'=>'SIM.8.1.4','judul'=>'TP esensial','urutan_atp'=>4,'status'=>'siap','jenis_tp'=>'esensial','rumusan'=>'Contoh','indikator'=>''],
            ['id'=>205,'paket_semester_id'=>12,'bab_id'=>32,'kode_tp'=>'SIM.9.2.1','judul'=>'TP semester 5','urutan_atp'=>1,'status'=>'siap','jenis_tp'=>'non_esensial','rumusan'=>'Contoh','indikator'=>''],
            ['id'=>206,'paket_semester_id'=>13,'bab_id'=>33,'kode_tp'=>'SIM.8.1.1','judul'=>'TP esensial saja','urutan_atp'=>1,'status'=>'siap','jenis_tp'=>'esensial','rumusan'=>'Contoh','indikator'=>''],
        ]);
        $this->db->table('versi_konten_tp')->insertBatch([
            ['id'=>401,'tp_id'=>201],
            ['id'=>402,'tp_id'=>202],
            ['id'=>403,'tp_id'=>203],
            ['id'=>404,'tp_id'=>204],
            ['id'=>405,'tp_id'=>205],
            ['id'=>406,'tp_id'=>206],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>51,'tp_id'=>201,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'tuntas'],
            ['id'=>52,'tp_id'=>202,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'perlu_bantuan'],
            ['id'=>53,'tp_id'=>203,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'tuntas'],
        ]);
        $this->db->table('percobaan_ujian')->insertBatch([
            ['id'=>101,'progres_tp_id'=>51,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>102,'progres_tp_id'=>51,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>103,'progres_tp_id'=>52,'jenis'=>'diagnostik','status'=>'selesai_sah'],
            ['id'=>104,'progres_tp_id'=>53,'jenis'=>'sumatif','status'=>'berjalan'],
        ]);
        $this->db->table('nilai_tp')->insertBatch([
            ['id'=>201,'progres_tp_id'=>51,'status_nilai'=>'final','nilai_tp'=>0],
            ['id'=>202,'progres_tp_id'=>52,'status_nilai'=>'sementara','nilai_tp'=>70],
            ['id'=>203,'progres_tp_id'=>53,'status_nilai'=>'final','nilai_tp'=>100],
        ]);
        $this->db->table('sertifikat')->insertBatch([
            ['id'=>301,'status'=>'berlaku'],
            ['id'=>302,'status'=>'digantikan'],
        ]);
        $this->db->table('sertifikat_aktif')->insert([
            'id'=>1,'siswa_id'=>1,'paket_semester_id'=>11,'sertifikat_id'=>301,
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private static function akun(int $id=101,string $role='siswa',string $status='aktif'): callable
    {
        return static fn (): array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>$status,
            'wajib_ganti_sandi'=>0,
        ];
    }

    private function servis(?callable $actor=null): DashboardSiswaService
    {
        return new DashboardSiswaService($this->db,$actor??self::akun());
    }

    public function testProgresPerluSumatifSahNilaiNolDanSertifikatAktif(): void
    {
        $r=$this->servis()->ringkasan();
        self::assertSame(1,$r['mapel']);
        self::assertSame(1,$r['paket_terbuka']);
        self::assertSame(3,$r['tp_wajib']);
        self::assertSame(1,$r['tp_tuntas']);
        self::assertSame(1,$r['nilai_final']);
        self::assertSame(1,$r['perlu_bantuan']);
        self::assertSame(1,$r['sertifikat_berlaku']);
        self::assertEqualsWithDelta(33.3,$r['persen'],.1);
        self::assertSame('siswa/tp/202',$r['lanjut']['tautan']);
        self::assertSame('SIM.8.1.2',$r['lanjut']['tp_berikutnya']['kode']);
        self::assertSame('TP kedua',$r['lanjut']['tp_berikutnya']['judul']);
        self::assertSame('siswa/tp/202',$r['paket'][0]['tautan']);
        self::assertSame($r['lanjut']['tp_berikutnya'],$r['paket'][0]['tp_berikutnya']);
        self::assertSame('Lanjutkan TP',$r['paket'][0]['label_aksi']);
        self::assertStringNotContainsString('nisn',json_encode($r));
    }

    public function testSemesterDilewatiTidakMenjadiPaketTerbuka(): void
    {
        $r=$this->servis(self::akun(102))->ringkasan();
        self::assertSame(1,$r['paket_terbuka']);
        self::assertSame(5,$r['siswa']['semester_mulai']);
        self::assertSame(12,$r['paket'][0]['paket_id']);
        self::assertSame('siswa/tp/205',$r['lanjut']['tautan']);
        self::assertSame('SIM.9.2.1',$r['lanjut']['tp_berikutnya']['kode']);
        self::assertSame(0,$r['tp_tuntas']);
        self::assertSame(0.0,$r['persen']);
    }

    public function testPaketEsensialTanpaTargetTidakDitafsirkanSeratusPersen(): void
    {
        $r=$this->servis(self::akun(103))->ringkasan();
        self::assertSame(1,$r['paket_terbuka']);
        self::assertSame(0,$r['tp_wajib']);
        self::assertNull($r['persen']);
        self::assertNull($r['paket'][0]['persen']);
        self::assertSame('Buka Semester',$r['paket'][0]['label_aksi']);
        self::assertNull($r['paket'][0]['tp_berikutnya']);
    }

    public function testTpYangBelumSiapTidakDitampilkanSebagaiBerikutnya(): void
    {
        // Siswa mulai Semester 5, satu TP ada tetapi belum Siap.
        // Katalog tidak boleh mengirim kode/judul TP terkunci ke dashboard.
        $this->db->table('tp')->where('id',205)->update(['status'=>'draf']);
        $r=$this->servis(self::akun(102))->ringkasan();
        self::assertSame('siswa/semester/12',$r['lanjut']['tautan']);
        self::assertSame('Buka Semester',$r['lanjut']['label']);
        self::assertNull($r['lanjut']['tp_berikutnya']);
        self::assertNull($r['paket'][0]['tp_berikutnya']);
        self::assertStringNotContainsString('TP semester 5',json_encode($r));
    }

    public function testSiswaTanpaPaketMenerimaStateKosong(): void
    {
        $r=$this->servis(self::akun(104))->ringkasan();
        self::assertSame(0,$r['paket_terbuka']);
        self::assertSame([], $r['paket']);
        self::assertSame(0,$r['mapel']);
        self::assertNull($r['persen']);
        self::assertSame('siswa/pembelajaran',$r['lanjut']['tautan']);
    }

    public function testSelesaiSemuaTpMembukaSemesterBerikutnya(): void
    {
        $this->db->table('progres_tp')->whereIn('id',[52,53])
            ->update(['status'=>'tuntas']);
        $this->db->table('percobaan_ujian')->insertBatch([
            ['id'=>105,'progres_tp_id'=>52,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>106,'progres_tp_id'=>53,'jenis'=>'sumatif','status'=>'selesai_sah'],
        ]);
        $r=$this->servis()->ringkasan();
        self::assertSame(2,$r['paket_terbuka']);
        self::assertSame(4,$r['tp_wajib']);
        self::assertSame(3,$r['tp_tuntas']);
        self::assertSame('Lihat Hasil',$r['paket'][0]['label_aksi']);
        self::assertSame('siswa/tp/205',$r['lanjut']['tautan']);
    }

    public function testBukanSiswaAktifDitolak(): void
    {
        foreach ([self::akun(101,'guru'),self::akun(101,'siswa','nonaktif')] as $actor) {
            try {
                $this->servis($actor)->ringkasan();
                self::fail('Peran/status tidak sah seharusnya ditolak.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('Siswa aktif',$e->getMessage());
            }
        }
    }

    public function testPembacaanTidakMenyimpanProgresAtauMengubahNilai(): void
    {
        $sebelum=$this->db->table('progres_tp')->countAllResults();
        $this->servis()->ringkasan();
        self::assertSame($sebelum,$this->db->table('progres_tp')->countAllResults());
        self::assertSame(0.0,(float)$this->db->table('nilai_tp')
            ->where('progres_tp_id',51)->get()->getRow('nilai_tp'));
    }
}
