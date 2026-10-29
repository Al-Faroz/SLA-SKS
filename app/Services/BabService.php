<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/** CRUD tahap awal: membuat BAB pada paket yang sudah dimiliki Guru G1. */
final class BabService
{
    private BaseConnection $db;

    /** @var callable():?array */
    private $akunAktif;

    /** @param (callable():?array)|null $akunAktif */
    public function __construct(?BaseConnection $db = null, ?callable $akunAktif = null)
    {
        $this->db = $db ?? db_connect();
        $this->akunAktif = $akunAktif ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }


    /** @return array{nama:string,deskripsi:string} */
    public static function validasiInput(int $paketId, string $nama, string $deskripsi): array
    {
        $nama = trim($nama);
        $deskripsi = trim($deskripsi);
        if ($paketId < 1 || $nama === '' || mb_strlen($nama) > 50
            || $deskripsi === '' || mb_strlen($deskripsi) > 225) {
            throw new DomainException('Nama BAB wajib 1–50 karakter dan deskripsi wajib 1–225 karakter.');
        }

        return ['nama' => $nama, 'deskripsi' => $deskripsi];
    }

    public function buat(int $paketId, string $nama, string $deskripsi): int
    {
        $data = self::validasiInput($paketId, $nama, $deskripsi);
        $nama = $data['nama'];
        $deskripsi = $data['deskripsi'];
        $akun = ($this->akunAktif)();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Guru aktif yang dapat membuat BAB.');
        }
        if (! $this->db->tableExists('bab')) {
            throw new DomainException('Database pola baru belum terpasang. Hubungi Admin.');
        }
        $batasKolom = BatasNamaAtpService::aktual($this->db, 'bab');
        if (mb_strlen($nama) > $batasKolom) {
            throw new DomainException('Nama BAB maksimal ' . $batasKolom
                . ' karakter pada database terpasang. Pelebaran ke 50 memerlukan patch SQL.');
        }

        $guruId = (int) $akun['id'];
        $this->db->transBegin();
        try {
            // ID paket tidak dipercaya; sumber hak hanya pengampu aktif Gen–Mapel.
            $paket = $this->db->query(
                'SELECT p.* FROM paket_semester p '
                . 'JOIN penugasan_guru_gen g ON g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id '
                . 'WHERE p.id=? AND g.guru_id=? FOR UPDATE',
                [$paketId, $guruId]
            )->getRowArray();
            if ($paket === null || $paket['status'] === 'arsip') {
                throw new DomainException('Paket semester tidak tersedia untuk penugasan Anda.');
            }

            $semester = (int) $paket['semester'];
            $dataGen = $this->db->table('ref_angkatan')
                ->select('semester_mulai')
                ->where('id', $paket['angkatan_id'])
                ->get()->getRowArray();
            if ($dataGen === null) {
                throw new DomainException('Data semester mulai Gen tidak ditemukan.');
            }
            $semesterMulai = (int) $dataGen['semester_mulai'];
            $referensiSemester = $this->db->table('ref_semester')->select('kelas_id')
                ->where('id', $semester)->get()->getRowArray();
            if ($referensiSemester === null) {
                throw new DomainException('Referensi tingkat kelas semester belum tersedia.');
            }
            $kelasId = (int) $referensiSemester['kelas_id'];
            $awal = $semester % 2 === 1 ? $semester : $semester - 1;

            // Kunci paket dua semester pada kelas sama agar nomor tidak berlomba.
            $this->db->query(
                'SELECT id FROM paket_semester WHERE angkatan_id=? AND mapel_id=? '
                . 'AND kurikulum_id=? AND tahun_ajaran_id=? AND semester IN (?,?) ORDER BY id FOR UPDATE',
                [(int) $paket['angkatan_id'], (int) $paket['mapel_id'],
                    (int) $paket['kurikulum_id'], (int) $paket['tahun_ajaran_id'], $awal, $awal + 1]
            )->getResultArray();

            $terdaftar = $this->db->table('bab b')
                ->select('b.nomor_bab, p.semester')
                ->join('paket_semester p', 'p.id=b.paket_semester_id')
                ->where('b.angkatan_id', $paket['angkatan_id'])
                ->where('b.mapel_id', $paket['mapel_id'])
                ->where('b.kurikulum_id', $paket['kurikulum_id'])
                ->where('b.tahun_ajaran_id', $paket['tahun_ajaran_id'])
                ->where('b.kelas_id', $kelasId)
                ->get()->getResultArray();
            $nomor = AturanBabService::nomorBerikutnya($semester, $terdaftar, $semesterMulai);

            $this->db->table('bab')->insert([
                'paket_semester_id' => $paketId,
                'angkatan_id' => $paket['angkatan_id'],
                'mapel_id' => $paket['mapel_id'],
                'kurikulum_id' => $paket['kurikulum_id'],
                'tahun_ajaran_id' => $paket['tahun_ajaran_id'],
                'kelas_id' => $kelasId,
                'nomor_bab' => $nomor,
                'nama_bab' => $nama,
                'deskripsi_bab' => $deskripsi,
                'dibuat_pada' => date('Y-m-d H:i:s'),
            ]);
            if ($this->db->transStatus() === false || $this->db->affectedRows() !== 1) {
                throw new DomainException('BAB gagal disimpan. Periksa kembali data dan coba lagi.');
            }
            $id = (int) $this->db->insertID();
            $this->db->transCommit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
