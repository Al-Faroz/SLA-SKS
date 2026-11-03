-- QA A8 Pra-Semester -- HANYA SELECT, tidak mengubah data.
-- Jalankan di database sla_sks_v4 setelah BACKUP.
-- Jangan mengirim hasil berisi nama siswa/NISN/hash/kredensial ke GitHub.
SELECT 'ujian_pra_semester' AS objek, COUNT(*) AS jumlah FROM ujian_pra_semester
UNION ALL SELECT 'soal_pra_semester', COUNT(*) FROM soal_pra_semester
UNION ALL SELECT 'percobaan_pra_semester', COUNT(*) FROM percobaan_ujian WHERE jenis='pra_semester';

-- Status bank dan jendela ujian utama, per paket. Tanpa PII siswa.
SELECT p.id AS paket_id, p.semester, p.status AS status_paket,
       u.id AS ujian_id, u.status AS status_ujian, u.mulai_pada AS awal_jendela_wib,
       u.selesai_pada AS akhir_jendela_wib, u.durasi_menit,
       u.jumlah_soal, COUNT(DISTINCT s.id) AS jumlah_soal_aktif
FROM paket_semester p
LEFT JOIN ujian_pra_semester u ON u.paket_semester_id=p.id
LEFT JOIN soal_pra_semester s ON s.ujian_pra_semester_id=u.id AND s.aktif=1
GROUP BY p.id,p.semester,p.status,u.id,u.status,u.mulai_pada,u.selesai_pada,
         u.durasi_menit,u.jumlah_soal
ORDER BY p.id;

-- Audit status keseluruhan tanpa membuka jawaban/kunci.
SELECT jenis,tahap,status,COUNT(*) AS jumlah
FROM percobaan_ujian WHERE jenis='pra_semester'
GROUP BY jenis,tahap,status ORDER BY tahap,status;

-- Cakupan BAB yang benar-benar punya soal aktif.
SELECT b.paket_semester_id,b.nomor_bab,COUNT(s.id) AS soal_aktif
FROM bab b
LEFT JOIN ujian_pra_semester u ON u.paket_semester_id=b.paket_semester_id
LEFT JOIN soal_pra_semester s ON s.ujian_pra_semester_id=u.id
  AND s.bab_id=b.id AND s.aktif=1
GROUP BY b.paket_semester_id,b.id,b.nomor_bab
ORDER BY b.paket_semester_id,b.nomor_bab;
