<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * S08A: akses media Materi dari snapshot TP yang dipin siswa.
 * Belum ada pencatatan ketuntasan media. Jangan membuka Materi selanjutnya
 * sebelum media, Resume final, dan HOTS final tersedia dalam S08B–S10.
 */
final class MateriSiswaService
{
    private BaseConnection $db;
    private KatalogPembelajaranSiswaService $katalog;
    private ProgresTpSiswaService $progres;
    private $akunSaatIni;

    public function __construct(?BaseConnection $db = null, ?callable $akunSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->akunSaatIni = $akunSaatIni
            ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
        $this->katalog = new KatalogPembelajaranSiswaService($this->db, $this->akunSaatIni);
        $this->progres = new ProgresTpSiswaService($this->db, $this->akunSaatIni);
    }

    /**
     * Gerbang tunggal untuk daftar/detail/media.
     * Validasi otorisasi Gen–TP dan checksum snapshot selalu di server.
     */
    private function konteks(int $tpId): array
    {
        $akun = ($this->akunSaatIni)();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'siswa'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Siswa aktif yang dapat membaca Materi.');
        }
        $katalog = $this->katalog->rincianTp($tpId);
        $p = $this->progres->status($tpId);
        if ($p === null || !in_array($p['status'], ['belajar', 'tuntas'], true)) {
            throw new DomainException('Selesaikan Diagnostik sah sebelum membuka Materi.');
        }
        // Status tuntas boleh membaca media sukarela, tanpa tugas resmi.
        // Status belajar hanya boleh membuka Materi pertama hingga S09/S10 aktif.
        $versi = $this->db->table('versi_konten_tp')
            ->select('id,tp_id,nomor_versi,snapshot_json,checksum')
            ->where('id', (int) $p['versi_konten_tp_id'])
            ->where('tp_id', $tpId)->get()->getRowArray();
        if (!$versi || !ProgresTpSiswaService::validasiSnapshot($versi, $tpId)) {
            throw new DomainException('Snapshot pembelajaran tidak sah. Hubungi Guru.');
        }
        $snapshot = json_decode($versi['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        $materi = $snapshot['materi'] ?? null;
        if (!is_array($materi) || $materi === []) {
            throw new DomainException('Daftar Materi versi TP ini belum tersedia.');
        }
        usort($materi, static fn (array $a, array $b): int =>
            [(int) ($a['urutan'] ?? 0), (int) ($a['id'] ?? 0)]
                <=> [(int) ($b['urutan'] ?? 0), (int) ($b['id'] ?? 0)]);
        $ids = [];
        foreach ($materi as $i => $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id < 1 || isset($ids[$id]) || (int) ($item['urutan'] ?? 0) !== $i + 1
                || (int) ($item['tp_id'] ?? 0) !== $tpId
                || (int) ($item['aktif'] ?? 0) !== 1) {
                throw new DomainException('Urutan atau identitas Materi pada snapshot tidak sah.');
            }
            $ids[$id] = true;
        }
        return ['katalog' => $katalog, 'progres' => $p, 'materi' => $materi];
    }

    /** @return array{progres:array,materi:list<array<string,mixed>>} */
    public function daftar(int $tpId): array
    {
        $ctx = $this->konteks($tpId);
        $tuntas = $ctx['progres']['status'] === 'tuntas';
        $rows = [];
        foreach ($ctx['materi'] as $i => $m) {
            $boleh = $tuntas || $i === 0;
            $rows[] = [
                'id' => (int) $m['id'], 'urutan' => (int) $m['urutan'],
                'judul' => (string) $m['judul'], 'jenis' => (string) $m['jenis'],
                'buka' => $boleh, 'sukarela' => $tuntas,
                'status' => $tuntas ? 'Baca sukarela' : ($boleh ? 'Baca Materi pertama'
                    : 'Menunggu media, Resume dan HOTS Materi sebelumnya'),
            ];
        }
        return ['progres' => $ctx['progres'], 'materi' => $rows];
    }

    /** @return array{tp:array,progres:array,materi:array,media_url:?string,video_url:?string,sukarela:bool} */
    public function lihat(int $tpId, int $materiId): array
    {
        $ctx = $this->konteks($tpId);
        $m = $this->materiBerizin($ctx, $materiId);
        $jenis = (string) ($m['jenis'] ?? '');
        $video = null;
        if ($jenis === 'youtube') {
            $video = VideoMateriService::urlEmbed((string) ($m['url_youtube'] ?? ''));
        } elseif (in_array($jenis, ['pdf', 'audio', 'gambar'], true)) {
            if ((int) ($m['berkas_id'] ?? 0) < 1) {
                throw new DomainException('Media Materi belum tersedia.');
            }
        } else {
            throw new DomainException('Jenis media tidak dikenal.');
        }
        return [
            'tp' => $ctx['katalog']['tp'], 'progres' => $ctx['progres'],
            'materi' => [
                'id' => (int) $m['id'], 'urutan' => (int) $m['urutan'],
                'judul' => (string) $m['judul'], 'jenis' => $jenis,
                'min_waktu_detik' => (int) ($m['min_waktu_detik'] ?? 0),
                'petunjuk_resume' => (string) ($m['petunjuk_resume'] ?? ''),
                'petunjuk_hots' => (string) ($m['petunjuk_hots'] ?? ''),
            ],
            'media_url' => $video === null
                ? site_url('siswa/tp/' . $tpId . '/materi/' . $materiId . '/media') : null,
            'video_url' => $video,
            'sukarela' => $ctx['progres']['status'] === 'tuntas',
        ];
    }

    private function materiBerizin(array $ctx, int $materiId): array
    {
        foreach ($ctx['materi'] as $i => $m) {
            if ((int) $m['id'] !== $materiId) {
                continue;
            }
            if ($ctx['progres']['status'] === 'tuntas' || $i === 0) {
                return $m;
            }
            throw new DomainException('Materi berikutnya menunggu Resume dan HOTS final sebelumnya.');
        }
        throw new DomainException('Materi tidak tersedia pada snapshot TP siswa.');
    }

    /** Data privat untuk controller streaming; path/nama_acak tidak pernah dikirim ke view. */
    public function media(int $tpId, int $materiId): array
    {
        $ctx = $this->konteks($tpId);
        $m = $this->materiBerizin($ctx, $materiId);
        $jenis = (string) ($m['jenis'] ?? '');
        if (!in_array($jenis, ['pdf', 'gambar', 'audio'], true)) {
            throw new DomainException('Media file tidak tersedia.');
        }
        $berkasId = (int) ($m['berkas_id'] ?? 0);
        if ($berkasId < 1) throw new DomainException('Berkas tidak tersedia.');
        $row = $this->db->table('berkas')->where('id', $berkasId)
            ->where('tp_id', $tpId)->get()->getRowArray();
        $nama = (string) ($row['nama_acak'] ?? '');
        $allowed = [
            'pdf' => ['application/pdf' => 'pdf'],
            'gambar' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
            'audio' => ['audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a'],
        ];
        $mime = (string) ($row['mime'] ?? '');
        $ext = $allowed[$jenis][$mime] ?? null;
        if ($ext === null || !preg_match('/^[a-f0-9]{48}\\.' . $ext . '$/D', $nama)) {
            throw new DomainException('Metadata media tidak sah.');
        }
        $path = rtrim(WRITEPATH, '/\\') . '/uploads/materi/' . $nama;
        $bytes = (int) ($row['ukuran_byte'] ?? 0);
        if ($bytes < 1 || !is_file($path) || !is_readable($path)
            || (int) filesize($path) !== $bytes) {
            throw new DomainException('Media tidak ditemukan pada penyimpanan privat.');
        }
        return ['path' => $path, 'bytes' => $bytes, 'mime' => $mime, 'jenis' => $jenis];
    }
}
