# 14 — Keamanan, Privasi, Root Hosting dan Audit

## 1. Keamanan CI4 dan akun
Hanya Admin membuat akun; tidak ada daftar mandiri/Google. Username siswa = NISN dan guru/staf = NIP atau username manual jika tidak punya NIP. Password awal sesuai pola akun lama bila digunakan sekolah, **langsung wajib diganti pada login pertama**; hash memakai `password_hash(PASSWORD_DEFAULT)`; akun terkunci setelah 5 kegagalan selama 5 menit; durasi sesi tidak aktif 120 menit. Sesi database dengan Secure/HttpOnly/SameSite, regenerasi saat login/ubah peran, CSRF semua mutasi, HTTPS wajib, throttle login dan verifikasi QR.

Pemberlakuan roles harus server-side sesuai `03-PERAN-DAN-HAK-AKSES.md`. Tiap request dengan ID dari URL tidak boleh melihat data siswa/mapel lain hanya karena ID valid. Pratinjau Guru/Pimpinan *read-only* dan tidak menulis progres, ujian, nilai atau audit pencapaian siswa.

### Akun, reset sandi dan profil v2.4
- Pengelola dan Siswa **tetap satu sistem login**; dua menu hanya memisahkan administrasi. Admin boleh reset per akun pada kedua daftar. Reset membuat sandi sementara CSPRNG yang hanya tampil satu kali kepada Admin; simpan hanya hash, naikkan versi sesi/batalkan seluruh sesi lama, set wajib ganti sandi, audit Admin+objek+waktu tanpa sandi, jangan pernah kirim sandi lewat URL, GET, email/WA otomatis, log atau file ekspor.
- Impor XLSX: periksa jenis berkas, MIME, batas ukuran/baris, nama sheet/header/versi, peran/Gen/NISN, duplikasi intra-file dan database, persetujuan perubahan, idempotensi, CSRF, permission saat pratinjau **dan** commit; dokumen disimpan privat sementara dan dibersihkan terkontrol. **Khusus ATP Excel, hanya INSERT ke semester dengan paket sah yang benar-benar belum mempunyai BAB/TP**, tanpa UPDATE/DELETE. Bila semester sudah terisi, file hanya boleh menghasilkan status Tetap apabila isi seluruh BAB/TP sama persis, tidak menulis. Paket dan Tahun Ajaran dipilih **per semester**, bukan diturunkan dari paket acuan. Perubahan salah satu semester membatalkan konfirmasi seluruh workbook. Penolakan dan hasil operasi dicatat tanpa kredensial.
- Ekspor XLSX hanya oleh Admin, membatasi filter/jenis Pengelola atau Siswa dan menghilangkan sandi/hash, token, sesi serta kontak pribadi yang tidak diperlukan. Netralisasi formula injection (`=`, `+`, `-`, `@`) untuk semua nilai teks dari pengguna.
- Upload foto profil staf wajib validasi MIME, ekstensi/ukuran, decode gambar dan otorisasi pemilik. Aset foto disimpan secara aman; endpoint profil profesional Guru yang dapat dilihat siswa **hanya** untuk Guru aktif yang ditugaskan pada Gen siswa, tampilkan nama, foto disetujui dan mapel; NIP, telepon pribadi, username jika sensitif, log/audit dan hash tidak tersedia.
- Kewenangan Guru untuk membuka, menilai, mengoreksi TP atau ujian memerlukan pemeriksaan **penugasan aktif Gen–Mapel** atas objek di server, bukan `ref_mapel.guru_id`, `created_by`, atau pilihan Gen dari browser. ID guessing lintas Gen ditolak dan diaudit. Validasi Guru kandidat dilakukan saat tampil dropdown dan saat transaksi commit: status aktif + role `guru`, termasuk akun staf yang memiliki role Guru dalam kombinasi yang diizinkan.

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
| TP Siap/koreksi | TP, versi lama/baru, guru, Admin pemutus, alasan |
| CP diterbitkan | CP, sumber, versi, pengusul, Admin |
| Ujian insiden/keputusan | percobaan, insiden, waktu server, alasan, Guru keputusan |
| Nilai ditetapkan/dikoreksi | nilai sebelum/sesudah, TP, pengusul, Admin jika diperlukan |
| Biodata/NISN diperbaiki | siswa ID, sebelum/sesudah, Admin, waktu; hindari kebocoran publik |
| Sertifikat revisi | nomor/QR ID lama/baru, template, TTD aktif, alasan, checksum |
| Pengumuman/FAQ/identitas/aset | actor, versi, status lama/baru |

### Audit v2.4 yang wajib ada
| Peristiwa | Bukti minimal |
|---|---|
| Tetapkan/ganti/kosongkan Guru per Gen–Mapel | Gen, mapel, Guru lama/baru, alasan, Admin, waktu |
| Impor akun Pengelola/Siswa | Versi template, checksum, jumlah tambah/ubah/tolak, Admin, waktu, status (tanpa sandi) |
| Ekspor akun Pengelola/Siswa | Admin, jenis, filter/ringkasan jumlah, waktu; tanpa menyimpan isi berkas sensitif |
| Reset sandi per akun | Admin, target, waktu, pembatalan sesi; **tanpa sandi sementara** |
| Edit profil sendiri | Aktor, jenis field/berkas, waktu; tidak membocorkan nomor pribadi di log yang tidak perlu |
| Akses profil Guru oleh siswa | Cek Gen–Mapel sebelum respons; catat penolakan akses lintas Gen yang relevan |

## 9. Backup dan data lama
Keputusan: **backup manual melalui SOP sekolah**, bukan janji backup otomatis. Tidak ada fitur penghapusan data siswa/sertifikat. Backup yang berisi data siswa ditempatkan privat/terenkripsi dan perlu uji restore berkala. Kebijakan lama penyimpanan **media backup** berbeda dari larangan menghapus catatan siswa/sertifikat utama; jangan mengimplementasikan aksi hapus permanen tanpa kebijakan baru yang sah.

## 10. Perlindungan format dan aset baru v2.4
- Generator template Excel per Gen–Mapel/Kurikulum dan Word per TP memakai identitas **dari sesi/penugasan terotorisasi**, checksum dan token/signature berbasis secret server, bukan kepercayaan pada file yang dapat diedit. Impor ulang wajib cek role, Gen–Mapel, status TP, versi, anti replay/idempotensi, batas ukuran/ZIP bomb dan anti formula injection.
- DOCX diurai dengan parser OOXML terkendali: **no macro**, no XXE, no external remote fetch otomatis, no `altChunk` HTML tak tepercaya, batas nesting tabel/media, sanitasi render agar XSS/SSRF tertolak. Formula OMML dan tabel bersarang disimpan sebagai konten kaya yang aman, bukan dipaksakan teks biasa.
- Google Drive hanya URL domain terpercaya hasil parsing ketat, lalu memerlukan pratinjau pemutaran yang nyata; tidak menyalin atau mengeksekusi remote HTML. Jangan menganggap status tautan publik Google selalu tersedia atau membuka kredensial Guru.
- Bank soal/kunci/snapshot ujian hanya dapat diakses Guru/Admin berhak; siswa tidak menerima kunci sebelum kebijakan ujian mengizinkan. File unggahan/hasil parser/gambar disimpan privat dan melalui controller cek izin. Aset lama dipertahankan untuk percobaan historis.
- Logo aplikasi diunggah dengan validasi MIME sungguhan, dimensi dan batas ukuran serta pemeriksaan decode; buat varian favicon aman dan ganti pointer aktif atomik, jangan izinkan SVG/script atau unggahan berbahaya disajikan dari origin aplikasi.


### Mitigasi clipboard pada jawaban tertulis siswa (26 Oktober 2026)

Browser S09/S10 menolak paste, copy, cut dan drop pada textarea sebagai
pengamanan best-effort. Pengamanan klien dapat dilewati sehingga bukan
bukti anti-plagiarisme; tidak ada hukuman otomatis. Validasi panjang,
snapshot terpin, akses, final immutable dan audit tetap di server,
sedangkan indikator kesamaan Master 08 hanya untuk tinjauan Guru.

## 11. Audit v4, Excel manual, Pra-Semester dan ekspor (26 Oktober 2026)
- Dump v3/v4 lokal mengandung NISN, nama, hash sandi, sesi dan jawaban siswa: **lokal PRIVAT saja, dilarang commit/push/PR**. File aset `writable/` backup terpisah; `.env` tidak diubah tanpa izin.
- Excel ATP final **manual `.xlsx` tanpa macro atau tombol Proses**. Tolak file `.xlsm` dan konten VBA/eksternal, hanya baca/validasi sel yang disepakati; cek ZIP/container, ukuran, ekstensi, MIME, formula dan referensi eksternal. Unduhan/unggahan tetap terikat konteks Gen–Mapel dan CSRF.
- Nomor/kode BAB/TP server-side, validasi lintas dua semester dan hak guru. Esensial tidak boleh menciptakan progres atau nilai; NonEsensial harus tepat satu Materi aktif dan snapshot historis tidak dihapus.
- Checkpoint klien 4 menit tidak memberi kepercayaan penuh pada waktu browser; Service membatasi delta sah dan memerlukan bukti tersimpan sebelum membuka Resume/HOTS/download. Simpan waktu lokal offline hanya sementara pada perangkat sama.
- Pra-Semester: Admin jadwal, belum selesai TP/di luar jendela waktu ditolak, izin Guru hanya Gen–Mapel, jawaban/snapshot/insiden ujian anonim bagi pihak tak berhak.
- Export Nilai Guru tersedia saat satu skor ada tetapi tetap membatasi semua baris ke Gen–Mapel pengampu, tanpa hash/token/sesi, mitigasi formula-injection, respons file tidak boleh terindeks/cache publik.
