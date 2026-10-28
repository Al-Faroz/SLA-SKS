-- SLA-SKS Master v2.5 | V4-0002 | Revisi Elemen CP SKI: Pemahaman Konsep
-- BASIS AUDIT: dump sla_sks_v4 28-10-2026 02:45; MariaDB 10.4.32, skema V4-0001-R2.
-- IMPOR SEKALI pada database sla_sks_v4 yang sudah dicadangkan lewat phpMyAdmin.
-- Tanpa DROP, DELETE, TRUNCATE, reset CP/TP/hasil ujian, atau perubahan .env.
-- KONTEKS: satu CP operasional cp_mapel untuk SKI; elemen lain Keterampilan Proses tetap aktif.
-- Teks rumusan adalah revisi persis yang diberikan pemilik proyek pada 28-10-2026.
-- Otoritas/sumber URL/tanggal berlaku diwarisi dari SKI Pemahaman Konsep lama
-- (SK Dirjen Pendis 9941 Tahun 2025). KMA 1503/2025 tetap identitas kurikulum.
-- Periksa kesesuaian metadata sumber resmi secara manual sebelum penggunaan luas.

SET NAMES utf8mb4;
START TRANSACTION;

SET @ski_mapel_id = (SELECT id FROM ref_mapel WHERE kode='SKI' LIMIT 1);
SET @ski_kurikulum_id = (SELECT id FROM ref_kurikulum
    WHERE nama='Kurikulum Berbasis Cinta (KBC)'
      AND versi='Kurikulum Madrasah 2025 (KMA 1503/2025)' LIMIT 1);
SET @fase_d_id = (SELECT id FROM ref_fase WHERE kode='D' LIMIT 1);
SET @ski_cp_mapel_id = (SELECT id FROM cp_mapel
    WHERE kurikulum_id=@ski_kurikulum_id AND mapel_id=@ski_mapel_id
      AND fase_id=@fase_d_id LIMIT 1);
SET @ski_cp_pk_lama_id = (SELECT id FROM ref_cp
    WHERE kurikulum_id=@ski_kurikulum_id AND mapel_id=@ski_mapel_id
      AND fase_id=@fase_d_id AND kode='FD-SKI-01' LIMIT 1);
SET @ski_pk_versi_lama_id = (SELECT id FROM ref_cp_versi
    WHERE cp_id=@ski_cp_pk_lama_id AND elemen='Pemahaman Konsep'
      AND status='aktif' LIMIT 1);

-- STOP jika dump sudah berubah/versi patch sudah terpasang.
-- Menulis NULL ke schema_version.versi (NOT NULL) sengaja memicu galat jika
-- prasyarat tidak terpenuhi; jangan jalankan pernyataan setelah galat.
SET @ski_patch_sesuai = (
    (SELECT COUNT(*) FROM schema_version WHERE versi='V4-0001-R2') = 1
    AND (SELECT COUNT(*) FROM schema_version WHERE versi='V4-0002-CP-SKI-PK') = 0
    AND @ski_mapel_id IS NOT NULL AND @ski_kurikulum_id IS NOT NULL
    AND @fase_d_id IS NOT NULL AND @ski_cp_mapel_id IS NOT NULL
    AND @ski_cp_pk_lama_id IS NOT NULL AND @ski_pk_versi_lama_id IS NOT NULL
    AND (SELECT COUNT(*) FROM cp_mapel_elemen
         WHERE cp_mapel_id=@ski_cp_mapel_id
           AND cp_versi_id=@ski_pk_versi_lama_id) = 1
    AND (SELECT COUNT(*) FROM ref_cp
         WHERE kurikulum_id=@ski_kurikulum_id
           AND mapel_id=@ski_mapel_id AND fase_id=@fase_d_id
           AND kode='KBC25-SKI-D-PK') = 0
);
INSERT INTO schema_version (versi)
VALUES (CASE WHEN @ski_patch_sesuai THEN 'V4-0002-CP-SKI-PK' ELSE NULL END);

-- Buat kode CP baru sebagai identitas historis tersendiri; KODE CP lama
-- tidak diubah agar tp_cp dan versi konten lama tetap dapat ditelusuri.
INSERT INTO ref_cp
    (kurikulum_id,mapel_id,fase_id,kode,dibuat_oleh,dibuat_pada)
SELECT @ski_kurikulum_id,@ski_mapel_id,@fase_d_id,
       'KBC25-SKI-D-PK',r.dibuat_oleh,NOW()
FROM ref_cp r WHERE r.id=@ski_cp_pk_lama_id;
SET @ski_cp_baru_id = LAST_INSERT_ID();

-- Sumber hukum CP yang telah ada dipertahankan sebagai metadata;
-- rumusan ini berasal dari pemilik dan tidak diparafrasekan ulang AI.
INSERT INTO ref_cp_versi
    (cp_id,nomor_versi,elemen,rumusan,otoritas,nomor_dokumen,
     sumber_url,tanggal_berlaku,status,dibuat_oleh,dibuat_pada,
     diterbitkan_oleh,diterbitkan_pada)
SELECT @ski_cp_baru_id,1,'Pemahaman Konsep',
'Pada akhir Fase D, murid mampu :
1. Merefleksikan perjalanan dakwah Rasulullah saw. di Makkah yang menumbuhkan nilai rahmatan lil ''alamin dan peran dakwah beliau dalam membangun peradaban masyarakat Madinah; mengapresiasi pencapaian Khulafaurasyidin dalam perkembangan peradaban Islam;
2. Menganalisis perkembangan peradaban Islam pada masa Daulah Umayyah dan Abbasiyah;
3. Meneladani kontribusi ulama dan ilmuwan muslim dari kedua daulah tersebut;
4. Merefleksikan kepemimpinan Umar bin Abdul Aziz;
5. Menganalisis peradaban Islam pada masa Daulah Ayyubiyah dan mengambil keteladanan dari para ilmuwannya;
6. Menganalisis sejarah pertumbuhan serta perkembangan Islam di Indonesia;
7. Meneladani pendekatan dakwah Wali Sanga yang mengedepankan keharmonisan;
8. Menganalisis kontribusi pesantren dalam penyebaran Islam di Indonesia;
9. Menunjukkan tanggung jawab dan keterlibatan aktif dalam menghargai kearifan lokal;
10. Serta merefleksikan nilai keteladanan para pendiri organisasi kemasyarakatan Islam di Indonesia.',
v.otoritas,v.nomor_dokumen,v.sumber_url,v.tanggal_berlaku,
'aktif',r.dibuat_oleh,NOW(),r.dibuat_oleh,NOW()
FROM ref_cp_versi v
INNER JOIN ref_cp r ON r.id=v.cp_id
WHERE v.id=@ski_pk_versi_lama_id;
SET @ski_pk_versi_baru_id = LAST_INSERT_ID();

-- Nonaktifkan hanya elemen PK lama, JANGAN ubah Keterampilan Proses.
-- Baris referensi lama, tp_cp, versi_konten_tp, dan progres tetap disimpan.
UPDATE ref_cp_versi SET status='diarsipkan'
WHERE id=@ski_pk_versi_lama_id AND status='aktif';

-- Kaitkan versi baru ke CP operasional SKI yang sudah ada.
-- Tautan lama tidak dihapus demi keterlacakan histori.
INSERT INTO cp_mapel_elemen (cp_mapel_id,cp_versi_id)
VALUES (@ski_cp_mapel_id,@ski_pk_versi_baru_id);

INSERT INTO log_aktivitas
    (pengguna_id,peran_aktif,peristiwa,catatan,waktu)
SELECT r.dibuat_oleh,'admin','cp_ski_pk_v25_diterbitkan',
       CONCAT('CP SKI PK v2.5: ref_cp=',@ski_cp_baru_id,
              '; versi=',@ski_pk_versi_baru_id,
              '; versi lama arsip=',@ski_pk_versi_lama_id),
       NOW()
FROM ref_cp r WHERE r.id=@ski_cp_baru_id;

COMMIT;

-- QA setelah impor (baca-saja):
-- SELECT versi,diterapkan_pada FROM schema_version WHERE versi='V4-0002-CP-SKI-PK';
-- SELECT c.kode,v.nomor_versi,v.elemen,v.status,
--        LEFT(v.rumusan,120) AS awalan
--   FROM cp_mapel cm JOIN cp_mapel_elemen e ON e.cp_mapel_id=cm.id
--   JOIN ref_cp_versi v ON v.id=e.cp_versi_id
--   JOIN ref_cp c ON c.id=v.cp_id
--   JOIN ref_mapel m ON m.id=cm.mapel_id
--   WHERE m.kode='SKI' ORDER BY v.id;
-- Hasil yang diharapkan: PK baru aktif, PK lama diarsipkan,
-- Keterampilan Proses tetap aktif, tanpa perubahan TP/siswa.
