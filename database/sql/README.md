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


## Update K3-0008 — Kurikulum dan CP resmi berversi

**Sebelum impor:** backup database MySQL dan `writable/`. Periksa:
`SELECT versi FROM schema_version ORDER BY versi;`
Prasyarat `K2-0007` sudah terpasang dan `K3-0008` belum ada. Impor **hanya** `updates/0008_kurikulum_cp.sql` **satu kali** melalui phpMyAdmin.
Jangan mengimpor ulang dump atau patch 0001–0007; jangan menggunakan migration/seeder.

Patch additive membuat:
- `ref_kurikulum`: nama, versi, sumber, dasar hukum dan pembuat.
- `ref_cp`: kode CP stabil pada kombinasi kurikulum–mapel–Fase D; CP tidak dimiliki Gen tertentu.
- `ref_cp_versi`: teks rumusan, elemen, otoritas, nomor dokumen, URL sumber, tanggal berlaku, dan siklus draf/aktif/diarsipkan.

Admin mengelola `/admin/kurikulum-cp` untuk menambahkan kurikulum dan CP, memperbaiki draf,
menerbitkan versi serta membuat draf versi berikutnya. Hanya teks **draf** yang dapat diubah.
Ketika versi baru diterbitkan, status versi aktif lama menjadi diarsipkan; teks versi historis **tidak berubah**. Penerbitan menulis audit; seluruh mutasi memakai transaksi. Tidak ada CP/kurikulum dummy atau ketetapan hukum yang ditebak/dimasukkan otomatis.

**Batas tahap K3A:** proposal CP dari Guru dan keputusan Admin atas usulan (`usulan_cp`) serta penautan TP–CP belum tersedia, harus dibangun pada PR akademik berikutnya sebelum BAB/TP aktif. Setelah modul siap, penautan TP baru hanya memilih versi CP `aktif` dengan kurikulum/mapel/fase yang cocok; tautan historis tetap.

Jika impor gagal setelah sebagian DDL, periksa database dan pulihkan backup sebelum mencoba kembali.

## S01-0014 — Progres TP dan version pinning Siswa (v2.4, 24 Oktober 2026)

**Prasyarat:** database XAMPP telah berisi skema v2.4 terkini dan memiliki tabel
`siswa`, `tp`, `paket_semester`, `versi_konten_tp`, `penugasan_paket`.
Berkas SQL: `database/sql/updates/0014_progres_tp_pin.sql`.

1. **Backup** database aktif menggunakan phpMyAdmin sebelum perubahan, serta
   simpan backup `writable/` sebagai bagian prosedur proyek. Gunakan database
   pengujian lokal dahulu, bukan database produksi.
2. Pastikan tabel belum ada: `SHOW TABLES LIKE 'progres_tp';`. Jika sudah ada,
   **hentikan** dan periksa struktur/data; jangan mengimpor ulang atau mereset.
3. Impor **sekali** `0014_progres_tp_pin.sql` melalui phpMyAdmin pada database
   SLA-SKS v2.4. Patch ini **hanya CREATE TABLE**, tanpa DROP/DELETE/TRUNCATE
   dan tanpa Migration/Seeder. Karena DDL MySQL memiliki implicit commit,
   rollback otomatis terhadap CREATE TABLE **tidak tersedia**; gunakan backup
   bila impor gagal dan pemeriksaan sebelum mencoba ulang.
4. Verifikasi `SHOW CREATE TABLE progres_tp;` dan
   `SELECT COUNT(*) AS total FROM progres_tp;` (awal 0).
5. Pada TP **Siap**, login **Siswa pengujian** dari Gen/paket yang benar.
   Buka `/siswa/tp/{id}`, klik `Aktifkan TP & simpan versi`, lalu cek ulang
   halaman dan `SELECT id,siswa_id,tp_id,versi_konten_tp_id,status FROM progres_tp;`.
   Semestinya **satu** record per siswa–TP–versi paket; status `diagnostik`,
   dengan versi snapshot yang tetap ketika reload/POST berulang.
6. Coba siswa dari Gen lain dan TP selanjutnya; harus ditolak. **Jangan**
   memakai akun siswa nyata untuk uji aktivasi sebelum disepakati karena
   pengikatan historis bersifat permanen dan tidak memiliki tombol reset.
7. Belum membuat `percobaan_ujian`, jawaban, skor, ataupun progres Materi.
   Diagnostik wajib dipasang pada tahap selanjutnya; **tidak ada tombol
   Mulai Diagnostik** sebelum keamanan fullscreen/timer/acak soal tersedia.

**Kompatibilitas:** kolom/progres berkaitan langsung dengan `siswa`, `tp`,
`paket_semester`, dan `versi_konten_tp`; nama tabel dan tipe FK memerlukan
verifikasi terhadap skema nyata sebelum impor hosting MariaDB. Tidak ada data
siswa di GitHub. Jika skema yang terpasang ternyata memiliki tabel setara
`progres_tp`, lakukan review dan penyelarasan patch, bukan menjalankan DDL
yang berpotensi konflik.

