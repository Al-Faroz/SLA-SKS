# SQL SLA-SKS — Panduan pembaruan bernomor (Master v2.4)

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


## S06A — Fondasi Mesin Ujian Diagnostik / Sumatif Ketat (24 Oktober 2026)

**Patch:** `database/sql/updates/0015_mesin_ujian_fondasi.sql`, **sesudah** `0014_progres_tp_pin.sql`.

Tahap S06A membangun **skema historis** dan **aturan domain murni**, belum mengizinkan siswa membuat percobaan. Akses S05 petunjuk dan uji fullscreen tetap seperti PR #45. **Tidak ada rute Mulai Ujian, tombol ujian, jawaban, atau nilai baru**.

1. **Backup database XAMPP lebih dahulu**. Jangan mengimpor ulang dump instalasi atau memodifikasi progres nyata.
2. Periksa `SHOW TABLES LIKE 'percobaan_ujian';` dan `SHOW CREATE TABLE progres_tp;`. Jika sudah ada tabel ujian, **hentikan** dan bandingkan struktur; SQL tahap ini bukan patch overwrite.
3. Impor **sekali** `0015_mesin_ujian_fondasi.sql` melalui phpMyAdmin pada database lokal yang sudah mengandung `progres_tp`, `versi_konten_tp`. DDL MySQL/MariaDB melakukan implicit commit: backup diperlukan bila ada kegagalan sebagian. **Jangan hapus tabel hasil impor parsial tanpa inspeksi terlebih dahulu**.
4. Verifikasi enam tabel: `percobaan_ujian`, `butir_percobaan`, `jawaban_percobaan`, `riwayat_status_ujian`, `segmen_jeda_ujian`, `pelanggaran_ujian`. Jalankan `SHOW CREATE TABLE percobaan_ujian;` dan `SELECT COUNT(*) AS total FROM percobaan_ujian;`, semestinya **0**.
5. Tidak perlu menekan tombol lain di S05: pada tahap ini halaman tidak diubah. Pengujian otomatis menggunakan data sintetis, bukan siswa XAMPP/hosting.
6. **S06B** akan menambahkan adapter transaksional `FOR UPDATE`, rekonsiliasi pada setiap request, antarmuka fullscreen tanpa sidebar, token percobaan sulit ditebak, pengacakan dalam transaksi, penyimpanan jawaban idempoten dan penilaian sah. Tombol Mulai Ujian hanya boleh muncul setelah seluruh kontrol itu terhubung dan diuji.

**Kontrak tabel:** `percobaan_ujian` mengikat `progres_tp` dan `versi_konten_tp`; `butir_percobaan` menyimpan opsi/kunci teracak, `jawaban_percobaan` memiliki UNIQUE per percobaan–butir dan FK komposit yang mencegah jawaban pada butir milik percobaan lain. Segmen jeda, riwayat status, dan insiden memiliki riwayat append-only. Keadaan batal tidak mempunyai nilai sah; batas 20/90/180/300 detik dan pelanggaran ketiga berasal dari Master Document 07, bukan konfigurasi baru.

**QA:** PHPUnit aturan murni, uji PHP lint, serta impor DDL menggunakan **MariaDB sementara dengan parent sintetis** di GitHub Actions. Pengujian MariaDB bukan bukti otomatis kesesuaian semua tipe FK pada dump MySQL XAMPP; uji impor lokal tetap wajib. Tidak ada Migration/Seeder atau data siswa nyata di repo.

## S06B — Pelaksanaan Diagnostik nyata (24 Oktober 2026)

**Tidak ada SQL tambahan untuk tahap ini.** S06B menggunakan tabel yang
sudah dibuat melalui patch 0014 (progres_tp) dan 0015 (enam tabel ujian).
**Jangan mengimpor ulang** dua patch tersebut bila tabel sudah tersedia.

Sebelum pengujian browser XAMPP:
1. Backup database dan gunakan akun **Siswa khusus uji**, bukan data siswa nyata,
   sebab Diagnostik yang selesai/batal bersifat historis dan tidak tersedia reset.
2. Login Siswa dengan TP pertama Siap serta progres Diagnostik yang terpin;
   buka S05, lalu **Masuk ruang Tes Diagnostik**.
3. Uji masuk fullscreen. Sebelum fullscreen berhasil, tidak boleh ada baris
   `percobaan_ujian`. Sesudah mulai, harus ada satu percobaan serta butir teracak.
4. Uji pilihan A–E, pergantian soal, refresh, koneksi offline, keluar fullscreen,
   jeda/pulih, submit, timeout aktif dan hasil. **Simpan** hanya berhasil jika server
   mengonfirmasi. Percobaan batal tak boleh memiliki skor.
5. Pastikan satu siswa tidak memperoleh dua percobaan normal dari refresh/klik
   dua kali. Perubahan status progres menjadi `tuntas` (≥KKM), `belajar` (<KKM)
   atau `perlu_bantuan` (batal) adalah perubahan data **nyata**.
6. Uji halaman hasil/status setelah ujian selesai, akses Gen lain dan perangkat
   tanpa fullscreen. Bila error, kumpulkan teks error dan status di tabel
   percobaan; **jangan** DELETE, TRUNCATE, DROP, reset skor ataupun mengubah
   snapshot untuk mencoba lagi. Laporkan agar diperbaiki melalui patch aman.
7. Modul tindak lanjut Guru pada percobaan batal serta pelaksanaan Materi,
   Resume/HOTS, Sumatif dan laporan masih tahap lanjutan.

Pemeriksaan SQL baca-saja:

```sql
SELECT id, progres_tp_id, jenis, status, jumlah_soal,
       jumlah_pelanggaran_fullscreen, skor, jumlah_benar
FROM percobaan_ujian
ORDER BY id DESC;
SELECT percobaan_ujian_id, COUNT(*) AS jawaban_disimpan
FROM jawaban_percobaan
GROUP BY percobaan_ujian_id;
```


## S08B — Pencatatan aktivitas media (24 Oktober 2026)

**Patch baru:** `database/sql/updates/0016_progres_aktivitas_materi.sql`.
Sesudah `0014_progres_tp_pin.sql` dan `0015_mesin_ujian_fondasi.sql` yang
sudah diimpor. **Backup database XAMPP** lebih dahulu. Impor hanya sekali
dengan phpMyAdmin pada database SLA-SKS yang sedang digunakan. Jangan
mengimpor ulang dump instalasi, menghapus hasil percobaan ujian, atau
menerapkan Migration/Seeder.

Dua tabel ditambahkan: `progres_materi` (unik per progres TP × Materi ×
versi snapshot terpin) dan `halaman_progres_materi` (satu baris per halaman
per progres untuk pembaca PDF.js lokal pada tahap berikutnya).

Verifikasi baca-saja:
```sql
SHOW CREATE TABLE progres_materi;
SHOW CREATE TABLE halaman_progres_materi;
SELECT COUNT(*) AS jumlah_progres_media FROM progres_materi;
SELECT progres_tp_id,materi_id,jenis,status,waktu_aktif_detik,waktu_putar_detik
FROM progres_materi ORDER BY id DESC;
```

Saat pertama diimpor, jumlah progres media seharusnya **0**. Setelah
Siswa pengujian jalur `belajar` menonton audio/YouTube atau melihat gambar
di halaman aktif, POST heartbeat menciptakan progres bila diperlukan, dan
kredit detik berikutnya hanya berasal dari request berjarak 5–20 detik,
dihitung dengan jam server. Progres `media_selesai` hanya untuk durasi
yang lolos aturan; **bukan** tanda tugas Resume/HOTS selesai. Jalur
`tuntas` belajar sukarela tidak menciptakan baris progres resmi.

**Batas tahap:** pembaca PDF yang ada di S08A masih iframe browser.
Iframe tidak membuktikan halaman yang dibaca. S08B tidak mengarang
kredit PDF: pencatatan PDF sampai `media_selesai` menunggu PDF.js lokal
dan pengujian aktivitas setiap halaman; tabel halaman sengaja masih kosong.
Peristiwa browser (fokus/pemutar) adalah mitigasi best-effort, bukan bukti
antikecurangan absolut.

**QA:** MariaDB sementara di GitHub Actions (DDL/FK/UNIQUE) dan unit test
aturan durasi. Uji browser dan impor pada MySQL XAMPP tetap diperlukan.


## S09/S10 — Resume dan HOTS per Materi (25 Oktober 2026)

Patch additive: database/sql/updates/0017_kegiatan_resume_hots_materi.sql.
**Backup MySQL/MariaDB dan writable/ sebelum impor.** Jalankan SETELAH
0016_progres_aktivitas_materi.sql (serta 0014/0015) terpasang. Jangan mereset
atau mengimpor ulang nilai Diagnostik maupun progres siswa.

1. Periksa tabel resume_materi dan **jawaban_hots_materi** belum tersedia.
   Tabel **tugas_hots_materi adalah bank pertanyaan Guru** dan tidak boleh dibuat ulang.
   Jika salah satunya sudah ada, hentikan dan bandingkan struktur.
2. Impor 0017 **sekali** melalui phpMyAdmin pada database XAMPP yang digunakan.
3. Jalankan SHOW CREATE TABLE untuk resume_materi dan jawaban_hots_materi; SELECT COUNT(*) pada tabel
   awal semestinya 0 sebelum ada pengiriman.
4. Siswa pengujian jalur belajar: buka Materi pertama, tuntaskan media,
   Simpan Draf Resume, reload, Kirim Final Resume, Simpan/Kirim Final HOTS;
   setelah semua terpenuhi tombol Materi Selanjutnya dapat dibuka.
   Jalur tuntas Diagnostik hanya membaca sukarela. Pengiriman final dua kali
   tidak membuat duplikat, draf setelah final ditolak.
5. Arsip tahap sebelum PDF.js: PDF tidak dapat menyelesaikan media saat itu; **pada tahap S08D pembaca PDF.js lokal sudah dibuat**.
   Jangan bypass dengan SQL manual. Pengiriman jawaban dan penilaian Guru
   memerlukan integritas proses belajar.

Belum ada UI penilaian Guru ataupun Sumatif dalam tahap ini. Tabel menyimpan
kolom penilaian untuk tahap berikutnya, namun tidak mengisi nilai secara otomatis.


### Galat #1050 dan peringatan CHECK pada impor 0017 (25 Oktober 2026)

JANGAN impor 0017 ulang atau DROP/TRUNCATE tabel yang telah terbentuk.
Galat server #1050 Table already exists berarti objek target telah ada.
Tiga peringatan Data statis pada CHECK berasal dari parser phpMyAdmin,
bukan kode galat yang sama. SQL untuk instalasi BARU diperbaiki agar
CHECK berada inline di definisi kolom nilai (rentang 0–100 tetap dijaga).
Tabel yang sudah terbentuk dengan constraint CHECK lama tetap sah, tidak
perlu dibuat ulang atau diubah.

LANGKAH AMAN:
1. Di phpMyAdmin pilih DB aktif, jalankan file
   database/sql/qa/0017_verifikasi_kegiatan_readonly.sql pada tab SQL.
   File hanya SELECT metadata dari information_schema.
2. Jika KEDUA tabel SUDAH_ADA, cocokkan 14 kolom per tabel, PRIMARY,
   UNIQUE gabungan, empat FOREIGN KEY, dan CHECK skor (jika didukung).
   Jika cocok, SQL 0017 telah terpasang: jangan impor ulang.
3. Jika hanya salah satu tabel yang ada atau strukturnya berbeda,
   STOP. Jangan menjalankan sisa CREATE sembarangan atau menghapus tabel;
   simpan hasil pemeriksaan tanpa data pribadi untuk patch additive terarah.
4. Hanya jika kedua tabel BELUM_ADA, backup DB lalu impor sekali.
   Tidak menggunakan CREATE TABLE IF NOT EXISTS sebab dapat
   menyamarkan struktur tabel yang sudah ada tetapi tidak cocok.

Pengujian SQL di MariaDB CI bukan pengganti verifikasi MySQL di XAMPP.

### S08D Pembaca PDF per halaman: **TANPA impor SQL baru**

Menggunakan kedua tabel yang telah dibuat SQL 0016, yaitu progres_materi dan
halaman_progres_materi. Patch 0017 yang sudah FIX tetap sama; jangan impor
0016 atau 0017 ulang dan jangan menghapus data. Jika tabel 0016 ada,
langsung uji pembaca PDF siswa di branch S08D setelah backup rutin. Setiap
detak PDF tercatat pada snapshot Materi terpin; jumlah halaman berasal
dari berkas unggahan Guru. Perubahan ini tidak menyentuh
PdfMateriValidator.php atau validator batas 15 MB/40 halaman.


### G10/G11 Penilaian Guru — tanpa impor SQL baru

Tabel Resume/HOTS SQL 0017 yang sudah berhasil diimpor memiliki
kolom nilai, dinilai_oleh, dinilai_pada, umpan_balik. Tahap G10/G11
menggunakan kolom tersebut; **jangan impor ulang SQL 0016/0017**,
jangan DROP/TRUNCATE jawaban atau progres. Pemeriksaan nilai pertama
menggunakan transaksi, audit, dan perizinan G1 per Gen–Mapel; koreksi
nilai historis tetap memerlukan alur AB-33 terpisah.


### Koreksi nama tabel HOTS dan pemulihan instalasi 0017 parsial (25-10-2026)

Inspeksi dump lokal memperlihatkan tabel tugas_hots_materi **sudah merupakan
bank pertanyaan HOTS Guru**, berisi materi_id, row_uuid, pertanyaan, petunjuk.
Tabel tersebut TIDAK BOLEH dihapus, diubah nama atau dipakai menyimpan
jawaban siswa. Jawaban siswa sekarang menggunakan tabel jawaban_hots_materi.

Untuk database lokal yang sempat mengimpor 0017 parsial (resume_materi ada,
sementara jawaban_hots_materi belum ada), **jangan impor 0017 lagi**.
Backup database, jalankan qa/0018_verifikasi_jawaban_hots_readonly.sql.
Pastikan resume_materi 14 kolom, tabel bank Guru tetap utuh (5 kolom), dan
jawaban_hots_materi masih BELUM_ADA. Lalu impor HANYA
updates/0018_pemulihan_jawaban_hots_materi.sql sekali. Jalankan QA baca-saja
kembali untuk mengonfirmasi jawaban_hots_materi 14 kolom. Tidak ada DROP,
TRUNCATE, reset nilai atau migrasi/seeder. Instalasi baru memakai SQL 0017
terkoreksi dan tidak perlu memanggil 0018 lagi.

Kesalahan query database tidak boleh ditampilkan sebagai 403; kegagalan
skema ditampilkan 503 dengan petunjuk memperbaiki SQL. 403 tetap khusus
masalah hak akses yang sah.

### Gerbang wajib SQL baru — 25 Oktober 2026

**Kondisi saat ini: XAMPP lokal, belum deploy ke hosting.** Pekerjaan SQL berikutnya hanya boleh dimulai sesudah menerima dan memeriksa dump terbaru setelah patch terakhir. Dump TERBARU yang telah diterima dan diperiksa untuk audit ini ialah `sla_sks_v3 (3).sql` (203.295 byte, 52 tabel; header phpMyAdmin 25-10-2026 pukul 11.01, sementara metadata Windows Properties 17.01 pada hari yang sama). `jawaban_hots_materi` **sudah ada (14 kolom)** bersama `resume_materi` (14 kolom) dan bank Guru `tugas_hots_materi` (5 kolom). **Jangan impor ulang SQL 0017 atau 0018** pada kondisi tersebut. Perbedaan waktu pada header dan Windows tidak boleh ditafsirkan sebagai perbedaan file ataupun bukti zona waktu tertentu.

Sebelum SQL baru: jalankan `qa/audit_kontrak_database_readonly.sql` di DB yang benar, verifikasi metadata dan `schema_version`; bandingkan SQL historis serta query Service; backup DB+`writable/`. Jangan impor ulang 0017 atau 0018 jika objek telah ada. Semua perubahan skema harus patch **bernomor baru**, additive, diuji terhadap seluruh tabel nyata dan diputuskan setelah dump terbaru. Jangan me-reset database, menimpa data siswa, atau menaruh dump privat di repo.

## Transisi khusus v2.5 / database BARU sla_sks_v4 (26 Oktober 2026)
- Data sumber terakhir adalah dump privat pemilik `sla_sks_v3 (4).sql`. Database **`sla_sks_v3` tidak dihapus**. Arsip pengujian dan file `writable/` harus dicadangkan.
- File privat **`sla_sks_v4_LOKAL_PRIVAT.sql`** (dibagikan langsung ke pemilik, bukan repository) sudah berisi DDL dan baris v3 penting serta patch perubahan v4. Ia menjalankan `CREATE DATABASE sla_sks_v4`; **v4 harus belum ada**.
- File publik `updates/V4-0001_POLA_BAB_TP_PRA_SEMESTER.sql` **hanya patch skema** untuk clone v3 kosong-perubahan yang sudah tersedia dalam DB `sla_sks_v4`; tidak berisi data pribadi. **JANGAN IMPOR patch ini lagi setelah mengimpor dump privat v4**, karena patch sudah digabung dan perubahan hanya untuk sekali pakai.
- Kode aplikasi MVC/Service/UI saat ini belum kompatibel penuh dengan v4. Jangan mengubah `.env` secara otomatis; cek SQL `schema_version`, FK, CP mapel dan 4 materi historis dalam arsip di [audit v4](../../docs/AUDIT-DATABASE-V3-KE-V4-2026-10-26.md).
- Tidak memakai migration/seeder. Uji MariaDB lokal dan MySQL target berbeda, bukan klaim impor telah PASS.

## V4-0009 — Jadwal Remedial dan Susulan Pra-Semester (3 November 2026)

**Basis audit:** dump lokal privat `sla_sks_v4 (13).sql`, header 03-11-2026 pukul 04.32, MariaDB 10.4.32, versi terakhir `V4-0008-KEPUTUSAN-UJIAN-BATAL`. **Jangan commit dump privat.**

1. Backup **database `sla_sks_v4` dan direktori `writable/`**, jangan reset atau impor ulang dump.
2. Pada phpMyAdmin pilih database `sla_sks_v4`, jalankan pemeriksaan read-only awal dari `database/sql/qa/V4-0009_PRA_SESI_READONLY.sql`.
3. Pastikan versi `V4-0008-KEPUTUSAN-UJIAN-BATAL` sudah ada dan `V4-0009-PRA-SESI-TERJADWAL` belum ada.
4. Impor file **`database/sql/updates/V4-0009_PRA_SESI_TERJADWAL.sql` tepat sekali**, bukan Migration/Seeder.
5. Jalankan bagian pemeriksaan **SETELAH impor** dari QA read-only: harus terdapat `jadwal_pra_sesi`, `peserta_pra_susulan`, kolom nullable `percobaan_ujian.jadwal_pra_sesi_id`, dan `schema_version V4-0009`. Jumlah attempt/hasil historis tidak boleh berubah oleh update ini.
6. Baru setelah berhasil, uji jadwal utama, remedial dan susulan lewat browser peran Admin/Guru/Siswa. Jika impor gagal, **jangan** mengulang secara membabi buta; simpan pesan galat dan lakukan audit patch parsial.

SQL ini aditif, tidak menghapus data siswa, tidak mengubah root CI4, URL, atau .env. Tidak boleh diimpor pada database selain `sla_sks_v4` yang telah diaudit.

## V4-0010 — Word Pra-Semester (A8-7, 3 November 2026)
Berdasarkan basis `V4-0009` yang telah dikonfirmasi berhasil diimpor pengguna. Patch ini **bukan** pengganti V4-0009 dan tidak mereset data.

1. Backup database `sla_sks_v4` dan folder `writable/private/` sebelum impor.
2. Pastikan `schema_version` mengandung `V4-0009-PRA-SESI-TERJADWAL`, dan **belum** mengandung `V4-0010-BANK-WORD-PRA`.
3. Di phpMyAdmin pilih database `sla_sks_v4`, impor **sekali** `database/sql/updates/V4-0010_BANK_WORD_PRA_SEMESTER.sql`.
4. Jalankan pemeriksaan baca-saja `database/sql/qa/V4-0010_BANK_WORD_PRA_READONLY.sql`. Harus muncul tabel `impor_soal_pra_docx` dan versi `V4-0010-BANK-WORD-PRA`. Jumlah soal/percobaan lama tetap sama.
5. Guru membuka `/index.php/guru/pra-semester/{paketId}`, memilih jumlah 30–200 untuk template, mengisi setiap BAB, soal, kunci ✓, kemudian Unggah → Review → Konfirmasi. Upload ke staging privat berumur dua jam.

Tidak memakai Migration/Seeder. Jangan mengunggah dump atau kunci HMAC ke repo. Jika terjadi galat impor, periksa objek yang sudah terbentuk, **jangan** langsung menjalankan ulang SQL.

## V4-0011-SERTIFIKAT — Sertifikat PDF/QR dan aset privat (3 November 2026)

Sumber audit baca-saja **dump privat** `sla_sks_v4 (15).sql` (03-11-2026 pukul 09.32, MariaDB 10.4.32, versi terakhir `V4-0010-BANK-WORD-PRA`). Kode penerbitan sertifikat A11-2 memerlukan empat tabel baru. **Tidak ada data siswa asli atau template TTD dalam patch.**

1. **BACKUP database `sla_sks_v4` dan seluruh `writable/`** sebelum impor. Jangan impor ulang dump instalasi atau SQL historis.
2. Jalankan baca-saja:
   ```sql
   SELECT versi FROM schema_version
    WHERE versi IN ('V4-0010-BANK-WORD-PRA','V4-0011-SERTIFIKAT')
    ORDER BY versi;
   SELECT table_name FROM information_schema.tables
    WHERE table_schema=DATABASE()
      AND table_name IN ('sertifikat_aset','sertifikat_aset_aktif',
                         'sertifikat','sertifikat_aktif');
   ```
   Syarat: marker V4-0010 ada, marker V4-0011 belum ada, empat tabel baru belum ada. Jika berbeda, **jangan impor**; audit dahulu.
3. Via phpMyAdmin database `sla_sks_v4`, impor **sekali** `database/sql/updates/V4-0011-SERTIFIKAT-ASSET-DAN-VERSI.sql`. **Tidak memakai CI4 Migration/Seeder**.
4. Verifikasi:
   ```sql
   SELECT versi FROM schema_version WHERE versi='V4-0011-SERTIFIKAT';
   SELECT COUNT(*) AS jumlah_sertifikat FROM sertifikat;
   SELECT COUNT(*) AS jumlah_aset FROM sertifikat_aset;
   SELECT COUNT(*) AS jumlah_pointer_aktif FROM sertifikat_aktif;
   ```
   Pada pemasangan awal ketiga jumlah harus 0. Data siswa, TP, nilai dan attempt tidak diubah.
5. Login Admin, lengkapi identitas, unggah dan aktifkan **TTD PNG**. Background boleh fallback; template unggahan harus dipratinjau dulu sebelum aktif. Pastikan `writable/private/sertifikat/` dapat ditulis tetapi **HTTP langsung ditolak**.
6. Uji penerbitan hanya untuk siswa **simulasi dengan syarat akademik yang benar-benar terpenuhi**, bukan dengan mengubah nilai asli demi melewati gate. Periksa PDF A4 landscape, QR localhost, download pemilik saja, revisi dengan alasan dan arsip digantikan.
7. Jika DDL gagal sebagian, **jangan jalankan ulang**: MariaDB dapat melakukan implicit commit; inspeksi tabel/marker dan pulihkan dari backup yang telah dibuat.

Berkas aset/PDF historis berada pada `writable/private/sertifikat/aset/` dan `writable/private/sertifikat/pdf/`. Backup dump SQL **saja tidak cukup**. Pada hosting nyata QR memerlukan domain HTTPS permanen; hasil uji localhost tidak dianggap sertifikat siap digunakan publik.

## V4-0012 — Pengumuman Global dan FAQ A13-2 (3 November 2026)

**Acuan terverifikasi:** dump SQL XAMPP privat `sla_sks_v4 (17).sql`, phpMyAdmin 5.2.1, **03 Nov 2026 pukul 16.02**, MariaDB **10.4.32**, database `sla_sks_v4`. Dump berisi **68 tabel**, tidak ada `pengumuman`, `faq`, atau `riwayat_informasi`, dan `schema_version` terakhir `V4-0011-SERTIFIKAT` sudah tercatat. Berkas dump privat **tidak** boleh diunggah ke GitHub.

### Langkah impor di XAMPP

1. **Sebelum apa pun**, backup `sla_sks_v4` ke berkas SQL privat dan folder `writable/`. Jangan mengganti dump aktif, jangan mengimpor ulang instalasi.
2. Buka phpMyAdmin → pilih **database `sla_sks_v4`** → tab SQL. Jalankan baca-saja:
   ```sql
   SELECT versi, diterapkan_pada FROM schema_version ORDER BY diterapkan_pada DESC;
   SHOW TABLES LIKE 'pengumuman';
   SHOW TABLES LIKE 'faq';
   SHOW TABLES LIKE 'riwayat_informasi';
   ```
   Versi `V4-0011-SERTIFIKAT` harus ada, `V4-0012-PENGUMUMAN-FAQ` belum ada, dan tiga tabel baru belum ada. **Bila hasil berbeda, hentikan impor dan laporkan; jangan mengulang/menimpa.**
3. Buka tab Import pada database yang sama; pilih **hanya** `database/sql/updates/V4-0012_PENGUMUMAN_FAQ.sql` dari folder hasil pull GitHub dan impor **sekali**. Tidak memakai CI4 migration/seeder.
4. Periksa **baca-saja**:
   ```sql
   SELECT versi, diterapkan_pada
   FROM schema_version WHERE versi = 'V4-0012-PENGUMUMAN-FAQ';
   SHOW CREATE TABLE pengumuman;
   SHOW CREATE TABLE faq;
   SHOW CREATE TABLE riwayat_informasi;
   SELECT COUNT(*) AS jumlah_pengumuman FROM pengumuman;
   SELECT COUNT(*) AS jumlah_faq FROM faq;
   SELECT COUNT(*) AS jumlah_riwayat FROM riwayat_informasi;
   ```
   Awal tiga tabel berisi 0 baris; jangan menambahkan pengumuman/FAQ palsu dalam SQL.
5. Login sebagai Admin → `/admin/pengumuman`, `/admin/faq`; Guru → `/guru/pengumuman`; semua akun login → `/pengumuman` dan `/bantuan`. Buat **konten pengujian tanpa identitas siswa nyata**, simpan Draf lalu Terbit, periksa tampilan antar peran. Arsipkan hanya data yang memang Anda buat untuk uji.
6. Periksa pergantian peran: Guru tidak boleh mengubah konten Pengumuman milik Guru lain; Siswa/Pimpinan hanya baca. Uji isi mirip HTML sebagai **teks biasa**, pagination, dark/light, HP, reload, status gagal simpan, dan login anonim.

**Sifat patch:** hanya tiga `CREATE TABLE` dengan FK pengguna dan satu `INSERT schema_version`; tidak mengubah data siswa, akun, ujian, nilai, progres, sertifikat atau jadwal. Riwayat sebelum/sesudah disimpan ke `riwayat_informasi` setiap kali ada CREATE/UPDATE/ARSIP lewat Service. Status `draf`, `terbit`, `arsip`; arsip terminal, tidak DELETE. Teks di-escape saat dirender.

**Jika impor gagal:** DDL MySQL/MariaDB melakukan *implicit commit*, sehingga membuat satu tabel tidak otomatis di-rollback saat pernyataan berikutnya gagal. **Jangan langsung impor ulang atau DROP hasil impor parsial.** Catat pesan gagal, `SHOW TABLES`, `schema_version`, lalu lakukan inspeksi berdasarkan backup. Patch versi ini tidak boleh dijalankan dua kali.

**Batas:** `notifikasi` internal per penerima, kebijakan privasi resmi publik, dan Hubungi Admin sebagai kontak sekolah belum dibangun pada A13-2. Perubahan SQL berikutnya kembali mewajibkan dump XAMPP **terbaru sesudah patch V4-0012** sesuai AGENTS.md.
