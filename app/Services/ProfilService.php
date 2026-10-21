<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * U02/U02S/U02G — data profil privat, foto dengan persetujuan, dan proyeksi Guru per Gen.
 * Tidak mengubah nama, NIP/username, peran, Gen atau data autentikasi.
 */
final class ProfilService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private function akun(): array
    {
        $akun = (new AuthService($this->db))->penggunaSaatIni();
        if ($akun === null || (int) $akun['wajib_ganti_sandi'] !== 0) {
            throw new DomainException('Sesi tidak berlaku. Masuk dan ganti sandi dahulu bila diwajibkan.');
        }

        return $akun;
    }

    private function staf(): array
    {
        $akun = $this->akun();
        if ($akun['peran_aktif'] === 'siswa') {
            throw new DomainException('Hanya akun Pengelola yang boleh mengubah kontak dan foto sendiri.');
        }

        return $akun;
    }

    private function admin(): array
    {
        $akun = $this->akun();
        if ($akun['peran_aktif'] !== 'admin') {
            throw new DomainException('Persetujuan foto hanya dapat dilakukan Admin aktif.');
        }
        return $akun;
    }

    /** @return array<string,mixed> */
    public function profilSendiri(): array
    {
        $akun = $this->akun();
        $result = [
            'id' => (int) $akun['id'],
            'nama' => (string) $akun['nama'],
            'username' => (string) $akun['username'],
            'peran_aktif' => (string) $akun['peran_aktif'],
            'daftar_peran' => $akun['daftar_peran'],
            'telepon_pribadi' => null,
            'foto_aktif_id' => null,
            'foto_menunggu_id' => null,
            'gen' => null,
            'semester_mulai' => null,
            'guru_gen' => [],
        ];

        if ($akun['peran_aktif'] !== 'siswa') {
            $profil = $this->db->table('profil_pengelola')
                ->select('telepon_pribadi,foto_aset_id,foto_menunggu_id')
                ->where('pengguna_id', (int) $akun['id'])->get()->getRowArray();
            if ($profil !== null) {
                $result['telepon_pribadi'] = $profil['telepon_pribadi'];
                $result['foto_aktif_id'] = $profil['foto_aset_id'];
                $result['foto_menunggu_id'] = $profil['foto_menunggu_id'];
            }
            return $result;
        }

        $siswa = $this->db->table('siswa s')
            ->select('s.angkatan_id,s.semester_mulai,a.nama AS gen_nama,a.kode AS gen_kode,a.semester_mulai AS gen_semester')
            ->join('ref_angkatan a', 'a.id=s.angkatan_id', 'left')
            ->where('s.pengguna_id', (int) $akun['id'])->get()->getRowArray();
        if ($siswa === null) {
            return $result;
        }
        $result['gen'] = $siswa['gen_nama'];
        $result['gen_kode'] = $siswa['gen_kode'];
        $result['semester_mulai'] = $siswa['semester_mulai'] ?? $siswa['gen_semester'];

        if ($siswa['angkatan_id'] === null) {
            return $result;
        }

        // Relasi Gen–Mapel merupakan satu-satunya dasar profil Guru yang terlihat siswa.
        $rows = $this->db->table('penugasan_guru_gen pg')
            ->select('g.id,g.nama,m.nama AS mapel_nama,pr.foto_aset_id')
            ->join('pengguna g', "g.id=pg.guru_id AND g.status='aktif'")
            ->join('pengguna_peran gp', "gp.pengguna_id=g.id AND gp.peran='guru'")
            ->join('ref_mapel m', 'm.id=pg.mapel_id')
            ->join('profil_pengelola pr', 'pr.pengguna_id=g.id', 'left')
            ->where('pg.angkatan_id', (int) $siswa['angkatan_id'])
            ->orderBy('g.nama')->orderBy('m.nama')
            ->get()->getResultArray();

        $guru = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (! isset($guru[$id])) {
                $guru[$id] = [
                    'id' => $id, 'nama' => $row['nama'],
                    'foto_id' => $row['foto_aset_id'],
                    'mapel' => [],
                ];
            }
            $guru[$id]['mapel'][] = $row['mapel_nama'];
        }
        $result['guru_gen'] = array_values($guru);

        return $result;
    }

    public static function teleponValid(string $nomor): bool
    {
        return $nomor === '' || (
            strlen($nomor) <= 25
            && preg_match('/^\+?[0-9][0-9 -]{5,23}[0-9]$/D', $nomor) === 1
        );
    }

    private function catat(int $id, string $role, string $aksi, string $catatan): void
    {
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => $id,
            'peran_aktif' => $role,
            'peristiwa' => $aksi,
            'catatan' => $catatan,
            'alamat_ip' => service('request')->getIPAddress(),
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function ubahKontak(string $telepon): void
    {
        $akun = $this->staf();
        $telepon = trim($telepon);
        if (! self::teleponValid($telepon)) {
            throw new DomainException('Telepon hanya boleh berisi angka, spasi, tanda hubung dan awalan + (7–25 karakter).');
        }
        $id = (int) $akun['id'];

        $this->db->transBegin();
        try {
            // Kunci akun agar perubahan profil terkait dengan pengguna yang sama.
            $this->db->query('SELECT id FROM pengguna WHERE id=? FOR UPDATE', [$id]);
            $profil = $this->db->table('profil_pengelola')->where('pengguna_id', $id)
                ->get()->getRowArray();
            $nilai = $telepon === '' ? null : $telepon;
            if ($profil !== null && $profil['telepon_pribadi'] === $nilai) {
                throw new DomainException('Nomor kontak belum berubah.');
            }
            $data = ['telepon_pribadi' => $nilai, 'diperbarui_pada' => gmdate('Y-m-d H:i:s')];
            if ($profil === null) {
                $this->db->table('profil_pengelola')->insert(['pengguna_id' => $id] + $data);
            } else {
                $this->db->table('profil_pengelola')->where('pengguna_id', $id)->update($data);
            }
            $this->catat($id, $akun['peran_aktif'], 'kontak_pribadi_diubah', 'Kontak pribadi akun sendiri diperbarui.');
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Kontak tidak berhasil disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public const MAKS_FOTO_BYTE = 2097152;
    public const MAKS_SISI = 1600;
    public const MIN_SISI = 100;

    /** @return array{mime:string,lebar:int,tinggi:int,ekstensi:string} */
    private static function validasiGambar(UploadedFile $file): array
    {
        if (! $file->isValid() || $file->getSize() < 100
            || $file->getSize() > self::MAKS_FOTO_BYTE) {
            throw new DomainException('Foto maksimal 2 MB. Pilih gambar JPG/PNG yang valid.');
        }
        $ext = strtolower((string) $file->getClientExtension());
        if (! in_array($ext, ['jpg','jpeg','png'], true)) {
            throw new DomainException('Foto hanya boleh berekstensi JPG atau PNG.');
        }
        $temp = $file->getTempName();
        $info = @getimagesize($temp);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temp);
        if ($info === false || ! in_array($info['mime'] ?? '', ['image/jpeg','image/png'], true)
            || $mime !== $info['mime']) {
            throw new DomainException('Konten gambar tidak sesuai dengan format JPG/PNG.');
        }
        if (($info['mime'] === 'image/jpeg' && ! in_array($ext, ['jpg','jpeg'], true))
            || ($info['mime'] === 'image/png' && $ext !== 'png')) {
            throw new DomainException('Ekstensi foto dan isi gambar tidak cocok.');
        }
        $width = (int) $info[0];
        $height = (int) $info[1];
        if ($width < self::MIN_SISI || $height < self::MIN_SISI
            || $width > self::MAKS_SISI || $height > self::MAKS_SISI) {
            throw new DomainException('Resolusi foto harus antara 100–1600 piksel pada tiap sisi.');
        }
        return ['mime' => $mime, 'lebar' => $width, 'tinggi' => $height,
            'ekstensi' => $mime === 'image/png' ? 'png' : 'jpg'];
    }

    public function unggahFoto(UploadedFile $file): void
    {
        $akun = $this->staf();
        $gambar = self::validasiGambar($file);
        $dir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' .
            DIRECTORY_SEPARATOR . 'profil';
        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException('Folder privat foto tidak dapat dibuat.');
        }
        $nama = bin2hex(random_bytes(16)) . '.' . $gambar['ekstensi'];
        $tujuan = $dir . DIRECTORY_SEPARATOR . $nama;
        $isi = file_get_contents($file->getTempName());
        $image = is_string($isi) ? @imagecreatefromstring($isi) : false;
        unset($isi);
        if ($image === false) {
            throw new DomainException('Foto tidak dapat diproses. Pilih JPG atau PNG lain.');
        }

        try {
            // Re-encode menghilangkan metadata EXIF/komponen aktif.
            $ok = $gambar['mime'] === 'image/png'
                ? imagepng($image, $tujuan, 6)
                : imagejpeg($image, $tujuan, 85);
        } finally {
            imagedestroy($image);
        }
        if (! $ok || ! is_file($tujuan)) {
            @unlink($tujuan);
            throw new RuntimeException('Foto gagal disimpan ke folder privat.');
        }
        @chmod($tujuan, 0600);

        $id = (int) $akun['id'];
        $this->db->transBegin();
        try {
            $this->db->query('SELECT id FROM pengguna WHERE id=? FOR UPDATE', [$id]);
            $profil = $this->db->table('profil_pengelola')->where('pengguna_id', $id)
                ->get()->getRowArray();
            $this->db->table('foto_profil')->insert([
                'pengguna_id' => $id,
                'nama_berkas' => $nama,
                'mime' => $gambar['mime'],
                'ukuran_byte' => filesize($tujuan),
                'lebar' => $gambar['lebar'], 'tinggi' => $gambar['tinggi'],
                'status' => 'menunggu',
                'dibuat_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $fotoId = (int) $this->db->insertID();
            if ($profil === null) {
                $this->db->table('profil_pengelola')->insert([
                    'pengguna_id' => $id, 'foto_menunggu_id' => $fotoId,
                    'diperbarui_pada' => gmdate('Y-m-d H:i:s'),
                ]);
            } else {
                $this->db->table('profil_pengelola')->where('pengguna_id', $id)->update([
                    'foto_menunggu_id' => $fotoId,
                    'diperbarui_pada' => gmdate('Y-m-d H:i:s'),
                ]);
            }
            $this->catat($id, $akun['peran_aktif'], 'foto_profil_diunggah',
                'Foto profil akun sendiri diunggah dan menunggu persetujuan; ID ' . $fotoId);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Metadata foto tidak berhasil disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            @unlink($tujuan);
            throw $e;
        }
    }

    /** @return list<array<string,mixed>> */
    public function daftarFotoMenunggu(): array
    {
        $this->admin();
        return $this->db->table('profil_pengelola pr')
            ->select('f.id,f.pengguna_id,f.dibuat_pada,p.nama')
            ->join('foto_profil f', 'f.id=pr.foto_menunggu_id')
            ->join('pengguna p', 'p.id=pr.pengguna_id')
            ->where('f.status', 'menunggu')
            ->orderBy('f.dibuat_pada', 'ASC')
            ->get(100)->getResultArray();
    }

    public function setujuiFoto(int $fotoId): void
    {
        $admin = $this->admin();
        if ($fotoId < 1) {
            throw new DomainException('Foto tidak ditemukan.');
        }
        $this->db->transBegin();
        try {
            $foto = $this->db->query(
                'SELECT id,pengguna_id,status FROM foto_profil WHERE id=? FOR UPDATE', [$fotoId]
            )->getRowArray();
            if ($foto === null || $foto['status'] !== 'menunggu') {
                throw new DomainException('Foto sudah diproses atau tidak ditemukan.');
            }
            $profil = $this->db->table('profil_pengelola')
                ->where('pengguna_id', (int) $foto['pengguna_id'])->get()->getRowArray();
            if ($profil === null || (int) $profil['foto_menunggu_id'] !== $fotoId) {
                throw new DomainException('Foto ini bukan foto yang sedang menunggu persetujuan.');
            }

            $this->db->table('foto_profil')->where('id', $fotoId)->update([
                'status' => 'disetujui', 'disetujui_oleh' => (int) $admin['id'],
                'disetujui_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->db->table('profil_pengelola')
                ->where('pengguna_id', (int) $foto['pengguna_id'])
                ->where('foto_menunggu_id', $fotoId)->update([
                    'foto_aset_id' => $fotoId, 'foto_menunggu_id' => null,
                    'diperbarui_pada' => gmdate('Y-m-d H:i:s'),
                ]);
            if ($this->db->affectedRows() !== 1) {
                // Pemilik dapat mengunggah foto lebih baru saat Admin meninjau.
                throw new DomainException('Ada unggahan foto lebih baru. Muat ulang daftar persetujuan.');
            }
            $this->catat((int) $admin['id'], 'admin', 'foto_profil_disetujui',
                'Foto profil ID ' . $fotoId . ' milik pengguna ID ' . $foto['pengguna_id'] . ' disetujui.');
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Persetujuan foto gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Hanya Admin aktif, pemilik foto, atau Siswa dari Gen yang diampu Guru aktif
     * dapat melihat foto terakhir yang DISETUJUI. Foto menunggu tidak dapat dilihat Siswa.
     * @return array{mime:string,path:string}
     */
    public function mediaFoto(int $fotoId): array
    {
        $akun = $this->akun();
        if ($fotoId < 1) {
            throw new DomainException('Foto tidak tersedia.');
        }
        $foto = $this->db->table('foto_profil f')
            ->select('f.id,f.pengguna_id,f.nama_berkas,f.mime,f.status,pr.foto_aset_id,pr.foto_menunggu_id')
            ->join('profil_pengelola pr', 'pr.pengguna_id=f.pengguna_id')
            ->where('f.id', $fotoId)->get()->getRowArray();
        if ($foto === null) {
            throw new DomainException('Foto tidak tersedia.');
        }

        $pemilik = (int) $akun['id'] === (int) $foto['pengguna_id'];
        $admin = $akun['peran_aktif'] === 'admin';
        $disetujui = $foto['status'] === 'disetujui'
            && (int) $foto['foto_aset_id'] === $fotoId;
        $guruSiswa = false;

        if (! $pemilik && ! $admin && $akun['peran_aktif'] === 'siswa' && $disetujui) {
            $tugas = $this->db->table('siswa s')
                ->join('penugasan_guru_gen pg', 'pg.angkatan_id=s.angkatan_id')
                ->join('pengguna g', "g.id=pg.guru_id AND g.status='aktif'")
                ->join('pengguna_peran gr', "gr.pengguna_id=g.id AND gr.peran='guru'")
                ->where('s.pengguna_id', (int) $akun['id'])
                ->where('g.id', (int) $foto['pengguna_id'])
                ->countAllResults();
            $guruSiswa = $tugas > 0;
        }
        if (! $pemilik && ! $admin && ! $guruSiswa) {
            throw new DomainException('Anda tidak memiliki izin melihat foto ini.');
        }
        if (! $admin && ! $pemilik && ! $disetujui) {
            throw new DomainException('Foto belum disetujui.');
        }
        // Cegah file orphan dari histori menjadi endpoint publik untuk pemilik.
        if ($pemilik && (int) $foto['foto_aset_id'] !== $fotoId
            && (int) $foto['foto_menunggu_id'] !== $fotoId) {
            throw new DomainException('Foto bukan foto profil yang digunakan.');
        }

        $name = (string) $foto['nama_berkas'];
        if (preg_match('/^[a-f0-9]{32}\.(jpg|png)$/D', $name) !== 1) {
            throw new DomainException('Nama aset foto tidak valid.');
        }
        $path = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'profil' . DIRECTORY_SEPARATOR . $name;
        if (! is_file($path) || ! is_readable($path)) {
            throw new DomainException('Berkas foto belum tersedia di penyimpanan privat.');
        }

        return ['mime' => (string) $foto['mime'], 'path' => $path];
    }
}
