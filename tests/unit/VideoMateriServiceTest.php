<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\VideoMateriService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class VideoMateriServiceTest extends TestCase
{
    /** @dataProvider tautanSah */
    public function testMenerimaVideoYouTube(string $url): void
    {
        self::assertSame('jfKfPfyJRdk', VideoMateriService::idVideo($url));
        self::assertSame('https://www.youtube-nocookie.com/embed/jfKfPfyJRdk', VideoMateriService::urlEmbed($url));
    }

    public static function tautanSah(): iterable
    {
        yield 'watch' => ['https://www.youtube.com/watch?v=jfKfPfyJRdk'];
        yield 'watch mobile' => ['https://m.youtube.com/watch?v=jfKfPfyJRdk&t=30'];
        yield 'pendek' => ['https://youtu.be/jfKfPfyJRdk'];
        yield 'shorts' => ['https://youtube.com/shorts/jfKfPfyJRdk'];
        yield 'embed' => ['https://www.youtube-nocookie.com/embed/jfKfPfyJRdk'];
        yield 'live' => ['https://www.youtube.com/live/jfKfPfyJRdk'];
    }

    /** @dataProvider tautanDitolak */
    public function testMenolakTautanPalsu(string $url): void
    {
        $this->expectException(DomainException::class);
        VideoMateriService::urlEmbed($url);
    }

    public static function tautanDitolak(): iterable
    {
        yield 'host palsu' => ['https://youtube.com.example.org/watch?v=jfKfPfyJRdk'];
        yield 'subdomain palsu' => ['https://evil.youtube.com/watch?v=jfKfPfyJRdk'];
        yield 'HTTP' => ['http://www.youtube.com/watch?v=jfKfPfyJRdk'];
        yield 'port' => ['https://www.youtube.com:8080/watch?v=jfKfPfyJRdk'];
        yield 'userinfo' => ['https://user@www.youtube.com/watch?v=jfKfPfyJRdk'];
        yield 'bukan video' => ['https://youtube.com/playlist?list=PL123'];
        yield 'id panjang' => ['https://youtu.be/abcdefghijkXXXX'];
        yield 'fragment' => ['https://youtu.be/jfKfPfyJRdk#x'];
        yield 'query ganda array' => ['https://www.youtube.com/watch?v[]=jfKfPfyJRdk'];
        yield 'slash palsu' => ['https://youtu.be/jfKfPfyJRdk/extra'];
        yield 'kosong' => [''];
        yield 'white space' => ["https://youtu.be/jfKfPfyJRdk\r\nmalicious"];
    }
}
