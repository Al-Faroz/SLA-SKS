<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DetailTpService;
use App\Services\HtmlSoalGuruService;
use App\Services\KelolaSoalGuruService;
use App\Services\MediaEditorSoalService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;

final class GuruSoal extends BaseController
{
    private function data(int $tpId, ?int $soalId = null): array
    {
        $rincian = (new DetailTpService())->lihat($tpId);
        $akun = (new AuthService())->penggunaSaatIni();
        $rincian += [
            'judul' => 'Editor Bank Soal',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'soal' => $soalId ? (new KelolaSoalGuruService())->lihat($tpId, $soalId) : null,
        ];
        return $rincian;
    }

    public function baru(int $tpId, string $jenis): ResponseInterface|string
    {
        try {
            if (!in_array($jenis, ['diagnostik', 'sumatif'], true)) {
                throw new DomainException('Jenis bank tidak dikenal.');
            }
            $data = $this->data($tpId);
            if ($data['tp']['status'] !== 'draf') {
                throw new DomainException('TP sudah Siap; penyuntingan langsung tidak diizinkan.');
            }
            $data['jenis'] = $jenis;
            return view('guru/soal_form', $data);
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('soal_galat', $e->getMessage());
        }
    }

    public function edit(int $tpId, int $soalId): ResponseInterface|string
    {
        try {
            $data = $this->data($tpId, $soalId);
            if ($data['tp']['status'] !== 'draf') {
                throw new DomainException('TP Siap memerlukan prosedur koreksi.');
            }
            $data['jenis'] = $data['soal']['jenis'];
            $data['soal_versi'] = KelolaSoalGuruService::versi($data['soal']);
            return view('guru/soal_form', $data);
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('soal_galat', $e->getMessage());
        }
    }

    public function simpan(int $tpId, string $jenis, int $soalId = 0): ResponseInterface
    {
        try {
            $post = (array) $this->request->getPost();
            $id = (new KelolaSoalGuruService())->simpan(
                $tpId, $jenis, $post, $soalId > 0 ? $soalId : null
            );
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal?jenis=' . $jenis))
                ->with('soal_sukses', 'Soal #' . $id . ' berhasil disimpan.');
        } catch (DomainException $e) {
            return redirect()->back()->withInput()->with('soal_galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Simpan soal gagal: {pesan}', ['pesan' => $e->getMessage()]);
            return redirect()->back()->withInput()
                ->with('soal_galat', 'Soal gagal disimpan. Silakan ulangi.');
        }
    }

    public function status(int $tpId, int $soalId): ResponseInterface
    {
        try {
            $aktif = $this->request->getPost('aktif');
            if (!in_array($aktif, ['0','1'], true)) {
                throw new DomainException('Tindakan soal tidak valid.');
            }
            $row = (new KelolaSoalGuruService())->lihat($tpId, $soalId);
            (new KelolaSoalGuruService())->status(
                $tpId, $soalId, $aktif === '1', (string) $this->request->getPost('versi')
            );
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal?jenis=' . $row['jenis']))
                ->with('soal_sukses', $aktif === '1' ? 'Soal diaktifkan.' : 'Soal dinonaktifkan tanpa menghapus data.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('soal_galat', $e->getMessage());
        }
    }

    public function uploadGambar(int $tpId): ResponseInterface
    {
        try {
            $file = $this->request->getFile('gambar');
            if ($file === null || !$file->isValid()) {
                throw new DomainException('Pilih gambar terlebih dahulu.');
            }
            $r = MediaEditorSoalService::unggah($tpId, $file->getTempName());
            return $this->response->setJSON([
                'result' => [['url' => $r['url'], 'name' => 'Gambar soal', 'size' => $r['size']]],
                'csrfName' => csrf_token(), 'csrfHash' => csrf_hash(),
            ]);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'errorMessage' => $e->getMessage(), 'csrfName' => csrf_token(), 'csrfHash' => csrf_hash()
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gambar soal gagal: {pesan}', ['pesan' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['errorMessage' => 'Gambar gagal disimpan.',
                'csrfName' => csrf_token(), 'csrfHash' => csrf_hash()]);
        }
    }

    public function gambar(int $tpId, string $id): ResponseInterface
    {
        try {
            $r = MediaEditorSoalService::baca($tpId, $id);
            return $this->response->setHeader('Content-Type', $r['mime'])
                ->setHeader('Cache-Control', 'private, no-store')->setBody($r['data']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(404)->setBody('Gambar tidak tersedia.');
        }
    }
}
