<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KoreksiNilaiService;
use App\Services\NavigasiService;
use App\Services\PenilaianGuruService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class GuruKoreksiNilai extends BaseController
{
    protected $helpers=['url','form'];

    private function konteks(string $judul): array
    {
        $akun=(new AuthService())->penggunaSaatIni();
        return ['judul'=>$judul,'namaPengguna'=>$akun['nama'],
            'peranAktif'=>'Guru',
            'tautanNavigasi'=>NavigasiService::untuk('guru','koreksi_nilai')];
    }

    private static function halaman(mixed $raw): int
    {
        return is_string($raw)&&preg_match('/^[1-9][0-9]{0,5}$/D',$raw)
            ?(int)$raw:1;
    }

    public function index(): ResponseInterface|string
    {
        try {
            $svc=new KoreksiNilaiService();
            return view('koreksi_nilai/daftar',$this->konteks('Pengajuan Koreksi Nilai')+[
                'data'=>$svc->daftar('guru',self::halaman($this->request->getGet('halaman'))),
                'role'=>'guru',
            ]);
        }catch(Throwable $e){
            log_message('error','Daftar koreksi Guru: {jenis}',['jenis'=>get_class($e)]);
            return $this->response->setStatusCode(503)->setBody(
                'Koreksi nilai belum tersedia. Periksa SQL V4-0013 dan V4-0014.');
        }
    }

    public function form(string $jenis,int $id): ResponseInterface|string
    {
        try {
            $tugas=(new PenilaianGuruService())->lihat($jenis,$id);
            if ($tugas['nilai']===null) {
                throw new DomainException('Nilai awal belum tersimpan. Gunakan Penilaian Guru.');
            }
            $usulanRaw=$this->request->getGet('nilai_usulan');
            $usulan=$usulanRaw===null?(int)$tugas['nilai']:(
                is_string($usulanRaw)
                && preg_match('/^(?:100|[1-9]?[0-9])$/D',$usulanRaw)
                ?(int)$usulanRaw:-1
            );
            if ($usulan<0 || $usulan>100) {
                throw new DomainException('Nilai usulan harus 0–100.');
            }
            $p=(new KoreksiNilaiService())->pratinjauGuru($jenis,$id,$usulan);
            return view('koreksi_nilai/form_guru',$this->konteks('Ajukan Koreksi Nilai')+[
                'tugas'=>$tugas,'konteks'=>$p['tugas'],'dampak'=>$p['dampak'],
                'usulan'=>$usulan,
            ]);
        }catch(DomainException $e){
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak',[
                    'judul'=>'Koreksi nilai ditolak',
                    'pesan'=>$e->getMessage(),
                    'tautanPath'=>'guru/penilaian',
                    'labelTautan'=>'Kembali ke antrean penilaian',
                ]));
        }catch(Throwable $e){
            log_message('error','Pratinjau koreksi nilai: {jenis}',['jenis'=>get_class($e)]);
            return $this->response->setStatusCode(503)->setBody(
                'Pratinjau belum dapat dibuka. Periksa SQL V4-0013/V4-0014.');
        }
    }

    public function ajukan(string $jenis,int $id): RedirectResponse
    {
        $asal=site_url('guru/koreksi-nilai/'.$jenis.'/'.$id);
        $score=$this->request->getPost('nilai_usulan');
        $alasan=$this->request->getPost('alasan');
        if (!is_string($score)||!preg_match('/^(?:100|[1-9]?[0-9])$/D',$score)
            ||!is_string($alasan)) {
            return redirect()->to($asal)->withInput()->with('galat',
                'Nilai harus bilangan bulat 0–100 dan alasan harus diisi.');
        }
        try{
            $pid=(new KoreksiNilaiService())->ajukan($jenis,$id,(int)$score,$alasan);
            return redirect()->to(site_url('guru/koreksi-nilai/permohonan/'.$pid))
                ->with('sukses','Pengajuan dikirim ke Admin. Nilai belum berubah.');
        }catch(DomainException $e){
            return redirect()->to($asal.'?nilai_usulan='.$score)->withInput()
                ->with('galat',$e->getMessage());
        }catch(Throwable $e){
            log_message('error','Ajukan koreksi Guru: {jenis}',['jenis'=>get_class($e)]);
            return redirect()->to($asal)->withInput()->with('galat',
                'Pengajuan gagal disimpan. Periksa koneksi atau patch SQL.');
        }
    }

    public function detail(int $id): ResponseInterface|string
    {
        try{
            $svc=new KoreksiNilaiService();
            return view('koreksi_nilai/detail',$this->konteks('Detail Koreksi Nilai')+[
                'baris'=>$svc->permohonan($id),
                'riwayat'=>$svc->riwayatPermohonan($id),
                'role'=>'guru',
            ]);
        }catch(DomainException $e){
            return $this->response->setStatusCode(403)->setBody('Permohonan tidak tersedia bagi akun Guru ini.');
        }catch(Throwable $e){
            log_message('error','Detail koreksi Guru: {jenis}',['jenis'=>get_class($e)]);
            return $this->response->setStatusCode(503)->setBody('Detail koreksi belum dapat dibuka.');
        }
    }
}
