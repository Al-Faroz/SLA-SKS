# Indeks Master Document SLA-SKS v2.2

**Satu sumber kebenaran: 16 berkas ini.** Gunakan menu isi berikut, bukan dokumen revisi historis. Nomor dokumen menetapkan wilayah tanggung jawabnya.

| No. | Dokumen | Ketika sedang mengerjakan... |
|---:|---|---|
| 01 | [Ruang lingkup](01-RUANG-LINGKUP.md) | Definisi produk, istilah, fitur dan batas lingkup |
| 02 | [Aturan bisnis](02-ATURAN-BISNIS.md) | Aturan lintas modul, status dan ketentuan keras |
| 03 | [Peran dan akses](03-PERAN-DAN-HAK-AKSES.md) | Menu role, kepemilikan dan otorisasi |
| 04 | [Kurikulum dan CP](04-KURIKULUM-DAN-CP.md) | MTs Fase D, CP, TP, penugasan versi paket |
| 05 | [BAB dan Excel](05-PAKET-BAB-DAN-IMPOR.md) | Editor BAB, media dan 8 sheet Excel |
| 06 | [Belajar siswa](06-ALUR-PEMBELAJARAN.md) | Diagnostik, media, resume, tugas, lencana |
| 07 | [Mesin ujian](07-MESIN-UJIAN.md) | Fullscreen, batas waktu, koneksi, ujian batal |
| 08 | [Penilaian](08-PENILAIAN.md) | Rumus nilai, Guru, koreksi skor dan kunci soal |
| 09 | [Sertifikat dan laporan](09-SERTIFIKAT-DAN-LAPORAN.md) | PDF, QR, TTD PNG, versi/revisi, ekspor |
| 10 | [Database dan SQL](10-DATABASE.md) | Entitas, FK, indeks, instalasi/update via dump SQL |
| 11 | [CI4 dan API](11-ARSITEKTUR-DAN-API.md) | Service, Controller, root routing dan endpoint |
| 12 | [UI/UX dan layar](12-UI-UX-DAN-HALAMAN.md) | Semua layar P/S/G/A/M/U dan state interaksi |
| 13 | [Design system CSS](13-DESIGN-SYSTEM-CSS.md) | Token, class `a-`, pemakaian dan galeri |
| 14 | [Keamanan](14-KEAMANAN-DAN-AUDIT.md) | Hak, privasi, root `.htaccess`, audit |
| 15 | [Instalasi](15-INSTALASI-DAN-OPERASIONAL.md) | Shared hosting, setup, backup manual, update SQL |
| 16 | [Pengujian](16-PENGUJIAN-DAN-PENERIMAAN.md) | 75 skenario E2E dan kriteria selesai |

**Berkas tambahan:** [galeri komponen](assets/galeri-komponen.html), [status keputusan](PENDING-DECISIONS.md), [panduan SQL](../database/sql/README.md), [panduan kontribusi](../CONTRIBUTING.md).

## Aturan mengubah acuan
Setiap aturan ditempatkan pada berkas domain utamanya; berkas lain merujuk, **tidak mengulang sebagai versi alternatif**. Usulan perubahan melalui PR harus memperbarui kode dan pengujian terkait. Perbaikan teknis kecil tidak membutuhkan kuesioner pemilik apabila tidak mengubah kebijakan produk.

## Peta lintas modul
| Proses | Aturan utama | UI/API | Data/tes |
|---|---|---|---|
| Buat BAB Siap | 04 + 05 | 11 + 12 + 13 | 10 + 16 |
| Siswa belajar | 06 + 07 | 11 + 12 | 10 + 16 |
| Koreksi nilai/kunci | 08 | 03 + 11 + 12 | 09 + 10 + 16 |
| Sertifikat | 09 | 11 + 12 + 13 | 10 + 14 + 16 |
| Pengaturan sekolah | 03 + 09 | 11 + 12 | 10 + 14 |
| Setup hosting | 15 | 11 + 14 | 10 + 16 |
