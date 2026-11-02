<?php

declare(strict_types=1);

use App\Services\EksporNilaiGuruService;
use CodeIgniter\Database\BaseConnection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

/** QA A7: buka file XLSX nyata dengan PhpSpreadsheet, tanpa data siswa riil. */
final class EksporNilaiGuruXlsxTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN'=>'','database'=>':memory:','DBDriver'=>'SQLite3',
            'DBPrefix'=>'','DBDebug'=>true,'pConnect'=>false,
            'foreignKeys'=>false,'busyTimeout'=>1000,'synchronous'=>null,'failover'=>[],
        ], false);
        foreach ([
            'CREATE TABLE pengguna(id INTEGER PRIMARY KEY,nama TEXT)',
            'CREATE TABLE ref_angkatan(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT,semester_mulai INTEGER)',
            'CREATE TABLE ref_mapel(id INTEGER PRIMARY KEY,kode TEXT,nama TEXT)',
            'CREATE TABLE penugasan_guru_gen(id INTEGER PRIMARY KEY,guru_id INTEGER,angkatan_id INTEGER,mapel_id INTEGER)',
            'CREATE TABLE siswa(id INTEGER PRIMARY KEY,pengguna_id INTEGER,angkatan_id INTEGER)',
            'CREATE TABLE paket_semester(id INTEGER PRIMARY KEY,angkatan_id INTEGER,mapel_id INTEGER,semester INTEGER)',
            'CREATE TABLE bab(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,nomor_bab INTEGER,nama_bab TEXT)',
            'CREATE TABLE tp(id INTEGER PRIMARY KEY,paket_semester_id INTEGER,bab_id INTEGER,kode_tp TEXT,judul TEXT,nama_tp TEXT,jenis_tp TEXT,status TEXT,urutan_dalam_bab INTEGER)',
            'CREATE TABLE progres_tp(id INTEGER PRIMARY KEY,siswa_id INTEGER,tp_id INTEGER,paket_semester_id INTEGER,status TEXT)',
            'CREATE TABLE nilai_tp(id INTEGER PRIMARY KEY,progres_tp_id INTEGER UNIQUE,jalur TEXT,nilai_resume REAL,nilai_hots REAL,nilai_sumatif REAL,nilai_tp REAL,status_nilai TEXT)',
        ] as $ddl) $this->db->query($ddl);
        $this->db->table('ref_angkatan')->insertBatch([
            ['id'=>1,'kode'=>'GA','nama'=>'Gen A','semester_mulai'=>1],
            ['id'=>2,'kode'=>'GB','nama'=>'Gen B','semester_mulai'=>1],
        ]);
        $this->db->table('ref_mapel')->insert(['id'=>7,'kode'=>'SKI','nama'=>'SKI']);
        $this->db->table('penugasan_guru_gen')->insertBatch([
            ['id'=>1,'guru_id'=>4,'angkatan_id'=>1,'mapel_id'=>7],
            ['id'=>2,'guru_id'=>5,'angkatan_id'=>2,'mapel_id'=>7],
        ]);
        $this->db->table('pengguna')->insertBatch([
            ['id'=>101,'nama'=>'=HYPERLINK("https://bad.test","x")'],
            ['id'=>102,'nama'=>'+RUMUS BERBAHAYA'],
            ['id'=>201,'nama'=>'GEN B PRIVATE'],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id'=>11,'pengguna_id'=>101,'angkatan_id'=>1],
            ['id'=>12,'pengguna_id'=>102,'angkatan_id'=>1],
            ['id'=>21,'pengguna_id'=>201,'angkatan_id'=>2],
        ]);
        $this->db->table('paket_semester')->insertBatch([
            ['id'=>31,'angkatan_id'=>1,'mapel_id'=>7,'semester'=>1],
            ['id'=>41,'angkatan_id'=>2,'mapel_id'=>7,'semester'=>1],
        ]);
        $this->db->table('bab')->insertBatch([
            ['id'=>51,'paket_semester_id'=>31,'nomor_bab'=>1,'nama_bab'=>'Bab A'],
            ['id'=>61,'paket_semester_id'=>41,'nomor_bab'=>1,'nama_bab'=>'Bab B'],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id'=>1,'paket_semester_id'=>31,'bab_id'=>51,
                'kode_tp'=>'SKI.7.1.1','judul'=>'=2+2','nama_tp'=>'=2+2',
                'jenis_tp'=>'non_esensial','status'=>'siap','urutan_dalam_bab'=>1],
            ['id'=>2,'paket_semester_id'=>31,'bab_id'=>51,
                'kode_tp'=>'SKI.7.1.2','judul'=>'TP kedua','nama_tp'=>'TP kedua',
                'jenis_tp'=>'non_esensial','status'=>'siap','urutan_dalam_bab'=>2],
            ['id'=>3,'paket_semester_id'=>31,'bab_id'=>51,
                'kode_tp'=>'SKI.7.1.3','judul'=>'TP Esensial','nama_tp'=>'TP Esensial',
                'jenis_tp'=>'esensial','status'=>'siap','urutan_dalam_bab'=>3],
            ['id'=>4,'paket_semester_id'=>41,'bab_id'=>61,
                'kode_tp'=>'SKI.7.1.1','judul'=>'GEN B PRIVATE','nama_tp'=>'GEN B PRIVATE',
                'jenis_tp'=>'non_esensial','status'=>'siap','urutan_dalam_bab'=>1],
        ]);
        $this->db->table('progres_tp')->insertBatch([
            ['id'=>501,'siswa_id'=>11,'tp_id'=>1,'paket_semester_id'=>31,'status'=>'tuntas'],
            ['id'=>502,'siswa_id'=>11,'tp_id'=>2,'paket_semester_id'=>31,'status'=>'tuntas'],
        ]);
        $this->db->table('nilai_tp')->insertBatch([
            ['id'=>1001,'progres_tp_id'=>501,'jalur'=>'lulus_diagnostik',
                'nilai_resume'=>null,'nilai_hots'=>null,
                'nilai_sumatif'=>0.0,'nilai_tp'=>0.0,'status_nilai'=>'final'],
            ['id'=>1002,'progres_tp_id'=>502,'jalur'=>'belajar_wajib',
                'nilai_resume'=>80.0,'nilai_hots'=>null,
                'nilai_sumatif'=>70.0,'nilai_tp'=>null,'status_nilai'=>'sementara'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function svc(int $id = 4, string $role = 'guru'): EksporNilaiGuruService
    {
        return new EksporNilaiGuruService($this->db, static fn (): array => [
            'id'=>$id,'peran_aktif'=>$role,'status'=>'aktif','wajib_ganti_sandi'=>0,
        ]);
    }

    private function pilih(int $gen = 1): array
    {
        return ['angkatan_id'=>$gen,'mapel_id'=>7,
            'kode_gen'=>$gen === 1 ? 'GA':'GB',
            'kode_mapel'=>'SKI',
            'nama_gen'=>'Gen '.$gen,
            'nama_mapel'=>'SKI'];
    }

    /** @return array{0:Spreadsheet,1:string} */
    private function baca(array $hasil): array
    {
        self::assertStringEndsWith('.xlsx',$hasil['nama']);
        self::assertSame('PK',substr($hasil['isi'],0,2));
        $path = tempnam(sys_get_temp_dir(),'test_nilai_');
        self::assertIsString($path);
        try {
            file_put_contents($path,$hasil['isi']);
            return [IOFactory::load($path),$hasil['isi']];
        } finally {
            @unlink($path);
        }
    }

    public function testXlsxSemuaSiswaNilaiParsialNolEsensialDanKeamananFormula(): void
    {
        [$book,$raw] = $this->baca($this->svc()->unduh($this->pilih(1),1));
        self::assertSame(['INFORMASI','DAFTAR SISWA','SEMESTER 1'],
            $book->getSheetNames());
        $siswa=$book->getSheetByName('DAFTAR SISWA');
        self::assertSame(2,$siswa->getHighestDataRow()-4);
        self::assertSame('=HYPERLINK("https://bad.test","x")',
            $siswa->getCell('B6')->getValue());
        self::assertSame(DataType::TYPE_STRING,
            $siswa->getCell('B6')->getDataType());
        $sheet=$book->getSheetByName('SEMESTER 1');
        self::assertSame(10,$sheet->getHighestDataRow()); // 2 siswa × 3 TP
        $barisNilaiNol = null;
        $barisSementara = null;
        $barisEsensial = null;
        $barisBelum = null;
        for ($row = 5; $row <= 10; $row++) {
            $nama = $sheet->getCell('B'.$row)->getValue();
            $kode = $sheet->getCell('F'.$row)->getValue();
            self::assertNotSame('GEN B PRIVATE', $nama);
            if ($nama === '=HYPERLINK("https://bad.test","x")') {
                self::assertSame(DataType::TYPE_STRING,
                    $sheet->getCell('B'.$row)->getDataType());
                if ($kode === 'SKI.7.1.1') $barisNilaiNol = $row;
                if ($kode === 'SKI.7.1.2') $barisSementara = $row;
                if ($kode === 'SKI.7.1.3') $barisEsensial = $row;
            } elseif ($kode === 'SKI.7.1.1') {
                $barisBelum = $row;
            }
        }
        foreach ([$barisNilaiNol,$barisSementara,$barisEsensial,$barisBelum] as $number) {
            self::assertIsInt($number);
        }
        self::assertEquals(0,$sheet->getCell('N'.$barisNilaiNol)->getValue());
        self::assertSame(DataType::TYPE_NUMERIC,
            $sheet->getCell('N'.$barisNilaiNol)->getDataType());
        self::assertSame('Dilewati',$sheet->getCell('K'.$barisNilaiNol)->getValue());
        self::assertSame('Final',$sheet->getCell('O'.$barisNilaiNol)->getValue());
        self::assertSame('=2+2',$sheet->getCell('G'.$barisNilaiNol)->getValue());
        self::assertSame(DataType::TYPE_STRING,
            $sheet->getCell('G'.$barisNilaiNol)->getDataType());
        self::assertNull($sheet->getCell('N'.$barisSementara)->getValue());
        self::assertSame('Sementara',$sheet->getCell('O'.$barisSementara)->getValue());
        self::assertSame('—',$sheet->getCell('N'.$barisEsensial)->getValue());
        self::assertSame('Tanpa nilai (Esensial)',
            $sheet->getCell('O'.$barisEsensial)->getValue());
        self::assertNull($sheet->getCell('N'.$barisBelum)->getValue());
        self::assertStringNotContainsString('GEN B PRIVATE',
            $book->getSheetByName('INFORMASI')->getCell('B3')->getValue());
        $book->disconnectWorksheets();
    }

    public function testSemuaSemesterEnamSheetDanSiswaAdaWalauSemesterKosong(): void
    {
        [$book] = $this->baca($this->svc()->unduh($this->pilih(),0));
        self::assertCount(8,$book->getSheetNames());
        for ($semester = 1; $semester <= 6; $semester++) {
            self::assertContains('SEMESTER '.$semester,$book->getSheetNames());
        }
        self::assertSame(4,$book->getSheetByName('SEMESTER 6')->getHighestDataRow());
        self::assertSame(6,$book->getSheetByName('DAFTAR SISWA')->getHighestDataRow());
        self::assertSame(2,$book->getSheetByName('INFORMASI')->getCell('B16')->getValue());
        $book->disconnectWorksheets();
    }

    public function testDilarangEksporGenAsingDanRoleTidakAktif(): void
    {
        try {
            $this->svc()->unduh($this->pilih(2),1);
            self::fail('Guru Gen A tidak boleh ekspor Gen B.');
        } catch (\DomainException $e) {
            self::assertStringContainsString('Gen–Mapel',$e->getMessage());
        }
        $this->expectException(\DomainException::class);
        $this->svc(4,'admin')->unduh($this->pilih(),1);
    }

    public function testTolakSemesterDiLuarRentang(): void
    {
        $this->expectException(\DomainException::class);
        $this->svc()->unduh($this->pilih(),7);
    }

    public function testEksporMelampauiPaginasiRekap25Baris(): void
    {
        $users = [];
        $students = [];
        for ($i=1;$i<=10;$i++) {
            $users[] = ['id'=>300+$i,'nama'=>'Siswa Tambahan '.$i];
            $students[] = ['id'=>400+$i,'pengguna_id'=>300+$i,'angkatan_id'=>1];
        }
        $this->db->table('pengguna')->insertBatch($users);
        $this->db->table('siswa')->insertBatch($students);
        [$book] = $this->baca($this->svc()->unduh($this->pilih(),1));
        self::assertSame(36,$book->getSheetByName('SEMESTER 1')->getHighestDataRow()-4);
        self::assertSame(12,$book->getSheetByName('DAFTAR SISWA')->getHighestDataRow()-4);
        $book->disconnectWorksheets();
    }

    public function testJalurUnduhTidakMengubahDatabase(): void
    {
        $sebelum=$this->db->table('nilai_tp')->get()->getResultArray();
        $this->svc()->unduh($this->pilih(),1);
        self::assertSame($sebelum,$this->db->table('nilai_tp')->get()->getResultArray());
        self::assertSame(2,$this->db->table('nilai_tp')->countAllResults());
    }
}
