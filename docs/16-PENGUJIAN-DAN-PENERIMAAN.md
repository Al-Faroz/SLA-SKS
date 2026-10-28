# 16 — Pengujian dan Kriteria Penerimaan

## 1. Strategi
Tiap fitur harus lolos: **uji aturan bisnis di Service, uji integrasi DB, uji endpoint & hak akses, uji browser desktop/HP, uji keamanan URL**, serta verifikasi perilaku sukses dan gagal. PHPUnit untuk kode server; browser testing dapat memakai Playwright bila fasilitas lokal tersedia. **Jangan mewajibkan test runner Node/daemon pada shared hosting**, pengujian dijalankan sebelum deploy.

Untuk daftar detail berikut, ID `E2E-...` adalah identitas pengujian final paket Master. Nomor UJ historis dari dokumen lama dapat digunakan sebagai referensi internal tetapi **hasil akhir wajib mengikuti keputusan v2.4 dalam paket ini**, bukan ekspektasi usang.

## 2. Skenario end-to-end wajib
| ID | Skenario | Hasil wajib |
|---|---|---|
| E2E-001 | Login pertama siswa/guru | Sandi awal wajib diganti; tidak bisa membuka fitur lain |
| E2E-002 | Gagal login 5 kali | Kunci 5 menit; tidak bocor akun mana yang valid |
| E2E-003 | Staf Admin+Guru mengganti peran | Session ID baru; izin hanya role aktif |
| E2E-004 | Satu Admin aktif | Administrasi normal dan koreksi biodata berfungsi |
| E2E-005 | Admin+Guru menyetujui usulan akademik miliknya | Ditolak, tanpa nilai/konten berubah; pesan informatif |
| E2E-006 | Guru mengakses Gen–Mapel lain via URL ID sekalipun mapel sama | Ditolak server, tanpa bocor konten/siswa |
| E2E-007 | Guru Gen–Mapel diganti | Guru baru hanya berwenang pada pasangan itu; Gen lain tetap, riwayat penilai lama utuh |
| E2E-008 | Semester Gen dan override siswa | Semester sebelum mulai Dilewati, tidak menghilangkan progres |
| E2E-009 | Guru membuat TP dengan CP resmi | Satu CP operasional Mapel, bisa banyak elemen, mapel/fase valid |
| E2E-010 | Guru usul CP; Admin memutus | Publikasi versi CP baru tanpa reviewer Guru lain |
| E2E-011 | Admin+Guru menyetujui usulan CP sendiri | Ditolak bila benar-benar permohonan akademik diri sendiri |
| E2E-012 | Download Excel peta ATP enam semester | Sheet panduan, RINGKASAN, REFERENSI CP, SEMESTER 1–6 dan contoh; hanya CP aktif mapel/kurikulum terkait |
| E2E-013 | Impor XLSX ATP valid diulang | Satu semester kosong ditambah sekali; file sama menghasilkan Tetap (0 tulis); file berbeda untuk semester terisi ditolak, tidak ada UPDATE |
| E2E-014 | Impor versi struktur salah | Ditolak, tampil sheet/baris/kolom/penjelasan |
| E2E-015 | Excel menghilangkan baris lama | Tidak menghapus otomatis |
| E2E-016 | Media/HOTS Materi belum lengkap | Excel hanya membentuk struktur; checklist TP Siap menolak materi tanpa media/Resume/HOTS |
| E2E-017 | TP Siap diedit via form/API/impor | Ditolak; hanya jalur koreksi A–C |
| E2E-018 | TP koreksi A–C disetujui | Versi baru; siswa yang mulai tetap pada versi lama |
| E2E-019 | Koreksi kategori D | Ditolak |
| E2E-020 | Siswa tidak lulus diagnostik | Tepat satu Materi → Resume final → HOTS final → Sumatif minimal 30 |
| E2E-021 | Siswa lulus diagnostik | Melewati Materi/Resume/HOTS tetapi tetap mengerjakan Sumatif minimal 30 untuk menyelesaikan TP |
| E2E-022 | Simpan draf manual | Data berubah baru tersimpan setelah klik Simpan; indikator jelas |
| E2E-023 | Siswa menutup tab dengan draf belum disimpan | Peringatan perubahan belum disimpan; tidak mengklaim autosave |
| E2E-024 | Siswa kirim resume/tugas final dua kali | Satu pengiriman; terkunci setelahnya |
| E2E-025 | Siswa akses TP terkunci via URL | 403/penolakan bisnis konsisten |
| E2E-026 | Diagnostik di HP tanpa fullscreen | Ditolak sebelum insert attempt; disarankan desktop/laptop |
| E2E-027 | Diagnostik di HP dengan fullscreen sah | Boleh dimulai jika semua pemeriksaan terpenuhi |
| E2E-028 | Sumatif di HP meski fullscreen | Ditolak, desktop/laptop wajib |
| E2E-029 | Sumatif di laptop dari rumah | Diizinkan jika memenuhi fullscreen dan hak siswa |
| E2E-030 | Keluar fullscreen sekali | Satu insiden, jeda waktu aktif, batas 90/180 detik |
| E2E-031 | Blur+visibility+fullscreen dari satu aksi | Tidak dihitung tiga insiden |
| E2E-032 | Fullscreen keluar 3 kali | Batal tanpa skor; Guru memutus tindak lanjut |
| E2E-033 | Heartbeat terlambat 20 detik | Waktu aktif berhenti tepat ambang berdasarkan jam server, walau rekonsiliasi terlambat |
| E2E-034 | Jeda koneksi 300 detik | Batal, tidak memotong kuota sah |
| E2E-035 | Insiden fullscreen dan koneksi bersamaan | Tidak menghitung waktu ganda |
| E2E-036 | Refresh ujian dan klik ganda | Tidak mengulang attempt atau menambah jawaban ganda |
| E2E-037 | Siswa mengirim jawaban saat jeda | Ditolak server |
| E2E-038 | Guru memutus ujian batal | Hanya empat opsi final sesuai jenis, tidak ada skor palsu |
| E2E-039 | Guru memberi nilai resume/HOTS | Skor 0–100; status sementara diperbarui |
| E2E-040 | Guru pengganti mengusulkan koreksi nilai | Menunggu Admin; tidak mengubah nilai sebelum sah |
| E2E-041 | Admin menyetujui koreksi valid | Nilai, agregat, audit berubah sekali |
| E2E-042 | Nilai sumber berubah ketika permohonan menunggu | Optimistic conflict; tidak menimpa |
| E2E-043 | Soal/kunci sah ternyata salah | Koreksi semua attempt sah terdampak dari snapshot; audit lengkap |
| E2E-044 | Koreksi soal menurunkan nilai di bawah KKM | Nilai/indikator KKM dihitung ulang; status TP Selesai tetap berdasar aktivitas dan attempt sah, riwayat tidak dihapus |
| E2E-045 | Semua TP tuntas tapi tugas menunggu nilai | Sertifikat ditahan; pesan menunggu guru |
| E2E-046 | TTD PNG belum diunggah | Sertifikat ditahan, Admin mendapat peringatan |
| E2E-047 | Background belum diunggah | Template bawaan dipakai; bukan kegagalan sertifikat |
| E2E-048 | Upload background rasio/ukuran tak sesuai | Ditolak saat upload, bukan baru saat aktivasi |
| E2E-049 | Template global A diganti B | Sertifikat lama/PDF tidak berubah |
| E2E-050 | Nilai berubah tetapi tampilan angka sertifikat sama | Nilai/audit berubah, tidak menerbitkan ulang PDF |
| E2E-051 | Nilai tercetak berubah | Nomor+QR baru, background historis, TTD terbaru, lama `digantikan` |
| E2E-052 | Revisi PDF gagal | Sertifikat lama tetap berlaku; tidak ada penerapan nilai parsial |
| E2E-053 | Admin membetulkan NISN | Username ikut benar; `siswa_id`, progres dan histori sama |
| E2E-054 | Siswa punya 3 sertifikat yang perlu revisi identitas | Admin merevisi satu per satu, bukan proses batch wajib |
| E2E-055 | QR lama dipindai | Status digantikan, tautan versi baru; data historis jelas |
| E2E-056 | QR publik dipindai | **Tidak ada NISN**, padahal PDF privat mencetaknya |
| E2E-057 | Percobaan mencari tombol cabut/hapus | Tidak ada endpoint sah, penghapusan siswa/sertifikat ditolak |
| E2E-058 | Admin/Guru menerbitkan pengumuman | Muncul global bagi akun login; tidak ada target Gen/role khusus |
| E2E-059 | Pengguna anonim membuka FAQ | Memerlukan login |
| E2E-060 | Penilaian selesai | Notifikasi internal penerima terbit sekali setelah commit |
| E2E-061 | Mengakses notifikasi milik pengguna lain | Ditolak |
| E2E-062 | Pimpinan pratinjau siswa | Semua aksi hanya baca, tidak menulis progres/ujian |
| E2E-063 | Laporan XLSX dengan sel `=FORMULA` | Diamankan dari formula injection |
| E2E-064 | Login di instalasi shared hosting | Semua rute dan rewrite root bekerja |
| E2E-065 | Mengakses `/.env`, `/app/...`, `/vendor/...`, `/writable/...`, `/database/sql/...` | 403/404, tidak bocor konten |
| E2E-066 | Menjalankan impor SQL final ke DB uji baru MySQL & MariaDB | Keduanya sukses; struktur Semester→BAB→TP dan data historis yang dipertahankan; sumber v3 tidak rusak |
| E2E-067 | Menjalankan SQL update pada DB berisi progres | Tidak menghapus siswa/sertifikat/riwayat |
| E2E-068 | Website kehilangan cron 5 menit | Request ujian tetap rekonsiliasi dengan waktu server |
| E2E-069 | Backup manual dan restore di lingkungan uji | Siswa, aset historis dan sertifikat dapat dibaca |
| E2E-070 | Visual/aksesibilitas 360/390/768/1100 px | Navigasi, tombol, form, grafik dapat digunakan |
| E2E-071 | Membuka halaman Kebijakan Privasi tanpa login | Teks kebijakan sekolah berversi terbaca, tanpa kebocoran data siswa |
| E2E-072 | Notifikasi jenis TP, ujian, penilaian, sertifikat, permohonan | Hanya event sah, penerima tepat, idempoten; tidak mengirim email/WhatsApp |
| E2E-073 | Dua jawaban resume sangat mirip pada materi sama | Badge Perlu dicek muncul pada Guru, tidak otomatis menahan siswa |
| E2E-074 | Resume/tugas dengan nilai sementara dan bobot 0 | Tetap menunggu penilaian komponen wajib sebelum sertifikat |
| E2E-075 | Menghapus TP draf tanpa progres, lalu mencoba menghapus TP Siap | Hanya draf aman yang boleh dihapus; data TP Siap utuh |

### Skenario penerimaan historis v2.4 yang diselaraskan v2.5
| ID | Skenario | Lulus bila |
|---|---|---|
| E2E-076 | Matematika Gen A dan Gen B punya Guru berbeda | Satu pengampu aktif per Gen–Mapel; keduanya hanya dapat mengakses cakupan Gen sendiri |
| E2E-077 | Dua Gen memiliki mapel dan semester sama | Paket/TP/soal/materi berbeda objek, tidak dapat ditautkan atau diubah lintas Gen |
| E2E-078 | Admin mengganti Guru Matematika hanya pada Gen A | Akses operasional pindah hanya Gen A; Gen B dan histori nilai/ujian/TP Siap tidak berubah |
| E2E-079 | Dropdown Guru penugasan per Gen | Hanya akun aktif dengan role Guru; staf Admin+Guru sah; non-Guru/nonaktif tak muncul dan ditolak saat POST |
| E2E-080 | Impor Pengelola XLSX valid, duplikat, dan file salah | Pratinjau/galat per baris; transaksi idempoten; satu akun staf multirole tidak digandakan |
| E2E-081 | Impor Siswa XLSX valid dan NISN duplikat | NISN unik 10 digit, Gen valid, baris salah ditolak; tidak menghapus siswa lain |
| E2E-082 | Export Pengelola dan Siswa sesuai filter | XLSX tepat data berizin; tanpa sandi/hash/token dan formula injection |
| E2E-083 | Reset sandi satu Pengelola atau Siswa | Sandi acak tampil sekali, sesi lama batal, wajib ganti sandi, target lain tetap |
| E2E-084 | Staf mengubah profil sendiri | Foto/kontak pribadi tersimpan; upaya mengubah nama resmi/NIP/role via POST ditolak |
| E2E-085 | Siswa melihat profil Guru Gen-nya | Nama/foto disetujui/mapel terlihat; NIP, nomor pribadi dan profil Guru Gen lain tidak dapat dibaca |
| E2E-086 | UI prototipe dashboard dan login v2.4 | Pemilik menyetujui sebelum rollout; font, ukuran, gap, layout padat/responsif di 360/390/768/1100px dan fokus/kontras lolos |
| E2E-087 | Konversi database lama dalam simulasi terpisah | Skema dan data yang harus dipertahankan terpetakan, tanpa berbagi TP lintas Gen, backup dan restore terbukti |
| E2E-088 | Admin+Guru mengakses Gen lain setelah berpindah peran | Service menegakkan peran aktif dan izin Gen–Mapel; mode Admin tidak dipakai untuk menyetujui usulan akademik sendiri |

## 3. Matriks nonfungsional
- **Keamanan:** CSRF, injection/XSS, file palsu, akses lintas role, force browse direktori root, rahasia `.env`, download TTD, QR throttle.
- **Integritas:** dua transaksi bersamaan, idempotensi konfirmasi Excel dan sertifikat, optimistic locking nilai, satu sertifikat aktif, audit append-only.
- **Kompatibilitas:** MySQL lokal, MariaDB hosting; HP Chrome/Android, desktop Chrome/Firefox/Edge, browser tanpa fullscreen.
- **Performa:** ±30 pengguna simultan sesuai skala; kiriman jawaban cepat, tidak duplikat; ekspor besar diberi pembatasan/pesan.
- **Aksesibilitas:** kontras ≥4,5:1, ukuran target ≥40px, teks alternatif grafik, fokus keyboard, reduced motion, formulir berlabel.
- **Kondisi buruk:** koneksi hilang, disk penuh, template/TTD hilang, SMTP tidak ada (tidak diperlukan), phpMyAdmin update gagal, browser refresh.

## 4. Definisi selesai tiap modul
Fitur dinyatakan selesai bila **aturan domain + izin + data SQL + route/service + UI normal dan seluruh state + uji sukses/gagal + catatan keputusan yang diimplementasikan** telah memenuhi dokumen resmi. Tidak cukup memiliki halaman yang tampak menarik tapi tombol belum bekerja. Setiap Pull Request menjelaskan bagian dokumen yang menjadi rujukan dan bukti pengujian yang relevan.

### Skenario hasil v2.4 yang telah ditinjau v2.5 — **E2E-089 s.d. E2E-112**
| ID | Skenario | Syarat PASS |
|---|---|---|
| E2E-089 | Guru ditugasi dua Gen/Mapel mengunduh ATP berbeda | RINGKASAN berisi Guru/Gen/Mapel/Kurikulum sesuai pilihan sah; file tidak saling tertukar |
| E2E-090 | Excel referensi CP otomatis | Hanya CP aktif sesuai Mapel/Fase D/Kurikulum, tanpa mapel lain; semua pilihan CP valid |
| E2E-091 | Excel enam semester revisi pemilik | Tidak ada istilah BAB operasional, enam sheet semester dan contoh; identitas tidak bisa diklaim oleh Gen lain |
| E2E-092 | Impor Excel satu semester saja | BAB/TP draf terbentuk hanya pada semester kosong dengan paket sah; Materi tidak dibuat otomatis; semester lain tidak dihapus |
| E2E-093 | Impor Excel sama dua kali | File sama menghasilkan Tetap tanpa penulisan; semester berisi BAB manual/TP draf yang berbeda ditolak total, tidak ada DELETE/UPDATE |
| E2E-094 | ATP mencoba mengubah BAB/TP manual/draf/Siap lewat Excel | Ditolak sebelum commit, bahkan jika draf; data historis tetap |
| E2E-095 | TP NonEsensial dengan satu Materi | Tidak boleh ada lebih dari satu Materi aktif; Esensial tanpa Materi |
| E2E-096 | Diagnostik lulus sesuai SKS | Bebas Materi/Resume/HOTS tetapi Sumatif minimal 30 tetap wajib; bukan tuntas dari Diagnostik |
| E2E-097 | Diagnostik gagal | Tepat satu Materi → Resume final → HOTS final → Sumatif; tidak ada Materi kedua |
| E2E-098 | Komponen nilai jalur gagal | Resume 20%, HOTS 20%, Sumatif terbaik 60%, bukan rata-rata banyak Materi |
| E2E-099 | Unduh template Word terikat TP | Gen/Mapel/TP/jenis ujian otomatis, template Diagnostik dan Sumatif memakai parser yang sama |
| E2E-100 | Word PG lima opsi | Semua A–E wajib terisi, tepat satu ✓; tipe PG lain, opsi kosong, dua kunci ditolak |
| E2E-101 | Word Equation OMML pada stem dan pilihan | Rumus tetap utuh pada preview dan browser ujian; bukan kehilangan format |
| E2E-102 | Word gambar pada stem dan pilihan | Seluruh gambar inline, urutan relatif dan izin akses valid di preview/ujian |
| E2E-103 | Word tabel biasa dan tabel bersarang | Tabel isi tidak menjadi nomor soal tambahan; struktur baris/kolom valid sampai browser |
| E2E-104 | Word campuran teks/rumus/tabel/gambar Unicode RTL | Tidak terbalik/hilang atau mengeksekusi skrip; kontras dan overflow ditangani |
| E2E-105 | Google Drive valid/gagal diputar | URL disaring; media tampil bila boleh, gagal memberi galat dan menghalangi Siap |
| E2E-106 | Upload DOCX korup/makro/eksternal berbahaya | Ditolak tanpa XSS, XXE, SSRF, ZIP bomb atau mutasi data |
| E2E-107 | Bank Word diimpor ulang | Pratinjau/konfirmasi idempoten; soal digunakan attempt tetap pada versi historis |
| E2E-108 | Form dan tabel Admin Pengelola/Siswa | Card form atas full-width; daftar bawah dengan pagination server-side yang mempertahankan filter |
| E2E-109 | Penetapan Guru per Gen | Pilihan Guru, Alasan, Simpan dalam kolom terpisah; pagination server-side dan POST aman |
| E2E-110 | Daftar CP | Hanya tabel daftar/status diubah; XLSX aman, sesuai filter/versi, pagination server-side |
| E2E-111 | Logo aplikasi di admin/identitas | Validasi upload, logo di aplikasi/login dan favicon baru, fallback, versi aman |
| E2E-112 | Profil semua role | Foto full-width atas, Identitas dan Kontak dua kolom desktop/satu mobile, tidak bocor atau membuka edit tanpa izin |

### Uji kontrak SQL lokal v2.5
Uji import dump hasil analisis ke DB kosong khusus XAMPP **sesudah backup/izin pemilik**, periksa jumlah data terpilih, FK, nomor ATP dan referensi CP, login sah (tanpa membuka password/hash ke log), BAB operasional terpasang, TP berelasi ke BAB/paket, HOTS per Materi, kemampuan restore; **uji di MariaDB dengan jalur non-destruktif yang berbeda**. Uji ini **belum** dilakukan pada perubahan dokumen saja.

## 5. Gerbang wajib audit SQL/DB sebelum menerima fitur (25-10-2026)
- Dump XAMPP **terbaru setelah patch terakhir** sudah benar-benar diterima; periksa waktu ekspor, nama DB, versi MariaDB/MySQL, skema, indeks, FK/UNIQUE/CHECK, status `schema_version` dan histori yang harus dipertahankan. Dump lama tidak dapat dijadikan bukti DB aktif.
- **Regresi benturan nama:** tabel bank HOTS Guru `tugas_hots_materi` dapat berisi baris dan tetap utuh saat jawaban siswa ditulis pada `jawaban_hots_materi`; Resume di `resume_materi`. Jangan membuat fixture yang hanya mencakup tabel baru.
- **Regresi kontrak Materi:** TP Siap memiliki media, instruksi Resume dan satu tugas HOTS per Materi sesuai Master 05; periksa snapshot terpin, tabel bank Guru, dan kesiapan tanpa memodifikasi progres/TP Siap secara tak sah.
- **Regresi konfigurasi:** minimal panjang Resume/HOTS harus mengacu pada `tp.resume_min` dan `tp.tugas_min` versi snapshot, bukan nilai default hardcode; pengujian berbagai konfigurasi dan versi TP sebelum perubahan Service dinyatakan FIX.
- **Regresi pengguna:** E2E-020–024, 039, 073–074, 095–098 termasuk draf/final, rute Guru Gen lain, idempoten, penilaian 0–100, koreksi nilai, tepat satu Materi dan Sumatif wajib meski lulus Diagnostik.
- **Galat yang benar:** masalah SQL bukan HTTP 403 atau instruksi ganti peran; 403 khusus izin sah, 503 khusus dependensi hilang, 500 untuk kegagalan server lain, log internal tanpa data sensitif.
- **Lingkungan uji:** jalankan SQL pada salinan struktur MariaDB XAMPP versi aktual, serta mesin kompatibilitas terpisah; GitHub Actions sukses tidak berarti browser/DB lokal telah lolos. **Hosting belum digunakan.**
- **Perubahan aman:** backup DB+`writable/`, QA sebelum/sesudah hanya SELECT; patch additive bernomor baru, tidak overwrite patch yang sudah pernah diterapkan, tidak reset data, dan tidak push dump SQL privat.


### E2E pengganti ketentuan ganda S08B (26-10-2026)

- Guru 600 detik; YouTube 420 detik: batas efektif 378 detik, tepat saat waktu putar sah mencapai 378 maka media_selesai, Resume terbuka. Tidak menunggu 600 detik.
- Guru 600 detik; YouTube 900 detik: batas efektif 600 detik, tepat saat pemutaran sah mencapai 600 maka media_selesai. Tidak menunggu 810 detik.
- Guru 360 detik; video 9.968 detik; progres lama mencatat 380 detik waktu putar sah: GET status siswa merekonsiliasi ke media_selesai secara idempoten tanpa memutar ulang, kemudian form Resume dibuka dari server.
- Hanya lompatan seek posisi jauh tetapi `waktu_putar_detik` kurang target tidak membuka Resume. Keadaan tab tersembunyi, seek, playback cepat, jeda panjang, siswa bukan pemilik progres atau jalur sukarela juga tidak boleh memberi kredit/akses.
- Audio mengikuti keputusan sama; gambar/PDF tetap mengikuti syaratnya sendiri; tidak ada SQL baru dan progres/nilai historis tidak di-reset.


### Regresi S09/S10 Kirim Final HOTS dan anti-paste (26 Oktober 2026)

- Setelah media selesai dan Resume final, HOTS dapat Simpan draf.
  Kirim final HOTS dengan teks minimal sesuai tugas_min snapshot harus
  menulis final di jawaban_hots_materi dan membuka Materi berikutnya.
- HOTS terlalu pendek ditolak dengan pesan yang terlihat di editor,
  bukan hanya atas halaman, dan isian POST gagal tidak hilang.
- Rute eksplisit /simpan dan /kirim tidak menggantungkan aksi pada
  kelengkapan parameter submitter; mismatch URL/aksi ditolak.
- POST final identik idempoten; tidak mengubah nilai/penilai dan tidak
  membuat log ganda. Bank Guru tugas_hots_materi tetap utuh.
- Browser mencegah paste, copy/cut, drop dan beforeinput paste biasa,
  namun pengetikan biasa/Unicode tetap berfungsi. Ini mitigasi saja,
  bukan anti-kecurangan absolut.


### Regresi S08 non-YouTube — pencatatan batas waktu (26 Oktober 2026)

- Audio dengan waktu Guru lebih lama dari 90% durasi: putaran sah mencapai 90% => media_selesai; audio pendek mencatat heartbeat awal dan sisa detak saat `ended`, tanpa menganggap seek sebagai waktu putar.
- Gambar dengan waktu minimum Guru 60 detik: hanya detak ketika gambar termuat, area gambar >=50% viewport, tab aktif/fokus. Waktu aktif 60 detik yang telah tercatat namun status masih berjalan direkonsiliasi oleh server tanpa menghapus hasil.
- PDF dua halaman: keduanya minimal 15 detik; total harus mencapai waktu Guru, tidak cukup hanya membuka seluruh halaman atau mencapai total tanpa melihat salah satu halaman. Daftar halaman belum lengkap muncul di UI; sinyal perpindahan saat request sebelumnya berlangsung tidak hilang.
- Status media selesai membuka Resume via reload server, HOTS hanya setelah Resume final; siswa jalur Diagnostik lulus boleh melewati seluruh kegiatan ini tetapi **tetap wajib Sumatif**. Akses Gen lain dan pengiriman tidak sah ditolak.

### Uji regresi presisi Gambar/PDF (26 Oktober 2026)

- Gambar selesai pada enam detak 5 detik sah ketika area pengamatan sedikitnya 50% terlihat dan tab fokus.
- Scroll gambar ke luar viewport atau kehilangan fokus lalu kembali: reset sesi tidak boleh memberi kredit bagi waktu tersembunyi; akumulasi sebelumnya tidak hilang.
- Detak pada 4 detik tidak dihitung dan tidak membuang baseline. Detak berikut di detik ke-6 menghitung 6 detik sah. Interval di atas 20 detik tetap nol.
- PDF tiap halaman selesai tepat setelah 15 detik sah, bukan harus menunggu 20 atau 30 detik; halaman lain tetap perlu sesi sendiri.
- Zoom/resize pada halaman sama tidak menyebabkan reset halaman; ganti nomor halaman, tab tersembunyi dan kehilangan fokus mereset sesi.
- Status PDF menampilkan waktu per halaman serta daftar yang belum selesai. Resume hanya terbuka jika seluruh halaman memenuhi 15 detik serta total waktu Guru terpenuhi.
- Tidak mengubah video YouTube/audio yang sudah FIX, tidak menambah SQL dan tidak melakukan reset data siswa. Uji akhir interaksi nyata diperlukan di XAMPP.

**Regresi visibilitas PDF:** canvas PDF sudah dirender tetapi area pembaca <50% viewport ⇒ tidak ada POST heartbeat; setelah area terlihat ⇒ detak baseline tanpa kredit, berikutnya tiap 5 detik. Scroll keluar/masuk lagi harus reset dan tidak menambah durasi sembunyi. Navigasi ke halaman lain harus memakai nomor baru dan baseline baru.


### Regresi akses unduh Media

- Siswa jalur wajib sebelum `media_selesai` tidak dapat membuka `.../unduh` sekalipun mengetik URL langsung. Setelah media_selesai, audio/gambar/PDF dapat diunduh dengan `Content-Disposition: attachment`.
- Siswa lintas Gen/versi TP ditolak. Untuk pola v2.5 tidak ada Materi kedua dalam satu TP; siswa lulus Diagnostik tidak diwajibkan mengerjakan Materi dan tetap wajib Sumatif.
- YouTube tidak memiliki unduhan. URL file dari media yang digunakan untuk preview tetap inline, tidak menjadi jalan pintas mengunduh resmi tanpa ketuntasan.

## 6. Regresi wajib perubahan kontrak v2.5 (E2E-113 s.d. E2E-134)
| ID | Pemeriksaan wajib |
|---|---|
| 113 | Satu CP operasional per Mapel walau beberapa elemen rujukan resmi; versi dan data lama utuh |
| 114 | Semester→BAB→TP: BAB terus bernomor antardua semester per kelas, reset pada tingkat baru |
| 115 | Validasi nama BAB/TP ≤50, deskripsi masing-masing ≤225, kode `BIND.8.1.1` sesuai mapel/kelas/BAB/TP |
| 116 | Guru membuat ATP Esensial dan NonEsensial langsung melalui aplikasi |
| 117 | Impor ATP lama setelah adaptasi: preview, konfirmasi, anti-duplikat, BAB, jenis TP, kode dan konteks Gen |
| 118 | Esensial tanpa Materi/ujian/nilai, tidak masuk progres, tetap tercantum sertifikat |
| 119 | NonEsensial menolak nol atau lebih dari satu Materi aktif pada status Siap |
| 120 | Diagnostik minimal 10; lulus tetap memerlukan Sumatif ≥30, tanpa Resume/HOTS |
| 121 | Diagnostik gagal wajib 1 Materi→Resume→HOTS→Sumatif, tanpa melompat |
| 122 | Nilai gagal diagnostik 20:20:60, lulus 100% Sumatif; skor Diagnostik bukan skor final |
| 123 | KKM default Admin/KKM khusus Guru; remedial hanya jika skor Sumatif awal < KKM |
| 124 | Remedial maksimal satu, skor tertinggi dari awal/remedial |
| 125 | TP Selesai meski nilai masih di bawah KKM setelah kesempatan sah |
| 126 | Progres hanya TP NonEsensial; TP draf belum dimulai tidak membuat progres palsu 100% |
| 127 | Admin jadwal serentak Pra-Semester dan penolakan ujian sebelum waktu |
| 128 | Pra-Semester hanya setelah semua NonEsensial selesai, bank ≥30 gabungan seluruh BAB |
| 129 | Pra-Semester memakai mesin ujian ketat, jeda/insiden, remedial sekali dan nilai mandiri |
| 130 | Tidak ada nilai akhir semester agregat di DB, UI, ekspor atau sertifikat |
| 131 | Export Nilai semua siswa/TP/Pra-Semester dengan hanya satu skor; NULL tidak dianggap nol; batas izin Guru |
| 132 | Sertifikat daftar Esensial tanpa nilai dan NonEsensial dengan nilai, revisi/QR historis aman |
| 133 | Checkpoint 4 menit Model B, pulih setelah koneksi putus, gate dan download audio/gambar/PDF aman |
| 134 | Impor v4 tidak menghapus v3/riwayat, arsip 4 Materi uji aman, data identitas dan FK terjaga |
Semua kasus memerlukan bukti tes nyata pada XAMPP sebelum dinyatakan PASS. Pengujian migrasi SQL tidak sama dengan tes UI/Service. Template Excel manual `.xlsx` (tanpa macro/tombol otomatis) diuji untuk baris BAB/TP, kode, validasi impor, dan keamanan file.


| 135 | Tambah BAB dan TP menggunakan modal yang dapat dibuka/ditutup lewat tombol, Escape, dan keyboard; fokus kembali; di HP modal dapat di-scroll | 
| 136 | Nama BAB/TP sepanjang 50 karakter diterima, 51 ditolak (Client dan Service), deskripsi 225 diterima dan 226 ditolak; data 35 karakter lama tetap sama setelah patch SQL baru | 
| 137 | Modal simpan POST mengirim CSRF, mencegah double-submit, hasil sukses/galat terlihat setelah redirect, dan tombol Batal tidak mengubah database | 


### QA tambahan G03 — Ringkasan dan halaman semester (27 Oktober 2026)
- E2E-138: Dua kelompok Peta ATP (Semester 1–3 / 4–6), keenam kartu memiliki latar berbeda, 3/2/1 kolom pada desktop/tablet/HP, seluruh kartu dapat dibuka lewat mouse/keyboard.
- E2E-139: Statistik kartu berasal dari DB Gen–Mapel Guru saat ini; jumlah BAB/TP Esensial/NonEsensial aktual. Ganti filter Gen–Mapel tidak membocorkan penugasan Guru lain.
- E2E-140: Halaman detail semester terotorisasi G1; breadcrumb kembali ke Peta ATP dengan filter tetap; header status dan angka benar; belum punya paket/semester sebelum Gen mulai/peran salah ditangani aman.
- E2E-141: Tombol Tambah BAB di bagian atas membuka modal; simpan kembali ke semester yang sama, nomor BAB lanjut tanpa duplikat. Tiap kartu BAB menampilkan nomor, nama, deskripsi dan tabel TP nyata; tombol Tambah TP hanya di BAB dalam paket yang dapat diedit.
- E2E-142: Modal BAB/TP Batal/Escape tidak menyimpan, CSRF wajib, cegah klik Simpan ganda, fokus dialog dan responsif; uji nama 50 diterima **hanya sesudah patch pelebaran kolom DB**, dan 51 ditolak.

## QA G05 final — Kelola ATP Excel global (28 Oktober 2026)
- E2E-142: Tautan **Kelola ATP Excel** hanya di `/guru/peta-atp`; tampilan detail semester tidak memiliki form unduh/impor XLSX. Browser back/breadcrumb kembali ke Peta ATP dengan pilihan Gen–Mapel yang sama.
- E2E-143: Halaman `/guru/peta-atp/impor` memperlihatkan enam semester, Tahun Ajaran tiap paket, kurikulum dan status kosong/terisi; unduh menghasilkan sepuluh sheet dengan data CP terbaru.
- E2E-144: Semester yang memiliki sekurangnya satu BAB atau TP manual menolak impor yang berbeda, tanpa INSERT/UPDATE; file identik tidak membuat transaksi.
- E2E-145: Satu workbook berisi Semester 4 dan 5 dengan Tahun Ajaran berbeda diimpor satu kali; `bab.tahun_ajaran_id` masing-masing sesuai `paket_semester` yang benar.
- E2E-146: Semester tanpa paket, kurikulum lain, paket ganda, nomer BAB lintas kelas salah atau file CP usang menolak seluruh konfirmasi tanpa perubahan separuh.
- E2E-147: Dua kali konfirmasi bersamaan atau keadaan BAB/TP berubah setelah pratinjau -> revalidasi dan rollback; tidak ada duplikasi, data siswa/TP Siap tetap.
