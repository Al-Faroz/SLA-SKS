# Instruksi untuk Coding Assistant — SLA-SKS

1. **WAJIB baca** `docs/README.md`, `docs/02-ATURAN-BISNIS.md`, `docs/03-PERAN-DAN-HAK-AKSES.md`, dan dokumen domain fitur sebelum membuat/mengubah kode.
2. Keputusan terakhir yang tertulis pada Master v2.3 ini merupakan sumber tunggal. Jangan memakai aturan lama dari addendum/v2.1 bila bertentangan dengan dokumen ini.
3. **Tidak boleh membuat CI4 migrations/seeders** sebagai alur instalasi. Buat *dump install* dan *SQL update bernomor* yang aman untuk MySQL lokal serta MariaDB shared hosting; jangan lakukan update destruktif.
4. **`index.php` dan `.htaccess` di root**; wajib uji deny untuk `.env`, `app/`, `writable/`, `vendor/`, `database/sql/` serta informasi privat lain.
5. **UI/CSS mengikuti** `12-UI-UX-DAN-HALAMAN.md` dan `13-DESIGN-SYSTEM-CSS.md`. Jangan membuat file stylesheet terpisah per role, tidak pakai framework CSS baru, semua tombol punya fungsi, galeri komponen harus ikut diperbarui.
6. **G1 v2.3:** satu Guru aktif per kombinasi Gen–Mapel; paket/BAB/TP/soal/materi per Gen, tanpa kewenangan Guru global dari `ref_mapel.guru_id`. Service wajib memeriksa Gen–Mapel objek, termasuk saat impor dan penilaian. Jangan merge PR K2 model lama apa adanya.
7. **Akun v2.3:** menu Admin Pengelola/Siswa terpisah (tetap satu autentikasi), impor/ekspor XLSX, reset sandi per akun, profil staf milik sendiri, profil Guru untuk siswa berizin Gen; semua sesuai 03/10/12/14.
8. Hak akses dicek di **Service**, bukan hanya route/controller/client. Hanya empat role. Admin tunggal cukup untuk administrasi; jangan menambahkan keharusan dua Admin bagi koreksi biodata.
9. Fitur ujian memakai satu **profil Ketat**, fullscreen wajib sebelum attempt, sumatif desktop/laptop dari rumah boleh, diagnostik HP hanya jika mendukung fullscreen. Gunakan waktu server. Shared hosting tidak boleh dipaksa daemon wajib.
10. Penilaian/koreksi harus audit dan idempoten. Jangan menghapus snapshot ujian, skor asal, atau sertifikat historis. **Tidak ada pencabutan sertifikat**.
11. Sertifikat: A4 landscape, NISN hanya dalam PDF privat, QR publik tanpa NISN, TTD PNG wajib; background fallback jika belum diunggah; saat revisi gunakan background lama dan TTD penandatangan aktif terbaru.
12. Draf siswa **disimpan manual**. Pengumuman global oleh Admin/Guru; notifikasi internal saja; FAQ login-only. Jangan membangun autosave/email/WA/forum secara sepihak.
13. Setiap fitur memiliki happy path, empty/loading/error/locked/read-only dan pengujian. Jalankan tes sesuai `docs/16-PENGUJIAN-DAN-PENERIMAAN.md` sebelum menyatakan selesai.
14. Bila ada ambiguitas teknis kecil, ambil solusi yang paling sederhana dan kompatibel lalu dokumentasikan. Jangan menambah alur approval, fitur atau pertanyaan pemilik baru tanpa konflik produk yang sungguh-sungguh.
