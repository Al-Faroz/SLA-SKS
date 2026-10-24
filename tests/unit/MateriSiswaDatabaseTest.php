<?php

declare(strict_types=1);

use App\Services\MateriSiswaService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/**
 * Integrasi SQLite in-memory, akun Gen dan file media sintetis.
 * Tidak membuat Migration/Seeder; tidak menyentuh database siswa nyata.
 */
final class MateriSiswaDatabaseTest extends TestCase
{
    private BaseConnection $db;
    private string $file;

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
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT,semester_mulai INTEGER)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER,semester_mulai INTEGER)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER,nomor_versi INTEGER,status TEXT)',
            'CREATE TABLE penugasan_paket(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER,paket_id INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,kode_tp TEXT,judul TEXT,urutan_atp INTEGER,status TEXT,rumusan TEXT,indikator TEXT)',
            'CREATE TABLE versi_konten_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,nomor_versi INTEGER,snapshot_json TEXT,checksum TEXT)',
            'CREATE TABLE materi(id INTEGER PRIMARY KEY,tp_id INTEGER,urutan INTEGER,judul TEXT,jenis TEXT,aktif INTEGER)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,siswa_id INTEGER,tp_id INTEGER,versi_konten_tp_id INTEGER,versi_paket_yang_ditugaskan INTEGER,status TEXT,dimulai_pada TEXT)',
            'CREATE TABLE berkas(id INTEGER PRIMARY KEY,tp_id INTEGER,nama_acak TEXT,mime TEXT,ukuran_byte INTEGER)',
        ] as $query) $this->db->query($query);
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'GENA','nama'=>'Gen A','semester_mulai'=>4],
            ['id'=>2,'kode'=>'GENB','nama'=>'Gen B','semester_mulai'=>4],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>11,'pengguna_id'=>101,'angkatan_id'=>1,'semester_mulai'=>null],
            ['id'=>12,'pengguna_id'=>102,'angkatan_id'=>1,'semester_mulai'=>null],
            ['id'=>22,'pengguna_id'=>202,'angkatan_id'=>2,'semester_mulai'=>null],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>5,'kode'=>'SKI','nama'=>'SKI']);
        $this->db->table('paket_semester')->insert([
            'id'=>8,'angkatan_id'=>1,'mapel_id'=>5,'semester'=>4,
            'tahun_ajaran_id'=>1,'nomor_versi'=>1,'status'=>'aktif',
        ]);
        $this->db->table('penugasan_paket')->insert([
            'id'=>8,'angkatan_id'=>1,'mapel_id'=>5,'semester'=>4,
            'tahun_ajaran_id'=>1,'paket_id'=>8,
        ]);
        $this->db->table('tp')->insert([
            'id'=>1,'paket_semester_id'=>8,'kode_tp'=>'TP-1',
            'judul'=>'Materi sejarah','urutan_atp'=>1,'status'=>'siap',
            'rumusan'=>'Rumusan', 'indikator'=>'Indikator',
        ]);
        $this->db->table('materi')->insertBatch([
            ['id'=>31,'tp_id'=>1,'urutan'=>1,'judul'=>'PDF materi','jenis'=>'pdf','aktif'=>1],
            ['id'=>32,'tp_id'=>1,'urutan'=>2,'judul'=>'Video materi','jenis'=>'youtube','aktif'=>1],
        ]);
        $snap = [
            'format'=>'SLA-SKS-TP-V1','tp'=>['id'=>1,'status'=>'siap'],
            'materi'=>[
                ['id'=>31,'tp_id'=>1,'urutan'=>1,'aktif'=>1,'judul'=>'PDF historis',
                    'jenis'=>'pdf','berkas_id'=>9,'min_waktu_detik'=>60,
                    'petunjuk_resume'=>'Tulis ringkasan',
                    'petunjuk_hots'=>'Analisis sejarah'],
                ['id'=>32,'tp_id'=>1,'urutan'=>2,'aktif'=>1,'judul'=>'YouTube historis',
                    'jenis'=>'youtube','berkas_id'=>null,
                    'url_youtube'=>'https://www.youtube.com/watch?v=abcdefghijk',
                    'min_waktu_detik'=>90,'petunjuk_resume'=>'Rangkum',
                    'petunjuk_hots'=>'Tanggapi'],
            ],'soal'=>[],
        ];
        $json = json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->db->table('versi_konten_tp')->insert([
            'id'=>3,'tp_id'=>1,'nomor_versi'=>1,
            'snapshot_json'=>$json,'checksum'=>hash('sha256',$json),
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>11,'siswa_id'=>11,'tp_id'=>1,'versi_konten_tp_id'=>3,
                'versi_paket_yang_ditugaskan'=>1,'status'=>'belajar','dimulai_pada'=>'2026-10-24 00:00:00'],
            ['id'=>12,'siswa_id'=>12,'tp_id'=>1,'versi_konten_tp_id'=>3,
                'versi_paket_yang_ditugaskan'=>1,'status'=>'tuntas','dimulai_pada'=>'2026-10-24 00:00:00'],
        ]);
        $nama = str_repeat('a',48) . '.pdf';
        $folder = rtrim(WRITEPATH, '/\\') . '/uploads/materi';
        if (!is_dir($folder)) mkdir($folder, 0700, true);
        $this->file = $folder . '/' . $nama;
        file_put_contents($this->file, "%PDF-1.4\n% uji sintetis\n");
        $this->db->table('berkas')->insert([
            'id'=>9,'tp_id'=>1,'nama_acak'=>$nama,'mime'=>'application/pdf',
            'ukuran_byte'=>filesize($this->file),
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->file) && is_file($this->file)) unlink($this->file);
        if (isset($this->db)) $this->db->close();
        parent::tearDown();
    }

    private function svc(int $userId = 101, string $role='siswa'): MateriSiswaService
    {
        return new MateriSiswaService($this->db, static fn (): array => [
            'id'=>$userId,'peran_aktif'=>$role,'status'=>'aktif',
            'wajib_ganti_sandi'=>0,
        ]);
    }

    public function testGagalDiagnostikBisaMelihatMateriPertamaTapiKeduaTerkunci(): void
    {
        $all = $this->svc()->daftar(1)['materi'];
        self::assertCount(2,$all);
        self::assertTrue($all[0]['buka']);
        self::assertFalse($all[1]['buka']);
        self::assertSame('PDF historis',$all[0]['judul']);
        self::assertSame('Tulis ringkasan',$this->svc()->lihat(1,31)['materi']['petunjuk_resume']);
        $this->expectException(\DomainException::class);
        $this->svc()->lihat(1,32);
    }

    public function testGantiIdUrlMediaMateriKeduaDitolak(): void
    {
        $this->expectException(\DomainException::class);
        $this->svc()->media(1,32);
    }

    public function testBerkasPrivatMateriPertamaBisaDibaca(): void
    {
        $media = $this->svc()->media(1,31);
        self::assertSame('application/pdf',$media['mime']);
        self::assertSame(filesize($this->file),$media['bytes']);
        self::assertSame($this->file,$media['path']);
    }

    public function testTPtuntasBolehMembukaSemuaMateriSecaraSukarela(): void
    {
        $all=$this->svc(102)->daftar(1)['materi'];
        self::assertTrue($all[0]['buka']);
        self::assertTrue($all[1]['buka']);
        self::assertTrue($all[1]['sukarela']);
        $detail=$this->svc(102)->lihat(1,32);
        self::assertStringContainsString('youtube-nocookie.com/embed/abcdefghijk',$detail['video_url']);
        self::assertNull($detail['media_url']);
    }

    public function testGenLainTidakDapatMembukaMedia(): void
    {
        $this->expectException(\DomainException::class);
        $this->svc(202)->media(1,31);
    }

    public function testGuruTidakDapatMengaksesMateriSiswa(): void
    {
        $this->expectException(\DomainException::class);
        $this->svc(101,'guru')->lihat(1,31);
    }

    public function testStatusDiagnostikDanChecksumRusakMemblokirAkses(): void
    {
        $this->db->table('progres_tp')->where('id',11)->update(['status'=>'diagnostik']);
        try {
            $this->svc()->lihat(1,31);
            self::fail('Diagnostik belum sah tidak boleh membuka Materi.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Diagnostik',$e->getMessage());
        }
        $this->db->table('progres_tp')->where('id',11)->update(['status'=>'belajar']);
        $this->db->table('versi_konten_tp')->where('id',3)
            ->update(['checksum'=>str_repeat('0',64)]);
        $this->expectException(\DomainException::class);
        $this->svc()->lihat(1,31);
    }

    public function testGETTidakMembuatProgresBaru(): void
    {
        self::assertSame(2,$this->db->table('progres_tp')->countAllResults());
        $this->svc()->daftar(1);
        $this->svc()->lihat(1,31);
        $this->svc()->media(1,31);
        self::assertSame(2,$this->db->table('progres_tp')->countAllResults());
    }
}
