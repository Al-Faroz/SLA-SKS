<?php

declare(strict_types=1);

use App\Services\InformasiGlobalService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/**
 * SQL nyata A13-5 pada MariaDB EPHEMERAL CI saja (skema/data anonim).
 * Tidak menjalankan query destructive pada database XAMPP.
 */
final class PengumumanGuruGenMariaDbTest extends TestCase
{
    private static BaseConnection $db;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $dsn = getenv('SLA_TEST_DB_DSN');
        $user = getenv('SLA_TEST_DB_USER');
        $pass = getenv('SLA_TEST_DB_PASS');
        if (!is_string($dsn) || !is_string($user) || !is_string($pass)
            || !str_contains($dsn, 'dbname=sla_a13_gen_test')) {
            throw new RuntimeException('Uji hanya boleh pada DB MariaDB sintetis sla_a13_gen_test.');
        }
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        foreach ([
            "CREATE TABLE pengguna (id BIGINT UNSIGNED PRIMARY KEY, status VARCHAR(20) NOT NULL)",
            "CREATE TABLE pengguna_peran (pengguna_id BIGINT UNSIGNED NOT NULL,
                peran VARCHAR(20) NOT NULL, KEY (pengguna_id,peran))",
            "CREATE TABLE siswa (id BIGINT UNSIGNED PRIMARY KEY,
                pengguna_id BIGINT UNSIGNED NOT NULL, angkatan_id BIGINT UNSIGNED NULL,
                UNIQUE KEY (pengguna_id), KEY (angkatan_id))",
            "CREATE TABLE penugasan_guru_gen (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                guru_id BIGINT UNSIGNED NOT NULL, angkatan_id BIGINT UNSIGNED NOT NULL,
                mapel_id BIGINT UNSIGNED NOT NULL, KEY (guru_id,angkatan_id))",
            "CREATE TABLE pengumuman (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                judul VARCHAR(150) NOT NULL, isi TEXT NOT NULL,
                status ENUM('draf','terbit','arsip') NOT NULL,
                dibuat_oleh BIGINT UNSIGNED NOT NULL, diubah_oleh BIGINT UNSIGNED NOT NULL,
                versi INT UNSIGNED NOT NULL, dibuat_pada DATETIME NOT NULL,
                diubah_pada DATETIME NOT NULL, diterbitkan_pada DATETIME NULL,
                diarsipkan_pada DATETIME NULL,
                KEY (status,diterbitkan_pada,id), KEY (dibuat_oleh,id))",
            "CREATE TABLE riwayat_informasi (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                jenis ENUM('pengumuman','faq') NOT NULL,
                objek_id BIGINT UNSIGNED NOT NULL, aksi ENUM('buat','sunting','terbit','arsip') NOT NULL,
                pengguna_id BIGINT UNSIGNED NOT NULL,
                peran_aktif ENUM('admin','guru') NOT NULL,
                sebelum_json LONGTEXT NULL, sesudah_json LONGTEXT NOT NULL,
                terjadi_pada DATETIME NOT NULL, KEY (jenis,objek_id,id))",
        ] as $sql) {
            $pdo->exec($sql);
        }
        $pdo->exec("INSERT INTO pengguna (id,status) VALUES
            (101,'aktif'),(201,'aktif'),(202,'aktif'),(203,'aktif')");
        $pdo->exec("INSERT INTO pengguna_peran (pengguna_id,peran) VALUES
            (101,'admin'),(201,'guru'),(202,'guru'),(203,'admin'),(203,'guru')");
        $pdo->exec("INSERT INTO siswa (id,pengguna_id,angkatan_id) VALUES
            (1,300,10),(2,301,20),(3,302,NULL)");
        $pdo->exec("INSERT INTO penugasan_guru_gen (guru_id,angkatan_id,mapel_id) VALUES
            (201,10,1),(201,10,2),(202,20,3),(203,10,4)");
        $pdo->exec("INSERT INTO pengumuman
            (judul,isi,status,dibuat_oleh,diubah_oleh,versi,dibuat_pada,diubah_pada,diterbitkan_pada)
            VALUES
            ('Global','Admin','terbit',101,101,1,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP()),
            ('Gen10','Guru 201','terbit',201,201,1,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP()),
            ('Gen20','Guru 202','terbit',202,202,1,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP()),
            ('Rangkap guru','Guru 203','terbit',203,101,2,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP()),
            ('Rangkap admin','Admin 203','terbit',203,203,1,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())");
        $pdo->exec("INSERT INTO riwayat_informasi
            (jenis,objek_id,aksi,pengguna_id,peran_aktif,sebelum_json,sesudah_json,terjadi_pada)
            VALUES
            ('pengumuman',1,'terbit',101,'admin',NULL,'{}',UTC_TIMESTAMP()),
            ('pengumuman',2,'terbit',201,'guru',NULL,'{}',UTC_TIMESTAMP()),
            ('pengumuman',3,'buat',202,'guru',NULL,'{}',UTC_TIMESTAMP()),
            ('pengumuman',3,'terbit',202,'guru','{}','{}',UTC_TIMESTAMP()),
            ('pengumuman',4,'terbit',203,'guru',NULL,'{}',UTC_TIMESTAMP()),
            ('pengumuman',4,'sunting',101,'admin','{}','{}',UTC_TIMESTAMP()),
            ('pengumuman',5,'terbit',203,'admin',NULL,'{}',UTC_TIMESTAMP())");

        self::$db = \Config\Database::connect([
            'DSN'=>'', 'hostname'=>'127.0.0.1', 'username'=>$user,
            'password'=>$pass, 'database'=>'sla_a13_gen_test',
            'DBDriver'=>'MySQLi', 'DBPrefix'=>'', 'pConnect'=>false,
            'DBDebug'=>true, 'charset'=>'utf8mb4',
            'DBCollat'=>'utf8mb4_general_ci', 'port'=>3306, 'failover'=>[],
        ], false);
    }

    private static function daftar(string $role, int $id): array
    {
        $svc = new InformasiGlobalService(self::$db, static fn(): array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
        return $svc->daftar('pengumuman',false,1,20);
    }

    public function testScopeMariaDbSesuaiPenugasanGenDanPeranPembuat(): void
    {
        self::assertEqualsCanonicalizing(['Global','Rangkap admin'],
            array_column(self::daftar('pimpinan',888)['data'],'judul'));
        $g10=self::daftar('siswa',300);
        self::assertSame(4,$g10['jumlah']);
        self::assertEqualsCanonicalizing(
            ['Global','Gen10','Rangkap guru','Rangkap admin'],
            array_column($g10['data'],'judul'));
        $g20=self::daftar('siswa',301);
        self::assertSame(3,$g20['jumlah']);
        self::assertEqualsCanonicalizing(['Global','Gen20','Rangkap admin'],
            array_column($g20['data'],'judul'));
        self::assertSame(2,self::daftar('siswa',302)['jumlah']);
        self::assertSame(2,self::daftar('guru',202)['jumlah']);
        self::assertSame(2,self::daftar('admin',101)['jumlah']);
    }

    public function testCakupanDinamisKetikaGuruTakLagiDitugaskan(): void
    {
        self::$db->table('penugasan_guru_gen')->where('guru_id',201)->delete();
        $data=self::daftar('siswa',300);
        self::assertSame(3,$data['jumlah']);
        self::assertNotContains('Gen10',array_column($data['data'],'judul'));
        self::assertSame(5,self::$db->table('pengumuman')->countAllResults());
    }
}
