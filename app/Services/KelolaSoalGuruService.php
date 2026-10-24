<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;

/** Bank soal draf untuk Guru G1; setiap mutasi memakai PK soal, bukan nomor urut. */
final class KelolaSoalGuruService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private static function jenis(string $jenis): string
    {
        if (!in_array($jenis, ['diagnostik', 'sumatif'], true)) {
            throw new DomainException('Jenis bank soal tidak valid.');
        }
        return $jenis;
    }

    private function konteks(int $tpId, bool $mutasi = false): array
    {
        $rincian = (new DetailTpService($this->db))->lihat($tpId);
        if ($mutasi && $rincian['tp']['status'] !== 'draf') {
            throw new DomainException('TP sudah Siap; koreksi soal memerlukan prosedur persetujuan.');
        }
        return $rincian;
    }

    /** @return list<array<string,mixed>> */
    public function daftar(int $tpId, string $jenis): array
    {
        $this->konteks($tpId);
        return $this->db->table('soal')
            ->where('tp_id', $tpId)->where('jenis', self::jenis($jenis))
            ->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
    }

    public function lihat(int $tpId, int $soalId): array
    {
        $this->konteks($tpId);
        $soal = $this->db->table('soal')->where('tp_id', $tpId)->where('id', $soalId)->get()->getRowArray();
        if (!$soal) {
            throw new DomainException('Soal tidak ditemukan pada TP ini.');
        }
        return $soal;
    }

    /** Fingerprint untuk cegah tab lama menimpa perubahan terbaru. */
    public static function versi(array $row): string
    {
        $values = [];
        foreach (['id', 'row_uuid', 'urutan', 'pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c',
            'opsi_d', 'opsi_e', 'kunci', 'aktif', 'konten_terstruktur_json'] as $name) {
            $values[] = (string) ($row[$name] ?? '');
        }
        return hash('sha256', implode("\x1f", $values));
    }

    /**
     * @param array<string,mixed> $input Pertanyaan/opsi berisi HTML yang harus dibersihkan.
     * @return int ID soal baru atau tersunting.
     */
    public function simpan(int $tpId, string $jenis, array $input, ?int $soalId): int
    {
        $jenis = self::jenis($jenis);
        if (!in_array($input['kunci'] ?? null, ['A', 'B', 'C', 'D', 'E'], true)) {
            throw new DomainException('Pilih tepat satu kunci jawaban A–E.');
        }
        $sebelumnya = $soalId !== null ? $this->lihat($tpId, $soalId) : null;
        $richLama = $sebelumnya !== null
            ? json_decode((string) ($sebelumnya['konten_terstruktur_json'] ?? ''), true) : null;
        $wordSource = is_array($richLama) ? ($richLama['word_source_id'] ?? null) : null;
        $semua = [];
        foreach (['pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e'] as $name) {
            $semua[$name] = HtmlSoalGuruService::bersihkan(
                (string) ($input[$name] ?? ''), $tpId, is_string($wordSource) ? $wordSource : null
            );
        }

        $this->db->transBegin();
        try {
            // Serialisasi semua mutasi bank melalui baris TP untuk menghindari konflik Word.
            $locked = $this->db->query('SELECT id,status FROM tp WHERE id = ? FOR UPDATE', [$tpId])->getRowArray();
            if (!$locked || $locked['status'] !== 'draf') {
                throw new DomainException('TP telah dikunci. Muat ulang halaman.');
            }
            $this->konteks($tpId, true);
            $record = ['kunci' => $input['kunci'], 'aktif' => 1];
            $rich = ['format' => 'SLA-SKS-RICH-V1', 'origin' => 'manual',
                'manual_edited' => true, 'html_pertanyaan' => $semua['pertanyaan']['html'],
                'html_opsi' => []];
            if (is_string($wordSource) && preg_match('/^[0-9a-f]{32}$/D', $wordSource)) {
                $rich['word_source_id'] = $wordSource;
            }
            foreach ($semua as $name => $data) {
                $record[$name] = $data['teks'];
                if ($name !== 'pertanyaan') {
                    $rich['html_opsi'][strtoupper(substr($name, -1))] = $data['html'];
                }
            }
            $record['konten_terstruktur_json'] = json_encode($rich, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if ($soalId === null) {
                $maks = $this->db->table('soal')->selectMax('urutan')
                    ->where('tp_id', $tpId)->where('jenis', $jenis)->get()->getRowArray();
                $nomor = (int) ($maks['urutan'] ?? 0) + 1;
                if ($nomor > 65535) {
                    throw new DomainException('Nomor urut bank soal sudah mencapai batas.');
                }
                $record += ['tp_id' => $tpId, 'jenis' => $jenis, 'urutan' => $nomor,
                    'row_uuid' => bin2hex(random_bytes(16))];
                $this->db->table('soal')->insert($record);
                $soalId = (int) $this->db->insertID();
            } else {
                $row = $this->db->table('soal')->where('id', $soalId)
                    ->where('tp_id', $tpId)->where('jenis', $jenis)->get()->getRowArray();
                if (!$row) {
                    throw new DomainException('Soal tidak ditemukan pada jenis bank ini.');
                }
                if ($row['versi_konten_tp_id'] !== null) {
                    throw new DomainException('Soal telah digunakan dalam versi TP dan tidak dapat diedit langsung.');
                }
                if (!is_string($input['versi'] ?? null)
                    || !hash_equals(self::versi($row), (string) $input['versi'])) {
                    throw new DomainException('Soal sudah diubah di tempat lain. Muat ulang dan tinjau lagi.');
                }
                $this->db->table('soal')->where('id', $soalId)
                    ->where('tp_id', $tpId)->where('jenis', $jenis)->update($record);
            }
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Penyimpanan soal gagal.');
            }
            $this->db->transCommit();
            return $soalId;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** Nonaktifkan atau aktifkan tanpa menghapus fisik ataupun snapshot lama. */
    public function status(int $tpId, int $soalId, bool $aktif, string $versi): void
    {
        $this->db->transBegin();
        try {
            $tp = $this->db->query('SELECT id,status FROM tp WHERE id = ? FOR UPDATE', [$tpId])->getRowArray();
            if (!$tp || $tp['status'] !== 'draf') {
                throw new DomainException('Soal TP Siap tidak dapat diubah langsung.');
            }
            $this->konteks($tpId, true);
            $row = $this->db->table('soal')->where('id', $soalId)->where('tp_id', $tpId)
                ->get()->getRowArray();
            if (!$row) {
                throw new DomainException('Soal tidak ditemukan.');
            }
            if ($row['versi_konten_tp_id'] !== null) {
                throw new DomainException('Soal yang telah digunakan tidak dapat diubah langsung.');
            }
            if (!hash_equals(self::versi($row), $versi)) {
                throw new DomainException('Soal telah berubah. Muat ulang daftar.');
            }
            $rich = json_decode((string) ($row['konten_terstruktur_json'] ?? ''), true);
            if (!is_array($rich)) {
                $rich = ['format' => 'SLA-SKS-RICH-V1'];
            }
            $rich['manual_edited'] = true;
            $this->db->table('soal')->where('id', $soalId)->where('tp_id', $tpId)
                ->update(['aktif' => $aktif ? 1 : 0,
                    'konten_terstruktur_json' => json_encode($rich, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Perubahan status soal gagal.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
