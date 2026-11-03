<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A11-2: aset sertifikat immutable, privat, pointer aktif global per jenis.
 * Background template harus dipratinjau sebagai PDF sebelum diaktifkan.
 */
final class SertifikatAsetService
{
    public const MAKS_BYTE = 8388608;

    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db=null, ?callable $akun=null)
    {
        $this->db=$db ?? db_connect();
        $this->akun=$akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    private function admin(): int
    {
        $a=($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? null)!=='admin'
            || ($a['status'] ?? null)!=='aktif'
            || (int)($a['wajib_ganti_sandi'] ?? 1)!==0) {
            throw new DomainException('Aset sertifikat hanya dapat dikelola Admin aktif.');
        }
        return (int)$a['id'];
    }

    private static function jenis(string $jenis): string
    {
        if (!in_array($jenis,['background','ttd'],true)) {
            throw new DomainException('Jenis aset sertifikat tidak valid.');
        }
        return $jenis;
    }

    public static function direktori(string $bagian='aset'): string
    {
        if (!in_array($bagian,['aset','pdf'],true)) {
            throw new DomainException('Lokasi aset tidak sah.');
        }
        return rtrim(WRITEPATH,'/\\').DIRECTORY_SEPARATOR.'private'
            .DIRECTORY_SEPARATOR.'sertifikat'.DIRECTORY_SEPARATOR.$bagian;
    }

    private static function pastikanDirektori(string $path): void
    {
        if (!is_dir($path) && !@mkdir($path,0700,true) && !is_dir($path)) {
            throw new RuntimeException('Folder privat sertifikat tidak dapat dibuat.');
        }
    }

    /** @return array{mime:string,lebar:int,tinggi:int,ekstensi:string} */
    public static function periksaBerkas(UploadedFile $file,string $jenis): array
    {
        self::jenis($jenis);
        if (!$file->isValid() || $file->getSize() < 100
            || $file->getSize() > self::MAKS_BYTE) {
            throw new DomainException('Gambar tidak sah atau melebihi 8 MB.');
        }
        $ext=strtolower((string)$file->getClientExtension());
        $asal=$file->getTempName();
        $info=@getimagesize($asal);
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($asal);
        if (!is_array($info) || !in_array($mime,['image/png','image/jpeg'],true)
            || $mime !== ($info['mime'] ?? null)) {
            throw new DomainException('Isi berkas bukan gambar PNG/JPG valid.');
        }
        if ($jenis==='ttd' && ($mime!=='image/png' || $ext!=='png')) {
            throw new DomainException('Tanda tangan wajib berformat PNG.');
        }
        if ($jenis==='background' && !(
            ($mime==='image/png' && $ext==='png')
            || ($mime==='image/jpeg' && in_array($ext,['jpg','jpeg'],true))
        )) {
            throw new DomainException('Ekstensi dan isi gambar tidak cocok.');
        }
        $w=(int)$info[0];$h=(int)$info[1];
        if ($jenis==='background') {
            // A4 landscape 297:210, toleransi 1.5% agar hasil cetak tidak meregang.
            $ratio=297/210;
            if ($w < 1188 || $h < 840 || $w > 6000 || $h > 4300
                || abs($w/$h - $ratio)>$ratio*0.015) {
                throw new DomainException(
                    'Background wajib rasio A4 landscape 297:210 (±1,5%), minimal 1188×840 piksel.');
            }
        } elseif ($w < 150 || $h < 60 || $w > 2500 || $h > 1600) {
            throw new DomainException('Tanda tangan PNG berukuran 150–2500 × 60–1600 piksel.');
        }
        return ['mime'=>$mime,'lebar'=>$w,'tinggi'=>$h,
            'ekstensi'=>$mime==='image/png'?'png':'jpg'];
    }

    public function unggah(UploadedFile $file,string $jenis): int
    {
        $admin=$this->admin();
        $gambar=self::periksaBerkas($file,$jenis);
        $dir=self::direktori();
        self::pastikanDirektori($dir);
        $nama=bin2hex(random_bytes(24));
        $target=$dir.DIRECTORY_SEPARATOR.$nama.'.'.$gambar['ekstensi'];
        $raw=@file_get_contents($file->getTempName());
        $im=is_string($raw)?@imagecreatefromstring($raw):false;
        unset($raw);
        if ($im===false) {
            throw new DomainException('Gambar tidak dapat didekode.');
        }
        try {
            if ($gambar['mime']==='image/png') {
                // Preserve alpha pada gambar TTD bertanda tangan transparan.
                imagealphablending($im,false);
                imagesavealpha($im,true);
                $ok=imagepng($im,$target,6);
            } else {
                $ok=imagejpeg($im,$target,88);
            }
        } finally {
            imagedestroy($im);
        }
        if (!$ok || !is_file($target)) {
            @unlink($target);
            throw new RuntimeException('Gambar gagal disimpan secara privat.');
        }
        @chmod($target,0600);
        $sha=hash_file('sha256',$target);
        if ($sha===false) {
            @unlink($target);
            throw new RuntimeException('Checksum aset gagal diperiksa.');
        }
        $this->db->transBegin();
        try {
            $this->db->table('sertifikat_aset')->insert([
                'jenis'=>$jenis,'nama_acak'=>$nama,'mime'=>$gambar['mime'],
                'lebar'=>$gambar['lebar'],'tinggi'=>$gambar['tinggi'],
                'ukuran_byte'=>(int)filesize($target),'sha256'=>$sha,
                'dibuat_oleh'=>$admin,'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
            ]);
            $id=(int)$this->db->insertID();
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$admin,'peran_aktif'=>'admin',
                'peristiwa'=>'sertifikat_aset_diunggah',
                'catatan'=>'Aset sertifikat '.$jenis.' ID '.$id.' disimpan privat.',
                'waktu'=>gmdate('Y-m-d H:i:s'),
            ]);
            if ($this->db->transStatus()===false || $id<1) {
                throw new RuntimeException('Metadata aset sertifikat gagal disimpan.');
            }
            $this->db->transCommit();
            return $id;
        } catch (Throwable $e) {
            $this->db->transRollback();
            @unlink($target);
            throw $e;
        }
    }

    public function daftar(): array
    {
        $this->admin();
        $aktif=$this->aktif();
        $rows=$this->db->table('sertifikat_aset')
            ->select('id,jenis,mime,lebar,tinggi,ukuran_byte,dibuat_pada')
            ->orderBy('id','DESC')->get(100)->getResultArray();
        foreach ($rows as &$r) {
            $r['aktif']=(int)($aktif[$r['jenis']]['id']??0)===(int)$r['id'];
        }
        unset($r);
        return $rows;
    }

    /** @return array<string,array<string,mixed>> */
    public function aktif(): array
    {
        $this->admin();
        $rows=$this->db->table('sertifikat_aset_aktif aa')
            ->select('aa.jenis,a.id,a.nama_acak,a.mime,a.sha256,a.lebar,a.tinggi')
            ->join('sertifikat_aset a','a.id=aa.aset_id AND a.jenis=aa.jenis')
            ->get()->getResultArray();
        $hasil=[];
        foreach ($rows as $r) $hasil[$r['jenis']]=$r;
        return $hasil;
    }

    public function ambil(int $id,?string $jenis=null): array
    {
        $this->admin();
        if ($id<1) throw new DomainException('Aset tidak ditemukan.');
        $q=$this->db->table('sertifikat_aset')->where('id',$id);
        if ($jenis!==null) $q->where('jenis',self::jenis($jenis));
        $r=$q->get()->getRowArray();
        if (!$r) throw new DomainException('Aset tidak ditemukan.');
        return $r;
    }

    public static function path(array $aset): string
    {
        $nama=(string)($aset['nama_acak']??'');
        if (!preg_match('/^[a-f0-9]{48}$/D',$nama)) {
            throw new DomainException('Nama berkas aset tidak valid.');
        }
        $ext=($aset['mime']??'')==='image/png'?'png':'jpg';
        $path=self::direktori().DIRECTORY_SEPARATOR.$nama.'.'.$ext;
        if (!is_file($path) || !is_readable($path)
            || hash_file('sha256',$path)!==($aset['sha256']??null)) {
            throw new DomainException('Berkas sertifikat hilang atau checksum berubah.');
        }
        return $path;
    }

    public function aktifkan(int $id): void
    {
        $admin=$this->admin();
        $aset=$this->ambil($id);
        $jenis=(string)$aset['jenis'];
        self::path($aset);
        $this->db->transBegin();
        try {
            $lockSql='SELECT id FROM pengguna WHERE id=?';
            if ($this->db->DBDriver !== 'SQLite3') $lockSql.=' FOR UPDATE';
            $this->db->query($lockSql,[$admin]);
            $old=$this->db->table('sertifikat_aset_aktif')
                ->where('jenis',$jenis)->get()->getRowArray();
            if ($old!==null && (int)$old['aset_id']===$id) {
                throw new DomainException('Aset ini sudah aktif.');
            }
            $row=[
                'aset_id'=>$id,'diaktifkan_oleh'=>$admin,
                'diaktifkan_pada'=>gmdate('Y-m-d H:i:s'),
            ];
            if ($old===null) {
                $this->db->table('sertifikat_aset_aktif')->insert(['jenis'=>$jenis]+$row);
            } else {
                $this->db->table('sertifikat_aset_aktif')
                    ->where('jenis',$jenis)->update($row);
            }
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$admin,'peran_aktif'=>'admin',
                'peristiwa'=>'sertifikat_aset_aktif',
                'catatan'=>'Aset sertifikat '.$jenis.' aktif ID '.$id.'.',
                'waktu'=>gmdate('Y-m-d H:i:s'),
            ]);
            if ($this->db->transStatus()===false) {
                throw new RuntimeException('Aset sertifikat tidak dapat diaktifkan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
