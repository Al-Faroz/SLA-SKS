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
