# Status dua template referensi v2.4

**Disetujui pemilik pada 23 Oktober 2026 setelah membuka kedua berkas hasil regenerasi.** Kedua berkas **sudah ada** di `docs/` pada branch PR sejak commit `0ea34fbaa18aae27b789f26a524820d72c4fb3e9`. Konten biner pada `main` dan branch v2.4 memiliki blob Git identik. SHA-256 berkas lokal sesuai tabel, dan `git hash-object` untuk masing-masing berkas lokal menghasilkan **Git blob SHA yang identik** dengan objek berkas pada kedua branch GitHub. Dengan demikian, identitas byte berkas lokal dan biner pada repository terverifikasi tanpa perlu membuat file baru.

| Lokasi final di repository | SHA-256 file yang disetujui | Ukuran |
|---|---|---:|
| `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx` | `5eaac8698d3ddb3819055720a498002c1a83776114aec6ca3a7d3104dfa27d00` | 58.063 byte |
| `docs/TEMPLATE_SOAL_SLA-SKS.docx` | `e476f6a524202d8a06f72d6c23d7a0dc6dd70243f3c1b18e53ffcc6f637687a3` | 36.241 byte |

Hash ini **menggantikan hash lama** dari regenerasi sebelumnya; hash setiap hasil unduhan dinamis dari aplikasi kelak tentu berbeda.

## Pemeriksaan berkas yang disetujui
- Excel: 10 sheet (MULAI DI SINI, RINGKASAN, REFERENSI CP, SEMESTER 1–6, CONTOH MAT VII), **1.050 formula** dan **26 validasi** terdeteksi; struktur contoh dan dropdown tetap.
- Word: dokumen contoh berisi 10 soal PG lima opsi A–E, satu kunci per contoh, satu Equation Word/OMML dan satu berkas media gambar. Pemilik menyetujui isi sebagai **contoh referensi saja**.
- Kedua file dibuka dan dinyatakan FIX oleh pemilik. Uji generator serta parser di aplikasi CI4 **belum** dilakukan.

## Ketentuan generator Word yang tidak boleh dilanggar
1. `docs/TEMPLATE_SOAL_SLA-SKS.docx` adalah **referensi dengan contoh soal dan jawabannya**, bukan file unduhan Guru apa adanya.
2. Guru memilih TP terotorisasi serta jenis **Diagnostik** atau **Sumatif**. Jumlah slot mengikuti **jumlah bank soal yang diisi di aplikasi** untuk TP dan jenis tersebut; jangan menyalin 10 soal contoh atau menganggap angka contoh sebagai batas bank.
3. File DOCX unduhan Guru harus berisi **semua slot soal kosong**, termasuk stem, pilihan A–E dan tanda jawaban benar; tidak boleh memuat teks/kunci contoh.
4. Pertahankan struktur `Bagian | Isi | Benar`, identitas otomatis Guru/Gen/Mapel/Kurikulum/Semester/TP/jenis ujian, dan marker `SLA-SKS-WORD-V1` yang divalidasi di server.
5. Dua tombol unduh berbeda; satu parser aman dan satu kontrak yang sama saat impor. Konten Word kaya OMML/gambar/tabel bersarang/Unicode/Drive dari isian Guru wajib didukung.
6. Generator dan parser Word tetap **target implementasi**, bukan fitur runtime yang sudah jadi.

## Gate sebelum merge
- [x] Pemilik membuka dan menyetujui kedua file regenerasi.
- [x] Pemeriksaan struktur formula, dropdown dan arsip Office pada berkas lokal.
- [x] Dua berkas biner berada di **branch** `docs/master-v2-4-atp-tp-materi`, masing-masing di `docs/` (Git blob identik dengan `main`).
- [x] SHA-256 dan ukuran file lokal cocok dengan catatan; `git hash-object` lokal cocok dengan Git blob SHA objek pada `main` dan branch v2.4. Kedua berkas sudah dibuka dan dinyatakan FIX oleh pemilik sebelum unggah.
- [ ] Tinjau perubahan PR dan dapatkan persetujuan merge dari pemilik; jangan menggabungkan tanpa persetujuan.
