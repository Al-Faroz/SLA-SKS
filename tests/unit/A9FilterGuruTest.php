<?php

declare(strict_types=1);

use App\Services\PenilaianGuruService;
use App\Services\RekapNilaiTpGuruService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** A9: SQL SQLite sintetis; tidak menyentuh dump atau siswa XAMPP. */
final class A9FilterGuruTest extends TestCase
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
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT,status TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,guru_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,status TEXT)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,nomor_bab INTEGER,nama_bab TEXT)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,bab_id INTEGER,jenis_tp TEXT,status TEXT,kode_tp TEXT,nama_tp TEXT,judul TEXT,urutan_dalam_bab INTEGER)',
            'CREATE TABLE materi(id INTEGER PRIMARY KEY,tp_id INTEGER,urutan INTEGER,judul TEXT)',
            'CREATE TABLE versi_konten_tp(id INTEGER PRIMARY KEY,tp_id INTEGER)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,siswa_id INTEGER,paket_semester_id INTEGER,versi_konten_tp_id INTEGER,status TEXT)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jalur TEXT,nilai_resume REAL,nilai_hots REAL,nilai_sumatif REAL,nilai_tp REAL,status_nilai TEXT)',
            'CREATE TABLE resume_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,materi_id INTEGER,versi_konten_tp_id INTEGER,isi TEXT,status TEXT,nilai REAL,dinilai_oleh INTEGER,dinilai_pada TEXT,umpan_balik TEXT,dikirim_pada TEXT)',
            'CREATE TABLE jawaban_hots_materi(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,materi_id INTEGER,versi_konten_tp_id INTEGER,isi TEXT,status TEXT,nilai REAL,dinilai_oleh INTEGER,dinilai_pada TEXT,umpan_balik TEXT,dikirim_pada TEXT)',
        ];
        foreach($sql as $q)$this->db->query($q);
        $this->db->table('ref_angkatan')->insert(['id'=>1,'nama'=>'Gen Uji']);
        $this->db->table('ref_mapel')->insert(['id'=>1,'nama'=>'SKI']);
        $this->db->table('penugasan_guru_gen')->insert([
            'id'=>1,'guru_id'=>10,'angkatan_id'=>1,'mapel_id'=>1,
        ]);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>100,'nama'=>'Siswa Satu','status'=>'aktif'],
            ['id'=>200,'nama'=>'Siswa Dua','status'=>'aktif'],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>1,'pengguna_id'=>100,'angkatan_id'=>1],
            ['id'=>2,'pengguna_id'=>200,'angkatan_id'=>1],
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'status'=>'aktif'],
            ['id'=>12,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>5,'status'=>'aktif'],
        ]);
        $this->db->table('bab')->insertBatch([
            ['id'=>51,'paket_semester_id'=>11,'nomor_bab'=>1,'nama_bab'=>'BAB Satu'],
            ['id'=>52,'paket_semester_id'=>12,'nomor_bab'=>2,'nama_bab'=>'BAB Dua'],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>101,'paket_semester_id'=>11,'bab_id'=>51,'jenis_tp'=>'non_esensial',
                'status'=>'siap','kode_tp'=>'SKI.8.1.1','nama_tp'=>'TP Nilai',
                'judul'=>'TP Nilai','urutan_dalam_bab'=>1],
            ['id'=>102,'paket_semester_id'=>11,'bab_id'=>51,'jenis_tp'=>'esensial',
                'status'=>'siap','kode_tp'=>'SKI.8.1.2','nama_tp'=>'TP Info',
                'judul'=>'TP Info','urutan_dalam_bab'=>2],
            ['id'=>103,'paket_semester_id'=>12,'bab_id'=>52,'jenis_tp'=>'non_esensial',
                'status'=>'siap','kode_tp'=>'SKI.9.2.1','nama_tp'=>'TP Berikut',
                'judul'=>'TP Berikut','urutan_dalam_bab'=>1],
        ]);
        $this->db->table('materi')->insertBatch([
            ['id'=>301,'tp_id'=>101,'urutan'=>1,'judul'=>'Materi Satu'],
            ['id'=>303,'tp_id'=>103,'urutan'=>1,'judul'=>'Materi Dua'],
        ]);
        $this->db->table('versi_konten_tp')->insertBatch([
            ['id'=>401,'tp_id'=>101],['id'=>403,'tp_id'=>103],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>201,'tp_id'=>101,'siswa_id'=>1,'paket_semester_id'=>11,'versi_konten_tp_id'=>401,'status'=>'tuntas'],
            ['id'=>203,'tp_id'=>103,'siswa_id'=>1,'paket_semester_id'=>12,'versi_konten_tp_id'=>403,'status'=>'tuntas'],
        ]);
        $this->db->table('nilai_tp')->insert([
            'id'=>1,'progres_tp_id'=>201,'jalur'=>'lulus_diagnostik',
            'nilai_resume'=>null,'nilai_hots'=>null,'nilai_sumatif'=>0,'nilai_tp'=>0,
            'status_nilai'=>'final',
        ]);
        $this->db->table('resume_materi')->insert([
            'id'=>1,'progres_tp_id'=>201,'materi_id'=>301,'versi_konten_tp_id'=>401,
            'isi'=>'Jawaban Resume','status'=>'final','nilai'=>null,
            'dikirim_pada'=>'2026-11-03 07:00:00',
        ]);
        $this->db->table('jawaban_hots_materi')->insert([
            'id'=>1,'progres_tp_id'=>203,'materi_id'=>303,'versi_konten_tp_id'=>403,
            'isi'=>'Jawaban HOTS','status'=>'final','nilai'=>null,
            'dikirim_pada'=>'2026-11-03 07:30:00',
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function aktor(int $id = 10): callable
    {
        return static fn (): array => [
            'id'=>$id,'peran_aktif'=>'guru','status'=>'aktif','wajib_ganti_sandi'=>0,
        ];
    }

    private function pilihan(): array
    {
        return ['angkatan_id'=>1,'mapel_id'=>1];
    }

    public function testAntreanTerfilterSemesterBabDanJenisTugas(): void
    {
        $svc=new PenilaianGuruService($this->db,$this->aktor());
        $list=$svc->daftarBab($this->pilihan(),4);
        self::assertCount(1,$list);
        self::assertSame(51,(int)$list[0]['id']);
        $resume=$svc->antrean($this->pilihan()+[
            'semester'=>4,'bab'=>51,'jenis'=>'resume',
        ]);
        self::assertSame(1,$resume['total']);
        self::assertSame('resume',$resume['baris'][0]['jenis']);
        self::assertArrayNotHasKey('kemiripan',$resume['baris'][0]);
        $hots=$svc->antrean($this->pilihan()+[
            'semester'=>5,'bab'=>52,'jenis'=>'hots',
        ]);
        self::assertSame(1,$hots['total']);
        self::assertSame('hots',$hots['baris'][0]['jenis']);
        self::assertSame(0,$svc->antrean($this->pilihan()+[
            'semester'=>4,'bab'=>51,'jenis'=>'hots',
        ])['total']);
    }

    public function testAntreanBabDiLuarSemesterDitolak(): void
    {
        $svc=new PenilaianGuruService($this->db,$this->aktor());
        $this->expectException(\DomainException::class);
        $svc->antrean($this->pilihan()+[
            'semester'=>4,'bab'=>52,'jenis'=>'semua',
        ]);
    }

    public function testRekapTpMemfilterJenisDanBabDenganNolSah(): void
    {
        $svc=new RekapNilaiTpGuruService($this->db,$this->aktor());
        self::assertCount(1,$svc->daftarBab($this->pilihan(),4));
        $non=$svc->rekap($this->pilihan(),4,1,25,'non_esensial');
        self::assertSame(2,$non['total']);
        self::assertSame('non_esensial',$non['baris'][0]['jenis_tp']);
        self::assertSame('0,00',RekapNilaiTpGuruService::teksKomponen(
            $non['baris'][0],'nilai_tp'
        ));
        self::assertSame(2,$svc->rekap($this->pilihan(),4,1,25,'esensial')['total']);
        self::assertSame(4,$svc->rekap($this->pilihan(),4,1,25,'semua')['total']);
        self::assertSame(2,$svc->rekap($this->pilihan(),4,1,25,'non_esensial',51)['total']);
        $this->expectException(\DomainException::class);
        $svc->rekap($this->pilihan(),4,1,25,'non_esensial',52);
    }

    public function testFilterRekapMenolakPenugasanGuruLain(): void
    {
        $svc=new RekapNilaiTpGuruService($this->db,$this->aktor(11));
        $this->expectException(\DomainException::class);
        $svc->rekap($this->pilihan(),4,1,25,'non_esensial');
    }
}
