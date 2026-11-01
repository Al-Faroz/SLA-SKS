# Checkpoint — Detail TP terpadu, satu Materi dan Step Progress

**Tanggal:** 1 November 2026
**Branch:** `feature/v25-detail-tp-step-progress-satu-materi-20261101`
**Basis:** `fix/f1-navigasi-detail-tp-materi-20261101` (PR #84)
**Status:** kode siap QA otomatis dan XAMPP; **belum merge main**, belum FIX tanpa bukti lokal.

## Keputusan pemilik dan implementasi
- Tabel ATP 6 kolom diperbaiki. Aksi diberi lebar tetap proporsional dan tombol draf **Kelola**, Siap **Lihat**. CSS responsif ada dalam `app.css` tanpa menambah framework. Aksi tidak lagi pecah vertikal.
- `guru/tp/{id}` adalah **satu halaman kerja Guru**: identitas, Step Progress nyata, Judul/Rumusan TP, satu editor Materi (YouTube/PDF/Gambar/Audio), tautan bank ujian NonEsensial dan pemeriksaan akhir. Form media menggunakan shared partial, CSRF, pembatas ukuran dan validasi Service yang sudah ada; tidak ada modal.
- URL `guru/tp/{id}/materi/{materiId}/edit` tetap sah sebagai *redirect* setelah otorisasi G1 dan pengecekan status draf, bukan editor terpisah; POST simpan dan galat kembali ke `guru/tp/{id}#materi-tp`.
- Indikator Ketercapaian **tidak wajib** lagi dalam UI, checklist awal, atau final. Kolom fisik DB dan nilai historis tetap disimpan tanpa diubah; tidak ada ALTER TABLE, Migration/Seeder, atau perubahan dump.
- Step Progress Esensial: Identitas/CP → satu Materi → Publikasi. NonEsensial: tambah Diagnostik dan Sumatif. Status berasal dari `KesiapanTpService::periksa`, lalu digrup oleh `StepProgressTpService`; status yang hilang dianggap belum lengkap. Halaman kesiapan masih menyimpan daftar rincian dan konfirmasi Guru/versi konten dalam transaksi.

## Invarian yang tidak boleh diubah
- CP → Semester → BAB → TP, satu TP satu Materi, Gen–Mapel otorisasi G1 dan siswa sesuai Gen.
- NonEsensial: Diagnostik minimal 10 soal; Sumatif minimal 30; Resume/HOTS, bobot 20/20/60 atau 100% Sumatif bagi lulus Diagnostik, remedial satu kali, TP Siap immutable.
- Esensial tetap satu Materi Guru tetapi tanpa ujian/nilai wajib siswa.
- PDF 15MB/40 halaman; data 36 TP dan 36 Materi yang sudah diimpor tidak boleh dihapus/reset.

## Bukti dan langkah QA
- Unit test Step Progress `tests/unit/StepProgressTpServiceTest.php`; regresi indikator tak wajib, update kontrol Detail dan checklist.
- PHP lint/PHPUnit/GitHub Actions: **periksa hasil pada commit branch terbaru**; jangan klaim PASS sebelum status workflow benar-benar sukses.
- QA XAMPP: halaman Semester 4 → Kelola pada TP 23 → Detail TP tampil langkah → editor Materi ID 25 langsung ada → uji isi media, URL lama edit mengarah ke section yang sama → POST simpan kembali tanpa membuat record baru → halaman Kesiapan mengelompokkan langkah dan rincian teknis → uji Esensial dan NonEsensial. Uji tampilan Dark/Light dan tablet/HP.
- Tidak ada SQL baru, tidak memodifikasi `.env` atau root CI4, tidak ada commit dump privat.
