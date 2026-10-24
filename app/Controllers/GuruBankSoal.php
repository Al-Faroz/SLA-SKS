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

    public function pratinjauWord(int $tpId, string $jenis): ResponseInterface|string
    {
        try {
            $rincian = (new DetailTpService())->lihat($tpId);
            if ($rincian['tp']['status'] !== 'draf' || ! in_array($jenis, ['diagnostik', 'sumatif'], true)) {
                throw new DomainException('Impor hanya diperbolehkan pada TP draf dan jenis ujian yang sah.');
            }
            $berkas = $this->request->getFile('word_soal');
            if ($berkas === null || ! $berkas->isValid()
                || strtolower($berkas->getClientExtension()) !== 'docx'
                || $berkas->getSize() > 15 * 1024 * 1024) {
                throw new DomainException('Pilih berkas .docx maksimal 15 MB.');
            }
            $akun = (new AuthService())->penggunaSaatIni();
            $hasil = PratinjauWordSoalService::periksa(
                $berkas->getTempName(), $tpId, (int) $akun['id'],
                (int) $rincian['paket']['id'], (int) $rincian['tp']['struktur_versi'],
                $jenis, KunciTemplateWordService::ambil()
            );
            $pesan = 'Validasi ' . ucfirst($jenis) . ' berhasil: ' . $hasil['jumlah']
                . ' soal terstruktur. Pratinjau ini belum menyimpan soal ke database.';
            $ringkas = array_map(static function (array $row): string {
                return 'Soal ' . $row['nomor'] . ': gambar ' . $row['konten']['gambar']
                    . ', rumus ' . $row['konten']['rumus'] . ', tabel ' . $row['konten']['tabel']
                    . ', teks Arab ' . $row['konten']['arab'];
            }, $hasil['soal']);
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('word_sukses', $pesan)
                ->with('word_rincian', $ringkas)
                ->with('word_soal', $hasil['soal']);
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('word_galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Validasi Word gagal TP {id}: {message}', [
                'id' => $tpId, 'message' => $e->getMessage(),
            ]);
            return redirect()->to(site_url('guru/tp/' . $tpId . '/bank-soal'))
                ->with('word_galat', 'Dokumen belum dapat diperiksa. Periksa format DOCX.');
        }
    }

}
