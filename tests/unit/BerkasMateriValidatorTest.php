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

    public function testValidasiGambarPng(): void
    {
        $img = imagecreatetruecolor(20, 20);
        $path = tempnam(sys_get_temp_dir(), 'qa-gambar-');
        $this->temp[] = $path;
        imagepng($img, $path);
        imagedestroy($img);
        $v = BerkasMateriService::validasi($path, 'gambar', 'foto.png');
        self::assertSame('image/png', $v['mime']);
        self::assertSame('png', $v['ext']);
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
