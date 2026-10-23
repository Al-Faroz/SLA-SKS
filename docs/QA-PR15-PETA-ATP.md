# QA PR #15 — Peta ATP enam semester (v2.4)

PR ini **baca-saja** dan sengaja berstatus Draft sampai uji PHP dan browser lulus.

## Syarat
- Gunakan **clone/checkout branch PR #15** di lingkungan uji terpisah. Jangan mengganti kode aplikasi kerja sebelum PR direview.
- Database uji `sla_sks_v3` hasil impor K5-0010, **jangan** ubah `.env` produksi atau database `sla_sks_v2`.
- Tidak ada SQL update atau reset dalam PR ini.

## Perintah pengujian (Windows XAMPP, dari folder checkout uji)

```bat
G:\xampp\php\php.exe -l app\Services\PetaTpService.php
G:\xampp\php\php.exe -l app\Controllers\GuruPetaTp.php
G:\xampp\php\php.exe -l app\Views\guru\peta_tp.php
G:\xampp\php\php.exe -l tests\unit\PetaTpServiceTest.php
G:\xampp\php\php.exe vendor\bin\phpunit tests\unit\PetaTpServiceTest.php
```

Jalankan `G:\xampp\php\php.exe spark routes` lalu pastikan **GET guru/peta-atp** berfilter `auth:guru`.

## Pengujian browser yang belum dikerjakan
1. Guru aktif dengan G1 membuka `/guru/peta-atp`. Gen–Mapel di daftar harus sesuai penugasan `penugasan_guru_gen`, tidak berdasarkan `ref_mapel.guru_id`.
2. Pilih penugasan berikutnya: GET `?pilihan=1`, data enam semester berubah sesuai G1 terpilih. Pilihan `?pilihan=999`, nilai negatif/nonangka harus ditolak dan tampil pesan di beranda Guru.
3. Paket draf tanpa TP: tampil keadaan kosong yang benar; semester 4 dengan data hasil konversi memperlihatkan TP (jika Guru login memiliki G1 terkait).
4. Guru tanpa G1: informasi penugasan kosong, tidak ada akses data Guru lain.
5. Admin, Siswa dan tamu: endpoint Peta ATP ditolak (filter dan Service). Periksa tidak terjadi kebocoran data G1 lintas peran.
6. Desktop 1366px, laptop dan HP 360px: tabel menggulir, selector dapat diakses keyboard, breadcrumb dan tautan berfungsi.
7. Penilaian dan pengeditan belum ditampilkan/tidak ada aksi palsu.

## Hasil terverifikasi dari laporan pengguna
Pada **MariaDB lokal `sla_sks_v3`**: 37 pengguna, 17 siswa, 20 Mapel, 3 CP, 1 paket, 1 TP; semua lima cek relasi bernilai 0; jumlah FK 81; kolom `tp.paket_semester_id` dan `tugas_hots_materi.materi_id` ada; tabel BAB tidak ada.

**PASS di GitHub Actions:** lint PHP, PHPUnit 5 tes (6 assertions), dan smoke MySQL sintetis yang membuktikan isolasi data Gen–Mapel, TP, serta semester kosong. Lihat run [39909166387](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39909166387).\n\n**Belum lulus:** eksekusi Service melalui sesi CI4 penuh terhadap database uji `sla_sks_v3`, serta pengujian browser desktop/mobile. Smoke MySQL memakai subset skema sintetik; ini bukan pengganti uji seluruh dump atau autentikasi nyata. Data pribadi tidak dicommit.
