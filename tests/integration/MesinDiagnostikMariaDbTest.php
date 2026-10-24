<?php

declare(strict_types=1);

use App\Services\MesinDiagnostikSiswaService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/**
 * Hanya MariaDB sementara bernama sla_s06_test di GitHub Actions.
 * Tidak terhubung database XAMPP atau data siswa nyata.
 */
final class MesinDiagnostikMariaDbTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $dsn = (string) getenv('SLA_TEST_DB_DSN');
        if (!str_starts_with($dsn, 'mysql:dbname=sla_s06_test;')) {
            self::markTestSkipped('Integrasi hanya untuk MariaDB sintetis sla_s06_test.');
        }
        $this->db = \Config\Database::connect([
            'DSN'=>'', 'hostname'=>'127.0.0.1', 'username'=>'root',
            'password'=>(string) getenv('SLA_TEST_DB_PASS'), 'database'=>'sla_s06_test',
            'DBDriver'=>'MySQLi', 'DBPrefix'=>'', 'port'=>3306, 'pConnect'=>false,
            'DBDebug'=>true, 'charset'=>'utf8mb4', 'DBCollat'=>'utf8mb4_general_ci',
            'failover'=>[],
        ], false);
        self::assertSame('sla_s06_test', $this->db->query('SELECT DATABASE() AS db')->getRowArray()['db']);
        foreach ([
            'CREATE TABLE ref_angkatan(id BIGINT UNSIGNED PRIMARY KEY,kode VARCHAR(30),nama VARCHAR(100),semester_mulai INT) ENGINE=InnoDB',
            'CREATE TABLE siswa(id BIGINT UNSIGNED PRIMARY KEY,pengguna_id BIGINT UNSIGNED,angkatan_id BIGINT UNSIGNED,semester_mulai INT NULL) ENGINE=InnoDB',
            'CREATE TABLE ref_mapel(id BIGINT UNSIGNED PRIMARY KEY,kode VARCHAR(30),nama VARCHAR(100)) ENGINE=InnoDB',
            'CREATE TABLE paket_semester(id BIGINT UNSIGNED PRIMARY KEY,angkatan_id BIGINT UNSIGNED,mapel_id BIGINT UNSIGNED,semester INT,tahun_ajaran_id INT,nomor_versi INT,status VARCHAR(30)) ENGINE=InnoDB',
            'CREATE TABLE penugasan_paket(id BIGINT UNSIGNED PRIMARY KEY,angkatan_id BIGINT UNSIGNED,mapel_id BIGINT UNSIGNED,semester INT,tahun_ajaran_id INT,paket_id BIGINT UNSIGNED) ENGINE=InnoDB',
            'CREATE TABLE tp(id BIGINT UNSIGNED PRIMARY KEY,paket_semester_id BIGINT UNSIGNED,kode_tp VARCHAR(64),judul TEXT,urutan_atp INT,status VARCHAR(30),rumusan TEXT,indikator TEXT) ENGINE=InnoDB',
            'CREATE TABLE materi(id BIGINT UNSIGNED PRIMARY KEY,tp_id BIGINT UNSIGNED,urutan INT,judul TEXT,jenis VARCHAR(15),aktif INT) ENGINE=InnoDB',
            'CREATE TABLE versi_konten_tp(id BIGINT UNSIGNED PRIMARY KEY,tp_id BIGINT UNSIGNED,nomor_versi INT,snapshot_json LONGTEXT,checksum CHAR(64)) ENGINE=InnoDB',
            'CREATE TABLE progres_tp(id BIGINT UNSIGNED PRIMARY KEY,siswa_id BIGINT UNSIGNED,tp_id BIGINT UNSIGNED,paket_semester_id BIGINT UNSIGNED,versi_paket_yang_ditugaskan INT,versi_konten_tp_id BIGINT UNSIGNED,status VARCHAR(30),dimulai_pada DATETIME,diubah_pada DATETIME) ENGINE=InnoDB',
        ] as $sql) $this->db->query($sql);
        $sql = file_get_contents(dirname(__DIR__,2).'/database/sql/updates/0015_mesin_ujian_fondasi.sql');
        $sql = preg_replace('/^--.*$/m', '', (string) $sql);
        foreach (explode(';', $sql) as $q) if (trim($q)!=='') $this->db->query($q);

        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'GEN-A','nama'=>'Gen A','semester_mulai'=>4],
            ['id'=>2,'kode'=>'GEN-B','nama'=>'Gen B','semester_mulai'=>4],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>11,'pengguna_id'=>101,'angkatan_id'=>1,'semester_mulai'=>null],
            ['id'=>12,'pengguna_id'=>102,'angkatan_id'=>1,'semester_mulai'=>null],
            ['id'=>13,'pengguna_id'=>103,'angkatan_id'=>1,'semester_mulai'=>null],
            ['id'=>22,'pengguna_id'=>202,'angkatan_id'=>2,'semester_mulai'=>null],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>4,'kode'=>'SKI','nama'=>'SKI']);
        $this->db->table('paket_semester')->insert([
            'id'=>7,'angkatan_id'=>1,'mapel_id'=>4,'semester'=>4,'tahun_ajaran_id'=>1,
            'nomor_versi'=>1,'status'=>'aktif',
        ]);
        $this->db->table('penugasan_paket')->insert([
            'id'=>7,'angkatan_id'=>1,'mapel_id'=>4,'semester'=>4,'tahun_ajaran_id'=>1,'paket_id'=>7,
        ]);
        $this->db->table('tp')->insert([
            'id'=>1,'paket_semester_id'=>7,'kode_tp'=>'TP-SKI-S4-01','judul'=>'Contoh TP',
            'urutan_atp'=>1,'status'=>'siap','rumusan'=>'Rumusan contoh','indikator'=>'Indikator contoh',
        ]);
        $soal=[];
        foreach (range(1,10) as $i) $soal[]=[
            'id'=>$i,'urutan'=>$i,'jenis'=>'diagnostik','aktif'=>1,
            'pertanyaan'=>'Contoh pertanyaan '.$i,
            'opsi_a'=>'A-'.$i,'opsi_b'=>'B-'.$i,'opsi_c'=>'C-'.$i,
            'opsi_d'=>'D-'.$i,'opsi_e'=>'E-'.$i,'kunci'=>'C',
            'konten_terstruktur_json'=>null,
        ];
        $snapshot=['format'=>'SLA-SKS-TP-V1','tp'=>[
            'id'=>1,'status'=>'siap','diag_tayang'=>10,'diag_durasi'=>30,'kkm'=>75,
        ],'materi'=>[],'soal'=>$soal];
        $json=json_encode($snapshot, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $this->db->table('versi_konten_tp')->insert([
            'id'=>21,'tp_id'=>1,'nomor_versi'=>1,'snapshot_json'=>$json,'checksum'=>hash('sha256',$json),
        ]);
        foreach ([11=>101,12=>102,13=>103] as $id=>$user) $this->db->table('progres_tp')->insert([
            'id'=>$id,'siswa_id'=>$id,'tp_id'=>1,'paket_semester_id'=>7,
            'versi_paket_yang_ditugaskan'=>1,'versi_konten_tp_id'=>21,
            'status'=>'diagnostik','dimulai_pada'=>'2026-10-24 00:00:00',
            'diubah_pada'=>'2026-10-24 00:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) $this->db->close();
        parent::tearDown();
    }

    private function mesin(int $id=101): MesinDiagnostikSiswaService
    {
        return new MesinDiagnostikSiswaService($this->db, static fn (): array => [
            'id'=>$id,'peran_aktif'=>'siswa','status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    public function testAlurSahDanBatalTidakMembocorkanKunciAtauDuplikasi(): void
    {
        $engine=$this->mesin(101);
        self::assertNull($engine->ruang(1)['percobaan']);
        self::assertSame(0,$this->db->table('percobaan_ujian')->countAllResults());
        $start=$engine->mulai(1,true);
        self::assertSame(48,strlen($start['token']));
        self::assertFalse($start['sudah_ada']);
        self::assertTrue($engine->mulai(1,true)['sudah_ada']);
        self::assertSame(1,$this->db->table('percobaan_ujian')->countAllResults());

        $token=$start['token'];
        $status=$engine->status(1,$token);
        self::assertSame('berjalan',$status['status']);
        self::assertCount(10,$status['soal']);
        self::assertArrayNotHasKey('kunci_acak',$status['soal'][0]);
        self::assertArrayNotHasKey('soal_asal_id',$status['soal'][0]);
        self::assertStringNotContainsString('kunci_acak',json_encode($status));
        $id=(int)$status['soal'][0]['id'];
        $key=(string)$this->db->table('butir_percobaan')->select('kunci_acak')
            ->where('id',$id)->get()->getRowArray()['kunci_acak'];
        $salah=$key==='A'?'B':'A';
        self::assertTrue($engine->jawab(1,$token,$id,$salah)['tersimpan']);
        self::assertTrue($engine->jawab(1,$token,$id,$key)['tersimpan']);
        self::assertSame(1,$this->db->table('jawaban_percobaan')->countAllResults());
        foreach ($status['soal'] as $butir) {
            $id=(int)$butir['id'];
            $kunci=(string)$this->db->table('butir_percobaan')->select('kunci_acak')
                ->where('id',$id)->get()->getRowArray()['kunci_acak'];
            $engine->jawab(1,$token,$id,$kunci);
        }
        try {
            $engine->tindakan(1,$token,'kirim');
            self::fail('Kirim tanpa sinyal fullscreen harus ditolak.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('fullscreen', $e->getMessage());
        }
        $end=$engine->tindakan(1,$token,'kirim',true);
        self::assertSame('selesai_sah',$end['status']);
        self::assertSame(100.0,$end['skor']);
        self::assertSame('tuntas',$this->db->table('progres_tp')
            ->select('status')->where('id',11)->get()->getRowArray()['status']);
        self::assertArrayNotHasKey('soal',$engine->status(1,$token));
        self::assertSame($token,$engine->mulai(1,true)['token']);
        self::assertSame(1,$this->db->table('percobaan_ujian')->countAllResults());

        $other=$this->mesin(102);
        $start2=$other->mulai(1,true);
        $token2=$start2['token'];
        self::assertSame('berjalan',$other->status(1,$token2)['status']);
        for ($i=0;$i<3;$i++) {
            $pause=$other->tindakan(1,$token2,'keluar_fullscreen');
            if ($i<2) $other->tindakan(1,$token2,'pulih',true);
        }
        self::assertSame('dibatalkan_menunggu_guru',$pause['status']);
        self::assertNull($pause['skor']);
        self::assertSame('perlu_bantuan',$this->db->table('progres_tp')
            ->select('status')->where('id',12)->get()->getRowArray()['status']);
        self::assertSame(0,$this->db->table('jawaban_percobaan')
            ->where('percobaan_ujian_id',2)->countAllResults());
        self::assertSame(2,$this->db->table('percobaan_ujian')->countAllResults());

        // Heartbeat lama dideteksi sebagai jeda koneksi; jawaban tidak boleh tersimpan.
        $third=$this->mesin(103);
        $thirdToken=$third->mulai(1,true)['token'];
        $thirdRoom=$third->status(1,$thirdToken);
        $thirdButir=(int)$thirdRoom['soal'][0]['id'];
        $old=gmdate('Y-m-d H:i:s',time()-35);
        $this->db->table('percobaan_ujian')->where('token_publik',$thirdToken)
            ->update(['waktu_aktif_terakhir'=>$old,'last_seen_server_at'=>$old]);
        $pause=$third->jawab(1,$thirdToken,$thirdButir,'A');
        self::assertSame('jeda_koneksi',$pause['status']);
        self::assertFalse($pause['tersimpan']);
        self::assertSame(0,$this->db->table('jawaban_percobaan')
            ->where('percobaan_ujian_id',3)->countAllResults());
        $pulih=$third->tindakan(1,$thirdToken,'pulih',true);
        self::assertSame('berjalan',$pulih['status']);
        self::assertGreaterThanOrEqual(15,$pulih['jeda_koneksi_detik']);

        // Bila waktu aktif habis pada request jawab, skor 0 disimpan sah
        // atomik dan jawaban terlambat ditolak.
        $old=gmdate('Y-m-d H:i:s',time()-15);
        $this->db->table('percobaan_ujian')->where('token_publik',$thirdToken)
            ->update(['sisa_detik'=>10, 'waktu_aktif_terakhir'=>$old,
                'last_seen_server_at'=>$old]);
        $expired=$third->jawab(1,$thirdToken,$thirdButir,'A');
        self::assertSame('selesai_sah',$expired['status']);
        self::assertFalse($expired['tersimpan']);
        self::assertSame(0.0,$expired['skor']);
        self::assertSame('belajar',$this->db->table('progres_tp')
            ->select('status')->where('id',13)->get()->getRowArray()['status']);
        self::assertSame(0,$this->db->table('jawaban_percobaan')
            ->where('percobaan_ujian_id',3)->countAllResults());

        $this->expectException(\DomainException::class);
        $this->mesin(202)->status(1,$token);
    }
}
