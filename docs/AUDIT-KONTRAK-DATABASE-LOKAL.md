# Audit Kontrak Database XAMPP — Gerbang Sebelum SQL

**Lampiran teknis Master 05, 10, 15, 16. Bukan Master Document ke-17.**

## Status terverifikasi per 25 Oktober 2026
- Aplikasi **hanya digunakan di XAMPP lokal**, belum pernah diunggah ke hosting. Shared hosting adalah target di masa datang, bukan lingkungan yang sudah diuji.
- Lampiran terakhir yang benar-benar terdeteksi adalah `sla_sks_v3 (2).sql` dengan header **25 Oktober 2026 pukul 10.24**, **MariaDB 10.4.32**. Dump ini **belum bisa mewakili kondisi terbaru** setelah SQL 0018 dan halaman Guru berhasil terbuka. Perlu ekspor ulang sesudah patch terakhir.
- Dump tersebut mencatat **51 tabel**; `tugas_hots_materi` merupakan bank Guru dengan 5 kolom (`id`, `materi_id`, `row_uuid`, `pertanyaan`, `petunjuk`) dan tidak ada baris jawaban siswa di tabel itu. `resume_materi` memiliki **14 kolom**; `jawaban_hots_materi` belum ada dalam dump **lama**. `schema_version` terakhir yang tercatat `K5-0010`, tetapi bisa tidak mencerminkan seluruh patch yang telah diterapkan.
- `CREATE TABLE resume_materi` pada dump lama berakhir dengan `);` tanpa ENGINE eksplisit. Ini **perlu verifikasi**, bukan bukti kerusakan tabel; lihat `information_schema`/SHOW CREATE TABLE pada dump aktual.

## Kontrak objek HOTS (bukan kebijakan bisnis baru)
1. `materi.petunjuk_hots` = instruksi dari editor Guru; saat ini dibaca siswa melalui snapshot TP terpin.
2. `tugas_hots_materi` = bank perintah/pertanyaan HOTS **Guru** pada tiap Materi; **bukan** pengumpulan siswa.
3. `jawaban_hots_materi` = jawaban HOTS **siswa**, status draf/final, skor dan penilai asli.
4. `resume_materi` = jawaban Resume siswa; entitas lain lagi.

**Konflik implementasi belum selesai:** Master 05 mewajibkan satu tugas HOTS per Materi, tetapi `KesiapanTpService` masih memeriksa `materi.petunjuk_hots` tanpa mengecek baris di bank `tugas_hots_materi`. Dump lama menunjukkan bank Guru kosong meskipun TP sudah Siap. Tidak boleh membuat tugas otomatis atau mengubah TP Siap/progres historis untuk menyelaraskan tanpa dump terbaru dan pemeriksaan keputusan akademik.

**Potensi bug validasi:** Service siswa masih perlu diperiksa agar batas minimal karakter mengikuti konfigurasi `tp.resume_min` dan `tp.tugas_min` pada snapshot terpin, bukan hardcode 50/200.

## Gerbang wajib sebelum pekerjaan database
1. **Terima dump XAMPP terbaru yang benar-benar terlampir**, di-export setelah patch terakhir. Catat nama DB, header waktu, versi MariaDB, DDL semua tabel, indeks, FK/CHECK, status `schema_version` dan data historis penting.
2. Cocokkan tabel dan kolom dengan seluruh query Service, Master dan SQL patch terdahulu, termasuk **bank HOTS Guru sekaligus jawaban siswa**, serta kemungkinan impor parsial dan nama bentrok.
3. Pisahkan fakta skema dari ketentuan Master; tidak mengubah bobot, syarat HOTS, TP Siap atau hasil siswa tanpa persetujuan.
4. Patch additive bernomor **baru**, bukan mengedit/mengimpor ulang patch yang sudah digunakan. Buat QA **baca-saja sebelum/sesudah**, backup DB+`writable/`, rencana pemulihan dan pengujian kompatibilitas MariaDB lokal nyata.
5. **Tanpa DROP/TRUNCATE/reset** kecuali ada permintaan khusus dan izin eksplisit dari pemilik untuk DB lokal yang telah dibackup. Tidak ada migrasi/seeder.
6. Dump privat hanya untuk analisis; **tidak masuk GitHub, fixture, CI, atau log**. Gunakan skema tes tersanitasi dan kasus negatif (403/500/503, duplikasi, nilai tidak tertimpa, lintas Gen).

## Gerbang penerimaan yang belum terpenuhi
E2E-016 (tugas Materi saat TP Siap), E2E-020–024 dan 095 (jalur belajar dan draf/final), E2E-039 dan 073–074 (Guru memberi skor dengan jawaban pengujian), E2E-066–069 (SQL dan backup/restore). Antrean kosong saja **tidak membuktikan** alur penilaian telah selesai.

**Status:** dokumentasi dan QA metadata baca-saja bisa selesai; pembuatan patch struktural / penyesuaian Service ditunda sampai dump terbaru diterima.
