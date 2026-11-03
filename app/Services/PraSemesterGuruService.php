<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A8-1: bank soal dan KKM Pra-Semester milik G1 Gen–Mapel.
 * Persiapan bank tidak membuat percobaan siswa atau jadwal.
 */
final class PraSemesterGuruService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    private function guru(): int
    {
        $a = ($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? '') !== 'guru'
            || ($a['status'] ?? '') !== 'aktif'
            || (int) ($a['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Bank Pra-Semester hanya untuk Guru aktif.');
        }
        return (int) $a['id'];
    }

    public function paket(int $paketId): array
    {
        $guruId = $this->guru();
        if ($paketId < 1) throw new DomainException('Paket semester tidak sah.');
        $row = $this->db->table('paket_semester p')
            ->select('p.id,p.angkatan_id,p.mapel_id,p.semester,p.status,'
                . 'a.nama AS nama_gen,m.nama AS nama_mapel')
            ->join('penugasan_guru_gen g', 'g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id')
            ->join('ref_angkatan a', 'a.id=p.angkatan_id')
            ->join('ref_mapel m', 'm.id=p.mapel_id')
            ->where('p.id', $paketId)->where('g.guru_id', $guruId)
            ->get()->getRowArray();
        if (!$row) throw new DomainException('Paket di luar penugasan Guru Gen–Mapel.');
        return $row;
    }

    /** Daftar seluruh paket Guru, termasuk semester yang belum memiliki bank. */
    public function paketGuru(): array
    {
        $guruId = $this->guru();
        return $this->db->table('paket_semester p')
            ->select('p.id,p.semester,p.status,a.nama AS nama_gen,m.nama AS nama_mapel')
            ->join('penugasan_guru_gen g', 'g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id')
            ->join('ref_angkatan a', 'a.id=p.angkatan_id')
            ->join('ref_mapel m', 'm.id=p.mapel_id')
            ->where('g.guru_id', $guruId)
            ->orderBy('a.nama')->orderBy('m.nama')->orderBy('p.semester')
            ->get()->getResultArray();
    }

    public function konteks(int $paketId): array
    {
        $paket = $this->paket($paketId);
        $bab = $this->db->table('bab')
            ->select('id,nomor_bab,nama_bab')->where('paket_semester_id', $paketId)
            ->orderBy('nomor_bab')->get()->getResultArray();
        $exam = $this->db->table('ujian_pra_semester')
            ->where('paket_semester_id', $paketId)->get()->getRowArray();
        $soal = $exam === null ? [] : $this->db->table('soal_pra_semester')
            ->select('id,bab_id,urutan,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d,opsi_e,kunci,aktif')
            ->where('ujian_pra_semester_id', (int) $exam['id'])
            ->orderBy('urutan')->get()->getResultArray();
        $ringkas = [];
        foreach ($soal as $row) {
            $valid = true;
            foreach (['pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e'] as $key) {
                if (trim((string) $row[$key]) === '') $valid = false;
            }
            $ringkas[] = [
                'id' => (int) $row['id'], 'bab_id' => (int) $row['bab_id'],
                'aktif' => (int) $row['aktif'] === 1,
                'opsi_lengkap' => $valid,
                'kunci_sah' => in_array($row['kunci'], ['A','B','C','D','E'], true),
            ];
        }
        $kesiapan = AturanPraSemesterService::kesiapanBank(
            array_map(static fn (array $b): int => (int) $b['id'], $bab),
            $ringkas
        );
        $kkm = (new KkmTpService($this->db, $this->akun))
            ->informasi(['kkm_khusus' => $exam['kkm_khusus'] ?? null]);
        $terpakai = $exam && $this->db->table('percobaan_ujian')
            ->where('ujian_pra_semester_id', (int) $exam['id'])
            ->countAllResults() > 0;
        return compact('paket','bab','exam','soal','kesiapan','kkm','terpakai');
    }

    private function cekBelumDipakai(int $examId): void
    {
        if ($this->db->table('percobaan_ujian')
            ->where('ujian_pra_semester_id', $examId)->countAllResults() > 0) {
            throw new DomainException('Bank Pra-Semester sudah digunakan siswa dan tidak boleh diubah.');
        }
    }

    private function examTerkunci(int $paketId, int $guruId): array
    {
        $row = $this->db->table('ujian_pra_semester')
            ->where('paket_semester_id', $paketId)->get()->getRowArray();
        if ($row) return $row;
        $this->db->table('ujian_pra_semester')->insert([
            'paket_semester_id' => $paketId,
            'status' => 'draf',
            'jumlah_soal' => AturanPraSemesterService::MINIMAL_SOAL,
            'durasi_menit' => 60, // nilai awal draf, Admin wajib menyetel durasi saat menjadwalkan
            'kkm_khusus' => null, 'dibuat_oleh' => $guruId,
            'dibuat_pada' => gmdate('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insertID();
        if ($id < 1) throw new RuntimeException('Gagal membuat draf bank Pra-Semester.');
        return $this->db->table('ujian_pra_semester')->where('id', $id)->get()->getRowArray();
    }

    public static function validasiSoal(array $post): array
    {
        $bab = $post['bab_id'] ?? '';
        $kunci = $post['kunci'] ?? '';
        if (!is_string($bab) || !ctype_digit($bab) || (int) $bab < 1
            || !is_string($kunci) || !in_array($kunci, ['A','B','C','D','E'], true)) {
            throw new DomainException('Pilih BAB yang sah dan satu kunci jawaban A–E.');
        }
        $hasil = ['bab_id' => (int) $bab, 'kunci' => $kunci];
        foreach (['pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e'] as $kolom) {
            $value = $post[$kolom] ?? null;
            if (!is_string($value)) throw new DomainException('Pertanyaan dan opsi A–E wajib diisi.');
            $value = trim($value);
            if ($value === '' || mb_strlen($value, 'UTF-8') > 12000) {
                throw new DomainException('Pertanyaan dan semua opsi harus terisi (maksimum 12.000 karakter).');
            }
            $hasil[$kolom] = $value;
        }
        return $hasil;
    }

    public function simpanSoal(int $paketId, array $post, int $soalId = 0): void
    {
        $data = self::validasiSoal($post);
        $guruId = $this->guru();
        $this->db->transBegin();
        try {
            $this->paket($paketId);
            $bab = $this->db->table('bab')->where('id', $data['bab_id'])
                ->where('paket_semester_id', $paketId)->countAllResults();
            if ($bab !== 1) throw new DomainException('BAB tidak termasuk semester ini.');
            $exam = $this->examTerkunci($paketId, $guruId);
            $eid = (int) $exam['id'];
            $this->cekBelumDipakai($eid);
            if ($soalId > 0) {
                $ada = $this->db->table('soal_pra_semester')
                    ->where('id', $soalId)->where('ujian_pra_semester_id', $eid)
                    ->countAllResults();
                if ($ada !== 1) throw new DomainException('Soal bukan milik bank semester.');
                $this->db->table('soal_pra_semester')->where('id', $soalId)
                    ->where('ujian_pra_semester_id', $eid)->update($data);
            } else {
                $last = $this->db->table('soal_pra_semester')
                    ->selectMax('urutan')->where('ujian_pra_semester_id', $eid)
                    ->get()->getRowArray();
                $urutan = (int) ($last['urutan'] ?? 0) + 1;
                if ($urutan > 65535) throw new DomainException('Bank soal sudah terlalu besar.');
                $this->db->table('soal_pra_semester')->insert([
                    'ujian_pra_semester_id' => $eid,
                    'urutan' => $urutan, 'aktif' => 1,
                ] + $data);
            }
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => $guruId, 'peran_aktif' => 'guru',
                'peristiwa' => 'bank_pra_semester_soal',
                'catatan' => 'Bank paket #' . $paketId . ', soal '
                    . ($soalId > 0 ? ('#' . $soalId . ' diperbaiki') : 'ditambahkan') . '.',
                'waktu' => gmdate('Y-m-d H:i:s'),
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('Gagal menyimpan bank soal Pra-Semester.');
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function statusSoal(int $paketId, int $soalId, bool $aktif): void
    {
        $guruId = $this->guru();
        $this->db->transBegin();
        try {
            $this->paket($paketId);
            $exam = $this->db->table('ujian_pra_semester')
                ->where('paket_semester_id', $paketId)->get()->getRowArray();
            if (!$exam) throw new DomainException('Bank soal belum tersedia.');
            $this->cekBelumDipakai((int) $exam['id']);
            $row = $this->db->table('soal_pra_semester')
                ->where('id', $soalId)->where('ujian_pra_semester_id', (int) $exam['id'])
                ->get()->getRowArray();
            if (!$row) throw new DomainException('Soal tidak ditemukan pada semester.');
            $this->db->table('soal_pra_semester')->where('id', $soalId)
                ->update(['aktif' => $aktif ? 1 : 0]);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => $guruId, 'peran_aktif' => 'guru',
                'peristiwa' => 'bank_pra_semester_status',
                'catatan' => 'Bank paket #' . $paketId . ', soal #' . $soalId
                    . ($aktif ? ' diaktifkan.' : ' dinonaktifkan.'),
                'waktu' => gmdate('Y-m-d H:i:s'),
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('Perubahan soal tidak dapat disimpan.');
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function simpanKkm(int $paketId, ?string $raw): void
    {
        $guru = $this->guru();
        $usulan = KkmTpService::validasi($raw, true);
        $default = (new KkmTpService($this->db, $this->akun))->defaultAdmin();
        $usulan = KkmTpService::normalisasiKhusus($usulan, $default);
        if (KkmTpService::pilihEfektif($default, $usulan) === null) {
            throw new DomainException('Tetapkan KKM default Admin atau nilai khusus Guru.');
        }
        $this->db->transBegin();
        try {
            $this->paket($paketId);
            $exam = $this->examTerkunci($paketId, $guru);
            $this->cekBelumDipakai((int) $exam['id']);
            $this->db->table('ujian_pra_semester')
                ->where('id', (int) $exam['id'])->update(['kkm_khusus' => $usulan]);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => $guru, 'peran_aktif' => 'guru',
                'peristiwa' => 'kkm_pra_semester',
                'catatan' => 'KKM Pra-Semester paket #' . $paketId . ': '
                    . ($usulan === null ? 'mengikuti Admin' : (string) $usulan),
                'waktu' => gmdate('Y-m-d H:i:s'),
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('KKM Pra-Semester tidak tersimpan.');
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
