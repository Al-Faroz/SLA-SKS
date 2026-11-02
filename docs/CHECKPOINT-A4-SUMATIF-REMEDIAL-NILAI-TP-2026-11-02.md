# Checkpoint A4 — Sumatif Wajib, Remedial Satu Kali dan Nilai TP

**Tanggal:** 2 November 2026  
**Branch:** `feature/v25-a4-sumatif-remedial-nilai-tp-20261102`  
**Basis:** A3 / PR #93. Status: kode diajukan untuk CI dan uji lokal, **belum FIX browser XAMPP**.

## Ruang lingkup

- Menambahkan `MesinSumatifSiswaService` dengan tahapan satu **normal** dan **remedial maksimal satu kali** jika skor normal sah di bawah KKM; sumber soal snapshot TP terpin, minimal 30 PG, kunci tidak dikirim ke klien.
- Memakai state machine Ketat yang sudah ada: fullscreen, heartbeat, timeout koneksi 20 detik, jeda fullscreen 90/180 detik, tiga insiden, jeda koneksi 300 detik, jawaban disimpan server. Ujian Sumatif memerlukan desktop/laptop, dengan pengecekan browser dan indikasi User-Agent server. Keduanya adalah mitigasi, bukan bukti fisik perangkat.
- Menghubungkan syarat A3 untuk kedua jalur: Diagnostik lulus boleh melewati Materi–Resume–HOTS; tidak lulus harus satu Materi + Resume final + HOTS final. Server memeriksa ulang di dalam transaksi sebelum membuat attempt.
- Sumatif di bawah KKM membuka satu remedial saja. Skor efektif `MAX(normal, remedial)`. TP menjadi `tuntas` seusai normal yang memenuhi KKM atau sesudah remedial sah, **termasuk jika skor masih di bawah KKM**. Percobaan batal menunggu keputusan Guru, tidak otomatis membuat kesempatan pengganti.
- `NilaiTpService` menyimpan nilai `nilai_tp` pada status tuntas: jalur lulus = 100% Sumatif terbaik; jalur belajar = 20% Resume + 20% HOTS + 60% Sumatif terbaik. Nilai Guru belum tersedia berarti NULL dan status sementara; ketika Guru menyimpan skor final, nilai disinkronkan secara transaksional. Nilai final yang sudah tersimpan tidak ditimpa otomatis oleh sinkronisasi berikutnya.
- Halaman Rincian TP menautkan Ujian Sumatif nyata dan menampilkan angka nilai sementara/final secara privat. Halaman ujian memakai komponen HTML/CSS/JS Ketat yang sudah digunakan Diagnostik, dan hasil Diagnostik diperbaiki agar tidak keliru menyatakan TP tuntas.
- Seluruh GET tidak membuat attempt Sumatif atau nilai. Mutasi hanya POST+CSRF+auth:siswa, transaksi DB dan row lock. Penugasan Gen dan snapshot diperiksa Service.

## Database

**Tidak ada file SQL update baru**, migration, seeder, reset, atau perubahan database produksi dalam branch ini. Struktur `percobaan_ujian.tahap` dan `nilai_tp` telah didefinisikan pada Master v4 / `V4-0001-R2`. **Tetap wajib mengaudit dump lokal yang dibuat sesudah patch terakhir sebelum mengeksekusi tes pada data XAMPP dan sebelum menyusun SQL baru.** Tidak ada dump privat di repository.

## QA dan status

- Unit `SumatifRemedialNilaiV25Test`: validasi satu normal/satu remedial, pembatalan, 30 soal minimum, hasil dua jalur dan status sementara vs final.
- Unit existing terkait pemilihan soal, mesin waktu, Diagnostik, katalog, kegiatan media dan penilaian Guru harus dijalankan regresi.
- GitHub Actions pada PR berjalan otomatis; status perlu diperiksa terpisah dan kegagalan harus diperbaiki. Sintaks PHP, unit, JavaScript dan eksekusi browser/DB XAMPP **tidak diklaim PASS sampai benar-benar berjalan**.
- Keterbatasan yang masih harus ditindaklanjuti: keputusan Guru atas percobaan Sumatif batal dan penggantian attempt perlu modul Guru terpisah, bersamaan dengan uji transaksi konkurensi serta audit SQL aktual. Jangan membuka jalur pengganti diam-diam. Koreksi nilai siswa dan sertifikat belum termasuk.
- Kasus manual prioritas: login siswa Gen sah dan asing, jalur Diagnostik lulus/gagal, gawai Android Sumatif ditolak, desktop fullscreen berhasil, jawaban tersimpan, jeda, normal >=KKM selesai, normal < KKM remedial satu kali, remedial tetap <KKM namun TP selesai, Resume/HOTS dinilai Guru setelah Sumatif menghasilkan nilai final; 0 vs NULL dipertahankan.

**Jangan gabungkan ke main sebelum pengujian.**
