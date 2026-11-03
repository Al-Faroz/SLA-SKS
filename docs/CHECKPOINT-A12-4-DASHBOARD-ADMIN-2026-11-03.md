# A12-4 — Dashboard Admin Akademik dan Kesiapan Operasional

Tanggal: **3 November 2026**.
Branch: `feature/a12-4-dashboard-admin-20261103`.
PR: **#119 Draft**, berbasis `fix/a12-3-ui-dashboard-siswa-20261103` (stacked), tidak merge main.

## Hasil penerimaan sebelumnya

Pemilik menerima Dashboard Siswa A12-3 beserta koreksi UI PR #118 dengan pernyataan **FIX**. Dashboard Pimpinan A12-1 dan Guru A12-2 juga telah diuji melalui screenshot XAMPP pada tahap sebelumnya. A12-4 mengubah **beranda Admin saja**; progres serta hak Siswa/Guru/Pimpinan tidak ikut diubah.

## Dasar pekerjaan

- `README.md`, `AGENTS.md`, `docs/README.md`, dan Master 02/03/12/13/15/16 telah diperiksa.
- Admin sebelumnya **sudah mempunyai beranda Emerald Glass** dengan empat KPI referensi: Gen, Mapel, penugasan Guru G1, pasangan tanpa Guru; serta seluruh shortcut. Pertahankan tata letak dan tautan yang berfungsi.
- Katalog A01 meminta status akun, TP siap, antrean persetujuan, tanda tangan dan kondisi operasional. Status cron, backup dan batas minimal kapasitas disk **belum mempunyai sumber pemeriksaan yang dapat diandalkan**, maka A12-4 menampilkan kondisi tersebut sebagai *belum diperiksa*, bukan klaim sehat.

## Implementasi

`app/Services/DashboardAdminService.php`:
- Tetap menyediakan `gen`, `mapel`, `guru_ditugaskan`, `belum_ditugaskan`.
- Menambah `akun_aktif` seluruh peran, `siswa` terdaftar, `tp_siap`, `tp_draf`.
- `foto_menunggu`: hanya foto profil `f.status='menunggu'` yang masih ditunjuk `profil_pengelola.foto_menunggu_id`, **bukan foto historis atau yang sudah disetujui**.
- `sertifikat_berlaku`: hanya pointer `sertifikat_aktif` dengan `sertifikat.status='berlaku'`; versi `digantikan` tidak dihitung.
- `ttd_status`: `belum_aktif`, `berkas_tidak_valid`, atau `siap`; status siap memeriksa path privat dan checksum SHA-256 melalui service aset yang sudah ada. Tidak membeberkan path, SHA, atau gambar pada output.
- `disk_sisa_byte`: kapasitas terukur dari `disk_free_space(WRITEPATH)`; `null` jika tidak dapat dibaca. **Tidak ditafsirkan otomatis sebagai aman**, sebab ambang kapasitas hosting tidak ditetapkan.
- `cron_status`/`backup_status` = `belum_diperiksa`, karena belum ada mekanisme pemeriksaan otomatis terverifikasi. Tidak menambahkan cron job atau backup palsu.
- `perhatian`: kondisi referensi, G1 kosong, foto menunggu, TTD belum aktif/bermasalah, masing-masing memakai tautan ke modul Admin yang sudah tersedia. Semua query read-only.

`app/Views/admin/beranda_prototipe.php`: mempertahankan empat KPI dan shortcut, menambah enam kartu operasional, dua panel perhatian dan kesiapan, teks kosong yang jujur. `public/assets/css/app.css`: grid Emerald Glass responsif 3→2→1 dan panel dua→satu kolom. `app/Controllers/Akun.php`: header `Cache-Control: private, no-store` khusus Dashboard Admin. Galeri komponen dan Master 03, 12, 13, 15, 16 diperbarui.

## SQL dan data privat

**Tidak ada SQL baru, migration, seeder, UPDATE, DELETE, atau reset.** Kode memakai tabel yang telah digunakan service produksi: referensi Gen/Mapel, G1, pengguna, siswa, TP, foto profil, sertifikat dan aset. Pemasangan V4-0011 sudah terjadi pada XAMPP sebelumnya; dump privat terakhir dalam percakapan terdahulu adalah `sla_sks_v4 (16).sql`. Karena belum ada dump baru yang diunggah dalam permintaan A12-4 ini, **jangan mengklaim audit ulang seluruh database XAMPP atau tanggal dump sebagai keadaan saat ini**. Tidak mengunggah dump, identitas siswa, sertifikat PDF atau TTD.

## QA yang dijalankan

Workflow `QA A12-4 Dashboard Admin` run **41649314078**: **SUCCESS**. Lint PHP (Service/Controller/View/Test) PASS; `tests/unit/DashboardAdminDatabaseTest.php` **6 tes, 30 assertions** PASS; regresi `DashboardPimpinanDatabaseTest.php` **6 tes, 29 assertions** PASS. Fixture SQLite anonim mencakup 2×2 Gen–Mapel, akun aktif/nonaktif, status TP Siap/Draf/arsip, antrean foto yang terikat pointer, sertifikat aktif vs digantikan, TTD tanpa pointer/berkas rusak/checksum cocok, role lain ditolak dan dashboard tidak menulis. CI dijalankan ulang pada checkpoint terbaru sesuai trigger workflow. **Uji visual dan angka XAMPP masih wajib** sebelum dinyatakan FIX.

## Langkah uji XAMPP

1. Pull `feature/a12-4-dashboard-admin-20261103`. Tidak impor SQL.
2. Login Admin, buka `http://localhost/sla-sks/index.php/admin`. Cocokkan empat KPI referensi lama dan enam KPI operasional terhadap modul Admin/Guru yang relevan; angka Gen DEMO tetap bagian data jika memang masih ada.
3. Periksa KPI **foto menunggu** terhadap halaman Persetujuan Foto dan sertifikat berlaku terhadap modul Sertifikat. TTD PNG yang sudah diunggah harus tampil **Aktif dan terverifikasi** jika pointer + file privat cocok.
4. Periksa kapasitas disk. Ingat angka ini volume `writable/`, bukan kuota shared hosting. Cron dan backup sengaja masih bertuliskan belum diperiksa.
5. Klik setiap tombol perhatian dan shortcut. Uji Dark/Light, Mode Dokumentasi, HP 360/390px, desktop dan keyboard.
6. Login sebagai Guru/Siswa/Pimpinan: rute `/admin` harus ditolak. Reload beranda tidak boleh mengubah progres, nilai, sertifikat maupun foto.

Status: implementasi dan QA sintetis PASS, **menunggu verifikasi XAMPP pemilik**. Tahap berikutnya adalah meninjau sisa fungsi Tahap 12 (notifikasi/pengumuman/FAQ dan E2E akhir), bukan menghidupkan kembali fitur bisnis yang dibatalkan.

## Audit UI Admin dari 14 screenshot XAMPP — PR #120

Pemilik mengirim seluruh rangkaian screenshot operasional Admin setelah pull A12-4 dan meminta `Analisa, Teliti & Perbaiki`. Observasi screenshot: beranda Admin menunjukkan 3 Gen × 20 Mapel = 60 kemungkinan, 3 pasangan G1 terisi dan 57 sisanya belum diisi; akun aktif 49, siswa 29, TP Siap 3, TP Draf 37, foto menunggu 0, sertifikat berlaku 1, TTD terverifikasi, sisa disk 25,31 GB. **Angka ditampilkan tanpa mengarang audit SQL baru**. Data disimpan di lokal pengguna, foto/NISN/screenshot tidak dipush.

Ditemukan masalah kontras kritis pada detail reset sandi (CSS lama #f6faf8 vs teks terang), tabel Pengelola/Siswa terjepit form kiri 340px, form Tambah Mapel terlalu sempit, aksi filter CP terlalu dekat tabel, label situs web opsional berulang, banner warisan K2 tetap muncul, dan peringatan '57 pasangan wajib' berpotensi menyesatkan. Branch `fix/a12-4-audit-ui-admin-20261103` memperbaiki semua ini di **Draft PR #120**. Status penugasan G1 dan angka total tetap sama; yang diperjelas hanya istilah **kombinasi potensial**. Endpoint POST, CSRF, nilai, riwayat dan kebijakan bisnis tidak diubah.

Layar referensi Gen/tahun, edit CP, KKM, ujian Pra, antrean foto kosong dan profil tidak memiliki kelainan visual yang cukup untuk mengubah fungsinya. Unit test statis UI dan regresi Dashboard Admin dijalankan pada GitHub Actions; uji tampilan Dark/Light, desktop/HP dan klik tautan tetap dilakukan pemilik sesudah pull. Tidak perlu impor SQL.
