# 06 — Alur Pembelajaran Mandiri Percepatan SKS

## 1. Akses dan urutan
Siswa belajar melalui paket semester **khusus Gen siswa**. Urutan: `Semester → TP → Materi`; tidak ada BAB sebagai langkah atau layar tersendiri. Semester sebelum `COALESCE(siswa.semester_mulai, angkatan.semester_mulai)` berstatus **Dilewati** tanpa nilai/sertifikat. Semester dan TP dibuka berurutan; TP draf berikutnya tidak menghalangi penyelesaian TP Siap sebelumnya, tetapi tampil **"Menunggu TP berikutnya disiapkan Guru"**. Jangan menampilkan semester selesai 100% sebelum **semua TP yang direncanakan** benar-benar disiapkan dan tuntas.

Paket/TP/materi/soal hanya milik Gen yang terkait; siswa Gen A tidak dapat mengakses Gen B dengan URL. Guru penilai adalah G1 aktif Gen–Mapel siswa. Versi TP dipin ketika siswa pertama kali memulai; perubahan Guru/versi konten tidak menghapus progres.

## 2. Mesin alur TP
```mermaid
flowchart TD
 A[TP dibuka dan versi dipin] --> B[Diagnostik wajib]
 B --> C{Nilai >= KKM?}
 C -->|Ya| D[TP tuntas; nilai diagnostik]
 D --> E[Materi boleh dilihat sukarela tanpa kirim Resume atau HOTS]
 D --> F[TP berikutnya saat tersedia]
 C -->|Tidak| G[Materi 1: media]
 G --> H[Resume 1 kirim final]
 H --> I[HOTS 1 kirim final]
 I --> J{Masih ada Materi?}
 J -->|Ya| K[Materi berikutnya berurutan]
 K --> H2[Resume dan HOTS Materi tersebut final]
 H2 --> J
 J -->|Tidak| L[Sumatif TP]
 L --> M{Nilai >= KKM?}
 M -->|Ya| N[TP tuntas]
 M -->|Tidak| O[Percobaan sah berikutnya atau perlu bantuan Guru]
```

Alur ini merupakan **kebijakan akademik internal madrasah untuk SKS percepatan dua tahun**. Diagnostik wajib; jika lulus, siswa tidak wajib menuntaskan media/Resume/HOTS dan nilainya dari Diagnostik. Jika tidak lulus, **setiap Materi memiliki Resume dan HOTS sendiri** dan setelah semua Materi final siswa mengikuti Sumatif. Guru tidak boleh memaksakan siswa jalur lulus Diagnostik mengirim tugas resmi; belajar sukarela tidak mempengaruhi nilai.

## 3. Syarat materi dan media
- PDF melalui PDF.js lokal: seluruh halaman pernah dilihat, waktu aktif default 15 detik/halaman. YouTube dan audio dihitung waktu putar nyata default ≥90% durasi; gambar default terlihat ≥30 detik. Guru dapat mengatur `min_waktu_detik` untuk draf.
- Heartbeat server kira-kira tiap **10 detik**; satu detak menambah maksimum 20 detik dan interval minimal 5 detik. Tab/background/jeda/refresh tidak boleh dihitung sebagai waktu belajar aktif palsu. Validasi jam server; jangan mempercayai durasi dari klien.
- Satu Materi berikutnya dibuka setelah media wajib selesai serta **Resume dan HOTS Materi sebelumnya terkirim final**; tidak perlu menunggu skor Guru. Simpan draf tidak dianggap final. Jika ada lebih dari satu jenis media, syarat materi memeriksa seluruh yang diwajibkan.
- Progres TP berdasarkan langkah jalur aktif; TP lulus Diagnostik langsung 100%. Semester tidak 100% hingga semua TP terencana Siap dan tuntas.

## 4. Resume dan HOTS setiap Materi
Masing-masing mempunyai editor teks, tombol **Simpan draf** manual ke database, status perubahan belum disimpan/berhasil/gagal, serta **Kirim final** yang mengunci hasil dan membuat antrean penilaian. Tidak ada autosave wajib. Pengiriman ganda harus idempoten; browser meminta konfirmasi jika keluar dengan perubahan belum disimpan. Browser boleh mencegah paste biasa, tanpa mengklaim tidak mungkin curang.

Guru memberi nilai 0–100 dan komentar menurut dokumen 08. Siswa boleh melanjutkan setelah kirim final tanpa menunggu Guru, tetapi rapor/sertifikat final menunggu semua skor wajib dinilai. Koreksi skor mengikuti jalur resmi dan audit.

## 5. Pencapaian dan teks status
Lencana per siswa/paket satu kali: `langkah_pertama`, `lolos_diagnostik`, `tp_tuntas`, `setengah_jalan`, `sekali_lulus`, `semester_tuntas`. Lencana lama `bab_tuntas` jika sudah ada di data historis dipetakan saat penataan dump tanpa menghilangkan catatan pemberian lama. Perayaan ringan boleh sesudah TP/semester tuntas, **tidak pernah saat ujian**, dan tidak memberi nilai/poin/ranking.

| Kondisi | Teks siswa |
|---|---|
| TP belum Siap | Menunggu TP disiapkan Guru |
| TP berikutnya terkunci | Selesaikan TP sebelumnya |
| Diagnostik belum dikerjakan | Mulai Diagnostik |
| Lolos Diagnostik | TP tuntas — lanjut atau pelajari materi sukarela |
| Jalur Materi | Baca/tonton, Simpan draf, Kirim final Resume, Kirim final HOTS |
| Penilaian belum lengkap | Menunggu penilaian Guru: n item |
| Ujian dibatalkan | Menunggu keputusan Guru; tidak ada nilai |
| Semester tuntas | Lihat nilai dan sertifikat jika syarat terbit lengkap |

## 6. Halaman Siswa
S01 Beranda, S02 Semester (**daftar TP**), S03 **Detail TP dan daftar Materi** (menggantikan layar BAB), S04 langkah TP/diagnostik, S05 petunjuk ujian, S06 ujian, S07 hasil, S08 materi, S09 Resume per Materi, S10 HOTS per Materi, S11 lencana, S12 nilai, S13 sertifikat. Pengumuman/notifikasi/FAQ/profil merupakan halaman umum. Semuanya ramah HP kecuali Sumatif yang memerlukan desktop/laptop fullscreen sebagaimana dokumen 07.


### Tahap implementasi S09/S10 (25 Oktober 2026)

Formulir Resume dan HOTS per Materi sudah ditautkan ke POST server dengan
Simpan Draf manual, Kirim Final permanen, revisi untuk mendeteksi konflik
antar-tab, dan audit final. Akses Materi berikutnya harus memenuhi ketiga
bukti dari versi konten TP yang terpin: media_selesai, Resume final,
dan HOTS final untuk seluruh Materi sebelumnya. Siswa yang tuntas melalui
Diagnostik tetap dapat membaca sukarela tanpa mengirim jawaban resmi.
Resume dan HOTS minimal sesuai `tp.resume_min`/`tp.tugas_min` pada snapshot TP siswa (bawaan 50/200 karakter); maksimal teknis 12.000 karakter.
PDF belum dapat menuntaskan media tanpa PDF.js lokal. Skor Guru dan Sumatif
belum diterbitkan pada tahap ini.

### S08D — Pembaca PDF.js lokal dan progres per halaman (25 Oktober 2026)

Pembaca PDF siswa hanya memakai library dan worker PDF.js 3.2.146 lokal
(public/assets/vendor/pdfjs/3.2.146), lisensi Apache-2.0. Tidak memakai CDN
atau iframe browser untuk mencatat pembelajaran resmi. PDF yang diperbolehkan
tetap sesuai validator dan keputusan PDF Materi yang sudah FIX (maks. 15 MB,
40 halaman, tanpa pembatasan tambahan pada revisi normal file).

Semua halaman dirender satu per satu dengan Prev/Next, zoom 50–200%,
fullscreen dan navigasi keyboard. Metadata jumlah halaman diperiksa terhadap
berkas privat dalam snapshot TP; data klien tidak boleh mengurangi jumlahnya.
Heartbeat aktif tiap ~10 detik; interval pertama/berganti halaman, saat
perenderan, tab berpindah/kehilangan fokus, koneksi hilang, dan jarak detak
di luar 5–20 detik tidak dihitung otomatis. Setiap halaman memerlukan
sekurang-kurangnya 15 detik kredensial waktu server; seluruh halaman lengkap
dan min_waktu_detik Guru harus terpenuhi sebelum media_selesai.

Progress ditulis ke progres_materi dan halaman_progres_materi dari SQL 0016
melalui transaksi InnoDB, snapshot dan akses siswa divalidasi server. Resume
dan HOTS tetap harus final. Belajar PDF sukarela setelah lulus Diagnostik
tetap tanpa pencatatan/penilaian resmi. Sinyal browser adalah mitigasi
best-effort (tidak dapat membuktikan mata benar-benar membaca).

### Koreksi S09/S10 — minimum teks mengikuti snapshot TP siswa

Nilai `resume_min` (rentang Master 10–1000) dan `tugas_min` (50–5000) dibaca dari `snapshot_json.tp` pada `versi_konten_tp` yang **terpin** di progres siswa. UI serta validasi POST server memakai nilai yang sama, bukan TP live atau angka bawaan hardcode. Nilai snapshot di luar rentang Master / tidak tersedia ditolak dengan galat konfigurasi; tidak melakukan fallback diam-diam, mengubah TP Siap, atau menulis data. Draf tetap boleh singkat. Siswa jalur Diagnostik tuntas tetap tidak dapat mengirim kegiatan resmi. Tidak ada SQL baru.

### Koreksi status Youtube/audio yang terlihat penuh tetapi belum tuntas (25-10-2026)

Bilah progres media harus mencerminkan **seluruh syarat server**, bukan waktu aktif Guru saja. Untuk audio/YouTube, tampilkan terpisah waktu belajar, waktu pemutaran yang benar-benar diakui server, durasi video/audio, dan posisi terjauh yang mencapai minimal 90% durasi (seek tidak dihitung sebagai pemutaran). Status 380/360 detik **tidak otomatis tuntas** bila pemutaran video baru sebagian. Bila seluruh syarat telah tercapai, halaman memuat ulang sekali untuk merender formulir Resume dari server; HTML tidak membuka gate tanpa pemeriksaan server. Tidak mengubah aturan 90% Master, data siswa atau SQL.
