<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * A9: rekap Pra-Semester baca-saja. Tidak menulis skor atau mengubah
 * histori ujian; memakai penentu nilai domain A8 secara tunggal.
 */
final class RekapNilaiPraSemesterGuruService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
    }

    private function guruId(): int
    {
        $akun = ($this->akun)();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int)($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Rekap Pra-Semester hanya untuk Guru aktif.');
        }
        return (int)$akun['id'];
    }

    public function penugasan(): array
    {
        $this->guruId();
        return (new PetaTpService($this->db,$this->akun))->genMapelDiampu();
    }

    /** Pemeriksaan G1 tidak mengandalkan indeks dropdown dari browser. */
    private function cakupan(array $pilihan): array
    {
        $guru = $this->guruId();
        $gen = (int)($pilihan['angkatan_id'] ?? 0);
        $mapel = (int)($pilihan['mapel_id'] ?? 0);
        if ($gen < 1 || $mapel < 1 || $this->db->table('penugasan_guru_gen')
            ->where('guru_id',$guru)->where('angkatan_id',$gen)
            ->where('mapel_id',$mapel)->countAllResults() !== 1) {
            throw new DomainException('Guru tidak ditugaskan pada Gen–Mapel ini.');
        }
        return [$gen,$mapel];
    }

    public static function labelFase(array $nilai): string
    {
        return match ($nilai['fase'] ?? null) {
            'selesai' => 'Final',
            'menunggu_remedial' => 'Sementara — menunggu remedial',
            'butuh_susulan_normal' => 'Menunggu susulan normal',
            'butuh_susulan_remedial' => 'Sementara — menunggu susulan remedial',
            'sedang_ujian' => 'Sedang ujian',
            default => 'Belum mengikuti ujian',
        };
    }

    public function rekap(?array $pilihan, int $semester, int $halaman = 1,
        int $perHalaman = 25): array
    {
        $this->guruId();
        if ($semester < 1 || $semester > 6 || $halaman < 1
            || $halaman > 100000 || $perHalaman < 1 || $perHalaman > 500) {
            throw new DomainException('Filter rekap Pra-Semester tidak sah.');
        }
        $hasil = [
            'baris'=>[], 'total'=>0, 'halaman'=>1, 'jumlah_halaman'=>1,
            'nama_ujian'=>null, 'ujian_id'=>null,
        ];
        if ($pilihan === null) return $hasil;
        [$gen,$mapel] = $this->cakupan($pilihan);

        $paket = $this->db->table('paket_semester')
            ->select('id')->where('angkatan_id',$gen)
            ->where('mapel_id',$mapel)->where('semester',$semester)
            ->get()->getResultArray();
        if (count($paket) > 1) {
            throw new DomainException('Paket Pra-Semester ganda; hubungi Admin.');
        }
        $ujian = null;
        if ($paket !== []) {
            $ujian = $this->db->table('ujian_pra_semester')
                ->select('id,kkm_khusus,status')
                ->where('paket_semester_id',(int)$paket[0]['id'])
                ->get()->getRowArray();
        }
        $total = $this->db->table('siswa')->where('angkatan_id',$gen)->countAllResults();
        $pages = max(1,(int)ceil($total/$perHalaman));
        $halaman = min($pages,$halaman);
        $offset = ($halaman-1)*$perHalaman;
        $siswa = $this->db->table('siswa s')
            ->select('s.id AS siswa_id,u.nama AS nama_siswa')
            ->join('pengguna u','u.id=s.pengguna_id')
            ->where('s.angkatan_id',$gen)
            ->orderBy('u.nama','ASC')->orderBy('s.id','ASC')
            ->get($perHalaman,$offset)->getResultArray();

        $histori = [];
        if ($ujian !== null && $siswa !== []) {
            $ids = array_map(static fn (array $s): int => (int)$s['siswa_id'],$siswa);
            $rows = $this->db->table('percobaan_ujian')
                ->select('id,siswa_id,tahap,status,skor,kkm')
                ->where('ujian_pra_semester_id',(int)$ujian['id'])
                ->where('jenis','pra_semester')->whereIn('siswa_id',$ids)
                ->orderBy('id','ASC')->get()->getResultArray();
            foreach ($rows as $r) $histori[(int)$r['siswa_id']][] = $r;
        }
        $default = $this->db->table('pengaturan')
            ->select('nilai')->where('kunci','kkm_default')->get()->getRow('nilai');
        $kkmDefault = is_numeric($default) ? (int)$default : null;
        foreach ($siswa as $s) {
            $nilai = PenilaianPraSemesterService::ringkas(
                $histori[(int)$s['siswa_id']] ?? []
            );
            $kkm = $nilai['kkm'] ?? (
                $ujian !== null && $ujian['kkm_khusus'] !== null
                    ? (int)$ujian['kkm_khusus'] : $kkmDefault
            );
            $hasil['baris'][] = [
                'siswa_id'=>(int)$s['siswa_id'],
                'nama_siswa'=>(string)$s['nama_siswa'],
                'semester'=>$semester,
                'kkm'=>$kkm,
                'skor_normal'=>$nilai['skor_normal'],
                'skor_remedial'=>$nilai['skor_remedial'],
                'nilai_terbaik'=>$nilai['nilai_terbaik'],
                'status_nilai'=>$nilai['status_nilai'],
                'fase'=>$nilai['fase'],
                'status_label'=>self::labelFase($nilai),
            ];
        }
        return $hasil + [
            'ujian_id'=>$ujian !== null ? (int)$ujian['id'] : null,
        ] + ['total'=>$total]; // kunci hasil lain ditetapkan di bawah
    }
}
