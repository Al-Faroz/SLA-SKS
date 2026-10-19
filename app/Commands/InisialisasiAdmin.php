<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\AuthService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DomainException;

final class InisialisasiAdmin extends BaseCommand
{
    protected $group = 'SLA-SKS';
    protected $name = 'sla:admin-awal';
    protected $description = 'Membuat satu Admin awal secara aman hanya dari terminal.';
    protected $usage = 'sla:admin-awal';

    /** @param list<string> $params */
    public function run(array $params)
    {
        if ($params !== []) {
            CLI::error('Jangan sertakan sandi atau parameter pada perintah.');
            return;
        }

        $username = trim((string) CLI::prompt('Username Admin awal (3-80 karakter)'));
        $nama = trim((string) CLI::prompt('Nama lengkap Admin'));
        $sandi = bin2hex(random_bytes(16));

        try {
            (new AuthService())->buatAdminAwal($username, $nama, $sandi);
        } catch (DomainException $e) {
            CLI::error($e->getMessage());
            return;
        } catch (\Throwable $e) {
            CLI::error('Inisialisasi gagal. Periksa database dan log aplikasi.');
            return;
        }

        CLI::write('Admin awal berhasil dibuat. Sandi sementara berikut muncul SEKALI:', 'green');
        CLI::write($sandi, 'yellow');
        CLI::write('Catat secara privat; masuk ke aplikasi dan langsung ganti sandi.', 'yellow');
    }
}
