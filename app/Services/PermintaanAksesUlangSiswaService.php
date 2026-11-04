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

    /**
     * Baca kebutuhan akses ulang yang AKTUAL, berdasarkan percobaan terakhir TP
     * lintas Diagnostik/Sumatif. Tidak boleh menyimpulkan jenis dari halaman
     * ujian yang kebetulan dibuka siswa.
     *
     * @return array{id:int,jenis:string,tahap:string,sudah_diminta:bool}|null
     */
    public function kebutuhan(int $tpId): ?array
    {
        $akun = $this->akunSiswa();
        if ($tpId < 1) {
            throw new DomainException('TP tidak sah.');
        }
        $pr = $this->progresSiswa($tpId, (int) $akun['id'], false);
        if (!$pr || $pr['status'] !== 'perlu_bantuan') {
            return null;
        }
        $attempt = $this->percobaanTerakhir((int) $pr['id']);
        if (!$attempt || !in_array($attempt['jenis'], ['diagnostik', 'sumatif'], true)
            || $attempt['status'] !== 'dibatalkan_menunggu_guru'
            || (int) $attempt['versi_konten_tp_id'] !== (int) $pr['versi_konten_tp_id']) {
            return null;
        }
        $izin = $this->db->table('keputusan_ujian_batal')
            ->select('keputusan')->where('percobaan_ujian_id', (int) $attempt['id'])
            ->get()->getRowArray();
        if ($izin && in_array($izin['keputusan'],
            ['ulang_diagnostik', 'ulang_sumatif', 'langsung_belajar'], true)) {
            return null;
        }
        return [
            'id' => (int) $attempt['id'],
            'jenis' => (string) $attempt['jenis'],
            'tahap' => (string) $attempt['tahap'],
            'sudah_diminta' => $this->db->table('riwayat_status_ujian')
                ->where('percobaan_ujian_id', (int) $attempt['id'])
                ->where('alasan', self::ALASAN_PERMINTAAN)->countAllResults() > 0,
        ];
    }

    private function akunSiswa(): array
    {
        $akun = ($this->akun)();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'siswa'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Permintaan akses ulang hanya untuk Siswa aktif.');
        }
        return $akun;
    }

    private function progresSiswa(int $tpId, int $akunId, bool $lock): ?array
    {
        $suffix = $lock && $this->db->DBDriver !== 'SQLite3' ? ' FOR UPDATE' : '';
        return $this->db->query(
            'SELECT pr.id,pr.status,pr.versi_konten_tp_id FROM progres_tp pr '
            . 'JOIN siswa s ON s.id=pr.siswa_id '
            . 'JOIN tp t ON t.id=pr.tp_id '
            . 'JOIN paket_semester p ON p.id=t.paket_semester_id AND p.id=pr.paket_semester_id '
            . 'WHERE pr.tp_id=? AND s.pengguna_id=? AND s.angkatan_id=p.angkatan_id' . $suffix,
            [$tpId, $akunId]
        )->getRowArray();
    }

    private function percobaanTerakhir(int $progresId): ?array
    {
        return $this->db->table('percobaan_ujian')
            ->where('progres_tp_id', $progresId)
            ->whereIn('jenis', ['diagnostik', 'sumatif'])
            ->orderBy('id', 'DESC')->get(1)->getRowArray();
    }

    public function minta(int $tpId, string $jenis): void
    {
        $akun = $this->akunSiswa();
        if ($tpId < 1 || !in_array($jenis, ['diagnostik', 'sumatif'], true)) {
            throw new DomainException('Tes yang diminta tidak sah.');
        }

        $this->db->transBegin();
        try {
            $progres = $this->progresSiswa($tpId, (int) $akun['id'], true);
            if (!$progres || $progres['status'] !== 'perlu_bantuan') {
                throw new DomainException('Tes tidak sedang menunggu akses ulang.');
            }
            // Percobaan terakhir lintas kedua jenis adalah sumber kebenaran.
            // Jangan izinkan Diagnostik bila Sumatif terakhir yang terhenti.
            $attempt = $this->percobaanTerakhir((int) $progres['id']);
            if (!$attempt || $attempt['jenis'] !== $jenis
                || $attempt['status'] !== 'dibatalkan_menunggu_guru'
                || (int) $attempt['versi_konten_tp_id'] !== (int) $progres['versi_konten_tp_id']) {
                throw new DomainException('Jenis tes tidak sesuai percobaan terakhir yang perlu akses ulang.');
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
            if (!$sudah) {
                $pengampu=$this->db->table('progres_tp pr')
                    ->select('g.guru_id')->join('paket_semester p','p.id=pr.paket_semester_id')
                    ->join('penugasan_guru_gen g',
                        'g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id')
                    ->where('pr.id',(int)$progres['id'])->get(1)->getRowArray();
                if ($pengampu && (int)($pengampu['guru_id']??0)>0) {
                    NotifikasiService::catat($this->db,'permintaan_akses_ulang',
                        (int)$pengampu['guru_id'],(int)$attempt['id']);
                }
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
