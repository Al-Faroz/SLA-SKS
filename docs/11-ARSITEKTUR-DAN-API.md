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
| GET,POST | `/guru/tp/{id}/...` | G1 aktif Gen–Mapel; mutasi konten boleh untuk Draf **atau Siap yang belum pernah digunakan siswa**; cek ulang usage pada commit |
| GET,POST | `GET /guru/peta-atp/impor`, `GET /guru/peta-atp/unduh/{paketId}`, `POST /guru/peta-atp/pratinjau/{paketId}`, `POST /guru/peta-atp/konfirmasi/{paketId}` | G05: workbook enam semester, konteks G1/CP, pratinjau dan konfirmasi INSERT awal semester kosong, satu transaksi |
| GET,POST | `/guru/penilaian/...`, `/guru/ujian/{id}/keputusan` | Guru hanya untuk Gen–Mapel siswa/ujian terkait |
| GET,POST | `/guru/koreksi-nilai/...` | Hanya koreksi **nilai siswa** sesuai aturan nilai; **bukan** jalur edit konten TP Siap yang belum dipakai |
| GET,POST | `/admin/pengelola/...`, `/admin/siswa/...` | Dua ruang Admin dengan akun bersama; tambah/ubah/nonaktif/reset per akun, audit |
| GET,POST | `/admin/pengelola/impor`, `/admin/siswa/impor`, `.../konfirmasi` | Template XLSX terpisah, pratinjau galat, transaksi dan idempotensi |
| GET | `/admin/pengelola/ekspor`, `/admin/siswa/ekspor` | XLSX sesuai filter; tanpa sandi/hash; sanitasi sel |
| POST | `/admin/pengelola/{id}/reset-sandi`, `/admin/siswa/{id}/reset-sandi` | CSRF, audit, konfirmasi, sandi sementara acak sekali tampil, cabut sesi lama |
| GET | `/guru/{id}/profil` | Siswa hanya profil profesional Guru yang ditugaskan pada Gen-nya; tanpa data kontak pribadi |
| GET,POST | `/admin/mapel-guru/.../gen/{angkatan_id}` | Penugasan/riwayat satu Guru aktif per Gen–Mapel, validasi role Guru |

| GET,POST | `/admin/kurikulum-cp/...`, `/admin/koreksi-nilai/...` | Admin sunting CP langsung kapan saja; persetujuan hanya untuk koreksi nilai yang memang memerlukannya |
| GET,POST | `/admin/pengaturan/identitas`, `/admin/pengaturan/aset` | Branding dengan upload tervalidasi |
| GET,POST | `/admin/sertifikat/template/...`, `/admin/sertifikat/{id}/revisi-identitas` | Versi background, pratinjau, revisi |
| GET,POST | `/pengumuman`, `/guru/pengumuman/...`, `/admin/pengumuman/...` | Sesuai penerima; Admin global, Guru siswa Gen pengampu |
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
- **Edit TP Siap belum digunakan siswa**: G1/CSRF, lock/periksa ulang progres + aktivitas + percobaan dalam transaksi, validasi kesiapan, simpan konten/media/soal, perbarui snapshot/pointer aktif secara konsisten sebelum memberi akses siswa. **Jika telah digunakan minimal satu siswa: tolak**. Tidak memerlukan persetujuan Admin, kategori, atau jalur draf koreksi terpisah.
- `Penilaian/koreksi`: hitung agregat, update nilai dan audit idempoten; bila sertifikat perlu revisi, siapkan PDF sebelumnya dan commit perpindahan secara terkoordinasi.
- `Koreksi NISN`: periksa keunikan, ubah biodata dan username dalam transaksi tanpa membuat siswa baru.
- `Impor Excel ATP`: satu peta Gen–Mapel, pratinjau dan konfirmasi transaksional dengan idempoten UUID/checksum; hanya INSERT ke semester yang benar-benar kosong, **tidak memperbarui semester terisi**, terlepas dari status TP.
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
- Proses penyuntingan setelah impor hanya melalui editor BAB/TP dengan izin G1 dan gerbang penggunaan siswa untuk TP; tidak memakai kategori koreksi akademik. SQL/migration/seeder tidak diperlukan untuk memindahkan halaman/aturan ini.

## 12. Kontrak operasional penyuntingan CP/BAB/TP (final 2 November 2026)
1. **CP**: Admin membuka Tambah/Sunting CP dengan **lima field saja** (kurikulum_id, mapel_id, kode, elemen, rumusan). Data Fase D dari sistem. Empat metadata lama tidak diperlukan pada request, validasi, atau UI. CP aktif/terpakai tetap boleh disunting kapan saja. FK `cp_mapel` dan riwayat teknis tetap konsisten. **Tombol Simpan** cukup; bila versi internal perlu diperbarui, jalankan otomatis tanpa mewajibkan pengguna membuat draf, review, dan terbit manual.
2. **BAB**: Guru G1 menggunakan editor isian BAB yang sudah ada, tanpa status TP Siap sebagai alasan penolakan. Validasi nomor/nama/deskripsi dan keunikan yang sudah disetujui tidak dihilangkan; tidak membuat operasi baru seperti pemindahan TP antar-BAB.
3. **TP**: fungsi tunggal penentu boleh mutasi: G1 sah **dan** (TP Draf **atau** TP Siap belum pernah digunakan siswa). Riwayat progres/aktivitas/percobaan sah adalah gerbang. Daftar TP yang sekadar dilihat siswa tidak dihitung penggunaan. Buat pemeriksaan konsisten pada semua operasi TP, Materi, media, Bank Soal, Word, pengaturan KKM, dan API.
4. **Race condition**: pada POST, lock/validasi ulang dalam transaksi terhadap kemungkinan siswa baru mulai, dan jangan terbitkan snapshot lama sesudah perubahan sudah commit. Seluruh simpan harus idempoten jika isian sama; perubahan gagal ditolak tanpa partial update.
5. **TP yang sudah digunakan siswa**: tidak ada operasi perubahan konten, termasuk melalui pengajuan koreksi kategori A/B/C atau admin approval; proteksi semua rute, termasuk rute historis yang sudah ditambahkan pada branch sebelumnya. Koreksi **nilai siswa** adalah domain terpisah.
6. **Transisi implementasi**: aplikasi/SQL `permohonan_koreksi_tp` kategori A/B/C yang telanjur dibangun harus dibersihkan/dinonaktifkan di tahap kode. Jangan menghapus tabel historis jika telah diimpor; ikuti audit dan izin pemilik.


### Implementasi A8 — peran dan batas jadwal (3 November 2026)
- `AturanPraSemesterService` adalah validator murni kelayakan seluruh TP NonEsensial, bank soal minimal 30/cakupan BAB, dan jendela **boleh mulai** (WIB); jangan mengubahnya menjadi timer global peserta.
- `PraSemesterGuruService` menangani bank Guru G1 dan KKM paket. `PraSemesterJadwalService` menangani jadwal dan durasi paket hanya untuk Admin. Adapter mesin berikutnya mengunci percobaan, membuat waktu aktif masing-masing saat mulai, dan tidak memotong durasi ketika jadwal masuk berakhir.
- Tahap susulan/remedial hanya boleh diaktifkan setelah skema jadwal per sesi dan daftar peserta ada, dengan audit, otorisasi dan izin mulai sesuai jadwal Admin; tidak boleh meminjam izin akses ulang Diagnostik/Sumatif.


### Adapter A8-4 (3 November 2026)
- Service `PenilaianPraSemesterService::ringkas($percobaan)` menerima **urutan kronologis attempt** Pra-Semester satu siswa dan satu bank, menghitung nilai awal, remedial, nilai terbaik, status final/sementara serta fase kesempatan berikutnya. Tidak menyentuh SQL maupun mengubah data.
- `AturanJadwalPraSemesterService::periksaMulai($jadwal,$percobaan,$waktuServer,...)` memeriksa jenis sesi `utama|remedial|susulan`, tahap `normal|remedial`, jendela WIB, peserta susulan yang ditetapkan Admin, kesiapan TP/bank/perangkat/fullscreen, dan melarang percobaan baru saat masih ada percobaan aktif. Jika sudah aktif, kelanjutan memakai token yang sama bahkan jika jendela mulai ditutup.
- Adapter berikutnya wajib memverifikasi kepemilikan paket **Gen–Mapel–Semester**, G1/Admin, identitas peserta dan sesi, kunci transaksi sebelum membuat attempt, snapshot soal/KKM, serta audit. Semua data diperoleh dari server, bukan dipilih bebas lewat POST siswa. **Belum ada skema jadwal remedial/susulan** terverifikasi dari XAMPP pascapatch, jadi jangan memanggil fungsi ini untuk membuka akses tanpa jadwal tersimpan.

### A8-5 — Endpoint dan Service sesi
- `PraSemesterSesiService`: daftar sesi per paket, jadwal Admin, calon susulan (absen/batal per tahap), penetapan peserta aditif dalam transaksi, pemilihan jadwal siswa dari histori dan waktu server. Pengubahan jadwal lama tidak tersedia sesudah dipakai.
- `AdminPraSemester::simpanSesi` POST `/admin/pra-semester/{paketId}/sesi` dan `AdminPraSemester::tambahPeserta` POST `/admin/pra-semester/{paketId}/sesi/{sesiId}/peserta`, wajib filter `auth:admin` + validasi Service dan CSRF.
- `MesinPraSemesterSiswaService` mengikat attempt ke sesi resmi, menjalankan gerbang TP/bank/KKM dan menghitung nomor percobaan, tahap, durasi individual serta snapshot soal tanpa mengubah record nilai lama.
- Filter list berbasis GET/Query Builder: Guru `gen` dalam penugasan G1; Admin `gen,mapel,semester,status` (belum/draf/siap/ditutup). Tidak melakukan filter palsu di browser. Reset membuka daftar penuh, bukan mengubah record.

### A8-7 — Word Pra-Semester
- `TemplateWordSoalService` dan `PratinjauWordSoalService` mendukung `pra_semester` menggunakan BAB per nomor; `RenderWordSoalService` tetap digunakan tanpa fork renderer. `PraSemesterWordService` mengatur HMAC template, upload privat, sidik jari, review, impor append-only transaksional, riwayat dan penjagaan bank terpakai.
- Route Guru: `GET /guru/pra-semester/{paketId}/word?jumlah=30`, `POST .../word/pratinjau`, `GET .../word/review/{id}`, `POST .../word/review/{id}/konfirmasi`. Semua wajib auth Guru G1, CSRF pada POST, sumber file privat.
- Endpoint gambar siswa `GET /siswa/pra-semester/{paketId}/{token}/media/{wordId}/{rid}` wajib auth siswa dan pengecekan snapshot attempt; tidak mengembalikan bank/kunci jawaban.

### A11-2 — Endpoint implementasi sertifikat (3 November 2026)

| Metode | Endpoint | Peran |
|---|---|---|
| GET | `/admin/sertifikat` | Admin: kelayakan, penerbitan/unduh/revisi satu siswa |
| GET,POST | `/admin/sertifikat/aset`, `/admin/sertifikat/aset/{jenis}` | Admin: daftar/unggah PNG/JPG privat |
| GET | `/admin/sertifikat/aset/{id}/gambar` | Admin: preview gambar berizin |
| GET | `/admin/sertifikat/template/pratinjau/{id}` | Admin: PDF dummy A4 landscape, `id=0` fallback |
| POST | `/admin/sertifikat/aset/{id}/aktifkan` | Admin: pointer template/TTD, preview BG wajib |
| POST | `/admin/sertifikat/{paketId}/siswa/{siswaId}/terbit` | Admin: cek ulang TP Final, idempoten |
| POST | `/admin/sertifikat/{paketId}/siswa/{siswaId}/revisi` | Admin: alasan, konten berubah, versi/QR baru |
| GET | `/admin/sertifikat/{id}/unduh` | Admin: PDF privat berizin |
| GET | `/siswa/sertifikat`, `/siswa/sertifikat/{id}/unduh` | Siswa: hanya PDF milik sendiri |
| GET | `/verifikasi/{kode}` | Publik: token acak + throttle, whitelist informasi |

Service: `SertifikatAsetService`, `SertifikatPdfService`, `SertifikatPenerbitanService`, `KelayakanSertifikatService`. Pembuatan PDF dilakukan di luar transaksi DB; sebelum commit dikunci `siswa` dan diperiksa ulang kelayakan dan checksum konten. Transaction rollback hanya membersihkan PDF **baru**, versi lama tetap aman. PHP Dompdf menggunakan data-URI tanpa remote request/embedded PHP; QR endroid tidak memuat PII. Tidak mengubah root `index.php`/`.htaccess`.

### A12-1 — Beranda KPI Pimpinan baca-saja (3 November 2026)

`GET /pimpinan` diarahkan ke `PimpinanDashboard::index` dengan `auth:pimpinan` dan validasi tambahan `DashboardPimpinanService`. Filter opsional `gen`, `mapel`, `semester`, `halaman` divalidasi server. Pilihan paket memakai `LaporanAkademikService::paketPimpinan()`, sehingga paket nonaktif dan tidak ditugaskan tidak ikut. KPI agregat memakai pemisahan **target pasangan siswa×TP** (berdasarkan semester mulai) dan **TP tuntas sah** (status tuntas + Sumatif selesai sah), lalu nilai Final dihitung terpisah tanpa menafsirkan 0 sebagai NULL. Sertifikat dihitung dari pointer `sertifikat_aktif` yang status versinya berlaku. Antrean Guru adalah `resume_materi` / `jawaban_hots_materi` berstatus final dan nilai NULL, bukan draft. Semua hanya SELECT, tanpa transaksi mutasi, tanpa pengambilan nama/NISN siswa.

### A12-3 — Endpoint Dashboard Siswa dan kontrak data (3 November 2026)

`GET /siswa` diarahkan ke `SiswaDashboard::index` dengan `auth:siswa`. `DashboardSiswaService` memeriksa role aktif dan status lalu menggunakan `KatalogPembelajaranSiswaService::daftarMapel()/daftarSemester()/daftarTp()` sebagai satu-satunya sumber paket dan tautan TP yang **boleh dibuka**. Tidak ada parameter `siswa_id` atau `paket_id` dari GET untuk penentuan hak akses.

Agregasi hanya `SELECT` terhadap TP NonEsensial nonarsip, `progres_tp` milik siswa yang diautentikasi, percobaan Sumatif `selesai_sah` (setidaknya satu), dan `nilai_tp` Final non-NULL; sertifikat dihitung hanya dari `sertifikat_aktif` berstatus `berlaku`. Nilai 0 valid. Paket tidak memiliki TP wajib menghasilkan progres null, bukan 100%. Link lanjut belajar hanya berasal dari `akses.buka` katalog. Nama siswa/NISN, kunci ujian, token, dan jawaban tidak masuk DTO dashboard. HTTP no-store; tidak menulis progres, nilai, ujian, atau sertifikat.

Pra-Semester tetap di `/siswa/laporan`, terpisah dari kemajuan TP dan sertifikat. Tidak menambahkan API baru untuk lencana atau pengumuman pada A12-3.

### A12-4 — Read model Dashboard Admin (3 November 2026)

`GET /admin` tetap melalui `Akun::berandaAdmin` dan filter `auth:admin`; data KPI disediakan `DashboardAdminService::ringkasan()` dengan validasi role aktif tambahan, query baca-saja dan respons `Cache-Control: private, no-store`. Model menghitung status sumbernya (akun aktif, siswa terdaftar, TP Siap/Draf, antrean foto pada pointer aktif, sertifikat berlaku pada pointer aktif) tanpa mengambil nama/NISN ataupun menulis state. Kesiapan TTD menggunakan `SertifikatAsetService::path()` untuk memverifikasi berkas privat/sha256; tidak menerbitkan atau menyentuh PDF sertifikat. Status disk adalah hasil `disk_free_space(WRITEPATH)` tanpa ambang sehat otomatis; cron/backup tetap tidak dipantau sampai ada mekanisme resmi. Tidak menambah endpoint, SQL, API notifikasi atau cron baru.

### A13-1 — GET /bantuan: panduan operasional tanpa penyimpanan (3 November 2026)

`GET /bantuan` memakai filter `auth`; Controller `Bantuan::index` melakukan verifikasi ulang `AuthService::penggunaSaatIni()` untuk akun aktif, peran sah dan sandi awal sudah diganti. `PanduanBantuanService::untukPeran($peran)` mengembalikan katalog tetap FAQ penggunaan per peran berupa tanya/jawab serta `path` GET yang telah ada, dan menolak peran tidak dikenal. View `bantuan/index.php` memakai `details/summary` native, `esc()`, navigasi dari `NavigasiService` dan respons private/no-store. Tidak ada tabel, query baru, notifikasi, event, POST, upload, atau perubahan log. Isi petunjuk berasal dari Master akademik v2.5, **tidak menjadi teks FAQ resmi sekolah yang Admin dapat ubah**.

Model `FaqService` terkelola, `PengumumanService`, `NotifikasiService`, `Hubungi Admin` dengan kontak sekolah resmi dan Kebijakan Privasi berversi tetap memerlukan implementasi terpisah. Sebelum merancang SQL/kolom/indeks, gerbang AGENTS.md mengharuskan dump XAMPP terbaru sesudah patch terakhir; jangan menulis skema berdasarkan asumsi.

### A13-2 — Informasi global terkelola (3 November 2026)

Database dari dump XAMPP 03-11-2026 16.02 (MariaDB 10.4.32) dinyatakan belum memiliki `pengumuman`, `faq`, `riwayat_informasi`. SQL additive `V4-0012_PENGUMUMAN_FAQ.sql` membuat ketiganya dengan FK pengguna; `schema_version` menandai `V4-0012-PENGUMUMAN-FAQ`.

`InformasiGlobalService` mengelola daftar, baca, tambah, ubah dan arsip. Akses baca dan tulis selalu diperiksa ulang lewat `AuthService` dan role aktif. `daftar('pengumuman'|'faq',false)` mengembalikan `status=terbit`; Pengumuman Admin tampil global, Pengumuman Guru hanya untuk siswa Gen pengampu saat dibaca. Asal peran pembuat berasal dari catatan pertama `riwayat_informasi`, tidak dari role akun sekarang atau penulis perubahan terakhir. `siswa.pengguna_id` → `siswa.angkatan_id` → `penugasan_guru_gen.angkatan_id` dengan `penugasan_guru_gen.guru_id=pengumuman.dibuat_oleh` dan Guru harus aktif serta memiliki role Guru. COUNT dan LIMIT sama-sama difilter di Service. Daftar kelola Guru dibatasi `dibuat_oleh`; Admin dapat mengelola semua. Update memakai row lock MySQL `FOR UPDATE`, nomor `versi` untuk menghindari stale POST, transaksi database dan audit `riwayat_informasi` serta `log_aktivitas` dalam transaksi yang sama. Tidak memakai cron, worker, email/WhatsApp atau event notifikasi; Pengumuman Guru memiliki penyaringan Gen/role di query baca server.

| Method | Rute | Akses |
|---|---|---|
| GET | `/pengumuman` | Terbit sesuai hak: Admin global, Guru untuk Siswa Gen pengampu |
| GET | `/bantuan` | Seluruh login, panduan A13-1 + FAQ resmi Terbit |
| GET/POST | `/admin/pengumuman`, `/admin/pengumuman/tambah`, `/admin/pengumuman/{id}/sunting`, POST `.../{id}/arsip` | Admin |
| GET/POST | `/guru/pengumuman`, `/guru/pengumuman/tambah`, `/guru/pengumuman/{id}/sunting`, POST `.../{id}/arsip` | Guru, hanya Pengumuman miliknya |
| GET/POST | `/admin/faq`, `/admin/faq/tambah`, `/admin/faq/{id}/sunting`, POST `.../{id}/arsip` | Admin |

Rute GET daftar kelola baca saja; simpan dan arsip memakai POST + CSRF global. Arsip terminal, tidak ada hard DELETE, HTML input disimpan plain text dan selalu `esc()` pada View. Untuk A13-2, `NotifikasiService` internal belum diimplementasikan; jangan mengklaim penerbitan Pengumuman memicu notifikasi atau badge unread.


### A13-3 — Read model kontak resmi (3 November 2026)

`GET /hubungi-admin` menggunakan `HubungiAdmin::index` dan `KontakResmiMadrasahService::ambil()`, filter `auth` dan pemeriksaan ulang akun aktif, peran serta sandi awal. Service membaca **lima kunci whitelist** tabel `pengaturan`, lalu membangun tautan `tel:`, `mailto:` dan HTTPS/HTTP hanya untuk nilai yang lolos validasi. Nilai lama tidak valid tampil sebagai teks yang di-escape tanpa tautan. Halaman mempunyai status kontak kosong, HTTP `private, no-store`, tidak mengubah database dan tidak mengirim pesan. Tidak ada SQL, migrasi, notifikasi, worker atau koneksi ke profil pribadi.


### A13-4 — Kebijakan sandi efektif tanpa SQL (4 November 2026)

`AuthService::kebijakanSandi(daftarPeran, flagAsli)` menghitung apakah sandi wajib atau hanya dianjurkan diganti. Hasil `penggunaSaatIni()` menampilkan nilai efektif agar filter, Controller dan Service lama konsisten: akun non-Admin tidak digerbang oleh `wajib_ganti_sandi=1` historis, sedangkan pemegang Admin (termasuk role rangkap) tetap wajib. Tidak ada pembaruan otomatis password/flag di database saat login; rekomendasi bertahan sampai pengguna berhasil mengganti sandi. Semua sesi tetap bergantung pada `versi_sesi` dan role aktif.

`PenggunaAdminService::resetSandi()` membedakan dua mode secara server-side setelah SELECT FOR UPDATE dan validasi role. Mode `acak` untuk akun pemegang Admin (tampil sekali), mode `default` untuk Guru/Pimpinan/Siswa yang mensyaratkan input manual sandi default dan konfirmasi di Controller. Hanya hash yang disimpan; versi sesi naik, gagal login direset, audit dibuat tanpa sandi, dan output mode default tidak pernah mengembalikan plaintext. Form `ganti-sandi` Guru/Pimpinan/Siswa dipindah ke `GET /profil`; POST aman tetap memakai endpoint yang ada dan CSRF. Tidak ada `.env`, konfigurasi domain, tabel, migrasi maupun seeder baru.
