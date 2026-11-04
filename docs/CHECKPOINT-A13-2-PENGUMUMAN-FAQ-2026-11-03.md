# Checkpoint A13-2 — Pengumuman Global dan FAQ Resmi

Tanggal 3 November 2026. Branch `feature/a13-2-pengumuman-faq-20261103`, Draft PR **#123** (stacked pada A13-1 `feature/a13-1-pusat-bantuan-panduan-20261103`, Draft PR #122). Belum digabung ke `main`, belum diuji pada browser XAMPP pemilik.

## Sumber dan audit dump aktual

Pengguna memberikan dump privat **`sla_sks_v4 (17).sql`** (hanya diperiksa dalam lingkungan kerja, **tidak diunggah ke GitHub**). Header menunjukkan phpMyAdmin 5.2.1, dump dibuat **03 Nov 2026 16.02**, MariaDB 10.4.32, PHP 8.2.12, database `sla_sks_v4`.

Audit DDL: **68 tabel**, terakhir tercatat di `schema_version` `V4-0011-SERTIFIKAT`. Tabel `pengumuman`, `faq`, `notifikasi` **belum ada**. `pengguna.id` bertipe `BIGINT UNSIGNED` dan `log_aktivitas` serta `schema_version` tersedia. Tidak ada data/dump pribadi di repository.

**Patch baru additive:** `database/sql/updates/V4-0012_PENGUMUMAN_FAQ.sql`, ditulis berdasarkan tipe FK dump nyata. Membuat tiga tabel `pengumuman`, `faq`, `riwayat_informasi` beserta PK, index dan FK ke `pengguna`, serta penanda `V4-0012-PENGUMUMAN-FAQ`. Tidak ada perubahan atau penghapusan tabel akademik, akun, isi sandi, siswa/ujian/nilai, aset, `.env`, atau root CI4. Tidak ada migrations/seeders. DDL implicit commit sehingga backup perlu dan impor **hanya sekali**.

## Fitur

- Semua peran aktif login: menu **Pengumuman** dan GET `/pengumuman` menampilkan hanya status **Terbit**, global tanpa filter Gen/role, dengan kondisi kosong dan pagination. Tidak ada data dummy.
- Admin: `/admin/pengumuman`, `/admin/faq` untuk daftar, tambah, sunting, terbitkan dan arsipkan. Bisa mengelola semua Pengumuman termasuk milik Guru; hanya Admin mengubah FAQ.
- Guru: `/guru/pengumuman` mengelola pengumuman **yang dibuatnya**, tidak bisa mengubah milik Guru lain/Admin. Siswa dan Pimpinan hanya membaca.
- Bantuan `/bantuan` mempertahankan panduan berbasis peran A13-1 dan menambahkan **Pertanyaan Resmi** dari FAQ Terbit Admin, diurutkan menurut `urutan` dengan pagination dan `details/summary`.
- Transisi Draf → Terbit → Arsip. Konten terbit bisa direvisi, tidak boleh kembali Draf; arsip terminal dan tidak hard DELETE. Semua perubahan menggunakan transaksi, audit `riwayat_informasi` sebelum/sesudah + `log_aktivitas`, optimistic concurrency nomor `versi` dan MySQL `FOR UPDATE`.
- Input panjang/status/urutan tervalidasi; teks biasa output `esc()`, tidak mengeksekusi HTML. POST CSRF global dan filter+Service role; media tidak diunggah dan tidak ada push/email/WhatsApp.

## Berkas

Dibuat:
- `database/sql/updates/V4-0012_PENGUMUMAN_FAQ.sql`
- `app/Services/InformasiGlobalService.php`
- `app/Controllers/Informasi.php`
- `app/Views/informasi/pengumuman.php`
- `app/Views/informasi/kelola.php`
- `app/Views/informasi/editor.php`
- `tests/unit/InformasiGlobalDatabaseTest.php`
- `tests/integration/SqlA13InformasiSchemaCheck.php`
- `.github/workflows/qa-a13-2-pengumuman-faq.yml`
- Checkpoint ini.

Diubah: `app/Config/Routes.php`, `app/Services/NavigasiService.php`, `app/Controllers/Bantuan.php`, `app/Views/bantuan/index.php`, `app/Views/layouts/panel.php`, `public/assets/css/app.css`, SQL README, Master 02/03/10/11/12/13/14/16, `docs/README.md`, `docs/PENDING-DECISIONS.md`, dan `docs/assets/galeri-komponen.html`.

## Pengujian otomatis

Workflow **QA A13-2 Pengumuman dan FAQ** (awal run `41662610185`) **PASS**: PHP lint seluruh berkas; PHPUnit SQLite anonim **12 tes/92 assertions**; regresi Bantuan A13-1 **9 tes/219 assertions**; eksekusi patch SQL asli V4-0012 pada MariaDB 11.4 ephemeral PASS (3 tabel, FK pengguna, `schema_version`, data sintetis). Setelah penambahan QA arsip/terbit dan dokumentasi, gunakan CI run terakhir untuk hasil akhir. Ini tidak menguji impor/dataset XAMPP secara langsung.

## Urutan pemasangan dan uji XAMPP

1. Backup **database `sla_sks_v4` dan folder `writable/`**. Periksa `schema_version` V4-0011 ada dan V4-0012 belum ada; periksa tiga tabel belum ada.
2. Pull branch **A13-2**; dari phpMyAdmin pilih **`sla_sks_v4` → Import** dan pilih **hanya** `database/sql/updates/V4-0012_PENGUMUMAN_FAQ.sql`. **Tidak mengimpor ulang** dump privat/SQL lama.
3. Verifikasi `schema_version` V4-0012 tepat satu, tiga tabel kosong dan FK tersedia. Petunjuk lengkap serta query baca-saja ada pada `database/sql/README.md`.
4. Login Admin: buat **satu Pengumuman uji** sebagai Draf; pastikan tidak muncul di `/pengumuman`. Terbitkan; periksa bisa dibaca Admin/Guru/Siswa/Pimpinan. Sunting; periksa teks dan riwayat. Arsipkan konten **uji milik sendiri**, periksa kembali tidak tampil publik.
5. Admin buat FAQ uji dengan urutan dan terbitkan; pastikan tampil di `/bantuan` setelah panduan A13-1; draf/arsip tidak tampil. Guru hanya bisa membuat Pengumuman, tidak bisa mengelola FAQ atau konten milik orang lain.
6. Uji tidak login → redirect login, role staf ganda, konfirmasi arsip, back/refresh, dua tab stale edit, teks `<script>` sebagai **teks** (tidak dijalankan), Dark/Light, Mode Dokumentasi, tampilan mobile dan tombol/pagination.
7. Jika gagal impor, **jangan ulang patch**. DDL implicit commit; catat galat, cek tiga tabel dan `schema_version`, laporkan sebelum langkah koreksi. Jangan hapus data nyata.

## Batas pekerjaan

Notifikasi internal per penerima, badge belum dibaca, `Hubungi Admin` dari kontak sekolah resmi, Kebijakan Privasi publik berversi, dan penerbitan notifikasi dari event penilaian/sertifikat **belum** diimplementasikan. A13-2 adalah **Pengumuman/FAQ**, bukan notifikasi. A13-2 belum dapat dinyatakan FIX sampai impor patch dan pengujian XAMPP benar-benar berhasil.

**Pekerjaan SQL selanjutnya wajib memakai dump XAMPP BARU yang dibuat setelah patch V4-0012 berhasil diimpor**. Jangan menggunakan kembali dump (17) sebagai bukti skema setelah patch.
