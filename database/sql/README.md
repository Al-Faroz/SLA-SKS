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


## Update K2-0005 — Edit identitas, role dan status akun
Bagi instalasi yang sudah mengimpor 0004: **backup DB dan writable/**; pastikan K2-0004 sudah ada lalu impor **hanya** `updates/0005_riwayat_identitas.sql` sekali via phpMyAdmin. Tabel riwayat_identitas menyimpan audit privat sebelum/sesudah perubahan. Koreksi NISN dan username wajib satu transaksi. Gen dan semester siswa yang sudah ditetapkan bersifat baca saja hingga pemeriksaan progres tersedia. Penonaktifan tidak menghapus akun/histori.


## K2-0006 — Pratinjau dan konfirmasi impor XLSX akun

Backup database dan writable/ terlebih dahulu, periksa `SELECT versi FROM schema_version ORDER BY versi;` dan pastikan K2-0005 sudah ada tetapi K2-0006 belum. **Impor hanya** `updates/0006_impor_akun.sql` **sekali** melalui phpMyAdmin. Jangan impor ulang K1–0005.

Patch additive membuat `impor_akun` (checksum, Admin, jenis, versi template, status dan jumlah) serta `impor_akun_baris` (perubahan sebelum/sesudah, aksi, galat per baris). Berkas XLSX mentah hanya ada dalam request upload, tidak disimpan ke repository/public. Sandi awal untuk akun BARU di-hash langsung; hanya hash staging yang sementara berada dalam database, dihapus sesudah konfirmasi dan dibersihkan saat pratinjau kedaluwarsa diproses lagi. Pratinjau 30 menit, maksimal 1 MB / 300 baris.

Gunakan template AKUN-V1 yang sudah ada. Akun baru wajib `sandi_awal` 8–200 karakter, akun lama wajib mengosongkannya. Akun lama hanya dapat memperbarui nama/peran yang sah, **tanpa mengubah NISN, sandi, Gen, semester, status atau histori**. Koreksi NISN dilakukan melalui menu Ubah manual. Konfirmasi ditolak jika ada baris invalid; tidak ada impor sebagian. Saat konfirmasi seluruh perubahan divalidasi ulang dan disimpan satu transaksi, pengulangan konfirmasi pada batch yang sama tidak membuat akun duplikat. Akun yang tidak muncul di Excel tidak dihapus.

Pakai akun pengujian dan jangan simpan/unggah file yang memuat sandi awal ke GitHub atau lokasi publik.


## Update K2-0007 — Profil pribadi dan foto berizin

Backup database dan folder `writable/`, pastikan `K2-0006` ada dan `K2-0007` belum ada di `schema_version`. Impor **hanya** `updates/0007_profil_pengelola.sql` satu kali melalui phpMyAdmin.

Patch additive ini menambah `foto_profil` (metadata foto privat dan status persetujuan) dan `profil_pengelola` (kontak privat, pointer foto disetujui, pointer foto menunggu). Tidak mengubah akun, NISN, progres, sandi maupun penugasan Guru. Foto fisik berada pada `writable/uploads/profil/` di luar akses langsung Web, tidak di-commit. **Backup dan restore perlu mencakup folder tersebut**; tidak cukup dump database saja.

Pengelola (Admin/Guru/Pimpinan) dapat mengubah kontak dan mengunggah foto sendiri; foto JPG/PNG maksimal 2 MB, setiap sisi 100–1600 piksel, didekode lalu dienkode ulang dan memerlukan persetujuan Admin. Foto lama tetap tersedia hingga foto baru disetujui. Siswa hanya melihat foto profesional Guru yang sudah disetujui dan ditugaskan untuk Gen-nya, tanpa telepon pribadi atau NIP. Server memeriksa akses pada setiap unduhan foto.

Jika impor SQL terhenti, jangan langsung ulangi; periksa tabel dan pulihkan backup terlebih dahulu. Tidak memakai migrations/seeders.
