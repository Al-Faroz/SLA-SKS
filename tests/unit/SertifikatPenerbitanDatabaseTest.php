<?php

declare(strict_types=1);

use App\Services\SertifikatAsetService;
use App\Services\SertifikatPdfService;
use App\Services\SertifikatPenerbitanService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian SQLite anonim: tidak memakai dump siswa atau mengubah DB XAMPP.
 * PDF/TTD sintetis ditempatkan hanya pada writable/private/sertifikat.
 */
final class SertifikatPenerbitanDatabaseTest extends TestCase
{
    private BaseConnection $db;
    private array $files=[];

    protected function setUp(): void
    {
        parent::setUp();
        $this->db=\Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,'foreignKeys'=>false,
            'busyTimeout'=>1000,'synchronous'=>null,'failover'=>[],
        ],false);
        foreach ([
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,nama TEXT,semester_mulai INTEGER)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT,status TEXT)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,nisn TEXT,angkatan_id INTEGER,semester_mulai INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER,status TEXT)',
            'CREATE TABLE penugasan_paket(id INTEGER PRIMARY KEY,paket_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,nomor_bab INTEGER,nama_bab TEXT)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,bab_id INTEGER,jenis_tp TEXT,status TEXT,kode_tp TEXT,nama_tp TEXT,judul TEXT,urutan_atp INTEGER)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,siswa_id INTEGER,paket_semester_id INTEGER,status TEXT)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jalur TEXT,nilai_resume REAL,nilai_hots REAL,nilai_sumatif REAL,nilai_tp REAL,status_nilai TEXT)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY,progres_tp_id INTEGER,jenis TEXT,status TEXT)',
            'CREATE TABLE pengaturan(kunci TEXT PRIMARY KEY,nilai TEXT)',
            'CREATE TABLE log_aktivitas(id INTEGER PRIMARY KEY AUTOINCREMENT,pengguna_id INTEGER,peran_aktif TEXT,peristiwa TEXT,catatan TEXT,waktu TEXT)',
            'CREATE TABLE sertifikat_aset(id INTEGER PRIMARY KEY,jenis TEXT,nama_acak TEXT,mime TEXT,lebar INTEGER,tinggi INTEGER,ukuran_byte INTEGER,sha256 TEXT,dibuat_oleh INTEGER,dibuat_pada TEXT)',
            'CREATE TABLE sertifikat_aset_aktif(jenis TEXT PRIMARY KEY,aset_id INTEGER,diaktifkan_oleh INTEGER,diaktifkan_pada TEXT)',
            'CREATE TABLE sertifikat(id INTEGER PRIMARY KEY AUTOINCREMENT,siswa_id INTEGER,paket_semester_id INTEGER,versi INTEGER,nomor TEXT,kode_verifikasi TEXT,status TEXT,menggantikan_id INTEGER,background_aset_id INTEGER,ttd_aset_id INTEGER,konten_sha256 TEXT,snapshot_json TEXT,snapshot_sha256 TEXT,pdf_nama_acak TEXT,pdf_sha256 TEXT,alasan_revisi TEXT,diterbitkan_oleh INTEGER,diterbitkan_pada TEXT,digantikan_pada TEXT)',
            'CREATE TABLE sertifikat_aktif(siswa_id INTEGER,paket_semester_id INTEGER,sertifikat_id INTEGER,diubah_pada TEXT,PRIMARY KEY(siswa_id,paket_semester_id))',
        ] as $sql) $this->db->query($sql);
        $this->db->table('ref_angkatan')->insert(['id'=>1,'nama'=>'Gen Contoh','semester_mulai'=>4]);
        $this->db->table('ref_mapel')->insert(['id'=>1,'nama'=>'Mapel Simulasi']);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>50,'nama'=>'Admin Contoh','status'=>'aktif'],
            ['id'=>101,'nama'=>'Siswa Simulasi A','status'=>'aktif'],
            ['id'=>102,'nama'=>'Siswa Simulasi B','status'=>'aktif'],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>1,'pengguna_id'=>101,'nisn'=>'1234567890','angkatan_id'=>1,'semester_mulai'=>4],
            ['id'=>2,'pengguna_id'=>102,'nisn'=>'0123456789','angkatan_id'=>1,'semester_mulai'=>4],
        ]);
        $this->db->table('paket_semester')->insert([
            'id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,
            'tahun_ajaran_id'=>1,'status'=>'aktif',
        ]);
        $this->db->table('penugasan_paket')->insert([
            'id'=>1,'paket_id'=>11,'angkatan_id'=>1,'mapel_id'=>1,
            'semester'=>4,'tahun_ajaran_id'=>1,
        ]);
        $this->db->table('bab')->insert([
            'id'=>21,'paket_semester_id'=>11,'nomor_bab'=>1,'nama_bab'=>'BAB Simulasi',
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>31,'paket_semester_id'=>11,'bab_id'=>21,'jenis_tp'=>'esensial',
                'status'=>'siap','kode_tp'=>'SIM.8.1.1','nama_tp'=>'TP Esensial','judul'=>'TP Esensial','urutan_atp'=>1],
            ['id'=>32,'paket_semester_id'=>11,'bab_id'=>21,'jenis_tp'=>'non_esensial',
                'status'=>'siap','kode_tp'=>'SIM.8.1.2','nama_tp'=>'TP Nilai Nol','judul'=>'TP Nilai Nol','urutan_atp'=>2],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>201,'tp_id'=>32,'siswa_id'=>1,'paket_semester_id'=>11,'status'=>'tuntas'],
            ['id'=>202,'tp_id'=>32,'siswa_id'=>2,'paket_semester_id'=>11,'status'=>'tuntas'],
        ]);
        $this->db->table('nilai_tp')->insertBatch([
            ['id'=>1,'progres_tp_id'=>201,'jalur'=>'lulus_diagnostik',
                'nilai_resume'=>null,'nilai_hots'=>null,'nilai_sumatif'=>0,
                'nilai_tp'=>0,'status_nilai'=>'final'],
            ['id'=>2,'progres_tp_id'=>202,'jalur'=>'belajar_wajib',
                'nilai_resume'=>85,'nilai_hots'=>null,'nilai_sumatif'=>70,
                'nilai_tp'=>null,'status_nilai'=>'sementara'],
        ]);
        $this->db->table('percobaan_ujian')->insertBatch([
            ['id'=>301,'progres_tp_id'=>201,'jenis'=>'sumatif','status'=>'selesai_sah'],
            ['id'=>302,'progres_tp_id'=>202,'jenis'=>'sumatif','status'=>'selesai_sah'],
        ]);
        foreach (\App\Services\PengaturanService::IDENTITAS as $k=>$v) {
            $this->db->table('pengaturan')->insert([
                'kunci'=>$k,'nilai'=>$k==='website'?'':'Contoh Data Valid',
            ]);
        }
        // TTD sintetis tanpa identitas sekolah asli.
        $dir=SertifikatAsetService::direktori();
        if (!is_dir($dir)) mkdir($dir,0700,true);
        $nama=str_repeat('e',48);
        $path=$dir.DIRECTORY_SEPARATOR.$nama.'.png';
        $im=imagecreatetruecolor(400,150);
        imagesavealpha($im,true);
        imagealphablending($im,false);
        $warna=imagecolorallocatealpha($im,0,0,0,127);
        imagefill($im,0,0,$warna);
        imagepng($im,$path);
        imagedestroy($im);
        $this->files[]=$path;
        $this->db->table('sertifikat_aset')->insert([
            'id'=>1,'jenis'=>'ttd','nama_acak'=>$nama,
            'mime'=>'image/png','lebar'=>400,'tinggi'=>150,
            'ukuran_byte'=>filesize($path),'sha256'=>hash_file('sha256',$path),
            'dibuat_oleh'=>50,'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
        ]);
        $this->db->table('sertifikat_aset_aktif')->insert([
            'jenis'=>'ttd','aset_id'=>1,'diaktifkan_oleh'=>50,
            'diaktifkan_pada'=>gmdate('Y-m-d H:i:s'),
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->db->table('sertifikat')->select('pdf_nama_acak')->get()->getResultArray() as $r) {
            $this->files[]=SertifikatAsetService::direktori('pdf')
                .DIRECTORY_SEPARATOR.$r['pdf_nama_acak'].'.pdf';
        }
        foreach ($this->files as $path) {
            if (is_file($path)) @unlink($path);
        }
        $this->db->close();
        parent::tearDown();
    }

    private static function actor(int $id=50,string $role='admin'): callable
    {
        return static fn (): array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif',
            'wajib_ganti_sandi'=>0,
        ];
    }

    private function svc(): SertifikatPenerbitanService
    {
        return new SertifikatPenerbitanService($this->db,self::actor());
    }

    public function testTerbitNilaiNolTanpaTabelPraSemesterDanIdempotensi(): void
    {
        // Fixture sengaja TIDAK memiliki tabel Pra-Semester.
        $s=$this->svc();
        $a=$s->terbit(1,11);
        self::assertSame('berlaku',$a['status']);
        self::assertSame(1,(int)$a['versi']);
        self::assertSame((int)$a['id'],(int)$s->terbit(1,11)['id']);
        self::assertSame(1,$this->db->table('sertifikat')->countAllResults());
        self::assertSame(1,$this->db->table('sertifikat_aktif')->countAllResults());
        $pdf=$s->pdfBerizin((int)$a['id']);
        self::assertStringStartsWith('%PDF-',file_get_contents($pdf['path']));
        $public=$s->verifikasi($a['kode_verifikasi']);
        self::assertSame('berlaku',$public['status']);
        self::assertSame(0.0,(float)$public['tp'][1]['nilai_tp']);
        self::assertArrayNotHasKey('nisn',$public);
        self::assertArrayNotHasKey('snapshot_json',$public);
    }

    public function testRevisiHanyaKetikaIsiCetakBerubahDanArsipTetapAda(): void
    {
        $s=$this->svc();
        $a=$s->terbit(1,11);
        try {
            $s->revisi(1,11,'Koreksi nilai TP');
            self::fail('Revisi tanpa perubahan cetak dilarang.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Tidak perlu revisi',$e->getMessage());
        }
        $this->db->table('nilai_tp')->where('progres_tp_id',201)->update([
            'nilai_tp'=>50,'nilai_sumatif'=>50,
        ]);
        $b=$s->revisi(1,11,'Koreksi resmi nilai TP siswa simulasi');
        self::assertNotSame((int)$a['id'],(int)$b['id']);
        self::assertSame('digantikan',$this->db->table('sertifikat')
            ->where('id',$a['id'])->get()->getRow('status'));
        self::assertSame('berlaku',$b['status']);
        self::assertSame(2,(int)$b['versi']);
        self::assertSame(2,$this->db->table('sertifikat')->countAllResults());
        self::assertSame(1,$this->db->table('sertifikat_aktif')->countAllResults());
        self::assertSame($b['kode_verifikasi'],$s->verifikasi($a['kode_verifikasi'])['kode_aktif']);
        self::assertFileExists($s->pdfBerizin((int)$a['id'])['path']);
        self::assertFileExists($s->pdfBerizin((int)$b['id'])['path']);
    }

    public function testNilaiSementaraTetapMenolakTanpaMembuatBerkas(): void
    {
        $this->expectException(\DomainException::class);
        $this->svc()->terbit(2,11);
    }

    public function testTanpaTtdAktifPenerbitanDitolak(): void
    {
        $this->db->table('sertifikat_aset_aktif')->where('jenis','ttd')->delete();
        $this->expectException(\DomainException::class);
        $this->svc()->terbit(1,11);
    }

    public function testUnduhAntarSiswaDanPeranLainDitolak(): void
    {
        $id=(int)$this->svc()->terbit(1,11)['id'];
        $siswaBenar=new SertifikatPenerbitanService($this->db,self::actor(101,'siswa'));
        self::assertSame(1,count($siswaBenar->daftarSaya()));
        self::assertFileExists($siswaBenar->pdfBerizin($id)['path']);
        $siswaLain=new SertifikatPenerbitanService($this->db,self::actor(102,'siswa'));
        self::assertSame([],$siswaLain->daftarSaya());
        $this->expectException(\DomainException::class);
        $siswaLain->pdfBerizin($id);
    }

    public function testPreviewDummyBukanDataSiswa(): void
    {
        $pdf=(new SertifikatPdfService())->pratinjau(null,null);
        self::assertStringStartsWith('%PDF-',$pdf);
        self::assertGreaterThan(1800,strlen($pdf));
    }
}
