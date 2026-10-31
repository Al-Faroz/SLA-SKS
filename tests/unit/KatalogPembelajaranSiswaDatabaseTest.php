<?php

declare(strict_types=1);

use App\Services\KatalogPembelajaranSiswaService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

/**
 * Integrasi SQLite in-memory, tanpa Migration/Seeder dan tanpa database siswa nyata.
 * Verifikasi otorisasi Gen terhadap query SQL, bukan sekadar pemeriksaan string.
 */
final class KatalogPembelajaranSiswaDatabaseTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN' => '', 'database' => ':memory:', 'DBDriver' => 'SQLite3',
            'DBPrefix' => '', 'DBDebug' => true, 'pConnect' => false,
            'foreignKeys' => false, 'busyTimeout' => 1000, 'synchronous' => null,
            'failover' => [],
        ], false);
        $sql = [
            'CREATE TABLE ref_angkatan (id INTEGER PRIMARY KEY, kode TEXT, nama TEXT, semester_mulai INT)',
            'CREATE TABLE siswa (id INTEGER PRIMARY KEY, pengguna_id INT, angkatan_id INT, semester_mulai INT)',
            'CREATE TABLE ref_mapel (id INTEGER PRIMARY KEY, kode TEXT, nama TEXT)',
            'CREATE TABLE paket_semester (id INTEGER PRIMARY KEY, angkatan_id INT, mapel_id INT, semester INT, tahun_ajaran_id INT, nomor_versi INT, status TEXT)',
            'CREATE TABLE penugasan_paket (id INTEGER PRIMARY KEY, angkatan_id INT, mapel_id INT, semester INT, tahun_ajaran_id INT, paket_id INT)',
            'CREATE TABLE tp (id INTEGER PRIMARY KEY, paket_semester_id INT, kode_tp TEXT, judul TEXT, urutan_atp INT, status TEXT, jenis_tp TEXT, rumusan TEXT, indikator TEXT)',
            'CREATE TABLE progres_tp (id INTEGER PRIMARY KEY, siswa_id INT, tp_id INT, paket_semester_id INT, status TEXT)',
            'CREATE TABLE versi_konten_tp (id INTEGER PRIMARY KEY, tp_id INT)',
            'CREATE TABLE materi (id INTEGER PRIMARY KEY, tp_id INT, urutan INT, judul TEXT, jenis TEXT, aktif INT)',
        ];
        foreach ($sql as $stmt) {
            $this->db->query($stmt);
        }
        $this->db->table('ref_angkatan')->insertBatch([
            ['id' => 1, 'kode' => 'GEN-A', 'nama' => 'Gen A', 'semester_mulai' => 4],
            ['id' => 2, 'kode' => 'GEN-B', 'nama' => 'Gen B', 'semester_mulai' => 1],
        ]);
        $this->db->table('siswa')->insertBatch([
            ['id' => 10, 'pengguna_id' => 100, 'angkatan_id' => 1, 'semester_mulai' => null],
            ['id' => 11, 'pengguna_id' => 101, 'angkatan_id' => 2, 'semester_mulai' => null],
        ]);
        $this->db->table('ref_mapel')->insert(['id' => 4, 'kode' => 'SKI', 'nama' => 'Sejarah Kebudayaan Islam']);
        $this->db->table('paket_semester')->insertBatch([
            ['id' => 1, 'angkatan_id' => 1, 'mapel_id' => 4, 'semester' => 4, 'tahun_ajaran_id' => 1, 'nomor_versi' => 1, 'status' => 'aktif'],
            ['id' => 2, 'angkatan_id' => 2, 'mapel_id' => 4, 'semester' => 1, 'tahun_ajaran_id' => 1, 'nomor_versi' => 1, 'status' => 'aktif'],
        ]);
        $this->db->table('penugasan_paket')->insertBatch([
            ['id' => 1, 'angkatan_id' => 1, 'mapel_id' => 4, 'semester' => 4, 'tahun_ajaran_id' => 1, 'paket_id' => 1],
            ['id' => 2, 'angkatan_id' => 2, 'mapel_id' => 4, 'semester' => 1, 'tahun_ajaran_id' => 1, 'paket_id' => 2],
        ]);
        $this->db->table('tp')->insertBatch([
            ['id' => 1, 'paket_semester_id' => 1, 'kode_tp' => 'TP-A-1', 'judul' => 'TP pertama', 'urutan_atp' => 1, 'status' => 'siap', 'jenis_tp' => 'non_esensial', 'rumusan' => 'Rumusan pertama', 'indikator' => 'Indikator pertama'],
            ['id' => 2, 'paket_semester_id' => 1, 'kode_tp' => 'TP-A-2', 'judul' => 'TP kedua', 'urutan_atp' => 2, 'status' => 'siap', 'rumusan' => 'Rumusan kedua', 'indikator' => 'Indikator kedua'],
            ['id' => 3, 'paket_semester_id' => 2, 'kode_tp' => 'TP-B-1', 'judul' => 'Milik Gen B', 'urutan_atp' => 1, 'status' => 'siap', 'rumusan' => 'Gen lain', 'indikator' => 'Gen lain'],
        ]);
        $this->db->table('versi_konten_tp')->insertBatch([
            ['id' => 1, 'tp_id' => 1], ['id' => 2, 'tp_id' => 2], ['id' => 3, 'tp_id' => 3],
        ]);
        $this->db->table('materi')->insert(['id' => 1, 'tp_id' => 1, 'urutan' => 1, 'judul' => 'Judul materi', 'jenis' => 'pdf', 'aktif' => 1]);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function layanan(int $akunId, string $role = 'siswa'): KatalogPembelajaranSiswaService
    {
        return new KatalogPembelajaranSiswaService($this->db,
            static fn (): array => [
                'id' => $akunId, 'peran_aktif' => $role,
                'status' => 'aktif', 'wajib_ganti_sandi' => 0,
            ]);
    }

    public function testGenAMendapatKatalogSemesterEmpatDanRincianTPPertama(): void
    {
        $layanan = $this->layanan(100);
        $daftar = $layanan->daftarMapel();
        self::assertCount(1, $daftar['mapel']);
        self::assertSame(4, $daftar['siswa']['mulai_efektif']);
        $semester = $layanan->daftarSemester(4);
        self::assertCount(6, $semester['semester']);
        self::assertSame('Dilewati', $semester['semester'][0]['label_status']);
        self::assertTrue($semester['semester'][3]['buka']);
        self::assertFalse($semester['semester'][4]['buka']);
        $tp = $layanan->daftarTp(1);
        self::assertCount(2, $tp['tp']);
        self::assertTrue($tp['tp'][0]['akses']['buka']);
        self::assertFalse($tp['tp'][1]['akses']['buka']);
        $rincian = $layanan->rincianTp(1);
        self::assertSame('Rumusan pertama', $rincian['tp']['rumusan']);
        self::assertSame('Judul materi', $rincian['materi'][0]['judul']);
        self::assertSame(0, $this->db->tableExists('progres_tp') ? (int) $this->db->table('progres_tp')->countAllResults() : 0);
    }

    public function testSemesterSebelumMulaiGenTetapDitolakMeskiPaketSudahAktif(): void
    {
        $this->db->table('paket_semester')->insert([
            'id' => 6, 'angkatan_id' => 1, 'mapel_id' => 4, 'semester' => 3,
            'tahun_ajaran_id' => 1, 'nomor_versi' => 1, 'status' => 'aktif',
        ]);
        $this->db->table('penugasan_paket')->insert([
            'id' => 6, 'angkatan_id' => 1, 'mapel_id' => 4, 'semester' => 3,
            'tahun_ajaran_id' => 1, 'paket_id' => 6,
        ]);
        $layanan = $this->layanan(100);
        self::assertFalse($layanan->daftarSemester(4)['semester'][2]['buka']);
        $this->expectException(DomainException::class);
        $layanan->daftarTp(6);
    }

    public function testGenLainTidakDapatMengaksesPaketGenA(): void
    {
        $this->expectException(DomainException::class);
        $this->layanan(101)->daftarTp(1);
    }

    public function testGantiIdTPGenLainDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->layanan(100)->rincianTp(3);
    }

    public function testTpKeduaBelumBolehDibukaTanpaProgres(): void
    {
        $this->expectException(DomainException::class);
        $this->layanan(100)->rincianTp(2);
    }

    public function testTpBerikutnyaTerbukaHanyaSetelahSiswaSendiriTuntas(): void
    {
        $guruSiswa = $this->layanan(100);
        self::assertFalse($guruSiswa->daftarTp(1)['tp'][1]['akses']['buka']);
        $this->db->table('progres_tp')->insert([
            'id' => 1, 'siswa_id' => 10, 'tp_id' => 1,
            'paket_semester_id' => 1, 'status' => 'tuntas',
        ]);
        self::assertTrue($guruSiswa->daftarTp(1)['tp'][1]['akses']['buka']);
    }

    public function testSemesterLanjutanDitolakSampaiSemuaTpWajibTuntas(): void
    {
        $this->db->table('paket_semester')->insert([
            'id' => 4, 'angkatan_id' => 1, 'mapel_id' => 4, 'semester' => 5,
            'tahun_ajaran_id' => 2, 'nomor_versi' => 1, 'status' => 'aktif',
        ]);
        $this->db->table('penugasan_paket')->insert([
            'id' => 4, 'angkatan_id' => 1, 'mapel_id' => 4, 'semester' => 5,
            'tahun_ajaran_id' => 2, 'paket_id' => 4,
        ]);
        $this->db->table('tp')->insert([
            'id' => 4, 'paket_semester_id' => 4, 'kode_tp' => 'TP-A-5',
            'judul' => 'Semester berikutnya', 'urutan_atp' => 1,
            'status' => 'siap', 'jenis_tp' => 'non_esensial',
            'rumusan' => 'Konten semester lanjut', 'indikator' => 'Indikator',
        ]);
        $this->db->table('versi_konten_tp')->insert(['id' => 4, 'tp_id' => 4]);
        $siswa = $this->layanan(100);
        self::assertFalse($siswa->daftarSemester(4)['semester'][4]['buka']);
        $this->db->table('progres_tp')->insert([
            'id' => 1, 'siswa_id' => 10, 'tp_id' => 1,
            'paket_semester_id' => 1, 'status' => 'tuntas',
        ]);
        self::assertFalse($siswa->daftarSemester(4)['semester'][4]['buka']);
        $this->db->table('progres_tp')->insert([
            'id' => 2, 'siswa_id' => 10, 'tp_id' => 2,
            'paket_semester_id' => 1, 'status' => 'tuntas',
        ]);
        self::assertTrue($siswa->daftarSemester(4)['semester'][4]['buka']);
        self::assertSame(4, $siswa->daftarTp(4)['paket']['id']);
        self::assertFalse($this->layanan(101)->daftarSemester(4)['semester'][4]['buka']);
    }

    public function testTpEsensialTidakMemilikiAksesAktivitasSiswa(): void
    {
        $this->db->table('tp')->insert([
            'id' => 5, 'paket_semester_id' => 1, 'kode_tp' => 'TP-A-E',
            'judul' => 'Esensial', 'urutan_atp' => 3,
            'status' => 'siap', 'jenis_tp' => 'esensial',
            'rumusan' => 'Konten akademik', 'indikator' => 'Indikator',
        ]);
        $this->db->table('versi_konten_tp')->insert(['id' => 5, 'tp_id' => 5]);
        $tp = $this->layanan(100)->daftarTp(1)['tp'][2];
        self::assertSame('esensial', $tp['akses']['kode']);
        self::assertFalse($tp['akses']['buka']);
    }

    public function testPenggunaBukanSiswaDitolakWalaupunMemilikiIdSiswa(): void
    {
        $this->expectException(DomainException::class);
        $this->layanan(100, 'guru')->daftarMapel();
    }
}
