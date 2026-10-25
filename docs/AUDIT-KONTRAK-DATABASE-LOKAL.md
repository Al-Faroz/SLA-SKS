# Audit Kontrak Database XAMPP — Gerbang Sebelum SQL

**Lampiran teknis Master 05, 10, 15, 16. Bukan Master Document ke-17.**

## Status terverifikasi dari dump terbaru (25 Oktober 2026)
- Dump terbaru **sudah diterima dan diperiksa langsung**: `sla_sks_v3 (3).sql`, 203.295 byte, database `sla_sks_v3`, phpMyAdmin 5.2.1, server MariaDB **10.4.32**, PHP 8.2.12. Dump privat **tidak diunggah ke GitHub**.
- **Perbedaan waktu pada dua sumber, bukan dua berkas berbeda:** header SQL mencantumkan `Waktu pembuatan: 25 Okt 2026 pada 11.01`; tangkapan layar Windows Properties memperlihatkan `Created: 25 Oktober 2026, 17.01.11` dan `Modified: 17.01.12`. Selisih enam jam **tidak cukup membuktikan pengaturan zona waktu mana yang salah**. Jangan menyebut waktu header sebagai waktu ekspor lokal tanpa kualifikasi.
- Dump terbaru memuat **52 tabel** (sebelumnya 51 tabel di `sla_sks_v3 (2).sql`). Tabel baru yang terlihat pada perbandingan dua skema ialah **`jawaban_hots_materi`**. Ini membuktikan patch pemulihan jawaban siswa telah menghasilkan tabel yang diperlukan dalam dump terbaru; bukan berarti alur penilaian siswa sudah teruji.
- `tugas_hots_materi` adalah **bank perintah HOTS Guru**: 5 kolom, `ENGINE=InnoDB`, tidak ada `INSERT` untuk tabel itu dalam dump. `jawaban_hots_materi` adalah **jawaban HOTS siswa**: 14 kolom, `ENGINE=InnoDB`, memiliki `CHECK (nilai <= 100)`, tidak ada `INSERT` untuk tabel itu. Tabel `resume_materi` memiliki **14 kolom**, juga tanpa `INSERT` pada dump. Dengan demikian antrean Guru kosong konsisten dengan data yang diekspor.
- `schema_version` masih mencatat **10 entri sampai `K5-0010`**. Ini berarti pencatatan versi tidak mengikuti seluruh perubahan fisik, **bukan** bukti bahwa tabel tambahan belum ada. Audit versi harus memakai DDL dan metadata juga.
- `CREATE TABLE resume_materi` dalam dump terbaru berakhir dengan `);` tanpa klausa `ENGINE` eksplisit; belum boleh menebak engine sebenarnya dari file saja. Periksa `information_schema.TABLES` di XAMPP bila perubahan skema membutuhkan kepastian.
- **Kondisi deployment:** aplikasi masih XAMPP lokal; hosting belum pernah dipakai/deploy. Tidak ada pengujian hosting yang diklaim.

## Kontrak objek HOTS (bukan kebijakan bisnis baru)
1. `materi.petunjuk_hots` = instruksi dari editor Guru; saat ini dibaca siswa melalui snapshot TP terpin.
2. `tugas_hots_materi` = bank perintah/pertanyaan HOTS **Guru** pada tiap Materi; **bukan** pengumpulan siswa.
3. `jawaban_hots_materi` = jawaban HOTS **siswa**, status draf/final, skor dan penilai asli.
4. `resume_materi` = jawaban Resume siswa; entitas lain lagi.

**Konflik implementasi belum selesai:** Master 05 mewajibkan satu tugas HOTS per Materi, tetapi `KesiapanTpService` masih memeriksa `materi.petunjuk_hots` tanpa mengecek baris di bank `tugas_hots_materi`. Dump lama menunjukkan bank Guru kosong meskipun TP sudah Siap. Tidak boleh membuat tugas otomatis atau mengubah TP Siap/progres historis untuk menyelaraskan tanpa dump terbaru dan pemeriksaan keputusan akademik.

**Potensi bug validasi:** Service siswa masih perlu diperiksa agar batas minimal karakter mengikuti konfigurasi `tp.resume_min` dan `tp.tugas_min` pada snapshot terpin, bukan hardcode 50/200.

## Gerbang wajib sebelum pekerjaan database
1. **Terima dump XAMPP terbaru untuk tugas terkait** (untuk audit ini sudah terpenuhi oleh `sla_sks_v3 (3).sql`), diekspor setelah patch terakhir. Catat nama DB, header waktu, versi MariaDB, DDL semua tabel, indeks, FK/CHECK, status `schema_version` dan data historis penting.
2. Cocokkan tabel dan kolom dengan seluruh query Service, Master dan SQL patch terdahulu, termasuk **bank HOTS Guru sekaligus jawaban siswa**, serta kemungkinan impor parsial dan nama bentrok.
3. Pisahkan fakta skema dari ketentuan Master; tidak mengubah bobot, syarat HOTS, TP Siap atau hasil siswa tanpa persetujuan.
4. Patch additive bernomor **baru**, bukan mengedit/mengimpor ulang patch yang sudah digunakan. Buat QA **baca-saja sebelum/sesudah**, backup DB+`writable/`, rencana pemulihan dan pengujian kompatibilitas MariaDB lokal nyata.
5. **Tanpa DROP/TRUNCATE/reset** kecuali ada permintaan khusus dan izin eksplisit dari pemilik untuk DB lokal yang telah dibackup. Tidak ada migrasi/seeder.
6. Dump privat hanya untuk analisis; **tidak masuk GitHub, fixture, CI, atau log**. Gunakan skema tes tersanitasi dan kasus negatif (403/500/503, duplikasi, nilai tidak tertimpa, lintas Gen).

## Gerbang penerimaan yang belum terpenuhi
E2E-016 (tugas Materi saat TP Siap), E2E-020–024 dan 095 (jalur belajar dan draf/final), E2E-039 dan 073–074 (Guru memberi skor dengan jawaban pengujian), E2E-066–069 (SQL dan backup/restore). Antrean kosong saja **tidak membuktikan** alur penilaian telah selesai.

**Status:** dump `sla_sks_v3 (3).sql` telah diterima dan diperiksa pada tingkat struktur; **tidak diperlukan unggah ulang untuk audit ini**. Audit kontrak Master/kode berikutnya boleh mengacu pada struktur ini; perubahan SQL apa pun tetap membutuhkan pemetaan perubahan dan uji menyeluruh sebelum diimpor. PR ini tidak mengubah database.
