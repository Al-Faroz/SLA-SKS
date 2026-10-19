# Status Keputusan Produk SLA-SKS v2.2

**Kebijakan K01–K24 dan respons T01–T10 telah diterapkan pada Master v2.2.** Tidak ada kuesioner terbuka untuk pemilik pada tahap penyusunan dokumentasi ini.

## Data instalasi yang diisi saat setup, bukan pertanyaan kebijakan
- Domain HTTPS aktual, versi PHP/CI4 dan MariaDB pada hosting, versi MySQL lokal, kapasitas hosting, direktori aplikasi, jadwal cron yang diizinkan.
- Nama sekolah, alamat, NPSN, NSM, telepon, email, kota penerbitan, nama/NIP kepala madrasah; situs web opsional.
- Berkas logo, ikon, PNG tanda tangan, dan template background opsional (fallback bawaan tersedia).
- Akun Admin awal privat, Gen nyata, Guru mapel, tahun ajaran, rujukan CP resmi per mata pelajaran.

## Pemeriksaan teknis saat coding (bukan syarat membuat keputusan produk baru)
- Uji nyata perlindungan direktori pada front controller ROOT dan ketergantungan Apache/LiteSpeed shared hosting.
- Finalkan DDL dan skema SQL terhadap realitas tabel serta kompatibilitas MySQL/MariaDB; **belum ada dump SQL instalasi siap impor** di paket dokumentasi.
- Detail algoritme laporan status sertifikat ketika koreksi kunci soal menyebabkan status TP/semester turun; jangan membuat pencabutan/penghapusan sertifikat.
- Uji batas ukuran/rasio background sertifikat dan penempatan QR/tanda tangan pada PDF A4 sesuai mutu berkas nyata.
- Uji kompatibilitas fullscreen nyata di browser yang dipakai siswa, dan kesiapan shared hosting untuk rekonsiliasi jam server.

**Instruksi:** selesaikan sebagai implementasi yang konsisten dan uji sampai lulus. Jangan mengulang kuesioner ke pemilik atas hal yang sudah diputuskan.
