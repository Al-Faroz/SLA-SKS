<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PraSemesterJadwalService;
use App\Services\PraSemesterSesiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class AdminPraSemester extends BaseController
{
    protected $helpers = ['url','form'];

    private function suffixFilter(): string
    {
        try {
            $f=PraSemesterJadwalService::filterDaftar([
                'gen'=>$this->request->getGet('gen'),
                'mapel'=>$this->request->getGet('mapel'),
                'semester'=>$this->request->getGet('semester'),
                'status'=>$this->request->getGet('status') ?? '',
            ]);
            $query=http_build_query(array_filter($f,
                static fn(mixed $value): bool => $value !== null && $value !== ''));
            return $query === '' ? '' : '?'.$query;
        } catch (DomainException) {
            return '';
        }
    }

    public function index(?int $paketId = null): ResponseInterface|string
    {
        try {
            $service = new PraSemesterJadwalService();
            $raw = [
                'gen'=>$this->request->getGet('gen'),
                'mapel'=>$this->request->getGet('mapel'),
                'semester'=>$this->request->getGet('semester'),
                'status'=>$this->request->getGet('status') ?? '',
            ];
            $filter = PraSemesterJadwalService::filterDaftar($raw);
            $akun = (new AuthService())->penggunaSaatIni();
            $sesi = new PraSemesterSesiService();
            $adaSesi = $sesi->terpasang();
            $daftarSesi = $paketId !== null && $adaSesi ? $sesi->daftar($paketId) : [];
            $calonSiswa = []; $sesiTerpilih = null;
            $sesiId = $this->request->getGet('sesi');
            if ($paketId !== null && $adaSesi && is_string($sesiId)
                && ctype_digit($sesiId) && (int)$sesiId > 0) {
                foreach ($daftarSesi as $item) {
                    if ((int)$item['id'] === (int)$sesiId && $item['jenis'] === 'susulan') {
                        $sesiTerpilih = $item;
                        $calonSiswa = $sesi->calonSusulan($paketId,(int)$sesiId);
                        break;
                    }
                }
            }
            return view('admin/pra_semester', [
                'judul'=>'Jadwal Pra-Semester',
                'namaPengguna'=>$akun['nama'], 'peranAktif'=>'Admin',
                'tautanNavigasi'=>NavigasiService::untuk('admin','pra_semester'),
                'daftar'=>$service->daftar($raw),
                'filter'=>$filter,
                'opsiFilter'=>$service->opsiFilter(),
                'pilih'=>$paketId !== null ? $service->lihat($paketId) : null,
                'terpasangSesi'=>$adaSesi,
                'daftarSesi'=>$daftarSesi,
                'sesiTerpilih'=>$sesiTerpilih,
                'calonSiswa'=>$calonSiswa,
            ]);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak',['judul'=>'Pengaturan Pra-Semester ditolak'])
            );
        } catch (Throwable $e) {
            log_message('error','Jadwal Pra-Semester: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setBody('Jadwal belum dapat dimuat.');
        }
    }

    public function simpan(int $paketId): RedirectResponse
    {
        $url=site_url('admin/pra-semester/' . $paketId) . $this->suffixFilter();
        try {
            (new PraSemesterJadwalService())->simpan($paketId,
                $this->request->getPost('mulai'),
                $this->request->getPost('akhir'),
                $this->request->getPost('durasi'));
            return redirect()->to(site_url('admin/pra-semester') . $this->suffixFilter())
                ->with('sukses','Jadwal ujian utama dan durasi per peserta berhasil disimpan.');
        } catch (DomainException $e) {
            return redirect()->to($url)->withInput()->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Simpan jadwal Pra-Semester: {error}', ['error'=>$e->getMessage()]);
            return redirect()->to($url)->withInput()
                ->with('galat','Jadwal belum tersimpan. Periksa log server.');
        }
    }
    public function simpanSesi(int $paketId): RedirectResponse
    {
        $url=site_url('admin/pra-semester/'.$paketId) . $this->suffixFilter();
        try {
            $tipe=$this->request->getPost('sesi_tipe');
            if (!is_string($tipe) || !in_array($tipe,
                ['remedial/remedial','susulan/normal','susulan/remedial'],true)) {
                throw new DomainException('Pilih jenis ujian remedial atau susulan yang sah.');
            }
            [$jenis,$tahap]=explode('/',$tipe,2);
            (new PraSemesterSesiService())->buat($paketId,$jenis,$tahap,
                $this->request->getPost('mulai'),$this->request->getPost('akhir'),
                $this->request->getPost('durasi'));
            return redirect()->to($url)
                ->with('sukses','Jadwal tambahan berhasil disimpan. Tetapkan peserta bila jenisnya susulan.');
        } catch (DomainException $e) {
            return redirect()->to($url)->withInput()->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Simpan sesi Pra-Semester: {error}',['error'=>$e->getMessage()]);
            return redirect()->to($url)->withInput()->with('galat','Jadwal tambahan gagal disimpan.');
        }
    }

    public function tambahPeserta(int $paketId, int $sesiId): RedirectResponse
    {
        $base=site_url('admin/pra-semester/'.$paketId);
        $suffix=$this->suffixFilter();
        $url=$base.'?sesi='.$sesiId.($suffix === '' ? '' : '&'.substr($suffix,1));
        try {
            $ids=$this->request->getPost('siswa_id');
            if (!is_array($ids)) throw new DomainException('Pilih peserta susulan yang sah.');
            $count=(new PraSemesterSesiService())->tambahPeserta($paketId,$sesiId,$ids);
            return redirect()->to($url)->with('sukses',$count.' peserta susulan ditambahkan.');
        } catch (DomainException $e) {
            return redirect()->to($url)->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Simpan peserta susulan Pra-Semester: {error}',
                ['error'=>$e->getMessage()]);
            return redirect()->to($url)->with('galat','Peserta susulan gagal disimpan.');
        }
    }

}
