# 05 — Paket Semester, BAB, TP, Materi dan Impor Excel/Word

> **Master v2.5 · 26 Oktober 2026.** Pengganti ketentuan v2.4 tanpa BAB dan beberapa Materi per TP.

## 1. Struktur paket
`paket_semester (Gen–Mapel–Semester) → BAB → TP`. BAB mempunyai nama singkat (≤35), deskripsi (≤225), nomor berlanjut lintas semester berpasangan kelas sama. TP mempunyai nama singkat (≤35), deskripsi asli (≤225), kode format `KODEMAPEL.KELAS.BAB.TP`, jenis Esensial/NonEsensial, nomor urut dalam BAB. TP Siap immutable di editor biasa; versi dan snapshot lama dipertahankan.

**Esensial:** tanpa Materi, Diagnostik, Resume, HOTS, Sumatif, dan nilai di aplikasi. Tetap muncul dalam ATP dan sertifikat.

**NonEsensial:** wajib tepat **satu Materi aktif** (bukan banyak materi atau submateri), berjenis YouTube, PDF, audio, atau gambar, disertai petunjuk Resume, satu HOTS, dan bank ujian TP.

## 2. ATP Excel dan editor aplikasi
- Guru dapat **mengisi ATP langsung di aplikasi ataupun mengimpor Excel**. Kedua jalur mengelola data yang sama dan menjaga otorisasi G1 Gen–Mapel.
- Basis template yang sudah ada: `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx`; adaptasi tahap berikutnya harus memuat identitas Gen–Mapel/Kurikulum, CP tunggal Mapel (boleh multi-elemen), Semester 1–6, Nomor BAB, Nama/Deskripsi BAB, Kode TP, Nama/Deskripsi TP, Jenis TP, dan keterkaitan satu Materi untuk NonEsensial.
- Kode TP diturunkan dari konteks dan nomor BAB/urutan TP, diverifikasi saat impor. BAB semester genap melanjutkan nomor BAB semester ganjil pada kelas sama.
- **Tahap setelah dokumen+database:** bangun versi Excel bermakro beserta validasi/pengaturan tambahan, kemudian sesuaikan generator/parser. Jangan menganggap `.xlsx` lama sudah mengikuti model BAB atau file makro sudah tersedia. Macro Excel tidak pernah dijalankan di server; parser hanya mengambil data sel yang diizinkan dan memastikan keamanan container.
- Excel untuk **peta akademik**, bukan upload media/soal. Guru mengelola satu file/link media Materi dan Resume/HOTS melalui aplikasi. Baris hilang tidak berarti delete. Unggah → validasi → pratinjau diff → konfirmasi atomik → audit.

## 3. Bank soal
- **Diagnostik TP NonEsensial minimal 10 soal** dan **Sumatif minimal 30 soal** yang valid/aktif. Tayang dan bank tidak boleh kurang dari batas tersebut. Semua soal PG tunggal lima opsi A–E dengan tepat satu kunci.
- Dua unduhan Word Diagnostik/Sumatif per TP memakai referensi `docs/TEMPLATE_SOAL_SLA-SKS.docx`, format `SLA-SKS-WORD-V1` dengan konten guru berupa pertanyaan, opsi, kunci; contoh di `docs/` bukan soal otomatis siswa. Generator unduhan guru membuat slot kosong sesuai konfigurasi.
- Parser DOCX harus menjaga Unicode/Arab RTL, gambar, tabel termasuk bersarang, Equation Word/OMML pada pertanyaan **dan opsi**, tautan Google Drive yang dapat divalidasi. Makro Word, elemen aktif, remote tracking dan HTML berbahaya ditolak; isi disanitasi. Pratinjau lengkap serta konfirmasi wajib, kunci tidak bocor ke siswa.
- **Pra-Semester** memiliki bank tersendiri di tingkat semester yang menggabungkan BAB semester tersebut, minimal 30 soal, menggunakan mesin keamanan dan pola Sumatif. Bukan TP tambahan.

## 4. Materi dan media
- Satu TP NonEsensial tepat satu media Materi aktif; riwayat Materi lama tidak boleh dihapus. Empat media yang dahulu berada dalam satu TP lama merupakan **data uji v3** dan tidak boleh otomatis dipilih sebagai materi v4 yang aktif.
- PDF bawaan maksimal **15 MB/40 halaman** tanpa menolak PDF normal hanya karena metadata atau revisi inkremental. Audio mp3/m4a ≤10 MB bawaan; gambar JPG/PNG/WebP, video menggunakan URL YouTube. Berkas privat, pratinjau Guru, validasi ukuran/MIME/akses mengikuti [dokumen keputusan PDF](KEPUTUSAN-VALIDASI-PDF-MATERI.md).
- Pembelajaran YouTube/audio selesai pada batas **lebih dahulu** antara waktu minimum Guru dan 90% durasi sah; gambar/PDF memenuhi waktu aktif dan halaman yang diwajibkan. Model penghitungan waktu klien dengan checkpoint server empat menit disepakati; unduh audio/gambar/PDF dibuka setelah syarat media selesai, bersamaan dengan akses Resume/HOTS.

## 5. Kesiapan dan penguncian
- BAB siap ditampilkan sebagai kelompok TP; status siap tetap pada TP. Validasi kode, nomor, tipe TP, CP, materi (hanya NonEsensial), bank, KKM, waktu/durasi, media, snapshot dan otorisasi dilakukan di Service.
- Admin menentukan KKM default (misalnya 80) dan Guru dapat menentukan KKM khusus. KKM efektif = khusus jika diatur, selain itu default Admin. Jangan menyisipkan KKM 75 sebagai kebijakan baru hanya karena nilai historis v3.
- Bobot **jalur tidak lulus Diagnostik 20% Resume + 20% HOTS + 60% Sumatif**. Jalur lulus Diagnostik **100% Sumatif**. Nilai Diagnostik tidak menjadi nilai akhir. Ujian Sumatif di bawah KKM mendapat satu remedial; skor tertinggi digunakan.
- TP Siap/versi historis, koreksi kategori A/B/C dengan persetujuan Admin, penolakan kategori D, audit dan larangan self-approval tetap. Jangan mereset nilai/progres ketika mengubah Master.
