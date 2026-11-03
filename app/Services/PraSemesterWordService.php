<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Impor DOCX Pra-Semester: template → staging privat → review → konfirmasi atomik.
 * Tidak menimpa soal manual maupun soal Word sebelumnya (selalu append).
 * Memakai generator, parser, renderer dan HMAC Word TP yang sudah teruji.
 */
final class PraSemesterWordService
{
    private BaseConnection $db;
    private $akun;
    private PraSemesterGuruService $guruService;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
        $this->guruService = new PraSemesterGuruService($this->db, $this->akun);
    }

    private function guruId(): int
    {
        $a = ($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? '') !== 'guru'
            || ($a['status'] ?? '') !== 'aktif'
            || (int)($a['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Impor Word hanya untuk Guru aktif.');
        }
        return (int)$a['id'];
    }

    private static function folder(): string
    {
        $dir = WRITEPATH . 'private/pra_semester_word/';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Folder Word privat tidak tersedia.');
        }
        $block = $dir . '.htaccess';
        if (!is_file($block)
            && @file_put_contents($block, "Require all denied\nDeny from all\n", LOCK_EX) === false) {
            throw new RuntimeException('Proteksi folder Word tidak dapat dibuat.');
        }
        return $dir;
    }

    private static function lokasi(string $id): string
    {
        if (!preg_match('/^[0-9a-f]{32}$/D', $id)) {
            throw new DomainException('ID review Word tidak sah.');
        }
        return self::folder() . $id . '.docx';
    }

    private function bab(int $paketId): array
    {
        $items = $this->db->table('bab')->select('id,nomor_bab,nama_bab')
            ->where('paket_semester_id', $paketId)
            ->orderBy('nomor_bab')->get()->getResultArray();
        if (!$items) throw new DomainException('Paket belum mempunyai BAB.');
        $map = [];
        foreach ($items as $r) {
            $n = (int)$r['nomor_bab'];
            if ($n < 1 || isset($map[$n])) throw new DomainException('Nomor BAB paket tidak sah.');
            $map[$n] = (int)$r['id'];
        }
        return $map;
    }

    private function identitas(int $paketId): array
    {
        $paket = $this->guruService->paket($paketId);
        $kurikulum = $this->db->table('paket_semester p')
            ->select('k.nama AS nama_kurikulum')
            ->join('ref_kurikulum k','k.id=p.kurikulum_id')
            ->where('p.id', $paketId)->get()->getRowArray();
        $akun = ($this->akun)();
        return [
            'tp_id'=>0,
            'paket_id'=>$paketId,
            'guru_id'=>(int)$akun['id'],
            'struktur_versi'=>1,
            'gen'=>(string)$paket['nama_gen'],
            'guru'=>(string)($akun['nama'] ?? 'Guru'),
            'mapel'=>(string)$paket['nama_mapel'],
            'kurikulum'=>(string)($kurikulum['nama_kurikulum'] ?? ''),
            'semester'=>(string)$paket['semester'],
            'bab_nomor'=>array_keys($this->bab($paketId)),
        ];
    }

    public function unduh(int $paketId, int $jumlah): string
    {
        $this->guruId();
        if ($this->guruService->konteks($paketId)['terpakai']) {
            throw new DomainException('Bank telah digunakan siswa; penyusunan Word dikunci.');
        }
        // 30 minimal untuk Pra-Semester; file tidak memodifikasi bank.
        return TemplateWordSoalService::buat(
            $this->identitas($paketId), 'pra_semester', $jumlah,
            KunciTemplateWordService::ambil()
        );
    }

    private function sidikJari(int $paketId): string
    {
        $ujian = $this->db->table('ujian_pra_semester')
            ->select('id')->where('paket_semester_id',$paketId)->get()->getRowArray();
        $rows = $ujian ? $this->db->table('soal_pra_semester')
            ->where('ujian_pra_semester_id',(int)$ujian['id'])
            ->orderBy('id')->get()->getResultArray() : [];
        $bab = $this->bab($paketId);
        return hash('sha256',json_encode([$bab,$rows],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function periksa(string $file, int $paketId, int $guruId): array
    {
        return PratinjauWordSoalService::periksa(
            $file, 0, $guruId, $paketId, 1, 'pra_semester',
            KunciTemplateWordService::ambil(), null, $this->bab($paketId)
        );
    }

    public function terpasang(): bool
    {
        return $this->db->tableExists('impor_soal_pra_docx');
    }

    public function unggah(string $file, int $paketId): array
    {
        $guruId = $this->guruId();
        $this->guruService->paket($paketId);
        if (!$this->terpasang()) {
            throw new DomainException('Pembaruan SQL V4-0010 belum diimpor.');
        }
        $konteks = $this->guruService->konteks($paketId);
        if ($konteks['terpakai']) {
            throw new DomainException('Bank sudah digunakan siswa dan tidak boleh diimpor.');
        }
        $hasil = $this->periksa($file, $paketId, $guruId);
        $id = bin2hex(random_bytes(16));
        $path = self::lokasi($id);
        if (!copy($file, $path)) throw new RuntimeException('Berkas Word gagal disimpan privat.');
        @chmod($path, 0600);
        try {
            $checksum = hash_file('sha256', $path);
            if (!$checksum) throw new RuntimeException('Checksum Word gagal dihitung.');
            $this->db->table('impor_soal_pra_docx')->insert([
                'id'=>$id,'paket_semester_id'=>$paketId,'guru_id'=>$guruId,
                'checksum'=>$checksum,'status'=>'pratinjau',
                'data_json'=>json_encode([
                    'jumlah'=>$hasil['jumlah'],
                    'sidik_jari_bank'=>$this->sidikJari($paketId),
                ], JSON_THROW_ON_ERROR),
                'dibuat_pada'=>date('Y-m-d H:i:s'),
                'kedaluwarsa_pada'=>date('Y-m-d H:i:s',time()+7200),
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('Review Word gagal dicatat.');
            return ['id'=>$id,'jumlah'=>$hasil['jumlah']];
        } catch (Throwable $e) {
            @unlink($path);
            throw $e;
        }
    }

    public function buka(int $paketId, string $id, bool $harusAktif=false): array
    {
        $guruId = $this->guruId();
        $this->guruService->paket($paketId);
        if (!$this->terpasang()) throw new DomainException('SQL V4-0010 belum diimpor.');
        $row = $this->db->table('impor_soal_pra_docx')
            ->where('id',$id)->where('paket_semester_id',$paketId)
            ->where('guru_id',$guruId)->get()->getRowArray();
        if (!$row) throw new DomainException('Review Word tidak tersedia untuk Guru ini.');
        if ($harusAktif && ($row['status'] !== 'pratinjau'
            || strtotime((string)$row['kedaluwarsa_pada']) < time())) {
            throw new DomainException('Pratinjau sudah digunakan atau kedaluwarsa.');
        }
        $path = self::lokasi($id);
        if (!is_file($path) || !hash_equals((string)$row['checksum'],
            (string)hash_file('sha256',$path))) {
            throw new DomainException('Sumber Word sudah tidak tersedia atau berubah.');
        }
        $parsed=$this->periksa($path,$paketId,$guruId);
        return ['row'=>$row,'path'=>$path,
            'hasil'=>RenderWordSoalService::hasil($path,$parsed)];
    }

    public function riwayat(int $paketId): array
    {
        $guru=$this->guruId();
        $this->guruService->paket($paketId);
        if (!$this->terpasang()) return [];
        return $this->db->table('impor_soal_pra_docx')
            ->select('id,status,dibuat_pada,diterapkan_pada')
            ->where('paket_semester_id',$paketId)
            ->where('guru_id',$guru)
            ->orderBy('dibuat_pada','DESC')->get(10)->getResultArray();
    }

    /** @return array{jumlah:int,baru:int,status:string} */
    public function konfirmasi(int $paketId,string $id): array
    {
        $guru=$this->guruId();
        $review=$this->buka($paketId,$id);
        if ($review['row']['status'] === 'diterapkan') {
            return ['jumlah'=>(int)$review['hasil']['jumlah'],'baru'=>0,'status'=>'sudah_diterapkan'];
        }
        if ($review['row']['status'] !== 'pratinjau'
            || strtotime((string)$review['row']['kedaluwarsa_pada']) < time()) {
            throw new DomainException('Review telah kedaluwarsa; unggah ulang Word.');
        }
        $rich=RenderWordSoalService::hasil($review['path'],$review['hasil'],
            static fn(string $rid): string => 'word-image:' . $rid);
        $this->db->transBegin();
        try {
            $this->guruService->paket($paketId);
            $lock=$this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $this->db->query('SELECT id FROM paket_semester WHERE id=?'.$lock,[$paketId])->getRowArray();
            $stage=$this->db->query('SELECT * FROM impor_soal_pra_docx WHERE id=? '
                . 'AND paket_semester_id=? AND guru_id=?'.$lock,
                [$id,$paketId,$guru])->getRowArray();
            if (!$stage || $stage['status'] !== 'pratinjau'
                || strtotime((string)$stage['kedaluwarsa_pada']) < time()
                || !hash_equals((string)$stage['checksum'],
                    (string)hash_file('sha256',$review['path']))) {
                throw new DomainException('Pratinjau sudah berubah. Unggah dan tinjau ulang Word.');
            }
            $ujian=$this->db->query('SELECT * FROM ujian_pra_semester '
                . 'WHERE paket_semester_id=?'.$lock,[$paketId])->getRowArray();
            if ($ujian && $this->db->table('percobaan_ujian')
                ->where('ujian_pra_semester_id',(int)$ujian['id'])->countAllResults()>0) {
                throw new DomainException('Bank sudah digunakan siswa; impor dilarang.');
            }
            $meta=json_decode((string)$stage['data_json'],true,8,JSON_THROW_ON_ERROR);
            if (!is_array($meta) || !is_string($meta['sidik_jari_bank']??null)
                || !hash_equals($meta['sidik_jari_bank'],$this->sidikJari($paketId))) {
                throw new DomainException('BAB atau bank soal berubah sejak review. Unggah ulang Word.');
            }
            // File identik yang sudah pernah diterapkan tidak menambah soal ganda.
            $identik=$this->db->table('impor_soal_pra_docx')
                ->select('id')->where('paket_semester_id',$paketId)
                ->where('checksum',(string)$stage['checksum'])
                ->where('status','diterapkan')->get(1)->getRowArray();
            if ($identik) {
                $this->db->table('impor_soal_pra_docx')->where('id',$id)
                    ->update(['status'=>'diterapkan','diterapkan_pada'=>date('Y-m-d H:i:s')]);
                if (!$this->db->transStatus()) throw new RuntimeException('Konfirmasi identik gagal.');
                $this->db->transCommit();
                return ['jumlah'=>(int)$rich['jumlah'],'baru'=>0,'status'=>'identik'];
            }
            if (!$ujian) {
                $this->db->table('ujian_pra_semester')->insert([
                    'paket_semester_id'=>$paketId,'status'=>'draf',
                    'jumlah_soal'=>30,'durasi_menit'=>60,'kkm_khusus'=>null,
                    'dibuat_oleh'=>$guru,'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
                ]);
                $ujian=['id'=>(int)$this->db->insertID()];
            }
            $ujianId=(int)$ujian['id'];
            $last=$this->db->table('soal_pra_semester')->selectMax('urutan')
                ->where('ujian_pra_semester_id',$ujianId)->get()->getRowArray();
            $urut=(int)($last['urutan']??0);
            if ($urut + count($rich['soal']) > 65535) {
                throw new DomainException('Urutan soal melebihi kapasitas database.');
            }
            foreach ($rich['soal'] as $soal) {
                $urut++;
                $opsi=$soal['opsi'];
                $konten=[
                    'format'=>'SLA-SKS-RICH-V1',
                    'word_source_id'=>$id,
                    'origin'=>'word',
                    'manual_edited'=>false,
                    'word_slot'=>(int)$soal['nomor'],
                    'html_pertanyaan'=>$soal['html_pertanyaan'],
                    'html_opsi'=>$soal['html_opsi'],
                    'konten'=>$soal['konten'],
                ];
                $plain=static fn(string $raw): string => $raw!=='' ? $raw : '[Konten kaya Word]';
                $this->db->table('soal_pra_semester')->insert([
                    'ujian_pra_semester_id'=>$ujianId,
                    'bab_id'=>(int)$soal['bab_id'],
                    'urutan'=>$urut,'pertanyaan'=>$plain((string)$soal['pertanyaan']),
                    'opsi_a'=>$plain((string)$opsi['A']),
                    'opsi_b'=>$plain((string)$opsi['B']),
                    'opsi_c'=>$plain((string)$opsi['C']),
                    'opsi_d'=>$plain((string)$opsi['D']),
                    'opsi_e'=>$plain((string)$opsi['E']),
                    'kunci'=>$soal['kunci'],'aktif'=>1,
                    'konten_terstruktur_json'=>json_encode($konten,
                        JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
                ]);
            }
            $this->db->table('impor_soal_pra_docx')->where('id',$id)->where('status','pratinjau')
                ->update(['status'=>'diterapkan','diterapkan_pada'=>date('Y-m-d H:i:s')]);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$guru,'peran_aktif'=>'guru',
                'peristiwa'=>'impor_word_pra_semester',
                'catatan'=>'Paket #' . $paketId . ', Word #' . $id
                    . ', soal baru: '.count($rich['soal']).', BAB dinilai pada setiap soal.',
                'waktu'=>gmdate('Y-m-d H:i:s'),
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('Impor Word gagal disimpan.');
            $this->db->transCommit();
            return ['jumlah'=>(int)$rich['jumlah'],
                'baru'=>count($rich['soal']),'status'=>'diterapkan'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function gambar(int $paketId,string $id,string $rid): array
    {
        $this->guruId();
        $this->guruService->paket($paketId);
        $row=$this->db->table('impor_soal_pra_docx')
            ->where('id',$id)->where('paket_semester_id',$paketId)
            ->where('status','diterapkan')->get()->getRowArray();
        if (!$row) throw new DomainException('Aset Word belum disimpan.');
        return RenderWordSoalService::gambar(self::lokasi($id),$rid);
    }
}
