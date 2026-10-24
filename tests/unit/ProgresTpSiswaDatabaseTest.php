<?php

declare(strict_types=1);

use App\Services\ProgresTpSiswaService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/**
 * Database sementara sepenuhnya sintetis, bukan database XAMPP.
 * CREATE TABLE untuk fixture tes saja, bukan Migration/Seeder aplikasi.
 */
final class ProgresTpSiswaDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN' => '', 'database' => ':memory:', 'DBDriver' => 'SQLite3',
            'DBPrefix' => '', 'DBDebug' => true, 'pConnect' => false,
            'foreignKeys' => false, 'busyTimeout' => 1000, 'synchronous' => null,
            'failover' => [],
        ], false);
        foreach ([
            'CREATE TABLE ref_angkatan (id INTEGER PRIMARY KEY, kode TEXT, nama TEXT, semester_mulai INT)',
            'CREATE TABLE siswa (id INTEGER PRIMARY KEY, pengguna_id INT, angkatan_id INT, semester_mulai INT)',
            'CREATE TABLE ref_mapel (id INTEGER PRIMARY KEY, kode TEXT, nama TEXT)',
            'CREATE TABLE paket_semester (id INTEGER PRIMARY KEY, angkatan_id INT, mapel_id INT, semester INT, tahun_ajaran_id INT, nomor_versi INT, status TEXT)',
            'CREATE TABLE penugasan_paket (id INTEGER PRIMARY KEY, angkatan_id INT, mapel_id INT, semester INT, tahun_ajaran_id INT, paket_id INT)',
            'CREATE TABLE tp (id INTEGER PRIMARY KEY, paket_semester_id INT, kode_tp TEXT, judul TEXT, urutan_atp INT, status TEXT, rumusan TEXT, indikator TEXT)',
            'CREATE TABLE versi_konten_tp (id INTEGER PRIMARY KEY, tp_id INT, nomor_versi INT, snapshot_json TEXT, checksum TEXT)',
            'CREATE TABLE materi (id INTEGER PRIMARY KEY, tp_id INT, urutan INT, judul TEXT, jenis TEXT, aktif INT)',
            'CREATE TABLE progres_tp (id INTEGER PRIMARY KEY AUTOINCREMENT, siswa_id INT, tp_id INT, paket_semester_id INT, versi_paket_yang_ditugaskan INT, versi_konten_tp_id INT, status TEXT, dimulai_pada TEXT, diubah_pada TEXT, UNIQUE(siswa_id,tp_id,versi_paket_yang_ditugaskan))',
            'CREATE TABLE log_aktivitas (id INTEGER PRIMARY KEY AUTOINCREMENT, pengguna_id INT, peran_aktif TEXT, peristiwa TEXT, catatan TEXT, waktu TEXT)',
        ] as $stmt) {
            $this->db->query($stmt);
        }
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'GENA','nama'=>'Gen A','semester_mulai'=>4],
            ['id'=>2,'kode'=>'GENB','nama'=>'Gen B','semester_mulai'=>4],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>11,'pengguna_id'=>101,'angkatan_id'=>1,'semester_mulai'=>null],
            ['id'=>22,'pengguna_id'=>202,'angkatan_id'=>2,'semester_mulai'=>null],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>4,'kode'=>'SKI','nama'=>'SKI']);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>7,'angkatan_id'=>1,'mapel_id'=>4,'semester'=>4,'tahun_ajaran_id'=>1,'nomor_versi'=>1,'status'=>'aktif'],
            ['id'=>8,'angkatan_id'=>2,'mapel_id'=>4,'semester'=>4,'tahun_ajaran_id'=>1,'nomor_versi'=>1,'status'=>'aktif'],
        ]);
        $this->db->table('penugasan_paket')->insertBatch([
            ['id'=>1,'angkatan_id'=>1,'mapel_id'=>4,'semester'=>4,'tahun_ajaran_id'=>1,'paket_id'=>7],
            ['id'=>2,'angkatan_id'=>2,'mapel_id'=>4,'semester'=>4,'tahun_ajaran_id'=>1,'paket_id'=>8],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>1,'paket_semester_id'=>7,'kode_tp'=>'TP-A-1','judul'=>'Pertama','urutan_atp'=>1,'status'=>'siap','rumusan'=>'Rumusan','indikator'=>'Indikator'],
            ['id'=>2,'paket_semester_id'=>7,'kode_tp'=>'TP-A-2','judul'=>'Kedua','urutan_atp'=>2,'status'=>'siap','rumusan'=>'Rumusan','indikator'=>'Indikator'],
            ['id'=>3,'paket_semester_id'=>8,'kode_tp'=>'TP-B-1','judul'=>'Gen lain','urutan_atp'=>1,'status'=>'siap','rumusan'=>'Rumusan','indikator'=>'Indikator'],
        ]);
        foreach ([1,2,3] as $tpId) {
            $json = json_encode([
                'format'=>'SLA-SKS-TP-V1','tp'=>['id'=>$tpId,'status'=>'siap'],
                'materi'=>[],'soal'=>[],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->db->table('versi_konten_tp')->insert([
                'id'=>$tpId,'tp_id'=>$tpId,'nomor_versi'=>1,'snapshot_json'=>$json,
                'checksum'=>hash('sha256',$json),
            ]);
        }
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function layanan(int $userId, string $role='siswa'): ProgresTpSiswaService
    {
        return new ProgresTpSiswaService($this->db, static fn (): array => [
            'id'=>$userId,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    public function testGetStatusTidakMembuatProgres(): void
    {
        self::assertNull($this->layanan(101)->status(1));
        self::assertSame(0, $this->db->table('progres_tp')->countAllResults());
        self::assertSame(0, $this->db->table('log_aktivitas')->countAllResults());
    }

    public function testMulaiMengunciVersiDanIdempoten(): void
    {
        $layanan = $this->layanan(101);
        $pertama = $layanan->mulai(1);
        self::assertSame('diagnostik',$pertama['status']);
        self::assertSame(1,(int)$pertama['versi_konten_tp_id']);
        self::assertSame(11,(int)$pertama['siswa_id']);
        self::assertSame(1,(int)$pertama['versi_paket_yang_ditugaskan']);
        $kedua = $layanan->mulai(1);
        self::assertSame((int)$pertama['id'],(int)$kedua['id']);
        self::assertSame(1,$this->db->table('progres_tp')->countAllResults());
        self::assertSame(1,$this->db->table('log_aktivitas')->countAllResults());
        self::assertSame((int)$pertama['id'],(int)$layanan->status(1)['id']);
    }

    public function testSetelahPinVersiBaruTidakMenggeserSnapshotSiswa(): void
    {
        $layanan = $this->layanan(101);
        $awal = $layanan->mulai(1);
        $json = json_encode(['format'=>'SLA-SKS-TP-V1',
            'tp'=>['id'=>1,'status'=>'siap'],'materi'=>[],'soal'=>[]]);
        $this->db->table('versi_konten_tp')->insert([
            'id'=>99,'tp_id'=>1,'nomor_versi'=>2,'snapshot_json'=>$json,
            'checksum'=>hash('sha256',$json),
        ]);
        $ulang = $layanan->mulai(1);
        self::assertSame((int)$awal['versi_konten_tp_id'],(int)$ulang['versi_konten_tp_id']);
        self::assertSame(1,$this->db->table('progres_tp')->countAllResults());
    }

    public function testGenLainDitolakTanpaMembuatProgres(): void
    {
        $this->expectException(\DomainException::class);
        try {
            $this->layanan(202)->mulai(1);
        } finally {
            self::assertSame(0,$this->db->table('progres_tp')->countAllResults());
        }
    }

    public function testTPKeduaDitolakSampaiTpSebelumnyaSelesai(): void
    {
        $this->expectException(\DomainException::class);
        $this->layanan(101)->mulai(2);
    }

    public function testBukanSiswaTidakBolehMengaktifkanTP(): void
    {
        $this->expectException(\DomainException::class);
        $this->layanan(101,'guru')->mulai(1);
    }

    public function testChecksumKorupMenolakAktivasiTanpaProgres(): void
    {
        $this->db->table('versi_konten_tp')->where('id',1)
            ->update(['checksum'=>str_repeat('0',64)]);
        $this->expectException(\DomainException::class);
        try {
            $this->layanan(101)->mulai(1);
        } finally {
            self::assertSame(0,$this->db->table('progres_tp')->countAllResults());
            self::assertSame(0,$this->db->table('log_aktivitas')->countAllResults());
        }
    }
}
