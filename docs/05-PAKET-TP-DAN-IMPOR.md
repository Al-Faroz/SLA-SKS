# 05 — Paket Semester, TP, Materi dan Impor Excel/Word

> **Master v2.4.** Dokumen ini menggantikan kontrak lama "BAB dan Excel delapan sheet". **Tidak ada lapisan BAB**; seluruh readiness, publikasi, penguncian dan versi berlaku untuk TP.

## 1. Struktur dan status
Satu `paket_semester` milik satu Gen–Mapel–Semester–Tahun Ajaran. Paket memiliki TP berurutan; setiap TP memiliki satu atau lebih Materi/Submateri berurutan. Tautan CP per TP mengikuti dokumen 04. Status TP: **draf → siap/terkunci**. Label UI `Belum dibuat`, `Perlu dilengkapi` berasal dari kondisi/checklist dan **bukan status database tambahan**. TP berikutnya boleh draf saat TP sebelumnya sudah Siap dan dipakai siswa.

Semua mutasi draf/konten/publikasi mengecek penugasan Guru aktif pada Gen–Mapel objek di Service. TP Siap immutable pada editor/impor biasa dan tidak dapat ditarik menjadi draf; versi konten historis tidak dihapus. Koreksi khusus mengikuti kategori A/B/C.

## 2. Master template Excel ATP enam semester
- Berkas referensi resmi di `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx`; desain yang disetujui pemilik menjadi dasar generator/validator, **tanpa istilah BAB**. Satu workbook **per Gen–Mapel** dengan sheet panduan, RINGKASAN, REFERENSI CP, SEMESTER 1–6 dan contoh pengisian. Contoh tidak pernah diimpor sebagai data sungguhan.
- **RINGKASAN:** GEN, GURU, MAPEL, KURIKULUM diisi server menurut akun yang login, pilihan Gen–Mapel yang diampu, dan kurikulum; Guru tidak mengisi ulang dan tidak boleh mengubah identitas.
- **REFERENSI CP:** hanya CP **aktif/terbit** untuk mapel, Fase D dan kurikulum yang dipilih, termasuk kode, elemen, rumusan dan versi; read-only. Pilihan CP pada setiap semester merujuk sheet tersebut, bukan CP mapel lain.
- **SEMESTER 1–6:** bagian **Pemetaan TP** berisi urutan/kode TP, judul singkat, rumusan/indikator yang diperlukan dan tautan CP utama; bagian **Materi/Submateri** berisi setiap judul dan urutan materi yang merujuk TP. Nomor otomatis/validasi dropdown digunakan bila tersedia; tidak ada batas akademik 12 TP/60 materi. Kapasitas file fisik dapat dibatasi eksplisit dan diinformasikan di UI.
- Excel hanya untuk **peta TP dan daftar Materi**. Guru mengisi media PDF/gambar/audio/YouTube, petunjuk Resume dan tugas HOTS setiap Materi melalui aplikasi. **Tidak ada template Excel konten materi/soal delapan sheet.**
- Unduhan menyertakan metadata identitas/versi bertanda tangan server yang diverifikasi pada impor (boleh hidden metadata); jangan mengandalkan kunci/proteksi sel Excel sebagai pengganti otorisasi. Workbook bersifat offline sesudah diunduh.
- Impor **boleh sebagian semester** dan dapat diulang idempoten menggunakan ID/kode stabil dan versi konteks; baris yang hilang **bukan instruksi hapus**. Sistem menolak pemetaan ke Gen–Mapel lain atau perubahan TP Siap/TP yang telah mempunyai progres. Perubahan urutan/CP pada TP Siap bukan jalur impor biasa.

## 3. Dua unduhan Word bank soal per TP, satu struktur
Berkas referensi resmi `docs/TEMPLATE_SOAL_SLA-SKS.docx`. Sistem menyediakan tombol **Unduh Word Diagnostik** dan **Unduh Word Sumatif**, masing-masing untuk **satu TP**. Generator mengisi identitas Gen, Guru, Mapel, Kurikulum, semester, TP, jenis ujian dan jumlah slot soal sesuai setelan bank yang sah. **Satu mesin parser untuk dua jenis**, format `SLA-SKS-WORD-V1`; identitas CBT lain tidak boleh dipakai.

**Pembedaan master dan unduhan Guru (disetujui pemilik 23 Oktober 2026):** DOCX pada `docs/` tetap berisi 10 contoh soal dan kunci sebagai referensi visual serta pengujian. **Unduhan Guru bukan salinan isi contoh:** generator wajib menghasilkan jumlah blok soal **sesuai jumlah bank yang diisi Guru di aplikasi untuk TP dan jenis ujian tersebut**, seluruh pertanyaan, opsi A–E dan kolom kuncinya kosong. Dua jenis Diagnostik/Sumatif memakai format sama, dengan identitas server otomatis. Jangan menyertakan jawaban contoh dalam unduhan ataupun menetapkan 10 soal contoh sebagai batas jumlah bank.

Struktur tabel Word: **Bagian | Isi | Benar**, satu blok `Soal n` dengan pertanyaan, tepat **lima pilihan A–E yang terisi**, dan **tepat satu kunci ✓** pada opsi benar. Hanya pilihan ganda satu kunci; bukan CBT lima tipe. Jumlah slot dapat dibuat dinamis menurut kebutuhan bank; nilai bawaan tayang diagnostik 10, sumatif 20, dengan checklist bank memadai. Guru boleh memperbanyak soal sesuai pengaturan; jumlah slot contoh dokumen tidak menetapkan batas.

**Persyaratan fidelitas DOCX (wajib dan harus diuji):**
- Setiap **pertanyaan dan setiap pilihan A–E** dapat memuat teks Unicode/RTL, **Equation Word/OMML**, tabel Word dan gambar *inline*; urutan campuran teks–rumus–tabel–gambar wajib dipertahankan.
- **Tabel bersarang:** tabel format soal bukan tabel isi; tabel dalam sel pertanyaan/opsi disimpan sebagai **bagian dari satu soal/opsi** dan tidak disalahtafsirkan sebagai blok soal baru. Hubungan gambar serta aset Word harus diekstrak dengan aman tanpa kehilangan rumus/tabel.
- **Audio/Video via URL Google Drive** dibolehkan pada sel pertanyaan atau pilihan. Parser memvalidasi URL dan menampilkan pratinjau pemutar/media sesuai kemampuan browser; akses file publik terbatas oleh izin Google harus diuji. Media tidak disalin ke repository; jangan menganggap tautan Drive yang sah pasti bisa dimainkan.
- Pertahankan konten kaya dalam format terstruktur yang **disanitasi dan dirender aman** untuk pratinjau dan siswa. **Jangan menggunakan HTML mentah dari DOCX tanpa sanitasi**; elemen berbahaya, remote tracking, external relationships tak dipercaya dan macro ditolak. Larangan editor HTML bebas siswa/Guru tetap berlaku.
- Identitas TP dan jenis ujian diperiksa dari metadata yang ditandatangani server serta otorisasi akun, tidak cukup dari judul yang dapat diubah Guru. Lampiran Word dan aset di `writable/` atau storage privat, bukan URL publik langsung.
- **Pratinjau wajib menampilkan semua soal lengkap sebelum konfirmasi** (kunci hanya terlihat untuk Guru berizin). Perubahan gambar, Equation, tabel dan opsi harus sesuai tampilan ujian; setiap galat menunjukkan nomor soal, bagian, penyebab dan cara memperbaiki.
- Uji kesetiaan tampilan pada soal bergambar dan pilihan bergambar, Equation pada pertanyaan **dan pilihan**, tabel biasa dan **tabel bersarang**, aksara khusus, Drive dapat/tidak dapat diputar, halaman panjang, DOCX korup/bermakro, duplikat, opsi kosong serta dua ✓.

## 4. Jalur impor dan status
1. Guru memilih Gen–Mapel yang diampu dan konteks paket/TP draf.
2. Sistem menyiapkan workbook Excel ATP atau Word bank Diagnostik/Sumatif **yang terikat versi dan identitas server**.
3. Guru mengunggah berkas asli `.xlsx`/`.docx`; cek ukuran/MIME/ZIP container, metadata, versi, konteks, jumlah, struktur, kunci dan ancaman berkas. Parsing dilakukan dengan pembatasan memori/waktu.
4. Service menyusun **pratinjau tanpa mutasi final** berisi tambah/ubah/tidak berubah, galat spesifik, peringatan media, dan diff. File dan aset temporer privat.
5. Guru mengonfirmasi; Service mengulang otorisasi/versi/konflik lalu menerapkan perubahan dalam transaksi DB secara idempoten. Jangan menghapus konten lama hanya karena baris/soal tidak ada pada file. Jika satu unsur gagal, commit ditolak.
6. Tampilkan laporan hasil atau panduan perbaikan. Audit pelaku, Gen–Mapel, paket/TP, checksum, versi dan hasil tanpa membocorkan kunci ke siswa.

## 5. Media materi dan kegiatan Guru
- PDF ≤15 MB, ≤40 halaman bawaan; gambar JPG/PNG/WebP dengan inspeksi dan kompres khusus **materi** sampai 1600 px; audio materi mp3/m4a ≤10 MB bawaan dan HTTP Range; video **materi pembelajaran** berupa tautan YouTube/embed yang valid. Media soal Word menggunakan **Google Drive** sesuai §3, tidak mengganti kebijakan media materi.
  - **Catatan implementasi PDF yang telah diuji di XAMPP:** [Keputusan final validasi PDF Materi](KEPUTUSAN-VALIDASI-PDF-MATERI.md). Fokus batas file/halaman untuk kapasitas hosting dan panjang bahan belajar; jangan menambah larangan format internal PDF normal di luar ketentuan keamanan dokumen 14. Catatan ini tidak mengubah nilai batas atau hak akses.
- Setiap Materi memiliki media/sumber belajar, instruksi Resume dan **satu tugas HOTS terkait Materi**, termasuk petunjuk. Guru tidak mengulang input judul Materi yang sudah diimpor. Semua media tampil pada pratinjau Guru.
- Draf Resume/HOTS siswa disimpan manual; pengiriman final, penilaian dan prasyarat materi lanjut mengikuti dokumen 06/08.

## 6. Checklist TP Siap
Sebelum publikasi, server memeriksa: TP mempunyai rumusan, indikator dan kode stabil; kaitan CP aktif/cocok dengan **tepat satu utama**; minimal satu Materi dengan media dan instruksi Resume serta **HOTS pada setiap Materi**; urutan materi utuh; bank Diagnostik aktif **≥10 dan ≥ jumlah tayang**; bank Sumatif aktif **≥ jumlah tayang** (bawaan jumlah tayang 20, minimal tayang 5); lima opsi A–E dan satu kunci setiap soal; durasi, KKM, bobot, kuota percobaan konsisten; semua aset/tautan wajib dapat ditinjau; Guru menyatakan sudah meninjau akademik. Kondisi yang tidak lengkap menampilkan lokasi kesalahan dan menonaktifkan aksi `TP Siap`. Publikasi membekukan konfigurasi, versi dan snapshot dalam transaksi.

## 7. Parameter TP yang tetap
| Parameter | Nilai ketentuan |
|---|---|
| KKM | 1–100, default 75 |
| Bobot Resume / HOTS / Sumatif | Jumlah 100%, default **20/30/50**, sumatif >0 |
| Resume minimal karakter | 10–1000, default 50 |
| HOTS minimal karakter | 50–5000, default 200 |
| Diagnostik tayang | Minimal 10, default 10 |
| Sumatif tayang | Minimal 5, default 20 |
| Durasi Diagnostik/Sumatif | 5–180 menit, default 30/60 |
| Kuota Sumatif | Default 3 percobaan normal sah |

Nilai efektif mengikuti `PengaturanService` dan dibekukan pada TP Siap. Perubahan parameter di luar jalur yang sah dilarang.

## 8. Versi, koreksi dan penghapusan
Kategori koreksi Guru atas **TP Siap**: A redaksional, B materi/media, C soal/opsi/kunci/petunjuk kegiatan; kategori D (struktur TP/CP/urutan/KKM/bobot/durasi/kuota) **dilarang**. Admin memutus usulan yang tersimpan tanpa menyunting langsung konten Guru; pengusul tidak boleh menyetujui sendiri. Versi baru immutable; siswa yang sudah mulai TP tetap pada versi terpin, dan percobaan/hasil lama tidak ditimpa. Koreksi soal/kunci historis mengikuti dokumen 08.

TP/Materi/soal **draf yang belum diterbitkan dan belum punya progres/percobaan** boleh dihapus dengan konfirmasi eksplisit. Tidak ada penghapusan fisik data akademik produksi. Dokumen dan aset historis tidak dioverwrite. Semua operasi per Gen–Mapel dengan audit.

## 9. UI Guru
Ruang utama: **Pilih Gen–Mapel/Kurikulum → Peta ATP enam semester → Editor TP dan Materi → Bank Word Diagnostik/Sumatif → Checklist TP Siap**. Menampilkan status kosong/loading/error/locked, breadcrumb Gen–Mapel, pratinjau dan tautan memperbaiki. TP Siap hanya menawarkan Lihat, Riwayat Versi, Ajukan Koreksi, tidak `Tarik ke Draf`.
