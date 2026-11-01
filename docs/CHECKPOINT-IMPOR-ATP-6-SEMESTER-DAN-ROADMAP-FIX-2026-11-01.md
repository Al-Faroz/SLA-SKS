# CHECKPOINT — Audit hasil impor ATP 6 semester dan roadmap FIX SLA-SKS
**Tanggal:** 1 November 2026. **Status:** Audit dump PASS (struktur akademik), aplikasi end-to-end **BELUM FIX**.  
**Branch acuan:** `feature/v25-atp-enam-semester-audit-belajar-20261031`.  
**Acuan:** `README.md`, `AGENTS.md`, `docs/README.md`, Master Document v2.5 terutama 02–16, kontrak ATP V25-3 dan checkpoint sebelumnya.

## 1. Sumber bukti dan batas privasi
Audit berdasarkan dump privat MySQL/MariaDB lokal `sla_sks_v4 (6).sql`, diekspor phpMyAdmin 5.2.1 pada **01-11-2026 09.56 (waktu ekspor pada header)**, server MariaDB 10.4.32, PHP 8.2.12. Dump diberikan pemilik setelah berhasil mengimpor ATP pada sekitar 15.56 WIB di lingkungan lokal. **Dump asli, identitas siswa/guru, NISN, sandi hash, session, token, dan contoh record privat tidak disimpan ke GitHub.** Pemeriksaan dilakukan sebagai pembacaan SQL tekstual; **tidak ada perintah UPDATE/DELETE/INSERT dieksekusi pada database pengguna.**

## 2. Hasil audit isi database setelah impor
| Entitas / aspek | Hasil |
| --- | --- |
| Skema dan patch | 58 tabel; 15 baris `schema_version`, terbaru `V4-0005-NAMA-ATP-50` |
| Nama BAB/TP | `bab.nama_bab VARCHAR(50)`, `tp.nama_tp VARCHAR(50)`: **sesuai v2.5** |
| Gen–Mapel | Satu penugasan ATP yang diperiksa: Gen mulai Semester 4, mapel SKI Fase D, kurikulum KBC; satu CP operasional SKI aktif (`KBC25-SKI-D-PK`), relasi `cp_mapel_id` valid |
| Paket semester | **6 paket** Gen–SKI, tepat satu per Semester 1–6, semua memakai tahun ajaran ID 1 sebagaimana dipilih pengguna; Semester 4 paket ID 1 **tetap aktif**, lima lainnya draf |
| BAB | **12 BAB** = 2 per semester; nomor berkelanjutan 1–4 **per kelas**, yaitu Semester 1–2, 3–4, 5–6 |
| TP | **36 TP** = 6/semester; **24 Esensial**, **12 NonEsensial**; seluruhnya draf |
| Kode dan nomor TP | **36/36 cocok** pola SKI.KELAS.BAB.URUTAN; tiap BAB TP berurutan 1–3 tanpa jeda; tidak ditemukan duplikasi kode pada konteks ini |
| CP setiap TP | **36/36** memiliki `cp_mapel_id=1` (SKI); satu CP aktif versi SKI; tidak perlu menambah relasi `tp_cp` warisan |
| Materi | **36/36 TP** masing-masing tepat satu Materi rencana dari Excel, berurutan 1, `aktif=0`. **35 PDF + 1 YouTube** |
| Konten Materi | Seluruh media belum berkas/URL aktual, `min_waktu_detik=0`, Resume/HOTS belum diisi. **Sesuai makna rencana draf; tidak siap publikasi** |
| Parameter default TP | 36/36: bobot 20/20/60, Diagnostik tayang 10, Sumatif tayang 30, kuota Sumatif 2 |
| Bank soal dan publikasi | `soal=0`, `versi_konten_tp=0`; tidak ada TP Siap |
| Rekam belajar | `progres_tp=0`, `progres_materi=0`, `percobaan_ujian=0`, `nilai_tp=0` |
| Penugasan siswa | `penugasan_paket` hanya 1 baris, untuk paket Semester 4 lama. Paket draf lain **tidak boleh dianggap tersedia bagi siswa** |
| Audit transaksi impor | Satu catatan `impor_atp_excel_konfirmasi` melaporkan **12 BAB baru, 36 TP baru, 0 pembaruan**; cocok dengan data tersimpan |
| Validasi struktural terhadap kumpulan impor | Tidak ditemukan TP tanpa BAB, tanpa satu Materi, kode tidak cocok, nama melebihi batas, atau urutan BAB/TP berlubang |

**Catatan:** Enam paket dipilih menggunakan tahun ajaran yang **sama** (ID 1). Ini fakta dump, **bukan bug yang boleh diubah otomatis**. Penentuan tahun ajaran per semester memang hak pemilik/Guru sesuai UI. Jika konteks penerapan nyata berbeda, koreksi harus melalui prosedur yang disetujui, tidak menghapus/mengganti seluruh impor.

**Catatan keamanan:** Dump memuat banyak informasi pribadi/sesi/password hash; dokumen ini hanya memuat agregat non-pribadi. Jangan commit/push file dump.

## 3. Makna hasil dan gerbang selanjutnya
- **Tahap impor ATP V25-3:** hasil *data akademik* lulus pemeriksaan statis SQL yang tersedia. Ini bukti keberhasilan penyimpanan struktur, bukan sertifikasi semua cabang validasi Excel, concurrency, dan UI.
- **Prioritas langsung:** Guru **tidak mengimpor ATP lagi** dan tidak membuat Materi kedua. Semua TP sudah punya satu Materi rencana (F/G); lengkapi melalui **Sunting** pada Materi yang ada.
- Jangan menandai TP siap sebelum satu berkas/URL sah, durasi, dan atribut akademik yang diwajibkan sesuai jenis TP terpenuhi. TP Esensial dan NonEsensial sama-sama wajib memiliki satu Materi lengkap; Esensial tidak mewajibkan aktivitas/ujian/nilai siswa.
- Semester 4 memiliki paket aktif lama tetapi keenam TP-nya masih draf; **belum ada konten pembelajaran yang sah untuk diuji oleh siswa**.
- Bukti end-to-end baru bisa dihasilkan setelah sekurang-kurangnya satu TP NonEsensial pada semester yang diizinkan siswa benar-benar Siap.

## 4. Rencana pengerjaan berurutan menuju aplikasi FIX
**Definisi FIX:** bisnis, database, Service/controller, UI, hak akses, audit, keamanan, pengujian otomatis dan XAMPP terbukti selesai; bukan hanya halaman tampil.

| Tahap | Pekerjaan prioritas | Kriteria penerimaan minimal |
| --- | --- | --- |
| **F1 — Stabilkan fondasi ATP pasca-impor (langsung)** | Audit detail Guru Semester–BAB–TP, status draf, nomor, relasi CP, tombol edit Materi satu-per-TP, pesan kosong/terkunci; uji impor idempoten *Tetap*, tolak perubahan semester terisi, rollback seluruh workbook; simpan hasil tes. | 36 TP/36 Materi tetap, tanpa data ganda/timpa; unggah ulang identik tidak menulis; berbagai akses salah ditolak; tidak ada perubahan DB tanpa izin |
| **F2 — Konten dan kesiapan TP** | Lengkapi alur Sunting Materi (YouTube/PDF/Audio/Gambar), upload privat, checkpoint Model B 4 menit, Esensial vs NonEsensial, indikator, tautan CP operasional, bank soal Word/edit; TP Siap & snapshot immutable; uji paket aktif + penugasan. | Setiap jenis TP punya satu Materi/media sah; NonEsensial ≥10 Diagnostik & ≥30 Sumatif, bobot 20/20/60; Esensial tidak dipaksa ujian siswa; Siap mengunci konten dan bisa dibaca pada hak yang sesuai |
| **F3 — Mesin ujian dan progres siswa** | Implementasikan/selesaikan Diagnostik sebagai gate, hak siswa Gen/semester, jalur lulus/lewati vs wajib Materi→Resume→HOTS; Sumatif wajib di kedua jalur, batas desktop/fullscreen, reconnect/jeda, heartbeat/server time, randomisasi & snapshot, remedial satu kali bila skor <KKM; pengujian refresh/double-submit. | Diagnostik lulus **tidak pernah** otomatis menuntaskan TP; Sumatif selesai sah, mengambil skor terbaik normal/remedial; TP tuntas meski di bawah KKM; hak siswa terlindungi |
| **F4 — Penilaian Guru dan Nilai TP** | Antrean nilai Resume/HOTS, finalisasi dan audit, kalkulasi nilai sesuai jalur (20/20/60 atau 100% Sumatif), KKM Admin vs khusus Guru, koreksi berizin, halaman nilai siswa. | Nilai sumber dan agregat dapat dilacak; perubahan aman/idempoten, tidak membatalkan progres/sertifikat historis |
| **F5 — Pra-Semester + ekspor nilai** | Bank ujian Pra-Semester gabungan semua BAB satu semester; jadwal Admin serentak, setelah seluruh TP NonEsensial selesai; mesin Sumatif yang sama, nilai berdiri sendiri; unduh nilai Guru per Gen–Mapel seluruh siswa, termasuk bila baru 1 nilai TP. | Jadwal/gate/attempt sah; ekspor sesuai hak akses, tanpa formula injection; **tidak ada nilai akhir semester** |
| **F6 — Sertifikat dan revisi** | Setelah nilai/progres stabil: template latar, tanda tangan PNG, PDF, QR, nomor, NISN privat, pratinjau & unduh; revisi mempertahankan riwayat, QR/nomor baru bila nilai tercetak berubah, larangan hapus/cabut. | Sertifikat dibuat hanya saat syarat terpenuhi; PDF/QR lama valid sebagai riwayat digantikan; tidak ada data siswa bocor |
| **F7 — Dashboard, Pimpinan, notifikasi, laporan** | Dashboard per empat peran, Pimpinan baca-saja, statistik/monitoring dan notifikasi internal setelah transaksi; responsif Emerald Glass tanpa framework baru. | Tidak ada aksi dummy; filter peran dan anti-akses silang lulus, semua UI/empty/loading/error bekerja |
| **F8 — Hardening dan penerimaan akhir** | Uji E2E Master 16, PHPUnit, php-lint, DB QA terpisah MySQL/MariaDB, kontrol CSRF/XSS/IDOR, root privat/.env, upload/ekspor, session/lockout, responsif desktop/HP, performance shared hosting, backup/restore; perbaiki semua temuan. | Tidak ada bug blocking/high, semua fungsi wajib terbukti, persetujuan pemilik setelah pull + uji XAMPP; baru review PR dan merge ke main |

**Pola pelaksanaan:** satu tahap atau subset kecil per branch/PR → AI mengubah kode dan menguji yang bisa → pemilik pull dan uji XAMPP → revisi bukti dan tutup tahap → lanjut tahap berikutnya. **Tidak akan melompat ke sertifikat/dashboard sebelum F2–F5 stabil.**

## 5. Hal yang harus dijaga dan belum diputuskan
- **Belum diputuskan oleh pemilik:** apakah Pra-Semester harus dituntaskan sebelum sertifikat dan apakah nilainya dicetak. Catat sebagai *pending*, **jangan diam-diam membuatnya sebagai gate baru**.
- Jangan memodifikasi `.env`, `index.php` atau `.htaccess` root tanpa izin; tetap CI4 MVC/Service, JS native/CSS custom, tanpa Migration/Seeder.
- Jangan menghapus/menimpa data akademik nyata, dump atau histori nilai; perubahan SQL harus patch bernomor, backup dan uji nonproduksi.
- Tetapkan prioritas pada **F1** ketika pengguna siap meneruskan pengerjaan: audit UI Detail TP / edit Materi hasil impor, dan tes idempotensi/penolakan impor ulang tanpa mengubah data. Lanjut **F2** setelah gate F1 PASS.

## 6. Status pemeriksaan aktual pada checkpoint ini
**Dijalankan:** parser SQL baca-saja lokal, hitung konsistensi 58 DDL dan 33 tabel dengan INSERT, verifikasi 6 paket, 12 BAB, 36 TP, 36 Materi, semua kode/urutan/relasi, versi SQL, dan pemeriksaan statis repo routes/Master. Tidak ada perubahan DB atau upload dump.

**Belum dijalankan:** php-lint/PHPUnit pada branch terbaru, transaksi MySQL QA, akses XAMPP/browser dan tes end-to-end. Hasil ini **tidak menyatakan seluruh SLA-SKS FIX**.
