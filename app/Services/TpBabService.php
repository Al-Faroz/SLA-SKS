<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Membuat TP DRAF pada satu BAB v2.5 milik Guru G1.
 * Proses kesiapan/media/ujian dilakukan pada tahap fitur terpisah.
 */
final class TpBabService
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

    /**
     * @return array{nama:string,deskripsi:string,jenis:string}
     */
    public static function validasiInput(int $babId, string $nama, string $deskripsi, string $jenis): array
    {
        $nama = trim($nama);
        $deskripsi = trim($deskripsi);
        if ($babId < 1 || $nama === '' || mb_strlen($nama) > 50
            || $deskripsi === '' || mb_strlen($deskripsi) > 225
            || ! in_array($jenis, ['esensial', 'non_esensial'], true)) {
            throw new DomainException('Isi nama TP (1–50 karakter), deskripsi (1–225 karakter), dan pilih jenis TP yang sah.');
        }
        return ['nama' => $nama, 'deskripsi' => $deskripsi, 'jenis' => $jenis];
    }

    /** @return array{id:int,kode_tp:string} */
    public function buat(int $babId, string $nama, string $deskripsi, string $jenis): array
    {
        $data = self::validasiInput($babId, $nama, $deskripsi, $jenis);
        $nama = $data['nama'];
        $deskripsi = $data['deskripsi'];
        $jenis = $data['jenis'];

        $akun = ($this->akunAktif)();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Guru aktif yang dapat menambahkan TP.');
        }
        if (! $this->db->tableExists('bab') || ! $this->db->tableExists('cp_mapel')) {
            throw new DomainException('Database SLA-SKS v4 belum terpasang.');
        }

        $this->db->transBegin();
        try {
            // Keanggotaan BAB dan G1 diturunkan server; jangan menerima paket/Gen dari POST.
            $objek = $this->db->query(
                'SELECT b.id AS bab_id,b.paket_semester_id,b.nomor_bab,b.kelas_id,'
                . ' p.angkatan_id,p.mapel_id,p.kurikulum_id,p.semester,p.status AS status_paket,'
                . ' m.kode AS kode_mapel,k.nama AS nama_kelas,k.fase_id,a.semester_mulai'
                . ' FROM bab b'
                . ' JOIN paket_semester p ON p.id=b.paket_semester_id'
                . ' JOIN ref_mapel m ON m.id=p.mapel_id'
                . ' JOIN ref_kelas k ON k.id=b.kelas_id'
                . ' JOIN ref_angkatan a ON a.id=p.angkatan_id'
                . ' JOIN penugasan_guru_gen g ON g.angkatan_id=p.angkatan_id'
                . ' AND g.mapel_id=p.mapel_id'
                . ' WHERE b.id=? AND g.guru_id=? FOR UPDATE',
                [$babId, (int) $akun['id']]
            )->getRowArray();
            if ($objek === null || $objek['status_paket'] === 'arsip') {
                throw new DomainException('BAB tidak ditemukan atau bukan bagian dari penugasan Anda.');
            }
            if ((int) $objek['semester'] < (int) $objek['semester_mulai']) {
                throw new DomainException('Semester sebelum semester mulai Gen tidak boleh diisi TP.');
            }

            // Kunci seluruh paket agar urutan_atp tetap unik antar-BAB dalam paket sama.
            $this->db->query('SELECT id FROM paket_semester WHERE id=? FOR UPDATE',
                [(int) $objek['paket_semester_id']])->getRowArray();

            $cp = $this->db->table('cp_mapel')
                ->select('id')
                ->where('kurikulum_id', (int) $objek['kurikulum_id'])
                ->where('mapel_id', (int) $objek['mapel_id'])
                ->where('fase_id', (int) $objek['fase_id'])
                ->get()->getResultArray();
            if (count($cp) !== 1) {
                throw new DomainException('CP operasional mapel belum tersedia. Admin perlu melengkapinya sebelum Guru menambah TP.');
            }
            if ($this->db->table('cp_mapel_elemen e')
                ->join('ref_cp_versi v', 'v.id=e.cp_versi_id')
                ->where('e.cp_mapel_id', (int) $cp[0]['id'])
                ->where('v.status', 'aktif')->countAllResults() < 1) {
                throw new DomainException('CP mapel belum mempunyai elemen versi aktif yang disetujui Admin.');
            }
            $urutan = $this->db->table('tp')
                ->select('urutan_dalam_bab')
                ->where('bab_id', $babId)
                ->orderBy('urutan_dalam_bab', 'ASC')
                ->get()->getResultArray();
            $nomorTp = KodeTpService::urutanBerikutnya($urutan);
            $kelas = KodeTpService::kelasDariNama((string) $objek['nama_kelas']);
            $kode = KodeTpService::bentuk(
                (string) $objek['kode_mapel'],
                $kelas,
                (int) $objek['nomor_bab'],
                $nomorTp
            );

            // urutan_atp kolom legacy yang NOT NULL dan unik per paket.
            // Termasuk TP arsip, agar histori/constraint lama tidak terganggu.
            $max = $this->db->table('tp')
                ->selectMax('urutan_atp', 'akhir')
                ->where('paket_semester_id', (int) $objek['paket_semester_id'])
                ->get()->getRowArray();
            $urutanAtp = (int) ($max['akhir'] ?? 0) + 1;
            if ($urutanAtp > 65535) {
                throw new DomainException('Urutan ATP semester telah mencapai batas penyimpanan.');
            }

            $this->db->table('tp')->insert([
                'paket_semester_id' => (int) $objek['paket_semester_id'],
                'bab_id' => $babId,
                'cp_mapel_id' => (int) $cp[0]['id'],
                'jenis_tp' => $jenis,
                'nama_tp' => $nama,
                'deskripsi_tp' => $deskripsi,
                'urutan_dalam_bab' => $nomorTp,
                'kode_tp' => $kode,
                // Kolom warisan masih wajib pada skema v4; draf belum siap/publikasi.
                'judul' => $nama,
                'rumusan' => $deskripsi,
                'indikator' => '',
                'urutan_atp' => $urutanAtp,
                'status' => 'draf',
                'struktur_versi' => 1,
                'bobot_resume' => 20,
                'bobot_tugas' => 20,
                'bobot_sumatif' => 60,
                'diag_tayang' => 10,
                'sum_tayang' => 30,
                'sum_maks_percobaan' => 2,
            ]);
            if ($this->db->transStatus() === false || $this->db->affectedRows() !== 1) {
                throw new DomainException('TP gagal disimpan. Periksa kembali data.');
            }
            $id = (int) $this->db->insertID();
            $this->db->transCommit();
            return ['id' => $id, 'kode_tp' => $kode];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
