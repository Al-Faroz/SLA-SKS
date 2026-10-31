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
        if ($paket === null) {
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
            ->select('v.id,v.elemen,v.rumusan,v.nomor_versi')
            ->join('ref_cp_versi v', 'v.id=ce.cp_versi_id')
            ->where('ce.cp_mapel_id', (int) $cp[0]['id'])
            ->where('v.status', 'aktif')
            ->orderBy('v.id', 'ASC')->get()->getResultArray();
        if ($elemen === []) {
            throw new DomainException('Belum tersedia referensi elemen CP aktif.');
        }

        $hasil = AtpExcelParserService::baca($file, (string) $paket['kode_mapel'],
            (int) $paket['semester_mulai'], (string) $paket['kode_gen'],
            array_column($elemen, 'elemen'), (int) $cp[0]['id'], $elemen);

        // Workbook berisi enam semester. Tahun Ajaran WAJIB diambil dari
        // paket semester masing-masing; tidak boleh menyalin TA paket acuan.
        $daftarPaket = $this->db->table('paket_semester')
            ->select('id,semester,status,kurikulum_id,tahun_ajaran_id')
            ->where('angkatan_id', (int) $paket['angkatan_id'])
            ->where('mapel_id', (int) $paket['mapel_id'])
            ->where('status !=', 'arsip')
            ->orderBy('semester', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();
        $paketSem = [];
        $tahunSem = [];
        $statusSem = [];
        $bukanKurikulum = [];
        foreach ($daftarPaket as $p) {
            $s = (int) $p['semester'];
            // Dua paket aktif satu semester ambigu, meskipun beda tahun/kurikulum.
            if (isset($paketSem[$s]) || isset($bukanKurikulum[$s])) {
                throw new DomainException('Semester ' . $s
                    . ' memiliki lebih dari satu paket nonarsip. Hubungi Admin.');
            }
            if ((int) $p['kurikulum_id'] !== (int) $paket['kurikulum_id']) {
                $bukanKurikulum[$s] = true;
                continue;
            }
            $paketSem[$s] = (int) $p['id'];
            $tahunSem[$s] = (int) $p['tahun_ajaran_id'];
            $statusSem[$s] = (string) $p['status'];
        }

        $babTerdaftar = [];
        $tpTerdaftar = [];
        if ($paketSem !== []) {
            $babTerdaftar = $this->db->table('bab')
                ->select('id,paket_semester_id,nomor_bab,nama_bab,deskripsi_bab')
                ->whereIn('paket_semester_id', array_values($paketSem))
                ->get()->getResultArray();
            $tpTerdaftar = $this->db->table('tp')
                ->select('id,paket_semester_id,bab_id,kode_tp,jenis_tp,'
                    . 'nama_tp,deskripsi_tp,status')
                ->whereIn('paket_semester_id', array_values($paketSem))
                ->get()->getResultArray();
        }
        // Judul dan jenis Media (F/G) menjadi Materi draf saat impor.
        // Saat unggah ulang, status Tetap harus mencocokkan rencana
        // media pula; jangan diam-diam mengabaikan perbedaan Materi.
        $materiTerdaftar = [];
        $materiTp = [];
        $tpIds = array_map('intval', array_column($tpTerdaftar, 'id'));
        if ($tpIds !== []) {
            $materiTerdaftar = $this->db->table('materi')
                ->select('id,tp_id,judul,jenis,aktif,berkas_id,url_youtube')
                ->whereIn('tp_id', $tpIds)
                ->orderBy('id', 'ASC')->get()->getResultArray();
            foreach ($materiTerdaftar as $m) {
                $materiTp[(int) $m['tp_id']][] = $m;
            }
        }
        $semesterDariPaket = array_flip($paketSem);
        $babIndeks = [];
        $babId = [];
        $babDbSem = [];
        foreach ($babTerdaftar as $b) {
            $s = (int) ($semesterDariPaket[(int) $b['paket_semester_id']] ?? 0);
            $n = (int) $b['nomor_bab'];
            $babIndeks[$s][$n] = $b;
            $babDbSem[$s][] = $b;
            $babId[(int) $b['id']] = [$s, $n];
        }
        $tpIndeks = [];
        $tpDbSem = [];
        foreach ($tpTerdaftar as $t) {
            $s = (int) ($semesterDariPaket[(int) $t['paket_semester_id']] ?? 0);
            $tpIndeks[$s][(string) $t['kode_tp']] = $t;
            $tpDbSem[$s][] = $t;
        }

        $babExcelSem = [];
        $tpExcelSem = [];
        foreach ($hasil['bab'] as $b) {
            $babExcelSem[(int) $b['semester']][] = $b;
        }
        foreach ($hasil['tp'] as $t) {
            $tpExcelSem[(int) $t['semester']][] = $t;
        }

        $batasBab = BatasNamaAtpService::aktual($this->db, 'bab');
        $batasTp = BatasNamaAtpService::aktual($this->db, 'tp');
        $daftar = [];
        $jumlah = ['baru' => 0, 'ubah' => 0, 'tetap' => 0, 'galat' => 0];
        $modeSem = [];
        $catatanSem = [];
        for ($s = 1; $s <= 6; ++$s) {
            $eb = $babExcelSem[$s] ?? [];
            $et = $tpExcelSem[$s] ?? [];
            if ($eb === [] && $et === []) {
                continue; // Tidak ada isi Excel: bukan perintah hapus atau update.
            }
            $dbBab = $babDbSem[$s] ?? [];
            $dbTp = $tpDbSem[$s] ?? [];
            $pesan = '';
            if (isset($bukanKurikulum[$s])) {
                $pesan = 'Paket semester menggunakan kurikulum berbeda. Pilih konteks Excel yang sesuai.';
            } elseif (! isset($paketSem[$s])) {
                $pesan = 'Paket semester belum ada. Buat draf enam semester dengan Tahun Ajaran masing-masing dari Peta ATP.';
            } elseif ($eb === [] || $et === []) {
                $pesan = 'Setiap semester sasaran wajib berisi BAB dan sekurangnya satu TP.';
            } elseif (array_filter($eb, static fn (array $b): bool => mb_strlen($b['nama']) > $batasBab)) {
                $pesan = 'Nama BAB melebihi batas kolom database (' . $batasBab . ' karakter).';
            } elseif (array_filter($et, static fn (array $t): bool => mb_strlen($t['nama']) > $batasTp)) {
                $pesan = 'Nama TP melebihi batas kolom database (' . $batasTp . ' karakter).';
            }

            if ($pesan !== '') {
                $modeSem[$s] = 'galat';
                $catatanSem[$s] = $pesan;
                continue;
            }

            if ($dbBab === [] && $dbTp === []) {
                $modeSem[$s] = 'baru';
                continue;
            }

            // Semester yang sudah diisi *hanya* boleh muncul sebagai status
            // Tetap bila SEMUA baris Excel identik dengan DB. Perbedaan sekecil
            // apa pun => GALAT; tidak ada UPDATE ataupun INSERT tambahan.
            $sama = count($dbBab) === count($eb)
                && count($dbTp) === count($et);
            if ($sama) {
                foreach ($eb as $b) {
                    $old = $babIndeks[$s][(int) $b['nomor']] ?? null;
                    if ($old === null || $old['nama_bab'] !== $b['nama']
                        || $old['deskripsi_bab'] !== $b['deskripsi']) {
                        $sama = false;
                        break;
                    }
                }
            }
            if ($sama) {
                foreach ($et as $t) {
                    $old = $tpIndeks[$s][(string) $t['kode']] ?? null;
                    $materiSesuai = false;
                    if ($old !== null) {
                        $daftarMateri = $materiTp[(int) $old['id']] ?? [];
                        $jenisMedia = [
                            'YouTube' => 'youtube', 'PDF' => 'pdf',
                            'Audio' => 'audio', 'Gambar' => 'gambar',
                        ][$t['media']] ?? null;
                        $materiSesuai = count($daftarMateri) === 1
                            && $daftarMateri[0]['judul'] === $t['materi']
                            && $daftarMateri[0]['jenis'] === $jenisMedia;
                    }
                    if ($old === null || ! $materiSesuai
                        || $old['nama_tp'] !== $t['nama']
                        || $old['deskripsi_tp'] !== $t['deskripsi']
                        || $old['jenis_tp'] !== $t['jenis']
                        || ($babId[(int) $old['bab_id']] ?? null)
                            !== [$s, (int) $t['nomor_bab']]) {
                        $sama = false;
                        break;
                    }
                }
            }
            $modeSem[$s] = $sama ? 'tetap' : 'galat';
            if (! $sama) {
                $catatanSem[$s] = 'Semester sudah memiliki BAB/TP. '
                    . 'Impor tidak boleh menambah atau mengubah data manual/draf. '
                    . 'Sunting melalui aplikasi.';
            }
        }

        // Setelah gabungan semester lama + baru, urutan BAB dalam tiap
        // kelas harus selalu 1,2,3,... termasuk di pasangan lintas TA.
        for ($awal = 1; $awal <= 5; $awal += 2) {
            $gabungan = [];
            for ($s = $awal; $s <= $awal + 1; ++$s) {
                if (($modeSem[$s] ?? '') === 'baru') {
                    foreach ($babExcelSem[$s] ?? [] as $b) {
                        $gabungan[] = (int) $b['nomor'];
                    }
                } else {
                    $rows = $babDbSem[$s] ?? [];
                    usort($rows, static fn (array $a, array $b): int =>
                        (int) $a['nomor_bab'] <=> (int) $b['nomor_bab']);
                    foreach ($rows as $b) {
                        $gabungan[] = (int) $b['nomor_bab'];
                    }
                }
            }
            foreach ($gabungan as $i => $n) {
                if ($n !== $i + 1) {
                    for ($s = $awal; $s <= $awal + 1; ++$s) {
                        if (($modeSem[$s] ?? '') === 'baru') {
                            $modeSem[$s] = 'galat';
                            $catatanSem[$s] = 'Nomor BAB harus berurutan di Semester '
                                . $awal . '–' . ($awal + 1)
                                . ', termasuk BAB yang sudah ada di aplikasi.';
                        }
                    }
                    break;
                }
            }
        }

        foreach ($hasil['bab'] as $b) {
            $s = (int) $b['semester'];
            $status = $modeSem[$s] ?? 'galat';
            $daftar[] = [
                'jenis' => 'BAB', 'semester' => $s, 'baris' => $b['baris'],
                'kode' => 'BAB ' . $b['nomor'], 'nama' => $b['nama'],
                'status' => $status, 'catatan' => $catatanSem[$s] ?? '',
            ];
            $jumlah[$status]++;
        }
        foreach ($hasil['tp'] as $t) {
            $s = (int) $t['semester'];
            $status = $modeSem[$s] ?? 'galat';
            $daftar[] = [
                'jenis' => 'TP', 'semester' => $s, 'baris' => $t['baris'],
                'kode' => $t['kode'], 'nama' => $t['nama'],
                'status' => $status, 'catatan' => $catatanSem[$s] ?? '',
            ];
            $jumlah[$status]++;
        }

        usort($babTerdaftar, static fn (array $a, array $b): int => (int) $a['id'] <=> (int) $b['id']);
        usort($tpTerdaftar, static fn (array $a, array $b): int => (int) $a['id'] <=> (int) $b['id']);
        usort($daftarPaket, static fn (array $a, array $b): int => (int) $a['id'] <=> (int) $b['id']);
        usort($elemen, static fn (array $a, array $b): int =>
            strcmp((string) $a['elemen'], (string) $b['elemen']));
        $fingerprint = hash('sha256', json_encode([
            $paket, $cp, $elemen, $daftarPaket, $babTerdaftar,
            $tpTerdaftar, $materiTerdaftar,
            $jumlah, $daftar, $modeSem, $tahunSem,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $kosong = $hasil['jumlah_bab'] === 0 && $hasil['jumlah_tp'] === 0;
        return [
            'gen' => $paket['nama_gen'], 'mapel' => $paket['nama_mapel'],
            'semester_paket' => (int) $paket['semester'],
            'jumlah' => $jumlah, 'baris' => $daftar,
            'kosong' => $kosong,
            'boleh_konfirmasi' => ! $kosong && $jumlah['galat'] === 0
                && $jumlah['baru'] > 0,
            'fingerprint' => $fingerprint,
            'rencana' => $hasil,
            'mode_semester' => $modeSem,
            'paket_semester' => $paketSem,
            'tahun_semester' => $tahunSem,
            'cp_mapel_id' => (int) $cp[0]['id'],
            'konteks' => [
                'angkatan_id' => (int) $paket['angkatan_id'],
                'mapel_id' => (int) $paket['mapel_id'],
                'kurikulum_id' => (int) $paket['kurikulum_id'],
            ],
        ];
    }
}
