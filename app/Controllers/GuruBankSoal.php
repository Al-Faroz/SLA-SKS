<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DetailTpService;
use App\Services\NavigasiService;
use App\Services\RingkasanBankSoalService;
use App\Services\TemplateWordSoalService;
use App\Services\KunciTemplateWordService;
use App\Services\PratinjauWordSoalService;
use App\Services\RenderWordSoalService;
use App\Services\ImporBankSoalService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;

final class GuruBankSoal extends BaseController
{
    public function index(int $tpId): ResponseInterface|string
    {
        try {
            // Penugasan aktif G1 dan konteks Gen–Mapel diverifikasi oleh Service.
            $rincian = (new DetailTpService())->lihat($tpId);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak', ['judul' => 'Akses bank soal ditolak'])
            );
        }

        $akun = (new AuthService())->penggunaSaatIni();
        $ringkasan = (new RingkasanBankSoalService())->lihat($tpId);

        return view('guru/bank_soal', array_merge($rincian, [
            'judul' => 'Bank Soal TP',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'ringkasan_bank' => $ringkasan,
        ]));
    }
    public function unduh(int $tpId, string $jenis): ResponseInterface
    {
        try {
            $rincian = (new DetailTpService())->lihat($tpId);
            if ($rincian['tp']['status'] !== 'draf') {
                throw new DomainException('Template untuk penyusunan hanya tersedia pada TP draf.');
            }
            if (! in_array($jenis, ['diagnostik', 'sumatif'], true)) {
                throw new DomainException('Jenis bank soal tidak valid.');
            }

            $jumlahRaw = $this->request->getGet('jumlah');
            $jumlah = filter_var($jumlahRaw, FILTER_VALIDATE_INT);
            $minimal = $jenis === 'diagnostik' ? 10 : 5;
            if ($jumlah === false || $jumlah < $minimal || $jumlah > 200) {
                throw new DomainException('Jumlah soal harus ' . $minimal . ' sampai 200.');
            }
            $db = db_connect();
            $paket = $rincian['paket'];
            $identitas = $db->table('paket_semester p')
                ->select('a.nama AS gen, m.nama AS mapel, k.nama AS kurikulum')
                ->join('ref_angkatan a', 'a.id = p.angkatan_id')
                ->join('ref_mapel m', 'm.id = p.mapel_id')
                ->join('ref_kurikulum k', 'k.id = p.kurikulum_id')
                ->where('p.id', (int) $paket['id'])
                ->get()->getRowArray();
            if ($identitas === null) {
                throw new DomainException('Identitas paket tidak ditemukan.');
            }
            $akun = (new AuthService())->penggunaSaatIni();
            $identitas = array_merge($identitas, [
                'tp_id' => $tpId,
                'paket_id' => (int) $paket['id'],
                'guru_id' => (int) $akun['id'],
                'guru' => (string) $akun['nama'],
                'semester' => (string) $paket['semester'],
                'kode_tp' => (string) $rincian['tp']['kode_tp'],
                'judul_tp' => (string) $rincian['tp']['judul'],
                'struktur_versi' => (int) $rincian['tp']['struktur_versi'],
            ]);
            $key = KunciTemplateWordService::ambil();
            $data = TemplateWordSoalService::buat($identitas, $jenis, $jumlah, $key);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(400)->setBody(
                '<!doctype html><html lang="id"><meta charset="utf-8"><title>Unduhan tidak tersedia</title><body>' .
                '<h1>Unduhan tidak dapat diproses</h1><p>' . esc($e->getMessage()) . '</p>' .
                '<p><a href="' . esc(site_url('guru/tp/' . $tpId . '/bank-soal'), 'attr') . '">Kembali ke bank soal</a></p></body></html>'
            );
        } catch (\Throwable $e) {
            log_message('error', 'Gagal unduh Word TP {tpId}: {reason}', [
                'tpId' => $tpId, 'reason' => $e->getMessage(),
            ]);
            return $this->response->setStatusCode(500)->setBody(
                '<!doctype html><html lang="id"><meta charset="utf-8"><title>Gagal membuat Word</title><body>' .
                '<h1>File Word belum berhasil dibuat</h1><p>Periksa log aplikasi atau hubungi pengelola.</p>' .
                '<p><a href="' . esc(site_url('guru/tp/' . $tpId . '/bank-soal'), 'attr') . '">Kembali ke bank soal</a></p></body></html>'
            );
        }

        $nama = 'SLA-SKS-' . strtoupper($jenis) . '-TP-' . $tpId . '.docx';
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nama . '"')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setBody($data);
    }

    /**
     * POST unggah Word: validasi + staging privat, belum melakukan mutasi soal.
     */
    public function pratinjauWord(int $tpId, string $jenis): ResponseInterface|string
    {
        try {
            if (!in_array($jenis, ['diagnostik', 'sumatif'], true)) {
                throw new DomainException('Jenis ujian tidak dikenal.');
            }
            $file = $this->request->getFile('word_soal');
            if ($file === null || !$file->isValid()
                || strtolower($file->getClientExtension()) !== 'docx'
                || $file->getSize() > 15 * 1024 * 1024) {
                throw new DomainException('Pilih berkas DOCX maksimal 15 MB.');
            }
            $akun = (new AuthService())->penggunaSaatIni();
            $stage = (new ImporBankSoalService())->unggah(
                $file->getTempName(), $tpId, (int) $akun['id'], $jenis
            );
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal/review/' . $stage['id']));
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))->with('word_galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Unggah bank soal gagal: {reason}', ['reason' => $e->getMessage()]);
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('word_galat', 'Berkas belum dapat diproses. Periksa format atau kapasitas penyimpanan.');
        }
    }

    public function reviewWord(int $tpId, string $id): ResponseInterface|string
    {
        try {
            $akun = (new AuthService())->penggunaSaatIni();
            $rincian = (new DetailTpService())->lihat($tpId);
            $stage = (new ImporBankSoalService())->buka($tpId, (int) $akun['id'], $id, false);
            $hasil = RenderWordSoalService::hasil($stage['path'], $stage['hasil']);
            $telahDisimpan = $stage['row']['status'] === 'diterapkan';
            return view('guru/review_bank_soal', array_merge($rincian, [
                'judul' => 'Review Bank Soal Word',
                'namaPengguna' => $akun['nama'],
                'peranAktif' => 'Guru',
                'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
                'word_review' => $hasil,
                'word_import_id' => $id,
                'word_sudah_disimpan' => $telahDisimpan,
                'word_expired' => strtotime((string) $stage['row']['kedaluwarsa_pada']) < time(),
            ]));
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))->with('word_galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Review bank soal gagal: {reason}', ['reason' => $e->getMessage()]);
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('word_galat', 'Pratinjau gagal dibuka. Silakan unggah ulang.');
        }
    }

    public function konfirmasiWord(int $tpId, string $id): ResponseInterface
    {
        try {
            $akun = (new AuthService())->penggunaSaatIni();
            $hasil = (new ImporBankSoalService())->konfirmasi($tpId, (int) $akun['id'], $id);
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('word_sukses', $hasil['jumlah'] . ' soal berhasil diproses: '
                    . $hasil['baru'] . ' baru, ' . $hasil['diubah'] . ' diperbarui. '
                    . 'Soal lama yang tidak ada pada Word tetap disimpan.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal/review/' . $id))
                ->with('word_galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Konfirmasi bank soal gagal: {reason}', ['reason' => $e->getMessage()]);
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('word_galat', 'Penyimpanan bank soal gagal. Tidak ada perubahan parsial yang disimpan.');
        }
    }

    public function mediaWord(int $tpId, string $id, string $rid): ResponseInterface
    {
        try {
            $akun = (new AuthService())->penggunaSaatIni();
            $gambar = (new ImporBankSoalService())->gambar($tpId, (int) $akun['id'], $id, $rid);
            return $this->response->setHeader('Content-Type', $gambar['mime'])
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody($gambar['data']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(404)->setBody('Gambar tidak tersedia.');
        }
    }
}
