<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ChecklistKontenTpService;
use PHPUnit\Framework\TestCase;

final class ChecklistKontenTpServiceTest extends TestCase
{
    public function testKosongTidakDapatDianggapLengkap(): void
    {
        $hasil = ChecklistKontenTpService::periksa([], []);
        self::assertCount(4, $hasil);
        foreach ($hasil as $cek) {
            self::assertFalse($cek['terpenuhi']);
        }
    }

    public function testSatuMateriLengkapDanUrutanSah(): void
    {
        $tp = ['kode_tp' => 'TP-01', 'rumusan' => 'Menganalisis', 'indikator' => 'Menjelaskan'];
        $materi = [
            ['urutan' => 1, 'jenis' => 'pdf', 'berkas_id' => 42,
             'petunjuk_resume' => 'Buat ringkasan', 'petunjuk_hots' => 'Berikan alasan'],
        ];
        $hasil = ChecklistKontenTpService::periksa($tp, $materi);
        self::assertCount(8, $hasil);
        foreach ($hasil as $cek) {
            self::assertTrue($cek['terpenuhi'], $cek['label']);
        }
    }

    public function testDuaMateriTidakMemenuhiKontrakV25(): void
    {
        $tp = ['kode_tp' => 'TP-01', 'rumusan' => 'Menganalisis', 'indikator' => 'Menjelaskan'];
        $dua = [
            ['urutan' => 1, 'jenis' => 'pdf', 'berkas_id' => 42,
                'petunjuk_resume' => 'Resume', 'petunjuk_hots' => 'HOTS'],
            ['urutan' => 2, 'jenis' => 'youtube',
                'url_youtube' => 'https://www.youtube.com/watch?v=abcdefghijk',
                'petunjuk_resume' => 'Resume', 'petunjuk_hots' => 'HOTS'],
        ];
        $cek = ChecklistKontenTpService::periksa($tp, $dua);
        self::assertSame('Tepat satu Materi', $cek[3]['label']);
        self::assertFalse($cek[3]['terpenuhi']);
    }

    public function testBerkasDanPetunjukYangHilangDitandai(): void
    {
        $hasil = ChecklistKontenTpService::periksa(
            ['kode_tp' => 'TP-1', 'rumusan' => 'Ada', 'indikator' => 'Ada'],
            [['urutan' => 3, 'jenis' => 'audio', 'berkas_id' => null,
              'petunjuk_resume' => ' ', 'petunjuk_hots' => 'Pertanyaan']]
        );
        self::assertFalse($hasil[4]['terpenuhi']);
        self::assertFalse($hasil[5]['terpenuhi']);
        self::assertTrue($hasil[6]['terpenuhi']);
        self::assertFalse($hasil[7]['terpenuhi']);
    }
    public function testTautanCpHilangDitandaiDanCpTertautBelumDianggapValid(): void
    {
        $tp = ['kode_tp' => 'TP-01', 'rumusan' => 'Menganalisis', 'indikator' => 'Menjelaskan'];
        $kosong = ChecklistKontenTpService::periksa($tp, [], 0);
        self::assertSame('Tautan CP pada TP', $kosong[3]['label']);
        self::assertFalse($kosong[3]['terpenuhi']);

        $ada = ChecklistKontenTpService::periksa($tp, [], 2);
        self::assertTrue($ada[3]['terpenuhi']);
        self::assertStringContainsString('belum diverifikasi', $ada[3]['keterangan']);
    }

}
