<?php

declare(strict_types=1);

use App\Services\KebijakanPrivasiValidasiService;
use App\Services\NotifikasiKontrakService;
use PHPUnit\Framework\TestCase;

final class A13KontrakNotifikasiPrivasiTest extends TestCase
{
    public function testJenisNotifikasiTerbatasPadaPeristiwaAkademikSah(): void
    {
        $jenis = NotifikasiKontrakService::jenis();
        self::assertContains('nilai_resume_final', $jenis);
        self::assertContains('izin_akses_ulang', $jenis);
        self::assertContains('sertifikat_awal_terbit', $jenis);
        self::assertContains('pra_semester_dibatalkan', $jenis);
        self::assertNotContains('email_massal', $jenis);
        self::assertNotContains('nilai_akhir_semester', $jenis);
        self::assertCount(count(array_unique($jenis)), $jenis);
    }

    public function testPayloadNotifikasiMinimalDanKunciIdempoten(): void
    {
        $satu = NotifikasiKontrakService::susun('sertifikat_awal_terbit', 101, 'siswa', 23);
        $duplikat = NotifikasiKontrakService::susun('sertifikat_awal_terbit', 101, 'siswa', 23);
        $penerimaLain = NotifikasiKontrakService::susun('sertifikat_awal_terbit', 102, 'siswa', 23);
        $versiLain = NotifikasiKontrakService::susun('sertifikat_awal_terbit', 101, 'siswa', 23, 2);
        self::assertSame($satu, $duplikat);
        self::assertSame($satu['kunci_idempoten'], $duplikat['kunci_idempoten']);
        self::assertNotSame($satu['kunci_idempoten'], $penerimaLain['kunci_idempoten']);
        self::assertNotSame($satu['kunci_idempoten'], $versiLain['kunci_idempoten']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $satu['kunci_idempoten']);
        foreach (['nisn', 'sandi_hash', 'nama_siswa', 'token_ujian', 'kunci_soal', 'jawaban'] as $rahasiakan) {
            self::assertArrayNotHasKey($rahasiakan, $satu);
        }
    }

    public function testPeranPenerimaTidakBolehDipalsukan(): void
    {
        foreach ([
            ['permintaan_akses_ulang', 'siswa'],
            ['izin_akses_ulang', 'guru'],
            ['sertifikat_awal_terbit', 'pimpinan'],
            ['permohonan_koreksi_nilai', 'guru'],
            ['invalid_event', 'siswa'],
        ] as [$jenis, $role]) {
            try {
                NotifikasiKontrakService::susun($jenis, 1, $role, 2);
                self::fail('Jenis/peran seharusnya ditolak.');
            } catch (\DomainException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
        self::assertSame('guru', NotifikasiKontrakService::susun(
            'permintaan_akses_ulang', 5, 'guru', 12
        )['peran_penerima']);
        foreach ([[0, 'siswa', 1, 1], [1, 'siswa', 0, 1], [1, 'siswa', 1, 0]] as $kasus) {
            try {
                NotifikasiKontrakService::susun('sertifikat_awal_terbit', ...$kasus);
                self::fail('ID tidak sah harus ditolak.');
            } catch (\DomainException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testValidasiVersiTanggalDanNaskahPrivasi(): void
    {
        $data = KebijakanPrivasiValidasiService::validasiNaskah([
            'versi' => '2026.11-1',
            'tanggal_berlaku' => '2026-11-04',
            'teks' => "Naskah resmi yang telah disahkan.\r\nBagian data dan kontak madrasah.",
        ]);
        self::assertSame('2026.11-1', $data['versi']);
        self::assertSame('2026-11-04', $data['tanggal_berlaku']);
        self::assertStringNotContainsString("\r", $data['teks']);
        self::assertSame(hash('sha256', $data['teks']), $data['checksum_sha256']);
        self::assertCount(4, $data);
    }

    public function testNaskahInvalidDitolakTanpaMenciptakanIsiKebijakan(): void
    {
        $valid = [
            'versi' => '2026-11',
            'tanggal_berlaku' => '2026-11-04',
            'teks' => 'Teks yang sudah disiapkan pihak madrasah.',
        ];
        $salah = [
            ['versi' => 'javascript:alert(1)'],
            ['versi' => str_repeat('A', 41)],
            ['tanggal_berlaku' => '2026-02-30'],
            ['tanggal_berlaku' => '04-11-2026'],
            ['teks' => ' '],
            ['teks' => str_repeat('X', 50001)],
            ['versi' => null],
        ];
        foreach ($salah as $ubah) {
            try {
                KebijakanPrivasiValidasiService::validasiNaskah(array_replace($valid, $ubah));
                self::fail('Naskah tidak valid seharusnya ditolak.');
            } catch (\DomainException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
        KebijakanPrivasiValidasiService::pastikanKonfirmasiPengesahan('1');
        foreach ([null, '', true, '0', 'true', 1] as $salahKonfirmasi) {
            try {
                KebijakanPrivasiValidasiService::pastikanKonfirmasiPengesahan($salahKonfirmasi);
                self::fail('Belum ada pernyataan pengesahan yang sah.');
            } catch (\DomainException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testTidakAdaEndpointPalsuAtauPerubahanSkemaPraAudit(): void
    {
        $root = dirname(__DIR__, 2) . '/';
        $routes = file_get_contents($root . 'app/Config/Routes.php');
        $publik = file_get_contents($root . 'app/Controllers/Publik.php');
        $view = file_get_contents($root . 'app/Views/publik/kebijakan_privasi.php');
        self::assertIsString($routes);
        self::assertStringContainsString("'kebijakan-privasi', 'Publik::kebijakanPrivasi'", $routes);
        self::assertStringContainsString('setStatusCode(503)', $publik);
        self::assertStringContainsString('Belum diterbitkan', $view);
        self::assertStringNotContainsString('INSERT INTO notifikasi', $publik);
        self::assertStringNotContainsString('pernah_disahkan = true', $view);
    }
}
