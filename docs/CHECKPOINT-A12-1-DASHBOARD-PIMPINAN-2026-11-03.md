# Checkpoint A12-1 — Dashboard Akademik Pimpinan

Tanggal: 3 November 2026
Branch: `feature/a12-1-dashboard-pimpinan-20261103`
PR: #115 Draft, **stacked** pada `fix/a11-3-layout-sertifikat-a4-20261103`.

## Tujuan dan batas

Tahap 12 dimulai secara bertahap. A12-1 mengganti beranda generik Pimpinan (`/pimpinan`) dengan dashboard baca-saja. Dashboard Admin/Guru/Siswa, notifikasi internal, pengumuman, FAQ, dan E2E keseluruhan menjadi PR A12 berikutnya. **Tidak menambah angka dummy pada UI aplikasi**; contoh sintetis hanya digunakan dalam galeri dan PHPUnit.

## Kontrak akademik

- **Siswa unik**: jumlah siswa pada Gen yang tercakup oleh pilihan paket aktif; boleh mencakup siswa yang belum masuk semester tertentu, dengan label jelas.
- **Target pembelajaran**: setiap siswa yang semester mulai-nya tidak melewati semester paket dikalikan jumlah TP NonEsensial nonarsip pada paket itu. Jika tidak ada pasangan wajib, cakupan `null` (tampilkan `Belum ada target`), bukan 0 atau 100%.
- **TP selesai sah**: `progres_tp.status='tuntas'` dan minimal satu percobaan Sumatif `selesai_sah`; duplikat attempt normal/remedial dihitung hanya satu per siswa×TP.
- **Nilai Final**: hanya pasangan TP selesai sah yang mempunyai `status_nilai='final'` dan `nilai_tp IS NOT NULL`. Nilai numerik 0 sah; status Sementara atau NULL tidak dihitung Final.
- **Sertifikat berlaku**: hanya pointer aktif `sertifikat_aktif` dengan versi `sertifikat.status='berlaku'`. Versi `digantikan` tidak dihitung.
- **Antrean Penilaian**: `resume_materi` dan `jawaban_hots_materi` final dengan `nilai IS NULL`, pada TP NonEsensial paket aktif dan siswa yang tidak melewati semester. Draf dan nilai 0 tidak masuk antrean.
- **Penugasan Guru**: pasangan Gen–Mapel unik yang memiliki `penugasan_guru_gen.guru_id`, bukan jumlah paket semester atau jumlah semua akun staf.
- **Pra-Semester**: hanya status mandiri draf/siap/ditutup/belum tersedia; tidak dihitung sebagai nilai TP, tidak menjadi gerbang sertifikat, tidak ada nilai akhir semester.

## Kode dan akses

- Baru `app/Services/DashboardPimpinanService.php`: agregasi read-only dalam beberapa query, cek role Pimpinan aktif dan paket dari `LaporanAkademikService::paketPimpinan`.
- Baru `app/Controllers/PimpinanDashboard.php`: validasi filter Gen/Mapel/Semester/halaman, response `Cache-Control: private, no-store`, kegagalan 403/500 dan flash error.
- Baru `app/Views/pimpinan/dashboard.php`: kartu KPI, progres native, filter dan reset, card operasional Guru/antrean, tabel paket paginasi 12 halaman, tautan ke `/pimpinan/laporan?paket=...`.
- Diubah `app/Config/Routes.php` (GET `/pimpinan`), `public/assets/css/app.css` (Dark/Light, HP), dan `docs/assets/galeri-komponen.html` (contoh visual sintetis).
- Batas peran diverifikasi di filter route **dan** Service. Tidak ada POST, ekspor jawaban atau NISN pada dashboard. Monitoring individual tetap memakai izin A10-1.
- Tidak mengubah `.env`, autentikasi, mesin ujian, nilai, laporan, PDF sertifikat, atau riwayat.

## Audit database sebelum kode

Sumber: dump privat `sla_sks_v4 (16).sql` yang benar-benar diunggah pemilik pada 3 November 2026, phpMyAdmin 5.2.1, dibuat 12.05 WIB, MariaDB 10.4.32, `sla_sks_v4`, `schema_version` mencatat `V4-0011-SERTIFIKAT`. Kontrak DDL yang digunakan diverifikasi: `siswa`, `ref_angkatan`, `paket_semester`, `penugasan_paket`, `penugasan_guru_gen`, `tp`, `progres_tp`, `percobaan_ujian`, `nilai_tp`, `resume_materi`, `jawaban_hots_materi`, `ujian_pra_semester`, `sertifikat`, `sertifikat_aktif`.

**SQL patch baru: tidak ada.** Dump privat, nama/NISN siswa, sandi, percobaan dan jawaban asli tidak pernah diunggah ke GitHub. Data berubah setelah dump (mis. sertifikat demo yang diterbitkan kemudian) selalu dibaca langsung saat dashboard diakses; angka checkpoint bukan snapshot indikator lokal yang diklaim tetap sama.

## Pengujian otomatis

GitHub Actions `QA A12-1 Dashboard Pimpinan` run 41637788372: **SUCCESS**; PHP lint 5 berkas PASS; `DashboardPimpinanDatabaseTest.php` **6 tes/29 assertions** PASS; regresi `LaporanAkademikDatabaseTest.php` **6 tes/34 assertions** PASS. SQLite sintetis mencakup dua Gen, semester override, Sumatif duplikat, nilai Final 0, nilai Sementara, arsip TP, paket draf/tanpa TP, penugasan Guru dan dua jenis tugas final menunggu nilai, pagination, filter, dan larangan akses peran selain Pimpinan. Tidak ada uji browser XAMPP oleh AI.

## Langkah uji lokal

1. Pull branch A12-1 sesudah A11-3; **tidak impor SQL**.
2. Login Pimpinan, buka `http://localhost/sla-sks/index.php/pimpinan`. Periksa seluruh KPI berasal dari data sebenarnya, termasuk satu sertifikat demo yang mungkin sudah terbit.
3. Coba filter Gen, Mapel dan Semester, tombol Reset, dan pagination bila ada banyak paket.
4. Klik `Lihat Monitoring`: paket yang terpilih harus sama dan daftar siswa sesuai.
5. Periksa HP/desktop, Dark/Light dan Mode Dokumentasi. Uji paket kosong/tanpa TP jika tersedia.
6. Login sebagai Guru/Admin/Siswa: tidak boleh mengakses beranda Pimpinan. Periksa bahwa tidak ada perubahan otomatis pada nilai/TP/sertifikat akibat membuka dashboard.

Status A12-1 menunggu uji visual dan angka XAMPP pemilik sebelum dinyatakan FIX. Tahap lanjut setelah FIX: Dashboard Guru, Siswa, Admin dan notifikasi/pengumuman/FAQ bertahap mengikuti Master 12 serta QA E2E.

## Verifikasi pengguna di XAMPP (3 November 2026)

Pemilik mengirim tangkapan layar `/pimpinan` setelah pull dan menyatakan untuk melanjutkan apabila tampilan sesuai. Pemeriksaan visual: filter Gen–Mapel–Semester, KPI, progress, penugasan Guru, antrean, tabel dan tombol Monitoring terpasang rapi; **A12-1 dinyatakan FIX untuk UI dan agregasi yang terlihat**, tanpa mengklaim audit ulang dump SQL saat itu. Angka yang tampak konsisten: 27+2=29 siswa unik per dua Gen; 6+2=8 TP sah dari 54+4=58 target (13,8%); Nilai Final 5+2=7; Sertifikat 0+1=1; antrean 4+0=4. Penugasan 1/2 konsisten dengan satu Gen DEMO belum dipasangi Guru. A12-2 dilanjutkan pada branch terpisah; tidak mengubah dashboard Pimpinan yang dinyatakan FIX.
