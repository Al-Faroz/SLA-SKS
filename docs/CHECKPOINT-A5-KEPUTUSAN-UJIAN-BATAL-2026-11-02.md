# Checkpoint A5 — Keputusan Guru atas Ujian Dibatalkan

**Tanggal:** 2 November 2026  
**Branch:** \`feature/v25-a5-keputusan-ujian-batal-20261102\` (berbasis A4, PR #94).  
**Status:** implementasi untuk review/QA; belum dinyatakan FIX XAMPP.

## Acuan
Master 03 (G1), 07 §5 (empat keputusan), 08 (skor batal tidak sah; jalur Langsung Belajar) dan 16 E2E-032–038. Tidak menambahkan kebijakan baru.

## Implementasi

1. Guru menu **Ujian Dibatalkan** hanya melihat percobaan batal siswa pada pasangan Gen–Mapel G1 aktif. Detail tidak menampilkan jawaban maupun kunci soal.
2. Diagnostik: \`ulang_diagnostik\` atau \`langsung_belajar\`. Sumatif: \`ulang_sumatif\` atau \`tunda_tinjauan\`. Keputusan bersifat transaksional, harus ada alasan, versi snapshot dipastikan sama, dan aktivitas diaudit.
3. Tunda Sumatif dapat disusul izin ulang; riwayat lama tetap append-only. Keputusan final tidak dapat diganti. Retry keputusan yang sama bersifat idempoten.
4. Izin ulang hanya boleh dipakai siswa ketika memulai percobaan pengganti yang sah melalui fullscreen. Percobaan lama tidak dihapus/diubah, skornya tetap NULL. Nomor percobaan pengganti meningkat, sehingga tidak berbenturan dengan unique key lama. Siswa tidak bisa membuat percobaan pengganti tanpa izin Guru.
5. Penilaian TP jalur Langsung Belajar mengakui keputusan Guru jika Diagnostik tidak punya skor sah; tidak pernah mengarang skor nol.
6. Ini tidak membuka fitur keputusan pra-semester, koreksi nilai atau mengubah algoritma anti-curang ujian.

## SQL baru — wajib pada XAMPP
- Berkas: \`database/sql/updates/V4-0008_KEPUTUSAN_UJIAN_BATAL.sql\`.
- **Backup database terlebih dulu**, kemudian impor satu kali pada \`sla_sks_v4\` melalui phpMyAdmin.
- Membuat dua tabel additive \`keputusan_ujian_batal\` dan \`riwayat_keputusan_ujian_batal\`, lalu menambah \`schema_version\`. Tidak menghapus/mereset data lama.
- Pemeriksaan sebelum: \`SHOW TABLES LIKE 'keputusan_ujian_batal';\`, \`SELECT versi FROM schema_version WHERE versi='V4-0008-KEPUTUSAN-UJIAN-BATAL';\` (keduanya semestinya kosong).
- Pemeriksaan sesudah: versi tercatat, dua tabel tersedia, dan \`SELECT COUNT(*) FROM keputusan_ujian_batal;\` menghasilkan nol sebelum Guru memutuskan.
- **Gerbang audit:** menggunakan dump privat terbaru yang ditemukan, \`sla_sks_v4 (11).sql\`, diekspor pada **02-Nov-2026 10.24**, MariaDB **10.4.32**, mencakup \`V4-0007\`; dokumen privat tidak disalin ke repository. Jika DB lokal telah berubah sejak dump itu, lakukan audit backup dan versi lagi sebelum impor.

## QA
- PHPUnit: \`tests/unit/KeputusanUjianBatalDatabaseTest.php\`; regresi \`tests/unit/SumatifRemedialNilaiV25Test.php\`.
- Workflow \`.github/workflows/qa-a5-ujian-batal.yml\` melakukan PHP lint, SQLite anonim, dan uji SQL pada MariaDB 10.11.
- Workflow luas yang mendeteksi perubahan \`app/**\` dan \`tests/**\` juga wajib PASS.
- **Masih wajib uji browser XAMPP**: dua Guru dari Gen berbeda, dua siswa, pending/tunda, pembatalan Diagnostik dan Sumatif, izin ulang fullscreen, skor batal NULL, click ganda, dan akses URL ID ilegal.

## Tindak lanjut
Setelah pengujian keputusan ujian batal, periksa ketuntasan semua TP dan penyelarasan nilai Guru sebelum membangun ekspor. Belum melakukan merge \`main\`.
