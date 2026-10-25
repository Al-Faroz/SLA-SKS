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

## 3. Instalasi awal tanpa migration/seeder
1. Siapkan hosting dan database MariaDB kosong, domain HTTPS dan PHP extension.
2. Upload aplikasi (kecuali data dev/test yang tidak diperlukan di produksi), `vendor/` hasil Composer; hak file/folder seminimal mungkin. `writable/` dapat ditulis PHP, directory listing mati.
3. Import **`database/sql/install/sla_sks_v2_4_full.sql`** melalui phpMyAdmin atau CLI setelah dump benar-benar dibuat/dites developer. Dump berisi skema dan pengaturan default non-pribadi, **tidak** menyisipkan password Admin umum sebagai teks biasa.
4. Buat akun Admin awal melalui tahap inisialisasi aman **sekali pakai** (atau data hash unik hasil setup), lalu nonaktifkan endpoint/script setup. Password pertama harus diganti.
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
