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

### A8-6 — Pemisahan halaman list/detail (permintaan lanjutan 3 November 2026)
- Pengguna telah mengonfirmasi SQL V4-0009 berhasil dijalankan di localhost. Tidak ada SQL baru untuk revisi ini.
- `app/Views/guru/pra_semester.php` hanya daftar dan filter; `app/Views/guru/pra_semester_detail.php` khusus bank soal, KKM, form.
- `app/Views/admin/pra_semester.php` hanya daftar dan filter; `app/Views/admin/pra_semester_detail.php` khusus jadwal utama/tambahan dan daftar peserta.
- Controller memilih View berdasarkan ada/tidaknya paketId, URL lama tetap. Setelah jadwal utama disimpan, Admin tetap pada detail untuk menerima pesan sukses.
- Butuh uji manual localhost untuk breadcrumb, filter dan form; jangan anggap PASS sebelum dilaporkan.

## A8-7 — Template, Pratinjau dan Impor Word Pra-Semester (3 November 2026)
- Branch: `feature/v25-a8-word-bank-pra-semester-20261103`, Draft PR #103 bertumpuk di atas PR #102. **Jangan merge langsung main** sebelum uji.
- Existing `TemplateWordSoalService`, `PratinjauWordSoalService`, `RenderWordSoalService` dipakai ulang. Template khusus Pra-Semester 30–200 PG per paket, kolom `BAB n` pada baris soal, kolom kunci ✓ pada baris pilihan, seluruh BAB semester harus terwakili. HMAC template tetap digunakan.
- Layanan baru `PraSemesterWordService`: file di `writable/private/pra_semester_word/`, staging 2 jam, hash berkas dan sidik jari BAB/bank, konfirmasi transaksional append-only, impor identik tidak menduplikasi, bank terkunci setelah attempt siswa.
- Halaman `guru/pra-semester/{paketId}` menyediakan unduh, unggah, riwayat; review per nomor berada di `.../word/review/{id}`; tidak ada perubahan halaman daftar/filter. Guru tetap bisa mengedit manual, dan edit manual membersihkan rich HTML lama.
- Mesin siswa menggunakan snapshot HTML/opsi teracak; proxy gambar di `siswa/pra-semester/{paketId}/{token}/media/{wordId}/{rid}` memverifikasi kepemilikan token dan sumber Word dalam snapshot.
- SQL **baru** `database/sql/updates/V4-0010_BANK_WORD_PRA_SEMESTER.sql` untuk staging, plus `database/sql/qa/V4-0010_BANK_WORD_PRA_READONLY.sql`. User sebelumnya mengonfirmasi V4-0009 berhasil diimpor. **V4-0010 belum diimpor pada localhost**. Tidak memakai Migration/Seeder, tidak menyertakan dump privat.
- QA: tes unit `PraSemesterWordTest.php`, integrasi SQLite pada `PraSemesterDatabaseTest.php`, regresi Word TP dan uji SQL MariaDB sintetis via workflow. Uji browser/XAMPP masih wajib untuk dianggap FIX.
- Belum dikerjakan pada tahap ini: ekspor nilai Pra-Semester; sertifikat tetap ditunda.

### Bukti QA A8-7 Word — 3 November 2026
- GitHub Actions **QA S06A Fondasi Mesin Ujian Ketat**, commit `bf488577a40380db8d7870565956225045839176`, run `41580942078`: **SUCCESS** setelah perbaikan direktori `writable/private` pada environment CI (tanpa memodifikasi layanan kunci Word produksi).
- PHPUnit: aturan waktu 11 tes/38 assertions; acak soal 7/108; kontrak Pra-Semester 6/169; SQLite 12/94; **PraSemesterWordTest 8/1.238**; generator Word lama 2/18; review Word lama 3/751; remedial/susulan 16/79. Total 65 tes pada workflow ini, dan kedua DDL MariaDB sintetis V4-0009 serta V4-0010 PASS.
- QA lain yang sudah terkonfirmasi PASS pada commit sama: `QA Bank Soal Terpadu v2.5`, `QA S06B Diagnostik End to End`, dan `QA Audit Kontrak Database`.
- **Tetap belum PASS manual XAMPP:** Guru perlu pull branch, backup dan impor hanya V4-0010 (V4-0009 sudah terpasang), mengunduh dan mengisi template 30 butir untuk BAB semester, review sebelum konfirmasi, kemudian Admin menjadwalkan dan siswa menguji tampilan Word.
