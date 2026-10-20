# SQL SLA-SKS v2.3 — K1, K2 dan penugasan Guru per Gen

## Wajib: instalasi K1/K2 yang SUDAH dipakai pemilik

**JANGAN impor ulang K1 atau K2-0002.** Berkas `updates/0002_referensi_identitas.sql` dipertahankan persis seperti yang sebelumnya diimpor. **Jangan hapus data** Gen, mapel, Guru, akun, identitas sekolah, riwayat atau sesi.

1. Backup database SLA-SKS **dan** folder `writable/` ke lokasi privat; uji restore backup pada DB pengujian jika memungkinkan.
2. Tarik branch K2 yang diperbarui (PR #3) di localhost, tetapi **jangan** langsung menguji halaman baru sebelum patch SQL berhasil.
3. Di phpMyAdmin pilih database SLA-SKS yang sama. Jalankan:
   `SELECT versi FROM schema_version ORDER BY versi;`
   Sebelum impor harus ada `K1-0001` dan `K2-0002`, tetapi **belum** `K2-0003`.
4. Impor **hanya** `database/sql/updates/0003_guru_per_gen.sql`, sekali.
5. Ulangi query nomor 3; pastikan penanda `K2-0003` serta kedua tabel baru tersedia.
6. Login Admin lama. Buka `/admin/mapel-guru`, pilih Gen lalu tentukan Guru untuk setiap mapel. **Penugasan `ref_mapel.guru_id` lama TIDAK otomatis disalin ke Gen mana pun.** Gunakan nilai lama sebagai informasi historis bila perlu, lalu tetapkan Guru dengan sengaja.

**Penting:** DDL MySQL/MariaDB dapat melakukan *implicit commit*. Jika impor gagal di tengah jalan, **jangan impor ulang** membabi buta. Simpan pesan galat; periksa status dan restore backup sesuai prosedur.

## Instalasi baru (database BENAR-BENAR kosong)

Impor secara berurutan satu kali:
1. `install/k1_fondasi_akun.sql`
2. `updates/0002_referensi_identitas.sql`
3. `updates/0003_guru_per_gen.sql`

Jalankan `php spark sla:admin-awal` satu kali sesuai panduan K1. Tidak menggunakan CI4 Migration atau Seeder.

## Batas tahap K2

K2 sekarang menegakkan satu Guru aktif per pasangan Gen–Mapel, berlaku untuk seluruh semester pada Gen itu. Guru dengan peran Guru+Admin atau Guru+Pimpinan sah; akun tanpa role Guru dan akun nonaktif tidak boleh dipilih, termasuk melalui POST langsung. Riwayat pergantian tidak ditimpa. K2 belum membangun paket/BAB, pengelolaan siswa terpisah, impor akun atau reset akun; fitur tersebut dilaksanakan pada tahap berikutnya sesuai Master Document v2.3.

Pengujian nyata yang diperlukan: MySQL XAMPP lokal, kompatibilitas MariaDB hosting, PHPUnit dan pengujian browser/HP. PR tidak boleh digabung sebelum hasil tersebut diperiksa.
