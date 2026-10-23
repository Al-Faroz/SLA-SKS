<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BerkasMateriService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class BerkasMateriValidatorTest extends TestCase
{
    /** @var list<string> */
    private array $temp = [];

    protected function tearDown(): void
    {
        foreach ($this->temp as $file) {
            @unlink($file);
        }
    }

    private function file(string $data): string
    {
        $path = tempnam(sys_get_temp_dir(), 'qa-media-');
        file_put_contents($path, $data);
        $this->temp[] = $path;
        return $path;
    }

    public function testValidasiGambarJpg(): void
    {
        $raw = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAAIAAgDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAABAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCfAAD/2Q==');
        $path = $this->file($raw);
        $info = @getimagesize($path);
        if ($info === false) {
            self::markTestSkipped('Fixture JPEG tidak dikenali oleh pustaka GD/Exif lingkungan ini.');
        }
        $v = BerkasMateriService::validasi($path, 'gambar', 'foto.jpg');
        self::assertSame('image/jpeg', $v['mime']);
        self::assertSame('jpg', $v['ext']);
    }

    public function testValidasiMp3HeaderId3(): void
    {
        $path = $this->file('ID3' . str_repeat("\0", 50));
        $v = BerkasMateriService::validasi($path, 'audio', 'materi.mp3');
        self::assertSame('audio/mpeg', $v['mime']);
        self::assertSame('mp3', $v['ext']);
    }

    /** @dataProvider berkasTidakSah */
    public function testTolakBerkasTidakSah(string $content, string $jenis, string $nama): void
    {
        $path = $this->file($content);
        $this->expectException(DomainException::class);
        BerkasMateriService::validasi($path, $jenis, $nama);
    }

    public static function berkasTidakSah(): iterable
    {
        yield 'PHP menyamar gambar' => ['<?php echo "x"; ?>', 'gambar', 'foto.jpg'];
        yield 'audio palsu' => [str_repeat('A', 100), 'audio', 'palsu.mp3'];
        yield 'ekstensi executable' => ['ID3' . str_repeat("\0", 50), 'audio', 'media.php'];
        yield 'file kecil' => ['test', 'audio', 'x.mp3'];
        yield 'pdf belum diaktifkan' => ['%PDF-1.7' . str_repeat('x', 100), 'pdf', 'sampel.pdf'];
        yield 'video upload ditolak' => ['testdata' . str_repeat('x', 100), 'youtube', 'a.mp4'];
    }
}
