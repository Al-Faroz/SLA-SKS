# 14 — Keamanan, Privasi, Root Hosting dan Audit

## 1. Keamanan CI4 dan akun
Hanya Admin membuat akun; tidak ada daftar mandiri/Google. Username siswa = NISN dan guru/staf = NIP atau username manual jika tidak punya NIP. Password awal sesuai pola akun lama bila digunakan sekolah, **langsung wajib diganti pada login pertama**; hash memakai `password_hash(PASSWORD_DEFAULT)`; akun terkunci setelah 5 kegagalan selama 5 menit; durasi sesi tidak aktif 120 menit. Sesi database dengan Secure/HttpOnly/SameSite, regenerasi saat login/ubah peran, CSRF semua mutasi, HTTPS wajib, throttle login dan verifikasi QR.

Pemberlakuan roles harus server-side sesuai `03-PERAN-DAN-HAK-AKSES.md`. Tiap request dengan ID dari URL tidak boleh melihat data siswa/mapel lain hanya karena ID valid. Pratinjau Guru/Pimpinan *read-only* dan tidak menulis progres, ujian, nilai atau audit pencapaian siswa.

### Akun, reset sandi dan profil v2.4
- Pengelola dan Siswa **tetap satu sistem login**; dua menu hanya memisahkan administrasi. Reset per akun harus membatalkan sesi lama (naikkan versi sesi), menyimpan hanya hash dan mengaudit Admin+objek+waktu tanpa sandi. Untuk akun dengan peran Admin, gunakan CSPRNG acak tampil sekali serta wajib ganti. Guru/Pimpinan/Siswa menggunakan sandi default **yang diketik Admin pada form reset** (8–200 karakter, konfirmasi), tidak ditampilkan ulang, dan hanya mendapat anjuran ganti di Profil. Flag lama `wajib_ganti_sandi` tetap menjadi penanda sandi awal; AuthService hanya memberlakukannya sebagai pemblokir bagi akun yang memiliki peran Admin, tanpa perlu SQL. Dilarang mengirim sandi lewat URL, GET, email/WA otomatis, log atau file ekspor.
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
| TP Siap / sunting sebelum digunakan | TP, guru G1, status/penggunaan, perubahan dan waktu; tanpa reviewer Admin |
| CP ditambah/disunting | CP, Admin pelaku, field sebelum/sesudah, waktu dan versi audit bila tersedia; tidak memerlukan data sumber hukum fiktif |
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
- Nomor/kode BAB/TP server-side, validasi lintas dua semester dan hak guru. Jenis TP tidak boleh digunakan untuk menolak pembuatan Materi Guru; kedua jenis TP wajib memiliki tepat satu Materi lengkap. Pembatasan progres/nilai siswa untuk Esensial tetap diawasi di Service siswa; snapshot historis tidak dihapus.
- Checkpoint klien 4 menit tidak memberi kepercayaan penuh pada waktu browser; Service membatasi delta sah dan memerlukan bukti tersimpan sebelum membuka Resume/HOTS/download. Simpan waktu lokal offline hanya sementara pada perangkat sama.
- Pra-Semester: Admin mengelola jadwal utama, remedial dan susulan per Gen–Mapel–Semester; belum selesai TP/di luar jendela mulai ditolak. Guru G1 hanya mengelola bank/KKM dan membaca insiden pada Gen–Mapel sendiri, bukan mengizinkan susulan. Jawaban, snapshot dan insiden ujian privat bagi pihak tak berhak.
- Export Nilai Guru tersedia saat satu skor ada tetapi tetap membatasi semua baris ke Gen–Mapel pengampu, tanpa hash/token/sesi, mitigasi formula-injection, respons file tidak boleh terindeks/cache publik.


### Ketetapan PDF Materi 1 November 2026

Validasi bisnis pada file PDF Materi hanya **jumlah halaman 6–40** dan **ukuran maksimal 15 MB**. Pemeriksaan `ObjStm`, token/struktur internal PDF, metadata maupun aksi PDF bukan syarat tambahan saat unggah. Ekstensi/MIME mengenali jenis file PDF; kontrol server yang terpisah seperti hak Guru G1, status TP draf, CSRF, endpoint media privat, CSP sandbox dan transaksi penyimpanan tidak diubah. Tidak ada file historis yang dihapus.

## 12. Keamanan perubahan CP/BAB/TP — final 2 November 2026
- **CP**: hanya Admin aktif yang boleh mengubah melalui endpoint sah dan CSRF. Boleh disunting saat dipakai TP/siswa; hubungan ID referensi tetap konsisten, audit perubahan dicatat. Tidak menuntut kategori permohonan atau otoritas penerbit/URL/tanggal berlaku pada form.
- **BAB**: Guru yang diizinkan oleh G1 dapat memperbaiki isian BAB yang tersedia meskipun TP dalam BAB sudah Siap; pertahankan validasi nomor/nama/deskripsi dan audit. Tidak menambah fitur pemindahan TP antar-BAB.
- **TP**: guard server **berdasarkan bukti satu siswa pernah menggunakan TP**, bukan status Siap. Saat siswa sudah mulai, semua perubahan konten TP, KKM, materi/media, bank soal, Word dan endpoint historis ditolak. Sebelum digunakan, perubahan Draf maupun Siap diizinkan untuk Guru G1 dan harus aman terhadap race condition dengan siswa yang mulai di waktu sama.
- **Tidak ada bypass** melalui status yang diubah paksa, URL ID, batch import, upload, PATCH/POST API maupun persetujuan kategori A/B/C yang telah dibatalkan. Snapshot nilai/percobaan dan audit tidak dihapus. Tidak mencatat password, berkas siswa atau data pribadi dalam GitHub.


### Proteksi Pra-Semester (3 November 2026)
Bank soal/kunci Pra-Semester hanya untuk Guru G1 dan server ujian; tidak pernah tampil dalam respons siswa. Admin hanya boleh jadwal/durasi per paket, bukan soal/nilai. Pemeriksaan kelayakan seluruh TP, status bank, jendela boleh mulai, peserta remedial/susulan dan perangkat dilakukan **di server dalam transaksi sebelum attempt dibuat**, bukan hanya pada UI. Bank/KKM/durasi yang digunakan dalam ujian tidak boleh berubah saat sudah ada percobaan. Audit jadwal, pembatalan otomatis, susulan berulang, partisipasi siswa, dan izin berjenjang dipertahankan. Token, jawaban, soal/kunci tidak disertakan pada file ekspor publik atau log yang tidak berizin.

### A8-7 — Keamanan Word Pra-Semester
- DOCX maksimum 15 MB; validasi berkas ZIP/OOXML, batas jumlah part, tolak macro, HMAC identitas, opsi/kunci, nomor BAB terdaftar. Renderer hanya menghasilkan HTML whitelist, sumber eksternal tidak dieksekusi. Staging dan gambar di bawah `writable/private/` dengan larangan akses langsung.
- Konfirmasi melock paket dan ujian, memverifikasi checksum, masa berlaku, kepemilikan Guru, sidik jari BAB/bank serta belum ada attempt siswa. Semua INSERT dan audit berada dalam transaksi; error rollback. Gambar siswa harus melewati autentikasi dan snapshot token, bukan URL file asli.

### A11-2 — Proteksi sertifikat, TTD dan QR (3 November 2026)

- Gambar template hanya PNG/JPEG valid; TTD wajib PNG. Periksa ekstensi, MIME melalui `finfo` dan decoder gambar, dimensi/rasio/ukuran; re-encode dengan GD agar metadata aktif tidak ikut. Berkas disimpan `writable/private/sertifikat/aset` dengan nama acak/checksum, bukan URL publik. TTD sumber hanya GET berizin Admin; PDF privat dapat diunduh Admin atau Siswa pemilik saja. Tidak menggunakan iframe URL terbuka untuk sumber PNG.
- PDF Dompdf dengan `isRemoteEnabled=false`, `isPhpEnabled=false`, sanitasi HTML, QR hanya menyimpan URL token acak. Token 24 byte acak (hex48), indeks UNIQUE, tidak memuat PII; informasi publik diproyeksikan secara whitelist tanpa NISN, TTD, kontak, PDF, alasan revisi. Per-IP verifikasi menggunakan throttler server.
- Penerbitan dan revisi hanya POST+CSRF+Admin aktif; validasi paket, Gen, NISN dan kelayakan TP di Service; percobaan Sumatif `selesai_sah` wajib. Simpan snapshot immutable + checksum, perbarui pointer aktif transaksional; sertifikat lama `digantikan` tanpa dihapus.
- Backup wajib memuat DB **dan** seluruh `writable/private/sertifikat/`; perubahan template aktif tidak mengubah PDF historis. Larang direktori private/SQL tersaji via front-controller ROOT, termasuk encoded-path dan dotfiles.
- QR pada XAMPP menunjuk localhost dan hanya untuk pengujian; dokumen produksi memerlukan domain HTTPS permanen untuk verifikasi stabil.

### A13-2 — Audit, transaksi dan isolasi Pengumuman/FAQ (3 November 2026)

- Semua akun login aktif yang memenuhi kebijakan sandi per peran (non-Admin boleh memakai sandi awal; pemegang Admin tetap wajib mengganti) dapat membaca FAQ Terbit dan Pengumuman Admin Terbit. Khusus Siswa, Pengumuman Guru Terbit dibatasi pada Gen yang diampu Guru pembuat. Draf dan Arsip tidak boleh keluar dari Service pembaca.
- Admin mengelola semua Pengumuman dan FAQ; Guru hanya Pengumuman yang dibuatnya sendiri; Pimpinan/Siswa hanya baca. Filter role di Routes dan otorisasi ulang pada `InformasiGlobalService`; staf dua peran tidak menggabungkan hak.
- Setiap POST memakai CSRF global, panjang judul/isi, status, urutan divalidasi di server. Teks disimpan plain dan di-`esc()` dalam semua View.
- Penyuntingan/pengarsipan memakai row lock MySQL/MariaDB `FOR UPDATE`, `versi` untuk menolak permintaan tab lama, dan transaksi yang menyimpan perubahan bersama `riwayat_informasi` serta `log_aktivitas`. Arsip terminal, tidak DELETE.
- Dump SQL privat yang menjadi acuan tidak dipublikasikan. Patch `V4-0012` hanya DDL + penanda versi, tidak menyisipkan akun/siswa nyata atau sandi.
- Belum ada modul Notifikasi internal per penerima, badge belum dibaca, ataupun email/WhatsApp/push.


### A13-3 — Privasi kontak resmi (3 November 2026)

`GET /hubungi-admin` dilindungi filter login dan verifikasi ulang role di `KontakResmiMadrasahService`. Query `pengaturan` hanya memilih kunci kontak sekolah resmi; informasi pribadi `profil_pengelola` dan data akademik tidak dimuat. Semua nilai HTML di-escape, termasuk atribut URL; tautan eksternal hanya HTTP(S) sah, email valid dan telepon yang dinormalisasi. Nilai legacy invalid hanya teks. No-store, tanpa mutasi, tiket, audit event palsu, data siswa maupun notifikasi otomatis.


### A13-5 — Anti-kebocoran Pengumuman lintas Gen (4 November 2026)
Pengumuman yang dibuat di peran Admin tampil global; yang dibuat di peran Guru hanya tampil kepada **Siswa** pada Gen yang saat ini ditugaskan kepada Guru tersebut. Catatan awal `riwayat_informasi` menentukan asal peran (termasuk akun rangkap Admin/Guru), bukan hak yang dimiliki saat membaca atau `diubah_oleh` sesudah Admin menyunting. Pengumuman tanpa catatan asal yang konsisten tidak ditampilkan ke pembaca. Query `EXISTS` memeriksa siswa terdaftar Gen, penugasan Guru–Gen, akun Guru aktif dan role Guru. Tidak ada pengambilan data dari parameter URL; status Draf/Arsip tidak ditampilkan; hitungan dan paginasi memakai filter server yang identik. Admin tetap boleh mengelola semua, Guru hanya miliknya. Bila penugasan Guru berubah, cakupan siswa berubah pada pembacaan berikutnya (riwayat lama tetap disimpan). Tidak menambah notifikasi otomatis atau tabel penerima.


### A13-6/A13-7 — gerbang keamanan pra-database (4 November 2026)

Belum tersedia dump lokal terkini **setelah** V4-0012 dalam sumber percakapan/Library yang dapat diverifikasi. Sesuai `AGENTS.md`, **tidak ada DDL, patch SQL, query terhadap skema baru, endpoint penyimpanan, pengiriman notifikasi atau publikasi privasi dalam tahap ini**. Kedua Service baru hanya menyediakan validasi payload/aturan murni dan tes anonim. Berkas tetap privat, tidak ada nama siswa, nomor induk, kredensial, sandi, NISN atau isi kebijakan rekaan yang masuk repository.

Adapter kelak harus membuktikan aktor penerima lewat relasi sah dan role aktif; event tidak boleh bocor antar Gen/role; cek izin lagi ketika tautan dibuka; hanya peristiwa akademik selesai sah yang boleh dipublikasikan; idempoten saat double-click/retry; dan audit tanpa teks/identitas sensitif. Untuk privasi: hanya Admin, naskah yang sungguh disahkan madrasah, versi/tanggal yang dapat dibedakan, audit perubahan tanpa penghapusan riwayat; halaman publik tidak boleh memperlihatkan draf atau teks belum disahkan. Konfirmasi checkbox bukan bukti persetujuan kelembagaan. `KebijakanPrivasiValidasiService` tidak menyatakan konten valid secara hukum.

### A13-6/A13-7 — proteksi setelah dump (18)

Catatan "belum tersedia dump" di atas bersifat historis. Dump privat (18) telah diperiksa tanpa disimpan di GitHub. SQL V4-0013 hanya additive, mengikat penerima dan pengelola kepada `pengguna`. Notifikasi hanya untuk penerima dan peran aktif; jumlah unread dan daftar tidak memuat informasi orang lain. Klik tautan mengecek ulang sumber, termasuk G1 Guru; POST penandaan dibaca memerlukan CSRF dan kepemilikan notifikasi, tanpa mengubah penilaian. Isi notifikasi tidak menyertakan NISN, skor, atau jawaban. Kebijakan Privasi resmi diterbitkan hanya Admin, mempertahankan versi/riwayat lama, dengan checksum dan escaping saat publik. Checkbox konfirmasi tidak menggantikan pengesahan sekolah. Belum ada naskah yang diunggah atau diterbitkan otomatis.


### A14-1 — Penutupan jalur HTTP ke skrip ROOT (4 November 2026)

Implementasi sekarang melindungi direktori tambahan `scripts/`, `tools/`, `builds/`, `uploads/` dan berkas root seperti `AGENTS.md`, `composer.json`, `README.md`, dotfiles, SQL/backup, selain direktori privat yang sudah ditolak sebelumnya. Hanya `public/assets/` dan aset publik terpilih diizinkan lewat path statis; PHP/shell pada direktori aset ditolak oleh `.htaccess`. Dua skrip pembaruan template ATP juga memeriksa `PHP_SAPI === 'cli'` sebelum memuat library. Posisi `index.php` dan `.htaccess` **tetap ROOT**.

Pemeriksaan dapat dijalankan tanpa identitas/login melalui `php scripts/qa/http_security_smoke.php http://localhost/sla-sks/`. Alat ini hanya menguji GET dan membuang body respons; **tidak pernah memanggil URL PHP skrip pemeliharaan** yang dapat mengubah template jika konfigurasi server salah. Hasil HTTP 403/404 pada contoh URL bukan jaminan semua traversal/alias/symlink aman; uji HTTP Apache nyata dan konfigurasi shared hosting tetap wajib sebelum go-live.
