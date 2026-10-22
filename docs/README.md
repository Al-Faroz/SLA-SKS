# Indeks Master Document SLA-SKS v2.4

**Satu sumber kebenaran: 16 berkas domain berikut**. Keputusan 22 Oktober 2026 di Master v2.4 mengatasi konflik dengan teks v2.3/PR #13 lama. Tidak ada entitas BAB dalam kontrak data baru.

| No. | Dokumen | Cakupan |
|---:|---|---|
| 01 | [Ruang lingkup](01-RUANG-LINGKUP.md) | Produk, istilah, SKS, batas fitur |
| 02 | [Aturan bisnis](02-ATURAN-BISNIS.md) | Ketentuan lintas role, pembelajaran, ketuntasan |
| 03 | [Peran dan hak akses](03-PERAN-DAN-HAK-AKSES.md) | Admin/Guru/Siswa/Pimpinan, G1 per Gen–Mapel |
| 04 | [Kurikulum, CP dan ATP](04-KURIKULUM-DAN-CP.md) | CP resmi, TP/ATP enam semester, KBC |
| 05 | [Paket TP dan impor](05-PAKET-TP-DAN-IMPOR.md) | Excel ATP, Word Diagnostik/Sumatif, TP Siap, media |
| 06 | [Alur siswa](06-ALUR-PEMBELAJARAN.md) | Diagnostik adaptif SKS, Materi, Resume/HOTS, Sumatif |
| 07 | [Ujian](07-MESIN-UJIAN.md) | PG lima opsi, fullscreen dan percobaan |
| 08 | [Penilaian](08-PENILAIAN.md) | Agregasi Resume/HOTS tiap Materi, koreksi |
| 09 | [Sertifikat dan laporan](09-SERTIFIKAT-DAN-LAPORAN.md) | PDF/QR/riwayat/versi |
| 10 | [Database SQL](10-DATABASE.md) | Model tanpa BAB, impor ulang lokal terkontrol |
| 11 | [Arsitektur/API](11-ARSITEKTUR-DAN-API.md) | MVC/Service, endpoint, generator dan parser |
| 12 | [UI/UX](12-UI-UX-DAN-HALAMAN.md) | Seluruh layar, enam revisi layout wajib |
| 13 | [Design system](13-DESIGN-SYSTEM-CSS.md) | CSS `a-`, pagination, responsif, galeri |
| 14 | [Keamanan/audit](14-KEAMANAN-DAN-AUDIT.md) | File DOCX/XLSX aman, histori, kontrol server |
| 15 | [Instalasi/operasional](15-INSTALASI-DAN-OPERASIONAL.md) | MySQL/MariaDB, dump/backup/root CI4 |
| 16 | [Pengujian](16-PENGUJIAN-DAN-PENERIMAAN.md) | Skenario E2E-001–112, acceptance |

## Template kerja resmi (dokumen non-Master)
- `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx` — master workbook enam semester setelah **pembersihan semua istilah BAB**. Identitas/CP pada hasil unduhan berasal dari database login Guru/Gen–Mapel/Kurikulum.
- `docs/TEMPLATE_SOAL_SLA-SKS.docx` — struktur Word Soal Global yang diselaraskan ke **SLA-SKS-WORD-V1**, satu tipe PG lima opsi A–E. Generator per TP/jenis ujian mengikuti ukuran bank aktual.
- `docs/assets/galeri-komponen.html` — galeri visual CSS yang diperbarui bersama perubahan kode UI.
- [Keputusan produk](PENDING-DECISIONS.md) dan [SQL](../database/sql/README.md) bersifat pelengkap, bukan Master ke-17.

## Ketentuan perubahan
- Aturan hidup pada dokumen domain masing-masing, tanpa membuat versi alternatif. Dokumen lain merujuk domain utama.
- **PR dokumentasi ini tidak otomatis mengubah kode atau SQL**. Branch implementasi terpisah mengacu v2.4; tiap langkah diikuti tes dan persetujuan pemilik sebelum merge.
- **PR #13** masih implementasi berdasarkan hierarki lama; **jangan merge** sebelum selaras.
- Dump SQL lokal dikirim pemilik nanti; developer menganalisis, memilih data aman yang perlu dipertahankan, menyusun dump final. Pemilik backup, mengosongkan database lokal sendiri, lalu mengimpor. **Bukan prosedur reset produksi**.

## Peta dependensi
| Pekerjaan | Aturan domain | Implementasi | Verifikasi |
|---|---|---|---|
| CP resmi, peta ATP, TP Siap dan bank Word | 02, 04, 05, 07 | 10, 11, 12, 14 | 16 |
| Pembelajaran jalur Diagnostik dan Materi | 02, 06, 08 | 10, 11, 12 | 16 |
| Penilaian dan sertifikat | 08, 09 | 10–14 | 16 |
| Akun, G1, layout Admin dan profil | 03, 12, 13 | 10, 11, 14 | 16 |
| SQL/hosting/backup | 10, 15 | AGENTS.md dan README | 16 |
