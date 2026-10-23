# QA PR #18 — Video Materi YouTube, tahap media awal

## Implementasi
- Validasi URL YouTube oleh `VideoMateriService` saat menyimpan draf. Hanya video HTTPS berdomain resmi, ID video 11 karakter pada `watch?v=`, `youtu.be/`, `shorts/`, `live/` atau `embed/`. Parameter pelacakan dihapus saat menyimpan tautan kanonis.
- Rincian TP menampilkan tautan **Pratinjau** hanya untuk Materi YouTube yang memiliki URL. Route `GET guru/tp/{id}/materi/{id}/pratinjau` memakai `auth:guru` dan `DetailTpService` yang memeriksa penugasan G1 objek. ID Materi wajib milik TP yang sama.
- Iframe tidak dibuat hingga Guru mengklik **Tampilkan video**. Embed dibangun server hanya ke host `www.youtube-nocookie.com`, dengan no-referrer, output aman, pesan Materi Nonaktif dan tautan YouTube.
- Tidak ada pengambilan URL remote oleh server, perubahan basis data, atau penayangan ke siswa.

## Batas sementara
- Upload PDF/gambar/audio **belum dibuat**. Master v2.4 menuntut metadata `berkas` dan storage privat; dump SQL v2.4 lengkap dan kolom `berkas` belum terdapat di repo `main`. Jangan mengarang kolom/SQL, melakukan migration/seeder, atau menyimpan file di `public/`.
- Belum ada pengujian XAMPP atas jalur pemutar dan kompatibilitas izin browser/YouTube. Video pihak ketiga mungkin tidak bisa diputar akibat pembatasan embed/koneksi.

## Pemeriksaan
- Jalankan GitHub Actions QA Pratinjau YouTube (lint + unit URL).
- Regresi PR #17 harus tetap PASS; fixture MySQL tidak dimodifikasi.
- Pada localhost setelah diinstruksikan: login Guru, buka TP yang mempunyai Materi YouTube, klik **Pratinjau**, lihat tombol **Tampilkan video**, klik dan uji pemutar.
- Coba Materi Nonaktif (seharusnya tetap bisa dipratinjau Guru tetapi diberi penanda); coba URL video palsu saat simpan draf (harus ditolak).
- Guru yang tidak ditugaskan pada TP lain harus menerima akses ditolak (tidak memperoleh URL embed).
- Uji navigasi HP 360px, pesan koneksi offline, dan buka link di YouTube. Jangan memakai data siswa sungguhan.

## Perbaikan hasil pengujian pemilik — 23 Oktober 2026

Pemilik melaporkan **halaman Pratinjau mengalami error saat menampilkan video**, dan meminta label serta input **Minimal waktu belajar dalam menit**, bukan detik. Perubahan tindak lanjut:

1. Pemutar video tidak lagi menggunakan skrip JavaScript inline atau pembuatan iframe dengan `document.createElement`. Antarmuka memakai `<details>` bawaan browser dan `<iframe>` HTML dengan sumber hasil validasi server; tautan langsung YouTube tetap tersedia untuk masalah pembatasan embed. Penyebab pasti error pertama belum dapat dibuktikan tanpa pesan galat/screenshot pengguna, sehingga diperlukan uji XAMPP ulang.
2. Input formulir bernama `min_waktu_menit` (angka menit, hingga dua desimal). Service memeriksa rentang dan mengonversi ke kolom `materi.min_waktu_detik`, tanpa SQL update. Nilai lama ditampilkan sebagai menit sampai dua desimal, dengan konversi `round(menit * 60)` saat disimpan. Pengujian otomatis membuktikan 1.5 menit menjadi 90 detik dan nilai di luar rentang ditolak.
3. Periksa lagi pada XAMPP: simpan 2 menit dan pastikan saat membuka ulang muncul 2.00; video dapat dipratinjau; URL yang tidak mendukung embed tetap mempunyai tautan alternatif.

Jangan merge sebelum kedua temuan diperiksa ulang pemilik.

## Konfirmasi perbaikan oleh pemilik — 23 Oktober 2026

Setelah mencoba perubahan pada branch pengujian XAMPP, pemilik menyatakan **"Oke sudah fix"** untuk dua temuan: error ketika menampilkan pratinjau video YouTube dan perubahan satuan minimal waktu belajar menjadi **menit**. Konfirmasi ini berlaku untuk kedua perbaikan yang diminta, bukan klaim bahwa keseluruhan integrasi media PDF/gambar/audio telah diuji atau selesai. PR dapat disiapkan untuk review, tetapi penggabungan ke `main` tetap memerlukan persetujuan eksplisit.
