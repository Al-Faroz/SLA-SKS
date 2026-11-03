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
- **`docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx` — template master ATP v2.5, kontrak aktif V25-3**, sepuluh sheet dan tanpa macro/VBA. Kontrak tetap: [KONTRAK-TEMPLATE-ATP-MANUAL-V25.md](KONTRAK-TEMPLATE-ATP-MANUAL-V25.md). `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx` adalah referensi lama, bukan format impor v2.5. Generator unduhan kontekstual, parser dan konfirmasi impor dilayani melalui halaman khusus **Guru → Peta ATP → Kelola ATP Excel (G05)**. Impor hanya mengisi semester yang belum mempunyai BAB/TP; tidak mengubah data draf/Siap yang sudah diisi lewat aplikasi.
- `docs/TEMPLATE_SOAL_SLA-SKS.docx` — struktur Word Soal Global yang diselaraskan ke **SLA-SKS-WORD-V1**, satu tipe PG lima opsi A–E. Generator per TP/jenis ujian mengikuti ukuran bank aktual; **berkas `docs/` berisi contoh, sedangkan unduhan Guru harus menghasilkan semua slot pertanyaan/opsi/kunci kosong sesuai jumlah bank yang diatur di aplikasi**.
- `docs/assets/galeri-komponen.html` — galeri visual CSS yang diperbarui bersama perubahan kode UI.
- [Keputusan produk](PENDING-DECISIONS.md) dan [SQL](../database/sql/README.md) bersifat pelengkap, bukan Master ke-17.
- **[Keputusan final validasi PDF Materi](KEPUTUSAN-VALIDASI-PDF-MATERI.md)** — pembatasan 15 MB/40 halaman untuk storage hosting, bug PDF yang sudah FIX di XAMPP, batas parser tersisa dan tes regresi wajib. Baca sebelum perubahan kode media; catatan implementasi ini **bukan Master ke-17**.

## Keputusan fundamental final — 2 November 2026 (wajib didahulukan)
- **Sunting CP:** Admin boleh memperbaiki CP kapan pun, termasuk sesudah digunakan TP/siswa; form Tambah/Sunting **hanya Kurikulum, Mata pelajaran, Kode CP, Elemen CP, Rumusan CP resmi**. Fase D dari sistem; tidak mewajibkan Otoritas penerbit, Nomor dokumen resmi, URL sumber dokumen, atau Tanggal berlaku. Lihat Master 04.
- **Sunting BAB:** Guru G1 boleh memperbaiki isian BAB yang sudah ada tanpa penguncian semata karena TP Siap; aturan nomor/kode yang sudah disepakati tetap. **Tidak membuat fitur memindah TP antar-BAB.** Lihat Master 04–05.
- **Sunting TP:** Guru G1 boleh mengubah TP Draf **dan Siap yang belum pernah digunakan siswa**. Sesudah ada satu siswa memulai TP, kontennya terkunci. Tidak ada kategori A/B/C, permohonan Guru, atau persetujuan Admin untuk edit TP yang belum digunakan. Lihat Master 02, 05, 11, 16.
- **Ketentuan lama yang bertentangan tidak berlaku:** draft PR kode koreksi TP Siap V4-0006 bukan jalur produk terbaru dan SQL-nya **tidak perlu diimpor**. Sebelum implementasi sesuaikan seluruh UI/controller/service/tes dan audit dump terbaru untuk kebutuhan perubahan skema; jangan reset data.

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
Struktur **Mapel → CP → Semester → BAB → TP**, Esensial dan NonEsensial masing-masing tepat satu Materi Guru; aktivitas/ujian/nilai siswa hanya pada NonEsensial. Diagnostik gate, Sumatif wajib minimal 30, remedial satu jika di bawah KKM, bobot 20/20/60 atau 100% Sumatif. Pra-Semester serentak sesuai Admin setelah TP NonEsensial selesai; ekspor nilai parsial Guru tersedia; **tidak ada nilai akhir semester**. **Keputusan terakhir:** gunakan Excel ATP manual `.xlsx` tanpa macro dan tanpa pembentukan otomatis. Template manual resmi telah masuk `docs/` pada branch fitur v2.5 (PR bertingkat setelah #68); generator unduhan dan alur pratinjau/konfirmasi impor sudah dikodekan pada branch fitur, tetapi belum dinyatakan lulus uji XAMPP.

Dokumen pendukung bukan Master ke-17: [progres](PROGRES-SEBELUM-POLA-BARU-2026-10-26.md), [rencana](RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md), [audit DB](AUDIT-DATABASE-V3-KE-V4-2026-10-26.md).


## Checkpoint terkini
- [Checkpoint 27 Oktober 2026 — ATP UI/UX FIX & arah diskusi redesain](CHECKPOINT-PENGEMBANGAN-2026-10-27-ATP-UI-FIX.md). Catatan serah-terima status nyata, QA, PR bertingkat, batas DB 35→50 dan baseline desain yang disetujui; **bukan pernyataan seluruh fitur v2.5 selesai**.
- **[Checkpoint penutupan Emerald Glass UI — 27 Oktober 2026](CHECKPOINT-PENUTUPAN-UI-EMERALD-GLASS-2026-10-27.md)**. Baseline visual diterima dan lingkup redesain ditutup; PR #68 tetap Draft/tidak di-merge. Ringkasan QA yang benar-benar diketahui, status PR #65–#68, batas SQL, kekurangan template Excel ATP v2.5, dan instruksi kembali ke pengembangan fitur.

- **[Checkpoint 1 November 2026 — hasil impor ATP 6 semester dan roadmap F1–F8](CHECKPOINT-IMPOR-ATP-6-SEMESTER-DAN-ROADMAP-FIX-2026-11-01.md)**. Berdasarkan dump privat terbaru, 6 paket, 12 BAB, 36 TP, dan 36 Materi draf valid secara struktur; patch V4-0005 terpasang. Fokus pekerjaan berikutnya F1 stabilisasi editor/impor lalu F2 kelengkapan konten. Tidak menandai aplikasi end-to-end sudah FIX.
- **[Checkpoint F1 — stabilisasi pasca-impor ATP (1 November 2026)](CHECKPOINT-F1-STABILISASI-PASCA-IMPOR-ATP-2026-11-01.md)**. Branch kerja `feature/f1-stabilisasi-pasca-impor-atp-20261101`: pratinjau Tetap/Galat memeriksa CP, urutan, serta Materi, UI Guru langsung menuju Sunting, tes regresi MySQL QA, dan pemeriksaan baca-saja `database/sql/qa/V4-0005_ATP_ENAM_SEMESTER_READONLY.sql`. **Menunggu uji XAMPP sebelum F1 FIX.**

- **[Checkpoint A10 — keputusan final sertifikat tanpa gerbang Pra-Semester (3 November 2026)](CHECKPOINT-A10-KEPUTUSAN-SERTIFIKAT-2026-11-03.md)**. Pra-Semester hanya checkpoint sebelum Ujian Semester yang sesungguhnya; **tidak wajib untuk sertifikat dan nilainya tidak dicetak**. Termasuk audit baca-saja dump lokal v4 (15) dan enam skenario penerimaan sertifikat; **belum ada kode sertifikat atau SQL baru**.
 - **[Checkpoint A10-1 — Laporan Akademik Siswa & Monitoring Pimpinan (3 November 2026)](CHECKPOINT-A10-1-LAPORAN-SISWA-PIMPINAN-2026-11-03.md)**. Implementasi halaman laporan Siswa pribadi dan monitoring Pimpinan read-only; progres TP sah, nilai 0/NULL, Pra-Semester mandiri; SQL tidak berubah. Menunggu regresi GitHub Actions dan uji XAMPP.

- **[Checkpoint A11-1 — Pemeriksaan Kelayakan Sertifikat (3 November 2026)](CHECKPOINT-A11-1-KELAYAKAN-SERTIFIKAT-2026-11-03.md)**. Pemeriksaan akademik Admin per siswa/Gen–Mapel–Semester berdasarkan TP tuntas, Sumatif sah dan nilai Final; **tanpa gerbang Pra-Semester**. Belum ada PDF/QR/terbit, dan **tidak ada SQL baru**.
- **[Checkpoint A11-2 — Sertifikat PDF/QR, aset privat dan histori revisi (3 November 2026)](CHECKPOINT-A11-2-SERTIFIKAT-PDF-QR-2026-11-03.md)**. Patch SQL additive V4-0011; Admin template/TTD/terbit/revisi, unduhan privat Siswa, verifikasi publik QR tanpa NISN. Pra-Semester bukan gate/nilai cetak. **Impor SQL dan uji browser XAMPP tetap harus dilakukan pemilik.**
- **[Checkpoint A11-3 — QA tata letak sertifikat PDF A4 (3 November 2026)](CHECKPOINT-A11-3-TATA-LETAK-SERTIFIKAT-A4-2026-11-03.md)**. Perbaikan visual dari PDF hasil cetak XAMPP: bingkai tinggi penuh untuk 2 BAB/4 TP, tipografi lebih besar, QR/TTD seimbang, footer aman untuk tabel panjang; tidak mengubah PDF/nomor/QR historis dan tidak ada SQL baru.

- **[Checkpoint A12-1 — Dashboard Akademik Pimpinan (3 November 2026)](CHECKPOINT-A12-1-DASHBOARD-PIMPINAN-2026-11-03.md)**. Beranda baca-saja dengan KPI siswa, paket, TP+Sumatif sah, Nilai Final, Guru, antrean Resume/HOTS, sertifikat berlaku, dan status Pra terpisah. Filter/paginasi dan QA SQLite. SQL tidak berubah, menunggu uji XAMPP.

- **[Checkpoint A12-2 — Dashboard Guru G1 (3 November 2026)](CHECKPOINT-A12-2-DASHBOARD-GURU-2026-11-03.md)**. Ringkasan penugasan Gen–Mapel, TP Siap/Draf, Materi aktif, antrean Resume/HOTS, siswa perlu bantuan dan ujian mandiri berjalan. Tanpa SQL; menunggu uji XAMPP sebelum FIX.

- **[Checkpoint A12-3 — Dashboard Siswa berbasis progres sah (3 November 2026)](CHECKPOINT-A12-3-DASHBOARD-SISWA-2026-11-03.md)**. Beranda lanjut belajar dengan gerbang Katalog, progres TP bersegmen, nilai Final termasuk 0, TP perlu bantuan dan sertifikat berlaku; Pra-Semester tetap checkpoint terpisah. Tanpa SQL; menunggu uji XAMPP sebelum FIX.

- **[Checkpoint A12-4 — Dashboard Admin (3 November 2026)](CHECKPOINT-A12-4-DASHBOARD-ADMIN-2026-11-03.md)**. Tambahan KPI akun/siswa/TP/foto/sertifikat, peringatan G1 dan TTD privat, sisa disk terukur; cron/backup belum dinyatakan sehat tanpa pemeriksaan. Tanpa SQL; QA SQLite, menunggu XAMPP.
