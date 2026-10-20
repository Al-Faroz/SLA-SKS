# 12 — Spesifikasi UI/UX, Peta Halaman dan Status Layar

## 1. Prinsip wajib
UI menggunakan **bahasa Indonesia** yang konsisten: kata kerja jelas, tombol utama tunggal per layar, status dan alasan terkunci, pesan galat memberi solusi, *loading/success/empty/error/permission-denied*, konfirmasi untuk aksi permanen, *breadcrumbs* pada halaman dalam, dan tidak ada tombol yang tidak punya endpoint. Desain mobile-first untuk siswa, desktop-first untuk editor kompleks Guru/Admin, responsif sampai 360 px. Siswa diberi pengalaman pembelajaran yang menarik, ruang ujian tenang; tidak ada papan peringkat/streak/poin. Empat dashboard tidak berarti empat sistem CSS berbeda.

## 2. Layout bersama
- **Publik:** P01 Login, P02 Verifikasi QR, P03 halaman 403/404/500, P04 Kebijakan Privasi. Login memakai logo sekolah dan ikon aplikasi yang dikelola Admin. Halaman privasi memuat teks disahkan sekolah, tanggal versi, dan tersedia tanpa login.
- **Panel:** sidebar modern yang ringkas dan responsif, header nama+role aktif, notifikasi internal, menu pengumuman dan Bantuan/FAQ, profil/ubah sandi, logout `POST`. Di HP sidebar menjadi menu ringkas/hamburger; jangan menampilkan seluruh menu panjang secara horisontal tanpa ruang.
- **Ujian:** layar fokus tanpa sidebar dan pop-up non-ujian, hanya timer server, soal, navigator, status simpan, insiden serta kendali fullscreen. Pada gangguan koneksi tampil overlay status otoritatif.
- **Pimpinan:** indikator `Hanya baca`, filter dan grafik dengan alternatif data tabel.

### Alur tampilan v2.3 sebelum katalog layar
1. **Admin – Pengelola dan Siswa:** dua menu sidebar terpisah. Tiap daftar mempunyai header ringkas (judul + jumlah), area pencarian/filter, toolbar **Tambah / Import / Export**, tabel yang mudah dipindai, aksi per baris **Lihat/Ubah/Reset Sandi/Nonaktif**, loading/kosong/galat dan konfirmasi aksi. Tombol hanya muncul jika endpoint siap.
2. **Impor XLSX:** pilih jenis pengguna → unduh template → unggah → validasi dan pratinjau (jumlah baris baru/diubah/ditolak, sumber kesalahan per baris/kolom) → konfirmasi → hasil akhir. Tidak mengubah data saat pratinjau; tidak otomatis menghapus akun yang tak muncul di file.
3. **Reset sandi:** tombol berada pada baris **Pengelola atau Siswa**, bukan reset massal. Konfirmasi menampilkan akun tujuan; sesudah server berhasil, sandi sementara acak ditampilkan sekali dengan kontrol salin dan anjuran penyampaian privat; sesi lama dicabut, login berikutnya wajib ubah sandi. Jangan menaruhnya pada query string atau unduhan.
4. **Guru:** sebelum BAB/soal/penilaian, pilih **Gen–Mapel yang benar-benar diampu**. Judul, breadcrumb, filter dan daftar selalu menampilkan konteks Gen sehingga tidak tertukar. Gen yang tidak ditugaskan tidak ditampilkan dan akses URL langsung ditolak.
5. **Profil:** akun staf mengedit foto/kontak pribadi/sandi sendiri; bukan nama resmi, peran, NIP atau Gen. Siswa hanya melihat profil profesional Guru pengampunya; kontak dan data autentikasi Guru disembunyikan.
6. **Konsistensi visual:** panel terinspirasi referensi TailDash dan publik/login terinspirasi UIdeck Appwind dengan komposisi lebih kompak. Desain disesuaikan warna SLA-SKS, tidak menyalin template atau memakai Tailwind sebagai dependency.

## 3. Katalog layar siswa
| ID | Layar | Konten dan aksi utama | Kondisi khusus |
|---|---|---|---|
| S01 | Beranda | Lanjutkan belajar, semester berjalan, progres bersegmen, rak lencana | Tidak ada paket, semua tuntas, menunggu BAB |
| S02 | Semester | Daftar BAB urut, status siap/terkunci/tuntas | BAB pertama belum Siap |
| S03 | BAB | TP dan indikator progres | Tidak mengungkap BAB draf |
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
| G01 | Beranda | Antrean penilaian, siswa perlu bantuan, BAB yang perlu media, kesiapan mapel, ujian berjalan |
| G02 | Semester saya | **Pilih Gen–Mapel yang diampu aktif**, lalu semester 1–6 dan paket khusus Gen |
| G03 | Paket/BAB | Rencana BAB, target Siap, daftar urut, tombol Buka/Tambah BAB |
| G04 | Struktur BAB/TP/CP | Draf BAB, TP/kode/indikator/ATP, pemilih CP versi resmi, aturan urutan |
| G05 | Impor Excel | Unduh 8 sheet, unggah, tabel galat/peringatan, diff, konfirmasi; draf saja |
| G06 | Pengaturan TP | KKM/bobot, jumlah/durasi soal, syarat karakter, pengaturan bawaan |
| G07 | Materi/berkas | PDF/YouTube/audio/gambar, upload, pratinjau dan status tersedia |
| G08 | Bank soal | Jumlah soal aktif vs diperlukan, diagnostik/sumatif |
| G09 | Editor soal | Pertanyaan PG 2–5 opsi, kunci, status aktif, validasi |
| G10 | Antrean penilaian | Resume/HOTS belum dinilai, filter **Gen–Mapel** dan urutan tertua |
| G11 | Penilaian | Lihat jawaban, isi skor 0–100 dan umpan balik, simpan & berikutnya |
| G12 | Monitoring | Matriks siswa × TP **khusus Gen–Mapel yang diampu**, ringkasan tindakan |
| G13 | Detail siswa/ujian | Nilai, histori attempt/insiden, keputusan pembatalan, izin ulang |
| G14 | Pratinjau siswa | Berkas/TP dalam mode baca tanpa progres, banner pratinjau |
| G15 | Laporan | Pilih laporan mapelnya dan PDF/Excel |
| G16 | Koreksi BAB | Ajukan kategori A/B/C, diff/versi, status keputusan, riwayat |
| G17 | Koreksi nilai/kunci ujian | Usulan nilai guru sebelumnya, peninjauan soal/kunci dan dampak akademik |
| G18 | Pengumuman | Buat/ubah/arsip pengumuman global secara sederhana |

## 5. Katalog layar Admin
| ID | Layar | Isi dan tombol utama |
|---|---|---|
| A01 | Beranda | Kesehatan sistem, akun, BAB siap, antrean persetujuan, TTD belum ada, disk dan cron |
| A02 | Pengelola | Daftar staf Admin/Guru/Pimpinan, pencarian/role/status, tambah/edit/nonaktif, reset sandi **per akun**, impor/ekspor XLSX |
| A02S | Siswa | Daftar siswa, filter Gen/semester/status, NISN dan biodata, tambah/edit/nonaktif, reset sandi **per akun**, impor/ekspor XLSX |
| A03 | Form Pengelola/Siswa | Identitas dan validasi berbeda: staf nama/NIP/role; siswa NISN/Gen/semester mulai; koreksi NISN juga mengubah username dengan audit |
| A04 | Impor dan ekspor pengguna | Template XLSX terpisah, validasi/pratinjau galat, konfirmasi dan idempotensi; ekspor sesuai filter dan bebas hash/sandi |
| A05 | Mapel/Guru per Gen | Matriks Gen × Mapel, satu Guru aktif per pasangan, dropdown hanya akun aktif ber-role Guru, riwayat dan alasan pergantian |
| A06 | Gen/Semester mulai | Kelola angkatan dan penimpaan siswa dengan cek progres |
| A07 | Kesiapan BAB | Matriks mapel × semester, siap/rencana, kekurangan |
| A08 | Pengaturan | Nilai bawaan, ujian profil Ketat tetap, akun, berkas, identitas madrasah |
| A09 | Audit | Filter peristiwa, aktor dan waktu, detail sebelum/sesudah aman |
| A10 | Laporan | Unduh laporan seluruh cakupan |
| A11 | Pemeliharaan | Kapasitas disk, status cron, akun terkunci, backup manual/SOP |
| A12 | Referensi CP | Daftar sumber/versi, usulan Guru, setujui/tolak/perbaikan langsung Admin |
| A13 | Permohonan akademik | Koreksi BAB, nilai pengganti/kunci ujian; bandingkan diff/dampak dan putuskan |
| A14 | Identitas sekolah | Nama, alamat, NPSN, NSM, telepon, email wajib, web opsional, kepala madrasah |
| A15 | Logo/ikon/TTD | Tiga aset terpisah, upload, preview, aktifkan, versi sejarah |
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
| M04 | Guru dan Kesiapan | BAB Siap, penilaian tertunda, kegiatan mapel |
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
Sistem menampilkan notifikasi penerima untuk: nilai resume/tugas selesai, keputusan Guru tentang ujian batal/ulang, BAB Siap tersedia atau koreksinya terbit, koreksi nilai selesai diterapkan, sertifikat pertama atau pengganti berhasil terbit, dan keputusan permohonan akademik kepada Guru. Semua hanya dalam aplikasi, tidak melalui email/WhatsApp. Badge `belum dibaca`, daftar dan tautan memeriksa izin kembali saat dibuka.

## 9. Detail interaksi wajib semua layar
**Setiap layar wajib memiliki:** tujuan, siapa dapat membuka, data sumber, komponen yang digunakan, form validation, aksi sukses, aksi gagal, akses browser back, loading, kosong, readonly, locked, responsive, uji minimal. Jangan merender tombol Simpan jika tidak ada perubahan kecuali desain memerlukan. Gunakan pesan error yang spesifik: bukan `Terjadi kesalahan` tanpa instruksi. Untuk aksi berisiko tampilkan modal konfirmasi dengan nama objek.

### Form penting
- **Impor:** progres unggah, hasil pratinjau, daftar kesalahan baris, opsi unduh laporan galat, konfirmasi perubahan.
- **Draf manual:** saat siswa mengetik tampil `Perubahan belum disimpan`; tombol `Simpan draf` mengirim server; saat sukses `Draf tersimpan pukul ...`; navigasi keluar dengan perubahan belum disimpan diberi peringatan. Jangan menjanjikan autosave.
- **Ujian:** layar S06 tidak menampilkan notifikasi/pengumuman, tombol kirim dikonfirmasi, status offline jelas; hitung waktu di server.
- **Sertifikat:** preview desain Admin dengan data *dummy* (bukan data siswa nyata), zona aman untuk QR/TTD, indikator upload valid, link lihat PDF sebelumnya bila digantikan.
- **Pembetulan biodata:** Admin melihat data lama/baru dan jumlah sertifikat yang perlu diperbaiki **satu per satu**, bukan memaksa semua sertifikat diganti otomatis.

### Kriteria khusus UX v2.3
- **Desktop Admin/Guru:** sidebar sekitar 228–240px, header sekitar 60px, tipografi isi 14–15px, judul halaman 24–26px, kartu 16–20px padding, gap antarkartu 12–16px; tabel/toolbar padat tetapi tetap jelas. Gunakan grid dua kolom bila form atau ringkasan menguntungkan; hindari satu kolom sangat panjang tanpa alasan.
- **Publik:** komposisi hero dan login seimbang, tidak seperti marketing site; tidak ada CTA daftar publik, harga, atau landing page promosi. Teks penting dan tombol utama langsung terlihat.
- **Mobile 360/390px:** ukuran sentuh tetap minimal 40px, bidang formulir tidak menyempit/terpotong, sidebar terkendali, scroll tabel hanya bila perlu, judul serta input tidak melebihi viewport.
- Sebelum merefaktor seluruh halaman, siapkan **satu prototipe dashboard Admin dan satu tampilan publik/login** dengan token v2.3 untuk persetujuan pemilik. Setelah disetujui, terapkan komponen bersama secara berurutan tanpa merombak aturan bisnis.

## 10. Aksesibilitas dan lebar responsif
Minimal siswa 360px, layar sentuh target ≥40px, kontras teks 4,5:1, fokus keyboard terlihat, pesan status tidak hanya warna, semua field berlabel, error terhubung dengan input, grafik mempunyai ringkasan/tabel; mendukung `prefers-reduced-motion`. Ujian desktop mengutamakan keyboard angka/panah dan tampilan tanpa distraksi. Di layar sempit, tabel panjang boleh scroll horizontal dengan label header tetap terlihat.
