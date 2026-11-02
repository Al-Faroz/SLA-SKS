<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * Tautan Audio/Video soal: hanya Google Drive HTTPS dengan identitas file.
 * Ketersediaan / izin Viewer diuji Guru di halaman review sebelum konfirmasi;
 * server tanpa kredensial Drive tidak mengklaim bisa memverifikasi playback.
 */
final class SoalMediaDriveService
{
    /** @return list<array{jenis:string,url:string}> */
    public static function tautan(string $teks): array
    {
        $jumlahLabel = preg_match_all('/\b(Audio|Video)\s*:/iu', $teks);
        preg_match_all('~\b(Audio|Video)\s*:\s*(https?://[^\s<>"\x27]+)~iu',
            $teks, $daftar, PREG_SET_ORDER);
        if ($jumlahLabel !== count($daftar)) {
            throw new DomainException('Audio/Video soal harus diikuti tautan file Google Drive HTTPS.');
        }
        $hasil = [];
        foreach ($daftar as $item) {
            $raw = rtrim($item[2], ".,;)");
            $url = parse_url($raw);
            if ($url === false || strtolower((string) ($url['scheme'] ?? '')) !== 'https'
                || strtolower((string) ($url['host'] ?? '')) !== 'drive.google.com'
                || isset($url['user']) || isset($url['pass']) || isset($url['port'])) {
                throw new DomainException('Tautan Audio/Video harus berasal dari file Google Drive HTTPS.');
            }
            $path = (string) ($url['path'] ?? '');
            $id = null;
            if (preg_match('~^/file/d/([A-Za-z0-9_-]{10,})(?:/(?:view|preview))?/?$~D', $path, $match)) {
                $id = $match[1];
            } elseif (in_array($path, ['/open', '/uc'], true)) {
                parse_str((string) ($url['query'] ?? ''), $query);
                $id = $query['id'] ?? null;
            }
            if (!is_string($id) || preg_match('/^[A-Za-z0-9_-]{10,}$/D', $id) !== 1) {
                throw new DomainException('Gunakan tautan Google Drive langsung ke file audio/video, bukan folder atau tautan lain.');
            }
            $hasil[] = [
                'jenis' => ucfirst(strtolower($item[1])),
                'url' => 'https://drive.google.com/file/d/' . $id . '/view',
            ];
        }
        return $hasil;
    }
}
