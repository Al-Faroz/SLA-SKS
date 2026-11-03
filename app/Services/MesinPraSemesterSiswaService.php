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
    private PraSemesterSesiService $sesi;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
        $this->siswa = new PraSemesterSiswaService($this->db,$this->akun);
        $this->sesi = new PraSemesterSesiService($this->db,$this->akun);
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

    /**
     * Ujian utama dan sesi tambahan selalu lewat gerbang sekolah yang sama;
     * penjadwalan susulan tidak melewati syarat TP dan kesiapan bank.
     */
    private function akses(array $ctx, array $histori, int $now): array
    {
        $state = PenilaianPraSemesterService::ringkas($histori);
        $exam = $ctx['ujian'];
        $tpSiap = (bool)($ctx['gerbang']['tp']['siap'] ?? false);
        $bankSiap = (bool)($ctx['gerbang']['bank']['siap'] ?? false)
            && $exam && (int)$ctx['gerbang']['bank']['jumlah_sah'] >= (int)$exam['jumlah_soal'];
        $boleh = $exam && $exam['status']==='siap'
            && $tpSiap && $bankSiap && $ctx['kkm']['efektif'] !== null;
        $slot = $boleh
            ? $this->sesi->sesiYangTerbuka($exam,(int)$ctx['siswa_id'],$state['fase'],$now)
            : null;
        $izin = $slot === null ? null
            : AturanJadwalPraSemesterService::periksaMulai(
                $slot,$histori,$now,$tpSiap,$bankSiap,true,true
            );
        return [
            'ringkasan'=>$state,
            'jadwal'=>$slot,
            'boleh_mulai'=>$boleh && $izin !== null && $izin['boleh_buat'],
        ];
    }

    public function ruang(int $paketId): array
    {
        $ctx = $this->siswa->konteks($paketId);
        $histori=$ctx['ujian']
            ? $this->sesi->riwayat((int)$ctx['ujian']['id'],(int)$ctx['siswa_id'])
            : [];
        $akses=$this->akses($ctx,$histori,time());
        $aktif=$akses['ringkasan']['percobaan_aktif_id'] !== null;
        return [
            'paket'=>$ctx['paket'],
            'ujian'=>$ctx['ujian'],
            'gerbang'=>$ctx['gerbang'],
            'percobaan'=>$ctx['percobaan'],
            'token_mulai'=>($aktif || !$akses['boleh_mulai'])
                ? (string)($ctx['percobaan']['token_publik'] ?? '') : '',
            'boleh_mulai'=>$akses['boleh_mulai'] || $aktif,
            'kkm'=>$ctx['kkm']['efektif'],
            'tahap_berikut'=>$akses['ringkasan']['tahap_berikut'],
            'fase'=>$akses['ringkasan']['fase'],
            'jadwal_aktif'=>$akses['jadwal'],
            'nilai_terbaik'=>$akses['ringkasan']['nilai_terbaik'],
            'status_nilai'=>$akses['ringkasan']['status_nilai'],
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
            if (!$exam) {
                throw new DomainException('Guru belum menyiapkan ujian Pra-Semester.');
            }
            $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $locked = $this->db->query('SELECT * FROM ujian_pra_semester WHERE id=?'
                . $lock,[(int)$exam['id']])->getRowArray();
            if (!$locked || $locked['status'] !== 'siap') {
                throw new DomainException('Jadwal ujian telah berubah.');
            }
            // Lock paket ujian melindungi dua klik Mulai dan penomoran
            // percobaan normal/susulan/remedial yang sama.
            $histori=$this->sesi->riwayat((int)$locked['id'],(int)$ctx['siswa_id']);
            $state=PenilaianPraSemesterService::ringkas($histori);
            if ($state['percobaan_aktif_id'] !== null) {
                $terakhir=end($histori);
                $this->db->transCommit();
                return [
                    'token'=>(string)$terakhir['token_publik'],
                    'sudah_ada'=>true,
                    'tahap'=>(string)$terakhir['tahap'],
                ];
            }
            $now=time();
            $akses=$this->akses($ctx,$histori,$now);
            if (!$akses['boleh_mulai'] || $akses['jadwal'] === null) {
                throw new DomainException('Belum ada jadwal utama, remedial, atau susulan '
                    . 'yang aktif dan sesuai kesempatan siswa.');
            }
            $slot=$akses['jadwal'];
            $durasi=(int)$slot['durasi_menit'];
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
            $insert=[
                'token_publik'=>$token,
                'progres_tp_id'=>null,'versi_konten_tp_id'=>null,
                'ujian_pra_semester_id'=>(int)$locked['id'],
                'siswa_id'=>(int)$ctx['siswa_id'],
                'jenis'=>'pra_semester','tahap'=>(string)$slot['tahap'],
                'nomor_percobaan'=>count($histori)+1,
                'status'=>'berjalan','profil_keamanan'=>'ketat',
                'jumlah_soal'=>$jumlah,'kkm'=>$kkm,
                'durasi_detik'=>$durasi*60,'sisa_detik'=>$durasi*60,
                'waktu_mulai'=>self::iso($now),
                'waktu_aktif_terakhir'=>self::iso($now),
                'last_seen_server_at'=>self::iso($now),
                'dibuat_pada'=>self::iso($now),'diubah_pada'=>self::iso($now),
            ];
            if ($this->sesi->terpasang()) {
                $insert['jadwal_pra_sesi_id']=$slot['id'];
            }
            $this->db->table('percobaan_ujian')->insert($insert);
            $id = (int)$this->db->insertID();
            if ($id < 1) throw new RuntimeException('Gagal membuat percobaan Pra-Semester.');
            foreach ($butir as $b) {
                $this->db->table('butir_percobaan')->insert(
                    ['percobaan_ujian_id'=>$id] + $b
                );
            }
            $this->riwayat($id,null,'berjalan',
                'Mulai Pra-Semester ' . $slot['jenis'] . '/' . $slot['tahap']
                . ', jadwal #' . ($slot['id'] ?? 'utama') . '.', $now);
            if (!$this->db->transStatus()) throw new RuntimeException('Percobaan gagal disimpan.');
            $this->db->transCommit();
            return ['token'=>$token,'sudah_ada'=>false,'tahap'=>(string)$slot['tahap']];
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
            // Jika perangkat baru tersambung setelah lebih dari 300 detik,
            // rekonsiliasi langsung menghasilkan batal (tanpa respons jeda).
            // Segmen koneksi tetap wajib dicatat pada batas deteksi 20 detik.
            $jedaKoneksiLangsungBatal = $s['status'] === 'berjalan'
                && $baru['status'] === 'dibatalkan_menunggu_guru'
                && ($baru['jeda_mulai_pada'] ?? null) !== null;
            if ($baru['status'] === 'jeda_koneksi' || $jedaKoneksiLangsungBatal) {
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
            'tahap'=>(string)$s['tahap'],
            'perlu_remedial'=>false, // hak remedial hanya ketika jadwal Admin aktif
            'pesan'=>$s['status'] === 'dibatalkan_menunggu_guru'
                ? 'Ujian Pra-Semester dibatalkan; tunggu jadwal susulan Admin.' : '',
        ];
    }

    public function status(int $paketId, string $token): array
    {
        return $this->transact($paketId,$token,function ($row,$s) use ($paketId,$token) {
            $out = self::info($s);
            if ($s['status'] !== 'berjalan') return $out;
            $butir = $this->db->table('butir_percobaan')
                ->select('id,posisi,pertanyaan,opsi_acak_json,konten_terstruktur_json')
                ->where('percobaan_ujian_id',(int)$row['id'])
                ->orderBy('posisi')->get()->getResultArray();
            $out['soal'] = array_map([SusunButirUjianService::class,'untukSiswa'],$butir);
            helper('url');
            foreach ($butir as $i=>$raw) {
                $r = json_decode((string)($raw['konten_terstruktur_json']??''),true);
                $source = is_array($r) ? (string)($r['word_source_id']??'') : '';
                if (!preg_match('/^[0-9a-f]{32}$/D',$source)) continue;
                $base=site_url('siswa/pra-semester/'.$paketId.'/'.$token.'/media/'.$source.'/');
                $rewrite = static fn(array $m): string =>
                    'src="' . htmlspecialchars($base.$m[1],ENT_QUOTES,'UTF-8') . '"';
                $out['soal'][$i]['html_pertanyaan'] = preg_replace_callback(
                    '/src="word-image:(rId[0-9]+)"/', $rewrite,
                    (string)($out['soal'][$i]['html_pertanyaan']??'')
                );
                foreach ($out['soal'][$i]['html_opsi'] as $key=>$html) {
                    $out['soal'][$i]['html_opsi'][$key] = preg_replace_callback(
                        '/src="word-image:(rId[0-9]+)"/',$rewrite,(string)$html
                    );
                }
            }
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
            // MariaDB menggunakan kunci unik (attempt,butir) untuk UPSERT atomik.
            // SQLite hanya dipakai fixture QA sintetis, tidak untuk instalasi SLA-SKS.
            if ($this->db->DBDriver === 'SQLite3') {
                $this->db->query(
                    'INSERT INTO jawaban_percobaan(percobaan_ujian_id,butir_percobaan_id,opsi_dipilih,waktu_disimpan) '
                    . 'VALUES(?,?,?,?) ON CONFLICT(percobaan_ujian_id,butir_percobaan_id) '
                    . 'DO UPDATE SET opsi_dipilih=excluded.opsi_dipilih,'
                    . 'waktu_disimpan=excluded.waktu_disimpan',
                    [(int)$row['id'],$butirId,$pilih,self::iso($now)]
                );
            } else {
                $this->db->query(
                    'INSERT INTO jawaban_percobaan(percobaan_ujian_id,butir_percobaan_id,opsi_dipilih,waktu_disimpan) '
                    . 'VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE opsi_dipilih=VALUES(opsi_dipilih),'
                    . 'waktu_disimpan=VALUES(waktu_disimpan)',
                    [(int)$row['id'],$butirId,$pilih,self::iso($now)]
                );
            }
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
    /**
     * Aset Word hanya jika sumbernya ada pada snapshot percobaan yang
     * benar-benar milik siswa. Token tidak memberi akses ke bank lain.
     */
    public function gambarWord(int $paketId,string $token,string $sourceId,string $rid): array
    {
        if (!preg_match('/^[a-f0-9]{48}$/D',$token)
            || !preg_match('/^[a-f0-9]{32}$/D',$sourceId)
            || !preg_match('/^rId[0-9]+$/D',$rid)) {
            throw new DomainException('Referensi gambar ujian tidak sah.');
        }
        $ctx=$this->siswa->konteks($paketId);
        $ujian=$ctx['ujian'];
        if (!$ujian) throw new DomainException('Ujian tidak tersedia.');
        $attempt=$this->db->table('percobaan_ujian')
            ->select('id')->where('token_publik',$token)
            ->where('ujian_pra_semester_id',(int)$ujian['id'])
            ->where('siswa_id',(int)$ctx['siswa_id'])
            ->where('jenis','pra_semester')->get()->getRowArray();
        if (!$attempt) throw new DomainException('Gambar bukan milik siswa.');
        $status=$this->db->table('percobaan_ujian')->select('status')
            ->where('id',(int)$attempt['id'])->get()->getRowArray();
        if (!in_array((string)$status['status'],
            ['berjalan','jeda_fullscreen','jeda_koneksi','selesai_sah'],true)) {
            throw new DomainException('Gambar ujian tidak tersedia.');
        }
        $source=$this->db->table('impor_soal_pra_docx')
            ->select('id')->where('id',$sourceId)
            ->where('paket_semester_id',$paketId)->where('status','diterapkan')
            ->get()->getRowArray();
        if (!$source) throw new DomainException('Sumber gambar tidak ditemukan.');
        $butir=$this->db->table('butir_percobaan')
            ->select('konten_terstruktur_json')
            ->where('percobaan_ujian_id',(int)$attempt['id'])
            ->get()->getResultArray();
        $authorized=false;
        foreach ($butir as $b) {
            $rich=json_decode((string)($b['konten_terstruktur_json']??''),true);
            if (is_array($rich) && ($rich['word_source_id']??null)===$sourceId
                && (str_contains((string)($rich['html_pertanyaan']??''),'word-image:'.$rid)
                    || str_contains(json_encode($rich['html_opsi']??[]),
                        'word-image:'.$rid))) {
                $authorized=true;break;
            }
        }
        if (!$authorized) throw new DomainException('Aset tidak ada pada snapshot ujian siswa.');
        return RenderWordSoalService::gambar(
            WRITEPATH.'private/pra_semester_word/'.$sourceId.'.docx',$rid
        );
    }

}
