<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\KebijakanPrivasiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman publik fondasi K0.
 *
 * Belum ada endpoint POST login atau akses panel sebelum AuthService dan SQL K1 siap.
 */
final class Publik extends BaseController
{
    protected $helpers = ['url'];

    public function beranda(): RedirectResponse
    {
        return redirect()->to(site_url('masuk'));
    }

    public function masuk(): string
    {
        return view('publik/masuk', [
            'judul' => 'Akses SLA-SKS',
        ]);
    }

    public function kebijakanPrivasi(): ResponseInterface
    {
        // Tanpa login; hanya kebijakan yang telah terbit dan sudah berlaku WIB.
        // Tidak pernah mengisi halaman dengan janji/teks buatan pengembang.
        try {
            $kebijakan = (new KebijakanPrivasiService())->publikAktif();
            $kontak = ['nama_madrasah'=>'','telepon'=>'','email'=>''];
            if ($kebijakan !== null) {
                $rows = db_connect()->table('pengaturan')->select('kunci,nilai')
                    ->whereIn('kunci',array_keys($kontak))->get()->getResultArray();
                foreach ($rows as $r) $kontak[$r['kunci']] = (string)($r['nilai']??'');
            }
            return $this->response->setStatusCode($kebijakan===null?503:200)
                ->setHeader('Cache-Control','no-store')
                ->setHeader('X-Content-Type-Options','nosniff')
                ->setBody(view('publik/kebijakan_privasi', [
                    'judul'=>'Kebijakan Privasi',
                    'kebijakan'=>$kebijakan,
                    'kontak'=>$kontak,
                ]));
        } catch (\Throwable $e) {
            log_message('error','Kebijakan Privasi publik: {jenis}',['jenis'=>get_class($e)]);
            return $this->response->setStatusCode(503)
                ->setHeader('Cache-Control','no-store')
                ->setBody(view('publik/kebijakan_privasi', [
                    'judul'=>'Kebijakan Privasi', 'kebijakan'=>null,
                ]));
        }
    }

    public function tidakDitemukan(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(404)
            ->setBody(view('publik/tidak_ditemukan', [
                'judul' => 'Halaman tidak ditemukan',
            ]));
    }
}
