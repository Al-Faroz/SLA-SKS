<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KoreksiNilaiLangsungService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** Koreksi langsung Guru G1; tidak ada pengajuan atau keputusan Admin. */
final class GuruKoreksiNilai extends BaseController
{
    protected $helpers=['url','form'];

    public function form(string $jenis,int $id): ResponseInterface|string
    {
        try {
            $svc=new KoreksiNilaiLangsungService();
            // Nilai awal diambil sekali dari sumber yang diverifikasi Service.
            $p=$svc->pratinjau($jenis,$id);
            $akun=(new AuthService())->penggunaSaatIni();
            return view('koreksi_nilai/form_guru',[
                'judul'=>'Koreksi Nilai',
                'namaPengguna'=>$akun['nama'],
                'peranAktif'=>'Guru',
                'tautanNavigasi'=>NavigasiService::untuk('guru','penilaian'),
                'tugas'=>$p['tugas'],
                'dampak'=>$p['dampak'],
            ]);
        }catch(DomainException $e){
            return $this->response->setStatusCode(403)
                ->setBody(view('publik/ditolak',[
                    'judul'=>'Koreksi Nilai tidak tersedia',
                    'pesan'=>$e->getMessage(),
                    'tautanPath'=>'guru/penilaian',
                    'labelTautan'=>'Kembali ke Penilaian',
                ]));
        }catch(Throwable $e){
            log_message('error','Tampilan koreksi langsung: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(503)
                ->setBody('Koreksi nilai belum bisa dibuka. Periksa struktur SQL V4-0014 dan log aplikasi.');
        }
    }

    public function proses(string $jenis,int $id): RedirectResponse
    {
        $url=site_url('guru/penilaian/'.$jenis.'/'.$id);
        $n=$this->request->getPost('nilai');
        $nilaiAwal=$this->request->getPost('nilai_awal');
        if(!is_string($n)||preg_match('/^(?:100|[1-9]?[0-9])$/D',$n)!==1
            ||!is_string($nilaiAwal)
            ||preg_match('/^(?:100|[1-9]?[0-9])$/D',$nilaiAwal)!==1){
            return redirect()->to($url)->with('nilai_galat','Nilai koreksi atau skor awal tidak sah. Buka ulang formulir.');
        }
        try{
            $hasil=(new KoreksiNilaiLangsungService())->proses($jenis,$id,(int)$n,(int)$nilaiAwal);
            return redirect()->to($url)->with('nilai_sukses',
                $hasil['berubah']
                    ?'Koreksi nilai berhasil diproses. Riwayat tersimpan dan nilai TP telah diperbarui jika tersedia.'
                    :'Nilai tidak berubah karena sama dengan skor sebelumnya.');
        }catch(DomainException $e){
            return redirect()->to($url)->with('nilai_galat',$e->getMessage());
        }catch(Throwable $e){
            log_message('error','Proses koreksi langsung: {error}',['error'=>$e->getMessage()]);
            return redirect()->to($url)->with('nilai_galat',
                'Koreksi belum dapat diproses. Periksa koneksi dan log server.');
        }
    }
}
