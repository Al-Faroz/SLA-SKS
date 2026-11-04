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

    public function lihatSoal(int $tpId, int $soalId): ResponseInterface|string
    {
        try {
            $data = $this->data($tpId, $soalId);
            return view('guru/soal_lihat', $data);
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('soal_galat', $e->getMessage());
        }
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

    /**
     * Pratinjau sementara dari HTML yang sudah melalui sanitasi server.
     * Tidak menulis bank soal, bukan pengganti tombol Simpan.
     */
    public function pratinjau(int $tpId): ResponseInterface
    {
        try {
            $context = (new DetailTpService())->lihat($tpId);
            if ($context['tp']['status'] !== 'draf') {
                throw new DomainException('TP sudah Siap dan tidak dapat disunting.');
            }
            $post = (array) $this->request->getPost();
            $jenis = $post['jenis'] ?? null;
            if (!is_string($jenis) || !in_array($jenis, ['diagnostik', 'sumatif'], true)
                || !in_array($post['kunci'] ?? null, ['A','B','C','D','E'], true)) {
                throw new DomainException('Pilih jenis ujian dan satu kunci A–E.');
            }
            $source = null;
            $idRaw = $post['soal_id'] ?? null;
            if ($idRaw !== null && $idRaw !== '') {
                if (!is_string($idRaw) || !ctype_digit($idRaw) || (int) $idRaw < 1) {
                    throw new DomainException('Identitas soal tidak valid.');
                }
                $row = (new KelolaSoalGuruService())->lihat($tpId, (int) $idRaw);
                if ($row['jenis'] !== $jenis || $row['versi_konten_tp_id'] !== null) {
                    throw new DomainException('Soal bukan bagian bank yang dapat disunting.');
                }
                $rich = json_decode((string) ($row['konten_terstruktur_json'] ?? ''), true);
                $source = is_array($rich) && is_string($rich['word_source_id'] ?? null)
                    ? $rich['word_source_id'] : null;
            }
            $semua = [];
            foreach (['pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e'] as $name) {
                if (!is_string($post[$name] ?? null)) {
                    throw new DomainException('Isi pertanyaan dan semua pilihan wajib tersedia.');
                }
                $clean = HtmlSoalGuruService::bersihkan($post[$name], $tpId, $source, true);
                $semua[$name] = HtmlSoalGuruService::untukGuru($clean['html'], $tpId, $source);
            }
            return $this->response->setJSON([
                'pertanyaan' => $semua['pertanyaan'],
                'opsi' => ['A' => $semua['opsi_a'], 'B' => $semua['opsi_b'],
                    'C' => $semua['opsi_c'], 'D' => $semua['opsi_d'], 'E' => $semua['opsi_e']],
                'kunci' => $post['kunci'],
                'csrfName' => csrf_token(), 'csrfHash' => csrf_hash(),
            ]);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'errorMessage' => $e->getMessage(),
                'csrfName' => csrf_token(), 'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Pratinjau soal gagal: {reason}', ['reason' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON([
                'errorMessage' => 'Pratinjau belum dapat ditampilkan.',
                'csrfName' => csrf_token(), 'csrfHash' => csrf_hash(),
            ]);
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
