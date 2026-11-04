<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KoreksiNilaiService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class AdminKoreksiNilai extends BaseController
{
    protected $helpers=['url','form'];

    private function konteks(string $judul): array
    {
        $akun=(new AuthService())->penggunaSaatIni();
        return ['judul'=>$judul,'namaPengguna'=>$akun['nama'],
            'peranAktif'=>'Admin',
            'tautanNavigasi'=>NavigasiService::untuk('admin','koreksi_nilai')];
    }

    public function index(): ResponseInterface|string
    {
        try{
            $raw=$this->request->getGet('halaman');
            $page=is_string($raw)&&preg_match('/^[1-9][0-9]{0,5}$/D',$raw)?(int)$raw:1;
            return view('koreksi_nilai/daftar',
                $this->konteks('Persetujuan Koreksi Nilai')+[
                    'data'=>(new KoreksiNilaiService())->daftar('admin',$page),
                    'role'=>'admin',
                ]);
        }catch(Throwable $e){
            log_message('error','Daftar koreksi Admin: {jenis}',['jenis'=>get_class($e)]);
            return $this->response->setStatusCode(503)->setBody(
                'Persetujuan koreksi belum tersedia. Periksa SQL V4-0013/V4-0014.');
        }
    }

    public function detail(int $id): ResponseInterface|string
    {
        try{
            $svc=new KoreksiNilaiService();
            return view('koreksi_nilai/detail',
                $this->konteks('Tinjau Koreksi Nilai')+[
                    'baris'=>$svc->permohonan($id),
                    'riwayat'=>$svc->riwayatPermohonan($id),
                    'role'=>'admin',
                ]);
        }catch(DomainException $e){
            return $this->response->setStatusCode(404)->setBody('Permohonan koreksi tidak ditemukan.');
        }catch(Throwable $e){
            log_message('error','Detail koreksi Admin: {jenis}',['jenis'=>get_class($e)]);
            return $this->response->setStatusCode(503)->setBody('Detail koreksi belum dapat dibuka.');
        }
    }

    public function putuskan(int $id): RedirectResponse
    {
        $to=site_url('admin/koreksi-nilai/'.$id);
        $keputusan=$this->request->getPost('keputusan');
        $catatan=$this->request->getPost('catatan_keputusan');
        if (!is_string($keputusan) || !in_array($keputusan,['disetujui','ditolak'],true)
            || !is_string($catatan)) {
            return redirect()->to($to)->withInput()->with('galat',
                'Pilih Setujui atau Tolak dan isi catatan keputusan.');
        }
        try{
            $hasil=(new KoreksiNilaiService())->putuskan($id,$keputusan,$catatan);
            $pesan=$hasil['status']==='disetujui'
                ?'Koreksi nilai telah diterapkan dan dicatat dalam audit.'
                :'Pengajuan ditolak. Nilai siswa tidak berubah.';
            if($hasil['perlu_tinjau_sertifikat']){
                $pesan.=' Penting: periksa dan revisi sertifikat aktif melalui menu Sertifikat.';
            }
            return redirect()->to($to)->with('sukses',$pesan);
        }catch(DomainException $e){
            return redirect()->to($to)->withInput()->with('galat',$e->getMessage());
        }catch(Throwable $e){
            log_message('error','Keputusan koreksi Admin: {jenis}',['jenis'=>get_class($e)]);
            return redirect()->to($to)->withInput()->with('galat',
                'Keputusan gagal disimpan; nilai tetap tidak diubah.');
        }
    }
}
