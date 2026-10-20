# Status Keputusan Produk SLA-SKS v2.3

**Keputusan v2.3 telah disetujui pemilik.** G1 per Gen–Mapel, paket/BAB sendiri tiap Gen, menu Pengelola/Siswa, impor/ekspor XLSX, reset per akun, profil staf dan Guru terbatas, serta perombakan visual dashboard/publik merupakan aturan final dalam 16 dokumen resmi. Keputusan bisnis v2.2 lain tetap berlaku sepanjang tidak bertentangan. Tidak ada kuesioner terbuka untuk hal yang sudah diputuskan.

## Data instalasi yang diisi saat setup, bukan pertanyaan kebijakan
- Domain HTTPS aktual, versi PHP/CI4 dan MariaDB pada hosting, versi MySQL lokal, kapasitas hosting, direktori aplikasi, jadwal cron yang diizinkan.
- Nama sekolah, alamat, NPSN, NSM, telepon, email, kota penerbitan, nama/NIP kepala madrasah; situs web opsional.
- Berkas logo, ikon, PNG tanda tangan, dan template background opsional (fallback bawaan tersedia).
- Akun Admin awal privat, Gen nyata, Guru per Gen–Mapel, tahun ajaran, rujukan CP resmi per mata pelajaran.

## Pemeriksaan teknis saat coding (bukan syarat membuat keputusan produk baru)
- Uji nyata perlindungan direktori pada front controller ROOT dan ketergantungan Apache/LiteSpeed shared hosting.
- Selesaikan patch SQL transisi v2.3 (versi 0003 bagi database K2 yang sudah berisi akun/data), SQL instalasi penuh, dan uji MySQL/MariaDB; K1 sudah mempunyai SQL awal dan PR K2 mempunyai SQL 0002, **belum ada dump SQL v2.3 penuh**.
- Detail algoritme laporan status sertifikat ketika koreksi kunci soal menyebabkan status TP/semester turun; jangan membuat pencabutan/penghapusan sertifikat.
- Uji batas ukuran/rasio background sertifikat dan penempatan QR/tanda tangan pada PDF A4 sesuai mutu berkas nyata.
- Uji kompatibilitas fullscreen nyata di browser yang dipakai siswa, dan kesiapan shared hosting untuk rekonsiliasi jam server.

## Ketergantungan implementasi setelah dokumentasi disahkan
1. Jangan gabungkan PR K2 #3 sebelum model `ref_mapel.guru_id` diubah menjadi penugasan per Gen–Mapel dan kode/data K2 yang sudah dipakai pemilik dipertahankan.
2. Siapkan patch 0003 baru bagi database yang telah menerapkan `K2-0002`, serta jalur instalasi segar yang konsisten; impor/ekspor akun dan profil menyusul di PR kode khusus.
3. Buat prototipe dashboard Admin dan publik/login sesuai dokumen 12–13 untuk persetujuan pemilik sebelum mengubah seluruh CSS. Implementasi tidak boleh mengubah root CI4 atau `.env`.

**Instruksi:** selesaikan sebagai implementasi yang konsisten dan uji sampai lulus. Jangan mengulang kuesioner ke pemilik atas hal yang sudah diputuskan.
