<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PraSemesterGuruService;
use App\Services\PraSemesterWordService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class GuruPraSemester extends BaseController
{
    protected $helpers = ['url','form'];

    private function suffixGen(): string
    {
        try {
            $gen=PraSemesterGuruService::filterGen($this->request->getGet('gen'));
            return $gen === null ? '' : '?gen='.$gen;
        } catch (DomainException) {
            return '';
        }
    }

    public function index(?int $paketId = null): ResponseInterface|string
    {
        try {
            $service = new PraSemesterGuruService();
            $filterGen = PraSemesterGuruService::filterGen($this->request->getGet('gen'));
            $paket = $paketId === null ? $service->paketGuru($filterGen) : [];
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
            $wordService = $paketId === null ? null : new PraSemesterWordService();
            return view($paketId === null
                ? 'guru/pra_semester'
                : 'guru/pra_semester_detail', [
                'judul'=>'Bank Soal Pra-Semester',
                'namaPengguna'=>$akun['nama'], 'peranAktif'=>'Guru',
                'tautanNavigasi'=>NavigasiService::untuk('guru','pra_semester'),
                'paketGuru'=>$paket,'konteks'=>$konteks,'soalEdit'=>$soalEdit,
                'filterGen'=>$filterGen,
                'pilihanGen'=>$paketId === null ? $service->pilihanGen() : [],
                'word_terpasang'=>$wordService === null ? false : $wordService->terpasang(),
                'word_riwayat'=>$wordService === null ? [] : $wordService->riwayat($paketId),
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
        $url = site_url('guru/pra-semester/' . $paketId) . $this->suffixGen();
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
        $url = site_url('guru/pra-semester/' . $paketId) . $this->suffixGen();
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
        $url = site_url('guru/pra-semester/' . $paketId) . $this->suffixGen();
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
    public function unduhWord(int $paketId): ResponseInterface
    {
        try {
            $raw = $this->request->getGet('jumlah');
            if (!is_string($raw) || !ctype_digit($raw)
                || (int)$raw < 30 || (int)$raw > 200) {
                throw new DomainException('Jumlah soal Word harus 30–200.');
            }
            $word = (new PraSemesterWordService())->unduh($paketId,(int)$raw);
            return $this->response
                ->setHeader('Content-Type','application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->setHeader('Content-Disposition',
                    'attachment; filename="SLA-SKS-PRA-SEMESTER-PAKET-'.$paketId.'.docx"')
                ->setHeader('Cache-Control','private, no-store')
                ->setBody($word);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        } catch (Throwable $e) {
            log_message('error','Template Word Pra-Semester gagal: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Template belum dapat dibuat; periksa log aplikasi.');
        }
    }

    public function unggahWord(int $paketId): RedirectResponse
    {
        $url=site_url('guru/pra-semester/'.$paketId).$this->suffixGen();
        try {
            $file=$this->request->getFile('word_soal');
            if (!$file || !$file->isValid()
                || strtolower($file->getClientExtension()) !== 'docx'
                || $file->getSize() > 15*1024*1024) {
                throw new DomainException('Pilih DOCX valid maksimal 15 MB.');
            }
            $stage=(new PraSemesterWordService())->unggah($file->getTempName(),$paketId);
            $tail=$this->suffixGen();
            return redirect()->to(site_url('guru/pra-semester/'.$paketId.'/word/review/'.$stage['id']).$tail);
        } catch (DomainException $e) {
            return redirect()->to($url)->with('word_galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Unggah Word Pra-Semester: {error}',['error'=>$e->getMessage()]);
            return redirect()->to($url)->with('word_galat',
                'Word tidak dapat diproses. Periksa struktur tabel, ukuran, atau log server.');
        }
    }

    public function reviewWord(int $paketId,string $id): ResponseInterface|string
    {
        $url=site_url('guru/pra-semester/'.$paketId).$this->suffixGen();
        try {
            $word=new PraSemesterWordService();
            $data=$word->buka($paketId,$id);
            $exam=(new PraSemesterGuruService())->konteks($paketId);
            $akun=(new AuthService())->penggunaSaatIni();
            return view('guru/pra_semester_word_review',[
                'judul'=>'Review Word Pra-Semester',
                'namaPengguna'=>$akun['nama'],'peranAktif'=>'Guru',
                'tautanNavigasi'=>NavigasiService::untuk('guru','pra_semester'),
                'paket'=>$exam['paket'],'stage'=>$data['row'],'hasil'=>$data['hasil'],
                'sudah_dipakai'=>$exam['terpakai'],
                'token_review'=>$id,'filterGen'=>$this->suffixGen(),
            ]);
        } catch (DomainException $e) {
            return redirect()->to($url)->with('word_galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Review Word Pra-Semester: {error}',['error'=>$e->getMessage()]);
            return redirect()->to($url)->with('word_galat','Pratinjau belum dapat dibuka; unggah ulang.');
        }
    }

    public function konfirmasiWord(int $paketId,string $id): RedirectResponse
    {
        $reviewUrl=site_url('guru/pra-semester/'.$paketId.'/word/review/'.$id)
            .$this->suffixGen();
        $detailUrl=site_url('guru/pra-semester/'.$paketId).$this->suffixGen();
        try {
            if ($this->request->getPost('tinjau') !== 'ya') {
                throw new DomainException('Centang bahwa semua soal dan BAB telah ditinjau.');
            }
            $result=(new PraSemesterWordService())->konfirmasi($paketId,$id);
            return redirect()->to($detailUrl)->with('word_sukses',
                $result['baru'].' soal Pra-Semester ditambahkan. '
                .'Soal manual dan soal lama tidak ditimpa.');
        } catch (DomainException $e) {
            return redirect()->to($reviewUrl)->with('word_galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Konfirmasi Word Pra-Semester: {error}',['error'=>$e->getMessage()]);
            return redirect()->to($reviewUrl)->with('word_galat',
                'Gagal menyimpan Word secara utuh. Tidak ada impor parsial yang diterapkan.');
        }
    }

}
