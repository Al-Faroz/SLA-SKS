# SLA-SKS — Repository Master Document v2.3

**Dokumentasi resmi proyek LMS SLA-SKS, MTs Fase D.** Paket ini berisi **16 dokumen acuan inti** yang sudah disusun sebagai spesifikasi fitur final, bukan kronologi revisi. Dokumen Markdown dapat disalin ke root repository aplikasi CI4.

## Mulai dari mana?
1. Baca [indeks 16 dokumen](docs/README.md).
2. Baca [aturan global](docs/02-ATURAN-BISNIS.md) dan [hak akses](docs/03-PERAN-DAN-HAK-AKSES.md).
3. Pilih modul yang dikerjakan lalu baca berkas domain + `10-DATABASE.md` + `11-ARSITEKTUR-DAN-API.md` + `12-UI-UX-DAN-HALAMAN.md` + `13-DESIGN-SYSTEM-CSS.md` + `16-PENGUJIAN-DAN-PENERIMAAN.md`.
4. Baca [AGENTS.md](AGENTS.md) bila menggunakan asisten coding dan [CONTRIBUTING.md](CONTRIBUTING.md) untuk standar PR.

## Keputusan implementasi wajib diingat
- CI4 **`index.php` dan `.htaccess` di root** untuk pemasangan shared hosting; lindungi seluruh folder privat dan tes HTTP nyata.
- Database lokal **MySQL**, hosting **MariaDB**. **Instalasi/update via impor SQL dump/patch**; tidak menjalankan CI4 migration/seeder.
- Frontend **CSS custom satu `app.css` + JavaScript native tanpa bundler**; empat role pada design system yang sama.
- Pembelajaran MTs Fase D, **G1 per Gen–Mapel dan paket/BAB tiap Gen terpisah**; sumatif desktop/laptop fullscreen boleh dari rumah; diagnostik HP hanya jika fullscreen didukung; profil Ketat.
- Nilai tampil angka saja; draf disimpan manual; tersedia notifikasi internal, pengumuman global Admin/Guru, FAQ login-only dan halaman Kebijakan Privasi publik.
- Satu Admin cukup. Admin mengelola biodata langsung; approval akademik Guru tetap diaudit dan tidak dilakukan oleh akun pengusul yang sama.
- PDF sertifikat A4 landscape dengan background global/fallback, TTD PNG wajib, NISN pada PDF **tanpa** NISN pada verifikasi QR publik; sertifikat historis tidak dihapus/dicabut.

## Keputusan v2.3 yang harus diterapkan sebelum modul pembelajaran
- **Guru per Gen–Mapel:** satu Guru aktif setiap kombinasi Gen + mapel; satu Guru dapat menangani banyak Gen/mapel. Paket/BAB/TP/materi/soal berbeda antargen; CP resmi boleh dipakai bersama.
- **Dua menu Admin:** Pengelola dan Siswa, masih satu autentikasi. Keduanya mempunyai impor/ekspor XLSX, reset sandi per akun dan pengelolaan sesuai hak.
- **Profil:** staf dapat mengubah foto, kontak dan sandi sendiri; siswa membaca profil profesional Guru yang benar-benar mengampu Gen-nya, tanpa informasi privat Guru.
- **UI:** dashboard terinspirasi TailDash dan login/area publik terinspirasi UIdeck Appwind. Gap, ukuran, font, layout dibuat lebih ringkas dalam CSS kustom `app.css`; prototipe dashboard dan login disetujui dahulu.
- **Transisi aman:** dokumentasi dahulu; kode, SQL bernomor dan tes menyusul pada PR berbeda. K2 lama tidak boleh digabung apa adanya.

## Status repo
Repository telah berisi fondasi aplikasi CI4 K0/K1 pada `main`, termasuk autentikasi dan SQL awal K1. **PR K2 #3 masih terbuka**, telah diuji pemilik di XAMPP tetapi masih memuat skema G1 global v2.2 yang harus diselaraskan sebelum digabung. **Master v2.3 adalah kontrak target**, bukan klaim seluruh fitur sudah terimplementasi. Dump instalasi penuh v2.3 dan patch transisi per Gen belum dibuat; jangan mengimpor ulang SQL K1/K2 yang sudah terpakai. Data pengguna nyata dan konfigurasi privat tidak berada dalam GitHub.

Dokumen lama v2.0/v2.1 dan addendum tidak diperlukan dalam repo acuan utama; arsip lama dapat disimpan terpisah di luar jalur yang dipakai pembuat kode. Hal yang benar-benar membutuhkan pengaturan situs (domain, kredensial, data sekolah, versi PHP/DB) diisi pada saat instalasi, bukan dijadikan alasan membuka kembali semua keputusan bisnis.


## Tahap K2D: ekspor dan template XLSX
Tersedia pada menu Admin Pengelola/Siswa: **Unduh template** dan **Ekspor XLSX** sesuai filter (pencarian/status/Gen). Menggunakan `phpoffice/phpspreadsheet` yang sudah ditetapkan di `composer.json`/`composer.lock`, bukan dependensi baru. Data XLSX diekspor dalam tipe sel teks untuk menjaga NISN dengan nol di depan, sekaligus menetralkan formula injection. Ekspor tidak membawa hash/sandi/sesi. Setiap unduhan diaudit tanpa data sensitif.

**Penting:** Ini tahap pertama K2D (ekspor + template). Fitur **unggah/pratinjau/konfirmasi impor XLSX belum tersedia** dan akan dikerjakan dalam PR berikutnya agar tidak ada tombol impor yang tampak berfungsi tetapi belum aman. Tidak ada SQL baru untuk tahap ini. Jangan mengimpor ulang SQL 0001–0005.
