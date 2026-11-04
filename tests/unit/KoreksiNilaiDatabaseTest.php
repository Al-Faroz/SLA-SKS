<?php

declare(strict_types=1);

use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/**
 * A14-4: fixture sintetis untuk koreksi langsung oleh Guru G1 dan audit historis V4-0014.
 * Tidak menulis nilai/riwayat ke database localhost.
 */
final class KoreksiNilaiDatabaseTest extends TestCase
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
        $tables=[
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT,status TEXT)',
            'CREATE TABLE pengguna_peran(pengguna_id INTEGER,peran TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER)',
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,guru_id INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,kode_tp TEXT,judul TEXT,jenis_tp TEXT)',
            'CREATE TABLE materi(id INTEGER PRIMARY KEY,tp_id INTEGER,urutan INTEGER,judul TEXT)',
            'CREATE TABLE versi_konten_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,snapshot_json TEXT,checksum TEXT)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,siswa_id INTEGER,tp_id INTEGER,
                paket_semester_id INTEGER,versi_konten_tp_id INTEGER,status TEXT)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jalur TEXT,
                nilai_resume REAL,nilai_hots REAL,nilai_sumatif REAL,nilai_tp REAL,
                status_nilai TEXT,diubah_pada TEXT)',
            'CREATE TABLE resume_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,materi_id INTEGER,
                versi_konten_tp_id INTEGER,isi TEXT,status TEXT,nilai INTEGER,dinilai_oleh INTEGER,
                dinilai_pada TEXT,umpan_balik TEXT,dikirim_pada TEXT)',
            'CREATE TABLE jawaban_hots_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,materi_id INTEGER,
                versi_konten_tp_id INTEGER,isi TEXT,status TEXT,nilai INTEGER,dinilai_oleh INTEGER,
                dinilai_pada TEXT,umpan_balik TEXT,dikirim_pada TEXT)',
            'CREATE TABLE sertifikat_aktif(siswa_id INTEGER,paket_semester_id INTEGER,sertifikat_id INTEGER)',
            'CREATE TABLE log_aktivitas(id INTEGER PRIMARY KEY AUTOINCREMENT,
                pengguna_id INTEGER,peran_aktif TEXT,peristiwa TEXT,catatan TEXT,waktu TEXT)',
            'CREATE TABLE notifikasi(id INTEGER PRIMARY KEY AUTOINCREMENT,penerima_id INTEGER,
                peran_penerima TEXT,jenis TEXT,sumber_id INTEGER,versi_sumber INTEGER,judul TEXT,
                pesan TEXT,tujuan TEXT,kunci_idempoten TEXT UNIQUE,dibuat_pada TEXT,dibaca_pada TEXT)',
            'CREATE TABLE permohonan_koreksi_nilai(id INTEGER PRIMARY KEY AUTOINCREMENT,
                jenis TEXT,sumber_id INTEGER,progres_tp_id INTEGER,versi_konten_tp_id INTEGER,
                versi_koreksi INTEGER,nilai_awal INTEGER,nilai_usulan INTEGER,
                nilai_tp_awal REAL,nilai_tp_usulan REAL,status_nilai_awal TEXT,
                perlu_tinjau_sertifikat INTEGER DEFAULT 0,alasan TEXT,guru_id INTEGER,
                status TEXT,admin_id INTEGER,catatan_keputusan TEXT,dibuat_pada TEXT,diputus_pada TEXT,
                kunci_terbuka TEXT GENERATED ALWAYS AS (
                    CASE WHEN status="diajukan" THEN jenis || ":" || sumber_id ELSE NULL END
                ) STORED,UNIQUE(kunci_terbuka),UNIQUE(jenis,sumber_id,versi_koreksi))',
            'CREATE TABLE riwayat_koreksi_nilai(id INTEGER PRIMARY KEY AUTOINCREMENT,
                permohonan_id INTEGER,aksi TEXT,pelaku_id INTEGER,peran_aktif TEXT,
                nilai_awal INTEGER,nilai_usulan INTEGER,nilai_tp_awal REAL,
                nilai_tp_usulan REAL,versi_koreksi INTEGER,waktu TEXT)',
        ];
        foreach($tables as $sql)$this->db->query($sql);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>11,'nama'=>'Siswa Uji','status'=>'aktif'],
            ['id'=>20,'nama'=>'Guru Uji','status'=>'aktif'],
            ['id'=>21,'nama'=>'Guru Gen Lain','status'=>'aktif'],
            ['id'=>30,'nama'=>'Admin Uji','status'=>'aktif'],
        ]);
        $this->db->table('pengguna_peran')->insertBatch([
            ['pengguna_id'=>11,'peran'=>'siswa'],
            ['pengguna_id'=>20,'peran'=>'guru'],
            ['pengguna_id'=>21,'peran'=>'guru'],
            ['pengguna_id'=>30,'peran'=>'admin'],
        ]);
        $this->db->table('siswa')->insert([
            'id'=>1,'pengguna_id'=>11,'angkatan_id'=>100,
        ]);
        $this->db->table('ref_angkatan')->insert(['id'=>100,'nama'=>'Gen Uji']);
        $this->db->table('ref_mapel')->insert(['id'=>6,'nama'=>'Mapel Uji']);
        $this->db->table('paket_semester')->insert([
            'id'=>5,'angkatan_id'=>100,'mapel_id'=>6,'semester'=>1,
        ]);
        $this->db->table('penugasan_guru_gen')->insert([
            'id'=>1,'angkatan_id'=>100,'mapel_id'=>6,'guru_id'=>20,
        ]);
        $this->db->table('tp')->insert([
            'id'=>7,'paket_semester_id'=>5,'kode_tp'=>'UJ.7.1.1','judul'=>'TP Uji',
            'jenis_tp'=>'non_esensial',
        ]);
        $this->db->table('materi')->insert([
            'id'=>3,'tp_id'=>7,'urutan'=>1,'judul'=>'Materi Uji',
        ]);
        $snapshot=json_encode(['materi'=>[
            ['id'=>3,'judul'=>'Materi Uji','petunjuk_resume'=>'Buat ringkasan',
                'petunjuk_hots'=>'Jawab soal uraian'],
        ]],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $this->db->table('versi_konten_tp')->insert([
            'id'=>4,'tp_id'=>7,'snapshot_json'=>$snapshot,
            'checksum'=>hash('sha256',$snapshot),
        ]);
        $this->db->table('progres_tp')->insert([
            'id'=>8,'siswa_id'=>1,'tp_id'=>7,'paket_semester_id'=>5,
            'versi_konten_tp_id'=>4,'status'=>'tuntas',
        ]);
        $this->db->table('nilai_tp')->insert([
            'id'=>9,'progres_tp_id'=>8,'jalur'=>'belajar_wajib',
            'nilai_resume'=>60,'nilai_hots'=>70,'nilai_sumatif'=>80,'nilai_tp'=>74,
            'status_nilai'=>'final','diubah_pada'=>'2026-11-04 00:00:00',
        ]);
        $this->db->table('resume_materi')->insert([
            'id'=>10,'progres_tp_id'=>8,'materi_id'=>3,'versi_konten_tp_id'=>4,
            'isi'=>'Tugas uji anonim.','status'=>'final','nilai'=>60,
            'dinilai_oleh'=>21,'dinilai_pada'=>'2026-11-04 00:00:00',
            'umpan_balik'=>'Penilaian awal','dikirim_pada'=>'2026-11-04 00:00:00',
        ]);
        $this->db->table('jawaban_hots_materi')->insert([
            'id'=>12,'progres_tp_id'=>8,'materi_id'=>3,'versi_konten_tp_id'=>4,
            'isi'=>'Jawaban HOTS uji','status'=>'final','nilai'=>70,
            'dinilai_oleh'=>21,'dinilai_pada'=>'2026-11-04 00:00:00',
            'umpan_balik'=>'Penilaian awal','dikirim_pada'=>'2026-11-04 00:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    /** Guru G1 mengoreksi langsung; audit versi tanpa Admin. */
    private function langsung(string $role='guru',int $id=20): \App\Services\KoreksiNilaiLangsungService
    {
        return new \App\Services\KoreksiNilaiLangsungService($this->db,static fn():array=>[
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    public function testKoreksiLangsungResumeMengubahAgregatDanMenyimpanHistoriTanpaAdmin(): void
    {
        $hasil=$this->langsung()->proses('resume',10,90,60);
        self::assertTrue($hasil['berubah']);
        self::assertSame(1,$hasil['versi']);
        self::assertSame(80.0,$hasil['nilai_tp_baru']);
        $res=$this->db->table('resume_materi')->where('id',10)->get()->getRowArray();
        self::assertSame(90,(int)$res['nilai']);
        self::assertSame(21,(int)$res['dinilai_oleh']); // riwayat penilai pertama tidak tertimpa
        self::assertSame(80.0,(float)$this->db->table('nilai_tp')->get()->getRow('nilai_tp'));
        $audit=$this->db->table('permohonan_koreksi_nilai')->get()->getRowArray();
        self::assertSame('disetujui',$audit['status']);
        self::assertNull($audit['admin_id']);
        self::assertSame(60,(int)$audit['nilai_awal']);
        self::assertSame(90,(int)$audit['nilai_usulan']);
        self::assertSame('guru',$this->db->table('riwayat_koreksi_nilai')
            ->get()->getRow('peran_aktif'));
        self::assertSame(1,$this->db->table('notifikasi')
            ->where('jenis','koreksi_nilai_diterapkan')->countAllResults());
        $ulang=$this->langsung()->proses('resume',10,90,90);
        self::assertFalse($ulang['berubah']);
        self::assertSame(1,$this->db->table('riwayat_koreksi_nilai')->countAllResults());
    }

    public function testGuruG1BolehKoreksiBerulangNomorVersiBertambah(): void
    {
        $this->langsung()->proses('hots',12,0,70);
        $hasil=$this->langsung()->proses('hots',12,50,0);
        self::assertSame(2,$hasil['versi']);
        self::assertSame(70.0,$hasil['nilai_tp_baru']);
        self::assertSame(2,$this->db->table('riwayat_koreksi_nilai')->countAllResults());
        self::assertSame(50,(int)$this->db->table('jawaban_hots_materi')
            ->where('id',12)->get()->getRow('nilai'));
        self::assertSame(2,$this->db->table('notifikasi')
            ->where('jenis','koreksi_nilai_diterapkan')->countAllResults());
    }

    public function testGuruLuarGenDanRoleAdminTidakBisaMemproses(): void
    {
        foreach ([['guru',21],['admin',30],['siswa',11]] as [$role,$uid]) {
            try {
                $this->langsung($role,$uid)->proses('resume',10,95,60);
                self::fail('Peran lain atau Guru luar Gen tidak boleh mengoreksi.');
            }catch(\DomainException $e){
                self::assertNotSame('',$e->getMessage());
            }
        }
        self::assertSame(60,(int)$this->db->table('resume_materi')
            ->where('id',10)->get()->getRow('nilai'));
    }

    public function testGuruG1PenggantiBolehKoreksiTanpaMengubahPenilaiHistoris(): void
    {
        $this->db->table('penugasan_guru_gen')->where('id',1)->update(['guru_id'=>21]);
        $hasil=$this->langsung('guru',21)->proses('resume',10,95,60);
        self::assertTrue($hasil['berubah']);
        self::assertSame(21,(int)$this->db->table('resume_materi')
            ->where('id',10)->get()->getRow('dinilai_oleh'));
        self::assertSame(95,(int)$this->db->table('resume_materi')
            ->where('id',10)->get()->getRow('nilai'));
    }

    public function testKoreksiKomponenSebelumTpTuntasTidakMengisiNilaiFinalFiktif(): void
    {
        $this->db->table('progres_tp')->where('id',8)->update(['status'=>'belajar']);
        $this->db->table('nilai_tp')->where('id',9)->delete();
        $hasil=$this->langsung()->proses('resume',10,80,60);
        self::assertSame(1,$hasil['versi']);
        self::assertNull($hasil['nilai_tp_baru']);
        self::assertSame(80,(int)$this->db->table('resume_materi')
            ->where('id',10)->get()->getRow('nilai'));
        self::assertSame(0,$this->db->table('nilai_tp')->countAllResults());
        self::assertSame('belajar',$this->db->table('progres_tp')->get()->getRow('status'));
    }

    public function testKoreksiLangsungSkorTidakSahDanSnapshotPalsuDitolak(): void
    {
        foreach ([-1,101] as $nilai) {
            try {
                $this->langsung()->proses('resume',10,$nilai,60);
                self::fail('Nilai di luar 0–100 harus ditolak.');
            }catch(\DomainException $e){self::assertNotSame('',$e->getMessage());}
        }
        $this->db->table('versi_konten_tp')->where('id',4)
            ->update(['snapshot_json'=>'{"materi":[]}']);
        $this->expectException(\DomainException::class);
        $this->langsung()->proses('resume',10,80,60);
    }

    public function testTabKoreksiLamaTidakDapatMenimpaSkorTerbaru(): void
    {
        $this->langsung()->proses('resume',10,80,60);
        try {
            // Tab kedua masih memegang nilai awal 60, padahal sudah 80.
            $this->langsung()->proses('resume',10,95,60);
            self::fail('Tab lama tidak boleh menimpa nilai dari tab pertama.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Buka ulang Koreksi Nilai',$e->getMessage());
        }
        self::assertSame(80,(int)$this->db->table('resume_materi')
            ->where('id',10)->get()->getRow('nilai'));
        self::assertSame(1,$this->db->table('riwayat_koreksi_nilai')->countAllResults());
        self::assertSame(78.0,(float)$this->db->table('nilai_tp')->get()->getRow('nilai_tp'));
    }

    public function testNilaiAwalYangDipalsukanDitolakMeskiNilaiBaruValid(): void
    {
        $this->expectException(\DomainException::class);
        $this->langsung()->proses('resume',10,90,50);
    }

    public function testPengalihanGuruG1SebelumKoreksiHarusDitolak(): void
    {
        // Guru pernah membuka nilai, tetapi sebelum Proses, pengampu Gen pindah.
        $this->db->table('penugasan_guru_gen')->where('id',1)
            ->update(['guru_id'=>21]);
        try {
            $this->langsung('guru',20)->proses('resume',10,90,60);
            self::fail('Hak G1 yang sudah dialihkan tidak boleh melakukan koreksi.');
        } catch (\DomainException $e) {
            self::assertNotSame('',$e->getMessage());
        }
        self::assertSame(60,(int)$this->db->table('resume_materi')
            ->where('id',10)->get()->getRow('nilai'));
        self::assertSame(0,$this->db->table('riwayat_koreksi_nilai')->countAllResults());
    }

}
