<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EksporNilaiPraSemesterGuruService;
use App\Services\PetaTpService;
use App\Services\RekapNilaiPraSemesterGuruService;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/** A9: unduhan privat hasil ujian Pra-Semester dengan pemeriksaan G1. */
final class GuruEksporPraSemester extends BaseController
{
    protected $helpers = ['url'];

    public function unduh(): DownloadResponse|RedirectResponse
    {
        $asal=site_url('guru/rekap-nilai/pra-semester');
        $pilihan=$this->request->getGet('pilihan');
        $semester=$this->request->getGet('semester');
        if (!is_string($pilihan) || !ctype_digit($pilihan) || strlen($pilihan)>5
            || !is_string($semester) || !preg_match('/^[1-6]$/D',$semester)) {
            return redirect()->to($asal)->with('rekap_galat','Filter unduhan tidak sah.');
        }
        try {
            $svc=new RekapNilaiPraSemesterGuruService();
            $dipilih=PetaTpService::pilihPenugasan($svc->penugasan(),(int)$pilihan);
            if ($dipilih===null) {
                throw new DomainException('Tidak ada penugasan Guru yang dapat diekspor.');
            }
            $file=(new EksporNilaiPraSemesterGuruService())->unduh($dipilih,(int)$semester);
            return $this->response->download($file['nama'],$file['isi'],true)
                ->setHeader('Content-Type',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Cache-Control','private, no-store, max-age=0')
                ->setHeader('Pragma','no-cache')
                ->setHeader('X-Content-Type-Options','nosniff')
                ->setHeader('X-Robots-Tag','noindex, nofollow');
        } catch (DomainException $e) {
            return redirect()->to($asal)->with('rekap_galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','A9 Export Pra-Semester: {error}',
                ['error'=>$e->getMessage()]);
            return redirect()->to($asal)->with('rekap_galat',
                'Unduhan Excel Pra-Semester gagal dibuat. Periksa log server.');
        }
    }
}
