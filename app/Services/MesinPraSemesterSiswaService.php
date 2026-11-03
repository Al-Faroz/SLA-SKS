<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A8-3: percobaan UTAMA Pra-Semester, timer individual sejak klik Mulai.
 * Tidak membuka remedial/susulan di luar penjadwalan Admin.
 */
final class MesinPraSemesterSiswaService
{
    private BaseConnection $db;
    private $akun;
    private PraSemesterSiswaService $siswa;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
        $this->siswa = new PraSemesterSiswaService($this->db,$this->akun);
    }

    private static function iso(?int $value): ?string
    {
        return $value === null ? null : gmdate('Y-m-d H:i:s',$value);
    }

    private static function epoch(?string $value): ?int
    {
        if ($value === null || $value === '') return null;
        $n = strtotime($value . ' UTC');
        if ($n === false) throw new RuntimeException('Waktu percobaan rusak.');
        return $n;
    }

    /** @return array<string,mixed> */
    private static function state(array $row): array
    {
        foreach (['waktu_mulai','waktu_aktif_terakhir','last_seen_server_at',
            'jeda_mulai_pada','waktu_selesai'] as $field) {
            $row[$field] = self::epoch($row[$field] ?? null);
        }
        foreach (['sisa_detik','jeda_fullscreen_total_detik','jeda_koneksi_total_detik',
            'jumlah_pelanggaran_fullscreen'] as $field) {
            $row[$field] = (int)$row[$field];
        }
        return $row;
    }

    public function ruang(int $paketId): array
    {
        $ctx = $this->siswa->konteks($paketId);
        return [
            'paket'=>$ctx['paket'],
            'ujian'=>$ctx['ujian'],
            'gerbang'=>$ctx['gerbang'],
            'percobaan'=>$ctx['percobaan'],
            'boleh_mulai'=>$ctx['gerbang']['boleh_mulai'],
            'kkm'=>$ctx['kkm']['efektif'],
        ];
    }

    /** Mulai hanya saat jendela Admin terbuka; durasi selalu dihitung sejak klik siswa. */
    public function mulai(int $paketId, bool $fullscreen, bool $desktop): array
    {
        if (!$desktop) throw new DomainException('Pra-Semester hanya melalui desktop/laptop.');
        if (!$fullscreen) throw new DomainException('Aktifkan fullscreen sebelum memulai ujian.');
        $this->db->transBegin();
        try {
            // Semua pemeriksaan diulang saat transaksi; lock baris jadwal
            // menyerialkan dua klik Mulai dari siswa sama pada paket ini.
            $ctx = $this->siswa->konteks($paketId);
            $exam = $ctx['ujian'];
            if (!$exam || !$ctx['gerbang']['boleh_mulai']) {
                throw new DomainException('Ujian belum dapat dimulai: '
                    . implode(' ', $ctx['gerbang']['alasan']));
            }
            $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $locked = $this->db->query('SELECT * FROM ujian_pra_semester WHERE id=?'
                . $lock,[(int)$exam['id']])->getRowArray();
            if (!$locked || $locked['status'] !== 'siap') {
                throw new DomainException('Jadwal ujian telah berubah.');
            }
            $now = time();
            $jadwal = AturanPraSemesterService::jadwal(
                (string)$locked['mulai_pada'],(string)$locked['selesai_pada'],$now
            );
            if (!$jadwal['boleh_mulai']) {
                throw new DomainException('Jendela untuk mulai ujian telah ditutup.');
            }
            $sudah = $this->db->table('percobaan_ujian')
                ->where('ujian_pra_semester_id',(int)$locked['id'])
                ->where('siswa_id',(int)$ctx['siswa_id'])
                ->where('jenis','pra_semester')
                ->orderBy('id','DESC')->get(1)->getRowArray();
            if ($sudah !== null) {
                if (in_array($sudah['status'], ['berjalan','jeda_fullscreen','jeda_koneksi'],true)) {
                    $this->db->transCommit();
                    return ['token'=>(string)$sudah['token_publik'],'sudah_ada'=>true];
                }
                throw new DomainException($sudah['status'] === 'dibatalkan_menunggu_guru'
                    ? 'Ujian dibatalkan. Tunggu jadwal susulan Admin.'
                    : 'Ujian utama sudah selesai. Remedial hanya melalui jadwal Admin.');
            }
            $durasi = (int)$locked['durasi_menit'];
            $jumlah = (int)$locked['jumlah_soal'];
            $kkm = (int)($ctx['kkm']['efektif'] ?? 0);
            if ($durasi < 5 || $durasi > 180 || $jumlah < 30 || $jumlah > 200
                || $kkm < 1 || $kkm > 100) {
                throw new DomainException('Pengaturan Pra-Semester belum sah.');
            }
            $bab = $this->db->table('bab')->select('id')
                ->where('paket_semester_id',$paketId)->get()->getResultArray();
            $bank = $this->db->table('soal_pra_semester')
                ->where('ujian_pra_semester_id',(int)$locked['id'])->get()->getResultArray();
            $butir = SusunButirUjianService::praSemester(
                $bank,array_map(static fn(array $b): int => (int)$b['id'],$bab),$jumlah
            );
            $state = AturanWaktuUjianService::awal($durasi*60,$now);
            $token = bin2hex(random_bytes(24));
            $this->db->table('percobaan_ujian')->insert([
                'token_publik'=>$token,
                'progres_tp_id'=>null,'versi_konten_tp_id'=>null,
                'ujian_pra_semester_id'=>(int)$locked['id'],
                'siswa_id'=>(int)$ctx['siswa_id'],
                'jenis'=>'pra_semester','tahap'=>'normal','nomor_percobaan'=>1,
                'status'=>'berjalan','profil_keamanan'=>'ketat',
                'jumlah_soal'=>$jumlah,'kkm'=>$kkm,
                'durasi_detik'=>$durasi*60,'sisa_detik'=>$durasi*60,
                'waktu_mulai'=>self::iso($now),
                'waktu_aktif_terakhir'=>self::iso($now),
                'last_seen_server_at'=>self::iso($now),
                'dibuat_pada'=>self::iso($now),'diubah_pada'=>self::iso($now),
            ]);
            $id = (int)$this->db->insertID();
            if ($id < 1) throw new RuntimeException('Gagal membuat percobaan Pra-Semester.');
            foreach ($butir as $b) {
                $this->db->table('butir_percobaan')->insert(
                    ['percobaan_ujian_id'=>$id] + $b
                );
            }
            $this->riwayat($id,null,'berjalan','Mulai ujian utama pada jadwal Admin.', $now);
            if (!$this->db->transStatus()) throw new RuntimeException('Percobaan gagal disimpan.');
            $this->db->transCommit();
            return ['token'=>$token,'sudah_ada'=>false,'tahap'=>'normal'];
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function lock(int $paketId, string $token): array
    {
        if (!preg_match('/^[a-f0-9]{48}$/D',$token)) {
            throw new DomainException('Token percobaan tidak valid.');
        }
        $ctx = $this->siswa->konteks($paketId);
        $exam = $ctx['ujian'];
        if (!$exam) throw new DomainException('Ujian tidak tersedia.');
        $sql = 'SELECT * FROM percobaan_ujian WHERE token_publik=? AND ujian_pra_semester_id=? '
            . 'AND siswa_id=? AND jenis=?';
        if ($this->db->DBDriver !== 'SQLite3') $sql .= ' FOR UPDATE';
        $r = $this->db->query($sql,
            [$token,(int)$exam['id'],(int)$ctx['siswa_id'],'pra_semester'])->getRowArray();
        if (!$r) throw new DomainException('Percobaan bukan milik siswa ini.');
        return $r;
    }

    private function transact(int $paketId, string $token, callable $handler): array
    {
        $this->db->transBegin();
        try {
            $row = $this->lock($paketId,$token);
            $now = time();
            $state = $this->selaraskan($row,$now);
            $hasil = $handler($row,$state,$now);
            if (!$this->db->transStatus()) throw new RuntimeException('Transaksi ujian gagal.');
            $this->db->transCommit();
            return $hasil;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function simpan(int $id, array $s, int $now): void
    {
        $this->db->table('percobaan_ujian')->where('id',$id)->update([
            'status'=>$s['status'],'sisa_detik'=>(int)$s['sisa_detik'],
            'waktu_aktif_terakhir'=>self::iso($s['waktu_aktif_terakhir']),
            'last_seen_server_at'=>self::iso($s['last_seen_server_at']),
            'jeda_mulai_pada'=>self::iso($s['jeda_mulai_pada']),
            'jeda_fullscreen_total_detik'=>(int)$s['jeda_fullscreen_total_detik'],
            'jeda_koneksi_total_detik'=>(int)$s['jeda_koneksi_total_detik'],
            'jumlah_pelanggaran_fullscreen'=>(int)$s['jumlah_pelanggaran_fullscreen'],
            'alasan_batal'=>$s['alasan_batal'],
            'waktu_selesai'=>self::iso($s['waktu_selesai']),
            'diubah_pada'=>self::iso($now),
        ]);
    }

    private function riwayat(int $id, ?string $sebelum, string $sesudah, string $pesan, int $now): void
    {
        $this->db->table('riwayat_status_ujian')->insert([
            'percobaan_ujian_id'=>$id,'status_lama'=>$sebelum,
            'status_baru'=>$sesudah,'alasan'=>$pesan,'terjadi_pada'=>self::iso($now),
        ]);
    }

    private function segmenMulai(int $id, string $jenis, int $mulai): void
    {
        $this->db->table('segmen_jeda_ujian')->insert([
            'percobaan_ujian_id'=>$id,'jenis'=>$jenis,
            'dimulai_pada'=>self::iso($mulai),'alasan'=>'Jeda keamanan Pra-Semester.',
        ]);
    }

    private function segmenSelesai(int $id, int $now): void
    {
        $r = $this->db->table('segmen_jeda_ujian')
            ->where('percobaan_ujian_id',$id)->where('berakhir_pada',null)
            ->orderBy('id','DESC')->get(1)->getRowArray();
        if (!$r) return;
        $mulai = self::epoch($r['dimulai_pada']);
        $this->db->table('segmen_jeda_ujian')->where('id',(int)$r['id'])
            ->update(['berakhir_pada'=>self::iso($now),
                'durasi_detik'=>max(0,$now-(int)$mulai)]);
    }

    private function finalisasi(array $s, int $now, string $alasan): array
    {
        if ($s['status'] !== 'berjalan') throw new DomainException('Ujian sedang jeda atau telah berakhir.');
        $id = (int)$s['id'];
        $butir = $this->db->table('butir_percobaan')->select('id,kunci_acak')
            ->where('percobaan_ujian_id',$id)->orderBy('posisi')->get()->getResultArray();
        if (count($butir) !== (int)$s['jumlah_soal']) {
            throw new RuntimeException('Butir Pra-Semester tidak lengkap; penilaian ditolak.');
        }
        $rows = $this->db->table('jawaban_percobaan')
            ->select('butir_percobaan_id,opsi_dipilih')
            ->where('percobaan_ujian_id',$id)->get()->getResultArray();
        $jawaban = [];
        foreach ($rows as $r) $jawaban[(int)$r['butir_percobaan_id']] = $r['opsi_dipilih'];
        $skor = SusunButirUjianService::hitung($butir,$jawaban);
        $s['status'] = 'selesai_sah';
        $s['waktu_selesai'] = $now;
        $this->simpan($id,$s,$now);
        $this->db->table('percobaan_ujian')->where('id',$id)
            ->update(['skor'=>$skor['skor'],'jumlah_benar'=>$skor['jumlah_benar']]);
        $this->riwayat($id,'berjalan','selesai_sah',$alasan,$now);
        $s['skor'] = $skor['skor'];
        $s['jumlah_benar'] = $skor['jumlah_benar'];
        return $s;
    }

    private function selaraskan(array $row, int $now): array
    {
        $s = self::state($row);
        if (in_array($s['status'], ['selesai_sah','dibatalkan_menunggu_guru'],true)) return $s;
        $baru = AturanWaktuUjianService::rekonsiliasi($s,$now);
        if (($baru['harus_finalisasi'] ?? false) === true) {
            return $this->finalisasi($baru,$now,'Waktu pengerjaan individual selesai.');
        }
        if ($baru['status'] !== $s['status']) {
            if ($baru['status'] === 'jeda_koneksi') {
                $this->segmenMulai((int)$row['id'],'koneksi',(int)$baru['jeda_mulai_pada']);
            }
            if ($baru['status'] === 'dibatalkan_menunggu_guru') {
                $this->segmenSelesai((int)$row['id'],$now);
            }
            $this->riwayat((int)$row['id'],(string)$s['status'],(string)$baru['status'],
                (string)($baru['alasan_batal'] ?? 'Jeda koneksi.'),$now);
        }
        $this->simpan((int)$row['id'],$baru,$now);
        return $baru;
    }

    private static function info(array $s): array
    {
        $sah = $s['status'] === 'selesai_sah';
        return [
            'status'=>(string)$s['status'],
            'sisa_detik'=>(int)$s['sisa_detik'],
            'pelanggaran'=>(int)$s['jumlah_pelanggaran_fullscreen'],
            'jeda_fullscreen_detik'=>(int)$s['jeda_fullscreen_total_detik'],
            'jeda_koneksi_detik'=>(int)$s['jeda_koneksi_total_detik'],
            'skor'=>$sah && $s['skor'] !== null ? (float)$s['skor'] : null,
            'kkm'=>$sah ? (int)$s['kkm'] : null,
            'jumlah_benar'=>$sah && $s['jumlah_benar'] !== null ? (int)$s['jumlah_benar'] : null,
            'tahap'=>'normal',
            'perlu_remedial'=>false, // remedial hanya pada jadwal baru oleh Admin
            'pesan'=>$s['status'] === 'dibatalkan_menunggu_guru'
                ? 'Ujian Pra-Semester dibatalkan; tunggu jadwal susulan Admin.' : '',
        ];
    }

    public function status(int $paketId, string $token): array
    {
        return $this->transact($paketId,$token,function ($row,$s) {
            $out = self::info($s);
            if ($s['status'] !== 'berjalan') return $out;
            $butir = $this->db->table('butir_percobaan')
                ->select('id,posisi,pertanyaan,opsi_acak_json,konten_terstruktur_json')
                ->where('percobaan_ujian_id',(int)$row['id'])
                ->orderBy('posisi')->get()->getResultArray();
            $out['soal'] = array_map([SusunButirUjianService::class,'untukSiswa'],$butir);
            $jawaban = $this->db->table('jawaban_percobaan')
                ->select('butir_percobaan_id,opsi_dipilih')
                ->where('percobaan_ujian_id',(int)$row['id'])->get()->getResultArray();
            $out['jawaban'] = [];
            foreach ($jawaban as $j) $out['jawaban'][(int)$j['butir_percobaan_id']] = $j['opsi_dipilih'];
            return $out;
        });
    }

    public function jawab(int $paketId, string $token, int $butirId, string $pilih): array
    {
        if ($butirId < 1 || !in_array($pilih,['A','B','C','D','E'],true)) {
            throw new DomainException('Jawaban tidak sah.');
        }
        return $this->transact($paketId,$token,function ($row,$s,$now) use ($butirId,$pilih) {
            if (!AturanWaktuUjianService::bolehMenjawab($s)) {
                return self::info($s) + ['tersimpan'=>false];
            }
            $ada = $this->db->table('butir_percobaan')
                ->where('id',$butirId)->where('percobaan_ujian_id',(int)$row['id'])
                ->countAllResults();
            if ($ada !== 1) throw new DomainException('Butir bukan milik percobaan siswa.');
            $this->db->query(
                'INSERT INTO jawaban_percobaan(percobaan_ujian_id,butir_percobaan_id,opsi_dipilih,waktu_disimpan) '
                . 'VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE opsi_dipilih=VALUES(opsi_dipilih),'
                . 'waktu_disimpan=VALUES(waktu_disimpan)',
                [(int)$row['id'],$butirId,$pilih,self::iso($now)]
            );
            return self::info($s) + ['tersimpan'=>true,'butir_id'=>$butirId,'opsi'=>$pilih];
        });
    }

    public function tindakan(int $paketId, string $token, string $aksi, bool $fullscreen): array
    {
        if (!in_array($aksi,['heartbeat','keluar_fullscreen','pulih','kirim'],true)) {
            throw new DomainException('Tindakan Pra-Semester tidak dikenal.');
        }
        if ($aksi !== 'keluar_fullscreen' && !$fullscreen) {
            throw new DomainException('Fullscreen wajib aktif untuk melanjutkan ujian.');
        }
        return $this->transact($paketId,$token,function ($row,$s,$now) use ($aksi) {
            $id = (int)$row['id'];
            if ($aksi === 'kirim') {
                if (!AturanWaktuUjianService::bolehMenjawab($s)) return self::info($s);
                return self::info($this->finalisasi($s,$now,'Jawaban dikirim siswa.'));
            }
            if (in_array($s['status'],['selesai_sah','dibatalkan_menunggu_guru'],true)) {
                return self::info($s);
            }
            $sebelum = (string)$s['status'];
            if ($aksi === 'heartbeat') {
                $s = AturanWaktuUjianService::heartbeat($s,$now);
            } elseif ($aksi === 'keluar_fullscreen') {
                $s = AturanWaktuUjianService::keluarFullscreen($s,$now);
                if ($sebelum === 'berjalan' && $s['status'] === 'jeda_fullscreen') {
                    $this->segmenMulai($id,'fullscreen',$now);
                }
                if ($sebelum !== $s['status']) {
                    $this->riwayat($id,$sebelum,(string)$s['status'],
                        'Insiden fullscreen Pra-Semester.',$now);
                    $this->db->table('pelanggaran_ujian')->insert([
                        'percobaan_ujian_id'=>$id,'kunci_insiden'=>bin2hex(random_bytes(16)),
                        'kategori'=>'fullscreen','dihitung'=>1,
                        'alasan'=>'Fullscreen atau fokus tab terputus.',
                        'terjadi_pada'=>self::iso($now),
                    ]);
                }
            } elseif ($aksi === 'pulih') {
                $s = AturanWaktuUjianService::pulih($s,$now);
                if ($sebelum !== 'berjalan' && $s['status'] === 'berjalan') {
                    $this->segmenSelesai($id,$now);
                    $this->riwayat($id,$sebelum,'berjalan','Fullscreen/koneksi dipulihkan.',$now);
                }
            }
            $this->simpan($id,$s,$now);
            return self::info($s);
        });
    }
}
