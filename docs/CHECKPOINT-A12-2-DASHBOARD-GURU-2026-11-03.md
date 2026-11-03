# A12-2 — Dashboard Guru per penugasan Gen–Mapel

Tanggal: **3 November 2026**. Branch: `feature/a12-2-dashboard-guru-20261103`.
Draft PR: **#116**, berbasis `feature/a12-1-dashboard-pimpinan-20261103` (stacked). Tidak merge main.

## Keputusan dan hasil konkret

- Beranda `GET /guru` menampilkan **Dashboard Guru**, bukan informasi akun aktif saja. Menunjukkan penugasan Guru aktif berdasarkan `penugasan_guru_gen`, dengan filter Gen–Mapel (nomor pilihan berasal dari daftar G1 server) dan Semester (semua/1–6), serta link Peta ATP/antrean penilaian/permintaan akses ulang/Pra-Semester yang berfungsi.
- Sumber dan definisi KPI: jumlah paket aktif; TP Siap/Draf/nonarsip; TP berstatus bukan arsip dengan **jumlah Materi aktif bukan tepat satu** (termasuk Esensial), bukan pengganti checklist kesiapan media; antrean Resume/HOTS **final** dengan skor NULL dan `dinilai_oleh` NULL serta snapshot versi cocok; siswa unik per paket dengan progres `perlu_bantuan`; percobaan Diagnostik/Sumatif berstatus `berjalan` (tidak memasukkan Pra-Semester).
- Semua kueri bekerja **hanya terhadap paket aktif milik Guru G1 yang dipilih**. Otorisasi diperiksa dua kali: filter rute dan `DashboardGuruService`, termasuk cek G1 terbaru sebelum query. Filter URL `pilihan` tidak dianggap identitas Gen/Mapel tanpa validasi daftar izin.
- Tidak memicu penilaian otomatis, tidak mengubah nilai atau progres, tidak mencampurkan nilai TP dan Pra-Semester, tidak membuat Nilai Akhir Semester. Siswa tanpa akses/penugasan tidak dapat membaca Gen lain.
- UI kustom Emerald Glass berbentuk kartu KPI, bar tindakan, tabel per semester dan adaptasi HP. Tabel lebar boleh digulir; state tak punya penugasan/paket jelas dan filter invalid menampilkan galat.

## Berkas dan QA

- `app/Services/DashboardGuruService.php` — agregasi autentikasi G1 dan data kegiatan Guru.
- `app/Controllers/GuruDashboard.php` — filter, error, response private no-store.
- `app/Views/guru/dashboard.php` — filter/kartu/tabel/link yang benar-benar aktif.
- `app/Config/Routes.php`: `/guru` diarahkan ke controller baru dengan auth:guru.
- `public/assets/css/app.css` — grid responsif, tata angka, tabel scroll.
- `tests/unit/DashboardGuruDatabaseTest.php` — fixture SQLite anonim 2 Gen + 2 Guru, TP Esensial/NonEsensial, Materi 0/1/2, antarsemester, nilai skor 0, jawaban draf/final/snapshot, bantuan, ujian berjalan, akses lintas penugasan.
- `.github/workflows/qa-a12-2-dashboard-guru.yml`: PHP lint + PHPUnit dashboard, regresi Penilaian Guru serta Izin Akses Ulang. Hasil awal run **41641507999 PASS**: dashboard **6 tes/25 assertions**, penilaian **11 tes/52 assertions**, akses ulang **8 tes/43 assertions**; pemeriksaan terbaru dilakukan setiap commit.
- Master 03, 12, 13, 16 dan indeks checkpoint diselaraskan. Master 08 tetap berlaku: nilai Final 0 valid, Resume/HOTS tidak dinilai otomatis.

## Database dan keamanan

Sumber skema dari dump privat terakhir yang diterima di percakapan proyek `sla_sks_v4 (16).sql`, marker `V4-0011-SERTIFIKAT`. Karena pemasangan data DEMO dilakukan sesudah dump tersebut, nilai agregat aktual harus dibaca secara langsung oleh XAMPP; angka historis pada dump **tidak dianggap data terbaru**. Tidak ada patch SQL, migration, seeder, penulisan data atau data siswa yang dipush ke GitHub. Tidak ada perubahan `.env`, `index.php` atau `.htaccess`.

## Uji localhost sebelum FIX

1. `git fetch`, switch/pull ke branch A12-2 dan login **Guru**. Buka `http://localhost/sla-sks/index.php/guru`.
2. Cek pilihan Gen–Mapel hanya yang memang diampu, ubah Semester 1–6, reset filter. Untuk guru yang punya lebih dari satu penugasan, pastikan tidak ada kebocoran Gen lainnya.
3. Cocokkan jumlah TP Siap/Draf di Peta ATP, periksa indikasi Materi aktif bukan tepat satu dan bandingkan dengan kondisi media/kesiapan manual.
4. Cocokkan antrean Resume/HOTS dengan `/guru/penilaian`, ingat skor 0 tidak dihitung belum dinilai. Uji akses ulang siswa melalui halaman yang sudah ada; `perlu_bantuan` bukan selalu berarti permintaan akses ulang yang baru diajukan.
5. Klik semua tautan. Uji Dark/Light, Mode Dokumentasi, ukuran HP dan tabel scroll horizontal.
6. Tidak perlu impor SQL atau mengubah nilai dummy/hasil sertifikat yang telah terbit.

**Status:** implementasi + unit/regresi PASS awal; menunggu hasil browser dan angka XAMPP pemilik. Setelah dinyatakan FIX, lanjut A12-3 Dashboard Siswa/Admin secara bertahap, kemudian notifikasi/pengumuman/FAQ dan E2E akhir.
