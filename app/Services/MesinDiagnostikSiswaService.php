<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * S06B: semua mutasi percobaan berada di transaksi & lock InnoDB.
 * Klien tidak pernah menerima kunci atau snapshot mentah.
 */
final class MesinDiagnostikSiswaService
{
    private BaseConnection $db;
    private KatalogPembelajaranSiswaService $katalog;
    private ProgresTpSiswaService $progres;
    private $akunSaatIni;

    public function __construct(?BaseConnection $db = null, ?callable $akunSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->akunSaatIni = $akunSaatIni ?? static fn(): ?array => (new AuthService())->penggunaSaatIni();
        $this->katalog = new KatalogPembelajaranSiswaService($this->db, $this->akunSaatIni);
        $this->progres = new ProgresTpSiswaService($this->db, $this->akunSaatIni);
    }

    private function konteks(int $tpId): array
    {
        $akun = ($this->akunSaatIni)();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'siswa'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Akses ujian hanya untuk Siswa aktif.');
        }
        $ctx = $this->katalog->rincianTp($tpId);
        $p = $this->progres->status($tpId);
        if (!$p) {
            throw new DomainException('Aktifkan TP sebelum membuka Diagnostik.');
        }
        return ['akun' => $akun, 'ctx' => $ctx, 'progres' => $p];
    }

    /** GET halaman ujian tidak membuat percobaan. */
    public function ruang(int $tpId): array
    {
        $ctx = $this->konteks($tpId);
        $existing = $this->db->table('percobaan_ujian')
            ->select('token_publik,status')
            ->where('progres_tp_id', (int) $ctx['progres']['id'])
            ->where('jenis', 'diagnostik')->orderBy('id','DESC')->get(1)->getRowArray();
        return [
            'tp' => $ctx['ctx']['tp'], 'progres' => $ctx['progres'],
            'percobaan' => $existing,
            'status_akhir' => in_array($ctx['progres']['status'], ['tuntas', 'belajar', 'perlu_bantuan'], true),
        ];
    }

    private static function iso(?int $t): ?string
    {
        return $t === null ? null : gmdate('Y-m-d H:i:s', $t);
    }

    private static function epoch(?string $t): ?int
    {
        if ($t === null || $t === '') return null;
        $epoch = strtotime($t . ' UTC');
        if ($epoch === false) throw new RuntimeException('Timestamp ujian rusak.');
        return $epoch;
    }

    private static function state(array $row): array
    {
        foreach (['waktu_mulai','waktu_aktif_terakhir','last_seen_server_at',
            'jeda_mulai_pada','waktu_selesai'] as $k) {
            $row[$k] = self::epoch($row[$k] ?? null);
        }
        foreach (['sisa_detik','jeda_fullscreen_total_detik','jeda_koneksi_total_detik',
            'jumlah_pelanggaran_fullscreen'] as $k) $row[$k] = (int) $row[$k];
        return $row;
    }

    private function simpan(int $id, array $s, int $now): void
    {
        $data = [
            'status' => $s['status'], 'sisa_detik' => $s['sisa_detik'],
            'waktu_aktif_terakhir' => self::iso($s['waktu_aktif_terakhir']),
            'last_seen_server_at' => self::iso($s['last_seen_server_at']),
            'jeda_mulai_pada' => self::iso($s['jeda_mulai_pada']),
            'jeda_fullscreen_total_detik' => $s['jeda_fullscreen_total_detik'],
            'jeda_koneksi_total_detik' => $s['jeda_koneksi_total_detik'],
            'jumlah_pelanggaran_fullscreen' => $s['jumlah_pelanggaran_fullscreen'],
            'alasan_batal' => $s['alasan_batal'], 'waktu_selesai' => self::iso($s['waktu_selesai']),
            'diubah_pada' => self::iso($now),
        ];
        $this->db->table('percobaan_ujian')->where('id', $id)->update($data);
    }

    private function riwayat(int $id, ?string $lama, string $baru, string $alasan, int $now): void
    {
        $this->db->table('riwayat_status_ujian')->insert([
            'percobaan_ujian_id' => $id, 'status_lama' => $lama,
            'status_baru' => $baru, 'alasan' => $alasan, 'terjadi_pada' => self::iso($now),
        ]);
    }

    private function segmenMulai(int $id, string $jenis, int $mulai, string $alasan): void
    {
        $this->db->table('segmen_jeda_ujian')->insert([
            'percobaan_ujian_id' => $id, 'jenis' => $jenis,
            'dimulai_pada' => self::iso($mulai), 'alasan' => $alasan,
        ]);
    }

    private function segmenSelesai(int $id, int $now): void
    {
        $row = $this->db->table('segmen_jeda_ujian')
            ->where('percobaan_ujian_id', $id)->where('berakhir_pada', null)
            ->orderBy('id', 'DESC')->get(1)->getRowArray();
        if (!$row) return;
        $mulai = self::epoch($row['dimulai_pada']);
        $this->db->table('segmen_jeda_ujian')->where('id', $row['id'])
            ->update(['berakhir_pada' => self::iso($now),
                'durasi_detik' => max(0, $now - $mulai)]);
    }

    private function batalProgress(int $progresId, int $now): void
    {
        $this->db->table('progres_tp')->where('id', $progresId)
            ->where('status', 'diagnostik')
            ->update(['status' => 'perlu_bantuan', 'diubah_pada' => self::iso($now)]);
    }

    /**
     * Rekonsiliasi saat tiap permintaan, bahkan tanpa cron. Status waktu habis
     * hanya diselesaikan bersama skor agar tidak ada selesai_sah tanpa nilai.
     */
    private function selaraskan(array $row, int $now): array
    {
        $id = (int) $row['id'];
        $awal = self::state($row);
        if (in_array($awal['status'], ['selesai_sah','dibatalkan_menunggu_guru','baru'], true)) {
            return $awal;
        }
        $baru = AturanWaktuUjianService::rekonsiliasi($awal, $now);
        if (($baru['harus_finalisasi'] ?? false) === true) {
            return $this->finalisasi($baru, $now, 'Waktu aktif habis.');
        }
        if ($awal['status'] !== $baru['status']) {
            if ($baru['status'] === 'jeda_koneksi') {
                $this->segmenMulai($id, 'koneksi', (int) $baru['jeda_mulai_pada'], 'Heartbeat terputus.');
            } elseif ($baru['status'] === 'dibatalkan_menunggu_guru') {
                $this->segmenSelesai($id, $now);
                if ($awal['status'] === 'jeda_fullscreen') {
                    $baru['jeda_fullscreen_total_detik'] += $now - (int) $awal['jeda_mulai_pada'];
                } elseif ($awal['status'] === 'jeda_koneksi') {
                    $baru['jeda_koneksi_total_detik'] += $now - (int) $awal['jeda_mulai_pada'];
                }
                $this->batalProgress((int) $row['progres_tp_id'], $now);
            }
            $this->riwayat($id, (string) $awal['status'], (string) $baru['status'],
                (string) ($baru['alasan_batal'] ?? 'Koneksi terputus.'), $now);
        }
        $this->simpan($id, $baru, $now);
        return $baru;
    }

    private function finalisasi(array $s, int $now, string $alasan): array
    {
        if (($s['status'] ?? '') !== 'berjalan') {
            throw new DomainException('Percobaan sedang jeda atau tidak berjalan.');
        }
        $id = (int) $s['id'];
        $butir = $this->db->table('butir_percobaan')->select('id,kunci_acak')
            ->where('percobaan_ujian_id', $id)->orderBy('posisi', 'ASC')
            ->get()->getResultArray();
        if (count($butir) !== (int) $s['jumlah_soal']) {
            throw new RuntimeException('Butir ujian tidak lengkap. Jangan memberi nilai.');
        }
        $jawaban = $this->db->table('jawaban_percobaan')
            ->select('butir_percobaan_id,opsi_dipilih')
            ->where('percobaan_ujian_id', $id)->get()->getResultArray();
        $pilih = [];
        foreach ($jawaban as $item) $pilih[(int) $item['butir_percobaan_id']] = $item['opsi_dipilih'];
        $nilai = SusunButirUjianService::hitung($butir, $pilih);
        $s['status'] = 'selesai_sah';
        $s['sisa_detik'] = max(0, (int) $s['sisa_detik']);
        $s['waktu_selesai'] = $now;
        $this->simpan($id, $s, $now);
        $this->db->table('percobaan_ujian')->where('id', $id)->update([
            'jumlah_benar' => $nilai['jumlah_benar'], 'skor' => $nilai['skor'],
        ]);
        $lulus = $nilai['skor'] >= (int) $s['kkm'];
        $this->db->table('progres_tp')->where('id', (int) $s['progres_tp_id'])
            ->where('status', 'diagnostik')
            ->update(['status' => $lulus ? 'tuntas' : 'belajar',
                'diubah_pada' => self::iso($now)]);
        $this->riwayat($id, 'berjalan', 'selesai_sah', $alasan, $now);
        $s['skor'] = $nilai['skor'];
        $s['jumlah_benar'] = $nilai['jumlah_benar'];
        return $s;
    }

    /**
     * POST setelah gesture fullscreen; diproteksi CSRF dan login.
     * Fullscreen browser adalah mitigasi best-effort, bukan bukti kriptografis.
     */
    public function mulai(int $tpId, bool $fullscreen): array
    {
        if (!$fullscreen) throw new DomainException('Aktifkan fullscreen sebelum memulai ujian.');
        $ctx = $this->konteks($tpId);
        $p = $ctx['progres'];
        $this->db->transBegin();
        try {
            $this->db->query('SELECT id FROM progres_tp WHERE id=? FOR UPDATE', [(int) $p['id']]);
            $existing = $this->db->table('percobaan_ujian')
                ->where('progres_tp_id', (int) $p['id'])->where('jenis', 'diagnostik')
                ->orderBy('id', 'DESC')->get(1)->getRowArray();
            if ($existing) {
                $this->db->transCommit();
                return ['token' => $existing['token_publik'], 'sudah_ada' => true];
            }
            $check = (new PetunjukDiagnostikSiswaService($this->db, $this->akunSaatIni))->periksa($tpId);
            if (!$check['boleh_uji'] || $p['status'] !== 'diagnostik') {
                throw new DomainException('Persyaratan Diagnostik belum terpenuhi.');
            }
            $versi = $this->db->table('versi_konten_tp')->where('id', (int) $p['versi_konten_tp_id'])
                ->get()->getRowArray();
            if (!$versi || !ProgresTpSiswaService::validasiSnapshot($versi, $tpId)) {
                throw new DomainException('Snapshot TP tidak sah.');
            }
            $snap = json_decode($versi['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
            $butir = SusunButirUjianService::diagnostik($snap);
            $kkm = (int) ($snap['tp']['kkm'] ?? 0);
            if ($kkm < 1 || $kkm > 100 || count($butir) > 65535) {
                throw new DomainException('KKM atau jumlah soal pada snapshot tidak sah.');
            }
            $now = time();
            $awal = AturanWaktuUjianService::awal($check['durasi_menit'] * 60, $now);
            $token = bin2hex(random_bytes(24));
            $this->db->table('percobaan_ujian')->insert([
                'token_publik' => $token, 'progres_tp_id' => (int) $p['id'],
                'versi_konten_tp_id' => (int) $p['versi_konten_tp_id'],
                'jenis' => 'diagnostik', 'nomor_percobaan' => 1, 'status' => 'berjalan',
                'profil_keamanan' => 'ketat', 'jumlah_soal' => count($butir),
                'kkm' => $kkm, 'durasi_detik' => $check['durasi_menit'] * 60,
                'sisa_detik' => $awal['sisa_detik'], 'waktu_mulai' => self::iso($now),
                'waktu_aktif_terakhir' => self::iso($now),
                'last_seen_server_at' => self::iso($now), 'dibuat_pada' => self::iso($now),
                'diubah_pada' => self::iso($now),
            ]);
            $id = (int) $this->db->insertID();
            if ($id < 1) throw new RuntimeException('Percobaan gagal dibuat.');
            foreach ($butir as $item) {
                $this->db->table('butir_percobaan')->insert(
                    array_merge($item, ['percobaan_ujian_id' => $id]));
            }
            $this->riwayat($id, null, 'berjalan', 'Memulai Diagnostik melalui fullscreen.', $now);
            if (!$this->db->transStatus()) throw new RuntimeException('Transaksi ujian gagal.');
            $this->db->transCommit();
            return ['token' => $token, 'sudah_ada' => false];
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function lock(int $tpId, string $token): array
    {
        if (!preg_match('/^[a-f0-9]{48}$/D', $token)) {
            throw new DomainException('Identitas percobaan tidak sah.');
        }
        $ctx = $this->konteks($tpId);
        $row = $this->db->query(
            'SELECT * FROM percobaan_ujian WHERE token_publik=? AND progres_tp_id=? AND jenis=? FOR UPDATE',
            [$token, (int) $ctx['progres']['id'], 'diagnostik']
        )->getRowArray();
        if (!$row || (int) $row['versi_konten_tp_id'] !== (int) $ctx['progres']['versi_konten_tp_id']) {
            throw new DomainException('Percobaan tidak ditemukan untuk akun ini.');
        }
        return $row;
    }

    private function transact(int $tpId, string $token, callable $handler): array
    {
        $this->db->transBegin();
        try {
            $row = $this->lock($tpId, $token);
            $now = time();
            $s = $this->selaraskan($row, $now);
            $out = $handler($row, $s, $now);
            if (!$this->db->transStatus()) throw new RuntimeException('Transaksi ujian gagal.');
            $this->db->transCommit();
            return $out;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private static function info(array $s): array
    {
        $status = (string) $s['status'];
        return [
            'status' => $status, 'sisa_detik' => (int) $s['sisa_detik'],
            'pelanggaran' => (int) $s['jumlah_pelanggaran_fullscreen'],
            'jeda_fullscreen_detik' => (int) $s['jeda_fullscreen_total_detik'],
            'jeda_koneksi_detik' => (int) $s['jeda_koneksi_total_detik'],
            'skor' => $status === 'selesai_sah' ? (float) ($s['skor'] ?? 0) : null,
            'jumlah_benar' => $status === 'selesai_sah' ? (int) ($s['jumlah_benar'] ?? 0) : null,
            'kkm' => $status === 'selesai_sah' ? (int) $s['kkm'] : null,
            'pesan' => $status === 'dibatalkan_menunggu_guru'
                ? 'Percobaan dibatalkan. Menunggu keputusan Guru.' : '',
        ];
    }

    private static function htmlSiswa(string $html, int $tpId, string $token, ?string $source): string
    {
        if ($html === '') return '';
        $sanitasi = HtmlSoalGuruService::bersihkan($html, $tpId, $source, true)['html'];
        return preg_replace_callback('/src="(soal-image:([a-f0-9]{32})|word-image:(rId[0-9]+))"/', 
            static function (array $m) use ($tpId, $token): string {
                $tipe = !empty($m[2]) ? 'editor' : 'word';
                $id = !empty($m[2]) ? $m[2] : $m[3];
                return 'src="' . htmlspecialchars(site_url('siswa/tp/' . $tpId .
                    '/diagnostik/media/' . $token . '/' . $tipe . '/' . $id),
                    ENT_QUOTES, 'UTF-8') . '"';
            }, $sanitasi) ?? '';
    }

    /** GET yang merekonsiliasi waktu; soal hanya bila sedang berjalan. */
    public function status(int $tpId, string $token): array
    {
        return $this->transact($tpId, $token, function ($row, $s) use ($tpId) {
            $out = self::info($s);
            if ($s['status'] !== 'berjalan') return $out;
            $butir = $this->db->table('butir_percobaan')
                ->select('id,posisi,pertanyaan,konten_terstruktur_json,opsi_acak_json')
                ->where('percobaan_ujian_id', (int) $row['id'])
                ->orderBy('posisi', 'ASC')->get()->getResultArray();
            $out['soal'] = [];
            foreach ($butir as $b) {
                $soal = SusunButirUjianService::untukSiswa($b);
                $rich = json_decode((string) ($b['konten_terstruktur_json'] ?? ''), true);
                $source = is_array($rich) && is_string($rich['word_source_id'] ?? null)
                    ? $rich['word_source_id'] : null;
                $soal['html_pertanyaan'] = self::htmlSiswa((string) $soal['html_pertanyaan'],
                    $tpId, (string) $row['token_publik'], $source);
                foreach ($soal['html_opsi'] as $huruf => $html) {
                    $soal['html_opsi'][$huruf] = self::htmlSiswa((string) $html,
                        $tpId, (string) $row['token_publik'], $source);
                }
                $out['soal'][] = $soal;
            }
            $saved = $this->db->table('jawaban_percobaan')
                ->select('butir_percobaan_id,opsi_dipilih')
                ->where('percobaan_ujian_id', (int) $row['id'])->get()->getResultArray();
            $out['jawaban'] = [];
            foreach ($saved as $r) $out['jawaban'][(int)$r['butir_percobaan_id']] = $r['opsi_dipilih'];
            return $out;
        });
    }

    public function jawab(int $tpId, string $token, int $butirId, string $pilih): array
    {
        if ($butirId < 1 || !in_array($pilih, ['A','B','C','D','E'], true)) {
            throw new DomainException('Jawaban tidak sah.');
        }
        return $this->transact($tpId, $token, function ($row, $s, $now) use ($butirId, $pilih) {
            if (!AturanWaktuUjianService::bolehMenjawab($s)) {
                return array_merge(self::info($s), ['tersimpan' => false,
                    'pesan' => 'Jawaban ditolak karena ujian sedang jeda atau telah berakhir.']);
            }
            $ada = $this->db->table('butir_percobaan')
                ->where('id', $butirId)->where('percobaan_ujian_id', $row['id'])
                ->countAllResults();
            if (!$ada) throw new DomainException('Butir bukan milik percobaan ini.');
            $this->db->query(
                'INSERT INTO jawaban_percobaan(percobaan_ujian_id,butir_percobaan_id,opsi_dipilih,waktu_disimpan)'
                . ' VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE opsi_dipilih=VALUES(opsi_dipilih),waktu_disimpan=VALUES(waktu_disimpan)',
                [$row['id'], $butirId, $pilih, self::iso($now)]
            );
            return array_merge(self::info($s), ['tersimpan' => true, 'butir_id' => $butirId,
                'opsi' => $pilih]);
        });
    }

    public function tindakan(int $tpId, string $token, string $aksi): array
    {
        if (!in_array($aksi, ['heartbeat','keluar_fullscreen','pulih','kirim'], true)) {
            throw new DomainException('Tindakan ujian tidak dikenal.');
        }
        return $this->transact($tpId, $token, function ($row, $s, $now) use ($aksi) {
            $id = (int) $row['id'];
            if ($aksi === 'kirim') {
                if (!AturanWaktuUjianService::bolehMenjawab($s)) {
                    return self::info($s);
                }
                return self::info($this->finalisasi($s, $now, 'Dikirim oleh Siswa.'));
            }
            if (in_array($s['status'], ['selesai_sah', 'dibatalkan_menunggu_guru'], true)) {
                return self::info($s);
            }
            $awal = $s['status'];
            if ($aksi === 'heartbeat') {
                $s = AturanWaktuUjianService::heartbeat($s, $now);
            } elseif ($aksi === 'keluar_fullscreen') {
                $s = AturanWaktuUjianService::keluarFullscreen($s, $now);
                if ($awal === 'berjalan' && $s['status'] === 'jeda_fullscreen') {
                    $this->segmenMulai($id, 'fullscreen', $now, 'Fullscreen atau tab kehilangan fokus.');
                }
                if ($awal === 'berjalan' && $s['status'] === 'dibatalkan_menunggu_guru') {
                    $this->batalProgress((int) $row['progres_tp_id'], $now);
                }
                if ($awal !== $s['status']) {
                    $this->riwayat($id, $awal, $s['status'], 'Insiden fullscreen.', $now);
                    $this->db->table('pelanggaran_ujian')->insert([
                        'percobaan_ujian_id' => $id,
                        'kunci_insiden' => bin2hex(random_bytes(16)),
                        'kategori' => 'fullscreen', 'dihitung' => 1,
                        'alasan' => 'Fullscreen tidak aktif atau tab kehilangan fokus.',
                        'terjadi_pada' => self::iso($now),
                    ]);
                }
            } elseif ($aksi === 'pulih') {
                $s = AturanWaktuUjianService::pulih($s, $now);
                if ($awal !== $s['status'] && $s['status'] === 'berjalan') {
                    $this->segmenSelesai($id, $now);
                    $this->riwayat($id, $awal, 'berjalan', 'Fullscreen dipulihkan.', $now);
                }
            }
            $this->simpan($id, $s, $now);
            return self::info($s);
        });
    }

    /** Akses asset historis hanya jika token milik siswa dan soal terpilih. */
    public function gambar(int $tpId, string $token, string $jenis, string $id): array
    {
        $ctx = $this->konteks($tpId);
        if (!preg_match('/^[a-f0-9]{48}$/D', $token)
            || !in_array($jenis, ['editor','word'], true)) {
            throw new DomainException('Media ujian tidak tersedia.');
        }
        $attempt = $this->db->table('percobaan_ujian')
            ->where('token_publik', $token)->where('progres_tp_id', $ctx['progres']['id'])
            ->get()->getRowArray();
        if (!$attempt || !in_array($attempt['status'], ['berjalan','jeda_fullscreen','jeda_koneksi'], true)) {
            throw new DomainException('Media ujian tidak tersedia.');
        }
        $richRows = $this->db->table('butir_percobaan')
            ->select('konten_terstruktur_json')->where('percobaan_ujian_id', $attempt['id'])
            ->get()->getResultArray();
        foreach ($richRows as $row) {
            $rich = json_decode((string) $row['konten_terstruktur_json'], true);
            if (!is_array($rich)) continue;
            $html = (string) ($rich['html_pertanyaan'] ?? '')
                . implode('', array_values((array) ($rich['html_opsi'] ?? [])));
            if ($jenis === 'editor' && preg_match('/^[a-f0-9]{32}$/D', $id)
                && str_contains($html, 'soal-image:' . $id)) {
                $base = WRITEPATH . 'private/soal_media/' . $tpId . '/' . $id;
                $file = is_file($base.'.png') ? $base.'.png' : $base.'.jpg';
                if (!is_file($file)) throw new DomainException('Gambar tidak ditemukan.');
                $data = file_get_contents($file);
                if ($data === false || strlen($data) > 2097152) throw new DomainException('Gambar tidak sah.');
                return ['mime' => str_ends_with($file,'.png')?'image/png':'image/jpeg','data'=>$data];
            }
            if ($jenis === 'word' && preg_match('/^rId[0-9]+$/D', $id)
                && str_contains($html, 'word-image:' . $id)) {
                $source = (string) ($rich['word_source_id'] ?? '');
                if (!preg_match('/^[a-f0-9]{32}$/D', $source)) {
                    throw new DomainException('Sumber Word tidak sah.');
                }
                return RenderWordSoalService::gambar(
                    WRITEPATH . 'private/word_soal/' . $source . '.docx', $id);
            }
        }
        throw new DomainException('Gambar bukan milik percobaan ini.');
    }
}
