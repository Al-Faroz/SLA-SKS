# Checkpoint SLA-SKS — 27 Oktober 2026: ATP UI/UX FIX, jeda sebelum diskusi redesain global

> **Status dokumen:** Catatan serah-terima sesi, berdasarkan hasil kerja GitHub, pengujian XAMPP/MySQL yang dilaporkan pemilik, dan persetujuan visual terakhir. **Bukan bukti bahwa seluruh v2.5 selesai**, bukan persetujuan merge otomatis, dan bukan perubahan aturan akademik.
>
> **Baseline UI G03 dinyatakan FIX/disetujui pemilik pada 27-10-2026.** Pertahankan sebagai acuan fungsional dan navigasi saat mempertimbangkan desain visual baru. Perubahan visual global **belum disetujui**: akan didiskusikan dalam sesi berikutnya.

## 1. Identitas dan sumber acuan

- Proyek: SLA-SKS, LMS CodeIgniter 4, MTs Fase D, peran Admin/Guru/Siswa/Pimpinan.
- Repository: https://github.com/Al-Faroz/SLA-SKS
- Local Windows/XAMPP: `G:\xampp\htdocs\sla-sks`; URL `http://localhost/sla-sks/`.
- Struktur yang **tidak boleh diubah sembarangan**: `index.php` dan `.htaccess` di ROOT, `public/` untuk aset, `.env` lokal milik pengguna, arsitektur MVC + Service, CSS kustom dan JavaScript native.
- Master Document v2.5: `docs/README.md` dan 16 Master Document, terutama `docs/04-*`, `docs/05-*`, `docs/10-*`, `docs/12-UI-UX-DAN-HALAMAN.md`, `docs/13-DESIGN-SYSTEM-CSS.md`, `docs/16-PENGUJIAN-DAN-PENERIMAAN.md`, serta `AGENTS.md`.
- Kontrak akademik v2.5: Mapel → CP operasional → Semester → BAB → TP; TP Esensial/NonEsensial; kode `MAPEL.KELAS.BAB.TP`; semester 1–6 dikelola menurut G1 Gen–Mapel. **Jangan hidupkan kembali kontrak v2.4 yang sudah dicabut**.
- Database lokal berjalan: `sla_sks_v4` hasil dump privat **V4-0001-R2**, terpisah dari `sla_sks_v3`. Tidak ada Migration/Seeder, reset data, upload dump privat, atau commit kredensial.

## 2. Titik implementasi yang sudah dijalankan dan ditinjau

### G03 — Peta ATP enam semester (**baseline FIX**)

- Halaman `/guru/peta-atp`: filter Gen–Mapel Guru dan **enam kartu navigasi**; dua kelompok berurutan **Semester 1–3** dan **Semester 4–6**.
- Tiap kartu: warna latar lembut berbeda (total enam), kelas, status paket, jumlah BAB, jumlah TP, rincian Esensial/NonEsensial; nilai berasal dari database penugasan Guru, **bukan contoh statis**.
- Setiap kartu merupakan tautan riil ke `/guru/peta-atp/semester/{1..6}?pilihan=...`. Gen yang mulai di semester lebih akhir melihat semester sebelumnya sebagai **Dilewati oleh Gen** dan tidak berhak menambah isi di sana.
- Responsif: 3 kartu per baris pada desktop lebar, 2 pada tablet, 1 pada HP; keyboard focus terlihat.

### G03 — Detail semester (**baseline FIX**)

- Breadcrumb kembali ke Peta ATP sambil mempertahankan filter Gen–Mapel.
- Header warna sesuai semester: identitas Gen–Mapel, kelas, status paket, total BAB/TP/Esensial/NonEsensial.
- Toolbar atas berisi penjelasan dan **Tambah BAB**; jika belum ada paket dengan prasyarat sah, tampil opsi membuat **Paket Semester Draf**.
- Daftar bawah: **satu kartu berwarna per BAB**, dengan nomor, nama, deskripsi, jumlah TP, tabel TP di dalam BAB serta tombol **Tambah TP**.
- Tambah BAB dan TP melalui dialog native `<dialog>`, tanpa framework UI tambahan; tombol Batal/Tutup, Escape, fokus pada input, CSRF, validasi Service dan redirect kembali ke semester asal.
- Kasus draf/kosong/arsip/semester dilewati/izin Guru diatur berdasarkan konteks server. Jangan mengembalikan form BAB/TP panjang ke halaman ringkasan.
- Perbaikan aset: `app/Views/layouts/panel.php` memberi query versi dari `filemtime` pada `app.css` dan `app.js`. **Riwayat bug nyata:** HTML G03 baru sempat tampil tanpa CSS kartu (enam semester menjadi teks menyambung); setelah cache-busting + token spacing yang benar dan pull/reload pengguna mengonfirmasi **FIX**.
- Tampilan akhir dibuktikan screenshot pemilik: dua kelompok kartu masing-masing tiga, Semester 4 berwarna ungu, kartu BAB 1 hijau, tabel TP, dan dialog Tambah BAB terbuka dengan focus input. Pemilik menyatakan **“Oke sudah fix, kunci ini dulu”**. Ini **persetujuan tampilan**, bukan bukti seluruh uji hak akses, mobile dan modal TP.

## 3. Bukti database dan pengujian yang memang sudah dilaporkan

### QA database `sla_sks_v4` R2 (27-10-2026)
- Hasil awal phpMyAdmin: **58 tabel, 126 foreign keys, `V4-0001-R2`**; 37 akun pengguna, 17 siswa, 20 mapel, 4 materi historis, 2 percobaan ujian historis; BAB mula-mula 0.
- Prasyarat: 3 penugasan G1, 2 tahun ajaran, 1 kurikulum, 2 CP mapel dengan elemen aktif, 1 paket semester.
- GEN2025–SKI: CP aktif 1, paket tersedia 1. GEN2025–QH dan GEN2026–QH: CP aktif 0 (belum cocok untuk uji buat TP).
- Paket SKI: `paket_semester.id=1`, Semester 4, status **aktif**, versi 1, tahun ajaran 2026/2027; GEN2025 mulai Semester 4 sehingga boleh mulai BAB nomor 1 tanpa Semester 3.
- **INSERT BAB teruji nyata:** `bab.id=1`, `nomor_bab=1`, `paket_semester_id=1`, nama *Periode Klasik (650M - 1250M)*; muncul di UI dan SELECT phpMyAdmin.
- **INSERT TP teruji nyata:** `tp.id=2`, kode `SKI.8.1.1`, `bab_id=1`, urutan 1, nama *Perkembangan Daulah Ummayyah*, jenis `non_esensial`, status `draf`, `cp_mapel_id=1`; muncul di UI/SELECT.
- Ini **data akademik yang sudah tersimpan**, jangan hapus/reset/menyalin ke GitHub sebagai dump.

### Uji PHP/PHPUnit XAMPP
- Pemilik menjalankan `git pull --ff-only` dan `php -l` untuk `PaketSemesterGuruService.php`, `TpBabService.php`, `guru/peta_tp.php`: **3/3 lint PASS** pada PHP CLI 8.2.12.
- `vendor/phpunit/phpunit/phpunit --filter "AturanBab|KodeTp|TpBabInput|PaketSemesterGuruInput"`: **34 tests, 53 assertions, tanpa test failure** pada PHPUnit 10.5.66. Pesan `No code coverage driver available` hanya warning lingkungan.
- Pengujian ini dilakukan **sebelum** perubahan UI terakhir dan beberapa penambahan unit-test berikutnya; **jangan mengklaim keseluruhan suite terbaru telah PASS**.
- Pengguna telah **memverifikasi tampilan browser setelah perbaikan cache CSS** dan menyatakan FIX. Belum ada hasil final PHPUnit regresi lengkap/QA mobile/akses negatif khusus desain terbaru.

## 4. Perubahan yang sudah dicatat, tetapi masih perlu dituntaskan

- **Batas nama BAB/TP target 50 karakter**, deskripsi masing-masing 225, sudah menjadi keputusan bisnis dan telah diperbarui dalam Master/validasi.
- **Database v4 R2 saat ini masih menyimpan nama BAB dan TP dalam `VARCHAR(35)`**. Service/UI memakai pemeriksaan `information_schema.COLUMNS.CHARACTER_MAXIMUM_LENGTH` untuk membatasi input sesuai kapasitas aktual. Pesan batas 35 yang tampil pada screenshot **bukan regresi UI**: patch pelebaran DB belum dilakukan.
- Untuk menerapkan 50, **wajib minta dump SQL lokal terbaru** setelah penambahan BAB/TP dan verifikasi DDL, lalu buat **patch SQL bernomor baru** (pelebaran tanpa menghapus data) beserta QA sebelum/sesudah; jangan mengimpor ulang dump R2, jangan mengubah patch historis.
- Template Excel ATP manual `.xlsx` tanpa macro sudah dibuat sebagai artefak lokal sebelumnya; integrasi resmi ke `docs/`, generator/validator impor, dan uji keamanan belum tuntas.
- Modul TP lengkap (editor/detail baru, kesiapan, Materi tunggal NonEsensial, Diagnostik/Sumatif, penilaian, Pra-Semester, sertifikat, ekspor, dan pengujian alur akhir v2.5) **belum disimpulkan selesai**. Fondasi G03 yang lolos uji tetap dipertahankan.
- Masih perlu audit regresi CSS/JS terhadap halaman lain setelah perubahan global, termasuk responsive desktop/HP, state loading/error/empty/locked, uji keyboard, focus, dan performa.

## 5. Status branch/PR saat checkpoint (27-10-2026)

1. [PR #65 — Master v2.5 dan skema](https://github.com/Al-Faroz/SLA-SKS/pull/65): **Open, Ready for Review**, base `main`; **belum merge**.
2. [PR #66 — Fondasi BAB Guru](https://github.com/Al-Faroz/SLA-SKS/pull/66): **Open, Draft**, base branch PR #65; **belum merge**.
3. [PR #67 — Paket draf, TP dalam BAB, UI ATP dua tingkat](https://github.com/Al-Faroz/SLA-SKS/pull/67): **Open, Draft**, base branch PR #66; **belum merge**. Implementasi UI yang dinyatakan FIX berada di branch `feature/v25-tp-dalam-bab-20261027`, commit checkpoint kode sebelum catatan ini: `7f06d33e98fb35de29468ab5de9c6d64804ef789`.

PR-PR bersifat **bertingkat**, urutan review/merge harus #65 → #66 → #67 setelah pengujian yang sesuai. **Jangan menggabungkan ke main hanya karena checkpoint ditulis.** Di XAMPP pemilik memakai branch `uji-v25-tp` yang melacak remote PR #67.

## 6. Keputusan pemilik untuk sesi berikutnya: kaji redesain UI/UX global

**Arah yang diminta, bukan keputusan desain final:**
- Banyak alur aplikasi (menurut pemilik hampir **40 alur**) akan disusun ulang. Momentum ini akan dipakai untuk mempertimbangkan identitas visual keseluruhan.
- Prioritas: SLA-SKS harus **cantik, fresh, elegan, modern, nyaman dipakai, konsisten, tetapi tetap ringan**, cepat, jelas bagi Guru/Siswa/Admin/Pimpinan serta bisa dipakai desktop/HP.
- Referensi yang sangat diminati: **https://codefronts.com/**. Situs tersebut mengelompokkan inspirasi **Components, Motion, Layouts, Navigation, UI Snippets, Design Styles**, dan menonjolkan vanilla CSS/komponen tanpa framework. Referensi untuk inspirasi komponen/layout/visual dan micro-interaction, **bukan kewajiban menyalin keseluruhan UI atau mengubah stack**.
- Rancangan sesi baru: **analisis dan konsep lebih dulu**, audit desain dan pengalaman 4 peran, definisikan arah visual, design tokens, tipografi, hierarki, sidebar/header, cards, modal, tabel, form, status, responsif dan micro-interaction ringan. Pertimbangkan konsistensi visual dan aksesibilitas, performa shared hosting, no framework besar, CSS custom + JS native.
- Hasil diskusi kelak perlu diformalkan dalam **Master 12 (UI/UX)** dan **Master 13 (Design System CSS)** dengan rencana migrasi bertahap, komponen reusable, contoh tampilan, dan pemeriksaan regresi; **jangan eksekusi redesign aplikasi secara masif sebelum konsep mendapat persetujuan pemilik**.
- **Pertahankan pola navigasi/fungsi G03 yang sudah FIX** sebagai baseline; bahasa visualnya boleh diajukan untuk disempurnakan pada diskusi baru, tanpa mengubah data, hak akses, urutan akademik, dan alur simpan yang lulus uji.

## 7. Cara melanjutkan sesi baru

1. Baca catatan ini, `AGENTS.md`, `README.md`, `docs/README.md`, Master 12/13 serta status PR dan kode terkini; jangan berangkat dari asumsi status `main` sudah mutakhir.
2. Bahas inspirasi CodeFronts dari sudut visual/komponen; tawarkan **satu konsep desain yang utuh dan pragmatis** disertai alternatif seperlunya, tanpa mengulang pertanyaan yang sudah disepakati.
3. Fokus dulu pada **konsep UI/UX global**, tidak mengerjakan fitur bisnis lain, mengubah DB/`.env`, atau merge PR sebelum pemilik meminta.
4. Jika diminta implementasi kemudian, pakai branch terpisah, update Master 12/13, galeri komponen dan CSS/JS terpusat, periksa efek lintas role, lalu uji XAMPP iteratif.

**Checkpoint berhenti di sini.** Tidak ada penghapusan/penambahan data akademik sebagai akibat penyusunan catatan ini.
