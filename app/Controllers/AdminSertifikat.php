<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KelayakanSertifikatService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** A11-1: halaman kesiapan saja; penerbitan PDF/QR belum diaktifkan. */
final class AdminSertifikat extends BaseController
{
    protected $helpers = ['url'];

    private static function angka(mixed $nilai, int $maks): ?int
    {
        if ($nilai === null || $nilai === '') return null;
        if (!is_string($nilai) || !ctype_digit($nilai)
            || strlen($nilai) > 7 || (int)$nilai > $maks) {
            throw new DomainException('Filter sertifikat tidak sah.');
        }
        return (int)$nilai;
    }

    public function index(): ResponseInterface|string
    {
        try {
            $svc = new KelayakanSertifikatService();
            $daftar = $svc->daftarPaket();
            $paketId = self::angka($this->request->getGet('paket'),1000000);
            $halaman = self::angka($this->request->getGet('halaman'),100000) ?? 1;
            $terpilih = null;
            if ($daftar !== []) {
                $paketId = $paketId ?? (int)$daftar[0]['id'];
                foreach ($daftar as $item) {
                    if ((int)$item['id'] === $paketId) {
                        $terpilih = $item;
                        break;
                    }
                }
                if ($terpilih === null) {
                    throw new DomainException('Paket aktif tidak tersedia.');
                }
            } elseif ($paketId !== null) {
                throw new DomainException('Paket aktif tidak tersedia.');
            }
            $data = $terpilih === null ? null : $svc->rekap($paketId,$halaman);
            $akun = (new AuthService())->penggunaSaatIni();
            return $this->response->setHeader('Cache-Control','private, no-store, max-age=0')
                ->setBody(view('admin/sertifikat_kelayakan',[
                    'judul'=>'Pemeriksaan Sertifikat',
                    'namaPengguna'=>$akun['nama'] ?? '',
                    'peranAktif'=>'Admin',
                    'tautanNavigasi'=>NavigasiService::untuk('admin','sertifikat'),
                    'daftarPaket'=>$daftar,
                    'terpilih'=>$terpilih,
                    'rekap'=>$data,
                    'identitas'=>$svc->identitas(),
                ]));
        } catch (DomainException $e) {
            if ($this->request->getGet() !== []) {
                return redirect()->to(site_url('admin/sertifikat'))
                    ->with('galat_sertifikat','Filter tidak valid atau tidak diizinkan.');
            }
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak',[
                    'judul'=>'Pemeriksaan sertifikat tidak diizinkan',
                    'pesan'=>'Halaman ini hanya tersedia untuk Admin aktif.',
                ])
            );
        } catch (Throwable $e) {
            log_message('error','A11-1 kesiapan sertifikat: {error}',
                ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Pemeriksaan sertifikat belum dapat dimuat. Periksa log server.');
        }
    }
}
