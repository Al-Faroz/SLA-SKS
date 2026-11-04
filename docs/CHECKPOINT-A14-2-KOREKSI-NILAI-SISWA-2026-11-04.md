# Checkpoint A14-2 — Koreksi Nilai Siswa Setelah Audit Dump (19)

Tanggal 4 November 2026. Branch \`feature/a14-2-audit-dampak-koreksi-nilai-20261104\`, PR **#130** (Draft di atas A14-1 #129, belum digabung main).

## Audit dan gerbang SQL
- Terverifikasi dump privat \`sla_sks_v4 (19).sql\` dari XAMPP (04 November 2026, 09.28), MariaDB 10.4.32, 71 tabel. Dump tidak diunggah ke GitHub.
- Marker terakhir \`V4-0012-PENGUMUMAN-FAQ\`; **tidak** ada marker \`V4-0013-NOTIFIKASI-PRIVASI\` maupun tabel \`notifikasi\`/\`kebijakan_privasi\` dalam dump (19). A13-6/A13-7 FIX berdasarkan konfirmasi pemilik **tidak berarti patch tersebut sudah tercatat**.
- Nilai sumber berada di \`resume_materi\` dan \`jawaban_hots_materi\`, terikat \`progres_tp\`, \`nilai_tp\` dan snapshot \`versi_konten_tp\`. Guru sah ditentukan oleh G1 \`penugasan_guru_gen\`. Sertifikat aktif terikat \`sertifikat_aktif\` dengan versi/PDF historis di \`sertifikat\`.
- \`permohonan_koreksi_tp\` legacy berkategori A/B/C adalah koreksi **konten TP**, bukan koreksi nilai siswa; **tidak dipakai atau dimodifikasi**.

## Fitur yang dibuat
- **SQL additive** \`database/sql/updates/V4-0014_KOREKSI_NILAI_SISWA.sql\`: tabel \`permohonan_koreksi_nilai\` dan \`riwayat_koreksi_nilai\` dengan FK, unique permohonan terbuka, versi berurutan, nilai sebelum/usulan, audit aktor dan penanda dampak sertifikat. Import tidak mengubah data nilai/sertifikat.
- **Service** \`KoreksiNilaiDampakService\` menggunakan kalkulator \`NilaiTpService::hitung()\` untuk nilai TP bobot 20/20/60. \`KoreksiNilaiService\` membuat permohonan Guru G1, menguji snapshot, memeriksa ulang nilai sumber/TP dan G1 pada transaksi Admin, menolak self-approval dan retry/konflik; hanya persetujuan yang dapat mengubah sumber + agregat nilai TP dalam satu transaksi.
- **Controller, rute dan UI** Guru: antrean permohonan pribadi, pratinjau, form alasan dan detail/riwayat. Admin: daftar permohonan, Setujui/Tolak dengan alasan, audit. Tombol dari detail tugas Guru muncul hanya setelah skor awal disimpan. CSRF global tetap dipakai.
- **Notifikasi** Admin menerima pengajuan, Guru pengusul menerima keputusan, Siswa pemilik nilai menerima kabar koreksi disetujui. Pemeriksaan sumber ketika tautan dibuka mengikuti role.
- **Sertifikat** bukan diubah otomatis. Bila nilai TP tercetak berubah dan ada sertifikat aktif, permohonan diberi penanda **Perlu Tinjau Sertifikat** untuk aksi revisi satu-per-satu Admin di modul A11. PDF dan QR lama tetap utuh.

## Batas penting
Tahap ini hanya untuk nilai **Resume/HOTS yang sudah dinilai dan TP NonEsensial berstatus Tuntas dengan nilai Sumatif/TP tersimpan**. Bukan koreksi nilai Diagnostik, Sumatif, remedial mesin atau Pra-Semester, dan tidak membenarkan perubahan kunci soal/TP historis. Bila Guru pindah Gen setelah mengajukan, keputusan ditahan, bukan dialihkan otomatis. Bila nilai belum lengkap, form koreksi menampilkan kondisi belum memenuhi syarat.

## Pengujian dan prosedur XAMPP
- \`tests/unit/KoreksiNilaiDampakTest.php\`: bobot dan validasi snapshot angka.
- \`tests/unit/KoreksiNilaiDatabaseTest.php\`: SQLite sintetis (persetujuan, penolakan, idempoten, self-approval, Guru Gen lain, sumber berubah, G1 berubah, sertifikat, nol).
- \`tests/integration/SqlA14KoreksiNilaiSchemaCheck.php\`: impor SQL asli V4-0014 pada MariaDB sementara dan pemeriksaan FK/unique/status/versi dengan data anonim.
- Workflow \`QA A14-2 Koreksi Nilai Resmi\`: lint + regresi + MariaDB. Hasil pada PR harus dicek; klaim XAMPP PASS hanya setelah pemilik benar-benar menguji.
- Backup DB dan \`writable/\` dahulu; cek marker/SHOW TABLES; **impor V4-0013 terlebih dahulu** jika belum ada (dan tabelnya masih belum ada), lalu V4-0014 masing-masing sekali. SQL dan tata cara verifikasi baca-saja tertera di \`database/sql/README.md\`.
- Jangan menjalankan patch bila kondisi tabel/marker parsial; jangan DROP tabel maupun memodifikasi data riil demi tes. Uji hanya akun simulasi.

**Status:** implementasi di Draft PR #130; belum dinyatakan FIX sampai SQL dan alur browser diverifikasi di localhost.


## Hasil QA GitHub Actions — 4 November 2026

- Workflow `QA A14-2 Koreksi Nilai Resmi` run **41802560315**: dua job **PASS**.
- Syntax lint: 13 berkas PHP dan satu skrip SQL integration test **PASS**.
- PHPUnit: `KoreksiNilaiDampakTest` **7 tes/39 assertions**, `KoreksiNilaiDatabaseTest` **10 tes/45 assertions**, `PenilaianGuruDatabaseTest` **11/52**, `NilaiTpLangsungBelajarTest` **3/12**, `SertifikatPenerbitanDatabaseTest` **6/28**, `A13NotifikasiPrivasiDatabaseTest` **6/27**. Total **43 tes/203 assertions PASS**.
- Patch V4-0014 pada MariaDB 10.11 sementara dari berkas SQL repo **PASS** (tabel, FK, kunci unik generated, nomor versi, riwayat). Ini bukan impor langsung pada XAMPP MariaDB 10.4; uji XAMPP tetap diperlukan.
- Perbaikan keamanan setelah QA awal: Admin dapat **menolak** permohonan usang tanpa mengubah skor sumber, sehingga pengajuan lain tidak terjebak kunci unik; persetujuan tetap memeriksa ulang skor/penugasan/snapshot. Pengujian untuk kondisi ini termasuk 43 PASS.
