# Checkpoint — ATP 6 Semester, Impor, Materi, dan Akses Siswa (31 Oktober 2026)

Status: **branch fitur untuk uji XAMPP, belum merge ke `main`**.
Branch: `feature/v25-atp-enam-semester-audit-belajar-20261031`.
Basis: `feature/v25-atp-dropdown-bab-urutan-otomatis-20261031`.

## Perubahan

1. **Paket draf Guru 6 Semester**. Pada `/guru/peta-atp` tersedia satu formulir Gen–Mapel/Kurikulum dan Tahun Ajaran masing-masing Semester 1–6. Pembuatan paket terjadi sekaligus dalam satu transaksi, hanya untuk semester yang belum punya paket. Paket draf/aktif tidak diubah; data duplikat, arsip/kurikulum konflik ditolak. Modal Paket Draf per semester dihapus. Semester sebelum `semester_mulai` Gen dapat disiapkan Guru, **tidak otomatis dapat dibuka siswa**. Pengaturan BAB berurutan dalam pasangan 1–2/3–4/5–6 tetap; Tahun Ajaran semester berpasangan boleh berbeda.
2. **Impor Excel ATP**. Service yang ada tetap membaca enam semester dari file V25-3, membandingkan terhadap BAB/TP yang sudah ada, dan hanya menerima penambahan di semester dengan paket sah dan BAB/TP kosong. Semester identik = Tetap; perubahan terhadap BAB/TP manual = Galat. Pratinjau staging dan konfirmasi memakai fingerprint lalu revalidasi di dalam transaksi. Pembuatan enam paket sebelumnya menghilangkan penghalang bahwa guru harus membuka halaman semester satu per satu untuk membuat paket.
3. **Materi**. Penambahan materi kedua dicegah di Service untuk YouTube maupun PDF/Gambar/Audio; editor menampilkan Sunting setelah satu Materi ada. Checklist awal dan TP Siap sama-sama mensyaratkan tepat satu Materi untuk Esensial dan NonEsensial. **Tidak mengubah** validator PDF 15 MB/40 halaman dan media privat yang sudah diuji sebelumnya.
4. **Siswa**. Semester di bawah awal Gen tetap Dilewati. Paket hanya berasal dari penugasan `penugasan_paket` Gen siswa dan status aktif; URL paket dan TP divalidasi lagi di Service. Semester setelah awal Gen hanya terbuka jika semua TP NonEsensial semester wajib sebelumnya berprogres `tuntas` **milik siswa itu sendiri DAN memiliki percobaan Sumatif `selesai_sah`**. TP Esensial tidak membuka aktivitas dan tidak menjadi syarat progres. TP berikutnya memerlukan TP NonEsensial sebelumnya tuntas **dan bukti Sumatif sah**. Diagnostik lulus memindahkan progres ke `sumatif` (bukan `tuntas`), dengan hak belajar materi sukarela tetap tersedia. Status `tuntas` historis setelah Diagnostik tidak cukup membuka jalur. TP draf/tanpa snapshot tetap tidak bisa dibuka.

## Pemeriksaan ulang sebelum pull (31 Oktober 2026)

- Ditemukan bug kritis: Diagnostik lulus sebelumnya memberi status `tuntas`, padahal Sumatif wajib. Diperbaiki menjadi `sumatif`; pembaca media pasca-Diagnostik tetap dapat dibuka sukarela.
- Semua gerbang buka TP berikutnya dan semester berikutnya sekarang memerlukan bukti `percobaan_ujian.jenis=sumatif` dan `status=selesai_sah` pada progres TP siswa yang sama. Gerbang gagal tertutup bila Sumatif belum ada.
- Master Excel di repository SHA blob `fc0901acdfc7e88721658dd786862a3c4400c627`: diverifikasi 17 entri ZIP, seluruh CRC cocok, tanda V25-3 tersedia, enam Defined Name BABS, 480 rumus COUNTIFS, 18 dropdown dengan `showDropDown=0`, dan enam sheet terproteksi.
- Halaman Kelola Excel diarahkan ke Peta ATP (bukan membuat draf per semester). Label Dilewati Gen dihapus dari status persiapan Guru; batas Gen untuk siswa tidak dihapus.
- Lint PHP dan PHPUnit **belum dapat dijalankan pada salinan repository yang utuh**. PHP CLI di mesin pemeriksaan terpisah tidak mempunyai dependensi/extensi proyek dan tidak tersedia clone repository melalui jaringan; GitHub Actions pada branch mengembalikan `startup_failure` atau run gagal sebelum memulai job. **Tidak ada tes otomatis yang boleh diklaim PASS**.
- Modul **Sumatif server-side yang lengkap belum terlihat pada route/service yang diperiksa**; dengan demikian, alur perpindahan TP/semester sampai akhir belum dapat dinyatakan FIX. Ini secara sadar fail-closed, bukan layar selesai palsu. Pengujian end-to-end setelah modul Sumatif tersedia wajib dilakukan.

## Pengujian dan keterbatasan

- Tes unit/integrasi di repository diperluas untuk validasi enam Tahun Ajaran, UI/CSRF route, Gen lintas semester, progres TP/semester, larangan aktivitas Esensial, larangan Materi kedua dan pemeriksaan PDF.
- **Belum dilakukan** eksekusi PHPUnit/CI dan pengujian browser/XAMPP pada branch terbaru. Tidak tersedia database MySQL QA terbaru atau dump privat proyek di lingkungan AI. Jangan menyebut seluruh empat modul FIX sampai bukti PASS dilaporkan.
- Tes integrasi MySQL memakai database `sla_sks_qa` khusus; **jangan** diarahkan ke `sla_sks_v4` yang berisi data nyata, jangan membuat Migration/Seeder.
- Ada **berkas patch SQL additive V4-0005** untuk pelebaran nama BAB/TP 35→50 karakter; **BELUM dijalankan pada database lokal**. Tidak ada perubahan .env, index.php/.htaccess, atau data siswa nyata. Tidak ada pembaruan pada `main`.
- Pengujian manual prioritas: buat draf enam semester untuk satu Gen–Mapel dengan semester mulai 4; unduh/isi/impor V25-3, pratinjau & konfirmasi sekali, unggah materi masing-masing YouTube/Audio/Gambar/PDF dan uji Sunting; login siswa Gen sama pastikan S1–3 tidak bisa diakses, S4 mengikuti TP Siap/urutan, S5 baru terbuka setelah semua TP wajib S4 tuntas. Uji URL langsung milik Gen lain.

## Risiko yang harus diuji di XAMPP

- Transaksi penyimpanan enam paket dengan data semester eksisting atau arsip/kurikulum berbeda.
- Pengisian Tahun Ajaran berbeda per semester, dan pembentukan Kode TP dan BAB lintas kelas/semester.
- File Excel yang sudah diisi Guru dan data CP aktif pada database lokal harus diuji tanpa menyimpan ulang/menimpa data akademik.
- Preview/upload media dan snapshot siswa bergantung data dan modul terkait di aplikasi. Audit kode tidak menggantikan pengujian media nyata.

**DILARANG** menganggap data historis boleh dihapus untuk membuat seluruh semester kosong. Tidak ada impor ke database oleh AI. Tunggu konfirmasi hasil XAMPP sebelum merge.

## Audit gabungan Master v2.5, branch dan dump v4 31-10-2026 (hasil lanjutan)

**Sumber:** dump SQL privat lokal, ekspor 31 Oktober 2026 pukul 14.06 (MariaDB 10.4.32). File SQL mengandung data pribadi/sesi/hash sandi; **tidak pernah dimasukkan ke GitHub**. Yang dicatat hanya pemeriksaan skema/agregat aman.

- Skema: 58 tabel, `schema_version` mencatat hingga `V4-0004-BERSIH-SKI-LAMA`; tersedia 1 paket semester aktif (semester 4 pada satu Gen–Mapel), serta BAB/TP/Materi/progres aktif kosong dalam dump. Form enam semester harus mempertahankan paket aktif lama dan membuat hanya semester lain; jangan reset data.
- `bab.nama_bab VARCHAR(35)` dan `tp.nama_tp VARCHAR(35)` berbeda dengan 50 karakter menurut Master. Patch additive `database/sql/updates/V4-0005_PERLEBAR_NAMA_BAB_TP_50.sql` dibuat tanpa mengubah data di mesin pengguna. **Backup dahulu, lalu impor di database lokal yang tepat dan verifikasi**; hingga saat itu validasi batas kolom 35 tetap dipakai.
- Bug ditemukan: `TpBabService` masih menolak Guru menyiapkan TP pada semester sebelum awal Gen. Batas tersebut dihapus hanya pada **persiapan Guru**; gate siswa tetap berlaku.
- Bug ditemukan: TP v2.5 yang mempunyai `cp_mapel_id` tidak otomatis mempunyai baris `tp_cp` legacy. `KesiapanTpService` kini memeriksa CP operasional dan elemen CP aktif melalui `cp_mapel_elemen` dan `ref_cp_versi`; bukan lagi mewajibkan tautan CP utama v2.4.
- Bug ditemukan: kesiapan dan ringkasan soal membolehkan bank Sumatif minimum 5. Diselaraskan menjadi **minimum 30**; bobot jalur belajar wajib **20/20/60** dan kuota Sumatif **2 percobaan total** (normal + remedial bila diperlukan).
- TP Esensial Guru tetap wajib memiliki satu Materi/media siap. Untuk pemeriksaan TP Siap, bank ujian siswa serta petunjuk Resume/HOTS hanya diwajibkan pada NonEsensial.
- Bug ditemukan: rencana Judul Materi (F) dan Media (G) dari ATP Excel tidak tersimpan setelah konfirmasi. Sekarang setiap TP baru menghasilkan satu Materi **draf tidak aktif** dalam transaksi yang sama. Belum ada file, URL ataupun waktu belajar sah; Guru wajib melengkapi lewat editor sebelum TP Siap.
- Bug ditemukan: paket draf yang berubah aktif pada penerbitan TP pertama belum mempunyai `penugasan_paket`, sehingga Siswa tidak dapat melihatnya. Penerbitan sekarang memvalidasi dan memastikan relasi penugasan per Gen–Mapel–Semester–Tahun Ajaran tanpa menimpa relasi lama.
- Keterbatasan utama: implementasi penuh Sumatif/remedial, penilaian, dan Pra-Semester belum tersedia pada seluruh alur siswa di branch ini. **Status aplikasi BELUM FIX end-to-end**, walau fondasi dan gate yang sudah ada dibuat lebih konsisten.
- **Pengujian nyata:** inspeksi isi file/dump, struktur DDL, pembacaan kode dan audit regresi secara statis; PHPUnit, lint PHP seluruh repository, MySQL QA dan browser XAMPP **belum berhasil dijalankan di lingkungan AI**. GitHub Actions sebelumnya gagal di startup. Uji lokal wajib sebelum merge.


## Koreksi audit final setelah pendalaman kode dan dump (31 Oktober 2026)

- `AtpImporPratinjauService` kini memasukkan baris Materi ke *fingerprint* dan pemeriksaan status **Tetap** (judul serta jenis media F/G); tidak menerima perbedaan rencana media sebagai isi ATP identik.
- Server dan UI editor Materi memperoleh jenis TP dari database. Untuk **Esensial** satu media tetap wajib, sementara petunjuk Resume/HOTS opsional. Untuk **NonEsensial**, petunjuk tersebut tetap wajib. File media harus aktual; draf hasil impor tidak dianggap selesai.
- `KesiapanTpService` menolak penerbitan TP NonEsensial dengan Sumatif di bawah 30 soal, bobot selain 20/20/60, atau kuota selain dua kesempatan (tes normal dan satu remedial bila diperlukan). Penilaian akhir/remedial **belum** diimplementasikan dalam tahap ini.
- Daftar uji prioritas setelah pull: (1) pastikan satu paket Semester 4 lama tidak berubah dan lima paket draf dibuat; (2) import ATP V25-3 pada enam semester ke data kosong via staging/pratinjau/konfirmasi, termasuk rencana Materi; (3) unggah/sunting masing-masing media dalam draf, Esensial tanpa tugas serta NonEsensial dengan tugas; (4) TP Siap memvalidasi CP operasional dan 30 Sumatif; (5) hanya paket aktif dengan penugasan valid muncul bagi siswa mulai Semester 4, semester 1–3 tetap ditolak; (6) penolakan akses URL lintas Gen, dan abort/rollback impor yang gagal.
- **Jangan jalankan** pengujian yang memodifikasi tabel pada database asli `sla_sks_v4`. Uji destruktif/rollback hanya pada salinan QA; jangan menaruh dump privat pada GitHub.
