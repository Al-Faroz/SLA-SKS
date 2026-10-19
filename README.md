# SLA-SKS — Repository Master Document v2.2

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
- Pembelajaran MTs Fase D, model Guru G1; sumatif desktop/laptop fullscreen boleh dari rumah; diagnostik HP hanya jika fullscreen didukung; profil Ketat.
- Nilai tampil angka saja; draf disimpan manual; tersedia notifikasi internal, pengumuman global Admin/Guru, FAQ login-only dan halaman Kebijakan Privasi publik.
- Satu Admin cukup. Admin mengelola biodata langsung; approval akademik Guru tetap diaudit dan tidak dilakukan oleh akun pengusul yang sama.
- PDF sertifikat A4 landscape dengan background global/fallback, TTD PNG wajib, NISN pada PDF **tanpa** NISN pada verifikasi QR publik; sertifikat historis tidak dihapus/dicabut.

## Status repo
Paket ini adalah **spesifikasi dan acuan coding**. Berkas aplikasi PHP lengkap, dump instalasi SQL final, dan file desain sekolah nyata **belum disertakan**; berkas tersebut dibuat pada fase implementasi berdasarkan kontrak dokumen. Folder `database/sql` pada paket ini hanya berisi **panduan format** SQL, bukan dump siap impor. Jangan mengklaim proyek sudah bisa dijalankan hanya dengan ZIP dokumentasi ini.

Dokumen lama v2.0/v2.1 dan addendum tidak diperlukan dalam repo acuan utama; arsip lama dapat disimpan terpisah di luar jalur yang dipakai pembuat kode. Hal yang benar-benar membutuhkan pengaturan situs (domain, kredensial, data sekolah, versi PHP/DB) diisi pada saat instalasi, bukan dijadikan alasan membuka kembali semua keputusan bisnis.
