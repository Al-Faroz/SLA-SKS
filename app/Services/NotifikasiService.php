<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A13-6: notifikasi transaksional. catat() DIPANGGIL hanya dari mutasi
 * akademik yang sah, dengan koneksi/transaksi sumber yang sama.
 */
final class NotifikasiService
{
    private BaseConnection $db;
    /** @var callable():?array */
    private $akun;

    private const TARGET = [
        'nilai_resume_final' => ['siswa', 'siswa/laporan'],
        'nilai_hots_final' => ['siswa', 'siswa/laporan'],
        'permintaan_akses_ulang' => ['guru', 'guru/akses-ulang'],
        'izin_akses_ulang' => ['siswa', 'siswa/pembelajaran'],
        'pra_semester_dibatalkan' => ['siswa', 'siswa/pembelajaran'],
        'tp_siap_tersedia' => ['siswa', 'siswa/pembelajaran'],
        'koreksi_nilai_diterapkan' => ['siswa', 'siswa/laporan'],
        'sertifikat_awal_terbit' => ['siswa', 'siswa/sertifikat'],
        'sertifikat_pengganti_terbit' => ['siswa', 'siswa/sertifikat'],
        'permohonan_koreksi_nilai' => ['admin', 'admin'],
        'keputusan_koreksi_nilai' => ['guru', 'guru/penilaian'],
    ];

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn(): ?array => (new AuthService())->penggunaSaatIni();
    }

    public static function catat(
        BaseConnection $db, string $jenis, int $penerimaId, int $sumberId, int $versi = 1
    ): void {
        if (! $db->tableExists('notifikasi')) {
            // Instalasi lama tetap berjalan sebelum patch V4-0013 diimpor.
            return;
        }
        if (!isset(self::TARGET[$jenis])) {
            throw new DomainException('Peristiwa notifikasi tidak dikenal.');
        }
        [$peran, $tujuan] = self::TARGET[$jenis];
        $isi = NotifikasiKontrakService::susun($jenis,$penerimaId,$peran,$sumberId,$versi);
        $penerima=$db->table('pengguna u')
            ->select('u.id')->join('pengguna_peran r','r.pengguna_id=u.id')
            ->where('u.id',$penerimaId)->where('u.status','aktif')
            ->where('r.peran',$peran)->get(1)->getRowArray();
        if (!$penerima) {
            // Jangan kirim kepada akun nonaktif/role salah.
            return;
        }
        $kunci=$isi['kunci_idempoten'];
        if ($db->table('notifikasi')->where('kunci_idempoten',$kunci)->countAllResults()>0) return;
        try {
            $db->table('notifikasi')->insert([
                'penerima_id'=>$penerimaId,'peran_penerima'=>$peran,
                'jenis'=>$jenis,'sumber_id'=>$sumberId,'versi_sumber'=>$versi,
                'judul'=>$isi['judul'],'pesan'=>$isi['pesan'],'tujuan'=>$tujuan,
                'kunci_idempoten'=>$kunci,'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Hanya tabrakan kunci identik yang idempoten. Kesalahan
            // DB lain harus mengakibatkan rollback transaksi akademik.
            if ($db->table('notifikasi')->where('kunci_idempoten',$kunci)
                ->countAllResults()===0) {
                throw $e;
            }
        }
    }

    /** @return array<string,mixed> */
    private function pengguna(): array
    {
        $akun=($this->akun)();
        if (!$akun || (int)($akun['id']??0)<1
            || ($akun['status']??'')!=='aktif'
            || (int)($akun['wajib_ganti_sandi']??1)!==0
            || !in_array($akun['peran_aktif']??null,['admin','guru','siswa','pimpinan'],true)) {
            throw new DomainException('Sesi untuk notifikasi tidak berlaku.');
        }
        if (!$this->db->tableExists('notifikasi')) {
            throw new RuntimeException('Impor V4-0013 terlebih dahulu.');
        }
        return $akun;
    }

    private function dasar(array $akun)
    {
        return $this->db->table('notifikasi')
            ->where('penerima_id',(int)$akun['id'])
            ->where('peran_penerima',(string)$akun['peran_aktif']);
    }

    /** @return array{baris:list<array<string,mixed>>,jumlah:int,belum_dibaca:int,halaman:int,total_halaman:int} */
    public function daftar(int $halaman = 1, int $perHalaman = 15): array
    {
        $a=$this->pengguna();
        $perHalaman=max(1,min(50,$perHalaman));
        $total=$this->dasar($a)->countAllResults();
        $pages=max(1,(int)ceil($total/$perHalaman));
        $halaman=max(1,min($pages,$halaman));
        $rows=$this->dasar($a)->orderBy('dibuat_pada','DESC')
            ->orderBy('id','DESC')->get($perHalaman,($halaman-1)*$perHalaman)
            ->getResultArray();
        // Tidak kirim tautan sumber bila penugasan/cakupan sudah berubah.
        foreach ($rows as &$r) {
            $r['boleh_buka']=$this->berhakSumber($a,$r);
        }
        unset($r);
        return ['baris'=>$rows,'jumlah'=>$total,
            'belum_dibaca'=>$this->belumDibacaAkun($a),
            'halaman'=>$halaman,'total_halaman'=>$pages];
    }

    public function belumDibaca(): int
    {
        return $this->belumDibacaAkun($this->pengguna());
    }

    private function belumDibacaAkun(array $a): int
    {
        return $this->dasar($a)->where('dibaca_pada',null)->countAllResults();
    }

    private function milik(int $id, array $a): array
    {
        $r=$this->dasar($a)->where('id',$id)->get(1)->getRowArray();
        if (!$r) throw new DomainException('Notifikasi tidak ditemukan untuk akun/peran aktif.');
        return $r;
    }

    public function tandaiDibaca(int $id): void
    {
        $a=$this->pengguna();
        if ($id<1) throw new DomainException('Notifikasi tidak valid.');
        $r=$this->milik($id,$a);
        if (!$this->berhakSumber($a,$r)) {
            throw new DomainException('Akses ke sumber notifikasi sudah tidak berlaku.');
        }
        $this->dasar($a)->where('id',$id)->where('dibaca_pada',null)
            ->update(['dibaca_pada'=>gmdate('Y-m-d H:i:s')]);
    }

    public function tujuanBerizin(int $id): string
    {
        $a=$this->pengguna();
        if ($id<1) throw new DomainException('Notifikasi tidak valid.');
        $r=$this->milik($id,$a);
        if (!$this->berhakSumber($a,$r)) {
            throw new DomainException('Akses menuju sumber notifikasi tidak tersedia lagi.');
        }
        $def=self::TARGET[$r['jenis']]??null;
        if (!$def || $def[0]!==$a['peran_aktif'] || $def[1]!==$r['tujuan']) {
            throw new DomainException('Tujuan notifikasi tidak sah.');
        }
        return $def[1];
    }

    private function berhakSumber(array $a, array $r): bool
    {
        $jenis=(string)$r['jenis'];$sid=(int)$r['sumber_id'];$uid=(int)$a['id'];
        if ($sid<1) return false;
        if (in_array($jenis,['nilai_resume_final','nilai_hots_final'],true)) {
            $t=$jenis==='nilai_resume_final'?'resume_materi':'jawaban_hots_materi';
            return $this->db->table($t.' x')->join('progres_tp pr','pr.id=x.progres_tp_id')
                ->join('siswa s','s.id=pr.siswa_id')
                ->where('x.id',$sid)->where('s.pengguna_id',$uid)
                ->where('x.nilai !=',null)->countAllResults()>0;
        }
        if (in_array($jenis,['sertifikat_awal_terbit','sertifikat_pengganti_terbit'],true)) {
            return $this->db->table('sertifikat c')->join('siswa s','s.id=c.siswa_id')
                ->where('c.id',$sid)->where('s.pengguna_id',$uid)->countAllResults()>0;
        }
        if ($jenis==='permintaan_akses_ulang') {
            return $this->db->table('percobaan_ujian pu')
                ->join('progres_tp pr','pr.id=pu.progres_tp_id')
                ->join('siswa s','s.id=pr.siswa_id')
                ->join('paket_semester p','p.id=pr.paket_semester_id')
                ->join('penugasan_guru_gen g',
                    'g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id')
                ->where('pu.id',$sid)->where('g.guru_id',$uid)
                ->where('s.angkatan_id=p.angkatan_id')->countAllResults()>0;
        }
        if ($jenis==='izin_akses_ulang') {
            return $this->db->table('keputusan_ujian_batal k')
                ->join('percobaan_ujian pu','pu.id=k.percobaan_ujian_id')
                ->join('progres_tp pr','pr.id=pu.progres_tp_id')
                ->join('siswa s','s.id=pr.siswa_id')
                ->where('k.id',$sid)->where('s.pengguna_id',$uid)
                ->countAllResults()>0;
        }
        if ($jenis==='tp_siap_tersedia') {
            return $this->db->table('tp t')
                ->join('paket_semester p','p.id=t.paket_semester_id')
                ->join('siswa s','s.angkatan_id=p.angkatan_id')
                ->where('t.id',$sid)->where('t.status','siap')
                ->where('s.pengguna_id',$uid)->countAllResults()>0;
        }
        if ($jenis==='pra_semester_dibatalkan') {
            return $this->db->table('percobaan_ujian pu')
                ->join('siswa s','s.id=pu.siswa_id')
                ->where('pu.id',$sid)->where('s.pengguna_id',$uid)
                ->where('pu.jenis','pra_semester')->countAllResults()>0;
        }
        // Belum ada alur koreksi nilai siswa yang diimplementasikan.
        // Jangan menawarkan tautan untuk peristiwa tersebut tanpa Service sah.
        return false;
    }
}
