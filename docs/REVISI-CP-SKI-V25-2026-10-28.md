# Audit CP SKI dan patch v2.5 — 28 Oktober 2026

## Basis data nyata
- Dump privat yang diperiksa: sla_sks_v4, dibuat 28-10-2026 pukul 02.45, MariaDB 10.4.32, marker terakhir V4-0001-R2.
- SKI: ref_mapel.id=4; kurikulum KBC id=1; Fase D id=1; CP operasional cp_mapel.id=1.
- Referensi aktif semula: FD-SKI-01 / Pemahaman Konsep (ref_cp_versi.id=2); FD-SKI-02 / Keterampilan Proses (id=3).
- TP historis menautkan versi Pemahaman Konsep lama pada tp_cp. Referensi/riwayat lama wajib dipertahankan.
- Kolom bab.nama_bab dan tp.nama_tp masih VARCHAR(35); perubahan ke 50 karakter merupakan patch terpisah, bukan bagian CP.

## Perbaikan
- SQL versi V4-0002-CP-SKI-PK membuat identitas referensi baru KBC25-SKI-D-PK dan versi aktif Pemahaman Konsep berisi **10 poin rumusan persis dari pemilik**.
- Versi Pemahaman Konsep lama menjadi diarsipkan, bukan dihapus. Elemen **Keterampilan Proses tetap aktif** karena tidak diminta dihapus. Satu cp_mapel tetap menjadi induk operasional SKI.
- Sumber URL, otoritas, dokumen CP dan tanggal berlaku versi baru disalin dari versi PK lama yang sebelumnya sudah aktif; bukan metadata baru buatan AI. **Konfirmasi kesesuaian metadata sumber ini sebelum penggunaan resmi.** KMA 1503/2025 tetap sebagai dasar kurikulum, sedangkan metadata CP lama menyebut SK Dirjen Pendis 9941/2025.
- Penerbitan CP oleh Admin kini secara otomatis menghubungkan versi terbit ke cp_mapel dan mengarsipkan versi aktif sebelumnya untuk elemen yang sama (bukan elemen lain).
- Admin melihat CP operasional per Kurikulum–Mapel–Fase dan daftar elemen aktif, sementara editor versi historis tetap tersedia.
- Parser ATP membandingkan versi, identitas, elemen dan rumusan dari sheet REFERENSI CP dengan server. **Workbook yang diunduh sebelum revisi CP harus diunduh ulang** agar bisa diimpor.
- Tidak mengubah data TP, nilai, siswa, akun, materi, atau percobaan historis.

## Cara menerapkan di XAMPP
1. Ekspor backup penuh database sla_sks_v4 dan backup writable/ ke lokasi privat sebelum perubahan.
2. Pastikan marker V4-0001-R2 sudah ada dan V4-0002-CP-SKI-PK belum ada. Pilih database sla_sks_v4 pada phpMyAdmin.
3. Impor **sekali** database/sql/updates/V4-0002_CP_OPERASIONAL_SKI_PK.sql dengan menu Import di phpMyAdmin. Bila gagal, **berhenti**, jangan lanjut atau jalankan ulang tanpa audit kondisi.
4. Jalankan database/sql/qa/V4-0002_CP_SKI_VERIFIKASI_READONLY.sql pada tab SQL dan periksa: PK baru aktif, PK lama diarsipkan, Keterampilan Proses aktif, histori tp_cp tetap.
5. Pull branch aplikasi v2.5 CP SKI. Periksa Admin → Referensi Kurikulum & CP → CP Operasional, lalu login Guru SKI dan **unduh ulang Excel ATP**.
6. Uji file lama ditolak karena versi/rumusan CP sudah berubah. Uji file baru dapat dipratinjau; konfirmasi simpan BAB/TP hanya sesudah memeriksa hasil pratinjau dan melakukan backup.

## Status uji
- GitHub Actions melakukan PHP lint, tes parser, dan simulasi SQL MariaDB dengan fixture sintetis anonim.
- Belum dinyatakan FIX sebelum uji nyata MySQL/MariaDB XAMPP oleh pemilik. Dump privat tidak disimpan dalam GitHub.

## Koreksi keputusan pemilik — 28 Oktober 2026, setelah uji Excel
**Versi V4-0002 telah diimpor pengguna dan bukti Excel masih menunjukkan dua elemen aktif.**
Pemilik menetapkan khusus untuk **SKI KBC Fase D hanya satu CP aktif**:
`KBC25-SKI-D-PK` dengan elemen `Pemahaman Konsep`, berisi 10 butir rumusan
yang sudah dipasang pada V4-0002. Elemen `Keterampilan Proses` **tidak aktif lagi**.

Patch incremental **`database/sql/updates/V4-0003_SKI_SATU_ELEMEN_CP_AKTIF.sql`**
mengubah status versi lama `FD-SKI-02` menjadi `diarsipkan` tanpa
menghapus ref_cp, ref_cp_versi atau cp_mapel_elemen. Versi CP baru tetap
aktif dan tidak digandakan. **Jangan impor ulang V4-0002.**
Service penerbitan CP Admin kini menolak penerbitan SKI KBC Fase D
selain `KBC25-SKI-D-PK / Pemahaman Konsep`. Mapel lain tetap bisa
memiliki banyak elemen sesuai Master Document.

Dalam dump audit, **TP SKI id=1 sudah berstatus arsip**, tetapi memiliki
`progres_tp` untuk 2 siswa, satu `versi_konten_tp` dan berbagai
soal/materi/jawaban/ujian. Meskipun pengguna memperbolehkan penghapusan
TP lama, **tidak ada penghapusan otomatis**, sebab penghapusan TP ini
akan membutuhkan penghapusan progres serta riwayat yang ditautkan banyak FK.
Pakai mekanisme TP baru v2.5; histori tetap terpelihara dan TP arsip
tidak boleh diedit/diaktifkan lagi. Pemeriksaan detail tersedia sebagai
SQL baca-saja `database/sql/qa/V4-0003_SKI_SATU_ELEMEN_VERIFIKASI_READONLY.sql`.

### Petunjuk penerapan
1. Cadangkan `sla_sks_v4` dan direktori writable/; pilih database `sla_sks_v4`.
2. Pastikan marker `V4-0002-CP-SKI-PK` sudah ada, dan marker
   `V4-0003-SKI-CP-TUNGGAL` belum ada.
3. Impor **sekali** `V4-0003_SKI_SATU_ELEMEN_CP_AKTIF.sql`
   melalui phpMyAdmin. Bila ada error, hentikan, jangan jalankan ulang.
4. Jalankan QA baca-saja; hasil: **tepat 1 aktif** (`Pemahaman Konsep`),
   KP diarsipkan, TP/progres historis tetap terpasang.
5. Ambil branch CP tunggal, login Guru SKI, unduh **ulang** Excel ATP.
   Sheet REFERENSI CP hanya boleh berisi satu baris CP aktif.
   Excel unduhan lama dengan dua CP ditolak saat impor.

Perubahan ini mengoreksi kebijakan khusus SKI saja dan
tidak mengubah desain template ATP atau kebijakan CP mapel lain.
