-- QA BACA SAJA untuk patch V4-0003, jalankan setelah impor di sla_sks_v4.
SELECT versi,diterapkan_pada FROM schema_version
WHERE versi IN ('V4-0002-CP-SKI-PK','V4-0003-SKI-CP-TUNGGAL');
SELECT v.id AS id_versi,r.kode AS kode_cp,v.elemen,v.status,
       LEFT(v.rumusan,110) AS ringkasan
FROM cp_mapel cm
JOIN ref_mapel m ON m.id=cm.mapel_id
JOIN ref_fase f ON f.id=cm.fase_id
JOIN cp_mapel_elemen ce ON ce.cp_mapel_id=cm.id
JOIN ref_cp_versi v ON v.id=ce.cp_versi_id
JOIN ref_cp r ON r.id=v.cp_id
WHERE m.kode='SKI' AND f.kode='D'
ORDER BY v.id;
-- Harus tepat 1: kode KBC25-SKI-D-PK dan elemen Pemahaman Konsep
SELECT COUNT(*) AS jumlah_cp_ski_aktif
FROM cp_mapel cm
JOIN ref_mapel m ON m.id=cm.mapel_id
JOIN cp_mapel_elemen ce ON ce.cp_mapel_id=cm.id
JOIN ref_cp_versi v ON v.id=ce.cp_versi_id
WHERE m.kode='SKI' AND v.status='aktif';
-- Audit TP lama saja (JANGAN DELETE): masih dipakai siswa dan snapshot.
SELECT t.id AS tp_id,t.status, t.kode_tp,
       (SELECT COUNT(*) FROM progres_tp pt WHERE pt.tp_id=t.id) AS progres_siswa,
       (SELECT COUNT(*) FROM versi_konten_tp vk WHERE vk.tp_id=t.id) AS snapshot,
       (SELECT COUNT(*) FROM materi mt WHERE mt.tp_id=t.id) AS materi,
       (SELECT COUNT(*) FROM tp_cp tc WHERE tc.tp_id=t.id) AS tautan_cp
FROM tp t JOIN paket_semester p ON p.id=t.paket_semester_id
JOIN ref_mapel m ON m.id=p.mapel_id
WHERE m.kode='SKI' ORDER BY t.id;
