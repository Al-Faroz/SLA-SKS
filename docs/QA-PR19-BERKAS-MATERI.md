# QA — Media Materi privat (PR #19)

## Sumber skema yang diverifikasi
Skema diverifikasi secara statis dari **dump bersih v2.4 privat** yang tersedia pada ruang kerja pemilik: tabel `berkas` berisi `id`, `tp_id`, `nama_acak`, `mime`, `ukuran_byte`, `checksum`, `halaman`, `durasi_detik`, `dibuat_pada`; `materi.berkas_id` memiliki FK ke `berkas.id`. **Dump privat tidak disalin ke GitHub.** Fixture anonim dibuat ulang hanya dengan struktur yang relevan.

## Yang diimplementasikan
- Guru G1 pada TP draf mengunggah **gambar JPG/PNG/WebP** dan **audio MP3/M4A** melalui editor Materi; kedua jenis tersimpan sebagai nama acak pada `writable/uploads/materi`, bukan `public/`.
- Gambar diperiksa jenis file, dimensi dan decode, di-*re-encode* sehingga metadata/aset tak dikenal tidak ikut, dengan sisi maksimum 1600px. Audio dibatasi 10 MB, diperiksa ekstensi/MIME/header dan disajikan dengan dukungan HTTP Range.
- Metadata masuk tabel `berkas` dan `materi.berkas_id` ditautkan dalam transaksi yang mengunci TP, memeriksa status draf dan versi. Unggahan yang gagal dibersihkan, tetapi berkas lama tidak dihapus untuk menjaga potensi histori.
- GET media memeriksa ulang hak Guru G1 dan relasi `tp_id`; tidak menerima path/nama berkas dari URL. Header privat, no-sniff, dan CSP sandbox.
- Media baru **tidak mengaktifkan Materi**, tidak mengubah skema SQL pengguna, dan tidak menerbitkan TP Siap.
- Pelayanan dan unggah memakai CSRF global, `auth:guru` dan Service authorization.

## Batas tahap
- **PDF belum diaktifkan** karena kebutuhan validasi maksimal 40 halaman serta skrip aktif memerlukan parser yang aman dan kompatibel shared hosting. Form hanya menjelaskan batasan, tidak menampilkan upload yang tidak berfungsi.
- Belum ada endpoint siswa, readiness/publikasi TP, atau pemeriksaan manual server shared hosting.
- Pastikan folder `writable/` benar-benar diblokir oleh konfigurasi HTTP. Jangan deploy jika web server melayani file privasi secara langsung.
- File fisik `writable/uploads/materi/` harus termasuk backup/restore bersama dump SQL; jangan commit file unggahan.
- Karena penyimpanan media privat melibatkan file sistem dan database yang transaksinya berbeda, restore harus mencakup keduanya.

## Checklist XAMPP oleh pemilik — setelah instruksi pengujian
1. Backup database `sla_sks_v3` **dan** `writable/` sebelum uji.
2. Login Guru G1 → TP draf → Sunting Materi tipe gambar. Unggah PNG/JPG/WebP yang sah. Pastikan muncul pesan sukses, dan tombol Lihat media menampilkan gambar.
3. Uji resolusi besar dan file dengan ekstensi .php yang diganti ke .png — harus ditolak.
4. Sunting Materi tipe audio. Unggah MP3/M4A nyata maksimal 10MB. Buka media, periksa audio bisa diputar/diunduh dan seek/Range berfungsi.
5. Uji file audio palsu dan file lebih dari 10MB — ditolak tanpa menyimpan metadata.
6. Guru Gen lain atau Materi dari TP lain tidak boleh mengunduh berkas melalui URL langsung.
7. TP Siap tidak memperlihatkan form unggah dan POST upload ditolak.
8. Simpan draf Materi pada tab lain untuk mengubah `struktur_versi`, lalu coba unggah dari tab versi lama. Unggahan harus ditolak.
9. Pastikan Materi baru tetap Nonaktif setelah upload; tidak ada perubahan tabel/SQL wajib.
10. Uji responsif desktop/HP dan akses langsung `http://localhost/sla-sks/writable/uploads/materi/` (harus 403/404).

**PR Draft, tidak merge sebelum hasil CI dan uji XAMPP diperiksa.**
