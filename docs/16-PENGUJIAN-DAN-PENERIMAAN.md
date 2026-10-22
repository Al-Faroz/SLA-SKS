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
| E2E-009 | Guru membuat TP dengan CP resmi | Minimal satu CP utama, mapel/fase valid |
| E2E-010 | Guru usul CP; Admin memutus | Publikasi versi CP baru tanpa reviewer Guru lain |
| E2E-011 | Admin+Guru menyetujui usulan CP sendiri | Ditolak bila benar-benar permohonan akademik diri sendiri |
| E2E-012 | Download Excel peta ATP enam semester | Sheet panduan, RINGKASAN, REFERENSI CP, SEMESTER 1–6 dan contoh; hanya CP aktif mapel/kurikulum terkait |
| E2E-013 | Impor XLSX valid diulang | Upsert idempoten, bukan baris ganda |
| E2E-014 | Impor versi struktur salah | Ditolak, tampil sheet/baris/kolom/penjelasan |
| E2E-015 | Excel menghilangkan baris lama | Tidak menghapus otomatis |
| E2E-016 | Media/HOTS Materi belum lengkap | Excel hanya membentuk struktur; checklist TP Siap menolak materi tanpa media/Resume/HOTS |
| E2E-017 | TP Siap diedit via form/API/impor | Ditolak; hanya jalur koreksi A–C |
| E2E-018 | TP koreksi A–C disetujui | Versi baru; siswa yang mulai tetap pada versi lama |
| E2E-019 | Koreksi kategori D | Ditolak |
| E2E-020 | Siswa tidak lulus diagnostik | Setiap Materi wajib media → Resume final → HOTS final secara urut, kemudian Sumatif |
| E2E-021 | Siswa lulus diagnostik | TP tuntas; materi boleh sukarela, resume/tugas resmi tidak dikirim |
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
| E2E-044 | Koreksi soal mengubah tuntas→belum | Status tuntas dihitung ulang tanpa hapus progres/attempt |
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
| E2E-066 | Menjalankan import SQL full di DB bersih MySQL & MariaDB | Keduanya sukses, struktur/relasi sesuai |
| E2E-067 | Menjalankan SQL update pada DB berisi progres | Tidak menghapus siswa/sertifikat/riwayat |
| E2E-068 | Website kehilangan cron 5 menit | Request ujian tetap rekonsiliasi dengan waktu server |
| E2E-069 | Backup manual dan restore di lingkungan uji | Siswa, aset historis dan sertifikat dapat dibaca |
| E2E-070 | Visual/aksesibilitas 360/390/768/1100 px | Navigasi, tombol, form, grafik dapat digunakan |
| E2E-071 | Membuka halaman Kebijakan Privasi tanpa login | Teks kebijakan sekolah berversi terbaca, tanpa kebocoran data siswa |
| E2E-072 | Notifikasi jenis TP, ujian, penilaian, sertifikat, permohonan | Hanya event sah, penerima tepat, idempoten; tidak mengirim email/WhatsApp |
| E2E-073 | Dua jawaban resume sangat mirip pada materi sama | Badge Perlu dicek muncul pada Guru, tidak otomatis menahan siswa |
| E2E-074 | Resume/tugas dengan nilai sementara dan bobot 0 | Tetap menunggu penilaian komponen wajib sebelum sertifikat |
| E2E-075 | Menghapus TP draf tanpa progres, lalu mencoba menghapus TP Siap | Hanya draf aman yang boleh dihapus; data TP Siap utuh |

### Skenario penerimaan tambahan v2.4
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
| E2E-087 | Patch SQL transisi 0003 pada database K2 yang telah terisi | K1/K2 dan akun/sandi/Gen/mapel/riwayat tetap utuh, penugasan global tak dianggap berlaku otomatis, rollback melalui backup terbukti |
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

### Skenario tambahan wajib v2.4 — **E2E-089 s.d. E2E-112**
| ID | Skenario | Syarat PASS |
|---|---|---|
| E2E-089 | Guru ditugasi dua Gen/Mapel mengunduh ATP berbeda | RINGKASAN berisi Guru/Gen/Mapel/Kurikulum sesuai pilihan sah; file tidak saling tertukar |
| E2E-090 | Excel referensi CP otomatis | Hanya CP aktif sesuai Mapel/Fase D/Kurikulum, tanpa mapel lain; semua pilihan CP valid |
| E2E-091 | Excel enam semester revisi pemilik | Tidak ada istilah BAB operasional, enam sheet semester dan contoh; identitas tidak bisa diklaim oleh Gen lain |
| E2E-092 | Impor Excel satu semester saja | Struktur TP dan Materi terbentuk tanpa input ulang, semester lain tidak dihapus |
| E2E-093 | Impor Excel sama dua kali | Idempoten tanpa TP/Materi duplikat; baris hilang bukan delete |
| E2E-094 | ATP mencoba mengubah TP Siap | Ditolak sebelum commit; data historis tetap |
| E2E-095 | TP dengan banyak Materi | Semua Materi memiliki Resume dan HOTS sendiri, penugasan/progres/penilaian per Materi |
| E2E-096 | Diagnostik lulus sesuai SKS | TP tuntas, nilai Diagnostik, Materi sukarela tanpa pengiriman Resume/HOTS resmi |
| E2E-097 | Diagnostik gagal | Materi berikutnya terkunci sampai Resume dan HOTS Materi sebelumnya final; nilai Guru tidak memblokir lanjut |
| E2E-098 | Skor HOTS beberapa Materi | Rata-rata skor HOTS diterapkan ke bobot HOTS TP, Resume dan bobot 20/30/50 tetap |
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

### Uji kontrak SQL lokal khusus v2.4
Uji import dump hasil analisis ke DB kosong khusus XAMPP **sesudah backup/izin pemilik**, periksa jumlah data terpilih, FK, nomor ATP dan referensi CP, login sah (tanpa membuka password/hash ke log), tidak ada BAB operasional baru, TP berelasi langsung ke paket, HOTS per Materi, kemampuan restore; **uji di MariaDB dengan jalur non-destruktif yang berbeda**. Uji ini **belum** dilakukan pada perubahan dokumen saja.
