<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\EksporNilaiGuruService;
use App\Services\PetaTpService;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/**
 * A7: file XLSX privat melalui GET berizin.
 * Filter role dan G1 diverifikasi lagi di Service untuk setiap batch.
 */
final class GuruEksporNilai extends BaseController
{
    protected $helpers = ['url'];

    public function unduh(): DownloadResponse|RedirectResponse
    {
        $asal = site_url('guru/rekap-nilai');
        $akun = (new AuthService())->penggunaSaatIni();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            return redirect()->to($asal)->with('rekap_galat',
                'Hanya Guru aktif dapat mengunduh nilai siswa.');
        }
        $pilihan = $this->request->getGet('pilihan');
        $semester = $this->request->getGet('semester');
        $jenisTp = $this->request->getGet('jenis_tp') ?? 'semua';
        $bab = $this->request->getGet('bab') ?? '0';
        if (!is_string($pilihan) || !ctype_digit($pilihan) || strlen($pilihan) > 5
            || !is_string($semester) || !preg_match('/^[0-6]$/D', $semester)
            || !is_string($jenisTp)
            || !in_array($jenisTp,['semua','esensial','non_esensial'],true)
            || !is_string($bab) || !ctype_digit($bab) || strlen($bab)>7
            || ($semester === '0' && (int)$bab !== 0)) {
            return redirect()->to($asal)->with('rekap_galat',
                'Filter unduhan nilai tidak valid.');
        }
        try {
            $svc = new EksporNilaiGuruService();
            $daftar = (new PetaTpService())->genMapelDiampu();
            $dipilih = PetaTpService::pilihPenugasan($daftar,(int)$pilihan);
            if ($dipilih === null) {
                throw new DomainException('Belum ada penugasan Guru yang dapat diekspor.');
            }
            $hasil = $svc->unduh($dipilih,(int)$semester,$jenisTp,
                (int)$bab > 0 ? (int)$bab : null);
            return $this->response->download($hasil['nama'],$hasil['isi'],true)
                ->setHeader('Content-Type',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Cache-Control','private, no-store, max-age=0')
                ->setHeader('Pragma','no-cache')
                ->setHeader('X-Content-Type-Options','nosniff')
                ->setHeader('X-Robots-Tag','noindex, nofollow');
        } catch (DomainException $e) {
            return redirect()->to($asal)->with('rekap_galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','A7 Ekspor Nilai Guru gagal: {error}',
                ['error'=>$e->getMessage()]);
            return redirect()->to($asal)->with('rekap_galat',
                'Berkas Excel gagal dibuat. Periksa log server atau hubungi Admin.');
        }
    }
}
