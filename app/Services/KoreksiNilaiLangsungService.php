<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Koreksi skor Resume/HOTS langsung oleh Guru G1.
 * V4-0014 yang TELAH diimpor dipakai hanya sebagai histori versi/audit.
 * Tidak ada pengajuan, self-approval, atau langkah Admin.
 */
final class KoreksiNilaiLangsungService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db=null,?callable $akun=null)
    {
        $this->db=$db ?? db_connect();
        $this->akun=$akun ?? static fn():?array=>(new AuthService())->penggunaSaatIni();
    }

    private function guru(): int
    {
        $a=($this->akun)();
        if (!$a || ($a['peran_aktif']??null)!=='guru'
            || ($a['status']??null)!=='aktif'
            || (int)($a['wajib_ganti_sandi']??1)!==0
            || (int)($a['id']??0)<1) {
            throw new DomainException('Hanya Guru pengampu aktif dapat mengoreksi nilai.');
        }
        foreach (['permohonan_koreksi_nilai','riwayat_koreksi_nilai'] as $t) {
            if (!$this->db->tableExists($t)) {
                throw new RuntimeException('Patch SQL V4-0014 belum terpasang.');
            }
        }
        return (int)$a['id'];
    }

    private static function tabel(string $jenis): string
    {
        return match($jenis) {
            'resume'=>'resume_materi',
            'hots'=>'jawaban_hots_materi',
            default=>throw new DomainException('Hanya skor Resume/HOTS yang dapat dikoreksi di sini.'),
        };
    }

    /** Semua filter G1, siswa, materi dan pin versi diperiksa di server. */
    private function sumber(string $jenis,int $id,int $guruId): array
    {
        if ($id<1)throw new DomainException('ID sumber tidak sah.');
        $r=$this->db->table(self::tabel($jenis).' x')
            ->select('x.id,x.progres_tp_id,x.materi_id,x.versi_konten_tp_id,'
                .'x.status AS status_sumber,x.nilai AS nilai_sumber,x.dinilai_oleh,'
                .'pr.siswa_id,pr.tp_id,pr.status AS status_progres,'
                .'pr.versi_konten_tp_id AS versi_progres,'
                .'p.id AS paket_id,p.angkatan_id,p.mapel_id,'
                .'s.pengguna_id AS pengguna_siswa,'
                .'g.guru_id AS guru_g1,'
                .'n.id AS nilai_tp_id,n.jalur,n.nilai_resume,n.nilai_hots,'
                .'n.nilai_sumatif,n.nilai_tp,n.status_nilai')
            ->join('progres_tp pr','pr.id=x.progres_tp_id')
            ->join('materi m','m.id=x.materi_id AND m.tp_id=pr.tp_id')
            ->join('tp t','t.id=pr.tp_id')
            ->join('paket_semester p','p.id=t.paket_semester_id'
                .' AND p.id=pr.paket_semester_id')
            ->join('siswa s','s.id=pr.siswa_id AND s.angkatan_id=p.angkatan_id')
            ->join('penugasan_guru_gen g',
                'g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id')
            ->join('nilai_tp n','n.progres_tp_id=pr.id','left')
            ->where('x.id',$id)->where('t.jenis_tp','non_esensial')
            ->where('g.guru_id',$guruId)->get(1)->getRowArray();
        if (!$r || $r['status_sumber']!=='final'
            || $r['nilai_sumber']===null || $r['dinilai_oleh']===null
            || (int)$r['versi_konten_tp_id']!==(int)$r['versi_progres']) {
            throw new DomainException('Nilai bukan milik penugasan Guru atau belum dinilai Final.');
        }
        // Nilai sumber tidak selalu menunggu Sumatif atau tuntas: komponen
        // boleh dikoreksi kapan pun setelah Guru menyimpan nilai awal.
        return $r;
    }

    private static function dampak(array $r,string $jenis,int $nilaiBaru): array
    {
        PenilaianGuruService::skor($nilaiBaru);
        $awal=(int)$r['nilai_sumber'];
        $hasil=['nilai_awal'=>$awal,'nilai_baru'=>$nilaiBaru,
            'nilai_tp_lama'=>$r['nilai_tp']===null?null:(float)$r['nilai_tp'],
            'nilai_tp_baru'=>$r['nilai_tp']===null?null:(float)$r['nilai_tp'],
            'status_nilai'=>$r['status_nilai']??'sementara'];
        if ($r['nilai_tp_id']===null) return $hasil;
        if (($r['jalur']??'')!=='belajar_wajib' || $r['nilai_sumatif']===null) {
            throw new DomainException('Jalur nilai TP ini tidak mendukung koreksi Resume/HOTS.');
        }
        $resume=$r['nilai_resume']===null?null:(float)$r['nilai_resume'];
        $hots=$r['nilai_hots']===null?null:(float)$r['nilai_hots'];
        $sumber=$jenis==='resume'?$resume:$hots;
        if ($sumber===null || $sumber!==(float)$awal) {
            throw new DomainException('Komponen nilai TP tidak cocok dengan nilai sumber.');
        }
        $sumatif=(float)$r['nilai_sumatif'];
        $lama=NilaiTpService::hitung('belajar_wajib',$resume,$hots,$sumatif);
        if ($lama['status_nilai']!==$r['status_nilai']
            || ($lama['nilai_tp']===null) !== ($r['nilai_tp']===null)
            || ($lama['nilai_tp']!==null
                && abs((float)$r['nilai_tp']-$lama['nilai_tp'])>0.005)) {
            throw new DomainException('Nilai TP sudah berubah; muat ulang.');
        }
        $baru=NilaiTpService::hitung('belajar_wajib',
            $jenis==='resume'?(float)$nilaiBaru:$resume,
            $jenis==='hots'?(float)$nilaiBaru:$hots,$sumatif);
        $hasil['nilai_tp_baru']=$baru['nilai_tp'];
        return $hasil;
    }

    public function pratinjau(string $jenis,int $id,?int $nilaiBaru=null): array
    {
        $guruId=$this->guru();
        // PenilaianGuruService memverifikasi checksum snapshot, hak G1,
        // versi materi, status jawaban dan menampilkan penilai awal.
        $tugas=(new PenilaianGuruService($this->db,$this->akun))->lihat($jenis,$id);
        $r=$this->sumber($jenis,$id,$guruId);
        if ($tugas['nilai']===null) {
            throw new DomainException('Nilai awal belum disimpan Guru.');
        }
        return ['tugas'=>$tugas,
            'dampak'=>self::dampak($r,$jenis,$nilaiBaru??(int)$tugas['nilai'])];
    }

    /** @return array{versi:int,berubah:bool,nilai_tp_baru:?float} */
    public function proses(string $jenis,int $id,int $nilaiBaru,int $nilaiAwalForm): array
    {
        $guruId=$this->guru();
        PenilaianGuruService::skor($nilaiBaru);
        PenilaianGuruService::skor($nilaiAwalForm);
        $tugas=(new PenilaianGuruService($this->db,$this->akun))->lihat($jenis,$id);
        // Melindungi formulir/tab lama: skor yang dilihat Guru saat membuka
        // formulir wajib tetap sama ketika tombol Proses digunakan.
        if ($tugas['nilai']===null || (int)$tugas['nilai']!==$nilaiAwalForm) {
            throw new DomainException('Nilai sudah berubah di tempat lain. Buka ulang Koreksi Nilai.');
        }
        $this->db->transBegin();
        try {
            $lock=$this->db->DBDriver==='SQLite3'?'':' FOR UPDATE';
            $parent=$this->db->query('SELECT id,versi_konten_tp_id FROM progres_tp'
                .' WHERE id=?'.$lock,[(int)$tugas['progres_tp_id']])->getRowArray();
            if (!$parent || (int)$parent['versi_konten_tp_id']!==(int)$tugas['versi_konten_tp_id']) {
                throw new DomainException('Versi TP berubah; muat ulang.');
            }
            $r=$this->sumber($jenis,$id,$guruId);
            if ((int)$r['progres_tp_id']!==(int)$tugas['progres_tp_id']
                || (int)$r['versi_konten_tp_id']!==(int)$tugas['versi_konten_tp_id']) {
                throw new DomainException('Snapshot nilai sumber telah berubah.');
            }
            // Periksa penugasan G1 setelah lock; pergantian pengampu harus
            // terlihat sebelum mutasi dan tidak boleh melewati batas Gen.
            if ((int)$r['guru_g1']!==$guruId) {
                throw new DomainException('Guru pengampu sudah berubah.');
            }
            if ((int)$r['nilai_sumber']!==$nilaiAwalForm
                || (int)$r['nilai_sumber']!==(int)$tugas['nilai']) {
                throw new DomainException('Nilai sudah berubah di tempat lain. Buka ulang Koreksi Nilai.');
            }
            $d=self::dampak($r,$jenis,$nilaiBaru);
            if ($d['nilai_awal']===$d['nilai_baru']) {
                $this->db->transCommit();
                return ['versi'=>0,'berubah'=>false,'nilai_tp_baru'=>$d['nilai_tp_baru']];
            }
            $tabel=self::tabel($jenis);
            $this->db->table($tabel)->where('id',$id)
                ->where('status','final')->where('nilai',$d['nilai_awal'])
                ->update(['nilai'=>$nilaiBaru]);
            if ($this->db->affectedRows()!==1) {
                throw new DomainException('Skor berubah bersamaan; tidak ditimpa.');
            }
            if ($r['nilai_tp_id']!==null) {
                $kolom=$jenis==='resume'?'nilai_resume':'nilai_hots';
                $q=$this->db->table('nilai_tp')
                    ->where('id',(int)$r['nilai_tp_id'])
                    ->where($kolom,$d['nilai_awal']);
                if ($d['nilai_tp_lama']===null) $q->where('nilai_tp',null);
                else $q->where('nilai_tp',$d['nilai_tp_lama']);
                $q->update([
                    $kolom=>$nilaiBaru,
                    'nilai_tp'=>$d['nilai_tp_baru'],
                    'diubah_pada'=>gmdate('Y-m-d H:i:s'),
                ]);
                if ($this->db->affectedRows()!==1) {
                    throw new DomainException('Nilai TP berubah bersamaan; koreksi dibatalkan.');
                }
            }
            $old=$this->db->table('permohonan_koreksi_nilai')
                ->where('jenis',$jenis)->where('sumber_id',$id)
                ->orderBy('versi_koreksi','DESC')->get(1)->getRowArray();
            $versi=(int)($old['versi_koreksi']??0)+1;
            $now=gmdate('Y-m-d H:i:s');
            // Kolom legacy V4-0014 hanya menyimpan sejarah; status langsung
            // disetujui tanpa akun Admin atau antrean pengajuan.
            // Sertifikat disinkronkan otomatis ketika siswa mengakses.
            // Tidak ada permohonan revisi/flag tindak lanjut Admin.
            $this->db->table('permohonan_koreksi_nilai')->insert([
                'jenis'=>$jenis,'sumber_id'=>$id,
                'progres_tp_id'=>(int)$r['progres_tp_id'],
                'versi_konten_tp_id'=>(int)$r['versi_konten_tp_id'],
                'versi_koreksi'=>$versi,'nilai_awal'=>$d['nilai_awal'],
                'nilai_usulan'=>$nilaiBaru,
                'nilai_tp_awal'=>$d['nilai_tp_lama'],
                'nilai_tp_usulan'=>$d['nilai_tp_baru'],
                'status_nilai_awal'=>$d['status_nilai'],
                'perlu_tinjau_sertifikat'=>0,
                'alasan'=>'Koreksi langsung oleh Guru G1',
                'guru_id'=>$guruId,'status'=>'disetujui',
                'dibuat_pada'=>$now,'diputus_pada'=>$now,
            ]);
            $auditId=(int)$this->db->insertID();
            if ($auditId<1) throw new RuntimeException('Audit koreksi gagal disimpan.');
            $this->db->table('riwayat_koreksi_nilai')->insert([
                'permohonan_id'=>$auditId,'aksi'=>'disetujui',
                'pelaku_id'=>$guruId,'peran_aktif'=>'guru',
                'nilai_awal'=>$d['nilai_awal'],'nilai_usulan'=>$nilaiBaru,
                'nilai_tp_awal'=>$d['nilai_tp_lama'],
                'nilai_tp_usulan'=>$d['nilai_tp_baru'],
                'versi_koreksi'=>$versi,'waktu'=>$now,
            ]);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$guruId,'peran_aktif'=>'guru',
                'peristiwa'=>'koreksi_nilai_langsung',
                'catatan'=>'Sumber '.$jenis.' #'.$id.' versi '.$versi
                    .'; nilai '.$d['nilai_awal'].' menjadi '.$nilaiBaru,
                'waktu'=>$now,
            ]);
            NotifikasiService::catat($this->db,'koreksi_nilai_diterapkan',
                (int)$r['pengguna_siswa'],$auditId,$versi);
            if (!$this->db->transStatus()) {
                throw new RuntimeException('Transaksi koreksi nilai tidak berhasil.');
            }
            $this->db->transCommit();
            return ['versi'=>$versi,'berubah'=>true,'nilai_tp_baru'=>$d['nilai_tp_baru']];
        }catch(Throwable $e){
            $this->db->transRollback();
            throw $e;
        }
    }
}
