# 11 — Arsitektur CodeIgniter 4, Service dan Kontrak API

## 1. Standar proyek
- **CI4 Layered MVC**, PHP 8.x versi stabil yang tersedia/kompatibel; Controller tipis, aturan bisnis di Service, Model melakukan kueri, View tidak menyimpan aturan bisnis.
- Bahasa penamaan aplikasi: Indonesia (`SiswaModel`, `KoreksiNilaiService`, `periksaAkses`); PHP PSR-12, tipe strict bila memungkinkan; escape semua output.
- **`index.php` dan `.htaccess` berada di ROOT proyek** sesuai standar pemilik, bukan di `public/`. Direktori `public/assets/` tetap menyimpan berkas static; rewrite `assets/*` diarahkan ke situ dengan aman. Jangan memindahkan `app/`, `writable/`, `vendor/` ke dalam folder aset publik.
- Database disiapkan dan diperbarui melalui **impor dump SQL**, bukan `php spark migrate` atau Seeder.
- Semua hak akses/permission dicek ulang di Service. Gunakan CSRF untuk mutasi dan throttle untuk login, ujian serta verifikasi QR.

## 2. Lapisan arsitektur
```text
HTTP → Filter (auth, peran aktif, CSRF, rate-limit) → Controller → Service/domain
                                                        ↓
                                            Model/DB + Storage privat
                                                        ↓
                                           Audit / Notifikasi / PDF
```
Service sentral minimum: `AuthService`, `PengaturanService`, `GuruMapelService`, `KurikulumService`, `CpService`, `PaketService`, `BabService`, `ImporBabService`, `MateriService`, `ProgresService`, `UjianService`, `PenilaianGuruService`, `KoreksiNilaiService`, `NilaiService`, `SertifikatService`, `NotifikasiService`, `PengumumanService`, `FaqService`, `LaporanService`, `BerkasService`, `LogService`.

Setiap metode mutasi menegakkan invariant dan melempar exception bisnis berkode (mis. `BAB_TERKUNCI`, `NILAI_BERUBAH`, `UJIAN_FULLSCREEN_WAJIB`, `TTD_SERTIFIKAT_KOSONG`, `SERTIFIKAT_REVISI_GAGAL`). Controller menerjemahkannya ke status HTTP dan pesan Indonesia. View tidak mengarang syarat baru.

## 3. Rute utama (kontrak URL logis)
| Metode | Path | Kontrol |
|---|---|---|
| GET,POST | `/masuk` | Public; rate limit, password hash |
| POST | `/keluar`, `/peran/ganti` | Auth + CSRF; sesi diregenerasi |
| GET,POST | `/ganti-sandi`, `/profil` | Auth; wajib ganti pada login awal |
| GET | `/verifikasi/{kode}` | Public; informasi terbatas, rate limit |
| GET | `/kebijakan-privasi` | Public; teks kebijakan sekolah berversi |
| GET | `/berkas/{id}` | Auth + owner; mendukung `Range` untuk audio/PDF |
| GET | `/siswa`, `/siswa/mapel/{id}/semester/{n}`, `/siswa/bab/{id}`, `/siswa/tp/{id}` | Peran siswa, prasyarat berurutan |
| GET,POST | `/siswa/ujian/{tp}/{jenis}/...` | Preflight, ujian server-auth, snapshot dan state |
| GET,POST | `/siswa/materi/{id}`, `/siswa/materi/{id}/resume` | Pembuktian materi, draf manual/kirim |
| GET,POST | `/siswa/tp/{id}/tugas` | Draf manual/kirim |
| GET | `/siswa/nilai`, `/siswa/sertifikat`, `/siswa/sertifikat/{id}/unduh` | Hanya miliknya |
| GET,POST | `/guru/bab/{id}/...`, `/guru/tp/{id}/...` | G1 + hanya draf kecuali koreksi khusus |
| GET,POST | `/guru/bab/{id}/impor`, `/guru/bab/{id}/impor/konfirmasi` | XLSX validasi lalu transaksi |
| GET,POST | `/guru/penilaian/...`, `/guru/ujian/{id}/keputusan` | Penilaian/keputusan Guru mapel |
| GET,POST | `/guru/koreksi-bab/...`, `/guru/koreksi-nilai/...` | Pengajuan akademik berversi |
| GET,POST | `/admin/akun/...`, `/admin/siswa/{id}/biodata` | Admin; audit |
| GET,POST | `/admin/cp/...`, `/admin/koreksi-bab/...`, `/admin/koreksi-nilai/...` | Admin; larangan approve usulan sendiri |
| GET,POST | `/admin/pengaturan/identitas`, `/admin/pengaturan/aset` | Branding dengan upload tervalidasi |
| GET,POST | `/admin/sertifikat/template/...`, `/admin/sertifikat/{id}/revisi-identitas` | Versi background, pratinjau, revisi |
| GET,POST | `/pengumuman`, `/guru/pengumuman/...`, `/admin/pengumuman/...` | Semua login membaca; Admin/Guru menulis global |
| GET | `/bantuan`, `/notifikasi` | Login-only; penerima masing-masing |
| POST | `/notifikasi/{id}/dibaca` | Penerima sendiri |
| GET | `/pimpinan/...`, `/pratinjau/...` | Baca saja; pratinjau tidak menyimpan data |

Rute final boleh mengikuti penamaan CI4 yang konsisten, tetapi **aksi, hak, efek data dan statusnya wajib sama**. Semua API mengembalikan hasil dan pesan terstruktur; parameter ID divalidasi kepemilikannya.

## 4. Penanganan upload
Validasi MIME hasil inspeksi, ekstensi, ukuran, dimensi, decoder, PDF aktif, dan checksum sebelum memindahkan file ke storage. Aset historis tidak ditimpa. Service membuat metadata setelah berkas lolos validasi. Gagal simpan tidak membiarkan baris file aktif tanpa berkas. Proxy berkas privat dengan cek hak akses dan *Content-Disposition* yang sesuai.

## 5. Transaksi penting
- `BAB draf → Siap`: checklist final, kunci versi dan catat audit dalam transaksi.
- `Persetujuan koreksi BAB`: admin putus, baru publish versi konten baru; student TP sudah mulai pin versi lama.
- `Penilaian/koreksi`: hitung agregat, update nilai dan audit idempoten; bila sertifikat perlu revisi, siapkan PDF sebelumnya dan commit perpindahan secara terkoordinasi.
- `Koreksi NISN`: periksa keunikan, ubah biodata dan username dalam transaksi tanpa membuat siswa baru.
- `Impor Excel`: satu BAB, satu transaksi dengan idempoten UUID/checksum.
- `Ujian`: transisi status setelah pemeriksaan jam server, row lock/optimistic concurrency, nol skor untuk batal.

## 6. Server timer di shared hosting
Tidak bergantung pada daemon/Redis wajib. Endpoint `status`, `jawab`, `heartbeat`, `lanjut`, dan `kirim` wajib **rekonsiliasi waktu** secara deterministik pada request, menggunakan ambang `last_seen+20 detik`, sebelum melakukan mutasi. Cron shared hosting sebagai tambahan untuk menemukan kasus tertunda, tetapi **bukan** satu-satunya sumber kebenaran. Jika ada hosting yang benar-benar menyediakan worker, boleh dipakai sebagai optimasi, bukan syarat fitur dasar.

## 7. Pembuatan PDF dan notifikasi
Dompdf membuat PDF dari snapshot dan versi background/TTD yang dipilih. QR menggunakan kode acak dan menunjuk domain HTTPS. Proses persiapan file di luar transaksi DB panjang, lalu revalidasi row version sebelum commit. Jika terjadi galat, simpan pekerjaan bisa diulang tanpa menerbitkan dua sertifikat. Notifikasi internal dibuat setelah commit, idempoten, dan tidak mengirim email atau WhatsApp.

## 8. Integrasi yang sengaja tidak ada
Tidak ada sistem login Google, API LMS eksternal, pembayaran, push notification, cloud storage wajib, dan frontend SPA. PDF.js serta efek membalik halaman hanya dimuat pada pembaca PDF. Diagram halaman pada `12-UI-UX-DAN-HALAMAN.md`; keamanan hosting root pada `14-KEAMANAN-DAN-AUDIT.md`.
