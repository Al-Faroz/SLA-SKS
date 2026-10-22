# 15 — Instalasi, Shared Hosting, SQL Dump dan Operasional

## 1. Target deployment
- **Hosting:** shared hosting; Apache/LiteSpeed dengan `mod_rewrite`/dukungan `.htaccess` yang diverifikasi nyata. `index.php` dan `.htaccess` **di ROOT** proyek.
- **Bahasa/framework:** PHP stabil kompatibel CI4 dan seluruh paket, Composer 2 (instal dependency boleh dilakukan sebelum upload jika hosting tidak memiliki SSH), ekstensi `intl`, `mbstring`, `mysqli`/`mysqlnd`, `curl`, `gd`, `zip`, `fileinfo`, `xml`, `dom`, `opcache`, `openssl`; `memory_limit` memadai untuk Excel/PDF.
- **Database:** MySQL lokal, **MariaDB hosting**; dump SQL dan patch update wajib diuji di keduanya.
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

### Pemutakhiran v2.4 pada instalasi K1/K2 yang sudah berisi data
- **PR K2 #3 belum digabung** dan memuat model Guru global v2.2; sebagian instalasi lokal **sudah mengimpor** patch SQL K2 dan mengisi data. Jangan mengganti berkas SQL yang pernah diimpor lalu meminta pemilik mengimpornya ulang.
- Setelah dokumen v2.4 disahkan, revisi kode K2 agar memilih Gen+mapel dan penugasan Guru per Gen. Buat SQL pembaruan **baru bernomor 0003** untuk instalasi yang sudah memakai `K2-0002` (bukan CI4 Migration); pra-syarat versi wajib dicek, backup DB dan `writable/` terlebih dahulu.
- Kolom `ref_mapel.guru_id` dan riwayat Guru global K2 dipertahankan sebagai data warisan selama transisi; **jangan otomatis menyebarkan penugasan Guru lama ke seluruh Gen**. Admin menetapkan ulang masing-masing pasangan Gen–Mapel.
- Kode paket baru mengikat setiap paket ke Gen pemilik. Paket/riwayat siswa yang sudah ada tidak dihapus, di-reset atau dipindahkan ke Gen lain; bila penentuan pemilik ambigu hentikan proses dan lakukan verifikasi manual.
- Perubahan dokumen saja **tidak** menjalankan SQL dan tidak mengubah aplikasi localhost; uji DB MySQL dan MariaDB tetap wajib sebelum produksi.

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
- [ ] Database MySQL lokal dan MariaDB hosting lolos impor SQL dari nol.
- [ ] Patch update dari versi sebelumnya dapat diterapkan tanpa kehilangan data.
- [ ] HTTPS dan domain final.
- [ ] `index.php`/`.htaccess` root, semua URL privat ditolak dari browser.
- [ ] Logo/ikon tampil; data sekolah lengkap; TTD PNG valid.
- [ ] Template fallback dan template upload A4 landscape mencetak benar.
- [ ] Login, ganti sandi, satu Admin, role aktif, dan larangan approval akademik sendiri lulus.
- [ ] Siswa menjalankan materi/diagnostik/sumatif, timer/gangguan/retry aman.
- [ ] PDF sertifikat berisi NISN, QR publik **tanpa NISN**, arsip versi.
- [ ] Backup manual dan uji pemulihan telah dicatat.

## 8. Prosedur khusus reset ulang database **lokal pengembangan** v2.4
1. **Belum dieksekusi dalam PR Master ini.** Pemilik akan mengirim dump SQL lokal terkini saat implementasi dimulai. Developer harus menganalisis `schema_version`, tabel, constraint dan isi data terlebih dulu.
2. Developer menyusun **dump SQL instalasi final baru** beserta data lama yang memang diperlukan dan aman dipindah; siapkan laporan pemetaan lama→baru, jumlah objek sebelum/sesudah, hash/referensi dan indikasi data ambigu. Jangan otomatis menghapus siswa, akun, nilai atau versi hanya karena skema berubah.
3. Pemilik **membuat backup dump asli dan `writable/`**, mengonfirmasi database lokal tujuan, lalu **secara manual menghapus seluruh tabel hanya di database lokal yang telah dipilih**, kemudian mengimpor dump SQL baru melalui phpMyAdmin/CLI; lakukan cek data/relasi/login/TP/CP setelah impor.
4. **Tidak pernah melakukan DROP/TRUNCATE database produksi/hosting**. Untuk shared hosting/MariaDB, gunakan patch SQL bernomor non-destruktif dengan rencana backup/restore sendiri.
5. Tidak menggunakan CI4 Migration/Seeder; root `index.php`, `.htaccess`, `.env` dan URL lokal yang telah berfungsi **jangan diubah**.
