<?php

declare(strict_types=1);

use App\Services\NotifikasiService;
use App\Services\KebijakanPrivasiService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

final class A13NotifikasiPrivasiDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db=\Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,
            'foreignKeys'=>false,'busyTimeout'=>1000,'synchronous'=>null,'failover'=>[],
        ],false);
        foreach ([
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,status TEXT NOT NULL)',
            'CREATE TABLE pengguna_peran(pengguna_id INTEGER,peran TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,guru_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,status TEXT)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,siswa_id INTEGER,paket_semester_id INTEGER,tp_id INTEGER)',
            'CREATE TABLE resume_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,nilai INTEGER)',
            'CREATE TABLE jawaban_hots_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,nilai INTEGER)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,siswa_id INTEGER,jenis TEXT)',
            'CREATE TABLE keputusan_ujian_batal(id INTEGER PRIMARY KEY,percobaan_ujian_id INTEGER)',
            'CREATE TABLE sertifikat(id INTEGER PRIMARY KEY,siswa_id INTEGER)',
            'CREATE TABLE notifikasi(id INTEGER PRIMARY KEY AUTOINCREMENT,
                penerima_id INTEGER,peran_penerima TEXT,jenis TEXT,sumber_id INTEGER,
                versi_sumber INTEGER,judul TEXT,pesan TEXT,tujuan TEXT,
                kunci_idempoten TEXT UNIQUE,dibuat_pada TEXT,dibaca_pada TEXT)',
            'CREATE TABLE kebijakan_privasi(id INTEGER PRIMARY KEY AUTOINCREMENT,
                versi TEXT UNIQUE,tanggal_berlaku TEXT,teks TEXT,checksum_sha256 TEXT,
                status TEXT,revisi_draf INTEGER,dibuat_oleh INTEGER,diubah_oleh INTEGER,
                diterbitkan_oleh INTEGER,dibuat_pada TEXT,diubah_pada TEXT,diterbitkan_pada TEXT)',
            'CREATE TABLE riwayat_kebijakan_privasi(id INTEGER PRIMARY KEY AUTOINCREMENT,
                kebijakan_privasi_id INTEGER,aksi TEXT,admin_id INTEGER,
                revisi_lama INTEGER,revisi_baru INTEGER,checksum_lama TEXT,
                checksum_baru TEXT,terjadi_pada TEXT)',
            'CREATE TABLE log_aktivitas(id INTEGER PRIMARY KEY AUTOINCREMENT,
                pengguna_id INTEGER,peran_aktif TEXT,peristiwa TEXT,catatan TEXT,waktu TEXT)',
        ] as $sql)$this->db->query($sql);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>11,'status'=>'aktif'],['id'=>12,'status'=>'aktif'],
            ['id'=>20,'status'=>'aktif'],['id'=>30,'status'=>'aktif'],
        ]);
        $this->db->table('pengguna_peran')->insertBatch([
            ['pengguna_id'=>11,'peran'=>'siswa'],
            ['pengguna_id'=>12,'peran'=>'siswa'],
            ['pengguna_id'=>20,'peran'=>'guru'],
            ['pengguna_id'=>30,'peran'=>'admin'],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>1,'pengguna_id'=>11,'angkatan_id'=>100],
            ['id'=>2,'pengguna_id'=>12,'angkatan_id'=>200],
        ]);
        $this->db->table('paket_semester')->insert([
            'id'=>5,'angkatan_id'=>100,'mapel_id'=>6,
        ]);
        $this->db->table('penugasan_guru_gen')->insert([
            'id'=>1,'guru_id'=>20,'angkatan_id'=>100,'mapel_id'=>6,
        ]);
        $this->db->table('tp')->insert([
            'id'=>7,'paket_semester_id'=>5,'status'=>'siap',
        ]);
        $this->db->table('progres_tp')->insert([
            'id'=>8,'siswa_id'=>1,'paket_semester_id'=>5,'tp_id'=>7,
        ]);
        $this->db->table('resume_materi')->insert([
            'id'=>9,'progres_tp_id'=>8,'nilai'=>80,
        ]);
        $this->db->table('percobaan_ujian')->insert([
            'id'=>14,'progres_tp_id'=>8,'siswa_id'=>1,'jenis'=>'diagnostik',
        ]);
        $this->db->table('sertifikat')->insert([
            'id'=>10,'siswa_id'=>1,
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();parent::tearDown();
    }

    private function notif(int $user,string $role): NotifikasiService
    {
        return new NotifikasiService($this->db,static fn():array=>[
            'id'=>$user,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    private function priv(string $role='admin',int $id=30): KebijakanPrivasiService
    {
        return new KebijakanPrivasiService($this->db,static fn():array=>[
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    public function testNotifikasiKunciUnikDanPenerimaBerbeda(): void
    {
        NotifikasiService::catat($this->db,'nilai_resume_final',11,9);
        NotifikasiService::catat($this->db,'nilai_resume_final',11,9);
        NotifikasiService::catat($this->db,'sertifikat_awal_terbit',11,10);
        NotifikasiService::catat($this->db,'nilai_resume_final',12,9);
        self::assertSame(3,$this->db->table('notifikasi')->countAllResults());
        self::assertSame(2,$this->notif(11,'siswa')->belumDibaca());
        self::assertSame(1,$this->notif(12,'siswa')->belumDibaca());
        self::assertSame(0,$this->notif(30,'admin')->belumDibaca());
        $l=$this->notif(11,'siswa')->daftar(1,1);
        self::assertSame(2,$l['jumlah']);
        self::assertSame(2,$l['total_halaman']);
    }

    public function testPemilikBisaMenandaiDibacaSedangkanLainnyaDitolak(): void
    {
        NotifikasiService::catat($this->db,'nilai_resume_final',11,9);
        $id=(int)$this->db->table('notifikasi')->get()->getRow('id');
        self::assertSame('siswa/laporan',$this->notif(11,'siswa')->tujuanBerizin($id));
        $this->notif(11,'siswa')->tandaiDibaca($id);
        $this->notif(11,'siswa')->tandaiDibaca($id);
        self::assertSame(0,$this->notif(11,'siswa')->belumDibaca());
        foreach ([$this->notif(12,'siswa'),$this->notif(30,'admin')] as $lain) {
            try{$lain->tandaiDibaca($id);self::fail('Tidak boleh baca notifikasi orang lain');}
            catch(\DomainException $e){self::assertNotSame('',$e->getMessage());}
            try{$lain->tujuanBerizin($id);self::fail('Tidak boleh buka sumber orang lain');}
            catch(\DomainException $e){self::assertNotSame('',$e->getMessage());}
        }
    }

    public function testGuruHanyaMembukaSumberDariGenMapelSaatIni(): void
    {
        NotifikasiService::catat($this->db,'permintaan_akses_ulang',20,14);
        $id=(int)$this->db->table('notifikasi')->get()->getRow('id');
        self::assertSame('guru/akses-ulang',$this->notif(20,'guru')->tujuanBerizin($id));
        $this->db->table('penugasan_guru_gen')->where('id',1)->update(['guru_id'=>null]);
        try{$this->notif(20,'guru')->tujuanBerizin($id);self::fail('Penugasan berubah');}
        catch(\DomainException $e){self::assertNotSame('',$e->getMessage());}
        self::assertFalse($this->notif(20,'guru')->daftar()['baris'][0]['boleh_buka']);
        $this->notif(20,'guru')->tandaiDibaca($id);
        self::assertSame(0,$this->notif(20,'guru')->belumDibaca());
    }

    public function testPrivasiDrafDanTerbitImmutableDenganHistori(): void
    {
        self::assertNull($this->priv()->publikAktif());
        $id=$this->priv()->simpanDraf([
            'versi'=>'v2026.1','tanggal_berlaku'=>'2020-01-01',
            'teks'=>'Naskah uji yang disahkan dalam simulasi, tanpa data asli.',
        ]);
        self::assertNull($this->priv()->publikAktif());
        $this->priv()->simpanDraf([
            'versi'=>'v2026.1','tanggal_berlaku'=>'2020-01-01',
            'teks'=>'Naskah uji simulasi versi revisi.',
            'revisi_draf'=>'1',
        ],$id);
        $this->priv()->terbitkan($id,2,'1');
        self::assertSame('v2026.1',$this->priv()->publikAktif()['versi']);
        self::assertCount(3,$this->priv()->riwayat($id));
        self::assertSame('terbit',$this->priv()->rincian($id)['status']);
        try{$this->priv()->simpanDraf([
            'versi'=>'v2026.1','tanggal_berlaku'=>'2020-01-01',
            'teks'=>'Hapus histori','revisi_draf'=>'2',
        ],$id);self::fail('Versi terbit immutable');}
        catch(\DomainException $e){self::assertNotSame('',$e->getMessage());}
    }

    public function testPrivasiVersiMendatangBelumTampilDanHanyaAdminKelola(): void
    {
        $id=$this->priv()->simpanDraf([
            'versi'=>'v2099.1','tanggal_berlaku'=>'2099-12-31',
            'teks'=>'Versi yang belum berlaku.',
        ]);
        $this->priv()->terbitkan($id,1,'1');
        self::assertNull($this->priv()->publikAktif());
        try{$this->priv('guru',20)->daftar();self::fail('Guru tidak boleh kelola');}
        catch(\DomainException $e){self::assertNotSame('',$e->getMessage());}
    }

    public function testPublikHanyaVersiTerbitTerbaruYangSudahBerlaku(): void
    {
        foreach ([['a','2010-01-01'],['b','2020-01-01']] as [$versi,$tgl]) {
            $id=$this->priv()->simpanDraf([
                'versi'=>$versi,'tanggal_berlaku'=>$tgl,'teks'=>'Naskah '.$versi,
            ]);
            $this->priv()->terbitkan($id,1,'1');
        }
        self::assertSame('b',$this->priv()->publikAktif()['versi']);
        self::assertSame(2,$this->db->table('kebijakan_privasi')->countAllResults());
        self::assertSame(4,$this->db->table('riwayat_kebijakan_privasi')->countAllResults());
    }
}
