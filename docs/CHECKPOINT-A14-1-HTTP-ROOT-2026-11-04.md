# Checkpoint A14-1 — Pengamanan ROOT dan QA HTTP Anonim
**Tanggal:** 4 November 2026  
**Acuan:** Master 14 (keamanan), 15 (instalasi/operasional), 16 (E2E-059/064/065/070/071).  
**Branch:** `fix/a14-1-pengamanan-root-http-20261104` · **PR:** #129 (Draft, di atas #128).

## Masalah yang diperiksa
Struktur sah tetap menaruh `index.php` dan `.htaccess` di **ROOT** sejajar `app/`, bukan di `public/`. `public/assets/` adalah aset statis. Sebelum perbaikan, deny Apache telah mencakup `app/`, `vendor/`, `writable/`, `database/`, `docs/`, dan `tests/`, tetapi **belum memblokir secara eksplisit** `scripts/`, `tools/`, `builds/`, semua dotfile/backup, PHP pada `public/assets/`, dan `public/index.php`. Ada skrip CLI pemutakhiran template ATP di `scripts/` yang tidak boleh dieksekusi dari HTTP.

## Perubahan nyata
- `.htaccess`: tambah penolakan Apache (`F`) untuk direktori internal, dotfile/backup, `public/` di luar aset/favicon/robots serta PHP/shell pada aset; *rewrite* ke front controller ROOT tetap dipertahankan.
- `scripts/perbarui_template_atp_v25_2.php`, `scripts/perbarui_template_atp_v25_3.php`: blokir ketika `PHP_SAPI` bukan `cli`. Tidak mengubah logika/isi template ATP.
- `scripts/qa/http_security_smoke.php`: pemeriksa status HTTP pada **localhost** tanpa login, tanpa POST, tanpa mencetak body respons; URL eksekusi skrip pemeliharaan sengaja **tidak dipanggil**. Memastikan aset statis masih terbaca, file sensitif 403/404, panel anonim tidak dapat dibuka, halaman Kebijakan Privasi 200 bila terbit atau 503 bila belum.
- `tests/unit/A14RootHttpProtectionTest.php`: cek konfigurasi root, blokir sumber, CLI guard dan operasi audit baca-saja.
- `.github/workflows/qa-a14-1-pengamanan-root-http.yml`: PHP lint, PHPUnit, dan pengujian HTTP melalui Apache mod_rewrite pada runner CI dengan konfigurasi kosong/sintetis. Hasil per commit perlu dilihat di Actions.

## Tidak diubah
- `.env` pengguna, `app/Config/Database.php`, aturan bisnis, skema SQL, migration/seeder, alamat localhost, file `index.php`, data siswa, sertifikat atau `writable/`.
- Tidak melakukan deploy hosting; keberhasilan CI Apache tidak sama dengan lulus XAMPP pengguna.

## Uji XAMPP wajib
Setelah PR ini tersedia pada branch kerja dan perubahan lokal disimpan:
```bat
cd /d G:\xampp\htdocs\sla-sks
php scripts/qa/http_security_smoke.php http://localhost/sla-sks/
```
Script hanya HTTP GET anonim. **Jangan** menguji akses langsung `/scripts/perbarui_template_atp_v25_3.php` melalui browser, karena pada server yang belum dilindungi itu berpotensi menjalankan skrip pemutakhiran. Gunakan hasil smoke yang aman dan verifikasi login/tema/aset di Chrome.

Hasil yang diharapkan: 0 FAIL, halaman `/masuk` dan CSS/JS/logo tetap 200, file internal 403/404. Jika `app.css` gagal 200, hentikan penandaan FIX dan kirim hasilnya untuk diperbaiki. Jika aturan Apache tidak aktif pada hosting nanti, jangan memaksa deploy; konfigurasi host harus ditinjau terpisah.
