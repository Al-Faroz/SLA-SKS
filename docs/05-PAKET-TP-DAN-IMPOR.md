# 05 — Paket Semester, BAB, TP, Materi dan Impor Excel/Word

> **Master v2.5 · 26 Oktober 2026.** Pengganti ketentuan v2.4 tanpa BAB dan beberapa Materi per TP.

## 1. Struktur paket
`paket_semester (Gen–Mapel–Semester) → BAB → TP`. BAB mempunyai nama singkat (≤50), deskripsi (≤225), nomor berlanjut lintas semester berpasangan kelas sama. TP mempunyai nama singkat (≤50), deskripsi asli (≤225), kode format `KODEMAPEL.KELAS.BAB.TP`, jenis Esensial/NonEsensial, nomor urut dalam BAB. TP Siap immutable di editor biasa; versi dan snapshot lama dipertahankan.

**Esensial:** **wajib disiapkan satu Materi/media oleh Guru**, sama seperti NonEsensial. Kebijakan siswa saat ini tidak mewajibkan aktivitas/ujian/nilai Esensial; pelaksanaannya dikendalikan di alur/otorisasi siswa, bukan dengan mengosongkan Materi.

**NonEsensial:** juga wajib tepat **satu Materi aktif** (bukan banyak materi atau submateri), berjenis YouTube, PDF, audio, atau gambar, disertai petunjuk Resume, satu HOTS, dan bank ujian TP sesuai alur siswa.

## 2. ATP Excel dan editor aplikasi
- Guru dapat **mengisi ATP langsung di aplikasi ataupun mengimpor Excel khusus untuk susunan awal semester kosong**. Setelah BAB/TP tersimpan, semester menjadi milik editor aplikasi dan Excel tidak boleh memperbarui/menyisipkan BAB/TP pada semester tersebut. Otorisasi G1 Gen–Mapel diperiksa saat pratinjau dan commit.
- Basis template yang sudah ada: `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx`; adaptasi tahap berikutnya harus memuat identitas Gen–Mapel/Kurikulum, CP tunggal Mapel (boleh multi-elemen), Semester 1–6, Nomor BAB, Nama/Deskripsi BAB, Kode TP, Nama/Deskripsi TP, Jenis TP, dan keterkaitan satu Materi untuk **setiap TP**, Esensial maupun NonEsensial.
- Kode TP diturunkan dari konteks dan nomor BAB/urutan TP, diverifikasi saat impor. BAB semester genap melanjutkan nomor BAB semester ganjil pada kelas sama.
- **Keputusan final 26 Oktober 2026:** gunakan template Excel ATP **manual** berformat `.xlsx`, tanpa macro, tanpa tombol Proses dan tanpa tabel pembentukan BAB/TP otomatis. Template yang dibuat dari contoh awal memiliki 10 sheet: MULAI DI SINI, RINGKASAN, REFERENSI CP, SEMESTER 1–6, CONTOH MAT VII. Guru mengisi Nomor/Nama/Deskripsi BAB, memilih Nomor BAB TP dari dropdown, lalu mengisi Jenis/Nama/Deskripsi TP, satu Judul Materi, dan Jenis Media untuk **setiap TP**. Urutan TP kolom B dihitung otomatis per BAB sejak V25-3; tidak diketik manual. Kapasitas fisik template saat ini: 20 baris BAB dan 80 baris TP per semester, **bukan batas akademik aplikasi**. Referensi/identitas hasil unduhan diisi server. Impor membaca hanya struktur yang diizinkan, menolak file `.xlsm`/makro dan struktur data TP yang tidak sesuai. Contoh MAT VII **tidak pernah diimpor**.
- Excel untuk **peta akademik**, bukan upload media/soal. Guru mengelola satu file/link media Materi dan Resume/HOTS melalui aplikasi. Baris hilang tidak berarti delete. Unggah → validasi → pratinjau diff → konfirmasi atomik → audit.

## 3. Bank soal
- **Diagnostik TP NonEsensial minimal 10 soal** dan **Sumatif minimal 30 soal** yang valid/aktif. Tayang dan bank tidak boleh kurang dari batas tersebut. Semua soal PG tunggal lima opsi A–E dengan tepat satu kunci.
- Dua unduhan Word Diagnostik/Sumatif per TP memakai referensi `docs/TEMPLATE_SOAL_SLA-SKS.docx`, format `SLA-SKS-WORD-V1` dengan konten guru berupa pertanyaan, opsi, kunci; contoh di `docs/` bukan soal otomatis siswa. Generator unduhan guru membuat slot kosong sesuai konfigurasi.
- Parser DOCX harus menjaga Unicode/Arab RTL, gambar, tabel termasuk bersarang, Equation Word/OMML pada pertanyaan **dan opsi**, tautan Google Drive yang dapat divalidasi. Makro Word, elemen aktif, remote tracking dan HTML berbahaya ditolak; isi disanitasi. Pratinjau lengkap serta konfirmasi wajib, kunci tidak bocor ke siswa.
- **Pra-Semester** memiliki bank tersendiri di tingkat semester yang menggabungkan BAB semester tersebut, minimal 30 soal, menggunakan mesin keamanan dan pola Sumatif. Bukan TP tambahan.

## 4. Materi dan media
- Setiap TP, Esensial maupun NonEsensial, tepat satu media Materi aktif; riwayat Materi lama tidak boleh dihapus. Empat media yang dahulu berada dalam satu TP lama merupakan **data uji v3** dan tidak boleh otomatis dipilih sebagai materi v4 yang aktif.
- PDF Materi **6–40 halaman dengan ukuran maksimal 15 MB** tanpa menolak PDF normal hanya karena metadata atau revisi inkremental. Audio mp3/m4a ≤10 MB bawaan; gambar JPG/PNG/WebP, video menggunakan URL YouTube. Berkas privat, pratinjau Guru, validasi ukuran/MIME/akses mengikuti [dokumen keputusan PDF](KEPUTUSAN-VALIDASI-PDF-MATERI.md).
- Pembelajaran YouTube/audio selesai pada batas **lebih dahulu** antara waktu minimum Guru dan 90% durasi sah; gambar/PDF memenuhi waktu aktif dan halaman yang diwajibkan. Model penghitungan waktu klien dengan checkpoint server empat menit disepakati; unduh audio/gambar/PDF dibuka setelah syarat media selesai, bersamaan dengan akses Resume/HOTS.

## 5. Kesiapan dan penguncian
- BAB siap ditampilkan sebagai kelompok TP; status siap tetap pada TP. Validasi kode, nomor, tipe TP, CP, materi (kedua jenis TP), bank, KKM, waktu/durasi, media, snapshot dan otorisasi dilakukan di Service.
- Admin menentukan KKM default (misalnya 80) dan Guru dapat menentukan KKM khusus. KKM efektif = khusus jika diatur, selain itu default Admin. Jangan menyisipkan KKM 75 sebagai kebijakan baru hanya karena nilai historis v3.
- Bobot **jalur tidak lulus Diagnostik 20% Resume + 20% HOTS + 60% Sumatif**. Jalur lulus Diagnostik **100% Sumatif**. Nilai Diagnostik tidak menjadi nilai akhir. Ujian Sumatif di bawah KKM mendapat satu remedial; skor tertinggi digunakan.
- TP Siap/versi historis, koreksi kategori A/B/C dengan persetujuan Admin, penolakan kategori D, audit dan larangan self-approval tetap. Jangan mereset nilai/progres ketika mengubah Master.

**Format final V25-2 (29 Oktober 2026):** sheet Semester 1–6 memiliki tepat **8 kolom input TP**, yaitu Nomor BAB, Urutan TP, Jenis TP, Nama TP, Deskripsi TP, Judul satu Materi, Jenis media (rencana), Catatan. Kolom Kode TP dan Elemen CP tidak ada dalam input. Kode TP dibuat/diperiksa server; seluruh TP tetap mengacu `cp_mapel_id` dari penugasan G1/kurikulum/fase. Referensi CP aktif hanya dibaca dari sheet REFERENSI CP dan diperiksa terhadap database. Tidak ada alasan membuat SQL untuk penyederhanaan dua kolom tersebut.

## Keputusan mengikat 28–29 Oktober 2026 — Penyiapan Guru vs akses siswa
Guru wajib mengisi **tepat satu judul Materi dan satu jenis media** untuk **setiap** TP Esensial maupun NonEsensial pada ATP V25-2 (kolom F dan G). Tidak boleh menolak atau mengosongkan Materi Esensial. Saat impor dikonfirmasi, rencana Judul Materi dan Jenis Media (F/G) dibuat sebagai tepat satu baris Materi **draf tidak aktif** pada setiap TP baru; tidak dibuat file/tautan semu dan tidak membuka akses siswa. Guru melengkapi berkas/URL serta durasi dan petunjuk sesuai jenis TP melalui editor sebelum TP Siap. Excel bukan proses mengunggah berkas. Jenis TP **tidak mengubah kewajiban Guru menyiapkan konten**, melainkan digunakan Service siswa untuk menegakkan akses/kewajiban aktivitas dan progres sesuai aturan yang berlaku. Data/progres/nilai siswa tidak diubah oleh perbaikan validasi impor.


**Revisi resmi V25-3, 31 Oktober 2026:** Guru tidak mengetik Urutan TP di kolom B. Kolom A TP adalah daftar pilihan Nomor BAB (Defined Name `BABS_1..BABS_6`, bersumber dari A13:A32 semester masing-masing). B39:B118 berisi formula yang dihitung per BAB; server menolak formula lain dan menghitung ulang `urutan_dalam_bab` untuk kode TP, tanpa mempercayai nilai cache Excel. Template V25-2 wajib diunduh ulang. Lihat `KONTRAK-TEMPLATE-ATP-MANUAL-V25.md` untuk batas aman dan contoh.

## Addendum operasional 1 November 2026 — editor TP dan instrumen minimum

Keputusan terbaru pemilik untuk pola **CP → Semester → BAB → TP, tepat satu Materi untuk Esensial dan NonEsensial**:

1. Guru **tidak lagi mengisi kolom terpisah Indikator Ketercapaian** saat mengedit TP. Rumusan/deskripsi TP dan CP operasional menjadi informasi akademik utama. Field DB lama `tp.indikator` tetap tersedia untuk kompatibilitas dan histori, tidak dihapus/reset, tidak menjadi gerbang TP Siap. Aplikasi hanya mengubah Judul dan Rumusan lewat form TP.
2. `/guru/tp/{id}` merupakan halaman utama penyiapan: identitas/rumusan, **satu formulir Sunting Materi yang sudah diimpor**, tautan bank Diagnostik/Sumatif hanya bagi NonEsensial, dan pemeriksaan sebelum terbit. Tidak ada urutan Naik/Turun/Hapus dalam tampilan Materi tunggal; endpoint historis tidak dikembalikan sebagai fitur multi-Materi.
3. Editor media tetap memakai form halaman (bukan modal). URL edit lama `/guru/tp/{id}/materi/{mediaId}/edit` memvalidasi hak G1, status draf dan kepemilikan sebelum mengarahkan ke bagian `#materi-tp` di Detail. Berkas PDF/Audio/Gambar tetap privat dan diunggah dengan validator lama.
4. Kesiapan TP menggunakan **Step Progress** yang dikelompokkan, tetapi pemeriksaan final Service, transaksi, snapshot dan konfirmasi publikasi tetap wajib; UI bukan pengganti pemeriksaan server. Esensial memiliki langkah TP+CP → Materi → Publikasi; NonEsensial menambah Diagnostik dan Sumatif.
5. Rencana satu Materi dari Excel tetap satu baris draf. Guru menyunting record yang ada, bukan menambah data. Perubahan SQL **tidak diperlukan** untuk perubahan UI ini.


## Addendum batas halaman PDF Materi (1 November 2026)

PDF Materi yang baru diunggah maupun file pengganti wajib berisi **minimal 6 dan maksimal 40 halaman** (maksimal 15 MB). PDF 1–5 halaman ditolak sebelum disimpan; data dan berkas historis tidak diubah/hapus otomatis. Pemeriksaan ada di server, dengan petunjuk form sejalan. Validasi file, keamanan dan toleransi metadata/revisi PDF normal tetap mengikuti `KEPUTUSAN-VALIDASI-PDF-MATERI.md`. Perubahan aturan ini tidak membutuhkan SQL.


## Keputusan final penyederhanaan validasi PDF (1 November 2026)

**Menggantikan semua instruksi validasi internal PDF sebelumnya:** PDF Materi hanya dibatasi dari sisi **jumlah halaman 6–40** dan **ukuran maksimal 15 MB**. Pembacaan katalog, objek terkompresi (ObjStm), revisi, metadata dan navigasi dipakai hanya seperlunya untuk memperoleh angka halaman, **tidak menjadi alasan penolakan tersendiri**. File tetap harus dikenali sebagai jenis PDF saat diunggah (ekstensi/MIME); kewenangan Guru, status TP draf, penyimpanan privat, CSRF dan transaksi tetap berlaku di luar pembatasan Materi. PDF historis tidak dihapus atau diubah otomatis. Tidak ada perubahan SQL.

## Addendum 2 November 2026 — pengelolaan bank soal satu pintu

Pada TP NonEsensial, tombol **Kelola Bank Soal** menuju satu halaman per TP dengan dua tab **Diagnostik** dan **Sumatif**. Pemisahan tab, jumlah minimal dan status readiness tetap per jenis: Diagnostik **≥10**, Sumatif **≥30**. Penyusunan bertahap diperbolehkan dalam status TP draf; syarat jumlah berlaku pada pembangkitan template Word resmi dan publikasi TP Siap, bukan melarang Guru menambah soal satu per satu.

Editor soal Guru mendukung pratinjau **tanpa menyimpan** (hasil sanitasi server), lalu simpan eksplisit. List soal memakai pagination server, bukan menyembunyikan baris dengan JavaScript. Review Word bersifat bertahap per butir (expand/collapse) dan memerlukan konfirmasi Guru; konfirmasi ditolak jika bank soal berubah setelah review, termasuk Word identik yang sebelumnya dapat melewati pemeriksaan edit manual.

Equation Word umum ditampilkan melalui MathML (pecahan, superscript, subscript, akar dan konstruksi lainnya yang didukung); **kesetaraan semua konstruksi Equation Word belum dijamin**, sehingga guru harus memeriksa bentuk kompleks terhadap Word sumber dan uji browser. Audio/Video Word tetap hanya menggunakan URL file **Google Drive HTTPS**; sistem memeriksa bentuk URL dan menyediakan link buka untuk uji Viewer/pemutaran **manual**, bukan klaim sudah memeriksa izin file secara otomatis. Jangan menerbitkan TP sebelum Guru memverifikasi media eksternal tersebut. Tidak ada SQL, dump privat maupun perubahan data historis.

### Keputusan KKM tunggal dan koreksi versi (2 November 2026)
- Diagnostik dan Sumatif memakai **satu KKM efektif**. Guru tidak mengisi dua KKM: nilai efektif = khusus Guru jika berbeda dan terset, selain itu mengikuti default Admin. Tanpa mengubah input Guru, KKM Admin langsung berlaku untuk TP draf, tidak disalin sebagai `kkm_khusus`. **Default Admin yang NULL tetap tidak sah**; contoh angka di UI bukan pengaturan resmi sampai disimpan.
- Guru dapat menyunting nilai khusus berulang kali pada status `draf`. Menyimpan kembali angka sama dengan default Admin melepaskan override; simpan tanpa perubahan tidak menambah versi atau audit.
- TP `siap` memiliki KKM tersimpan pada snapshot. Perubahan default Admin sesudahnya tidak mengubah KKM snapshot terbit; edit langsung ditolak. Koreksi A/B/C memakai permohonan Guru, persetujuan Admin, snapshot versi baru; kategori D tidak dibuka. **Implementasi operasional koreksi belum selesai** dan direncanakan dalam checkpoint `CHECKPOINT-KKM-DAN-KOREKSI-TP-2026-11-02.md`.


### Koreksi TP Siap berversi — implementasi V4-0006 (2 November 2026)
- Guru G1 membuka **Ajukan Koreksi TP Siap** dari Kelola TP, memilih kategori A/B/C (D dilarang), memberi alasan, lalu menyunting **salinan usulan**: judul/rumusan TP, KKM tunggal, satu Materi (judul, petunjuk Resume/HOTS, waktu minimum, URL YouTube), mengunggah sumber media PDF/audio/gambar ke penyimpanan privat, serta memperbaiki butir/kunci Diagnostik/Sumatif satu per satu.
- Semua perubahan usulan disimpan di `permohonan_koreksi_tp.usulan_json`; tidak menimpa TP, Materi, Bank Soal, snapshot, atau progres yang telah terbit. Berkas pengganti dapat dipratinjau hanya Guru berizin; file lama tetap untuk siswa snapshot lama. Persetujuan Admin menerbitkan `versi_konten_tp.nomor_versi + 1` di transaksi, mempertahankan riwayat snapshot. Siswa yang sudah mulai tetap terpin pada versi konten semula.
- Admin memiliki menu **Persetujuan Koreksi TP**, tampilan perbandingan sebelum–sesudah, catatan keputusan wajib, tolak/setujui. Satu akun Guru+Admin dilarang memutus usulan yang dibuatnya sendiri. Satu permohonan draf/diajukan per TP melalui UNIQUE generated key; konflik versi dan pergantian Guru ditolak.
- Butir yang diedit dibuat sebagai record soal **baru** saat persetujuan, record historis dinonaktifkan tetapi tidak dihapus; atribut KKM/Materi terbaru ditulis untuk kebutuhan Guru, snapshot siswa lama tetap tidak berubah. Word Equation tidak disunting melalui textarea biasa karena risiko menghilangkan rumus; koreksi kunci masih boleh.
- Instalasi modul melalui **SQL incremental V4-0006**, bukan Migration/Seeder atau impor dump ulang. Semua fitur draf dan form tetap terkunci untuk siswa/pengguna tidak berwenang.
