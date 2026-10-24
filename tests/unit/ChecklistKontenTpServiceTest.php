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

    public function testMateriLengkapDanUrutanSah(): void
    {
        $tp = ['kode_tp' => 'TP-01', 'rumusan' => 'Menganalisis', 'indikator' => 'Menjelaskan'];
        $materi = [
            ['urutan' => 1, 'jenis' => 'pdf', 'berkas_id' => 42,
             'petunjuk_resume' => 'Buat ringkasan', 'petunjuk_hots' => 'Berikan alasan'],
            ['urutan' => 2, 'jenis' => 'youtube', 'url_youtube' => 'https://www.youtube.com/watch?v=abc123xyz00',
             'petunjuk_resume' => 'Rangkum video', 'petunjuk_hots' => 'Buat analisis'],
        ];
        $hasil = ChecklistKontenTpService::periksa($tp, $materi);
        self::assertCount(12, $hasil);
        foreach ($hasil as $cek) {
            self::assertTrue($cek['terpenuhi'], $cek['label']);
        }
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
}
