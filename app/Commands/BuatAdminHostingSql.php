<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\AdminHostingSqlService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Membuat berkas SQL bootstrap Admin produksi TANPA koneksi database lokal.
 * Berkas privat hanya untuk diimpor ke database v5 di hosting lewat phpMyAdmin.
 */
final class BuatAdminHostingSql extends BaseCommand
{
    protected $group = 'SLA-SKS';
    protected $name = 'sla:admin-hosting-sql';
    protected $description = 'Buat SQL privat Admin hosting v5 tanpa mengubah DB XAMPP.';
    protected $usage = 'sla:admin-hosting-sql';

    /** @param list<string> $params */
    public function run(array $params)
    {
        if (! is_cli() || $params !== []) {
            CLI::error('Jalankan hanya lewat terminal tanpa parameter: php spark sla:admin-hosting-sql');
            return;
        }

        $username = trim((string) CLI::prompt('Username Admin produksi (3-80 karakter)'));
        $nama = trim((string) CLI::prompt('Nama lengkap Admin produksi'));
        $sandiSementara = bin2hex(random_bytes(16));
        $hasil = null;

        try {
            $sql = AdminHostingSqlService::buat(
                $username,
                $nama,
                password_hash($sandiSementara, PASSWORD_DEFAULT)
            );
            $direktori = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR
                . 'private' . DIRECTORY_SEPARATOR . 'setup';

            if (! is_dir($direktori) && ! mkdir($direktori, 0700, true) && ! is_dir($direktori)) {
                throw new \RuntimeException('Direktori privat tidak dapat dibuat.');
            }
            if (is_link($direktori)) {
                throw new \RuntimeException('Direktori privat tidak boleh berupa symlink.');
            }

            $denyPath = $direktori . DIRECTORY_SEPARATOR . '.htaccess';
            $deny = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
            if (! file_exists($denyPath) && file_put_contents($denyPath, $deny, LOCK_EX) === false) {
                throw new \RuntimeException('Perlindungan direktori tidak dapat dibuat.');
            }
            $terpasang = file_get_contents($denyPath);
            if (! is_string($terpasang) || ! str_contains($terpasang, 'Require all denied')) {
                throw new \RuntimeException('Perlindungan direktori tidak memenuhi syarat.');
            }

            $hasil = $direktori . DIRECTORY_SEPARATOR
                . 'admin_produksi_' . bin2hex(random_bytes(8)) . '.sql';
            $handle = fopen($hasil, 'x');
            if ($handle === false) {
                throw new \RuntimeException('Berkas SQL privat tidak dapat dibuat.');
            }
            try {
                $tertulis = 0;
                $panjang = strlen($sql);
                while ($tertulis < $panjang) {
                    $n = fwrite($handle, substr($sql, $tertulis));
                    if ($n === false || $n === 0) {
                        throw new \RuntimeException('Berkas SQL privat tidak selesai ditulis.');
                    }
                    $tertulis += $n;
                }
                fflush($handle);
            } finally {
                fclose($handle);
            }
            @chmod($hasil, 0600);
        } catch (Throwable $e) {
            if ($hasil !== null && is_file($hasil)) {
                @unlink($hasil);
            }
            CLI::error('Gagal membuat SQL privat: ' . $e->getMessage());
            return;
        }

        CLI::write('SQL privat berhasil dibuat (BELUM diimpor):', 'green');
        CLI::write((string) $hasil);
        CLI::write('Sandi sementara Admin produksi muncul SEKALI. Catat secara privat:', 'yellow');
        CLI::write($sandiSementara, 'yellow');
        CLI::write('Impor SQL hanya ke database hosting v5 melalui phpMyAdmin, lalu HAPUS berkas SQL privat.', 'yellow');
        CLI::write('Admin wajib ganti sandi pertama kali. Jangan unggah SQL/password ke GitHub atau obrolan.', 'yellow');
    }
}
