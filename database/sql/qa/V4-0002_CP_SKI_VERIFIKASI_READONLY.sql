-- BACA-S A J A: verifikasi setelah V4-0002 CP SKI.
-- Jalankan pada database sla_sks_v4 yang sudah dicadangkan.
SELECT versi, diterapkan_pada FROM schema_version WHERE versi IN ('V4-0001-R2','V4-0002-CP-SKI-PK');
SELECT m.kode AS mapel, cm.id AS cp_operasional_id, c.kode AS kode_referensi,
       v.id AS versi_id, v.nomor_versi, v.elemen, v.status,
       LEFT(v.rumusan,180) AS awal_rumusan
FROM cp_mapel cm
JOIN ref_mapel m ON m.id=cm.mapel_id
JOIN cp_mapel_elemen ce ON ce.cp_mapel_id=cm.id
JOIN ref_cp_versi v ON v.id=ce.cp_versi_id
JOIN ref_cp c ON c.id=v.cp_id
WHERE m.kode='SKI' ORDER BY v.id;
SELECT COUNT(*) AS aktif_pk_baru
FROM ref_cp c JOIN ref_cp_versi v ON v.cp_id=c.id
WHERE c.kode='KBC25-SKI-D-PK' AND v.status='aktif'
AND v.elemen='Pemahaman Konsep';
SELECT COUNT(*) AS tautan_tp_cp_lama_terpelihara
FROM tp_cp t JOIN ref_cp_versi v ON v.id=t.cp_versi_id
JOIN ref_cp c ON c.id=v.cp_id WHERE c.kode='FD-SKI-01';
SELECT COUNT(*) AS aktivitas_revisi_cp_ski
FROM log_aktivitas WHERE peristiwa='cp_ski_pk_v25_diterbitkan';
