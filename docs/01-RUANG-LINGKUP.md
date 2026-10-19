# 01 — Lingkup Produk, Istilah dan Peta Fitur

> **Sumber acuan normatif.** SLA-SKS v2.2 · MTs · Fase D · bahasa Indonesia · zona waktu Asia/Jakarta. Seluruh keputusan aplikasi di bawah berlaku tanpa perlu membaca berkas revisi lama.

## 1. Produk dan pengguna
SLA-SKS adalah LMS tertutup untuk program pembelajaran mandiri berurutan. Siswa mempelajari mata pelajaran menurut paket semester 1–6, BAB, TP dan materi. Fokusnya ketuntasan akademik, bukan kompetisi. Empat peran: **Siswa**, **Guru**, **Admin**, **Pimpinan**. Setiap siswa hanya memiliki peran siswa; akun staf boleh merangkap maksimal dua peran yang diizinkan. Perkiraan kapasitas kecil: satu kelas khusus maksimum tiap angkatan, bukan platform publik ratusan pengguna serentak. Akses utama siswa melalui HP/laptop; sumatif dari **desktop/laptop** saja.

Alur pokok: **Admin menyiapkan akun/mapel/angkatan/identitas → Guru merancang paket BAB, TP dan CP → Guru melengkapi dan menerbitkan BAB Siap → Siswa menjalani diagnostik → jika belum tuntas: materi/resume/tugas/sumatif → Guru memberi penilaian → siswa melihat kemajuan/sertifikat → Pimpinan memantau**.

## 2. Struktur belajar resmi
`Madrasah → Mapel → Paket semester (1–6) → BAB berurutan → TP berurutan → Materi berurutan`. Setiap TP memetakan satu atau lebih CP resmi yang dipublikasikan, tepat satu CP utama. MTs Fase D mencakup VII–IX; label semester SLA 1–6 **bukan periode tahun kalender**. Penugasan paket berlaku menurut kombinasi angkatan, tahun ajaran, mapel dan semester; siswa yang sudah memiliki progres dipertahankan pada paket dan versi konten asal.

| Semester SLA | Kelas | Semester akademik | Fase |
|---:|---|---|---|
| 1 | VII | Ganjil | D |
| 2 | VII | Genap | D |
| 3 | VIII | Ganjil | D |
| 4 | VIII | Genap | D |
| 5 | IX | Ganjil | D |
| 6 | IX | Genap | D |

## 3. Fungsi versi ini
- **Akun/otorisasi:** login tertutup, reset sandi oleh Admin, ganti sandi pertama kali, pemilih peran staf, nonaktif akun, impor pengguna Excel, pembatasan login.
- **Administrasi:** Gen, semester mulai, mapel–guru G1, tahun ajaran, kurikulum dan CP, branding sekolah, logo, ikon aplikasi, PNG tanda tangan, template sertifikat.
- **Konten LMS:** paket, BAB/TP, CP, Excel 8 sheet per BAB, berkas PDF/audio/gambar, video YouTube, daftar kesiapan BAB, koreksi terotorisasi versi BAB Siap.
- **Siswa:** pembaca materi, diagnostik, resume, HOTS, sumatif, hasil, nilai, progres, lencana, sertifikat, pengumuman global, notifikasi dalam aplikasi, FAQ setelah login.
- **Informasi dan privasi:** halaman Kebijakan Privasi aplikasi berisi ketentuan resmi yang disahkan sekolah, dapat dibaca tanpa login; kontak sekolah tampil pada menu Hubungi Admin (tanpa sistem tiket).
- **Guru:** penulisan konten mapel, penilaian 0–100, pemeriksaan kecurangan/perlu bantuan, izin ujian ulang, monitoring, laporan, pengumuman global.
- **Admin/Pimpinan:** Admin mengelola sumber daya dan persetujuan akademik tertentu, Pimpinan hanya membaca; laporan PDF/Excel dan dasbor peran.
- **Sertifikat:** A4 landscape, background JPG/PNG berversi, identitas siswa termasuk NISN pada PDF, QR publik tanpa NISN, penerbitan awal dan pengganti tanpa menghapus sejarah.

## 4. Tidak menjadi fitur
Tidak ada registrasi publik, login Google, marketplace kursus, rombel sebagai pemilik guru, diskusi/forum dua arah, peringkat, streak, poin, pemeriksaan plagiarisme AI, penilaian uraian otomatis, native app, offline PWA, notifikasi email/WhatsApp, upload video sendiri, pembatalan/pencabutan sertifikat, atau penghapusan data siswa/sertifikat dari aplikasi. **Notifikasi internal** termasuk lingkup, walau notifikasi eksternal tidak.

## 5. Glosarium
| Istilah | Makna tunggal |
|---|---|
| `Gen` / angkatan | Kelompok siswa dengan semester mulai default tertentu |
| Semester SLA | Posisi program 1–6; berurutan, bukan semester kalender saat login |
| Paket semester | Versi wadah konten yang ditugaskan ke kelompok siswa |
| BAB Siap | BAB dipublikasikan dan terkunci untuk perubahan biasa |
| TP | Tujuan pembelajaran dengan rumusan, indikator, urutan, CP dan penilaian |
| CP | Capaian Pembelajaran resmi bersumber/berversi |
| Jalur diagnostik | TP tuntas berdasarkan skor diagnostik ≥ KKM |
| Jalur belajar | Materi → resume per materi → HOTS → sumatif setelah diagnostik belum tuntas |
| Ujian dibatalkan | Tidak memiliki nilai; menunggu keputusan guru |
| Nilai sementara | Nilai belum lengkap karena komponen wajib masih menunggu penilaian guru |
| `berlaku` | Sertifikat resmi aktif untuk siswa dan paket semester tertentu |
| `digantikan` | Sertifikat historis yang telah memiliki pengganti; bukan pencabutan |
| G1 | Tepat satu guru aktif per mapel, ditetapkan Admin |

## 6. Peta dependensi dokumen
`02` aturan bisnis lintas modul → `03` akses → `04–09` domain aplikasi → `10` data → `11` service/rute → `12–13` UI/CSS → `14` keamanan → `15` deploy → `16` uji. Bila suatu rincian hanya berlaku pada satu domain, dokumen domain adalah acuan utamanya; dokumen lain cukup merujuk kodenya.

## 7. Pembatasan informasi
Aplikasi tidak mengirim data siswa ke penyedia layanan pihak ketiga kecuali memang diperlukan untuk media YouTube yang dibuka siswa. NISN adalah pengenal pribadi: ada pada PDF sertifikat privat sesuai keputusan pemilik, tetapi **tidak pernah muncul di halaman verifikasi QR publik**.
