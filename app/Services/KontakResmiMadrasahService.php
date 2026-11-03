<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * A13-3: hanya membaca kontak RESMI madrasah yang diisikan Admin.
 * Tidak mengambil telepon/email pribadi staf, tidak membuat tiket/pesan.
 */
final class KontakResmiMadrasahService
{
    private BaseConnection $db;
    /** @var callable():?array */
    private $identitas;

    public function __construct(?BaseConnection $db = null, ?callable $identitas = null)
    {
        $this->db = $db ?? db_connect();
        $this->identitas = $identitas ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    /**
     * @return array{peran:string,nama_pengguna:string,nama_madrasah:string,alamat:string,telepon:string,email:string,website:string,tautan_telepon:string,tautan_email:string,tautan_website:string}
     */
    public function ambil(): array
    {
        $akun = ($this->identitas)();
        if (! is_array($akun)
            || (int) ($akun['id'] ?? 0) < 1
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0
            || ! in_array($akun['peran_aktif'] ?? '', ['admin', 'guru', 'siswa', 'pimpinan'], true)) {
            throw new DomainException('Hanya akun aktif yang dapat melihat kontak resmi madrasah.');
        }

        $kunci = ['nama_madrasah', 'alamat_madrasah', 'telepon', 'email', 'website'];
        $nilai = array_fill_keys($kunci, '');
        $baris = $this->db->table('pengaturan')
            ->select('kunci, nilai')
            ->whereIn('kunci', $kunci)
            ->get()->getResultArray();
        foreach ($baris as $item) {
            if (array_key_exists((string) $item['kunci'], $nilai)) {
                $nilai[$item['kunci']] = trim((string) ($item['nilai'] ?? ''));
            }
        }

        // Data lama/keliru boleh tetap ditampilkan sebagai teks, tetapi tidak
        // boleh dijadikan href yang dapat mengeksekusi skema URL berbahaya.
        $telepon = $nilai['telepon'];
        $nomor = '';
        if (preg_match('/^[+0-9() .-]{5,40}$/D', $telepon)) {
            $normal = preg_replace('/[(). -]+/', '', $telepon);
            if (is_string($normal) && preg_match('/^\+?[0-9]{5,20}$/D', $normal)) {
                $nomor = 'tel:' . $normal;
            }
        }

        $email = $nilai['email'];
        $tautanEmail = filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            ? 'mailto:' . $email : '';

        $website = $nilai['website'];
        $skema = parse_url($website, PHP_URL_SCHEME);
        $tautanWebsite = is_string($skema)
            && in_array(strtolower($skema), ['http', 'https'], true)
            && filter_var($website, FILTER_VALIDATE_URL) !== false
            ? $website : '';

        return [
            'peran' => (string) $akun['peran_aktif'],
            'nama_pengguna' => (string) ($akun['nama'] ?? ''),
            'nama_madrasah' => $nilai['nama_madrasah'],
            'alamat' => $nilai['alamat_madrasah'],
            'telepon' => $telepon,
            'email' => $email,
            'website' => $website,
            'tautan_telepon' => $nomor,
            'tautan_email' => $tautanEmail,
            'tautan_website' => $tautanWebsite,
        ];
    }
}
