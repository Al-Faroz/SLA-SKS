<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Permintaan eksplisit siswa untuk mengulang TES MANDIRI yang tidak selesai.
 * Tidak membuat percobaan, tidak memberikan skor atau kuota remedial.
 * Status DB legacy digunakan sementara sampai update SQL pasca-audit dump lokal.
 */
final class PermintaanAksesUlangSiswaService
{
    public const ALASAN_PERMINTAAN = 'Siswa meminta akses ulang kepada Guru.';

    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    public function minta(int $tpId, string $jenis): void
    {
        $akun = ($this->akun)();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'siswa'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Permintaan akses ulang hanya untuk Siswa aktif.');
        }
        if ($tpId < 1 || !in_array($jenis, ['diagnostik', 'sumatif'], true)) {
            throw new DomainException('Tes yang diminta tidak sah.');
        }

        $this->db->transBegin();
        try {
            $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $progres = $this->db->query(
                'SELECT pr.id,pr.status,pr.versi_konten_tp_id FROM progres_tp pr '
                . 'JOIN siswa s ON s.id=pr.siswa_id '
                . 'JOIN tp t ON t.id=pr.tp_id '
                . 'JOIN paket_semester p ON p.id=t.paket_semester_id AND p.id=pr.paket_semester_id '
                . 'WHERE pr.tp_id=? AND s.pengguna_id=? AND s.angkatan_id=p.angkatan_id' . $lock,
                [$tpId, (int) $akun['id']]
            )->getRowArray();
            if (!$progres || $progres['status'] !== 'perlu_bantuan') {
                throw new DomainException('Tes tidak sedang menunggu akses ulang.');
            }
            $attempt = $this->db->table('percobaan_ujian')
                ->where('progres_tp_id', (int) $progres['id'])
                ->where('jenis', $jenis)->orderBy('id', 'DESC')->get(1)->getRowArray();
            if (!$attempt || $attempt['status'] !== 'dibatalkan_menunggu_guru'
                || (int) $attempt['versi_konten_tp_id'] !== (int) $progres['versi_konten_tp_id']) {
                throw new DomainException('Tidak ada percobaan tidak selesai yang dapat dimintakan akses ulang.');
            }
            $keputusan = $this->db->table('keputusan_ujian_batal')
                ->where('percobaan_ujian_id', (int) $attempt['id'])->get()->getRowArray();
            if ($keputusan !== null && in_array($keputusan['keputusan'],
                    ['ulang_diagnostik', 'ulang_sumatif', 'langsung_belajar'], true)) {
                throw new DomainException('Guru telah memberikan keputusan. Muat ulang halaman tes.');
            }
            $sudah = $this->db->table('riwayat_status_ujian')
                ->where('percobaan_ujian_id', (int) $attempt['id'])
                ->where('alasan', self::ALASAN_PERMINTAAN)->countAllResults() > 0;
            if (!$sudah) {
                $this->db->table('riwayat_status_ujian')->insert([
                    'percobaan_ujian_id' => (int) $attempt['id'],
                    'status_lama' => (string) $attempt['status'],
                    'status_baru' => (string) $attempt['status'],
                    'alasan' => self::ALASAN_PERMINTAAN,
                    'terjadi_pada' => gmdate('Y-m-d H:i:s'),
                ]);
            }
            if (!$this->db->transStatus()) {
                throw new RuntimeException('Permintaan akses ulang gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
