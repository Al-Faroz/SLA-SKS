<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\SoalMediaDriveService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class SoalMediaDriveServiceTest extends TestCase
{
    public function testTautanFileGoogleDriveBisaDitinjau(): void
    {
        $list = SoalMediaDriveService::tautan(
            'Audio: https://drive.google.com/file/d/1aBcDeFgHijkLMNopQrStu/view?usp=sharing'
            . "\n" . 'Video: https://drive.google.com/open?id=1XYZabcdeFghijklMNOPQ'
        );
        self::assertCount(2, $list);
        self::assertSame('Audio', $list[0]['jenis']);
        self::assertSame('https://drive.google.com/file/d/1aBcDeFgHijkLMNopQrStu/view', $list[0]['url']);
        self::assertSame('Video', $list[1]['jenis']);
        self::assertSame([], SoalMediaDriveService::tautan('Soal biasa tanpa media.'));
    }

    public function testTautanBukanGoogleDriveDitolak(): void
    {
        $this->expectException(DomainException::class);
        SoalMediaDriveService::tautan('Audio: https://example.com/audio.mp3');
    }

    public function testTautanFolderDitolak(): void
    {
        $this->expectException(DomainException::class);
        SoalMediaDriveService::tautan('Video: https://drive.google.com/drive/folders/1aBcDeFgHijkLMNopQrStu');
    }

    public function testLabelTanpaUrlDitolak(): void
    {
        $this->expectException(DomainException::class);
        SoalMediaDriveService::tautan('Audio: belum ada tautannya');
    }
}
