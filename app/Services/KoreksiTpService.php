<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Koreksi TP Siap V4-0006. Semua edit berada di salinan snapshot privat,
 * bukan pada TP/soal/materi yang sedang dipakai siswa.
 */
final class KoreksiTpService
{
    private BaseConnection $db;
    private $akunSaatIni;

    public function __construct(?BaseConnection $db = null, ?callable $akunSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->akunSaatIni = $akunSaatIni ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    private function akun(string $role): array
    {
        $akun = ($this->akunSaatIni)();
        if (!is_array($akun) || ($akun['peran_aktif'] ?? '') !== $role
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0
            || (int) ($akun['id'] ?? 0) < 1) {
            throw new DomainException('Akses koreksi akademik tidak diizinkan.');
        }
        return $akun;
    }

    public function tersedia(): bool
    {
        return $this->db->tableExists('permohonan_koreksi_tp');
    }

    private function perluTabel(): void
    {
        if (!$this->tersedia()) {
            throw new DomainException('Modul koreksi belum dipasang. Admin perlu mengimpor SQL V4-0006 terlebih dahulu.');
        }
    }

    private function guru(int $tpId): array
    {
        $akun = $this->akun('guru');
        $rincian = (new DetailTpService($this->db, $this->akunSaatIni))->lihat($tpId);
        if (($rincian['tp']['status'] ?? '') !== 'siap') {
            throw new DomainException('Permohonan koreksi hanya berlaku untuk TP Siap.');
        }
        return $akun;
    }

    private static function decode(string $data): array
    {
        $nilai = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($nilai) || !isset($nilai['tp'], $nilai['materi'], $nilai['soal'], $nilai['cp'], $nilai['paket'])) {
            throw new DomainException('Snapshot koreksi tidak lengkap.');
        }
        return $nilai;
    }

    private static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function terbaru(int $tpId): array
    {
        $v = $this->db->table('versi_konten_tp')
            ->where('tp_id', $tpId)->orderBy('nomor_versi', 'DESC')->get(1)->getRowArray();
        if ($v === null || !hash_equals((string) $v['checksum'], hash('sha256', (string) $v['snapshot_json']))) {
            throw new DomainException('Snapshot terakhir TP tidak tersedia atau pemeriksaan integritas gagal.');
        }
        return $v;
    }

    private function catat(int $id, string $peran, string $aksi, string $pesan): void
    {
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => $id,
            'peran_aktif' => $peran,
            'peristiwa' => $aksi,
            'catatan' => mb_substr($pesan, 0, 255),
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    private function row(int $id, bool $lock = false): array
    {
        $this->perluTabel();
        if ($id < 1) {
            throw new DomainException('Nomor permohonan tidak sah.');
        }
        $builder = $this->db->table('permohonan_koreksi_tp')->where('id', $id);
        $row = $lock
            ? $this->db->query('SELECT * FROM permohonan_koreksi_tp WHERE id=? FOR UPDATE', [$id])->getRowArray()
            : $builder->get()->getRowArray();
        if ($row === null) {
            throw new DomainException('Permohonan koreksi tidak ditemukan.');
        }
        return $row;
    }

    public function lihatGuru(int $id): array
    {
        $row = $this->row($id);
        $akun = $this->guru((int) $row['tp_id']);
        if ((int) $row['guru_id'] !== (int) $akun['id']) {
            throw new DomainException('Permohonan ini dibuat Guru lain.');
        }
        $row['usulan'] = self::decode((string) $row['usulan_json']);
        return $row;
    }

    public function lihatAdmin(int $id): array
    {
        $this->akun('admin');
        $row = $this->row($id);
        $row['usulan'] = self::decode((string) $row['usulan_json']);
        $row['asal'] = self::decode((string) $this->db->table('versi_konten_tp')
            ->where('id', $row['versi_dasar_id'])->get()->getRow('snapshot_json'));
        return $row;
    }

    public function daftarGuru(int $tpId): array
    {
        $this->guru($tpId);
        $this->perluTabel();
        return $this->db->table('permohonan_koreksi_tp')
            ->select('id,kategori,status,alasan,dibuat_pada,diajukan_pada,diputus_pada,catatan_keputusan,versi_terbit_id')
            ->where('tp_id', $tpId)->orderBy('id', 'DESC')->limit(20)->get()->getResultArray();
    }

    public function daftarAdmin(): array
    {
        $this->akun('admin');
        $this->perluTabel();
        return $this->db->table('permohonan_koreksi_tp k')
            ->select('k.id,k.tp_id,k.kategori,k.status,k.alasan,k.diajukan_pada,k.dibuat_pada,k.guru_id,p.nama AS guru_nama,t.kode_tp')
            ->join('pengguna p', 'p.id=k.guru_id')
            ->join('tp t', 't.id=k.tp_id')
            ->orderBy('k.id', 'DESC')->limit(50)->get()->getResultArray();
    }

    public function buat(int $tpId, string $kategori, string $alasan): int
    {
        $akun = $this->guru($tpId);
        $this->perluTabel();
        $kategori = strtoupper(trim($kategori));
        $alasan = trim($alasan);
        if (!in_array($kategori, ['A', 'B', 'C'], true)
            || mb_strlen($alasan) < 10 || mb_strlen($alasan) > 500) {
            throw new DomainException('Pilih kategori A/B/C dan jelaskan alasan koreksi (10–500 karakter).');
        }
        $this->db->transBegin();
        try {
            $tp = $this->db->query('SELECT id,status FROM tp WHERE id=? FOR UPDATE', [$tpId])->getRowArray();
            if ($tp === null || $tp['status'] !== 'siap') {
                throw new DomainException('TP sudah berubah. Muat ulang halaman.');
            }
            $this->guru($tpId); // G1 diperiksa ulang di dalam transaksi
            if ($this->db->table('permohonan_koreksi_tp')->where('tp_id', $tpId)
                ->whereIn('status', ['draf','diajukan'])->countAllResults() > 0) {
                throw new DomainException('Sudah ada permohonan koreksi terbuka untuk TP ini.');
            }
            $asal = $this->terbaru($tpId);
            $this->db->table('permohonan_koreksi_tp')->insert([
                'tp_id' => $tpId, 'versi_dasar_id' => (int) $asal['id'],
                'guru_id' => (int) $akun['id'], 'kategori' => $kategori,
                'alasan' => $alasan, 'status' => 'draf',
                'usulan_json' => (string) $asal['snapshot_json'],
                'checksum_dasar' => (string) $asal['checksum'],
                'dibuat_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $id = (int) $this->db->insertID();
            $this->catat((int) $akun['id'], 'guru', 'koreksi_tp_draf', 'TP #' . $tpId . ' permohonan #' . $id);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Gagal menyiapkan permohonan koreksi.');
            }
            $this->db->transCommit();
            return $id;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function ubah(int $id, callable $operasi): void
    {
        $row = $this->lihatGuru($id);
        if ($row['status'] !== 'draf') {
            throw new DomainException('Permohonan sudah dikirim dan tidak bisa disunting.');
        }
        $this->db->transBegin();
        try {
            $locked = $this->row($id, true);
            if ($locked['status'] !== 'draf' || $locked['checksum_dasar'] !== $row['checksum_dasar']
                || (int) $locked['guru_id'] !== (int) $row['guru_id']) {
                throw new DomainException('Permohonan telah berubah. Muat ulang halaman.');
            }
            $tp = $this->db->query('SELECT id,status FROM tp WHERE id=? FOR UPDATE',
                [(int) $locked['tp_id']])->getRowArray();
            if (!$tp || $tp['status'] !== 'siap') {
                throw new DomainException('TP bukan lagi Siap.');
            }
            $this->guru((int) $locked['tp_id']);
            $data = self::decode((string) $locked['usulan_json']);
            $operasi($data);
            $this->db->table('permohonan_koreksi_tp')->where('id', $id)->where('status', 'draf')
                ->update(['usulan_json' => self::encode($data)]);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Penyimpanan draf koreksi gagal.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** Form rincian TP dan tepat satu Materi. Media file lama tetap dipin. */
    public function simpanRincian(int $id, array $input): void
    {
        $this->ubah($id, static function (array &$data) use ($input): void {
            $judul = trim((string) ($input['judul'] ?? ''));
            $rumusan = trim((string) ($input['rumusan'] ?? ''));
            $judulMateri = trim((string) ($input['judul_materi'] ?? ''));
            $resume = trim((string) ($input['petunjuk_resume'] ?? ''));
            $hots = trim((string) ($input['petunjuk_hots'] ?? ''));
            $detik = filter_var($input['min_waktu_detik'] ?? null, FILTER_VALIDATE_INT);
            $kkm = KkmTpService::validasi((string) ($input['kkm'] ?? ''), false);
            if (mb_strlen($judul) < 3 || mb_strlen($judul) > 180
                || mb_strlen($rumusan) < 3 || mb_strlen($rumusan) > 20000
                || mb_strlen($judulMateri) < 3 || mb_strlen($judulMateri) > 180
                || mb_strlen($resume) > 20000 || mb_strlen($hots) > 20000
                || $detik === false || $detik < 1 || $detik > 65535
                || count($data['materi']) !== 1) {
                throw new DomainException('Rincian TP/Materi tidak sah. Periksa panjang teks, waktu, dan jumlah Materi.');
            }
            $m = &$data['materi'][0];
            if (($data['tp']['jenis_tp'] ?? '') === 'non_esensial' && ($resume === '' || $hots === '')) {
                throw new DomainException('Petunjuk Resume dan HOTS wajib diisi.');
            }
            $url = trim((string) ($input['url_youtube'] ?? ''));
            if ($m['jenis'] === 'youtube') {
                $host = parse_url($url, PHP_URL_HOST);
                if (filter_var($url, FILTER_VALIDATE_URL) === false
                    || !in_array(strtolower((string) $host), ['youtube.com','www.youtube.com','youtu.be','m.youtube.com'], true)
                    || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
                    throw new DomainException('Gunakan URL video YouTube HTTPS yang sah.');
                }
                $m['url_youtube'] = $url;
            }
            $data['tp']['judul'] = $judul;
            $data['tp']['rumusan'] = $rumusan;
            $data['tp']['kkm'] = $kkm;
            $m['judul'] = $judulMateri;
            $m['petunjuk_resume'] = $resume;
            $m['petunjuk_hots'] = $hots;
            $m['min_waktu_detik'] = (int) $detik;
        });
    }

    /** Unggah file koreksi ke penyimpanan privat TANPA mengganti media TP Siap. */
    public function unggahMedia(int $id, UploadedFile $file): void
    {
        $row = $this->lihatGuru($id);
        if ($row['status'] !== 'draf' || !$file->isValid() || $file->hasMoved()) {
            throw new DomainException('Draf atau unggahan media tidak sah.');
        }
        $materi = $row['usulan']['materi'][0] ?? null;
        $jenis = $materi['jenis'] ?? '';
        if (!in_array($jenis, ['pdf','audio','gambar'], true)) {
            throw new DomainException('Materi YouTube diperbaiki melalui kolom URL, bukan unggah berkas.');
        }
        $valid = BerkasMateriService::validasi($file->getTempName(), $jenis, $file->getClientName());
        $folder = rtrim(WRITEPATH, '/\\') . '/uploads/materi';
        if (!is_dir($folder) && !@mkdir($folder, 0700, true) && !is_dir($folder)) {
            throw new RuntimeException('Folder berkas Materi privat tidak bisa dibuat.');
        }
        $nama = bin2hex(random_bytes(24)) . '.' . $valid['ext'];
        $file->move($folder, $nama);
        $path = $folder . '/' . $nama;
        if (!is_file($path) || (int) filesize($path) !== (int) $valid['bytes']) {
            @unlink($path);
            throw new RuntimeException('Berkas koreksi tidak berhasil disimpan utuh.');
        }
        try {
            $this->ubah($id, function (array &$data) use ($row, $valid, $path, $nama): void {
                if (($data['materi'][0]['jenis'] ?? '') !== ($row['usulan']['materi'][0]['jenis'] ?? '')) {
                    throw new DomainException('Jenis Materi telah berubah.');
                }
                $this->db->table('berkas')->insert([
                    'tp_id' => (int) $row['tp_id'], 'nama_acak' => $nama,
                    'mime' => $valid['mime'], 'ukuran_byte' => $valid['bytes'],
                    'checksum' => hash_file('sha256', $path),
                    'halaman' => $valid['halaman'], 'durasi_detik' => $valid['durasi_detik'],
                    'dibuat_pada' => gmdate('Y-m-d H:i:s'),
                ]);
                $data['materi'][0]['berkas_id'] = (int) $this->db->insertID();
            });
        } catch (Throwable $e) {
            // File ini BELUM tercatat sebagai media terbit, boleh dibersihkan.
            @unlink($path);
            throw $e;
        }
    }

    /** Pratinjau media dari salinan koreksi tanpa mengakses file lewat URL publik. */
    public function mediaGuru(int $id): array
    {
        $row = $this->lihatGuru($id);
        $m = $row['usulan']['materi'][0] ?? null;
        if (!is_array($m) || !in_array($m['jenis'], ['pdf','audio','gambar'], true)
            || (int) ($m['berkas_id'] ?? 0) < 1) {
            throw new DomainException('Tidak ada berkas dalam usulan koreksi.');
        }
        $berkas = $this->db->table('berkas')->where('id', (int) $m['berkas_id'])
            ->where('tp_id', (int) $row['tp_id'])->get()->getRowArray();
        if ($berkas === null || !preg_match('/^[a-f0-9]{48}\.(?:pdf|jpg|png|webp|mp3|m4a)$/D',
            (string) $berkas['nama_acak'])) {
            throw new DomainException('Media privat koreksi tidak ditemukan.');
        }
        $path = rtrim(WRITEPATH, '/\\') . '/uploads/materi/' . $berkas['nama_acak'];
        if (!is_file($path) || !is_readable($path)
            || (int) filesize($path) !== (int) $berkas['ukuran_byte']
            || !hash_equals((string) $berkas['checksum'], hash_file('sha256', $path))) {
            throw new DomainException('Berkas media koreksi rusak atau tidak tersedia.');
        }
        return ['path' => $path, 'mime' => $berkas['mime'], 'bytes' => (int) $berkas['ukuran_byte']];
    }

    /** Sunting satu butir saja per POST agar tetap aman dari max_input_vars PHP. */
    public function simpanSoal(int $id, int $soalId, array $input): void
    {
        $this->ubah($id, static function (array &$data) use ($soalId, $input): void {
            if (!in_array($input['kunci'] ?? null, ['A','B','C','D','E'], true)) {
                throw new DomainException('Kunci jawaban harus A–E.');
            }
            foreach ($data['soal'] as &$item) {
                if ((int) ($item['id'] ?? 0) !== $soalId) {
                    continue;
                }
                $rich = json_decode((string) ($item['konten_terstruktur_json'] ?? ''), true);
                $hasRumus = is_array($rich) && (int) ($rich['konten']['rumus'] ?? 0) > 0;
                $item['kunci'] = $input['kunci'];
                foreach (['pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e'] as $field) {
                    $baru = trim((string) ($input[$field] ?? ''));
                    if ($baru === '' || mb_strlen($baru) > 10000) {
                        throw new DomainException('Pertanyaan dan pilihan A–E wajib diisi (maksimal 10.000 karakter).');
                    }
                    if ($hasRumus && $baru !== (string) $item[$field]) {
                        throw new DomainException('Soal ber-Equation Word tidak dapat diubah teksnya tanpa kehilangan rumus. Kunci jawaban masih boleh dikoreksi.');
                    }
                    if ($baru !== (string) $item[$field]) {
                        // Koreksi teks menjadi HTML aman. Sumber Word/media asli
                        // di snapshot versi sebelumnya tidak pernah diubah.
                        $html = nl2br(htmlspecialchars($baru, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                        if (!is_array($rich)) {
                            $rich = ['format' => 'SLA-SKS-RICH-V1'];
                        }
                        if ($field === 'pertanyaan') {
                            $rich['html_pertanyaan'] = $html;
                        } else {
                            $rich['html_opsi'][strtoupper(substr($field, -1))] = $html;
                        }
                        $rich['manual_edited'] = true;
                        $rich['origin'] = 'koreksi';
                        $item[$field] = $baru;
                    }
                }
                if (is_array($rich)) {
                    $item['konten_terstruktur_json'] = self::encode($rich);
                }
                return;
            }
            throw new DomainException('Soal bukan milik snapshot usulan ini.');
        });
    }

    private function periksaFileMateri(int $tpId, array $data): void
    {
        $m = $data['materi'][0] ?? [];
        if (!in_array($m['jenis'] ?? '', ['pdf','audio','gambar'], true)) {
            return;
        }
        $berkas = $this->db->table('berkas')->where('tp_id', $tpId)
            ->where('id', (int) ($m['berkas_id'] ?? 0))->get()->getRowArray();
        if (!$berkas) {
            throw new DomainException('Sumber file Materi usulan belum tersedia.');
        }
        $path = rtrim(WRITEPATH, '/\\') . '/uploads/materi/' . $berkas['nama_acak'];
        if (!is_file($path) || (int) filesize($path) !== (int) $berkas['ukuran_byte']
            || !hash_equals((string) $berkas['checksum'], hash_file('sha256', $path))) {
            throw new DomainException('Sumber file Materi usulan tidak utuh.');
        }
    }

    private static function periksa(array $data, array $asal): void
    {
        if (hash_equals(self::encode($data), self::encode($asal))) {
            throw new DomainException('Belum ada perubahan yang diajukan.');
        }
        $cek = KesiapanTpService::evaluasi(
            $data['tp'], $data['paket'], $data['cp'], $data['materi'], $data['soal']
        );
        $gagal = array_values(array_filter($cek, static fn (array $v): bool => !$v['terpenuhi']));
        if ($gagal !== []) {
            throw new DomainException('Usulan belum memenuhi kesiapan TP: ' . implode('; ',
                array_slice(array_column($gagal, 'label'), 0, 4)));
        }
    }

    public function kirim(int $id): void
    {
        $row = $this->lihatGuru($id);
        if ($row['status'] !== 'draf') {
            throw new DomainException('Permohonan ini bukan draf.');
        }
        $this->db->transBegin();
        try {
            $lock = $this->row($id, true);
            if ($lock['status'] !== 'draf') {
                throw new DomainException('Permohonan sudah diajukan.');
            }
            $this->guru((int) $lock['tp_id']);
            $asal = $this->terbaru((int) $lock['tp_id']);
            if ((int) $asal['id'] !== (int) $lock['versi_dasar_id']
                || !hash_equals((string) $lock['checksum_dasar'], (string) $asal['checksum'])) {
                throw new DomainException('Versi TP telah berubah. Buat permohonan berdasarkan versi terbaru.');
            }
            $usulan = self::decode((string) $lock['usulan_json']);
            $this->periksaFileMateri((int) $lock['tp_id'], $usulan);
            self::periksa($usulan, self::decode((string) $asal['snapshot_json']));
            $this->db->table('permohonan_koreksi_tp')->where('id', $id)->update([
                'status' => 'diajukan', 'diajukan_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->catat((int) $lock['guru_id'], 'guru', 'koreksi_tp_diajukan',
                'TP #' . $lock['tp_id'] . ' usulan #' . $id);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Gagal mengirim permohonan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function putuskan(int $id, bool $setujui, string $catatan): ?int
    {
        $admin = $this->akun('admin');
        $this->perluTabel();
        $catatan = trim($catatan);
        if (mb_strlen($catatan) < 8 || mb_strlen($catatan) > 500) {
            throw new DomainException('Isi alasan keputusan Admin (8–500 karakter).');
        }
        $this->db->transBegin();
        try {
            $row = $this->row($id, true);
            if ($row['status'] !== 'diajukan') {
                throw new DomainException('Permohonan tidak lagi menunggu keputusan.');
            }
            if ((int) $row['guru_id'] === (int) $admin['id']) {
                throw new DomainException('Akun pengusul Guru tidak boleh memutus permohonannya sendiri, baik setuju maupun tolak.');
            }
            $tpId = (int) $row['tp_id'];
            $tp = $this->db->query('SELECT * FROM tp WHERE id=? FOR UPDATE', [$tpId])->getRowArray();
            if (!$tp || $tp['status'] !== 'siap') {
                throw new DomainException('TP bukan lagi Siap.');
            }
            $asal = $this->terbaru($tpId);
            if ((int) $asal['id'] !== (int) $row['versi_dasar_id']
                || !hash_equals((string) $asal['checksum'], (string) $row['checksum_dasar'])) {
                throw new DomainException('Versi TP berubah; permohonan lama tidak boleh diterapkan.');
            }
            $waktu = gmdate('Y-m-d H:i:s');
            $versiId = null;
            if ($setujui) {
                $data = self::decode((string) $row['usulan_json']);
                $sebelum = self::decode((string) $asal['snapshot_json']);
                $this->periksaFileMateri($tpId, $data);
                self::periksa($data, $sebelum);
                $noVersi = (int) $asal['nomor_versi'] + 1;
                $data['tp']['struktur_versi'] = (int) $tp['struktur_versi'] + 1;
                $snapshot = self::encode($data);
                $this->db->table('versi_konten_tp')->insert([
                    'tp_id' => $tpId, 'nomor_versi' => $noVersi,
                    'snapshot_json' => $snapshot, 'checksum' => hash('sha256', $snapshot),
                    'disiapkan_oleh' => (int) $row['guru_id'], 'disiapkan_pada' => $waktu,
                ]);
                $versiId = (int) $this->db->insertID();

                // Soal yang berubah diganti dengan record BARU. Soal lama
                // tidak ditimpa dan tetap ber-UUID/versi historis.
                $soalAsal = [];
                foreach ($sebelum['soal'] as $s) {
                    $soalAsal[(int) $s['id']] = $s;
                }
                foreach ($data['soal'] as &$s) {
                    $idSoal = (int) $s['id'];
                    if (!isset($soalAsal[$idSoal])) {
                        throw new DomainException('Daftar soal usulan tidak cocok dengan versi dasar.');
                    }
                    if (self::encode($s) === self::encode($soalAsal[$idSoal])) {
                        continue;
                    }
                    $old = $this->db->table('soal')->where('id', $idSoal)
                        ->where('tp_id', $tpId)->get()->getRowArray();
                    if ($old === null || (int) $old['aktif'] !== 1) {
                        throw new DomainException('Soal sumber sudah berubah; hentikan koreksi.');
                    }
                    $max = $this->db->table('soal')->selectMax('urutan', 'maks')
                        ->where('tp_id', $tpId)->where('jenis', $old['jenis'])->get()->getRowArray();
                    $urutArsip = (int) ($max['maks'] ?? 0) + 1;
                    if ($urutArsip > 65535) {
                        throw new DomainException('Nomor urut arsip soal telah penuh.');
                    }
                    $this->db->table('soal')->where('id', $idSoal)
                        ->update(['urutan' => $urutArsip, 'aktif' => 0]);
                    $baru = array_intersect_key($s, array_flip([
                        'tp_id','jenis','urutan','pertanyaan','konten_terstruktur_json',
                        'opsi_a','opsi_b','opsi_c','opsi_d','opsi_e','kunci','aktif',
                    ]));
                    $baru['tp_id'] = $tpId;
                    $baru['row_uuid'] = bin2hex(random_bytes(16));
                    $baru['versi_konten_tp_id'] = $versiId;
                    $baru['aktif'] = 1;
                    $this->db->table('soal')->insert($baru);
                    $s['id'] = (int) $this->db->insertID();
                    $s['row_uuid'] = $baru['row_uuid'];
                    $s['versi_konten_tp_id'] = $versiId;
                }
                unset($s);
                // Materi memakai ID tetap. Siswa membaca isinya dari snapshot
                // versi TERPIN sehingga perubahan redaksi hanya berlaku untuk
                // siswa yang belum mulai. Media berkas lama tidak diganti.
                $m = $data['materi'][0];
                $this->db->table('materi')->where('id', (int) $m['id'])
                    ->where('tp_id', $tpId)->update([
                        'judul' => $m['judul'],
                        'petunjuk_resume' => $m['petunjuk_resume'],
                        'petunjuk_hots' => $m['petunjuk_hots'],
                        'min_waktu_detik' => (int) $m['min_waktu_detik'],
                        'url_youtube' => $m['url_youtube'],
                        'berkas_id' => $m['berkas_id'],
                    ]);
                $kkm = (int) $data['tp']['kkm'];
                $default = (new KkmTpService($this->db))->defaultAdmin();
                $this->db->table('tp')->where('id', $tpId)
                    ->where('status', 'siap')->where('struktur_versi', (int) $tp['struktur_versi'])
                    ->update([
                        'judul' => $data['tp']['judul'],
                        'rumusan' => $data['tp']['rumusan'],
                        'kkm' => $kkm,
                        'kkm_khusus' => KkmTpService::normalisasiKhusus($kkm, $default),
                        'struktur_versi' => (int) $tp['struktur_versi'] + 1,
                    ]);
                if ($this->db->affectedRows() !== 1) {
                    throw new DomainException('Versi struktur TP berubah pada saat koreksi.');
                }
                // Snapshot akhir memuat ID soal yang BENAR setelah versi baru.
                $data['tp']['kkm_khusus'] = KkmTpService::normalisasiKhusus($kkm, $default);
                $json = self::encode($data);
                $this->db->table('versi_konten_tp')->where('id', $versiId)->update([
                    'snapshot_json' => $json, 'checksum' => hash('sha256', $json),
                ]);
            }
            $this->db->table('permohonan_koreksi_tp')->where('id', $id)
                ->where('status', 'diajukan')->update([
                    'status' => $setujui ? 'disetujui' : 'ditolak',
                    'admin_id' => (int) $admin['id'],
                    'catatan_keputusan' => $catatan,
                    'versi_terbit_id' => $versiId,
                    'diputus_pada' => $waktu,
                ]);
            $this->catat((int) $admin['id'], 'admin',
                $setujui ? 'koreksi_tp_disetujui' : 'koreksi_tp_ditolak',
                'TP #' . $tpId . ' permohonan #' . $id . ($versiId ? ' versi #' . $versiId : ''));
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi keputusan koreksi gagal disimpan.');
            }
            $this->db->transCommit();
            return $versiId;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
