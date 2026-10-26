# Catatan Progres SLA-SKS sebelum perombakan pola — 26 Oktober 2026

> Catatan transisi untuk tidak mengulangi masalah yang sudah selesai. **Bukan klaim semua fitur siap produksi.** Status didasarkan pada riwayat pengujian XAMPP yang dilaporkan pemilik, dokumen implementasi repository, dan audit dump terbaru `sla_sks_v3 (4).sql`. Status DB aktual wajib diuji ulang setelah impor v4.

## 1. Fondasi proyek yang telah dibangun
- CodeIgniter 4/PHP di XAMPP lokal; `index.php` dan `.htaccess` berada di ROOT sejajar `app/`. Welcome/login dan `.env` pernah dinyatakan FIX pengguna; **jangan dipindah/diubah otomatis**.
- Empat peran Admin, Guru, Siswa, Pimpinan; autentikasi khusus CI4, login/logout, ganti sandi pertama, pembatasan gagal login, pemeriksaan akses dan pergantian peran staf telah diuji lokal.
- Admin Kelola Pengelola/Siswa: tambah/edit, reset sandi, impor/ekspor XLSX, layout form atas/tabel paginasi, profil/foto, identitas madrasah dan favicon; sebagian besar telah diperiksa pemilik.
- Kurikulum, mapel, CP dan versi CP; penugasan G1 Guru per Gen–Mapel; enam semester Gen khusus. Pengelolaan CP Admin dan usulan akademik memiliki aturan self-approval.
- Peta ATP Guru, detail TP draf/Siap, checklist, materi tambah/sunting/urut, pratinjau media dan bank soal Word PG A–E dengan Equation, gambar, tabel, aksara Arab telah dikembangkan pada pola v2.4.
- Media materi YouTube, audio, gambar, PDF (PDF maksimal 15 MB/40 halaman). Permasalahan upload PDF berfitur format normal **telah diselesaikan**; jangan menambahkan validasi restriktif lama. Pembaca PDF.js lokal, fullscreen, zoom gambar dan tombol media berikutnya telah melalui iterasi perbaikan.
- Diagnostik Ketat, snapshot soal/jawaban, fullscreen, jeda dan audit percobaan sudah memiliki data uji; perhitungan media Model B client-side dengan checkpoint server setiap 4 menit serta unduh setelah gate adalah keputusan akhir diskusi sebelumnya.
- Resume dan HOTS per Materi: Simpan Draf/Kirim Final serta pemantauan Guru sudah dikembangkan; nilai aktual di dump terkait kiriman masih perlu verifikasi.

## 2. Temuan dump lokal per 26 Oktober 2026
| Objek | Kondisi |
|---|---:|
| Tabel SQL v3 | 52 |
| Pengguna / siswa | 37 / 17 |
| Mapel / ref_CP / paket semester | 20 / 3 / 1 |
| TP / Materi | 1 / 4 (seluruh Materi pada TP yang sama) |
| Bank soal / Percobaan ujian | 40 / 2 |
| Snapshot TP | Minimal satu versi terbit; TP uji lama telah digunakan siswa |
| `schema_version` | Ada; penanda terakhir `K5-0010` dari dump |

**Konflik terhadap kontrak baru:**
1. Tidak ada entitas BAB dalam v3; TP berada langsung di paket semester.
2. Satu TP lama memiliki **4 materi**, sementara v2.5 menuntut tepat satu Materi aktif per TP NonEsensial.
3. Dua hasil Diagnostik lama berhubungan dengan aturan tuntas dari diagnostik; pada v2.5 Diagnostik hanya gate dan Sumatif wajib.
4. Nilai/bobot baku TP v3 (20/30/50, Sumatif tayang 20, kuota 3) berbeda dengan v2.5 (20/20/60, Sumatif minimal 30, satu remedial).
5. Beberapa referensi CP mapel yang sama mewakili elemen/rumusan berbeda; CP operasional mapel baru harus menggabungkan referensinya **tanpa membuang sumber/versi asli**.
6. Metadata berkas di dump **bukan konten file `writable/`**; perlu backup aset terpisah.
7. Penanda `schema_version` terakhir tidak otomatis membuktikan semua patch historis tercatat; verifikasi DDL dan indeks langsung tetap wajib.

## 3. Prinsip mempertahankan hasil terdahulu
- **Dipertahankan:** akun, siswa, profil, Gen, mapel, referensi kurikulum/CP, G1, identitas, keamanan, histori audit, engine ujian, parser Word rich, pengolah media, solusi PDF, UI komponen yang masih relevan.
- **Direvisi:** hierarki kurikulum, ATP/Excel, atribut BAB/TP, tipe Esensial/NonEsensial, relasi Materi tunggal, readiness, Diagnostik gate, Sumatif dan remedial, nilai TP, progres semester, Pra-Semester, ekspor nilai, sertifikat.
- **Arsip:** TP dan empat Materi uji v3 yang tidak bisa langsung menjadi TP Siap pola baru. Riwayat/resume/HOTS/ujian lama tidak dihapus tetapi tidak dipakai untuk menghitung skor/progres v2.5 yang baru.
- **Belum selesai untuk pola baru:** SQL v4 teruji live; implementasi Controller/Service/View terhubung v4; Excel bermakro dan parser, Pra-Semester, remedial final, ekspor nilai, nilai/sertifikat, laporan/dasbor final dan QA menyeluruh.

## 4. Catatan status pengujian
Pernah dilaporkan PASS untuk beberapa tahap PHPUnit/halaman lokal pada percakapan lama, tetapi **tidak dijalankan ulang pada pengerjaan dokumen ini**. Pengujian lampiran SQL bersifat pemeriksaan tekstual/struktur sampai dilakukan impor MariaDB secara nyata dan XAMPP pemilik. Jangan menyatakan satu pun modul pola baru FIX sebelum verifikasi itu.
