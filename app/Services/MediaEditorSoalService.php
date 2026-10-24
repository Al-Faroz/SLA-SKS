<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;
use RuntimeException;

/** Lampiran gambar editor disimpan privat per TP, bukan base64 pada DB. */
final class MediaEditorSoalService
{
    private static function dir(int $tpId): string
    {
        $dir = WRITEPATH . 'private/soal_media/' . $tpId . '/';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Folder gambar soal tidak dapat dibuat.');
        }
        foreach ([WRITEPATH . 'private/soal_media/.htaccess', $dir . '.htaccess'] as $deny) {
            if (!is_file($deny)
                && @file_put_contents($deny, "Require all denied\nDeny from all\n", LOCK_EX) === false) {
                throw new RuntimeException('Proteksi akses gambar tidak dapat dibuat.');
            }
        }
        return $dir;
    }

    /** @return array{id:string,size:int,url:string} */
    public static function unggah(int $tpId, string $temp): array
    {
        $r = (new DetailTpService())->lihat($tpId);
        if ($r['tp']['status'] !== 'draf') {
            throw new DomainException('Gambar hanya dapat ditambahkan pada TP Draf.');
        }
        $size = is_file($temp) ? filesize($temp) : false;
        if ($size === false || $size < 1 || $size > 2 * 1024 * 1024) {
            throw new DomainException('Gambar maksimal 2 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temp);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new DomainException('Gambar harus PNG atau JPEG.');
        }
        $info = @getimagesize($temp);
        if ($info === false || $info[0] < 1 || $info[1] < 1
            || $info[0] > 3000 || $info[1] > 3000) {
            throw new DomainException('Resolusi gambar maksimal 3000 × 3000 piksel.');
        }
        $id = bin2hex(random_bytes(16));
        $suffix = $mime === 'image/png' ? '.png' : '.jpg';
        $target = self::dir($tpId) . $id . $suffix;
        if (!copy($temp, $target)) {
            throw new RuntimeException('Gambar gagal disimpan.');
        }
        @chmod($target, 0600);
        return ['id' => $id, 'size' => (int) $size,
            'url' => site_url('guru/tp/' . $tpId . '/bank-soal/aset/' . $id)];
    }

    /** @return array{mime:string,data:string} */
    public static function baca(int $tpId, string $id): array
    {
        (new DetailTpService())->lihat($tpId);
        if (!preg_match('/^[0-9a-f]{32}$/D', $id)) {
            throw new DomainException('ID gambar tidak valid.');
        }
        $dir = self::dir($tpId);
        $file = is_file($dir . $id . '.png') ? $dir . $id . '.png' : $dir . $id . '.jpg';
        if (!is_file($file)) {
            throw new DomainException('Gambar tidak tersedia.');
        }
        $data = file_get_contents($file);
        if (!is_string($data) || $data === '') {
            throw new DomainException('Gambar tidak dapat dibaca.');
        }
        return ['mime' => str_ends_with($file, '.png') ? 'image/png' : 'image/jpeg', 'data' => $data];
    }
}
