# Checkpoint Penutupan UI/UX SLA-SKS — 27 Oktober 2026

> **Status: baseline visual DITERIMA / lingkup redesain DITUTUP atas keputusan pemilik.** Catatan ini mengikat transisi dari pekerjaan UI Emerald Glass ke implementasi fitur aplikasi **Master Document v2.5**. Penerimaan tampilan **tidak sama dengan** seluruh uji otomatis, regresi browser, maupun aplikasi v2.5 telah selesai, dan **bukan perintah merge ke `main`**. Dokumen ini melengkapi, bukan mengganti, 16 Master Document.

## 1. Titik akhir pengerjaan UI
- Desain **Emerald Glass Academic UI**: Dark Mode default forest–emerald, Light opsional, glassmorphism ringan pada shell/kartu; form, tabel, editor dan pembaca materi lebih solid demi keterbacaan.
- Sidebar desktop expanded/collapsed, drawer HP, keyboard focus, Escape/backdrop; ikon subset Lucide SVG lokal tanpa CDN. Enam gradasi berbeda untuk semester 1–6.
- Tombol akhir: **Primary Indigo–Violet**, Secondary soft glass, Danger tinted merah. Percobaan **Juicy Orange / Stripe / Bloody Mary** telah dibatalkan dan berkasnya dipulihkan. Jangan membuka ulang diskusi palet tanpa permintaan pemilik.
- Lapisan visual dikembangkan bertahap untuk shell/login, Admin, G03 Guru (Peta ATP, Semester, BAB/TP, modal), komponen tabel/form/status/pagination, Siswa (katalog TP, materi/progres/Resume/HOTS), Profil, Ganti Sandi, dan halaman status publik.
- Layout Profil staf final: **Foto Profil di atas full-width**, lalu **Identitas Akun** dan **Kontak Pribadi** berdampingan desktop/bertumpuk mobile. View khusus Pimpinan dan beberapa modul akhir **belum diimplementasikan**; ini pekerjaan fitur terpisah, bukan alasan mengulang redesign.
- Pertahankan `docs/12-UI-UX-DAN-HALAMAN.md`, `docs/13-DESIGN-SYSTEM-CSS.md`, `docs/assets/galeri-komponen.html`, `public/assets/css/emerald-glass.css`, `public/assets/css/app.css`, `public/assets/js/theme-init.js`, `public/assets/js/app.js` dan aset SVG lokal.
- **Hentikan penambahan ornamen/eksperimen warna.** Gunakan komponen UI yang ada untuk fitur baru. Perubahan UI dibolehkan hanya untuk kebutuhan fitur nyata, aksesibilitas dan regresi.

## 2. Keadaan GitHub yang terverifikasi saat penutupan
- Repository: `https://github.com/Al-Faroz/SLA-SKS.git`.
- **PR #65** [Master v2.5 dan skema](https://github.com/Al-Faroz/SLA-SKS/pull/65) — **Open, Ready for Review**, base `main`.
- **PR #66** [Fondasi BAB Guru](https://github.com/Al-Faroz/SLA-SKS/pull/66) — **Open, Draft**, base branch PR #65.
- **PR #67** [TP dalam BAB](https://github.com/Al-Faroz/SLA-SKS/pull/67) — **Open, Draft**, base branch PR #66.
- **PR #68** [Emerald Glass UI](https://github.com/Al-Faroz/SLA-SKS/pull/68) — **Open, Draft**, base `feature/v25-tp-dalam-bab-20261027`. Branch `feature/ui-emerald-glass-fondasi-20261027`; sebelum checkpoint ini HEAD `ce074164593e7e034c75db6b30c42858335fb177`.
- Perbandingan PR #68 dengan branch basis PR #67 pada penutupan menunjukkan perubahan 11 berkas UI/dokumentasi: layout panel/publik, Profil, CSS, JS, aset Lucide, galeri, Master UI dan lisensi; tidak ada berkas SQL, Controller atau Service.
- **PR masih bertingkat**. Review/merge (jika disetujui setelah QA) harus mempertahankan ketergantungan **#65 → #66 → #67 → #68**. Tidak menganggap `main` sudah mengandung semua perubahan. Jangan merge sendiri atas nama penutupan UI.

## 3. Kriteria penerimaan dan batas bukti
- Pemilik telah menyatakan FIX bertahap untuk responsive/drawer, Light Mode/Admin–Guru, lalu UI Siswa; penutupan visual global diterima pada 27-10-2026.
- Pemeriksaan statis di sesi implementasi terakhir: tahap UI Siswa **19/19 PASS**, tahap halaman bersama **22/22 PASS**. Ini pemeriksaan struktur sumber, bukan E2E browser.
- Pengujian XAMPP dari checkpoint G03 terdahulu: **3/3 `php -l` PASS** pada berkas yang diuji saat itu; PHPUnit terfilter **34 tests, 53 assertions PASS**; hasil historis dan **tidak mewakili seluruh revisi UI/fitur terbaru**.
- Belum ada laporan pengujian otomatis penuh terbaru, PHP lint semua berkas yang diubah, regresi E2E lintas peran Dark/Light, atau QA browser seluruh halaman. Status ini perlu dituntaskan sebagai bagian review PR sebelum merge.
- Tidak ada perubahan `.env`, root `index.php`/`.htaccess`, database atau data siswa pada pekerjaan penutupan ini.

## 4. Posisi implementasi fitur v2.5 dan batas keputusan
- Rujukan: `README.md`, `AGENTS.md`, `docs/README.md`, `docs/PENDING-DECISIONS.md`, Master terkait 01–16, terutama 04, 05, 06, 07, 08, 10, 12, 13, 16, dan `docs/RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md`.
- Aturan **v2.5 final**: Mapel → satu CP operasional → Semester → BAB → TP. TP Esensial tanpa Materi/ujian/nilai LMS; NonEsensial tepat satu Materi. Diagnostik ≥10 soal **hanya gate**; jika lulus boleh lewati Materi/Resume/HOTS tetapi **Sumatif ≥30 tetap wajib**. Jalur tidak lulus: Materi → Resume final → HOTS final → Sumatif. Nilai 20/20/60 atau 100% Sumatif; satu remedial jika di bawah KKM; progres semester hanya TP NonEsensial; Pra-Semester terjadwal dan tidak ada nilai akhir semester. **Jangan pakai aturan v2.4 historis**.
- Fondasi nyata G03 telah dibuat dan diuji di localhost: enam kartu semester dua kelompok, kartu BAB, tabel TP, tambah BAB/TP via native dialog, otorisasi G1 Gen–Mapel. Pekerjaan akademik berikutnya masih memerlukan audit kode terbaru, implementasi Service/Controller/UI terkait, dan uji yang benar-benar dijalankan.
- **Perbedaan DB aktif vs kontrak:** checkpoint sebelumnya melaporkan database privat `sla_sks_v4` R2 masih memakai `VARCHAR(35)` untuk nama BAB/TP, sementara keputusan terbaru batas nama **50 karakter** dan deskripsi **225**. Periksa dump SQL lokal **terbaru yang diunggah setelah patch terakhir** sebelum pekerjaan SQL; jangan membuat patch berasumsi data lama, jangan impor ulang R2, reset, atau menimpa data nyata. SQL pembaruan bernomor, additive, MySQL/MariaDB compatible, bukan Migration/Seeder.
- **Template ATP manual v2.5** (`.xlsx`, **tanpa macro/VBA/`.xlsm`**) memiliki kontrak 10 sheet (MULAI DI SINI, RINGKASAN, REFERENSI CP, SEMESTER 1–6, CONTOH MAT VII). **Pemeriksaan direktori `docs/` pada branch PR #68 saat penutupan tidak menemukan berkas template ATP manual tersebut; template Word `TEMPLATE_SOAL_SLA-SKS.docx` tersedia.** Jangan mengklaim generator/impor Excel v2.5 sudah lengkap.
- Perbaikan PDF materi lama telah dinyatakan FIX; batasi 15 MB/40 halaman; Model B checkpoint aktivitas media 4 menit dan ketika selesai/pindah tahap. Jangan mengubah atau merusak zoom, fullscreen, unduh pasca-gate, dan status server tanpa kebutuhan bisnis/test.
- Satu hal bisnis **belum diputuskan** dalam `docs/PENDING-DECISIONS.md`: apakah Pra-Semester menjadi prasyarat sertifikat dan apakah nilainya dicetak. **Jangan memutuskan diam-diam.**

## 5. Instruksi percakapan berikutnya — kembali ke implementasi fitur
1. Baca checkpoint ini dan dokumen resmi, cek status PR/branch terbaru; **jangan mengasumsikan branch `main` sudah mutakhir**.
2. Cocokkan daftar implementasi aktual dengan `docs/RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md`. Tahap workbook ATP resmi dan konsistensi BAB–TP/DB adalah dependensi awal sebelum impor ATP dan editor/kesiapan v2.5. Pilih **satu tugas terdekat yang belum selesai**, hindari mengulang fitur yang sudah FIX.
3. Jika tahap berikutnya memerlukan dump SQL, minta dump terbaru dan audit; jika tidak perlu DB, kerjakan bagian aman lebih dahulu (contoh: verifikasi workbook di repo, kontrak parser atau tes unit) tanpa mengubah skema.
4. Gunakan branch kerja terpisah serta PR yang berbasis branch yang benar; lakukan edit nyata di GitHub, tes/lint yang tersedia, dan laporkan berkas, SQL, hasil tes yang benar-benar dijalankan, serta cara pengguna menarik perubahan ke XAMPP.
5. Fokus **membangun fungsi aplikasi** sampai siap; tidak ada redesign global baru tanpa permintaan eksplisit. Jangan membangun fitur Pimpinan/sertifikat lebih awal dari dependensi dokumen.
