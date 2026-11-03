<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * A8: konteks terotorisasi siswa dan gerbang ujian utama Pra-Semester.
 * Data soal/kunci tidak dikirim ke halaman siswa.
 */
final class PraSemesterSiswaService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    public function konteks(int $paketId): array
    {
        $akun = ($this->akun)();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'siswa'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0 || $paketId < 1) {
            throw new DomainException('Akses Pra-Semester hanya untuk siswa aktif.');
        }
        // Paket semester harus ditugaskan resmi ke Gen siswa, tidak cukup
        // mengandalkan ID yang ditebak dari URL.
        $row = $this->db->table('paket_semester p')
            ->select('p.id,p.angkatan_id,p.mapel_id,p.semester,p.status,'
                . 's.id AS siswa_id,s.semester_mulai,a.nama AS nama_gen,m.nama AS nama_mapel')
            ->join('siswa s','s.angkatan_id=p.angkatan_id')
            ->join('penugasan_paket pp','pp.paket_id=p.id AND pp.angkatan_id=s.angkatan_id'
                . ' AND pp.mapel_id=p.mapel_id AND pp.semester=p.semester'
                . ' AND pp.tahun_ajaran_id=p.tahun_ajaran_id')
            ->join('ref_angkatan a','a.id=p.angkatan_id')
            ->join('ref_mapel m','m.id=p.mapel_id')
            ->where('s.pengguna_id',(int)$akun['id'])
            ->where('p.id',$paketId)->where('p.status','aktif')
            ->get()->getRowArray();
        if (!$row || (int)$row['semester'] < (int)$row['semester_mulai']) {
            throw new DomainException('Paket Pra-Semester tidak tersedia bagi siswa ini.');
        }

        $tp = $this->db->table('tp')
            ->select('id,jenis_tp')->where('paket_semester_id',$paketId)
            ->where('status !=','arsip')->get()->getResultArray();
        $progres = $this->db->table('progres_tp')
            ->select('id,tp_id,status')->where('siswa_id',(int)$row['siswa_id'])
            ->where('paket_semester_id',$paketId)->get()->getResultArray();
        $statusPerTp = [];
        foreach ($progres as $p) {
            if ($p['status'] !== 'tuntas') {
                $statusPerTp[(int)$p['tp_id']] = (string)$p['status'];
                continue;
            }
            // Proteksi progres historis: tuntas hanya sah jika benar-benar
            // memiliki Sumatif selesai_sah, bukan diagnostik yang dulu
            // menandai tuntas terlalu dini.
            $sah = $this->db->table('percobaan_ujian')
                ->where('progres_tp_id',(int)$p['id'])
                ->where('jenis','sumatif')->where('status','selesai_sah')
                ->countAllResults() > 0;
            $statusPerTp[(int)$p['tp_id']] = $sah ? 'tuntas' : 'belum_sah';
        }

        $ujian = $this->db->table('ujian_pra_semester')
            ->where('paket_semester_id',$paketId)->get()->getRowArray();
        $bab = $this->db->table('bab')->select('id')->where('paket_semester_id',$paketId)
            ->get()->getResultArray();
        $daftarSoal = [];
        if ($ujian) {
            $soal = $this->db->table('soal_pra_semester')
                ->where('ujian_pra_semester_id',(int)$ujian['id'])
                ->get()->getResultArray();
            foreach ($soal as $s) {
                $isiLengkap = trim((string)$s['pertanyaan']) !== '';
                foreach (['opsi_a','opsi_b','opsi_c','opsi_d','opsi_e'] as $k) {
                    if (trim((string)$s[$k]) === '') $isiLengkap = false;
                }
                $daftarSoal[] = [
                    'id'=>(int)$s['id'],'bab_id'=>(int)$s['bab_id'],
                    'aktif'=>(int)$s['aktif'] === 1,'opsi_lengkap'=>$isiLengkap,
                    'kunci_sah'=>in_array($s['kunci'],['A','B','C','D','E'],true),
                ];
            }
        }
        $gerbang = AturanPraSemesterService::gerbangMulai(
            array_map(static fn(array $t): array => [
                'id'=>(int)$t['id'],'jenis_tp'=>(string)$t['jenis_tp'],
            ], $tp),
            $statusPerTp,
            array_map(static fn(array $b): int => (int)$b['id'],$bab),
            $daftarSoal,
            $ujian['mulai_pada'] ?? null,
            $ujian['selesai_pada'] ?? null,
            time()
        );
        $kkm = $ujian
            ? (new KkmTpService($this->db,$this->akun))->informasi($ujian)
            : ['efektif'=>null,'default'=>null,'khusus'=>null,'sumber'=>'belum_tersedia'];
        if (!$ujian || $ujian['status'] !== 'siap') {
            $gerbang['boleh_mulai'] = false;
            $gerbang['alasan'][] = 'Guru/Admin belum menyiapkan dan membuka ujian utama.';
        }
        if ($kkm['efektif'] === null) {
            $gerbang['boleh_mulai'] = false;
            $gerbang['alasan'][] = 'KKM Pra-Semester belum tersedia.';
        }
        if ($ujian && $gerbang['bank']['jumlah_sah'] < (int)$ujian['jumlah_soal']) {
            $gerbang['boleh_mulai'] = false;
            $gerbang['alasan'][] = 'Bank soal kurang dari jumlah tayang yang ditetapkan.';
        }

        $attempt = $ujian ? $this->db->table('percobaan_ujian')
            ->select('id,token_publik,jenis,tahap,status,skor,kkm,nomor_percobaan')
            ->where('ujian_pra_semester_id',(int)$ujian['id'])
            ->where('siswa_id',(int)$row['siswa_id'])
            ->where('jenis','pra_semester')
            ->orderBy('id','DESC')->get(1)->getRowArray() : null;

        if ($attempt) {
            $gerbang['boleh_mulai'] = false; // tidak membuat attempt normal kedua
            if ($attempt['status'] === 'dibatalkan_menunggu_guru') {
                $gerbang['alasan'][] = 'Ujian dibatalkan. Menunggu jadwal susulan yang dibuka Admin.';
            } elseif ($attempt['status'] === 'selesai_sah') {
                $gerbang['alasan'][] = (float)$attempt['skor'] < (int)$attempt['kkm']
                    ? 'Ujian sah di bawah KKM. Menunggu jadwal remedial Admin.'
                    : 'Ujian Pra-Semester telah selesai sah.';
            } else {
                $gerbang['alasan'][] = 'Percobaan sudah dimulai. Lanjutkan percobaan yang sama.';
            }
        }
        return [
            'paket'=>$row,'ujian'=>$ujian,'siswa_id'=>(int)$row['siswa_id'],
            'gerbang'=>$gerbang,'kkm'=>$kkm,'percobaan'=>$attempt,
        ];
    }
}
