<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Pratinjau ATP v2.5 read-only. Berkas tidak disimpan dan DB tidak ditulis.
 * Konfirmasi transaksi (tahap berikutnya) wajib menghitung ulang diff/otorisasi.
 */
final class AtpImporPratinjauService
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

    /** @return array<string,mixed> */
    public function pratinjau(int $paketId, string $file): array
    {
        $akun = ($this->akunAktif)();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Guru aktif yang dapat melihat pratinjau impor ATP.');
        }
        if ($paketId < 1
            || ! (new PaketSemesterGuruService($this->db, $this->akunAktif))->skemaV4Siap()) {
            throw new DomainException('Paket atau skema database v4 R2 belum valid.');
        }

        $paket = $this->db->table('paket_semester p')
            ->select('p.id,p.semester,p.angkatan_id,p.mapel_id,p.kurikulum_id,p.tahun_ajaran_id,'
                . 'a.kode AS kode_gen,a.nama AS nama_gen,a.semester_mulai,'
                . 'm.kode AS kode_mapel,m.nama AS nama_mapel')
            ->join('penugasan_guru_gen g',
                'g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id')
            ->join('ref_angkatan a', 'a.id=p.angkatan_id')
            ->join('ref_mapel m', 'm.id=p.mapel_id')
            ->where('p.id', $paketId)
            ->where('p.status !=', 'arsip')
            ->where('g.guru_id', (int) $akun['id'])
            ->get()->getRowArray();
        if ($paket === null || (int) $paket['semester'] < (int) $paket['semester_mulai']) {
            throw new DomainException('Paket di luar penugasan Gen–Mapel atau semester mulai Guru.');
        }

        $cp = $this->db->table('cp_mapel c')
            ->select('c.id')
            ->join('ref_fase f', 'f.id=c.fase_id')
            ->where('c.kurikulum_id', $paket['kurikulum_id'])
            ->where('c.mapel_id', $paket['mapel_id'])
            ->where('f.kode', 'D')
            ->get()->getResultArray();
        if (count($cp) !== 1) {
            throw new DomainException('CP operasional Fase D tidak valid.');
        }
        $elemen = $this->db->table('cp_mapel_elemen ce')
            ->select('v.elemen')
            ->join('ref_cp_versi v', 'v.id=ce.cp_versi_id')
            ->where('ce.cp_mapel_id', (int) $cp[0]['id'])
            ->where('v.status', 'aktif')
            ->get()->getResultArray();
        if ($elemen === []) {
            throw new DomainException('Belum tersedia referensi elemen CP aktif.');
        }

        $hasil = AtpExcelParserService::baca($file, (string) $paket['kode_mapel'],
            (int) $paket['semester_mulai'], (string) $paket['kode_gen'],
            array_column($elemen, 'elemen'));

        // Paket yang dibandingkan harus identik Gen–Mapel–TA–Kurikulum, bukan seluruh G1.
        $daftarPaket = $this->db->table('paket_semester')
            ->select('id,semester,status')
            ->where('angkatan_id', $paket['angkatan_id'])
            ->where('mapel_id', $paket['mapel_id'])
            ->where('kurikulum_id', $paket['kurikulum_id'])
            ->where('tahun_ajaran_id', $paket['tahun_ajaran_id'])
            ->where('status !=', 'arsip')
            ->get()->getResultArray();
        $paketSem = [];
        foreach ($daftarPaket as $p) {
            $s = (int) $p['semester'];
            if (isset($paketSem[$s])) {
                throw new DomainException('Paket semester aktif ganda. Hubungi Admin.');
            }
            $paketSem[$s] = (int) $p['id'];
        }

        $babTerdaftar = [];
        $tpTerdaftar = [];
        if ($paketSem !== []) {
            $babTerdaftar = $this->db->table('bab b')
                ->select('b.id,b.paket_semester_id,b.nomor_bab,b.nama_bab,b.deskripsi_bab')
                ->whereIn('b.paket_semester_id', array_values($paketSem))
                ->get()->getResultArray();
            $tpTerdaftar = $this->db->table('tp t')
                ->select('t.id,t.paket_semester_id,t.bab_id,t.kode_tp,t.jenis_tp,'
                    . 't.nama_tp,t.deskripsi_tp,t.status')
                ->whereIn('t.paket_semester_id', array_values($paketSem))
                ->get()->getResultArray();
        }
        $semesterDariPaket = array_flip($paketSem);
        $babIndeks = [];
        $babId = [];
        foreach ($babTerdaftar as $b) {
            $s = (int) ($semesterDariPaket[(int) $b['paket_semester_id']] ?? 0);
            $n = (int) $b['nomor_bab'];
            $babIndeks[$s][$n] = $b;
            $babId[(int) $b['id']] = [$s, $n];
        }
        $tpIndeks = [];
        foreach ($tpTerdaftar as $t) {
            $s = (int) ($semesterDariPaket[(int) $t['paket_semester_id']] ?? 0);
            $tpIndeks[$s][(string) $t['kode_tp']] = $t;
        }

        $batasBab = BatasNamaAtpService::aktual($this->db, 'bab');
        $batasTp = BatasNamaAtpService::aktual($this->db, 'tp');
        $daftar = [];
        $jumlah = ['baru' => 0, 'ubah' => 0, 'tetap' => 0, 'galat' => 0];
        foreach ($hasil['bab'] as $b) {
            $s = $b['semester'];
            $lama = $babIndeks[$s][$b['nomor']] ?? null;
            $status = $lama === null ? 'baru'
                : (($lama['nama_bab'] === $b['nama']
                    && $lama['deskripsi_bab'] === $b['deskripsi']) ? 'tetap' : 'ubah');
            $catatan = '';
            if (! isset($paketSem[$s])) {
                $status = 'galat';
                $catatan = 'Paket draf semester ini belum dibuat atau sudah diarsipkan.';
            } elseif (mb_strlen($b['nama']) > $batasBab) {
                $status = 'galat';
                $catatan = 'Database saat ini membatasi nama BAB hingga ' . $batasBab . ' karakter.';
            }
            if ($status === 'ubah' && $lama !== null) {
                foreach ($tpTerdaftar as $t) {
                    if ((int) $t['bab_id'] === (int) $lama['id']
                        && $t['status'] === 'siap') {
                        $status = 'galat';
                        $catatan = 'BAB memiliki TP Siap; perubahan perlu mekanisme koreksi.';
                        break;
                    }
                }
            }
            $daftar[] = [
                'jenis' => 'BAB', 'semester' => $s, 'baris' => $b['baris'],
                'kode' => 'BAB ' . $b['nomor'], 'nama' => $b['nama'],
                'status' => $status, 'catatan' => $catatan,
            ];
            $jumlah[$status]++;
        }
        foreach ($hasil['tp'] as $t) {
            $s = $t['semester'];
            $lama = $tpIndeks[$s][$t['kode']] ?? null;
            $status = $lama === null ? 'baru'
                : (($lama['nama_tp'] === $t['nama']
                    && $lama['deskripsi_tp'] === $t['deskripsi']
                    && $lama['jenis_tp'] === $t['jenis']
                    && isset($babId[(int) $lama['bab_id']])
                    && $babId[(int) $lama['bab_id']] === [$s, $t['nomor_bab']])
                    ? 'tetap' : 'ubah');
            $catatan = '';
            if (! isset($paketSem[$s])) {
                $status = 'galat';
                $catatan = 'Paket semester ini belum dibuat atau sudah diarsipkan.';
            } elseif (mb_strlen($t['nama']) > $batasTp) {
                $status = 'galat';
                $catatan = 'Database membatasi nama TP hingga ' . $batasTp . ' karakter.';
            } elseif ($lama !== null && $lama['status'] === 'arsip') {
                $status = 'galat';
                $catatan = 'Kode TP sudah dipakai pada TP arsip; tidak boleh ditimpa.';
            } elseif ($lama !== null && $lama['status'] === 'siap' && $status === 'ubah') {
                $status = 'galat';
                $catatan = 'TP Siap tidak boleh diubah oleh impor biasa.';
            }
            $daftar[] = [
                'jenis' => 'TP', 'semester' => $s, 'baris' => $t['baris'],
                'kode' => $t['kode'], 'nama' => $t['nama'],
                'status' => $status, 'catatan' => $catatan,
            ];
            $jumlah[$status]++;
        }

        return [
            'gen' => $paket['nama_gen'], 'mapel' => $paket['nama_mapel'],
            'semester_paket' => (int) $paket['semester'],
            'jumlah' => $jumlah, 'baris' => $daftar,
            'kosong' => $hasil['jumlah_bab'] === 0 && $hasil['jumlah_tp'] === 0,
            'boleh_konfirmasi' => false,
        ];
    }
}
