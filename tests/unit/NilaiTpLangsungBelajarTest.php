<?php

declare(strict_types=1);

use App\Services\NilaiTpService;
use PHPUnit\Framework\TestCase;

/** Nilai jalur Langsung Belajar: Diagnostik batal tanpa skor tidak menjadi 0. */
final class NilaiTpLangsungBelajarTest extends TestCase
{
    private \CodeIgniter\Database\BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,
            'foreignKeys'=>false,'busyTimeout'=>1000,'synchronous'=>null,'failover'=>[],
        ], false);
        foreach ([
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,versi_konten_tp_id INTEGER,status TEXT)',
            'CREATE TABLE versi_konten_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,nomor_versi INTEGER,snapshot_json TEXT,checksum TEXT)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,versi_konten_tp_id INTEGER,jenis TEXT,tahap TEXT,status TEXT,kkm INTEGER,skor REAL)',
            'CREATE TABLE keputusan_ujian_batal(id INTEGER PRIMARY KEY,percobaan_ujian_id INTEGER,keputusan TEXT)',
            'CREATE TABLE resume_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,versi_konten_tp_id INTEGER,materi_id INTEGER,status TEXT,nilai INTEGER)',
            'CREATE TABLE jawaban_hots_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,versi_konten_tp_id INTEGER,materi_id INTEGER,status TEXT,nilai INTEGER)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY AUTOINCREMENT,progres_tp_id INTEGER UNIQUE,jalur TEXT,nilai_resume REAL,nilai_hots REAL,nilai_sumatif REAL,nilai_tp REAL,status_nilai TEXT,diubah_pada TEXT)',
        ] as $sql) $this->db->query($sql);
        $snap = ['format'=>'SLA-SKS-TP-V1','tp'=>['id'=>1,'status'=>'siap'],
            'materi'=>[['id'=>31,'judul'=>'Materi Uji']], 'soal'=>[]];
        $json = json_encode($snap, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->db->table('versi_konten_tp')->insert([
            'id'=>21,'tp_id'=>1,'nomor_versi'=>1,'snapshot_json'=>$json,
            'checksum'=>hash('sha256',(string)$json),
        ]);
        $this->db->table('progres_tp')->insert([
            'id'=>11,'tp_id'=>1,'versi_konten_tp_id'=>21,'status'=>'tuntas',
        ]);
        $this->db->table('percobaan_ujian')->insertBatch([
            ['id'=>101,'progres_tp_id'=>11,'versi_konten_tp_id'=>21,
                'jenis'=>'diagnostik','tahap'=>'normal','status'=>'dibatalkan_menunggu_guru',
                'kkm'=>75,'skor'=>null],
            ['id'=>201,'progres_tp_id'=>11,'versi_konten_tp_id'=>21,
                'jenis'=>'sumatif','tahap'=>'normal','status'=>'selesai_sah',
                'kkm'=>75,'skor'=>65],
            ['id'=>202,'progres_tp_id'=>11,'versi_konten_tp_id'=>21,
                'jenis'=>'sumatif','tahap'=>'remedial','status'=>'selesai_sah',
                'kkm'=>75,'skor'=>55],
        ]);
        $this->db->table('keputusan_ujian_batal')->insert([
            'id'=>1,'percobaan_ujian_id'=>101,'keputusan'=>'langsung_belajar',
        ]);
        $this->db->table('resume_materi')->insert([
            'id'=>1,'progres_tp_id'=>11,'versi_konten_tp_id'=>21,
            'materi_id'=>31,'status'=>'final','nilai'=>80,
        ]);
        $this->db->table('jawaban_hots_materi')->insert([
            'id'=>1,'progres_tp_id'=>11,'versi_konten_tp_id'=>21,
            'materi_id'=>31,'status'=>'final','nilai'=>90,
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    public function testMemakaiSkorSumatifTerbaikDanNilaiGuruTanpaSkorDiagnostik(): void
    {
        $result = NilaiTpService::sinkronkan($this->db,11);
        self::assertNotNull($result);
        self::assertSame('belajar_wajib',$result['jalur']);
        self::assertSame(65.0,$result['nilai_sumatif']);
        self::assertSame(73.0,$result['nilai_tp']); // 16 + 18 + 39
        self::assertSame('final',$result['status_nilai']);
        NilaiTpService::sinkronkan($this->db,11);
        self::assertSame(1,$this->db->table('nilai_tp')->countAllResults());
        self::assertNull($this->db->table('percobaan_ujian')->where('id',101)
            ->get()->getRow('skor'));
    }

    public function testNilaiGuruBelumAdaTetapNullBukanNol(): void
    {
        $this->db->table('jawaban_hots_materi')->where('id',1)->update(['nilai'=>null]);
        $hasil = NilaiTpService::sinkronkan($this->db,11);
        self::assertSame('sementara',$hasil['status_nilai']);
        self::assertNull($hasil['nilai_hots']);
        self::assertNull($hasil['nilai_tp']);
        self::assertSame(65.0,$hasil['nilai_sumatif']);
    }

    public function testTanpaKeputusanGuruTidakBolehMengarangJalurBelajar(): void
    {
        $this->db->table('keputusan_ujian_batal')->delete();
        $this->expectException(\DomainException::class);
        NilaiTpService::sinkronkan($this->db,11);
    }
}
