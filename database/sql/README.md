# SQL SLA-SKS v2.2 — Pembaruan K2

## Database K1 yang sudah dipakai
JANGAN instal ulang file install/k1_fondasi_akun.sql. Patch K2 tidak menghapus data K1.

1. Backup manual database dan folder writable/ ke lokasi privat.
2. Pada phpMyAdmin pilih database SLA-SKS yang sama.
3. Jalankan SELECT versi FROM schema_version; pastikan K1-0001 ada dan K2-0002 belum ada.
4. Impor database/sql/updates/0002_referensi_identitas.sql SATU KALI.
5. Periksa schema_version sekarang berisi K1-0001 dan K2-0002.
6. Login kembali dengan akun Admin yang lama; gunakan menu Gen & Tahun Ajaran, Mapel & Guru, Identitas Madrasah.

File SQL menambah 10 tabel tanpa menyentuh data K1, serta referensi tetap MTs Fase D (semester 1–6).
Data nyata siswa, guru, dan identitas sekolah tidak ada pada dump.

PENTING: DDL MySQL/MariaDB dapat melakukan implicit commit. Bila impor terputus,
JANGAN impor ulang begitu saja. Periksa tabel yang telah dibuat dan lakukan pemulihan
melalui backup bila perlu. Pengujian MariaDB hosting masih harus dilakukan.

## Instalasi baru (database benar-benar kosong)
Jalankan install/k1_fondasi_akun.sql kemudian updates/0002_referensi_identitas.sql.
Baru kemudian jalankan CLI inisialisasi Admin awal dari K1.

Tidak memakai CI4 Migration atau Seeder. SQL berikutnya harus incremental dan diberi nomor.
