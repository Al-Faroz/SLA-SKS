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
| GET,POST | `GET /guru/peta-atp/impor`, `GET /guru/peta-atp/unduh/{paketId}`, `POST /guru/peta-atp/pratinjau/{paketId}`, `POST /guru/peta-atp/konfirmasi/{paketId}` | G05: workbook enam semester, konteks G1/CP, pratinjau dan konfirmasi INSERT awal semester kosong, satu transaksi |
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
- `/guru/tp/{id}` mengelola tepat satu Materi/Resume/HOTS jika TP NonEsensial dan checklist `TP Siap`. Domain Service minimum: `AtpService`, `ImporAtpService`, `TpService`, `MateriService`, `ImporSoalWordService`, `UjianService`, `NilaiService`, `IdentitasService`. Nama kelas boleh diselaraskan konvensi proyek tanpa mengubah kontrak bisnis.
- `GET /admin/kurikulum-cp/ekspor`: XLSX daftar CP dan status versi sesuai filter, admin-only. `/admin/pengelola`, `/admin/siswa`, `/admin/mapel-guru` memakai paginasi server. `POST /admin/identitas/logo-aplikasi` mengunggah logo aplikasi sekaligus menghasilkan/favicon terkait.
- Semua endpoint mutasi POST dengan CSRF, validasi role aktif di Controller **dan** Service, transaksi serta audit. Tidak menambahkan migrations/seeder, framework JS baru atau mengubah root CI4.

## 10. Kontrak Service/route baru v2.5 (TARGET — BELUM DIIMPLEMENTASIKAN)
Hierarki operasional CP → Semester → BAB → TP; kode TP dibangkitkan dari mapel/kelas/no BAB/no TP dalam BAB. `BabService` menegakkan urutan BAB antarsemester tingkat kelas sama. `TpService` mewajibkan tepat satu Materi pada kedua jenis TP; pembatasan akses/aktivitas/nilai siswa berdasarkan jenis TP dilakukan di Service siswa, bukan Service penyiapan Guru. `AtpImportService` menerima editor aplikasi/Excel secara konsisten, validasi identitas Gen–Mapel, pratinjau diff, idempotensi, kesiapan TP dan snapshot.
- Guru (rute logis, belum tetap): `/guru/bab/{id}`, `/guru/tp/{id}`, `/guru/pra-semester`, `/guru/export-nilai`. Gunakan komponen CSS kustom/JS native, bukan framework besar baru.
- Siswa (rute logis): `/siswa/semester/{n}/bab/{id}`, `/siswa/tp/{id}`, `/siswa/pra-semester`; semua pemeriksaan izin/syarat di server.
- Admin: pengaturan KKM default dan penjadwalan Pra-Semester, hanya terkait paket yang valid; tidak menyentuh nilai akademik Guru.
- `NilaiTpService` menghitung 20/20/60 atau 100% Sumatif dengan remedial satu kali, membedakan **SELESAI** dari nilai ≥KKM. `ProgresSemesterService` hanya TP NonEsensial. `ExportNilaiService` selalu dapat mengekspor dari satu nilai dan tidak menghitung rata-rata nilai akhir semester.
- `PraSemesterService` memeriksa semua TP NonEsensial selesai + waktu Admin, bank minimal 30 mencakup seluruh BAB, attempt Ketat/rehitung skor terbaik. Mesin ujian TP diperluas, tetapi status/rate limits/snapshot/jeda tetap.
- `ProgresMediaService`: Model B hitung klien, checkpoint server 4 menit + checkpoint final. Bila offline simpan akumulasi lokal belum terkirim pada perangkat/browser yang sama, server validasi agar tidak menerima waktu tidak wajar; tombol unduh audio/gambar/PDF setelah gate sah.
Semua endpoint adalah **kontrak rancangan**, bukan klaim API telah terpasang. `.env`, lokasi index root, upload privat, transaksi dan hak G1 tetap.

## 11. Implementasi G05 ATP Excel enam semester — keputusan final 28 Oktober 2026
- `GET /guru/peta-atp`: pemilih Gen–Mapel dan enam kartu semester; satu tautan ke `GET /guru/peta-atp/impor?pilihan=N`, bukan enam formulir impor.
- `GET /guru/peta-atp/impor`: tampilkan paket nonarsip dan Tahun Ajaran per semester dari server; `paket_id` adalah paket acuan CP/Kurikulum, **bukan sumber Tahun Ajaran semua semester**. Paket yang tidak ada wajib dibuat lewat layar semester, tidak dibuat diam-diam.
- `GET /guru/peta-atp/unduh/{paketId}`: mengirim XLSX resmi 6 semester dengan identitas penugasan G1 dan CP aktif. File tidak berisi perubahan BAB/TP otomatis.
- `POST /guru/peta-atp/pratinjau/{paketId}`: otorisasi Guru, CSRF, parser XLSX, validasi semua sheet/CP, perbandingan per semester. Sasaran INSERT hanyalah semester dengan paket sah dan **BAB=0, TP=0**; semester yang sudah berisi satu saja ditolak bila file berbeda, tetapi file seluruhnya identik ditandai Tetap tanpa modifikasi. Satu konflik memblokir konfirmasi semua semester.
- `POST /guru/peta-atp/konfirmasi/{paketId}`: staging privat terikat sesi/akun/paket/checksum maksimal 15 menit, locking G1/paket, fingerprint dan validasi ulang, **INSERT BAB/TP saja** pada paket tiap semester dengan `tahun_ajaran_id` yang diambil dari paket itu. Tidak ada UPDATE/DELETE, tidak membuat Materi/soal/aktivitas siswa. Commit satu transaksi lengkap atau rollback; audit dicatat.
- Proses penyuntingan setelah impor hanya melalui editor BAB/TP atau prosedur koreksi akademik. SQL/migration/seeder tidak diperlukan untuk memindahkan halaman/aturan ini.
