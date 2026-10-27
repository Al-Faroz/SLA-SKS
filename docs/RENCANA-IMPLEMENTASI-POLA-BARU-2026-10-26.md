# RENCANA KERJA IMPLEMENTASI SLA-SKS v2.5 — Setelah Template ATP Manual
**Tanggal:** 26 Oktober 2026 · **Target lingkungan:** CI4/XAMPP lokal dan MySQL/MariaDB shared hosting.

> **Jenis dokumen:** rencana pelaksanaan konkret setelah pemilik menyetujui template ATP **manual tanpa macro**. Dokumen ini menggantikan rencana sebelumnya yang mengharuskan pembuatan file `.xlsm`. Ini BUKAN klaim fitur sudah diimplementasikan.

## A. Kondisi yang telah diverifikasi
1. **Repository:** `main` saat pemeriksaan masih memuat kode/aturan model v2.4, tanpa BAB; revisi dokumen v2.5 ada di **PR #65 (DRAFT, belum merge)**. Implementasi CI4 yang sudah berjalan tidak otomatis mengikuti SQL v4.
2. **Database:** pemilik melaporkan `sla_sks_v4_LOKAL_PRIVAT_DIPERBAIKI_R2.sql` berhasil diimpor dengan **268 kueri**. **QA baca-saja 27 Oktober 2026 telah PASS pada indikator awal:** 58 tabel, 126 foreign key, penanda `V4-0001-R2`, 37 pengguna, 17 siswa, 20 mapel, 0 BAB (belum diisi), 4 Materi dan 2 percobaan historis. QA ini belum menggantikan uji seluruh fungsi aplikasi; `.env` pengguna tidak diubah otomatis.
3. **Excel:** `TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx` telah dibuat dan ditambahkan ke `docs/` pada branch fitur sesudah PR #68. Memiliki **10 sheet** (MULAI DI SINI, RINGKASAN, REFERENSI CP, SEMESTER 1–6, CONTOH MAT VII); 20 baris BAB dan 80 baris TP kosong per semester; **tanpa macro, tombol Proses, rumus pembentukan otomatis**. Tidak ada tugas menanam VBA, meminta pengguna memasang `.bas`, atau membuat `.xlsm`.
4. **Data historis:** salinan v4 menyimpan pengguna, siswa, CP, soal, media, snapshot dan ujian lama. TP lama memiliki 4 materi sehingga tidak boleh otomatis dianggap TP NonEsensial aktif dalam kontrak satu materi.
5. **Komponen aplikasi yang bisa dipakai kembali:** `PetaTpService`, `DetailTpService`, `KesiapanTpService`, `ProgresTpSiswaService`, `ProgresMediaMateriSiswaService`, `SiswaUjian` dan rute terkait. Ini titik refaktor, bukan bukti siap untuk v4.
6. **Hal yang harus diperbaiki pada PR dokumen:** teks AGENTS.md tentang Diagnostik yang langsung menuntaskan TP sudah diperbarui pada branch, seperti juga referensi Excel bermakro. Patch SQL publik di PR sudah diperbarui untuk revisi inti **R2** (FK/nullability/penanda versi); **jangan jalankan patch tersebut pada v4 yang sudah berhasil diimpor**. Pemeriksaan sumber versus data historis untuk setiap relasi tetap diperlukan saat modul diaktifkan.

## B. Kontrak inti yang tidak boleh berubah
- **Mapel → satu CP operasional (boleh banyak elemen/versi) → Semester → BAB → TP**; enam semester Fase D, nomor BAB berlanjut S1–S2 untuk VII, S3–S4 untuk VIII, S5–S6 untuk IX; kembali BAB 1 pada kelas baru.
- Nama BAB/TP maksimal 50 karakter, deskripsi masing-masing maksimal 225; `KODEMAPEL.KELAS.NOBAB.NOTP`, contoh `BIND.8.1.1`. Nomor TP kembali 1 dalam BAB baru.
- ATP diisi Guru **langsung dalam aplikasi atau dari Excel manual**. TP Esensial ada di ATP/sertifikat tetapi tanpa materi/ujian/nilai; TP NonEsensial memiliki **tepat satu materi aktif**.
- Diagnostik ≥10 soal hanya gate. Lulus boleh melewati Materi/Resume/HOTS; gagal wajib satu Materi → Resume final → HOTS final. **Semua** TP NonEsensial wajib Sumatif ≥30 soal.
- Nilai gagal Diagnostik 20% Resume +20% HOTS +60% Sumatif; lulus Diagnostik 100% Sumatif. Remedial **sekali** bila Sumatif < KKM dan ambil skor tertinggi. Status TP Selesai tidak membutuhkan nilai ≥KKM.
- Progres semester hanya dari TP NonEsensial. Pra-Semester ujian serentak pada jadwal Admin setelah semua TP NonEsensial selesai. Nilai Pra-Semester berdiri sendiri; **tidak ada nilai akhir semester**.
- Guru dapat **export semua siswa dan nilai TP** walaupun hanya satu nilai ada. Kolom Pra-Semester tetap ditampilkan kosong bila belum terlaksana. Jangan menahan export sampai Pra-Semester selesai.
- Model B aktivitas media client-side dengan checkpoint server **4 menit + saat selesai/pindah tahap**; validasi server tetap wajib. PDF 15 MB/40 halaman, zoom/fullscreen, unduh audio/gambar/PDF setelah gate diakui harus dipertahankan.

## C. Urutan PR implementasi
| Urutan | PR / tahap | Hasil konkret (Service, Controller, UI, data) | Gerbang penerimaan |
|---:|---|---|---|
| **0** | **Fondasi dokumen & DB v4** | Sinkronkan semua Master v2.5, hilangkan referensi macro dan kebijakan Diagnostik lama. Samakan patch SQL publik dengan R2, verifikasi 58 tabel, PK/FK, `schema_version`, 37 akun/17 siswa/20 mapel dan retensi materi/attempt. **Tanpa rewrite DB sumber v3.** | QA SQL baca-saja PASS, PR #65 lolos review dan disetujui merge sebelum PR fitur tergantungnya |
| **1** | **Template ATP resmi** | Tambahkan workbook manual `.xlsx` yang sudah dibuat ke `docs/` sebagai template acuan resmi, mengganti atau memversioning template lama. Tetapkan **rentang sel, header dan identitas** sesuai template 10 sheet; larang file contoh menjadi data riil. Tidak ada macro/VBA. | Buka normal Excel, sheet/header/validasi sel sesuai; file sumber di GitHub dapat diambil; versi template tercatat |
| **2** | **Fondasi BAB–TP dan CP** | Ubah `PetaTpService`, `DetailTpService`/Controller dan UI Guru agar mengelola BAB dan TP per Gen–Mapel. CRUD BAB draf; kode TP/urutan dari konteks, jenis Esensial/NonEsensial, CP mapel, aturan lintas semester; tidak mengubah TP Siap historis. | Guru Gen A tidak akses B; dua semester satu kelas nomor BAB sambung; kelas berikutnya reset; kode salah ditolak |
| **3** | **Impor ATP Excel manual** | Generator unduh workbook berdasar file resmi dan isi identitas Gen–Mapel–Kurikulum/CP dari SERVER. Parser `.xlsx` baca sheet SEMESTER 1–6, BAB dan TP manual; cek sheet/kolom/jenis, kode dan relasi; **pratinjau tambah/ubah/tetap/galat** → konfirmasi transaksi idempoten. Baris hilang bukan hapus; sheet CONTOH diabaikan; formula/metadata identitas tak dipercaya. | File valid masuk tepat, kode TP salah/duplikat/lintas Gen ditolak; impor ulang tidak ganda; tidak ubah TP Siap; galat menyebut sheet/baris |
| **4** | **Editor TP dan kesiapan** | TP Esensial tanpa form Materi/ujian/Resume/HOTS; NonEsensial tepat satu Materi. Ubah `KesiapanTpService`, `MateriDrafService`, snapshot dan CSS halaman BAB/TP mengikuti standar 12/13. | Esensial siap tanpa materi; NonEsensial menolak 0 atau >1 aktif; versi siswa lama aman |
| **5** | **Bank soal dan kesiapan Diagnostik/Sumatif** | Adaptasi generator/unggah Word dua bank per TP; batas Diagnostik ≥10 dan Sumatif ≥30. Pertahankan PG A–E, Equation/tabel/gambar/Arab, pratinjau aman dan asset privat. | Validasi jumlah dan konten rich, tidak bocor kunci, impor berulang idempoten |
| **6** | **Siswa: BAB → TP dan Diagnostik** | Katalog siswa dan `ProgresTpSiswaService` hanya hitung NonEsensial. Diagnostik lulus membuka jalur Sumatif tanpa syarat Materi/Resume/HOTS; gagal menjalani media/resume/HOTS. Esensial informatif saja. | Tidak mungkin lulus Diagnostik langsung menuntaskan TP; URL siswa Gen lain ditolak |
| **7** | **Satu Materi & Model B** | Sesuaikan pembaca YouTube/audio/gambar/PDF, checkpoint 4 menit, offline/retry, gate Resume/HOTS; unduh hanya audio/gambar/PDF setelah waktu sah. Gunakan komponen media existing, jangan ulang masalah validator PDF. | Waktu tak hilang saat gangguan jaringan; gate ditolak bila belum checkpoint sah; PDF/zoom/fullscreen/download regresi PASS |
| **8** | **Sumatif, remedial dan Nilai TP** | Satu Sumatif wajib untuk semua NonEsensial, remedial maksimal satu jika skor awal < KKM, skor tertinggi. KKM default Admin/khusus Guru, skor Resume/HOTS dan nilai sementara/final di Service; status Selesai terpisah nilai KKM. | Jalur nilai 20/20/60 & 100%, Sumatif wajib, remedial sekali, TP tetap selesai meski skor rendah |
| **9** | **Export Nilai Guru (jangan tunggu Pra-Semester)** | Export XLSX seluruh siswa Gen–Mapel/semester; kolom TP Esensial tanpa nilai, NonEsensial nilai/sedang menunggu, Pra-Semester **kolom kosong hingga ada nilai**. Filter & formula-injection aman. | Berfungsi saat **baru ada satu nilai TP**, NULL ≠ 0, siswa tak bernilai tetap muncul, akses lintas Gen ditolak |
| **10** | **Pra-Semester serentak** | Admin menjadwalkan per paket semester; Guru mengelola bank gabungan seluruh BAB (≥30 soal); gunakan mesin ujian Ketat lama dengan jendela waktu, snapshot/insiden, remedial sekali, hasil mandiri. Hubungkan hasil dengan Export Nilai. | Siswa belum selesai seluruh NonEsensial ditolak; di luar jadwal ditolak; nilainya muncul di ekspor tanpa agregat |
| **11** | **Sertifikat & laporan** | Daftar BAB/semua TP; Esensial tanpa nilai, NonEsensial bernilai; versi PDF/QR/TTD audit. Tidak ada nilai akhir semester. Laporan Guru/Pimpinan lengkap sesuai izin. | Histori pengganti aman; ujian Pra-Semester tidak otomatis jadi gate penerbitan tanpa keputusan pemilik |
| **12** | **Dashboard, notifikasi, E2E final** | Dashboard setiap peran memakai data BAB/TP/Pra, notifikasi internal, pengumuman, FAQ; responsif HP/laptop; audit dan test E2E-113–134 plus regresi fitur sebelumnya. | Unit/feature/integration PASS; manual QA XAMPP per peran, tanpa perubahan tidak diminta |

**Pemisahan tugas:** Satu PR untuk satu tahap/dependensi kecil. Jika suatu tahap terlalu luas, pecah menjadi beberapa PR teknis tetapi urutan akademik di atas dipertahankan. Tidak ada UI dummy atau tombol palsu.

## D. Peta workbook manual — kontrak impor
| Sheet | Perlakuan server |
|---|---|
| **MULAI DI SINI** | Panduan saja; **tidak ada tabel input jumlah BAB/TP atau tombol proses** |
| **RINGKASAN** | Saat download, Gen/Guru/Mapel/Kurikulum berasal dari akun server; pada upload metadata dari workbook wajib diverifikasi, tidak dipakai untuk menaikkan hak akses |
| **REFERENSI CP** | Diisi versi CP aktif yang sesuai Mapel/Fase/Kurikulum; dipakai untuk rujukan, bukan instruksi menambah CP resmi |
| **SEMESTER 1–6** | Parse **Bagian A** tabel BAB dan **Bagian B** tabel TP. Header Bagian A di baris 12 dan Bagian B baris 38 pada file yang diperiksa. Data BAB memiliki nomor/nama/deskripsi; data TP nomor BAB/urutan/kode/jenis/nama/deskripsi/1 Materi/perkiraan media/elemen CP/catatan. Semua field akademik diverifikasi oleh Service. |
| **CONTOH MAT VII** | **JANGAN PERNAH DIIMPOR** sebagai ATP nyata |

**Kapasitas:** workbook ini menyediakan 20 baris BAB dan 80 TP tiap sheet semester. Template harus menampilkan batas fisiknya dengan jelas; batas akademik tidak disamakan dengan 20/80. Jika kapasitas perlu ditambah nantinya, gunakan template revisi berversi dan validator yang sesuai tanpa menambah macro.

**Uji wajib workbook:** BAB nomor 1..3 di semester 1 dan nomor 4.. di semester 2; kelas VIII kembali BAB 1; TP urut 1,2,3 dalam BAB dan kembali 1 dalam BAB baru; kode misalnya `BIND.7.4.2`; beda jenis TP; gagal bila kode tidak cocok dengan mapel/kelas/nomor BAB/urutan, BAB dirujuk tetapi tak ada, atau TP NonEsensial menyalahi materi tunggal. File Excel tanpa formula otomatis: server **menghitung dan memeriksa** identitas dari baris input.

## E. Aturan penyerahan dan cek pengguna
1. **Audit kondisi terbaru:** README/AGENTS/Master, dump SQL v4 terbaru saat ada perubahan SQL, kode PHP saat PR dimulai.
2. Branch terpisah; CodeIgniter 4 MVC+Service, HTML/CSS custom/JS native, root `index.php` dan `.htaccess` tidak dipindahkan; `.env` dan koneksi DB tidak dimodifikasi otomatis.
3. DB memakai **file SQL bernomor**, tanpa CI4 Migration/Seeder, tidak mereset/menimpa data historis. Jika skema harus berubah, patch additive bernomor baru dengan petunjuk impor. Tidak pernah commit dump privat/NISN/hash/sesi/jawaban siswa.
4. Jalankan `php -l` file diubah, PHPUnit unit/feature terkait, tes Excel/parser sintetis dan SQL read-only bila tersedia. Laporkan hasil yang **benar-benar** dijalankan; keberhasilan statis tidak sama dengan sukses browser.
5. Buka PR tanpa merge; pemilik meninjau, lalu pull dan uji di `G:\xampp\htdocs\sla-sks` pada `http://localhost/sla-sks/`. Perbaiki regresi berdasarkan hasil nyata, baru lanjut tahap berikutnya.
6. **Belum diputuskan** apakah Pra-Semester harus selesai sebelum sertifikat diterbitkan dan apakah nilainya dicetak; jangan membuat kebijakan tersembunyi.

## F. Titik mulai yang direkomendasikan
**Tahap 0: QA awal database v4 R2 sudah PASS dan patch publik sudah diperbarui; PR #65 masih perlu review serta persetujuan merge.** **Tahap 1** menempatkan workbook manual resmi di repository (telah diajukan untuk review); sesudah itu **Tahap 2–3** mengimplementasikan editor BAB–TP dan impor Excel secara nyata. Jangan melompat ke Pra-Semester/sertifikat sebelum siswa dan nilai TP bekerja.
