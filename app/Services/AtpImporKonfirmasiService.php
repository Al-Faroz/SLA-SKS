<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Konfirmasi ATP manual dalam satu transaksi MySQL/MariaDB.
 * Mengunci penugasan/paket, menghitung ulang diff, tidak menyentuh TP Siap,
 * tidak membuat Materi/ujian/nilai, dan tanpa operasi DELETE akademik.
 */
final class AtpImporKonfirmasiService
{
    private BaseConnection $db;

    /** @var callable():?array */
    private $akunAktif;

    /** @param (callable():?array)|null $akunAktif */
    public function __construct(?BaseConnection $db = null, ?callable $akunAktif = null)
    {
        $this->db = $db ?? db_connect();
        $this->akunAktif = $akunAktif
            ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    /** @return array{baru_bab:int,ubah_bab:int,baru_tp:int,ubah_tp:int,tetap:int} */
    public function simpan(int $paketId, string $file, string $fingerprint): array
    {
        $akun = ($this->akunAktif)();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Guru aktif yang dapat mengonfirmasi impor ATP.');
        }
        if ($paketId < 1 || preg_match('/\A[a-f0-9]{64}\z/D', $fingerprint) !== 1) {
            throw new DomainException('Data konfirmasi ATP tidak sah.');
        }
        if (! (new PaketSemesterGuruService($this->db, $this->akunAktif))->skemaV4Siap()) {
            throw new DomainException('Database v4 R2 diperlukan untuk impor ATP.');
        }

        // Setiap konfirmasi adalah kelompok transaksi independen. Mode strict
        // CI4 menyimpan flag kegagalan untuk transaksi berikutnya pada koneksi
        // yang sama, meskipun rollback telah berhasil.
        $this->db->transStrict(false);
        $this->db->transBegin();
        try {
            // Penugasan merupakan satu-satunya sumber hak akses; FOR UPDATE
            // menyerialkan pergantian pengampu dan dua konfirmasi bersamaan.
            $g1 = $this->db->query(
                'SELECT g.id,p.angkatan_id,p.mapel_id,p.kurikulum_id'
                . ' FROM paket_semester p'
                . ' JOIN penugasan_guru_gen g ON g.angkatan_id=p.angkatan_id'
                . ' AND g.mapel_id=p.mapel_id'
                . ' WHERE p.id=? AND g.guru_id=? AND p.status<>? FOR UPDATE',
                [$paketId, (int) $akun['id'], 'arsip']
            )->getRowArray();
            if ($g1 === null) {
                throw new DomainException('Paket/penugasan Guru sudah berubah. Ulangi pratinjau.');
            }
            $paketTerkunci = $this->db->query(
                'SELECT id,semester,tahun_ajaran_id FROM paket_semester'
                . ' WHERE angkatan_id=? AND mapel_id=? AND kurikulum_id=?'
                . ' AND status<>? ORDER BY id FOR UPDATE',
                [(int) $g1['angkatan_id'], (int) $g1['mapel_id'],
                 (int) $g1['kurikulum_id'], 'arsip']
            )->getResultArray();

            // Periksa ulang seluruh file dan state di dalam transaksi, sehingga
            // snapshot pratinjau tidak boleh dijadikan sumber kebenaran.
            $preview = (new AtpImporPratinjauService($this->db, $this->akunAktif))
                ->pratinjau($paketId, $file);
            if (! hash_equals($fingerprint, (string) $preview['fingerprint'])) {
                throw new DomainException('Data akademik berubah setelah pratinjau. Unggah Excel kembali.');
            }
            if (! $preview['boleh_konfirmasi'] || (int) $preview['jumlah']['galat'] !== 0) {
                throw new DomainException('File tidak berisi perubahan sah atau masih memiliki galat.');
            }
            $paketSem = $preview['paket_semester'];
            if (count($paketTerkunci) !== count($paketSem)) {
                throw new DomainException('Paket semester berubah saat konfirmasi.');
            }
            foreach ($paketTerkunci as $p) {
                $n = (int) $p['semester'];
                if (($paketSem[$n] ?? null) !== (int) $p['id']
                    || ($preview['tahun_semester'][$n] ?? null) !== (int) $p['tahun_ajaran_id']) {
                    throw new DomainException('Paket/Tahun Ajaran semester berubah. Unggah ulang ATP.');
                }
            }
            // Konfirmasi hanya menulis semester yang 100% kosong pada
            // pratinjau terbaru. Semester lain "tetap" dilewati, tak ditimpa.
            foreach ($preview['mode_semester'] as $mode) {
                if (! in_array($mode, ['baru', 'tetap'], true)) {
                    throw new DomainException('Semester tidak memenuhi aturan impor awal.');
                }
            }
            $hasil = [
                'baru_bab' => 0, 'ubah_bab' => 0,
                'baru_tp' => 0, 'ubah_tp' => 0, 'tetap' => 0,
            ];
            $refKelas = $this->db->table('ref_semester')
                ->select('id,kelas_id')->whereIn('id', [1,2,3,4,5,6])
                ->get()->getResultArray();
            $kelas = [];
            foreach ($refKelas as $r) {
                $kelas[(int) $r['id']] = (int) $r['kelas_id'];
            }

            // Semua baris existing hanya boleh muncul sebagai "tetap" dan
            // sama sekali tidak dapat dijadikan sasaran UPDATE dari Excel.
            // Inilah pembatas tegas jalur Excel vs editor manual.

            // Bagian A: INSERT hanya semester kosong, tanpa UPDATE lama.
            // Verifikasi sekali per paket sebelum mulai INSERT batch; jangan
            // memeriksa ulang setelah BAB pertama karena batch ini sendiri.
            foreach ($preview['mode_semester'] as $semester => $mode) {
                if ($mode !== 'baru') {
                    continue;
                }
                $pid = $paketSem[$semester] ?? null;
                if ($pid === null
                    || $this->db->table('bab')->where('paket_semester_id', $pid)->countAllResults() !== 0
                    || $this->db->table('tp')->where('paket_semester_id', $pid)->countAllResults() !== 0) {
                    throw new DomainException('Semester ' . $semester
                        . ' sudah memiliki BAB/TP. Impor dibatalkan.');
                }
            }
            $babId = [];
            foreach ($preview['rencana']['bab'] as $b) {
                $s = (int) $b['semester'];
                $n = (int) $b['nomor'];
                if (($preview['mode_semester'][$s] ?? '') === 'tetap') {
                    $hasil['tetap']++;
                    continue;
                }
                if (($preview['mode_semester'][$s] ?? '') !== 'baru'
                    || ! isset($paketSem[$s], $kelas[$s], $preview['tahun_semester'][$s])) {
                    throw new DomainException('SEMESTER ' . $s . ': konteks paket tidak valid.');
                }
                $this->db->table('bab')->insert([
                    'paket_semester_id' => (int) $paketSem[$s],
                    'angkatan_id' => (int) $g1['angkatan_id'],
                    'mapel_id' => (int) $g1['mapel_id'],
                    'kurikulum_id' => (int) $g1['kurikulum_id'],
                    'tahun_ajaran_id' => (int) $preview['tahun_semester'][$s],
                    'kelas_id' => $kelas[$s],
                    'nomor_bab' => $n,
                    'nama_bab' => $b['nama'],
                    'deskripsi_bab' => $b['deskripsi'],
                    'dibuat_pada' => date('Y-m-d H:i:s'),
                ]);
                $id = (int) $this->db->insertID();
                if ($this->db->transStatus() === false || $id < 1) {
                    throw new RuntimeException('Gagal membuat BAB baru.');
                }
                $hasil['baru_bab']++;
                $babId[$s][$n] = $id;
            }

            $atpTerakhir = [];
            foreach ($paketSem as $s => $pid) {
                $row = $this->db->table('tp')->selectMax('urutan_atp', 'maks')
                    ->where('paket_semester_id', $pid)->get()->getRowArray();
                $atpTerakhir[$s] = (int) ($row['maks'] ?? 0);
            }
            foreach ($preview['rencana']['tp'] as $t) {
                $s = (int) $t['semester'];
                $n = (int) $t['nomor_bab'];
                $kode = (string) $t['kode'];
                if (($preview['mode_semester'][$s] ?? '') === 'tetap') {
                    $hasil['tetap']++;
                    continue;
                }
                if (($preview['mode_semester'][$s] ?? '') !== 'baru'
                    || ! isset($paketSem[$s], $babId[$s][$n])) {
                    throw new DomainException('SEMESTER ' . $s . ' baris ' . $t['baris']
                        . ': BAB atau paket sasaran tidak tersedia.');
                }
                if (++$atpTerakhir[$s] > 65535) {
                    throw new DomainException('Urutan ATP paket sudah mencapai batas.');
                }
                $this->db->table('tp')->insert([
                    'jenis_tp' => $t['jenis'],
                    'nama_tp' => $t['nama'],
                    'deskripsi_tp' => $t['deskripsi'],
                    'judul' => $t['nama'],
                    'rumusan' => $t['deskripsi'],
                    'paket_semester_id' => (int) $paketSem[$s],
                    'bab_id' => $babId[$s][$n],
                    'cp_mapel_id' => (int) $preview['cp_mapel_id'],
                    'urutan_dalam_bab' => (int) $t['urutan'],
                    'kode_tp' => $kode,
                    'indikator' => '',
                    'urutan_atp' => $atpTerakhir[$s],
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
                    throw new RuntimeException('Gagal membuat TP baru.');
                }
                $hasil['baru_tp']++;
            }

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi penyimpanan BAB/TP gagal.');
            }
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => (int) $akun['id'],
                'peran_aktif' => 'guru',
                'peristiwa' => 'impor_atp_excel_konfirmasi',
                'catatan' => 'ATP awal; paket acuan=' . $paketId . '; BAB baru=' . $hasil['baru_bab']
                    . ', ubah=' . $hasil['ubah_bab'] . '; TP baru=' . $hasil['baru_tp']
                    . ', ubah=' . $hasil['ubah_tp'] . '; checksum preview='
                    . substr($fingerprint, 0, 16),
                'alamat_ip' => service('request')->getIPAddress(),
                'waktu' => gmdate('Y-m-d H:i:s'),
            ]);
            if ($this->db->transStatus() === false || $this->db->transCommit() === false) {
                throw new RuntimeException('Penyimpanan ATP gagal disahkan.');
            }
            return $hasil;
        } catch (Throwable $e) {
            $this->db->transRollback();
            // Mode manual transBegin/transRollback tidak menjalankan reset flag
            // dari transComplete(). CI4 >=4.6 menyediakan resetTransStatus().
            $this->db->resetTransStatus();
            throw $e;
        }
    }
}
