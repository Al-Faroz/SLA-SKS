# Keputusan Implementasi — Pengelolaan Bank Soal Guru (24 Oktober 2026)

**Acuan:** Master Document v2.4 `05`, `07`, `10`, `12`, `13`, `14`, `16`. Ini catatan implementasi, bukan perubahan kebijakan akademik.

## Tujuan
Guru dapat melihat, menambah, mengedit dan menonaktifkan soal Diagnostik/Sumatif per TP (semua sumber: Word maupun input aplikasi) tanpa mengulang impor. Tetap PG dengan opsi A–E terisi dan satu kunci. Saat TP `draf` dan tidak memiliki percobaan/progres terikat, penyuntingan langsung diperbolehkan. TP `siap` hanya melalui proses koreksi ber-versi yang terpisah.

## UI/UX
- Halaman Bank Soal: pilih Diagnostik/Sumatif, jumlah aktif/target, tabel tiap soal + status + tindakan Lihat/Edit/Nonaktifkan/Aktifkan; tombol Tambah Soal.
- Satu halaman editor baik untuk soal hasil Word maupun manual: WYSIWYG untuk pertanyaan dan tiap opsi A–E, pilihan kunci berupa radio terpisah, pratinjau, Simpan/Batal.
- Editor: **SunEditor v3** MIT, vanilla JS, berkas vendor lokal; toolbar teks, tabel, RTL, rumus dan gambar. Tanpa Bootstrap/React/Vue. JS/HTML fallback berupa textarea jika aset editor belum tersedia, bukan tombol palsu.
- HTML masuk server dinormalisasi lewat sanitasi allowlist, bukan bebas menjalankan HTML, JS, SVG, iframe, URL eksternal, data URL. Gambar dari Word memakai sumber privat berizin; editor gambar baru menggunakan penyimpanan privat setelah implementasi upload aset tersendiri. Hindari base64 di database.
- Pesan khusus saat terjadi konflik; jangan menolak dokumen Word hanya karena Word menghilangkan metadata tambahan saat Save.

## Identitas & perlindungan impor ulang
- Identitas tetap `soal.id` dan `soal.row_uuid`; `urutan` hanya posisi tampil, **bukan ID soal**.
- Penyimpanan manual tetap menggunakan `konten_terstruktur_json`, ditandai `origin=manual`, `manual_edited=true`.
- Impor Word hanya memperbarui soal yang memang berasal dari slot impor dan belum pernah diedit manual. Tidak pernah menimpa soal manual (bahkan bila nomor urut sama).
- Jika ada konflik penempatan atau edit manual, **tolak konfirmasi secara atomik** dengan pesan yang menyebut nomor soal; Guru dapat menyelesaikan konflik melalui daftar soal. Jangan menghapus/menggeser soal lain tanpa izin. Baris Word yang hilang bukan perintah hapus.
- Setelah perubahan manual, impor Word identik lama tidak boleh diam-diam dianggap sudah menjaga edit; audit harus memperhitungkan perubahan manual.
- Edit dan nonaktifkan memakai transaksi DB, otorisasi Guru pada Gen–Mapel objek, status TP draf dan penolakan bila sudah ada progres/percobaan. Jangan hapus fisik soal akademik yang sudah memiliki relasi historis.

## Batasan awal dan QA wajib
- Rendering ulang OMML di browser belum setara Microsoft Word, dan perlu pengujian terpisah. Jangan merusak file Word privat yang sudah menjadi sumber gambar/rumus.
- Uji: soal manual + edit + nonaktifkan + aktifkan, hasil impor Word, percobaan impor ulang setelah edit, pergantian Gen, TP Siap, CSRF, pilihan A–E kosong/dua kunci, XSS di editor, gambar/tabel/RTL, mobile, serta tidak mengubah soal pada percobaan ujian lama.
- SQLite/migration/seeder **tidak digunakan**; jika skema bertambah, sertakan SQL kompatibel MySQL/MariaDB yang diimpor manual.
- Tidak mengubah konfigurasi root, .env, upload PDF, atau keputusan proses pembelajaran.

## Catatan pelaksanaan
Implementasikan bertahap melalui PR. Tunjukkan batas yang belum didukung (misalnya editor gambar baru dan Equation Word) secara jujur. Jangan menyatakan fitur selesai sampai PHPUnit, pengujian database dan XAMPP relevan lulus.

## Instalasi editor lokal dan deploy shared hosting

Setelah pull branch pengujian, jalankan **sekali saja** dari root proyek Windows:

```bat
powershell -ExecutionPolicy Bypass -File tools\install-suneditor.ps1
```

Script mengambil paket resmi `suneditor@3.3.3` berlisensi MIT dari npm dan menempatkan JS/CSS di `public/assets/vendor/suneditor/`. **Aplikasi tidak memerlukan CDN saat digunakan.** Berkas vendor diabaikan Git agar tidak tercampur dengan kode proyek; saat deploy shared hosting, unggah pula direktori vendor tersebut ke lokasi yang sama di hosting. Tanpa pemasangan aset, halaman tetap menyediakan textarea dan menampilkan pemberitahuan bahwa WYSIWYG belum tersedia.

**Status bertahap PR #34:** Daftar/tambah/edit/nonaktifkan dan gambar privat tersedia untuk Guru TP Draf; Equation Word/OMML belum dapat diedit tanpa kehilangan format dan akan ditolak dengan pesan spesifik. Hapus pada UI adalah **nonaktifkan** yang dapat dibalik, bukan DELETE SQL. Sanitasi HTML saat ini menjaga teks, tabel, list, RTL, dan gambar terotorisasi; format yang tak terwakili editor tidak boleh diam-diam merusak data. Aset gambar manual per TP dibatasi 100 file atau 100 MB; kebijakan pembersihan aset yatim perlu desain terpisah agar aset soal historis tidak terhapus.

