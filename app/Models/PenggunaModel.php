<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class PenggunaModel extends Model
{
    protected $table = 'pengguna';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'username', 'nama', 'sandi_hash', 'status', 'wajib_ganti_sandi',
        'jumlah_gagal', 'terkunci_sampai', 'versi_sesi', 'login_terakhir',
    ];
    protected $useTimestamps = false;
}
