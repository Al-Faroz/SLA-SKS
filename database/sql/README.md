# SQL SLA-SKS v2.2 — Tahap K1

**K1 hanya skema identitas, peran, sesi, pembatasan login, dan audit.** Bukan dump final LMS. Tidak ada data pribadi atau sandi contoh pada repository.

## Instalasi di database KOSONG
1. Pastikan koneksi database .env lokal telah benar.
2. Buka phpMyAdmin dan pilih database SLA-SKS yang masih kosong. Jangan menghapus data apa pun.
3. Impor file database/sql/install/k1_fondasi_akun.sql SATU KALI.
4. Periksa schema_version = K1-0001 serta tabel pengguna, pengguna_peran, batas_login, log_aktivitas, ci_sessions.
5. Pada CMD di root proyek jalankan: G:\xampp\php\php.exe spark sla:admin-awal
6. Ikuti petunjuk nama dan username. Perintah memberikan sandi sementara acak hanya satu kali di terminal. Simpan privat, lalu wajib ganti sandi pada login pertama.

**Jangan menyimpan sandi, .env, atau data riil pengguna di GitHub.** Skrip admin awal menolak dijalankan bila sudah ada pengguna.

## Ketergantungan dan keamanan
- K1 berbasis K0 pada branch feature/k0-foundation. Uji dan gabungkan K0 terlebih dahulu.
- Tidak menggunakan migrations/seeders CI4.
- Jika database tidak kosong, JANGAN impor skema K1 tanpa audit tabel yang sudah ada.
- Dump instalasi final sla_sks_v2_2_full.sql belum dibuat. K1 menyiapkan tabel yang dibutuhkan tahap ini saja.
- SQL berikutnya memakai file tambahan incremental bernomor di database/sql/updates, tanpa reset data.
- Lakukan backup manual sebelum pembaruan; DDL tidak selalu dapat dibatalkan dengan ROLLBACK.

## Uji manual yang dibutuhkan
Uji MySQL XAMPP dan MariaDB hosting pada database pengujian terpisah, login salah lima kali, ganti sandi pertama, logout POST/CSRF, akses lintas role, URL privat, dan sesi usang. K1 tidak boleh dianggap siap produksi tanpa seluruh pemeriksaan ini.
