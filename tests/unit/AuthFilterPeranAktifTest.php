<?php

declare(strict_types=1);

use App\Filters\AuthFilter;
use PHPUnit\Framework\TestCase;

final class AuthFilterPeranAktifTest extends TestCase
{
    public function testAkunAdminGuruMendapatPetunjukGantiPeran(): void
    {
        $view = AuthFilter::informasiPeranDitolak([
            'peran_aktif'=>'admin','daftar_peran'=>['admin','guru']
        ], 'guru');
        self::assertSame('profil', $view['tautanPath']);
        self::assertSame('Buka Profil & Peran', $view['labelTautan']);
        self::assertStringContainsString('Admin', $view['pesan']);
        self::assertStringContainsString('Guru', $view['pesan']);
        self::assertStringContainsString('Aktifkan peran', $view['pesan']);
    }

    public function testAkunTanpaRoleGuruTidakMendapatJalanPintas(): void
    {
        $view = AuthFilter::informasiPeranDitolak([
            'peran_aktif'=>'siswa','daftar_peran'=>['siswa']
        ], 'guru');
        self::assertSame('profil', $view['tautanPath']);
        self::assertStringContainsString('belum memiliki peran', $view['pesan']);
        self::assertStringNotContainsString('Aktifkan peran', $view['pesan']);
    }
}
