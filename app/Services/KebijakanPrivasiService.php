<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A13-7: Admin menyiapkan dan menerbitkan teks sekolah yang telah disahkan.
 * Tidak ada kebijakan default buatan pengembang; semua versi terbit immutable.
 */
final class KebijakanPrivasiService
{
    private BaseConnection $db;
    /** @var callable():?array */
    private $akun;

    public function __construct(?BaseConnection $db=null,?callable $akun=null)
    {
        $this->db=$db??db_connect();
        $this->akun=$akun??static fn(): ?array=>(new AuthService())->penggunaSaatIni();
    }

    private function admin(): int
    {
        $a=($this->akun)();
        if (!$a || ($a['peran_aktif']??'')!=='admin'
            || ($a['status']??'')!=='aktif'
            || (int)($a['wajib_ganti_sandi']??1)!==0
            || (int)($a['id']??0)<1) {
            throw new DomainException('Hanya Admin aktif yang dapat mengelola Kebijakan Privasi.');
        }
        $this->skema();
        return (int)$a['id'];
    }

    private function skema(): void
    {
        foreach (['kebijakan_privasi','riwayat_kebijakan_privasi'] as $tabel) {
            if (!$this->db->tableExists($tabel)) {
                throw new RuntimeException('Impor SQL V4-0013_NOTIFIKASI_PRIVASI.sql terlebih dahulu.');
            }
        }
    }

    public function daftar(): array
    {
        $this->admin();
        return $this->db->table('kebijakan_privasi')
            ->select('id,versi,tanggal_berlaku,status,revisi_draf,checksum_sha256,'
                .'dibuat_pada,diubah_pada,diterbitkan_pada')
            ->orderBy('id','DESC')->limit(200)->get()->getResultArray();
    }

    public function rincian(int $id): array
    {
        $this->admin();
        $r=$this->db->table('kebijakan_privasi')->where('id',$id)->get(1)->getRowArray();
        if (!$r) throw new DomainException('Versi Kebijakan Privasi tidak ditemukan.');
        return $r;
    }

    public function riwayat(int $id): array
    {
        $this->rincian($id);
        return $this->db->table('riwayat_kebijakan_privasi')
            ->where('kebijakan_privasi_id',$id)
            ->orderBy('id','DESC')->get()->getResultArray();
    }

    public function simpanDraf(array $input, ?int $id=null): int
    {
        $admin=$this->admin();
        $data=KebijakanPrivasiValidasiService::validasiNaskah($input);
        $versiDraf=(int)($input['revisi_draf']??0);
        if ($id!==null && ($id<1 || $versiDraf<1)) {
            throw new DomainException('Versi draf tidak valid. Muat ulang editor.');
        }
        $now=gmdate('Y-m-d H:i:s');
        $this->db->transBegin();
        try {
            $lama=null;
            if ($id!==null) {
                $lock=$this->db->DBDriver==='SQLite3'?'':' FOR UPDATE';
                $lama=$this->db->query(
                    'SELECT * FROM kebijakan_privasi WHERE id=?'.$lock,[$id]
                )->getRowArray();
                if (!$lama || $lama['status']!=='draf'
                    || (int)$lama['revisi_draf']!==$versiDraf) {
                    throw new DomainException('Draf berubah atau telah diterbitkan. Muat ulang halaman.');
                }
                if (hash_equals((string)$lama['checksum_sha256'],$data['checksum_sha256'])
                    && (string)$lama['versi']===$data['versi']
                    && (string)$lama['tanggal_berlaku']===$data['tanggal_berlaku']) {
                    $this->db->transCommit();
                    return $id;
                }
                $this->db->table('kebijakan_privasi')->where('id',$id)
                    ->where('status','draf')->where('revisi_draf',$versiDraf)
                    ->update($data+[]);
                // Kolom berubah hanya untuk Draf, ditambah metadata audit.
                $this->db->table('kebijakan_privasi')->where('id',$id)
                    ->update(['revisi_draf'=>$versiDraf+1,
                        'diubah_oleh'=>$admin,'diubah_pada'=>$now]);
                $revisiBaru=$versiDraf+1;
            } else {
                if ($this->db->table('kebijakan_privasi')->where('versi',$data['versi'])
                    ->countAllResults()>0) {
                    throw new DomainException('Label versi sudah digunakan. Gunakan versi berbeda.');
                }
                $this->db->table('kebijakan_privasi')->insert($data+[
                    'status'=>'draf','revisi_draf'=>1,'dibuat_oleh'=>$admin,
                    'diubah_oleh'=>$admin,'dibuat_pada'=>$now,'diubah_pada'=>$now,
                ]);
                $id=(int)$this->db->insertID();
                $revisiBaru=1;
            }
            if ($id<1) throw new RuntimeException('Draf belum tersimpan.');
            $this->db->table('riwayat_kebijakan_privasi')->insert([
                'kebijakan_privasi_id'=>$id,
                'aksi'=>$lama?'sunting_draf':'buat_draf',
                'admin_id'=>$admin,'revisi_lama'=>$lama['revisi_draf']??null,
                'revisi_baru'=>$revisiBaru,
                'checksum_lama'=>$lama['checksum_sha256']??null,
                'checksum_baru'=>$data['checksum_sha256'],'terjadi_pada'=>$now,
            ]);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$admin,'peran_aktif'=>'admin',
                'peristiwa'=>'kebijakan_privasi_draf',
                'catatan'=>'Draf Kebijakan Privasi #'.$id.' revisi '.$revisiBaru,
                'waktu'=>$now,
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('Draf Kebijakan Privasi gagal disimpan.');
            $this->db->transCommit();
            return $id;
        }catch(Throwable $e){
            $this->db->transRollback();
            throw $e;
        }
    }

    public function terbitkan(int $id, int $revisi, mixed $pengesahan): void
    {
        $admin=$this->admin();
        KebijakanPrivasiValidasiService::pastikanKonfirmasiPengesahan($pengesahan);
        if ($id<1 || $revisi<1) throw new DomainException('Versi publikasi tidak sah.');
        $this->db->transBegin();
        try {
            $lock=$this->db->DBDriver==='SQLite3'?'':' FOR UPDATE';
            $r=$this->db->query('SELECT * FROM kebijakan_privasi WHERE id=?'.$lock,[$id])
                ->getRowArray();
            if (!$r || $r['status']!=='draf' || (int)$r['revisi_draf']!==$revisi) {
                throw new DomainException('Draf berubah atau telah diterbitkan; muat ulang.');
            }
            KebijakanPrivasiValidasiService::validasiNaskah([
                'versi'=>$r['versi'],'tanggal_berlaku'=>$r['tanggal_berlaku'],
                'teks'=>$r['teks'],
            ]);
            if (!hash_equals((string)$r['checksum_sha256'],hash('sha256',(string)$r['teks']))) {
                throw new DomainException('Checksum naskah tidak cocok. Periksa draf terlebih dahulu.');
            }
            $now=gmdate('Y-m-d H:i:s');
            $this->db->table('kebijakan_privasi')->where('id',$id)
                ->where('status','draf')->where('revisi_draf',$revisi)
                ->update(['status'=>'terbit','diterbitkan_oleh'=>$admin,'diterbitkan_pada'=>$now]);
            if ($this->db->affectedRows()!==1) throw new DomainException('Naskah berubah saat penerbitan.');
            $this->db->table('riwayat_kebijakan_privasi')->insert([
                'kebijakan_privasi_id'=>$id,'aksi'=>'terbit',
                'admin_id'=>$admin,'revisi_lama'=>$revisi,
                'revisi_baru'=>$revisi,'checksum_lama'=>$r['checksum_sha256'],
                'checksum_baru'=>$r['checksum_sha256'],'terjadi_pada'=>$now,
            ]);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$admin,'peran_aktif'=>'admin',
                'peristiwa'=>'kebijakan_privasi_terbit',
                'catatan'=>'Kebijakan Privasi #'.$id.' versi '.$r['versi'],
                'waktu'=>$now,
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('Publikasi Kebijakan Privasi gagal.');
            $this->db->transCommit();
        }catch(Throwable $e){
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Tidak perlu login. Hanya dokumen terbit yang tanggal berlakunya
     * sudah sampai di WIB. Tidak boleh ada fallback ke Draf/versi lama.
     */
    public function publikAktif(): ?array
    {
        if (!$this->db->tableExists('kebijakan_privasi')) return null;
        $hari=(new DateTimeImmutable('now',new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
        $r=$this->db->table('kebijakan_privasi')
            ->select('versi,tanggal_berlaku,teks,checksum_sha256,diterbitkan_pada')
            ->where('status','terbit')->where('tanggal_berlaku <=',$hari)
            ->orderBy('tanggal_berlaku','DESC')->orderBy('diterbitkan_pada','DESC')
            ->orderBy('id','DESC')->get(1)->getRowArray();
        if ($r===null) return null;
        if (!hash_equals((string)$r['checksum_sha256'],hash('sha256',(string)$r['teks']))) {
            throw new RuntimeException('Integritas naskah privasi gagal diverifikasi.');
        }
        return $r;
    }
}
