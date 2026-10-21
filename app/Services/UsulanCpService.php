<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/** K3B: keputusan akademik harus diputus Admin yang bukan pengusul Guru. */
final class UsulanCpService
{
    public function __construct(private readonly AkademikGuruContext $ctx = new AkademikGuruContext())
    {
    }

    /** @return list<array<string,mixed>> */
    public function cpUntukGuru(int $mapel): array
    {
        $guru = $this->ctx->guru();
        if ($this->ctx->db->table('penugasan_guru_gen')
            ->where('guru_id', (int) $guru['id'])->where('mapel_id', $mapel)
            ->countAllResults() === 0) {
            throw new DomainException('Mapel di luar penugasan Guru.');
        }
        return $this->ctx->db->table('ref_cp c')
            ->select('c.id,c.kode,c.kurikulum_id,v.id AS versi_id,v.elemen,v.rumusan,v.otoritas,v.nomor_dokumen,v.sumber_url,v.tanggal_berlaku')
            ->join('ref_cp_versi v', "v.cp_id=c.id AND v.status='aktif'")
            ->where('c.mapel_id', $mapel)->where('c.fase_id', 1)
            ->orderBy('c.kode')->get()->getResultArray();
    }

    /** @return list<array<string,mixed>> */
    public function daftarGuru(): array
    {
        $guru = $this->ctx->guru();
        return $this->ctx->db->table('usulan_cp u')
            ->select('u.id,u.jenis,u.status,u.alasan,u.catatan_keputusan,u.dibuat_pada,c.kode,m.nama AS mapel_nama')
            ->join('ref_cp c', 'c.id=u.cp_id')->join('ref_mapel m', 'm.id=c.mapel_id')
            ->where('u.pengusul_id', (int) $guru['id'])
            ->orderBy('u.id', 'DESC')->get(100)->getResultArray();
    }

    public function ajukan(int $cpId, string $jenis, string $alasan, array $form): void
    {
        $guru = $this->ctx->guru();
        $alasan = trim($alasan);
        if (! in_array($jenis, ['tambahan_rujukan','koreksi_transkripsi','catatan_lokal'], true)
            || mb_strlen($alasan) < 15 || mb_strlen($alasan) > 2000) {
            throw new DomainException('Pilih jenis usulan dan tulis alasan 15–2000 karakter.');
        }
        $this->ctx->transaksi(function () use ($cpId, $jenis, $alasan, $form, $guru): void {
            $cp = $this->ctx->db->table('ref_cp c')
                ->select('c.mapel_id,c.fase_id')
                ->where('c.id', $cpId)->get()->getRowArray();
            if ($cp === null || (int) $cp['fase_id'] !== 1 ||
                $this->ctx->db->table('penugasan_guru_gen')
                    ->where('mapel_id', (int) $cp['mapel_id'])
                    ->where('guru_id', (int) $guru['id'])->countAllResults() < 1) {
                throw new DomainException('CP tidak sesuai mapel aktif Guru.');
            }
            $v = $this->ctx->db->table('ref_cp_versi')
                ->where('cp_id', $cpId)->where('status', 'aktif')->get()->getRowArray();
            if ($v === null) {
                throw new DomainException('CP belum memiliki versi resmi aktif.');
            }
            $data = [];
            foreach (['elemen','rumusan','otoritas','nomor_dokumen','sumber_url','tanggal_berlaku'] as $key) {
                $data[$key] = trim((string) ($form[$key] ?? ($v[$key] ?? '')));
            }
            if ($jenis === 'catatan_lokal') {
                // Catatan lokal bukan naskah resmi pengganti. Simpan metadata versi asal.
                foreach ($data as $k => $_) {
                    $data[$k] = (string) $v[$k];
                }
            } elseif (! CpReferensiService::versiValid($data)) {
                throw new DomainException('Rumusan/sumber CP yang diusulkan belum lengkap atau tidak sah.');
            }
            $this->ctx->db->table('usulan_cp')->insert($data + [
                'cp_id' => $cpId, 'versi_asal_id' => $v['id'],
                'pengusul_id' => (int) $guru['id'], 'jenis' => $jenis,
                'alasan' => $alasan, 'status' => 'diajukan',
                'dibuat_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->ctx->catat((int) $guru['id'], 'guru', 'cp_usulan_diajukan',
                'usulan_cp', (int) $this->ctx->db->insertID());
        });
    }

    /** @return list<array<string,mixed>> */
    public function daftarAdmin(): array
    {
        $this->ctx->admin();
        return $this->ctx->db->table('usulan_cp u')
            ->select('u.*,c.kode,p.nama AS pengusul_nama,m.nama AS mapel_nama')
            ->join('ref_cp c', 'c.id=u.cp_id')
            ->join('pengguna p', 'p.id=u.pengusul_id')
            ->join('ref_mapel m', 'm.id=c.mapel_id')
            ->orderBy('u.status', 'DESC')->orderBy('u.id', 'DESC')
            ->get(100)->getResultArray();
    }

    /** @return array<string,mixed> */
    public function detailAdmin(int $id): array
    {
        $this->ctx->admin();
        $u = $this->ctx->db->table('usulan_cp u')
            ->select('u.*,c.kode,c.mapel_id,v.nomor_versi AS nomor_asal,v.rumusan AS rumusan_asal,p.nama AS pengusul_nama,m.nama AS mapel_nama')
            ->join('ref_cp c', 'c.id=u.cp_id')
            ->join('ref_cp_versi v', 'v.id=u.versi_asal_id')
            ->join('pengguna p', 'p.id=u.pengusul_id')
            ->join('ref_mapel m', 'm.id=c.mapel_id')
            ->where('u.id', $id)->get()->getRowArray();
        if ($u === null) {
            throw new DomainException('Usulan CP tidak ditemukan.');
        }
        return $u;
    }

    public function putuskan(int $id, string $keputusan, string $catatan): void
    {
        $admin = $this->ctx->admin();
        $catatan = trim($catatan);
        if (! in_array($keputusan, ['setujui','tolak','kembalikan_perbaikan'], true)
            || mb_strlen($catatan) < 12 || mb_strlen($catatan) > 2000) {
            throw new DomainException('Pilih keputusan dan tulis alasan 12–2000 karakter.');
        }
        $this->ctx->transaksi(function () use ($id,$keputusan,$catatan,$admin): void {
            $u = $this->ctx->db->query('SELECT * FROM usulan_cp WHERE id=? FOR UPDATE', [$id])->getRowArray();
            if ($u === null || $u['status'] !== 'diajukan') {
                throw new DomainException('Usulan CP sudah diputus atau tidak ditemukan.');
            }
            if ((int) $u['pengusul_id'] === (int) $admin['id']) {
                throw new DomainException('Usulan ini diajukan oleh akun Admin+Guru yang sama. Anda tidak boleh menyetujui atau memutus permohonan akademik sendiri; minta Admin lain.');
            }
            $data = [
                'status' => match ($keputusan) {
                    'setujui' => 'disetujui',
                    'tolak' => 'ditolak',
                    default => 'dikembalikan',
                },
                'catatan_keputusan' => $catatan,
                'pemutus_id' => (int) $admin['id'],
                'diputus_pada' => gmdate('Y-m-d H:i:s'),
            ];
            if ($keputusan === 'setujui' && $u['jenis'] !== 'catatan_lokal') {
                $this->ctx->db->query('SELECT id FROM ref_cp WHERE id=? FOR UPDATE', [(int) $u['cp_id']]);
                $asal = $this->ctx->db->table('ref_cp_versi')
                    ->where('cp_id', (int) $u['cp_id'])->where('status', 'aktif')
                    ->get()->getRowArray();
                if ($asal === null || (int) $asal['id'] !== (int) $u['versi_asal_id']) {
                    throw new DomainException('Versi resmi CP telah berubah sejak usulan. Tolak dan minta usulan baru sesuai versi kini.');
                }
                if ($this->ctx->db->table('ref_cp_versi')
                    ->where('cp_id', (int) $u['cp_id'])->where('status', 'draf')->countAllResults() > 0) {
                    throw new DomainException('Ada draf Admin pada CP yang sama. Terbitkan atau selesaikan draf tersebut dahulu.');
                }
                $next = (int) $asal['nomor_versi'] + 1;
                $last = $this->ctx->db->table('ref_cp_versi')->selectMax('nomor_versi', 'maks')
                    ->where('cp_id', (int) $u['cp_id'])->get()->getRowArray();
                $next = max($next, (int) ($last['maks'] ?? 0) + 1);
                $this->ctx->db->table('ref_cp_versi')->where('id', $asal['id'])
                    ->update(['status' => 'diarsipkan']);
                $versi = [
                    'cp_id' => $u['cp_id'], 'nomor_versi' => $next,
                    'status' => 'aktif', 'dibuat_oleh' => $u['pengusul_id'],
                    'dibuat_pada' => gmdate('Y-m-d H:i:s'),
                    'diterbitkan_oleh' => $admin['id'],
                    'diterbitkan_pada' => gmdate('Y-m-d H:i:s'),
                ];
                foreach (['elemen','rumusan','otoritas','nomor_dokumen','sumber_url','tanggal_berlaku'] as $key) {
                    $versi[$key] = $u[$key];
                }
                $this->ctx->db->table('ref_cp_versi')->insert($versi);
                $data['versi_hasil_id'] = (int) $this->ctx->db->insertID();
            }
            $this->ctx->db->table('usulan_cp')->where('id', $id)->update($data);
            $this->ctx->catat((int) $admin['id'], 'admin',
                'cp_usulan_' . $data['status'], 'usulan_cp', $id);
        });
    }
}
