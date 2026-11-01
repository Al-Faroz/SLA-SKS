-- QA F1 SLA-SKS v2.5 — Pemeriksaan pasca-impor ATP V25-3.
-- BACA SAJA / TIDAK MENGUBAH DATA. Jalankan di phpMyAdmin pada database salinan
-- QA atau `sla_sks_v4` yang SUDAH dipilih dengan benar.
-- Divalidasi terhadap struktur dump privat 01-11-2026 (MariaDB 10.4.32).
-- Tidak menampilkan nama, NISN, username, hash, sesi, atau konten materi.

SELECT DATABASE() AS database_diperiksa;

-- Satu baris per semester: paket, BAB, TP, Materi, CP, dan status.
SELECT p.semester, p.status AS status_paket, ta.nama AS tahun_ajaran,
  (SELECT COUNT(*) FROM bab b WHERE b.paket_semester_id = p.id) AS jumlah_bab,
  (SELECT COUNT(*) FROM tp t WHERE t.paket_semester_id = p.id) AS jumlah_tp,
  (SELECT COUNT(*) FROM tp t WHERE t.paket_semester_id = p.id
     AND t.jenis_tp = 'esensial') AS esensial,
  (SELECT COUNT(*) FROM tp t WHERE t.paket_semester_id = p.id
     AND t.jenis_tp = 'non_esensial') AS non_esensial,
  (SELECT COUNT(*) FROM materi m INNER JOIN tp t ON t.id = m.tp_id
     WHERE t.paket_semester_id = p.id) AS jumlah_materi
FROM paket_semester p
JOIN ref_angkatan a ON a.id = p.angkatan_id
JOIN ref_mapel r ON r.id = p.mapel_id
JOIN ref_tahun_ajaran ta ON ta.id = p.tahun_ajaran_id
WHERE a.kode = 'GEN2025' AND r.kode = 'SKI' AND p.status <> 'arsip'
ORDER BY p.semester, p.id;

-- Semua hasil anomali di bawah harus 0.
SELECT 'TP tanpa tepat satu Materi' AS anomali, COUNT(*) AS jumlah
FROM tp t
JOIN paket_semester p ON p.id = t.paket_semester_id
JOIN ref_angkatan a ON a.id = p.angkatan_id
JOIN ref_mapel r ON r.id = p.mapel_id
LEFT JOIN (SELECT tp_id, COUNT(*) AS n FROM materi GROUP BY tp_id) m ON m.tp_id = t.id
WHERE a.kode = 'GEN2025' AND r.kode = 'SKI' AND COALESCE(m.n, 0) <> 1
UNION ALL
SELECT 'TP CP tidak sesuai Gen-Mapel-Kurikulum', COUNT(*)
FROM tp t
JOIN paket_semester p ON p.id = t.paket_semester_id
JOIN ref_angkatan a ON a.id = p.angkatan_id
JOIN ref_mapel r ON r.id = p.mapel_id
LEFT JOIN cp_mapel c ON c.id = t.cp_mapel_id
WHERE a.kode = 'GEN2025' AND r.kode = 'SKI'
  AND (c.id IS NULL OR c.mapel_id <> p.mapel_id OR c.kurikulum_id <> p.kurikulum_id)
UNION ALL
SELECT 'TP tanpa BAB pada paket yang sama', COUNT(*)
FROM tp t
JOIN paket_semester p ON p.id = t.paket_semester_id
JOIN ref_angkatan a ON a.id = p.angkatan_id
JOIN ref_mapel r ON r.id = p.mapel_id
LEFT JOIN bab b ON b.id = t.bab_id AND b.paket_semester_id = t.paket_semester_id
WHERE a.kode = 'GEN2025' AND r.kode = 'SKI' AND b.id IS NULL
UNION ALL
SELECT 'TP atau Materi sudah diterbitkan sebelum disiapkan', COUNT(*)
FROM tp t
JOIN paket_semester p ON p.id = t.paket_semester_id
JOIN ref_angkatan a ON a.id = p.angkatan_id
JOIN ref_mapel r ON r.id = p.mapel_id
JOIN materi m ON m.tp_id = t.id
WHERE a.kode = 'GEN2025' AND r.kode = 'SKI'
  AND (t.status <> 'draf' OR m.aktif <> 0);

-- Urutan TP mulai dari 1 dan tanpa jeda dalam setiap BAB.
SELECT COUNT(*) AS jumlah_urutan_tp_bermasalah
FROM (
 SELECT t.id, t.urutan_dalam_bab,
   ROW_NUMBER() OVER (PARTITION BY t.bab_id ORDER BY t.urutan_dalam_bab, t.id) AS urutan_benar
 FROM tp t
 JOIN paket_semester p ON p.id = t.paket_semester_id
 JOIN ref_angkatan a ON a.id = p.angkatan_id
 JOIN ref_mapel r ON r.id = p.mapel_id
 WHERE a.kode = 'GEN2025' AND r.kode = 'SKI'
) x WHERE x.urutan_dalam_bab <> x.urutan_benar;

-- Kode TP dan isi rencana materi diverifikasi lebih lengkap oleh
-- audit aplikasi/checkpoint. File ini TIDAK membuat, mereset, atau memperbaiki baris.
