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
| E2E-010 | Admin sunting CP aktif meski telah dipakai TP/siswa | Berhasil tanpa reviewer/approval, relasi TP dan nilai siswa tetap utuh |
| E2E-011 | Form Tambah/Sunting CP | Tepat lima isian (Kurikulum, Mata pelajaran, Kode CP, Elemen CP, Rumusan CP resmi); empat metadata lama tidak diwajibkan oleh UI/server |
| E2E-012 | Download Excel peta ATP enam semester | Sheet panduan, RINGKASAN, REFERENSI CP, SEMESTER 1–6 dan contoh; hanya CP aktif mapel/kurikulum terkait |
| E2E-013 | Impor XLSX ATP valid diulang | Satu semester kosong ditambah sekali; file sama menghasilkan Tetap (0 tulis); file berbeda untuk semester terisi ditolak, tidak ada UPDATE |
| E2E-014 | Impor versi struktur salah | Ditolak, tampil sheet/baris/kolom/penjelasan |
| E2E-015 | Excel menghilangkan baris lama | Tidak menghapus otomatis |
| E2E-016 | Media/HOTS Materi belum lengkap | Excel hanya membentuk struktur; checklist TP Siap menolak materi tanpa media/Resume/HOTS |
| E2E-017 | TP Siap belum dipakai siswa diedit Guru G1 | Diizinkan via editor biasa; kesiapan dan snapshot diselaraskan otomatis, tidak perlu kategori/approval |
| E2E-018 | TP telah dipakai minimal satu siswa dicoba sunting | Seluruh mutasi konten ditolak (form/API/Word/media/KKM), progres dan snapshot tetap utuh |
| E2E-019 | BAB dengan TP Siap disunting Guru G1 | Isian BAB yang telah ada dapat diperbaiki tanpa dikunci hanya karena TP Siap; tidak ada fitur pindah TP antar-BAB |
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
| E2E-032 | Fullscreen keluar 3 kali | Diagnostik/Sumatif: tes tidak selesai tanpa skor dan menunggu **izin akses ulang Guru**; Pra-Semester: pembatalan tanpa skor |
| E2E-033 | Heartbeat terlambat 20 detik | Waktu aktif berhenti tepat ambang berdasarkan jam server, walau rekonsiliasi terlambat |
| E2E-034 | Jeda koneksi 300 detik | Tes mandiri: akses ulang Guru; Pra-Semester: batal; tidak memotong kuota sah |
| E2E-035 | Insiden fullscreen dan koneksi bersamaan | Tidak menghitung waktu ganda |
| E2E-036 | Refresh ujian dan klik ganda | Tidak mengulang attempt atau menambah jawaban ganda |
| E2E-037 | Siswa mengirim jawaban saat jeda | Ditolak server |
| E2E-038 | Guru memberi izin setelah tes mandiri tidak selesai | Hanya akses ulang tahap Diagnostik/Sumatif yang gagal; satu izin dipakai sekali; tanpa keputusan langsung_belajar/tunda_tinjauan baru, tanpa skor palsu |
| E2E-039 | Guru memberi nilai resume/HOTS | Skor 0–100; status sementara diperbarui |
| E2E-040 | Guru pengganti mengusulkan koreksi nilai | Menunggu Admin; tidak mengubah nilai sebelum sah |
| E2E-041 | Admin menyetujui koreksi valid | Nilai, agregat, audit berubah sekali |
| E2E-042 | Nilai sumber berubah ketika permohonan menunggu | Optimistic conflict; tidak menimpa |
| E2E-043 | Ditemukan kesalahan soal/kunci pada TP yang sudah digunakan | Soal/kunci TP tetap terkunci; bila diperlukan pembetulan nilai siswa gunakan mekanisme koreksi **nilai** yang terpisah dengan audit/snapshot, bukan edit TP |
| E2E-044 | Koreksi nilai siswa yang sah berakibat skor di bawah KKM | Hanya nilai/indikator penilaian yang diperbarui sesuai kewenangan, bukan konten soal/KKM TP; status TP Selesai tetap berdasar aktivitas, riwayat tidak dihapus |
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
| E2E-091 | Excel enam semester revisi pemilik | BAB menjadi entitas operasional; enam sheet semester dan contoh; identitas Gen–Mapel tidak bisa diklaim oleh Gen lain |
| E2E-092 | Impor Excel satu semester saja | BAB/TP draf terbentuk hanya pada semester kosong dengan paket sah; Materi tidak dibuat otomatis; semester lain tidak dihapus |
| E2E-093 | Impor Excel sama dua kali | File sama menghasilkan Tetap tanpa penulisan; semester berisi BAB manual/TP draf yang berbeda ditolak total, tidak ada DELETE/UPDATE |
| E2E-094 | ATP mencoba mengubah BAB/TP manual/draf/Siap lewat Excel | Ditolak sebelum commit, bahkan jika draf; data historis tetap |
| E2E-095 | TP Esensial maupun NonEsensial dengan satu Materi | Kedua jenis wajib tepat satu Materi aktif saat Siap; aktivitas/progres siswa diuji terpisah |
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
| 118 | Esensial memiliki satu Materi Guru tetapi tanpa aktivitas/nilai wajib siswa sesuai alur saat ini; tidak masuk progres, tetap tercantum sertifikat |
| 119 | Esensial dan NonEsensial sama-sama menolak nol atau lebih dari satu Materi aktif pada status Siap |
| 120 | Diagnostik minimal 10; lulus tetap memerlukan Sumatif ≥30, tanpa Resume/HOTS |
| 121 | Diagnostik gagal wajib 1 Materi→Resume→HOTS→Sumatif, tanpa melompat |
| 122 | Nilai gagal diagnostik 20:20:60, lulus 100% Sumatif; skor Diagnostik bukan skor final |
| 123 | KKM default Admin/KKM khusus Guru; remedial hanya jika skor Sumatif awal < KKM |
| 124 | Remedial maksimal satu, skor tertinggi dari awal/remedial |
| 125 | TP Selesai meski nilai masih di bawah KKM setelah kesempatan sah |
| 126 | Progres hanya TP NonEsensial; TP draf belum dimulai tidak membuat progres palsu 100% |
| 127 | Admin jadwal serentak Pra-Semester dan penolakan ujian sebelum waktu |
| 128 | Pra-Semester hanya setelah semua NonEsensial selesai, bank ≥30 gabungan seluruh BAB |
| 129 | Pra-Semester memakai mesin ujian ketat, jeda/insiden, **pembatalan**, remedial sekali dan nilai mandiri |
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

### E2E-148–150 — Kelengkapan Materi kedua jenis TP (29 Oktober 2026)
- **E2E-148:** XLSX V25-2 berisi TP Esensial dengan judul satu Materi di F39 dan jenis media di G39 — parser menerima dan menampilkan rencana dalam pratinjau tanpa membuka akses siswa.
- **E2E-149:** Esensial maupun NonEsensial yang kolom F/G-nya kosong ditolak dengan lokasi kolom yang jelas; pemeriksaan isi CP, penomoran BAB/TP, hak G1 dan transaksi tetap aktif.
- **E2E-150:** Guru dapat mengisi satu Materi lengkap pada TP kedua jenis melalui editor; cek Siap memerlukan tepat satu Materi. Layanan siswa/URL media tetap memeriksa hak, alur, status, dan progres serta tidak memberi aktivitas Esensial secara otomatis. Impor Excel hanya menyimpan struktur BAB/TP sampai Guru menambahkan media nyata dalam aplikasi.

## Kasus uji lanjutan 1 November 2026 — penyederhanaan Guru satu Materi

| Kasus | Pemeriksaan | Hasil wajib |
| --- | --- | --- |
| UI-TP-001 | Guru melihat Semester → tabel 6 kolom | Header Aksi tidak vertikal, tombol Kelola/Lihat pendek, responsif Dark/Light |
| UI-TP-002 | Guru membuka `guru/tp/{id}` hasil impor | Hanya satu form editor Materi yang sudah ada, URL/file/durasi dapat disunting |
| UI-TP-003 | URL Sunting lama `/materi/{id}/edit` | Hak G1 diuji sebelum diarahkan ke `#materi-tp`, tanpa membuka TP lintas Gen |
| UI-TP-004 | TP dengan 0 Materi | Form Tambah hanya untuk membuat satu, lalu disembunyikan |
| UI-TP-005 | TP dengan >1 Materi historis | UI menahan pengeditan ganda, tidak menghapus otomatis |
| UI-TP-006 | Indikator lama kosong | Tidak memblokir kesiapan TP; nilai indikator historis tidak tertimpa |
| UI-TP-007 | Step Progress Esensial/NonEsensial | 3 vs 5 langkah; status diturunkan dari Service, tidak memberi PASS palsu |
| UI-TP-008 | TP Siap / snapshot | TP Siap **belum digunakan siswa** tetap bisa disunting Guru G1; setelah ada satu aktivitas siswa, seluruh konten terkunci. Publikasi awal tetap setelah tes server dan konfirmasi |
| UI-TP-009 | Gagal upload / pergantian file | Pesan mudah dipahami, isian teks kembali, file harus dipilih ulang, DB tetap satu Materi |


### Regresi PDF Materi 6–40 halaman (ketetapan 1 November 2026)

- Guru unggah PDF 1 atau 5 halaman: **ditolak oleh server**, pesan minimal 6 halaman, tanpa membuat file/metadata baru.
- Guru unggah PDF 6 atau 40 halaman yang sah: **diterima** dan kolom `berkas.halaman` sesuai.
- PDF 41 halaman atau di atas 15 MB: ditolak; PDF dengan skrip/aksi aktif tetap ditolak.
- Sunting Materi dengan penggantian PDF 5 halaman: gagal, berkas lama dan versi TP tidak berubah; histori berkas tidak dihapus.
- Form/JavaScript menampilkan batas yang sama; lanjutkan PHPUnit dan uji manual upload/ganti di XAMPP sebelum menyatakan FIX.


### Pembaruan QA PDF (1 November 2026, menggantikan aturan lama)

- Satu-satunya batas konten PDF adalah **6–40 halaman** dan **maksimal 15 MB**. PDF asli dari Word yang memuat objek terkompresi `ObjStm` dengan 8 halaman diterima; kompresi/metadata bukan alasan gagal.
- PDF 1/5 halaman ditolak; 6/8/40 diterima; 41 atau >15 MB ditolak; berkas PDF tidak terbaca jumlah halamannya memberi pesan jelas.
- Pengenalan jenis file (ekstensi/MIME), hak Guru G1, status TP draf, CSRF dan penyimpanan privat tetap dijaga oleh komponen yang sudah ada; ketentuan pemeriksaan skrip/aksi/struktur internal pada regresi historis tidak menjadi persyaratan bisnis PDF lagi.
- Pada Sunting, PDF yang tidak memenuhi dua batas tidak mengganti file atau metadata lama. Lakukan PHPUnit serta tes XAMPP nyata.

### Regresi permintaan akses ulang tes mandiri (2 November 2026)
- Siswa dengan Diagnostik terakhir tidak selesai (contoh `sla_uji_05`) melihat hanya aksi **Minta akses ulang Diagnostik**, lalu status Menunggu izin Guru. Tidak ada nilai palsu atau jalur belajar otomatis.
- Siswa dengan **Diagnostik sah dan Sumatif terakhir tidak selesai** (contoh `sla_uji_08`) melihat **Minta akses ulang Sumatif**, **bukan Diagnostik**. POST salah jenis ditolak server walaupun URL ditebak.
- Ketika **remedial Sumatif** yang terakhir tidak selesai, akses ulang tetap **tahap remedial** dan tidak memberi kesempatan remedial baru; skor sah Sumatif normal tidak diubah.
- Guru G1 melihat permintaan siswa, tombol tunggal **Izinkan Akses Ulang** tanpa isian alasan dan tanpa dropdown; sebab teknis/izin otomatis tercatat di audit. Tes pengganti hanya sekali dan hanya setelah izin Guru; G1 Gen lain ditolak; klik ganda idempoten.
- Uji `perlu_bantuan`, `sumatif`, `diagnostik`, `tuntas`; setelah izin diberikan halaman Rincian TP membuka tes yang benar. Nilai, riwayat dan soal historis tetap utuh.

## Regresi Bank Soal Diagnostik–Sumatif terpadu (2 November 2026)
- UI satu tombol Kelola Bank Soal dari TP NonEsensial; tab Diagnostik/Sumatif tetap terpisah dan tetap mengingat jenis pada redirect gagal/sukses. TP Esensial tidak menampilkan bank soal wajib.
- Generator Word: Diagnostik 10–200 dan Sumatif 30–200, bukan 5 soal Sumatif; impor dan tambah manual boleh berlangsung bertahap sampai mencapai batas TP Siap.
- Daftar >15 soal: pagination server; berpindah tab/halaman tanpa tercampur Gen atau jenis. 403 untuk Guru lain; edit TP Siap **belum dipakai siswa** diizinkan dan mutasi sesudah siswa mulai ditolak.
- Editor: tambah/edit, periksa A–E, satu kunci, gambar privat, pratinjau HTML tersanitasi sebelum simpan, form tanpa JS sebagai fallback, CSRF pada unggah gambar dan pratinjau.
- Impor Word: upload → review → konfirmasi; pemeriksaan 10/30 pada TP Siap; soal manual tidak tertimpa; perubahan apa pun pada bank setelah review membatalkan konfirmasi; impor identik tidak melompati perlindungan konflik.
- Equation Word pecahan/pangkat/akar dapat dibaca sebagai MathML dalam review; bentuk kompleks dan rendering browser perlu tes dokumen nyata. Audio/Video Google Drive hanya tautan file HTTPS, tautan selain file ditolak dan Guru menguji izin Viewer/pemutaran sebelum menyetujui review.
- PHP lint, unit, integrasi, QA CI dan review XAMPP adalah gerbang. Jangan mengklaim semua kasus E2E PASS bila hanya CI yang lulus; tidak ada migrasi SQL.

### Regresi Mode Dokumentasi untuk Screenshot Full-Page — 2 November 2026
- Di semua panel berbasis `layouts/panel` (Admin, Guru, Siswa, Pimpinan), tombol kamera Dokumentasi tampak dan bisa ditoggle; `aria-pressed` dan labelnya mengikuti mode.
- Dengan mode mati, sidebar desktop sticky tetap dapat diminimalkan, menu HP/drawer, tombol tema, login/logout, form dan rute tetap berfungsi. Dengan mode hidup, sidebar tidak sticky, tidak memakai tinggi tetap 100vh dan tidak menggulir sendiri; bidang konten tidak hilang di luar sidebar saat screenshot panjang.
- Mode disimpan per tab pada sessionStorage, dipulihkan saat navigasi/reload tanpa mengubah preferensi default Dark. Tombol Selesai harus mengembalikan layout tanpa kehilangan form yang belum dikirim. Light/Dark keduanya diuji.
- Uji ekstensi Screenshot Tool Chrome pada halaman panjang: Review Word 30 soal (beberapa `details` dibuka), Detail TP, Peta ATP/semester, tabel panjang Admin, serta halaman Siswa. Bandingkan screenshot dengan tampilan layar dan cek tidak ada pengulangan sidebar, konten terpotong atau bagian gambar kosong.
- Unit `ModeDokumentasiShellTest`, JS `node --check` dan PHP lint harus PASS. **Pengujian statis tidak bisa menjamin perilaku semua ekstensi pihak ketiga**; hasil capture XAMPP pengguna tetap penentu status FIX.


### Regresi penyesuaian Step Progress TP dan KKM — 2 November 2026
- Detail TP NonEsensial: langkah Diagnostik berbunyi **Minimal 10 soal sah dan pengaturan kegiatan**; Sumatif **Minimal 30 soal sah, KKM terset**. Keduanya tetap mengarah ke jenis tab yang tepat.
- Status selesai dan belum selesai memiliki latar/border berbeda serta teks, bukan warna saja; warna teks `✓ Selesai` menggunakan merah kontras pada Dark dan Light. Periksa urutan keyboard, tombol Lihat/Lengkapi, HP/tablet/desktop.
- Step Sumatif juga mensyaratkan kode pemeriksaan `kkm` (sama dengan Diagnostik), tidak boleh selesai hanya karena bank soalnya cukup ketika KKM efektif tidak valid.
- **Tahap berikutnya (butuh dump SQL terbaru):** audit penyimpanan `kkm_default` Admin dan `tp.kkm_khusus` Guru vs pembacaan `tp.kkm` saat ini; kemudian form satu nilai KKM efektif yang terlihat pada tab Diagnostik/Sumatif, dapat diganti Guru pada TP draf, konsisten hingga snapshot, soal dan mesin ujian. Jangan menambah kolom/tes DB asumtif sebelum audit dump privat terbaru.


### Regresi KKM Admin/Guru dan identitas ringkas — 2 November 2026
- Draf non_esensial tanpa default Admin dan tanpa KKM khusus Guru belum dapat diterbitkan, meskipun `tp.kkm` historis 75. Isi default Admin melalui `admin/kkm`, periksa Diagnostik+Sumatif menampilkan angka sama. Override Guru lalu buka kedua tab dan periksa angka yang sama.
- Hapus override memakai tombol **Ikuti Default Admin**; pastikan kembali mengikuti nilai Admin. Perubahan default Admin selanjutnya mempengaruhi TP Draf/Siap-belum-digunakan tanpa override; tidak menimpa nilai atau snapshot TP yang sudah digunakan siswa. TP Esensial tidak menerima penyesuaian KKM ujian.
- Uji keamanan: akun Siswa/Admin tidak boleh POST ke rute KKM Guru; Guru lain tak boleh mengubah TP; CSRF ditolak; versi TP usang harus meminta muat ulang; perubahan KKM menghasilkan audit tanpa menghapus soal/Word/Materi.
- Tampilan Kelola TP memakai kartu Identitas TP ringkas pada desktop, laptop, HP; seluruh lima metadata tetap terbaca. Import Word diagnostik/sumatif yang sudah FIX tidak diubah.
- PHPUnit `KkmTpServiceTest` memeriksa batas 1–100 dan pemilihan KKM; CI lint file terkait. Pengujian penuh penyimpanan DB dan browser tetap menunggu XAMPP.

### Regresi KKM diwarisi dan status penggunaan siswa — 2 November 2026
- Admin belum menyimpan default: contoh 80 pada UI Admin jelas belum aktif, Guru melihat belum terset. Tidak memakai nilai historis 75 otomatis.
- Admin menyimpan default: TP Draf maupun Siap belum dipakai siswa tanpa override mengikuti satu KKM Admin untuk Diagnostik dan Sumatif. Mengisi angka sama tidak menjadi override; perubahan berbeda disimpan selama belum digunakan.
- TP yang pernah digunakan siswa terkunci termasuk KKM; perubahan default Admin berikutnya tidak memutasi KKM/nilai/snapshot siswa historis.

### E2E final penyuntingan CP/BAB/TP dan simplifikasi isian CP — 2 November 2026
1. **CP lima field**: Admin buat/sunting CP menggunakan hanya Kurikulum, Mata pelajaran, Kode CP, Elemen CP, Rumusan CP resmi. Empat metadata (Otoritas penerbit, Nomor dokumen resmi, URL sumber dokumen, Tanggal berlaku) tidak tampil dan tidak diminta server. Fase D otomatis. Uji validasi pilihan kurikulum/mapel, kode dan rumusan; jangan fabrikasi metadata yang dihapus.
2. **CP boleh disunting selalu**: perbaiki CP aktif yang dirujuk TP #23 bahkan bila siswa sudah menggunakan TP; perubahan tersimpan tanpa draf/approval manual dan tidak mengubah TP, bank soal, KKM, progres, ataupun nilai siswa. Relasi CP tetap benar.
3. **BAB**: Guru G1 edit isian BAB yang tersedia meskipun ada TP Siap; nomor/keunikan tetap valid. Guru Gen lain dilarang. Tidak ada tombol/proses pindah TP antar-BAB.
4. **TP Siap belum digunakan**: Guru G1 edit rincian, KKM tunggal, satu Materi/media, soal/Word lewat editor normal tanpa kategori, alasan wajib, atau persetujuan Admin. Pemeriksaan kesiapan/snapshot aktif benar setelah simpan, status Siap tidak menjadi indikator terkunci.
5. **TP yang sudah digunakan**: lakukan satu awal aktivitas siswa sah, lalu Guru coba semua jalur editor/KKM/Word/upload/APIs dan URL lama; seluruh mutasi ditolak. Siswa tetap dapat menyelesaikan aktivitas memakai snapshot yang sah. Jangan membuka kunci dengan menghapus/reset data siswa.
6. **Persaingan transaksi**: siswa memulai TP tepat ketika Guru menyimpan perubahan TP Siap; satu operasi harus ditolak atau diserialisasi sehingga siswa tidak pernah mengakses snapshot campuran; rollback tanpa data parsial.
7. **Alur usang**: tidak ada UI wajib kategori A/B/C, permohonan TP atau keputusan Admin untuk TP belum digunakan. Patch SQL V4-0006 tidak diperlukan. Jika tabel sudah ada pada DB lokal, tidak dihapus tanpa izin/backup. Pastikan penilaian siswa dan sertifikat yang sudah FIX tetap tidak tersentuh.
8. **Regresi**: semua 16 Master tetap konsisten; API, CSRF, G1, keamanan file, PHP lint/PHPUnit, browser HP/desktop Dark/Light dan pengujian DB XAMPP harus dijalankan setelah kode berubah. Pembaruan Master saja **bukan** klaim fitur telah berfungsi.


### Regresi Pra-Semester final (3 November 2026)
1. Dua mapel pada Gen/semester sama dapat memiliki **jadwal dan durasi berbeda**; jadwal tidak menyebar otomatis ke mapel lain. Admin, bukan Guru, menyimpan jadwal; Guru G1, bukan Admin, menyimpan bank dan KKM.
2. Jendela boleh mulai 08.00–09.00 dan durasi 60 menit: mulai 08.45 memberi timer penuh 60 menit dari **08.45** meskipun jendela tertutup 09.00; mulai 09.00 tepat ditolak. Mulai 07.59 ditolak. Semua waktu server, WIB untuk jadwal.
3. Bank <30 atau BAB belum terwakili, KKM kosong, TP NonEsensial belum tuntas, akun/gen/mapel salah, fullscreen tidak didukung → **tidak dibuat attempt**.
4. Ujian awal selesai sah di bawah KKM → jadwal remedial Admin, hanya **satu remedial sah**, skor tertinggi, nilai mandiri; tanpa nilai akhir semester.
5. Fullscreen/koneksi melampaui batas → **Pra-Semester dibatalkan**, tidak memberi skor; siswa yang dibatalkan maupun tidak hadir hanya mendapat akses ketika Admin menjadwalkan susulan khusus peserta tersebut. Susulan batal lagi → jadwal baru dengan audit; remedial yang batal → susulan remedial, bukan jatah tambahan.
6. Setelah mulai, ujian memiliki timer individual, jawaban tersimpan server dan dapat pulih saat gangguan sah; race condition start bersamaan tidak membuat dua attempt aktif. Semua keputusan/riwayat tersimpan; tidak ada reset data siswa.
7. UI normal/kosong/locked/sukses/gagal, desktop/laptop fullscreen, aksesibilitas dan kebocoran kunci; hak Guru G1, Admin, Siswa dan Pimpinan tidak saling melintas.


### QA A8-4 kebijakan remedial/susulan (3 November 2026)
- Sebelum ada attempt: normal hanya pada jadwal utama; **tidak hadir** boleh masuk susulan normal bila Admin menetapkan peserta; selain itu ditolak.
- Normal selesai sah ≥KKM: nilai final, tidak ada remedial. Normal sah <KKM: nilai sementara, satu remedial sah pada jadwal Admin; nilai terbaik menjadi final setelah remedial selesai sah.
- Normal dibatalkan sekali/berulang: skor NULL, **susulan normal** terjadwal untuk peserta ditunjuk; tidak memakai jadwal utama lagi atau memberi skor palsu. Remedial dibatalkan: nilai normal tetap utuh, **susulan remedial** dan bukan kesempatan remedial kedua.
- `0.00` adalah nilai sah, tidak dianggap kosong. KKM berubah antarattempt, dua normal sah, remedial tanpa normal sah, attempt dibatalkan tetapi ada skor → ditolak oleh domain service.
- Percobaan masih berjalan/jeda boleh dilanjutkan dengan token sama setelah jendela boleh mulai ditutup. Jendela mulai baru tidak mengubah timer individual yang sedang berjalan.
- Regresi otomatis: `tests/unit/PraSemesterRemedialSusulanTest.php` melalui QA A8/S06A. Tes integrasi DB, role, jadwal susulan dan remedial **masih diperlukan** setelah ada dump XAMPP pascapatch terakhir, bukan dinyatakan PASS berdasarkan unit test saja.

### A8-5 — Uji penerimaan implementasi
- Filter Guru: hanya paket Gen yang ditugaskan, nilai Gen lain ditolak/tidak muncul; tanpa filter semua paket hak Guru tampil. Filter Admin: kombinasi Gen, Mapel, Semester 1–6, status ujian, semua dapat di-reset; percobaan injeksi query tidak diterima.
- Impor `V4-0009` sekali setelah backup; cek `schema_version`, dua tabel tambahan, kolom FK nullable, indeks unik, dan jumlah attempt/soal/riwayat **tetap**. Tidak boleh mencoba impor dump privat ke CI.
- Admin: buat remedial, susulan normal, susulan remedial; tolak jadwal masa lalu/bentrok/jeda utama belum selesai. Tetapkan peserta susulan yang berhak, tidak dapat menyelundupkan siswa Gen lain; peserta yang sudah ditetapkan tidak terhapus.
- Siswa: normal batal -> hanya susulan normal terdaftar; tidak hadir -> hanya susulan normal setelah jendela utama tutup; normal sah <KKM -> remedial; remedial batal -> susulan remedial; normal sah ≥KKM -> tidak bisa lanjut. Perubahan peserta/jadwal setelah ujian digunakan harus ditolak. Skor terbaik dan 0.00 sah tetap dibedakan dari NULL.
- Pengujian otomatis mencakup PHP lint, tes Service dan SQLite sintetis; uji MySQL/MariaDB lokal/XAMPP, browser fullscreen, keamanan URL dan peran tetap wajib sebelum PASS pengguna.
