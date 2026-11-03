<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * A11-1: pemeriksaan kelayakan akademik sertifikat per siswa/paket.
 * Read-only; tidak membuat PDF, nomor, token QR, atau rekaman sertifikat.
 * Pra-Semester sengaja tidak dibaca: bukan gerbang maupun nilai cetak.
 */
final class KelayakanSertifikatService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
    }

    private function adminId(): int
    {
        $a = ($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? '') !== 'admin'
            || ($a['status'] ?? '') !== 'aktif'
            || (int)($a['wajib_ganti_sandi'] ?? 1) !== 0
            || (int)($a['id'] ?? 0) < 1) {
            throw new DomainException('Pemeriksaan sertifikat hanya untuk Admin aktif.');
        }
        return (int)$a['id'];
    }

    public function daftarPaket(): array
    {
        $this->adminId();
        return $this->db->table('penugasan_paket pp')
            ->distinct()
            ->select('p.id,p.semester,p.angkatan_id,p.mapel_id,'
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
            ->orderBy('p.semester','ASC')->orderBy('p.id','ASC')
            ->get()->getResultArray();
    }

    /** Hanya paket aktif dan ditugaskan, bukan setiap id paket dalam URL. */
    private function paket(int $id): array
    {
        if ($id < 1) throw new DomainException('Paket tidak tersedia.');
        $p = $this->db->table('paket_semester p')
            ->select('p.id,p.angkatan_id,p.mapel_id,p.semester,p.status,'
                .'a.nama AS nama_gen,a.semester_mulai AS gen_semester_mulai,'
                .'m.nama AS nama_mapel')
            ->join('ref_angkatan a','a.id=p.angkatan_id')
            ->join('ref_mapel m','m.id=p.mapel_id')
            ->join('penugasan_paket pp','pp.paket_id=p.id'
                .' AND pp.angkatan_id=p.angkatan_id'
                .' AND pp.mapel_id=p.mapel_id'
                .' AND pp.semester=p.semester'
                .' AND pp.tahun_ajaran_id=p.tahun_ajaran_id')
            ->where('p.id',$id)->where('p.status','aktif')
            ->get()->getResultArray();
        if (count($p) !== 1) {
            throw new DomainException('Paket aktif dan penugasannya tidak valid.');
        }
        return $p[0];
    }

    /** Data kesiapan administratif; tanda tangan baru diimplementasi A11-2. */
    public function identitas(): array
    {
        $this->adminId();
        $wajib = array_diff(
            array_keys(PengaturanService::IDENTITAS),['website']
        );
        $nilai = $this->db->table('pengaturan')
            ->select('kunci,nilai')->whereIn('kunci',$wajib)
            ->get()->getResultArray();
        $terisi = [];
        foreach ($nilai as $r) {
            if (trim((string)($r['nilai'] ?? '')) !== '') {
                $terisi[$r['kunci']] = true;
            }
        }
        $kurang = [];
        foreach ($wajib as $k) {
            if (!isset($terisi[$k])) {
                $kurang[] = PengaturanService::IDENTITAS[$k];
            }
        }
        return [
            'lengkap'=>$kurang === [],
            'kekurangan'=>$kurang,
            'ttd_tersedia'=>false,
            'penerbitan_aktif'=>false,
        ];
    }

    /**
     * @return array{paket:array,baris:array,total:int,halaman:int,
     * jumlah_halaman:int,total_tp:int,total_non_esensial:int}
     */
    public function rekap(int $paketId, int $halaman = 1, int $perHalaman = 25): array
    {
        $this->adminId();
        if ($halaman < 1 || $halaman > 100000
            || $perHalaman < 1 || $perHalaman > 100) {
            throw new DomainException('Halaman pemeriksaan tidak sah.');
        }
        $paket = $this->paket($paketId);
        $semuaTp = $this->db->table('tp t')
            ->select('t.id,t.kode_tp,t.jenis_tp,t.nama_tp,t.judul,'
                .'t.status,t.urutan_atp,b.nomor_bab,b.nama_bab')
            ->join('bab b','b.id=t.bab_id'
                .' AND b.paket_semester_id=t.paket_semester_id','left')
            ->where('t.paket_semester_id',$paketId)
            ->where('t.status !=','arsip')
            ->orderBy('t.urutan_atp','ASC')->orderBy('t.id','ASC')
            ->get()->getResultArray();
        $wajib = array_values(array_filter($semuaTp,
            static fn (array $tp): bool => $tp['jenis_tp'] === 'non_esensial'
        ));
        $total = $this->db->table('siswa')
            ->where('angkatan_id',(int)$paket['angkatan_id'])->countAllResults();
        $pages = max(1,(int)ceil($total/$perHalaman));
        $halaman = min($pages,$halaman);
        $siswa = $this->db->table('siswa s')
            ->select('s.id,s.semester_mulai,u.nama')
            ->join('pengguna u','u.id=s.pengguna_id')
            ->where('s.angkatan_id',(int)$paket['angkatan_id'])
            ->orderBy('u.nama','ASC')->orderBy('s.id','ASC')
            ->get($perHalaman,($halaman-1)*$perHalaman)->getResultArray();

        // Seluruh nilai dan percobaan diambil per halaman, bukan N+1 per siswa.
        $progress = [];
        $sumatifSah = [];
        if ($siswa !== [] && $wajib !== []) {
            $siswaIds = array_map(static fn (array $s): int => (int)$s['id'],$siswa);
            $tpIds = array_map(static fn (array $t): int => (int)$t['id'],$wajib);
            $rows = $this->db->table('progres_tp pr')
                ->select('pr.id AS progres_id,pr.siswa_id,pr.tp_id,pr.status AS progres_status,'
                    .'n.jalur,n.nilai_resume,n.nilai_hots,n.nilai_sumatif,'
                    .'n.nilai_tp,n.status_nilai')
                ->join('nilai_tp n','n.progres_tp_id=pr.id','left')
                ->where('pr.paket_semester_id',$paketId)
                ->whereIn('pr.siswa_id',$siswaIds)
                ->whereIn('pr.tp_id',$tpIds)->get()->getResultArray();
            $progressIds = [];
            foreach ($rows as $row) {
                $sid = (int)$row['siswa_id'];
                $tid = (int)$row['tp_id'];
                $progress[$sid][$tid] = $row;
                $progressIds[] = (int)$row['progres_id'];
            }
            if ($progressIds !== []) {
                $sah = $this->db->table('percobaan_ujian')
                    ->distinct()->select('progres_tp_id')
                    ->whereIn('progres_tp_id',$progressIds)
                    ->where('jenis','sumatif')->where('status','selesai_sah')
                    ->get()->getResultArray();
                foreach ($sah as $row) {
                    $sumatifSah[(int)$row['progres_tp_id']] = true;
                }
            }
        }

        $hasil = [];
        foreach ($siswa as $s) {
            $sid = (int)$s['id'];
            $mulai = (int)($s['semester_mulai'] ?? $paket['gen_semester_mulai']);
            $dilewati = (int)$paket['semester'] < $mulai;
            $alasan = [];
            $selesai = 0;
            $final = 0;
            if ($dilewati) {
                $alasan[] = 'Semester ini dilewati karena sebelum semester mulai siswa.';
            }
            if ($wajib === []) {
                $alasan[] = 'Belum ada TP NonEsensial pada semester ini.';
            }
            foreach ($wajib as $t) {
                $p = $progress[$sid][(int)$t['id']] ?? null;
                $kode = (string)$t['kode_tp'];
                if ($p === null || $p['progres_status'] !== 'tuntas') {
                    $alasan[] = $kode.': TP belum tuntas.';
                    continue;
                }
                $idProgres = (int)$p['progres_id'];
                if (!isset($sumatifSah[$idProgres])) {
                    $alasan[] = $kode.': belum ada Sumatif selesai sah.';
                    continue;
                }
                $selesai++;
                if ($p['status_nilai'] !== 'final'
                    || $p['nilai_tp'] === null || $p['nilai_sumatif'] === null
                    || !in_array($p['jalur'],['lulus_diagnostik','belajar_wajib'],true)
                    || ($p['jalur'] === 'belajar_wajib'
                        && ($p['nilai_resume'] === null || $p['nilai_hots'] === null))) {
                    $alasan[] = $kode.': penilaian TP belum Final atau belum lengkap.';
                    continue;
                }
                $final++;
            }
            $hasil[] = [
                'siswa_id'=>$sid,'nama_siswa'=>(string)$s['nama'],
                'dilewati'=>$dilewati,'total_tp'=>count($wajib),
                'tp_selesai'=>$selesai,'nilai_final'=>$final,
                'layak_akademik'=>!$dilewati && $wajib !== [] && $alasan === [],
                'alasan'=>$alasan,
            ];
        }
        return [
            'paket'=>$paket,'baris'=>$hasil,'total'=>$total,
            'halaman'=>$halaman,'jumlah_halaman'=>$pages,
            'total_tp'=>count($semuaTp),
            'total_non_esensial'=>count($wajib),
        ];
    }
}
