# 14 — Keamanan, Privasi, Root Hosting dan Audit

## 1. Keamanan CI4 dan akun
Hanya Admin membuat akun; tidak ada daftar mandiri/Google. Username siswa = NISN dan guru/staf = NIP atau username manual jika tidak punya NIP. Password awal sesuai pola akun lama bila digunakan sekolah, **langsung wajib diganti pada login pertama**; hash memakai `password_hash(PASSWORD_DEFAULT)`; akun terkunci setelah 5 kegagalan selama 5 menit; durasi sesi tidak aktif 120 menit. Sesi database dengan Secure/HttpOnly/SameSite, regenerasi saat login/ubah peran, CSRF semua mutasi, HTTPS wajib, throttle login dan verifikasi QR.

Pemberlakuan roles harus server-side sesuai `03-PERAN-DAN-HAK-AKSES.md`. Tiap request dengan ID dari URL tidak boleh melihat data siswa/mapel lain hanya karena ID valid. Pratinjau Guru/Pimpinan *read-only* dan tidak menulis progres, ujian, nilai atau audit pencapaian siswa.

## 2. Keputusan root `index.php` + `.htaccess`
**Final:** front controller `index.php` dan file rewrite `.htaccess` berada di root proyek pada hosting shared Apache/LiteSpeed yang sesuai. Tidak menyatakan bahwa memindahkan file saja otomatis aman. Karena root proyek sekaligus diakses web, pengujian **deny** untuk folder rahasia wajib lulus. `public/assets/` tetap sumber aset publik; koneksi aset boleh menggunakan rewrite URL `/assets/...` ke `/public/assets/...` yang terbatas. Jalur ini berbeda dari rekomendasi instalasi CI4 standar yang menaruh web-root di `public/`, sehingga di repo perlu menyimpan dokumentasi penyesuaian `index.php` path untuk `app/Config/Paths.php`.

### Contoh kebijakan root Apache/LiteSpeed
Cuplikan berikut menjelaskan **prinsip deny** dan bukan jaminan langsung bisa dipakai tanpa tes versi hosting. Jangan mengandalkan `.htaccess` jika server Nginx tidak mengeksekusinya.
```apache
Options -Indexes
RewriteEngine On
# Tolak semua lokasi private dan file tersembunyi
RewriteRule ^(?:app|vendor|writable|tests|docs|database|system|\.git)(?:/|$) - [F,L,NC]
RewriteRule ^(?:\.env(?:\..*)?|\.gitignore|composer\.(?:json|lock)|spark|phpunit\.xml(?:\.dist)?|.*\.sql)$ - [F,L,NC]
RewriteRule ^(?:assets|public/assets)/.*\.(?:php|phtml|phar)$ - [F,L,NC]
RewriteRule ^public/(?!assets/) - [F,L,NC]
RewriteRule ^assets/(.*)$ public/assets/$1 [L,NC]
# Front controller: aturan rewrite final harus diuji pada hosting aktual
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L,QSA]
```
Tambahkan perlindungan agar PHP di direktori aset tidak dieksekusi; izinkan ekstensi aset statis yang sah saja; apabila aturan direktori/host tidak memberi proteksi memadai, **jangan go-live** sebelum konfigurasi diperbaiki. Pastikan path `app`, `vendor`, `writable`, `database`, berkas `.env`, SQL, log dan backup tidak terekspos, termasuk melalui `/index.php/...`, path encoding, file cadangan dan *symlink*.

## 3. Checklist keamanan URL negatif
Dari browser anonim, semua akses berikut harus mengembalikan 403/404, **tidak pernah isi file**: `/.env`, `/app/Config/Database.php`, `/vendor/autoload.php`, `/writable/logs/`, `/database/sql/install/...`, `/docs/`, `/.git/config`, `/composer.json`, `/spark`, `/*.sql`, dan PHP upload berbahaya. Uji juga URL dengan *case variation*, encoded slash, nama file tersembunyi, serta akses HTTP tanpa TLS. Jika `.htaccess` tidak dieksekusi pada server, blokir deployment.

## 4. Keamanan input dan berkas
- SQL: parameter binding/Query Builder, tidak membangun query dari string input.
- XSS: teks biasa, `esc()` atau `nl2br(esc())` sesuai konteks; hindari HTML mentah pengguna.
- Upload: cek MIME sungguhan dan decoder, batas ukuran, nama acak, folder privat, anti-traversal, jangan langsung menjalankan script upload. Untuk background sertifikat, cek **rasio dan ukuran saat upload**.
- PDF unggahan: cek skrip aktif dan halaman; batasi parser. File gambar PNG TTD diberi akses Admin saja; jangan menaruh sumber TTD pada URL publik tanpa autentikasi.
- Ekspor CSV/XLSX: cegah formula injection; laporan hanya sesuai cakupan role.
- Audit: **jangan** mencatat password, kunci jawaban aktif, isi `.env`, token sesi dan NISN berlebihan. Simpan objek, pelaku, waktu, event, diff relevan dan metadata IP sewajarnya.

## 5. Privasi sertifikat
NISN dan gambar tanda tangan muncul dalam **PDF yang diunduh secara berizin**, bukan halaman verifikasi QR publik. Verifikasi publik hanya nomor, nama, mapel, semester, nilai akhir, tanggal, dan status, serta tautan versi baru saat digantikan. QR membuktikan kecocokan sertifikat terhadap catatan internal, **bukan** TTE bersertifikat.

## 6. Halaman Kebijakan Privasi
Tampilkan halaman baca publik berisi kebijakan privasi yang disahkan sekolah, versi/tanggal berlaku, kategori data yang diproses, siapa yang dapat mengakses, penyimpanan arsip serta kontak sekolah. Jangan membuat janji hukum/retensi yang tidak disahkan; sekolah bertanggung jawab atas isi kebijakannya. Form Admin hanya untuk mempublikasikan teks yang sudah disetujui sekolah, dengan histori versi dan audit.

## 7. Ketahanan ujian
Server mengontrol state/jam/attempt, tidak menerima jawaban saat jeda; soal dan kunci snapshot server. Permintaan berulang/refresh harus idempoten, bukan menambah kuota atau mengganti soal. Fullscreen dan pembatasan salin-tempel adalah mitigasi di browser, bukan klaim keamanan mutlak. Ujian sumatif dari rumah tidak menjanjikan pengawasan fisik.

## 8. Audit mutasi wajib
| Peristiwa | Data jejak minimum |
|---|---|
| Login/gagal/peran/reset sandi | actor, IP, waktu, tipe; tanpa sandi |
| Guru ditetapkan/diganti | mapel, guru asal/baru, Admin, waktu |
| BAB Siap/koreksi | BAB, versi lama/baru, guru, Admin pemutus, alasan |
| CP diterbitkan | CP, sumber, versi, pengusul, Admin |
| Ujian insiden/keputusan | percobaan, insiden, waktu server, alasan, Guru keputusan |
| Nilai ditetapkan/dikoreksi | nilai sebelum/sesudah, TP, pengusul, Admin jika diperlukan |
| Biodata/NISN diperbaiki | siswa ID, sebelum/sesudah, Admin, waktu; hindari kebocoran publik |
| Sertifikat revisi | nomor/QR ID lama/baru, template, TTD aktif, alasan, checksum |
| Pengumuman/FAQ/identitas/aset | actor, versi, status lama/baru |

## 9. Backup dan data lama
Keputusan: **backup manual melalui SOP sekolah**, bukan janji backup otomatis. Tidak ada fitur penghapusan data siswa/sertifikat. Backup yang berisi data siswa ditempatkan privat/terenkripsi dan perlu uji restore berkala. Kebijakan lama penyimpanan **media backup** berbeda dari larangan menghapus catatan siswa/sertifikat utama; jangan mengimplementasikan aksi hapus permanen tanpa kebijakan baru yang sah.
