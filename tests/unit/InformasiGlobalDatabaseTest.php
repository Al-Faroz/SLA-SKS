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
        ] as $sql) {
            $this->db->query($sql);
        }
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

    public function testTerbitBerlakuGlobalSemuaPeranTanpaSegmentasi(): void
    {
        $id=$this->svc('guru',201)->simpan('pengumuman',self::isi('terbit'));
        foreach (['admin','guru','siswa','pimpinan'] as $role) {
            $data=$this->svc($role)->daftar('pengumuman');
            self::assertSame(1,$data['jumlah']);
            self::assertSame($id,(int)$data['data'][0]['id']);
            self::assertNotNull($data['data'][0]['diterbitkan_pada']);
        }
        self::assertSame('terbit',$this->db->table('riwayat_informasi')->get()->getRowArray()['aksi']);
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
