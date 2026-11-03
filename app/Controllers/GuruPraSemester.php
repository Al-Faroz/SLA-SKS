<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PraSemesterGuruService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class GuruPraSemester extends BaseController
{
    protected $helpers = ['url','form'];

    public function index(?int $paketId = null): ResponseInterface|string
    {
        try {
            $service = new PraSemesterGuruService();
            $paket = $service->paketGuru();
            $konteks = $paketId === null ? null : $service->konteks($paketId);
            $soalEdit = null;
            if ($konteks !== null) {
                $id = filter_var($this->request->getGet('edit'), FILTER_VALIDATE_INT);
                if ($id !== false && $id > 0) {
                    foreach ($konteks['soal'] as $soal) {
                        if ((int) $soal['id'] === $id) $soalEdit = $soal;
                    }
                }
            }
            $akun = (new AuthService())->penggunaSaatIni();
            return view('guru/pra_semester', [
                'judul'=>'Bank Soal Pra-Semester',
                'namaPengguna'=>$akun['nama'], 'peranAktif'=>'Guru',
                'tautanNavigasi'=>NavigasiService::untuk('guru','pra_semester'),
                'paketGuru'=>$paket,'konteks'=>$konteks,'soalEdit'=>$soalEdit,
            ]);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak',['judul'=>'Bank Pra-Semester tidak tersedia'])
            );
        } catch (Throwable $e) {
            log_message('error','Gagal membuka bank Pra-Semester: {error}', ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Bank soal belum dapat dimuat. Periksa log server.');
        }
    }

    public function simpanSoal(int $paketId): RedirectResponse
    {
        $url = site_url('guru/pra-semester/' . $paketId);
        $raw = $this->request->getPost('soal_id');
        $id = is_string($raw) && ctype_digit($raw) ? (int) $raw : 0;
        try {
            (new PraSemesterGuruService())->simpanSoal($paketId, (array)$this->request->getPost(), $id);
            return redirect()->to($url)->with('sukses','Soal Pra-Semester disimpan.');
        } catch (DomainException $e) {
            return redirect()->to($url)->withInput()->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Simpan soal Pra-Semester: {error}', ['error'=>$e->getMessage()]);
            return redirect()->to($url)->withInput()->with('galat','Soal gagal disimpan. Periksa log server.');
        }
    }

    public function statusSoal(int $paketId, int $soalId): RedirectResponse
    {
        $url = site_url('guru/pra-semester/' . $paketId);
        try {
            $aktif = $this->request->getPost('aktif');
            if (!in_array($aktif, ['0','1'],true)) {
                throw new DomainException('Status soal harus 0 atau 1.');
            }
            (new PraSemesterGuruService())->statusSoal($paketId,$soalId,$aktif === '1');
            return redirect()->to($url)->with('sukses','Status soal diperbarui.');
        } catch (DomainException $e) {
            return redirect()->to($url)->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Status soal Pra-Semester: {error}', ['error'=>$e->getMessage()]);
            return redirect()->to($url)->with('galat','Status soal gagal diperbarui.');
        }
    }

    public function simpanKkm(int $paketId): RedirectResponse
    {
        $url = site_url('guru/pra-semester/' . $paketId);
        $raw = $this->request->getPost('kkm_khusus');
        try {
            if (!is_string($raw)) throw new DomainException('KKM tidak valid.');
            (new PraSemesterGuruService())->simpanKkm($paketId,$raw);
            return redirect()->to($url)->with('sukses','KKM Pra-Semester disimpan.');
        } catch (DomainException $e) {
            return redirect()->to($url)->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','KKM Pra-Semester gagal: {error}', ['error'=>$e->getMessage()]);
            return redirect()->to($url)->with('galat','KKM gagal disimpan.');
        }
    }
}
