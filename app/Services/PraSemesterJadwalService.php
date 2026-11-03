<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A8-2: hanya Admin yang menetapkan jendela BOLEH MULAI dan durasi per paket.
 * Jam ujian siswa tidak dimulai di sini; timer mulai di percobaan siswa.
 */
final class PraSemesterJadwalService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    private function admin(): int
    {
        $a = ($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? '') !== 'admin'
            || ($a['status'] ?? '') !== 'aktif'
            || (int) ($a['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Pengaturan jadwal Pra-Semester hanya untuk Admin aktif.');
        }
        return (int) $a['id'];
    }

    /** @return list<array<string,mixed>> */
    public function daftar(): array
    {
        $this->admin();
        return $this->db->table('paket_semester p')
            ->select('p.id AS paket_id,p.semester,p.status AS paket_status,p.angkatan_id,p.mapel_id,'
                . 'a.nama AS nama_gen,m.nama AS nama_mapel,e.id AS ujian_id,e.status AS ujian_status,'
                . 'e.mulai_pada,e.selesai_pada,e.durasi_menit,e.jumlah_soal,e.kkm_khusus')
            ->join('ref_angkatan a','a.id=p.angkatan_id')
            ->join('ref_mapel m','m.id=p.mapel_id')
            ->join('ujian_pra_semester e','e.paket_semester_id=p.id','left')
            ->orderBy('a.nama')->orderBy('m.nama')->orderBy('p.semester')
            ->get()->getResultArray();
    }

    public function lihat(int $paketId): array
    {
        $this->admin();
        $row = $this->db->table('paket_semester p')
            ->select('p.id AS paket_id,p.semester,p.status AS paket_status,p.angkatan_id,p.mapel_id,'
                . 'a.nama AS nama_gen,m.nama AS nama_mapel,e.id AS ujian_id,e.status AS ujian_status,'
                . 'e.mulai_pada,e.selesai_pada,e.durasi_menit,e.jumlah_soal,e.kkm_khusus')
            ->join('ref_angkatan a','a.id=p.angkatan_id')
            ->join('ref_mapel m','m.id=p.mapel_id')
            ->join('ujian_pra_semester e','e.paket_semester_id=p.id','left')
            ->where('p.id',$paketId)->get()->getRowArray();
        if (!$row) throw new DomainException('Paket Pra-Semester tidak ditemukan.');
        return $row;
    }

    public static function validasiForm(mixed $awal, mixed $akhir, mixed $durasi): array
    {
        if (!is_string($awal) || !is_string($akhir)
            || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $awal)
            || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $akhir)
            || !is_string($durasi) || !preg_match('/^[0-9]{1,3}$/D', $durasi)
            || (int) $durasi < 5 || (int) $durasi > 180) {
            throw new DomainException('Isi jadwal mulai/akhir WIB dan durasi 5–180 menit.');
        }
        $mulai = str_replace('T',' ',$awal) . ':00';
        $selesai = str_replace('T',' ',$akhir) . ':00';
        // Validasi tanggal riil dan urutan; awal/akhir adalah jendela boleh mulai.
        AturanPraSemesterService::jadwal($mulai,$selesai,time());
        return ['mulai'=>$mulai,'selesai'=>$selesai,'durasi'=>(int) $durasi];
    }

    public function simpan(int $paketId, mixed $awal, mixed $akhir, mixed $durasi): void
    {
        $admin = $this->admin();
        $input = self::validasiForm($awal,$akhir,$durasi);
        $cek = AturanPraSemesterService::jadwal($input['mulai'],$input['selesai'],time());
        if ($cek['status'] !== 'belum_mulai') {
            throw new DomainException('Jadwal baru harus dimulai pada waktu yang akan datang.');
        }

        $this->db->transBegin();
        try {
            $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $paket = $this->db->query('SELECT id,angkatan_id,mapel_id,semester,status '
                . 'FROM paket_semester WHERE id=?' . $lock,[$paketId])->getRowArray();
            if (!$paket || $paket['status'] !== 'aktif') {
                throw new DomainException('Jadwal hanya dapat disimpan untuk paket semester aktif.');
            }
            $exam = $this->db->query('SELECT * FROM ujian_pra_semester '
                . 'WHERE paket_semester_id=?' . $lock,[$paketId])->getRowArray();
            if (!$exam || $exam['status'] === 'ditutup') {
                throw new DomainException('Guru perlu menyiapkan bank Pra-Semester terlebih dahulu.');
            }
            $eid = (int) $exam['id'];
            $dipakai = $this->db->table('percobaan_ujian')
                ->where('ujian_pra_semester_id',$eid)->countAllResults();
            if ($dipakai > 0) {
                throw new DomainException('Jadwal tidak dapat diubah setelah ada percobaan siswa.');
            }

            $bab = $this->db->table('bab')->select('id')->where('paket_semester_id',$paketId)
                ->get()->getResultArray();
            $soal = $this->db->table('soal_pra_semester')
                ->where('ujian_pra_semester_id',$eid)->get()->getResultArray();
            $daftar = [];
            foreach ($soal as $s) {
                $opsiLengkap = trim((string) $s['pertanyaan']) !== '';
                foreach (['opsi_a','opsi_b','opsi_c','opsi_d','opsi_e'] as $k) {
                    if (trim((string) $s[$k]) === '') $opsiLengkap = false;
                }
                $daftar[] = [
                    'id' => (int) $s['id'],'bab_id'=>(int) $s['bab_id'],
                    'aktif' => (int) $s['aktif'] === 1,
                    'opsi_lengkap' => $opsiLengkap,
                    'kunci_sah' => in_array($s['kunci'],['A','B','C','D','E'],true),
                ];
            }
            $ready = AturanPraSemesterService::kesiapanBank(
                array_map(static fn(array $x): int => (int) $x['id'],$bab),$daftar
            );
            if (!$ready['siap'] || $ready['jumlah_sah'] < (int) $exam['jumlah_soal']) {
                throw new DomainException('Bank Guru belum siap: minimal 30 soal dan setiap BAB terwakili.');
            }
            $kkm = (new KkmTpService($this->db, $this->akun))
                ->informasi($exam);
            if ($kkm['efektif'] === null) {
                throw new DomainException('KKM Pra-Semester belum disiapkan Guru/Admin.');
            }

            // Hindari dua ujian pada waktu bersamaan untuk Gen dan semester yang sama.
            $tabrakan = $this->db->table('ujian_pra_semester u')
                ->join('paket_semester p','p.id=u.paket_semester_id')
                ->where('p.angkatan_id',(int)$paket['angkatan_id'])
                ->where('p.semester',(int)$paket['semester'])
                ->where('u.id !=',$eid)
                ->where('u.status','siap')
                ->where('u.mulai_pada <',$input['selesai'])
                ->where('u.selesai_pada >',$input['mulai'])
                ->countAllResults();
            if ($tabrakan > 0) {
                throw new DomainException('Jadwal bertabrakan dengan mapel lain pada Gen dan semester yang sama.');
            }

            $this->db->table('ujian_pra_semester')->where('id',$eid)->update([
                'status'=>'siap',
                'mulai_pada'=>$input['mulai'],
                'selesai_pada'=>$input['selesai'],
                'durasi_menit'=>$input['durasi'],
            ]);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$admin,'peran_aktif'=>'admin',
                'peristiwa'=>'jadwal_pra_semester',
                'catatan'=>'Paket #' . $paketId . ' mulai ' . $input['mulai']
                    . ' hingga ' . $input['selesai']
                    . ' WIB, durasi ' . $input['durasi'] . ' menit per peserta.',
                'waktu'=>gmdate('Y-m-d H:i:s'),
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('Jadwal tidak dapat disimpan.');
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
