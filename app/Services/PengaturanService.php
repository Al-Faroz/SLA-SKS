<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

final class PengaturanService
{
    // Semua nilai merupakan teks identitas yang disahkan/diisi oleh Admin.
    public const IDENTITAS = [
        'nama_madrasah' => 'Nama madrasah',
        'alamat_madrasah' => 'Alamat madrasah',
        'npsn' => 'NPSN',
        'nsm' => 'NSM',
        'telepon' => 'Telepon',
        'email' => 'Email madrasah',
        'website' => 'Situs web (opsional)',
        'kota_penerbitan' => 'Kota penerbitan sertifikat',
        'kepala_madrasah_nama' => 'Nama kepala madrasah',
        'kepala_madrasah_nip' => 'NIP kepala madrasah',
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /** @return array<string,string> */
    public static function validasiIdentitas(array $input): array
    {
        $galat = [];
        foreach (self::IDENTITAS as $kunci => $label) {
            $nilai = $input[$kunci] ?? null;
            if (! is_string($nilai)) {
                $galat[$kunci] = $label . ' harus berupa teks.';
                continue;
            }
            $nilai = trim($nilai);
            if ($kunci !== 'website' && $nilai === '') {
                $galat[$kunci] = $label . ' wajib diisi.';
            } elseif (mb_strlen($nilai) > ($kunci === 'alamat_madrasah' ? 1000 : 200)) {
                $galat[$kunci] = $label . ' terlalu panjang.';
            }
        }

        if (isset($input['npsn']) && is_string($input['npsn']) && trim($input['npsn']) !== ''
            && ! preg_match('/^[0-9]{8}$/D', trim($input['npsn']))) {
            $galat['npsn'] = 'NPSN harus terdiri dari 8 angka.';
        }
        if (isset($input['nsm']) && is_string($input['nsm']) && trim($input['nsm']) !== ''
            && ! preg_match('/^[0-9]{12}$/D', trim($input['nsm']))) {
            $galat['nsm'] = 'NSM harus terdiri dari 12 angka.';
        }
        if (isset($input['email']) && is_string($input['email']) && trim($input['email']) !== ''
            && ! filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL)) {
            $galat['email'] = 'Isi alamat email madrasah yang valid.';
        }
        if (isset($input['telepon']) && is_string($input['telepon']) && trim($input['telepon']) !== ''
            && ! preg_match('/^[+0-9() .-]{5,40}$/D', trim($input['telepon']))) {
            $galat['telepon'] = 'Nomor telepon hanya boleh berisi angka dan tanda telepon umum.';
        }
        if (isset($input['website']) && is_string($input['website']) && trim($input['website']) !== '') {
            $url = trim($input['website']);
            $skema = parse_url($url, PHP_URL_SCHEME);
            if (! in_array($skema, ['http', 'https'], true)
                || ! filter_var($url, FILTER_VALIDATE_URL)) {
                $galat['website'] = 'Situs web harus berupa alamat http:// atau https:// yang valid.';
            }
        }

        return $galat;
    }

    private function adminId(): int
    {
        $akun = (new AuthService())->penggunaSaatIni();
        if ($akun === null || $akun['peran_aktif'] !== 'admin'
            || (int) $akun['wajib_ganti_sandi'] !== 0) {
            throw new DomainException('Hanya Admin aktif yang dapat mengubah identitas madrasah.');
        }

        return (int) $akun['id'];
    }

    /** @return array<string,string> */
    public function ambilIdentitas(): array
    {
        $this->adminId();
        $hasil = array_fill_keys(array_keys(self::IDENTITAS), '');
        $rows = $this->db->table('pengaturan')
            ->select('kunci,nilai')
            ->whereIn('kunci', array_keys(self::IDENTITAS))
            ->get()->getResultArray();

        foreach ($rows as $row) {
            $hasil[$row['kunci']] = (string) ($row['nilai'] ?? '');
        }

        return $hasil;
    }

    public function simpanIdentitas(array $masukan): void
    {
        $adminId = $this->adminId();
        $galat = self::validasiIdentitas($masukan);
        if ($galat !== []) {
            throw new DomainException(reset($galat));
        }

        $baru = [];
        foreach (self::IDENTITAS as $kunci => $label) {
            $baru[$kunci] = trim($masukan[$kunci]);
        }

        $this->db->transBegin();
        try {
            // Kunci semua baris identitas sehingga audit perubahan konsisten.
            $kunci = array_keys(self::IDENTITAS);
            $penanda = implode(',', array_fill(0, count($kunci), '?'));
            $baris = $this->db->query(
                'SELECT kunci, nilai FROM pengaturan WHERE kunci IN (' . $penanda . ') ORDER BY kunci FOR UPDATE',
                $kunci
            )->getResultArray();
            if (count($baris) !== count($kunci)) {
                throw new RuntimeException('Pengaturan madrasah belum lengkap. Periksa impor SQL K2.');
            }
            $sebelum = array_fill_keys(array_keys(self::IDENTITAS), '');
            foreach ($baris as $item) {
                $sebelum[$item['kunci']] = (string) ($item['nilai'] ?? '');
            }
            if ($sebelum === $baru) {
                throw new DomainException('Tidak ada perubahan identitas untuk disimpan.');
            }

            $waktu = gmdate('Y-m-d H:i:s');
            foreach ($baru as $kunci => $nilai) {
                $this->db->table('pengaturan')->where('kunci', $kunci)->update([
                    'nilai' => $nilai === '' ? null : $nilai,
                    'diperbarui_oleh' => $adminId,
                    'diperbarui_pada' => $waktu,
                ]);
            }

            $this->db->table('riwayat_identitas_madrasah')->insert([
                'admin_id' => $adminId,
                'data_sebelum' => json_encode($sebelum, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'data_sesudah' => json_encode($baru, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'waktu' => $waktu,
            ]);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => $adminId,
                'peran_aktif' => 'admin',
                'peristiwa' => 'identitas_madrasah_diubah',
                'catatan' => 'Riwayat disimpan pada riwayat_identitas_madrasah.',
                'waktu' => $waktu,
            ]);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Identitas tidak dapat disimpan. Periksa koneksi database.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
