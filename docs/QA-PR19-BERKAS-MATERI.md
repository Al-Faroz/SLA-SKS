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

## Revisi antarmuka berdasarkan screenshot pengguna — 23 Oktober 2026

Tabel Daftar Materi/Submateri kini mempunyai empat kolom aksi terpisah: **Pratinjau**, **Urutan** (Naik/Turun), **Edit** (Sunting), dan **Hapus**. Di layar sempit tabel dapat digulir horizontal, dan tombol mutasi TP Siap tidak tampil.

Input **Upload file** untuk PDF kini tidak disabled sehingga pengguna dapat memilih file PDF dari komputer. Namun ini **belum berarti penyimpanan PDF aktif**: tombol Simpan tetap dibatasi untuk PDF baru dan endpoint server menolak hingga pemeriksaan jumlah halaman serta skrip aktif selesai. Materi PDF lama masih dapat disunting metadata; tidak ada klaim upload PDF sukses.

Penghapusan Materi draf kini memiliki form POST+CSRF dan pemeriksaan versi, konfirmasi browser dan server, hak Guru G1, TP draf, serta perlindungan referensi progres/resume/HOTS. Materi yang dapat dihapus diikuti perapian nomor urut dalam transaksi. Berkas historis tidak dihapus secara fisik.

QA tambahan: tests/integration/MateriHapusDatabaseTest.php memeriksa hapus di tengah, penolakan versi lama, TP Siap, Guru Gen lain dan ID Materi dari TP lain. Pengujian lokal: periksa konfirmasi Hapus/Batal, urutan setelah Hapus, file PDF chooser, empat kolom tindakan pada desktop, dan scroll pada HP.

CI: QA Berkas Materi #39933983805, QA Materi Draf #39933983584, QA Peta ATP #39933983544, QA Pratinjau YouTube #39933983587 dan QA Rincian TP #39933983578 — semuanya PASS.

## Revisi final PDF dan pratinjau seluruh media — 23 Oktober 2026

**Bagian ini menggantikan catatan sebelumnya yang menyatakan penyimpanan PDF belum aktif.** Form PDF kini bisa dikirim; Service memvalidasi PDF terlebih dahulu, lalu menyimpan Materi dan berkas dalam satu transaksi privat.

- Validasi PDF: maksimal 15 MiB dan 40 halaman; MIME asli, ekstensi .pdf, header, footer, tabel referensi xref klasik, katalog, halaman, serta daftar halaman konsisten. Dokumen dengan JavaScript, OpenAction, formulir, lampiran, revisi inkremental, berkas terenkripsi, nama PDF tersamarkan atau struktur yang tidak didukung **ditolak (fail closed)**.
- **Batas kompatibilitas:** Validasi ini konservatif untuk PDF dengan xref klasik dan objek terbuka. PDF modern dengan object stream/xref stream, fitur lanjutan, atau format rumit bisa ditolak walaupun di bawah 15 MB/40 halaman. Pesan penolakan tidak berarti ukuran selalu melebihi batas. Jangan melonggarkan validasi menggunakan hitungan regex halaman saja.
- Metadata: `berkas.halaman` diisi jumlah halaman sesungguhnya pada PDF yang diterima, checksum SHA-256 tersimpan, berkas fisik di `writable/uploads/materi`. Tidak ada perubahan SQL.
- Pratinjau Guru: halaman `guru/tp/{tpId}/materi/{materiId}/pratinjau` sekarang mencakup **YouTube, PDF, Audio dan Gambar** dengan breadcrumb, judul, status Nonaktif, pemutar/pembaca, serta tombol kembali seperti halaman YouTube. File PDF/audio/gambar dimuat melalui endpoint GET media privat (bukan path file fisik atau aset publik). PDF memakai pembaca bawaan browser dan fallback buka tab baru. HTTP Range kini didukung untuk PDF dan audio.
- QA unit `tests/unit/PdfMateriValidatorTest.php` menguji PDF 1 halaman, 40 halaman, 41 halaman, aksi JavaScript, escaped-name, ekstensi salah dan file palsu. QA integrasi `tests/integration/BerkasMateriUnggahTest.php` juga menguji simpan PDF beserta metadata halaman dan penolakan PDF berisi skrip tanpa baris/file tersisa.
- Formulir file PDF sekarang tidak menonaktifkan submit. Validasi ukuran/jumlah halaman tetap berada di server; jenis media berbeda tidak dapat mengirim file yang salah. Modalitas kontrol tampilan tidak menggantikan keamanan server.

**Checklist XAMPP tambahan:**
1. Tambah Materi PDF menggunakan PDF klasik yang berisi 1–40 halaman, ukuran tidak lebih dari 15 MB; tombol Simpan harus aktif, penyimpanan berhasil dan Materi muncul sebagai Nonaktif.
2. Pratinjau PDF dari daftar membuka halaman Guru, menampilkan pembaca PDF atau fallback tab baru dengan autentikasi Guru. Gunakan PDF sendiri, bukan dokumen siswa.
3. Pratinjau Audio dengan kontrol play/pause/seek dan Gambar dengan skala responsif. Periksa banner Nonaktif, breadcrumb dan tombol kembali pada semua media.
4. Uji PDF lebih dari 40 halaman, PDF palsu/terpotong/berisi JavaScript, dan PDF dengan struktur modern yang tidak didukung: server harus menolak tanpa membuat Materi atau metadata yatim.
5. Guru Gen lain tidak boleh mengakses pratinjau/media PDF/audio/gambar milik Gen yang bukan tugasnya; TP Siap tidak dapat diubah.
6. Pastikan URL privat `/writable/uploads/materi/` tidak dapat diakses langsung. Jangan merger sampai semua hasil uji browser didokumentasikan.

**Catatan:** tes otomatis tidak mensimulasikan viewer PDF nyata di Chrome/Firefox atau konfigurasi CSP Apache XAMPP. Ini wajib diperiksa manual.

## Laporan pemilik 24 Oktober 2026 — Tambah Materi PDF tidak tersimpan

Pengguna melaporkan sudah melengkapi form dan memilih PDF, menekan Tambah Materi Draf, namun Materi tidak tersimpan. Penyebab konkret belum bisa ditentukan karena pesan error/berkas PDF tidak disertakan. Dua kondisi nyata yang diidentifikasi dari kode:
- Validator PDF yang konservatif menolak PDF modern/kompleks termasuk xref-stream/ObjStm dan revisi inkremental walaupun batas halaman/MB terpenuhi. Batas ini belum dilonggarkan karena perlu pemeriksaan keamanan yang andal.
- Pengaturan server `post_max_size` dan `upload_max_filesize` dapat lebih rendah dari limit aplikasi 15 MB. Jika POST terlalu besar, field formulir kosong; jika file melebihi batas upload PHP, UploadedFile berisi kode galat, bukan file valid.

Perbaikan pada controller: periksa Content-Length terhadap post_max_size ketika body kosong, terjemahkan kode PHP UPLOAD_ERR_* yang relevan menjadi pesan berbahasa Indonesia, arahkan redirect gagal/sukses ke fragment #tambah-materi, dan tampilkan galat langsung pada formulir Tambah Materi. JS juga memeriksa 15/10 MB di browser tanpa menonaktifkan submit hanya karena PDF dipilih. Validasi PDF di server tetap wajib dan tidak dilemahkan.

QA otomatis PASS, tetapi **belum memverifikasi PDF spesifik milik pengguna** maupun konfigurasi PHP XAMPP. Uji ulang setelah pull, dan jika gagal catat teks alert yang sekarang terlihat, nilai upload_max_filesize/post_max_size, ukuran file, serta apakah file menggunakan object-stream/xref-stream (jangan kirim data siswa rahasia).

## Investigasi lanjutan 24 Oktober 2026 — skrip DebugBar bukan error upload

Pemilik masih melaporkan PDF tidak tersimpan dan menempelkan kode JavaScript `document.addEventListener('DOMContentLoaded', loadDoc, false)` dari **CodeIgniter DebugBar**. Kode itu bukan exception, HTTP response upload atau pesan penolakan PDF; dari isinya tidak dapat diketahui apakah request POST mencapai controller, ditolak CSRF, melewati batas PHP, atau gagal oleh validasi PDF. Jangan menebak satu penyebab sebagai fakta.

Ditambahkan tes realistis `testPdfDariDompdfAsliDapatDiunggah` yang menghasilkan PDF asli menggunakan `dompdf/dompdf` dari dependency yang memang sudah ada dan menjalankan `BerkasMateriService::validasi`; CI QA Berkas Materi **PASS** [run #40048962798](https://github.com/Al-Faroz/SLA-SKS/actions/runs/40048962798): 8 tes PDF/12 assertions serta seluruh regresi validasi berkas, Materi, akses Guru, integrasi upload dan Hapus PASS. Ini membuktikan salah satu PDF nyata bisa diterima, tetapi **tidak membuktikan bahwa PDF yang diuji pengguna diterima**.

Tindak lanjut yang diperlukan: perbarui branch; coba lagi dan catat pesan **Materi belum disimpan** pada formulir. Jika pesan tidak muncul, periksa di Browser DevTools tab Network request POST `guru/tp/{id}/materi`: status HTTP (contoh 200/302/403/413/500) dan halaman response, tanpa membagikan cookie/CSRF/kredensial. Bila diperlukan, gunakan PDF contoh anonim yang gagal untuk tes reproduksi format. PR belum boleh di-merge.

## Perbaikan notifikasi yang ditemukan dari telaah kode (24 Oktober 2026)

Masalah UI terkonfirmasi dari kode (tanpa perlu data tambahan pengguna): `guru/detail_tp.php` menampilkan notifikasi `galat`/`sukses` untuk editor TP pada kartu **Rumusan dan indikator** di posisi atas, sedangkan controller tambah Materi juga memakai key sama dan kembali ke `#tambah-materi`. Akibatnya pesan penolakan PDF dapat terlihat jauh dari formulir dan pengguna hanya melihat Materi tidak bertambah. Setelah keluhan berulang, key dipisah menjadi `materi_galat`/`materi_sukses` untuk `GuruMateri::tambah`, dibaca sekali di atas formulir Tambah Materi, dan copy bantuan disesuaikan dengan PDF yang sudah aktif pada Service. Notifikasi editor TP tetap menggunakan key `galat`/`sukses` masing-masing.

**Harus diverifikasi manual:** setelah pull, lakukan unggah PDF ulang, baca pesan tepat di atas formulir. Jika jenis PDF ditolak karena struktur, dokumentasikan teks penolakan. Jika file diunggah dan diterima, periksa baris baru serta tombol Pratinjau. Perubahan ini terutama memperbaiki visibilitas kegagalan dan **tidak menjamin PDF spesifik pengguna pasti valid**.

## Kasus PDF jsPDF satu halaman — perbaikan 24 Oktober 2026

Pengguna mengunggah contoh `Masuk CMS MIN 6 JEMBER.pdf` **hanya untuk pemeriksaan lokal**, tidak dimasukkan ke GitHub. Hasil inspeksi byte PDF lokal: PDF 1.3 dari jsPDF 1.x-master, 59.242 byte, 1 halaman, tidak terenkripsi, tanpa JavaScript atau formulir interaktif. File memuat kode katalog:
```pdf
/OpenAction [3 0 R /FitH null]
```
Tujuan tersebut hanya mengatur **tampilan awal halaman**, bukan aksi yang menjalankan skrip. Validator sebelumnya memblokir **seluruh** token `/OpenAction` tanpa membedakan format, sehingga menolak berkas normal dengan alasan generik. Ini akar penolakan konkret pada file contoh.

Perbaikan dalam `app/Services/PdfMateriValidator.php`: hanya `/OpenAction` array tujuan tampilan ke objek `/Page` yang terverifikasi dengan mode Fit/FitH/FitV/FitB/FitBH/FitBV sederhana yang diterima; referensi yang bukan halaman, dictionary action, JavaScript, Launch, URL dan struktur lain tetap ditolak. PDF asli tidak dimodifikasi. Validasi ukuran 15 MB/40 halaman, MIME, xref, enkripsi dan akses privat tetap berlaku.

QA regresi `tests/unit/PdfMateriValidatorTest.php`: contoh jsPDF 1 halaman dengan OpenAction FitH valid; OpenAction Launch, target bukan Page dan campuran instruksi JS ditolak. Inspeksi lokal file unggahan menunjukkan satu-satunya pola larangan lama yang cocok adalah `/OpenAction` dengan `/FitH null`; objek target `3 0` adalah `/Page` dan seluruh pemeriksaan xref, katalog, jumlah halaman lainnya cocok. **Belum diuji pada browser XAMPP pemilik** setelah patch.

Jika kelak ditemukan contoh PDF modern/berstruktur lain, identifikasi terpisah dan jangan menonaktifkan pemeriksaan action secara umum. Jangan commit dokumen nyata atau URL berisi identitas sekolah jika tidak relevan dengan QA.

## Temuan 24 Oktober 2026 — PDF pengganti satu halaman ditolak oleh pemeriksaan EOF

Pemilik berhasil menambah Materi PDF baru pada XAMPP, lalu mencoba mengganti PDF pada Materi sama dan mendapat pesan "PDF dengan revisi inkremental atau penutup tidak jelas ditolak". Contoh pengganti hanya diperiksa lokal, **tidak ditambahkan ke repository**. Byte contoh menunjukkan PDF 1.3 jsPDF, ukuran ~199 KB, satu halaman, tepat satu %%EOF, satu startxref, offset xref valid, tanpa /Prev, tanpa JS dan enkripsi; pemeriksaan PHP atas footer file asli berhasil. Jadi pesan lama **tidak sesuai dengan file yang dilampirkan**. Belum terbukti bahwa byte yang diterima PHP/XAMPP identik dengan file asli.

Perbaikan:
1. PdfMateriValidator.php tidak lagi menghitung token EOF/startxref mentah di seluruh konten biner; pemeriksaan mencari **blok akhir PDF struktural** dan memastikan hanya satu blok. Dokumen multi-revisi masih ditolak; validasi xref, aksi aktif, MIME, 15 MiB dan 40 halaman tetap.
2. Jika penutup yang diterima PHP masih salah, log privat writable/logs mencatat label [MATERI-PDF], ukuran byte, SHA-256, jumlah token mentah dan klasifikasi error, tanpa menyimpan isi/nama dokumen. Data ini dapat dibandingkan dengan hash file lokal, tanpa membagikan dokumen ke GitHub.
3. Tes unit mencakup token EOF/startxref di metadata (bukan revisi), EOF tanpa newline ala jsPDF, revisi berulang, penutup tidak sah.
4. Tes integrasi mencakup **Sunting Materi PDF dengan file pengganti**, mempertahankan metadata/file historis, menaikkan versi TP hanya bila berhasil, dan mempertahankan file lama saat PDF pengganti ditolak.

CI terbaru [QA Berkas Materi #40053134703](https://github.com/Al-Faroz/SLA-SKS/actions/runs/40053134703) PASS: 16 tes PDF/21 assertions; 11 tes integrasi unggah/45 assertions.

Uji manual XAMPP: tarik branch terbaru, Sunting Materi PDF yang sudah ada, pilih PDF pengganti, simpan dan buka Pratinjau. Jika masih muncul error penutup baru, cari [MATERI-PDF] dalam log privat, bandingkan SHA-256 dengan perintah Windows certutil -hashfile "file.pdf" SHA256. Jangan mengganti SQL, .env atau menghapus file lama.
