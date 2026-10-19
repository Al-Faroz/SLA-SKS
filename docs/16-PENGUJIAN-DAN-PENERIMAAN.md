# 16 — Pengujian dan Kriteria Penerimaan

## 1. Strategi
Tiap fitur harus lolos: **uji aturan bisnis di Service, uji integrasi DB, uji endpoint & hak akses, uji browser desktop/HP, uji keamanan URL**, serta verifikasi perilaku sukses dan gagal. PHPUnit untuk kode server; browser testing dapat memakai Playwright bila fasilitas lokal tersedia. **Jangan mewajibkan test runner Node/daemon pada shared hosting**, pengujian dijalankan sebelum deploy.

Untuk daftar detail berikut, ID `E2E-...` adalah identitas pengujian final paket Master. Nomor UJ historis dari dokumen lama dapat digunakan sebagai referensi internal tetapi **hasil akhir wajib mengikuti keputusan v2.2 dalam paket ini**, bukan ekspektasi usang.

## 2. Skenario end-to-end wajib
| ID | Skenario | Hasil wajib |
|---|---|---|
| E2E-001 | Login pertama siswa/guru | Sandi awal wajib diganti; tidak bisa membuka fitur lain |
| E2E-002 | Gagal login 5 kali | Kunci 5 menit; tidak bocor akun mana yang valid |
| E2E-003 | Staf Admin+Guru mengganti peran | Session ID baru; izin hanya role aktif |
| E2E-004 | Satu Admin aktif | Administrasi normal dan koreksi biodata berfungsi |
| E2E-005 | Admin+Guru menyetujui usulan akademik miliknya | Ditolak, tanpa nilai/konten berubah; pesan informatif |
| E2E-006 | Guru mengakses mapel lain via ID URL | Ditolak server |
| E2E-007 | Guru mapel diganti | Guru baru berwenang operasional; riwayat penilai lama utuh |
| E2E-008 | Semester Gen dan override siswa | Semester sebelum mulai Dilewati, tidak menghilangkan progres |
| E2E-009 | Guru membuat TP dengan CP resmi | Minimal satu CP utama, mapel/fase valid |
| E2E-010 | Guru usul CP; Admin memutus | Publikasi versi CP baru tanpa reviewer Guru lain |
| E2E-011 | Admin+Guru menyetujui usulan CP sendiri | Ditolak bila benar-benar permohonan akademik diri sendiri |
| E2E-012 | Download template BAB | Tepat 8 sheet: PETUNJUK, IDENTITAS_BAB, TP, MATERI, TUGAS_HOTS, SOAL_DIAGNOSTIK, SOAL_SUMATIF, _META |
| E2E-013 | Impor XLSX valid diulang | Upsert idempoten, bukan baris ganda |
| E2E-014 | Impor versi struktur salah | Ditolak, tampil sheet/baris/kolom/penjelasan |
| E2E-015 | Excel menghilangkan baris lama | Tidak menghapus otomatis |
| E2E-016 | Media belum lengkap di Excel | Impor memberi warning, BAB Siap ditolak |
| E2E-017 | BAB Siap diedit via form/API/impor | Ditolak; hanya jalur koreksi A–C |
| E2E-018 | BAB koreksi A–C disetujui | Versi baru; siswa yang mulai tetap pada versi lama |
| E2E-019 | Koreksi kategori D | Ditolak |
| E2E-020 | Siswa tidak lulus diagnostik | Wajib materi/resume/HOTS/sumatif |
| E2E-021 | Siswa lulus diagnostik | TP tuntas; materi boleh sukarela, resume/tugas resmi tidak dikirim |
| E2E-022 | Simpan draf manual | Data berubah baru tersimpan setelah klik Simpan; indikator jelas |
| E2E-023 | Siswa menutup tab dengan draf belum disimpan | Peringatan perubahan belum disimpan; tidak mengklaim autosave |
| E2E-024 | Siswa kirim resume/tugas final dua kali | Satu pengiriman; terkunci setelahnya |
| E2E-025 | Siswa akses BAB/TP terkunci via URL | 403/penolakan bisnis konsisten |
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
| E2E-072 | Notifikasi jenis BAB, ujian, penilaian, sertifikat, permohonan | Hanya event sah, penerima tepat, idempoten; tidak mengirim email/WhatsApp |
| E2E-073 | Dua jawaban resume sangat mirip pada materi sama | Badge Perlu dicek muncul pada Guru, tidak otomatis menahan siswa |
| E2E-074 | Resume/tugas dengan nilai sementara dan bobot 0 | Tetap menunggu penilaian komponen wajib sebelum sertifikat |
| E2E-075 | Menghapus BAB draf tanpa progres, lalu mencoba menghapus BAB Siap | Hanya draf aman yang boleh dihapus; data BAB Siap utuh |

## 3. Matriks nonfungsional
- **Keamanan:** CSRF, injection/XSS, file palsu, akses lintas role, force browse direktori root, rahasia `.env`, download TTD, QR throttle.
- **Integritas:** dua transaksi bersamaan, idempotensi konfirmasi Excel dan sertifikat, optimistic locking nilai, satu sertifikat aktif, audit append-only.
- **Kompatibilitas:** MySQL lokal, MariaDB hosting; HP Chrome/Android, desktop Chrome/Firefox/Edge, browser tanpa fullscreen.
- **Performa:** ±30 pengguna simultan sesuai skala; kiriman jawaban cepat, tidak duplikat; ekspor besar diberi pembatasan/pesan.
- **Aksesibilitas:** kontras ≥4,5:1, ukuran target ≥40px, teks alternatif grafik, fokus keyboard, reduced motion, formulir berlabel.
- **Kondisi buruk:** koneksi hilang, disk penuh, template/TTD hilang, SMTP tidak ada (tidak diperlukan), phpMyAdmin update gagal, browser refresh.

## 4. Definisi selesai tiap modul
Fitur dinyatakan selesai bila **aturan domain + izin + data SQL + route/service + UI normal dan seluruh state + uji sukses/gagal + catatan keputusan yang diimplementasikan** telah memenuhi dokumen resmi. Tidak cukup memiliki halaman yang tampak menarik tapi tombol belum bekerja. Setiap Pull Request menjelaskan bagian dokumen yang menjadi rujukan dan bukti pengujian yang relevan.
