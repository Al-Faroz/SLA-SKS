<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Pelacakan PDF lokal S08D tanpa SQL baru: menggunakan tabel SQL 0016.
 * Halaman pertama setelah klik/jeda tidak memperoleh kredit otomatis.
 * posisi_terakhir_detik pada JENIS PDF menyimpan nomor halaman terakhir;
 * untuk media audio/video tetap detik pemutaran.
 */
final class ProgresPdfMateriSiswaService
{
    private BaseConnection $db;
    private $clock;

    public function __construct(BaseConnection $db, ?callable $clock = null)
    {
        $this->db = $db;
        $this->clock = $clock ?? static fn (): int => time();
    }

    public function status(array $ctx, int $materiId): array
    {
        $total = (int) ($ctx['materi']['halaman_pdf'] ?? 0);
        if ($total < 1 || $total > 40) {
            throw new DomainException('Jumlah halaman PDF belum sah. Hubungi Guru.');
        }
        $key = $this->kunci($ctx, $materiId);
        $row = $this->db->table('progres_materi')->where($key)->get()->getRowArray();
        $sukarela = $ctx['sukarela'];
        $done = (int) ($row['halaman_selesai'] ?? 0);
        $minTotal = max(15 * $total, (int) $ctx['materi']['min_waktu_detik']);
        $finish = !$sukarela && ($row['status'] ?? '') === 'media_selesai';
        return [
            'jenis' => 'pdf',
            'sukarela' => $sukarela,
            'status' => $sukarela ? 'sukarela' : ($row['status'] ?? 'belum_dimulai'),
            'media_selesai' => $finish,
            'waktu_aktif_detik' => (int) ($row['waktu_aktif_detik'] ?? 0),
            'waktu_putar_detik' => 0,
            'posisi_terjauh_detik' => (int) ($row['posisi_terjauh_detik'] ?? 0),
            'minimal_detik' => $minTotal,
            'persen_putar_minimal' => null,
            'halaman_total' => $total,
            'halaman_selesai' => $done,
            'minimal_per_halaman_detik' => 15,
            'pesan' => $sukarela
                ? 'Belajar PDF sukarela tidak membuat progres resmi.'
                : ($finish
                    ? 'Semua halaman PDF selesai. Lanjutkan Resume dan HOTS.'
                    : 'Baca setiap halaman PDF sekurang-kurangnya 15 detik dengan tab aktif.'),
        ];
    }

    private function kunci(array $ctx, int $materiId): array
    {
        return [
            'progres_tp_id' => (int) $ctx['progres']['id'],
            'materi_id' => $materiId,
            'versi_konten_tp_id' => (int) $ctx['progres']['versi_konten_tp_id'],
        ];
    }

    public function detak(array $ctx, int $materiId, ?int $halaman, ?int $jumlah): array
    {
        if ($ctx['sukarela'] || $ctx['progres']['status'] !== 'belajar') {
            throw new DomainException('Belajar sukarela tidak membuat progres resmi.');
        }
        $total = (int) ($ctx['materi']['halaman_pdf'] ?? 0);
        if ($total < 1 || $total > 40 || $jumlah !== $total
            || $halaman === null || $halaman < 1 || $halaman > $total) {
            throw new DomainException('Nomor atau jumlah halaman PDF tidak sah.');
        }
        $now = ($this->clock)();
        $nowText = gmdate('Y-m-d H:i:s', $now);
        $key = $this->kunci($ctx, $materiId);
        $progresId = $key['progres_tp_id'];
        $this->db->transBegin();
        try {
            $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $parent = $this->db->query(
                'SELECT status,versi_konten_tp_id FROM progres_tp WHERE id=?' . $lock,
                [$progresId]
            )->getRowArray();
            if (!$parent || $parent['status'] !== 'belajar'
                || (int) $parent['versi_konten_tp_id'] !== $key['versi_konten_tp_id']) {
                throw new DomainException('Status TP berubah. Muat ulang halaman.');
            }
            $row = $this->db->query(
                'SELECT * FROM progres_materi WHERE progres_tp_id=? AND materi_id=?'
                    . ' AND versi_konten_tp_id=?' . $lock,
                [$progresId, $materiId, $key['versi_konten_tp_id']]
            )->getRowArray();
            if ($row && ($row['jenis'] !== 'pdf'
                || (int) $row['halaman_total'] !== $total)) {
                throw new DomainException('Versi atau jumlah halaman PDF berubah.');
            }
            if ($row === null) {
                $this->db->table('progres_materi')->insert($key + [
                    'jenis' => 'pdf', 'status' => 'berjalan',
                    'waktu_aktif_detik' => 0, 'waktu_putar_detik' => 0,
                    'posisi_terakhir_detik' => $halaman,
                    'posisi_terjauh_detik' => $halaman,
                    'halaman_total' => $total, 'halaman_selesai' => 0,
                    'heartbeat_terakhir_pada' => $nowText,
                    'dibuat_pada' => $nowText, 'diubah_pada' => $nowText,
                ]);
            } elseif ($row['status'] !== 'media_selesai') {
                $last = strtotime((string) $row['heartbeat_terakhir_pada'] . ' UTC');
                $dt = $now - (int) $last;
                $same = (int) $row['posisi_terakhir_detik'] === $halaman;
                $kredit = $same && $dt >= 5 && $dt <= 20 ? $dt : 0;
                $waktuTotal = (int) $row['waktu_aktif_detik'] + $kredit;
                $pageDone = (int) $row['halaman_selesai'];
                if ($kredit > 0) {
                    $part = $this->db->table('halaman_progres_materi')
                        ->where('progres_materi_id', (int) $row['id'])
                        ->where('nomor_halaman', $halaman)->get()->getRowArray();
                    $detik = (int) ($part['waktu_aktif_detik'] ?? 0) + $kredit;
                    $payload = ['waktu_aktif_detik' => $detik];
                    if ($part === null) {
                        $payload += [
                            'progres_materi_id' => (int) $row['id'],
                            'nomor_halaman' => $halaman,
                        ];
                        if ($detik >= 15) {
                            $payload['selesai_pada'] = $nowText;
                            $pageDone++;
                        }
                        $this->db->table('halaman_progres_materi')->insert($payload);
                    } else {
                        if ($part['selesai_pada'] === null && $detik >= 15) {
                            $payload['selesai_pada'] = $nowText;
                            $pageDone++;
                        }
                        $this->db->table('halaman_progres_materi')
                            ->where('id', (int) $part['id'])->update($payload);
                    }
                }
                $complete = $pageDone === $total
                    && $waktuTotal >= max(15 * $total,
                        (int) $ctx['materi']['min_waktu_detik']);
                $payload = [
                    'status' => $complete ? 'media_selesai' : 'berjalan',
                    'waktu_aktif_detik' => $waktuTotal,
                    'halaman_selesai' => $pageDone,
                    'posisi_terakhir_detik' => $halaman,
                    'posisi_terjauh_detik' => max(
                        (int) $row['posisi_terjauh_detik'], $halaman),
                    'heartbeat_terakhir_pada' => $nowText,
                    'diubah_pada' => $nowText,
                ];
                if ($complete) $payload['media_selesai_pada'] = $nowText;
                $this->db->table('progres_materi')->where('id', (int) $row['id'])
                    ->update($payload);
            }
            if (!$this->db->transStatus()) {
                throw new RuntimeException('Pencatatan halaman PDF gagal.');
            }
            $this->db->transCommit();
            return $this->status($ctx, $materiId);
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
