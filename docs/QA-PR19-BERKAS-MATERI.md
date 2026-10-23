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

## QA lanjutan — unggah aktual di Service (CI)

Ditambahkan `tests/integration/BerkasMateriUnggahTest.php` dengan file PNG anonim hasil GD dan fixture MySQL sementara: unggah menghasilkan metadata berkas, checksum, nama acak dan versi TP berikutnya; unggah versi kadaluarsa tidak menyisakan file/metadata; Guru Gen lain dan TP Siap ditolak. Untuk file CLI, PHPUnit memakai subclass `UploadedFile` **hanya pada fixture** karena `is_uploaded_file()` memang tidak berlaku untuk file yang dibuat CLI; perlindungan produksi tidak diubah.

GitHub Actions [QA Berkas #39927697636](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39927697636) **PASS**: validasi 8 tes/10 assertions, regresi Materi 11 tes/18 assertions, akses baca 4 tes/6 assertions, integrasi unggah 4 tes/12 assertions. Pengujian ini tidak menggantikan uji POST browser/Apache/PHP XAMPP maupun pemeriksaan streaming HTTP Range secara nyata.

Dukungan unggah PDF masih **belum dikerjakan** karena parser PDF yang menghitung halaman dan memeriksa tindakan aktif secara aman belum tersedia sebagai dependensi yang teruji pada shared hosting. Jangan menganggap berkas PDF telah diterima oleh Service.

## Revisi alur dropdown media (masukan pengguna)
Pengguna menegaskan satu aturan UI:
- Pilih **YouTube** → tampil **Alamat YouTube**, file upload tersembunyi.
- Pilih **Gambar** → tampil **Upload file** JPG/PNG/WebP, URL YouTube tersembunyi.
- Pilih **Audio** → tampil **Upload file** MP3/M4A, URL YouTube tersembunyi.
- Pilih **PDF** → tampil bagian **Upload file PDF** dengan keterangan bahwa upload PDF masih **belum aktif** sampai parser keamanan dan pemeriksaan jumlah halaman tersedia. Kontrol PDF tidak menerima file maupun melakukan penyimpanan palsu.

Form tambah sudah `multipart/form-data` dan memiliki input media kondisional. Gambar/audio disimpan **bersamaan dengan pembuatan Materi** melalui transaksi atomik; tidak perlu membuat Materi dahulu baru membuka Sunting hanya untuk unggah. Form Sunting memakai input yang sama dengan penggantian file opsional jika berkas sudah terpasang. Materi PDF yang sebelumnya tersimpan tetap dapat disunting metadata tanpa mengubah/menimpa berkas. Tanpa JavaScript, server tetap menolak data/file yang tidak cocok; alur kondisional diuji lagi secara manual di browser.

CI setelah perubahan pada commit `602bdfd5e74fb00527eceab745240d33d5787c57`: [QA Media #39930851395](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39930851395) PASS, [Materi #39930851609](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39930851609) PASS, [Rincian TP #39930854385](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39930854385) PASS, [YouTube #39930851575](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39930851575) PASS, [Peta ATP #39930851604](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39930851604) PASS.

Checklist tambahan XAMPP:
1. Pilih satu per satu keempat opsi dropdown: pastikan **tidak pernah terlihat kolom URL dan input file sekaligus**.
2. Tambah Materi YouTube memakai URL valid; pastikan lolos dan tidak meminta file.
3. Tambah Materi Gambar/Audio sambil unggah file dalam **satu tombol Simpan**. Materi harus terbuat dan mempunyai berkas_id.
4. Sunting Materi Gambar/Audio: pilih file pengganti dan simpan dalam formulir yang sama.
5. Ganti dari YouTube menjadi Gambar tanpa memilih file: browser/server menolak, tidak meninggalkan Materi tanpa sumber.
6. Pilih PDF: field upload terlihat tetapi **dinonaktifkan secara eksplisit**, tidak mengirim/menerima file sampai validasi PDF benar-benar selesai. Jangan dinyatakan lulus sebagai *upload PDF*.
7. Pastikan Materi sudah Siap tetap tidak dapat diubah lewat editor draf.
