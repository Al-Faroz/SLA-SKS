<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DetailTpService;
use App\Services\NavigasiService;
use App\Services\RingkasanBankSoalService;
use App\Services\TemplateWordSoalService;
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
            $key = (string) config('Encryption')->key;
            $data = TemplateWordSoalService::buat($identitas, $jenis, $jumlah, $key);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal unduh Word TP {tpId}: {reason}', [
                'tpId' => $tpId, 'reason' => $e->getMessage(),
            ]);
            return $this->response->setStatusCode(400)->setBody(
                view('publik/ditolak', ['judul' => 'Unduhan template belum dapat diproses'])
            );
        }

        $nama = 'SLA-SKS-' . strtoupper($jenis) . '-TP-' . $tpId . '.docx';
        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nama . '"')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setBody($data);
    }

}
