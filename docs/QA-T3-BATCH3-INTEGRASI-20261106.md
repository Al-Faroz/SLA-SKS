# QA XAMPP — Stabilisasi UI Batch 2, Batch 3 dan Integrasi v2.5
Tanggal: 6 November 2026 · Repo: Al-Faroz/SLA-SKS
Status: **panduan uji manual; hasil tidak dianggap PASS hingga dicatat pemilik**.

## A. Persiapan aman (tanpa SQL)
1. Buat backup database dan folder `writable/` lokal. Jangan reset atau impor ulang V4-0013/V4-0014.
2. Di CMD, jika tidak ada perubahan lokal yang belum disimpan:
   ```bat
   cd /d G:\xampp\htdocs\sla-sks
   git status --short
   git fetch origin
   git switch feature/ui-tahap3-batch3-pra-sertifikat-laporan-20261106
   git pull --ff-only origin feature/ui-tahap3-batch3-pra-sertifikat-laporan-20261106
   php -l app\Views\admin\pra_semester.php
   php -l app\Views\guru\pra_semester.php
   php -l app\Views\siswa\sertifikat.php
   vendor\bin\phpunit --no-coverage tests\unit\VibrantGlassBatch3AuditTest.php
   vendor\bin\phpunit --no-coverage tests\unit
   ```
3. Jalankan Apache/MySQL XAMPP seperti biasa, buka `http://localhost/sla-sks/`. Jangan mengubah `.env`, struktur ROOT, atau URL.
4. Pakai akun **simulasi** dan data uji yang sudah sah; jangan unggah data nyata/sandi/screenshot privasi ke GitHub. Uji Dark dan Light, sidebar normal/collapsed, ukuran 1440, 1100, 820, 390, 360 piksel, serta navigasi keyboard.

## B. UI Stabilisasi Batch 2
- [ ] Guru → Kelola TP: status Draf/Siap/telah dipakai siswa jelas; Step Progress sesuai kondisi server; aksi simpan materi tetap bekerja dan menolak yang terkunci.
- [ ] Guru → Bank Soal: tab Diagnostik/Sumatif, jumlah soal sah, KKM efektif, pagination, form manual, pratinjau Word, konfirmasi tanpa soal ganda.
- [ ] Guru → Penilaian: lima filter GET, antrean dan detail; nilai 0 tetap bermakna; Koreksi Nilai G1 langsung memperbarui histori tanpa approval Admin.
- [ ] Di halaman panjang: tombol Kembali ke Atas hanya panah dalam lingkaran 48px, berfungsi dan tidak menutupi tombol penting; Mode Dokumentasi lama tidak tampil.
- [ ] Browser desktop/HP: jangan ada form yang menyatu dengan latar, teks hilang/terpotong, sidebar bertindihan atau tabel keluar viewport.

## C. UI Batch 3
- [ ] Admin → Pra-Semester: filter Gen, Mapel, Semester, Status; reset; paket kosong; status dan tombol Atur Jadwal. Tabel lebar bisa scroll horizontal dengan keyboard.
- [ ] Admin → Detail Pra-Semester: simpan jendela/durasi, jadwal remedial, susulan dan pemilihan peserta tetap terotorisasi; pesan validasi terlihat.
- [ ] Guru → Pra-Semester: filter Gen, status paket/bank, tombol Kelola soal, pratinjau Word dan penyimpanan. Guru tidak melihat Gen yang tidak diampu.
- [ ] Admin → Sertifikat: kesiapan administratif, upload TTD/background aman, list berkas/versi; tombol tidak kehilangan fungsi.
- [ ] Siswa → Sertifikat: status Berlaku/Digantikan dan nomor, aksi Unduh PDF tepat milik siswa, kosong saat belum layak. Pra-Semester tidak menjadi syarat.
- [ ] Pimpinan → Monitoring: filter paket, progres TP sah, tampilkan kondisi 0/0 tanpa 100% semu, pagination dan tautan laporan individu hanya baca.
- [ ] Admin/Guru → Kelola Pengumuman, Admin → FAQ: daftar/card, sunting/arsip, pagination, akses yang sah. Pengumuman Guru hanya terlihat siswa Gen yang diampu.
- [ ] Semua halaman: cek fokus keyboard `Tab` pada tabel scroll, form dan tombol; Dark/Light serta target 360/390/820/1100/1440 piksel.

## D. Regresi integrasi akademik (Master 16)
Gunakan hanya data tes XAMPP, catat ID TP/paket percobaan tanpa identitas siswa nyata.
- [ ] E2E-006/017/018: Guru G1 boleh sunting TP Siap belum dipakai; TP yang sudah dipakai tetap terkunci termasuk media/soal/KKM.
- [ ] E2E-020/021: Siswa jalur Diagnostik gagal wajib satu Materi→Resume→HOTS→Sumatif; lulus boleh lewati tiga kegiatan tetapi Sumatif tetap wajib.
- [ ] E2E-026/028/032/034/038: perangkat dan fullscreen, timeout/jeda koneksi 600 detik, akses ulang Diagnostik/Sumatif bukan pembatalan Pra-Semester.
- [ ] E2E-039–044: simpan penilaian 0–100; koreksi langsung Guru G1 memperbarui nilai final dan audit tanpa kehilangan data historis.
- [ ] E2E-045–056: sertifikat otomatis ketika layak, memperbarui bila konten cetak berubah, QR/nomor/arsip lama tetap ada, tanpa menampilkan NISN pada QR publik.
- [ ] E2E-058/061/062/063/065: pembatasan pengumuman Gen, notifikasi privat, Pimpinan baca-saja, ekspor aman, perlindungan ROOT dan .env.
- [ ] Pra-Semester: bank ≥30 lintas BAB, jadwal utama individual, remedial satu kali, susulan bila ujian dibatalkan, bukan gate sertifikat.
- [ ] Admin/ Guru/ Siswa/ Pimpinan: satu siklus login→navigasi→aktivitas→logout tanpa kesalahan 403/404 yang tidak sah.

## E. Laporan hasil
Catat untuk tiap langkah: **PASS/FAIL/BLOCKED**, URL lokal, role, browser/viewport, kondisi Dark/Light, langkah reproduksi dan pesan galat. Cantumkan jumlah tes PHPUnit, bukti screenshot tanpa data privat dan waktu pengujian. **Jangan klaim XAMPP PASS tanpa hasil nyata.** Uji hosting MySQL/MariaDB dan backup/restore tetap pekerjaan terpisah.
