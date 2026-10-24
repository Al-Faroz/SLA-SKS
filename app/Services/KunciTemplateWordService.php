<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Kunci HMAC khusus template Word, tersimpan privat dan persisten.
 * Tidak bergantung pada perubahan .env atau kunci sesi.
 */
final class KunciTemplateWordService
{
    public static function ambil(): string
    {
        $direktori = WRITEPATH . 'private/';
        if (! is_dir($direktori) && ! @mkdir($direktori, 0700, true) && ! is_dir($direktori)) {
            throw new RuntimeException('Penyimpanan kunci Word tidak dapat dibuat.');
        }

        $pelindung = $direktori . '.htaccess';
        if (! is_file($pelindung) && @file_put_contents(
            $pelindung,
            "Require all denied\nDeny from all\n",
            LOCK_EX
        ) === false) {
            throw new RuntimeException('Pelindung penyimpanan privat tidak dapat dibuat.');
        }

        $lokasi = $direktori . 'word_hmac.key';
        $file = @fopen($lokasi, 'c+b');
        if ($file === false) {
            throw new RuntimeException('Penyimpanan kunci Word tidak dapat diakses.');
        }

        try {
            @chmod($lokasi, 0600);
            if (! flock($file, LOCK_EX)) {
                throw new RuntimeException('Kunci Word sedang tidak dapat dikunci.');
            }
            $kunci = stream_get_contents($file);
            if ($kunci === false) {
                throw new RuntimeException('Kunci Word gagal dibaca.');
            }
            if ($kunci === '') {
                $kunci = random_bytes(32);
                if (fwrite($file, $kunci) !== 32 || ! fflush($file)) {
                    throw new RuntimeException('Kunci Word gagal disimpan.');
                }
            }
            if (strlen($kunci) !== 32) {
                throw new RuntimeException('Kunci Word tidak valid; jangan ditimpa karena template lama dapat menjadi tidak sah.');
            }
            return $kunci;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
