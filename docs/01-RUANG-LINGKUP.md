# 01 — Lingkup Produk, Istilah dan Peta Fitur

> **Sumber acuan normatif.** SLA-SKS v2.5 · MTs · Fase D · bahasa Indonesia · zona waktu Asia/Jakarta. Seluruh keputusan aplikasi di bawah berlaku tanpa perlu membaca berkas revisi lama.

## 1. Produk dan pengguna
SLA-SKS adalah LMS tertutup untuk program pembelajaran mandiri berurutan. Siswa mempelajari mata pelajaran menurut paket semester 1–6, BAB, TP dan materi. Fokusnya ketuntasan akademik, bukan kompetisi. Empat peran: **Siswa**, **Guru**, **Admin**, **Pimpinan**. Setiap siswa hanya memiliki peran siswa; akun staf boleh merangkap maksimal dua peran yang diizinkan. Perkiraan kapasitas kecil: satu kelas khusus maksimum tiap angkatan, bukan platform publik ratusan pengguna serentak. Akses utama siswa melalui HP/laptop; sumatif dari **desktop/laptop** saja.

Alur pokok: **Admin menyiapkan akun/mapel/angkatan/identitas → Guru menyusun ATP/TP dan Materi berdasarkan CP resmi → Guru melengkapi dan menerbitkan TP Siap → Siswa menjalani diagnostik → jika gagal: satu materi/resume/HOTS; semua NonEsensial wajib sumatif → Guru memberi penilaian → siswa melihat kemajuan/sertifikat → Pimpinan memantau**.

## 2. Struktur belajar resmi
Struktur utama: **Mapel → 1 CP operasional → Semester → BAB → TP**. Enam semester tetap: 1–2 VII, 3–4 VIII, 5–6 IX; satu Gen–Mapel memiliki paket semester berversi. BAB/TP bernama singkat (maksimum 50 karakter) dan berdeskripsi (maksimum 225). BAB berlanjut nomor lintas dua semester pada tingkat kelas yang sama. Kode TP `KODEMAPEL.KELAS.NO_BAB.NO_TP_DALAM_BAB` (misal `BIND.8.1.1`). TP Esensial maupun NonEsensial sama-sama wajib mempunyai tepat satu Materi yang disiapkan Guru; akses/progres/nilai siswa mengikuti alur jenis TP (Esensial tanpa nilai LMS menurut aturan siswa saat ini).

## 3. Fungsi versi ini
- **Akun/otorisasi:** login tertutup; menu Admin terpisah **Pengelola** (Admin/Guru/Pimpinan) dan **Siswa**, keduanya tetap memakai akun autentikasi yang sama. Impor/ekspor XLSX, reset sandi per akun, ganti sandi pertama kali, peran staf, nonaktif akun, pembatasan login.
- **Administrasi:** Gen, semester mulai, penugasan Guru G1 per Gen–Mapel, tahun ajaran, kurikulum dan CP, branding sekolah, logo, ikon aplikasi, PNG tanda tangan, template sertifikat.
- **Konten LMS:** paket, TP, CP, Excel peta ATP enam semester dan Word bank soal Diagnostik/Sumatif per TP, berkas PDF/audio/gambar, video YouTube, daftar kesiapan TP, koreksi terotorisasi versi TP Siap.
- **Siswa:** pembaca materi, diagnostik, resume, HOTS, sumatif, hasil, nilai, progres, lencana, sertifikat, pengumuman global, notifikasi dalam aplikasi, FAQ setelah login.
- **Informasi dan privasi:** halaman Kebijakan Privasi aplikasi berisi ketentuan resmi yang disahkan sekolah, dapat dibaca tanpa login; kontak sekolah tampil pada menu Hubungi Admin (tanpa sistem tiket).
- **Guru:** penulisan paket/TP khusus Gen–Mapel yang diampu, penilaian 0–100, pemeriksaan kecurangan/perlu bantuan, izin ujian ulang, monitoring, laporan, pengumuman global.
- **Admin/Pimpinan:** Admin mengelola sumber daya dan persetujuan akademik tertentu, Pimpinan hanya membaca; laporan PDF/Excel dan dasbor peran.
- **Sertifikat:** A4 landscape, background JPG/PNG berversi, identitas siswa termasuk NISN pada PDF, QR publik tanpa NISN, penerbitan awal dan pengganti tanpa menghapus sejarah.

### Akun, profil dan UI v2.5
- Admin mengelola Pengelola dan Siswa pada dua halaman terpisah; template, impor, ekspor, pratinjau galat dan reset sandi tersedia pada keduanya. Tidak ada registrasi publik.
- Pengelola dapat mengubah foto, kontak pribadi dan sandinya sendiri. Nama resmi, NIP, peran dan penugasan diubah Admin. Siswa melihat profil profesional Guru pengampu Gen-nya secara terbatas, bukan data pribadi atau autentikasinya.
- Paket, BAB, TP, soal dan materi **tidak dibagi otomatis lintas Gen**. CP resmi tetap dapat dipakai bersama selama cocok.
- UI panel modern terinspirasi TailDash dan UI publik terinspirasi UIdeck Appwind, tetapi implementasi tetap CSS kustom/JS native, bukan framework Tailwind.

## 4. Tidak menjadi fitur
Tidak ada registrasi publik, login Google, marketplace kursus, rombel sebagai pemilik guru, diskusi/forum dua arah, peringkat, streak, poin, pemeriksaan plagiarisme AI, penilaian uraian otomatis, native app, offline PWA, notifikasi email/WhatsApp, upload video sendiri, pembatalan/pencabutan sertifikat, atau penghapusan data siswa/sertifikat dari aplikasi. **Notifikasi internal** termasuk lingkup, walau notifikasi eksternal tidak.

## 5. Glosarium
| Istilah | Makna tunggal |
|---|---|
| `Gen` / angkatan | Kelompok siswa dengan semester mulai default tertentu |
| Semester SLA | Posisi program 1–6; berurutan, bukan semester kalender saat login |
| Paket semester | Konten berversi milik satu Gen untuk satu mapel/semester/tahun ajaran; tidak otomatis dibagi antar-Gen |
| TP Siap | TP dipublikasikan dan terkunci untuk perubahan biasa |
| TP | Tujuan pembelajaran dengan rumusan, indikator, urutan, CP dan penilaian |
| CP | Capaian Pembelajaran resmi bersumber/berversi |
| Jalur diagnostik | Lulus dapat melewati Materi, Resume dan HOTS tetapi tetap wajib Sumatif |
| Jalur belajar | Satu Materi → Resume → HOTS → Sumatif setelah Diagnostik belum lulus |
| Ujian dibatalkan | Tidak memiliki nilai; menunggu keputusan guru |
| Nilai sementara | Nilai belum lengkap karena komponen wajib masih menunggu penilaian guru |
| `berlaku` | Sertifikat resmi aktif untuk siswa dan paket semester tertentu |
| `digantikan` | Sertifikat historis yang telah memiliki pengganti; bukan pencabutan |
| G1 | Tepat satu Guru aktif untuk setiap pasangan Gen–Mapel; satu Guru dapat mengampu banyak Gen/mapel |

## 6. Peta dependensi dokumen
`02` aturan bisnis lintas modul → `03` akses → `04–09` domain aplikasi → `10` data → `11` service/rute → `12–13` UI/CSS → `14` keamanan → `15` deploy → `16` uji. Bila suatu rincian hanya berlaku pada satu domain, dokumen domain adalah acuan utamanya; dokumen lain cukup merujuk kodenya.

## 7. Pembatasan informasi
Aplikasi tidak mengirim data siswa ke penyedia layanan pihak ketiga kecuali memang diperlukan untuk media YouTube yang dibuka siswa. NISN adalah pengenal pribadi: ada pada PDF sertifikat privat sesuai keputusan pemilik, tetapi **tidak pernah muncul di halaman verifikasi QR publik**.

## 8. Keputusan arsitektur v2.5 — 22 Oktober 2026
- Struktur v2.4 tanpa BAB, beberapa Materi per TP, dan nilai final langsung dari Diagnostik **telah diganti**.
- Diagnostik minimal 10 menjadi gate, Sumatif minimal 30 wajib. Nilai TP: jalur gagal Diagnostik = 20% Resume +20% HOTS +60% Sumatif, lulus = 100% Sumatif.
- KKM default Admin/khusus Guru, remedial sekali jika skor Sumatif < KKM dan diambil nilai tertinggi; TP Selesai walau nilai masih < KKM.
- Progres semester berdasarkan TP NonEsensial; Pra-Semester pada waktu serentak Admin setelah semua TP NonEsensial selesai. Tidak ada nilai akhir semester. Guru ekspor nilai parsial termasuk ketika baru satu TP bernilai.

