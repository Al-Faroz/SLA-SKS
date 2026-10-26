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
    private string $folder;

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
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,kode_tp TEXT,judul TEXT,urutan_atp INTEGER,status TEXT,rumusan TEXT,indikator TEXT,resume_min INTEGER,tugas_min INTEGER)',
            'CREATE TABLE versi_konten_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,nomor_versi INTEGER,snapshot_json TEXT,checksum TEXT)',
            'CREATE TABLE materi(id INTEGER PRIMARY KEY,tp_id INTEGER,urutan INTEGER,judul TEXT,jenis TEXT,aktif INTEGER)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,siswa_id INTEGER,tp_id INTEGER,versi_konten_tp_id INTEGER,versi_paket_yang_ditugaskan INTEGER,status TEXT,dimulai_pada TEXT)',
            'CREATE TABLE berkas(id INTEGER PRIMARY KEY,tp_id INTEGER,nama_acak TEXT,mime TEXT,ukuran_byte INTEGER,halaman INTEGER)',
            'CREATE TABLE resume_materi(id INTEGER PRIMARY KEY AUTOINCREMENT,progres_tp_id INTEGER,materi_id INTEGER,versi_konten_tp_id INTEGER,isi TEXT,status TEXT,revisi INTEGER,dibuat_pada TEXT,diubah_pada TEXT,dikirim_pada TEXT,nilai INTEGER,dinilai_oleh INTEGER,umpan_balik TEXT,dinilai_pada TEXT)',
            'CREATE TABLE tugas_hots_materi(id INTEGER PRIMARY KEY AUTOINCREMENT,materi_id INTEGER,row_uuid TEXT,pertanyaan TEXT,petunjuk TEXT)',
            'CREATE TABLE jawaban_hots_materi(id INTEGER PRIMARY KEY AUTOINCREMENT,progres_tp_id INTEGER,materi_id INTEGER,versi_konten_tp_id INTEGER,isi TEXT,status TEXT,revisi INTEGER,dibuat_pada TEXT,diubah_pada TEXT,dikirim_pada TEXT,nilai INTEGER,dinilai_oleh INTEGER,umpan_balik TEXT,dinilai_pada TEXT)',
            'CREATE TABLE log_aktivitas(id INTEGER PRIMARY KEY AUTOINCREMENT,pengguna_id INTEGER,peran_aktif TEXT,peristiwa TEXT,catatan TEXT,waktu TEXT)',
            'CREATE TABLE progres_materi(id INTEGER PRIMARY KEY AUTOINCREMENT,progres_tp_id INTEGER,materi_id INTEGER,versi_konten_tp_id INTEGER,jenis TEXT,status TEXT,waktu_aktif_detik INTEGER DEFAULT 0,waktu_putar_detik INTEGER DEFAULT 0,posisi_terakhir_detik INTEGER,posisi_terjauh_detik INTEGER DEFAULT 0,durasi_media_detik INTEGER,halaman_total INTEGER,halaman_selesai INTEGER DEFAULT 0,heartbeat_terakhir_pada TEXT,dibuat_pada TEXT,diubah_pada TEXT,media_selesai_pada TEXT)',
            'CREATE TABLE halaman_progres_materi(id INTEGER PRIMARY KEY AUTOINCREMENT,progres_materi_id INTEGER,nomor_halaman INTEGER,waktu_aktif_detik INTEGER,selesai_pada TEXT,UNIQUE(progres_materi_id,nomor_halaman))',
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
            'resume_min'=>50, 'tugas_min'=>200,
        ]);
        $this->db->table('materi')->insertBatch([
            ['id'=>31,'tp_id'=>1,'urutan'=>1,'judul'=>'PDF materi','jenis'=>'pdf','aktif'=>1],
            ['id'=>32,'tp_id'=>1,'urutan'=>2,'judul'=>'Video materi','jenis'=>'youtube','aktif'=>1],
        ]);
        $snap = [
            'format'=>'SLA-SKS-TP-V1','tp'=>[
                'id'=>1,'status'=>'siap','resume_min'=>'50','tugas_min'=>'200',
            ],
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
        $this->folder = sys_get_temp_dir() . '/sla_s08a_' . bin2hex(random_bytes(8));
        if (!mkdir($this->folder, 0700, true)) throw new RuntimeException('Folder uji sementara tidak tersedia.');
        $this->file = $this->folder . '/' . $nama;
        file_put_contents($this->file, "%PDF-1.4\n% uji sintetis\n");
        $this->db->table('berkas')->insert([
            'id'=>9,'tp_id'=>1,'nama_acak'=>$nama,'mime'=>'application/pdf',
            'ukuran_byte'=>filesize($this->file),'halaman'=>2,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->file) && is_file($this->file)) unlink($this->file);
        if (isset($this->folder) && is_dir($this->folder)) rmdir($this->folder);
        if (isset($this->db)) $this->db->close();
        parent::tearDown();
    }

    private function svc(int $userId = 101, string $role='siswa'): MateriSiswaService
    {
        return new MateriSiswaService($this->db, static fn (): array => [
            'id'=>$userId,'peran_aktif'=>$role,'status'=>'aktif',
            'wajib_ganti_sandi'=>0,
        ], $this->folder);
    }

    public function testGagalDiagnostikBisaMelihatMateriPertamaTapiKeduaTerkunci(): void
    {
        $all = $this->svc()->daftar(1)['materi'];
        self::assertCount(2,$all);
        self::assertTrue($all[0]['buka']);
        self::assertFalse($all[1]['buka']);
        self::assertSame('PDF historis',$all[0]['judul']);
        self::assertSame('Tulis ringkasan',$this->svc()->lihat(1,31)['materi']['petunjuk_resume']);
        $berikutnya=$this->svc()->lihat(1,31)['materi_berikutnya'];
        self::assertSame(32,$berikutnya['id']);
        self::assertFalse($berikutnya['boleh_buka']);
        self::assertStringContainsString('HOTS',$berikutnya['alasan']);
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
        $awal=$this->svc(102)->lihat(1,31);
        self::assertSame(32,$awal['materi_berikutnya']['id']);
        self::assertTrue($awal['materi_berikutnya']['boleh_buka']);
        self::assertNull($detail['materi_berikutnya']);
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
    private function progresMediaService(int $userId, callable $clock): \App\Services\ProgresMediaMateriSiswaService
    {
        return new \App\Services\ProgresMediaMateriSiswaService(
            $this->db, static fn (): array => [
                'id'=>$userId,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
            ], $clock
        );
    }

    /** Memodifikasi fixture snapshot TEST SAJA; checksum tetap sah. */
    private function ubahBatasSnapshot(int $resume, int $hots): void
    {
        $row = $this->db->table('versi_konten_tp')
            ->where('id',3)->get()->getRowArray();
        $snap = json_decode($row['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        $snap['tp']['resume_min'] = (string) $resume;
        $snap['tp']['tugas_min'] = (string) $hots;
        $json = json_encode($snap,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $this->db->table('versi_konten_tp')->where('id',3)
            ->update(['snapshot_json'=>$json,'checksum'=>hash('sha256',$json)]);
    }

    private function jadikanMediaPertamaGambar(): void
    {
        $row=$this->db->table('versi_konten_tp')->where('id',3)->get()->getRowArray();
        $snap=json_decode($row['snapshot_json'], true);
        $snap['materi'][0]['jenis']='gambar';
        $snap['materi'][0]['min_waktu_detik']=30;
        $json=json_encode($snap,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->db->table('versi_konten_tp')->where('id',3)
            ->update(['snapshot_json'=>$json,'checksum'=>hash('sha256',$json)]);
    }

    public function testDetakGambarMembuatSatuProgresLaluSelesaiDenganJamServer(): void
    {
        $this->jadikanMediaPertamaGambar();
        $now=1000;
        $service=$this->progresMediaService(101,static function () use (&$now): int {return $now;});
        self::assertSame('belum_dimulai',$service->status(1,31)['status']);
        self::assertSame('berjalan',$service->detak(1,31,true,null,null)['status']);
        foreach ([1010,1020,1030] as $now) {
            $hasil=$service->detak(1,31,true,null,null);
        }
        self::assertSame('media_selesai',$hasil['status']);
        self::assertTrue($hasil['media_selesai']);
        self::assertSame(30,$hasil['waktu_aktif_detik']);
        self::assertSame(1,$this->db->table('progres_materi')->countAllResults());
        $now=1040;
        self::assertSame(30,$service->detak(1,31,true,null,null)['waktu_aktif_detik']);
    }

    public function testSukarelaTidakDapatMengirimDetakResmi(): void
    {
        $this->jadikanMediaPertamaGambar();
        $service=$this->progresMediaService(102,static fn (): int => 1000);
        self::assertSame('sukarela',$service->status(1,31)['status']);
        try {
            $service->detak(1,31,true,null,null);
            self::fail('Jalur tuntas tidak boleh membuat progres media.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('sukarela',$e->getMessage());
        }
        self::assertSame(0,$this->db->table('progres_materi')->countAllResults());
    }

    public function testResetSesiGambarMelaluiServiceTidakMenambahJedaScroll(): void
    {
        $this->jadikanMediaPertamaGambar();
        $now=1000;
        $service=$this->progresMediaService(101,
            static function () use (&$now):int {return $now;});
        $service->detak(1,31,true,null,null);
        $now=1005;
        self::assertSame(5,$service->detak(1,31,true,null,null)['waktu_aktif_detik']);
        // Pengamatan terputus, kembali dengan reset sesi.
        $now=1010;
        self::assertSame(5,$service->detak(1,31,true,null,null,
            null,null,true)['waktu_aktif_detik']);
        $now=1015;
        self::assertSame(10,$service->detak(1,31,true,null,null)['waktu_aktif_detik']);
        self::assertSame('berjalan',
            $this->db->table('progres_materi')->get()->getRow('status'));
    }

    public function testPdfDuaHalamanBisaLengkapTepat15DetikPerHalaman(): void
    {
        $now=1000;
        $service=$this->progresMediaService(101,
            static function () use (&$now):int {return $now;});
        $service->detak(1,31,true,null,null,1,2);
        foreach ([1005,1010,1015] as $now) {
            $status=$service->detak(1,31,true,null,null,1,2);
        }
        self::assertSame(15,$status['waktu_per_halaman'][1]);
        self::assertSame(1,$status['halaman_selesai']);
        self::assertSame([2],$status['halaman_belum_selesai']);
        $now=1020;
        $service->detak(1,31,true,null,null,2,2,true);
        foreach ([1025,1030,1035] as $now) {
            $status=$service->detak(1,31,true,null,null,2,2);
        }
        self::assertSame(15,$status['waktu_per_halaman'][2]);
        self::assertSame(2,$status['halaman_selesai']);
        self::assertFalse($status['media_selesai']); // Guru masih min 60.
        foreach ([1040,1045,1050,1055,1060,1065] as $now) {
            $status=$service->detak(1,31,true,null,null,2,2);
        }
        self::assertTrue($status['media_selesai']);
        self::assertSame(60,$status['waktu_aktif_detik']);
        self::assertSame([],$status['halaman_belum_selesai']);
    }

    public function testPdfDetakTerlaluCepatTetapBisaKreditDiDetakBerikut(): void
    {
        $now=1000;
        $svc=$this->progresMediaService(101,
            static function() use (&$now):int {return $now;});
        $svc->detak(1,31,true,null,null,1,2);
        $now=1004;
        self::assertSame(0,
            $svc->detak(1,31,true,null,null,1,2)['waktu_aktif_detik']);
        $now=1006;
        $data=$svc->detak(1,31,true,null,null,1,2);
        self::assertSame(6,$data['waktu_aktif_detik']);
        self::assertSame(6,$data['waktu_per_halaman'][1]);
    }

    public function testUnduhTerkunciSebelumMediaSelesaiDanTerbukaSetelahnya(): void
    {
        $svc = $this->svc();
        try {
            $svc->mediaUntukUnduh(1,31);
            self::fail('Materi belum selesai tidak boleh diunduh.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('waktu belajar',$e->getMessage());
        }
        $this->db->table('progres_materi')->insert([
            'progres_tp_id'=>11,'materi_id'=>31,'versi_konten_tp_id'=>3,
            'jenis'=>'pdf','status'=>'media_selesai',
            'waktu_aktif_detik'=>30,
        ]);
        $berkas = $svc->mediaUntukUnduh(1,31);
        self::assertSame($this->file,$berkas['path']);
        self::assertSame('application/pdf',$berkas['mime']);

        // TP tuntas setelah pembelajaran tidak mencabut unduhan.
        $this->db->table('progres_tp')->where('id',11)->update(['status'=>'tuntas']);
        self::assertSame($this->file,$svc->mediaUntukUnduh(1,31)['path']);
    }

    public function testUnduhTidakDiberikanUntukJalurDiagnostikTuntas(): void
    {
        $this->db->table('progres_tp')->where('id',11)->update(['status'=>'tuntas']);
        try {
            $this->svc()->mediaUntukUnduh(1,31);
            self::fail('Jalur sukarela tidak memiliki akses unduh resmi.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('waktu belajar',$e->getMessage());
        }
    }

    public function testGambarWaktuSudahCukupDirekonsiliasiSaatGET(): void
    {
        $this->jadikanMediaPertamaGambar();
        $this->db->table('progres_materi')->insert([
            'progres_tp_id'=>11, 'materi_id'=>31, 'versi_konten_tp_id'=>3,
            'jenis'=>'gambar', 'status'=>'berjalan',
            'waktu_aktif_detik'=>40, 'waktu_putar_detik'=>0,
        ]);
        $svc = $this->progresMediaService(101, static fn ():int=>2000);
        $status = $svc->status(1,31);
        self::assertTrue($status['media_selesai']);
        self::assertSame(30,$status['minimal_detik']);
        self::assertSame('media_selesai',
            $this->db->table('progres_materi')->get()->getRow('status'));
        self::assertNotNull($this->db->table('progres_materi')
            ->get()->getRow('media_selesai_pada'));
        $svc->status(1,31);
        self::assertSame(1,$this->db->table('progres_materi')->countAllResults());
        $k = new \App\Services\KegiatanMateriSiswaService($this->db,
            static fn():array=>[
                'id'=>101,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
            ]);
        self::assertTrue($k->status(1,31)['media_selesai']);
    }

    public function testGambarWaktuTidakCukupTetapTerkunci(): void
    {
        $this->jadikanMediaPertamaGambar();
        $this->db->table('progres_materi')->insert([
            'progres_tp_id'=>11,'materi_id'=>31,'versi_konten_tp_id'=>3,
            'jenis'=>'gambar','status'=>'berjalan','waktu_aktif_detik'=>20,
        ]);
        $status = $this->progresMediaService(101,static fn():int=>2000)
            ->status(1,31);
        self::assertFalse($status['media_selesai']);
        self::assertSame(10,$status['waktu_tersisa_detik']);
        self::assertSame('berjalan',
            $this->db->table('progres_materi')->get()->getRow('status'));
    }

    public function testPdfSelesaiBerdasarkanSeluruhBuktiHalamanDanWaktuGuru(): void
    {
        $this->db->table('progres_materi')->insert([
            'id'=>17,'progres_tp_id'=>11,'materi_id'=>31,
            'versi_konten_tp_id'=>3,'jenis'=>'pdf','status'=>'berjalan',
            'waktu_aktif_detik'=>70,'halaman_total'=>2,'halaman_selesai'=>2,
        ]);
        foreach ([1,2] as $halaman) {
            $this->db->table('halaman_progres_materi')->insert([
                'progres_materi_id'=>17,'nomor_halaman'=>$halaman,
                'waktu_aktif_detik'=>35,'selesai_pada'=>'2026-10-25 10:00:00',
            ]);
        }
        $svc=$this->progresMediaService(101,static fn():int=>2000);
        $status=$svc->status(1,31);
        self::assertSame(2,$status['halaman_selesai']);
        self::assertSame([], $status['halaman_belum_selesai']);
        self::assertTrue($status['media_selesai']);
        self::assertNotNull($this->db->table('progres_materi')
            ->where('id',17)->get()->getRow('media_selesai_pada'));
        self::assertTrue($svc->status(1,31)['media_selesai']);
        self::assertSame(1,$this->db->table('progres_materi')->countAllResults());
        self::assertTrue((new \App\Services\KegiatanMateriSiswaService(
            $this->db,static fn():array=>[
                'id'=>101,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
            ]))->status(1,31)['media_selesai']);
    }

    public function testPdfWaktuGuruCukupTetapiHalamanBelumLengkapTetapTerkunci(): void
    {
        $this->db->table('progres_materi')->insert([
            'id'=>17,'progres_tp_id'=>11,'materi_id'=>31,
            'versi_konten_tp_id'=>3,'jenis'=>'pdf','status'=>'berjalan',
            'waktu_aktif_detik'=>90,'halaman_total'=>2,'halaman_selesai'=>1,
        ]);
        $this->db->table('halaman_progres_materi')->insert([
            'progres_materi_id'=>17,'nomor_halaman'=>1,
            'waktu_aktif_detik'=>90,'selesai_pada'=>'2026-10-25 10:00:00',
        ]);
        $status=$this->progresMediaService(101,static fn():int=>2000)
            ->status(1,31);
        self::assertFalse($status['media_selesai']);
        self::assertSame([2],$status['halaman_belum_selesai']);
        self::assertSame(0,$status['waktu_tersisa_detik']);
    }

    public function testPdfSeluruhHalamanSudahDibacaTetapiWaktuGuruBelumTuntas(): void
    {
        $this->db->table('progres_materi')->insert([
            'id'=>17,'progres_tp_id'=>11,'materi_id'=>31,
            'versi_konten_tp_id'=>3,'jenis'=>'pdf','status'=>'berjalan',
            'waktu_aktif_detik'=>50,'halaman_total'=>2,'halaman_selesai'=>2,
        ]);
        foreach ([1,2] as $halaman) {
            $this->db->table('halaman_progres_materi')->insert([
                'progres_materi_id'=>17,'nomor_halaman'=>$halaman,
                'waktu_aktif_detik'=>25,'selesai_pada'=>'2026-10-25 10:00:00',
            ]);
        }
        $status=$this->progresMediaService(101,static fn():int=>2000)
            ->status(1,31);
        self::assertFalse($status['media_selesai']);
        self::assertSame(10,$status['waktu_tersisa_detik']);
        self::assertSame([],$status['halaman_belum_selesai']);
    }

    public function testPdfDuaHalamanServerDanGerbangResume(): void
    {
        $now = 1000;
        $service = $this->progresMediaService(101,
            static function() use (&$now): int { return $now; });
        try {
            $service->detak(1, 31, true, null, null, 1, 1);
            self::fail('Jumlah halaman klien tidak boleh palsu.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('jumlah halaman', $e->getMessage());
        }
        self::assertSame(0, $this->db->table('progres_materi')->countAllResults());
        $service->detak(1,31,true,null,null,1,2);
        $now = 1010;
        $service->detak(1,31,true,null,null,1,2);
        $now = 1020;
        $status = $service->detak(1,31,true,null,null,1,2);
        self::assertSame(1, $status['halaman_selesai']);
        self::assertFalse($status['media_selesai']);
        $now = 1030;
        $service->detak(1,31,true,null,null,2,2);
        $now = 1040;
        $service->detak(1,31,true,null,null,2,2);
        $now = 1050;
        $status = $service->detak(1,31,true,null,null,2,2);
        self::assertSame(2, $status['halaman_selesai']);
        self::assertFalse($status['media_selesai']);
        $now = 1060;
        $service->detak(1,31,true,null,null,2,2);
        $now = 1070;
        $status = $service->detak(1,31,true,null,null,2,2);
        self::assertTrue($status['media_selesai']);
        self::assertSame(60, $status['waktu_aktif_detik']);
        self::assertSame(2, $this->db->table('halaman_progres_materi')->countAllResults());
        $k = new \App\Services\KegiatanMateriSiswaService($this->db,
            static fn(): array => ['id'=>101, 'peran_aktif'=>'siswa', 'status'=>'aktif',
                'wajib_ganti_sandi'=>0]);
        self::assertTrue($k->status(1,31)['media_selesai']);
    }

    public function testPdfPindahHalamanDanJedaPanjangTidakMenghitungWaktu(): void
    {
        $now = 1000;
        $service = $this->progresMediaService(101,
            static function() use (&$now): int { return $now; });
        $service->detak(1,31,true,null,null,1,2);
        $now = 1010;
        $service->detak(1,31,true,null,null,2,2);
        $now = 1110;
        $status = $service->detak(1,31,true,null,null,2,2);
        self::assertSame(0, $status['waktu_aktif_detik']);
        $now = 1120;
        $status = $service->detak(1,31,true,null,null,2,2);
        self::assertSame(10, $status['waktu_aktif_detik']);
        $this->expectException(\DomainException::class);
        $service->detak(1,31,true,null,null,3,2);
    }

    public function testPdfResetSetelahBackgroundTidakMemberiKreditPalsu(): void
    {
        $now = 1000;
        $service = $this->progresMediaService(101,
            static function () use (&$now):int {return $now;});
        $service->detak(1,31,true,null,null,1,2);
        $now = 1010;
        $status = $service->detak(1,31,true,null,null,1,2);
        self::assertSame(10,$status['waktu_aktif_detik']);
        $now = 1020;
        $status = $service->detak(1,31,true,null,null,1,2,true);
        self::assertSame(10,$status['waktu_aktif_detik']);
        $now = 1030;
        $status = $service->detak(1,31,true,null,null,1,2);
        self::assertSame(20,$status['waktu_aktif_detik']);
    }

    public function testPdfSukarelaTidakMencatatProgresResmi(): void
    {
        $service = $this->progresMediaService(102,static fn():int => 1000);
        try {
            $service->detak(1,31,true,null,null,1,2);
            self::fail('Siswa tuntas tidak boleh mencatat detak resmi.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('sukarela',$e->getMessage());
        }
        self::assertSame(0, $this->db->table('progres_materi')->countAllResults());
    }

    public function testResumeHotsHanyaSetelahMediaDanBerurutanSertaFinalIdempoten(): void
    {
        $this->jadikanMediaPertamaGambar();
        $k = new \App\Services\KegiatanMateriSiswaService($this->db,
            static fn (): array => [
                'id'=>101,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
            ]);
        try {
            $k->simpan(1,31,'resume','simpan','Draf saya',0);
            self::fail('Draf belum diizinkan sebelum media selesai.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('media',$e->getMessage());
        }
        $now = 1000;
        $media = $this->progresMediaService(101,static function () use (&$now): int {return $now;});
        $media->detak(1,31,true,null,null);
        foreach ([1010,1020,1030] as $now) $media->detak(1,31,true,null,null);
        try {
            $k->simpan(1,31,'hots','kirim',str_repeat('H',200),0);
            self::fail('HOTS tanpa Resume final ditolak.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Resume',$e->getMessage());
        }
        $saved = $k->simpan(1,31,'resume','simpan','Draf singkat',0);
        self::assertSame('draf',$saved['status']);
        self::assertSame(1,$saved['revisi']);
        self::assertSame('Draf singkat',$k->status(1,31)['resume']['isi']);
        $panjang = str_repeat('R',50);
        $final = $k->simpan(1,31,'resume','kirim',$panjang,1);
        self::assertSame('final',$final['status']);
        $k->simpan(1,31,'resume','kirim',$panjang,1);
        self::assertSame(1,$this->db->table('resume_materi')->countAllResults());
        try {
            $k->simpan(1,31,'resume','simpan','Tulis ulang',2);
            self::fail('Final immutable.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('final',$e->getMessage());
        }
        self::assertFalse($this->svc()->lihat(1,31)['materi_berikutnya']['boleh_buka']);
        $k->simpan(1,31,'hots','kirim',str_repeat('H',200),0);
        self::assertTrue($this->svc()->lihat(1,31)['materi_berikutnya']['boleh_buka']);
        self::assertSame(32,$this->svc()->lihat(1,32)['materi']['id']);
        self::assertSame(2,$this->db->table('log_aktivitas')->countAllResults());
    }

    public function testHotsDrafLaluFinalTersimpanDiTabelJawabanSiswa(): void
    {
        $this->jadikanMediaPertamaGambar();
        $this->db->table('progres_materi')->insert([
            'progres_tp_id'=>11,'materi_id'=>31,'versi_konten_tp_id'=>3,
            'jenis'=>'gambar','status'=>'media_selesai',
        ]);
        $this->db->table('tugas_hots_materi')->insert([
            'materi_id'=>31,'row_uuid'=>'uji-bank','pertanyaan'=>'Tugas Guru',
            'petunjuk'=>'Petunjuk Guru',
        ]);
        $svc = new \App\Services\KegiatanMateriSiswaService($this->db,
            static fn():array=>['id'=>101,'peran_aktif'=>'siswa',
                'status'=>'aktif','wajib_ganti_sandi'=>0]);
        $svc->simpan(1,31,'resume','kirim',str_repeat('R',50),0);
        $draft=$svc->simpan(1,31,'hots','simpan','Jawaban HOTS pendek',0);
        self::assertSame('draf',$draft['status']);
        self::assertSame(1,$draft['revisi']);
        self::assertSame('draf',$svc->status(1,31)['hots']['status']);
        self::assertFalse($this->svc()->lihat(1,31)['materi_berikutnya']['boleh_buka']);

        try {
            $svc->simpan(1,31,'hots','kirim','Terlalu singkat',1);
            self::fail('HOTS pendek tidak boleh dianggap final.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('200 karakter',$e->getMessage());
        }
        self::assertSame('draf',$svc->status(1,31)['hots']['status']);

        $finalText=str_repeat('H',205);
        $final=$svc->simpan(1,31,'hots','kirim',$finalText,1);
        self::assertSame('final',$final['status']);
        self::assertSame(2,$final['revisi']);
        $row=$this->db->table('jawaban_hots_materi')->get()->getRowArray();
        self::assertSame('final',$row['status']);
        self::assertSame($finalText,$row['isi']);
        self::assertSame(11,(int)$row['progres_tp_id']);
        self::assertSame(31,(int)$row['materi_id']);
        self::assertSame(3,(int)$row['versi_konten_tp_id']);
        self::assertNotNull($row['dikirim_pada']);
        self::assertSame('final',$svc->status(1,31)['hots']['status']);
        self::assertTrue($this->svc()->lihat(1,31)['materi_berikutnya']['boleh_buka']);

        // Pengiriman ulang identik tidak menggandakan nilai/riwayat.
        $retry=$svc->simpan(1,31,'hots','kirim',$finalText,1);
        self::assertSame('final',$retry['status']);
        self::assertSame(1,$this->db->table('jawaban_hots_materi')->countAllResults());
        self::assertSame(2,$this->db->table('log_aktivitas')->countAllResults());
        self::assertSame(1,$this->db->table('tugas_hots_materi')->countAllResults());
        self::assertSame('Tugas Guru',
            $this->db->table('tugas_hots_materi')->get()->getRow('pertanyaan'));
    }

    public function testBatasKegiatanVersiTerpinBukanNilaiTPLive(): void
    {
        $this->jadikanMediaPertamaGambar();
        $this->ubahBatasSnapshot(80,250);
        $this->db->table('tp')->where('id',1)->update([
            'resume_min'=>10,'tugas_min'=>50,
        ]);
        $this->db->table('progres_materi')->insert([
            'progres_tp_id'=>11,'materi_id'=>31,'versi_konten_tp_id'=>3,
            'jenis'=>'gambar','status'=>'media_selesai',
        ]);
        $k = new \App\Services\KegiatanMateriSiswaService($this->db,
            static fn():array=>['id'=>101,'peran_aktif'=>'siswa',
                'status'=>'aktif','wajib_ganti_sandi'=>0]);
        $status=$k->status(1,31);
        self::assertSame(80,$status['minimal_resume']);
        self::assertSame(250,$status['minimal_hots']);
        try {
            $k->simpan(1,31,'resume','kirim',str_repeat('R',79),0);
            self::fail('Angka live 10 tidak boleh mengganti snapshot 80.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('80 karakter',$e->getMessage());
        }
        $k->simpan(1,31,'resume','kirim',str_repeat('R',80),0);
        try {
            $k->simpan(1,31,'hots','kirim',str_repeat('H',249),0);
            self::fail('Angka live 50 tidak boleh mengganti snapshot 250.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('250 karakter',$e->getMessage());
        }
        $k->simpan(1,31,'hots','kirim',str_repeat('H',250),0);
        self::assertTrue($this->svc()->lihat(1,31)['materi_berikutnya']['boleh_buka']);
        self::assertSame(1,$this->db->table('resume_materi')->countAllResults());
        self::assertSame(1,$this->db->table('jawaban_hots_materi')->countAllResults());
    }

    public function testSnapshotTanpaBatasMinimalDitolakTanpaMengubahData(): void
    {
        $this->ubahBatasSnapshot(0,0);
        $k = new \App\Services\KegiatanMateriSiswaService($this->db,
            static fn():array=>['id'=>101,'peran_aktif'=>'siswa',
                'status'=>'aktif','wajib_ganti_sandi'=>0]);
        try {
            $k->status(1,31);
            self::fail('Snapshot tanpa batas sah harus ditolak.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('snapshot TP',$e->getMessage());
        }
        self::assertSame(0,$this->db->table('resume_materi')->countAllResults());
    }

    public function testYoutube380DetikDari360DirekonsiliasiDariProgresLama(): void
    {
        $row = $this->db->table('versi_konten_tp')
            ->where('id',3)->get()->getRowArray();
        $snap = json_decode($row['snapshot_json'],true);
        $snap['materi'][0]['jenis'] = 'youtube';
        $snap['materi'][0]['berkas_id'] = null;
        $snap['materi'][0]['url_youtube'] = 'https://www.youtube.com/watch?v=abcdefghijk';
        $snap['materi'][0]['min_waktu_detik'] = 360;
        $json = json_encode($snap,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->db->table('versi_konten_tp')->where('id',3)->update([
            'snapshot_json'=>$json,'checksum'=>hash('sha256',$json),
        ]);
        $this->db->table('progres_materi')->insert([
            'progres_tp_id'=>11,'materi_id'=>31,'versi_konten_tp_id'=>3,
            'jenis'=>'youtube','status'=>'berjalan',
            'waktu_aktif_detik'=>380, 'waktu_putar_detik'=>380,
            'posisi_terjauh_detik'=>380,'durasi_media_detik'=>3600,
        ]);
        $svc=$this->progresMediaService(101,static fn():int=>1500);
        $data=$svc->status(1,31);
        self::assertSame(380,$data['waktu_aktif_detik']);
        self::assertSame(360,$data['minimal_detik']);
        self::assertSame(360,$data['batas_efektif_detik']);
        self::assertTrue($data['syarat_efektif_terpenuhi']);
        self::assertSame(3240,$data['pemutaran_minimal_detik']);
        self::assertFalse($data['syarat_putar_terpenuhi']); // informasi 90%, bukan gate
        self::assertTrue($data['media_selesai']);
        self::assertSame('media_selesai',
            $this->db->table('progres_materi')->where('materi_id',31)
                ->get()->getRow('status'));
        self::assertNotNull($this->db->table('progres_materi')
            ->where('materi_id',31)->get()->getRow('media_selesai_pada'));
        self::assertTrue((new \App\Services\KegiatanMateriSiswaService(
            $this->db,static fn():array=>[
                'id'=>101,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
            ]))->status(1,31)['media_selesai']);
        $svc->status(1,31);
        self::assertSame(1,$this->db->table('progres_materi')->countAllResults());
    }

    public function testTuntasDiagnostikTidakDapatMengirimJawaban(): void
    {
        $this->jadikanMediaPertamaGambar();
        $k = new \App\Services\KegiatanMateriSiswaService($this->db,
            static fn (): array => [
                'id'=>102,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
            ]);
        $this->expectException(\DomainException::class);
        $k->simpan(1,31,'resume','kirim',str_repeat('x',50),0);
    }

    public function testSimpanDenganRevisiLamaDitolakTanpaTimpa(): void
    {
        $this->jadikanMediaPertamaGambar();
        $this->db->table('progres_materi')->insert([
            'progres_tp_id'=>11,'materi_id'=>31,'versi_konten_tp_id'=>3,
            'jenis'=>'gambar','status'=>'media_selesai',
        ]);
        $k = new \App\Services\KegiatanMateriSiswaService($this->db,
            static fn (): array => [
                'id'=>101,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
            ]);
        $k->simpan(1,31,'resume','simpan','Teks asli',0);
        try {
            $k->simpan(1,31,'resume','simpan','Teks lain',0);
            self::fail('Konflik revisi harus ditolak.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('tab lain',$e->getMessage());
        }
        self::assertSame('Teks asli',$k->status(1,31)['resume']['isi']);
    }

}
