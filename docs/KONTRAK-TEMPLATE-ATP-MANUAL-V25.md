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

Berkas di `docs/` adalah **kerangka master tanpa identitas akun**. Generator unduhan berikutnya wajib mengisi `RINGKASAN` dan `REFERENSI CP` dari penugasan Guru yang sah; tidak menyalin data pribadi ke GitHub. Parser berikutnya hanya membaca sheet `SEMESTER 1–6`, menolak formula/makro/zip bomb serta kode/jenis/relasi salah. Isian Materi di Excel **bukan unggah media atau penguncian TP Siap**. Pratinjau perubahan dan konfirmasi transaksional/idempoten tetap wajib; baris kosong atau hilang tidak menghapus TP terdahulu. TP Siap, progres, ujian, nilai dan histori tidak boleh ditimpa.

**Status implementasi PR ini:** hanya template fisik, versi dan tes kontrak. Unduh dinamis kontekstual, parser, pratinjau dan transaksi impor **belum dibuat**. UI Emerald Glass tidak diubah. Tidak ada perubahan SQL.

**QA:** `php vendor/phpunit/phpunit/phpunit --filter AtpManualTemplateTest`; cek file dapat dibuka Excel/LibreOffice, sheet dan header sesuai, dropdown jenis TP di D39:D118. Pengujian ini tidak menggantikan uji manual upload/otorisasi pada tahap berikutnya.
