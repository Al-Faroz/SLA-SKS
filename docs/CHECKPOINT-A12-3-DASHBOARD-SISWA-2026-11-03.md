# Checkpoint A12-3 — Dashboard Siswa berbasis gerbang akademik

Tanggal: **3 November 2026**. Branch: `feature/a12-3-dashboard-siswa-20261103`.
PR: **#117 Draft**, berbasis `feature/a12-2-dashboard-guru-20261103`. Belum merge main.

## Penerimaan sebelumnya

Dashboard Pimpinan A12-1 dan Dashboard Guru A12-2 telah diperiksa pemilik menggunakan screenshot localhost, lalu diminta melanjutkan apabila sesuai. A12-2 terlihat konsisten: 1 Paket aktif, 6 TP (1 Siap + 5 Draf), 5 TP Materi perlu diperiksa, 4 jawaban final Resume/HOTS menunggu nilai (2+2), 0 siswa perlu bantuan dan 0 ujian mandiri berjalan. **A12-2 FIX berdasarkan UI serta regresi awal**; screenshot tidak membuktikan setiap breakpoint dan bukan dump SQL terbaru.

## Implementasi A12-3

- `GET /siswa` kini membaca `DashboardSiswaService`, bukan layar akun generik. Role filter `auth:siswa` dan check role aktif di Service; respons `Cache-Control: private, no-store`.
- Sumber paket dan tombol belajar: `KatalogPembelajaranSiswaService::daftarMapel`, `daftarSemester`, dan `daftarTp`. Hanya paket aktif/ditugaskan yang terbuka menurut Gen siswa, semester mulai, dan ketuntasan semester sebelumnya. **Tidak menerima ID siswa/paket dari browser**. Tombol lanjut TP tidak terbentuk bila `akses.buka` belum diset oleh katalog.
- Ringkasan siswa: mapel ditugaskan, jumlah paket semester terbuka, target TP NonEsensial, TP selesai sah, nilai Final yang sah (0 tetap valid), TP perlu bantuan, sertifikat aktif berlaku.
- Bukti TP tuntas: progres `tuntas` disertai minimal satu Sumatif `selesai_sah`; Diagnostik saja atau Sumatif masih berjalan bukan TP selesai. Nilai Final hanya jika status final dan bukan NULL pada TP tuntas sah. TP Esensial, TP arsip, serta semester dilewati tidak masuk target.
- Daftar per paket menggunakan segmen kemajuan sampai 12 TP; lebih dari 12 memakai `progress` native. Rasio 0/0 tidak ditampilkan sebagai 100%; kosong berarti belum ada target. Status perlu bantuan dibedakan dari kelulusan, dan tidak menciptakan izin akses ulang otomatis.
- Hasil Pra-Semester tetap checkpoint mandiri di halaman Laporan Akademik; bukan nilai TP/sertifikat dan tidak dicetak pada sertifikat. Dokumen Sertifikat ditautkan dari beranda tanpa membuat nomor/QR baru. **Tidak ada Nilai Akhir Semester.**
- Lencana resmi S11 belum diterbitkan melalui dashboard ini karena sistem penerbitan lencana belum tersedia; tidak menciptakan poin atau peringkat baru. Hanya pencapaian hitungan akademik valid.

## Berkas dan QA

- Baru `app/Services/DashboardSiswaService.php` (otorisasi katalog + agregasi progres/Final/sertifikat).
- Baru `app/Controllers/SiswaDashboard.php` (GET baca-saja, penanganan galat).
- Baru `app/Views/siswa/dashboard.php` (hero dan tombol lanjut, KPI, progres bersegmen, laporan/sertifikat).
- Diubah `app/Config/Routes.php` (GET `/siswa`), `public/assets/css/app.css` (Emerald Glass responsif).
- Baru `tests/unit/DashboardSiswaDatabaseTest.php` dan `.github/workflows/qa-a12-3-dashboard-siswa.yml` (SQLite anonim, regresi Katalog dan Laporan Akademik).
- Master 03/06/12/13/16, indeks Master dan galeri komponen diperbarui.

### Pengujian awal sebelum sinkronisasi dokumentasi

Workflow **QA A12-3 Dashboard Siswa** run **41644850144** PASS: PHP lint 5 berkas PASS, **7 tes/37 assertions** Dashboard Siswa, **10 tes/38 assertions** Katalog, **6 tes/34 assertions** Laporan Akademik. Fixture tes diperbaiki untuk menyamakan kolom minimal progres_tp yang sebenarnya; periksa ulang CI commit terakhir setelah revisi/dokumentasi.

## Database dan privasi

Tidak ada patch SQL, migrasi, seeder atau mutasi database. Sumber tabel telah ada sejak fondasi pembelajaran dan patch sertifikat V4-0011 pada dump privat sebelumnya. **Tidak menggunakan nama/NISN siswa nyata untuk fixture dan tidak mengunggah dump SQL**. Belum melakukan pemeriksaan ulang database berjalan sesudah perubahan data demo lokal; klaim angka final hanya berdasarkan query nanti saat browser diakses.

## Langkah uji manual XAMPP

1. Pull branch A12-3 dan buka `http://localhost/sla-sks/index.php/siswa` setelah login Siswa simulasi.
2. Pilih siswa yang belum mulai, sudah menuntaskan satu TP, mendapat nilai Sementara, dan yang telah menuntaskan semua TP; periksa tindakan lanjut hanya mengarah ke TP/semester yang boleh dibuka.
3. Periksa nilai Final 0 tetap terhitung; Sumatif belum sah tidak dianggap TP selesai. Semester dilewati tampil **tidak sebagai paket terbuka**.
4. Periksa `Siswa Simulasi 08` jika masih dalam data uji dan status butuh akses ulang; dashboard harus menampilkan bantuan bila progres sesuai, bukan membuat ujian baru.
5. Periksa siswa DEMO sertifikat. Sertifikat yang sudah terbit terlihat sebagai satu versi aktif tanpa mencetak/mengubah PDF.
6. Buka menu Laporan Akademik untuk nilai Pra-Semester terpisah dan Sertifikat Saya untuk unduhan historis; dashboard tidak menampilkan NISN, QR atau token ujian.
7. Uji Dark/Light, Mode Dokumentasi dan HP 360/390px. Pastikan segmen progres, teks dan tombol rapi.
8. **Tidak perlu impor SQL** atau membuat nilai/percobaan palsu hanya untuk mengisi dashboard.

Status: implementasi dan QA sintetis awal PASS, **menunggu uji angka/tampilan XAMPP** sebelum FIX. Berikutnya A12-4 Dashboard Admin/QA indikator sesuai Master.

## Koreksi UI A12-3 dari tangkapan layar XAMPP (3 November 2026)

Pada screenshot Siswa Simulasi 05 beranda berhasil ditampilkan, tanpa error pada struktur, KPI maupun progress: satu mapel/semester terbuka, target 2 TP NonEsensial, 0 TP selesai, 0 nilai Final, bantuan 0, sertifikat 0. Tidak ada bukti visual yang cukup untuk menganggap bantuan seharusnya 1 hanya karena nama akun simulasi.

Perbaikan pada branch **`fix/a12-3-ui-dashboard-siswa-20261103`** (Draft PR #118 berlapis di atas A12-3):
- Satu kartu Semester pada desktop memenuhi lebar kontainer dan memakai layout dua bidang: mapel/status di kiri, progres/tombol di kanan. HP kembali satu kolom; lebih dari satu paket tetap grid.
- Hero dan kartu menunjukkan **kode dan judul TP yang benar-benar dapat dibuka**, diambil dari Katalog resmi dengan `akses.buka=true` (tidak boleh mengungkap TP Draf/terkunci). Jika tidak ada TP berizin, tautan tetap menuju Semester.
- Kontras segmen diperjelas tanpa mengganti angka nilai, aturan Sumatif, peran, gerbang semester atau syarat sertifikat. Tidak ada perubahan SQL.
- Tambahan tes untuk judul TP berizin, semester dilewati dan TP status Draf yang **tidak boleh tampil sebagai tujuan belajar**.

A12-3 dinilai **berfungsi pada screenshot tetapi masih memerlukan QA visual ulang setelah PR #118**. Jangan menganggap perubahan ini memperbaiki status ujian siswa simulasi tanpa audit histori progres tersendiri.

### Hasil QA otomatis revisi UI

GitHub Actions `QA A12-3 Dashboard Siswa`, run **41647071674**, hasil **SUCCESS**: PHP lint seluruh berkas terkait PASS, `DashboardSiswaDatabaseTest.php` **8 tes/47 assertions**, regresi Katalog **10 tes/38 assertions**, regresi Laporan Akademik **6 tes/34 assertions**. Tes baru memverifikasi kode/judul TP Siap berizin muncul pada hero/kartu, sedangkan TP yang masih Draf tidak ditampilkan dan tombol tetap menuju daftar Semester. SQL tidak diubah. Status: **tes otomatis PASS, pemeriksaan visual browser hasil perubahan pada XAMPP masih diperlukan**.
