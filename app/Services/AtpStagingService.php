<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;
use RuntimeException;

/**
 * Berkas sementara ATP yang terikat akun, paket, sesi dan fingerprint DB.
 * Tidak menyimpan password/siswa; tak ada perubahan data akademik.
 */
final class AtpStagingService
{
    public const TTL = 900; // 15 menit

    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = rtrim($directory ?? WRITEPATH . 'private/atp_import', '/\\') . DIRECTORY_SEPARATOR;
    }

    private function siapkan(): void
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0700, true)
            && ! is_dir($this->directory)) {
            throw new RuntimeException('Direktori staging ATP tidak dapat dibuat.');
        }
        // Lindungi pada server Apache untuk struktur root CI4 milik proyek.
        $proteksi = $this->directory . '.htaccess';
        if (! is_file($proteksi)) {
            file_put_contents($proteksi, "Require all denied\nDeny from all\n", LOCK_EX);
        }
        $this->bersihkanKadaluarsa();
    }

    private function bersihkanKadaluarsa(): void
    {
        foreach (glob($this->directory . '*.json') ?: [] as $json) {
            if (filemtime($json) !== false && filemtime($json) < time() - self::TTL - 60) {
                $token = basename($json, '.json');
                if (self::validasiToken($token)) {
                    @unlink($json);
                    @unlink($this->directory . $token . '.xlsx');
                }
            }
        }
    }

    public static function validasiToken(string $token): bool
    {
        return preg_match('/\A[a-f0-9]{64}\z/D', $token) === 1;
    }

    /** @return array{token:string,kedaluwarsa:int} */
    public function buat(string $sumber, int $penggunaId, int $paketId,
        string $sesiId, string $fingerprint): array
    {
        if ($penggunaId < 1 || $paketId < 1 || $sesiId === ''
            || ! preg_match('/\A[a-f0-9]{64}\z/D', $fingerprint)
            || ! is_file($sumber) || filesize($sumber) > 5 * 1024 * 1024) {
            throw new DomainException('Sumber pratinjau ATP tidak valid.');
        }
        $this->siapkan();
        $token = bin2hex(random_bytes(32));
        $xls = $this->directory . $token . '.xlsx';
        $json = $this->directory . $token . '.json';
        if (! copy($sumber, $xls)) {
            throw new RuntimeException('Berkas sementara ATP tidak dapat disalin.');
        }
        @chmod($xls, 0600);
        try {
            $hash = hash_file('sha256', $xls);
            if ($hash === false || filesize($xls) === 0) {
                throw new RuntimeException('Checksum berkas ATP tidak dapat dihitung.');
            }
            $meta = [
                'pengguna_id' => $penggunaId, 'paket_id' => $paketId,
                'sesi_hash' => hash('sha256', $sesiId),
                'sha256' => $hash, 'fingerprint' => $fingerprint,
                'kedaluwarsa' => time() + self::TTL,
            ];
            $bytes = json_encode($meta, JSON_THROW_ON_ERROR);
            if (file_put_contents($json, $bytes, LOCK_EX) !== strlen($bytes)) {
                throw new RuntimeException('Metadata pratinjau ATP tidak tersimpan.');
            }
            @chmod($json, 0600);
        } catch (\Throwable $e) {
            @unlink($xls);
            @unlink($json);
            throw $e;
        }
        return ['token' => $token, 'kedaluwarsa' => $meta['kedaluwarsa']];
    }

    /** @return array{file:string,fingerprint:string,sha256:string} */
    public function ambil(string $token, int $penggunaId, int $paketId, string $sesiId): array
    {
        if (! self::validasiToken($token) || $penggunaId < 1 || $paketId < 1 || $sesiId === '') {
            throw new DomainException('Token pratinjau ATP tidak sah.');
        }
        $json = $this->directory . $token . '.json';
        $xls = $this->directory . $token . '.xlsx';
        if (! is_file($json) || ! is_file($xls) || is_link($json) || is_link($xls)) {
            throw new DomainException('Pratinjau sudah tidak tersedia. Unggah kembali file Excel.');
        }
        $raw = file_get_contents($json);
        $meta = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($meta)
            || (int) ($meta['pengguna_id'] ?? 0) !== $penggunaId
            || (int) ($meta['paket_id'] ?? 0) !== $paketId
            || ! hash_equals((string) ($meta['sesi_hash'] ?? ''), hash('sha256', $sesiId))
            || (int) ($meta['kedaluwarsa'] ?? 0) < time()) {
            throw new DomainException('Pratinjau kadaluarsa atau bukan milik akun/sesi Anda.');
        }
        $hash = hash_file('sha256', $xls);
        if ($hash === false || ! hash_equals((string) ($meta['sha256'] ?? ''), $hash)
            || ! preg_match('/\A[a-f0-9]{64}\z/D', (string) ($meta['fingerprint'] ?? ''))) {
            throw new DomainException('File pratinjau berubah. Unggah ulang Excel.');
        }
        return [
            'file' => $xls,
            'fingerprint' => (string) $meta['fingerprint'],
            'sha256' => $hash,
        ];
    }

    public function hapus(string $token): void
    {
        if (self::validasiToken($token)) {
            @unlink($this->directory . $token . '.json');
            @unlink($this->directory . $token . '.xlsx');
        }
    }
}
