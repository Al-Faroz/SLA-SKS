# A10 — Checkpoint keputusan final Pra-Semester dan sertifikat

Tanggal keputusan: **3 November 2026**
Status: **kebijakan disetujui pemilik; implementasi sertifikat belum dimulai**.

## Keputusan final pemilik

1. **Ujian Pra-Semester TIDAK wajib diselesaikan sebelum sertifikat diterbitkan.**
2. **Nilai Pra-Semester TIDAK dicetak pada sertifikat.**

Alasan: **Pra-Semester merupakan checkpoint akademik sebelum Ujian Semester yang sesungguhnya**, bukan syarat sertifikat dan bukan komponen nilai yang harus dicetak pada sertifikat.

## Kontrak implementasi sertifikat

- Gerbang penerbitan tetap berdasarkan seluruh **TP NonEsensial selesai**, seluruh **nilai wajib TP Final**, identitas/penandatangan serta tanda tangan PNG siap. Siswa yang belum mengikuti Pra-Semester tetap bisa menerima sertifikat jika syarat TP dan administratif terpenuhi.
- Cetakan memuat daftar BAB/TP; **TP Esensial tanpa nilai**, **TP NonEsensial dengan nilai TP final**. Tidak ada **nilai Pra-Semester** atau **Nilai Akhir Semester**.
- Perubahan hasil Pra-Semester tidak merevisi PDF, nomor atau QR. Revisi hanya jika konten tercetak berubah secara resmi (misalnya nilai TP atau biodata), versi historis tetap dilindungi.
- Nilai Pra-Semester tetap dapat dilihat pada laporan/rekap dan diunduh lewat halaman tersendiri; tidak digabung ke ekspor Nilai TP (A9).
- Sebutan Ujian Semester yang sesungguhnya hanya menjelaskan peran Pra-Semester, **tidak memberikan mandat membangun fitur, nilai atau jadwal Ujian Semester baru**.

## Audit awal dump XAMPP yang diberikan (baca-saja)

Berkas sumber **privat** yang diunggah pemilik dalam percakapan: `sla_sks_v4 (15).sql`. **Dump tidak disalin atau di-commit ke repository**.

- phpMyAdmin: 5.2.1; waktu ekspor **3 November 2026 pukul 09.32** (menurut header file); host lokal 127.0.0.1; MariaDB **10.4.32**, PHP **8.2.12**.
- Database bernama `sla_sks_v4`; **64** pernyataan CREATE TABLE; catatan `schema_version` berjumlah **20**, terakhir `V4-0010-BANK-WORD-PRA`.
- Rekap baris `INSERT` dalam dump (belum diuji impor langsung): **27 siswa**, **6 paket_semester**, **12 BAB**, **36 TP**, **11 progres_tp**, **6 nilai_tp**, **21 percobaan_ujian** (Diagnostik/Sumatif), **1 ujian_pra_semester**. Tidak ditemukan percobaan bertipe `pra_semester` pada isi `percobaan_ujian`.
- **Belum terdapat tabel sertifikat** dalam dump; tidak ditemukan rute publik sertifikat pada `app/Config/Routes.php` dari branch induk A9-2. Oleh sebab itu implementasi sertifikat/QR dan SQL additive terkait harus menjadi pekerjaan tersendiri setelah fondasi laporan siap, bukan dinyatakan PASS sekarang.

Audit ini hanya membaca file SQL yang dilampirkan. Belum memeriksa keadaan DB aktif pada saat berikutnya, integritas restore, seluruh FK, atau menguji transaksi. Jangan menambahkan data asli/dump ke GitHub. Bila pembuatan SQL sertifikat dilakukan pada tahap berikutnya, pastikan dump terbaru setelah patch terakhir dan backup, sesuai `AGENTS.md`.

## Dokumen yang diselaraskan

- Master **08-PENILAIAN**, Master **09-SERTIFIKAT-DAN-LAPORAN**.
- `docs/PENDING-DECISIONS.md` (keputusan tidak lagi pending).
- `docs/RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md` (ekspor A9 terpisah dan gerbang sertifikat).
- Master **16-PENGUJIAN-DAN-PENERIMAAN** (SERT-PRA-001 sampai SERT-PRA-006).

## Langkah berikut

Tahap **A10 Laporan Akademik Siswa dan Monitoring Pimpinan**, menggunakan data TP dan Pra-Semester yang sudah tersedia; jaga batasan akses dan pemisahan nilai. Tahap **A11 Sertifikat PDF/QR** mengikuti ketentuan final di atas. Kedua modul ini belum diklaim selesai oleh checkpoint kebijakan ini.

**SQL perubahan:** tidak ada. **Berkas aplikasi yang diubah:** tidak ada. **Pengujian:** hanya audit statis dokumen dan dump privat, bukan PHPUnit atau XAMPP.
