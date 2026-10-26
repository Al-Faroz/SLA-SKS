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
Service sentral minimum: `AuthService`, `PengaturanService`, `GuruMapelService`, `PenggunaImportService`, `PenggunaExportService`, `ProfilService`, `KurikulumService`, `CpService`, `PaketService`, `TpService`, `ImporAtpService`, `MateriService`, `ProgresService`, `UjianService`, `PenilaianGuruService`, `KoreksiNilaiService`, `NilaiService`, `SertifikatService`, `NotifikasiService`, `PengumumanService`, `FaqService`, `LaporanService`, `BerkasService`, `LogService`.

Setiap metode mutasi menegakkan invariant dan melempar exception bisnis berkode (mis. `TP_TERKUNCI`, `NILAI_BERUBAH`, `UJIAN_FULLSCREEN_WAJIB`, `TTD_SERTIFIKAT_KOSONG`, `SERTIFIKAT_REVISI_GAGAL`). Controller menerjemahkannya ke status HTTP dan pesan Indonesia. View tidak mengarang syarat baru.

## 3. Rute utama (kontrak URL logis)
| Metode | Path | Kontrol |
|---|---|---|
| GET,POST | `/masuk` | Public; rate limit, password hash |
| POST | `/keluar`, `/peran/ganti` | Auth + CSRF; sesi diregenerasi |
| GET,POST | `/ganti-sandi`, `/profil` | Auth; staf hanya edit foto/kontak pribadi/sandi sendiri; data resmi hanya Admin |
| GET | `/verifikasi/{kode}` | Public; informasi terbatas, rate limit |
| GET | `/kebijakan-privasi` | Public; teks kebijakan sekolah berversi |
| GET | `/berkas/{id}` | Auth + owner; mendukung `Range` untuk audio/PDF |
| GET | `/siswa`, `/siswa/mapel/{id}/semester/{n}`, `/siswa/tp/{id}` | Peran siswa, prasyarat berurutan |
| GET,POST | `/siswa/ujian/{tp}/{jenis}/...` | Preflight, ujian server-auth, snapshot dan state |
| GET,POST | `/siswa/materi/{id}`, `/siswa/materi/{id}/resume` | Pembuktian materi, draf manual/kirim |
| GET,POST | `/siswa/materi/{id}/hots` | Draf manual/kirim |
| GET | `/siswa/nilai`, `/siswa/sertifikat`, `/siswa/sertifikat/{id}/unduh` | Hanya miliknya |
| GET,POST | `/guru/tp/{id}/...` | G1 aktif **Gen–Mapel objek** + hanya draf kecuali koreksi khusus |
| GET,POST | `/guru/peta-atp/unduh`, `/guru/peta-atp/impor`, `/guru/peta-atp/impor/{id}/konfirmasi` | Workbook enam semester terikat Gen–Mapel/Kurikulum, pratinjau, transaksi |
| GET,POST | `/guru/penilaian/...`, `/guru/ujian/{id}/keputusan` | Guru hanya untuk Gen–Mapel siswa/ujian terkait |
| GET,POST | `/guru/koreksi-tp/...`, `/guru/koreksi-nilai/...` | Pengajuan akademik berversi |
| GET,POST | `/admin/pengelola/...`, `/admin/siswa/...` | Dua ruang Admin dengan akun bersama; tambah/ubah/nonaktif/reset per akun, audit |
| GET,POST | `/admin/pengelola/impor`, `/admin/siswa/impor`, `.../konfirmasi` | Template XLSX terpisah, pratinjau galat, transaksi dan idempotensi |
| GET | `/admin/pengelola/ekspor`, `/admin/siswa/ekspor` | XLSX sesuai filter; tanpa sandi/hash; sanitasi sel |
| POST | `/admin/pengelola/{id}/reset-sandi`, `/admin/siswa/{id}/reset-sandi` | CSRF, audit, konfirmasi, sandi sementara acak sekali tampil, cabut sesi lama |
| GET | `/guru/{id}/profil` | Siswa hanya profil profesional Guru yang ditugaskan pada Gen-nya; tanpa data kontak pribadi |
| GET,POST | `/admin/mapel-guru/.../gen/{angkatan_id}` | Penugasan/riwayat satu Guru aktif per Gen–Mapel, validasi role Guru |

| GET,POST | `/admin/cp/...`, `/admin/koreksi-tp/...`, `/admin/koreksi-nilai/...` | Admin; larangan approve usulan sendiri |
| GET,POST | `/admin/pengaturan/identitas`, `/admin/pengaturan/aset` | Branding dengan upload tervalidasi |
| GET,POST | `/admin/sertifikat/template/...`, `/admin/sertifikat/{id}/revisi-identitas` | Versi background, pratinjau, revisi |
| GET,POST | `/pengumuman`, `/guru/pengumuman/...`, `/admin/pengumuman/...` | Semua login membaca; Admin/Guru menulis global |
| GET | `/bantuan`, `/notifikasi` | Login-only; penerima masing-masing |
| POST | `/notifikasi/{id}/dibaca` | Penerima sendiri |
| GET | `/pimpinan/...`, `/pratinjau/...` | Baca saja; pratinjau tidak menyimpan data |

Rute final boleh mengikuti penamaan CI4 yang konsisten, tetapi **aksi, hak, efek data dan statusnya wajib sama**. Semua API mengembalikan hasil dan pesan terstruktur; parameter ID divalidasi kepemilikannya.

### Kontrak izin dan data Gen v2.4
Semua service Guru, paket, TP, penilaian, ujian dan laporan menyelesaikan `angkatan_id` melalui objek terikat server (paket/siswa) dan menegaskan `penugasan_guru_gen(angkatan_id,mapel_id,guru_id)` untuk pengguna Guru berperan aktif. **Jangan** menjadikan `created_by`, `ref_mapel.guru_id`, parameter Gen dari form saja, atau kombinasi role staf sebagai bukti kepemilikan. `paket_semester` harus dimiliki Gen yang sama dengan penugasan aktif; perubahan Guru tidak mengubah snapshot/riwayat siswa.
Impor pengguna harus memisahkan mode pratinjau dan konfirmasi, memakai checksum/kunci idempoten, pemeriksaan server saat commit, dan transaksi pembaruan NISN+username secara atomik. Reset sandi per akun menaikkan versi sesi dan memaksa ganti sandi; hasil sementara tidak boleh masuk log, URL atau ekspor.

## 4. Penanganan upload
Validasi MIME hasil inspeksi, ekstensi, ukuran, dimensi, decoder, PDF aktif, dan checksum sebelum memindahkan file ke storage. Aset historis tidak ditimpa. Service membuat metadata setelah berkas lolos validasi. Gagal simpan tidak membiarkan baris file aktif tanpa berkas. Proxy berkas privat dengan cek hak akses dan *Content-Disposition* yang sesuai.

## 5. Transaksi penting
- `TP draf → Siap`: checklist final, kunci versi dan catat audit dalam transaksi.
- `Persetujuan koreksi TP`: admin putus, baru publish versi konten baru; student TP sudah mulai pin versi lama.
- `Penilaian/koreksi`: hitung agregat, update nilai dan audit idempoten; bila sertifikat perlu revisi, siapkan PDF sebelumnya dan commit perpindahan secara terkoordinasi.
- `Koreksi NISN`: periksa keunikan, ubah biodata dan username dalam transaksi tanpa membuat siswa baru.
- `Impor Excel ATP`: satu peta Gen–Mapel, pratinjau dan konfirmasi transaksional dengan idempoten UUID/checksum serta larangan mengubah TP Siap.
- `Ujian`: transisi status setelah pemeriksaan jam server, row lock/optimistic concurrency, nol skor untuk batal.

## 6. Server timer di shared hosting
Tidak bergantung pada daemon/Redis wajib. Endpoint `status`, `jawab`, `heartbeat`, `lanjut`, dan `kirim` wajib **rekonsiliasi waktu** secara deterministik pada request, menggunakan ambang `last_seen+20 detik`, sebelum melakukan mutasi. Cron shared hosting sebagai tambahan untuk menemukan kasus tertunda, tetapi **bukan** satu-satunya sumber kebenaran. Jika ada hosting yang benar-benar menyediakan worker, boleh dipakai sebagai optimasi, bukan syarat fitur dasar.

## 7. Pembuatan PDF dan notifikasi
Dompdf membuat PDF dari snapshot dan versi background/TTD yang dipilih. QR menggunakan kode acak dan menunjuk domain HTTPS. Proses persiapan file di luar transaksi DB panjang, lalu revalidasi row version sebelum commit. Jika terjadi galat, simpan pekerjaan bisa diulang tanpa menerbitkan dua sertifikat. Notifikasi internal dibuat setelah commit, idempoten, dan tidak mengirim email atau WhatsApp.

## 8. Integrasi yang sengaja tidak ada
Tidak ada sistem login Google, API LMS eksternal, pembayaran, push notification, cloud storage wajib, dan frontend SPA. PDF.js serta efek membalik halaman hanya dimuat pada pembaca PDF. Diagram halaman pada `12-UI-UX-DAN-HALAMAN.md`; keamanan hosting root pada `14-KEAMANAN-DAN-AUDIT.md`.

## 9. Kontrak route dan Service v2.4 (target; belum semua terimplementasi)
- `GET /guru/peta-atp`: pemilih Gen–Mapel/Kurikulum yang hanya menampilkan penugasan Guru aktif; `GET /guru/peta-atp/unduh` membangkitkan workbook enam semester dari master `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx`, menanam identitas Guru, Gen, mapel, kurikulum, CP aktif, token versi terikat; bukan data global.
- `POST /guru/peta-atp/impor` pratinjau dan `POST /guru/peta-atp/impor/{id}/konfirmasi` commit peta TP+Materi idempoten; validasi sheet/rentang, CP aktif, urutan, status Siap dan verifikasi Gen–Mapel server. Tidak menghapus karena baris hilang.
- `GET /guru/tp/{id}/template-soal/{jenis}`, `POST /guru/tp/{id}/impor-soal/{jenis}`, `POST /guru/tp/{id}/impor-soal/{jenis}/{id}/konfirmasi`; `jenis` hanya `diagnostik`/`sumatif`. Generator menyiapkan satu format `SLA-SKS-WORD-V1`, renderer/pratinjau mempertahankan OMML, tabel bersarang, gambar dan Google Drive.
- `/guru/tp/{id}` mengelola Materi/media/Resume/HOTS per Materi dan checklist `TP Siap`. Domain Service minimum: `AtpService`, `ImporAtpService`, `TpService`, `MateriService`, `ImporSoalWordService`, `UjianService`, `NilaiService`, `IdentitasService`. Nama kelas boleh diselaraskan konvensi proyek tanpa mengubah kontrak bisnis.
- `GET /admin/kurikulum-cp/ekspor`: XLSX daftar CP dan status versi sesuai filter, admin-only. `/admin/pengelola`, `/admin/siswa`, `/admin/mapel-guru` memakai paginasi server. `POST /admin/identitas/logo-aplikasi` mengunggah logo aplikasi sekaligus menghasilkan/favicon terkait.
- Semua endpoint mutasi POST dengan CSRF, validasi role aktif di Controller **dan** Service, transaksi serta audit. Tidak menambahkan migrations/seeder, framework JS baru atau mengubah root CI4.


### API checkpoint pembelajaran media (26 Oktober 2026)

- `POST /siswa/tp/{tp}/materi/{materi}/mulai-media`: mencatat baseline waktu server ketika siswa pertama kali aktif membaca/memutar media.
- `POST /siswa/tp/{tp}/materi/{materi}/checkpoint`: menerima JSON angka **kumulatif** dalam form `laporan`, setiap 180 detik, saat syarat media tercapai atau ketika koneksi pulih. Transaksi harus memvalidasi sesi siswa, akses Gen, versi TP terpin, jenis media, batas waktu server, rincian halaman dan idempotensi. Request tidak boleh mengubah status siswa lain.
- `GET /siswa/tp/{tp}/materi/{materi}/unduh`: hanya audio/gambar/PDF setelah `media_selesai` untuk progres siswa aktif; berkas privat dikirim sebagai attachment. Endpoint `/media` untuk streaming/tampilan tidak otomatis memberikan hak untuk `/unduh`.

Checkpoint media **berbeda dari ujian**: aturan pause/fullscreen/heartbeat ujian tetap server-side sesuai Master 07. Data lokal merupakan kandidat, bukan otoritas pengesahan kelulusan.
