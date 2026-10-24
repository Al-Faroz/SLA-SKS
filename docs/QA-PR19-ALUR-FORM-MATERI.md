# QA PR #19 — Alur Tambah & Sunting Materi Draf

Status: implementasi UI dan validasi form pada branch `feature/v24-media-berkas-privat`, **PR Draft**. Belum diuji pada browser/XAMPP pengguna sesudah perubahan ini.

## Kontrak layar

Kedua layar memakai `guru/_materi_fields.php`, validasi form `MateriDrafService::bersihkan()`, dan upload privat `BerkasMateriService`. Untuk setiap Materi: judul maks. 180 karakter; jenis YouTube, PDF, Gambar atau Audio; petunjuk Resume dan HOTS wajib, maks. 20.000 karakter masing-masing; waktu 0–1092,25 menit dengan maksimal dua desimal (DB tetap detik). Kondisi baru: Materi nonaktif setelah disimpan.

| Jenis | Kontrol yang tampil | Syarat | Sumber |
|---|---|---|---|
| YouTube | URL HTTPS YouTube | URL tervalidasi dan dinormalisasi | `url_youtube` |
| PDF | Pilih file PDF | ≤15 MiB, ≤40 halaman, PDF aktif/terenkripsi/struktur tak terverifikasi ditolak | `berkas_id` |
| Gambar | Pilih JPG/PNG/WebP | ≤10 MiB, inspeksi isi dan kompres sampai 1600 px | `berkas_id` |
| Audio | Pilih MP3/M4A | ≤10 MiB, MIME dan header valid | `berkas_id` |

**Ketentuan PDF:** parser internal masih *fail closed*, hanya menerima bentuk yang dapat diperiksa; PDF modern tertentu dapat ditolak walaupun ukuran dan halaman sesuai. Error harus menjelaskan sebab. Belum boleh dinyatakan mendukung seluruh variasi PDF. Bila ditemukan dokumen PDF yang valid namun ditolak, identifikasi strukturnya sebelum memperluas parser/paket dependensi.

## Alur Tambah Materi — G04

1. Buka Rincian TP draf dengan hak Guru G1, lihat daftar Materi di atas dan form Tambah di bawah.
2. Isi judul, pilih satu jenis sumber, masukkan URL **atau** pilih file, waktu, Resume, HOTS. Input media lain di-hide dan disabled; server tidak percaya pada JavaScript saja.
3. Klien mengecek ukuran berdasarkan batas aplikasi **dan** `upload_max_filesize`/`post_max_size` yang berlaku saat request (batas POST dikurangi ruang formulir). Tampilkan alasan bila melebihi, jangan kirim request pasti gagal.
4. Tekan Tambah Materi draf; tampil status mengirim dan cegah double submit selama navigasi.
5. Controller cek input+versi, kode error `UPLOAD_ERR_*`, Service memeriksa autentikasi Gen–Mapel, TP masih draf, versi terkini, file valid dan transaksi. Berkas privat/nama acak + metadata ditulis bersama Materi, versi TP meningkat. Jika gagal rollback (dan file baru dibersihkan), tidak membuat Materi aktif atau yatim.
6. Redirect PRG 303 ke `#tambah-materi`. Pesan **materi_sukses** atau **materi_galat** ditampilkan di atas form, tidak bercampur dengan notifikasi Rumusan TP. Pada gagal, `materi_input_lama` mempertahankan semua input teks, pilihan jenis dan waktu; file harus dipilih ulang oleh browser. Field terkait diberi `aria-invalid`.

## Alur Sunting Materi — G07

1. Dari tabel daftar, klik Sunting. Tampilkan media lama (jika ada), jenis terkunci jika berkas sudah terpasang, dan tautan Pratinjau berizin.
2. Judul, waktu, Resume, HOTS dapat diubah. File baru opsional bila telah ada berkas; jika dipilih, jenis harus sama dan berkas baru disimpan tanpa menghapus berkas historis.
3. Jika belum ada file, pilih PDF/Gambar/Audio lalu unggah, atau YouTube dengan URL. Tanpa file baru pada jenis berkas yang masih kosong, server menolak.
4. Controller memeriksa input, error PHP upload, Service mengunci TP, menguji status/versi dan otorisasi ulang, menyimpan data+berkas dalam transaksi.
5. Berhasil/gagal kembali ke URL **Sunting Materi yang sama** dengan flash `materi_edit_sukses`/`materi_edit_galat`; isian dipulihkan saat gagal, file perlu dipilih lagi. Jangan mengalihkan ke Rincian TP sehingga sumber kegagalan tidak terlihat.

## Pesan wajib

- Sukses: “Materi draf dan sumber belajar berhasil disimpan. Materi masih nonaktif.”
- Gagal field: nama kolom dan perbaikan yang harus dilakukan (judul, waktu, Resume, HOTS, URL/jenis media/file).
- File > batas aplikasi/server PHP: tolak dengan pesan ukuran, batas `upload_max_filesize` dan `post_max_size` yang aktif.
- PDF ditolak: alasan spesifik pemeriksaan PDF; **tidak** membuat Materi tanpa file.
- TP Siap / hak G1 / konflik versi: penolakan dengan tindakan memuat ulang bila relevan.
- Error teknis tak terduga: pesan umum dan kode kejadian 8-hex; detail class, message, berkas dan baris dicatat **hanya di writable/logs privat**, tidak ditampilkan sebagai SQL/path pengguna.
- Uji browser native required/format URL/angka. Saat submit tampil status proses tanpa janji sukses sebelum server merespons.

## QA otomatis + uji manual

Automated:
- `tests/unit/MateriFormViewTest.php`: HTML form Tambah, nilai lama PDF dipulihkan, jenis media terkunci saat sudah berkas, escaping.
- `tests/integration/MateriDrafDatabaseTest.php`, `tests/integration/BerkasMateriUnggahTest.php`: validasi, transaksi DB, status nonaktif, PDF/Gambar/Audio, versi, akses Guru.
- Unit PDF tetap menolak contoh palsu/aktif/lebih dari 40 halaman; lulus satu PDF Dompdf nyata.
- Workflow `.github/workflows/qa-berkas-materi.yml` menjalankan lint PHP/JS, unit, dan integrasi dengan MySQL QA sementara.

Manual XAMPP (belum dilakukan): **backup DB+writable terlebih dulu**, pull branch PR. Uji (a) Tambah YouTube; (b) Tambah PDF valid dan baca pesan bila ditolak; (c) PDF salah; (d) PDF melebihi batas PHP; (e) Gambar/Audio; (f) Sunting tanpa file pengganti; (g) Sunting dengan file pengganti; (h) hilangkan kolom wajib; (i) duplikasi submit; (j) tab TP versi lama; (k) TP Siap; (l) Guru Gen lain; (m) desktop/HP dan akses keyboard. Jangan mengubah .env, SQL, index.php atau struktur root.
