# Checkpoint A9 — Rekap Nilai Terpisah & Penilaian Guru

Tanggal: 3 November 2026

## Keputusan pemilik

- Menu sidebar Guru: **Rekap Nilai** dengan submenu **Nilai TP** dan **Nilai Pra-Semester**.
- Tabel dan ekspor kedua kategori nilai terpisah, tidak ada sheet gabungan.
- Antrean Penilaian filter Gen–Mapel (sesuai penugasan G1), Semester, BAB, Resume/HOTS. Hapus persentase indikator kemiripan dari kolom Jenis, tetapi detail tetap menampilkan indikator untuk pemeriksaan Guru.
- Detail nilai Resume/HOTS: petunjuk dari snapshot Materi versi siswa dan jawaban final pada **dua kartu** dengan latar berbeda dalam tema Emerald Glass.
- Rekap TP filter Gen–Mapel, Semester, Jenis TP, BAB; **default NonEsensial**. Esensial dan Semua bisa dipilih. Unduhan Excel menyesuaikan filter.
- Rekap Pra-Semester: Gen–Mapel, Semester, seluruh roster siswa Gen; normal/remedial sah, nilai terbaik, status Sementara/Final. Ujian batal/null tidak disamakan dengan 0, tidak ada Nilai Akhir Semester.

## Implementasi kode

- Controller: `GuruPenilaian`, `GuruRekapNilai`, `GuruEksporNilai`, `GuruRekapPraSemester` (baru), `GuruEksporPraSemester` (baru).
- Service: `PenilaianGuruService`, `RekapNilaiTpGuruService`, `EksporNilaiGuruService`, `RekapNilaiPraSemesterGuruService` (baru), `EksporNilaiPraSemesterGuruService` (baru), `NavigasiService`.
- View & shell: `antrean_penilaian`, `nilai_kegiatan`, `rekap_nilai_tp`, `rekap_nilai_pra_semester` (baru), `layouts/panel`, `app.css`, `emerald-glass.css`.
- Rute: `/guru/rekap-nilai/pra-semester` dan `/guru/rekap-nilai/pra-semester/unduh` (GET + auth Guru).
- Regression: `tests/unit/A9FilterGuruTest.php` (baru), `tests/unit/PraSemesterDatabaseTest.php` (ditambah), `tests/unit/RekapNilaiTpGuruDatabaseTest.php` (menyesuaikan menu).
- Workflow: `qa-a9-rekap-guru.yml`. Dokumen 08, 12, 16 ikut diselaraskan.

## Database dan status QA

- Tidak membuat migration, seeder, atau patch SQL. Tidak menyentuh dump/database, jadwal, histori, atau `.env` lokal.
- QA A9 di GitHub Actions: pada commit sebelum checkpoint, PHP lint PASS, 4 tes/18 assertions filter + 22 tes/188 assertions Pra-Semester PASS.
- Regresi A7, G10/G11, dan pemeriksaan tambahan tetap harus dinilai dari hasil workflow setelah pembaruan akhir. **Pengujian browser/XAMPP belum PASS**.
- **Sebelum memverifikasi integrasi dengan database lokal**, pemilik mengirim dump SQL XAMPP terbaru pascapatch terakhir untuk audit sesuai `AGENTS.md`. Tidak boleh mengubah struktur/data siswa tanpa persetujuan.

## Langkah pengujian manual ketika branch dipull

1. Guru → Penilaian: uji 4 filter, Reset, pagination, hilangnya label persentase dalam tabel dan dua kartu berbeda pada Resume/HOTS.
2. Guru → Rekap Nilai → Nilai TP: default NonEsensial, uji Esensial/Semua, BAB, unduh Excel sesuai filter dan semua semester.
3. Guru → Rekap Nilai → Nilai Pra-Semester: tabel seluruh siswa, status nilai, unduh XLSX terpisah, tanpa data TP.
4. Uji Gen–Mapel di luar penugasan, nilai kosong dan skor 0.00. Laporan status browser setelah uji manual, tanpa rekayasa histori.
