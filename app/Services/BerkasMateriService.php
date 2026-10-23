<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Penyimpanan media Materi privat, khusus TP draf milik Guru G1.
 * Berkas lama tidak dihapus untuk melindungi referensi historis.
 */
final class BerkasMateriService
{
    private BaseConnection $db;
    private DetailTpService $detail;
    private string $folder;

    /** @param (callable():?array)|null $penggunaSaatIni */
    public function __construct(?BaseConnection $db = null, ?callable $penggunaSaatIni = null, ?string $folder = null)
    {
        $this->db = $db ?? db_connect();
        $this->detail = new DetailTpService($this->db, $penggunaSaatIni);
        $this->folder = $folder ?? rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'materi';
    }

    /**
     * @return array{mime:string,ext:string,bytes:int,halaman:?int,durasi_detik:?int}
     */
    public static function validasi(string $path, string $jenis, string $originalName): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new DomainException('Berkas tidak dapat dibaca.');
        }
        $size = filesize($path);
        if ($size === false || $size < 16) {
            throw new DomainException('Berkas kosong atau rusak.');
        }
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (! is_string($mime)) {
            throw new DomainException('Format berkas tidak dapat dikenali.');
        }

        if ($jenis === 'gambar') {
            if ($size > 10 * 1024 * 1024 || ! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)
                || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw new DomainException('Gambar hanya JPG, PNG atau WebP, maksimal 10 MB.');
            }
            $info = @getimagesize($path);
            if ($info === false || ($info['mime'] ?? '') !== $mime
                || $info[0] < 1 || $info[1] < 1 || $info[0] > 8000 || $info[1] > 8000
                || $info[0] * $info[1] > 20000000) {
                throw new DomainException('Dimensi atau isi gambar tidak valid.');
            }
            return ['mime' => $mime, 'ext' => $mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/png' ? 'png' : 'webp'),
                'bytes' => $size, 'halaman' => null, 'durasi_detik' => null];
        }

        if ($jenis === 'audio') {
            if ($size > 10 * 1024 * 1024
                || ! in_array($ext, ['mp3', 'm4a'], true)) {
                throw new DomainException('Audio hanya MP3/M4A maksimal 10 MB.');
            }
            if ($ext === 'mp3' && ! in_array($mime, ['audio/mpeg', 'audio/mp3', 'application/octet-stream'], true)) {
                throw new DomainException('Jenis isi audio tidak cocok dengan MP3.');
            }
            if ($ext === 'm4a' && ! in_array($mime, ['audio/mp4', 'audio/x-m4a', 'video/mp4', 'application/octet-stream'], true)) {
                throw new DomainException('Jenis isi audio tidak cocok dengan M4A.');
            }
            $head = file_get_contents($path, false, null, 0, 20);
            if (! is_string($head) || ($ext === 'mp3' && ! (
                str_starts_with($head, 'ID3') || (ord($head[0]) === 255 && (ord($head[1]) & 224) === 224)
            )) || ($ext === 'm4a' && substr($head, 4, 4) !== 'ftyp')) {
                throw new DomainException('Header audio tidak sah.');
            }
            return ['mime' => $ext === 'mp3' ? 'audio/mpeg' : 'audio/mp4', 'ext' => $ext,
                'bytes' => $size, 'halaman' => null, 'durasi_detik' => null];
        }

        throw new DomainException('Unggah PDF memerlukan pemeriksaan halaman khusus dan belum diaktifkan.');
    }

    /** @return array{path:string,mime:string,bytes:int} */
    private function tulisPrivat(string $tmp, string $jenis, array $v): array
    {
        if (! is_dir($this->folder) && ! @mkdir($this->folder, 0700, true) && ! is_dir($this->folder)) {
            throw new RuntimeException('Folder privat Materi tidak dapat dibuat.');
        }
        if (! is_writable($this->folder)) {
            throw new RuntimeException('Folder privat Materi tidak dapat ditulis.');
        }
        $name = bin2hex(random_bytes(24)) . '.' . $v['ext'];
        $dest = $this->folder . DIRECTORY_SEPARATOR . $name;
        if ($jenis === 'gambar') {
            $data = file_get_contents($tmp);
            $src = is_string($data) ? @imagecreatefromstring($data) : false;
            unset($data);
            if ($src === false) {
                throw new DomainException('Gambar tidak dapat didekode.');
            }
            try {
                $w = imagesx($src);
                $h = imagesy($src);
                $scale = min(1.0, 1600.0 / max($w, $h));
                $tw = max(1, (int) round($w * $scale));
                $th = max(1, (int) round($h * $scale));
                $out = imagecreatetruecolor($tw, $th);
                if ($out === false) {
                    throw new RuntimeException('Memori gambar tidak cukup.');
                }
                try {
                    imagealphablending($out, false);
                    imagesavealpha($out, true);
                    imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
                    imagecopyresampled($out, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
                    $ok = match ($v['ext']) {
                        'jpg' => imagejpeg($out, $dest, 83),
                        'png' => imagepng($out, $dest, 6),
                        'webp' => imagewebp($out, $dest, 82),
                        default => false,
                    };
                } finally {
                    imagedestroy($out);
                }
            } finally {
                imagedestroy($src);
            }
        } else {
            $ok = @copy($tmp, $dest);
        }
        if (! $ok || ! is_file($dest)) {
            @unlink($dest);
            throw new RuntimeException('Penyimpanan media privat gagal.');
        }
        @chmod($dest, 0600);
        return ['path' => $dest, 'mime' => $v['mime'], 'bytes' => (int) filesize($dest)];
    }

    public function unggah(int $tpId, int $materiId, int $versi, UploadedFile $file): void
    {
        if ($tpId < 1 || $materiId < 1 || $versi < 1 || ! $file->isValid() || $file->hasMoved()) {
            throw new DomainException('Permintaan unggahan tidak sah.');
        }
        // Cek G1 terlebih dahulu sebelum mengolah file atau menulis berkas privat.
        $rincian = $this->detail->lihat($tpId);
        if ($rincian['tp']['status'] !== 'draf') {
            throw new DomainException('TP Siap tidak boleh diubah.');
        }
        $materi = null;
        foreach ($rincian['materi'] as $item) {
            if ((int) $item['id'] === $materiId) {
                $materi = $item;
                break;
            }
        }
        if ($materi === null || ! in_array($materi['jenis'], ['gambar', 'audio'], true)) {
            throw new DomainException('Hanya Materi gambar/audio pada TP ini yang dapat diunggah.');
        }
        $v = self::validasi($file->getTempName(), $materi['jenis'], $file->getClientName());
        $baru = $this->tulisPrivat($file->getTempName(), $materi['jenis'], $v);
        $nama = basename($baru['path']);

        $this->db->transBegin();
        try {
            $lock = $this->db->query('SELECT status,struktur_versi FROM tp WHERE id=? FOR UPDATE', [$tpId])->getRowArray();
            if ($lock === null || $lock['status'] !== 'draf' || (int) $lock['struktur_versi'] !== $versi) {
                throw new DomainException('TP telah berubah. Muat ulang sebelum mengunggah.');
            }
            // Periksa lagi kepemilikan Materi/jenis setelah mengambil lock.
            $item = $this->db->table('materi')->where('id', $materiId)->where('tp_id', $tpId)
                ->get()->getRowArray();
            if ($item === null || $item['jenis'] !== $materi['jenis']) {
                throw new DomainException('Materi berubah saat unggahan diproses.');
            }
            $this->db->table('berkas')->insert([
                'tp_id' => $tpId,
                'nama_acak' => $nama,
                'mime' => $baru['mime'],
                'ukuran_byte' => $baru['bytes'],
                'checksum' => hash_file('sha256', $baru['path']),
                'halaman' => null,
                'durasi_detik' => null,
                'dibuat_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $id = (int) $this->db->insertID();
            $this->db->table('materi')->where('id', $materiId)->where('tp_id', $tpId)
                ->update(['berkas_id' => $id]);
            $this->db->table('tp')->where('id', $tpId)->where('struktur_versi', $versi)
                ->where('status', 'draf')->update(['struktur_versi' => $versi + 1]);
            if ($this->db->affectedRows() !== 1 || ! $this->db->transStatus()) {
                throw new DomainException('Konflik versi saat menyimpan media.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            @unlink($baru['path']);
            throw $e;
        }
    }

    /**
     * Hanya Guru G1: tidak ada URL berkas langsung dalam HTML atau database publik.
     * @return array{path:string,mime:string,bytes:int,jenis:string}
     */
    public function media(int $tpId, int $materiId): array
    {
        $rincian = $this->detail->lihat($tpId);
        $materi = null;
        foreach ($rincian['materi'] as $item) {
            if ((int) $item['id'] === $materiId) {
                $materi = $item;
                break;
            }
        }
        if ($materi === null || ! in_array($materi['jenis'], ['gambar', 'audio'], true)
            || ! isset($materi['berkas_id']) || $materi['berkas_id'] === null) {
            throw new DomainException('Media Materi tidak tersedia.');
        }
        $berkas = $this->db->table('berkas')->where('id', (int) $materi['berkas_id'])
            ->where('tp_id', $tpId)->get()->getRowArray();
        if ($berkas === null || ! preg_match('/^[0-9a-f]{48}\.(?:jpg|png|webp|mp3|m4a)$/D', $berkas['nama_acak'])) {
            throw new DomainException('Metadata media tidak tersedia.');
        }
        $path = $this->folder . DIRECTORY_SEPARATOR . $berkas['nama_acak'];
        if (! is_file($path) || ! is_readable($path) || (int) filesize($path) !== (int) $berkas['ukuran_byte']) {
            throw new DomainException('Berkas fisik media tidak ditemukan.');
        }
        if (! in_array($berkas['mime'], $materi['jenis'] === 'gambar'
            ? ['image/jpeg', 'image/png', 'image/webp'] : ['audio/mpeg', 'audio/mp4'], true)) {
            throw new DomainException('MIME media tidak sesuai dengan Materi.');
        }
        return ['path' => $path, 'mime' => $berkas['mime'],
            'bytes' => (int) $berkas['ukuran_byte'], 'jenis' => $materi['jenis']];
    }
}
