# Audit migrasi privat v3 → v4 (26 Oktober 2026)

## Sumber
File pemilik `sla_sks_v3 (4).sql` (phpMyAdmin 5.2.1, header 26 Oktober 2026 pukul 09.36, server MariaDB 10.4.32, PHP 8.2.12). Sumber ini mengandung identitas, NISN, password hash, token, jawaban, riwayat IP: **tidak boleh dipush** atau dimasukkan PR.

## Ringkasan dan dampak
Sumber memiliki 52 tabel, 37 pengguna, 17 siswa, 20 mapel, 3 `ref_cp`, satu paket semester, satu TP siap, empat Materi seluruhnya pada TP tersebut, 40 soal, dua percobaan Diagnostik. Pada pola baru, satu Mapel satu CP operasional tetapi SKI mempunyai rujukan CP bertema berbeda; v4 mempertahankan semuanya sebagai elemen/versi dalam `cp_mapel_elemen`. Empat Materi eksperimen v3 tetap ada untuk audit, **tidak dipilih otomatis** sebagai Materi aktif di v4.

## Strategi migrasi
1. `CREATE DATABASE sla_sks_v4` dengan nama baru dan **tanpa DROP**; impor salinan struktur+data dari dump v3. Database sumber v3 tidak disentuh.
2. Terapkan patch skema `V4-0001`: tambah CP operasional/elemen; tabel BAB; atribut TP (jenis, nama/deskripsi, BAB, urutan/KKM khusus); indeks maksimum satu Materi aktif per TP; nilai TP; Pra-Semester/bank; perluas engine ujian; checkpoint.
3. Status TP uji lama tanpa BAB ditandai **arsip** pada v4 dan Materi uji lama dinonaktifkan, snapshot/progres/riwayat asal tetap tercatat. Sistem baru harus mengecualikan TP arsip dari progres dan sertifikat v2.5.
4. Nilai KKM default Admin disimpan sebagai pengaturan yang **belum diisi** (NULL), bukan otomatis memutuskan nilai 80 dari contoh. Guru boleh KKM khusus.
5. Tidak ada tabel angka **nilai akhir semester**. Nilai TP dan Pra-Semester berdiri sendiri.
6. Foto/media yang didaftarkan sebagai nama berkas pada DB memerlukan salinan file fisik privat dari `writable/`, tidak terdapat dalam dump SQL.

## Pemeriksaan sebelum impor
- Backup database v3, direktori `writable/`, dan catat versi MariaDB/PHP.
- Pastikan target `sla_sks_v4` **belum ada**. Jika sudah ada, berhenti; jangan drop otomatis.
- Pastikan file SQL v4 privat cocok dengan header dump yang terakhir diterima dan checksum sumber. Dilarang menaruh dump/akun/credential pada branch GitHub.
- Jalankan impor melalui phpMyAdmin/CLI memakai user dengan hak CREATE DATABASE/TABLE/INDEX/FK.

## QA setelah impor (baca-saja)
```sql
SELECT versi FROM sla_sks_v4.schema_version ORDER BY diterapkan_pada DESC;
SELECT COUNT(*) AS jumlah_pengguna FROM sla_sks_v4.pengguna;
SELECT COUNT(*) AS jumlah_siswa FROM sla_sks_v4.siswa;
SELECT COUNT(*) AS jumlah_bab FROM sla_sks_v4.bab;
SELECT jenis_tp, status, COUNT(*) AS jumlah FROM sla_sks_v4.tp GROUP BY jenis_tp, status;
SELECT tp_id, COUNT(*) AS materi_aktif FROM sla_sks_v4.materi WHERE aktif=1 GROUP BY tp_id HAVING COUNT(*)>1;
SELECT m.kode AS mapel, COUNT(*) AS cp_operasional
FROM sla_sks_v4.cp_mapel cm JOIN sla_sks_v4.ref_mapel m ON m.id=cm.mapel_id
GROUP BY cm.kurikulum_id,cm.fase_id,m.id HAVING COUNT(*)>1;
SELECT COUNT(*) AS percobaan_historis FROM sla_sks_v4.percobaan_ujian;
SELECT COUNT(*) AS soal_historis FROM sla_sks_v4.soal;
```
Diharapkan akun/siswa/soal/attempt historis tetap, 0 duplikasi Materi aktif, 0 duplikasi CP operasional. BAB baru bisa kosong sampai Guru mengisi ATP pola baru; **0 BAB bukan kesalahan impor**.

## Keterbatasan
Pemeriksaan teks SQL **bukan bukti impor berhasil**. SQL DDL dapat melakukan implicit commit, bukan seluruhnya rollback transaksional. Jika ada galat atau MariaDB/MySQL berbeda implementasi generated column/CHECK, jangan memperbaiki dengan menghapus sumber v3; buat revisi bernomor dan uji ulang pada clone yang disetujui.

## Langkah setelah dokumen/DB
Adaptasi Excel ATP bermakro (tahap berikutnya), kemudian refactor CI4 MVC/Service/UI mengikuti [rencana](RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md).

## Hasil QA impor lokal — 27 Oktober 2026
Pemilik berhasil mengimpor berkas privat **R2** ke `sla_sks_v4` dengan **268 kueri**, lalu menjalankan SQL baca-saja pada phpMyAdmin. Hasilnya: `jumlah_tabel=58`, `jumlah_fk=126`, `versi_database=V4-0001-R2`, `pengguna=37`, `siswa=17`, `mapel=20`, `bab=0`, `materi_historis=4`, dan `percobaan_historis=2`.

**Status:** QA struktur awal dan retensi jumlah inti **PASS**. Belum membuktikan seluruh query/Controller CI4, retensi isi per baris, atau alur E2E; pengujian tahap aplikasi masih diperlukan. Tidak ada dump privat/identitas siswa yang boleh di-commit.
