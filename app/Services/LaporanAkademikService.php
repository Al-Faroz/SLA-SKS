<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * A10-1: laporan akademik baca-saja untuk pemilik data (Siswa) dan Pimpinan.
 * Nilai dari tabel hasil sah; tidak menjalankan sinkronisasi/penilaian ulang.
 */
final class LaporanAkademikService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
    }

    private function peran(): array
    {
        $a = ($this->akun)();
        if (!$a || !in_array($a['peran_aktif'] ?? null, ['siswa','pimpinan'],true)
            || ($a['status'] ?? '') !== 'aktif'
            || (int)($a['wajib_ganti_sandi'] ?? 1) !== 0
            || (int)($a['id'] ?? 0) < 1) {
            throw new DomainException('Laporan hanya tersedia untuk Siswa atau Pimpinan aktif.');
        }
        return $a;
    }

    public function daftarLaporanSendiri(): array
    {
        if ($this->peran()['peran_aktif'] !== 'siswa') {
            throw new DomainException('Daftar laporan siswa tidak diizinkan.');
        }
        $katalog = new KatalogPembelajaranSiswaService($this->db,$this->akun);
        $hasil = [];
        foreach ($katalog->daftarMapel()['mapel'] as $m) {
            foreach ($katalog->daftarSemester((int)$m['id'])['semester'] as $s) {
                if (!empty($s['buka']) && (int)($s['paket_id'] ?? 0) > 0) {
                    $hasil[] = [
                        'paket_id'=>(int)$s['paket_id'],
                        'mapel'=>(string)$m['nama'],
                        'semester'=>(int)$s['nomor'],
                    ];
                }
            }
        }
        return $hasil;
    }

    public function laporanSendiri(int $paketId): array
    {
        $a = $this->peran();
        if ($a['peran_aktif'] !== 'siswa') {
            throw new DomainException('Laporan siswa tidak diizinkan.');
        }
        $siswa = $this->db->table('siswa')->select('id')
            ->where('pengguna_id',(int)$a['id'])->get()->getRowArray();
        if (!$siswa) throw new DomainException('Data siswa belum tersedia.');
        return $this->laporan((int)$siswa['id'],$paketId);
    }

    /** Paket yang benar-benar ditugaskan, bukan hasil menebak id URL. */
    private function paket(int $paketId): array
    {
        if ($paketId < 1) throw new DomainException('Paket tidak tersedia.');
        $p = $this->db->table('paket_semester p')
            ->select('p.id,p.angkatan_id,p.mapel_id,p.semester,p.status,'
                .'a.nama AS nama_gen,a.kode AS kode_gen,'
                .'a.semester_mulai AS gen_semester_mulai,'
                .'m.nama AS nama_mapel,m.kode AS kode_mapel')
            ->join('ref_angkatan a','a.id=p.angkatan_id')
            ->join('ref_mapel m','m.id=p.mapel_id')
            ->join('penugasan_paket pp','pp.paket_id=p.id'
                .' AND pp.angkatan_id=p.angkatan_id'
                .' AND pp.mapel_id=p.mapel_id'
                .' AND pp.semester=p.semester'
                .' AND pp.tahun_ajaran_id=p.tahun_ajaran_id')
            ->where('p.id',$paketId)->where('p.status','aktif')
            ->get()->getRowArray();
        if ($p === null) throw new DomainException('Paket tidak tersedia.');
        return $p;
    }

    /** Pimpinan melihat semua paket aktif, tanpa data pribadi siswa di pilihan. */
    public function paketPimpinan(): array
    {
        if (($this->peran()['peran_aktif'] ?? '') !== 'pimpinan') {
            throw new DomainException('Akses monitoring tidak diizinkan.');
        }
        return $this->db->table('penugasan_paket pp')
            ->distinct()
            ->select('p.id,p.angkatan_id,p.mapel_id,p.semester,'
                .'a.nama AS nama_gen,m.nama AS nama_mapel')
            ->join('paket_semester p','p.id=pp.paket_id'
                .' AND p.angkatan_id=pp.angkatan_id'
                .' AND p.mapel_id=pp.mapel_id'
                .' AND p.semester=pp.semester'
                .' AND p.tahun_ajaran_id=pp.tahun_ajaran_id')
            ->join('ref_angkatan a','a.id=p.angkatan_id')
            ->join('ref_mapel m','m.id=p.mapel_id')
            ->where('p.status','aktif')
            ->orderBy('a.nama','ASC')->orderBy('m.nama','ASC')
            ->orderBy('p.semester','ASC')->get()->getResultArray();
    }

    /**
     * Monitoring Pimpinan per Gen–Mapel–Semester. Total/tuntas hanya TP
     * NonEsensial; tuntas perlu bukti Sumatif selesai sah.
     */
    public function monitoring(int $paketId, int $halaman = 1, int $perHalaman = 25): array
    {
        if (($this->peran()['peran_aktif'] ?? '') !== 'pimpinan') {
            throw new DomainException('Akses monitoring tidak diizinkan.');
        }
        if ($halaman < 1 || $halaman > 100000 || $perHalaman < 1 || $perHalaman > 100) {
            throw new DomainException('Halaman monitoring tidak valid.');
        }
        $paket = $this->paket($paketId);
        $jumlah = $this->db->table('siswa')
            ->where('angkatan_id',(int)$paket['angkatan_id'])->countAllResults();
        $pages = max(1,(int)ceil($jumlah/$perHalaman));
        $halaman = min($halaman,$pages);
        $siswa = $this->db->table('siswa s')
            ->select('s.id,s.semester_mulai,u.nama')
            ->join('pengguna u','u.id=s.pengguna_id')
            ->where('s.angkatan_id',(int)$paket['angkatan_id'])
            ->orderBy('u.nama','ASC')->orderBy('s.id','ASC')
            ->get($perHalaman,($halaman-1)*$perHalaman)->getResultArray();

        $totalTp = $this->db->table('tp')
            ->where('paket_semester_id',$paketId)
            ->where('jenis_tp','non_esensial')->where('status !=','arsip')
            ->countAllResults();
        $tuntas = [];
        if ($siswa !== []) {
            $ids = array_map(static fn (array $row): int => (int)$row['id'],$siswa);
            $rows = $this->db->table('progres_tp pr')
                ->select('pr.siswa_id,COUNT(DISTINCT pr.tp_id) AS selesai',false)
                ->join('tp t','t.id=pr.tp_id AND t.paket_semester_id=pr.paket_semester_id')
                ->join('percobaan_ujian u','u.progres_tp_id=pr.id')
                ->whereIn('pr.siswa_id',$ids)
                ->where('pr.paket_semester_id',$paketId)
                ->where('pr.status','tuntas')
                ->where('t.jenis_tp','non_esensial')
                ->where('t.status !=','arsip')
                ->where('u.jenis','sumatif')->where('u.status','selesai_sah')
                ->groupBy('pr.siswa_id')->get()->getResultArray();
            foreach ($rows as $r) $tuntas[(int)$r['siswa_id']] = (int)$r['selesai'];
        }
        foreach ($siswa as &$r) {
            $mulai = $r['semester_mulai'] ?? $paket['gen_semester_mulai'];
            $dilewati = (int)$paket['semester'] < (int)$mulai;
            $selesai = $dilewati ? 0 : min($totalTp, $tuntas[(int)$r['id']] ?? 0);
            $r['dilewati'] = $dilewati;
            $r['selesai'] = $selesai;
            $r['total_tp'] = $totalTp;
            $r['persen'] = $dilewati || $totalTp === 0
                ? null : round(100 * $selesai / $totalTp,1);
        }
        unset($r);
        return ['paket'=>$paket,'siswa'=>$siswa,'total'=>$jumlah,
            'halaman'=>$halaman,'jumlah_halaman'=>$pages,'total_tp'=>$totalTp];
    }

    /**
     * Laporan individual. Siswa hanya boleh melihat miliknya dan semester
     * yang telah dibuka pada katalog; Pimpinan boleh pratinjau baca-saja.
     */
    public function laporan(int $siswaId, int $paketId): array
    {
        $a = $this->peran();
        $paket = $this->paket($paketId);
        if ($siswaId < 1) throw new DomainException('Laporan tidak ditemukan.');
        $siswa = $this->db->table('siswa s')
            ->select('s.id,s.pengguna_id,s.angkatan_id,s.semester_mulai,'
                .'u.nama AS nama_siswa')
            ->join('pengguna u','u.id=s.pengguna_id')
            ->where('s.id',$siswaId)
            ->where('s.angkatan_id',(int)$paket['angkatan_id'])
            ->get()->getRowArray();
        if (!$siswa) throw new DomainException('Laporan tidak ditemukan.');
        if ($a['peran_aktif'] === 'siswa') {
            if ((int)$siswa['pengguna_id'] !== (int)$a['id']) {
                throw new DomainException('Laporan tidak ditemukan.');
            }
            $semesterKatalog = (new KatalogPembelajaranSiswaService(
                $this->db,$this->akun
            ))->daftarSemester((int)$paket['mapel_id'])['semester'];
            $diizinkan = false;
            foreach ($semesterKatalog as $row) {
                if ((int)($row['paket_id'] ?? 0) === $paketId
                    && !empty($row['buka'])) {
                    $diizinkan = true;
                    break;
                }
            }
            if (!$diizinkan) throw new DomainException('Semester laporan belum terbuka.');
        }

        $mulai = (int)($siswa['semester_mulai'] ?? $paket['gen_semester_mulai']);
        $dilewati = (int)$paket['semester'] < $mulai;

        $tp = $this->db->table('tp t')
            ->select('t.id AS tp_id,t.kode_tp,t.nama_tp,t.judul,'
                .'t.jenis_tp,t.status AS status_tp,t.urutan_atp,'
                .'b.nomor_bab,b.nama_bab,'
                .'pr.id AS progres_id,pr.status AS status_progres,'
                .'n.jalur,n.nilai_resume,n.nilai_hots,n.nilai_sumatif,'
                .'n.nilai_tp,n.status_nilai')
            ->join('bab b','b.id=t.bab_id AND b.paket_semester_id=t.paket_semester_id','left')
            ->join('progres_tp pr','pr.tp_id=t.id AND pr.siswa_id='.(int)$siswaId
                .' AND pr.paket_semester_id=t.paket_semester_id','left')
            ->join('nilai_tp n','n.progres_tp_id=pr.id','left')
            ->where('t.paket_semester_id',$paketId)
            ->where('t.status !=','arsip')
            ->orderBy('t.urutan_atp','ASC')->orderBy('t.id','ASC')
            ->get()->getResultArray();

        $progresIds = array_values(array_map('intval',array_filter(
            array_column($tp,'progres_id'),static fn ($id): bool => $id !== null
        )));
        $ujianTp = [];
        if ($progresIds !== []) {
            $attempts = $this->db->table('percobaan_ujian')
                ->select('progres_tp_id,jenis,tahap,skor,kkm')
                ->whereIn('progres_tp_id',$progresIds)
                ->where('status','selesai_sah')
                ->whereIn('jenis',['diagnostik','sumatif'])
                ->orderBy('id','ASC')->get()->getResultArray();
            foreach ($attempts as $r) {
                $ujianTp[(int)$r['progres_tp_id']][(string)$r['jenis']]
                    [(string)$r['tahap']] = $r['skor'] === null ? null : (float)$r['skor'];
            }
        }

        $total = 0; $selesai = 0; $final = 0; $sementara = 0;
        foreach ($tp as &$row) {
            $esensial = $row['jenis_tp'] === 'esensial';
            $score = $ujianTp[(int)($row['progres_id'] ?? 0)] ?? [];
            $row['skor_diagnostik'] = $esensial || $dilewati
                ? null : ($score['diagnostik']['normal'] ?? null);
            $row['skor_sumatif_awal'] = $esensial || $dilewati
                ? null : ($score['sumatif']['normal'] ?? null);
            $row['skor_sumatif_remedial'] = $esensial || $dilewati
                ? null : ($score['sumatif']['remedial'] ?? null);
            if ($esensial || $dilewati) {
                foreach (['nilai_resume','nilai_hots','nilai_sumatif',
                    'nilai_tp','status_nilai','jalur'] as $field) $row[$field] = null;
                $row['status_laporan'] = $dilewati ? 'Dilewati' : 'Esensial — tanpa nilai';
                continue;
            }
            $total++;
            $tuntas = $row['status_progres'] === 'tuntas'
                && $row['skor_sumatif_awal'] !== null;
            if ($tuntas) $selesai++;
            $row['status_laporan'] = $tuntas ? 'Selesai' :
                (string)($row['status_progres'] ?? 'Belum mulai');
            if ($row['status_nilai'] === 'final') $final++;
            elseif ($row['status_nilai'] === 'sementara') $sementara++;
        }
        unset($row);

        $pra = ['ujian_id'=>null,'kkm'=>null,'skor_normal'=>null,
            'skor_remedial'=>null,'nilai_terbaik'=>null,'status_nilai'=>null,
            'fase'=>'belum_mulai','label'=>'Belum tersedia'];
        $ujian = $this->db->table('ujian_pra_semester')
            ->select('id,kkm_khusus,status')
            ->where('paket_semester_id',$paketId)->get()->getRowArray();
        if ($ujian && !$dilewati) {
            $historis = $this->db->table('percobaan_ujian')
                ->select('id,tahap,status,skor,kkm')
                ->where('ujian_pra_semester_id',(int)$ujian['id'])
                ->where('siswa_id',$siswaId)->where('jenis','pra_semester')
                ->orderBy('id','ASC')->get()->getResultArray();
            $nilai = PenilaianPraSemesterService::ringkas($historis);
            $kkmDefault = $this->db->table('pengaturan')
                ->select('nilai')->where('kunci','kkm_default')->get()->getRow('nilai');
            $pra = [
                'ujian_id'=>(int)$ujian['id'],
                'kkm'=>$nilai['kkm'] ?? ($ujian['kkm_khusus'] !== null
                    ? (int)$ujian['kkm_khusus'] : (is_numeric($kkmDefault)
                        ? (int)$kkmDefault : null)),
                'skor_normal'=>$nilai['skor_normal'],
                'skor_remedial'=>$nilai['skor_remedial'],
                'nilai_terbaik'=>$nilai['nilai_terbaik'],
                'status_nilai'=>$nilai['status_nilai'],
                'fase'=>$nilai['fase'],
                'label'=>RekapNilaiPraSemesterGuruService::labelFase($nilai),
            ];
        }
        return [
            'siswa'=>$siswa,'paket'=>$paket,'tp'=>$tp,
            'ringkasan'=>[
                'jumlah_tp'=>$total,'selesai'=>$selesai,
                'persen'=>$dilewati || $total===0 ? null : round(100*$selesai/$total,1),
                'final'=>$final,'sementara'=>$sementara,
                'dilewati'=>$dilewati,
            ],
            'pra'=>$pra,
        ];
    }
}
