<?php

declare(strict_types=1);

namespace App\Services;

/** Satu sumber menu yang hanya memuat endpoint yang sudah tersedia. */
final class NavigasiService
{
    /** @return list<array{label:string,path:string,aktif:bool}> */
    public static function untuk(string $peran, string $aktif): array
    {
        $menu = [
            ['label' => 'Beranda', 'path' => $peran, 'aktif' => $aktif === $peran],
        ];
        if ($peran === 'admin') {
            foreach ([
                'pengelola' => ['Pengelola', 'admin/pengelola'],
                'siswa' => ['Siswa', 'admin/siswa'],
                'gen' => ['Gen & Tahun Ajaran', 'admin/referensi'],
                'mapel' => ['Mapel & Guru', 'admin/mapel-guru'],
                'identitas' => ['Identitas Madrasah', 'admin/identitas'],
            ] as $kode => [$label, $path]) {
                $menu[] = ['label' => $label, 'path' => $path, 'aktif' => $aktif === $kode];
            }
        }

        $menu[] = ['label' => 'Profil & peran', 'path' => 'profil', 'aktif' => $aktif === 'profil'];

        return $menu;
    }
}
