<?php

declare(strict_types=1);

use App\Services\PenilaianGuruService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** Fixture SQLite sintetis; tidak mengakses data pribadi atau MySQL pengguna. */
final class PenilaianGuruDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,
            'foreignKeys'=>false,'busyTimeout'=>1000,'synchronous'=>null,
            'failover'=>[],
        ], false);
        foreach ([
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT)',
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,kode_tp TEXT,judul TEXT)',
            'CREATE TABLE materi(id INTEGER PRIMARY KEY,tp_id INTEGER,urutan INTEGER,judul TEXT)',
            'CREATE TABLE versi_konten_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,snapshot_json TEXT,checksum TEXT)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,siswa_id INTEGER,tp_id INTEGER,versi_konten_tp_id INTEGER)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,guru_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE resume_materi(id INTEGER PRIMARY KEY AUTOINCREMENT,progres_tp_id INTEGER,materi_id INTEGER,versi_konten_tp_id INTEGER,isi TEXT,status TEXT,revisi INTEGER,dikirim_pada TEXT,nilai INTEGER,dinilai_oleh INTEGER,dinilai_pada TEXT,umpan_balik TEXT)',
            'CREATE TABLE tugas_hots_materi(id INTEGER PRIMARY KEY AUTOINCREMENT,progres_tp_id INTEGER,materi_id INTEGER,versi_konten_tp_id INTEGER,isi TEXT,status TEXT,revisi INTEGER,dikirim_pada TEXT,nilai INTEGER,dinilai_oleh INTEGER,dinilai_pada TEXT,umpan_balik TEXT)',
            'CREATE TABLE log_aktivitas(id INTEGER PRIMARY KEY AUTOINCREMENT,pengguna_id INTEGER,peran_aktif TEXT,peristiwa TEXT,catatan TEXT,waktu TEXT)',
        ] as $ddl) $this->db->query($ddl);
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'A','nama'=>'Gen A'],
            ['id'=>2,'kode'=>'B','nama'=>'Gen B'],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>5,'kode'=>'SKI','nama'=>'Sejarah']);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>101,'nama'=>'Guru A'],['id'=>202,'nama'=>'Guru B'],
            ['id'=>301,'nama'=>'Siswa A'],['id'=>302,'nama'=>'Siswa B'],
            ['id'=>303,'nama'=>'Siswa C'],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>31,'pengguna_id'=>301,'angkatan_id'=>1],
            ['id'=>32,'pengguna_id'=>302,'angkatan_id'=>2],
            ['id'=>33,'pengguna_id'=>303,'angkatan_id'=>1],
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>11,'angkatan_id'=>1,'mapel_id'=>5],
            ['id'=>22,'angkatan_id'=>2,'mapel_id'=>5],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>1,'paket_semester_id'=>11,'kode_tp'=>'TP-A','judul'=>'Peristiwa A'],
            ['id'=>2,'paket_semester_id'=>22,'kode_tp'=>'TP-B','judul'=>'Peristiwa B'],
        ]);
        $this->db->table('materi')->insertBatch([
            ['id'=>10,'tp_id'=>1,'urutan'=>1,'judul'=>'Materi A'],
            ['id'=>20,'tp_id'=>2,'urutan'=>1,'judul'=>'Materi B'],
        ]);
        foreach ([1=>[10,1010],2=>[20,2020]] as $tpId=>$ids) {
            $j = json_encode(['materi'=>[[
                'id'=>$ids[0], 'judul'=>'Judul snapshot',
                'petunjuk_resume'=>'Ringkas isi',
                'petunjuk_hots'=>'Berikan analisis',
            ]]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->db->table('versi_konten_tp')->insert([
                'id'=>$ids[1],'tp_id'=>$tpId,'snapshot_json'=>$j,
                'checksum'=>hash('sha256',$j),
            ]);
        }
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>1,'siswa_id'=>31,'tp_id'=>1,'versi_konten_tp_id'=>1010],
            ['id'=>2,'siswa_id'=>32,'tp_id'=>2,'versi_konten_tp_id'=>2020],
            ['id'=>3,'siswa_id'=>33,'tp_id'=>1,'versi_konten_tp_id'=>1010],
        ]);
        $this->db->table('penugasan_guru_gen')->insertBatch([
            ['id'=>1,'guru_id'=>101,'angkatan_id'=>1,'mapel_id'=>5],
            ['id'=>2,'guru_id'=>202,'angkatan_id'=>2,'mapel_id'=>5],
        ]);
        $this->db->table('resume_materi')->insertBatch([
            ['id'=>1,'progres_tp_id'=>1,'materi_id'=>10,'versi_konten_tp_id'=>1010,
                'isi'=>'Ringkasan resmi A','status'=>'final','revisi'=>1,
                'dikirim_pada'=>'2026-10-25 00:10:00'],
            ['id'=>2,'progres_tp_id'=>2,'materi_id'=>20,'versi_konten_tp_id'=>2020,
                'isi'=>'Ringkasan resmi B','status'=>'final','revisi'=>1,
                'dikirim_pada'=>'2026-10-25 00:11:00'],
            ['id'=>3,'progres_tp_id'=>3,'materi_id'=>10,'versi_konten_tp_id'=>1010,
                'isi'=>'Draf belum final','status'=>'draf','revisi'=>1,
                'dikirim_pada'=>null],
        ]);
        $this->db->table('tugas_hots_materi')->insert([
            'id'=>7,'progres_tp_id'=>1,'materi_id'=>10,'versi_konten_tp_id'=>1010,
            'isi'=>'Analisis HOTS A','status'=>'final','revisi'=>1,
            'dikirim_pada'=>'2026-10-25 00:12:00',
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function svc(int $id=101, string $role='guru'): PenilaianGuruService
    {
        return new PenilaianGuruService($this->db,static fn ():array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif',
            'wajib_ganti_sandi'=>0,
        ]);
    }

    public function testAntreanG1PerGenDanHanyaJawabanFinalBelumDinilai(): void
    {
        $svc = $this->svc();
        $g = $svc->penugasan();
        self::assertCount(1,$g);
        self::assertSame('Gen A',$g[0]['nama_gen']);
        $list = $svc->antrean();
        self::assertSame(2,$list['total']);
        self::assertSame(1,$list['baris'][0]['id']);
        self::assertSame('resume',$list['baris'][0]['jenis']);
        self::assertSame(7,$list['baris'][1]['id']);
        self::assertSame('hots',$list['baris'][1]['jenis']);
        self::assertSame(2,$svc->antrean($g[0])['total']);
        self::assertSame(1,$this->svc(202)->antrean()['total']);
    }

    public function testIDMilikiGenLainTidakBolehDibacaAtauDinilai(): void
    {
        try {
            $this->svc()->lihat('resume',2);
            self::fail('Lintas Gen harus ditolak.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Gen',$e->getMessage());
        }
        $this->expectException(\DomainException::class);
        $this->svc()->simpan('resume',2,90,'Aman?');
    }

    public function testNilaiNolTersimpanSekaliDanTetapTerlihatPenilaiAsli(): void
    {
        $svc = $this->svc();
        self::assertSame('Ringkas isi',$svc->lihat('resume',1)['petunjuk']);
        $svc->simpan('resume',1,0,'Perlu pengembangan.');
        $data=$svc->lihat('resume',1);
        self::assertSame(0,(int)$data['nilai']);
        self::assertSame(101,(int)$data['dinilai_oleh']);
        self::assertNotNull($data['dinilai_pada']);
        self::assertSame('Perlu pengembangan.',$data['umpan_balik']);
        self::assertSame(1,$svc->antrean()['total']);
        $svc->simpan('resume',1,0,'Perlu pengembangan.');
        self::assertSame(1,$this->db->table('log_aktivitas')->countAllResults());
        try {
            $svc->simpan('resume',1,80,'Koreksi terselubung');
            self::fail('Nilai tak boleh ditimpa.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('koreksi',$e->getMessage());
        }
        self::assertSame(0,(int) $svc->lihat('resume',1)['nilai']);
    }

    public function testGuruPenggantiMenilaiTertundaNamunTidakMengubahNilaiLama(): void
    {
        $lama = $this->svc();
        $lama->simpan('resume',1,90,'Sudah dinilai Guru A');
        $this->db->table('penugasan_guru_gen')->where('id',1)
            ->update(['guru_id'=>202]);
        $baru = $this->svc(202);
        // Guru B kini mengampu dua pasangan: Gen A (alih G1) dan Gen B.
        self::assertSame(2,$baru->antrean()['total']);
        $g1 = $baru->penugasan();
        self::assertCount(2,$g1);
        self::assertSame(1,$baru->antrean($g1[0])['total']);
        $baru->simpan('hots',7,100,'Sangat baik');
        self::assertSame(1,$baru->antrean()['total']);
        self::assertSame(0,$baru->antrean($g1[0])['total']);
        self::assertSame(101,(int) $baru->lihat('resume',1)['dinilai_oleh']);
        try {
            $baru->simpan('resume',1,95,'Ubah nilai lama');
            self::fail('Guru baru tidak menimpa penilai lama.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('koreksi',$e->getMessage());
        }
        $this->expectException(\DomainException::class);
        $lama->simpan('hots',7,50,'G1 sudah dialihkan');
    }

    public function testHanyaGuruAktifDanBatasNilaiSah(): void
    {
        foreach ([-1,101] as $nilai) {
            try {
                $this->svc()->simpan('resume',1,$nilai,'');
                self::fail('Skor tidak sah harus ditolak.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('0–100',$e->getMessage());
            }
        }
        try {
            $this->svc()->simpan('resume',1,50,str_repeat('a',1001));
            self::fail('Komentar >1000 harus ditolak.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('1.000',$e->getMessage());
        }
        $this->expectException(\DomainException::class);
        $this->svc(101,'admin')->lihat('resume',1);
    }

    public function testSnapshotDiubahTidakBolehDinilai(): void
    {
        $this->db->table('versi_konten_tp')->where('id',1010)
            ->update(['snapshot_json'=>'{"materi":[]}']);
        $this->expectException(\DomainException::class);
        $this->svc()->simpan('resume',1,80,'');
    }

    public function testFilterPaginasiTetapBerdasarkanSQLDanDrafTidakMuncul(): void
    {
        for ($i=0;$i<30;$i++) {
            $this->db->table('tugas_hots_materi')->insert([
                'progres_tp_id'=>3,'materi_id'=>10,
                'versi_konten_tp_id'=>1010,'isi'=>'Jawaban ' . $i,
                'status'=>'final','revisi'=>1,
                'dikirim_pada'=>'2026-10-25 00:13:00',
            ]);
        }
        $svc=$this->svc();
        self::assertSame(32,$svc->antrean()['total']);
        self::assertCount(25,$svc->antrean()['baris']);
        self::assertCount(7,$svc->antrean(null,2)['baris']);
        self::assertSame(2,$svc->antrean(null,999)['halaman']);
    }
}
