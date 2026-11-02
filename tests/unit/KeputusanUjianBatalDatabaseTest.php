<?php

declare(strict_types=1);

use App\Services\KeputusanUjianBatalService;
use App\Services\PermintaanAksesUlangSiswaService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** Tes mandiri: permintaan eksplisit siswa, izin Guru G1, audit, satu pakai. */
final class KeputusanUjianBatalDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,'foreignKeys'=>false,
            'busyTimeout'=>1000,'synchronous'=>null,'failover'=>[],
        ], false);
        foreach ([
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,kode_tp TEXT,judul TEXT)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,siswa_id INTEGER,tp_id INTEGER,paket_semester_id INTEGER,versi_konten_tp_id INTEGER,status TEXT,diubah_pada TEXT)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,guru_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,versi_konten_tp_id INTEGER,jenis TEXT,tahap TEXT,nomor_percobaan INTEGER,status TEXT,alasan_batal TEXT,waktu_selesai TEXT,skor REAL,kkm INTEGER)',
            'CREATE TABLE keputusan_ujian_batal(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER UNIQUE,keputusan TEXT,guru_id INTEGER,alasan TEXT,diputus_pada TEXT,digunakan_pada TEXT)',
            'CREATE TABLE riwayat_keputusan_ujian_batal(id INTEGER PRIMARY KEY AUTOINCREMENT,keputusan_ujian_batal_id INTEGER,guru_id INTEGER,keputusan_lama TEXT,keputusan_baru TEXT,alasan TEXT,terjadi_pada TEXT)',
            'CREATE TABLE riwayat_status_ujian(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,status_lama TEXT,status_baru TEXT,alasan TEXT,terjadi_pada TEXT)',
        ] as $sql) $this->db->query($sql);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>4,'nama'=>'Guru A'],['id'=>5,'nama'=>'Guru B'],
            ['id'=>100,'nama'=>'Siswa Gen A'],['id'=>200,'nama'=>'Siswa Gen B'],
        ]);
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'nama'=>'Gen A'],['id'=>2,'nama'=>'Gen B'],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>7,'nama'=>'SKI']);
        $this->db->table('siswa')->insertBatch([
            ['id'=>1,'pengguna_id'=>100,'angkatan_id'=>1],
            ['id'=>2,'pengguna_id'=>200,'angkatan_id'=>2],
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>10,'angkatan_id'=>1,'mapel_id'=>7],
            ['id'=>20,'angkatan_id'=>2,'mapel_id'=>7],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>10,'paket_semester_id'=>10,'kode_tp'=>'SKI.8.1.1','judul'=>'TP Uji'],
            ['id'=>20,'paket_semester_id'=>20,'kode_tp'=>'SKI.8.1.1','judul'=>'TP Asing'],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>11,'siswa_id'=>1,'tp_id'=>10,'paket_semester_id'=>10,'versi_konten_tp_id'=>31,'status'=>'perlu_bantuan'],
            ['id'=>22,'siswa_id'=>2,'tp_id'=>20,'paket_semester_id'=>20,'versi_konten_tp_id'=>32,'status'=>'perlu_bantuan'],
        ]);
        $this->db->table('penugasan_guru_gen')->insertBatch([
            ['id'=>1,'guru_id'=>4,'angkatan_id'=>1,'mapel_id'=>7],
            ['id'=>2,'guru_id'=>5,'angkatan_id'=>2,'mapel_id'=>7],
        ]);
        $this->db->table('percobaan_ujian')->insertBatch([
            ['id'=>101,'progres_tp_id'=>11,'versi_konten_tp_id'=>31,'jenis'=>'diagnostik',
                'tahap'=>'normal','nomor_percobaan'=>1,'status'=>'dibatalkan_menunggu_guru',
                'alasan_batal'=>'Koneksi terputus','skor'=>null,'kkm'=>75],
            ['id'=>202,'progres_tp_id'=>22,'versi_konten_tp_id'=>32,'jenis'=>'diagnostik',
                'tahap'=>'normal','nomor_percobaan'=>1,'status'=>'dibatalkan_menunggu_guru',
                'alasan_batal'=>'Koneksi terputus','skor'=>null,'kkm'=>75],
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function guru(int $id = 4, string $role = 'guru'): KeputusanUjianBatalService
    {
        return new KeputusanUjianBatalService($this->db, static fn (): array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    private function siswa(int $id = 100): PermintaanAksesUlangSiswaService
    {
        return new PermintaanAksesUlangSiswaService($this->db, static fn (): array => [
            'id'=>$id,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    public function testHanyaDuaIzinAksesUlangDanGenTerisolasi(): void
    {
        self::assertSame(['ulang_diagnostik'],
            KeputusanUjianBatalService::pilihan('diagnostik'));
        self::assertSame(['ulang_sumatif'],
            KeputusanUjianBatalService::pilihan('sumatif'));
        self::assertSame([], KeputusanUjianBatalService::pilihan('pra_semester'));
        self::assertCount(0, $this->guru()->antrean()); // tidak masuk antrean tanpa permintaan siswa
        $this->siswa()->minta(10, 'diagnostik');
        $this->siswa()->minta(10, 'diagnostik'); // idempoten
        self::assertSame(1, $this->db->table('riwayat_status_ujian')->countAllResults());
        $queue = $this->guru()->antrean();
        self::assertCount(1, $queue);
        self::assertSame(101, (int) $queue[0]['id']);
        try {
            $this->guru()->lihat(202);
            self::fail('Guru A tidak boleh membaca ujian Gen B.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Gen–Mapel', $e->getMessage());
        }
        $this->expectException(\DomainException::class);
        $this->guru(4,'admin')->putuskan(101,'langsung_belajar','Insiden sudah diperiksa.');
    }

    public function testUlangDiagnostikSekaliPakaiDenganAuditIdempoten(): void
    {
        $this->siswa()->minta(10, 'diagnostik');
        $this->guru()->putuskan(101,'ulang_diagnostik','Koneksi buruk, izinkan ujian ulang.');
        self::assertSame('diagnostik',$this->db->table('progres_tp')
            ->where('id',11)->get()->getRow('status'));
        $izin = KeputusanUjianBatalService::izin($this->db,101,'ulang_diagnostik');
        self::assertNotNull($izin);
        self::assertSame(1,$this->db->table('riwayat_keputusan_ujian_batal')->countAllResults());
        $this->guru()->putuskan(101,'ulang_diagnostik','Permintaan ulang tanpa dampak.');
        self::assertSame(1,$this->db->table('riwayat_keputusan_ujian_batal')->countAllResults());
        KeputusanUjianBatalService::gunakan($this->db,(int)$izin['id']);
        self::assertNull(KeputusanUjianBatalService::izin($this->db,101,'ulang_diagnostik'));
        self::assertSame('dibatalkan_menunggu_guru',
            $this->db->table('percobaan_ujian')->where('id',101)->get()->getRow('status'));
        self::assertNull($this->db->table('percobaan_ujian')->where('id',101)->get()->getRow('skor'));
        $this->expectException(\DomainException::class);
        KeputusanUjianBatalService::gunakan($this->db,(int)$izin['id']);
    }

    public function testKeputusanLangsungBelajarLamaTidakBolehDibuatLagi(): void
    {
        $this->siswa()->minta(10, 'diagnostik');
        $this->expectException(\DomainException::class);
        $this->guru()->putuskan(101,'langsung_belajar','Opsi historis tidak berlaku.');
    }

    public function testSumatifDitundaLaluDiizinkanUlangTapiTidakMemotongKuotaSah(): void
    {
        $this->db->table('progres_tp')->where('id',11)->update(['status'=>'perlu_bantuan']);
        $this->db->table('percobaan_ujian')->where('id',101)
            ->update(['status'=>'selesai_sah','skor'=>90.0]);
        $this->db->table('percobaan_ujian')->insert([
            'id'=>103,'progres_tp_id'=>11,'versi_konten_tp_id'=>31,
            'jenis'=>'sumatif','tahap'=>'normal','nomor_percobaan'=>1,
            'status'=>'dibatalkan_menunggu_guru','alasan_batal'=>'Fullscreen',
            'skor'=>null,'kkm'=>75,
        ]);
        $this->siswa()->minta(10, 'sumatif');
        $this->guru()->putuskan(103,'ulang_sumatif','Insiden selesai diperiksa, izin ulang.');
        self::assertSame('sumatif',$this->db->table('progres_tp')
            ->where('id',11)->get()->getRow('status'));
        self::assertCount(1,$this->db->table('riwayat_keputusan_ujian_batal')
            ->get()->getResultArray());
        self::assertNotNull(KeputusanUjianBatalService::izin($this->db,103,'ulang_sumatif'));
        self::assertNull($this->db->table('percobaan_ujian')->where('id',103)->get()->getRow('skor'));
    }

    public function testPermintaanLintasGenDanJenisTidakSahDitolak(): void
    {
        try {
            $this->siswa(200)->minta(10, 'diagnostik');
            self::fail('Siswa tidak boleh mengajukan permintaan untuk TP Gen lain.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('tidak sedang', $e->getMessage());
        }
        $this->expectException(\DomainException::class);
        $this->siswa()->minta(10, 'pra_semester');
    }

    public function testJenisKeputusanSalahDitolakTanpaDataBaru(): void
    {
        $this->siswa()->minta(10, 'diagnostik');
        try {
            $this->guru()->putuskan(101,'ulang_sumatif','Ulang ujian.');
            self::fail('Pilihan Sumatif tidak boleh dipakai untuk Diagnostik.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('jenis', $e->getMessage());
        }
        self::assertSame(0, $this->db->table('keputusan_ujian_batal')->countAllResults());
        self::assertSame('perlu_bantuan',$this->db->table('progres_tp')
            ->where('id',11)->get()->getRow('status'));
    }
}
