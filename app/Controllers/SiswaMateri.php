<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KegiatanMateriSiswaService;
use App\Services\MateriSiswaService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** S08A: akses baca media privat; tidak mengubah nilai/progres tanpa bukti belajar. */
final class SiswaMateri extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function lihat(int $tpId, int $materiId): ResponseInterface|string
    {
        try {
            $auth = new AuthService();
            if (!$auth->izinkan('siswa')) throw new DomainException('Akses hanya untuk Siswa.');
            $data = (new MateriSiswaService())->lihat($tpId, $materiId);
            $data['kegiatan'] = (new KegiatanMateriSiswaService())->status($tpId, $materiId);
            $akun = $auth->penggunaSaatIni();
            $this->response->setHeader('Cache-Control', 'private, no-store');
            return view('siswa/materi', array_merge($data, [
                'judul' => 'Materi Pembelajaran',
                'namaPengguna' => $akun['nama'],
                'peranAktif' => 'Siswa',
                'tautanNavigasi' => NavigasiService::untuk('siswa', 'pembelajaran'),
            ]));
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('publik/ditolak', ['judul' => 'Materi belum dapat dibuka']));
        } catch (Throwable $e) {
            log_message('error', 'S08A akses Materi: {error}', ['error' => $e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Materi belum dapat dimuat. Hubungi pengelola.');
        }
    }

    public function media(int $tpId, int $materiId): ResponseInterface
    {
        try {
            $item = (new MateriSiswaService())->media($tpId, $materiId);
            $size = $item['bytes'];
            $start = 0;
            $end = $size - 1;
            $range = $this->request->getHeaderLine('Range');
            if ($range !== '') {
                if (!in_array($item['jenis'], ['pdf', 'audio'], true)
                    || preg_match('/^bytes=(\\d*)-(\\d*)$/D', $range, $m) !== 1
                    || ($m[1] === '' && $m[2] === '')) {
                    return $this->tidakSahRange($size);
                }
                if ($m[1] === '') {
                    $count = (int) $m[2];
                    if ($count < 1) return $this->tidakSahRange($size);
                    $start = max(0, $size - $count);
                } else {
                    $start = (int) $m[1];
                    $end = $m[2] === '' ? $end : min($end, (int) $m[2]);
                }
                if ($start > $end || $start >= $size) return $this->tidakSahRange($size);
            }

            $length = $end - $start + 1;
            $fp = @fopen($item['path'], 'rb');
            if ($fp === false) throw new DomainException('Berkas tidak dapat dibaca.');
            try {
                if (fseek($fp, $start) !== 0) throw new DomainException('Posisi media tidak valid.');
                $data = '';
                while (strlen($data) < $length && !feof($fp)) {
                    $chunk = fread($fp, min(65536, $length - strlen($data)));
                    if ($chunk === false || $chunk === '') break;
                    $data .= $chunk;
                }
            } finally {
                fclose($fp);
            }
            if (strlen($data) !== $length) throw new DomainException('Media tidak lengkap.');

            $response = $this->response->setStatusCode($range === '' ? 200 : 206)
                ->setHeader('Content-Type', $item['mime'])
                ->setHeader('Content-Length', (string) $length)
                ->setHeader('Content-Disposition', 'inline')
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Content-Security-Policy', "default-src 'none'; sandbox")
                ->setHeader('Accept-Ranges', in_array($item['jenis'], ['pdf', 'audio'], true)
                    ? 'bytes' : 'none')
                ->setBody($data);
            if ($range !== '') {
                $response->setHeader('Content-Range',
                    'bytes ' . $start . '-' . $end . '/' . $size);
            }
            return $response;
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody('Media tidak tersedia.');
        } catch (Throwable $e) {
            log_message('error', 'S08A media siswa: {error}', ['error' => $e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody('Media gagal dimuat.');
        }
    }

    /** S08: unduh berkas asli hanya setelah gerbang media selesai di server. */
    public function unduh(int $tpId, int $materiId): ResponseInterface
    {
        try {
            $item = (new MateriSiswaService())->mediaUntukUnduh($tpId, $materiId);
            $ekstensi = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'audio/mpeg' => 'mp3',
                'audio/mp4' => 'm4a',
            ][$item['mime']] ?? null;
            if ($ekstensi === null) {
                throw new DomainException('Jenis berkas unduhan tidak sah.');
            }
            // Tidak menerima path/nama file dari URL atau input siswa.
            $nama = 'materi-' . $tpId . '-' . $materiId . '.' . $ekstensi;
            $data = @file_get_contents($item['path']);
            if ($data === false || strlen($data) !== (int) $item['bytes']) {
                throw new DomainException('Berkas unduhan belum tersedia.');
            }
            return $this->response->setStatusCode(200)
                ->setHeader('Content-Type', $item['mime'])
                ->setHeader('Content-Length', (string) strlen($data))
                ->setHeader('Content-Disposition', 'attachment; filename="' . $nama . '"')
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Content-Security-Policy', "default-src 'none'; sandbox")
                ->setBody($data);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody('Unduhan belum tersedia. Tuntaskan waktu belajar media.');
        } catch (Throwable $e) {
            log_message('error', 'S08 unduh Materi siswa: {error}', ['error' => $e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody('Unduhan materi gagal. Hubungi pengelola.');
        }
    }

    private function tidakSahRange(int $size): ResponseInterface
    {
        return $this->response->setStatusCode(416)
            ->setHeader('Content-Range', 'bytes */' . $size)
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Accept-Ranges', 'bytes');
    }
}
