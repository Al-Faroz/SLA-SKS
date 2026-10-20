# SQL SLA-SKS v2.3 — Pembaruan berurutan

## Instalasi lama dengan K2-0003 (sudah ada data)

**DILARANG mengimpor ulang instalasi awal atau update 0002/0003.**

1. Backup database dan folder `writable/` ke lokasi privat; pastikan ada salinan yang dapat dipulihkan.
2. Di phpMyAdmin pilih DB yang digunakan aplikasi. Periksa:
   `SELECT versi FROM schema_version ORDER BY versi;`
   Harus ada `K1-0001`, `K2-0002`, `K2-0003`, dan **belum ada** `K2-0004`.
3. Impor sekali `database/sql/updates/0004_siswa_akun.sql`.
4. Periksa ulang `schema_version`, jumlah baris `siswa`, dan login Admin yang lama. Jangan membuat ulang akun.
5. Halaman `/admin/pengelola` mengelola staf; `/admin/siswa` mengelola akun siswa, NISN, Gen dan semester mulai. Admin melengkapi Gen akun siswa lama yang belum ditempatkan pada tahap pengeditan data berikutnya.

SQL 0004 hanya menambah tabel `siswa` dan mengisi identitas akun siswa **yang sudah ada** dengan NISN yang sama dengan username. **Tidak menebak Gen** bagi siswa lama, tidak mereset sandi, tidak menghapus akun atau catatan K1–K2.

DDL MySQL/MariaDB bisa *implicit commit*. Bila impor gagal, hentikan, catat pesan galat dan pulihkan backup setelah memeriksa tabel yang terbentuk; jangan menjalankan ulang tanpa pemeriksaan.

## Instalasi baru (DB benar-benar kosong)
Impor berurutan `install/k1_fondasi_akun.sql`, `updates/0002_referensi_identitas.sql`, `updates/0003_guru_per_gen.sql`, lalu `updates/0004_siswa_akun.sql`. Jalankan `php spark sla:admin-awal` hanya satu kali.

Tidak menggunakan CI4 Migration/Seeder, tidak menyimpan data nyata/kredensial di repository.

## Batas pekerjaan tahap
Pemisahan menu Pengelola/Siswa, pembuatan akun valid, dan reset sandi per akun selesai setelah pengujian XAMPP. Import/export XLSX (template, pratinjau, transaksi idempoten), edit biodata lama, serta profil pengguna merupakan PR implementasi berikutnya. Jangan menampilkan tombol import/export sebagai tombol palsu sebelum endpoint tersedia.
