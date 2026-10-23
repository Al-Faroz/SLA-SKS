<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\BerkasMateriService;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use DomainException;
use PHPUnit\Framework\TestCase;

final class BerkasMateriUnggahTest extends TestCase
{
    private static $db;
    private string $folder;
    private string $tmp;
    private int $versiAwal;

    public static function setUpBeforeClass(): void
    {
        if (getenv('SLA_QA_DB') !== 'sla_sks_qa') {
            self::markTestSkipped('Hanya database QA sementara.');
        }
        self::$db = Database::connect([
            'DSN' => '', 'hostname' => '127.0.0.1', 'username' => 'root',
            'password' => '', 'database' => 'sla_sks_qa', 'DBDriver' => 'MySQLi',
            'DBPrefix' => '', 'pConnect' => false, 'DBDebug' => true,
            'charset' => 'utf8mb4', 'DBCollat' => 'utf8mb4_unicode_ci',
            'swapPre' => '', 'encrypt' => false, 'compress' => false,
            'strictOn' => false, 'failover' => [], 'port' => 3306,
        ], false);
    }

    protected function setUp(): void
    {
        $this->folder = sys_get_temp_dir() . '/sla-upload-qa-' . bin2hex(random_bytes(8));
        $this->tmp = tempnam(sys_get_temp_dir(), 'sla-upload-png-');
        $img = imagecreatetruecolor(64, 64);
        imagefilledrectangle($img, 0, 0, 63, 63, imagecolorallocate($img, 200, 200, 200));
        imagepng($img, $this->tmp);
        imagedestroy($img);
        $tp = self::$db->table('tp')->where('id', 1)->get()->getRowArray();
        $this->versiAwal = (int) $tp['struktur_versi'];
        self::$db->table('materi')->where('id', 1)->update(['jenis' => 'gambar']);
    }

    protected function tearDown(): void
    {
        self::$db->table('materi')->where('tp_id', 1)->where('id !=', 1)->delete();
        self::$db->table('materi')->where('id', 1)->update(['jenis' => 'pdf', 'berkas_id' => null, 'judul' => 'Materi Uji A']);
        self::$db->table('berkas')->where('tp_id', 1)->delete();
        self::$db->table('tp')->where('id', 1)->update(['struktur_versi' => $this->versiAwal]);
        if (is_dir($this->folder)) {
            foreach (glob($this->folder . '/*') ?: [] as $path) {
                @unlink($path);
            }
            @rmdir($this->folder);
        }
        @unlink($this->tmp);
    }

    private function service(int $user): BerkasMateriService
    {
        return new BerkasMateriService(self::$db, static fn (): array => [
            'id' => $user, 'status' => 'aktif', 'peran_aktif' => 'guru', 'wajib_ganti_sandi' => 0,
        ], $this->folder);
    }

    private function uploaded(): UploadedFile
    {
        // Pada CLI file uji bukan hasil HTTP POST; hanya simulasi status isValid.
        // Jalur produksi tetap memakai UploadedFile asli dan is_uploaded_file().
        return new class ($this->tmp, 'gambar.png', 'image/png', filesize($this->tmp), UPLOAD_ERR_OK) extends UploadedFile {
            public function isValid(): bool
            {
                return true;
            }
        };
    }

    public function testUnggahMembuatMetadataDanSimpanPrivat(): void
    {
        $this->service(1)->unggah(1, 1, $this->versiAwal, $this->uploaded());
        $materi = self::$db->table('materi')->where('id', 1)->get()->getRowArray();
        self::assertNotNull($materi['berkas_id']);
        $meta = self::$db->table('berkas')->where('id', (int) $materi['berkas_id'])->get()->getRowArray();
        self::assertSame(1, (int) $meta['tp_id']);
        self::assertSame('image/png', $meta['mime']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{48}\\.png$/', $meta['nama_acak']);
        $path = $this->folder . '/' . $meta['nama_acak'];
        self::assertFileExists($path);
        self::assertSame(hash_file('sha256', $path), $meta['checksum']);
        self::assertSame($this->versiAwal + 1, (int) self::$db->table('tp')->where('id', 1)->get()->getRow('struktur_versi'));
    }

    private function inputMedia(): array
    {
        return [
            'judul' => 'Gambar Materi Terbaru',
            'jenis' => 'gambar',
            'petunjuk_resume' => 'Tuliskan ringkasan gambar.',
            'petunjuk_hots' => 'Analisis gambar ini.',
            'min_waktu_menit' => '2',
            'url_youtube' => '',
        ];
    }

    public function testTambahMateriSekaligusUpload(): void
    {
        $this->service(1)->tambahDenganMedia(1, $this->versiAwal, $this->inputMedia(), $this->uploaded());
        $baris = self::$db->table('materi')->where('tp_id', 1)
            ->where('judul', 'Gambar Materi Terbaru')->get()->getRowArray();
        self::assertNotNull($baris);
        self::assertSame(0, (int) $baris['aktif']);
        self::assertSame(120, (int) $baris['min_waktu_detik']);
        self::assertNotNull($baris['berkas_id']);
        self::assertSame(1, self::$db->table('berkas')->where('tp_id', 1)->countAllResults());
    }

    public function testSuntingMateriSekaligusUpload(): void
    {
        $input = $this->inputMedia();
        $input['judul'] = 'Materi Uji A Diperbarui';
        $this->service(1)->simpanDenganMedia(1, 1, $this->versiAwal, $input, $this->uploaded());
        $baris = self::$db->table('materi')->where('id', 1)->get()->getRowArray();
        self::assertSame('Materi Uji A Diperbarui', $baris['judul']);
        self::assertNotNull($baris['berkas_id']);
        self::assertSame(120, (int) $baris['min_waktu_detik']);
        self::assertSame($this->versiAwal + 1, (int) self::$db->table('tp')->where('id', 1)
            ->get()->getRow('struktur_versi'));
    }

    public function testTambahMateriVersiLamaTidakMembuatBaris(): void
    {
        try {
            $this->service(1)->tambahDenganMedia(1, 9999, $this->inputMedia(), $this->uploaded());
            self::fail('Versi lama harus ditolak.');
        } catch (DomainException $e) {
            self::assertSame(0, self::$db->table('berkas')->where('tp_id', 1)->countAllResults());
            self::assertSame(1, self::$db->table('materi')->where('tp_id', 1)->countAllResults());
            self::assertSame([], glob($this->folder . '/*') ?: []);
        }
    }

    public function testVersiLamaTidakMenyisakanBerkasAtauMetadata(): void
    {
        try {
            $this->service(1)->unggah(1, 1, 9999, $this->uploaded());
            self::fail('Unggahan versi lama seharusnya ditolak.');
        } catch (DomainException $e) {
            self::assertStringContainsString('berubah', $e->getMessage());
            self::assertSame(0, self::$db->table('berkas')->where('tp_id', 1)->countAllResults());
            self::assertSame([], glob($this->folder . '/*') ?: []);
        }
    }

    public function testGuruGenLainTidakBolehUnggah(): void
    {
        $this->expectException(DomainException::class);
        $this->service(2)->unggah(1, 1, $this->versiAwal, $this->uploaded());
    }

    public function testTpSiapTidakBolehUnggah(): void
    {
        self::$db->table('tp')->where('id', 1)->update(['status' => 'siap']);
        try {
            $this->expectException(DomainException::class);
            $this->service(1)->unggah(1, 1, $this->versiAwal, $this->uploaded());
        } finally {
            self::$db->table('tp')->where('id', 1)->update(['status' => 'draf']);
        }
    }
}
