<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use Throwable;

/**
 * CRUD terbatas Materi TP draf; tanpa penghapusan, publikasi atau unggahan berkas.
 * Seluruh operasi mengunci TP induk, menguji hak G1 dan versi struktur.
 */
final class MateriDrafService
{
    private BaseConnection $db;
    private DetailTpService $detail;

    /** @param (callable():?array)|null $penggunaSaatIni */
    public function __construct(?BaseConnection $db = null, ?callable $penggunaSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->detail = new DetailTpService($this->db, $penggunaSaatIni);
    }

    /** @return array{judul:string,jenis:string,petunjuk_resume:string,petunjuk_hots:string,min_waktu_detik:int,url_youtube:?string} */
    public function bersihkan(array $input): array
    {
        $judul = trim((string) ($input['judul'] ?? ''));
        $jenis = (string) ($input['jenis'] ?? '');
        $resume = trim((string) ($input['petunjuk_resume'] ?? ''));
        $hots = trim((string) ($input['petunjuk_hots'] ?? ''));
        $menit = $input['min_waktu_menit'] ?? null;
        $formatMenit = is_string($menit) && preg_match('/^(?:[0-9]{1,4})(?:\\.[0-9]{1,2})?$/D', $menit) === 1;
        $waktu = $formatMenit ? (int) round((float) $menit * 60) : -1;
        $url = trim((string) ($input['url_youtube'] ?? ''));

        if ($judul === '' || mb_strlen($judul) > 180
            || ! in_array($jenis, ['pdf', 'gambar', 'audio', 'youtube'], true)
            || $resume === '' || mb_strlen($resume) > 20000
            || $hots === '' || mb_strlen($hots) > 20000
            || ! $formatMenit || $waktu < 0 || $waktu > 65535) {
            throw new DomainException('Lengkapi judul, jenis media, petunjuk Resume/HOTS dan waktu yang valid.');
        }

        if ($jenis === 'youtube') {
            // URL tervalidasi dinormalisasi agar ID video tidak membawa parameter/tracker.
            $url = VideoMateriService::urlStandar($url);
        } elseif ($url !== '') {
            throw new DomainException('Tautan YouTube hanya boleh untuk materi berjenis YouTube.');
        }

        return [
            'judul' => $judul,
            'jenis' => $jenis,
            'petunjuk_resume' => $resume,
            'petunjuk_hots' => $hots,
            'min_waktu_detik' => (int) $waktu,
            'url_youtube' => $jenis === 'youtube' ? $url : null,
        ];
    }

    /**
     * Semua operasi di bawah memakai transaksi serta lock baris TP agar urutan,
     * status dan versi tidak berubah serentak dari tab lain.
     */
    private function ubah(int $tpId, int $versi, callable $aksi): void
    {
        if ($tpId < 1 || $versi < 1) {
            throw new DomainException('TP atau versi struktur tidak valid.');
        }

        $this->db->transBegin();
        try {
            $rincian = $this->detail->lihat($tpId);
            $baris = $this->db->query('SELECT id,status,struktur_versi FROM tp WHERE id = ? FOR UPDATE', [$tpId])->getRowArray();

            if ($baris === null || $baris['status'] !== 'draf') {
                throw new DomainException('TP Siap tidak dapat diubah melalui editor Materi.');
            }
            if ((int) $baris['struktur_versi'] !== $versi
                || (int) $rincian['tp']['struktur_versi'] !== $versi) {
                throw new DomainException('Struktur TP berubah. Muat ulang halaman sebelum menyimpan.');
            }

            $aksi();
            $this->db->table('tp')->where('id', $tpId)
                ->where('status', 'draf')->where('struktur_versi', $versi)
                ->update(['struktur_versi' => $versi + 1]);
            if ($this->db->affectedRows() !== 1 || $this->db->transStatus() === false) {
                throw new DomainException('Penyimpanan Materi gagal karena versi berubah.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function tambah(int $tpId, int $versi, array $input): void
    {
        $data = $this->bersihkan($input);
        $this->ubah($tpId, $versi, function () use ($tpId, $data): void {
            $akhir = $this->db->table('materi')->selectMax('urutan', 'maks')
                ->where('tp_id', $tpId)->get()->getRowArray();
            $nomor = (int) ($akhir['maks'] ?? 0) + 1;
            if ($nomor > 65535) {
                throw new DomainException('Urutan Materi melebihi kapasitas penyimpanan.');
            }
            $this->db->table('materi')->insert(array_merge($data, [
                'tp_id' => $tpId,
                'row_uuid' => bin2hex(random_bytes(16)),
                'urutan' => $nomor,
                // Materi baru tidak ditayangkan otomatis, termasuk tautan YouTube.
                'aktif' => 0,
                'berkas_id' => null,
            ]));
        });
    }

    public function simpan(int $tpId, int $materiId, int $versi, array $input): void
    {
        $data = $this->bersihkan($input);
        $this->ubah($tpId, $versi, function () use ($tpId, $materiId, $data): void {
            $lama = $this->db->table('materi')->where('id', $materiId)
                ->where('tp_id', $tpId)->get()->getRowArray();
            if ($lama === null) {
                throw new DomainException('Materi tidak ditemukan pada TP ini.');
            }
            if ($lama['jenis'] !== $data['jenis'] && $lama['berkas_id'] !== null) {
                throw new DomainException('Jenis media tidak boleh diubah ketika berkas sudah terhubung.');
            }
            if ($data['jenis'] === 'pdf' && $lama['jenis'] !== 'pdf') {
                throw new DomainException('Jenis PDF belum dapat dipilih sebelum unggah PDF didukung.');
            }
            if (in_array($data['jenis'], ['gambar', 'audio'], true) && $lama['berkas_id'] === null) {
                throw new DomainException('Pilih file gambar atau audio sebelum menyimpan perubahan Materi.');
            }
            $this->db->table('materi')->where('id', $materiId)->where('tp_id', $tpId)
                ->update($data);
        });
    }

    public function geser(int $tpId, int $materiId, int $versi, string $arah): void
    {
        if (! in_array($arah, ['naik', 'turun'], true)) {
            throw new DomainException('Arah pengurutan tidak valid.');
        }
        $this->ubah($tpId, $versi, function () use ($tpId, $materiId, $arah): void {
            $daftar = $this->db->table('materi')->select('id,urutan')->where('tp_id', $tpId)
                ->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
            $posisi = null;
            foreach ($daftar as $k => $item) {
                if ((int) $item['id'] === $materiId) {
                    $posisi = $k;
                    break;
                }
            }
            if ($posisi === null) {
                throw new DomainException('Materi tidak ditemukan pada TP ini.');
            }
            $tujuan = $posisi + ($arah === 'naik' ? -1 : 1);
            if (! isset($daftar[$tujuan])) {
                throw new DomainException('Materi sudah berada pada urutan paling ujung.');
            }
            // UNIQUE(tp_id,urutan) berlaku pada skema v2.4: gunakan nomor 0
            // sementara di dalam transaksi untuk menghindari benturan saat swap.
            if ((int) $daftar[$posisi]['urutan'] < 1 || (int) $daftar[$tujuan]['urutan'] < 1
                || $this->db->table('materi')->where('tp_id', $tpId)->where('urutan', 0)->countAllResults() !== 0) {
                throw new DomainException('Urutan Materi tidak konsisten. Periksa data terlebih dahulu.');
            }
            $this->db->table('materi')->where('id', $materiId)->where('tp_id', $tpId)
                ->update(['urutan' => 0]);
            $this->db->table('materi')->where('id', $daftar[$tujuan]['id'])->where('tp_id', $tpId)
                ->update(['urutan' => $daftar[$posisi]['urutan']]);
            $this->db->table('materi')->where('id', $materiId)->where('tp_id', $tpId)
                ->update(['urutan' => $daftar[$tujuan]['urutan']]);
        });
    }
}
