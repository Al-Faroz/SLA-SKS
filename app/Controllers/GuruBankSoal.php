<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DetailTpService;
use App\Services\NavigasiService;
use App\Services\RingkasanBankSoalService;
use App\Services\TemplateWordSoalService;
use App\Services\KunciTemplateWordService;
use App\Services\PratinjauUnggahWordService;
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

    public function unggah(int $tpId, string $jenis): ResponseInterface|string
    {
        try {
            $rincian = (new DetailTpService())->lihat($tpId);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak', ['judul' => 'Akses bank soal ditolak'])
            );
        }
        $pesan = null;
        $pratinjau = null;
        if ($rincian['tp']['status'] !== 'draf' || ! in_array($jenis, ['diagnostik', 'sumatif'], true)) {
            $pesan = 'Jenis ujian tidak valid atau TP sudah terkunci.';
        } else {
            $berkas = $this->request->getFile('word');
            if ($berkas === null || ! $berkas->isValid()
                || strtolower((string) $berkas->getClientExtension()) !== 'docx'
                || $berkas->getSize() > 15 * 1024 * 1024) {
                $pesan = 'Pilih file Word .docx yang valid (maksimal 15 MB).';
            } else {
                try {
                    $akun = (new AuthService())->penggunaSaatIni();
                    $pratinjau = PratinjauUnggahWordService::baca(
                        $berkas->getTempName(), $jenis,
                        ['tp_id'=>$tpId, 'paket_id'=>(int) $rincian['paket']['id'],
                            'struktur_versi'=>(int) $rincian['tp']['struktur_versi'],
                            'guru_id'=>(int) $akun['id']],
                        KunciTemplateWordService::ambil()
                    );
                } catch (\Throwable $e) {
                    $pesan = $e instanceof DomainException ? $e->getMessage()
                        : 'File Word belum dapat dibaca. Periksa log aplikasi.';
                    log_message('error', 'Pratinjau Word gagal untuk TP {id}: {error}',
                        ['id'=>$tpId, 'error'=>$e->getMessage()]);
                }
            }
        }
        $akun = (new AuthService())->penggunaSaatIni();
        return view('guru/unggah_word_soal', array_merge($rincian, [
            'judul' => 'Pratinjau Word Bank Soal',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'jenis' => $jenis, 'pesan' => $pesan, 'pratinjau' => $pratinjau,
        ]));
    }

}
