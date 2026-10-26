# Keputusan Teknis Final — Validasi PDF Materi SLA-SKS v2.4

> **Status:** keputusan implementasi yang telah diuji pengguna pada XAMPP, **24 Oktober 2026**.  
> **Ruang lingkup:** unggah, penggantian, penyimpanan dan pratinjau **PDF Materi/Submateri** oleh Guru pada TP draf.  
> **Rujukan resmi:** Master Document [05 §5](05-PAKET-TP-DAN-IMPOR.md), [10](10-DATABASE.md) dan [14 §4](14-KEAMANAN-DAN-AUDIT.md).  
> **PR implementasi:** [#19 — Media Materi Privat](https://github.com/Al-Faroz/SLA-SKS/pull/19). Dokumen ini **bukan Master Document ke-17**; ia mencatat keputusan operasional dan regresi, tanpa mengubah kebijakan Master.

## 1. Masalah yang sudah diputuskan — jangan diperdebatkan ulang

**Tujuan utama batas PDF adalah menjaga penggunaan ruang simpan shared hosting agar tidak membengkak.** Batas halaman membatasi panjang bahan belajar. Guru **hanya perlu memilih PDF yang dapat dibuka dan mengunggahnya**, bukan memahami tabel xref, incremental update, C2PA, `OpenAction`, `startxref`, atau format internal PDF lainnya.

Aturan bisnis baku:
- File PDF Materi: **maksimal 15 MB** (implementasi PHP memakai 15 × 1024 × 1024 byte), **1–40 halaman**.
- Pastikan file memang PDF yang dapat dibaca, dengan ekstensi/MIME yang sesuai.
- Perlindungan keamanan tetap wajib: jangan menjalankan atau menerima PDF berisi skrip/aksi aktif yang tidak diizinkan; jangan izinkan akses langsung ke folder privat. Pemeriksaan dilakukan **di server**, bukan hanya atribut HTML/JavaScript.
- Unggah PDF tidak mengaktifkan Materi otomatis. Batas halaman/ukuran dan otorisasi yang ada **tidak boleh dilonggarkan tanpa keputusan pemilik proyek**.

**Yang bukan larangan bisnis:** revisi inkremental PDF yang sah, penanda `%%EOF` tambahan sebagai bagian riwayat revisi, navigasi tampilan awal halaman (`/FitH`, `/XYZ`), hyperlink biasa, anotasi wajar, metadata tanda asal/C2PA, dan filter/kompresi PDF umum. **Jangan menolak otomatis** hanya karena ciri-ciri tersebut ada. Ini pernah menyebabkan *false positive*.

## 2. Kronologi singkat bug yang tidak boleh berulang

1. Form Tambah Materi awalnya menonaktifkan tombol untuk PDF; diperbaiki menjadi satu alur **YouTube → URL**, sedangkan **PDF/Gambar/Audio → pilih file**.
2. Guru dapat memilih PDF namun gagal menyimpan tanpa penjelasan jelas. Pesan gagal akhirnya diletakkan **tepat di formulir**, input teks dipertahankan setelah redirect, dan error upload PHP dibuat spesifik.
3. PDF normal buatan jsPDF ditolak karena `/OpenAction [3 0 R /FitH null]` disalahartikan sebagai skrip berbahaya; padahal hanya mengatur tampilan awal halaman.
4. PDF pengganti sempat ditolak dengan alasan `%%EOF`/revisi walaupun file normal. Pemeriksaan berbasis hitung substring mentah dianggap tidak layak.
5. PDF lain yang valid memiliki metadata C2PA dan navigasi `/XYZ`. Pembatasan internal yang tidak diperintahkan Master tidak boleh menjadi syarat tambahan bagi Guru.
6. Validator PDF kemudian diselaraskan ke **15 MB / 40 halaman**, pohon halaman dan perlindungan dasar. Pada **24 Oktober 2026 pemilik menyatakan: “Oke sudah fix”** setelah pengujian XAMPP. Ini adalah **konfirmasi untuk masalah PDF yang sedang diuji**, bukan bukti bahwa semua PDF dari semua generator telah lolos.

**Keputusan akhir:** kalau PDF wajar gagal, **periksa kompatibilitas parser dan bukti teknis terlebih dulu**. Jangan menganjurkan Guru mengonversi file normal atau menambahkan daftar larangan format tanpa dasar Master.

## 3. Kontrak operasional Tambah dan Sunting

- **Tambah:** judul, jenis, sumber/berkas, waktu, petunjuk Resume dan HOTS tervalidasi; upload PDF + metadata `berkas` + `materi.berkas_id` disimpan atomik, dengan TP draf, hak Guru G1, CSRF dan cek `struktur_versi`. Materi baru tetap **Nonaktif**.
- **Sunting:** file pengganti boleh diunggah pada jenis yang sama, tanpa menghapus file historis; jika validasi gagal, **file lama dan referensi Materi tetap utuh** dan versi TP tidak naik.
- **Tampilan:** pesan gagal/sukses **di lokasi formulir**, bukan di panel lain; isian teks lama dipulihkan saat gagal, file harus dipilih ulang (batas keamanan browser). Tidak boleh ada tombol yang tampak berhasil padahal tidak menyimpan.
- **Pratinjau:** PDF, Gambar, Audio dan YouTube menggunakan halaman Pratinjau Guru yang konsisten; file lokal dilayani lewat endpoint privat dengan hak akses, bukan URL filesystem publik.
- **Error:** untuk Guru gunakan penjelasan sederhana seperti “PDF harus 15 MB atau kurang”, “PDF maksimal 40 halaman”, “File tidak dapat dibaca” atau “File gagal diunggah”; detail decoder/parser dan korelasi server disimpan hanya pada log privat.

## 4. Pengamanan penting tanpa mengganggu pengguna

- Tetap cek file PDF sungguhan, ukuran dan jumlah halaman di server, skrip/aksi aktif, status draf, otorisasi Guru, CSRF, checksum, nama berkas acak, storage privat dan transaksi.
- **Jangan mengasumsikan pemeriksaan byte/regex sederhana merupakan PDF parser sempurna.** Implementasi ringan saat ini **masih dapat menolak PDF `ObjStm` (objek terkompresi)** karena belum dapat memeriksa struktur halaman/aksi tersembunyi. Ini **keterbatasan teknis yang terbuka**, bukan aturan bisnis baru. Jika file PDF valid terhalang, perbaiki parser/ketergantungannya dengan tes regresi, tetap amankan file aktif.
- Jangan menghapus file historis atau memperbarui SQL/data produksi hanya untuk memperbaiki parser.
- **Jangan menuliskan dump database privat, identitas siswa, password, PDF pengguna atau rahasia server ke repository/log publik**. Contoh pengujian gunakan PDF sintetis/anonim.

## 5. Regression gate wajib pada perubahan berikutnya

Sebelum memperbarui validator, Service, form PDF atau menggabungkan PR terkait:
1. PDF 1 halaman biasa dan PDF **40 halaman** diterima; PDF **41 halaman** ditolak; file **lebih dari 15 MB** ditolak.
2. PDF jsPDF dengan `/OpenAction` navigasi `/FitH` diterima; JavaScript dan aksi aktif tetap ditolak.
3. PDF dengan revisi inkremental normal, metadata C2PA dan `/XYZ` diterima bila tidak aktif/berbahaya serta halaman terbaca.
4. Materi PDF baru tersimpan bersama berkas dan metadata dalam transaksi; jika gagal tidak ada baris Materi yatim.
5. Penggantian PDF pada Sunting mengubah referensi hanya setelah sukses; pada gagal, file/referensi lama tetap ada.
6. Halaman Pratinjau PDF dapat dibuka Guru berhak; Guru lintas Gen dan akses langsung `writable/` ditolak.
7. Pesan error mudah dipahami Guru; form tidak hilang seluruhnya ketika validasi gagal.
8. Jalankan workflow CI **QA Berkas Materi** dan regresi; uji manual di XAMPP untuk format PDF yang sebelumnya bermasalah. CI PASS **tidak menggantikan** tes browser.
9. Jika ada perbedaan perilaku format PDF: **tambahkan tes reproduksi** lebih dulu, lalu perbaiki parser; jangan menambah larangan sementara yang menjadi aturan permanen.

Rujukan tes: `tests/unit/PdfMateriValidatorTest.php`, `tests/integration/BerkasMateriUnggahTest.php`, `tests/unit/MateriFormViewTest.php`, `.github/workflows/qa-berkas-materi.yml` dan [QA PR19](QA-PR19-BERKAS-MATERI.md).

## 6. Status serah terima

- **Konfirmasi pengguna:** upload PDF yang menjadi masalah berulang telah **FIX di XAMPP**, 24 Oktober 2026.
- **QA otomatis:** regresi PDF dan media telah lulus pada branch PR #19; lihat workflow di PR untuk status HEAD.
- **Bukan bukti:** seluruh variasi PDF, seluruh media dan semua halaman sudah lolos uji pengguna.
- **PR #19:** tetap **Draft, OPEN, belum merge**; jangan merge ke `main` sebelum persetujuan eksplisit pengguna.
- **Tahap sesudahnya:** kembali ke urutan modul Master Document, jangan berputar lagi membahas PDF yang telah FIX kecuali ada laporan bug baru yang konkret.

**Instruksi untuk AI/developer berikutnya:** baca dokumen ini **sebelum** menyentuh kode validasi PDF atau modul Materi, hormati keputusan final, dan kerjakan fitur berikutnya. Jika bug PDF baru muncul, kumpulkan bukti minimal dan buat uji reproduksi; jangan mulai dari nol atau membuat Guru harus memahami format internal PDF.
