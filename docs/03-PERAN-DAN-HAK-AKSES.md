# 03 — Peran dan Hak Akses

## 1. Empat role dan peran aktif
| Role | Area utama |
|---|---|
| **Admin** | Pengelola/Siswa, impor/ekspor, reset sandi, Gen–Mapel–Guru, CP, profil sekolah, branding/template, biodata, penyuntingan CP langsung, pengumuman, FAQ, laporan, pemeliharaan |
| **Guru** | TP/paket milik Gen–Mapel yang diampu G1, impor, media, soal, penilaian, keputusan ujian, monitoring, pengumuman untuk siswa Gen pengampu |
| **Siswa** | Belajar dan ujian miliknya, nilai, pengumuman, FAQ, notifikasi, sertifikat sendiri |
| **Pimpinan** | Dasbor, monitoring dan laporan secara **baca saja**; pratinjau siswa tanpa perubahan data |

Satu akun staf boleh memiliki dua role saja: Admin+Guru, Admin+Pimpinan, Guru+Pimpinan. Siswa tidak dirangkap. Role aktif disimpan di sesi, sesi diregenerasi saat ganti role; otorisasi tidak menggabungkan hak kedua role sekaligus.

## 2. Matriks fitur
| Tindakan | Admin | Guru | Siswa | Pimpinan |
|---|---|---|---|---|
| Membuat/reset/nonaktif akun | **Ya** | Tidak | Tidak | Baca terbatas |
| Memperbaiki biodata/NISN siswa | **Ya** | Tidak | Tidak | Tidak |
| Mengelola Gen dan semester mulai | **Ya** | Tidak | Tidak | Baca |
| Menetapkan Guru untuk Gen–Mapel | **Ya** | Tidak | Tidak | Baca |
| Menambah dan menyunting CP (termasuk CP yang telah dipakai TP/siswa) | **Ya, langsung** | Tidak | Baca ringkas | Baca |
| Menyusun/menyunting TP, Materi dan soal | Tidak | **Ya, G1 Gen–Mapel; Draf atau Siap yang belum digunakan siswa** | Tidak | Tidak |
| Mengubah TP Siap yang sudah digunakan siswa | Tidak | **Tidak; konten terkunci** | Tidak | Tidak |
| Menyunting isian BAB yang ada | Tidak | **Ya, G1 Gen–Mapel; tidak terkunci hanya karena TP-nya Siap** | Tidak | Tidak |
| Menilai resume/tugas | Tidak | **Ya, Gen–Mapelnya** | Tidak | Tidak |
| Mengajukan koreksi nilai oleh guru pengganti | Tidak | **Ya, Gen–Mapelnya** | Tidak | Tidak |
| Menyetujui koreksi akademik | **Ya**, bukan usulan sendiri | Tidak | Tidak | Tidak |
| Memberi izin akses ulang Diagnostik/Sumatif; melihat pembatalan Pra-Semester | Tidak | **Ya, Gen–Mapelnya** | Tidak | Baca |
| Menyetel logo aplikasi/favikon, TTD PNG dan template | **Ya** | Tidak | Tidak | Baca |
| Mengelola Pengumuman | Semua; publikasi Admin global | Hanya miliknya; publikasi untuk siswa Gen yang diampu | Baca Admin dan Guru pengampu Gen-nya | Baca Admin saja |
| Mengelola FAQ dan teks Kebijakan Privasi sekolah | **Ya** | Tidak | FAQ baca / privasi publik | FAQ baca / privasi publik |
| Mengunduh sertifikat siswa | Seluruhnya | Gen–Mapelnya | Miliknya | Seluruhnya (baca) |
| Melihat log audit sensitif | **Ya** | Audit relevan miliknya | Tidak | Ringkasan baca sesuai kebijakan |
| Menyunting pengaturan keamanan & server | **Ya** | Tidak | Tidak | Tidak |

### Hak administratif dan profil v2.4
| Tindakan | Admin | Guru | Siswa | Pimpinan |
|---|---|---|---|---|
| Mengelola akun Pengelola/Siswa, impor/ekspor XLSX, reset per akun | Ya | Tidak | Tidak | Tidak |
| Mengubah foto/kontak pribadi/sandi sendiri | Ya | Ya | Sandi sendiri; biodata resmi baca saja | Ya |
| Mengubah nama resmi/NIP/peran/NISN/Gen | Ya, diaudit | Tidak | Tidak | Tidak |
| Melihat profil profesional Guru pengampu Gen siswa | Ya | Menurut cakupan | Ya, khusus Guru Gen-nya | Baca sesuai izin |
| Melihat hash/sandi/token/sesi pengguna lain | Tidak pernah | Tidak | Tidak | Tidak |

Siswa hanya boleh melihat identitas profesional Guru (nama, foto yang disetujui, mata pelajaran), bukan NIP, telepon pribadi atau informasi keamanan. Login dan tabel akun tetap bersama; Pengelola dan Siswa adalah dua ruang UI Admin.

## 3. Model G1 v2.4 — penugasan Guru per Gen–Mapel
Sumber kewenangan Guru adalah relasi `penugasan_guru_gen(angkatan_id, mapel_id, guru_id)`, satu Guru aktif per pasangan Gen–Mapel dan berlaku pada semua semester Gen tersebut. Guru boleh mengampu banyak Gen/mapel; tidak ada penugasan per siswa, rombel atau semester. Kolom lama `ref_mapel.guru_id` bukan sumber izin v2.4. Hanya akun berstatus aktif dengan peran `guru` yang boleh ditunjuk; akun Admin+Guru dan Guru+Pimpinan memenuhi syarat, tetapi saat mengajar harus memakai role aktif Guru.

Setiap paket/TP/soal/materi memiliki konteks Gen pemilik yang tidak dapat dilompati dengan mengubah ID URL. Guru pengampu aktif G1 dapat meneruskan Draf maupun menyunting TP Siap **yang belum digunakan siswa** pada Gen–Mapel yang dialihkan. TP yang sudah digunakan siswa tetap terkunci; nilai, versi, audit dan penilai lama tetap utuh. Gen lain tidak terpengaruh. Bila pengampu Gen–Mapel kosong, siswa masih boleh melanjutkan TP Siap historis yang sudah tersedia; penilaian baru menunggu Guru.

## 4. Aturan self-approval yang dibatasi tepat sasaran
- Sunting CP oleh Admin, sunting BAB oleh Guru, dan sunting TP Siap yang belum digunakan siswa **bukan** proses pengajuan. Tidak ada pemeriksaan self-approval untuk ketiga aksi langsung itu.
- Bila masih terdapat proses koreksi nilai siswa yang benar-benar memerlukan keputusan Admin, akun pengusul yang sama tidak boleh memutus usulannya sendiri.
- Admin A secara langsung **mengedit biodata administratif** siswa: tidak ada konsep usulan Guru/approval ganda. Admin A dapat menerbitkan revisi identitas sertifikat berdasarkan perbaikan tersebut; dicatat otomatis.
- Tidak disyaratkan minimal dua Admin. Aturan self-approval hanya berlaku pada proses persetujuan akademik yang memang tersisa (misalnya koreksi nilai), bukan edit CP/BAB/TP belum digunakan.

## 5. Proteksi server
Pemeriksaan izin dilakukan di Controller **dan** Service: `peran_aktif`, status akun, `penugasan_guru_gen` + Gen/Mapel objek, `siswa_id` untuk siswa, status TP, dan versi objek. Guru tidak boleh mengakses siswa mapel lain dengan mengganti ID URL. Pimpinan tidak boleh melakukan POST mutasi maupun lewat API. Peran aktif bukan bukti kepemilikan. Semua percobaan akses ilegal dilog sewajarnya.

## 6. Peta navigasi per role
- **Siswa:** Beranda → Mapel → Semester → TP → TP → Diagnostik/Materi/Resume/Tugas/Sumatif → Nilai → Sertifikat; Pengumuman, Notifikasi, Bantuan, Profil.
- **Guru:** Beranda → Pilih Gen–Mapel → Semester/ATP → Struktur TP & CP → Excel Peta & Media → Bank Soal → Checklist Siap → Antrean Penilaian → Permintaan Akses Ulang → Monitoring → Laporan; Pengumuman.
- **Admin:** Beranda → Pengelola → Siswa → Gen → Mapel/Guru per Gen → Referensi CP → Koreksi Nilai (bila diperlukan) → Pengaturan (Identitas/Aset/Template/TTD) → Pengumuman/FAQ → Sertifikat → Log → Pemeliharaan → Laporan.
- **Pimpinan:** Beranda → Progres → Siswa → Mutu TP/Guru → Ujian → Sertifikat/Laporan → Pratinjau baca-saja.

### Hak baru yang sama dalam v2.4
- Guru G1 boleh **unduh/unggah peta ATP Gen–Mapel**, impor bank soal Word Diagnostik/Sumatif per TP, mengisi Resume/HOTS tiap Materi dan menerbitkan TP Siap. Admin tidak mengisi konten Guru.
- Admin berhak mengekspor daftar CP dan status versi sesuai filter (tanpa data sensitif), serta mengunggah logo aplikasi yang juga digunakan sebagai favicon. Seluruh akses POST/GET divalidasi server.
- Siswa hanya melihat TP yang telah Siap sesuai urutan semester; Pimpinan hanya membaca laporan/progres.

## 7. Hak akses tambahan pola BAB–TP v2.5
- **Admin:** mengelola KKM default, jadwal utama/remedial/susulan dan durasi per Gen–Mapel–Semester, identitas referensi kurikulum/CP; tidak mengubah nilai Guru secara langsung.
- **Guru:** G1 berwenang memberi akses ulang Diagnostik/Sumatif mandiri sesuai percobaan yang tidak selesai; tidak menyamakan akses ulang dengan remedial nilai. Pembatalan hanya Pra-Semester.
- **Guru:** G1 per Gen–Mapel tetap; boleh mengisi ATP lewat aplikasi/Excel, mengelola BAB dan TP Esensial/NonEsensial, tepat satu Materi untuk setiap TP Esensial dan NonEsensial, bank soal TP dan Pra-Semester, penilaian, dan **Export Nilai** seluruh siswa Gen–Mapel termasuk hasil parsial.
- **Siswa:** mengakses BAB/TP sesuai Gen/semester; hanya TP NonEsensial memerlukan Diagnostik, Materi jika gagal, Resume/HOTS jika wajib, Sumatif dan remedial. Pra-Semester dibuka oleh jadwal Admin setelah semua TP NonEsensial selesai.
- **Pimpinan:** melihat progres semester/hasil TP dan Pra-Semester secara baca-saja, tidak mengatur jadwal atau nilai.
Seluruh layanan menegakkan Gen–Mapel dan peran aktif di Controller **dan Service**, termasuk file ekspor agar Guru lain tidak dapat mengambil rekap siswa.

## 8. Otorisasi edit tanpa pembatasan berlebihan — 2 November 2026
- **Admin CP**: POST Tambah/Sunting CP langsung diizinkan untuk CP terpakai, tanpa pengecekan status penggunaan siswa atau keputusan reviewer.
- **Guru BAB**: editor isian BAB yang sudah ada dapat dibuka sesuai penugasan G1; jangan menutupnya hanya karena satu TP dalam BAB `siap`. Tidak menambah operasi pemindahan TP antar-BAB.
- **Guru TP**: cek penugasan G1 dan bukti penggunaan siswa pada objek TP; boleh sunting Draf maupun Siap-belum-digunakan. Tolak seluruh mutasi konten pada TP yang pernah digunakan siswa, bukan sekadar yang `status=siap`.
- **Admin** tidak ikut memberi approval atas edit CP/BAB/TP pada jalur ini; operasi koreksi nilai siswa dan perlindungan sertifikat tidak berubah.


### Pembagian kewenangan Pra-Semester (3 November 2026)
- **Guru G1:** kelola bank soal semester dan KKM khusus Pra-Semester dalam Gen–Mapel sendiri, lihat status/jawaban dan pembatalan milik siswanya; **tidak** menjadwalkan atau menyetujui susulan.
- **Admin:** jadwalkan jendela **mulai** dan durasi tiap Gen–Mapel–Semester secara terpisah; jadwalkan remedial dan satu atau lebih susulan, tetapkan peserta susulan yang dibatalkan/tidak hadir, audit perubahan. Tidak membuat/mengoreksi nilai atau bank soal Guru.
- **Siswa:** hanya dapat memulai ketika jendela Admin untuk tahap/identitasnya berlangsung dan seluruh TP NonEsensial paket selesai; setelah mulai memiliki durasi pengerjaan individual. Siswa yang dibatalkan tidak dapat memulai lagi tanpa masuk jadwal susulan Admin. **Pimpinan:** hanya baca hasil.
- Rumusan historis "Guru menangani pembatalan Pra-Semester" berarti **melihat/memonitor insiden**, bukan memberi izin ulang yang menggantikan jadwal Admin.

### A10-1 — Hak akses laporan akademik (3 November 2026)

- **Siswa:** membuka `GET /siswa/laporan` dan `GET /siswa/laporan/{paketId}`, hanya untuk `siswa_id` milik akun login dan semester yang sudah boleh dibuka menurut katalog. Tidak menerima ID siswa dari URL, tidak menulis progres/nilai, dan tidak memberi akses ke materi/ujian di luar alur pembelajaran.
- **Pimpinan:** membuka `GET /pimpinan/laporan` untuk memilih Gen–Mapel–Semester ditugaskan, daftar siswa dan progres baca-saja; `GET /pimpinan/laporan/{paketId}/siswa/{siswaId}` adalah pratinjau laporan, dengan verifikasi Gen siswa sama dengan Gen paket. Pimpinan tidak berhak mengubah skor, memperpanjang ujian atau melakukan POST penilaian.
- Nilai yang ditampilkan TP dan Pra-Semester berdiri sendiri. Halaman laporan tidak menjadi jalan memintas hak Guru G1, identitas siswa lain, atau syarat sertifikat. Server menolak perubahan peran aktif atau pemalsuan id.

### A12-1 — Dashboard baca-saja Pimpinan (3 November 2026)

Beranda `/pimpinan` menghitung KPI paket aktif/ditugaskan, Gen/Mapel/semester, siswa yang wajib TP, hasil TP tuntas dengan Sumatif sah, Nilai TP Final, sertifikat berlaku, dan antrean Resume/HOTS final belum dinilai. Service menegakkan akun Pimpinan aktif selain filter rute; tidak boleh mengakses jalur ini dengan role Guru/Admin/Siswa, termasuk staf multi-role yang sedang memakai role lain. Tombol dashboard hanya GET menuju Monitoring A10-1. Status Pra-Semester ditampilkan mandiri dan **tidak** menjadi gerbang sertifikat atau nilai akhir semester. Tidak ada hak menilai, menerbitkan sertifikat, atau mengubah jadwal dari beranda.

### A12-2 — Akses Dashboard Guru (3 November 2026)

Beranda `/guru` menunjukkan indikator kerja hanya untuk penugasan aktif `penugasan_guru_gen.guru_id` milik role aktif Guru; tidak boleh membaca Gen–Mapel lain melalui parameter `pilihan`/Semester yang dibuat sendiri. Filter role dan otorisasi Service sama-sama wajib. Dashboard **GET baca-saja**, aksi Penilaian/Kelola TP/Akses Ulang diarahkan ke halaman yang telah memiliki otorisasi G1 sendiri. Siswa yang perlu bantuan dihitung agregat tanpa menampilkan NISN/teks jawaban, dan Sumatif/Pra-Semester tetap berbeda. Tidak ada penyatuan hak Guru–Pimpinan untuk staf multi-role.

### A12-3 — Dashboard Siswa (3 November 2026)

Beranda `/siswa` memakai filter `auth:siswa` dan pemeriksaan ulang role/status di `DashboardSiswaService`. Semua paket berasal dari `KatalogPembelajaranSiswaService` yang memeriksa Gen, penugasan, semester mulai dan gerbang semester sebelumnya. Kode TP tujuan navigasi juga harus lolos `daftarTp` dengan `akses.buka`. Tidak menerima `siswa_id` atau `paket_id` dari GET; tidak membaca nama/NISN siswa lain, kunci, jawaban, token ujian, atau identitas autentikasi. Hanya SELECT; mengunjungi dashboard tidak memulai TP, percobaan maupun mengubah skor. Pengampu Guru dan Admin tidak mewarisi hak dashboard siswa pada akun multi-role.

### A12-4 — Otorisasi dan privasi Dashboard Admin (3 November 2026)

`GET /admin` menggunakan filter `auth:admin` serta pemeriksaan role aktif/status/`wajib_ganti_sandi` di `DashboardAdminService`. Dashboard hanya melakukan SELECT agregat dan pemeriksaan berkas TTD privat (keberadaan + checksum); tidak memuat nama, NISN, password, hash, token, PDF/TTD mentah atau jawaban siswa pada HTML ringkasan. Foto belum disetujui dihitung hanya bila masih ditunjuk oleh `profil_pengelola.foto_menunggu_id` dan status `foto_profil=menunggu`; sertifikat historis `digantikan` tidak dihitung. Link tindakan mengarah ke halaman Admin yang telah dilindungi sendiri. Header `Cache-Control: private, no-store` wajib. Pada akun multi-role yang sedang berperan Guru/Pimpinan, tidak boleh melihat Dashboard Admin.

### A13-1 — Bantuan dasar sesuai peran aktif (3 November 2026)

`GET /bantuan` berlaku hanya untuk akun sudah login dengan status aktif; akun non-Admin boleh mengakses dengan sandi awal sambil memperoleh anjuran penggantian. Akun pemegang peran Admin tetap mengikuti kewajiban ganti sandi awal. AuthFilter `auth` dan `Bantuan::index` mengecek sesi/peran aktif; panduan `PanduanBantuanService::untukPeran()` hanya mengembalikan pertanyaan dan tautan GET yang sesuai **peran aktif** (bukan gabungan role staf). Guru tidak memperoleh panduan tautan Admin, Siswa tidak melihat tautan Guru/Admin, dan Pimpinan hanya menuju monitoring baca-saja atau Profil. Semua target tetap menerapkan otorisasi normal server ketika diklik. Halaman Bantuan tidak menerima ID siswa/Gen sebagai query, tidak melakukan mutasi, dan memakai `Cache-Control: private, no-store`.

Halaman ini adalah **panduan operasional tetap yang diverifikasi terhadap Master**, bukan sistem FAQ yang dapat diubah Admin. Pengumuman, FAQ resmi terkelola, notifikasi internal dan kontak resmi Hubungi Admin memiliki hak akses tersendiri; rincian terbaru A13-5 di bawah berlaku untuk Pengumuman Guru. Notifikasi internal masih memerlukan tahap terpisah.

### A13-2/A13-5 — Pengumuman Admin global, Guru per Gen dan FAQ resmi

| Aksi | Admin | Guru | Siswa | Pimpinan |
|---|---|---|---|---|
| Membaca Pengumuman berstatus Terbit | Buatan Admin | Buatan Admin | Buatan Admin + Guru yang mengampu Gen sendiri | Buatan Admin |
| Membuat Pengumuman | Global | Untuk Siswa Gen yang diampu | Tidak | Tidak |
| Menyunting/mengarsip Pengumuman | Seluruhnya | **Miliknya sendiri** | Tidak | Tidak |
| Membaca FAQ resmi berstatus Terbit pada Bantuan | Ya | Ya | Ya | Ya |
| Membuat/menyunting/mengarsip FAQ resmi | Ya | Tidak | Tidak | Tidak |

Pembaca hanya memperoleh status `terbit`, tidak melihat draft/arsip. Satu akun staf multi-role tidak menggabungkan hak: saat aktif sebagai Guru ia tidak bisa mengelola FAQ; perubahan peran melalui Profil terlebih dahulu. URL `/admin/pengumuman`, `/guru/pengumuman`, `/admin/faq` memerlukan filter role **dan** otorisasi Service untuk mencegah manipulasi ID. Tidak ada endpoint penghapusan permanen.


### A13-3 — Hubungi Admin memakai kontak resmi (3 November 2026)

Seluruh peran **aktif dan login** (Admin, Guru, Siswa, Pimpinan) boleh membuka `GET /hubungi-admin`. Sumber hanya kunci `nama_madrasah`, `alamat_madrasah`, `telepon`, `email`, dan `website` dari tabel `pengaturan` yang diisi Admin pada Identitas Madrasah. Tidak membaca `profil_pengelola`, nomor pribadi Admin/Guru, NIP, NISN atau detail autentikasi. Role aktif diperiksa kembali di Service; tidak ada formulir/tiket/chat/pesan otomatis.


### A13-4 — Aturan sandi menurut kepemilikan role (4 November 2026)

- Akun dengan **peran Admin** (termasuk Admin+Guru, Admin+Pimpinan) tetap wajib mengganti sandi pertama/reset sebelum beroperasi, apa pun peran aktif yang dipilih. Resetnya hanya menggunakan CSPRNG acak.
- Guru/Pimpinan/Siswa **boleh langsung masuk** meskipun memakai sandi awal/reset, namun dianjurkan mengganti sandi di halaman Profil. Reset non-Admin oleh Admin memakai sandi default yang diketik/diulang saat konfirmasi, bukan nilai universal yang ditanam di kode.
- `AuthService::penggunaSaatIni()` mengekspos kebijakan efektif `wajib_ganti_sandi` untuk Admin dan `sandi_direkomendasikan` untuk non-Admin tanpa mengubah kolom skema. Semua penolakan akses oleh Controller/Service memakai hasil autentikasi yang telah terverifikasi.
- Navigasi `Informasi & Bantuan` menyatukan Pengumuman, Kelola Pengumuman (Admin/Guru), Kelola FAQ (Admin), Bantuan dan Hubungi Admin. Pengelompokan menu tidak menaikkan hak akses: Guru tetap hanya boleh mengelola Pengumuman buatannya, bukan Pengumuman rekan maupun FAQ.
