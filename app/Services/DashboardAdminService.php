<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Data ringkas dashboard Admin dibaca langsung dari database, tanpa angka contoh.
 * Tidak menambahkan aturan akademik maupun status pembelajaran palsu.
 */
final class DashboardAdminService
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    /** @return array{gen:int,mapel:int,guru_ditugaskan:int,belum_ditugaskan:int} */
    public function ringkasan(): array
    {
        if (! (new AuthService())->izinkan('admin')) {
            throw new DomainException('Hanya Admin aktif yang dapat membaca ringkasan akademik.');
        }

        $gen = $this->db->table('ref_angkatan')->countAllResults();
        $mapel = $this->db->table('ref_mapel')->countAllResults();
        $ditugaskan = $this->db->table('penugasan_guru_gen')
            ->where('guru_id IS NOT NULL', null, false)
            ->countAllResults();

        return [
            'gen' => $gen,
            'mapel' => $mapel,
            'guru_ditugaskan' => $ditugaskan,
            'belum_ditugaskan' => max(0, ($gen * $mapel) - $ditugaskan),
        ];
    }
}
