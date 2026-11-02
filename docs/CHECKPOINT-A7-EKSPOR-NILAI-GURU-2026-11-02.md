# CHECKPOINT A7 — Ekspor Nilai TP Guru XLSX

**Tanggal:** 2 November 2026  
**Branch:** \`feature/v25-a7-export-nilai-guru-xlsx-20261102\` (berbasis A6, PR #96).  
**Status:** kode untuk review; QA GitHub Actions dan pengujian XAMPP harus dilaporkan sebelum FIX/merge main.

## Acuan
Master v2.5 §03, 08 (ekspor semua siswa meski baru ada satu nilai), 12 (G15, filter Gen–Mapel/Semester dan tombol unduh), 14 (formula injection; privasi), 16 (E2E-063). Tidak ada nilai akhir semester.

## Fitur
- Guru **Rekap Nilai TP**: dua aksi unduh nyata, **Excel Semester aktif** dan **Excel Semua Semester 1–6**.
- Endpoint GET \`/guru/rekap-nilai/unduh?pilihan={index-G1}&semester={0..6}\` memakai \`auth:guru\` + validasi peran aktif + penugasan Guru G1 dalam Service, bukan percaya ID URL. \`semester=0\` berarti enam semester.
- Workbook Office Open XML (.xlsx), sheet **INFORMASI**, **DAFTAR SISWA**, dan **SEMESTER 1..6** (atau hanya semester dipilih).
- Roster mencakup semua siswa Gen meskipun belum ada TP semester, belum mulai, atau belum ada nilai. Sheet nilai memuat setiap pasangan siswa×TP dari rekap A6 tanpa pembatasan 25 baris UI.
- Nilai 0 ditulis numerik; nilai yang belum tersedia tetap sel kosong. Jalur lulus Diagnostik menandai Resume/HOTS \`Dilewati\`; Esensial \`—\`, status \`Tanpa nilai (Esensial)\`. Nilai Sementara berbeda dari Final. **Tidak ada agregat/kolom Nilai Akhir Semester**.
- **Formula injection:** semua nilai teks ditulis \`DataType::TYPE_STRING\`, sekalipun isi dimulai \`=,+,-,@\`. Skor angka menggunakan sel numeric.
- Batas 5.000 siswa roster dan 15.000 pasangan siswa–TP per workbook untuk kontrol beban shared hosting. Pembacaan nilai dibagi batch sampai 500, dengan verifikasi G1 berulang. Unduhan tanpa cache publik, file temporary server dibersihkan.
- Identitas dalam XLSX terbatas nama siswa; **tidak memuat NISN, nomor telepon, password/hash, token, jawaban atau kunci ujian**.

## Batas integrasi saat ini
- **Belum mencakup Pra-Semester** karena ujian/nilai Pra-Semester belum diimplementasikan di tahap A7. Tidak membuat nilai atau kolom palsu; hal ini tercantum eksplisit di sheet INFORMASI/UI. Setelah modul sah tersedia, gabungkan ekspor Pra-Semester ke workbook pada tahap terpisah.
- A7 **tidak mengubah SQL**. Data diambil dari tabel yang sudah dipakai Rekap Nilai A6, tanpa SQL update, migration, seeder atau sinkronisasi nilai historis.
- Audit dump XAMPP lokal setelah SQL A5 V4-0008 masih diperlukan untuk klaim kompatibilitas DB berjalan. Jangan menganggap SQL riwayat lama cukup membuktikan deploy terbaru.

## QA
- \`tests/unit/EksporNilaiGuruXlsxTest.php\`: membuat file XLSX asli, buka kembali dengan PhpSpreadsheet; uji semua sheet, nilai 0 numeric, sementara NULL, Esensial tanpa nilai, teks formula aman, siswa tanpa progres, >25 baris, privasi Gen lain, role aktif, input semester, dan tanpa mutasi nilai.
- \`.github/workflows/qa-a7-ekspor-nilai-guru.yml\`: PHP lint + PHPUnit A7 dan regresi A6 pada SQLite anonim; QA lint/DB pekerjaan lain tetap dijalankan oleh Actions.
- XAMPP manual: login Guru, buka \`guru/rekap-nilai\`, pilih Gen–Mapel dan semester, unduh dua pilihan XLSX, periksa 0/kosong/Esensial/Sementara/Final, enam sheet, dan uji Guru Gen lain. Periksa Dark/Light dan Chrome/Edge.
- Tetap PR Draft, tanpa merge \`main\` sebelum QA dan pengujian pemilik.

## Berkas
- Baru: \`app/Services/EksporNilaiGuruService.php\`, \`app/Controllers/GuruEksporNilai.php\`, \`tests/unit/EksporNilaiGuruXlsxTest.php\`, workflow QA A7, dokumen checkpoint ini.
- Diubah: \`app/Services/RekapNilaiTpGuruService.php\`, \`app/Config/Routes.php\`, \`app/Views/guru/rekap_nilai_tp.php\`.

## Langkah kerja lokal
\`\`\`bat
cd /d G:\xampp\htdocs\sla-sks
git status --short
git fetch origin
git switch --track origin/feature/v25-a7-export-nilai-guru-xlsx-20261102
git pull --ff-only
\`\`\`
Tidak ada patch SQL baru pada A7; SQL A5 V4-0008 tetap dibutuhkan bila menguji penanganan ujian batal.
