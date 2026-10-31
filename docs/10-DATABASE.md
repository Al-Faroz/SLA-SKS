# 10 — Model Database dan Kontrak Dump SQL

## 1. Aturan deployment database — WAJIB
**Tidak memakai CI4 migrations dan seeders dalam alur pembangunan, instalasi, atau pembaruan.** Database lokal **MySQL** dan hosting **MariaDB**. Database dibuat dan diubah melalui **impor berkas `.sql`** versi yang disediakan proyek: `database/sql/install/` untuk instalasi dari nol, `database/sql/updates/` untuk pembaruan data/skema **incremental**. Setiap file SQL harus bisa diimpor dari phpMyAdmin pada shared hosting, selain lewat CLI. Jangan menggunakan perintah `php spark migrate` / `db:seed` pada petunjuk pemasangan.

**Istilah:** `dump instalasi` adalah snapshot **struktur+data referensi bawaan tanpa data siswa produksi**. `update SQL` adalah patch bernomor yang mengubah skema atau data yang diperlukan **tanpa menjatuhkan/menghapus tabel atau nilai produksi**. Jangan menyamakan update dengan impor ulang dump penuh secara destruktif.

## 2. Kelompok tabel logis
**Audit v3 lokal per dump 26-10-2026:** 52 tabel, 37 pengguna, 17 siswa, 20 mapel, 3 `ref_cp`, 1 paket, 1 TP aktif lama, empat Materi pada TP itu, 40 soal dan dua attempt Diagnostik. Ini adalah basis **privat** dan bukan data yang boleh di-push.

**Kontrak v4:** `cp_mapel` (satu CP operasional per mapel/kurikulum/fase), `cp_mapel_elemen` (rujukan multi-elemen/versi `ref_cp` lama), `bab` (paket, nomor, kelas, nama50, deskripsi225; keunikan lintas semester berpasangan), `tp` (BAB, CP operasional, jenis esensial/non_esensial, nama50, deskripsi225, nomor urut BAB, kkm_khusus), `materi` maksimal satu aktif per TP, `nilai_tp` (nilai mandiri per TP), `ujian_pra_semester` dan `soal_pra_semester` (bank lintas BAB dan jadwal Admin), `percobaan_ujian` generik dengan tahap normal/remedial, serta checkpoint pada `progres_materi`.

**Data lama penting tetap ada:** pengguna, peran, siswa, mapel, kurikulum, referensi, Gen, penugasan guru, identitas, materi/soal/percobaan/snapshot. TP uji v3 yang memiliki **4 Materi** diarsipkan dan materi lamanya dinonaktifkan **hanya pada salinan v4** untuk menghindari pelanggaran ketentuan satu Materi aktif per TP. Tidak mengubah progres/percobaan historis. File fisik di `writable/` perlu backup terpisah.

## 3. Invarian SQL dan transaksi
1. Username, NISN unik; FK Gen–Mapel, siswa, CP, paket dan versi wajib dijaga. Pengguna Gen A tidak memperoleh data Gen B.
2. `cp_mapel` unik (kurikulum,mapel,fase); satu CP operasional dapat mengacu ke beberapa `ref_cp_versi` sebagai elemen, bukan data hilang.
3. BAB per paket memiliki nomor; nomor BAB **unik dan berlanjut antarsemester kelas yang sama** pada Gen–Mapel–TahunAjaran–Kurikulum. Kode TP server `KODEMAPEL.KELAS.BAB.TP`; status Siap hanya jika kode, BAB dan relasi valid.
4. TP Esensial maupun NonEsensial **wajib tepat 1 Materi aktif ketika Siap**. DB membatasi maksimum satu Materi aktif; Service Kesiapan menegakkan tepat satu untuk kedua jenis. Akses/progres/nilai siswa ditentukan secara terpisah oleh aturan siswa; Esensial tetap tidak dihitung nilai sesuai alur saat ini.
5. Nilai TP 20/20/60 atau 100% Sumatif. Percobaan remedial satu jika skor awal Sumatif < KKM; ambil skor tertinggi. TP selesai tidak menuntut skor >=KKM. Tidak membuat nilai akhir semester agregat.
6. Pra-Semester hanya dapat dibuka pada jadwal Admin bagi siswa yang seluruh TP NonEsensial selesai, menggunakan engine ujian dan audit yang sama; bank minimal 30 dan mencakup seluruh BAB semester. Cegah 2 attempt berjalan sekaligus.
7. Nilai ekspor parsial Guru NULL ≠ 0; hak Gen–Mapel di server, output terhindar formula injection dan sandi.
8. Versi konten/CP, snapshot attempt, sertifikat dan audit tidak dihapus atau ditimpa; perubahan lintas tabel transaksi dan pemeriksaan ulang hak.
9. Database `sla_sks_v4` **dibuat baru**, bukan DROP `sla_sks_v3`; impor privat memerlukan file sumber terbaru, backup dan QA referensial. DDL melakukan implicit commit, jangan menjanjikan rollback otomatis.

## 4. Parameter `pengaturan` penting
- `kkm_default`: ditetapkan Admin, dapat kosong hingga dikonfigurasi; contoh 80 bukan angka dipaksakan. `tp.kkm_khusus`: opsional Guru; nilai efektif = khusus jika terisi, selain itu default Admin.
- Bobot efektif TP NonEsensial: **20/20/60** bila gagal Diagnostik, atau **0/0/100** bila lulus.
- Minimal soal tayang/bank: Diagnostik 10, Sumatif 30, Pra-Semester 30. Sumatif/Pra-Semester: satu remedial bila skor di bawah KKM.
- Media pembelajaran: checkpoint client-side **4 menit** dan saat perubahan tahap/selesai, validasi server. YouTube/audio selesai pada min(waktu Guru,90% durasi). PDF ≤15MB dan ≤40 halaman.
- Keamanan ujian: jeda fullscreen 90/180 detik, insiden ketiga batal, heartbeat koneksi 20 detik/jeda 300 detik. Parameter madrasah, sertifikat, foto, login dan audit lama tidak dihapus.

## 5. Paket SQL instalasi dan update
**Instalasi lokal v4:** dari dump v3 26 Oktober 2026 bangun satu SQL privat `sla_sks_v4_LOKAL_PRIVAT.sql` yang menjalankan `CREATE DATABASE sla_sks_v4` pada DB **baru dan belum ada** lalu memulihkan data v3 dan menerapkan revisi skema berurutan `V4-0001`. `sla_sks_v3` tidak disentuh. **JANGAN COMMIT** file privat (nama/NISN/hash/sesi/jawaban). Repository hanya boleh berisi DDL/patch tanpa data pribadi dan dokumentasi.

Langkah: backup DB v3 dan `writable/`, impor v4 di phpMyAdmin/CLI, cek `schema_version`, tabel baru, foreign key, CP operasional, arsip Materi uji, dan agregat siswa; XAMPP/code belum dialihkan otomatis. MySQL lokal dan MariaDB target perlu diuji nyata. Tidak memakai CI4 Migration/Seeder. Aplikasi tetap di ROOT sebagaimana pemasangan pengguna.

## 5A. Kontrak tambahan model v2.4
- Implementasi masih bertahap: kode CI4 v3 yang membaca TP→banyak Materi serta Diagnostik sebagai nilai final **belum otomatis kompatibel** dengan skema v4.
- SQL v4 tidak menghapus snapshot, data ujian atau progres sebelumnya; perlakuan data uji historis v3 harus transparan.
- Versi dokumen v2.5 mengganti ketentuan v2.4 yang bertentangan; jadwal Pra-Semester dan persyaratan sertifikat yang belum disepakati tidak boleh diinferensikan.

## 6. Pemeriksaan lintas DB dan target
Karena MySQL lokal dan MariaDB hosting **berbeda mesin**, semua enum, CHECK constraint, generated column, index, JSON, collation dan DDL alter harus diuji pada **versi spesifik keduanya**, bukan dianggap identik. Catat versi yang lulus tes pada `15-INSTALASI-DAN-OPERASIONAL.md`. Ukuran data kecil tidak menghapus kebutuhan FK, transaksi, indeks, atau audit.

## 7. Larangan penghapusan
Tidak ada tombol/batch yang menghapus fisik siswa, sertifikat, riwayat nilai, snapshot ujian, file historis, atau log akademik. Data akun nonaktif tetap ada. Berkas temporer upload yang belum sah boleh dibersihkan dengan pembeda jelas dari dokumen historis.


### Dua entitas HOTS berbeda dalam implementasi (25-10-2026)

Nama tugas_hots_materi pada skema operasional adalah **bank perintah/
pertanyaan HOTS oleh Guru** yang terhubung ke Materi. Untuk menjalankan
Resume/HOTS per Materi yang dikerjakan siswa, **jawaban HOTS siswa** disimpan
di jawaban_hots_materi, dengan progres_tp_id, materi_id,
versi_konten_tp_id, isi, status, nilai, penilai dan waktu. Tabel resume_materi
menyimpan Resume siswa. Perbedaan ini memperbaiki benturan nama SQL 0017,
tidak mengubah aturan pembelajaran maupun bobot nilai. Pemulihan parsial
memakai SQL 0018 yang additive dan hanya diimpor jika diperlukan.

### Audit kontrak tiga objek HOTS dan syarat dump terkini (25-10-2026)

- `materi.petunjuk_hots`: petunjuk yang sekarang diedit Guru, dibaca siswa dari snapshot TP terpin.
- `tugas_hots_materi`: bank perintah HOTS Guru per Materi; **bukan** tabel pengumpulan siswa.
- `jawaban_hots_materi`: pengumpulan HOTS siswa, status final/draf, nilai, identitas penilai; terpisah dari `resume_materi`.
- Belum selaras: `KesiapanTpService` saat ini hanya memeriksa `petunjuk_hots`, belum ada pengecekan baris bank Guru. Master 05 mewajibkan satu tugas HOTS per Materi. **Jangan mengubah kebijakan, memutasi TP Siap atau menambah tugas otomatis** sebelum dump terbaru dan implikasi snapshot dinilai.
- Koreksi struktur historis harus memakai patch baru dan uji kegagalan impor sebagian, tidak menamai ulang atau membuat ulang tabel yang sudah ada.


### Revisi nama BAB/TP 27 Oktober 2026
Batas nama akademik `bab.nama_bab` dan `tp.nama_tp` berubah dari `VARCHAR(35)` menjadi **`VARCHAR(50)`** tanpa perubahan `deskripsi_bab`/`deskripsi_tp` (`VARCHAR(225)`). Database v4 R2 yang telah dipasang tidak boleh diimpor ulang atau direset; gunakan hanya patch additive/pelebaran kolom **bernomor baru**, setelah backup serta pemeriksaan dump XAMPP terkini. TP draf dan BAB yang sudah tersimpan tetap dipertahankan.

## Pembaruan kolom nama ATP pada skema V4 R2 (31 Oktober 2026)

Dump lokal terbaru `sla_sks_v4` (MariaDB 10.4.32, ekspor 31-10-2026 14.06) masih mempunyai `bab.nama_bab VARCHAR(35)` dan `tp.nama_tp VARCHAR(35)`. Master v2.5 menetapkan batas **50 karakter**. Gunakan patch additive `database/sql/updates/V4-0005_PERLEBAR_NAMA_BAB_TP_50.sql` setelah backup database/writable; jangan mengubah dump privat atau patch yang telah diimpor sebelumnya. Sampai patch diterapkan, Service dan pratinjau impor sengaja menolak nama yang melewati kapasitas nyata (35) dengan pesan validasi, bukan memotong data diam-diam. Verifikasi `information_schema.COLUMNS` dan `schema_version` sesudah impor. Tidak ada Migration/Seeder.

