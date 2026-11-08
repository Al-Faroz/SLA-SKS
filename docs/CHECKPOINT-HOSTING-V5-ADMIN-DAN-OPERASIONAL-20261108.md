# Checkpoint hosting — database v5, bootstrap Admin privat, dan SOP operasional

Tanggal: 8 November 2026. Tahap persiapan, **BELUM DEPLOY ke shared hosting**.

## 1. Batas data dan status yang sudah dibuktikan

- **Database pengembangan:** `sla_sks_v4` di XAMPP, tetap dipakai oleh `.env` lokal dan **tidak diubah** untuk persiapan hosting.
- **Calon produksi:** `sla_sks_v5`, klon v4 yang dibersihkan melalui berkas privat milik pemilik: `V5-0001-DATA-GEN2025` dan `V5-0002-FAQ-DRAF`. SQL privat/dump/Excel **tidak masuk GitHub**.
- Pemilik telah menjalankan 57 kueri QA baca-saja (dieksekusi tanpa error; **bukan berarti 57/57 PASS**). Ringkasan SQL yang benar-benar dikirim dan diperiksa: marker V5=2, CP=15, penugasan G1 GEN2025=17, siswa GEN2025 semester mulai 5=17, FAQ terbit=1, FAQ draf=27, BAB=0, TP=0, percobaan ujian=0, sertifikat=0.
- Akun Admin historis `pengguna.id=1` tetap ada dengan status `nonaktif` karena menjadi pemilik FK CP/FAQ/data referensi; **jangan hapus dan jangan aktifkan kembali password development**. Admin produksi dibuat sebagai pengguna BARU saat hosting sudah siap.
- Pimpinan dibuat kemudian oleh Admin melalui menu Pengelola setelah login di hosting; **tidak dibuat sebagai akun default atau data palsu**.
- Belum ada hasil uji restore, PHP/MariaDB versi hosting, HTTPS, izin folder, autentikasi hosting, atau pemeriksaan HTTP privat di server target; jangan menandai go-live PASS.

## 2. Generator Admin produksi (tanpa SSH hosting)

**Alasan:** `php spark sla:admin-awal` menolak membuat Admin jika tabel `pengguna` tidak kosong. Data Guru/Siswa GEN2025 harus tetap ada, jadi **jangan menjalankan/merombak perintah lama tersebut**.

Perintah baru `sla:admin-hosting-sql` menyiapkan **file SQL privat** di komputer operator, bukan menulis ke DB XAMPP. Jalankan **hanya setelah snapshot final v5 sudah diimpor di database hosting yang dipilih** (jangan saat masih mempersiapkan salinan).

Pada Windows XAMPP, setelah update branch yang sudah ditinjau/di-merge:

```bat
cd /d G:\xampp\htdocs\sla-sks
G:\xampp\php\php.exe spark sla:admin-hosting-sql
```

1. Ketik username dan nama Admin produksi melalui prompt lokal. **Jangan menulis sandi sebagai argumen CMD.** Sandi sementara 32 karakter heksadesimal dihasilkan CSPRNG dan dicetak **sekali**; catat di tempat privat.
2. Perintah membuat `writable/private/setup/admin_produksi_<acak>.sql` dengan `password_hash(PASSWORD_DEFAULT)`; **tidak memuat sandi plaintext** dan tidak membuat akun pada database `sla_sks_v4` atau `sla_sks_v5` lokal.
3. Di phpMyAdmin hosting, **pilih database hosting yang benar**, lalu impor file yang dihasilkan. Nama database hosting boleh berbeda/berawalan prefix cPanel karena SQL **tanpa perintah USE**.
4. Preflight pada SQL menolak kondisi tidak sesuai: belum ada kedua marker V5, CP bukan 15, Admin lama bukan id 1 nonaktif, sudah ada Admin aktif, username bentrok, atau marker bootstrap sudah terpasang. Tidak menghapus data dan tidak mengubah sandi akun lain. Jika salah satu kueri ERROR: **STOP**, jangan mengimpor ulang tanpa pemeriksaan.
5. Setelah impor berhasil, hasil akhir harus: `admin_aktif=1`, `admin_wajib_ganti_sandi=1`, `penanda_admin_produksi=1`. **Simpan password sementara secara privat** lalu hapus **file SQL generator** dari komputer setelah selesai/tercatat dan jangan menaruhnya di folder upload situs, repo, email atau chat.
6. Login via domain **HTTPS**, ubah password Admin saat login pertama, lalu buat akun Pimpinan melalui menu Admin/Pengelola dan lengkapi identitas/aset madrasah. Jangan membuka akses pengguna sebelum syarat hosting di bawah PASS.

**Batas keamanan:** generator hanya perintah CLI Spark, tidak menambah endpoint web/registrasi. SQL bergantung pada `CREATE TEMPORARY TABLE` dan CHECK yang aktif (teruji dalam pola patch v5 lokal MariaDB 10.4.32); jika akun hosting tidak memiliki privilege TEMPORARY TABLE, jangan mematikan guard—laporkan dan gunakan prosedur DB terkontrol oleh operator hosting. Password diganti setelah masuk. Tidak boleh menyimpan SQL berisi hash pada GitHub.

## 3. Kompatibilitas target (wajib cek di lingkungan hosting NYATA)

| Pemeriksaan | Target | Status per 8 Nov |
|---|---|---|
| PHP | >=8.2, cocok `composer.lock`, `intl`, `mbstring`, `mysqli/mysqlnd`, `curl`, `gd`, `zip`, `fileinfo`, `dom/xml`, `openssl`, `opcache` | BELUM diuji di hosting |
| Database | MariaDB/MySQL target: InnoDB, utf8mb4, FK, CHECK, JSON/ENUM sesuai dump v5, privilege `CREATE TEMPORARY TABLES` untuk bootstrap | BELUM diuji di hosting |
| Web server | Apache/LiteSpeed dengan `.htaccess` dan `mod_rewrite`; root `index.php` tetap sejajar dengan `app/` | BELUM diuji di hosting |
| HTTPS | Sertifikat valid, baseURL domain HTTPS, cookies Secure, QR ke domain final | BELUM diuji di hosting |
| Aset privat | URL ke `/.env`, `/app/`, `/vendor/`, `/writable/`, `/database/`, `/docs/`, `/tools/`, `/scripts/` **403/404**, tanpa isi file | BELUM diuji di hosting |
| Storage | `writable/` dapat ditulis oleh PHP, tidak dapat dibaca anonim; batas upload PDF/foto/Word, RAM dan disk memadai | BELUM diuji di hosting |

**Jangan membuka `phpinfo()` publik tanpa proteksi.** Versi PHP/ekstensi dapat dilihat di panel hosting atau CLI PHP bila SSH tersedia. Cek nomor versi MariaDB di panel atau SQL `SELECT VERSION();`. Jangan menebak dari versi XAMPP 10.4.32.

## 4. Cadangan dan pemulihan

**Sebelum deployment dan setiap update:** ambil (a) ekspor SQL lengkap struktur+data, (b) salinan `writable/` yang meliputi upload, foto, TTD privat dan PDF sertifikat, serta (c) aset identitas/branding yang bukan dari Git. Pastikan tanggal dan checksum serta penyimpanan terpisah dari direktori web/repo. Tidak mencantumkan sandi DB atau identitas siswa pada tiket terbuka/GitHub.

**Uji restore hanya di lingkungan terisolasi** dengan nama database dan folder privat berbeda:
1. Siapkan database uji **kosong**, impor cadangan, verifikasi tabel/marker/CP/siswa/Guru dan integritas FK.
2. Pulihkan folder `writable/` yang sepasang dengan dump, cek keberadaan dokumen privat dan hak akses; jangan memaparkan dokumen ke URL publik.
3. Catat hasil dan baru nyatakan rencana restore tervalidasi. **Jangan mengganti DB produksi yang sudah menerima data baru dengan backup lama** tanpa rekonsiliasi; DDL MySQL/MariaDB tidak dapat dijamin rollback otomatis.
4. Untuk v5 calon produksi yang masih kosong akademik, ambil **backup baseline setelah Admin berhasil dibuat dan sandi diganti**, bukan dari dump lama yang masih memuat data simulasi.

## 5. Urutan kerja yang tidak perlu mengulang pengujian lama

1. **Sekarang:** review PR generator Admin produksi dan pemeriksaan CI. **Tidak ada SQL atau perintah CMD yang harus dijalankan pengguna sekarang.**
2. **Menjelang deployment:** dapatkan spesifikasi paket hosting (versi PHP, MariaDB, Apache/LiteSpeed, SSH atau tanpa SSH, HTTPS, disk/backup). Review aset privat dan kebijakan privasi terbit.
3. **Saat hosting tersedia:** upload kode (tanpa `.env`, dump privat dan `build/` lokal), impor **snapshot privat v5 yang sudah bersih**, buat DB config **khusus hosting**; generator Admin dari CMD lokal → SQL privat via phpMyAdmin. `.env` XAMPP tetap untuk v4.
4. **Gerbang go-live target:** lakukan uji ringkas hanya di hosting baru—login Admin/ganti sandi, data Guru/Siswa, FAQ, rute root, 403/404 file privat, SQL backup/restore di salinan, dan PDF/QR yang ada dasar datanya. Bukan mengulang seluruh E2E XAMPP yang pernah dinyatakan FIX.
5. **Setelah hosting aktif:** akun Pimpinan dibuat Admin; FAQ draf 27 ditinjau dan diterbitkan satu per satu bila sah. Jangan otomatis menerbitkan atau mengisi CP yang belum punya sumber resmi.

## 6. Berkas implementasi

- `app/Services/AdminHostingSqlService.php` — generator SQL privat dengan preflight database v5.
- `app/Commands/BuatAdminHostingSql.php` — perintah Spark CLI, satu kali pembuatan berkas dan sandi acak.
- `tests/unit/AdminHostingSqlServiceTest.php` — validasi format/guard dan larangan kredensial plaintext.
- `.github/workflows/qa-hosting-bootstrap-v5.yml` — lint & tes tanpa data siswa.
- **Tidak ada SQL privat, password, hash nyata, dump v5, Excel GEN2025, perubahan database, atau perubahan `.env` dalam PR.**
