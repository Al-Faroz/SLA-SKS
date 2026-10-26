# SLA-SKS — Master Document v2.5 (Rancangan 26 Oktober 2026)

MTs Fase D · LMS CodeIgniter 4 · Admin, Guru, Siswa, Pimpinan · 6 semester.

> **Status:** Master v2.5 dan SQL v4 sedang disiapkan pada branch review. **Kode aplikasi di XAMPP belum diubah ke kontrak baru**. Tidak boleh menyatakan fitur siap sampai implementasi dan tes manual selesai.

## Alur produk terbaru
**Mapel (1 CP operasional) → Semester → BAB → TP**. ATP Guru dibuat melalui aplikasi atau impor template Excel manual (.xlsx) yang sudah disiapkan; tanpa macro, tombol pembentukan otomatis, atau pemasangan VBA. Generator/validator di aplikasi akan diselaraskan pada tahap implementasi. Kode TP `KODEMAPEL.KELAS.NO_BAB.NO_TP_DALAM_BAB` (misalnya `BIND.8.1.1`). Nomor BAB berlanjut semester 1–2, 3–4, 5–6. Nama BAB/TP maksimal 35 karakter, deskripsi maksimal 225.

TP **Esensial** tanpa Materi/nilai LMS, tetapi tetap masuk ATP dan daftar nilai sertifikat. TP **NonEsensial** tepat satu Materi. Diagnostik minimal 10 soal adalah gerbang: lulus boleh melewati Materi, Resume, HOTS; yang tidak lulus wajib Materi→Resume→HOTS. **Keduanya wajib Sumatif minimal 30 soal**. Nilai jalur gagal Diagnostik = 20% Resume +20% HOTS +60% Sumatif; jalur lulus = 100% Sumatif. KKM default Admin/khusus Guru; Sumatif < KKM mendapat satu remedial, skor tertinggi dipakai, TP tetap selesai walau di bawah KKM.

Progres semester hanya TP NonEsensial. **Pra-Semester** setelah seluruh TP NonEsensial selesai, mengikuti tanggal/jam serentak Admin, soal gabungan seluruh BAB satu semester, pola Sumatif. **Tidak ada nilai akhir semester.** Guru dapat ekspor semua nilai TP + Pra-Semester seluruh siswa Gen–Mapel sejak satu nilai pun tersedia.

## Dokumen dan perubahan
- [Indeks 16 Master Document](docs/README.md)
- [Catatan progres sebelumnya](docs/PROGRES-SEBELUM-POLA-BARU-2026-10-26.md)
- [Rencana tahap baru](docs/RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md)
- [Audit dump v3 dan rencana v4](docs/AUDIT-DATABASE-V3-KE-V4-2026-10-26.md)
- [Panduan pengembang](AGENTS.md)

**SQL:** `sla_sks_v4` dibuat BARU dari dump lokal terakhir; `sla_sks_v3` tidak dihapus. Dump privat berisi identitas dan hash sandi hanya dibagikan kepada pemilik, **tidak masuk GitHub**. CI4 migration/seeder dilarang. `index.php` dan `.htaccess` tetap di ROOT dan `.env` tidak diubah tanpa izin.
