<?php

declare(strict_types=1);

use App\Services\MateriDrafService;
use PHPUnit\Framework\TestCase;

final class MateriEsensialValidationTest extends TestCase
{
    public function testBersihkanMenerimaMateriEsensialTanpaTugasDanMenolakNonEsensial(): void
    {
        $db = \Config\Database::connect([
            'DSN' => '', 'database' => ':memory:',
            'DBDriver' => 'SQLite3', 'DBPrefix' => '',
            'DBDebug' => true, 'pConnect' => false,
            'foreignKeys' => false, 'failover' => [],
        ], false);
        try {
            $db->query('CREATE TABLE tp (id INTEGER PRIMARY KEY, jenis_tp TEXT)');
            $db->table('tp')->insertBatch([
                ['id' => 1, 'jenis_tp' => 'esensial'],
                ['id' => 2, 'jenis_tp' => 'non_esensial'],
            ]);
            $service = new MateriDrafService($db);
            $input = [
                'judul' => 'Materi sejarah', 'jenis' => 'youtube',
                'url_youtube' => 'https://www.youtube.com/watch?v=abcdefghijk',
                'min_waktu_menit' => '2',
                'petunjuk_resume' => '', 'petunjuk_hots' => '',
            ];
            $hasil = $service->bersihkan($input, 1);
            self::assertSame('', $hasil['petunjuk_resume']);
            self::assertSame('', $hasil['petunjuk_hots']);
            self::assertSame(120, $hasil['min_waktu_detik']);
            $this->expectException(\DomainException::class);
            $service->bersihkan($input, 2);
        } finally {
            $db->close();
        }
    }
}
