# SLA-SKS v2.5 — Gerbang regresi integrasi dan finalisasi
**Checkpoint:** 6 November 2026 · **Lingkungan pengguna:** XAMPP lokal saja · **Status:** calon konsolidasi, **belum rilis produksi**.

## 0. Checkpoint kerja TERKINI — 8 November 2026 (jangan mengulang QA tanpa perubahan relevan)

**Keputusan proses:** hasil pengujian dan pernyataan FIX pemilik pada tahap implementasi tetap menjadi bukti penerimaan untuk versi kode yang diuji. Jangan meminta pemilik mengulang Diagnostik, Sumatif, Materi, penilaian, sertifikat, atau Pra-Semester hanya karena membuka percakapan baru atau belum dibuatnya matriks E2E terpusat. Ketiadaan rekap bukan bukti fitur rusak. Bila muncul laporan bug baru atau perubahan kode yang mengubah perilaku terkait, uji **hanya skenario yang terdampak**.

**Bukti penerimaan terbaru yang dapat diverifikasi:**
- Instalasi Windows/XAMPP dipulihkan; persyaratan Composer terpenuhi, ekstensi SQLite3/GD/ZIP aktif, PHPUnit CLI PHP 8.2.12 berjalan.
- PHPUnit **Windows XAMPP PASS 720 tes / 10.268 assertions**, 0 error / 0 failure pada commit `c7c548a` (8 Nov). Perbaikan lokal sebelumnya hanya asersi CRLF dan separator path pada fixture tes.
- CI **GitHub Actions 45/45 PASS** pada commit `9559068` (8 Nov). Bukti berasal dari GitHub Actions untuk head PR #143.
- Audit keamanan HTTP anonim Apache XAMPP **41/41 PASS**, 0 FAIL, pada `http://localhost/sla-sks/` (8 Nov). Ruang lingkup: sampel URL publik, file privat, dan pengalihan autentikasi; bukan penetration test.
- Pemilik telah menyatakan lima kelompok smoke test UI Batch 2–3 **FIX** (6 Nov).
- E2E tambahan yang dilakukan saat pemulihan XAMPP: siswa simulasi `sla_uji_07` berhasil masuk jalur Sumatif setelah Diagnostik lulus; Sumatif dimulai lalu **sengaja diakhiri tanpa jawaban**, menghasilkan skor 0 dan opsi remedial. Ini perilaku yang diharapkan, **bukan bug** dan **tidak perlu diulang** hanya untuk finalisasi PR.

**Penerimaan historis:** selama pengembangan sebelum 8 Nov, pemilik telah mengonfirmasi FIX untuk banyak kelompok implementasi, termasuk impor soal Word, KKM Guru, jadwal Pra-Semester, beberapa tahap sertifikat, dan perbaikan UI. Pengujian itu **tidak otomatis digugurkan**; jangan mengubahnya menjadi klaim bahwa seluruh skenario Master 16 sudah terlacak satu per satu. Jika diperlukan matriks lengkap untuk *go-live*, susun terlebih dahulu dari bukti historis dan audit kode, tanpa membebankan pengulangan kepada pemilik.

**Posisi repo saat checkpoint:** implementasi inti v2.5 sudah pada `main` melalui PR #134 dan branding A15 melalui #136. UI #137–#142 sudah diuji lokal dan diajukan sebagai **satu PR konsolidasi #143** (head `release/v25-regresi-integrasi-konsolidasi-20261106`, target `main`). PR #143 **Draft, mergeable=true, 36 berkas berubah** pada audit 8 Nov; perubahan hanya Views/CSS/JS/icon, tes, workflow, dan dokumen. **Tidak ada SQL, migrasi, .env, controller/service bisnis, index.php atau .htaccess** pada diff PR. Jangan menggabungkan PR bertingkat #137–#142 bersamaan dengan PR #143.

**SATU gerbang kerja berikutnya: review akhir diff PR #143, CI HEAD, dan persetujuan pemilik untuk integrasi ke `main`.** Tidak ada E2E manual baru sebagai syarat otomatis PR UI ini karena UI Batch 2–3 sudah diuji dan perbaikan setelahnya hanya tes portabilitas/dokumentasi. Setelah persetujuan, integrasi dilakukan satu jalur secara aman; jangan merge otomatis. Tes baru hanya jika ada perubahan bermakna/konflik/bug baru.

**Pisahkan dari keputusan produksi:** uji backup-restore pada salinan DB, shared hosting/MariaDB target, pengesahan kebijakan privasi dan penelusuran bukti E2E menyeluruh tetap **belum dinyatakan tuntas untuk go-live**. Ini backlog operasional *terpisah*, **bukan instruksi mengulang semua pekerjaan aplikasi sekarang**. Jangan menyebut aplikasi siap produksi sebelum gerbang produksinya benar-benar dipenuhi.

---

## 1. Dasar bukti dan batas kesimpulan
- [x] **Smoke test UI Batch 2–3**: pemilik menyampaikan **lima kelompok halaman telah diperiksa dan tidak ditemukan masalah** pada XAMPP (6 November 2026). Ini penerimaan visual/navigasi singkat, bukan bukti seluruh POST, keamanan ujian, penguncian TP, atau penerbitan sertifikat ujung-ke-ujung.
- [x] **CI PR #142** commit `b8afa7004c8ad540463b68d4cc48ee09c58a8178`: **28/28 workflow GitHub Actions PASS**. Workflow Batch 3: 6 tes baru / 70 assertions; suite penuh 715 tes / 8.295 assertions.
- [x] **CI gerbang finalisasi** pada commit `83cd1da6c72045894014d2e16d16a03ac710cbf0`: **43/43 workflow PASS**, termasuk kontrak rute (5 tes / 1.969 assertions) dan suite penuh **720 tes / 10.264 assertions**. Commit perbaikan portabilitas Windows `c7c548a96e40a912d4d21e05a995bd3ebf42525e` selanjutnya mendapatkan **45/45 workflow PASS**; suite penuh CI Linux **720 tes / 10.267 assertions**. Hasil khusus per commit; perubahan berikutnya wajib diuji ulang.
- [x] **Regresi unit XAMPP Windows** (8 November 2026): PHP 8.2.12, PHPUnit 10.5.66, `OK (720 tests, 10268 assertions)` setelah mengaktifkan ekstensi `sqlite3` dan menormalkan dua fixture lintas OS; tanpa perubahan MySQL.
- [ ] **E2E akademik di XAMPP** memakai data simulasi, termasuk kegagalan, otorisasi lintas Gen, ujian mandiri, Pra-Semester, koreksi dan sertifikat.
- [x] **Uji HTTP ROOT anonim** langsung pada Apache XAMPP (8 November 2026): `php scripts/qa/http_security_smoke.php http://localhost/sla-sks/` menghasilkan **41 PASS, 0 FAIL**; publik HTTP 200, berkas privat HTTP 403, halaman terlindungi HTTP 302. Ini audit sampel URL anonim, bukan bukti otorisasi lintas peran secara menyeluruh.
- [ ] **Backup → restore pada DB uji**; shared hosting/MariaDB target **belum diuji langsung**.
- [ ] **Pengesahan dan penerbitan Kebijakan Privasi** oleh madrasah (bukan tindakan developer).
- [ ] **Audit skema dan data XAMPP terbaru**: hanya setelah pemilik mengunggah dump privat terbaru; jangan kirim dump, hash sandi, NISN, atau data siswa ke GitHub.

Kriteria **kode boleh di-merge**: CI pada commit final PASS, review diff tidak memuat perubahan database/aturan bisnis tidak diminta, dan pemilik menyetujui hasil uji lokal yang relevan. Kriteria **aplikasi dinyatakan selesai/siap hosting** lebih ketat: skenario E2E, backup/restore dan keamanan/deployment terpenuhi. Keduanya tidak boleh dicampur.

## 2. Jejak branch dan PR
Rangkaian UI:
`#137 (shell) → #138 (komponen) → #139 (Batch 1) → #140 (Batch 2) → #141 (stabilisasi CI) → #142 (Batch 3)`.

Branch calon finalisasi: `release/v25-regresi-integrasi-konsolidasi-20261106`, dibangun di atas branch Batch 3. Semua PR tetap Draft sampai ditinjau. **Jangan menggabungkan PR bertingkat dan PR konsolidasi sekaligus** (menghindari duplikasi commit); pilih satu jalur integrasi yang disetujui.

**Penting:** pada audit 6 Nov, perbandingan branch Batch 3 ke `main` menghasilkan **ahead 52 / behind 1**. PR konsolidasi **#143** kemudian diverifikasi GitHub sebagai **mergeable=true**, tetapi tetap **Draft**. Diff PR #143 pada audit terbaru memuat 36 berkas (UI/CSS/JS, dokumen, tes, workflow) dan **tidak memuat SQL, `.env`, `index.php` atau `.htaccess`**. Perbedaan riwayat A15 tidak boleh dijadikan alasan untuk force-push/merge tanpa review; evaluasi status CI pada setiap commit baru.

## 3. Regresi otomatis finalisasi
Berkas `tests/unit/FinalisasiKontrakIntegrasiTest.php` memeriksa:
- Seluruh definisi rute GET/POST terbaca, tidak duplikat, dan seluruh URL privat memakai filter `auth`/`auth:peran` sesuai kewenangan.
- Controller dan method handler tersedia; rantai route ATP → TP → Diagnostik/Materi → Sumatif → Penilaian/Koreksi → Sertifikat serta Pra-Semester dan laporan Pimpinan tetap terhubung.
- Mutasi kritis tidak berubah menjadi GET; jalur approval koreksi nilai lama tidak diaktifkan.
- CSRF global aktif, auto-routing nonaktif, perlindungan ROOT tetap tercatat.

Workflow `.github/workflows/qa-finalisasi-integrasi-v25.yml` menjalankan PHP lint, kontrak akses, pengujian nyata Service menggunakan fixture SQLite sintetis (ujian/Pra-Semester/koreksi/sertifikat/notifikasi), kemudian semua PHPUnit unit. Hasil ini **bukan uji HTTP/browser XAMPP dan tidak membaca DB lokal**.

## 4. Referensi skenario E2E untuk kesiapan PRODUKSI (bukan perintah uji ulang)
Tabel di bawah ini adalah katalog risiko untuk audit bukti historis dan gerbang go-live, **bukan daftar tugas manual baru bagi pemilik untuk menyelesaikan PR #143**. Baca checkpoint bagian 0. Bila benar-benar memerlukan uji spesifik karena bug/perubahan terkait, gunakan data **simulasi**, backup dulu, catat PASS/FAIL/BLOCKED beserta bukti. Jika memerlukan perubahan skema/SQL, **hentikan** dan minta dump SQL terbaru sesuai `AGENTS.md`.

| Kode Master 16 | Skenario minimal | Syarat lulus |
|---|---|---|
| E2E-006/017–019 | Guru G1 vs Gen lain; TP Draf/Siap sebelum dan sesudah dipakai siswa | Hanya Guru G1 sah; konten yang sudah digunakan terkunci; CP/BAB mengikuti keputusan final |
| E2E-020–025/096–098 | Dua jalur Diagnostik pada satu TP NonEsensial | Diagnostik lulus tetap wajib Sumatif; gagal wajib satu Materi, Resume, HOTS lalu Sumatif; draf vs final benar |
| E2E-026–038 | Fullscreen desktop/Android, jeda koneksi 600 detik, izin akses ulang | Sumatif wajib desktop; insiden Diagnostik/Sumatif → izin ulang Guru, bukan remedial atau pembatalan Pra-Semester |
| E2E-039–044 | Nilai Resume/HOTS, koreksi langsung Guru G1 | Skor 0 sah; nilai TP dihitung ulang dengan audit dan notifikasi; tidak ada approval Admin |
| E2E-045–056 | Sertifikat pribadi otomatis dan revisi akibat koreksi | Syarat Final+TTD; nomor/QR/PDF lama disimpan; QR publik tanpa NISN; Pra-Semester bukan syarat |
| Pra-Semester Master 07/08 | Ujian utama, remedial, susulan, gangguan | ≥30 soal sah mencakup semua BAB; jadwal per paket; tidak hadir/batal → susulan Admin; satu remedial sah |
| E2E-058–063 | Pengumuman, FAQ, notifikasi, laporan Pimpinan | Gen dan role terisolasi; notifikasi privat; laporan hanya baca |
| E2E-063–069 | Ekspor, HTTP ROOT, instalasi dan backup | Tidak ada formula injection; `/.env` dan direktori privat 403/404; backup/restore pada DB uji |
| E2E-070–072 | Responsif dan Kebijakan Privasi | Hasil smoke test diterima; privasi publik terbit hanya jika naskah resmi disahkan |

Untuk pemeriksaan HTTP anonim baca-saja (tanpa autentikasi, tanpa POST), dari direktori ROOT XAMPP:

`php scripts/qa/http_security_smoke.php http://localhost/sla-sks/`

## 5. Prosedur setelah persetujuan review
1. Periksa `git status --short`, backup DB dan `writable/`; jangan sampai ada berkas privat masuk Git.
2. Lakukan `git fetch origin`; **jangan langsung pull `main` untuk mencoba PR Draft**.
3. **Gunakan hasil yang sudah ada:** PHPUnit Windows 720/720 dan HTTP anonim 41/41 PASS; CI HEAD wajib PASS. Jangan minta pemilik mengulang tanpa perubahan relevan.
4. Review diff dan penerimaan UI yang telah dicatat pada checkpoint bagian 0. Audit kelengkapan E2E merupakan pekerjaan konsolidasi bukti/go-live terpisah, bukan gerbang ulang PR UI.
5. Hanya setelah CI final, kompatibilitas diff `main`, dan QA lokal diterima, lakukan merge PR yang disetujui. Pull `main` sesudah merge, lalu cek XAMPP kembali.
6. Shared hosting menunggu uji lingkungan, DNS HTTPS stabil (terutama URL QR), dan backup/restore. **Jangan klaim go-live** pada tahap ini.

**Batas perubahan tahap ini:** tidak membuat/menjalankan patch SQL, migration, seeder, reset data, konfigurasi `.env`, atau pindah `index.php/.htaccess` ke `public/`.
