# A10-1 — Checkpoint Laporan Akademik Siswa dan Monitoring Pimpinan

Tanggal: 3 November 2026

## Sasaran tahap

Mengimplementasikan laporan akademik yang dapat dibaca Siswa secara **pribadi** dan Pimpinan secara **baca-saja**, mengikuti Master 03, 06, 08, 09, 12, 13, 16 v2.5. Tahap ini tidak membuat Ujian Semester baru, tidak membuat rata-rata Nilai Akhir Semester, dan tidak menerbitkan sertifikat.

## Rute dan halaman

| Peran | Endpoint GET | Isi |
|---|---|---|
| Siswa | `/siswa/laporan` | Pilihan Mapel–Semester yang sudah dibuka melalui hak katalog siswa |
| Siswa | `/siswa/laporan/{paketId}` | Detail TP, progres, komponen nilai serta Pra-Semester pribadi tanpa parameter siswa_id |
| Pimpinan | `/pimpinan/laporan` | Pilihan paket Gen–Mapel–Semester, paginasi siswa, progres TP per siswa |
| Pimpinan | `/pimpinan/laporan/{paketId}/siswa/{siswaId}` | Pratinjau detail yang sama, khusus siswa yang Gen-nya sesuai paket |

- Service tunggal `LaporanAkademikService` membaca data, tidak menulis/menghitung ulang nilai; Controller menjaga request, peran, response.
- `status_progres=tuntas` **belum cukup**; untuk progres semester dihitung tuntas bila ada percobaan **Sumatif selesai_sah** pada TP NonEsensial yang sama. TP Esensial tetap muncul tanpa skor dan tidak menjadi penyebut.
- Laporan menampilkan skor Diagnostik sah, Resume/HOTS yang sudah tercatat, Sumatif Normal/Remedial sah, Sumatif terbaik dari `nilai_tp`, serta nilai TP dan status Final/Sementara. Skor 0 harus tampil sebagai 0,00; nilai NULL sebagai tanda —.
- Pra-Semester menggunakan `PenilaianPraSemesterService::ringkas` dan ditampilkan **terpisah**: KKM, Normal, Remedial, terbaik, status; tanpa angka nilai akhir semester.
- Semester sebelum `semester_mulai` diberi status **Dilewati** dan tidak diisi progres 100%. Semester tanpa TP NonEsensial juga tidak otomatis dianggap 100%.
- Siswa tidak dapat memilih ID siswa lain; Pimpinan tidak mempunyai rute POST laporan atau tombol mutasi nilai.

## Berkas

- Baru: `app/Services/LaporanAkademikService.php`, `app/Controllers/SiswaLaporan.php`, `app/Controllers/PimpinanLaporan.php`.
- Baru: `app/Views/siswa/laporan_daftar.php`, `app/Views/pimpinan/laporan.php`, `app/Views/laporan/detail.php`.
- Diubah: `app/Config/Routes.php`, `app/Services/NavigasiService.php`, `public/assets/css/app.css`, `public/assets/css/emerald-glass.css`.
- Baru: `tests/unit/LaporanAkademikDatabaseTest.php` dan `.github/workflows/qa-a10-laporan.yml`.
- Master 03, 06, 09, 12, 13, 16 diselaraskan. Keputusan Pra-Semester bukan syarat/nilai sertifikat tetap di Master 09 dan PR #110.

## Audit database lokal

Sumber baca-saja: **dump privat** `sla_sks_v4 (15).sql` dari pengguna (ekspor 03-11-2026, MariaDB 10.4.32). Kolom yang dibutuhkan diperiksa pada DDL: `siswa`, `pengguna`, `ref_angkatan`, `ref_mapel`, `penugasan_paket`, `paket_semester`, `bab`, `tp`, `progres_tp`, `nilai_tp`, `percobaan_ujian`, `ujian_pra_semester` dan `pengaturan`. Nama siswa, akun, hash dan data privat tidak dimasukkan dalam GitHub.

**SQL tambahan: tidak ada.** Tidak ada reset, migrasi, Seeder, edit .env atau perubahan histori siswa.

## QA dan penerimaan

- PHPUnit menggunakan SQLite sintetis anonim: monitoring Pimpinan, progres wajib bukti Sumatif, semester dilewati, angka 0/NULL, hasil remedial, Pra-Semester terpisah, pembatasan siswa sendiri/Gen/role.
- Workflow `QA A10 Laporan Akademik Siswa dan Pimpinan` menjalankan `php -l` untuk kode PHP dan PHPUnit `LaporanAkademikDatabaseTest.php`.
- Uji browser **XAMPP** (masih diperlukan): login sebagai Siswa dan Pimpinan, pilih semester/Gen, lihat progres dan perincian nilai, periksa nama siswa tidak silang, tampil Dark/Light, HP 360/390px dan Mode Dokumentasi.
- Keputusan final QA bukan hanya hijau CI; pemilik memberi umpan balik screenshot dari localhost. PR `feature/a10-1-laporan-siswa-pimpinan-20261103` Draft, tidak digabung `main` tanpa review.

## Tahap sesudah A10-1

Jika hasil laporan ini PASS di XAMPP, lanjutkan fondasi **A11 Sertifikat PDF/QR** sesuai keputusan final tanpa syarat dan nilai Pra-Semester, atau isi monitoring lanjutan yang secara eksplisit diminta pemilik. Jangan melompat ke dashboard akhir atau menambah Ujian Semester baru.
