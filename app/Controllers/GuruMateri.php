<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\BerkasMateriService;
use App\Services\DetailTpService;
use App\Services\MateriDrafService;
use App\Services\NavigasiService;
use App\Services\VideoMateriService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class GuruMateri extends BaseController
{
    protected $helpers = ['url', 'form'];

    /** @return array<string,mixed>|null */
    private function formulir(): ?array
    {
        $nama = ['judul', 'jenis', 'petunjuk_resume', 'petunjuk_hots', 'min_waktu_menit', 'url_youtube'];
        $hasil = [];
        foreach ($nama as $kunci) {
            $nilai = $this->request->getPost($kunci);
            // Saat dropdown memilih file, kolom YouTube dinonaktifkan sehingga
            // field itu memang tidak dikirim browser.
            if ($kunci === 'url_youtube' && $nilai === null) {
                $nilai = '';
            }
            if (! is_string($nilai)) {
                return null;
            }
            $hasil[$kunci] = $nilai;
        }
        return $hasil;
    }

    private function versi(): ?int
    {
        $nilai = $this->request->getPost('struktur_versi');
        return is_string($nilai) && ctype_digit($nilai) && strlen($nilai) <= 10
            && (float) $nilai <= PHP_INT_MAX ? (int) $nilai : null;
    }

    private function batasPhpByte(string $nilai): int
    {
        if (! preg_match('/^\\s*(\\d+(?:\\.\\d+)?)\\s*([KMG]?)\\s*$/i', $nilai, $m)) {
            return 0;
        }
        $pengali = match (strtoupper($m[2])) {
            'G' => 1073741824, 'M' => 1048576, 'K' => 1024, default => 1,
        };
        return (int) ((float) $m[1] * $pengali);
    }

    private function pesanUpload(int $kode): string
    {
        return match ($kode) {
            UPLOAD_ERR_INI_SIZE => 'File melebihi batas upload_max_filesize PHP (' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas formulir.',
            UPLOAD_ERR_PARTIAL => 'Berkas hanya terunggah sebagian. Silakan unggah kembali.',
            UPLOAD_ERR_NO_FILE => 'Belum ada file yang dipilih.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara PHP untuk unggahan tidak tersedia.',
            UPLOAD_ERR_CANT_WRITE => 'PHP tidak dapat menulis berkas sementara.',
            UPLOAD_ERR_EXTENSION => 'Ekstensi PHP menghentikan unggahan.',
            default => 'Unggahan gagal (kode ' . $kode . ').',
        };
    }

    private function kembali(int $tpId, string $pesan, bool $sukses = false): RedirectResponse
    {
        return redirect()->to(site_url('guru/tp/' . $tpId))
            ->with($sukses ? 'sukses' : 'galat', $pesan);
    }

    public function tambah(int $tpId): RedirectResponse
    {
        $data = $this->formulir();
        $versi = $this->versi();
        if ($data === null || $versi === null) {
            $panjang = (int) $this->request->getHeaderLine('Content-Length');
            $batasPost = $this->batasPhpByte((string) ini_get('post_max_size'));
            if ($panjang > 0 && $batasPost > 0 && $panjang > $batasPost) {
                return $this->kembali($tpId,
                    'Ukuran unggahan melebihi batas post_max_size PHP (' . ini_get('post_max_size')
                    . '). Periksa pengaturan PHP XAMPP bersama pengelola server.');
            }
            return $this->kembali($tpId, 'Formulir Materi tidak lengkap atau sudah berubah. Periksa semua kolom, lalu muat ulang jika perlu.');
        }
        try {
            if (in_array($data['jenis'], ['pdf', 'gambar', 'audio'], true)) {
                $file = $this->request->getFile('media');
                if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
                    throw new DomainException('Pilih berkas ' . strtoupper((string) $data['jenis']) . ' sebelum menyimpan Materi.');
                }
                if (! $file->isValid()) {
                    throw new DomainException($this->pesanUpload($file->getError()));
                }
                (new BerkasMateriService())->tambahDenganMedia($tpId, $versi, $data, $file);
            } else {
                (new MateriDrafService())->tambah($tpId, $versi, $data);
            }
            return $this->kembali($tpId, 'Materi draf dan sumber belajar berhasil disimpan. Materi masih nonaktif.', true);
        } catch (DomainException $e) {
            return $this->kembali($tpId, $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Tambah Materi beserta media gagal: ' . get_class($e));
            return $this->kembali($tpId, 'Gagal menyimpan Materi/media. Periksa file, izin writable dan konfigurasi upload PHP.');
        }
    }

    public function edit(int $tpId, int $materiId): ResponseInterface|string
    {
        try {
            $rincian = (new DetailTpService())->lihat($tpId);
            if ($rincian['tp']['status'] !== 'draf') {
                throw new DomainException('TP sudah Siap.');
            }
            $materi = null;
            foreach ($rincian['materi'] as $baris) {
                if ((int) $baris['id'] === $materiId) {
                    $materi = $baris;
                    break;
                }
            }
            if ($materi === null) {
                throw new DomainException('Materi tidak ditemukan.');
            }
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)
                ->setBody(view('publik/ditolak', ['judul' => 'Akses Materi ditolak']));
        }

        $akun = (new AuthService())->penggunaSaatIni();
        return view('guru/materi_edit', [
            'judul' => 'Sunting Materi Draf',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'tp' => $rincian['tp'],
            'materi' => $materi,
        ]);
    }

    public function pratinjau(int $tpId, int $materiId): ResponseInterface|string
    {
        try {
            $rincian = (new DetailTpService())->lihat($tpId);
            $materi = null;
            foreach ($rincian['materi'] as $baris) {
                if ((int) $baris['id'] === $materiId) {
                    $materi = $baris;
                    break;
                }
            }
            if ($materi === null) {
                throw new DomainException('Materi tidak ditemukan.');
            }
            $urlEmbed = null;
            $urlAsli = null;
            $urlMedia = null;
            if ($materi['jenis'] === 'youtube') {
                if (! is_string($materi['url_youtube']) || $materi['url_youtube'] === '') {
                    throw new DomainException('Video Materi belum tersedia.');
                }
                $urlEmbed = VideoMateriService::urlEmbed($materi['url_youtube']);
                $urlAsli = VideoMateriService::urlStandar($materi['url_youtube']);
            } elseif (in_array($materi['jenis'], ['pdf', 'audio', 'gambar'], true)) {
                // Cek ulang metadata, kepemilikan TP, berkas fisik dan hak G1
                // sebelum mengeluarkan tautan proxy privat.
                (new BerkasMateriService())->media($tpId, $materiId);
                $urlMedia = site_url('guru/tp/' . $tpId . '/materi/' . $materiId . '/media');
            } else {
                throw new DomainException('Jenis media tidak dikenali.');
            }
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)
                ->setHeader('Cache-Control', 'no-store, private')
                ->setBody(view('publik/ditolak', ['judul' => 'Pratinjau tidak tersedia']));
        }

        $akun = (new AuthService())->penggunaSaatIni();
        return view('guru/materi_pratinjau', [
            'judul' => 'Pratinjau Media Materi',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'tp' => $rincian['tp'],
            'materi' => $materi,
            'urlEmbed' => $urlEmbed,
            'urlAsli' => $urlAsli,
            'urlMedia' => $urlMedia,
        ]);
    }

    public function simpan(int $tpId, int $materiId): RedirectResponse
    {
        $data = $this->formulir();
        $versi = $this->versi();
        if ($data === null || $versi === null) {
            return $this->kembali($tpId, 'Data formulir Materi tidak valid.');
        }
        try {
            $file = $this->request->getFile('media');
            if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                (new BerkasMateriService())->simpanDenganMedia($tpId, $materiId, $versi, $data, $file);
            } else {
                (new MateriDrafService())->simpan($tpId, $materiId, $versi, $data);
            }
            return $this->kembali($tpId, 'Materi draf berhasil diperbarui.', true);
        } catch (DomainException $e) {
            return $this->kembali($tpId, $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Edit Materi/media gagal: ' . get_class($e));
            return $this->kembali($tpId, 'Gagal mengubah Materi atau file. Periksa berkas dan izin writable.');
        }
    }

    public function unggahMedia(int $tpId, int $materiId): RedirectResponse
    {
        $versi = $this->versi();
        $file = $this->request->getFile('media');
        if ($versi === null || $file === null) {
            return $this->kembali($tpId, 'Pilih berkas media dan muat ulang halaman jika versi berubah.');
        }
        try {
            (new BerkasMateriService())->unggah($tpId, $materiId, $versi, $file);
            return $this->kembali($tpId, 'Media privat berhasil diunggah. Materi tidak otomatis aktif.', true);
        } catch (DomainException $e) {
            return $this->kembali($tpId, $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Media Materi gagal disimpan: ' . get_class($e));
            return $this->kembali($tpId, 'Media gagal diproses. Periksa ukuran, jenis file dan izin folder writable.');
        }
    }

    public function bukaMedia(int $tpId, int $materiId): ResponseInterface
    {
        try {
            $berkas = (new BerkasMateriService())->media($tpId, $materiId);
            $panjang = $berkas['bytes'];
            $awal = 0;
            $akhir = $panjang - 1;
            $range = $this->request->getHeaderLine('Range');
            if ($range !== '') {
                if (! in_array($berkas['jenis'], ['audio', 'pdf'], true)
                    || preg_match('/^bytes=(\\d*)-(\\d*)$/D', $range, $m) !== 1
                    || ($m[1] === '' && $m[2] === '')) {
                    return $this->response->setStatusCode(416)
                        ->setHeader('Content-Range', 'bytes */' . $panjang)
                        ->setHeader('Cache-Control', 'private, no-store');
                }
                if ($m[1] === '') {
                    $akhir = $panjang - 1;
                    $awal = max(0, $panjang - (int) $m[2]);
                } else {
                    $awal = (int) $m[1];
                    $akhir = $m[2] === '' ? $akhir : min($akhir, (int) $m[2]);
                }
                if ($awal > $akhir || $awal >= $panjang) {
                    return $this->response->setStatusCode(416)
                        ->setHeader('Content-Range', 'bytes */' . $panjang)
                        ->setHeader('Cache-Control', 'private, no-store');
                }
            }

            $length = $akhir - $awal + 1;
            $fp = @fopen($berkas['path'], 'rb');
            if ($fp === false) {
                throw new DomainException('Media tidak dapat dibaca.');
            }
            try {
                if (fseek($fp, $awal) !== 0) {
                    throw new DomainException('Media tidak dapat dibaca.');
                }
                $isi = '';
                while (strlen($isi) < $length && ! feof($fp)) {
                    $part = fread($fp, min(65536, $length - strlen($isi)));
                    if ($part === false || $part === '') {
                        break;
                    }
                    $isi .= $part;
                }
            } finally {
                fclose($fp);
            }
            if (strlen($isi) !== $length) {
                throw new DomainException('Media tidak lengkap.');
            }

            $res = $this->response
                ->setStatusCode($range === '' ? 200 : 206)
                ->setHeader('Content-Type', $berkas['mime'])
                ->setHeader('Content-Length', (string) $length)
                ->setHeader('Content-Disposition', 'inline')
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Content-Security-Policy', "default-src 'none'; sandbox")
                ->setHeader('Accept-Ranges', in_array($berkas['jenis'], ['audio', 'pdf'], true) ? 'bytes' : 'none')
                ->setBody($isi);
            if ($range !== '') {
                $res->setHeader('Content-Range', 'bytes ' . $awal . '-' . $akhir . '/' . $panjang);
            }
            return $res;
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody('Media tidak tersedia.');
        }
    }

    public function hapus(int $tpId, int $materiId): RedirectResponse
    {
        $versi = $this->versi();
        $konfirmasi = $this->request->getPost('konfirmasi');
        if ($versi === null || $konfirmasi !== 'HAPUS') {
            return $this->kembali($tpId, 'Konfirmasi penghapusan tidak sah.');
        }
        try {
            (new MateriDrafService())->hapus($tpId, $materiId, $versi);
            return $this->kembali($tpId, 'Materi draf dihapus. Urutan materi lainnya sudah diperbarui.', true);
        } catch (DomainException $e) {
            return $this->kembali($tpId, $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Hapus Materi draf ditolak: ' . get_class($e));
            return $this->kembali($tpId, 'Materi tidak dapat dihapus karena masih memiliki referensi atau sedang digunakan.');
        }
    }

    public function urut(int $tpId, int $materiId): RedirectResponse
    {
        $versi = $this->versi();
        $arah = $this->request->getPost('arah');
        if ($versi === null || ! is_string($arah)) {
            return $this->kembali($tpId, 'Permintaan urutan tidak valid.');
        }
        try {
            (new MateriDrafService())->geser($tpId, $materiId, $versi, $arah);
            return $this->kembali($tpId, 'Urutan Materi berhasil diperbarui.', true);
        } catch (DomainException $e) {
            return $this->kembali($tpId, $e->getMessage());
        }
    }
}
