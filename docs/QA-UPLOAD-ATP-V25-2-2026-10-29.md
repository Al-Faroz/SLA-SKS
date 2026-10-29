# QA Unggah, Pratinjau, dan Konfirmasi ATP V25-2 — 29 Oktober 2026

## Sasaran
Menuntaskan alur Guru G05 untuk workbook sepuluh sheet **SLA-SKS-ATP-MANUAL-V25-2**:
Guru → Peta ATP → Kelola ATP Excel → Pilih paket acuan → Unggah → Periksa & Pratinjau → Tinjau enam semester → Konfirmasi → Simpan BAB/TP draf.

- Delapan kolom TP A:H (Kode TP dihasilkan server; Elemen CP tidak diisi per TP).
- CP resmi tetap divalidasi terhadap sheet REFERENSI CP dan versi aktif server.
- Hanya semester dengan paket sah dan belum memiliki BAB/TP yang menerima INSERT.
- File identik pada semester berisi data menjadi **Tetap** tanpa penulisan; file berbeda menjadi **Galat**; satu galat membatalkan seluruh konfirmasi.
- Staging XLSX privat terikat akun/sesi/paket/checksum/fingerprint selama 15 menit; konfirmasi ulang dalam transaksi DB.
- Tidak membuat materi, media, bank soal, progres, atau nilai. Tidak ada SQL, migration, seeder maupun perubahan .env.

## Pemeriksaan sebelum parser
Service `AtpValidasiUnggahService` memeriksa nama berekstensi .xlsx, batas 5 MB, MIME aktual via finfo, dan struktur ZIP OpenXML. Parser tetap memeriksa ZIP bomb, macro/eksternal, 10 sheet, versi V25-2, rumus, CP dan aturan BAB/TP. File lama V25-1 mendapat petunjuk unduh ulang.

## UI
- Form pada G05 menampilkan file terpilih, ukuran, dan status upload (JavaScript native, fallback POST normal).
- Ketika dikirim: label sementara "Mengunggah & memeriksa Excel", tombol nonaktif sementara untuk mencegah klik ganda; tidak mengklaim penyimpanan.
- Pratinjau menampilkan ringkasan enam semester: **Akan ditambahkan**, **Sudah sesuai**, **Ditolak**, atau **Tidak diisi**, dan rincian per baris termasuk kode TP hasil server.
- Konfirmasi memerlukan checkbox pemeriksaan Guru; validasi juga dilakukan pada Controller; button nonaktif sementara setelah submit.
- Setelah commit, pesan sukses menyebut jumlah BAB/TP baru dan data tetap; tidak ada klaim UPDATE.

## Tes otomatis tanpa database asli
- Unit `AtpUnggahPratinjauRoundTripTest`: XLSX V25-2 nyata → MIME/ZIP → staging privat → parser → kode TP otomatis; file V25-1, ekstensi berbahaya dan file palsu/kosong ditolak.
- Integrasi `AtpImporMysqlTest`: file V25-2 → validasi → pratinjau → staging → transaksi INSERT pada fixture MySQL khusus QA; tetap menguji idempotensi, larangan edit manual, lintas Tahun Ajaran, rollback.
- Workflow lint seluruh berkas PHP relevan + PHPUnit parser, staging dan UI.

## Uji manual XAMPP oleh pemilik
1. Pastikan checkout branch PR terbaru dan SQL lokal yang sudah disepakati terpasang; **tidak perlu impor SQL baru**.
2. Backup database `sla_sks_v4` sebelum konfirmasi simpan.
3. Unduh **ulang** ATP V25-2; isi SATU BAB dan SATU TP pada semester yang punya paket kosong, misalnya Semester 4.
4. Unggah file; pastikan halaman pratinjau menunjukkan kode TP otomatis dan 2 baris **Baru**, serta lima semester lainnya **Tidak diisi**.
5. Konfirmasi checkbox → Simpan; lihat hasil BAB dan TP draf pada halaman semester.
6. Unggah ulang file identik: **Tetap**, tidak ada tombol konfirmasi dan tidak ada baris ganda.
7. Ubah satu nama TP pada Excel lalu unggah lagi: **Galat**, tanpa UPDATE ke data hasil impor pertama.
8. Coba unggah file V25-1 atau file .xlsm: harus ditolak dengan pesan yang jelas.
9. Periksa hak akses Guru Gen lain; tidak boleh mengakses paket/pratinjau/konfirmasi Gen yang tidak ditugaskan.

## Status
Pengujian GitHub Actions dicatat pada Draft PR. Lulus CI bukan berarti pengujian UI di browser lokal sudah selesai.
