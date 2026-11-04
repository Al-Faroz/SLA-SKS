# Checkpoint A13-5 — Pengumuman Guru hanya untuk Siswa Gen yang Diampu

Tanggal: 4 November 2026. Branch `fix/a13-pengumuman-guru-hanya-siswa-gen-20261104`, berbasis A13-4 (`feature/a13-4-grup-informasi-revisi-sandi-faq-20261104`). Jangan merge main tanpa review.

## Revisi final
- **Admin**: Pengumuman yang dibuat dengan peran Admin berstatus Terbit dibaca semua akun aktif login.
- **Guru**: Pengumuman yang dibuat dengan peran Guru berstatus Terbit hanya muncul pada daftar baca **Siswa yang Gen-nya sedang diampu Guru pembuat**; tidak muncul pada daftar baca Pimpinan, Guru lain, Admin, atau siswa Gen lain. Guru pembuat tetap bisa mengelola miliknya lewat Kelola Pengumuman, dan Admin dapat mengelola semua.
- Guru yang mengampu dua Gen membuat pengumuman yang terlihat siswa kedua Gen; tidak ada target khusus per Mapel yang dapat dipilih. Jika penugasan Guru–Gen berubah, cakupan baca mengikuti penugasan yang sah saat ini.
- Audit **pertama** `riwayat_informasi` menentukan peran saat pengumuman dibuat. Ini menjaga status asal pada akun rangkap Admin+Guru dan saat Admin menyunting pengumuman Guru. Jika audit pertama hilang/invalid, daftar baca fail-closed; kelola dan riwayat tidak dihapus.

## Perubahan teknis
- Filter ada di `InformasiGlobalService::daftar('pengumuman',false)`, dengan SQL `EXISTS` ke audit awal, siswa, penugasan Guru–Gen, status dan peran Guru saat ini. Validasi akun tetap melalui `AuthService`.
- Filter dipakai sebelum `COUNT` dan `LIMIT`: pagination/jumlah tidak bocor lintas Gen.
- Tampilan Pengumuman, Kelola dan Editor menampilkan penjelasan sasaran yang benar; status Draf/Terbit/Arsip, audit CRUD, FAQ dan grup menu tetap.
- Master 01/02/03/10/11/12/14/16, draf FAQ, pending decisions disinkronkan.
- Tes sintetik diperluas untuk 2 Guru, 2 Gen, tanpa Gen, akun rangkap, perubahan penugasan, guru nonaktif, pembuat audit hilang, admin sunting, status, hak kelola, pagination. CI SQLite dan MariaDB.

## Batas dan pengujian
**Tidak ada SQL patch, migrasi, seeder, data dummy ke GitHub, pembaruan data XAMPP, penghapusan isi atau perubahan .env/root.** Query hanya mengandalkan relasi yang sudah digunakan modul Profil Guru serta tabel V4-0012. Schema dump privat baru pasca-V4-0012 belum dilampirkan pada turn ini; pengujian MySQL/MariaDB sintetik tidak menggantikan verifikasi skema XAMPP.

Sebelum dinyatakan FIX: tarik branch ini, login Admin/Guru Gen A/Siswa Gen A/Siswa Gen B/Guru B/Pimpinan; buat Pengumuman Terbit Guru A dan Admin; verifikasi masing-masing daftar baca. Uji Guru yang mengampu dua Gen, setelah perubahan penugasan, serta Daftar Kelola. Tidak perlu impor SQL baru. Bila ada kesalahan database, kirim dump terbaru privat **tanpa** memublikasikannya di repository.
