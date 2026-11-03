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
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT,status TEXT)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE ref_kurikulum(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER,kurikulum_id INTEGER,status TEXT)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,guru_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE penugasan_paket(id INTEGER PRIMARY KEY,paket_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER,tahun_ajaran_id INTEGER)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER,semester_mulai INTEGER)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,jenis_tp TEXT,status TEXT)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,tp_id INTEGER,siswa_id INTEGER,paket_semester_id INTEGER,status TEXT)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,nomor_bab INTEGER,nama_bab TEXT)',
            'CREATE TABLE ujian_pra_semester(id INTEGER PRIMARY KEY AUTOINCREMENT,paket_semester_id INTEGER,status TEXT,mulai_pada TEXT,selesai_pada TEXT,jumlah_soal INTEGER,durasi_menit INTEGER,kkm_khusus INTEGER,dibuat_oleh INTEGER,dibuat_pada TEXT)',
            'CREATE TABLE soal_pra_semester(id INTEGER PRIMARY KEY AUTOINCREMENT,ujian_pra_semester_id INTEGER,bab_id INTEGER,urutan INTEGER,pertanyaan TEXT,konten_terstruktur_json TEXT,opsi_a TEXT,opsi_b TEXT,opsi_c TEXT,opsi_d TEXT,opsi_e TEXT,kunci TEXT,aktif INTEGER)',
            'CREATE TABLE percobaan_ujian(id INTEGER PRIMARY KEY AUTOINCREMENT,token_publik TEXT,progres_tp_id INTEGER,versi_konten_tp_id INTEGER,ujian_pra_semester_id INTEGER,jadwal_pra_sesi_id INTEGER,siswa_id INTEGER,jenis TEXT,tahap TEXT,nomor_percobaan INTEGER,status TEXT,profil_keamanan TEXT,jumlah_soal INTEGER,kkm INTEGER,durasi_detik INTEGER,sisa_detik INTEGER,waktu_mulai TEXT,waktu_aktif_terakhir TEXT,last_seen_server_at TEXT,jeda_mulai_pada TEXT,jeda_fullscreen_total_detik INTEGER DEFAULT 0,jeda_koneksi_total_detik INTEGER DEFAULT 0,jumlah_pelanggaran_fullscreen INTEGER DEFAULT 0,waktu_selesai TEXT,alasan_batal TEXT,skor REAL,jumlah_benar INTEGER,dibuat_pada TEXT,diubah_pada TEXT)',
            'CREATE TABLE jadwal_pra_sesi(id INTEGER PRIMARY KEY AUTOINCREMENT,ujian_pra_semester_id INTEGER,jenis TEXT,tahap TEXT,mulai_pada TEXT,selesai_pada TEXT,durasi_menit INTEGER,status TEXT,dibuat_oleh INTEGER,dibuat_pada TEXT)',
            'CREATE TABLE peserta_pra_susulan(id INTEGER PRIMARY KEY AUTOINCREMENT,jadwal_pra_sesi_id INTEGER,siswa_id INTEGER,ditetapkan_oleh INTEGER,ditetapkan_pada TEXT,UNIQUE(jadwal_pra_sesi_id,siswa_id))',
            'CREATE TABLE impor_soal_pra_docx(id TEXT PRIMARY KEY,paket_semester_id INTEGER,guru_id INTEGER,checksum TEXT,status TEXT,data_json TEXT,dibuat_pada TEXT,kedaluwarsa_pada TEXT,diterapkan_pada TEXT)',
            'CREATE TABLE butir_percobaan(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,soal_asal_id INTEGER,soal_pra_semester_id INTEGER,posisi INTEGER,pertanyaan TEXT,konten_terstruktur_json TEXT,opsi_acak_json TEXT,kunci_acak TEXT)',
            'CREATE TABLE jawaban_percobaan(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,butir_percobaan_id INTEGER,opsi_dipilih TEXT,waktu_disimpan TEXT, UNIQUE(percobaan_ujian_id,butir_percobaan_id))',
            'CREATE TABLE riwayat_status_ujian(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,status_lama TEXT,status_baru TEXT,alasan TEXT,terjadi_pada TEXT)',
            'CREATE TABLE segmen_jeda_ujian(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,jenis TEXT,dimulai_pada TEXT,berakhir_pada TEXT,durasi_detik INTEGER,alasan TEXT)',
            'CREATE TABLE pelanggaran_ujian(id INTEGER PRIMARY KEY AUTOINCREMENT,percobaan_ujian_id INTEGER,kunci_insiden TEXT,kategori TEXT,dihitung INTEGER,alasan TEXT,terjadi_pada TEXT)',
            'CREATE TABLE pengaturan(kunci TEXT PRIMARY KEY,nilai TEXT)',
            'CREATE TABLE log_aktivitas(id INTEGER PRIMARY KEY AUTOINCREMENT,pengguna_id INTEGER,peran_aktif TEXT,peristiwa TEXT,catatan TEXT,waktu TEXT)',
        ];
        foreach($sql as $q)$this->db->query($q);
        $this->db->table('pengguna')->insert(['id'=>100,'nama'=>'Siswa Anonim','status'=>'aktif']);
        $this->db->table('ref_angkatan')->insert(['id'=>1,'nama'=>'Gen Uji']);
        $this->db->table('ref_kurikulum')->insert(['id'=>1,'nama'=>'KBC']);
        $this->db->table('ref_mapel')->insertBatch([
            ['id'=>1,'nama'=>'SKI'],['id'=>2,'nama'=>'Fikih']
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>11,'angkatan_id'=>1,'mapel_id'=>1,'semester'=>4,'tahun_ajaran_id'=>3,'kurikulum_id'=>1,'status'=>'aktif'],
            ['id'=>12,'angkatan_id'=>1,'mapel_id'=>2,'semester'=>4,'tahun_ajaran_id'=>3,'kurikulum_id'=>1,'status'=>'aktif'],
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

    public function testFilterDaftarAdminDanGuruDiterapkanPadaQueryServer(): void
    {
        $admin = new PraSemesterJadwalService($this->db,$this->actor('admin',1));
        self::assertCount(2,$admin->daftar());
        self::assertCount(1,$admin->daftar(['gen'=>'1','mapel'=>'1','semester'=>'4','status'=>'siap']));
        self::assertCount(1,$admin->daftar(['mapel'=>'2','status'=>'belum']));
        self::assertSame(12,(int)$admin->daftar(['mapel'=>'2','status'=>'belum'])[0]['paket_id']);
        self::assertSame([], $admin->daftar(['semester'=>'5']));
        self::assertSame([], $admin->daftar(['status'=>'ditutup']));

        $guru = new PraSemesterGuruService($this->db,$this->actor('guru',10));
        self::assertCount(1,$guru->paketGuru());
        self::assertCount(1,$guru->paketGuru(1));
        self::assertSame([],$guru->paketGuru(2)); // Gen lain tidak boleh terbuka
        self::assertSame([1=>'Gen Uji'],$guru->pilihanGen());
        self::assertSame(1,PraSemesterGuruService::filterGen('1'));
    }

    public function testFilterWhitelistMenolakInputTidakSah(): void
    {
        foreach ([
            ['gen'=>'-1'],
            ['mapel'=>'1 OR 1=1'],
            ['semester'=>'7'],
            ['status'=>'dibatalkan_menunggu_guru'],
        ] as $f) {
            try {
                PraSemesterJadwalService::filterDaftar($f);
                self::fail('Filter Admin yang tidak sah seharusnya ditolak.');
            } catch (\DomainException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
        foreach (['-2','1 OR 1=1',[]] as $f) {
            try {
                PraSemesterGuruService::filterGen($f);
                self::fail('Filter Guru yang tidak sah seharusnya ditolak.');
            } catch (\DomainException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
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
        // Default Admin berubah setelah attempt pertama: KKM tetap dipin.
        $this->db->table('pengaturan')->where('kunci','kkm_default')->update(['nilai'=>'95']);
        $tetap=(new PraSemesterSiswaService($this->db,$this->actor('siswa',100)))->konteks(11);
        self::assertSame(75,$tetap['kkm']['efektif']);
        self::assertSame('snapshot_ujian',$tetap['kkm']['sumber']);
        $selesai=$mesin->tindakan(11,$mulai['token'],'kirim',true);
        self::assertSame('selesai_sah',$selesai['status']);
        self::assertSame(0.0,$selesai['skor']);
        self::assertSame(1,$this->db->table('percobaan_ujian')
            ->where('jenis','pra_semester')->countAllResults());
        $this->expectException(\DomainException::class);
        $mesin->mulai(11,true,true);
    }


    public function testSiswaMenjawabDanMengubahPilihanTanpaDuplikasiLaluSelesaiSah(): void
    {
        $mesin = new MesinPraSemesterSiswaService($this->db,$this->actor('siswa',100));
        $mulai = $mesin->mulai(11,true,true);
        $token = $mulai['token'];
        $semua = $mesin->status(11,$token);
        self::assertCount(30,$semua['soal']);
        self::assertArrayNotHasKey('kunci_acak',$semua['soal'][0]);

        $butir1=(int)$semua['soal'][0]['id'];
        $butir2=(int)$semua['soal'][1]['id'];
        $kunci1=(string)$this->db->table('butir_percobaan')
            ->where('id',$butir1)->get()->getRow('kunci_acak');
        $kunci2=(string)$this->db->table('butir_percobaan')
            ->where('id',$butir2)->get()->getRow('kunci_acak');
        $salah=$kunci1 === 'A' ? 'B' : 'A';

        self::assertTrue($mesin->jawab(11,$token,$butir1,$salah)['tersimpan']);
        self::assertTrue($mesin->jawab(11,$token,$butir1,$kunci1)['tersimpan']);
        self::assertTrue($mesin->jawab(11,$token,$butir2,$kunci2)['tersimpan']);
        self::assertSame(2,$this->db->table('jawaban_percobaan')
            ->where('percobaan_ujian_id',(int)$this->db->table('percobaan_ujian')
                ->where('token_publik',$token)->get()->getRow('id'))->countAllResults());

        $status=$mesin->status(11,$token);
        self::assertSame($kunci1,$status['jawaban'][$butir1]);
        self::assertSame($kunci2,$status['jawaban'][$butir2]);
        $selesai=$mesin->tindakan(11,$token,'kirim',true);
        self::assertSame('selesai_sah',$selesai['status']);
        self::assertSame(2,(int)$selesai['jumlah_benar']);
        self::assertSame(6.67,(float)$selesai['skor']);
        self::assertFalse($mesin->jawab(11,$token,$butir1,$salah)['tersimpan']);
        self::assertSame('selesai_sah',$mesin->status(11,$token)['status']);
        self::assertSame(6.67,$mesin->ruang(11)['nilai_terbaik']);
        self::assertSame('menunggu_remedial',$mesin->ruang(11)['fase']);
        self::assertFalse($mesin->ruang(11)['boleh_mulai']);

        $this->expectException(\\DomainException::class);
        $mesin->mulai(11,true,true); // tidak membuka remedial sendiri
    }

    public function testGerbangSiswaTidakMenganggapProgresTuntasTanpaSumatifSah(): void
    {
        $this->db->table('percobaan_ujian')
            ->where('progres_tp_id',33)->where('jenis','sumatif')->delete();
        $this->db->table('progres_tp')->where('id',33)->update(['status'=>'tuntas']);
        $siswa=new PraSemesterSiswaService($this->db,$this->actor('siswa',100));
        $ctx=$siswa->konteks(11);
        self::assertFalse($ctx['gerbang']['tp']['siap']);
        self::assertFalse((new MesinPraSemesterSiswaService(
            $this->db,$this->actor('siswa',100)))->ruang(11)['boleh_mulai']);
        $this->expectException(\\DomainException::class);
        (new MesinPraSemesterSiswaService(
            $this->db,$this->actor('siswa',100)))->mulai(11,true,true);
    }

    public function testPembatalanPraSemesterTanpaSkorDanTanpaAksesUlangMandiri(): void
    {
        $mesin = new MesinPraSemesterSiswaService($this->db,$this->actor('siswa',100));
        $token = $mesin->mulai(11,true,true)['token'];
        for ($i=0; $i<3; $i++) {
            $s=$mesin->tindakan(11,$token,'keluar_fullscreen',false);
            if ($i<2) {
                self::assertSame('jeda_fullscreen',$s['status']);
                $s=$mesin->tindakan(11,$token,'pulih',true);
                self::assertSame('berjalan',$s['status']);
            }
        }
        self::assertSame('dibatalkan_menunggu_guru',$s['status']);
        self::assertNull($s['skor']);
        self::assertSame('dibatalkan_menunggu_guru',
            $mesin->status(11,$token)['status']);
        self::assertNull($this->db->table('percobaan_ujian')
            ->where('token_publik',$token)->get()->getRow('skor'));
        $this->expectException(\DomainException::class);
        $mesin->mulai(11,true,true);
    }

    public function testBankDanKKMTerkunciSetelahPercobaanPertama(): void
    {
        $guru=new PraSemesterGuruService($this->db,$this->actor('guru',10));
        $mesin=new MesinPraSemesterSiswaService($this->db,$this->actor('siswa',100));
        $mesin->mulai(11,true,true);
        $this->expectException(\DomainException::class);
        $guru->simpanKkm(11,'88');
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
    private function jadwalTambahanAktif(string $jenis,string $tahap): int
    {
        $wib=new \DateTimeImmutable('now',new \DateTimeZone('Asia/Jakarta'));
        $this->db->table('jadwal_pra_sesi')->insert([
            'ujian_pra_semester_id'=>51,'jenis'=>$jenis,'tahap'=>$tahap,
            'mulai_pada'=>$wib->modify('-1 minute')->format('Y-m-d H:i:s'),
            'selesai_pada'=>$wib->modify('+1 hour')->format('Y-m-d H:i:s'),
            'durasi_menit'=>35,'status'=>'siap','dibuat_oleh'=>1,
            'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insertID();
    }

    public function testRemedialTerjadwalMembukaSatuAttemptDanNilaiTerbaik(): void
    {
        $this->db->table('percobaan_ujian')->insert([
            'ujian_pra_semester_id'=>51,'siswa_id'=>1,'jenis'=>'pra_semester',
            'tahap'=>'normal','nomor_percobaan'=>1,'status'=>'selesai_sah',
            'skor'=>50,'kkm'=>75,'token_publik'=>str_repeat('a',48),
        ]);
        $id=$this->jadwalTambahanAktif('remedial','remedial');
        $mesin=new MesinPraSemesterSiswaService($this->db,$this->actor('siswa',100));
        $ruang=$mesin->ruang(11);
        self::assertTrue($ruang['boleh_mulai']);
        self::assertSame('remedial',$ruang['tahap_berikut']);
        self::assertSame(50.0,$ruang['nilai_terbaik']);
        $baru=$mesin->mulai(11,true,true);
        self::assertSame('remedial',$baru['tahap']);
        $saved=$this->db->table('percobaan_ujian')
            ->where('token_publik',$baru['token'])->get()->getRowArray();
        self::assertSame(2,(int)$saved['nomor_percobaan']);
        self::assertSame($id,(int)$saved['jadwal_pra_sesi_id']);
        self::assertSame(35*60,(int)$saved['durasi_detik']);
        $done=$mesin->tindakan(11,$baru['token'],'kirim',true);
        self::assertSame('selesai_sah',$done['status']);
        self::assertSame('remedial',$done['tahap']);
        self::assertSame(50.0,$mesin->ruang(11)['nilai_terbaik']);
        self::assertSame('final',$mesin->ruang(11)['status_nilai']);
        self::assertFalse($mesin->ruang(11)['boleh_mulai']);
    }

    public function testSusulanBatalMembutuhkanPesertaYangDitunjukAdmin(): void
    {
        $this->db->table('percobaan_ujian')->insert([
            'ujian_pra_semester_id'=>51,'siswa_id'=>1,'jenis'=>'pra_semester',
            'tahap'=>'normal','nomor_percobaan'=>1,'status'=>'dibatalkan_menunggu_guru',
            'skor'=>null,'kkm'=>75,'token_publik'=>str_repeat('b',48),
        ]);
        $id=$this->jadwalTambahanAktif('susulan','normal');
        $mesin=new MesinPraSemesterSiswaService($this->db,$this->actor('siswa',100));
        self::assertFalse($mesin->ruang(11)['boleh_mulai']);
        $this->db->table('peserta_pra_susulan')->insert([
            'jadwal_pra_sesi_id'=>$id,'siswa_id'=>1,'ditetapkan_oleh'=>1,
            'ditetapkan_pada'=>gmdate('Y-m-d H:i:s'),
        ]);
        self::assertTrue($mesin->ruang(11)['boleh_mulai']);
        $baru=$mesin->mulai(11,true,true);
        self::assertSame('normal',$baru['tahap']);
        $saved=$this->db->table('percobaan_ujian')
            ->where('token_publik',$baru['token'])->get()->getRowArray();
        self::assertSame(2,(int)$saved['nomor_percobaan']);
        self::assertSame($id,(int)$saved['jadwal_pra_sesi_id']);
        self::assertNull($saved['skor']);
    }

    public function testAdminJadwalSusulanAbsenDanDaftarPesertaAditif(): void
    {
        $wib=new \DateTimeImmutable('now',new \DateTimeZone('Asia/Jakarta'));
        $this->db->table('ujian_pra_semester')->where('id',51)->update([
            'mulai_pada'=>$wib->modify('-3 days')->format('Y-m-d H:i:s'),
            'selesai_pada'=>$wib->modify('-2 days')->format('Y-m-d H:i:s'),
        ]);
        $admin=new \App\Services\PraSemesterSesiService($this->db,$this->actor('admin',1));
        $d=$wib->modify('+2 days')->setTime(8,0);
        $id=$admin->buat(11,'susulan','normal',
            $d->format('Y-m-d\TH:i'),$d->modify('+1 hour')->format('Y-m-d\TH:i'),'45');
        self::assertGreaterThan(0,$id);
        $calon=$admin->calonSusulan(11,$id);
        self::assertCount(1,$calon);
        self::assertTrue($calon[0]['boleh_ditambah']);
        self::assertSame(1,$admin->tambahPeserta(11,$id,['1']));
        self::assertSame(0,$admin->tambahPeserta(11,$id,['1']));
        self::assertTrue($admin->calonSusulan(11,$id)[0]['ditetapkan']);
        self::assertSame(1,(int)$admin->daftar(11)[0]['jumlah_peserta']);
        $this->expectException(\DomainException::class);
        $admin->buat(11,'susulan','normal',
            $d->format('Y-m-d\TH:i'),$d->modify('+1 hour')->format('Y-m-d\TH:i'),'45');
    }

    public function testWordPraSemesterImporAppendDanIdempotenDenganSiswaBelumMenggunakan(): void
    {
        $guru = new \App\Services\PraSemesterWordService(
            $this->db,$this->actor('guru',10)
        );
        $data = $guru->unduh(11,30);
        $path = tempnam(sys_get_temp_dir(),'pra-dbword-');
        self::assertIsString($path);
        file_put_contents($path,$data);
        $stageId=null;
        try {
            $zip=new \ZipArchive();
            self::assertTrue($zip->open($path)===true);
            $doc=new \DOMDocument();
            self::assertTrue($doc->loadXML($zip->getFromName('word/document.xml'),LIBXML_NONET));
            $xp=new \DOMXPath($doc);
            $xp->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $rows=$xp->query('//w:body/w:tbl[1]/w:tr');
            for($i=0;$i<30;$i++) {
                for($j=0;$j<6;$j++) {
                    $cells=$xp->query('./w:tc',$rows->item(1+$i*6+$j));
                    $cell=$xp->query('.//w:t',$cells->item(1))->item(0);
                    $cell->nodeValue= $j===0 ? 'Pertanyaan A8 '.($i+1)
                        : 'Opsi '.chr(64+$j).' nomor '.($i+1);
                    if($j===1) {
                        $xp->query('.//w:t',$cells->item(2))->item(0)->nodeValue='✓';
                    }
                }
            }
            self::assertTrue($zip->addFromString('word/document.xml',$doc->saveXML()));
            self::assertTrue($zip->close());
            $stage=$guru->unggah($path,11);
            $stageId=$stage['id'];
            self::assertSame(30,$stage['jumlah']);
            // Unggah hanya staging; semua 30 soal manual lama tetap sama.
            self::assertSame(30,$this->db->table('soal_pra_semester')->countAllResults());
            self::assertSame('pratinjau',$guru->buka(11,$stageId)['row']['status']);
            $confirm=$guru->konfirmasi(11,$stageId);
            self::assertSame(30,$confirm['baru']);
            self::assertSame(60,$this->db->table('soal_pra_semester')->countAllResults());
            self::assertSame('Soal 1',$this->db->table('soal_pra_semester')
                ->where('id',1)->get()->getRow('pertanyaan')); // manual tidak ditimpa
            $rich=$this->db->table('soal_pra_semester')
                ->where('urutan',31)->get()->getRow('konten_terstruktur_json');
            self::assertSame($stageId,json_decode($rich,true)['word_source_id']);
            $repeat=$guru->konfirmasi(11,$stageId);
            self::assertSame(0,$repeat['baru']);
            self::assertSame(60,$this->db->table('soal_pra_semester')->countAllResults());
            self::assertSame('diterapkan',$guru->buka(11,$stageId)['row']['status']);
            $this->db->table('percobaan_ujian')->insert([
                'ujian_pra_semester_id'=>51,'siswa_id'=>1,'jenis'=>'pra_semester',
                'tahap'=>'normal','nomor_percobaan'=>1,'status'=>'selesai_sah',
                'kkm'=>75,'skor'=>75,
            ]);
            $this->expectException(\DomainException::class);
            $guru->unggah($path,11);
        } finally {
            @unlink($path);
            if($stageId!==null) {
                @unlink(WRITEPATH.'private/pra_semester_word/'.$stageId.'.docx');
            }
        }
    }

}
