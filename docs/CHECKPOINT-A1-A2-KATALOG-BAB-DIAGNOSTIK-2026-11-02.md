# Checkpoint — A1/A2 Katalog BAB dan Diagnostik Siswa v2.5

**Tanggal:** 2 November 2026  
**Branch kerja:** `feature/v25-a1-a2-siswa-dan-kunci-tp-20261102`  
**Dependensi:** PR #90 (Master), PR #91 (edit langsung CP/BAB/TP), PR #92 (A1/A2).  
**Status:** implementasi pada branch untuk QA otomatis dan XAMPP; **belum dinyatakan FIX end-to-end**.

## Kontrak yang dipakai

Master 01, 02, 03, 04, 05, **06**, **07**, 10, 11, 12, 13, 14, 15, **16** v2.5:
- Gen → Mapel → Semester → BAB → TP, relasi `bab_id` dari database; bukan menafsirkan nomor BAB dari string kode TP.
- TP Esensial informatif tanpa aktivitas/nilai wajib siswa; NonEsensial mengikuti Diagnostik sebagai gate dan **Sumatif selalu wajib**, meskipun Diagnostik lulus.
- Lulus Diagnostik → status `sumatif`, bukan `tuntas`; tidak lulus → status `belajar` dan perlu Materi/Resume/HOTS final sebelum Sumatif.
- Edit TP Siap oleh Guru G1 hanya sebelum minimal satu siswa mulai menggunakan; aktivasi siswa mengikat snapshot, sesudahnya konten TP terkunci.
- Izin Gen–Mapel, semester awal, urutan TP dan snapshot diperiksa server. GET katalog/rincian tidak membuat progres.

## Pekerjaan kode pada PR #92

1. `KatalogPembelajaranSiswaService`: query TP memakai JOIN `bab` sah milik paket; pengelompokan `bab` dengan nama/deskripsi asli, sekaligus mempertahankan urutan dan status otorisasi TP.
2. `siswa/daftar_tp.php`: kelompok BAB berisi daftar TP dan alasan status, Esensial informatif; CSS memakai token Emerald Glass dengan layout responsif.
3. `siswa/diagnostik_petunjuk.php` dan `siswa/rincian_tp.php`: hilangkan informasi v2.4 yang keliru tentang TP tuntas hanya dari Diagnostik; jelaskan dua jalur menuju Sumatif tanpa membuat tombol Sumatif palsu. Indikator TP historis ditampilkan hanya bila ada.
4. `tests/unit/UIKonsistensiHalamanTest.php`: sesuaikan tes referensi CP dengan UI Admin terakhir, termasuk panel CP yang dibuka sesuai kebutuhan.
5. Fixture SQLite/MariaDB anonim diperluas dengan BAB; pengujian baru memeriksa dua BAB tidak tercampur dan progres siswa langsung memicu gerbang kunci edit Guru tanpa mengubah snapshot.
6. Ekspektasi string lama pada unit test katalog/progres siswa diselaraskan dengan kontrak Diagnostik terbaru.

## Lingkup di luar PR #92

- Mesin Sumatif lengkap, remedial, skor/nilai TP, kalkulasi progres semester hingga selesai, ekspor Guru, Pra-Semester dan sertifikat **belum diimplementasikan di PR ini**.
- Regresi penuh browser XAMPP (termasuk fullscreen HP/laptop) **belum diverifikasi**.
- Tidak ada perubahan SQL produksi, Migration/Seeder, `.env`, konfigurasi root CI4, atau penghapusan/penimpaan data siswa.
- Database lokal terakhir yang diaudit: `sla_sks_v4`, V4-0007, 36 TP, 36 Materi, satu TP Siap #23, dua snapshot TP #23, belum ada progres siswa pada audit. Jangan membuat progres nyata pada data non-uji tanpa persetujuan pemilik.

## Gerbang uji lokal sebelum melanjutkan A3/A4

- Setelah pull PR #92, login siswa uji Gen/semester sesuai paket aktif, buka Semester 4, pastikan BAB dan TP ditampilkan menurut relasi data yang benar.
- Periksa Esensial tidak meluncurkan aktivitas siswa dan TP berikutnya tidak dapat dibuka sebelum Sumatif sah pada TP NonEsensial sebelumnya.
- Aktifkan TP #23 dari siswa uji yang berhak. Setelah progres muncul, Guru G1 tidak dapat mengubah rincian/KKM/Materi/Soal TP, termasuk POST langsung; snapshot tidak berubah.
- Petunjuk Diagnostik menjelaskan lulus/gagal dengan benar; percobaan dibuat hanya setelah fullscreen sah.
- Periksa hasil QA Actions pada SHA terakhir; jika ada kegagalan, koreksi sebelum merge. Jangan menganggap pengujian statis atau fixture sintetis sebagai bukti browser sudah lulus.
