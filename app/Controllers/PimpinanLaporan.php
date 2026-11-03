<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\LaporanAkademikService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** Monitoring Pimpinan baca-saja, tidak memuat tombol POST atau mutasi nilai. */
final class PimpinanLaporan extends BaseController
{
    protected $helpers = ['url'];

    private function data(): array
    {
        $a=(new AuthService())->penggunaSaatIni();
        return [
            'judul'=>'Monitoring Akademik Pimpinan',
            'namaPengguna'=>$a['nama'] ?? '',
            'peranAktif'=>'Pimpinan',
            'tautanNavigasi'=>NavigasiService::untuk('pimpinan','laporan'),
        ];
    }

    private static function angka(mixed $v, int $maks): ?int
    {
        if ($v === null || $v === '') return null;
        if (!is_string($v) || !ctype_digit($v)
            || strlen($v)>7 || (int)$v>$maks) {
            throw new DomainException('Filter laporan tidak sah.');
        }
        return (int)$v;
    }

    public function index(): ResponseInterface|string
    {
        try {
            $svc=new LaporanAkademikService();
            $paket=$svc->paketPimpinan();
            $id=self::angka($this->request->getGet('paket'),1000000);
            $halaman=self::angka($this->request->getGet('halaman'),100000) ?? 1;
            $terpilih=null;
            if ($paket !== []) {
                $id=$id ?? (int)$paket[0]['id'];
                foreach ($paket as $p) {
                    if ((int)$p['id']===$id) {
                        $terpilih=$p;
                        break;
                    }
                }
                if ($terpilih===null) {
                    throw new DomainException('Paket laporan tidak tersedia.');
                }
            } elseif ($id !== null) {
                throw new DomainException('Paket laporan tidak tersedia.');
            }
            $monitor=$terpilih === null ? null : $svc->monitoring($id,$halaman);
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('pimpinan/laporan',$this->data()+[
                    'pilihan'=>$paket,'terpilih'=>$terpilih,'monitor'=>$monitor,
                ]));
        } catch (DomainException $e) {
            return redirect()->to(site_url('pimpinan/laporan'))
                ->with('galat_laporan','Filter tidak valid atau tidak diizinkan.');
        } catch (Throwable $e) {
            log_message('error','A10 monitoring Pimpinan: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Monitoring belum dapat dimuat. Periksa log server.');
        }
    }

    public function detail(int $paketId, int $siswaId): ResponseInterface|string
    {
        try {
            $svc=new LaporanAkademikService();
            // Role aktif Pimpinan wajib; Service tidak menerima akses dari role lain.
            $laporan=$svc->laporan($siswaId,$paketId);
            if (($this->data()['peranAktif'] ?? '') !== 'Pimpinan') {
                throw new DomainException('Pratinjau laporan tidak diizinkan.');
            }
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('laporan/detail',$this->data()+[
                    'laporan'=>$laporan,
                    'kembali'=>'pimpinan/laporan?paket='.$paketId,
                    'pratinjau'=>true,
                ]));
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)->setBody(
                view('publik/ditolak',['judul'=>'Laporan tidak tersedia'])
            );
        } catch (Throwable $e) {
            log_message('error','A10 pratinjau Pimpinan: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Laporan belum dapat dimuat. Coba lagi nanti.');
        }
    }
}
