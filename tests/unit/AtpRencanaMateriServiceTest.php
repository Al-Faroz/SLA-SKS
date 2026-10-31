<?php

declare(strict_types=1);

use App\Services\AtpRencanaMateriService;
use PHPUnit\Framework\TestCase;

final class AtpRencanaMateriServiceTest extends TestCase
{
    public function testRencanaGuruDisimpanSebagaiMateriDrafTidakAktif(): void
    {
        foreach ([
            'YouTube' => 'youtube', 'PDF' => 'pdf',
            'Audio' => 'audio', 'Gambar' => 'gambar',
        ] as $input => $hasil) {
            $baris = AtpRencanaMateriService::draf(19, [
                'materi' => 'Materi contoh dari Excel', 'media' => $input,
                'jenis' => 'esensial',
            ]);
            self::assertSame(19, $baris['tp_id']);
            self::assertSame('Materi contoh dari Excel', $baris['judul']);
            self::assertSame($hasil, $baris['jenis']);
            self::assertSame(1, $baris['urutan']);
            self::assertSame(0, $baris['aktif']);
            self::assertSame(0, $baris['min_waktu_detik']);
            self::assertNull($baris['berkas_id']);
            self::assertNull($baris['url_youtube']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/D', $baris['row_uuid']);
        }
    }

    public function testMediaTidakSahDitolak(): void
    {
        $this->expectException(\DomainException::class);
        AtpRencanaMateriService::draf(19, ['materi' => 'Uji', 'media' => 'EXE']);
    }

    public function testJudulKosongDitolak(): void
    {
        $this->expectException(\DomainException::class);
        AtpRencanaMateriService::draf(19, ['materi' => '', 'media' => 'PDF']);
    }
}
