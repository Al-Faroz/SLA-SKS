# Status keputusan Master v2.5 — 26 Oktober 2026

## Keputusan final
- CP → Semester → BAB → TP; satu CP operasional/mapel, beberapa elemen; BAB/TP 50+225, kode `MAPEL.KELAS.BAB.TP`, nomor BAB sambung 1–2/3–4/5–6.
- TP Esensial maupun NonEsensial wajib disiapkan Guru dengan tepat satu Materi/media. Aktivitas siswa, ujian dan nilai hanya wajib pada TP NonEsensial; Esensial tetap tercatat di ATP.
- Diagnostik ≥10 gate; Sumatif ≥30 wajib, bobot jalur gagal 20/20/60 atau lulus 100% Sumatif, KKM default Admin/khusus Guru, 1 remedial <KKM ambil nilai tertinggi, TP Selesai meski nilai di bawah KKM.
- Guru isi ATP langsung atau Excel ATP manual `.xlsx` tanpa macro dan tanpa tombol Proses; template resmi sudah ditambahkan ke branch fitur pada `docs/`; integrasi generator/impor sudah dikodekan pada branch fitur dan membutuhkan verifikasi manual melalui XAMPP sebelum dinyatakan selesai. Progres persemester hanya NonEsensial.
- Pra-Semester pada tanggal dan jam serentak Admin setelah semua NonEsensial selesai, bank gabungan BAB, pola Sumatif, nilai berdiri sendiri. Tidak ada nilai akhir semester. Export Nilai Guru dapat dilakukan dari satu skor TP.
- Database baru `sla_sks_v4` terpisah dari `sla_sks_v3`, sumber privat tak dikirim ke repo; semua versi/progres lama dipertahankan sebagai historis.

## Keputusan yang telah ditetapkan pada 3 November 2026
- **Tidak:** siswa **tidak wajib menuntaskan Pra-Semester** untuk mendapatkan sertifikat. Syarat sertifikat tetap seluruh TP NonEsensial selesai, nilai wajib TP final, serta identitas dan TTD siap.
- **Tidak:** nilai Pra-Semester **tidak dicetak** di sertifikat. Nilai mandiri ini hanya ditampilkan pada rekap/laporan/ekspor Pra-Semester yang terpisah.
- **Alasan:** Pra-Semester berfungsi sebagai checkpoint akademik sebelum Ujian Semester yang sesungguhnya. Penjelasan tujuan ini tidak menambah modul Ujian Semester atau Nilai Akhir Semester.

## Belum dikerjakan setelah tahap dokumentasi/DB
- Template Excel ATP v2.5 resmi telah tersedia pada branch fitur setelah #68; langkah yang tersisa adalah generator unduh kontekstual dan parser/impor BAB–TP.
- Refactor MVC/Service, UI, ujian Pra-Semester, nilai, ekspor, progres, sertifikat dan regresi. Rincian tahap di `RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md`.
- Pengujian MySQL/MariaDB secara eksekusi dan verifikasi XAMPP oleh pemilik.

- **UI ATP (27-10-2026):** Peta ATP hanya ringkasan dua kelompok tiga kartu semester; buka halaman semester untuk mengelola daftar BAB dalam kartu warna berbeda dan tabel TP; tambah BAB/TP via modal native. Data tetap berdasar G1 Gen–Mapel, status paket, dan batas nama 50/deskripsi 225. Tidak mengubah alur akademik.


## Keputusan final Pra-Semester — 3 November 2026
- Jadwal dan durasi **per Gen–Mapel–Semester**, tidak global. Admin menetapkan jendela boleh mulai dan durasi pengerjaan. Durasi individual dihitung **sejak siswa memulai**, jendela berakhir tidak memotong durasi siswa yang sudah berjalan.
- Guru G1 mengelola bank minimal 30 soal mencakup setiap BAB dan KKM tersendiri Pra-Semester (default Admin/khusus Guru). Admin menjadwalkan ujian utama, remedial tersendiri dan susulan.
- Siswa yang ujian Pra-Semester-nya dibatalkan **harus mendapat jadwal susulan Admin**, bukan akses ulang mandiri. Siswa tidak hadir dapat ditetapkan oleh Admin untuk susulan. Susulan boleh diulang bila dibatalkan lagi dengan audit. Pembatalan remedial → susulan untuk remedial yang sama; satu remedial sah setelah hasil normal sah <KKM.
- **Pembaruan keputusan 3 November 2026:** Pra-Semester **bukan gerbang sertifikat dan tidak dicetak di sertifikat**. Isu yang sebelumnya ditunda sudah diputuskan pemilik; keputusan ini berlaku untuk pengembangan berikutnya.

## Status implementasi A13-2 (3 November 2026)

Setelah Pusat Bantuan A13-1, dump SQL lokal `sla_sks_v4 (17).sql` dibuat 03-11-2026 16.02 di MariaDB 10.4.32 telah diberikan dan diaudit (68 tabel; `schema_version` akhir `V4-0011-SERTIFIKAT`). Tidak ada tabel `pengumuman`, `faq`, atau `riwayat_informasi`. A13-2 menambahkan patch additive `V4-0012_PENGUMUMAN_FAQ.sql` dan CRUD non-destruktif Pengumuman global serta FAQ Admin. Semua login hanya membaca konten Terbit; Admin dapat mengelola keduanya; Guru hanya Pengumuman yang dia buat. Tidak ada data siswa/akun nyata dalam patch/repository. **Impor patch dan pengujian XAMPP oleh pemilik belum dilakukan**, sehingga status belum FIX.

Tahap berikutnya: Notifikasi internal untuk peristiwa sah menurut Master 12/14/16, badge belum dibaca dan kontrol hanya penerima sendiri. Sebelum SQL berikutnya, minta **dump XAMPP baru yang dibuat setelah V4-0012 berhasil diimpor**, bukan memakai dump versi sebelum patch.


## Status implementasi A13-3 (3 November 2026)

Tahap `Hubungi Admin` menggunakan lima pengaturan resmi yang sudah tersedia, tanpa SQL baru dan tanpa akses ke kontak pribadi Pengelola. Modul ini **tidak** membangun sistem tiket, pesan, email otomatis atau notifikasi. Seluruh peran aktif login dapat melihat kontak, dan tautan eksternal hanya dibentuk dari nilai yang valid. Implementasi branch bertumpuk A13-2 memerlukan review, CI serta uji XAMPP sebelum FIX. Gerbang notifikasi internal tetap sama: **jangan membuat patch skema berikutnya sebelum menerima dump baru XAMPP setelah V4-0012 berhasil diimpor**.


## Revisi final A13-4 — 4 November 2026

Pemilik mengubah kebijakan reset dan sandi awal: akun pemegang **Admin** saja yang mendapat reset acak dan wajib ganti; Guru/Pimpinan/Siswa direset ke **sandi default manual pada form Admin**, bukan acak, serta hanya dianjurkan ganti sandi pada Profil. Aplikasi lama tidak menyimpan plaintext sandi awal sehingga tidak mungkin memulihkan sandi yang sebelumnya diketik; solusi ini tidak menebak sandi universal. Semua sesi lama dicabut dan audit tidak menyimpan sandi. Kelima menu informasi dikelompokkan tanpa mengubah hak akses. Draf 28 FAQ ada sebagai dokumen salin, belum diterbitkan dan bukan SQL. Perubahan di branch A13-4 di atas A13-3; uji XAMPP diperlukan sebelum FIX. Notifikasi internal SQL masih menunggu dump terbaru pasca V4-0012.


## Keputusan final A13-5 — Pengumuman Guru per Gen (4 November 2026)
Pemilik menyatakan modul lain A13-4 FIX, tetapi cakupan Pengumuman Guru harus diperbaiki: **hanya akun Siswa pada Gen yang diampu Guru pembuat**, bukan Guru lain, Pimpinan, maupun siswa Gen lain. Pengumuman Admin tetap global. Tidak ada fitur pemilihan target manual; relasi penugasan `penugasan_guru_gen` dan siswa pada Gen menjadi filter live. Klasifikasi Admin/Guru mengikuti peran aktif saat membuat pengumuman dan disimpan di audit awal agar akun rangkap maupun sunting Admin tidak mengubah sasaran. Kelola, arsip, FAQ, laporan dan hak akademik tidak berubah. Branch perbaikan di atas A13-4, SQL V4-0012 historis **tidak diubah**, dan uji lokal dibutuhkan sebelum FIX.


## Status A13-6 dan A13-7 — 4 November 2026

Pemilik meminta kedua modul dikerjakan. Gerbang `AGENTS.md` untuk penulisan database **belum dipenuhi**: dump lokal pasca V4-0012 tidak tersedia sebagai berkas yang dapat dibaca dalam konteks saat ini; dump lama pra V4-0012 tidak boleh dianggap terbaru. Teks kebijakan privasi resmi yang disahkan sekolah juga belum disediakan, sehingga jangan membuat/menebak pernyataan hukum atau mengklaim halaman sudah berlaku.

Pada branch `feature/a13-6-a13-7-kontrak-pra-db-20261104`, tahap yang aman diselesaikan lebih dulu: `NotifikasiKontrakService` (katalog event, validasi role, key idempoten), `KebijakanPrivasiValidasiService` (versi, tanggal berlaku, teks, checksum dan konfirmasi), tes unit dan dokumen integrasi. **Tidak ada DDL, patch SQL, penerbitan, notifikasi operasional, menu palsu, migrasi, seeder, perubahan .env atau root.** Langkah berikutnya setelah dump dan naskah resmi: integrasi A13-6 lalu A13-7 per tahap/PR dengan QA MariaDB dan XAMPP. Kedua fitur masih **BELUM FIX**.

## Pembaruan setelah dump (18) diterima — 4 November 2026

Dump terbaru `sla_sks_v4 (18).sql` telah diaudit: 71 tabel pada MariaDB 10.4.32 dan marker V4-0012 sudah terpasang. Gerbang SQL pada catatan pra-audit **terpenuhi**. Branch `feature/a13-6-a13-7-implementasi-20261104`/PR #128 menambahkan patch V4-0013, aplikasi Notifikasi Internal dan pengelola Kebijakan Privasi. Masih menunggu impor SQL dan QA browser XAMPP; naskah Kebijakan Privasi yang disahkan madrasah belum disediakan sehingga halaman publik belum akan menampilkan teks. Jalur koreksi nilai yang sah belum dikaitkan ke notifikasi. Jangan menyatakan kedua modul FIX sebelum uji lokal.
