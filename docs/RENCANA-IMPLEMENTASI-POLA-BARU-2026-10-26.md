# Rencana Kerja SLA-SKS v2.5 — Perombakan Pola 26 Oktober 2026

> **Status:** rencana prioritas, bukan daftar fitur yang diklaim telah selesai. Urutan disusun agar bisa di-PR/merge dan diuji XAMPP bertahap; tidak langsung melompat ke sertifikat/dasbor sebelum fondasi.

## Tahap A — Pekerjaan sekarang (4 tugas pemilik)
| Langkah | Hasil yang diharapkan | Gerbang selesai |
|---|---|---|
| A1 | Catatan progres & mana yang dipertahankan/diubah/arsip | Membandingkan repo v2.4 dan dump terbaru, tanpa nama siswa/credential di Git |
| A2 | Revisi **16 Master Document v2.5** dan README/AGENTS | Tidak ada kebijakan lama yang masih mengikat, konflik keputusan ditandai, PR review belum merge |
| A3 | Rencana kerja transisi v2.5, ketergantungan dan E2E baru | Urutan realistis; fitur tunda disebut jelas |
| A4 | SQL lokal baru `sla_sks_v4` dari salinan data v3 | SQL privat terpisah, v3 tetap utuh, audit QA impor oleh pemilik; belum perlu ubah `.env` |

**Pekerjaan baru boleh diklaim 'selesai' untuk A4 setelah berhasil impor di XAMPP dan audit query baca-saja.** File SQL berhasil dihasilkan saja tidak membuktikan DB sudah terpasang.

## Tahap B — SETELAH tahap A dan pemeriksaan pengguna
**B1. File ATP Excel bermakro (prioritas sesudah A):** gunakan berkas `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx` sebagai dasar, buat versi baru bermakro dengan pengaturan yang disepakati, memuat 6 semester, BAB bernomor sambung, TP Esensial/NonEsensial, kode TP, nama35/deskripsi225, CP Mapel, identitas Gen/Guru terkunci sesuai generator. Tentukan format impor yang aman: macro tidak dieksekusi oleh server; validasi sel dan metadata; pratinjau hasil impor. **Belum dikerjakan sekarang.**

**B2. Finalisasi rincian implementasi pola baru:** turunkan Master v2.5 menjadi rencana PR kode/SQL dan matriks regression test final setelah hasil ATP Excel dapat diverifikasi. Rencana tingkat modul sudah terpetakan dalam Tahap C–K berikut, bukan klaim pekerjaan kode telah dimulai.

## Tahap implementasi aplikasi setelah Excel
| Tahap | Fokus implementasi | Prasyarat | Uji lokal |
|---|---|---|---|
| C | `bab`, CP tunggal Mapel/multi-elemen, kode BAB/TP, CRUD ATP langsung, editor/import Excel | v4 valid + Excel disetujui | BAB sambung lintas semester, jenis TP, otorisasi G1 |
| D | TP Esensial/NonEsensial, kesiapan TP, satu Materi aktif, snapshot versi, kartu UI | C | Esensial tanpa kegiatan; NonEsensial menolak 0/>1 Materi |
| E | Perbaikan generator/import Word bank Soal, ≥10 Diagnostik, ≥30 Sumatif, rich content dan media | D | DOCX soal Arab/rumus/gambar/tabel, pratinjau, anti kunci bocor |
| F | Alur siswa Semester→BAB→TP, diagnostik gate, satu Materi, Model B checkpoint 4 menit, Resume/HOTS, unduh media | D+E | Gating, offline-resync, layar media, jalur lulus/gagal |
| G | Sumatif wajib, remedial 1, skor tertinggi, nilai TP dua jalur, status Selesai walau <KKM | F | 20:20:60 vs 100%, nilai sementara, audit attempt |
| H | Jadwal Admin & bank soal gabungan BAB Pra-Semester, ujian Ketat serentak | G | Belum selesai TP ditolak, jadwal, fullscreen, remedial, nilai mandiri |
| I | Export Nilai Guru seluruh siswa dengan semua kolom TP dan Pra-Semester termasuk parsial | G+H (format Pra disiapkan sebelumnya) | Satu skor dapat diunduh, NULL bukan 0, G1 |
| J | Sertifikat daftar TP Esensial/NonEsensial, koreksi revisi dan QR/TTD privat | G+I+keputusan Pra terkait sertifikat | No nilai akhir semester, label Esensial, histori tidak hilang |
| K | Dashboard, laporan akhir, notifikasi, bantuan, performa/responsif, QA lintas peran | C–J | E2E-113–134, unit/feature, audit SQL, browser desktop+HP |

## Prinsip penyerahan per tahap
1. Audit dokumen Master terkait, query SQL atas dump terbaru dan kondisi kode aktual; gunakan branch terpisah.
2. Satu PR fokus per kelompok, sertakan SQL patch bernomor **jika** schema berubah, bukan migration/seeder; tidak commit data nyata.
3. Jalankan syntax/unit/feature bila tersedia; tulis hasil tes yang benar-benar dijalankan.
4. Review dan merge **setelah PASS**; pemilik pull ke `G:\xampp\htdocs\sla-sks`, menguji URL lokal, mengirim tangkapan layar/error; ulangi jika belum sesuai.
5. Tidak mengubah `.env`, ROOT `index.php`, kebijakan G1, privasi, atau mengulang isu upload PDF yang sudah FIX tanpa bukti bug baru.

## Keputusan belum final
Relasi penyelesaian Pra-Semester terhadap **hak terbit sertifikat** serta pencetakan nilai Pra-Semester pada sertifikat belum diputuskan. Jangan mengarang keputusan sebelum tahap J; nilai Pra-Semester tetap tersedia di ekspor dan hasil siswa.
