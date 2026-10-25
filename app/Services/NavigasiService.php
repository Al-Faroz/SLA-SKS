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
        if ($peran === 'siswa') {
            $menu[] = ['label' => 'Pembelajaran', 'path' => 'siswa/pembelajaran',
                'aktif' => $aktif === 'pembelajaran'];
        }
        if ($peran === 'guru') {
            $menu[] = ['label' => 'Peta ATP', 'path' => 'guru/peta-atp', 'aktif' => $aktif === 'peta_tp'];
            $menu[] = ['label' => 'Antrean Penilaian', 'path' => 'guru/penilaian',
                'aktif' => $aktif === 'penilaian'];
        }
        if ($peran === 'admin') {
            foreach ([
                'pengelola' => ['Pengelola', 'admin/pengelola'],
                'siswa' => ['Siswa', 'admin/siswa'],
                'gen' => ['Gen & Tahun Ajaran', 'admin/referensi'],
                'mapel' => ['Mapel & Guru', 'admin/mapel-guru'],
                'cp' => ['Referensi Kurikulum & CP', 'admin/kurikulum-cp'],
                'identitas' => ['Identitas Madrasah', 'admin/identitas'],
                'foto_profil' => ['Persetujuan Foto', 'admin/foto-profil'],
            ] as $kode => [$label, $path]) {
                $menu[] = ['label' => $label, 'path' => $path, 'aktif' => $aktif === $kode];
            }
        }

        $menu[] = ['label' => 'Profil & peran', 'path' => 'profil', 'aktif' => $aktif === 'profil'];

        return $menu;
    }
}
