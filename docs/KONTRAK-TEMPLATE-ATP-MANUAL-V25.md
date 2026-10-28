# Kontrak Template ATP Manual SLA-SKS v2.5

**Berkas resmi:** [TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx](TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx)

**Versi:** `SLA-SKS-ATP-MANUAL-V25-1` — file `.xlsx`, 10 sheet, tanpa VBA/macro/tombol proses/rumus pembentuk BAB–TP.

## Struktur stabil untuk generator dan parser

| Sheet | Sel / wilayah | Kontrak |
|---|---|---|
| MULAI DI SINI | A1, A3–A16 | Petunjuk. Tidak diimpor. |
| RINGKASAN | B4 (versi), B5–B15 (konteks) | Identitas Guru, Gen, Mapel, Kurikulum dan CP. Diisi server saat unduh; jangan dipercaya untuk otorisasi saat upload. |
| REFERENSI CP | A6:E6, data A7:E... | Satu CP operasional dengan elemen versi aktif. Hanya referensi, tidak mengedit CP. |
| SEMESTER 1–6 | A12:C12 | Header BAB: nomor, nama (maks. 50), deskripsi (maks. 225). |
| SEMESTER 1–6 | A13:C32 | **20 baris kosong** untuk BAB. |
| SEMESTER 1–6 | A38:J38 | Header TP: nomor BAB, urutan TP, kode, jenis, nama (maks. 50), deskripsi (maks. 225), judul satu Materi, rencana jenis media, elemen CP rujukan, catatan. |
| SEMESTER 1–6 | A39:J118 | **80 baris kosong** untuk TP. |
| CONTOH MAT VII | Semua | Ilustrasi saja; **tidak pernah dibaca sebagai ATP nyata**. |

Jumlah 20 BAB/80 TP adalah **kapasitas fisik template per semester, bukan batas akademik**. Jangan menyisipkan baris/kolom atau mengubah header pada file versi ini. Kode TP harus diketik manual dan diperiksa server terhadap `KODEMAPEL.KELAS.NOMOR_BAB.URUTAN_TP`. BAB berlanjut antardua semester per kelas, reset pada kelas berikutnya; TP selalu mulai urutan 1 per BAB. Nama akademik maksimum 50 karakter; **database `sla_sks_v4` lokal dapat masih membatasi 35**, sehingga proses penyimpanan baru >35 karakter tidak boleh dipaksakan sebelum patch SQL yang diaudit.

## Pemisahan tanggung jawab

Berkas di `docs/` adalah **kerangka master tanpa identitas akun**. Generator unduhan berikutnya wajib mengisi `RINGKASAN` dan `REFERENSI CP` dari penugasan Guru yang sah; tidak menyalin data pribadi ke GitHub. Parser berikutnya hanya membaca sheet `SEMESTER 1–6`, menolak formula/makro/zip bomb serta kode/jenis/relasi salah. Isian Materi di Excel **bukan unggah media atau penguncian TP Siap**. Pratinjau perubahan dan konfirmasi transaksional/idempoten tetap wajib; baris kosong atau hilang tidak menghapus TP terdahulu. **Revisi aturan final 28-10-2026:** impor hanya INSERT ke semester kosong, bukan upsert/UPDATE draf; semester terisi hanya diterima sebagai *Tetap* apabila seluruh BAB/TP identik; Excel yang mencoba mengubah/menambah ditolak untuk seluruh batch. TP Siap, progres, ujian, nilai dan histori tidak boleh ditimpa.

**Status setelah PR generator:** Template fisik dan generator unduhan kontekstual Guru → Paket telah dibuat. Tombol unduh muncul pada detail semester dengan paket non-arsip, memverifikasi hak G1 pada Service, mengambil kurikulum/tahun dan CP aktif dari server, mengisi RINGKASAN dan REFERENSI CP, dan menjaga enam sheet input BAB/TP tetap kosong. **Parser, pratinjau dan transaksi impor belum dibuat**. UI tetap menggunakan komponen Emerald Glass yang sudah ada. Tidak ada patch SQL. Unduhan memerlukan paket semester yang sudah dibuat dan CP aktif Fase D; paket lintas Gen maupun arsip ditolak.

**QA:** `php vendor/phpunit/phpunit/phpunit --filter AtpManualTemplateTest`; cek file dapat dibuka Excel/LibreOffice, sheet dan header sesuai, dropdown jenis TP di D39:D118. Pengujian ini tidak menggantikan uji manual upload/otorisasi pada tahap berikutnya.

## Kontrak tampilan workbook (revisi visual)
- Judul setiap sheet terbaca: teks forest di pita mint terang, tipografi tegas, tinggi baris cukup.
- Petunjuk ringkas, Bagian A/B dipisahkan, header kontras tinggi; area input BAB/TP bergaris halus dan zebra row. Gridline Excel disembunyikan.
- Kolom Jenis TP (D39:D118) diberi hijau muda; Judul satu Materi (G39:G118) diberi biru muda. Dropdown D39:D118 dan H39:H118 dipertahankan.
- Ringkasan berformat label/nilai; referensi CP luas dan dapat membungkus teks; CONTOH MAT VII tetap sekadar ilustrasi, tidak diimpor.
- Tidak berubah: **10 nama sheet**, versi B4, header BAB A12:C12, header TP A38:J38, **20 BAB + 80 TP** tiap semester, otorisasi G1, larangan macro/VBA/formula, dan semua aturan akademik. UI Emerald Glass pada aplikasi tidak diubah.

Perbaikan ini disatukan ke PR #70 setelah peninjauan file hasil unduhan di XAMPP. Fungsi parser/impor merupakan tahap berikutnya.

## Pratinjau impor ATP — tahap 3A, tanpa perubahan data
- Guru mengunggah `.xlsx` dari halaman detail semester yang memiliki paket sah. Route `POST guru/peta-atp/pratinjau/{paketId}` memakai `auth:guru`, CSRF, dan pemeriksaan penugasan G1 dalam Service.
- Parser membaca **keenam** sheet SEMESTER 1–6, memvalidasi nama kolom, versi, kode TP, jenis TP, nomor BAB yang berlanjut lintas pasangan semester, urutan TP per BAB, elemen CP aktif, panjang nama/deskripsi, larangan rumus/macro/koneksi, serta batas ZIP 5 MB terkompresi / 25 MB isi. Sheet CONTOH MAT VII tidak diimpor.
- Pratinjau mengambil konteks Gen–Mapel–Kurikulum–Tahun Ajaran–CP dari **server**, lalu membandingkan dengan BAB/TP tersimpan. Hasil per baris: **Baru / Berubah / Tetap / Galat**, disertai nomor semester dan baris sumber. Baris yang tidak tercantum **bukan perintah hapus**.
- Perubahan TP Siap, TP arsip, BAB yang berisi TP Siap, nama melebihi batas kolom database terpasang, dan data untuk semester tanpa paket nonarsip diperingatkan sebagai **Galat**.
- **Tidak ada staging persisten, konfirmasi, INSERT, UPDATE, DELETE, atau impor otomatis** pada tahap 3A. Berkas unggahan sementara dibuang oleh PHP setelah request. Setelah konfirmasi transaksi aman pada tahap berikutnya, data harus divalidasi ulang, termasuk otorisasi, diff, urutan, dan perlindungan histori.
- UI menggunakan komponen Emerald Glass final dan tidak mengubah desain workbook yang sudah disetujui.

## Tahap 3B — konfirmasi transaksional (PR setelah #71)
- Tombol **Konfirmasi dan Simpan BAB/TP** tampil hanya untuk hasil pratinjau tanpa galat yang mempunyai baris baru/berubah. Tidak ada penyimpanan pada saat mengunggah/pratinjau.
- File XLSX lolos pratinjau disalin sementara ke `writable/private/atp_import/` (bukan `public/`), dilindungi aturan akses Apache, izin file 0600, nama token acak 64-hex, checksum SHA-256, identitas pengguna/paket/hash sesi, serta batas berlaku **15 menit**. File dan token dibersihkan setelah commit berhasil; kedaluwarsa dibersihkan oportunistik. Folder tersebut tidak boleh dipush dan tidak boleh berisi data siswa.
- Saat POST dengan CSRF, Service memverifikasi token dan checksum, memeriksa ulang akun Guru aktif, pengampu G1, status paket serta referensi CP server, lalu mengunci paket-paket Gen–Mapel–kurikulum–tahun ajaran dengan `SELECT ... FOR UPDATE` pada satu **transaksi database**. Semua isi Excel dan diff dihitung ulang. Bila fingerprint state pratinjau berubah, pembatalan dilakukan tanpa menerapkan sebagian.
- **Idempoten**: BAB dicocokkan per paket/nomor; TP per paket/kode. Data tetap tidak diubah, baru memakai INSERT, revisi draf yang aman memakai UPDATE. Tidak ada DELETE historis; angka BAB/kode/urutan wajib benar. TP Siap/arsip tidak ditimpa dan TP draf yang punya Materi/snapshot/progres harus disunting melalui editor resmi. **Rencana judul/media dalam Excel tidak membuat Materi/berkas/soal.**
- Audit transaksi dicatat pada `log_aktivitas` (tanpa isi CP/data siswa) dan rollback jika query gagal. Tidak ada migration/seeder/patch SQL. Pastikan uji lokal MariaDB/MySQL dan otorisasi sebelum PR dinyatakan FIX.
- **Perubahan kebijakan dari tahap 3A yang disengaja:** staging 15 menit disimpan sementara hanya setelah pratinjau sah sebagai persiapan konfirmasi eksplisit; data BAB/TP tetap tidak disentuh saat pratinjau.
