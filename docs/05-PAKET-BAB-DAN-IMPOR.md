# 05 — Paket, BAB, TP, Media dan Impor Excel

## 1. Entitas dan status
Paket semester memuat BAB urut 1..n sesuai rencana. BAB memiliki `draf` atau `siap`. Pada BAB draf, Guru pengampu dapat membuat TP (kode stabil, rumusan, indikator, CP, urutan ATP) dan materi. **BAB Siap terkunci permanen dari edit biasa** termasuk editor, Excel, berkas, soal, KKM, bobot, durasi, dan urutan. Admin tidak mengisi konten Guru.

`Belum dibuat`, `Draf`, `Perlu dilengkapi`, `Siap/Terkunci` tampil jelas di UI; `Perlu dilengkapi` adalah label hasil checklist, bukan perlu status DB baru. BAB sesudahnya boleh tetap draf sambil BAB sebelumnya digunakan siswa.

## 2. Delapan sheet Excel resmi v2.2
| Sheet | Isi dan peraturan |
|---|---|
| `PETUNJUK` | Dibuat sistem, baca saja; jenis materi dan contoh penulisan |
| `IDENTITAS_BAB` | Identitas paket/mapel/semester/kelas/fase/BAB/versi; terkunci |
| `TP` | Satu baris per TP dari aplikasi, ID/kode/CP terkunci; parameter nilai/keterangan terbatas |
| `MATERI` | Materi per `kode_tp`: judul, jenis, urutan, petunjuk resume, waktu minimum |
| `TUGAS_HOTS` | Satu pertanyaan dan petunjuk tugas per TP |
| `SOAL_DIAGNOSTIK` | Bank PG per TP, 2–5 pilihan, satu kunci benar |
| `SOAL_SUMATIF` | Bank PG per TP, 2–5 pilihan, satu kunci benar |
| `_META` | Sheet sangat tersembunyi, HMAC, `bab_id`, versi template/struktur dan `row_uuid` |

Sumber struktur TP **hanya layar BAB**, bukan Excel. Jangan membuat TP baru, mengganti CP, atau mengubah urutan TP melalui Excel. Jangan memakai template v2.1 yang masih memuat sheet/kolom berbeda.

## 3. Prosedur impor
1. Guru memilih BAB **draf** dan mengunduh template untuk struktur/versi terkini.
2. Guru mengisi dan mengunggah `.xlsx` (tanpa ZIP); server membaca dan memeriksa identitas, checksum/HMAC, template version, `struktur_versi`, kolom, tipe, urutan, row UUID, kode TP, soal/kunci dan bobot.
3. Server menampilkan **pratinjau** baris tambah/ubah, daftar galat (`sheet`, baris, kolom, kode, solusi), peringatan media, dan operasi hapus yang diminta secara eksplisit.
4. Setelah konfirmasi, Service memeriksa lagi BAB masih draf, checksum dan versi asal belum berubah, lalu impor semua tabel dalam **satu transaksi**. Galat menghentikan seluruh operasi.
5. Impor ulang idempoten: `row_uuid` stabil atau ID server sebagai dasar *upsert*; file sama tidak melahirkan baris ganda. Hilangnya satu baris dari Excel **bukan** perintah delete otomatis; penghapusan draf memerlukan pilihan dan konfirmasi tersendiri.
6. Tautan YouTube/berkas yang belum lengkap boleh menjadi *peringatan saat impor*, tetapi menghalangi `BAB Siap`.

## 4. Jenis media dan berkas
- **PDF**: ≤ 15 MB, ≤ 40 halaman (bawaan), inspeksi isi, larang skrip aktif, ditayangkan PDF.js/efek buku dengan mode geser cadangan.
- **Audio**: mp3/m4a, ≤ 10 MB bawaan, durasi diperiksa server, dukungan permintaan `Range`.
- **Gambar**: JPG/PNG/WebP, kompres otomatis untuk **gambar materi** sampai sisi panjang ≤ 1600 px bawaan dan hapus EXIF. Aturan gambar materi **tidak otomatis berlaku** pada background sertifikat yang perlu resolusi cetak.
- **Video**: tautan/embed YouTube saja; tidak mengunggah file video ke server. Tautan tidak sah/blokir embed ditandai di checklist.
- Nama berkas acak, di `writable/berkas/` atau storage privat dengan controller cek izin; jangan expose lewat URL direct.

## 5. Pemeriksaan BAB Siap (dua gerbang)
**Gerbang impor** memeriksa struktur file/validasi data; **Gerbang Siap** menuntut setiap TP memiliki rumusan, indikator, CP resmi published, minimal satu materi lengkap, petunjuk resume, HOTS, bank diagnostik aktif cukup ≥ 10 dan ≥ jumlah tayang, bank sumatif aktif ≥ jumlah tayang, durasi/KKM/bobot konsisten, urutan valid, serta konfirmasi peninjauan akademik oleh Guru. Server memvalidasi ulang pada transaksi yang mengunci BAB draf → siap. Guru melihat checklist lokasi masalah lengkap dan tautan memperbaiki.

## 6. Pengaturan TP
| Pengaturan | Ketentuan |
|---|---|
| KKM | Integer 1–100; default 75 |
| Bobot resume/tugas/sumatif | Jumlah =100; sumatif >0; bawaan 20/30/50 |
| Resume min karakter | 10–1000; bawaan 50 |
| Tugas min karakter | 50–5000; bawaan 200 |
| Diagnostik jumlah tayang | minimal 10; bawaan 10 |
| Sumatif jumlah tayang | minimal 5; bawaan 20 |
| Durasi diagnostik/sumatif | 5–180 menit; bawaan 30/60 |
| Sumatif maksimal percobaan | bawaan 3 percobaan sah normal |

Parameter yang tidak diisi memakai `PengaturanService` sebelum BAB Siap; nilai efektif yang dipublikasikan dibekukan dalam snapshot versi BAB.

## 7. Koreksi khusus BAB Siap
Kategori yang boleh diusulkan Guru: **A redaksional**, **B materi/media**, **C soal/opsi/kunci/petunjuk tugas**. Kategori **D (struktur BAB/TP/CP/KKM/bobot/durasi/kuota/urutan)** dilarang. Guru mengirim diff, alasan dan referensi versi lama; Admin memutus tanpa menyunting konten. Setelah disetujui, diterbitkan versi konten baru immutable; **TP yang sudah dimulai tetap pada versi semula**, TP belum dimulai memakai versi baru. Soal/jawaban/hasil ujian historis tidak ditimpa. Koreksi kunci yang dapat memengaruhi ujian selesai ditangani melalui alur koreksi nilai ujian terpisah di `08-PENILAIAN.md`.

## 8. Penghapusan konten draf dan kualitas Excel
- BAB, TP, materi, soal yang **belum diterbitkan** dan **belum punya progres/percobaan** boleh dihapus oleh Guru pemilik melalui konfirmasi eksplisit; penghapusan konten produksi/historis ditolak. Tanpa `ON DELETE CASCADE` yang dapat merusak jejak siswa.
- File Excel `.xlsx` wajib asli, batas bawaan **5 MB** dan maksimum **2.000 baris per sheet** sesuai rancangan impor terdahulu; file bermakro ditolak; server tetap membatasi konsumsi memori parser.
- Pilihan PG wajib berurutan: A dan B terisi, C/D/E jika digunakan tidak boleh berlubang, kunci mengarah opsi yang terisi; soal yang dipakai attempt tidak dihapus, hanya dinonaktifkan pada jalur yang sah.
- Jika Guru mengganti berkas pada konten yang masih boleh diedit, progres historis siswa tidak dihapus. BAB Siap tidak berubah file melalui editor normal.

## 9. UI yang wajib disediakan
Wizard empat ruang: `Semester/BAB`, `Struktur TP/CP`, `Excel/Media`, `Checklist/Publikasi`. Setiap langkah menampilkan status, ringkasan validasi, tombol kembali, simpan, dan aksi utama; jangan membiarkan tombol Siap aktif jika validasi gagal. Pada BAB Siap tampil `Lihat`, `Riwayat Versi`, `Ajukan Koreksi Darurat`, bukan `Tarik ke Draf`.
