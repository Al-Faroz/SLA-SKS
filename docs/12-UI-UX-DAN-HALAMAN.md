# 12 — Spesifikasi UI/UX, Peta Halaman dan Status Layar

## 1. Prinsip wajib
UI menggunakan **bahasa Indonesia** yang konsisten: kata kerja jelas, tombol utama tunggal per layar, status dan alasan terkunci, pesan galat memberi solusi, *loading/success/empty/error/permission-denied*, konfirmasi untuk aksi permanen, *breadcrumbs* pada halaman dalam, dan tidak ada tombol yang tidak punya endpoint. Desain mobile-first untuk siswa, desktop-first untuk editor kompleks Guru/Admin, responsif sampai 360 px. Siswa diberi pengalaman pembelajaran yang menarik, ruang ujian tenang; tidak ada papan peringkat/streak/poin. Empat dashboard tidak berarti empat sistem CSS berbeda.

## 2. Layout bersama
- **Publik:** P01 Login, P02 Verifikasi QR, P03 halaman 403/404/500, P04 Kebijakan Privasi. Login memakai logo sekolah dan ikon aplikasi yang dikelola Admin. Halaman privasi memuat teks disahkan sekolah, tanggal versi, dan tersedia tanpa login.
- **Panel:** sidebar modern yang ringkas dan responsif, header nama+role aktif, notifikasi internal, menu pengumuman dan Bantuan/FAQ, profil/ubah sandi, logout `POST`. Di HP sidebar menjadi menu ringkas/hamburger; jangan menampilkan seluruh menu panjang secara horisontal tanpa ruang.
- **Ujian:** layar fokus tanpa sidebar dan pop-up non-ujian, hanya timer server, soal, navigator, status simpan, insiden serta kendali fullscreen. Pada gangguan koneksi tampil overlay status otoritatif.
- **Pimpinan:** indikator `Hanya baca`, filter dan grafik dengan alternatif data tabel.

### Alur tampilan v2.4 sebelum katalog layar
1. **Admin – Pengelola dan Siswa:** dua menu sidebar terpisah. Tiap daftar mempunyai header ringkas (judul + jumlah), area pencarian/filter, toolbar **Tambah / Import / Export**, tabel yang mudah dipindai, aksi per baris **Lihat/Ubah/Reset Sandi/Nonaktif**, loading/kosong/galat dan konfirmasi aksi. Tombol hanya muncul jika endpoint siap.
2. **Impor XLSX:** pilih jenis pengguna → unduh template → unggah → validasi dan pratinjau (jumlah baris baru/diubah/ditolak, sumber kesalahan per baris/kolom) → konfirmasi → hasil akhir. Tidak mengubah data saat pratinjau; tidak otomatis menghapus akun yang tak muncul di file.
3. **Reset sandi:** tombol berada pada baris **Pengelola atau Siswa**, bukan reset massal. Konfirmasi menampilkan akun tujuan; sesudah server berhasil, sandi sementara acak ditampilkan sekali dengan kontrol salin dan anjuran penyampaian privat; sesi lama dicabut, login berikutnya wajib ubah sandi. Jangan menaruhnya pada query string atau unduhan.
4. **Guru:** sebelum TP/soal/penilaian, pilih **Gen–Mapel yang benar-benar diampu**. Judul, breadcrumb, filter dan daftar selalu menampilkan konteks Gen sehingga tidak tertukar. Gen yang tidak ditugaskan tidak ditampilkan dan akses URL langsung ditolak.
5. **Profil:** akun staf mengedit foto/kontak pribadi/sandi sendiri; bukan nama resmi, peran, NIP atau Gen. Siswa hanya melihat profil profesional Guru pengampunya; kontak dan data autentikasi Guru disembunyikan.
6. **Konsistensi visual:** panel terinspirasi referensi TailDash dan publik/login terinspirasi UIdeck Appwind dengan komposisi lebih kompak. Desain disesuaikan warna SLA-SKS, tidak menyalin template atau memakai Tailwind sebagai dependency.

## 3. Katalog layar siswa
| ID | Layar | Konten dan aksi utama | Kondisi khusus |
|---|---|---|---|
| S01 | Beranda | Lanjutkan belajar, semester berjalan, progres bersegmen, rak lencana | Tidak ada paket, semua tuntas, menunggu TP |
| S02 | Semester | Daftar TP urut sesuai ATP, status siap/terkunci/tuntas | TP pertama belum Siap |
| S03 | Detail TP | Ringkasan TP, CP terkait, daftar Materi dan alur Diagnostik/Resume/HOTS/Sumatif | Tidak mengungkap TP draf |
| S04 | TP | Daftar langkah, indikator akses, materi, diagnostik/sumatif | Diagnostik sudah lulus, pembelajaran sukarela |
| S05 | Petunjuk ujian | Durasi, percobaan, syarat perangkat/fullscreen, tombol Mulai | Fullscreen tidak didukung ⇒ pesan gunakan desktop/laptop; jangan buat percobaan |
| S06 | Pengerjaan ujian | PG, timer, navigator, simpan, kirim final, fullscreen | Jeda fullscreen, jeda koneksi, batal, expired, reload |
| S07 | Hasil | Skor sah, status lulus, percobaan berikutnya atau perlu guru | Batal tidak memiliki skor |
| S08 | Materi | PDF book/scroll, YouTube, audio, gambar, progres waktu | File hilang, offline, materi belum tersedia |
| S09 | Resume | Editor teks, `Simpan draf` manual, `Kirim final` | Draf belum disimpan, gagal simpan, sudah terkirim |
| S10 | HOTS | Pertanyaan tugas, editor, `Simpan draf`, `Kirim final` | Batas karakter, locked setelah kirim |
| S11 | Pencapaian | Lencana saat ini dan histori paket | Kosong, telah dilihat |
| S12 | Nilai saya | Nilai TP, cara tuntas, sementara/final, rata-rata | Menunggu Guru, nilai setelah koreksi |
| S13 | Sertifikat | Sertifikat berlaku, arsip `digantikan`, unduh PDF | TTD belum ada, belum memenuhi syarat, koreksi identitas |

## 4. Katalog layar Guru
| ID | Layar | Isi dan tombol utama |
|---|---|---|
| G01 | Beranda | Antrean penilaian, siswa perlu bantuan, TP yang perlu media, kesiapan mapel, ujian berjalan |
| G02 | Semester saya | **Pilih Gen–Mapel yang diampu aktif**, lalu semester 1–6 dan paket khusus Gen |
| G03 | Paket/ATP enam semester | Unduh/unggah Excel pemetaan TP/Materi, pratinjau diff, daftar TP urut dan status |
| G04 | Editor TP/CP | TP draf, BAB, CP operasional, kode TP, jenis Esensial/NonEsensial, satu Materi jika NonEsensial |
| G05 | Impor Peta ATP | Unduh Excel 6 semester terpersonalisasi, unggah, pratinjau/galat/diff, konfirmasi draf |
| G06 | Pengaturan TP | KKM/bobot, jumlah/durasi soal, syarat karakter, pengaturan bawaan |
| G07 | Materi/berkas | PDF/YouTube/audio/gambar, upload, pratinjau dan status tersedia |
| G08 | Bank soal | Jumlah soal aktif vs diperlukan, diagnostik/sumatif |
| G09 | Bank Word soal | Unduh Word Diagnostik/Sumatif per TP, impor PG lima opsi, pratinjau Equation/gambar/tabel bersarang/Drive, validasi |
| G10 | Antrean penilaian | Resume/HOTS belum dinilai, filter **Gen–Mapel** dan urutan tertua |
| G11 | Penilaian | Lihat jawaban, isi skor 0–100 dan umpan balik, simpan & berikutnya |
| G12 | Monitoring | Matriks siswa × TP **khusus Gen–Mapel yang diampu**, ringkasan tindakan |
| G13 | Detail siswa/ujian | Nilai, histori attempt/insiden, keputusan pembatalan, izin ulang |
| G14 | Pratinjau siswa | Berkas/TP dalam mode baca tanpa progres, banner pratinjau |
| G15 | Laporan | Pilih laporan mapelnya dan PDF/Excel |
| G16 | Koreksi TP | Ajukan kategori A/B/C, diff/versi, status keputusan, riwayat |
| G17 | Koreksi nilai/kunci ujian | Usulan nilai guru sebelumnya, peninjauan soal/kunci dan dampak akademik |
| G18 | Pengumuman | Buat/ubah/arsip pengumuman global secara sederhana |

## 5. Katalog layar Admin
| ID | Layar | Isi dan tombol utama |
|---|---|---|
| A01 | Beranda | Kesehatan sistem, akun, TP siap, antrean persetujuan, TTD belum ada, disk dan cron |
| A02 | Pengelola | Daftar staf Admin/Guru/Pimpinan, pencarian/role/status, tambah/edit/nonaktif, reset sandi **per akun**, impor/ekspor XLSX |
| A02S | Siswa | Daftar siswa, filter Gen/semester/status, NISN dan biodata, tambah/edit/nonaktif, reset sandi **per akun**, impor/ekspor XLSX |
| A03 | Form Pengelola/Siswa | Identitas dan validasi berbeda: staf nama/NIP/role; siswa NISN/Gen/semester mulai; koreksi NISN juga mengubah username dengan audit |
| A04 | Impor dan ekspor pengguna | Template XLSX terpisah, validasi/pratinjau galat, konfirmasi dan idempotensi; ekspor sesuai filter dan bebas hash/sandi |
| A05 | Mapel/Guru per Gen | Matriks Gen × Mapel, satu Guru aktif per pasangan, dropdown hanya akun aktif ber-role Guru, riwayat dan alasan pergantian |
| A06 | Gen/Semester mulai | Kelola angkatan dan penimpaan siswa dengan cek progres |
| A07 | Kesiapan TP | Matriks mapel × semester, siap/rencana, kekurangan |
| A08 | Pengaturan | Nilai bawaan, ujian profil Ketat tetap, akun, berkas, identitas madrasah |
| A09 | Audit | Filter peristiwa, aktor dan waktu, detail sebelum/sesudah aman |
| A10 | Laporan | Unduh laporan seluruh cakupan |
| A11 | Pemeliharaan | Kapasitas disk, status cron, akun terkunci, backup manual/SOP |
| A12 | Referensi CP | Daftar sumber/versi, usulan Guru, setujui/tolak/perbaikan langsung Admin |
| A13 | Permohonan akademik | Koreksi TP, nilai pengganti/kunci ujian; bandingkan diff/dampak dan putuskan |
| A14 | Identitas sekolah | Nama, alamat, NPSN, NSM, telepon, email wajib, web opsional, kepala madrasah |
| A15 | Logo Aplikasi/Favicon/TTD | Satu upload logo aplikasi digunakan sebagai logo dan favicon; TTD dan template sertifikat tetap aset terlindung terpisah |
| A16 | Template sertifikat | JPG/PNG A4 landscape, cek upload, preset posisi, pratinjau, aktivasi global/fallback |
| A17 | Sertifikat dan koreksi biodata | Lihat versi, revisi satu per satu, nomor/QR baru, tanpa cabut/hapus |
| A18 | Pengumuman dan FAQ | Kelola pengumuman global dan FAQ login-only |
| A19 | Kebijakan Privasi | Admin memasukkan teks kebijakan resmi yang disahkan sekolah, versi/tanggal berlaku; halaman baca publik |

## 6. Katalog layar Pimpinan
| ID | Layar | Isi |
|---|---|---|
| M01 | Beranda | KPI total siswa, TP selesai, guru/anggaran penilaian, sertifikat, peringatan |
| M02 | Progres | Grafik status mapel/semester/Gen, tabel alternatif |
| M03 | Siswa | Matriks progres siswa × mapel, rapor baca-saja |
| M04 | Guru dan Kesiapan | TP Siap, penilaian tertunda, kegiatan mapel |
| M05 | Ujian | Distribusi skor, TP sulit, pelanggaran/ujian batal |
| M06 | Laporan | Seluruh ekspor baca-saja sesuai AB-41 |

## 7. Halaman bersama yang sering terlupakan
| ID | Nama | Perilaku |
|---|---|---|
| U01 | Ganti sandi | Wajib pada login pertama; tidak membuka modul lain sampai selesai |
| U02 | Profil sendiri | Admin/Guru/Pimpinan dapat ubah foto, kontak pribadi, sandi; nama resmi, NIP, role dan penugasan baca saja (Admin berwenang mengelola) |
| U02G | Profil Guru untuk siswa | Hanya Guru aktif yang ditugaskan pada Gen siswa; nama, foto disetujui, daftar mapel; **tanpa NIP/telepon pribadi/data keamanan** |
| U02S | Profil siswa | Siswa melihat biodata dan Gen sendiri, dapat ganti sandi; koreksi identitas resmi oleh Admin |

| U03 | Notifikasi | Daftar per penerima; unread badge, mark read, tautan berizin |
| U04 | Pengumuman | Daftar global aktif untuk akun login, tanpa segmentasi role/Gen |
| U05 | Bantuan/FAQ | Pertanyaan sesuai menu, **harus login**; Hubungi Admin berupa kontak resmi |
| U06 | Pencarian dan filter | Paginasi, indikator jumlah, kosong/hasil tidak ada, reset filter |
| P04 | Kebijakan Privasi publik | Teks kebijakan resmi sekolah, versi dan kontak sekolah; tanpa login |

## 8. Notifikasi internal yang ditampilkan
Sistem menampilkan notifikasi penerima untuk: nilai resume/tugas selesai, keputusan Guru tentang ujian batal/ulang, TP Siap tersedia atau koreksinya terbit, koreksi nilai selesai diterapkan, sertifikat pertama atau pengganti berhasil terbit, dan keputusan permohonan akademik kepada Guru. Semua hanya dalam aplikasi, tidak melalui email/WhatsApp. Badge `belum dibaca`, daftar dan tautan memeriksa izin kembali saat dibuka.

## 9. Detail interaksi wajib semua layar
**Setiap layar wajib memiliki:** tujuan, siapa dapat membuka, data sumber, komponen yang digunakan, form validation, aksi sukses, aksi gagal, akses browser back, loading, kosong, readonly, locked, responsive, uji minimal. Jangan merender tombol Simpan jika tidak ada perubahan kecuali desain memerlukan. Gunakan pesan error yang spesifik: bukan `Terjadi kesalahan` tanpa instruksi. Untuk aksi berisiko tampilkan modal konfirmasi dengan nama objek.

### Form penting
- **Impor:** progres unggah, hasil pratinjau, daftar kesalahan baris, opsi unduh laporan galat, konfirmasi perubahan.
- **Draf manual:** saat siswa mengetik tampil `Perubahan belum disimpan`; tombol `Simpan draf` mengirim server; saat sukses `Draf tersimpan pukul ...`; navigasi keluar dengan perubahan belum disimpan diberi peringatan. Jangan menjanjikan autosave.
- **Ujian:** layar S06 tidak menampilkan notifikasi/pengumuman, tombol kirim dikonfirmasi, status offline jelas; hitung waktu di server.
- **Sertifikat:** preview desain Admin dengan data *dummy* (bukan data siswa nyata), zona aman untuk QR/TTD, indikator upload valid, link lihat PDF sebelumnya bila digantikan.
- **Pembetulan biodata:** Admin melihat data lama/baru dan jumlah sertifikat yang perlu diperbaiki **satu per satu**, bukan memaksa semua sertifikat diganti otomatis.

### Kriteria khusus UX v2.4
- **Desktop Admin/Guru:** sidebar sekitar 228–240px, header sekitar 60px, tipografi isi 14–15px, judul halaman 24–26px, kartu 16–20px padding, gap antarkartu 12–16px; tabel/toolbar padat tetapi tetap jelas. Gunakan grid dua kolom bila form atau ringkasan menguntungkan; hindari satu kolom sangat panjang tanpa alasan.
- **Publik:** komposisi hero dan login seimbang, tidak seperti marketing site; tidak ada CTA daftar publik, harga, atau landing page promosi. Teks penting dan tombol utama langsung terlihat.
- **Mobile 360/390px:** ukuran sentuh tetap minimal 40px, bidang formulir tidak menyempit/terpotong, sidebar terkendali, scroll tabel hanya bila perlu, judul serta input tidak melebihi viewport.
- Sebelum merefaktor seluruh halaman, siapkan **satu prototipe dashboard Admin dan satu tampilan publik/login** dengan token v2.4 untuk persetujuan pemilik. Setelah disetujui, terapkan komponen bersama secara berurutan tanpa merombak aturan bisnis.

## 10. Aksesibilitas dan lebar responsif
Minimal siswa 360px, layar sentuh target ≥40px, kontras teks 4,5:1, fokus keyboard terlihat, pesan status tidak hanya warna, semua field berlabel, error terhubung dengan input, grafik mempunyai ringkasan/tabel; mendukung `prefers-reduced-motion`. Ujian desktop mengutamakan keyboard angka/panah dan tampilan tanpa distraksi. Di layar sempit, tabel panjang boleh scroll horizontal dengan label header tetap terlihat.

## 11. Revisi layout halaman yang WAJIB pada v2.4 (22 Oktober 2026)
1. **`/admin/pengelola`**: card `admin-akun_form` **baris pertama full-width/landscape**, field membentuk grid horizontal responsif; card `admin-akun_daftar` **di bawah** dan berisi pencarian/filter/tabel serta **pagination server-side** dengan nomor halaman, jumlah total, empty state dan filter yang bertahan antarhalaman.
2. **`/admin/siswa`**: susunan sama; `admin-akun_form` atas full-width, `admin-akun_daftar` di bawah dengan pagination server-side. Pertahankan menu impor/ekspor, reset, CSRF dan validasi yang sudah ada.
3. **`/admin/mapel-guru`**: tabel **Penetapan Guru per Gen** mengatur kontrol pada kolom terpisah: **Guru Pengampu (select)** | **Alasan Penetapan/Pergantian (input)** | **Aksi Simpan (button)**, satu baris konteks Gen–Mapel. Tidak bertumpuk vertikal pada desktop, responsif/scroll aman di HP; **pagination server-side** dan filter kontekstual.
4. **`/admin/kurikulum-cp`**: **hanya tabel Daftar CP dan Status Versinya** yang diubah: pagination server-side serta **Ekspor Excel XLSX** sesuai filter/status, mencakup kode/mapel/kurikulum/fase/elemen/versi/status/identitas tanggal yang sah dan aman dari formula injection. Formulir dan panel lain **tidak ikut dirombak**, kecuali penyederhanaan form CP yang sudah diputuskan dalam dokumen 04.
5. **`/admin/identitas`**: tambahkan card unggah **Logo Aplikasi**, pratinjau, validasi file gambar asli/dimensi/ukuran, aktifkan setelah sukses; gunakan sebagai **logo antarmuka/login dan favicon** (varian ukuran dari satu sumber diperbolehkan). Fallback ketika belum ada logo; TTD dan background sertifikat tetap diproses menurut dokumen 09.
6. **`/profil` untuk SELURUH ROLE**: card **Foto Profil** paling atas **lebar penuh**; di bawahnya **dua card berdampingan** pada desktop, **Identitas Akun** dan **Kontak Pribadi**. Di mobile keduanya bertumpuk sesuai urutan dan tanpa memotong input. Hak edit mengikuti role (siswa hanya hak yang sah); fitur foto yang membutuhkan moderasi tetap memakai proses persetujuan yang sudah berlaku.
7. Prinsip desain: tetap HTML, CSS kustom `app.css`, JS native, komponen `a-`, keadaan normal/kosong/loading/sukses/galat, akses keyboard/kontras dan responsivitas 360/390/768/1100px. Pagination **bukan memuat seluruh tabel lalu menyembunyikan baris dengan JS**.

## 12. UI penyusunan Guru v2.4
- Kartu konteks Gen–Mapel/Kurikulum, tombol **Unduh Excel ATP enam semester** dan Unggah Peta, pratinjau dengan validasi daftar CP resmi sesuai mapel saja.
- Setiap TP mempunyai status draf/perlu dilengkapi/Siap; TP NonEsensial memiliki tepat satu Materi dan tugas Resume/HOTS terkait, bank Word Diagnostik dan Sumatif **per TP** dengan unduhan terpisah tetapi struktur sama.
- Preview soal menampilkan konten kaya Word **pada pertanyaan dan A–E**, tabel di dalam tabel, Equation, gambar, pemutar audio/video Google Drive bila valid; soal/catatan yang gagal tidak dapat dikonfirmasi.


### S08B YouTube/audio — tampilan syarat tercepat (26-10-2026)

S08 menampilkan durasi media, minimum Guru, 90% media, **batas efektif yang lebih pendek**, waktu pemutaran yang benar-benar diakui dan bilah kemajuan berdasarkan batas efektif (bukan hanya satu syarat lain). Penghitung media yang tersimpan dan sudah memenuhi batas langsung membuka editor Resume melalui refresh hasil verifikasi server; HOTS tetap setelah Resume final. Jika API pemutar gagal, tampilkan galat yang dapat dimengerti tanpa memberi kredit waktu palsu. Menekan link refresh tidak bypass pemeriksaan server.


### S09/S10 — status pengiriman dan pembatasan paste (26 Oktober 2026)

Editor Resume/HOTS menampilkan indikator jumlah karakter sesuai batas
snapshot dan alasan gagal kirim final tepat di editor. Tombol Simpan draf
dan Kirim final menggunakan form action berbeda menuju endpoint
POST terotorisasi. Redirect hasil kiriman menuju bagian Resume/HOTS
yang bersangkutan. Input pada POST gagal dipulihkan; final sukses
ditampilkan sebagai jawaban terkunci sesudah reload.

Browser menolak paste/copy/cut dan drag/drop biasa sebagai pencegahan
dasar; tidak menghalangi Unicode atau pengetikan normal. Kebijakan ini
bukan bukti keaslian tulisan dan tidak bisa menjamin semua cara tempel
dihentikan. Server tetap memeriksa final tanpa JavaScript.


### S08 Audio / Gambar / PDF — perbaikan sinkronisasi status (26 Oktober 2026)

Indikator progres harus memperlihatkan syarat yang sebenarnya: audio menampilkan waktu putar sah dan batas efektif, gambar menampilkan waktu total yang diakui server serta sisa durasi, dan PDF menampilkan waktu total, jumlah halaman selesai, daftar beberapa halaman yang belum memenuhi minimal 15 detik dan alasan belum tuntas. Bilah media tidak boleh 100% hanya karena satu syarat selesai. Begitu server menyatakan `media_selesai`, GET ulang merender Resume tanpa perlu klik tombol manual; HOTS tetap sesudah Resume Final. Gagal/tertunda heartbeat tidak boleh dinyatakan sukses palsu.

### Status presisi Gambar dan PDF (26 Oktober 2026)

Waktu gambar/PDF dibaca dari heartbeat sah 5 detik. Gambar yang tidak terlihat/fokus tidak memberi kredit; kembali aktif memulai baseline baru, tanpa mereset akumulasi waktu. PDF menampilkan detik baca untuk halaman aktif (misalnya 10/15 detik) dan halaman lain yang belum selesai. Zoom/fullscreen/resize tidak memberi kredit otomatis dan tidak menghanguskan progres halaman yang telah sah. Hanya status media_selesai dari server yang mengaktifkan Resume melalui pemuatan ulang.

**Area PDF:** selain pemeriksaan halaman PDF.js yang sudah render, waktu hanya dihitung ketika area pembaca setidaknya setengah terlihat di layar dan tab/jendela aktif. Status menampilkan sisa durasi per halaman; tidak memberi kredit ketika siswa sedang melihat bagian halaman web lainnya.


### Unduh materi setelah media_selesai

Pada kartu Media Audio/Gambar/PDF, tampilkan tombol `Unduh Materi` yang nonaktif dengan penjelasan ketika waktu belajar belum selesai. Setelah server menetapkan `media_selesai`, tampilkan tautan unduh asli. URL harus memiliki gerbang backend yang sama, tidak cukup kontrol tombol saja; respons attachment menggunakan nama berkas aman dan tidak membocorkan path privat.

## 13. Kontrak layar pola baru v2.5 (menggantikan alur layar tanpa BAB)
**Guru:** Peta ATP enam semester → Card BAB (nama ≤50, ringkasan ≤225) → daftar TP tiap BAB (kode, nama ≤50, deskripsi asli ≤225, badge jenis) → editor 1 Materi untuk NonEsensial → Diagnostik/Word → Resume/HOTS → Sumatif/Word → kesiapan. Esensial hanya data akademik, tidak menampilkan tombol unggah media/soal. Guru dapat mengisi langsung atau Excel setelah template diadaptasi. Nomor BAB berlanjut antarsemester berpasangan; perlihatkan kode TP contoh `BIND.8.1.1`.
**Siswa:** Beranda → Semester (progres hanya NonEsensial) → Card BAB → TP Esensial (informasi tanpa tombol pengerjaan) atau NonEsensial (Diagnostik → gate → Materi/Resume/HOTS bila belum lulus → Sumatif wajib → remedial 1 bila perlu → Selesai walau nilai < KKM).
**Pra-Semester:** card/halaman terkunci sebelum seluruh NonEsensial selesai; setelah selesai tampil tanggal/jam serentak Admin, belum berlangsung/berjalan/ditutup, fullscreen preflight, alasan tidak bisa mulai. Admin memiliki pengaturan jadwal dan validasi tabrakan.
**Nilai dan ekspor:** Guru filter Gen–Mapel/Semester, tombol **Export Nilai** aktif walau baru satu nilai; kolom TP kode/nama dan Pra-Semester; tampil kosong, bukan 0, ketika nilai belum ada. Siswa tidak melihat nilai akhir semester gabungan.
**Sertifikat:** daftar semua TP per BAB; Esensial tanpa angka, NonEsensial bernilai. Ketentuan Pra-Semester sebagai syarat terbit belum diputuskan, jangan membuat tombol gate yang diasumsikan.
Semua layar memiliki normal/kosong/loading/sukses/gagal/locked, responsive HP dan desktop sesuai batas ujian; kontrol yang terlihat wajib berfungsi. Catatan layar implementasi v3 terdahulu di bagian lain dokumen ini adalah histori, **bukan aturan yang mengalahkan §13**.


### G03 — Dialog tambah BAB dan TP (revisi 27 Oktober 2026)
- Tombol **Tambah BAB** di setiap semester yang boleh diisi membuka modal `<dialog>` fokus ke Nama BAB; tombol **Tambah TP** pada setiap BAB membuka modal tersendiri berisi konteks BAB, Nama TP, Deskripsi TP, dan jenis Esensial/NonEsensial.
- Tombol **Batal/Tutup**, tombol `Escape` serta penutupan modal memulihkan fokus ke pemicu; form tetap memakai POST, CSRF dan otorisasi G1 serta tidak menyimpan tanpa menekan **Simpan**.
- Nama BAB/TP maksimal **50** karakter; Deskripsi BAB/TP tetap **225** karakter. Wajib divalidasi di HTML dan Service. Dialog memiliki judul aksesibel, fokus keyboard, scroll di HP, status loading di tombol saat submit, dan pesan sukses/galat setelah redirect.
- Daftar BAB/TP tetap berupa tabel/kartu pada halaman Peta ATP; formulir tidak memperpanjang keenam semester secara permanen.


### G03 — Arsitektur Peta ATP dua tingkat (27 Oktober 2026)
**Halaman Peta ATP** hanya menampilkan pemilih Gen–Mapel G1 dan **enam kartu navigasi semester** dalam dua kelompok visual: Semester 1–3 dan Semester 4–6 (masing-masing tiga kartu pada desktop). Urutan semester tetap 1–6; warna latar enam kartu berbeda tetapi tetap memakai palet lembut dengan kontras teks dan fokus keyboard. Seluruh kartu menjadi tautan langsung ke `guru/peta-atp/semester/{1..6}?pilihan={indeks G1}`, bukan tautan semu. Statistik dari database terotorisasi: jumlah BAB, jumlah TP, jumlah Esensial/NonEsensial, status paket (belum ada/draf/aktif/arsip); tidak boleh dipalsukan atau mencampur Gen.

**Halaman detail semester** merupakan tempat Guru menyusun ATP. Bagian atas memuat identitas Gen–Mapel, kelas, semester, status, statistik BAB/TP, petunjuk pengisian, serta tombol modal **Tambah BAB** (atau **Buat Paket Draf** bila belum ada paket dan CP memenuhi prasyarat). Di bawahnya setiap BAB ditampilkan sebagai kartu terpisah dengan warna background bergantian, nomor, nama (≤50), deskripsi (≤225), jumlah TP, tabel TP di dalam BAB, dan tombol modal **Tambah TP** yang hanya tersedia jika paket sah/tidak arsip dan semester diizinkan. Kartu BAB tanpa TP menampilkan empty state; data TP historis yang tidak memiliki BAB tetap dipertahankan dan hanya dapat dilihat, tidak dipindah otomatis.

**Aturan interaksi:** Semester yang dilewati karena Gen mulai lebih akhir tetap dapat dibuka untuk melihat keadaan tetapi tidak menyediakan aksi tambah. Tambah BAB/TP menggunakan modal native aksesibel dengan Escape/Batal, fokus, CSRF, POST Service G1, pencegahan submit ganda; setelah POST kembali ke halaman semester yang sama. Tidak mengubah model akademik ataupun status draf/siap. Formulir tidak ditampilkan memanjang pada halaman peta. Pada desktop tiga kartu per baris; tablet dua; HP satu. Tiap halaman harus menangani normal, kosong, draf, arsip, galat, terkunci, dan sukses.


### Pola UI untuk modul selanjutnya
Jika satu modul mempunyai daftar kelompok dan rincian yang panjang, gunakan pola **halaman ringkasan card sebagai navigasi → halaman rincian satu kelompok → toolbar tindakan di bagian atas → card/tabulasi data per subkelompok → modal native untuk tambah data sederhana**. Terapkan hanya pada modul yang cocok (misalnya semester/BAB/TP), tanpa memaksakan modal untuk editor materi, ujian, impor atau form kompleks yang memerlukan halaman khusus. Pertahankan konteks filter, breadcrumb, hak akses, status kosong/terkunci, warna lembut yang bermakna dan validasi server. Pola ini menjadi rujukan komposisi UI berikutnya tanpa menambah fitur bisnis.
