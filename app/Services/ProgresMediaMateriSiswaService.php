<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * S08B: hanya jalur belajar & Materi yang diizinkan snapshot.
 * Perubahan progress diserialisasi di baris progres_tp dengan InnoDB lock;
 * tuntas Diagnostik/sukarela tidak dapat mengirim heartbeat resmi.
 */
final class ProgresMediaMateriSiswaService
{
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

    /** @return array<string,mixed> */
    public function status(int $tpId, int $materiId): array
    {
        $ctx = $this->materi->lihat($tpId, $materiId);
        if ($ctx['materi']['jenis'] === 'pdf') {
            return (new ProgresPdfMateriSiswaService($this->db, $this->clock))
                ->status($ctx, $materiId);
        }
        $key = [
            'progres_tp_id' => (int) $ctx['progres']['id'],
            'materi_id' => $materiId,
            'versi_konten_tp_id' => (int) $ctx['progres']['versi_konten_tp_id'],
        ];
        $row = $this->db->table('progres_materi')->where($key)
            ->get()->getRowArray();
        // Perubahan aturan Guru/90% tetap berlaku untuk waktu PUTAR sah
        // yang sudah tercatat. Jangan paksa siswa memutar ulang videonya.
        // UPDATE ini idempoten, terikat ke progres siswa dan snapshot.
        if ($row !== null && !$ctx['sukarela']
            && ($ctx['progres']['status'] ?? '') === 'belajar'
            && $row['status'] === 'berjalan'
            && $row['jenis'] === $ctx['materi']['jenis']
            && in_array($row['jenis'], ['gambar', 'audio', 'youtube'], true)) {
            $min = (int) $ctx['materi']['min_waktu_detik'];
            $syarat = null;
            if ($row['jenis'] === 'gambar' && $min >= 1 && $min <= 10800) {
                $syarat = max(AturanAktivitasMateriService::GAMBAR_MINIMAL, $min);
            } elseif ($row['jenis'] !== 'gambar'
                && $row['durasi_media_detik'] !== null) {
                $durasi = (int) $row['durasi_media_detik'];
                if ($min >= 1 && $min <= 10800 && $durasi >= 5
                    && $durasi <= 10800
                    && AturanAktivitasMateriService::memenuhiPemutaran(
                        $row, $min, $durasi)) {
                    $syarat = AturanAktivitasMateriService::batasEfektif($min, $durasi);
                }
            }
            if ($syarat !== null
                && (int) $row['waktu_aktif_detik'] >= $syarat) {
                $now = self::tanggal(($this->clock)());
                $update = $this->db->table('progres_materi')->where($key)
                    ->where('status', 'berjalan')
                    ->where('jenis', $row['jenis'])
                    ->where('waktu_aktif_detik >=', $syarat);
                if ($row['jenis'] !== 'gambar') {
                    $update->where('durasi_media_detik', $durasi)
                        ->where('waktu_putar_detik >=', $syarat);
                }
                $update->update([
                    'status' => 'media_selesai',
                    'media_selesai_pada' => $now,
                    'diubah_pada' => $now,
                ]);
                $row = $this->db->table('progres_materi')->where($key)
                    ->get()->getRowArray();
            }
        }
        return self::publicStatus($ctx['materi'], $row, $ctx['sukarela']);
    }

    /** @return array<string,mixed> */
    public function detak(int $tpId, int $materiId, bool $aktif,
        ?int $posisi, ?int $durasi, ?int $halaman = null,
        ?int $jumlahHalaman = null, bool $resetSesi = false): array
    {
        if (!$aktif) throw new DomainException('Heartbeat hanya diterima saat media aktif.');
        // Konteks selalu diperiksa ulang, termasuk ID dari URL.
        $ctx = $this->materi->lihat($tpId, $materiId);
        if ($ctx['sukarela'] || $ctx['progres']['status'] !== 'belajar') {
            throw new DomainException('Belajar sukarela tidak membuat progres/penilaian resmi.');
        }
        $jenis = (string) $ctx['materi']['jenis'];
        if ($jenis === 'pdf') {
            return (new ProgresPdfMateriSiswaService($this->db, $this->clock))
                ->detak($ctx, $materiId, $halaman, $jumlahHalaman, $resetSesi);
        }
        $min = (int) $ctx['materi']['min_waktu_detik'];
        if ($min < 1) throw new DomainException('Batas waktu media pada snapshot belum sah.');
        $now = ($this->clock)();
        $this->db->transBegin();
        try {
            $progresId = (int) $ctx['progres']['id'];
            $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $p = $this->db->query(
                'SELECT id,status,versi_konten_tp_id FROM progres_tp WHERE id=?' . $lock,
                [$progresId]
            )->getRowArray();
            if (!$p || $p['status'] !== 'belajar'
                || (int) $p['versi_konten_tp_id'] !==
                   (int) $ctx['progres']['versi_konten_tp_id']) {
                throw new DomainException('Status atau versi TP berubah. Muat ulang halaman.');
            }
            $row = $this->db->query(
                'SELECT * FROM progres_materi WHERE progres_tp_id=? AND materi_id=? AND versi_konten_tp_id=?' . $lock,
                [$progresId, $materiId, (int) $p['versi_konten_tp_id']]
            )->getRowArray();
            if ($row !== null && $row['jenis'] !== $jenis) {
                throw new DomainException('Jenis media tidak cocok dengan snapshot.');
            }
            if ($row === null) {
                $next = AturanAktivitasMateriService::awal($jenis, $now, $posisi, $durasi);
                $this->db->table('progres_materi')->insert([
                    'progres_tp_id' => $progresId,
                    'materi_id' => $materiId,
                    'versi_konten_tp_id' => (int) $p['versi_konten_tp_id'],
                    'jenis' => $jenis,
                    'status' => 'berjalan',
                    'waktu_aktif_detik' => 0,
                    'waktu_putar_detik' => 0,
                    'posisi_terakhir_detik' => $next['posisi_terakhir_detik'],
                    'posisi_terjauh_detik' => $next['posisi_terjauh_detik'],
                    'durasi_media_detik' => $next['durasi_media_detik'],
                    'heartbeat_terakhir_pada' => self::tanggal($now),
                    'dibuat_pada' => self::tanggal($now),
                    'diubah_pada' => self::tanggal($now),
                ]);
            } elseif ($row['status'] !== 'media_selesai') {
                $state = [
                    'status' => $row['status'],
                    'waktu_aktif_detik' => (int) $row['waktu_aktif_detik'],
                    'waktu_putar_detik' => (int) $row['waktu_putar_detik'],
                    'posisi_terakhir_detik' => $row['posisi_terakhir_detik'] === null
                        ? null : (int) $row['posisi_terakhir_detik'],
                    'posisi_terjauh_detik' => (int) $row['posisi_terjauh_detik'],
                    'durasi_media_detik' => $row['durasi_media_detik'] === null
                        ? null : (int) $row['durasi_media_detik'],
                    'heartbeat_terakhir_pada' => (int) strtotime($row['heartbeat_terakhir_pada'] . ' UTC'),
                ];
                $next = AturanAktivitasMateriService::detak(
                    $state, $jenis, $now, $posisi, $durasi, $min,
                    $jenis === 'gambar' && $resetSesi);
                $data = [
                    'status' => $next['status'],
                    'waktu_aktif_detik' => $next['waktu_aktif_detik'],
                    'waktu_putar_detik' => $next['waktu_putar_detik'],
                    'posisi_terakhir_detik' => $next['posisi_terakhir_detik'],
                    'posisi_terjauh_detik' => $next['posisi_terjauh_detik'],
                    'durasi_media_detik' => $next['durasi_media_detik'],
                    'heartbeat_terakhir_pada' =>
                        self::tanggal((int) $next['heartbeat_terakhir_pada']),
                    'diubah_pada' => self::tanggal($now),
                ];
                if ($next['status'] === 'media_selesai') {
                    $data['media_selesai_pada'] = self::tanggal($now);
                }
                $this->db->table('progres_materi')->where('id', $row['id'])->update($data);
            }
            if (!$this->db->transStatus()) throw new RuntimeException('Pencatatan media gagal.');
            $this->db->transCommit();
            return $this->status($tpId, $materiId);
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private static function tanggal(int $epoch): string
    {
        return gmdate('Y-m-d H:i:s', $epoch);
    }

    private static function publicStatus(array $materi, ?array $row, bool $sukarela): array
    {
        $jenis = (string) $materi['jenis'];
        $min = max(1, (int) $materi['min_waktu_detik']);
        if ($jenis === 'gambar') $min = max(30, $min);
        $selesai = !$sukarela && ($row['status'] ?? '') === 'media_selesai';
        $aktif = (int) ($row['waktu_aktif_detik'] ?? 0);
        $putar = (int) ($row['waktu_putar_detik'] ?? 0);
        $terjauh = (int) ($row['posisi_terjauh_detik'] ?? 0);
        $durasi = isset($row['durasi_media_detik'])
            ? (int) $row['durasi_media_detik'] : null;
        $harusPutar = $durasi !== null && $durasi >= 5
            ? (int) ceil($durasi * AturanAktivitasMateriService::PERSEN_PUTAR / 100) : null;
        $perluPutar = in_array($jenis, ['audio', 'youtube'], true);
        $batasEfektif = $perluPutar && $durasi !== null && $durasi >= 5
            && $durasi <= 10800 && $min <= 10800
            ? AturanAktivitasMateriService::batasEfektif($min, $durasi) : null;
        return [
            'jenis' => $jenis, 'sukarela' => $sukarela,
            'status' => $sukarela ? 'sukarela' : ($row['status'] ?? 'belum_dimulai'),
            'media_selesai' => $selesai,
            'waktu_aktif_detik' => $aktif,
            'waktu_putar_detik' => $putar,
            'posisi_terjauh_detik' => $terjauh,
            'durasi_media_detik' => $durasi,
            'minimal_detik' => $min,
            'syarat_waktu_terpenuhi' => $aktif >= $min,
            'waktu_tersisa_detik' => max(0, $min - $aktif),
            'pemutaran_minimal_detik' => $perluPutar ? $harusPutar : null,
            'batas_efektif_detik' => $batasEfektif,
            'syarat_efektif_terpenuhi' => $batasEfektif !== null
                ? $aktif >= $batasEfektif && $putar >= $batasEfektif : null,
            'syarat_putar_terpenuhi' => $perluPutar && $harusPutar !== null
                ? $putar >= $harusPutar : null,
            'syarat_jangkauan_terpenuhi' => $perluPutar && $harusPutar !== null
                ? $terjauh >= $harusPutar : null,
            'persen_putar_minimal' => $perluPutar
                ? AturanAktivitasMateriService::PERSEN_PUTAR : null,
            'pesan' => $sukarela
                ? 'Belajar sukarela tidak mempengaruhi nilai TP.'
                : ($jenis === 'pdf'
                    ? 'PDF belum dilacak; menunggu PDF.js lokal dan rekam setiap halaman.'
                    : ($selesai
                        ? 'Media terpenuhi. Resume dan HOTS masih harus dikirim final.'
                        : 'Waktu dihitung saat media aktif dan halaman terlihat.')),
        ];
    }
}
