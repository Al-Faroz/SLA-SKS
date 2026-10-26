<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Kontrak final TP Siap v2.4. Semua pemeriksaan ulang dilakukan di bawah lock TP
 * sebelum snapshot dan status dipindahkan dalam satu transaksi.
 */
final class KesiapanTpService
{
    private BaseConnection $db;
    private DetailTpService $detail;

    public function __construct(?BaseConnection $db = null, ?callable $penggunaSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->detail = new DetailTpService($this->db, $penggunaSaatIni);
    }

    /**
     * @return array{tp:array,paket:array,materi:array,cp:array,soal:array,cek:array,siap:bool,versi_terbit:?array}
     */
    public function periksa(int $tpId): array
    {
        $context = $this->detail->lihat($tpId); // G1 berizin, juga untuk TP Siap
        $tp = $this->db->table('tp')->where('id', $tpId)->get()->getRowArray();
        $paket = $this->db->table('paket_semester')
            ->where('id', (int) $context['paket']['id'])->get()->getRowArray();
        if ($tp === null || $paket === null) {
            throw new DomainException('TP atau paket semester tidak tersedia.');
        }

        $cp = $this->db->table('tp_cp tc')
            ->select('tc.cp_versi_id,tc.utama,v.nomor_versi,v.status AS versi_status,' .
                'c.kode,c.kurikulum_id,c.mapel_id,f.kode AS fase', false)
            ->join('ref_cp_versi v', 'v.id=tc.cp_versi_id')
            ->join('ref_cp c', 'c.id=v.cp_id')
            ->join('ref_fase f', 'f.id=c.fase_id')
            ->where('tc.tp_id', $tpId)
            ->orderBy('tc.id', 'ASC')->get()->getResultArray();
        $materi = $this->db->table('materi')
            ->where('tp_id', $tpId)->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();
        $soal = $this->db->table('soal')->where('tp_id', $tpId)
            ->where('aktif', 1)->orderBy('jenis', 'ASC')->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')->get()->getResultArray();

        $cek = self::evaluasi($tp, $paket, $cp, $materi, $soal);
        // Pemeriksaan file fisik/URL dilakukan hanya jika detail media tercantum,
        // tanpa menjalankan validasi format PDF sekali lagi.
        foreach ($materi as $i => $item) {
            $nomor = $i + 1;
            $ada = true;
            if (in_array($item['jenis'], ['pdf', 'gambar', 'audio'], true)) {
                $berkas = $item['berkas_id'] === null ? null
                    : $this->db->table('berkas')->where('id', $item['berkas_id'])
                        ->where('tp_id', $tpId)->get()->getRowArray();
                if ($berkas === null || !preg_match('/^[a-f0-9]{48}\\.(pdf|jpg|png|webp|mp3|m4a)$/D', (string) ($berkas['nama_acak'] ?? ''))) {
                    $ada = false;
                } else {
                    $path = WRITEPATH . 'uploads/materi/' . $berkas['nama_acak'];
                    $ada = is_file($path) && is_readable($path)
                        && (int) filesize($path) === (int) $berkas['ukuran_byte'];
                }
            } elseif ($item['jenis'] === 'youtube' && trim((string) $item['url_youtube']) !== '') {
                try {
                    VideoMateriService::idVideo((string) $item['url_youtube']);
                } catch (DomainException) {
                    $ada = false;
                }
            }
            self::tambahCek($cek, 'media-' . $nomor, 'Media Materi ' . $nomor . ' dapat ditinjau',
                $ada, $ada ? 'Sumber media siap dibuka.' : 'Berkas tidak tersedia atau URL YouTube tidak sah.',
                'guru/tp/' . $tpId . '/materi/' . $item['id'] . '/edit');
        }

        // Dokumen Word privat dan gambar hasil editor tetap harus tersedia saat publikasi.
        $mediaSoalBaik = true;
        foreach ($soal as $item) {
            $json = (string) ($item['konten_terstruktur_json'] ?? '');
            if ($json === '') {
                continue;
            }
            $rich = json_decode($json, true);
            if (!is_array($rich)) {
                $mediaSoalBaik = false;
                break;
            }
            $word = $rich['word_source_id'] ?? null;
            if ($word !== null && (!is_string($word)
                || !preg_match('/^[0-9a-f]{32}$/D', $word)
                || !is_file(WRITEPATH . 'private/word_soal/' . $word . '.docx'))) {
                $mediaSoalBaik = false;
                break;
            }
            $html = (string) ($rich['html_pertanyaan'] ?? '')
                . implode('', is_array($rich['html_opsi'] ?? null) ? $rich['html_opsi'] : []);
            if (str_contains($html, 'word-image:') && $word === null) {
                $mediaSoalBaik = false;
                break;
            }
            if (preg_match_all('/soal-image:([0-9a-f]{32})/', $html, $matches)) {
                foreach ($matches[1] as $token) {
                    $folder = WRITEPATH . 'private/soal_media/' . $tpId . '/' . $token;
                    if (!is_file($folder . '.png') && !is_file($folder . '.jpg')) {
                        $mediaSoalBaik = false;
                        break 2;
                    }
                }
            }
        }
        self::tambahCek($cek, 'aset-soal', 'Sumber media bank soal',
            $mediaSoalBaik,
            $mediaSoalBaik ? 'Sumber gambar Word/editor tersedia.' : 'Satu atau lebih gambar/Word asal tidak ditemukan.',
            'guru/tp/' . $tpId . '/bank-soal');

        $versi = $this->db->table('versi_konten_tp')
            ->select('id,nomor_versi,checksum,disiapkan_pada,disiapkan_oleh')
            ->where('tp_id', $tpId)->orderBy('nomor_versi', 'DESC')->get(1)->getRowArray();
        return ['tp' => $tp, 'paket' => $paket, 'materi' => $materi, 'cp' => $cp,
            'soal' => $soal, 'cek' => $cek, 'siap' => !in_array(false, array_column($cek, 'terpenuhi'), true),
            'sidik_jari' => self::sidikJari($tp, $paket, $cp, $materi, $soal),
            'versi_terbit' => $versi];
    }

    /**
     * Mengikat konfirmasi pada konten yang benar-benar ditinjau Guru.
     * Token tidak memuat atau memperlihatkan kunci jawaban.
     */
    public static function sidikJari(array $tp, array $paket, array $cp, array $materi, array $soal): string
    {
        return hash('sha256', json_encode(
            [$tp, $paket, $cp, $materi, $soal],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ));
    }

    /** @return list<array{kode:string,label:string,terpenuhi:bool,keterangan:string,tautan:string}> */
    public static function evaluasi(array $tp, array $paket, array $cp, array $materi, array $soal): array
    {
        $id = (int) ($tp['id'] ?? 0);
        $detail = 'guru/tp/' . $id;
        $hasil = [];
        foreach (['kode_tp' => 'Kode TP', 'judul' => 'Judul TP',
            'rumusan' => 'Rumusan TP', 'indikator' => 'Indikator ketercapaian'] as $field => $label) {
            self::tambahCek($hasil, $field, $label,
                trim((string) ($tp[$field] ?? '')) !== '',
                'Periksa isian ' . $label . '.', $detail . '#rumusan-tp');
        }
        $utama = 0;
        $validCp = count($cp) > 0;
        foreach ($cp as $row) {
            $utama += (int) ($row['utama'] ?? 0) === 1 ? 1 : 0;
            if ($row['versi_status'] !== 'aktif' || ($row['fase'] ?? '') !== 'D'
                || (int) $row['mapel_id'] !== (int) $paket['mapel_id']
                || (int) $row['kurikulum_id'] !== (int) $paket['kurikulum_id']) {
                $validCp = false;
            }
        }
        self::tambahCek($hasil, 'paket', 'Paket semester berlaku',
            in_array($paket['status'] ?? '', ['draf', 'aktif'], true),
            'Paket semester arsip tidak boleh menerbitkan TP baru.',
            $detail . '#identitas-tp');
        self::tambahCek($hasil, 'cp', 'CP resmi aktif dan cocok', $validCp && $utama === 1,
            'Setiap CP harus aktif, Fase D, sesuai Mapel/Kurikulum, dengan tepat satu CP utama.',
            'guru/peta-atp');

        self::tambahCek($hasil, 'materi', 'Minimal satu Materi', count($materi) >= 1,
            'TP wajib memiliki paling sedikit satu Materi.', $detail . '#daftar-materi');
        foreach ($materi as $idx => $m) {
            $nomor = $idx + 1;
            $benar = (int) ($m['urutan'] ?? 0) === $nomor
                && trim((string) ($m['judul'] ?? '')) !== ''
                && trim((string) ($m['petunjuk_resume'] ?? '')) !== ''
                && trim((string) ($m['petunjuk_hots'] ?? '')) !== ''
                && (int) ($m['min_waktu_detik'] ?? 0) > 0
                && (in_array($m['jenis'] ?? '', ['pdf', 'audio', 'gambar'], true)
                    ? !empty($m['berkas_id']) : (($m['jenis'] ?? '') === 'youtube'
                        && trim((string) ($m['url_youtube'] ?? '')) !== ''));
            self::tambahCek($hasil, 'materi-' . $nomor, 'Materi ' . $nomor . ' lengkap',
                $benar, 'Periksa urutan, judul, media, waktu belajar, Resume dan HOTS.',
                $detail . '/materi/' . ($m['id'] ?? 0) . '/edit');
        }

        foreach ([
            ['kkm', 1, 100, 'KKM'],
            ['resume_min', 10, 1000, 'Minimal karakter Resume'],
            ['tugas_min', 50, 5000, 'Minimal karakter HOTS'],
            ['diag_durasi', 5, 180, 'Durasi Diagnostik'],
            ['sum_durasi', 5, 180, 'Durasi Sumatif'],
            ['diag_tayang', 10, 65535, 'Jumlah tayang Diagnostik'],
            ['sum_tayang', 5, 65535, 'Jumlah tayang Sumatif'],
            ['sum_maks_percobaan', 1, 255, 'Kuota Sumatif'],
        ] as [$name, $min, $max, $label]) {
            $val = (int) ($tp[$name] ?? 0);
            self::tambahCek($hasil, $name, $label, $val >= $min && $val <= $max,
                'Nilai ' . $label . ' harus berada dalam rentang ' . $min . '–' . $max . '.',
                $detail . '#kesiapan-tp');
        }
        $bobot = (int) ($tp['bobot_resume'] ?? -1) + (int) ($tp['bobot_tugas'] ?? -1)
            + (int) ($tp['bobot_sumatif'] ?? -1);
        self::tambahCek($hasil, 'bobot', 'Bobot penilaian',
            $bobot === 100 && (int) ($tp['bobot_sumatif'] ?? 0) > 0,
            'Jumlah bobot Resume, HOTS dan Sumatif harus 100%, Sumatif lebih dari nol.',
            $detail . '#kesiapan-tp');

        foreach (['diagnostik' => ['diag_tayang', 10], 'sumatif' => ['sum_tayang', 5]] as $jenis => [$field, $minimal]) {
            $bank = array_values(array_filter($soal, static fn (array $s): bool => $s['jenis'] === $jenis));
            $target = (int) ($tp[$field] ?? 0);
            $jumlah = count($bank);
            self::tambahCek($hasil, 'bank-' . $jenis, 'Jumlah soal ' . ucfirst($jenis),
                $target >= $minimal && $jumlah >= $minimal && $jumlah >= $target,
                $jumlah . ' aktif dari ' . $target . ' yang ditayangkan, minimal ' . $minimal . '.',
                $detail . '/bank-soal?jenis=' . $jenis);
            $nomor = [];
            $valid = true;
            foreach ($bank as $s) {
                $urutan = (int) ($s['urutan'] ?? 0);
                if ($urutan < 1 || isset($nomor[$urutan])
                    || !in_array($s['kunci'] ?? null, ['A', 'B', 'C', 'D', 'E'], true)) {
                    $valid = false;
                }
                $nomor[$urutan] = true;
                foreach (['pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e'] as $fieldSoal) {
                    if (trim((string) ($s[$fieldSoal] ?? '')) === '') {
                        $valid = false;
                    }
                }
            }
            self::tambahCek($hasil, 'valid-' . $jenis, 'Struktur soal ' . ucfirst($jenis),
                $valid, 'Soal aktif harus unik per urutan, A–E terisi dan satu kunci sah.',
                $detail . '/bank-soal?jenis=' . $jenis);
        }
        return $hasil;
    }

    /** @param list<array> $out */
    private static function tambahCek(array &$out, string $kode, string $label, bool $ok, string $pesan, string $url): void
    {
        $out[] = ['kode' => $kode, 'label' => $label, 'terpenuhi' => $ok,
            'keterangan' => $ok ? 'Memenuhi ketentuan.' : $pesan, 'tautan' => $url];
    }

    /** Publikasi tidak boleh dicoba tanpa tanda tinjau akademik dari Guru. */
    public function terbitkan(int $tpId, int $guruId, int $strukturVersi, bool $diperiksa, string $sidikJari): int
    {
        if (!$diperiksa || $guruId < 1 || $strukturVersi < 1
            || !preg_match('/^[a-f0-9]{64}$/D', $sidikJari)) {
            throw new DomainException('Periksa semua komponen dan centang konfirmasi akademik sebelum TP disiapkan.');
        }
        $this->db->transBegin();
        try {
            $lock = $this->db->query('SELECT id,status,struktur_versi FROM tp WHERE id=? FOR UPDATE', [$tpId])
                ->getRowArray();
            if ($lock === null || $lock['status'] !== 'draf'
                || (int) $lock['struktur_versi'] !== $strukturVersi) {
                throw new DomainException('TP sudah berubah atau Siap. Muat ulang pemeriksaan.');
            }
            $cek = $this->periksa($tpId); // G1 + readiness di dalam lock
            if ($cek['tp']['status'] !== 'draf' || !$cek['siap']) {
                throw new DomainException('Ada komponen TP yang belum lengkap. Perbaiki daftar pemeriksaan terlebih dahulu.');
            }
            if (!hash_equals($cek['sidik_jari'], $sidikJari)) {
                throw new DomainException('Konten TP berubah sejak pemeriksaan. Muat ulang, tinjau perubahannya, lalu konfirmasi kembali.');
            }
            if ($cek['versi_terbit'] !== null) {
                throw new DomainException('TP sudah memiliki versi publikasi; gunakan prosedur koreksi.');
            }
            $akun = (new AuthService())->penggunaSaatIni();
            if ($akun === null || (int) $akun['id'] !== $guruId || $akun['peran_aktif'] !== 'guru') {
                throw new DomainException('Konfirmasi harus dilakukan Guru pengampu yang sedang masuk.');
            }

            // Snapshot adalah keadaan efektif setelah diterbitkan, bukan draf sebelum
            // perubahan status. Semua Materi yang lolos readiness menjadi aktif.
            $snapshotTp = array_replace($cek['tp'], ['status' => 'siap']);
            $snapshotPaket = array_replace($cek['paket'], [
                'status' => $cek['paket']['status'] === 'draf' ? 'aktif' : $cek['paket']['status'],
            ]);
            $snapshotMateri = array_map(
                static fn (array $row): array => array_replace($row, ['aktif' => 1]),
                $cek['materi']
            );
            $snapshot = [
                'format' => 'SLA-SKS-TP-V1',
                'tp' => $snapshotTp, 'paket' => $snapshotPaket,
                'cp' => $cek['cp'], 'materi' => $snapshotMateri, 'soal' => $cek['soal'],
            ];
            $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $waktu = gmdate('Y-m-d H:i:s');
            $this->db->table('versi_konten_tp')->insert([
                'tp_id' => $tpId, 'nomor_versi' => 1, 'snapshot_json' => $json,
                'checksum' => hash('sha256', $json),
                'disiapkan_oleh' => $guruId, 'disiapkan_pada' => $waktu,
            ]);
            $versiId = (int) $this->db->insertID();
            if ($versiId < 1) {
                throw new RuntimeException('Snapshot TP gagal dicatat.');
            }
            // Soal yang dipakai perlu menunjuk snapshot agar koreksi berikutnya
            // tidak mengubah referensi ujian lama.
            $this->db->table('soal')->where('tp_id', $tpId)->where('aktif', 1)
                ->where('versi_konten_tp_id', null)->update(['versi_konten_tp_id' => $versiId]);
            // Materi yang lengkap disiapkan bersamaan dengan TP, bukan saat upload.
            $this->db->table('materi')->where('tp_id', $tpId)->update(['aktif' => 1]);
            $this->db->table('tp')->where('id', $tpId)->where('status', 'draf')
                ->where('struktur_versi', $strukturVersi)->update(['status' => 'siap']);
            if ($this->db->affectedRows() !== 1) {
                throw new DomainException('TP berubah selama publikasi. Muat ulang halaman.');
            }
            if ($cek['paket']['status'] === 'draf') {
                $this->db->table('paket_semester')->where('id', $cek['paket']['id'])
                    ->where('status', 'draf')->update(['status' => 'aktif']);
            }
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => $guruId, 'peran_aktif' => 'guru',
                'peristiwa' => 'tp_disiapkan',
                'catatan' => 'TP #' . $tpId . ' diterbitkan dalam versi ' . $versiId . '.',
                'waktu' => $waktu,
            ]);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi publikasi TP gagal.');
            }
            $this->db->transCommit();
            return $versiId;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
