# 15 — Instalasi, Shared Hosting, SQL Dump dan Operasional

## 1. Target deployment
- **Hosting:** shared hosting; Apache/LiteSpeed dengan `mod_rewrite`/dukungan `.htaccess` yang diverifikasi nyata. `index.php` dan `.htaccess` **di ROOT** proyek.
- **Bahasa/framework:** PHP stabil kompatibel CI4 dan seluruh paket, Composer 2 (instal dependency boleh dilakukan sebelum upload jika hosting tidak memiliki SSH), ekstensi `intl`, `mbstring`, `mysqli`/`mysqlnd`, `curl`, `gd`, `zip`, `fileinfo`, `xml`, `dom`, `opcache`, `openssl`; `memory_limit` memadai untuk Excel/PDF.
- **Database:** XAMPP lokal menggunakan **MariaDB 10.4.32 menurut header dump lama**; server aktif wajib diverifikasi dari dump terbaru. Shared hosting/MariaDB masih **target, belum ada aplikasi yang diunggah/deploy**. Kompatibilitas hosting dipersiapkan, bukan diklaim telah diuji di hosting.
- **Alamat publik:** domain HTTPS permanen untuk QR dan cookie aman. Nama/identitas sekolah diisi Admin saat setup.
- **Aset:** CSS kustom + JavaScript native tanpa bundler; PDF.js + page-flip ringan disajikan lokal; YouTube untuk video; PhpSpreadsheet, Dompdf, endroid QR untuk dokumen.
- **Skala:** satu kelas khusus maksimum per Gen, jumlah akun/berkas kecil. Ukuran server dihitung dari gambar/PDF per TP, bukan dari jumlah siswa semata.

## 2. Struktur ROOT wajib
```text
sla-sks/
├── index.php              # front controller ROOT
├── .htaccess              # rewrite + deny ROOT
├── .env                   # privat, tidak masuk Git
├── .env.example           # templat tanpa rahasia
├── composer.json
├── composer.lock
├── spark
├── app/
├── public/assets/        # file statis yang boleh publik
├── vendor/
├── writable/             # berkas siswa dan hasil PDF privat
├── database/sql/         # berkas install/update; harus tidak terbaca publik
├── docs/
└── tests/
```

`index.php` ROOT harus menggunakan jalur yang benar menuju `app/Config/Paths.php` (karena berpindah dari `public/`); sesuaikan rute aset `/assets/...`. Gunakan contoh aturan deny di `14-KEAMANAN-DAN-AUDIT.md`, tetapi **uji HTTP nyata**; jangan hanya percaya file `.htaccess` berada di folder.

### Checkpoint calon produksi v5 / GEN2025 — 8 November 2026

Untuk deployment **baru** dari klon bersih `sla_sks_v5`, ikuti [checkpoint hosting v5 dan Admin privat](CHECKPOINT-HOSTING-V5-ADMIN-DAN-OPERASIONAL-20261108.md). Hasil ringkasan pemeriksaan setelah pembersihan: 15 CP, 17 G1 GEN2025, 17 siswa semester 5, FAQ 1 terbit +27 draf, BAB/TP/ujian/sertifikat 0. Ini **belum** berarti backup-restore, PHP/MariaDB hosting atau security HTTP target sudah diuji. Database `sla_sks_v4` beserta `.env` XAMPP tetap tidak berubah. SQL privat v5 tidak masuk GitHub.

## 3. Instalasi awal tanpa migration/seeder
1. Siapkan hosting dan database MariaDB kosong, domain HTTPS dan PHP extension.
2. Upload aplikasi (kecuali data dev/test yang tidak diperlukan di produksi), `vendor/` hasil Composer; hak file/folder seminimal mungkin. `writable/` dapat ditulis PHP, directory listing mati.
3. **Untuk hosting SLA-SKS v2.5 GEN2025**, impor hanya snapshot **privat v5 yang sudah dibersihkan dan dicadangkan** ke database hosting kosong melalui phpMyAdmin. **Jangan mengimpor ulang dump lama v2.4, v4, atau patch berulang**. Basis data hosting dapat bernama dengan prefix akun; periksa hasil marker V5 dan jumlah CP/Guru/Siswa sebelum membuka akses. Snapshot privat/dump identitas siswa tidak masuk GitHub.
4. Setelah snapshot v5 siap di hosting, buat Admin **baru** melalui SQL privat dari `php spark sla:admin-hosting-sql` **yang dijalankan di XAMPP lokal tanpa mengubah database lokal**, lalu impor berkas hasilnya ke phpMyAdmin hosting; sandi acak dicetak sekali dan wajib diganti saat login. **Jangan** menjalankan `sla:admin-awal` karena pengguna Guru/Siswa sudah ada, atau membuka endpoint setup web. Buat Pimpinan kemudian melalui Admin/Pengelola.
5. Salin `.env.example` menjadi `.env` secara privat; isi koneksi DB, `app.baseURL`, HTTPS, session/CSRF, zona Asia/Jakarta. Jangan push `.env`.
6. Login Admin dan lengkapi **seluruh data madrasah wajib** (nama, alamat, NPSN, NSM, telepon, email, kota, kepala madrasah dan NIP); situs web boleh kosong. Upload logo, ikon dan **PNG tanda tangan wajib untuk sertifikat**; latar sertifikat optional karena ada template bawaan.
7. Atur Gen, siswa, Guru, mapel, satu Guru **per pasangan Gen–Mapel**, kurikulum dan referensi CP. Guru menyusun ATP enam semester melalui Excel lalu melengkapi konten Materi/HOTS langsung di aplikasi serta mengimpor Word Diagnostik/Sumatif per TP.
8. Jalankan checklist rute, akses privat, ujian fullscreen dan QR sebelum dipakai siswa.

### Pemutakhiran skema lama ke v2.4
- Repository sudah melewati K1/K2/K3A, sedangkan PR #13 masih memakai struktur BAB lama. **Jangan mengasumsikan nomor patch SQL terakhir**; periksa dump lokal dan `schema_version` aktual. Patch yang pernah diterapkan tidak boleh ditimpa/diedit dan diminta impor ulang.
- Untuk **lokal pengembangan**, setiap tugas DB dimulai dari dump SQL **terbaru sesudah update terakhir** → pemetaan tabel/relasi/patch → patch additive bernomor baru dan pemeriksaan baca-saja → backup → pengguna impor sendiri. **Reset tabel bukan prosedur baku** dan memerlukan permintaan/izin eksplisit khusus database lokal terpilih (§8).
- Untuk hosting/produksi, perubahan harus memakai **SQL pembaruan bernomor lebih tinggi dari yang telah diterapkan**, menjaga data dan audit, serta diuji sebagai patch non-destruktif di database salinan, **bukan reset tabel**.
- Guru mengikuti Gen–Mapel dan paket/TP khusus Gen, histori progres/sertifikat dipertahankan. Pemetaan ambigu wajib diperiksa sebelum konversi.

## 4. Pembaruan versi tanpa migration
1. Baca catatan release/versi SQL saat ini dari `schema_version`.
2. Jadwalkan pemeliharaan sederhana dan lakukan **backup manual lengkap** DB+file sebelum impor patch.
3. Upload kode yang kompatibel; impor SQL `database/sql/updates/NNNN_*.sql` **berurutan** via phpMyAdmin/CLI. Patch harus preserve data produksi, tidak `TRUNCATE`/`DROP DATABASE`.
4. Periksa laporan error SQL; catat versi setelah sukses; bila DDL gagal, pemulihan melalui backup (DDL MySQL/MariaDB tidak selalu rollback transaksional).
5. Perbarui aset versi cache, uji login, sertifikat, sumatif, dan halaman data. Nonaktifkan maintenance.
6. Tidak menjalankan `php spark migrate`, `db:seed`, atau impor ulang dump penuh yang menimpa data produksi.

## 5. Shared hosting dan timer ujian
Batas heartbeat 20 detik **dihitung menurut waktu server**. Karena shared hosting mungkin hanya menyediakan cron tiap 1–5 menit, Service `UjianService` harus menjalankan rekonsiliasi *lazy* setiap request dan setiap heartbeat, bukan bergantung pada cron untuk menentukan validitas jawaban. Bila cron lebih lambat, `last_seen +20 detik` tetap menjadi waktu ambang. Pesan jika koneksi putus ditampilkan klien saat terdeteksi; server menyelesaikan keadaan pada request selanjutnya. Jangan menjanjikan pembatalan notifikasi tepat detik-ke-20 tanpa worker/scheduler yang memadai.

## 6. Backup manual dan pemulihan
SOP minimal: Admin/operator hosting mengambil **dump database dan salinan `writable/`**, menyimpan di tempat berbeda, mengunci akses, mencatat tanggal, dan mencoba restore di lingkungan uji secara periodik. Jangan menyimpan backup di direktori publik atau repo. Keputusan tanpa penghapusan data berarti prosedur pemulihan tidak boleh mengganti data baru dengan dump lama tanpa verifikasi kerugian data.

## 7. Monitoring sederhana
Dashboard Admin A11: sisa disk, status permintaan pemeriksaan ujian terjadwal, masalah render PDF/QR, akun terkunci, berkas template/TTD hilang, antrean koreksi, log error dan notifikasi gagal. Jika disk habis, proses unggah/PDF harus gagal aman, bukan menerbitkan dokumen rusak.

## 8. Checklist go-live
- [ ] PHP dan ekstensi sesuai paket `composer.lock`.
- [ ] Dump final MySQL lokal dan skema instalasi MariaDB hosting lolos pengujian pada database uji kosong; patch produksi diuji non-destruktif.
- [ ] Patch update dari versi sebelumnya dapat diterapkan tanpa kehilangan data.
- [ ] HTTPS dan domain final.
- [ ] `index.php`/`.htaccess` root, semua URL privat ditolak dari browser.
- [ ] Logo/ikon tampil; data sekolah lengkap; TTD PNG valid.
- [ ] Template fallback dan template upload A4 landscape mencetak benar.
- [ ] Login, ganti sandi, satu Admin, role aktif, dan larangan approval akademik sendiri lulus.
- [ ] Siswa menjalankan materi/diagnostik/sumatif, timer/gangguan/retry aman.
- [ ] PDF sertifikat berisi NISN, QR publik **tanpa NISN**, arsip versi.
- [ ] Backup manual dan uji pemulihan telah dicatat.

## 8. SOP SQL untuk pengembangan XAMPP lokal (kondisi aktual)
1. **Belum ada deployment ke hosting**. Seluruh implementasi dan uji manual pada XAMPP lokal; jangan menyatakan pengujian hosting telah dilakukan. Header dump sebelumnya mencatat MariaDB 10.4.32 dan harus divalidasi lagi dari dump terbaru.
2. Sebelum **setiap** tugas SQL/database, minta dump SQL XAMPP terbaru yang benar-benar terunggah **sesudah impor patch terakhir**. Baca nama database, waktu ekspor, versi engine, CREATE TABLE/INDEX/FK/CHECK, baris historis yang relevan, dan `schema_version`.
3. Peta kontrak Master 05/10 terhadap semua Service dan tabel yang ada. Contoh wajib: `tugas_hots_materi` (bank pertanyaan Guru) tidak boleh tertukar dengan `jawaban_hots_materi` (pengumpulan siswa). Jangan membuat tabel atau indeks duplikat ketika patch historis sebagian sudah ada.
4. Patch **nomor baru** bersifat additive; sertakan QA baca-saja sebelum/sesudah, uji DDL di MariaDB versi lokal aktual pada skema terisolasi/tersanitasi, pemeriksaan phpMyAdmin, dan rencana pemulihan berbasis backup (DDL tidak otomatis rollback).
5. Pemilik melakukan backup database dan `writable/`, memilih target serta mengimpor patch sendiri. **Dilarang DROP/TRUNCATE/reset tanpa permintaan dan izin eksplisit**, meskipun masih XAMPP lokal. Jangan mengubah `.env`, root front controller atau URL yang sudah FIX tanpa alasan dan izin.
6. Ketika hosting benar-benar disiapkan kelak, lakukan pemeriksaan kompatibilitas MariaDB/PHP/Apache/LiteSpeed/HTTPS dan akses privat secara terpisah sebelum upload. SOP hosting pada §3–7 adalah **rencana deployment**, bukan laporan bahwa aplikasi telah diunggah.

## 9. SOP instalasi khusus `sla_sks_v4` (26 Oktober 2026)
1. Sumber privat: dump `sla_sks_v3 (4).sql`, dibuat 26-10-2026 09:36 oleh MariaDB 10.4.32. Backup ulang `sla_sks_v3` **dan `writable/`** dahulu. Jangan hapus sumber v3.
2. **Berkas lama `sla_sks_v4_LOKAL_PRIVAT.sql` digantikan R2.** Pemilik telah mengimpor `sla_sks_v4_LOKAL_PRIVAT_DIPERBAIKI_R2.sql` ke DB baru `sla_sks_v4` menggunakan phpMyAdmin dengan 268 kueri. Penanda final `V4-0001-R2`. Jangan mengimpor ulang file lama, R2, atau patch SQL repo pada database v4 yang sudah terpasang; sebelum update berikutnya lakukan backup dan gunakan patch bernomor BARU. DB `sla_sks_v3` tetap tidak disentuh.
3. **QA awal lokal 27-10-2026 PASS:** 58 tabel, 126 FK, penanda `V4-0001-R2`, 37 pengguna, 17 siswa, 20 mapel, 0 BAB, 4 Materi historis, 2 percobaan historis. Ini hanya QA struktur/jumlah awal, bukan bukti seluruh relasi dan alur UI. Untuk perubahan berikutnya tetap jalankan QA baca-saja plus pengujian CI4.
4. **Jangan mengalihkan `.env` ke v4 selama kode MVC/Service/UI masih kontrak lama.** Sesudah penerapan kode v2.5 dan QA, pemilik dapat memilih DB v4 lokal dan menjalankan pengujian. URL `http://localhost/sla-sks/`/root index.php tidak berubah.
5. MySQL lokal dan MariaDB hosting **perlu dua tes terpisah**; belum ada deployment hosting sehingga jangan menyatakan lolos di sana. Dump PII/backup/hash tidak dipush GitHub.
6. Template ATP Excel **manual tanpa macro** sudah dibuat sebagai berkas lokal; pemasukan template ke `docs/`, generator/validator impor, dan implementasi aplikasi dijadwalkan setelah fondasi dokumen/DB ini.


## Catatan transisi keputusan edit CP/BAB/TP (2 November 2026)
- Revisi 16 Master Document ini **dokumentasi aturan bisnis**, bukan SQL atau patch program; tidak ada alasan mengubah `.env`, konfigurasi localhost, root `index.php`/`.htaccess`, atau data XAMPP hanya untuk menyimpan dokumen.
- Jalur kategori A/B/C/approval untuk TP Siap pada branch fitur terdahulu **dibatalkan**. **Jangan impor `V4-0006_KOREKSI_TP_SIAP.sql`** untuk menerapkan aturan baru. Jika sudah pernah diimpor pengguna, jangan hapus tabel tanpa dump terbaru, backup dan izin eksplisit.
- Implementasi form CP **tepat lima field** mungkin perlu pembaruan validasi/SQL untuk membuat metadata lama tidak wajib; audit dump privat XAMPP terbaru terlebih dahulu. Bila skema harus berubah, sediakan patch SQL incremental yang kompatibel MySQL/MariaDB; tidak memakai Migration/Seeder.
- Setiap implementasi cek G1, status penggunaan siswa, atomisitas penyimpanan/snapshot, akses berkas privat, regresi Word dan pengujian XAMPP sebelum merge.

### Instalasi patch sertifikat V4-0011 (3 November 2026)

1. Backup database `sla_sks_v4` dan folder `writable/` **sebelum pull/impor**. Periksa `SELECT versi FROM schema_version WHERE versi IN ('V4-0010-BANK-WORD-PRA','V4-0011-SERTIFIKAT');`: V4-0010 harus ada, V4-0011 belum ada. Pastikan tidak ada empat tabel sertifikat dalam schema lama.
2. Pull branch A11-2; impor **sekali** `database/sql/updates/V4-0011-SERTIFIKAT-ASSET-DAN-VERSI.sql` di phpMyAdmin database yang benar. **Jangan menjalankan Migration/Seeder atau mengimpor ulang dump lama**.
3. Periksa `schema_version`, empat tabel baru, isi awal 0; cek kembali jumlah siswa, TP, progres dan nilai dengan query SELECT biasa (seharusnya tidak berubah).
4. Login Admin → **Sertifikat → Template & TTD**. Background A4 landscape boleh fallback, TTD PNG wajib; lakukan pratinjau sebelum aktivasi background. Pastikan folder `writable/private/sertifikat/{aset,pdf}` dapat ditulis dan tidak bisa diakses langsung lewat HTTP.
5. Uji penerbitan hanya memakai akun siswa simulasi yang **memenuhi seluruh syarat TP/Final**. Jika belum ada siswa layak, jangan mengubah nilai siswa nyata hanya untuk memunculkan tombol. Uji PDF, nilai 0, QR, idempotensi, revisi & arsip, akses siswa berbeda, serta tautan QR localhost.
6. Sebelum dipakai untuk sertifikat nyata, atur domain HTTPS permanen untuk `site_url` agar QR tidak berisi tautan localhost. Perubahan domain/URL tetap memerlukan persetujuan pemilik dan uji hosting tersendiri.

Jika impor DDL gagal di tengah, jangan mengulang tanpa audit keadaan parsial dan backup; MariaDB DDL mungkin melakukan implicit commit. Tidak ada reset/hapus data.

### A12-4 — Batas indikator operasional di Dashboard Admin (3 November 2026)

Dashboard Admin menampilkan sisa ruang disk dari `disk_free_space(WRITEPATH)` pada volume penyimpanan `writable/`. Angka tersebut bukan prediksi aman hosting atau ukuran sisa kuota shared hosting, dan bisa tidak tersedia jika lingkungan menolak pembacaan. **Status cron dan backup sengaja ditampilkan `Belum diperiksa`** hingga tersedia pemeriksaan terintegrasi yang benar-benar dijalankan. Prosedur backup SQL dan berkas `writable/private/` tetap manual sesuai SOP; jangan menandai PASS hanya karena kartu dashboard bisa dimuat. TTD yang diaktifkan diperiksa dengan hash SHA-256 saat dashboard dibaca, tanpa membeberkan isi/metadata privat kepada pengguna. Tidak memerlukan perubahan .env, instalasi tugas terjadwal, ataupun SQL.


### A14-1 — SOP uji HTTP anonim XAMPP setelah perubahan `.htaccess` (4 November 2026)

Sesudah review dan pull branch A14-1, jalankan dari `G:\\xampp\\htdocs\\sla-sks` dengan PHP CLI XAMPP:
```bat
php scripts/qa/http_security_smoke.php http://localhost/sla-sks/
```
Tidak memerlukan kredensial, dump SQL, reset DB, HTTP POST atau perubahan `.env`. Script menguji aset statis publik (harus 200), berkas internal (403/404), route panel anonim (redirect login/401/403), dan Kebijakan Privasi (200 jika versi resmi berlaku, 503 jika belum diterbitkan). Hasil FAIL harus diperbaiki sebelum hosting. **Jangan membuka URL pembaruan ATP di browser untuk menguji penolakan**, karena pada server yang mengabaikan `.htaccess` hal tersebut dapat menjalankan skrip; pengujian otomatis hanya memakai URL tanpa efek mutasi. Pastikan Apache XAMPP mengaktifkan `mod_rewrite` dan izin `AllowOverride` secara benar; jangan mengubah root atau URL jika status gagal tanpa analisis. QA di GitHub Actions tidak menggantikan tes XAMPP dan hosting.


### Ketentuan final A14-2 setelah impor V4-0014 — 4 November 2026
Pemilik sudah mengimpor V4-0013 dan V4-0014 pada 04-11-2026 16:02:43/16:02:59 WIB. **Tidak diperlukan dan tidak boleh ada impor ulang**. Koreksi skor Resume/HOTS langsung oleh Guru G1 tanpa approval Admin. Penerbitan pertama dan versi pengganti sertifikat pribadi dilakukan otomatis saat siswa membuka menu Sertifikat, jika layak, identitas dan TTD siap; Admin hanya mengelola aset/identitas dan melihat audit. PDF/QR histori tetap privat. Akses GET Sertifikat menulis hasil penerbitan hanya pada data akun siswa sendiri; jangan menguji dengan akun siswa nyata sebelum backup DB + `writable/`. Lihat Master 08,09,14,16.
