<?php

declare(strict_types=1);

use App\Services\MesinPraSemesterSiswaService;
use App\Services\PraSemesterGuruService;
use App\Services\PraSemesterJadwalService;
use App\Services\PraSemesterSiswaService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** Data fiktif anonim; alur DB A8 menggunakan tabel yang tersedia pada V4-0001. */
final class PraSemesterDatabaseTest extends TestCase
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
        $sql=[
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER,status TEXT)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,guru_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE penugasan_paket(id INTEGER PRIMARY KEY,paket_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER,semester_mulai INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,jenis_tp TEXT,status TEXT)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,siswa_id INTEGER,paket_semester_id INTEGER,status TEXT)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,nomor_bab INTEGER,nama_bab TEXT)',
            'CREATE TABLE ujian_pra_semester(id INTEGER PRIMARY KEY AUTOINCREMENT,paket_semester_id INTEGER,status TEXT,mulai_pada TEXT,selesai_pada TEXT,jumlah_soal INTEGER,durasi_menit INTEGER,kkm_khusus INTEGER,dibuat_oleh INTEGER,dibuat_pada TEXT)',
            'CREATE TABLE soal_pra_semester(id INTEGER PRIMARY KEY AUTOINCREMENT,ujian_pra_semester_id INTEGER,bab_id INTEGER,urutan INTEGER,pertanyaan TEXT,konten_terstruktur_json TEXT,opsi_a TEXT,opsi_b TEXT,opsi_c TEXT,opsi_d TEXT,opsi_e TEXT,kunci TEXT,aktif INTEGER)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY AUTOINCREMENT,token_publik TEXT,progres_tp_id INTEGER,versi_konten_tp_id INTEGER,ujian_pra_semester_id INTEGER,siswa_id INTEGER,jenis TEXT,tahap TEXT,nomor_percobaan INTEGER,status TEXT,profil_keamanan TEXT,jumlah_soal INTEGER,kkm INTEGER,durasi_detik INTEGER,sisa_detik INTEGER,waktu_mulai TEXT,waktu_aktif_terakhir TEXT,last_seen_server_at TEXT,jeda_mulai_pada TEXT,jeda_fullscreen_total_detik INTEGER DEFAULT 0,jeda_koneksi_total_detik INTEGER DEFAULT 0,jumlah_pelanggaran_fullscreen INTEGER DEFAULT 0,waktu_selesai TEXT,alasan_batal TEXT,skor REAL,jumlah_benar INTEGER,dibuat_pada TEXT,diubah_pada TEXT)',
            'CREATE TABLE butir_percobaan(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,soal_asal_id INTEGER,soal_pra_semester_id INTEGER,posisi INTEGER,pertanyaan TEXT,konten_terstruktur_json TEXT,opsi_acak_json TEXT,kunci_acak TEXT)',
            'CREATE TABLE jawaban_percobaan(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,butir_percobaan_id INTEGER,opsi_dipilih TEXT,waktu_disimpan TEXT)',
            'CREATE TABLE riwayat_status_ujian(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,status_lama TEXT,status_baru TEXT,alasan TEXT,terjadi_pada TEXT)',
            'CREATE TABLE segmen_jeda_ujian(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,jenis TEXT,dimulai_pada TEXT,berakhir_pada TEXT,durasi_detik INTEGER,alasan TEXT)',
            'CREATE TABLE pelanggaran_ujian(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,kunci_insiden TEXT,kategori TEXT,dihitung INTEGER,alasan TEXT,terjadi_pada TEXT)',
            'CREATE TABLE pengaturan(kunci TEXT PRIMARY KEY,nilai TEXT)',
            'CREATE TABLE log_aktivitas(id INTEGER PRIMARY KEY AUTOINCREMENT,pengguna_id INTEGER,peran_aktif TEXT,peristiwa TEXT,catatan TEXT,waktu TEXT)',
        ];
        foreach($sql as $q)$this->db->query($q);
        $this->db->table('ref_angkatan')->insert(['id'=>1,'nama'=>'Gen Uji']);
        $this->db->table('ref_mapel')->insertBatch([
            ['id'=>1,'nama'=>'SKI'],['id'=>2,'nama'=>'Fikih']
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>3,'status'=>'aktif'],
            ['id'=>12,'angkatan_id'=>1,'mapel_id'=>2,'semester'=>4,'tahun_ajaran_id'=>3,'status'=>'aktif'],
        ]);
        $this->db->table('penugasan_guru_gen')->insertBatch([
            ['id'=>1,'guru_id'=>10,'angkatan_id'=>1,'mapel_id'=>1],
            ['id'=>2,'guru_id'=>11,'angkatan_id'=>1,'mapel_id'=>2],
        ]);
        $this->db->table('penugasan_paket')->insert([
            'id'=>1,'paket_id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>3,
        ]);
        $this->db->table('siswa')->insert([
            'id'=>1,'pengguna_id'=>100,'angkatan_id'=>1,'semester_mulai'=>4,
        ]);
        $this->db->table('tp')->insert(['id'=>22,'paket_semester_id'=>11,'jenis_tp'=>'non_esensial','status'=>'siap']);
        $this->db->table('progres_tp')->insert([
            'id'=>33,'tp_id'=>22,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'tuntas',
        ]);
        $this->db->table('percobaan_ujian')->insert([
            'id'=>101,'progres_tp_id'=>33,'jenis'=>'sumatif','tahap'=>'normal',
            'nomor_percobaan'=>1,'status'=>'selesai_sah','skor'=>90.0,
        ]);
        $this->db->table('bab')->insertBatch([
            ['id'=>1,'paket_semester_id'=>11,'nomor_bab'=>1,'nama_bab'=>'BAB Satu'],
            ['id'=>2,'paket_semester_id'=>11,'nomor_bab'=>2,'nama_bab'=>'BAB Dua'],
        ]);
        $wib=new \DateTimeImmutable('now',new \DateTimeZone('Asia/Jakarta'));
        $mulai=$wib->modify('-1 minute')->format('Y-m-d H:i:s');
        $akhir=$wib->modify('+1 hour')->format('Y-m-d H:i:s');
        $this->db->table('ujian_pra_semester')->insert([
            'id'=>51,'paket_semester_id'=>11,'status'=>'siap',
            'mulai_pada'=>$mulai,'selesai_pada'=>$akhir,
            'jumlah_soal'=>30,'durasi_menit'=>60,'kkm_khusus'=>75,
            'dibuat_oleh'=>10,'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
        ]);
        $bank=[];
        for($i=1;$i<=30;$i++){
            $bank[]=[
                'id'=>$i,'ujian_pra_semester_id'=>51,'bab_id'=>$i%2+1,
                'urutan'=>$i,'pertanyaan'=>'Soal '.$i,'aktif'=>1,'kunci'=>'C',
                'opsi_a'=>'A'.$i,'opsi_b'=>'B'.$i,'opsi_c'=>'C'.$i,
                'opsi_d'=>'D'.$i,'opsi_e'=>'E'.$i,
            ];
        }
        $this->db->table('soal_pra_semester')->insertBatch($bank);
        $this->db->table('pengaturan')->insert(['kunci'=>'kkm_default','nilai'=>'80']);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function actor(string $role,int $id): callable
    {
        return static fn (): array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ];
    }

    public function testSiswaMendapatkanJadwalDanDuaBabTanpaMembocorkanKunci(): void
    {
        $siswa=new PraSemesterSiswaService($this->db,$this->actor('siswa',100));
        $k=$siswa->konteks(11);
        self::assertTrue($k['gerbang']['boleh_mulai']);
        self::assertSame(75,$k['kkm']['efektif']);
        self::assertSame(2,$k['gerbang']['bank']['jumlah_bab_tercakup']);
        self::assertArrayNotHasKey('soal',$k);
        $this->expectException(\DomainException::class);
        (new PraSemesterSiswaService($this->db,$this->actor('siswa',999)))->konteks(11);
    }

    public function testMesinMembuatPercobaanSekaliDanMenilaiNolDenganSah(): void
    {
        $mesin=new MesinPraSemesterSiswaService($this->db,$this->actor('siswa',100));
        $mulai=$mesin->mulai(11,true,true);
        self::assertFalse($mulai['sudah_ada']);
        self::assertSame(48,strlen($mulai['token']));
        $lanjut=$mesin->mulai(11,true,true);
        self::assertTrue($lanjut['sudah_ada']);
        self::assertSame($mulai['token'],$lanjut['token']);
        $st=$mesin->status(11,$mulai['token']);
        self::assertSame('berjalan',$st['status']);
        self::assertCount(30,$st['soal']);
        self::assertArrayNotHasKey('kunci_acak',$st['soal'][0]);
        $selesai=$mesin->tindakan(11,$mulai['token'],'kirim',true);
        self::assertSame('selesai_sah',$selesai['status']);
        self::assertSame(0.0,$selesai['skor']);
        self::assertSame(1,$this->db->table('percobaan_ujian')
            ->where('jenis','pra_semester')->countAllResults());
        $this->expectException(\DomainException::class);
        $mesin->mulai(11,true,true);
    }

    public function testGuruG1MengelolaKKMTanpaMengubahTP(): void
    {
        $svc=new PraSemesterGuruService($this->db,$this->actor('guru',10));
        $k=$svc->konteks(11);
        self::assertSame(30,$k['kesiapan']['jumlah_sah']);
        $svc->simpanKkm(11,'78');
        self::assertSame(78,$svc->konteks(11)['kkm']['efektif']);
        $this->expectException(\DomainException::class);
        (new PraSemesterGuruService($this->db,$this->actor('guru',11)))->konteks(11);
    }

    public function testAdminMengubahDurasiTanpaMengubahMapelLain(): void
    {
        $svc=new PraSemesterJadwalService($this->db,$this->actor('admin',1));
        $this->db->table('ujian_pra_semester')->where('id',51)
            ->update(['status'=>'draf','mulai_pada'=>null,'selesai_pada'=>null]);
        $d=(new \DateTimeImmutable('now',new \DateTimeZone('Asia/Jakarta')))
            ->modify('+3 days')->setTime(8,0);
        $svc->simpan(11,$d->format('Y-m-d\TH:i'),$d->modify('+1 hour')->format('Y-m-d\TH:i'),'90');
        $row=$svc->lihat(11);
        self::assertSame(90,(int)$row['durasi_menit']);
        self::assertSame('siap',$row['ujian_status']);
        $this->expectException(\DomainException::class);
        $svc->simpan(12,$d->format('Y-m-d\TH:i'),$d->modify('+1 hour')->format('Y-m-d\TH:i'),'60');
    }
}
