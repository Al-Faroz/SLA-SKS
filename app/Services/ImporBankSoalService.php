<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;

/**
 * Tahap Word bank: upload privat → review persisten → konfirmasi transaksional.
 * Tidak menghapus soal lama yang tidak ada pada berkas baru.
 */
final class ImporBankSoalService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private static function folder(): string
    {
        $dir = WRITEPATH . 'private/word_soal/';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Penyimpanan Word privat tidak tersedia.');
        }
        if (!is_file($dir . '.htaccess')
            && @file_put_contents($dir . '.htaccess', "Require all denied\nDeny from all\n", LOCK_EX) === false) {
            throw new RuntimeException('Proteksi penyimpanan Word tidak dapat dipasang.');
        }
        return $dir;
    }

    private static function lokasi(string $id): string
    {
        if (!preg_match('/^[0-9a-f]{32}$/D', $id)) {
            throw new DomainException('ID pratinjau tidak valid.');
        }
        return self::folder() . $id . '.docx';
    }

    /**
     * Bersihkan unggahan belum dikonfirmasi yang sudah kedaluwarsa.
     * Word yang telah diterapkan tidak dihapus karena merupakan sumber aset historis.
     */
    private function bersihkanPratinjau(): void
    {
        $rows = $this->db->table('impor_soal_docx')
            ->select('id')->where('status', 'pratinjau')
            ->where('kedaluwarsa_pada <', date('Y-m-d H:i:s'))
            ->limit(50)->get()->getResultArray();
        foreach ($rows as $row) {
            $this->db->table('impor_soal_docx')->where('id', $row['id'])
                ->where('status', 'pratinjau')->update(['status' => 'kedaluwarsa']);
            if ($this->db->affectedRows() === 1) {
                @unlink(self::lokasi((string) $row['id']));
            }
        }
    }

    /** @return array{id:string,jumlah:int} */
    public function unggah(string $file, int $tpId, int $guruId, string $jenis): array
    {
        $this->bersihkanPratinjau();
        $rincian = (new DetailTpService($this->db))->lihat($tpId);
        if ($rincian['tp']['status'] !== 'draf' || !in_array($jenis, ['diagnostik', 'sumatif'], true)) {
            throw new DomainException('Bank soal hanya bisa diunggah pada TP draf.');
        }
        $versi = (int) $rincian['tp']['struktur_versi'];
        $parsed = PratinjauWordSoalService::periksa(
            $file, $tpId, $guruId, (int) $rincian['paket']['id'],
            $versi, $jenis, KunciTemplateWordService::ambil()
        );
        $id = bin2hex(random_bytes(16));
        $loc = self::lokasi($id);
        if (!copy($file, $loc)) {
            throw new RuntimeException('Word gagal disimpan di penyimpanan privat.');
        }
        @chmod($loc, 0600);
        try {
            $digest = hash_file('sha256', $loc);
            if (!is_string($digest)) {
                throw new RuntimeException('Checksum berkas gagal dihitung.');
            }
            $this->db->table('impor_soal_docx')->insert([
                'id' => $id, 'tp_id' => $tpId, 'guru_id' => $guruId,
                'jenis' => $jenis, 'versi_template' => 'SLA-SKS-WORD-V1',
                'checksum' => $digest, 'status' => 'pratinjau',
                'data_json' => json_encode([
                    'struktur_versi' => $versi, 'jumlah' => $parsed['jumlah'],
                ], JSON_THROW_ON_ERROR),
                'dibuat_pada' => date('Y-m-d H:i:s'),
                'kedaluwarsa_pada' => date('Y-m-d H:i:s', time() + 7200),
            ]);
            if ($this->db->affectedRows() !== 1) {
                throw new RuntimeException('Riwayat pratinjau gagal dicatat.');
            }
            return ['id' => $id, 'jumlah' => $parsed['jumlah']];
        } catch (\Throwable $e) {
            @unlink($loc);
            throw $e;
        }
    }

    /** @return array{row:array<string,mixed>,hasil:array<string,mixed>,path:string} */
    public function buka(int $tpId, int $guruId, string $id, bool $harusAktif = true): array
    {
        $rincian = (new DetailTpService($this->db))->lihat($tpId);
        $row = $this->db->table('impor_soal_docx')
            ->where('id', $id)->where('tp_id', $tpId)->where('guru_id', $guruId)
            ->get()->getRowArray();
        if ($row === null) {
            throw new DomainException('Pratinjau tidak ditemukan atau bukan milik Anda.');
        }
        if ($harusAktif && ($row['status'] !== 'pratinjau'
            || strtotime((string) $row['kedaluwarsa_pada']) < time())) {
            throw new DomainException('Pratinjau sudah kedaluwarsa atau telah diterapkan.');
        }
        if ($rincian['tp']['status'] !== 'draf' && $row['status'] !== 'diterapkan') {
            throw new DomainException('TP sudah terkunci.');
        }
        $path = self::lokasi($id);
        if (!is_file($path) || !hash_equals((string) $row['checksum'], (string) hash_file('sha256', $path))) {
            throw new DomainException('Berkas sumber Word tidak ditemukan atau berubah.');
        }
        $meta = json_decode((string) $row['data_json'], true, 8, JSON_THROW_ON_ERROR);
        if ($row['status'] === 'pratinjau'
            && (int) ($meta['struktur_versi'] ?? 0) !== (int) $rincian['tp']['struktur_versi']) {
            throw new DomainException('TP telah berubah. Unggah ulang Word terbaru.');
        }
        $hasil = PratinjauWordSoalService::periksa(
            $path, $tpId, $guruId, (int) $rincian['paket']['id'],
            (int) ($meta['struktur_versi'] ?? 0), (string) $row['jenis'],
            KunciTemplateWordService::ambil()
        );
        return ['row' => $row, 'hasil' => $hasil, 'path' => $path];
    }

    /** @return array{jumlah:int,baru:int,diubah:int,status:string} */
    public function konfirmasi(int $tpId, int $guruId, string $id): array
    {
        $rincian = $this->buka($tpId, $guruId, $id, false);
        if ($rincian['row']['status'] === 'diterapkan') {
            return ['jumlah' => (int) $rincian['hasil']['jumlah'], 'baru' => 0,
                'diubah' => 0, 'status' => 'sudah_diterapkan'];
        }
        if ($rincian['row']['status'] !== 'pratinjau'
            || strtotime((string) $rincian['row']['kedaluwarsa_pada']) < time()) {
            throw new DomainException('Pratinjau kedaluwarsa. Unggah ulang.');
        }

        // HTML dari renderer OOXML tepercaya; gambar dinyatakan sebagai token rId,
        // bukan data:image yang akan membuat database membengkak.
        $parsed = RenderWordSoalService::hasil(
            $rincian['path'], $rincian['hasil'],
            static fn (string $rid): string => 'word-image:' . $rid
        );
        $this->db->transBegin();
        try {
            $tp = $this->db->query('SELECT id,status,struktur_versi FROM tp WHERE id = ? FOR UPDATE', [$tpId])->getRowArray();
            $stage = $this->db->query('SELECT id,status,checksum,kedaluwarsa_pada FROM impor_soal_docx WHERE id = ? AND tp_id = ? AND guru_id = ? FOR UPDATE',
                [$id, $tpId, $guruId])->getRowArray();
            if ($tp === null || $stage === null || $tp['status'] !== 'draf'
                || (int) $tp['struktur_versi'] !== (int) json_decode((string) $rincian['row']['data_json'], true)['struktur_versi']
                || $stage['status'] !== 'pratinjau' || strtotime((string) $stage['kedaluwarsa_pada']) < time()
                || !hash_equals((string) $stage['checksum'], (string) hash_file('sha256', $rincian['path']))) {
                throw new DomainException('Data TP/Word sudah berubah. Ulangi proses review.');
            }

            $jenis = (string) $rincian['row']['jenis'];
            $terakhir = $this->db->table('impor_soal_docx')
                ->select('id,checksum')->where('tp_id', $tpId)->where('jenis', $jenis)
                ->where('status', 'diterapkan')->orderBy('diterapkan_pada', 'DESC')
                ->get(1)->getRowArray();
            if ($terakhir !== null && hash_equals((string) $terakhir['checksum'], (string) $stage['checksum'])) {
                // Impor identik terakhir: konfirmasi ulang tidak mengubah soal.
                $this->db->table('impor_soal_docx')->where('id', $id)->where('status', 'pratinjau')
                    ->update(['status' => 'diterapkan', 'diterapkan_pada' => date('Y-m-d H:i:s')]);
                if ($this->db->affectedRows() !== 1 || $this->db->transStatus() === false) {
                    throw new RuntimeException('Penyimpanan konfirmasi identik gagal.');
                }
                $this->db->transCommit();
                return ['jumlah' => count($parsed['soal']), 'baru' => 0,
                    'diubah' => 0, 'status' => 'tidak_berubah'];
            }
            $baru = 0;
            $diubah = 0;
            foreach ($parsed['soal'] as $soal) {
                $nomor = (int) $soal['nomor'];
                $existing = $this->db->table('soal')
                    ->where('tp_id', $tpId)->where('jenis', $jenis)
                    ->where('urutan', $nomor)->get()->getRowArray();
                $opsi = $soal['opsi'];
                $record = [
                    'pertanyaan' => $soal['pertanyaan'] !== '' ? $soal['pertanyaan'] : '[Konten kaya dalam dokumen Word]',
                    'opsi_a' => $opsi['A'] !== '' ? $opsi['A'] : '[Konten kaya]',
                    'opsi_b' => $opsi['B'] !== '' ? $opsi['B'] : '[Konten kaya]',
                    'opsi_c' => $opsi['C'] !== '' ? $opsi['C'] : '[Konten kaya]',
                    'opsi_d' => $opsi['D'] !== '' ? $opsi['D'] : '[Konten kaya]',
                    'opsi_e' => $opsi['E'] !== '' ? $opsi['E'] : '[Konten kaya]',
                    'kunci' => $soal['kunci'], 'aktif' => 1,
                    'konten_terstruktur_json' => json_encode([
                        'format' => 'SLA-SKS-RICH-V1', 'word_source_id' => $id,
                        'html_pertanyaan' => $soal['html_pertanyaan'], 'html_opsi' => $soal['html_opsi'],
                        'konten' => $soal['konten'],
                    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ];
                if ($existing !== null) {
                    $this->db->table('soal')->where('id', (int) $existing['id'])->update($record);
                    ++$diubah;
                } else {
                    $record += ['tp_id' => $tpId, 'jenis' => $jenis, 'urutan' => $nomor,
                        'row_uuid' => bin2hex(random_bytes(16))];
                    $this->db->table('soal')->insert($record);
                    ++$baru;
                }
            }
            $this->db->table('impor_soal_docx')->where('id', $id)->where('status', 'pratinjau')
                ->update(['status' => 'diterapkan', 'diterapkan_pada' => date('Y-m-d H:i:s')]);
            if ($this->db->affectedRows() !== 1 || $this->db->transStatus() === false) {
                throw new RuntimeException('Konfirmasi bank soal gagal.');
            }
            $this->db->transCommit();
            return ['jumlah' => count($parsed['soal']), 'baru' => $baru,
                'diubah' => $diubah, 'status' => 'diterapkan'];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function gambar(int $tpId, int $guruId, string $id, string $rid): array
    {
        $rincian = $this->buka($tpId, $guruId, $id, false);
        if ($rincian['row']['status'] !== 'diterapkan') {
            throw new DomainException('Aset belum dikonfirmasi.');
        }
        return RenderWordSoalService::gambar($rincian['path'], $rid);
    }
}
