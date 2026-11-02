# Referensi Database Privat SLA-SKS v4 — 2 November 2026

## Acuan sumber terbaru untuk pengembangan berikutnya

- **Nama unggahan privat:** `sla_sks_v4 (12).sql`, diterima dari pemilik proyek pada percakapan pengembangan ini.
- **Ekspor:** 2 November 2026 pukul **13.29 WIB**; MariaDB **10.4.32**, PHP 8.2.12, phpMyAdmin 5.2.1.
- **SHA-256 berkas asli:** `00da1f549e048b049b2722c294bd48827e7d230412d777bb3314c51494caaef4`.
- **Versi tertinggi dalam `schema_version`:** `V4-0007-CP-LIMA-ISIAN`; **V4-0008 A5 belum tercatat dalam dump ini**.
- **Struktur yang terkonfirmasi:** tabel `ujian_pra_semester`, `soal_pra_semester`, `percobaan_ujian` dengan jenis `pra_semester`, `butir_percobaan`, `progres_tp`, `nilai_tp`, `siswa`, `materi`, `soal`, `versi_konten_tp`, `paket_semester` tersedia. Tabel `keputusan_ujian_batal` dan riwayatnya baru masuk lewat V4-0008 dan tidak terdapat pada dump ini.
- **Objek uji yang nyata pada dump:** Gen ID 1 / SKI Mapel ID 4, `paket_semester.id=1` semester 4, BAB ID 8, TP ID 23 status Siap, `versi_konten_tp.id=3`, Materi ID 25, bank 10 Diagnostik dan 30 Sumatif, KKM khusus 82.
- **Kondisi sumber sebelum simulasi:** 17 siswa dalam Gen tersebut, 37 pengguna, 36 TP (hanya satu TP yang telah Siap dengan konten soal dan media lengkap), dan satu `progres_tp` historis; riwayat ini tidak boleh dihapus.

**KRITIS — PRIVASI:** File SQL ini mengandung identitas dan kredensial terenkripsi pengguna asli. **Dilarang menyalin/commit dump penuh, akun simulasi beserta kata sandi, hasil simulasi dengan data asli, atau metadata personal ke GitHub.** Repository hanya menyimpan referensi nama, waktu, hash, struktur teknis, dan ID uji yang diperlukan.

## Dataset simulasi QA

Untuk menguji implementasi A1–A7 dibuat **salinan dump privat dengan 10 akun siswa simulasi** dan **SQL penambahan data (insert-only)** yang dapat diunduh dari percakapan. Akun dummy: `sla_uji_01` s.d. `sla_uji_10`, ID pengguna/siswa/progres 910001–910010, ditautkan ke TP 23/Snapshot 3 tanpa mengubah siswa/akun historis.

Skenario meliputi Diagnostik lulus/gagal, Materi–Resume–HOTS, penilaian belum masuk, Sumatif normal/remedial, tuntas di bawah KKM, pembatalan menunggu keputusan Guru, keputusan langsung belajar, nilai 0,00 valid vs NULL, serta rekap dan ekspor nilai Guru. Semua angka skor diturunkan dari jawaban PG A–E hasil simulasi dan jumlah butir asli, bukan nilai acak.

Salinan dump lengkap QA menambahkan skema A5 V4-0008 pada tahap setelah data sumber. SQL **patch dummy** untuk database aktif mensyaratkan V4-0008 diterapkan terlebih dahulu; tidak memiliki perintah UPDATE/DELETE/DROP/TRUNCATE. Jangan impor dump lengkap ke DB berisi tabel; gunakan database uji **kosong**. **Tidak ada impor XAMPP dilakukan AI**, dan belum ada uji runtime MariaDB terhadap output.

## Keputusan untuk tahap A8-1

Audit sumber kini memungkinkan desain patch dari **DDL aktual terbaru yang diberikan pengguna**. Selanjutnya cek secara eksplisit bahwa V4-0008 telah diimpor pada database target sebelum integrasi keputusan ujian batal dan Pra-Semester. Jadwal Admin, bank soal Guru per BAB, penilaian mandiri dan kontrak attempt harus dikembangkan dengan mengacu schema aktual ini serta Master 03/06/07/08/10/12/14/16. Jangan mengubah konfigurasi root/.env atau menetapkan Pra-Semester sebagai syarat sertifikat tanpa keputusan pengguna.

Berkas SQL privat tetap milik pengguna; jika memerlukan byte berkas untuk analisis putaran lain, ambil dari lampiran percakapan/Library yang benar, **jangan mengarang path atau menyimpan salinan privat ke repo**.
