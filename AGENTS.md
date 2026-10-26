# Instruksi Coding Assistant — SLA-SKS Master v2.5

1. Baca `README.md`, `docs/README.md`, dokumen domain 01–16 terkait dan `docs/PENDING-DECISIONS.md` sebelum mengubah kode.
2. **Kontrak akademik v2.5:** 1 Mapel → 1 CP operasional → Semester → BAB → TP. Esensial tanpa materi/nilai, NonEsensial tepat satu Materi. Kode `MAPEL.KELAS.BAB.TP`, nama ≤35, deskripsi ≤225, BAB berlanjut semester berpasangan.
3. Fase D dan KBC/Panca Cinta tetap relevan. **Aturan akademik v2.5:** Diagnostik ≥10 soal hanya menjadi gerbang; siswa lulus boleh melewati Materi/Resume/HOTS, tetapi tetap WAJIB Sumatif ≥30 soal. Siswa tidak lulus menjalani tepat satu Materi → Resume → HOTS → Sumatif. Nilai jalur gagal 20/20/60, jalur lulus 100% Sumatif; remedial hanya sekali bila Sumatif di bawah KKM, skor tertinggi dipakai. TP Selesai tidak mensyaratkan skor ≥KKM. Tidak ada nilai akhir semester.
4. **G1 per Gen–Mapel**; paket/TP/materi/soal Gen A tidak dipakai otomatis untuk Gen B. Cek hak di Controller DAN Service; satu Admin cukup, usulan akademik tidak self-approved.
5. **Keputusan final Excel:** pengguna memilih template ATP **MANUAL tanpa macro** (.xlsx), tanpa tombol Proses dan tanpa file .bas. Template 10 sheet (MULAI DI SINI, RINGKASAN, REFERENSI CP, SEMESTER 1–6, CONTOH MAT VII) sudah dihasilkan lokal dan perlu direview/ditambahkan sebagai file resmi ke docs/ sebelum pengembangan generator/impor. Kode TP diisi manual dan DIVALIDASI SERVER terhadap konteks Gen–Mapel/Kelas/BAB/urutan. Guru juga dapat mengisi ATP langsung dalam aplikasi.
6. Word resmi: `docs/TEMPLATE_SOAL_SLA-SKS.docx`, satu parser PG lima opsi A–E untuk Diagnostik/Sumatif per TP; pertanyaan dan semua pilihan dapat teks/Equation Word, gambar, tabel bersarang, Unicode, audio/video Google Drive. Upload → validate → preview → confirm; no kunci bocor.
7. Enam revisi UI wajib menurut `docs/12-UI-UX-DAN-HALAMAN.md`: Pengelola/Siswa form atas & tabel pagination bawah; Mapel/Guru input 3 kolom + pagination; CP daftar export+pagination; identitas logo/favicon; profil foto full-width lalu Identitas/Kontak. Design system `docs/13-DESIGN-SYSTEM-CSS.md`.
8. Tetap CI4 MVC + Service Layer, `index.php` dan `.htaccess` di ROOT, `.env` dan URL tidak diubah. CSS kustom `app.css` serta JS native; tanpa Bootstrap/React/Vue tambahan.
9. **Tidak memakai Migration/Seeder**. Database baru `sla_sks_v4` dari dump lokal privat 26 Oktober 2026; `sla_sks_v3` tidak diubah. Hash, NISN, nama dan progres siswa **JANGAN DI-PUSH**; patch skema bernomor dan QA terpisah.
10. Privat: tidak ada credential, data siswa nyata, dump/backup privat dalam GitHub. Semua SQL, unggahan XLSX/DOCX, preview dan aset lulus pemeriksaan izin, sanitasi, audit, concurrency dan anti-duplikasi.
11. Jangan hapus/timpa data nilai, attempt, file, sertifikat, CP/TP Siap dan riwayat versi produksi. Koreksi konten akademik lewat permohonan Guru dan keputusan Admin tanpa menyetujui usulan diri sendiri; sertifikat tidak dicabut.
12. Semua UI memiliki normal/kosong/loading/sukses/gagal/locked, keyboard, kontras, mobile. Semua tombol berfungsi; komponen baru wajib masuk galeri saat implementasi kode. E2E sesuai `docs/16-PENGUJIAN-DAN-PENERIMAAN.md`.
13. Bekerja di branch terpisah, PR untuk review, **jangan merge `main` tanpa PASS**. PR #13 masih memakai model lama; revisi implementasi lewat branch baru setelah Master ini direview. Lakukan tes nyata bila mampu dan jujur tentang yang belum diuji.

14. **Keputusan final PDF Materi (hasil uji XAMPP 24-10-2026):** baca `docs/KEPUTUSAN-VALIDASI-PDF-MATERI.md` **sebelum** mengubah `PdfMateriValidator.php`, upload atau pratinjau. Fokus aturan **PDF maksimal 15 MB dan 40 halaman** untuk kapasitas hosting/panjang materi; PDF normal dengan revisi inkremental, navigasi, metadata C2PA, hyperlink atau anotasi **tidak boleh ditolak hanya karena format internalnya**. Keamanan aktif dan akses privat tetap. Pengguna telah menyatakan masalah upload PDF sebelumnya **FIX**; jangan membahas ulang tanpa laporan bug baru yang nyata. Catatan ini tidak mengubah 16 Master Document.


## Aturan tambahan gerbang audit SQL (25 Oktober 2026)
- **Status deployment:** aplikasi hanya sedang dibangun/diuji di XAMPP lokal, **belum pernah dipasang ke hosting**. Shared hosting/MariaDB adalah target kompatibilitas di masa datang; jangan mengklaim sudah diuji/diupload.
- **Sebelum SETIAP pekerjaan SQL/database** (CREATE/ALTER/patch/import, query bergantung struktur, pemetaan atau perbaikan data), wajib minta **dump SQL lokal terbaru yang benar-benar terunggah** dan dibuat **sesudah patch terakhir**. Jangan menganggap dump percakapan lama mewakili DB berjalan.
- Verifikasi header waktu, versi server, nama DB, DDL seluruh tabel relevan, kolom, indeks/FK/CHECK, row count/agregat yang aman, `schema_version`, SQL patch sebelumnya dan query yang memakai tabel yang sama.
- Jangan membuat fixture tes hanya berdasarkan skema yang diasumsikan Service; sertakan tabel historis/indeks/relasi yang sudah ada, skenario patch parsial, tab phpMyAdmin dan uji XAMPP sebelum klaim fitur FIX.
- Default: patch SQL **additive bernomor baru**, QA sebelum/sesudah **baca-saja**, backup DB dan `writable/`. Jangan mengedit patch historis yang sudah pernah diimpor lalu meminta impor ulang. Dilarang reset/DROP/TRUNCATE tanpa permintaan serta persetujuan eksplisit khusus database lokal dan backup.
- Dump privat, akun/identitas siswa, password/hash, backup dan kredensial tidak boleh dimasukkan ke GitHub/workflow atau publik. Audit teknis boleh memakai skema sintetis yang meniru DDL realistis.
- Jika dump terbaru belum terlampir/terverifikasi, **hentikan perubahan SQL**, lanjutkan hanya telaah/dokumentasi/tes yang tidak mengubah DB dan jelaskan keterbatasan pemeriksaan.


## Keputusan final baru (26 Oktober 2026)
Ketentuan v2.5 **mencabut** kontrak v2.4 tanpa BAB, banyak materi dalam satu TP, diagnostik sebagai nilai final, bobot 20:30:50, tiga percobaan normal sumatif, dan rata-rata akhir semester. Status implementasi historis dalam dokumen tidak mengubah kontrak baru. Export nilai Guru harus tersedia walau baru satu skor TP.
