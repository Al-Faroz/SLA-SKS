<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * YouTube saja, bukan URL embed bebas. Tidak melakukan fetch server-side
 * atau menerima host/HTML dari pengguna.
 */
final class VideoMateriService
{
    public static function idVideo(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 255 || preg_match('/[\x00-\x20\x7f]/', $url)) {
            throw new DomainException('Masukkan URL HTTPS video YouTube yang valid.');
        }
        $parts = parse_url($url);
        if ($parts === false
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || isset($parts['fragment'])) {
            throw new DomainException('URL YouTube harus HTTPS tanpa kredensial, port atau fragmen.');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $id = '';
        if ($host === 'youtu.be') {
            if (preg_match('~^/([a-zA-Z0-9_-]{11})/?$~D', $path, $m) !== 1) {
                throw new DomainException('Tautan video YouTube tidak dikenali.');
            }
            $id = $m[1];
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $id = $query['v'] ?? '';
            } elseif (preg_match('~^/(?:shorts|embed|live)/([a-zA-Z0-9_-]{11})/?$~D', $path, $m) === 1) {
                $id = $m[1];
            }
        }

        if (! is_string($id) || preg_match('/^[a-zA-Z0-9_-]{11}$/D', $id) !== 1) {
            throw new DomainException('Tautan harus mengarah ke satu video YouTube yang valid.');
        }

        return $id;
    }

    public static function urlEmbed(string $url): string
    {
        return 'https://www.youtube-nocookie.com/embed/' . self::idVideo($url);
    }

    public static function urlStandar(string $url): string
    {
        return 'https://www.youtube.com/watch?v=' . self::idVideo($url);
    }
}
