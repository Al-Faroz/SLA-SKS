<?php

declare(strict_types=1);

use App\Services\KesiapanTpService;
use PHPUnit\Framework\TestCase;

final class KesiapanTpTest extends TestCase
{
    private static function data(): array
    {
        $tp = [
            'id' => 1, 'kode_tp' => 'TP-SKI-S4-01', 'judul' => 'Sejarah',
            'rumusan' => 'Memahami peristiwa', 'indikator' => 'Menguraikan peristiwa',
            'status' => 'draf', 'struktur_versi' => 1, 'kkm' => 75,
            'bobot_resume' => 20, 'bobot_tugas' => 30, 'bobot_sumatif' => 50,
            'resume_min' => 50, 'tugas_min' => 200, 'diag_tayang' => 10,
            'sum_tayang' => 20, 'diag_durasi' => 30, 'sum_durasi' => 60,
            'sum_maks_percobaan' => 3,
        ];
        $paket = ['id' => 10, 'mapel_id' => 2, 'kurikulum_id' => 1, 'status' => 'draf'];
        $cp = [[
            'cp_versi_id' => 3, 'utama' => 1, 'versi_status' => 'aktif',
            'mapel_id' => 2, 'kurikulum_id' => 1, 'fase' => 'D',
        ]];
        $materi = [[
            'id' => 1, 'urutan' => 1, 'judul' => 'Materi 1', 'jenis' => 'youtube',
            'url_youtube' => 'https://www.youtube.com/watch?v=abcdefghijk',
            'berkas_id' => null, 'min_waktu_detik' => 120,
            'petunjuk_resume' => 'Tuliskan ringkasan', 'petunjuk_hots' => 'Jelaskan dampaknya',
        ]];
        $soal = [];
        foreach (['diagnostik' => 10, 'sumatif' => 20] as $jenis => $jumlah) {
            for ($i = 1; $i <= $jumlah; $i++) {
                $soal[] = [
                    'id' => count($soal) + 1, 'jenis' => $jenis, 'urutan' => $i,
                    'pertanyaan' => 'Pertanyaan ' . $i, 'opsi_a' => 'Jawaban A',
                    'opsi_b' => 'Jawaban B', 'opsi_c' => 'Jawaban C',
                    'opsi_d' => 'Jawaban D', 'opsi_e' => 'Jawaban E',
                    'kunci' => 'A', 'aktif' => 1,
                ];
            }
        }
        return [$tp, $paket, $cp, $materi, $soal];
    }

    private static function hasil(array $items, string $kode): array
    {
        foreach ($items as $row) {
            if ($row['kode'] === $kode) {
                return $row;
            }
        }
        self::fail('Kode pemeriksaan tidak ditemukan: ' . $kode);
    }

    public function testKelengkapanValidMemenuhiSemuaKetentuan(): void
    {
        [$tp, $paket, $cp, $materi, $soal] = self::data();
        $result = KesiapanTpService::evaluasi($tp, $paket, $cp, $materi, $soal);
        self::assertNotEmpty($result);
        self::assertNotContains(false, array_column($result, 'terpenuhi'));
        self::assertSame('bank-diagnostik', self::hasil($result, 'bank-diagnostik')['kode']);
        self::assertSame('bank-sumatif', self::hasil($result, 'bank-sumatif')['kode']);
    }

    public function testCpBukanFaseAtauAdaDuaUtamaMemblokirPublikasi(): void
    {
        [$tp, $paket, $cp, $materi, $soal] = self::data();
        $cp[0]['fase'] = 'E';
        self::assertFalse(self::hasil(KesiapanTpService::evaluasi($tp, $paket, $cp, $materi, $soal), 'cp')['terpenuhi']);
        $cp[0]['fase'] = 'D';
        $cp[] = $cp[0];
        self::assertFalse(self::hasil(KesiapanTpService::evaluasi($tp, $paket, $cp, $materi, $soal), 'cp')['terpenuhi']);
    }

    public function testMateriTanpaResumeHotsDanUrutanUtuhDitolak(): void
    {
        [$tp, $paket, $cp, $materi, $soal] = self::data();
        $materi[0]['petunjuk_resume'] = '';
        $materi[0]['petunjuk_hots'] = '';
        $materi[0]['urutan'] = 3;
        self::assertFalse(self::hasil(KesiapanTpService::evaluasi($tp, $paket, $cp, $materi, $soal), 'materi-1')['terpenuhi']);
    }

    public function testJumlahSoalDanKunciTidakValidMenolakSiap(): void
    {
        [$tp, $paket, $cp, $materi, $soal] = self::data();
        $soal[0]['kunci'] = 'G';
        self::assertFalse(self::hasil(KesiapanTpService::evaluasi($tp, $paket, $cp, $materi, $soal), 'valid-diagnostik')['terpenuhi']);
        array_pop($soal);
        self::assertFalse(self::hasil(KesiapanTpService::evaluasi($tp, $paket, $cp, $materi, $soal), 'bank-sumatif')['terpenuhi']);
    }

    public function testPengaturanWaktuBobotDanKkmWajibSah(): void
    {
        [$tp, $paket, $cp, $materi, $soal] = self::data();
        $tp['diag_durasi'] = 3;
        $tp['bobot_sumatif'] = 30;
        $tp['kkm'] = 0;
        $hasil = KesiapanTpService::evaluasi($tp, $paket, $cp, $materi, $soal);
        foreach (['diag_durasi', 'bobot', 'kkm'] as $code) {
            self::assertFalse(self::hasil($hasil, $code)['terpenuhi']);
        }
    }

    public function testHalamanMencantumkanKonfirmasiDanJalurPerbaikan(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root . '/app/Views/guru/kesiapan_tp.php');
        $detail = file_get_contents($root . '/app/Views/guru/detail_tp.php');
        $route = file_get_contents($root . '/app/Config/Routes.php');
        self::assertIsString($view);
        self::assertIsString($detail);
        self::assertIsString($route);
        self::assertStringContainsString('tinjau_akademik', $view);
        self::assertStringContainsString('name="sidik_jari"', $view);
        self::assertStringContainsString('csrf_field()', $view);
        self::assertStringContainsString('Konfirmasi &amp; Jadikan TP Siap', $view);
        self::assertStringContainsString("if (\$siap)", $view);
        self::assertStringContainsString("if (\$tp['status'] === 'draf')", $view);
        self::assertStringContainsString('Periksa kesiapan TP Siap', $detail);
        self::assertStringContainsString("GuruKesiapanTp::siapkan", $route);
    }
    public function testSidikJariMendeteksiSuntinganSoalTanpaMengubahStrukturVersi(): void
    {
        [$tp, $paket, $cp, $materi, $soal] = self::data();
        $awal = KesiapanTpService::sidikJari($tp, $paket, $cp, $materi, $soal);
        self::assertSame(64, strlen($awal));
        self::assertSame($awal, KesiapanTpService::sidikJari($tp, $paket, $cp, $materi, $soal));
        $soal[0]['kunci'] = 'B';
        self::assertNotSame($awal, KesiapanTpService::sidikJari($tp, $paket, $cp, $materi, $soal));
    }

    public function testPaketArsipTidakDapatMenerbitkanTpBaru(): void
    {
        [$tp, $paket, $cp, $materi, $soal] = self::data();
        $paket['status'] = 'arsip';
        self::assertFalse(self::hasil(
            KesiapanTpService::evaluasi($tp, $paket, $cp, $materi, $soal), 'paket'
        )['terpenuhi']);
    }

    public function testHanyaSatuChecklistDitampilkanPadaHalamanKesiapan(): void
    {
        $root = dirname(__DIR__, 2);
        $detail = file_get_contents($root . '/app/Views/guru/detail_tp.php');
        $kesiapan = file_get_contents($root . '/app/Views/guru/kesiapan_tp.php');
        $service = file_get_contents($root . '/app/Services/KesiapanTpService.php');
        self::assertIsString($detail);
        self::assertIsString($kesiapan);
        self::assertIsString($service);

        self::assertStringNotContainsString('Pemeriksaan awal kelengkapan konten', $detail);
        self::assertStringNotContainsString('checklist-konten-tp', $detail);
        self::assertStringNotContainsString('foreach ($checklist_konten', $detail);
        self::assertSame(1, substr_count($detail, 'id="kesiapan-tp"'));
        self::assertStringContainsString("'/kesiapan'", $detail);

        self::assertSame(1, substr_count($kesiapan, 'id="judul-checklist-akhir"'));
        self::assertStringContainsString('Daftar pemeriksaan final', $kesiapan);
        self::assertStringContainsString("if ($tp['status'] === 'siap')", $detail);
        self::assertStringContainsString("if ($tp['status'] === 'draf')", $kesiapan);
        self::assertStringNotContainsString("'#checklist-konten-tp'", $service);
        self::assertStringContainsString("'guru/peta-atp'", $service);
    }

}
