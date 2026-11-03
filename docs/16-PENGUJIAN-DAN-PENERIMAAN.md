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
| E2E-034 | Jeda koneksi kumulatif 600 detik | Tes mandiri: akses ulang Guru; Pra-Semester: batal; tidak memotong kuota sah |
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

### A8-6 — Uji navigasi daftar/detail Pra-Semester
1. URL Guru tanpa `paketId` harus hanya menunjukkan filter Gen dan tabel paket; **tidak** boleh ada form KKM/soal di bawahnya. Klik **Kelola soal** membuka halaman detail tanpa tabel pemilihan paket, berisi KKM, daftar soal, dan form; breadcrumb **Kembali ke daftar** mempertahankan Gen.
2. URL Admin tanpa `paketId` harus hanya menunjukkan filter Gen/Mapel/Semester/Status dan tabel jadwal; **tidak** boleh ada form jadwal atau daftar peserta di bawahnya. Klik **Atur jadwal** membuka halaman detail tanpa tabel utama; breadcrumb dan kembali mempertahankan seluruh filter.
3. POST simpan soal/KKM, jadwal utama/tambahan, dan peserta susulan tetap berfungsi dan kembali ke halaman detail. Uji 403 akses Guru ke paket Gen lain, status kosong, responsif HP, dan CSRF.
4. SQL **tetap V4-0009** yang sudah terpasang; tidak mengimpor ulang atau mengubah data lokal untuk perbaikan UI ini.

### A8-7 — QA Word Pra-Semester
- Guru mengunduh template 30 soal dengan BAB semester; unggah DOCX 30 soal berisi PG A–E dan kunci ✓, metadata HMAC benar atau identitas tampak yang masih sesuai. Pratinjau menampilkan jumlah dan asal BAB, gambar, tabel, rumus/Arab/Drive.
- Tolak BAB asing/di luar semester, semua soal hanya satu BAB, paket/Guru lain, opsi kosong/kunci salah, file macro, >15 MB, dan perubahan BAB/bank setelah review; tidak membuat soal parsial. Soal manual tidak boleh tertimpa oleh impor. Impor ulang dokumen identik tidak menambah soal.
- Setelah ada attempt Pra-Semester, semua impor ditolak, dan hasil siswa/riwayat tidak berubah. Browser siswa harus menampilkan konten rich dan gambar lewat media proxy berotorisasi; token/akun siswa lain mendapat 404.
- Uji automated `tests/unit/PraSemesterWordTest.php`, `PraSemesterDatabaseTest.php`, regresi Word Diagnostik/Sumatif, serta SQL MariaDB sintetis `V4-0010`; uji XAMPP manual tetap wajib sebelum dinyatakan FIX.

### A8-8 — Gerbang siswa, jawaban dan hasil Pra-Semester
1. Pada daftar TP siswa, kartu Pra-Semester menggunakan data dari `MesinPraSemesterSiswaService` (otorisasi Gen+pembuktian TP NonEsensial tuntas **disertai Sumatif selesai sah**), bukan menebak dari URL atau status TP historis saja. Nilai `0.00` sah tidak boleh ditampilkan sebagai kosong; token/kunci tidak boleh terkirim.
2. Uji percobaan normal dalam jadwal Admin: satu kali Mulai, snapshot 30 butir dari bank 35, opsi A–E acak tanpa kunci, simpan jawaban pada server, mengganti jawaban pada butir yang sama **tidak menduplikasi**, reload memulihkan jawaban, Kirim mencatat skor dan jumlah benar. Tidak dapat mengubah jawaban setelah selesai sah.
3. Jika skor normal <KKM, nilai sementara dan menunggu jadwal remedial yang resmi. Siswa tidak boleh membuat percobaan kedua di luar jadwal Admin. Kelayakan TP palsu (status `tuntas` tanpa Sumatif sah) tetap menolak percobaan.
4. Jendela utama dalam dump audit 3 Nov 2026: **15 Desember 2026 pukul 09.00–11.00 WIB**, durasi per siswa 60 menit. Saat audit tidak ada attempt Pra-Semester; **uji browser aktual belum PASS** dan tidak boleh diakali dengan mengubah SQL/histori diam-diam. Skenario otomatis memakai jam sintetis; pengujian XAMPP memerlukan jadwal yang benar-benar aktif menurut Admin.
5. Uji susulan/remedial yang sudah ada tetap dijalankan; tidak menambah aturan sertifikat/rekap semester.

### A8-9 — Regresi lanjutan mesin Siswa (3 November 2026)

- Setelah pengguna menyatakan PASS pada tampilan/status Pra-Semester Siswa A8-8, jalankan regresi integrasi SQLite sintetis tanpa menulis histori XAMPP: (1) siswa yang sudah memulai tetap melanjutkan token, menjawab, dan mengirim setelah jendela **boleh mulai** Admin ditutup; tidak tercipta attempt kedua; (2) token siswa A harus ditolak oleh siswa B pada paket Gen yang sama untuk status, jawab, dan kirim; (3) siswa tidak hadir baru boleh mengikuti susulan normal setelah ditetapkan sebagai peserta oleh Admin, tanpa membuat skor palsu untuk jadwal utama.
- Kasus ada di `tests/unit/PraSemesterDatabaseTest.php` dan dijalankan melalui workflow `qa-a8-pra-semester.yml`. Status PASS otomatis harus berdasarkan hasil Actions, bukan hanya adanya tes dalam GitHub.
- Pengujian browser pengerjaan soal, fullscreen, timer, pembatalan, dan remedial/susulan masih memerlukan siswa uji yang memenuhi seluruh TP serta jadwal aktif yang sah. Dilarang melewati gerbang dengan UPDATE progres siswa atau mengubah jadwal resmi tanpa persetujuan.

### A8-10 — Audit pembatalan langsung setelah offline panjang (3 November 2026)

- Buat percobaan Pra-Semester berjalan dalam fixture SQLite anonim, putuskan heartbeat lebih dari total batas 600 detik, lalu lakukan **satu** pembacaan status server. Hasil wajib `dibatalkan_menunggu_guru`, skor NULL, serta tepat satu segmen `koneksi` yang dimulai pada `last_seen_server_at + 20 detik` dan berakhir ketika pembatalan terdeteksi.
- Permintaan status kedua harus idempoten: tidak menambah segmen, nilai atau percobaan; siswa tetap menunggu jadwal susulan Admin. Kasus masuk `tests/unit/PraSemesterDatabaseTest.php` dan alur CI S06A/A8.
- Tidak mengubah kebijakan pengamanan Master 07: koneksi 20 detik dan jeda kumulatif maksimal 600 detik. Uji browser XAMPP tetap perlu dilakukan pada akun simulasi berhak dan jadwal Admin sah.

### A8-11 — Perubahan batas jeda koneksi 10 menit (3 November 2026)

- Keputusan terbaru pemilik: batas **jeda koneksi kumulatif** pada profil keamanan Ketat diubah dari **300 menjadi 600 detik (10 menit)**, berlaku untuk Diagnostik, Sumatif TP dan Pra-Semester. Timeout heartbeat/deteksi putus koneksi tetap **20 detik**; jeda fullscreen dan batas tiga insiden tidak berubah. Ketentuan sebelumnya yang menyebut 300 detik pada bagian A8-10 telah digantikan oleh aturan ini.
- Uji batas: setelah 300 detik jeda koneksi ujian belum dihentikan; pada 599 detik tetap jeda; pada 600 detik berlaku konsekuensi sesuai jenis tes. Dua jeda yang terpisah tetap dijumlahkan, bukan mendapat 600 detik baru tiap kejadian.
- Regresi integrasi Pra-Semester: offline sekitar 400 detik membuka `jeda_koneksi` dan masih dapat pulih tanpa membuat attempt baru; offline sekitar 700 detik dapat langsung membatalkan ujian dengan skor NULL dan tepat satu segmen audit koneksi yang tertutup. Diagnostik/Sumatif yang terhenti tetap menggunakan **izin akses ulang Guru**, bukan pembatalan Pra-Semester.
- Periksa juga pesan jeda pada `public/assets/js/diagnostik-ujian.js` menyebut 10 menit. Tidak ada SQL, reset data, perubahan jadwal Admin, atau kebijakan waktu individual ujian pada tahap ini. Uji browser aktual tetap diperlukan.

### A9 — Regresi filter, menu rekap terpisah dan unduhan (3 November 2026)

- G10 antrean: kombinasi Gen–Mapel, Semester, BAB, Resume/HOTS benar-benar diterapkan ke query server (bukan CSS hiding), 403/galat untuk BAB lintas Gen, urutan tertua, pager memelihara filter, reset jelas, tidak ada label `Perlu dicek xx%` dalam kolom Jenis tabel.
- G11 detail: petunjuk snapshot versi siswa dan jawaban siswa muncul pada kartu berbeda dengan latar berbeda dalam Dark/Light; teks di-escape, kemiripan hanya indikator pada detail, penyimpanan nilai tetap 0–100 dan CSRF.
- A9 TP: NonEsensial terpilih secara default; Esensial/semua dan BAB hanya menampilkan pasangan siswa–TP yang sesuai. Ekspor mengikuti filter dan tetap memasukkan semua siswa pada sheet roster; tidak pernah mencampur nilai Pra-Semester.
- A9 Pra-Semester: semua siswa Gen muncul meski belum ujian; hanya `selesai_sah` bernilai, normal 0.00 adalah skor sah, normal < KKM sementara, remedial sah mengambil terbaik, batal tetap tanpa skor. Ekspor XLSX terpisah, sel kosong bukan nol, teks diawali `=` disimpan sebagai string dan Guru Gen lain ditolak.
- Pemeriksaan otomatis dilakukan di SQLite/MariaDB sintetis dan PHP lint. **Uji XAMPP nyata belum dinyatakan PASS tanpa dump lokal terbaru dan browser Guru**; data siswa, histori ujian, .env, dan jadwal tidak boleh diubah untuk memaksanya.

### A9-2 — Pemeriksaan UI Guru dari screenshot (3 November 2026)

- Rekap TP: empat metrik berjejer 4/2/1 sesuai lebar; tabel kelas `a-tbl`, scrollbar horizontal, kolom nama siswa/BAB/TP serta empat nilai tetap terpisah; skor 0/NULL tidak diperlakukan sebagai satu nilai.
- Rekap Pra-Semester: filter Gen–Mapel dan Semester memenuhi lebar panel tanpa kolom kosong yang tak diperlukan, tabel status/KKM/Normal/Remedial/Nilai Terbaik dapat dibaca, halaman/ekspor dan nilai tidak berubah.
- Penilaian: empat filter tetap berfungsi pada resolusi laptop dan HP; detail Petunjuk/Jawaban berbeda warna walau `data-documentation=on`, form skor dan umpan balik tampil dua kolom di desktop.
- Dokumentasi full-page: menu Rekap Nilai dengan submenu tidak membuat baris ketiga header. Di sidebar normal tetap dapat mengakses kedua submenu.
- Otomatis: `tests/unit/A9TampilanGuruTest.php` memeriksa class tabel, kartu metrik, pembagian form, menu, dan selector Mode Dokumentasi. Pengujian visual browser nyata Dark/Light, layar 360/390/768/1100+ dan mode Dokumentasi tetap tugas QA localhost pemilik.

### A10 — Ketentuan final sertifikat tanpa gerbang Pra-Semester (3 November 2026)

| ID | Skenario uji | Hasil wajib |
|---|---|---|
| SERT-PRA-001 | Seluruh TP NonEsensial tuntas, nilai TP wajib Final, identitas serta TTD siap; Pra-Semester belum dijadwalkan/belum diikuti | **Sertifikat boleh diterbitkan**; tidak meminta hasil Pra-Semester |
| SERT-PRA-002 | Persyaratan sertifikat TP terpenuhi, Pra-Semester dibatalkan / menunggu susulan / remedial / nilainya Sementara | **Sertifikat tetap boleh diterbitkan**; tidak ada dependensi ke status ujian atau nilai Pra-Semester |
| SERT-PRA-003 | TP NonEsensial belum tuntas atau satu nilai TP wajib masih Sementara, meski Pra-Semester selesai sah | **Sertifikat ditahan** sesuai prasyarat TP; Pra-Semester tidak menggantikan finalisasi TP |
| SERT-PRA-004 | PDF sertifikat terbit setelah siswa memenuhi syarat | Hanya daftar BAB/TP Esensial tanpa nilai dan NonEsensial dengan nilai final TP, identitas/TTD/QR; **tidak ada skor, status, atau kolom Pra-Semester**, tidak ada Nilai Akhir Semester |
| SERT-PRA-005 | Nilai Pra-Semester berubah sah setelah sertifikat terbit | **Nomor/QR/PDF sertifikat tidak berubah**; perubahan Pra-Semester hanya tercatat di rekap/ekspor mandiri |
| SERT-PRA-006 | Nilai TP yang tercetak atau biodata dikoreksi sah | Tetap mengikuti prosedur revisi sertifikat historis (nomor/QR baru jika konten cetak berubah), terpisah dari perubahan nilai Pra-Semester |

**Dasar keputusan:** Pra-Semester berfungsi sebagai checkpoint sebelum Ujian Semester yang sesungguhnya, bukan gate sertifikat atau nilai tercetak. Tidak menginstruksikan pembuatan modul Ujian Semester baru. Tes sertifikat ini merupakan **kriteria penerimaan mendatang**, bukan klaim bahwa implementasi PDF/QR telah tersedia di branch A9 atau XAMPP.

### A10-1 — Pengujian Laporan Akademik dan Monitoring Pimpinan (3 November 2026)

| ID | Kondisi | Hasil yang wajib |
|---|---|---|
| A10-001 | Siswa melihat daftar laporan | Hanya paket Mapel–Semester aktif, ditugaskan, dan telah terbuka menurut katalog; semester berikutnya tetap terkunci |
| A10-002 | Siswa mengubah id paket/identitas siswa atau membuka laporan Gen lain | Ditolak server; rute siswa tidak menerima `siswa_id`, tidak mengungkap data siswa lain |
| A10-003 | Pimpinan memilih Gen–Mapel–Semester dan halaman siswa berikutnya | Total siswa dan pagination benar, progres berdasarkan TP NonEsensial+Sumatif selesai sah, tanpa duplikasi akibat join ujian |
| A10-004 | TP Esensial, TP berstatus tuntas tanpa Sumatif sah, semester dilewati atau tanpa TP wajib | Esensial tanpa skor; tuntas tanpa Sumatif tidak dihitung; dilewati dan tanpa TP tidak otomatis 100% |
| A10-005 | Nilai 0 dan remedial sah | 0,00 tetap angka; Sumatif normal/remedial tampil terpisah; nilai TP tersimpan tidak dihitung ulang |
| A10-006 | Pra-Semester belum diikuti/batal/normal+remedial sah | NULL tetap kosong, hasil sah ditampilkan terpisah, tidak ada Nilai Akhir Semester dan tidak menunda sertifikat |
| A10-007 | Akun Guru/Admin, Pimpinan pratinjau siswa Gen yang tidak sesuai, akses tanpa role benar | Ditolak; GET-only; tidak menyimpan perubahan nilai, progres atau histori |
| A10-008 | Browser desktop, laptop, HP Dark/Light dan Mode Dokumentasi | Daftar, progress bar, tabel horizontal, pesan kosong dan navigasi terbaca pada 360/390/768/1100px |

Integrasi otomatis A10 memakai **SQLite sintetis** di `tests/unit/LaporanAkademikDatabaseTest.php` dan PHP lint; pengujian browser XAMPP tetap wajib sebelum status FIX. Dump privat tidak diunggah ke repo.

### A11-1 — Regresi kesiapan sertifikat sebelum penerbitan (3 November 2026)

| ID | Skenario | Hasil wajib |
|---|---|---|
| A11-001 | Semua TP NonEsensial tuntas, Sumatif sah dan nilai TP Final termasuk nilai 0 | **Layak akademik**, walaupun tidak ada data/jadwal Pra-Semester |
| A11-002 | TP `tuntas` tetapi tidak mempunyai percobaan Sumatif `selesai_sah` | **Belum layak**; Sumatif bukan hanya status progres |
| A11-003 | Sumatif sah tetapi nilai TP Sementara/NULL atau komponen belajar wajib masih kosong | **Belum layak**; tampil alasan penilaian |
| A11-004 | Semester sebelum mulai siswa atau paket tanpa TP NonEsensial | **Belum layak**, bukan otomatis 100% |
| A11-005 | Admin melihat Gen/semester aktif dan halaman kedua | Paket nonaktif tidak dipilih; siswa Gen lain tidak tercampur; pager benar |
| A11-006 | Guru, Siswa, Pimpinan atau Admin tidak aktif mengakses | Ditolak server; tidak ada data NISN atau PDF privat |
| A11-007 | Identitas lengkap tetapi TTD belum dikonfigurasi | Tetap **belum ada penerbitan**; jangan membuat nomor, PDF atau token QR |
| A11-008 | Filter dan tampilan Dark/Light, HP, Mode Dokumentasi | Tabel dan alasan terbaca, tidak ada tombol semu |

Tes SQLite sintetis di `tests/unit/KelayakanSertifikatDatabaseTest.php` dan lint workflow `QA A11-1 Kelayakan Sertifikat`. Ini kriteria **A11-1 saja**, belum menggantikan E2E-045 sampai E2E-057 penerbitan/versi/QR serta uji browser XAMPP.

### A11-2 — QA sertifikat PDF/QR dan histori (3 November 2026)

| ID | Skenario | Ekspektasi |
|---|---|---|
| A11-201 | Upload background rasio A4 landscape valid / rusak / tipe palsu / terlalu besar | Valid hanya PNG/JPG yang lolos MIME+decode+rasio; privat; preview PDF dummy sebelum aktivasi |
| A11-202 | Upload TTD PNG valid / kosong / format salah | TTD wajib dan PNG asli; penggantian aktif tidak menimpa arsip |
| A11-203 | Siswa layak dengan skor Nilai TP Final 0, Pra-Semester belum ada | Sertifikat boleh terbit; 0,00 bukan NULL; Pra-Semester tidak dicetak |
| A11-204 | TP belum tuntas, Sumatif tidak sah, nilai Sementara atau TTD hilang | Tidak ada PDF/DB sertifikat baru, tampil alasan |
| A11-205 | Admin mengirim POST penerbitan dua kali/bersamaan | Idempoten: satu versi dan satu sertifikat aktif |
| A11-206 | Admin mengubah nilai cetak secara sah, lalu revisi satu siswa dengan alasan | Nomor+QR baru, template background historis, TTD aktif terbaru; versi lama `digantikan` dan PDF tidak dihapus |
| A11-207 | Admin revisi tanpa perubahan isi cetak | Tidak membuat versi/PDF baru; hanya audit nilai internal bila ada |
| A11-208 | Siswa mencoba download sertifikat siswa lain, non-Admin coba POST | Ditolak; file privat tidak dapat diakses langsung |
| A11-209 | Publik memindai QR berlaku/digantikan dan token acak tak sah | Hanya data whitelist, versi lama menunjuk aktif, tidak ada NISN/TTD/kontak/alasan/PDF, throttle berlaku |
| A11-210 | Transaksi gagal, PDF rusak/hilang, aset historis berubah | Rollback tanpa dua sertifikat berlaku; file baru boleh dibersihkan, PDF/riwayat lama utuh |
| A11-211 | Backup dan restore MariaDB XAMPP salinan dengan V4-0011 | Empat tabel, FK/UNIQUE, aset dan PDF lama tetap dapat diverifikasi |
| A11-212 | Browser Dark/Light, HP dan Mode Dokumentasi | Card, tabel, preview PDF, tombol/galat/sukses, QR terbaca |

PHP lint + PHPUnit SQLite sintetik + renderer Dompdf/endroid dijalankan GitHub Actions `qa-a11-2-sertifikat.yml`; **pengujian MariaDB aktual dan browser XAMPP tetap wajib** sebelum menyatakan fitur FIX.

### A11-3 — Pemeriksaan tata letak hasil cetak Sertifikat (3 November 2026)

| ID | Kondisi | Hasil wajib |
|---|---|---|
| A11-301 | PDF hasil XAMPP dengan 2 BAB, 4 TP, NISN demo, QR dan TTD | A4 landscape; bingkai memenuhi sebagian besar halaman, judul/nama/tabel terbaca, QR dan TTD tidak saling bertumpuk |
| A11-302 | Preview template bawaan Admin | Dua BAB/empat TP **sintetis**, watermark pratinjau, tanpa NISN/TTD nyata |
| A11-303 | Semester dengan 24 TP dan beberapa BAB panjang | Semua baris TP muncul dalam PDF; halaman tambahan diperbolehkan, tidak menghapus baris dan tidak menumpuk tanda tangan |
| A11-304 | Dokumen historis versi V001 sudah diterbitkan sebelum pembaruan renderer | Bytes PDF lama, nomor/QR dan status tidak berubah; tidak ada penerbitan ulang otomatis |
| A11-305 | Nilai TP Esensial/NonEsensial dan Pra-Semester | Esensial tanpa nilai, NonEsensial nilai TP tersimpan, tidak ada nilai Pra-Semester/Nilai Akhir Semester di dokumen |
| A11-306 | Cetak printer PDF dan browser layar HP/desktop | Tidak ada judul yang terpotong, font terlalu kecil, overlap tabel dengan QR atau tanda tangan |

Tes otomatis `tests/unit/SertifikatTataLetakPdfTest.php` membuat PDF sintetis 4/24 TP; workflow `QA A11-3 Layout Cetak Sertifikat PDF` memeriksa A4 landscape, jumlah halaman dan teks TP menggunakan `pdfinfo`/`pdftotext`. **QA visual PDF terbaru di XAMPP masih wajib sebelum menutup A11-3.**

### A12-1 — QA Dashboard Pimpinan berbasis data sah (3 November 2026)

| ID | Skenario | Hasil wajib |
|---|---|---|
| A12-101 | Pimpinan membuka `/pimpinan` dengan satu atau beberapa Gen–Mapel–Semester | KPI numerik sesuai data, hanya paket aktif/ditugaskan, tanpa NISN/tombol mutasi |
| A12-102 | Sumatif normal dan remedial sama-sama `selesai_sah` | Satu pasangan siswa×TP dihitung **sekali**, bukan dua kali |
| A12-103 | Nilai TP Final 0 dan nilai Sementara/NULL | Nilai Final 0 ikut dihitung; nilai Sementara/NULL tidak |
| A12-104 | Semester `siswa.semester_mulai` atau Gen lebih tinggi | Siswa semester dilewati tidak masuk pasangan wajib; paket tanpa TP tidak 100% |
| A12-105 | Sertifikat versi baru dan arsip `digantikan` | Hanya pointer `sertifikat_aktif` yang berstatus berlaku dihitung |
| A12-106 | Resume/HOTS final skor NULL, draf skor NULL, final skor 0 | Hanya final skor NULL masuk antrean; nilai 0 dianggap sudah dinilai |
| A12-107 | Penugasan Guru per Gen–Mapel pada beberapa semester | Tidak menghitung pasangan Gen–Mapel dua kali hanya karena paket semester berbeda |
| A12-108 | Filter Gen/Mapel/Semester dan pagination | Semua KPI mengikuti filter; tabel 12 paket/halaman; tautan Monitoring mempertahankan paket |
| A12-109 | Filter ID tidak sah/array, akun Guru atau Pimpinan nonaktif | Penolakan server; tidak ada pembacaan lewat role lain |
| A12-110 | Dark/Light, Mode Dokumentasi, HP 360/390px, keyboard | Label, tabel scroll, progress, dan pesan kosong terbaca |
| A12-111 | Pra-Semester siap/draf/ditutup/belum tersedia | Status hanya tampil terpisah; tidak memengaruhi TP, nilai, sertifikat |
| A12-112 | Dataset kosong atau paket tanpa TP NonEsensial | Tidak menampilkan progres sebagai 100%, tidak membuat skor simulasi |

QA otomatis `tests/unit/DashboardPimpinanDatabaseTest.php` menggunakan SQLite anonim sesuai skema dump V4-0011; workflow `QA A12-1 Dashboard Pimpinan` menjalankan lint dan regresi `LaporanAkademikDatabaseTest.php`. Uji browser XAMPP tetap wajib sebelum A12-1 dinyatakan FIX. A12 berikutnya mencakup dashboard Admin/Guru/Siswa serta notifikasi/pengumuman/FAQ, bukan klaim bagian A12-1.

### A12-2 — QA Dashboard Guru berbasis Gen–Mapel (3 November 2026)

| ID | Skenario | Syarat |
|---|---|---|
| A12-201 | Guru G1 memiliki dua paket semester, Gen lain dimiliki Guru berbeda | Hanya paket Gen–Mapel sendiri; indeks GET lain ditolak |
| A12-202 | Guru tanpa penugasan, penugasan dicabut, paket semester belum tersedia | State kosong jelas, tidak membaca data Guru sebelumnya |
| A12-203 | TP Esensial dan NonEsensial masing-masing 0/1/2 Materi aktif | Hanya yang jumlahnya bukan satu masuk `Materi perlu diperiksa`; termasuk Esensial |
| A12-204 | TP status Siap, Draf, Arsip | Siap/Draf terhitung dan Arsip dikecualikan |
| A12-205 | Resume/HOTS final dan skor NULL; skor 0; draf; versi lama; Gen lain | Hanya final NULL, dinilai_oleh NULL, snapshot sesuai, Gen cocok masuk antrean |
| A12-206 | Siswa dengan dua TP status `perlu_bantuan` pada paket sama | Satu siswa dihitung sekali per paket |
| A12-207 | Diagnostik dan Sumatif status `berjalan` versus selesai dan Pra-Semester | Hanya dua ujian TP aktif dihitung, Pra-Semester tidak dicampur |
| A12-208 | Filter Semester 1–6 dan pergantian penugasan Guru | Semua KPI mengikuti kombinasi terpilih; data Gen lain tidak bocor |
| A12-209 | Guru Nonaktif/Pimpinan/Admin/Siswa mencoba service/route | Ditolak; tidak ada POST/mutasi dari dashboard |
| A12-210 | UI Dark/Light, 360/390/768/1100px dan Mode Dokumentasi | KPI, filter, link Buka Semester, scroll keyboard aman |

Workflow `QA A12-2 Dashboard Guru` menjalankan `DashboardGuruDatabaseTest.php` SQLite sintetis, regresi Penilaian Guru dan Keputusan Akses Ulang, serta PHP lint. Hasil perlu ditinjau pada PR; **uji browser XAMPP tetap diperlukan sebelum FIX**. Tidak ada perubahan SQL.

### A12-3 — QA Dashboard Siswa dan gerbang pembelajaran (3 November 2026)

| ID | Skenario | Hasil wajib |
|---|---|---|
| A12-301 | Siswa Gen A mulai Semester 4, memiliki paket 4 dan 5 | Hanya paket yang lolos gerbang katalog; semester 5 belum terbuka sebelum semua TP wajib semester 4 sah |
| A12-302 | Siswa pindahan mulai Semester 5 | Semester 4 dilewati, tidak dihitung sebagai target dan tidak diberi tautan |
| A12-303 | TP status tuntas tetapi Sumatif belum `selesai_sah`; Diagnostik selesai sah | **Bukan** TP tuntas sah, tidak membuka TP/semester berikutnya |
| A12-304 | Sumatif normal/remedial sama-sama sah pada TP sama | Dihitung satu kali, tidak menggandakan TP maupun progres |
| A12-305 | Nilai TP Final 0, Sementara 70, final tetapi Sumatif belum sah | Hanya Final untuk TP tuntas sah ikut KPI, 0 termasuk sah |
| A12-306 | Paket hanya Esensial/tanpa TP NonEsensial | Progres null dan pesan tidak ada target, bukan 100% |
| A12-307 | TP perlu bantuan dan hanya TP berikutnya yang boleh dibuka | Status bantuan jelas; tautan lanjut belajar berasal dari `akses.buka` katalog, bukan memulai ujian |
| A12-308 | Sertifikat berlaku dan arsip digantikan | KPI hanya pointer versi berlaku, tidak menghitung arsip |
| A12-309 | Gen belum memiliki paket atau tidak memiliki semester terbuka | State kosong informatif, link ke daftar pembelajaran, tanpa progres fiktif |
| A12-310 | Guru/Admin/Pimpinan, siswa nonaktif, atau Gen lain | Akses ditolak; tidak membuka paket/TP Gen lain atau data siswa lain |
| A12-311 | Reload/tutup dashboard | Tidak membuat progres, attempt, nilai, atau sertifikat |
| A12-312 | Dark/Light, Mode Dokumentasi, HP/desktop | Segmen/progress tidak bertumpuk, teks dan tombol tetap terbaca/fokus keyboard |

Pengujian otomatis `tests/unit/DashboardSiswaDatabaseTest.php` menggunakan SQLite in-memory anonim, ditambah regresi Katalog Pembelajaran serta Laporan Akademik di workflow `QA A12-3 Dashboard Siswa`. Tidak ada SQL baru; hasil visual harus diuji pemilik pada XAMPP sebelum A12-3 dinyatakan FIX.

### A12-3 UI QA lanjutan — Validasi setelah screenshot XAMPP (3 November 2026)

| ID | Skenario | Hasil yang wajib |
|---|---|---|
| A12-313 | Siswa punya tepat satu paket Semester terbuka pada desktop | Kartu Semester memenuhi lebar panel, informasi mapel/TP serta progres/aksi seimbang dalam dua bidang |
| A12-314 | Dashboard siswa di HP 360–390px dengan paket tunggal | Kartu dan hero kembali satu kolom; teks panjang TP tidak terpotong/tumpang tindih |
| A12-315 | TP pertama/berikutnya Siap dan `akses.buka=true` | Kode dan judul TP sesuai katalog terlihat di hero dan kartu, link mengarah ke `/siswa/tp/{id}` |
| A12-316 | TP belum Siap/terkunci, semester belum terbuka | Kode/judul TP tersebut tidak bocor; tidak ada tombol direct TP, gunakan tautan Semester/daftar yang sah |
| A12-317 | Nama akun mengandung teks "Diagnostik Dibatalkan" namun progres tidak `perlu_bantuan` | KPI bantuan tetap mengikuti data progres server, bukan membaca nama akun atau asumsi dari screenshot |

Regresi A12-3 wajib memverifikasi bahwa penambahan metadata TP tujuan **tidak melonggarkan hak akses** dan tidak memodifikasi nilai/progres siswa. QA visual XAMPP tetap diperlukan untuk menyatakan revisi UI ini FIX.

### A12-4 — QA Dashboard Admin dan indikator kesehatan yang jujur (3 November 2026)

| ID | Uji | Hasil wajib |
|---|---|---|
| A12-401 | Akun/Admin aktif membuka `/admin` | Empat KPI referensi, tautan akses cepat, enam KPI operasional dan panel perhatian tampil |
| A12-402 | 2 Gen × 2 Mapel, 2 pasangan G1 terisi | KPI terisi 2, belum 2; tidak mengubah penugasan |
| A12-403 | Akun aktif/nonaktif dan siswa terdaftar | Hitungan akun aktif berbeda dari siswa terdaftar, tidak menampilkan biodata |
| A12-404 | TP Siap, Draf, Arsip | Hanya status Siap dan Draf dihitung sesuai kolom masing-masing |
| A12-405 | Dua foto menunggu tetapi hanya satu ditunjuk profil | Antrean persetujuan = 1; foto historis tidak digandakan |
| A12-406 | Sertifikat berlaku dan digantikan | Hanya versi berlaku pada `sertifikat_aktif` yang dihitung |
| A12-407 | TTD belum aktif, hilang, checksum rusak, valid | Status jelas belum/bermasalah/siap; tanpa path/SHA berkas privat pada HTML |
| A12-408 | Disk tidak dapat dibaca, cron dan backup tidak mempunyai sumber status | Tidak menampilkan sehat palsu; cron/backup tetap belum diperiksa |
| A12-409 | Admin nonaktif/role Guru/Siswa/Pimpinan mengakses service | Ditolak, tanpa data agregat |
| A12-410 | Reload Dashboard Admin dan tautan aksi | Tidak mengubah foto, nilai, progres, sertifikat, penugasan |
| A12-411 | UI laptop, HP 360/390px, Dark/Light, Dokumentasi | Grid, angka, teks tindakan, alert dan shortcut responsif; tidak bertumpuk |

GitHub Actions `QA A12-4 Dashboard Admin` memakai SQLite anonim + lint + regresi Dashboard Pimpinan. Uji visual angka pada XAMPP tetap wajib. Tidak ada migrasi, seeder atau SQL pembaruan.

### A12-4 QA lanjutan — audit screenshot halaman Admin (3 November 2026)

| ID | Uji | Hasil wajib |
|---|---|---|
| A12-412 | Konfirmasi reset sandi pada DARK/LIGHT | Nama, Username/NISN, Status terbaca pada panel kontras; tombol bahaya, CSRF dan pembatasan reset sendiri tetap |
| A12-413 | Daftar Pengelola/Siswa pada desktop ≥1100px | Form tambah di atas; tabel 5 kolom memenuhi lebar kontainer; tombol Ubah/Reset/Status tidak saling bertumpuk |
| A12-414 | Daftar Pengelola/Siswa pada HP 360/390px | Form satu kolom dan tabel responsif berupa kartu; aksi dapat disentuh dan pager tidak terpotong |
| A12-415 | Tambah Mapel & Guru per Gen | Form dua kolom dan tidak menyisakan ruang horizontal besar; penugasan G1/riwayat sama; banner historis K2 tidak mengacaukan alur |
| A12-416 | Filter CP cari/mapel/kurikulum/status, ekspor XLSX | Tombol filter dan tabel terpisah, tidak overlap; fitur GET, 5 isian CP dan riwayat tetap |
| A12-417 | Identitas Madrasah situs web opsional | Label tidak menduplikasi kata '(opsional)' |
| A12-418 | Dashboard Admin 3 Gen × 20 Mapel, 3 G1 | 57 ditampilkan sebagai kombinasi potensial belum ditugaskan, bukan mengklaim 57 kelas wajib tanpa sumber |
| A12-419 | Reload dan navigasi semua 14 layar | Tidak membuat/mengubah nilai, akun, penugasan, CP, jadwal, TTD atau sertifikat hanya dari kunjungan GET |

Tes otomatis `AdminUiAuditTest.php` memeriksa keberadaan token dan struktur HTML di CI serta regresi database anonim Dashboard Admin; hasil ini **bukan pengganti uji screenshot** setelah pull XAMPP. Tidak ada SQL.

### A12-4 QA lanjutan 2 — formulir dan Profil dari screenshot XAMPP (3 November 2026)

| ID | Skenario | Hasil wajib |
|---|---|---|
| A12-420 | Tambah Pengelola desktop/HP | Pilihan peran tiga label sejajar desktop, satu kolom HP; syarat maksimal 2 peran dan validasi server tetap |
| A12-421 | Tambah Pengelola/Siswa, input sandi dan CTA | Tombol Buat Akun berada di baris tindakan sendiri; tidak menutup petunjuk sandi, tidak mengubah POST/CSRF |
| A12-422 | Gen & Tahun Ajaran | Kolom Gen: Form→Daftar Gen; kolom Tahun: Form→Daftar Tahun. Daftar Tahun tidak didorong ke bawah oleh tinggi Form Gen; responsive HP |
| A12-423 | Identitas Madrasah | Input singkat dua kolom desktop, alamat full-width, CTA di bawah; field wajib, dirty-check, validasi angka/email tetap |
| A12-424 | Profil Admin/Guru/Pimpinan | Foto disetujui dan menunggu tetap terbaca; file JPG/PNG dan petunjuk unggah berdampingan desktop, bertumpuk HP |
| A12-425 | Profil Siswa, multi-peran, unggah foto | Akses profil Siswa tidak menampilkan upload staf; proses upload tetap meminta persetujuan Admin, ganti sandi/peran tidak berubah |

Regresi statis PHP menegaskan struktur form/CSRF/elemen CSS, sedangkan screenshot XAMPP Dark/Light dan mobile tetap diperlukan sebelum FIX. Tidak ada perubahan SQL atau penyimpanan data.

### A13-1 — QA Pusat Bantuan peran (3 November 2026)

| ID | Skenario | Kriteria PASS |
|---|---|---|
| A13-101 | Admin, Guru, Siswa, Pimpinan aktif login membuka `/bantuan` | Panduan sesuai role aktif, navigasi dan tombol kembali ke beranda sendiri |
| A13-102 | Pengunjung belum login | AuthFilter mengarahkan login; tidak menampilkan panduan tanpa autentikasi |
| A13-103 | Sandi sementara belum diganti, akun tidak aktif, peran tidak dikenal | Ditolak/diarahkan oleh pemeriksaan otorisasi sebelum render |
| A13-104 | Siswa membuka Bantuan | Tidak ada tautan `admin/` atau `guru/`; hanya `siswa/` dan Profil |
| A13-105 | Staf multi-peran sedang Guru/Pimpinan | Tidak mendapat tautan peran lain sampai ganti peran melalui Profil |
| A13-106 | Pimpinan membuka panduan | Hanya Monitoring baca-saja dan Profil, tidak ada tombol menulis nilai/jadwal |
| A13-107 | Seluruh tombol panduan | Semua target GET terdaftar di Routes, akses tetap dicek lagi pada target |
| A13-108 | Isi akademik Siswa | Diagnostik hanya gerbang, Sumatif wajib, akses ulang bukan remedial, Pra-Semester tidak syarat/cetak sertifikat |
| A13-109 | Render judul/jawaban dan `path` | Di-escape; tidak ada token/kunci/identitas siswa, tidak ada POST ataupun database baru |
| A13-110 | Reload dan navigasi, Dark/Light, lebar 360/390/768/1100px | Detail/summary bekerja dengan mouse/keyboard tanpa JS, tidak mengubah status akademik |

Workflow `QA A13-1 Pusat Bantuan Peran` menjalankan PHP lint dan `tests/unit/PanduanBantuanServiceTest.php` serta regresi UI Admin. **Pengumuman, FAQ terkelola Admin, Notifikasi internal, dan Hubungi Admin belum termasuk A13-1** karena perubahan SQL dan sumber resmi harus diverifikasi pada dump XAMPP terbaru menurut AGENTS. Belum ada E2E browser otomatis; uji XAMPP manual tetap wajib untuk FIX.

### A13-2 — QA Pengumuman dan FAQ resmi (3 November 2026)

| ID | Skenario | Kriteria PASS |
|---|---|---|
| A13-201 | Impor patch V4-0012 pada database v4 setelah backup | Tabel Pengumuman/FAQ/Riwayat, FK dan penanda versi ada, data lama utuh |
| A13-202 | Admin/Guru menyimpan Pengumuman Draf | Hanya tampak di kelola berizin, tidak bocor pada daftar publik |
| A13-203 | Pengumuman Terbit | Admin/Guru/Siswa/Pimpinan membaca konten yang sama tanpa target Gen/role |
| A13-204 | Guru menebak ID Pengumuman Guru lain/Admin | Editor, simpan dan arsip ditolak Service; audit/konten utuh |
| A13-205 | Admin menyunting Pengumuman Guru | Perubahan versi dan audit aktor benar, tetap global |
| A13-206 | Mengirim formulir dari tab yang tertinggal versinya | Ditolak tanpa menimpa versi baru |
| A13-207 | Mengarsipkan Pengumuman/FAQ | Tidak tampil di publik; record dan riwayat tetap ada |
| A13-208 | Guru/Siswa/Pimpinan mencoba kelola FAQ | Ditolak filter dan Service; hanya Admin dapat mengelola |
| A13-209 | FAQ Admin Draf/Terbit/Arsip dan urutan | Hanya Terbit muncul pada `/bantuan`, urut dan berpaginasi |
| A13-210 | Input kosong, panjang berlebihan, status/urutan invalid, HTML | Ditolak server; konten HTML ditampilkan sebagai teks |
| A13-211 | Akun tidak login/nonaktif/sandi sementara/role salah | Akses ditolak, tidak ada data draf yang bocor |
| A13-212 | Dark/Light, 360/390/768/1100px, keyboard | Tabel scroll, panel terbaca, FAQ bisa dibuka via keyboard, pesan kosong benar |

Workflow `QA A13-2 Pengumuman dan FAQ` menjalankan PHP lint, PHPUnit `InformasiGlobalDatabaseTest.php` dengan SQLite anonim, regresi A13-1 `PanduanBantuanServiceTest.php`, serta **impor SQL asli V4-0012 pada MariaDB ephemeral** lewat `SqlA13InformasiSchemaCheck.php`. Pada run awal `41662610185` semuanya PASS: **12 tes/92 assertions**, **9 tes/219 assertions**, SQL MariaDB PASS. Ini bukan uji XAMPP; backup + impor SQL dan browser QA lokal tetap wajib. Notifikasi per penerima, badge unread, Hubungi Admin dan Kebijakan Privasi publik belum ditangani.


### A13-3 — QA Hubungi Admin (3 November 2026)

| ID | Skenario | Kriteria PASS |
|---|---|---|
| A13-301 | Admin/Guru/Siswa/Pimpinan membuka `/hubungi-admin` | Hanya lima kolom kontak sekolah resmi dari `pengaturan`; tidak memuat data pribadi staf |
| A13-302 | Belum login, akun nonaktif, sandi awal atau role invalid | Ditolak sebelum membaca kontak |
| A13-303 | Email, telepon, situs valid | Tautan mailto/tel/HTTP(S) terbentuk dan di-escape |
| A13-304 | Nilai lama skema JavaScript, email/telepon invalid | Hanya teks di-escape, tidak membuat tautan yang berbahaya |
| A13-305 | Informasi kontak kosong | State kosong jelas, tidak mengarang nomor/alamat |
| A13-306 | Navigasi empat role, Bantuan, Dark/Light, HP 360–390px | Semua tautan bekerja, kartu tidak meluber dan fokus keyboard terlihat |
| A13-307 | Kunjungan GET dan reload | Tidak mengubah pengaturan atau membuat log/tiket/notifikasi |

CI: PHP lint, PHPUnit SQLite anonim `KontakResmiMadrasahServiceTest.php`, regresi Bantuan A13-1; pengujian XAMPP tetap diperlukan sebelum FIX. **A13-3 tidak memerlukan patch SQL.**
