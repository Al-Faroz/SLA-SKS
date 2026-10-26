<?php

declare(strict_types=1);

use App\Services\PetunjukDiagnostikSiswaService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** Fixture SQLite sintetis, tidak menyentuh XAMPP dan bukan CI4 Migration/Seeder. */
final class PetunjukDiagnostikSiswaDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN' => '', 'database' => ':memory:', 'DBDriver' => 'SQLite3',
            'DBPrefix' => '', 'DBDebug' => true, 'pConnect' => false,
            'foreignKeys' => false, 'busyTimeout' => 1000, 'synchronous' => null, 'failover' => [],
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
            'CREATE TABLE progres_tp (id INTEGER PRIMARY KEY, siswa_id INT, tp_id INT, paket_semester_id INT, versi_paket_yang_ditugaskan INT, versi_konten_tp_id INT, status TEXT, dimulai_pada TEXT, diubah_pada TEXT)',
        ] as $sql) {
            $this->db->query($sql);
        }

        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'GENA','nama'=>'Gen A','semester_mulai'=>4],
            ['id'=>2,'kode'=>'GENB','nama'=>'Gen B','semester_mulai'=>4],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>10,'pengguna_id'=>100,'angkatan_id'=>1,'semester_mulai'=>null],
            ['id'=>20,'pengguna_id'=>200,'angkatan_id'=>2,'semester_mulai'=>null],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>1,'kode'=>'SKI','nama'=>'SKI']);
        $this->db->table('paket_semester')->insert([
            'id'=>1,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,
            'nomor_versi'=>1,'status'=>'aktif',
        ]);
        $this->db->table('penugasan_paket')->insert([
            'id'=>1,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>1,'paket_id'=>1,
        ]);
        $this->db->table('tp')->insert([
            'id'=>1,'paket_semester_id'=>1,'kode_tp'=>'TP-SKI','judul'=>'TP 1',
            'urutan_atp'=>1,'status'=>'siap','rumusan'=>'Mempelajari','indikator'=>'Memahami',
        ]);

        $soal = [];
        foreach (range(1,10) as $i) {
            $soal[] = [
                'id'=>$i, 'urutan'=>$i,'jenis'=>'diagnostik','pertanyaan'=>'Soal '.$i,
                'opsi_a'=>'A', 'opsi_b'=>'B', 'opsi_c'=>'C', 'opsi_d'=>'D', 'opsi_e'=>'E',
                'kunci'=>'B','aktif'=>1,
            ];
        }
        $this->buatVersi(1, 1, $soal);
        $this->db->table('progres_tp')->insert([
            'id'=>1, 'siswa_id'=>10,'tp_id'=>1,'paket_semester_id'=>1,
            'versi_paket_yang_ditugaskan'=>1,'versi_konten_tp_id'=>1,
            'status'=>'diagnostik','dimulai_pada'=>'2026-10-24 00:00:00',
            'diubah_pada'=>'2026-10-24 00:00:00',
        ]);
    }

    private function buatVersi(int $versiId, int $nomor, array $soal): void
    {
        $json = json_encode([
            'format'=>'SLA-SKS-TP-V1', 'tp'=>[
                'id'=>1,'status'=>'siap','diag_tayang'=>10,'diag_durasi'=>30,
            ], 'materi'=>[], 'soal'=>$soal,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->db->table('versi_konten_tp')->insert([
            'id'=>$versiId,'tp_id'=>1,'nomor_versi'=>$nomor,'snapshot_json'=>$json,
            'checksum'=>hash('sha256', $json),
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function layanan(int $akunId = 100): PetunjukDiagnostikSiswaService
    {
        return new PetunjukDiagnostikSiswaService($this->db, static fn (): array => [
            'id'=>$akunId,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    public function testPetunjukSiapTidakMembuatPercobaanAtauProgresBaru(): void
    {
        $awal = (int) $this->db->table('progres_tp')->countAllResults();
        $data = $this->layanan()->periksa(1);
        self::assertTrue($data['boleh_uji']);
        self::assertSame(10, $data['jumlah_soal']);
        self::assertSame(10, $data['jumlah_tayang']);
        self::assertSame(30, $data['durasi_menit']);
        self::assertArrayNotHasKey('soal', $data);
        self::assertSame($awal, (int) $this->db->table('progres_tp')->countAllResults());
        self::assertFalse($this->db->tableExists('percobaan_ujian'));
    }

    public function testVersiBaruTidakMenggantikanSnapshotTerpin(): void
    {
        $this->buatVersi(2, 2, []);
        $data = $this->layanan()->periksa(1);
        self::assertTrue($data['boleh_uji']);
        self::assertSame(1, (int) $data['progres']['versi_konten_tp_id']);
    }

    public function testSiswaGenLainTidakDapatMembukaPetunjuk(): void
    {
        $this->expectException(\DomainException::class);
        $this->layanan(200)->periksa(1);
    }

    public function testTanpaAktivasiTpTidakDapatMembukaPetunjuk(): void
    {
        $this->db->table('progres_tp')->where('id',1)->delete();
        $this->expectException(\DomainException::class);
        $this->layanan()->periksa(1);
    }

    public function testBankPadaSnapshotTerpinTidakLengkap(): void
    {
        $versi = $this->db->table('versi_konten_tp')->where('id',1)->get()->getRowArray();
        $data = json_decode($versi['snapshot_json'],true);
        array_pop($data['soal']);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->db->table('versi_konten_tp')->where('id',1)
            ->update(['snapshot_json'=>$json,'checksum'=>hash('sha256',$json)]);
        $hasil = $this->layanan()->periksa(1);
        self::assertFalse($hasil['boleh_uji']);
        self::assertSame(9,$hasil['jumlah_soal']);
    }

    public function testChecksumSnapshotTerpinRusakDitolak(): void
    {
        $this->db->table('versi_konten_tp')->where('id',1)
            ->update(['checksum'=>str_repeat('0',64)]);
        $this->expectException(\DomainException::class);
        $this->layanan()->periksa(1);
    }
}
