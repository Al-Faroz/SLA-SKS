# Checkpoint A6 — Rekap Nilai TP Guru (baca-saja)

**Tanggal:** 2 November 2026  
**Branch:** \`feature/v25-a6-rekap-nilai-tp-guru-20261102\`, basis A5 / PR #95.  
**Status:** disiapkan untuk pemeriksaan otomatis dan uji browser XAMPP, **belum FIX lokal**.

## Acuan Master v2.5
- 03: Guru G1 hanya membaca siswa pada Gen–Mapel yang sedang diampu.
- 08: TP Esensial tanpa nilai; nilai NonEsensial dapat sementara/final; NULL bukan nol; 100% Sumatif jika Diagnostik lulus, selain itu 20/20/60. Tidak ada nilai akhir semester.
- 12: filter Gen–Mapel dan semester, tabel mudah digunakan pada desktop/HP, UI kosong/error.
- 16: uji akses lintas Gen, urutan, nilai 0, nilai belum tersedia, pagination, dan tanpa perubahan data.

## Implementasi
1. **Guru → Rekap Nilai TP** (\`GET /guru/rekap-nilai\`) menggunakan \`auth:guru\` dan pengecekan status/peran aktif dalam Controller serta Service.
2. Filter pilihan hanya dari penugasan G1 sah; Service memvalidasi ulang pasangan Gen–Mapel di server. Guru yang menebak ID/parameter tidak mendapat nilai siswa dari Gen lain.
3. Data baris per **siswa × TP** di semester yang dipilih. Termasuk TP Draf/Siap dan TP Esensial (tanpa nilai); hanya TP arsip tidak ditampilkan. Semua siswa Gen terlihat walau belum punya progres/nilai, selama TP semester tersedia.
4. Angka diambil dari **nilai_tp tersimpan** (tidak menulis, membuat, atau menyinkronkan nilai). Skor \`0,00\` tidak sama dengan \`Belum ada\`, jalur Diagnostik lulus menandai Resume/HOTS sebagai \`Dilewati\`, nilai Sementara berbeda dari Final.
5. Ringkasan jumlah siswa, pasangan siswa–TP Final/Sementara/Belum ada, tabel lengkap komponen Resume/HOTS/Sumatif terbaik/Nilai TP, scroll horizontal dan pagination 25 baris. **Tidak ada rata-rata/nilai akhir semester**.
6. Ekspor Excel, Ujian Pra-Semester dan koreksi nilai siswa **belum termasuk A6**; jangan menampilkan tombol palsu. Nilai Pra-Semester dapat diintegrasikan dalam ekspor mendatang sesudah mesin ujian tersedia.

## Database
- **Tanpa perubahan SQL, migration, seeder, atau operasi tulis**. Service membaca tabel \`penugasan_guru_gen\`, \`ref_angkatan\`, \`ref_mapel\`, \`siswa\`, \`pengguna\`, \`paket_semester\`, \`bab\`, \`tp\`, \`progres_tp\`, \`nilai_tp\` yang sudah menjadi kontrak v4.
- Audit dump SQL pengguna terakhir yang tersedia sebelum A5: \`sla_sks_v4 (11).sql\` 02-Nov-2026 10:24 (sampai V4-0007). **Status impor V4-0008 di XAMPP belum dilaporkan**; A6 tidak bergantung pada tabel A5. Jangan mengasumsikan dump lama sebagai keadaan DB lokal saat ini.

## QA
- Fixture SQLite anonim \`tests/unit/RekapNilaiTpGuruDatabaseTest.php\`: G1, isolasi antar Gen, TP Esensial, nilai nol, sementara, NULL, semester kosong dan dua halaman.
- GitHub Actions memeriksa lint, PHPUnit/regresi. Hasil yang benar-benar selesai dilaporkan saat commit diperiksa.
- Uji browser XAMPP yang perlu pemilik: Guru Gen A, Guru Gen B, daftar siswa tanpa nilai, satu skor 0,00, skor Sementara/Final setelah Sumatif, semester berbeda, pagination, URL dan filter tidak sah, Dark/Light pada desktop/HP.
- Belum menggabungkan PR ke main; tidak mengubah konfigurasi lokal.

## Tindak lanjut
Setelah rekap sudah diverifikasi, implementasikan ekspor nilai XLSX Guru yang mendukung nilai parsial dan Pra-Semester saat data sah tersedia; tidak membuat agregat akhir semester.
