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
                'cp' => ['Referensi Kurikulum & CP', 'admin/kurikulum-cp'],
                'usulan_cp' => ['Keputusan Usulan CP', 'admin/usulan-cp'],
                'identitas' => ['Identitas Madrasah', 'admin/identitas'],
                'foto_profil' => ['Persetujuan Foto', 'admin/foto-profil'],
            ] as $kode => [$label, $path]) {
                $menu[] = ['label' => $label, 'path' => $path, 'aktif' => $aktif === $kode];
            }
        }

        if ($peran === 'guru') {
            $menu[] = ['label' => 'Paket dan BAB', 'path' => 'guru/paket', 'aktif' => $aktif === 'paket'];
            $menu[] = ['label' => 'Usulan CP', 'path' => 'guru/usulan-cp', 'aktif' => $aktif === 'usulan_cp'];
        }

        $menu[] = ['label' => 'Profil & peran', 'path' => 'profil', 'aktif' => $aktif === 'profil'];

        return $menu;
    }
}
