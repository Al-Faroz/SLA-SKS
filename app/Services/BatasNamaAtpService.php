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
        foreach ($db->getFieldData($tabel) as $field) {
            if (($field->name ?? '') === $kolom) {
                $lebar = (int) ($field->max_length ?? 0);
                // Jika driver tidak melaporkan kapasitas VARCHAR dengan benar,
                // tetap konservatif agar form tidak menyebabkan truncation.
                return $lebar > 0 ? min(50, $lebar) : 35;
            }
        }
        return 35;
    }
}
