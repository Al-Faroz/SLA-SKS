<?php

declare(strict_types=1);

use App\Services\InformasiGlobalService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/** A13-2: database sintetis SQLite; bukan dump privat XAMPP. */
final class InformasiGlobalDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,
            'foreignKeys'=>false,'busyTimeout'=>1000,'synchronous'=>null,'failover'=>[],
        ],false);
        foreach ([
            'CREATE TABLE pengumuman (
                id INTEGER PRIMARY KEY AUTOINCREMENT, judul TEXT NOT NULL, isi TEXT NOT NULL,
                status TEXT NOT NULL, dibuat_oleh INTEGER NOT NULL, diubah_oleh INTEGER NOT NULL,
                versi INTEGER NOT NULL, dibuat_pada TEXT NOT NULL, diubah_pada TEXT NOT NULL,
                diterbitkan_pada TEXT, diarsipkan_pada TEXT)',
            'CREATE TABLE faq (
                id INTEGER PRIMARY KEY AUTOINCREMENT, pertanyaan TEXT NOT NULL, jawaban TEXT NOT NULL,
                urutan INTEGER NOT NULL, status TEXT NOT NULL, dibuat_oleh INTEGER NOT NULL,
                diubah_oleh INTEGER NOT NULL, versi INTEGER NOT NULL, dibuat_pada TEXT NOT NULL,
                diubah_pada TEXT, diterbitkan_pada TEXT, diarsipkan_pada TEXT)',
            'CREATE TABLE riwayat_informasi (
                id INTEGER PRIMARY KEY AUTOINCREMENT, jenis TEXT NOT NULL, objek_id INTEGER NOT NULL,
                aksi TEXT NOT NULL, pengguna_id INTEGER NOT NULL, peran_aktif TEXT NOT NULL,
                sebelum_json TEXT, sesudah_json TEXT NOT NULL, terjadi_pada TEXT NOT NULL)',
            'CREATE TABLE log_aktivitas (
                id INTEGER PRIMARY KEY AUTOINCREMENT, pengguna_id INTEGER,
                peran_aktif TEXT, peristiwa TEXT NOT NULL, catatan TEXT, waktu TEXT NOT NULL)',
            'CREATE TABLE pengguna (id INTEGER PRIMARY KEY, status TEXT NOT NULL)',
            'CREATE TABLE pengguna_peran (pengguna_id INTEGER NOT NULL, peran TEXT NOT NULL)',
            'CREATE TABLE siswa (id INTEGER PRIMARY KEY, pengguna_id INTEGER NOT NULL, angkatan_id INTEGER)',
            'CREATE TABLE penugasan_guru_gen (
                id INTEGER PRIMARY KEY AUTOINCREMENT, guru_id INTEGER NOT NULL,
                angkatan_id INTEGER NOT NULL, mapel_id INTEGER NOT NULL)',
        ] as $sql) {
            $this->db->query($sql);
        }
        // Hanya ID sintetis; tidak memuat data sekolah/akun nyata.
        foreach ([201, 202, 203] as $id) {
            $this->db->table('pengguna')->insert(['id' => $id, 'status' => 'aktif']);
            $this->db->table('pengguna_peran')
                ->insert(['pengguna_id' => $id, 'peran' => 'guru']);
        }
        $this->db->table('pengguna_peran')->insert([
            'pengguna_id' => 203, 'peran' => 'admin',
        ]);
        foreach ([
            [300, 10], [301, 20], [302, null], [303, 30], [304, 10],
        ] as [$id, $gen]) {
            $this->db->table('siswa')->insert([
                'id' => $id - 200, 'pengguna_id' => $id, 'angkatan_id' => $gen,
            ]);
        }
        $this->db->table('penugasan_guru_gen')->insertBatch([
            ['guru_id' => 201, 'angkatan_id' => 10, 'mapel_id' => 1],
            ['guru_id' => 202, 'angkatan_id' => 20, 'mapel_id' => 2],
            ['guru_id' => 203, 'angkatan_id' => 10, 'mapel_id' => 3],
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function svc(string $role='admin',int $id=101,string $status='aktif',int $ganti=0): InformasiGlobalService
    {
        return new InformasiGlobalService($this->db,static fn():array=>[
            'id'=>$id,'peran_aktif'=>$role,'status'=>$status,'wajib_ganti_sandi'=>$ganti,
        ]);
    }

    private static function isi(string $status='draf'): array
    {
        return ['judul'=>'Informasi Jadwal','isi'=>"Baca pengumuman\nresmi madrasah.",'status'=>$status];
    }

    private static function faq(string $status='draf',int $urutan=5): array
    {
        return ['pertanyaan'=>'Bagaimana membuka Bantuan?','jawaban'=>'Gunakan menu Bantuan.',
            'urutan'=>(string)$urutan,'status'=>$status];
    }

    private static function ditolak(callable $callback): void
    {
        try {
            $callback();
            self::fail('Tindakan seharusnya ditolak');
        } catch (\DomainException $e) {
            self::assertNotSame('',$e->getMessage());
        }
    }

    public function testDrafTidakBocorKePembacaTermasukPembuat(): void
    {
        $id=$this->svc('guru',201)->simpan('pengumuman',self::isi());
        self::assertGreaterThan(0,$id);
        foreach (['admin','guru','siswa','pimpinan'] as $role) {
            self::assertSame(0,$this->svc($role)->daftar('pengumuman')['jumlah']);
        }
        self::assertSame(1,$this->svc('guru',201)->daftar('pengumuman',true)['jumlah']);
        self::assertSame(0,$this->svc('guru',202)->daftar('pengumuman',true)['jumlah']);
        self::assertSame(1,$this->svc()->daftar('pengumuman',true)['jumlah']);
        self::assertSame('buat',$this->db->table('riwayat_informasi')->get()->getRowArray()['aksi']);
    }

    public function testTerbitAdminGlobalSedangkanGuruHanyaSiswaPadaGenDiampu(): void
    {
        $admin=$this->svc('admin',101)->simpan('pengumuman',self::isi('terbit'));
        $guru=$this->svc('guru',201)->simpan('pengumuman',[
            'judul'=>'Info untuk Gen 10','isi'=>'Khusus siswa Guru 201.','status'=>'terbit',
        ]);
        foreach (['admin','guru','pimpinan'] as $role) {
            $data=$this->svc($role, $role === 'guru' ? 202 : 101)
                ->daftar('pengumuman');
            self::assertSame(1,$data['jumlah']);
            self::assertSame($admin,(int)$data['data'][0]['id']);
        }
        foreach ([300,304] as $siswa) {
            $data=$this->svc('siswa',$siswa)->daftar('pengumuman');
            self::assertSame(2,$data['jumlah']);
            self::assertEqualsCanonicalizing([$admin,$guru],
                array_map('intval',array_column($data['data'],'id')));
        }
        foreach ([301,302,303] as $siswa) {
            $data=$this->svc('siswa',$siswa)->daftar('pengumuman');
            self::assertSame(1,$data['jumlah']);
            self::assertSame($admin,(int)$data['data'][0]['id']);
        }
        // Guru pembuat melihat Pengumuman sendiri di menu Kelola, bukan
        // di daftar baca global; Admin tetap boleh mengelola semuanya.
        self::assertSame(1,$this->svc('guru',201)->daftar('pengumuman',true)['jumlah']);
        self::assertSame(2,$this->svc('admin')->daftar('pengumuman',true)['jumlah']);
        self::assertSame('terbit',$this->db->table('riwayat_informasi')
            ->where('objek_id',$guru)->get()->getRowArray()['aksi']);
    }

    public function testPerubahanPenugasanGenMenyaringPengumumanGuruSaatDibaca(): void
    {
        $id=$this->svc('guru',201)->simpan('pengumuman',self::isi('terbit'));
        self::assertSame(0,$this->svc('siswa',301)->daftar('pengumuman')['jumlah']);
        $this->db->table('penugasan_guru_gen')->insert([
            'guru_id'=>201,'angkatan_id'=>20,'mapel_id'=>5,
        ]);
        self::assertSame(1,$this->svc('siswa',301)->daftar('pengumuman')['jumlah']);
        // Dua mapel pada Gen sama tidak boleh menggandakan hasil COUNT/daftar.
        $this->db->table('penugasan_guru_gen')->insert([
            'guru_id'=>201,'angkatan_id'=>20,'mapel_id'=>6,
        ]);
        self::assertSame(1,$this->svc('siswa',301)->daftar('pengumuman')['jumlah']);
        $this->db->table('penugasan_guru_gen')
            ->where('guru_id',201)->where('angkatan_id',10)->delete();
        self::assertSame(0,$this->svc('siswa',300)->daftar('pengumuman')['jumlah']);
        self::assertSame(1,$this->svc('siswa',301)->daftar('pengumuman')['jumlah']);
        self::assertSame('terbit',$this->db->table('pengumuman')->where('id',$id)
            ->get()->getRowArray()['status']);
    }

    public function testAsalPeranTidakBerubahSetelahAdminMenyuntingGuruAtauAkunRangkap(): void
    {
        $id=$this->svc('guru',203)->simpan('pengumuman',self::isi('terbit'));
        $this->svc('admin',101)->simpan('pengumuman',[
            'judul'=>'Disunting Admin','isi'=>'Isi baru','status'=>'terbit','versi'=>'1',
        ],$id);
        self::assertSame(1,$this->svc('siswa',300)->daftar('pengumuman')['jumlah']);
        self::assertSame(0,$this->svc('siswa',301)->daftar('pengumuman')['jumlah']);
        self::assertSame(0,$this->svc('pimpinan')->daftar('pengumuman')['jumlah']);
        self::assertSame(0,$this->svc('admin')->daftar('pengumuman')['jumlah']);

        // Pengguna 203 memiliki Admin+Guru; saat membuat sebagai Admin,
        // Pengumuman berikutnya global, bukan salah dianggap milik Guru.
        $global=$this->svc('admin',203)->simpan('pengumuman',[
            'judul'=>'Resmi Global','isi'=>'Pengumuman Madrasah','status'=>'terbit',
        ]);
        self::assertSame(1,$this->svc('pimpinan')->daftar('pengumuman')['jumlah']);
        self::assertSame($global,(int)$this->svc('pimpinan')
            ->daftar('pengumuman')['data'][0]['id']);
        self::assertSame(1,$this->svc('siswa',301)->daftar('pengumuman')['jumlah']);
        self::assertSame(2,$this->svc('siswa',300)->daftar('pengumuman')['jumlah']);
    }

    public function testDaftarDanHitunganPaginasiMenggunakanHakPenerimaSama(): void
    {
        $this->svc('admin')->simpan('pengumuman',[
            'judul'=>'Global A','isi'=>'A','status'=>'terbit',
        ]);
        $this->svc('guru',201)->simpan('pengumuman',[
            'judul'=>'Gen 10','isi'=>'B','status'=>'terbit',
        ]);
        $this->svc('guru',202)->simpan('pengumuman',[
            'judul'=>'Gen 20','isi'=>'C','status'=>'terbit',
        ]);
        $this->svc('admin')->simpan('pengumuman',[
            'judul'=>'Global B','isi'=>'D','status'=>'terbit',
        ]);
        $satu=$this->svc('siswa',300)->daftar('pengumuman',false,1,2);
        $dua=$this->svc('siswa',300)->daftar('pengumuman',false,2,2);
        self::assertSame(3,$satu['jumlah']);
        self::assertSame(2,$satu['total_halaman']);
        self::assertCount(2,$satu['data']);
        self::assertCount(1,$dua['data']);
        self::assertNotContains('Gen 20',
            array_merge(array_column($satu['data'],'judul'),array_column($dua['data'],'judul')));
        self::assertSame(3,$this->svc('siswa',301)->daftar('pengumuman')['jumlah']);
        self::assertSame(2,$this->svc('pimpinan')->daftar('pengumuman')['jumlah']);
        self::assertSame(2,$this->svc('guru',201)->daftar('pengumuman')['jumlah']);
    }

    public function testGuruNonaktifAtauPerannyaDicabutTidakLagiMenyasarSiswa(): void
    {
        $guru=$this->svc('guru',201)->simpan('pengumuman',self::isi('terbit'));
        $admin=$this->svc('admin')->simpan('pengumuman',[
            'judul'=>'Informasi Admin','isi'=>'Tetap global','status'=>'terbit',
        ]);
        self::assertSame(2,$this->svc('siswa',300)->daftar('pengumuman')['jumlah']);
        $this->db->table('pengguna')->where('id',201)->update(['status'=>'nonaktif']);
        self::assertSame(1,$this->svc('siswa',300)->daftar('pengumuman')['jumlah']);
        $this->db->table('pengguna')->where('id',201)->update(['status'=>'aktif']);
        $this->db->table('pengguna_peran')->where('pengguna_id',201)
            ->where('peran','guru')->delete();
        $hasil=$this->svc('siswa',300)->daftar('pengumuman');
        self::assertSame(1,$hasil['jumlah']);
        self::assertSame($admin,(int)$hasil['data'][0]['id']);
        self::assertSame(2,$this->db->table('pengumuman')->countAllResults());
        self::assertSame('terbit',$this->db->table('pengumuman')->where('id',$guru)
            ->get()->getRowArray()['status']);
    }

    public function testRiwayatPembuatanYangHilangAtauTidakSahDisembunyikan(): void
    {
        $id=$this->svc('guru',201)->simpan('pengumuman',self::isi('terbit'));
        $this->db->table('riwayat_informasi')
            ->where('jenis','pengumuman')->where('objek_id',$id)->delete();
        foreach (['admin','guru','siswa','pimpinan'] as $role) {
            self::assertSame(0,$this->svc($role,$role==='siswa'?300:101)
                ->daftar('pengumuman')['jumlah']);
        }
        // Panel Kelola tetap memuat row historis; bukan menghapus isinya.
        self::assertSame(1,$this->svc('guru',201)->daftar('pengumuman',true)['jumlah']);
    }

    public function testGuruTidakMengeditMilikAdminAtauGuruLain(): void
    {
        $id=$this->svc()->simpan('pengumuman',self::isi());
        self::ditolak(fn()=> $this->svc('guru',201)->rincianKelola('pengumuman',$id));
        self::ditolak(fn()=> $this->svc('guru',201)->simpan('pengumuman',
            self::isi('terbit')+['versi'=>'1'],$id));
        self::ditolak(fn()=> $this->svc('guru',201)->arsipkan('pengumuman',$id,1));
        self::assertSame('draf',$this->db->table('pengumuman')->where('id',$id)->get()->getRowArray()['status']);
        self::assertSame(1,$this->db->table('riwayat_informasi')->countAllResults());
    }

    public function testAdminDapatMenyuntingPengumumanGuruDanArsipMempertahankanHistori(): void
    {
        $id=$this->svc('guru',201)->simpan('pengumuman',self::isi());
        $this->svc()->simpan('pengumuman',[
            'judul'=>'Jadwal Direvisi','isi'=>'Pembelajaran Senin.',
            'status'=>'terbit','versi'=>'1'],$id);
        self::assertSame(1,$this->svc('siswa',300)->daftar('pengumuman')['jumlah']);
        $this->svc()->arsipkan('pengumuman',$id,2);
        self::assertSame(0,$this->svc('siswa',300)->daftar('pengumuman')['jumlah']);
        self::assertSame(1,$this->db->table('pengumuman')->countAllResults());
        self::assertSame(3,$this->db->table('riwayat_informasi')->countAllResults());
        self::assertSame(3,$this->db->table('log_aktivitas')->countAllResults());
        self::assertSame(['buat','terbit','arsip'],
            array_column($this->db->table('riwayat_informasi')->orderBy('id')->get()->getResultArray(),'aksi'));
        self::ditolak(fn()=> $this->svc()->simpan('pengumuman',[
            'judul'=>'Kembali','isi'=>'Dilarang','status'=>'terbit','versi'=>'3'],$id));
        self::ditolak(fn()=> $this->svc()->rincianKelola('pengumuman',$id));
    }

    public function testTerbitTidakBolehTurunKeDrafSelainMelaluiArsip(): void
    {
        $id=$this->svc()->simpan('pengumuman',self::isi('terbit'));
        self::ditolak(fn()=> $this->svc()->simpan('pengumuman',[
            'judul'=>'Sembunyikan','isi'=>'Teks yang sama','status'=>'draf','versi'=>'1'],$id));
        self::assertSame('terbit',$this->db->table('pengumuman')->where('id',$id)->get()->getRowArray()['status']);
        self::assertSame(1,$this->db->table('riwayat_informasi')->countAllResults());
    }

    public function testPerubahanBersamaanDitolakDenganVersi(): void
    {
        $id=$this->svc()->simpan('pengumuman',self::isi());
        $this->svc()->simpan('pengumuman',[
            'judul'=>'Baru','isi'=>'Isi baru','status'=>'draf','versi'=>'1'],$id);
        self::ditolak(fn()=> $this->svc()->simpan('pengumuman',[
            'judul'=>'Timpa','isi'=>'Usang','status'=>'terbit','versi'=>'1'],$id));
        self::assertSame('Baru',$this->db->table('pengumuman')->where('id',$id)->get()->getRowArray()['judul']);
        self::assertSame(2,$this->db->table('riwayat_informasi')->countAllResults());
    }

    public function testSimpanIdentikTidakMenambahVersiAtauAudit(): void
    {
        $id=$this->svc()->simpan('pengumuman',self::isi());
        self::ditolak(fn()=> $this->svc()->simpan('pengumuman',self::isi()+['versi'=>'1'],$id));
        self::assertSame('1',(string)$this->db->table('pengumuman')->where('id',$id)->get()->getRowArray()['versi']);
        self::assertSame(1,$this->db->table('riwayat_informasi')->countAllResults());
    }

    public function testFaqHanyaAdminBolehKelolaDenganUrutanDanDraf(): void
    {
        foreach (['guru','siswa','pimpinan'] as $role) {
            self::ditolak(fn()=> $this->svc($role)->simpan('faq',self::faq('terbit')));
            self::ditolak(fn()=> $this->svc($role)->daftar('faq',true));
        }
        $this->svc()->simpan('faq',self::faq('terbit',10));
        $this->svc()->simpan('faq',[
            'pertanyaan'=>'FAQ awal','jawaban'=>'Jawaban awal','urutan'=>'1','status'=>'terbit',
        ]);
        $draf=$this->svc()->simpan('faq',self::faq('draf',0));
        foreach (['admin','guru','siswa','pimpinan'] as $role) {
            $d=$this->svc($role)->daftar('faq');
            self::assertSame(2,$d['jumlah']);
            self::assertSame('FAQ awal',$d['data'][0]['pertanyaan']);
        }
        self::assertSame(3,$this->svc()->daftar('faq',true)['jumlah']);
        $this->svc()->arsipkan('faq',$draf,1);
        self::assertSame(2,$this->svc('siswa')->daftar('faq')['jumlah']);
    }

    public function testPaginasiFaqTidakMengulangAtauMenampilkanDraf(): void
    {
        for($i=1;$i<=5;$i++) {
            $this->svc()->simpan('faq',[
                'pertanyaan'=>'FAQ '.$i,'jawaban'=>'Jawaban '.$i,
                'urutan'=>(string)$i,'status'=>$i===3?'draf':'terbit',
            ]);
        }
        $a=$this->svc('siswa')->daftar('faq',false,1,2);
        $b=$this->svc('siswa')->daftar('faq',false,2,2);
        self::assertSame(4,$a['jumlah']);
        self::assertSame(2,$a['total_halaman']);
        self::assertSame(['FAQ 1','FAQ 2'],array_column($a['data'],'pertanyaan'));
        self::assertSame(['FAQ 4','FAQ 5'],array_column($b['data'],'pertanyaan'));
        self::assertSame(2,$this->svc('siswa')->daftar('faq',false,999,2)['halaman']);
    }

    public function testAkunNonaktifSandiSementaraDanPeranLainDitolak(): void
    {
        self::ditolak(fn()=> $this->svc('siswa',300,'nonaktif')->daftar('pengumuman'));
        self::ditolak(fn()=> $this->svc('guru',300,'aktif',1)->daftar('pengumuman'));
        self::ditolak(fn()=> $this->svc('unknown')->daftar('pengumuman'));
        self::ditolak(fn()=> $this->svc('pimpinan')->simpan('pengumuman',self::isi()));
        self::assertSame(0,$this->db->table('pengumuman')->countAllResults());
    }

    public function testValidasiPanjangStatusDanUrutan(): void
    {
        self::ditolak(fn()=> InformasiGlobalService::validasi('faq',self::faq('terbit',65536)));
        self::ditolak(fn()=> InformasiGlobalService::validasi('faq',[
            'pertanyaan'=>'Teks','jawaban'=>'','urutan'=>'1','status'=>'terbit']));
        self::ditolak(fn()=> InformasiGlobalService::validasi('pengumuman',[
            'judul'=>str_repeat('x',151),'isi'=>'valid','status'=>'terbit']));
        self::ditolak(fn()=> InformasiGlobalService::validasi('pengumuman',[
            'judul'=>'Valid','isi'=>str_repeat('x',5001),'status'=>'terbit']));
        self::ditolak(fn()=> InformasiGlobalService::validasi('pengumuman',self::isi('arsip')));
        self::ditolak(fn()=> InformasiGlobalService::validasi('invalid',[]));
        self::assertSame('Valid',InformasiGlobalService::validasi('pengumuman',[
            'judul'=>' Valid ','isi'=>' Teks ','status'=>'terbit'])['judul']);
    }

    public function testViewCsrfEscapeAuthDanVersi(): void
    {
        $base=dirname(__DIR__,2).'/';
        $editor=file_get_contents($base.'app/Views/informasi/editor.php');
        $list=file_get_contents($base.'app/Views/informasi/kelola.php');
        $public=file_get_contents($base.'app/Views/informasi/pengumuman.php');
        $bantuan=file_get_contents($base.'app/Views/bantuan/index.php');
        $routes=file_get_contents($base.'app/Config/Routes.php');
        self::assertIsString($editor);
        self::assertStringContainsString('csrf_field()',$editor);
        // Regresi 404 XAMPP: form BARU wajib POST /tambah, bukan GET daftar.
        // Satu editor dipakai oleh FAQ Admin, Pengumuman Admin dan Guru.
        self::assertStringContainsString(
            "\$action = \$prefix . (\$baru ? '/tambah'", $editor
        );
        self::assertStringContainsString("site_url(\$action)", $editor);
        self::assertStringNotContainsString(
            "site_url(\$prefix.(\$baru?'':'/'", $editor
        );
        foreach ([
            "admin/faq/tambah" => "Informasi::adminSimpanFaq",
            "admin/pengumuman/tambah" => "Informasi::adminSimpanPengumuman",
            "guru/pengumuman/tambah" => "Informasi::guruSimpanPengumuman",
        ] as $path => $handler) {
            self::assertStringContainsString(
                "\$routes->post('".$path."', '".$handler."'", $routes
            );
        }
        // Edit dan arsip punya POST eksplisit, bukan auto-route.
        foreach (['admin/faq','admin/pengumuman','guru/pengumuman'] as $path) {
            self::assertStringContainsString(
                "\$routes->post('".$path."/(:num)/sunting'", $routes
            );
            self::assertStringContainsString(
                "\$routes->post('".$path."/(:num)/arsip'", $routes
            );
        }

        self::assertStringContainsString("esc(old(\$kunciIsi",$editor);
        self::assertStringContainsString('name="versi"',$editor);
        self::assertStringContainsString('csrf_field()',$list);
        self::assertStringContainsString("esc(\$item['isi'])",$public);
        self::assertStringContainsString("esc(\$item['jawaban'])",$bantuan);
        self::assertStringContainsString("'filter' => 'auth:admin'",$routes);
        self::assertStringContainsString("'filter' => 'auth:guru'",$routes);
        self::assertStringContainsString("'Informasi::pengumuman', ['filter' => 'auth']",$routes);
    }

    public function testPatchSQLAdditiveTanpaDataPengguna(): void
    {
        $sql=file_get_contents(dirname(__DIR__,2).'/database/sql/updates/V4-0012_PENGUMUMAN_FAQ.sql');
        self::assertIsString($sql);
        foreach (['pengumuman','faq','riwayat_informasi','V4-0012-PENGUMUMAN-FAQ',
            'REFERENCES'] as $term) {
            self::assertStringContainsString($term,$sql);
        }
        foreach (['TRUNCATE TABLE','DELETE FROM pengguna','INSERT INTO pengguna'] as $larang) {
            self::assertStringNotContainsString($larang,$sql);
        }
    }
}
