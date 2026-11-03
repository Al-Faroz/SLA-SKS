# Status keputusan Master v2.5 — 26 Oktober 2026

## Keputusan final
- CP → Semester → BAB → TP; satu CP operasional/mapel, beberapa elemen; BAB/TP 50+225, kode `MAPEL.KELAS.BAB.TP`, nomor BAB sambung 1–2/3–4/5–6.
- TP Esensial maupun NonEsensial wajib disiapkan Guru dengan tepat satu Materi/media. Aktivitas siswa, ujian dan nilai hanya wajib pada TP NonEsensial; Esensial tetap tercatat di ATP.
- Diagnostik ≥10 gate; Sumatif ≥30 wajib, bobot jalur gagal 20/20/60 atau lulus 100% Sumatif, KKM default Admin/khusus Guru, 1 remedial <KKM ambil nilai tertinggi, TP Selesai meski nilai di bawah KKM.
- Guru isi ATP langsung atau Excel ATP manual `.xlsx` tanpa macro dan tanpa tombol Proses; template resmi sudah ditambahkan ke branch fitur pada `docs/`; integrasi generator/impor sudah dikodekan pada branch fitur dan membutuhkan verifikasi manual melalui XAMPP sebelum dinyatakan selesai. Progres persemester hanya NonEsensial.
- Pra-Semester pada tanggal dan jam serentak Admin setelah semua NonEsensial selesai, bank gabungan BAB, pola Sumatif, nilai berdiri sendiri. Tidak ada nilai akhir semester. Export Nilai Guru dapat dilakukan dari satu skor TP.
- Database baru `sla_sks_v4` terpisah dari `sla_sks_v3`, sumber privat tak dikirim ke repo; semua versi/progres lama dipertahankan sebagai historis.

## Keputusan yang telah ditetapkan pada 3 November 2026
- **Tidak:** siswa **tidak wajib menuntaskan Pra-Semester** untuk mendapatkan sertifikat. Syarat sertifikat tetap seluruh TP NonEsensial selesai, nilai wajib TP final, serta identitas dan TTD siap.
- **Tidak:** nilai Pra-Semester **tidak dicetak** di sertifikat. Nilai mandiri ini hanya ditampilkan pada rekap/laporan/ekspor Pra-Semester yang terpisah.
- **Alasan:** Pra-Semester berfungsi sebagai checkpoint akademik sebelum Ujian Semester yang sesungguhnya. Penjelasan tujuan ini tidak menambah modul Ujian Semester atau Nilai Akhir Semester.

## Belum dikerjakan setelah tahap dokumentasi/DB
- Template Excel ATP v2.5 resmi telah tersedia pada branch fitur setelah #68; langkah yang tersisa adalah generator unduh kontekstual dan parser/impor BAB–TP.
- Refactor MVC/Service, UI, ujian Pra-Semester, nilai, ekspor, progres, sertifikat dan regresi. Rincian tahap di `RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md`.
- Pengujian MySQL/MariaDB secara eksekusi dan verifikasi XAMPP oleh pemilik.

- **UI ATP (27-10-2026):** Peta ATP hanya ringkasan dua kelompok tiga kartu semester; buka halaman semester untuk mengelola daftar BAB dalam kartu warna berbeda dan tabel TP; tambah BAB/TP via modal native. Data tetap berdasar G1 Gen–Mapel, status paket, dan batas nama 50/deskripsi 225. Tidak mengubah alur akademik.


## Keputusan final Pra-Semester — 3 November 2026
- Jadwal dan durasi **per Gen–Mapel–Semester**, tidak global. Admin menetapkan jendela boleh mulai dan durasi pengerjaan. Durasi individual dihitung **sejak siswa memulai**, jendela berakhir tidak memotong durasi siswa yang sudah berjalan.
- Guru G1 mengelola bank minimal 30 soal mencakup setiap BAB dan KKM tersendiri Pra-Semester (default Admin/khusus Guru). Admin menjadwalkan ujian utama, remedial tersendiri dan susulan.
- Siswa yang ujian Pra-Semester-nya dibatalkan **harus mendapat jadwal susulan Admin**, bukan akses ulang mandiri. Siswa tidak hadir dapat ditetapkan oleh Admin untuk susulan. Susulan boleh diulang bila dibatalkan lagi dengan audit. Pembatalan remedial → susulan untuk remedial yang sama; satu remedial sah setelah hasil normal sah <KKM.
- **Pembaruan keputusan 3 November 2026:** Pra-Semester **bukan gerbang sertifikat dan tidak dicetak di sertifikat**. Isu yang sebelumnya ditunda sudah diputuskan pemilik; keputusan ini berlaku untuk pengembangan berikutnya.

## Status implementasi A13-2 (3 November 2026)

Setelah Pusat Bantuan A13-1, dump SQL lokal `sla_sks_v4 (17).sql` dibuat 03-11-2026 16.02 di MariaDB 10.4.32 telah diberikan dan diaudit (68 tabel; `schema_version` akhir `V4-0011-SERTIFIKAT`). Tidak ada tabel `pengumuman`, `faq`, atau `riwayat_informasi`. A13-2 menambahkan patch additive `V4-0012_PENGUMUMAN_FAQ.sql` dan CRUD non-destruktif Pengumuman global serta FAQ Admin. Semua login hanya membaca konten Terbit; Admin dapat mengelola keduanya; Guru hanya Pengumuman yang dia buat. Tidak ada data siswa/akun nyata dalam patch/repository. **Impor patch dan pengujian XAMPP oleh pemilik belum dilakukan**, sehingga status belum FIX.

Tahap berikutnya: Notifikasi internal untuk peristiwa sah menurut Master 12/14/16, badge belum dibaca dan kontrol hanya penerima sendiri. Sebelum SQL berikutnya, minta **dump XAMPP baru yang dibuat setelah V4-0012 berhasil diimpor**, bukan memakai dump versi sebelum patch.
