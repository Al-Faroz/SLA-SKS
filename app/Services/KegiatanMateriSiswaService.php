<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * S09/S10: satu Resume dan HOTS per Materi dan versi TP yang dipin.
 * GET baca-saja; POST manual dengan lock parent progres_tp, revisi optimistis,
 * kirim final sekali, dan audit. Tidak menilai otomatis.
 */
final class KegiatanMateriSiswaService
{
    private BaseConnection $db;
    private MateriSiswaService $materi;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
        $this->materi = new MateriSiswaService($this->db, $this->akun);
    }

    public static function tabel(string $jenis): string
    {
        return match ($jenis) {
            'resume' => 'resume_materi',
            'hots' => 'jawaban_hots_materi',
            default => throw new DomainException('Jenis kegiatan tidak dikenal.'),
        };
    }

    public static function batasMinimal(string $jenis): int
    {
        self::tabel($jenis);
        return $jenis === 'resume' ? 50 : 200;
    }

    public static function normalisasi(string $teks): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $teks));
    }

    public static function validasi(string $jenis, string $teks, bool $final): string
    {
        $teks = self::normalisasi($teks);
        $len = mb_strlen($teks, 'UTF-8');
        if ($len > 12000) {
            throw new DomainException('Jawaban terlalu panjang (maksimal 12.000 karakter).');
        }
        if ($final && $len < self::batasMinimal($jenis)) {
            throw new DomainException('Jawaban final minimal '
                . self::batasMinimal($jenis) . ' karakter.');
        }
        return $teks;
    }

    /** @return array<string,mixed> */
    public function status(int $tpId, int $materiId): array
    {
        $ctx = $this->materi->lihat($tpId, $materiId);
        $p = $ctx['progres'];
        $sukarela = $ctx['sukarela'];
        $key = [
            'progres_tp_id' => (int) $p['id'],
            'materi_id' => $materiId,
            'versi_konten_tp_id' => (int) $p['versi_konten_tp_id'],
        ];
        $media = $sukarela ? null : $this->db->table('progres_materi')
            ->where($key)->get()->getRowArray();
        $resume = $sukarela ? null : $this->db->table('resume_materi')
            ->where($key)->get()->getRowArray();
        $hots = $sukarela ? null : $this->db->table('jawaban_hots_materi')
            ->where($key)->get()->getRowArray();
        return [
            'sukarela' => $sukarela,
            'media_selesai' => ($media['status'] ?? null) === 'media_selesai',
            'resume' => self::publik($resume),
            'hots' => self::publik($hots),
            'minimal_resume' => 50,
            'minimal_hots' => 200,
        ];
    }

    private static function publik(?array $row): array
    {
        return [
            'isi' => (string) ($row['isi'] ?? ''),
            'status' => (string) ($row['status'] ?? 'belum_dimulai'),
            'revisi' => (int) ($row['revisi'] ?? 0),
            'diubah_pada' => $row['diubah_pada'] ?? null,
            'dikirim_pada' => $row['dikirim_pada'] ?? null,
        ];
    }

    /** @return array<string,mixed> */
    public function simpan(int $tpId, int $materiId, string $jenis,
        string $aksi, string $teks, int $revisi): array
    {
        $tabel = self::tabel($jenis);
        if (!in_array($aksi, ['simpan', 'kirim'], true) || $revisi < 0) {
            throw new DomainException('Aksi atau revisi jawaban tidak sah.');
        }
        $final = $aksi === 'kirim';
        $teks = self::validasi($jenis, $teks, $final);
        $ctx = $this->materi->lihat($tpId, $materiId);
        if ($ctx['sukarela'] || $ctx['progres']['status'] !== 'belajar') {
            throw new DomainException('Jalur tuntas Diagnostik tidak mengirim tugas resmi.');
        }
        $akun = ($this->akun)();
        $p = $ctx['progres'];
        $pid = (int) $p['id'];
        $vid = (int) $p['versi_konten_tp_id'];
        $key = [
            'progres_tp_id' => $pid,
            'materi_id' => $materiId,
            'versi_konten_tp_id' => $vid,
        ];

        $this->db->transBegin();
        try {
            $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $induk = $this->db->query(
                'SELECT id,status,versi_konten_tp_id FROM progres_tp WHERE id=?' . $lock,
                [$pid]
            )->getRowArray();
            if (!$induk || $induk['status'] !== 'belajar'
                || (int) $induk['versi_konten_tp_id'] !== $vid) {
                throw new DomainException('Status atau versi TP berubah. Muat ulang halaman.');
            }
            $media = $this->db->table('progres_materi')->where($key)
                ->get()->getRowArray();
            if (($media['status'] ?? '') !== 'media_selesai') {
                throw new DomainException('Selesaikan durasi media sebelum mengerjakan Resume.');
            }
            if ($jenis === 'hots') {
                $resume = $this->db->table('resume_materi')->where($key)
                    ->get()->getRowArray();
                if (($resume['status'] ?? '') !== 'final') {
                    throw new DomainException('Kirim Final Resume terlebih dahulu sebelum HOTS.');
                }
            }
            $row = $this->db->table($tabel)->where($key)->get()->getRowArray();
            if ($row !== null && $row['status'] === 'final') {
                // POST ulang identik: tidak menambah pengiriman, nilai atau audit.
                if ($final && hash_equals((string) $row['isi'], $teks)) {
                    $this->db->transCommit();
                    return self::publik($row);
                }
                throw new DomainException('Jawaban sudah terkirim final dan tidak dapat diubah.');
            }
            if (($row === null ? 0 : (int) $row['revisi']) !== $revisi) {
                throw new DomainException('Draf telah berubah di tab lain. Muat ulang sebelum menyimpan.');
            }
            $now = gmdate('Y-m-d H:i:s');
            $data = [
                'isi' => $teks,
                'status' => $final ? 'final' : 'draf',
                'revisi' => $revisi + 1,
                'diubah_pada' => $now,
                'dikirim_pada' => $final ? $now : null,
            ];
            if ($row === null) {
                $this->db->table($tabel)->insert($key + $data + ['dibuat_pada' => $now]);
            } else {
                $this->db->table($tabel)->where('id', (int) $row['id'])->update($data);
            }
            if ($final) {
                $this->db->table('log_aktivitas')->insert([
                    'pengguna_id' => (int) $akun['id'],
                    'peran_aktif' => 'siswa',
                    'peristiwa' => $jenis . '_materi_final',
                    'catatan' => 'Progres TP #' . $pid . ', Materi #' . $materiId
                        . ', snapshot #' . $vid,
                    'waktu' => $now,
                ]);
            }
            if (!$this->db->transStatus()) {
                throw new RuntimeException('Penyimpanan kegiatan gagal.');
            }
            $this->db->transCommit();
            return self::publik($data);
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
