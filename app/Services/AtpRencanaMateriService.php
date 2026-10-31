<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * ATP Excel V25-3 menyimpan satu rencana Materi per TP baru.
 * Ini DRAF akademik, bukan unggahan file atau izin membuka Materi siswa.
 * Guru tetap wajib melengkapi URL/file, waktu dan petunjuk di editor.
 */
final class AtpRencanaMateriService
{
    /**
     * @param array<string,mixed> $tp
     * @return array<string,int|string|null>
     */
    public static function draf(int $tpId, array $tp): array
    {
        $judul = trim((string) ($tp['materi'] ?? ''));
        $media = (string) ($tp['media'] ?? '');
        $jenis = match ($media) {
            'YouTube' => 'youtube',
            'PDF' => 'pdf',
            'Audio' => 'audio',
            'Gambar' => 'gambar',
            default => throw new DomainException('Jenis media pada rencana ATP tidak valid.'),
        };
        if ($tpId < 1 || $judul === '' || mb_strlen($judul) > 180) {
            throw new DomainException('Rencana Materi ATP harus memiliki judul 1–180 karakter dan TP yang sah.');
        }
        return [
            'tp_id' => $tpId,
            'row_uuid' => bin2hex(random_bytes(16)),
            'urutan' => 1,
            'judul' => $judul,
            'jenis' => $jenis,
            'petunjuk_resume' => '',
            'petunjuk_hots' => null,
            'min_waktu_detik' => 0,
            'url_youtube' => null,
            'berkas_id' => null,
            'aktif' => 0,
        ];
    }
}
