# Indeks Master Document SLA-SKS v2.5

**Satu sumber kebenaran: 16 berkas domain berikut**. Keputusan 26 Oktober 2026 di Master v2.5 mengatasi konflik dengan teks v2.3/PR #13 lama. BAB merupakan entitas utama dalam kontrak baru.

| No. | Dokumen | Cakupan |
|---:|---|---|
| 01 | [Ruang lingkup](01-RUANG-LINGKUP.md) | Produk, istilah, SKS, batas fitur |
| 02 | [Aturan bisnis](02-ATURAN-BISNIS.md) | Ketentuan lintas role, pembelajaran, ketuntasan |
| 03 | [Peran dan hak akses](03-PERAN-DAN-HAK-AKSES.md) | Admin/Guru/Siswa/Pimpinan, G1 per Gen–Mapel |
| 04 | [Kurikulum, CP dan ATP](04-KURIKULUM-DAN-CP.md) | CP Mapel, Semester, BAB dan TP berjenis |
| 05 | [Paket TP dan impor](05-PAKET-TP-DAN-IMPOR.md) | ATP langsung/Excel, BAB, satu Materi, Word bank soal |
| 06 | [Alur siswa](06-ALUR-PEMBELAJARAN.md) | Diagnostik gate, Sumatif wajib, Model B checkpoint, Pra-Semester |
| 07 | [Ujian](07-MESIN-UJIAN.md) | Diagnostik, Sumatif, Pra-Semester, remedial dan keamanan |
| 08 | [Penilaian](08-PENILAIAN.md) | Bobot 20/20/60 atau 100% Sumatif, nilai TP dan ekspor |
| 09 | [Sertifikat dan laporan](09-SERTIFIKAT-DAN-LAPORAN.md) | Daftar TP Esensial/NonEsensial, QR dan histori |
| 10 | [Database SQL](10-DATABASE.md) | Database baru sla_sks_v4, BAB, CP dan relasi nilai/ujian |
| 11 | [Arsitektur/API](11-ARSITEKTUR-DAN-API.md) | MVC/Service, endpoint, generator dan parser |
| 12 | [UI/UX](12-UI-UX-DAN-HALAMAN.md) | Seluruh layar, enam revisi layout wajib |
| 13 | [Design system](13-DESIGN-SYSTEM-CSS.md) | CSS `a-`, pagination, responsif, galeri |
| 14 | [Keamanan/audit](14-KEAMANAN-DAN-AUDIT.md) | File DOCX/XLSX aman, histori, kontrol server |
| 15 | [Instalasi/operasional](15-INSTALASI-DAN-OPERASIONAL.md) | MySQL/MariaDB, dump/backup/root CI4 |
| 16 | [Pengujian](16-PENGUJIAN-DAN-PENERIMAAN.md) | E2E-001–134 (v2.5 menimpa kasus lama yang berubah) |

## Template kerja resmi (dokumen non-Master)
- `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx` — berkas lama di repository; template pengganti manual tanpa macro disiapkan dalam percakapan dan perlu ditambahkan ke repository sebagai acuan resmi. Identitas/CP pada hasil unduhan berasal dari database login Guru/Gen–Mapel/Kurikulum.
- `docs/TEMPLATE_SOAL_SLA-SKS.docx` — struktur Word Soal Global yang diselaraskan ke **SLA-SKS-WORD-V1**, satu tipe PG lima opsi A–E. Generator per TP/jenis ujian mengikuti ukuran bank aktual; **berkas `docs/` berisi contoh, sedangkan unduhan Guru harus menghasilkan semua slot pertanyaan/opsi/kunci kosong sesuai jumlah bank yang diatur di aplikasi**.
- `docs/assets/galeri-komponen.html` — galeri visual CSS yang diperbarui bersama perubahan kode UI.
- [Keputusan produk](PENDING-DECISIONS.md) dan [SQL](../database/sql/README.md) bersifat pelengkap, bukan Master ke-17.
- **[Keputusan final validasi PDF Materi](KEPUTUSAN-VALIDASI-PDF-MATERI.md)** — pembatasan 15 MB/40 halaman untuk storage hosting, bug PDF yang sudah FIX di XAMPP, batas parser tersisa dan tes regresi wajib. Baca sebelum perubahan kode media; catatan implementasi ini **bukan Master ke-17**.

## Ketentuan perubahan
- Aturan hidup pada dokumen domain masing-masing, tanpa membuat versi alternatif. Dokumen lain merujuk domain utama.
- **PR dokumentasi ini tidak otomatis mengubah kode atau SQL**. Branch implementasi terpisah mengacu v2.5; tiap langkah diikuti tes dan persetujuan pemilik sebelum merge.
- **PR #13** masih implementasi berdasarkan hierarki lama; **jangan merge** sebelum selaras.
- **Gerbang DB wajib:** pemilik mengirim dump XAMPP terbaru sebelum *setiap* pekerjaan SQL/DB; developer memverifikasi bahwa dump dibuat sesudah patch terakhir, baru menyusun perubahan additive. Tidak ada reset tanpa permintaan/izin eksplisit dan backup. Proyek belum diunggah ke hosting.
- [Audit kontrak database dan gerbang QA lokal](AUDIT-KONTRAK-DATABASE-LOKAL.md) — pelengkap non-Master; tidak memuat dump/data pribadi.

## Peta dependensi
| Pekerjaan | Aturan domain | Implementasi | Verifikasi |
|---|---|---|---|
| CP resmi, peta ATP, TP Siap dan bank Word | 02, 04, 05, 07 | 10, 11, 12, 14 | 16 |
| Pembelajaran jalur Diagnostik dan Materi | 02, 06, 08 | 10, 11, 12 | 16 |
| Penilaian dan sertifikat | 08, 09 | 10–14 | 16 |
| Akun, G1, layout Admin dan profil | 03, 12, 13 | 10, 11, 14 | 16 |
| SQL/hosting/backup | 10, 15 | AGENTS.md dan README | 16 |

> **Status template biner:** Kedua berkas referensi telah berada di `docs/` pada branch v2.5 dan disetujui pemilik; pemeriksaan checksum atas hasil unduhan GitHub serta review PR tetap wajib sebelum merge. Lihat [status template](STATUS-TEMPLATE-V2.4.md).

- [KEPUTUSAN-EDITOR-DAN-KELOLA-SOAL-GURU.md](KEPUTUSAN-EDITOR-DAN-KELOLA-SOAL-GURU.md) — keputusan teknis CRUD soal Guru, SunEditor lokal dan perlindungan edit manual dari impor ulang Word.

## Kontrak perubahan 26 Oktober 2026 (v2.5)
Struktur **Mapel → CP → Semester → BAB → TP**, Esensial tanpa Materi/nilai dan NonEsensial tepat satu Materi. Diagnostik gate, Sumatif wajib minimal 30, remedial satu jika di bawah KKM, bobot 20/20/60 atau 100% Sumatif. Pra-Semester serentak sesuai Admin setelah TP NonEsensial selesai; ekspor nilai parsial Guru tersedia; **tidak ada nilai akhir semester**. **Keputusan terakhir:** gunakan Excel ATP manual `.xlsx` tanpa macro dan tanpa pembentukan otomatis. File pengganti perlu ditambahkan ke repository setelah review struktur sel.

Dokumen pendukung bukan Master ke-17: [progres](PROGRES-SEBELUM-POLA-BARU-2026-10-26.md), [rencana](RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md), [audit DB](AUDIT-DATABASE-V3-KE-V4-2026-10-26.md).
