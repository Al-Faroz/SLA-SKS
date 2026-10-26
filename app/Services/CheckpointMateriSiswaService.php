<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Checkpoint client-Side S08; server hanya menerima durasi kumulatif
 * yang tidak melebihi selisih jam server sejak checkpoint sebelumnya.
 *
 * Sinyal browser bersifat best-effort (khususnya saat offline).
 * Tidak mengklaim membuktikan tatapan mata atau pemutaran asli.
 * Menggunakan SQL 0016 tanpa perubahan skema.
 */
final class CheckpointMateriSiswaService
{
    public const INTERVAL = 180; // Tiga menit; final boleh dikirim lebih awal.
    private BaseConnection $db;
    private MateriSiswaService $materi;
    private $clock;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null,
        ?callable $clock = null)
    {
        $this->db = $db ?? db_connect();
        $this->materi = new MateriSiswaService($this->db, $akun);
        $this->clock = $clock ?? static fn (): int => time();
    }

    private function context(int $tpId, int $materiId): array
    {
        $ctx = $this->materi->lihat($tpId, $materiId);
        if ($ctx['sukarela'] || ($ctx['progres']['status'] ?? '') !== 'belajar') {
            throw new DomainException('Belajar sukarela tidak membuat progres resmi.');
        }
        return $ctx;
    }

    private static function kunci(array $ctx, int $materiId): array
    {
        return [
            'progres_tp_id' => (int) $ctx['progres']['id'],
            'materi_id' => $materiId,
            'versi_konten_tp_id' => (int) $ctx['progres']['versi_konten_tp_id'],
        ];
    }

    /**
     * Register server-clock baseline pertama. Panggil saat media aktif
     * pertama kali, bukan ketika baru halaman dibuka atau video dijeda.
     */
    public function mulai(int $tpId, int $materiId, ?int $posisi,
        ?int $durasi, ?int $halaman): array
    {
        $ctx = $this->context($tpId, $materiId);
        $jenis = (string) $ctx['materi']['jenis'];
        $pdfTotal = $jenis === 'pdf'
            ? (int) $ctx['materi']['halaman_pdf'] : null;
        if ($jenis === 'pdf' && ($halaman === null
            || $halaman < 1 || $halaman > (int) $pdfTotal)) {
            throw new DomainException('Halaman pembaca PDF tidak sah.');
        }
        // API lama mengunci versi/TP dan menginisiasi baris; reset tidak
        // menambah detik tersembunyi. Tidak memanggil di setiap checkpoint.
        return (new ProgresMediaMateriSiswaService($this->db, null, $this->clock))
            ->detak($tpId, $materiId, true, $posisi, $durasi,
                $halaman, $pdfTotal, true);
    }

    /**
     * Durasi adalah angka KUMULATIF, bukan selisih POST; retry idempoten.
     * Siswa membawa angka yang tetap disimpan lokal saat offline.
     * @param array<string,mixed> $laporan
     */
    public function kirim(int $tpId, int $materiId, array $laporan): array
    {
        $ctx = $this->context($tpId, $materiId);
        $jenis = (string) $ctx['materi']['jenis'];
        $total = self::bilangan($laporan['total'] ?? null, 'Total durasi');
        $posisi = null;
        $durasi = null;
        $halaman = null;
        $pages = [];
        if (in_array($jenis, ['audio', 'youtube'], true)) {
            $posisi = self::bilangan($laporan['posisi'] ?? null, 'Posisi pemutaran');
            $durasi = self::bilangan($laporan['durasi'] ?? null, 'Durasi media');
            if ($durasi < 5 || $durasi > 10800 || $posisi > $durasi + 1) {
                throw new DomainException('Metadata pemutaran tidak sah.');
            }
        } elseif ($jenis === 'pdf') {
            $halaman = self::bilangan($laporan['halaman'] ?? null, 'Halaman PDF');
            $count = (int) $ctx['materi']['halaman_pdf'];
            if ($halaman < 1 || $halaman > $count || $count < 1 || $count > 40
                || !is_array($laporan['halaman_detik'] ?? null)
                || count($laporan['halaman_detik']) > $count) {
                throw new DomainException('Data halaman PDF tidak sah.');
            }
            foreach ($laporan['halaman_detik'] as $page => $time) {
                if (!ctype_digit((string) $page) || (int) $page < 1
                    || (int) $page > $count) {
                    throw new DomainException('Nomor halaman PDF tidak sah.');
                }
                $pages[(int) $page] = self::bilangan($time, 'Durasi halaman');
            }
        }
        $key = self::kunci($ctx, $materiId);
        $now = ($this->clock)();
        $nowText = gmdate('Y-m-d H:i:s', $now);
        $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
        $this->db->transBegin();
        try {
            $parent = $this->db->query(
                'SELECT status,versi_konten_tp_id FROM progres_tp WHERE id=?' . $lock,
                [$key['progres_tp_id']]
            )->getRowArray();
            if (!$parent || $parent['status'] !== 'belajar'
                || (int) $parent['versi_konten_tp_id'] !== $key['versi_konten_tp_id']) {
                throw new DomainException('TP berubah; muat ulang materi.');
            }
            $row = $this->db->query(
                'SELECT * FROM progres_materi WHERE progres_tp_id=? AND materi_id=?'
                . ' AND versi_konten_tp_id=?' . $lock,
                array_values($key)
            )->getRowArray();
            if (!$row) {
                throw new DomainException('Mulai media saat online sebelum mencatat durasi.');
            }
            if ($row['jenis'] !== $jenis) {
                throw new DomainException('Jenis media berubah dari snapshot.');
            }
            if ($row['status'] === 'media_selesai') {
                $this->db->transCommit();
                return (new ProgresMediaMateriSiswaService($this->db, null, $this->clock))
                    ->status($tpId, $materiId);
            }
            $oldTotal = max(0, (int) $row['waktu_aktif_detik']);
            $additional = max(0, $total - $oldTotal);
            // Jangan menerima total yang melampaui waktu nyata sejak anchor server.
            $last = strtotime((string) $row['heartbeat_terakhir_pada'] . ' UTC');
            if ($last === false || $now < $last) {
                throw new DomainException('Penanda waktu server tidak valid.');
            }
            $wallBudget = min(10800, $now - $last + 2); // toleransi 2 dtk jaringan
            if ($additional > $wallBudget) {
                throw new DomainException('Waktu lokal belum cocok dengan jam server. '
                    . 'Progres tersimpan di perangkat; coba sinkron ulang saat online.');
            }
            $pageDone = (int) ($row['halaman_selesai'] ?? 0);
            if ($jenis === 'pdf') {
                if ((int) $row['halaman_total'] !== (int) $ctx['materi']['halaman_pdf']) {
                    throw new DomainException('Jumlah halaman PDF berbeda dari versi TP.');
                }
                $existing = $this->db->table('halaman_progres_materi')
                    ->where('progres_materi_id', (int) $row['id'])
                    ->get()->getResultArray();
                $byPage = [];
                foreach ($existing as $part) {
                    $byPage[(int) $part['nomor_halaman']] = $part;
                }
                $pageDelta = 0;
                foreach ($pages as $p => $time) {
                    $old = (int) ($byPage[$p]['waktu_aktif_detik'] ?? 0);
                    $pageDelta += max(0, $time - $old);
                }
                if ($pageDelta !== $additional) {
                    throw new DomainException('Total dan rincian waktu halaman tidak konsisten.');
                }
                foreach ($pages as $p => $time) {
                    $old = (int) ($byPage[$p]['waktu_aktif_detik'] ?? 0);
                    if ($time <= $old) continue;
                    $payload = ['waktu_aktif_detik' => $time];
                    if (empty($byPage[$p]['selesai_pada']) && $time >= 15) {
                        $payload['selesai_pada'] = $nowText;
                        $pageDone++;
                    }
                    if (isset($byPage[$p])) {
                        $this->db->table('halaman_progres_materi')
                            ->where('id', (int) $byPage[$p]['id'])->update($payload);
                    } else {
                        $this->db->table('halaman_progres_materi')
                            ->insert(['progres_materi_id'=>(int) $row['id'],
                                'nomor_halaman'=>$p] + $payload);
                    }
                }
            }
            if (in_array($jenis, ['audio', 'youtube'], true)) {
                if ($row['durasi_media_detik'] !== null
                    && abs((int) $row['durasi_media_detik'] - $durasi) > 2) {
                    throw new DomainException('Durasi media berubah.');
                }
            }
            $limit = (int) $ctx['materi']['min_waktu_detik'];
            if ($limit < 1 || $limit > 10800) {
                throw new DomainException('Batas Guru tidak sah.');
            }
            $sudah = false;
            if ($jenis === 'gambar') {
                $sudah = $total >= max(30, $limit);
            } elseif ($jenis === 'pdf') {
                $sudah = $pageDone >= (int) $ctx['materi']['halaman_pdf']
                    && $total >= max($limit, 15*(int)$ctx['materi']['halaman_pdf']);
            } else {
                $sudah = $total >= AturanAktivitasMateriService::batasEfektif($limit, $durasi);
            }
            $payload = [
                'status' => $sudah ? 'media_selesai' : 'berjalan',
                'waktu_aktif_detik' => $total,
                'heartbeat_terakhir_pada' => $nowText,
                'diubah_pada' => $nowText,
            ];
            if ($jenis === 'pdf') {
                $payload += [
                    'halaman_selesai' => $pageDone,
                    'posisi_terakhir_detik' => $halaman,
                    'posisi_terjauh_detik' => max((int)$row['posisi_terjauh_detik'], $halaman),
                ];
            } elseif ($jenis !== 'gambar') {
                $payload += [
                    'waktu_putar_detik' => $total,
                    'posisi_terakhir_detik' => $posisi,
                    'posisi_terjauh_detik' => max((int)$row['posisi_terjauh_detik'], $posisi),
                    'durasi_media_detik' => $durasi,
                ];
            }
            if ($sudah) $payload['media_selesai_pada'] = $nowText;
            if ($additional > 0 || $sudah) {
                $this->db->table('progres_materi')
                    ->where('id', (int) $row['id'])->update($payload);
            }
            if (!$this->db->transStatus()) {
                throw new RuntimeException('Penyimpanan checkpoint media gagal.');
            }
            $this->db->transCommit();
            return (new ProgresMediaMateriSiswaService($this->db, null, $this->clock))
                ->status($tpId, $materiId);
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private static function bilangan(mixed $value, string $label): int
    {
        if ((!is_int($value) && !(is_string($value)
                && preg_match('/^[0-9]{1,5}$/D', $value) === 1))
            || (int) $value < 0 || (int) $value > 10800) {
            throw new DomainException($label . ' tidak sah.');
        }
        return (int) $value;
    }
}
