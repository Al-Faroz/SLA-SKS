# Checkpoint A13-6 dan A13-7 — Implementasi setelah Audit Dump (18)

Tanggal: 4 November 2026. Branch `feature/a13-6-a13-7-implementasi-20261104` dan PR #128 (Draft, stacked di atas PR #127 dan #126). Tidak merge ke main.

## Bukti audit sebelum SQL
- File privat percakapan: `sla_sks_v4 (18).sql` dari XAMPP, bertanggal 04 Nov 2026 pukul 03.59, MariaDB 10.4.32.
- Database `sla_sks_v4` berisi 71 tabel; `schema_version` hingga `V4-0012-PENGUMUMAN-FAQ`.
- Tabel `pengguna`, `pengguna_peran`, `siswa`, `penugasan_guru_gen`, `pengaturan`, `log_aktivitas`, materi, penilaian, keputusan akses ulang, sertifikat, pengumuman/FAQ dan FK/indeks terkait telah diperiksa. Tidak ada tabel `notifikasi`, `kebijakan_privasi` maupun `riwayat_kebijakan_privasi` sebelumnya.
- Tidak ada dump privat, akun nyata, hash sandi, NISN atau data akademik masuk GitHub.

## Perubahan
- SQL: `database/sql/updates/V4-0013_NOTIFIKASI_PRIVASI.sql` menambahkan 3 tabel + marker versi. Additive tanpa ALTER/DELETE/DROP, tanpa Migration/Seeder. Backup DB dan `writable/` lalu impor satu kali; petunjuk di `database/sql/README.md`.
- A13-6: `NotifikasiService` memakai notifikasi per akun dan **peran aktif** dengan unique event key, transaksi sumber, daftar dan badge, POST tandai dibaca + CSRF, rute aman. Hak ke sumber dinilai ulang sebelum redirect, termasuk perubahan Guru G1. Sumber sah yang sudah dihubungkan: nilai Resume/HOTS dari Guru, permintaan dan keputusan akses ulang, TP NonEsensial Siap, sertifikat awal/pengganti, pembatalan Pra-Semester.
- Koreksi nilai siswa **belum** mempunyai Service koreksi final sesuai aturan v2.5. Notifikasi koreksi tidak akan dibuat otomatis melalui tabel kategori A/B/C yang dibatalkan.
- A13-7: `KebijakanPrivasiService` mendukung Draf, sunting (hanya Draf), versi/berlaku WIB, konfirmasi pengesahan, publikasi atomik, audit, checksum dan histori immutable. Admin punya daftar/editor; pembaca publik hanya mendapat Terbit yang berlaku (tanpa login). Belum ada naskah resmi disahkan yang diserahkan pengguna; tidak ada teks otomatis. Status publik tetap 503 sebelum ada.
- UI: Notifikasi header dan menu sendiri untuk semua role; Kebijakan Privasi pengaturan Admin, bukan tambahan grup Informasi & Bantuan (tetap lima item). Aset ikon bel ditambahkan lokal.

## QA
- `tests/unit/A13KontrakNotifikasiPrivasiTest.php`: kontrak event/role/versi/naskah.
- `tests/unit/A13NotifikasiPrivasiDatabaseTest.php`: SQLite sintetis, deduplikasi, isolasi penerima, izin sumber/Gen, baca, Draf/Terbit, histori.
- `tests/integration/SqlA13NotifikasiPrivasiSchemaCheck.php`: impor patch SQL asli di MariaDB ephemeral, FK, unique, marker; tanpa data asli.
- CI `QA A13-6 A13-7 Implementasi`: PHP lint, unit/regresi + SQL MariaDB. Hasil wajib diperiksa di PR yang terkini; jangan menyatakan browser XAMPP sudah dijalankan.
- Regresi UI Dark/Light, HP dan tautan peran, serta transaksi akademik manual XAMPP masih diperlukan.

## Langkah pengguna
1. Backup database `sla_sks_v4` dan `writable/`.
2. Pull branch dan di phpMyAdmin cek marker V4-0012 ada, V4-0013 belum, tiga tabel baru belum tersedia.
3. Impor hanya `V4-0013_NOTIFIKASI_PRIVASI.sql` satu kali, cek marker/tabel sesuai `database/sql/README.md`.
4. Login empat role, uji notifikasi dari akun **simulasi**; jangan mengubah nilai nyata untuk tes.
5. Admin masuk `/admin/kebijakan-privasi`, siapkan teks resmi sekolah yang telah disahkan, tanggal berlaku dan versi. Jika teks belum sah, biarkan Draf; halaman publik `/kebijakan-privasi` tetap 503.
6. Laporkan tangkapan layar/galat uji lokal untuk iterasi. Tidak ada perubahan .env, localhost URL, index.php atau .htaccess.

Status: **implementasi tersedia di branch review, belum FIX sebelum impor/QA XAMPP dan naskah resmi untuk penerbitan publik**.
