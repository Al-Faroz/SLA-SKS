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
