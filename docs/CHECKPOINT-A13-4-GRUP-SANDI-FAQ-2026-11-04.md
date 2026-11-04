# Checkpoint A13-4 — Grup Informasi, Revisi Sandi dan Draf FAQ

Tanggal 4 November 2026. Branch `feature/a13-4-grup-informasi-revisi-sandi-faq-20261104`, bertumpuk pada A13-3 (`feature/a13-3-hubungi-admin-kontak-resmi-20261103`, draft PR #124). **Tidak digabung `main` sebelum review serta uji XAMPP.**

## Keputusan pemilik
1. Menu Pengumuman, Kelola Pengumuman, Kelola FAQ, Bantuan dan Hubungi Admin menjadi satu grup **Informasi & Bantuan**. Admin/Guru tetap memiliki item kelola sesuai hak; Siswa/Pimpinan hanya baca.
2. Reset Guru/Pimpinan/Siswa memakai **sandi default**. Struktur lama hanya menyimpan hash sandi awal pribadi, sehingga aplikasi tidak dapat memulihkan nilai sandi terdahulu. Untuk menghindari sandi default rekaan/tertanam dalam repo, Admin wajib memasukkan sandi default yang disepakati sekolah pada form reset dan mengonfirmasinya. Tidak ada sandi default universal, generator acak, ataupun penyimpanan plaintext pada mode non-Admin.
3. Akun dengan role Admin, termasuk yang memiliki role rangkap Admin+Guru/Admin+Pimpinan, **reset acak** dan **tetap wajib ganti sandi**. Reset mandiri Admin tetap ditolak.
4. Siswa/Guru/Pimpinan **dianjurkan** mengganti sandi awal melalui form langsung di Profil, tetapi **tidak dipaksa**. Penanda `wajib_ganti_sandi` pada DB dipertahankan: dibaca sebagai `sandi_direkomendasikan` untuk non-Admin, tetap sebagai pengunci bagi pemegang Admin. Tidak ada SQL, migrasi, seeder, perubahan `.env`, root/front controller atau nilai/ujian siswa.
5. FAQ resmi yang diterbitkan oleh Admin tetap berawal dari Draf dalam database. 28 teks saran disediakan dalam `docs/DRAF-FAQ-RESMI-SLA-SKS-2026-11-04.md` dan **tidak dimasukkan ke database** tanpa tindakan Admin.

## Fitur dan berkas
- `AuthService` menghitung kebijakan sandi efektif per daftar peran; sesi lama/izin tetap diperiksa. Login non-Admin menuju beranda tanpa gerbang sandi, dengan anjuran pada respons awal dan Profil hingga sandi diganti.
- `PenggunaAdminService` reset `acak` untuk Admin, `default` manual untuk non-Admin. Controller dan dua view Reset mengonfirmasi input, tidak menampakkan sandi default di hasil, CSRF dipertahankan. Versi sesi meningkat; audit tanpa plaintext.
- `Akun` dan Profil menggunakan POST ganti sandi yang sama, bukan membuat jalur baru; non-Admin menyunting sandi dari Profil. Ubah sandi memperbarui sesi aktif.
- `NavigasiService` memproduksi grup peran yang dibuka ketika aktif, menggunakan komponen `details/summary` sidebar lama, CSS Dark/Light/responsif dan ikon lokal.
- `PanduanBantuanService`, teks form akun, Master 02/03/11/12/13/14/16, `docs/README.md`, `docs/PENDING-DECISIONS.md` diselaraskan.
- PHPUnit kebijakan sandi/reset + grup menu/FAQ; regresi Panduan, Kontak, Pengelola, Profil, UI Admin pada workflow `QA A13-4 Informasi dan Revisi Sandi`.

## QA lokal yang masih harus dilakukan
1. Backup DB XAMPP dan `writable/` sebelum menarik branch bertumpuk. A13-4 tidak punya patch SQL sendiri; bila A13-2 belum diterapkan, patch V4-0012 harus diuji menurut checkpoint A13-2.
2. Login Guru, Pimpinan, Siswa yang `wajib_ganti_sandi` DB-nya 1: langsung ke Dashboard, terlihat anjuran; form Profil bisa ganti sandi dan anjuran hilang. Login Admin dengan flag 1 masih harus mengganti sandi.
3. Reset sandi akun non-Admin oleh Admin melalui isian default & konfirmasi, lalu logout/login target; periksa sesi lama dicabut. Reset akun Admin lain acak tampil sekali dan wajib ubah; reset akun sendiri ditolak.
4. Uji grup Informasi untuk Admin (5 anak), Guru (4), Siswa/Pimpinan (3); seleksi aktif, collapse sidebar, Mode Dokumentasi dan HP. Periksa Admin/Guru tidak mendapat hak baru.
5. Salin beberapa FAQ Draf ke Kelola FAQ, sunting/terbitkan setelah ditinjau, periksa yang diterbitkan muncul di Bantuan dan yang Draf tidak tampil. Jangan mengimpor data asli ke repository.
6. Gunakan data anonim untuk QA, lalu sampaikan tangkapan layar dan error aktual. **Status belum FIX sebelum pengujian tersebut.**

## Pekerjaan tertunda
Notifikasi internal per penerima dan badge unread tetap memerlukan **dump baru pasca V4-0012**; A13-4 bukan SQL notifikasi. Kebijakan Privasi publik tetap menunggu teks resmi disahkan madrasah.
