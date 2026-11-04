# Checkpoint A8-0 — Fondasi Aturan Pra-Semester

**Tanggal:** 2 November 2026  
**Branch:** \`feature/v25-a8-fondasi-kebijakan-pra-semester-20261102\`, berbasis A7 / PR #97.  
**Status:** domain service + tes tanpa database; **belum modul ujian Pra-Semester operasional** dan belum FIX XAMPP.

## Acuan yang mengikat
- Master 02, 03, 06, 07, 08, 10, 11, 12, 14, 16 (v2.5); README dan AGENTS.
- Pra-Semester **bukan TP**, minimal **30 soal sah** gabungan semua BAB dalam semester yang sama.
- Siswa dapat mulai hanya jika **seluruh TP NonEsensial paket itu status tuntas**. Esensial tidak punya progres siswa dan tidak menjadi gerbang. TP selesai meski nilai di bawah KKM; jangan ganti uji status dengan tes >= KKM.
- Jadwal tanggal/jam serentak ditetapkan Admin. Bandingkan hanya dengan jam server zona \`Asia/Jakarta\`; sebelum jadwal dan setelah tutup tidak boleh mulai.
- Pemeriksaan fullscreen Ketat, jeda, heartbeat, satu remedial bila <KKM dan histori attempt wajib dipakai lagi saat mesin nyata dibuat. Tidak ada nilai akhir semester.
- Status Pra-Semester sebagai gerbang sertifikat dan nilai dalam sertifikat **belum diputuskan**; tidak menambah ketentuan sendiri.

## Yang benar-benar dibuat pada A8-0
1. \`app/Services/AturanPraSemesterService.php\`: kebijakan deterministik **tanpa koneksi DB**:
   - \`kelayakanTp()\`: hitung jumlah TP NonEsensial dan yang tuntas; hasil menolak siswa belum lengkap atau paket belum memiliki TP wajib; identitas TP duplikat/jenis salah ditolak.
   - \`kesiapanBank()\`: minimal 30 soal aktif PG A–E sah, cek opsi dan kunci yang telah divalidasi server, soal harus berasal dari BAB yang tepat, dan **setiap BAB** terwakili; soal tak sah tak dihitung; ID ganda ditolak.
   - \`jadwal()\`: input format datetime WIB, validasi tanggal dan urutan, waktu mulai inklusif/waktu akhir eksklusif; tidak mengandalkan jam dari browser.
   - \`gerbangMulai()\`: ketiga syarat harus lolos; mengembalikan alasan bila gagal.
2. \`tests/unit/AturanPraSemesterServiceTest.php\`: fixture anonim, uji TP tuntas/kurang, Esensial, paket tanpa TP wajib, bank 29/30, cakupan BAB, soal salah, duplikat, jadwal sebelum/pada/sesudah batas WIB, dan gerbang kombinasi.
3. \`.github/workflows/qa-a8-pra-semester.yml\`: lint, PHPUnit A8 dan regresi penghitungan waktu Ketat.

## Mengapa A8 belum membuat tabel, Controller, atau tombol ujian?
Instruksi AGENTS.md menetapkan audit dump SQL **sesudah patch terbaru** sebelum pekerjaan database (CREATE/ALTER dan query bergantung skema). Dump privat terakhir yang terkonfirmasi saat audit masih \`sla_sks_v4 (11).sql\` diekspor 2 November 2026 pukul **10.24 WIB**; belum ada bukti bahwa dump tersebut diambil **sesudah** A5 \`V4-0008\`. Maka:
- **Tidak ada SQL baru, query struktural baru, atau impor DB yang dibuat A8-0**.
- Belum ada endpoint GET/POST, penjadwalan Admin, editor bank soal lintas BAB, percobaan ujian siswa, atau sertifikat yang berpura-pura dapat digunakan.
- A7 XLSX belum mengisi nilai Pra-Semester sebelum hasil sah tersedia.

## Input yang diperlukan untuk implementasi A8-1 terhubung database
Pemilik melakukan **backup database** XAMPP, lalu ekspor dump SQL \`sla_sks_v4\` yang **terbaru setelah seluruh patch yang benar-benar diimpor termasuk V4-0008 bila telah dipasang**, dan unggah dump privat **ke percakapan saja** (jangan commit ke GitHub). Perlu verifikasi: waktu export, server, struktur, FK/unique/enum dan row counts aman dari \`paket_semester\`, \`bab\`, \`tp\`, \`progres_tp\`, \`soal\`, \`percobaan_ujian\`, \`schema_version\`, dan keberadaan \`ujian_pra_semester\`/\`soal_pra_semester\`.

Setelah audit: implementasi patch SQL \`V4-0009\` **hanya bila memang diperlukan**; pengaturan Admin dan audit, bank soal Guru G1 dengan 30 soal/cakupan BAB, preflight Siswa & mesin Ketat transaksional, remedial dan nilai mandiri, integrasi A7 XLSX. Jangan mengubah A5 schema/patch historis atau men-reset data siswa.

## QA & langkah kerja
- GitHub Actions harus lulus pada SHA final; catat hasil nyata.
- Tes lokal setelah kelak terintegrasi: Admin jadwal, Guru bank dari semua BAB, siswa TP belum tuntas ditolak, siswa tuntas tapi belum jam ditolak, di waktu sah diperbolehkan preflight desktop/fullscreen, di akhir waktu ditolak; batal tanpa skor dan remedial sekali. Semua diuji di XAMPP setelah impor SQL berizin.
- A8-0 dapat dipull, tetapi **tidak muncul menu baru**. Ini sengaja agar tidak ada fitur dummy.
- Branch ini dan PR masih Draft, jangan merge \`main\` tanpa verifikasi.
