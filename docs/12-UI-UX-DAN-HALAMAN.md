# 12 — Spesifikasi UI/UX, Peta Halaman dan Status Layar


## 0. KEPUTUSAN UI/UX GLOBAL — EMERALD GLASS ACADEMIC UI (27 Oktober 2026)

**Disetujui pemilik dan didahulukan sebelum pengembangan fitur baru.** Bagian ini menjadi kontrak visual terkini; ketentuan desain terang historis yang bertentangan diganti. Aturan akademik v2.5, akses, database, layanan, dan navigasi Peta ATP/Halaman Semester yang FIX tidak berubah.

1. **Tema**: Dark default, gradasi hijau tua forest–emerald (bukan hitam pekat); Light tersedia. Tema aktif tetap konsisten antarhalaman dan pilihan disimpan lokal di browser jika diizinkan. Tanpa JavaScript, dark tetap tampil sebagai default yang dapat digunakan.
2. **Soft glassmorphism** pada shell/hero/sidebar/header dan kartu ringkasan; permukaan kartu semi-solid dengan gradasi, border tipis, blur dan shadow ringan. Tabel, form, konten soal, materi PDF, editor, serta ujian harus lebih solid agar mudah dibaca.
3. **Sidebar**: desktop expanded sekitar 240px dan collapsed sekitar 72px dengan ikon lokal serta label aksesibel; mobile berupa drawer. Tidak mengubah rute, peran, menu atau hak akses server.
4. **Tombol**: SEMUA tombol tindakan, termasuk secondary, danger, soft dan icon button, memiliki latar jelas. Primary menggunakan gradasi indigo–violet (bukan hijau seperti background) agar CTA mudah dibedakan; state hover/fokus/disabled dan kontras teks wajib jelas pada Dark serta Light. Link teks dan badge tidak boleh tampak sebagai tombol; satu kartu semester clickable tetap satu link utuh tanpa elemen klik bersarang.
5. **Enam kartu gradient resmi**: Emerald, Teal, Blue Emerald, Violet, Amber Olive, Rose Plum untuk semester 1–6. Warna boleh dipakai ulang pada kartu lain. Gradasi berbeda tiap semester wajib jelas tetapi teks tetap kontras.
6. **Warna huruf beragam dan fungsional**: teks netral utama, mint untuk positif, coral/merah untuk penekanan/perhatian, amber untuk menunggu, biru untuk informasi, violet untuk variasi akademik. Status wajib punya teks, tidak bergantung warna saja. Kontras normal minimum 4.5:1 (WCAG AA) pada latar aktual.
7. **Ikon**: SVG subset Lucide di-host lokal, tanpa CDN atau ketergantungan frontend besar; animasi halus 120–200ms, hormati prefers-reduced-motion.
8. **Satu sistem UI untuk empat peran**: variasi konten sesuai tugas Admin, Guru, Siswa, Pimpinan. Layar ujian standalone tanpa sidebar dan dekorasi mengganggu tetap dipertahankan.
9. **Kontrak G03 yang terkunci**: Peta ATP enam semester dalam dua kelompok 1–3/4–6, kartu 3/2/1 responsif dengan data aktual dan filter Gen–Mapel; halaman semester tetap header, kartu per BAB, tabel TP dan dialog native untuk tambah BAB/TP/Paket. Perubahan hanya visual, tidak mengubah tujuan GET/POST, CSRF, otorisasi, isi data.
10. **Tahapan dan QA**: mulai token, tema, shell, galeri, G03; lanjut bertahap ke tabel/form dan tiap role setelah pemeriksaan XAMPP. Uji mode dark/light, expanded/collapsed, 360/390/768/1100 px, kosong/loading/galat, keyboard, fokus, kontras dan kinerja. Fitur tidak disebut FIX tanpa uji pemilik.

Rincian palet, token dan komponen berada di Master Document 13. Ketentuan lama di bawah tetap berlaku sepanjang tidak bertentangan dengan v2.5 dan kontrak UI ini.

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
| S06 | Pengerjaan ujian | PG, timer, navigator, simpan, kirim final, fullscreen | Jeda fullscreen, jeda koneksi, akses ulang tes mandiri, pembatalan khusus Pra-Semester, expired, reload |
| S07 | Hasil | Skor sah, status lulus, percobaan berikutnya atau perlu guru | Tes mandiri tidak selesai/Pra-Semester dibatalkan tidak memiliki skor |
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
| G04 | Editor TP/CP | TP draf, BAB, CP operasional, kode TP, jenis Esensial/NonEsensial, satu Materi untuk kedua jenis TP |
| G05 | Kelola ATP Excel | Halaman khusus `/guru/peta-atp/impor` diakses dari Peta ATP, memilih Gen–Mapel/paket acuan, melihat status enam semester, unduh/unggah XLSX, pratinjau/galat, konfirmasi impor awal tanpa mengubah semester berisi BAB/TP |
| G06 | Pengaturan TP | KKM/bobot, jumlah/durasi soal, syarat karakter, pengaturan bawaan |
| G07 | Materi/berkas | PDF/YouTube/audio/gambar, upload, pratinjau dan status tersedia |
| G08 | Bank soal | Jumlah soal aktif vs diperlukan, diagnostik/sumatif |
| G09 | Bank Word soal | Unduh Word Diagnostik/Sumatif per TP, impor PG lima opsi, pratinjau Equation/gambar/tabel bersarang/Drive, validasi |
| G10 | Antrean penilaian | Resume/HOTS belum dinilai, filter **Gen–Mapel** dan urutan tertua |
| G11 | Penilaian | Lihat jawaban, isi skor 0–100 dan umpan balik, simpan & berikutnya |
| G12 | Monitoring | Matriks siswa × TP **khusus Gen–Mapel yang diampu**, ringkasan tindakan |
| G13 | Detail siswa/ujian | Nilai, histori attempt/insiden, izin akses ulang Diagnostik/Sumatif, pembatalan khusus Pra-Semester |
| G14 | Pratinjau siswa | Berkas/TP dalam mode baca tanpa progres, banner pratinjau |
| G15 | Laporan | Pilih laporan mapelnya dan PDF/Excel |
| G16 | Edit TP Siap belum digunakan | Guru G1 langsung membuka editor TP/Materi/soal saat belum ada siswa yang menggunakan TP; bila telah digunakan, tampilkan status terkunci |
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
| A12 | Referensi CP | Tambah/Sunting CP langsung oleh Admin dengan 5 isian; daftar/filter/ekspor dan histori internal tanpa approval |
| A13 | Koreksi nilai bila dibutuhkan | Usulan dan keputusan atas nilai yang sudah ada (bukan izin membuka TP yang sudah digunakan siswa) |
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
Sistem menampilkan notifikasi penerima untuk: nilai resume/tugas selesai, izin akses ulang tes mandiri oleh Guru atau keputusan pembatalan Pra-Semester, TP Siap tersedia, koreksi nilai selesai diterapkan, sertifikat pertama atau pengganti berhasil terbit, dan keputusan permohonan akademik yang masih berlaku (misalnya koreksi nilai). Sunting langsung CP/BAB/TP yang belum digunakan bukan permohonan approval. Semua hanya dalam aplikasi, tidak melalui email/WhatsApp. Badge `belum dibaca`, daftar dan tautan memeriksa izin kembali saat dibuka.

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
4. **`/admin/kurikulum-cp`**: tabel Daftar CP tetap memiliki pagination dan **Ekspor Excel XLSX** sesuai filter. Form CP **wajib disederhanakan menjadi lima isian** sesuai Master 04; kolom metadata tambahan yang lama tidak ditampilkan/diwajibkan, dan ekspor hanya memuat data sah tanpa formula injection.
5. **`/admin/identitas`**: tambahkan card unggah **Logo Aplikasi**, pratinjau, validasi file gambar asli/dimensi/ukuran, aktifkan setelah sukses; gunakan sebagai **logo antarmuka/login dan favicon** (varian ukuran dari satu sumber diperbolehkan). Fallback ketika belum ada logo; TTD dan background sertifikat tetap diproses menurut dokumen 09.
6. **`/profil` untuk SELURUH ROLE**: card **Foto Profil** paling atas **lebar penuh**; di bawahnya **dua card berdampingan** pada desktop, **Identitas Akun** dan **Kontak Pribadi**. Di mobile keduanya bertumpuk sesuai urutan dan tanpa memotong input. Hak edit mengikuti role (siswa hanya hak yang sah); fitur foto yang membutuhkan moderasi tetap memakai proses persetujuan yang sudah berlaku.
7. Prinsip desain: tetap HTML, CSS kustom `app.css`, JS native, komponen `a-`, keadaan normal/kosong/loading/sukses/galat, akses keyboard/kontras dan responsivitas 360/390/768/1100px. Pagination **bukan memuat seluruh tabel lalu menyembunyikan baris dengan JS**.

## 12. UI penyusunan Guru v2.4
- Kartu konteks Gen–Mapel/Kurikulum, tombol **Unduh Excel ATP enam semester** dan Unggah Peta, pratinjau dengan validasi daftar CP resmi sesuai mapel saja.
- Setiap TP mempunyai status draf/perlu dilengkapi/Siap; TP Esensial maupun NonEsensial masing-masing memiliki tepat satu Materi lengkap; kewajiban Resume/HOTS untuk siswa ditentukan oleh alur siswa, bank Word Diagnostik dan Sumatif **per TP** dengan unduhan terpisah tetapi struktur sama.
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

### Revisi akses ulang tes mandiri (2 November 2026)
- Pada Rincian TP siswa berstatus `perlu_bantuan`, server menentukan kebutuhan akses ulang dari **percobaan Diagnostik/Sumatif terakhir lintas jenis**; tampilkan **hanya satu aksi** sesuai jenis dan tahap (Sumatif normal/remedial tetap satu jenis Sumatif). Jangan menampilkan tombol Diagnostik apabila Sumatif adalah percobaan terakhir yang gagal selesai.
- Setelah siswa mengirim permintaan, tampilkan status **Menunggu izin Guru** tanpa form berulang. Guru melihat satu keputusan yang sudah ditentukan sistem: **Izinkan Akses Ulang**. **Tidak ada isian Alasan keputusan atau dropdown keputusan.** Sebab gangguan, Guru, waktu, tes dan tahap tercatat otomatis di audit; sistem tidak mengubah skor/kuota.
- Layar ujian yang menunjukkan tes tidak selesai harus mengarahkan siswa ke Rincian TP untuk meminta akses ulang; hindari tombol generik yang mengajukan jenis tes keliru.

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

## Addendum G03/G04/G07 1 November 2026 — Detail TP terpadu

- Tabel TP di kartu BAB memiliki **enam kolom berproporsi stabil**, kolom Aksi tidak boleh pecah huruf per huruf; tombol satu kata **Kelola** untuk draf atau **Lihat** untuk Siap. Pada ≤980px tabel berubah menjadi kartu dan aksi tetap dapat disentuh. Warna memakai Emerald Glass, bukan gaya tabel baru yang terpisah.
- Halaman `guru/tp/{id}`: kartu identitas, ringkasan **Step Progress**, form Judul/Rumusan TP tanpa Indikator Ketercapaian terpisah, **tepat satu editor media langsung di halaman**, pengelolaan bank soal NonEsensial melalui halaman khusus, dan tombol pemeriksaan/publikasi.
- Form unggah media tetap lengkap dan tidak dimasukkan ke modal. URL lama Sunting Materi boleh mengarahkan dengan otorisasi ke bagian editor yang sama; tidak boleh membuat form edit ganda dan tidak boleh membuat Materi kedua.
- Halaman kesiapan menampilkan Step Progress sebagai ringkasan utama dengan daftar rincian teknis dapat dibuka (native `details`). Status selesai bersumber dari pemeriksaan final yang sama; informasi kosong/galat, link tindak lanjut, CSRF dan hak akses tidak boleh hilang.
- Penilaian dan media siswa tidak dipercepat/dibuka hanya karena perubahan UI ini. QA wajib Dark/Light, 390/820/desktop, keyboard, validasi file, dan akses lintas Gen.

### Penataan Bank Soal Guru — 2 November 2026
- Detail TP menampilkan **satu tombol Kelola Bank Soal**, bukan dua tombol yang menuju satu halaman identik. Step Progress Diagnostik/Sumatif tetap dua status karena syarat berbeda.
- Halaman Bank Soal mempunyai ringkasan dua jenis; pekerjaan hanya pada **tab aktif**, dengan daftar soal paginasi server (15 per halaman), tombol tambah per butir, unduh/unggah Word per jenis dan riwayat review untuk jenis terpilih.
- Form manual menampilkan tombol **Pratinjau Soal** yang meminta sanitasi HTML dari server tanpa INSERT/UPDATE, diikuti tombol **Simpan Soal** terpisah. Pratinjau berubah atau hilang ketika input diganti; gambar soal menggunakan aset privat.
- Review Word dapat membuka per butir sehingga 30+ soal tidak memenuhi layar sekaligus. Pertanyaan, A–E, kunci dan konten kaya harus dapat ditelaah; link Audio/Video Google Drive membuka sumber untuk verifikasi Viewer/pemutaran oleh Guru. Status kesalahan, loading, sukses dan hak akses tetap eksplisit.


### Penegasan susunan halaman Bank Soal — 2 November 2026
- **Bagian pertama: Ketersediaan Bank Soal**, card informasi permanen di atas dengan dua ringkasan Diagnostik/Sumatif, jumlah soal aktif, batas minimal, jumlah tayang dan status kecukupan jumlah. Tidak menyatakan soal siap secara akademik hanya karena jumlah memadai.
- **Bagian kedua: Bank Soal**, card area kerja tersendiri di bawahnya dengan **dua tab utama Diagnostik dan Sumatif**. Tab aktif jelas terlihat, berlabel jenis dan jumlah soal aktif, memakai navigasi link server `?jenis=...` beserta `aria-current="page"` sehingga tidak bergantung JavaScript. Seluruh daftar 15-per-halaman, tambah/edit, unduh/unggah Word dan riwayat tampil hanya untuk tab tersebut.
- Pesan sukses/gagal berada dalam card area kerja di bawah navigasi tab; ringkasan tidak bercampur dengan form. Kedua card, tab dan ringkasan responsif pada desktop/HP menggunakan token CSS Emerald Glass yang sudah ada. Tidak mengubah controller, service, aturan akademik, SQL atau format impor.


### Revisi Step Progress TP — 2 November 2026
- Langkah 3 Diagnostik: **Minimal 10 soal sah dan pengaturan kegiatan**. Langkah 4 Sumatif: **Minimal 30 soal sah, KKM terset**. Keduanya tetap menjadi langkah independen pada TP NonEsensial, tetapi memakai satu nilai KKM efektif sesuai Master 02/05/06/08/10.
- Kartu dengan status `terpenuhi` mendapatkan latar hijau pekat/terang khas Emerald Glass dan batas solid; kartu belum terpenuhi menggunakan latar netral dan batas putus-putus. Tulisan `✓ Selesai` memakai aksen merah kontras (Dark `#FF9BA6`, Light `#A72C47`) yang ditentukan tema, sementara langkah belum terpenuhi tetap memiliki label tekstual. Tombol Lihat/Lengkapi tetap bekerja.
- **Riwayat catatan sebelum implementasi KKM (telah digantikan 2-11-2026):** audit dump lokal terbaru selesai dan formulir KKM efektif pada dua tab sudah ada; `tp.kkm` historis tidak menjadi fallback draf, tetapi dipakai sebagai angka snapshot TP Siap.


### KKM TP dan Identitas TP ringkas — 2 November 2026 (menggantikan catatan menunggu SQL)
- Halaman **Kelola TP** menampilkan `Identitas TP` sebagai **kartu ringkas** dengan lima pasangan label–nilai (Kode TP, Urutan ATP, Semester, Status, Materi), satu baris pada desktop dan dua kolom pada HP, memakai `dl/dt/dd` tanpa tabel vertikal. Semua informasi lama tetap tampil.
- Halaman **Bank Soal** pada tab Diagnostik dan Sumatif memuat kartu Pengaturan KKM TP yang sama: Default Admin, Khusus Guru, dan KKM efektif. Input KKM terisi **nilai efektif saat ini**; tombol **Simpan KKM Khusus** mengatur override, sedangkan **Ikuti Default Admin** melepas override. **TP Siap yang belum digunakan siswa juga dapat diubah**; setelah pernah digunakan siswa, informasi hanya dibaca. Pesan berhasil/gagal ditampilkan dekat form.
- Admin memiliki menu **Pengaturan KKM** (`admin/kkm`) dengan formulir default 1–100. Jika Admin belum mengisinya, aplikasi menampilkan **Belum ditetapkan**; angka dari kolom lama `tp.kkm` tidak dijadikan default terselubung.

### KKM default yang diwarisi, bukan override palsu (2 November 2026)
- Guru melihat KKM default Admin otomatis di isian kedua tab (satu KKM bersama); tidak perlu menekan Simpan jika tidak mengubahnya. Angka berbeda dapat disimpan sebagai KKM khusus berkali-kali selama draf. Kembali ke angka Admin mengembalikan pewarisan.
- Admin yang belum mengonfigurasi default mendapatkan contoh 80 yang diberi peringatan **belum aktif sampai disimpan**, bukan default tersembunyi. Guru melihat peringatan bila default tidak ada.
- TP Siap **belum dipakai siswa** menampilkan KKM efektif dan editor Guru yang sama seperti Draf; simpan memeriksa ulang tidak ada penggunaan siswa dan menyelaraskan snapshot sebelum siswa memulai. TP yang sudah dipakai menampilkan data readonly dengan alasan kunci yang jelas; tidak menawarkan permohonan kategori A/B/C.


### Penyuntingan langsung CP/BAB/TP (aturan final 2 November 2026)
- **Guru G1**: pada `/guru/tp/{id}` berstatus Siap **tanpa penggunaan siswa** tampil aksi **Sunting TP** dan editor eksisting yang sama dengan Draf (rincian, KKM bersama, Materi/media, soal/Word). Jika sudah ada satu saja siswa menggunakan TP, tampil informasi **Konten TP telah digunakan siswa; penyuntingan terkunci**, tanpa aksi mutasi; server tetap menolak POST.
- **BAB**: tombol Sunting pada isian BAB yang sudah ada tetap tersedia berdasarkan hak G1; jangan sembunyikan hanya karena TP dalam BAB telah Siap. Tidak ada menu pindah TP antar-BAB.
- **Admin**: `/admin/kurikulum-cp` menggunakan form Tambah/Sunting yang sama dengan **tepat lima isian**: Kurikulum, Mata pelajaran, Kode CP, Elemen CP, Rumusan CP resmi. Empat field metadata lama tidak tampak/tidak wajib. Untuk CP yang sudah dipakai TP/siswa, tombol **Sunting CP** tetap aktif dan Simpan langsung berlaku pada referensi CP; audit/versi teknis otomatis tanpa draf/penerbitan manual.
- **Alur kategori lama dicabut**: tidak menampilkan Ajukan Koreksi A/B/C, kategori, alasan wajib, halaman review Admin untuk perubahan TP yang belum digunakan. Jika SQL V4-0006 belum diimpor, tidak ada kewajiban mengimpornya. Kesesuaian kode dan DB dituntaskan pada tahap implementasi.


### UI Pra-Semester per mapel (3 November 2026)
- Guru: kartu bank Pra-Semester pada setiap paket, daftar soal dikelompokkan menurut BAB, indikator jumlah sah/30 dan BAB yang belum terwakili, editor PG A–E serta pengaturan KKM khusus tanpa mencampur KKM TP. Tampil readonly sesudah pernah digunakan siswa.
- Admin: tabel paket Gen–Mapel–Semester, masing-masing pengaturan **jendela boleh mulai** dan **durasi** yang berbeda, serta status belum dijadwalkan/belum mulai/berlangsung/ditutup; jadwal remedial/susulan dan peserta terpilih menjadi tindakan lanjutan setelah skema tersedia.
- Siswa: kartu Pra-Semester terkunci hingga semua TP NonEsensial tuntas; tampil jadwal per mapel dan durasi yang **mulai dihitung saat peserta memulai**, bukan saat jam jadwal dibuka. Setelah ujian berjalan, jendela jadwal boleh berakhir sementara timer tetap berjalan. Pembatalan memerlukan jadwal susulan Admin; tidak ada tombol mengulang mandiri.

### A8-5 — Penyempurnaan UI Pra-Semester
- Tabel `guru/pra-semester` menggunakan tampilan `a-tbl` / `a-pra-table` Emerald Glass, status paket dan bank, **filter Gen** (pilihan terbatas ke penugasan G1), tombol Kelola soal, kosong dan hitungan hasil.
- Tabel `admin/pra-semester` menggunakan tampilan sama dengan **filter Gen, Mapel, Semester, Status ujian**, baris terpilih, jendela WIB dan durasi, tombol Atur jadwal, kosong/reset dan hitungan hasil.
- Kedua halaman: label input, aksi nyata GET, tabel horizontal scroll pada HP, fokus keyboard, warna dan spacing dari design system. Halaman Admin detail memiliki form jadwal remedial/susulan dan tabel calon peserta khusus susulan.
- Siswa hanya melihat tombol mulai jika ada jendela/tahap sesuai dan perangkat desktop memenuhi fullscreen; hasil sebelumnya tetap dapat dilihat. Seluruh perilaku dikendalikan server.

### A8-6 — Pemisahan halaman daftar dan pengelolaan Pra-Semester (3 November 2026)
Keputusan pemilik: **tidak menempatkan area pengelolaan di bawah tabel daftar**.
- **Guru** `GET /guru/pra-semester`: hanya tabel paket + filter Gen. Tombol **Kelola soal** membuka `GET /guru/pra-semester/{paketId}`, halaman detail tersendiri berisi ringkasan, KKM, daftar soal, serta formulir tambah/sunting soal. Tidak merender tabel semua paket dalam halaman detail.
- **Admin** `GET /admin/pra-semester`: hanya daftar paket + filter Gen, Mapel, Semester, Status. Tombol **Atur jadwal** membuka `GET /admin/pra-semester/{paketId}`, halaman detail tersendiri untuk jadwal utama, remedial, susulan, dan peserta susulan. Tidak merender tabel semua paket dalam halaman detail.
- Kedua halaman detail menyediakan breadcrumb dan **Kembali ke daftar**, mempertahankan query filter daftar sebelumnya. Setelah POST sukses/gagal pengguna tetap di halaman detail paket yang sama.
- Tidak mengubah aturan bisnis, akses Guru G1, data/SQL, warna UI global atau endpoint POST.

### A8-7 — Word pada halaman detail Guru Pra-Semester
- Halaman daftar dan filter tetap terpisah. Halaman detail `/guru/pra-semester/{paketId}` menambahkan card **Template & Impor Word** di samping KKM, daftar soal dan editor manual. Kontrol: jumlah template 30–200, unduh DOCX, unggah DOCX maksimal 15 MB, riwayat tahap review, pesan SQL belum terpasang/terkunci.
- Review menggunakan halaman tersendiri yang menampilkan setiap soal dalam `details`, nomor BAB, HTML pertanyaan dan opsi, kunci benar, jumlah media, peringatan review kompleks, checkbox persetujuan dan tombol Konfirmasi. Data belum masuk bank hingga konfirmasi sukses.

### A8-8 — Status Pra-Semester pada daftar TP Siswa (3 November 2026)
- Halaman `siswa/semester/{paketId}` menampilkan kartu Pra-Semester **berdasarkan Service mesin ujian yang sama dengan URL ujian**, tanpa memuat daftar soal, token atau kunci jawaban.
- Kartu memuat TP NonEsensial selesai sah/jumlah wajib, jumlah soal bank sah dan cakupan BAB, jadwal utama WIB beserta durasi individual dan KKM, nilai mandiri bila ada, serta status belum mulai/berjalan/menunggu remedial/susulan/selesai. Link ke ruang ujian tetap dapat dibuka untuk membaca alasan terkunci; server yang menentukan hak mulai.
- Ujian belum tersedia bukan kesalahan tampilan. Hindari tombol mulai palsu di luar jadwal Admin. Saat remedial atau susulan tersedia, tampilkan tahap/jadwal sesuai histori siswa. Tidak mengubah alur Diagnostik, Sumatif, atau UI Admin/Guru yang telah PASS.

### A9 — UI Guru Penilaian dan dua Rekap Nilai (3 November 2026)

1. Sidebar Guru menggunakan menu grup **Rekap Nilai** (native expand/collapse) dengan dua submenu **Nilai TP** dan **Nilai Pra-Semester**; menu aktif dan navigasi di HP harus terlihat jelas.
2. `guru/penilaian`: filter Gen–Mapel, Semester, BAB, Jenis Tugas (Resume/HOTS) dalam grid responsif; reset filter, pager mempertahankan filter aktif, nilai bukan bagian dari dropdown Jenis.
3. `guru/penilaian/resume/{id}` dan `guru/penilaian/hots/{id}`: Petunjuk dari Materi versi siswa serta Jawaban siswa adalah **dua kartu berbeda** dengan background kontras lembut berbeda pada Emerald Glass dark/light. Konten snapshot tidak boleh ditimpa tampilan.
4. `guru/rekap-nilai`: filter Gen–Mapel, Semester, Jenis TP, BAB. Jenis TP **NonEsensial default** karena TP Esensial tidak dinilai; filter juga berlaku saat ekspor. Nilai NULL dan 0 dibedakan.
5. `guru/rekap-nilai/pra-semester`: halaman tersendiri untuk seluruh siswa Gen dan skor sah ujian Pra-Semester, dengan unduhan XLSX tersendiri; tidak membentuk nilai akhir semester.

### A9-2 — Audit visual berdasarkan tangkapan layar Guru (3 November 2026)

Empat halaman yang direvisi: `guru/rekap-nilai`, `guru/rekap-nilai/pra-semester`, `guru/penilaian`, serta detail `guru/penilaian/hots/{id}`/`resume/{id}`.

- Rekap Nilai TP: empat metrik **Siswa Gen, Final, Sementara, Belum Ada Nilai** ditata dengan grid responsif, bukan daftar vertikal. Tabel 10 kolom menggunakan komponen `a-tbl` dan `a-tabel-gulir` Emerald Glass dengan lebar minimum dan scroll horizontal; nama siswa dan identitas BAB/TP boleh menjadi dua baris tetapi kolom nilai tidak terhimpit.
- Rekap Nilai Pra-Semester: tabel 7 kolom juga memakai komponen tabel yang benar; nama siswa, KKM, normal, remedial, nilai terbaik dan status lebih terbaca. Filter Gen–Mapel dan Semester terbagi dua kolom seimbang pada desktop dan satu pada HP. Navigasi dua jenis rekap memakai komponen tab tautan konsisten.
- Antrean Penilaian: empat filter punya proporsi adaptif dengan field Gen–Mapel lebih lebar; pada laptop dua kolom, pada HP satu kolom. Tabel/aksi/hasil filter dan parameter asli tidak berubah.
- Detail Penilaian: formulir skor dan umpan balik dua kolom seimbang pada desktop, satu kolom di HP. Petunjuk snapshot memakai aksen emerald; jawaban siswa aksen biru-violet pada Dark/Light, **termasuk ketika Mode Dokumentasi aktif**.
- Mode Dokumentasi `data-documentation="on"` memindahkan sidebar ke bagian atas untuk screenshot panjang. Submenu Rekap Nilai menjadi popover `details` pada hover/fokus keyboard, bukan baris bertumpuk yang memperpanjang header. Mode panel normal tetap mempertahankan sidebar.
- Perubahan hanya presentation layer. Kontrol filter, ekspor, paginasi, penilaian, CSRF, otorisasi, data, dan SQL tidak dimodifikasi.

### A10-1 — Tiga halaman laporan akademik (3 November 2026)

1. `siswa/laporan`: daftar paket Mapel–Semester **yang telah terbuka**, berupa kartu responsif; jangan memberikan tombol ke semester yang terkunci/dilewati.
2. `siswa/laporan/{paketId}`: progres TP NonEsensial (kartu angka dan progress bar), rincian skor sah per BAB/TP dalam tabel gulir horizontal, kemudian **Hasil Pra-Semester pada kartu berbeda**; nilai kosong/0 ditampilkan dengan benar dan tidak ada tombol ubah.
3. `pimpinan/laporan`: filter paket Gen–Mapel–Semester, tabel paginasi semua siswa, progres sah dan tombol pratinjau. `pimpinan/laporan/{paket}/siswa/{id}`: memakai tampilan detail read-only yang sama, dibatasi Gen.
4. Tampilkan keadaan belum ada paket/siswa/TP/ujian, tanpa membuat nilai atau skor dummy. Pada 360/390px, komponen metrik berubah menjadi satu kolom dan tabel yang lebar tetap bisa digulir, bukan memperkecil tulisan sampai tak terbaca.

### A11-1 — Layar pemeriksaan sertifikat Admin (3 November 2026)

- Menu **Admin → Pemeriksaan Sertifikat** mempunyai filter paket Gen–Mapel–Semester aktif, tabel siswa paginasi dan dua angka per siswa: TP NonEsensial tuntas dengan Sumatif sah serta Nilai TP Final. Gunakan badge teks **Layak akademik** atau **Belum layak**, serta `details` yang dapat dibuka untuk melihat seluruh alasan, bukan hanya warnanya.
- Card **Kesiapan Administratif** menampilkan kelengkapan identitas madrasah, tautan ke pengaturan identitas, dan pemberitahuan **TTD PNG/template belum tersedia pada A11-1**. Jangan menampilkan tombol *Terbitkan*, *Unduh Sertifikat*, atau QR yang tidak berfungsi. Penerbitan penuh baru pada A11-2.
- Kondisi paket kosong, siswa kosong, semester dilewati, TP belum Final, data belum lengkap, dan parameter filter tidak sah ditangani. Tabel menggunakan `a-tbl` dan `a-tabel-gulir` dengan scroll horizontal di HP; Dark/Light/Mode Dokumentasi tidak mengubah akses.

### A11-2 — Antarmuka Sertifikat (3 November 2026)

- Admin → Sertifikat: status identitas/TTD aktif, tautan kelola aset, tabel siswa dan aksi berdasarkan kelayakan. Siswa **Layak akademik** hanya mendapatkan tombol **Terbitkan Sertifikat** bila identitas madrasah lengkap dan TTD aktif; setelah terbit terlihat **Unduh PDF** dan panel **Revisi satu per satu** dengan alasan wajib.
- Admin → Template & TTD: dua kartu unggah terpisah, riwayat aset, status aktif, pratinjau gambar berizin, **pratinjau PDF dummy** dan aktivasi versi. Background kosong tetap memakai template fallback; TTD kosong menghalangi penerbitan dan diberi pesan.
- Siswa → Sertifikat Saya: daftar sertifikat aktif maupun yang `digantikan` dengan unduhan PDF hanya miliknya. Jika kosong, tampil pesan tanpa tombol palsu.
- Publik → Verifikasi QR: halaman sederhana responsif, status berlaku/digantikan, rujukan nomor versi pengganti, daftar TP/jenis/nilai yang boleh dipublikasikan; tanpa NISN, TTD, PDF atau kontak. Tidak ada tombol cabut/hapus.
- CSS tetap Emerald Glass kustom; tabel berkolom lebar boleh digulir di HP. Uji 360/390/768/1100px, dark/light, Mode Dokumentasi, invalid/missing upload, gagal render, ulang submit, dan historis.

### A11-3 — QA layout PDF hasil sertifikat (3 November 2026)

Sertifikat resmi cetak **A4 landscape** harus mengisi bidang cetak secara proporsional, bukan menempatkan seluruh bingkai pada dua pertiga teratas. Hirarki visual: identitas madrasah, judul/nomor, identitas penerima, tabel BAB/TP, lalu QR verifikasi dan area TTD kepala madrasah. Nama siswa serta nilai harus lebih mudah dibaca pada kertas, tetap dengan bahasa formal dan tanpa teks teknis internal penilaian.

Pratinjau PDF di Admin memakai **data dummy** (dua BAB, empat TP), bukan siswa sebenarnya. Untuk paket dengan banyak TP, legibilitas dan kelengkapan semua baris diutamakan daripada menekan tulisan kecil agar tetap satu halaman. Jika diperlukan, konten lanjut ke halaman berikutnya secara aman dan tanda tangan tidak boleh menutupi tabel. Tidak menambahkan tombol regenerate otomatis PDF yang sudah terbit.

### A12-1 — Dashboard Akademik Pimpinan (3 November 2026)

- GET `/pimpinan` tidak lagi menampilkan beranda generik akun aktif, melainkan **Dashboard Akademik** baca-saja. KPI dari database resmi: siswa unik Gen yang masuk filter, paket aktif ditugaskan, pasangan siswa×TP NonEsensial wajib, pasangan tuntas dengan Sumatif sah, pasangan dengan nilai TP Final (termasuk 0), sertifikat berlaku, penugasan Guru per Gen–Mapel dan antrean final Resume/HOTS yang belum dinilai.
- Filter **Gen / Mapel / Semester** bersifat opsional, tombol Terapkan dan Reset berfungsi; pilihan dibatasi paket aktif pada `penugasan_paket`. Angka dihitung ulang mengikuti filter; pagination tabel paket 12 baris/halaman mempertahankan filter.
- Tabel per paket berisi siswa wajib (semester yang dilewati dikecualikan), jumlah TP NonEsensial, selesai sah/target, Final, sertifikat berlaku, antrean penilaian, status Pra-Semester yang terpisah, dan tautan **Lihat Monitoring** ke halaman A10-1.
- Cakupan 0/0 ditampilkan **Belum ada target**, bukan 0% atau 100%. Grafik meter native `progress` disertai rasio serta angka teks; tidak ada grafik tanpa alternatif. Tidak ada Nilai Akhir Semester atau gabungan nilai Pra-Semester.
- Responsif 360/390/768/1100px: kartu KPI grid 5/3/2/1 kolom sesuai ruang, filter 3 kolom desktop/1 HP, tabel lebar boleh scroll horizontal, fokus keyboard terlihat, mode Dark/Light/Dokumentasi tetap tersedia.
- Tidak menampilkan data NISN, jawaban siswa, token ujian, maupun tombol POST/mutasi untuk Pimpinan. Filter rusak, halaman kosong, paket tanpa TP, role salah, dan kegagalan server mendapat penjelasan yang sesuai.

### A12-2 — Dashboard Guru per penugasan G1 (3 November 2026)

- GET `/guru` kini menampilkan ringkasan pekerjaan, bukan sekadar status akun aktif. Filter utama memilih **Gen–Mapel penugasan Guru aktif** (bukan semua Gen) dan Semester (semua/1–6). Reset dan tautan ke Peta ATP/semester mempertahankan pilihan.
- KPI: Paket Semester aktif, TP Siap, TP Draf, jumlah TP dengan Materi aktif bukan tepat satu (Esensial dan NonEsensial sama-sama wajib satu Materi), antrean final Resume/HOTS belum dinilai, siswa unik per paket berstatus perlu bantuan, dan percobaan Diagnostik/Sumatif sedang berjalan. Antrean membedakan NULL (belum dinilai) dari skor 0 (sudah dinilai).
- Card aksi Guru mempunyai tautan nyata ke **Antrean Penilaian**, **Permintaan Akses Ulang**, **Peta ATP**, dan **Pra-Semester**. Tabel Semester memecah semua indikator per paket, tombol Buka Semester berizin G1.
- Tidak mengklaim satu Materi aktif berarti media/kesiapan final sudah lengkap; langkah itu tetap diuji melalui Pemeriksaan Kesiapan TP. Tidak menggabungkan nilai TP dan Pra-Semester serta tidak menghitung nilai akhir semester.
- Kondisi tidak ada penugasan, tidak ada paket pada semester filter, filter invalid, dan role ditolak mempunyai pesan khusus. Kartu dan tabel responsif Dark/Light, HP scroll tabel, fokus keyboard. Tidak ada angka dummy pada aplikasi.

### A12-3 — Beranda Siswa berbasis progres akademik nyata (3 November 2026)

- GET `/siswa` menggantikan halaman status akun dengan **Dashboard Pembelajaran Saya**, menggunakan satu tombol utama yang mengarah ke TP terbuka berikutnya (atau semester/pembelajaran jika TP masih menunggu Guru). Tidak ada tombol Mulai Ujian palsu.
- Hero menampilkan Gen dan **semester mulai**, tidak mengarang kalender semester berjalan. KPI mapel yang ditugaskan, jumlah semester yang sudah boleh diakses, TP tuntas sah/target, nilai TP Final, TP memerlukan bantuan dan sertifikat berlaku. Skor Final 0 tidak boleh disulap menjadi belum ada.
- Daftar paket yang dapat dibuka saja, dengan progres **bersegmen** jika jumlah TP wajib ≤12 atau native progress bila lebih besar, status teks, rasio jumlah dan aksi berizin. Esensial tidak dihitung. Paket 0 TP wajib tidak dianggap 100%; tampilkan pesan jelas.
- Link Laporan Akademik dan Sertifikat ditempatkan terpisah. Pra-Semester sebagai checkpoint di laporan, tanpa kontribusi pada nilai TP, sertifikat atau rata-rata akhir semester.
- Dark/Light, HP 360/390/768/1100px, Mode Dokumentasi dan keyboard: KPI 5/3/2/1 kolom, paket dua kolom/HP satu kolom, alt teks jumlah progres untuk pembaca layar, fokus tombol jelas. Data belum ada, semesternya terkunci, semua TP selesai, role salah, dan server gagal mempunyai keluaran yang jujur.
- Rak lencana resmi S11 belum diaktifkan di A12-3 karena belum ada mekanisme penetapan lencana yang disetujui dan dilengkapi data; tidak menggunakan placeholder seolah-olah lencana benar-benar diterbitkan. Pencapaian yang ditampilkan adalah **angka akademik sah**, bukan poin/ranking fiktif.

### A12-3 UI QA — Screenshot XAMPP Dashboard Siswa (3 November 2026)

Tangkapan layar Siswa Simulasi 05 menunjukkan beranda dapat ditampilkan: Gen dan Semester mulai, 0/2 TP selesai sah, nilai Final 0, bantuan 0, sertifikat 0, serta progres semester 0%. **Tampilan secara umum sesuai**, tetapi satu kartu Semester 4 sebelumnya hanya memenuhi sisi kiri halaman dan dua tombol `Lanjutkan TP` tidak menjelaskan TP tujuan.

Perbaikan: jika hanya satu paket semester boleh dibuka, kartunya menggunakan **seluruh lebar panel**, identitas Semester/Mapel di sisi kiri dan progres/tombol di sisi kanan pada laptop/desktop, kembali menjadi satu kolom pada layar kecil. Pada hero dan kartu, tampilkan **kode dan judul TP berikutnya** hanya bila katalog memberikan `akses.buka`; TP draf/terkunci tidak boleh ditampilkan melalui judul atau link. Jika belum ada TP berizin, tampilkan navigasi ke Semester dan pesan yang benar, tanpa tombol ujian palsu. Warna segmen belum selesai lebih jelas, tetap memperhatikan Dark/Light dan aksesibilitas.

Catatan: nama akun simulasi yang mengandung kata "Diagnostik Dibatalkan" adalah **label data uji**, bukan bukti langsung bahwa `progres_tp.status` saat screenshot masih `perlu_bantuan`. Jangan mengganti KPI 0 hanya karena nama akun; cek progres dan riwayat akses ulang jika ada selisih yang dilaporkan.

### A12-4 — Dashboard Admin operasional (3 November 2026)

Beranda Admin `GET /admin` **mempertahankan dashboard prototipe yang sudah terpasang**, empat KPI referensi (Gen, Mapel, penugasan G1 dan pasangan tanpa Guru) serta seluruh shortcut. Tambahan operasional berupa 6 KPI: akun aktif (semua peran), siswa terdaftar (termasuk belum aktif), TP Siap, TP Draf, foto profil staf menunggu persetujuan, dan sertifikat **berlaku** dari pointer aktif. TP Siap/Draf bukan nilai pembelajaran dan tidak memberi Admin hak menyunting konten Guru.

Panel **Perhatian Admin** hanya menampilkan temuan yang mempunyai dasar data: Gen/Mapel kosong, penugasan G1 kosong, foto staf belum disetujui, serta TTD belum aktif/berkas checksum tidak sah. Setiap peringatan mempunyai tautan ke modul yang sudah ada; tidak ada tombol penanganan palsu. Panel **Kesiapan Aset dan Operasional** memisahkan TTD aktif-terverifikasi, kapasitas disk yang dapat dibaca pada volume `writable/`, dan status **cron/backup belum diperiksa**. Angka disk tidak otomatis berarti sehat; tidak boleh ada simulasi backup/cron berjalan.

Grid kartu mengikuti Emerald Glass Dark/Light, 3→2→1 untuk 1100/768/360px, alert mudah dibaca dan tindakan dapat diakses keyboard. State tanpa perhatian berarti tidak ada *temuan pada pemeriksaan ini*, **bukan** sistem seluruhnya sehat atau siap dihosting. Tidak ada perubahan pada nilai, percobaan ujian, sertifikat historis atau struktur SQL.

### A12-4 QA UI Admin — audit 14 tangkapan layar XAMPP (3 November 2026)

Area yang diperiksa: Dashboard Admin, Pengelola, Siswa, reset sandi, Gen & Tahun Ajaran, Mapel & Guru, daftar/sunting CP, Identitas Madrasah, KKM, daftar/detail Jadwal Pra-Semester, persetujuan foto, dan profil.

**Perbaikan yang diperlukan:** panel rincian konfirmasi reset sandi berlatar terang pada Dark sehingga nama/username/status tidak terbaca; daftar Pengelola/Siswa menyempit karena form tambah dan tabel 5 kolom dipasang berdampingan; form Tambah Mapel meninggalkan bidang kosong; tombol filter CP terlalu dekat dengan tabel; label situs web menampilkan `(opsional) (opsional)`; banner historis K2 tetap muncul di halaman G1 baru; angka semua kombinasi Gen×Mapel yang belum ditugaskan disalahpahami sebagai tugas wajib.

**Keputusan visual:** reset sandi memakai permukaan dan teks bertoken Dark/Light. Pada daftar Pengelola/Siswa, form tambah berada **di atas tabel lebar penuh**, field form dua kolom desktop dan satu kolom HP; filter, aksi per baris, impor/ekspor XLSX dan pagination dipertahankan. Form tambah Mapel melebar dengan dua input berdampingan di desktop; tabel penugasan tetap terpisah di bawah. Filter CP memiliki ruang aksi sendiri, tidak menyentuh lima isian CP. Label situs web hanya satu penanda opsional. Angka Gen×Mapel adalah **kombinasi potensial** (bukan kewajiban mengisi semua 20 mapel ke setiap Gen), sementara kebijakan G1/angka historis tidak diubah.

**Layar tanpa temuan fungsional visual yang mewajibkan edit:** Gen & Tahun Ajaran, editor Sunting CP lima isian, KKM Default, jadwal Pra-Semester utama/susulan/remedial, antrean Persetujuan Foto kosong, dan Profil. Screenshot tidak membuktikan proses POST atau Dark/Light/HP otomatis PASS; semua fungsi tetap memerlukan uji klik manual jika belum dilakukan. Tidak ada SQL atau perluasan aturan bisnis.

### A12-4 QA UI lanjutan — lima screenshot XAMPP (3 November 2026)

Screenshot terbaru membuktikan daftar Pengelola/Siswa sekarang memakai form dan tabel **lebar penuh** dengan Dark Mode yang konsisten. Catatan perapian tersisa: pilihan peran Admin/Guru/Pimpinan terlalu tinggi dalam fieldset, tombol Buat Akun terletak di samping input Sandi, Gen & Tahun Ajaran memakai dua baris grid bersama sehingga Daftar Tahun Ajaran turun mengikuti tinggi form Gen, Identitas Madrasah hanya memakai sebagian ruang desktop, dan panel Foto Profil menempatkan pratinjau kecil sendirian di sisi kiri dengan area kosong luas.

Solusi UI: tiga pilihan peran sejajar pada desktop dan satu kolom HP, tombol Buat Akun ditempatkan setelah semua field pada baris sendiri. Setiap kolom halaman Gen & Tahun Ajaran menyusun **form di atas daftar miliknya sendiri**, agar tabel Tahun tidak tertahan form Gen. Identitas resmi memakai dua kolom input singkat pada desktop, alamat textarea selebar panel dan tombol di bawah. Profil Guru/Admin/Pimpinan menata foto + petunjuk/unggah berdampingan pada desktop, tetap satu kolom HP. **Tidak mengubah alur Persetujuan Foto, data akun, hak peran, nilai, atau kelengkapan identitas resmi.**
