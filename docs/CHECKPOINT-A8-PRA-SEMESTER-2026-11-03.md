# Checkpoint A8 — Pra-Semester (3 November 2026)

## Keputusan final
- Hanya Pra-Semester yang menggunakan istilah **pembatalan ujian**. Diagnostik/Sumatif mandiri memakai **akses ulang Guru** dan sudah PASS di XAMPP (laporan pengguna).
- Setiap **Gen–Mapel–Semester** memiliki jadwal dan durasi masing-masing. **Jendela Admin hanya menentukan kapan siswa boleh memulai**. Durasi individual dihitung dari saat siswa menekan Mulai hingga durasi aktif habis, tanpa terpotong ketika jendela mulai ditutup. Jeda keamanan berlaku seperti Master 07.
- Admin menangani **jadwal utama/remedial/susulan**. Siswa dibatalkan harus mendapat susulan Admin; siswa tidak hadir dapat dimasukkan susulan; pembatalan berulang dapat dijadwalkan lagi dengan audit. Guru mengelola bank soal/KKM, tidak memberi izin manual pengganti jadwal. Remedial hanya sekali setelah nilai awal sah <KKM, nilai terbaik.
- Masalah sertifikat **ditunda**. Tidak ada nilai akhir semester gabungan.

## Implementasi dalam branch ini
- Dokumen utama 02/03/05/06/07/08/10/11/12/14/16 dan PENDING-DECISIONS telah diselaraskan.
- `PraSemesterGuruService`: bank soal PG A–E tersendiri untuk setiap paket; soal terkait BAB, minimal 30 soal aktif mencakup semua BAB; KKM default Admin/khusus Guru; bank/KKM tidak berubah setelah dipakai siswa. `GuruPraSemester`, `app/Views/guru/pra_semester.php`, menu dan rute terkait.
- `PraSemesterJadwalService`: Admin menetapkan jadwal utama WIB dan durasi terpisah per paket; validasi benturan Gen–Semester, bank, KKM, status paket. Jadwal terkunci setelah dipakai siswa. `AdminPraSemester`, `app/Views/admin/pra_semester.php`, menu dan rute terkait.
- `PraSemesterSiswaService`: izin berbasis Gen dan penugasan paket, seluruh TP NonEsensial tuntas dengan bukti Sumatif sah, bank dan KKM lengkap, jam server, browser. KKM efektif dipin ke attempt pertama agar seluruh peserta sama.
- `MesinPraSemesterSiswaService`: ujian **utama** 30+ soal acak dengan setiap BAB terwakili, pengacakan opsi A–E, jawaban tersimpan server, timer aktif per peserta, jeda fullscreen/koneksi, pembatalan tanpa skor, finalisasi dan skor sah atomik, tidak membuat ulang attempt selesai/batal.
- `SiswaPraSemester`, `app/Views/siswa/pra_semester_ujian.php`, tautan dari daftar TP, adaptasi JS ujian agar Pra-Semester tidak memakai notifikasi akses ulang mandiri.
- `tests/unit/PraSemesterImplementasiTest.php`, `tests/unit/PraSemesterDatabaseTest.php`, workflow QA A8 dan regresi QA S06A, audit baca-saja `database/sql/qa/A8_PRA_SEMESTER_READONLY.sql`.

## Batas yang BELUM selesai
- **Jadwal remedial dan susulan per peserta** belum dapat diaktifkan pada skema V4-0001 (hanya punya satu jendela utama). Membutuhkan dump XAMPP **setelah** impor patch data simulasi terakhir sebelum SQL update baru dibuat, sesuai AGENTS. **Jangan** mengubah data lokal dengan SQL asumtif. Bank Word Pra-Semester dan ekspor nilai Pra-Semester juga belum diselesaikan.
- Halaman ujian hanya menjalankan kesempatan **utama**; setelah batal atau skor awal <KKM, sistem tidak membuka attempt lagi sampai jadwal susulan/remedial benar-benar diimplementasikan. Tidak ada tombol palsu.
- Pengujian CI dan XAMPP masih harus dibuktikan; kode branch belum boleh dinyatakan FIX akhir atau langsung digabung ke main.

## Langkah QA manual
1. Guru G1 buka `/guru/pra-semester`, pilih paket aktif, tambah soal BAB (minimal 30 dengan semua BAB terwakili), tetapkan KKM.
2. Admin buka `/admin/pra-semester`, tetapkan jendela boleh mulai dan durasi per mapel. Uji menolak bank belum siap, tabrakan dalam satu Gen/semester, dan waktu masa lalu.
3. Siswa yang seluruh TP NonEsensial selesai buka `/siswa/semester/{paketId}` → Pra-Semester. Uji jendela, fullscreen dan waktu individual. Uji 1 skor 0.00 sah dan pembatalan tanpa skor.
4. Jalankan QA otomatis dan query baca-saja di atas pada XAMPP; catat kesalahan untuk iterasi berikutnya. Jangan kirim data pribadi/sandi ke GitHub.


## Kelanjutan A8-4: kontrak kesempatan dan penilaian (3 November 2026)
- Ditambahkan `PenilaianPraSemesterService` (pure, tanpa SQL): memastikan satu ujian normal sah, satu remedial sah hanya setelah nilai normal sah <KKM; skor 0.00 sah; ujian batal tidak bernilai; skor terbaik final; status nilai sementara saat remedial belum selesai; histori salah ditolak.
- Ditambahkan `AturanJadwalPraSemesterService` (pure): fase awal, tidak hadir ditunjuk Admin, susulan normal bagi ujian dibatalkan, jadwal remedial setelah skor awal sah <KKM, susulan remedial jika pembatalan saat remedial, pembatalan berulang, dan percobaan aktif dapat dilanjutkan walau jendela mulai tutup.
- Tes regresi: `tests/unit/PraSemesterRemedialSusulanTest.php`, dimasukkan dalam QA A8 dan S06A untuk PHP 8.2.
- **Batas mutlak:** dump terbaru ditemukan di Library bernama `sla_sks_v4 (12).sql`, dibuat 2 Nov 2026 pukul 13.29 waktu dump, **lebih awal** dari patch simulasi/percobaan yang dinyatakan PASS. Tidak cukup untuk mengaudit SQL berikutnya. Belum dibuat SQL, tidak ada perubahan/DELETE data siswa, tidak ada penjadwalan remedial/susulan di database/UI yang diklaim aktif.
- Setelah pengguna mengunggah ekspor `sla_sks_v4` terbaru **pasca patch terakhir**, verifikasi DDL/indeks/FK, `schema_version`, count agregat tabel relevan, lalu buat patch SQL additive bernomor, adapter jadwal peserta, UI Admin dan mesin susulan/remedial. Pastikan tetap kompatibel MySQL/MariaDB tanpa CI4 Migration/Seeder.

## A8-5 — Implementasi jadwal operasional dan UI tabel (3 November 2026)
- Pengguna mengunggah dump terbaru `sla_sks_v4 (13).sql`; audit header dan `schema_version` menunjukkan MariaDB 10.4.32, V4-0008 terakhir, tidak terdapat sesi remedial/susulan. Dump privat tetap di luar repository.
- Penambahan SQL additive **V4-0009** untuk jadwal khusus, peserta susulan dan relasi percobaan. Berkas audit baca-saja disertakan. SQL **belum diimpor otomatis**; pengguna perlu backup dan impor satu kali di XAMPP.
- Filter dan tabel Guru/Admin ditata ulang pada Service + Controller + View + CSS. Guru filter Gen; Admin filter Gen, Mapel, Semester, Status. Tidak menyentuh kebijakan UI global lain.
- Service sesi Admin/roster dan mesin siswa terhubung. Form tambahan dan daftar peserta nyata; jadwal tanpa SQL V4-0009 tetap nonaktif (pesan impor), sedangkan daftar jadwal utama/filter tetap dapat dibuka.
- Belum menangani impor Word Pra-Semester dan ekspor nilai Pra-Semester ke XLSX; fokus susulan/remedial dan UI tabel terlebih dahulu.
- Uji XAMPP menunggu pull dan laporan pengguna. Jangan anggap PASS browser/SQL hanya dari CI.
