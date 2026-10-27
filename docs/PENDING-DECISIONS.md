# Status keputusan Master v2.5 — 26 Oktober 2026

## Keputusan final
- CP → Semester → BAB → TP; satu CP operasional/mapel, beberapa elemen; BAB/TP 50+225, kode `MAPEL.KELAS.BAB.TP`, nomor BAB sambung 1–2/3–4/5–6.
- TP Esensial tanpa media/ujian/nilai, tetap masuk ATP/sertifikat. TP NonEsensial tepat satu Materi.
- Diagnostik ≥10 gate; Sumatif ≥30 wajib, bobot jalur gagal 20/20/60 atau lulus 100% Sumatif, KKM default Admin/khusus Guru, 1 remedial <KKM ambil nilai tertinggi, TP Selesai meski nilai di bawah KKM.
- Guru isi ATP langsung atau Excel ATP manual `.xlsx` tanpa macro dan tanpa tombol Proses; file adaptasi lokal sudah dibuat, integrasi generator/impor aplikasi masih belum dikerjakan. Progres persemester hanya NonEsensial.
- Pra-Semester pada tanggal dan jam serentak Admin setelah semua NonEsensial selesai, bank gabungan BAB, pola Sumatif, nilai berdiri sendiri. Tidak ada nilai akhir semester. Export Nilai Guru dapat dilakukan dari satu skor TP.
- Database baru `sla_sks_v4` terpisah dari `sla_sks_v3`, sumber privat tak dikirim ke repo; semua versi/progres lama dipertahankan sebagai historis.

## Keputusan yang sengaja ditunda
- Apakah siswa wajib menuntaskan Pra-Semester **sebelum sertifikat diterbitkan**, dan apakah nilainya perlu dicetak di daftar nilai sertifikat. Belum menjadi gate atau kolom default; menunggu pembahasan pemilik.

## Belum dikerjakan setelah tahap dokumentasi/DB
- Menambahkan template Excel manual pengganti yang sudah dibuat ke repository dan menyesuaikan generator/parser impor sesuai struktur BAB–TP.
- Refactor MVC/Service, UI, ujian Pra-Semester, nilai, ekspor, progres, sertifikat dan regresi. Rincian tahap di `RENCANA-IMPLEMENTASI-POLA-BARU-2026-10-26.md`.
- Pengujian MySQL/MariaDB secara eksekusi dan verifikasi XAMPP oleh pemilik.
