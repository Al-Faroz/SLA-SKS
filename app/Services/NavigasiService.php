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
            $menu[] = ['label' => 'Laporan Akademik', 'path' => 'siswa/laporan',
                'aktif' => $aktif === 'laporan'];
            $menu[] = ['label' => 'Sertifikat', 'path' => 'siswa/sertifikat',
                'aktif' => $aktif === 'sertifikat'];
        }
        if ($peran === 'guru') {
            $menu[] = ['label' => 'Peta ATP', 'path' => 'guru/peta-atp', 'aktif' => $aktif === 'peta_tp'];
            $menu[] = ['label' => 'Antrean Penilaian', 'path' => 'guru/penilaian',
                'aktif' => $aktif === 'penilaian'];
            $menu[] = ['label' => 'Pra-Semester', 'path' => 'guru/pra-semester',
                'aktif' => $aktif === 'pra_semester'];
            $menu[] = ['label' => 'Permintaan Akses Ulang', 'path' => 'guru/akses-ulang',
                'aktif' => $aktif === 'ujian_batal'];
            $menu[] = [
                'label'=>'Rekap Nilai',
                'aktif'=>in_array($aktif,['rekap_nilai','rekap_pra_semester'],true),
                'submenu'=>[
                    ['label'=>'Nilai TP','path'=>'guru/rekap-nilai',
                        'aktif'=>$aktif === 'rekap_nilai'],
                    ['label'=>'Nilai Pra-Semester','path'=>'guru/rekap-nilai/pra-semester',
                        'aktif'=>$aktif === 'rekap_pra_semester'],
                ],
            ];
        }
        if ($peran === 'admin') {
            foreach ([
                'pengelola' => ['Pengelola', 'admin/pengelola'],
                'siswa' => ['Siswa', 'admin/siswa'],
                'gen' => ['Gen & Tahun Ajaran', 'admin/referensi'],
                'mapel' => ['Mapel & Guru', 'admin/mapel-guru'],
                'cp' => ['Referensi Kurikulum & CP', 'admin/kurikulum-cp'],
                'identitas' => ['Identitas Madrasah', 'admin/identitas'],
                'sertifikat' => ['Sertifikat', 'admin/sertifikat'],
                'kkm' => ['Pengaturan KKM', 'admin/kkm'],
                'pra_semester' => ['Jadwal Pra-Semester', 'admin/pra-semester'],
                'foto_profil' => ['Persetujuan Foto', 'admin/foto-profil'],
            ] as $kode => [$label, $path]) {
                $menu[] = ['label' => $label, 'path' => $path, 'aktif' => $aktif === $kode];
            }
        }

        if ($peran === 'pimpinan') {
            $menu[] = ['label' => 'Monitoring Akademik', 'path' => 'pimpinan/laporan',
                'aktif' => $aktif === 'laporan'];
        }
        // A13-4: satu grup informasi untuk menjaga sidebar ringkas, tetap role-scoped.
        $informasi = [
            ['label' => 'Pengumuman', 'path' => 'pengumuman',
                'aktif' => $aktif === 'pengumuman'],
        ];
        if (in_array($peran, ['admin', 'guru'], true)) {
            $informasi[] = ['label' => 'Kelola Pengumuman',
                'path' => $peran . '/pengumuman', 'aktif' => $aktif === 'pengumuman_kelola'];
        }
        if ($peran === 'admin') {
            $informasi[] = ['label' => 'Kelola FAQ',
                'path' => 'admin/faq', 'aktif' => $aktif === 'faq_kelola'];
        }
        $informasi[] = ['label' => 'Bantuan', 'path' => 'bantuan',
            'aktif' => $aktif === 'bantuan'];
        $informasi[] = ['label' => 'Hubungi Admin', 'path' => 'hubungi-admin',
            'aktif' => $aktif === 'hubungi_admin'];
        $menu[] = [
            'label' => 'Informasi & Bantuan',
            'ikon' => 'book-open',
            'aktif' => in_array($aktif,
                ['pengumuman', 'pengumuman_kelola', 'faq_kelola', 'bantuan', 'hubungi_admin'], true),
            'submenu' => $informasi,
        ];
        $menu[] = ['label' => 'Profil & peran', 'path' => 'profil', 'aktif' => $aktif === 'profil'];

        return $menu;
    }
}
