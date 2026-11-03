<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use Throwable;

/**
 * A12-4 — indikator administrasi akademik dari database dan file server.
 * Hanya SELECT, tidak memulai proses siswa/ujian ataupun memperbaiki data.
 * Tidak mengarang status backup/cron bila belum mempunyai pemeriksa resmi.
 */
final class DashboardAdminService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
    }

    private function admin(): void
    {
        $a = ($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? '') !== 'admin'
            || ($a['status'] ?? '') !== 'aktif'
            || (int)($a['wajib_ganti_sandi'] ?? 1) !== 0
            || (int)($a['id'] ?? 0) < 1) {
            throw new DomainException('Ringkasan hanya untuk Admin aktif.');
        }
    }

    /** @return array<string,mixed> */
    public function ringkasan(): array
    {
        $this->admin();

        // Pertahankan 4 KPI referensi dashboard Admin yang telah ada.
        $gen = (int)$this->db->table('ref_angkatan')->countAllResults();
        $mapel = (int)$this->db->table('ref_mapel')->countAllResults();
        $ditugaskan = (int)$this->db->table('penugasan_guru_gen')
            ->where('guru_id IS NOT NULL',null,false)->countAllResults();
        $belum = max(0,$gen*$mapel-$ditugaskan);

        // Akun aktif berbeda dari siswa terdaftar (status siswa terpisah).
        $akunAktif = (int)$this->db->table('pengguna')
            ->where('status','aktif')->countAllResults();
        $siswa = (int)$this->db->table('siswa')->countAllResults();
        $tpSiap = (int)$this->db->table('tp')->where('status','siap')
            ->countAllResults();
        $tpDraf = (int)$this->db->table('tp')->where('status','draf')
            ->countAllResults();

        // Sumber antrean identik dengan halaman persetujuan Admin: hanya
        // foto yang masih menjadi pointer menunggu milik profil staf.
        $fotoMenunggu = (int)$this->db->table('profil_pengelola pr')
            ->join('foto_profil f','f.id=pr.foto_menunggu_id')
            ->where('f.status','menunggu')->countAllResults();

        // Satu sertifikat berlaku per pointer aktif; arsip digantikan tidak ikut.
        $sertifikatAktif = (int)$this->db->table('sertifikat_aktif a')
            ->join('sertifikat c','c.id=a.sertifikat_id')
            ->where('c.status','berlaku')->countAllResults();

        $ttd=$this->db->table('sertifikat_aset_aktif aa')
            ->select('a.nama_acak,a.mime,a.sha256')
            ->join('sertifikat_aset a',
                'a.id=aa.aset_id AND a.jenis=aa.jenis')
            ->where('aa.jenis','ttd')->get()->getRowArray();
        $statusTtd='belum_aktif';
        if ($ttd!==null) {
            try {
                SertifikatAsetService::path($ttd);
                $statusTtd='siap';
            } catch (Throwable $e) {
                // Jangan membeberkan path privat, SHA, atau isi berkas pada UI.
                log_message('warning',
                    'Dashboard Admin: berkas TTD aktif tidak dapat diverifikasi.');
                $statusTtd='berkas_tidak_valid';
            }
        }

        // Tidak ada ambang batas disk/cron dalam Master; baca angka disk saja.
        // false/null artinya tidak dapat diukur, bukan otomatis sehat.
        $disk=@disk_free_space(WRITEPATH);
        $diskSisa=is_float($disk)||is_int($disk) ? max(0,(int)$disk) : null;

        $perhatian=[];
        if ($gen===0 || $mapel===0) {
            $perhatian[]=['pesan'=>'Referensi Gen atau Mata Pelajaran belum lengkap.',
                'label'=>'Kelola referensi','path'=>'admin/referensi'];
        }
        if ($belum>0) {
            $perhatian[]=['pesan'=>$belum.' kombinasi potensial Gen–Mapel belum ditetapkan Guru (dari seluruh Gen × Mapel).',
                'label'=>'Periksa pengampu','path'=>'admin/mapel-guru'];
        }
        if ($fotoMenunggu>0) {
            $perhatian[]=['pesan'=>$fotoMenunggu.' foto profil staf menunggu persetujuan.',
                'label'=>'Tinjau foto','path'=>'admin/foto-profil'];
        }
        if ($statusTtd==='belum_aktif') {
            $perhatian[]=['pesan'=>'Tanda tangan kepala madrasah belum diaktifkan.',
                'label'=>'Kelola TTD','path'=>'admin/sertifikat/aset'];
        } elseif ($statusTtd==='berkas_tidak_valid') {
            $perhatian[]=['pesan'=>'Berkas TTD aktif tidak ditemukan atau tidak sesuai checksum.',
                'label'=>'Periksa aset','path'=>'admin/sertifikat/aset'];
        }

        return [
            'gen'=>$gen,'mapel'=>$mapel,
            'guru_ditugaskan'=>$ditugaskan,'belum_ditugaskan'=>$belum,
            'akun_aktif'=>$akunAktif,'siswa'=>$siswa,
            'tp_siap'=>$tpSiap,'tp_draf'=>$tpDraf,
            'foto_menunggu'=>$fotoMenunggu,
            'sertifikat_berlaku'=>$sertifikatAktif,
            'ttd_status'=>$statusTtd,
            'disk_sisa_byte'=>$diskSisa,
            'cron_status'=>'belum_diperiksa',
            'backup_status'=>'belum_diperiksa',
            'perhatian'=>$perhatian,
        ];
    }
}
