<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Batas input mengikuti kolom MySQL/MariaDB yang benar-benar sudah dipasang.
 * Kontrak baru 50; database R2 lama tetap VARCHAR(35) hingga patch impor.
 */
final class BatasNamaAtpService
{
    public static function aktual(BaseConnection $db, string $entitas): int
    {
        [$tabel, $kolom] = match ($entitas) {
            'bab' => ['bab', 'nama_bab'],
            'tp' => ['tp', 'nama_tp'],
            default => [null, null],
        };
        if ($tabel === null || ! $db->tableExists($tabel)) {
            return 35;
        }
        // CHARACTER_MAXIMUM_LENGTH adalah kapasitas karakter, bukan ukuran
        // byte UTF-8 dari field metadata driver (yang dapat 4x lebih besar).
        $baris = $db->query(
            'SELECT CHARACTER_MAXIMUM_LENGTH AS batas FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tabel, $kolom]
        )->getRowArray();
        $lebar = (int) ($baris['batas'] ?? 0);
        // Default aman untuk skema lama atau metadata yang belum tersedia.
        return $lebar > 0 ? min(50, $lebar) : 35;
    }
}
