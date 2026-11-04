# A11-2 — Checkpoint Sertifikat PDF/QR, Template dan Histori

Tanggal: **3 November 2026**  
Branch: `feature/a11-2-sertifikat-pdf-qr-20261103`  
PR: **#113 Draft**, berbasis A11-1. Tidak digabung ke `main`.

## Keputusan bisnis mengikat

1. Seluruh TP NonEsensial perlu tuntas, mempunyai percobaan Sumatif `selesai_sah`, dan nilai wajib TP harus `final`. Nilai 0 dan nilai di bawah KKM sah jika proses TP sudah selesai benar. TP Esensial dicetak tanpa skor.
2. **Pra-Semester bukan syarat sertifikat**. Nilainya **tidak dicetak**, tidak dihitung, dan perubahan hasil Pra-Semester tidak memicu revisi PDF/QR. Tidak ada Nilai Akhir Semester.
3. Background sertifikat A4 landscape optional (fallback). TTD PNG aktif wajib. Pratinjau PDF **data contoh** wajib untuk aktivasi background yang diunggah; tidak ada preview berdasarkan data siswa nyata pada tahap desain template.
4. Sertifikat awal/perubahan resmi satu per satu, bukan batch otomatis. Revisi yang mengubah konten cetak membuat nomor/QR/PDF/versi baru, lama menjadi `digantikan`. Jika hash konten sama, tidak ada versi baru. Nomor, PDF dan QR lama tidak dihapus.

## Struktur database

Sumber audit privat: `sla_sks_v4 (15).sql`, ekspor 03-11-2026 09.32 (MariaDB 10.4.32), berisi 64 tabel, marker terakhir `V4-0010-BANK-WORD-PRA`, belum ada tabel sertifikat/TTD. Sumber ini **tidak diunggah ke GitHub**.

Patch additive sekali: `database/sql/updates/V4-0011-SERTIFIKAT-ASSET-DAN-VERSI.sql`.

- `sertifikat_aset`: metadata aset JPG/PNG immutable di `writable/private/sertifikat/aset`.
- `sertifikat_aset_aktif`: satu pointer background dan satu pointer TTD aktif, histori aset tetap tersimpan.
- `sertifikat`: snapshot privat NISN, BAB/TP final, identitas madrasah, versi aset, nomor, token QR acak, checksum konten/PDF dan status `berlaku`/`digantikan`.
- `sertifikat_aktif`: satu pointer aktif per siswa–paket. FK dan UNIQUE menjaga integritas historis.

Patch **tidak berisi DELETE, TRUNCATE, reset siswa, reset nilai atau kredensial**. Impor hanya dilakukan **pemilik** di XAMPP setelah backup DB dan `writable/`; jangan ulang jika marker V4-0011 sudah ada.

## Kode yang dibuat/diubah

- Service: `SertifikatAsetService.php`, `SertifikatPdfService.php`, `SertifikatPenerbitanService.php`, penambahan `KelayakanSertifikatService::periksaSiswa`.
- Controller: `AdminSertifikatAset.php`, `AdminSertifikatTerbit.php`, `SiswaSertifikat.php`, `VerifikasiSertifikat.php`; pembaruan `AdminSertifikat.php`.
- View: `admin/sertifikat_aset.php`, `admin/sertifikat_kelayakan.php`, `siswa/sertifikat.php`, `publik/verifikasi_sertifikat.php`.
- Rute: Admin GET/POST aset + pratinjau + terbit/revisi/unduh, Siswa daftar/unduh, publik verifikasi kode. Menu Siswa sertifikat tersedia dan menu Admin Sertifikat memakai halaman pemeriksaan + aksi.
- CSS: `app.css`, `emerald-glass.css` ditata untuk tabel/status/aksi responsif.
- Proteksi tambahan `writable/private/sertifikat/.htaccess` meski root sudah melarang `/writable`.
- Tests/QA: `SertifikatPenerbitanDatabaseTest.php` (SQLite anonim, Dompdf/endroid), `tests/scripts/a11_sql_patch_mariadb.php` (MariaDB 10.4 sintetis), `.github/workflows/qa-a11-2-sertifikat.yml`.
- Master 09–16 dan README SQL diperbarui untuk kontrak domain/keamanan/UX/operasional.

## Pengujian dan batasan

- PHP lint, PHPUnit SQLite sintetis, rendering PDF dengan Dompdf, QR Endroid, nilai 0, idempotensi, revisi, PDF lama, unduhan pemilik dan proyeksi publik dijalankan di workflow A11-2.
- SQL additive juga dites di service MariaDB 10.4 GitHub Actions dengan empat tabel minimal dan data anonim; **bukan** restore penuh dump XAMPP.
- Uji sesudah pull: backup, impor patch sekali, Admin upload background/TTD, pratinjau, aktivasi, periksa kelayakan, terbit untuk **akun simulasi yang benar-benar memenuhi TP**, unduh PDF, verifikasi URL QR, coba revisi setelah perubahan sah, periksa arsip lama dan hak Siswa lain.
- QR localhost cocok untuk uji browser pada mesin lokal; **tidak cocok untuk QR sertifikat nyata lintas perangkat dan hosting** sebelum domain HTTPS permanen ditetapkan. Jangan mengubah baseURL/.env tanpa izin pemilik.
- Status final A11-2 hanya PASS sesudah CI dan uji browser+impor MariaDB lokal, termasuk potensi layout PDF A4 dan akses HTTP langsung ke `writable`.

## Titik QA yang harus diawasi

1. Gambar TTD sumber harus tidak dapat diakses oleh publik, QR publik juga tidak memuat NISN atau PDF privat.
2. TTD/template lama tidak terhapus saat baru diaktifkan, PDF historis immutable.
3. Penerbitan ganda identik tidak membuat sertifikat baru; revisi konten beda membuat versi baru secara aman.
4. Jika pemilik belum memiliki siswa yang memenuhi syarat, **tombol terbit tidak muncul**, bukan error aplikasi. Jangan membuat data siswa/hasil ujian palsu pada DB nyata.
5. Restore harus meliputi DB **dan** folder private PDF/TTD; PDF yang hilang/checksum tidak cocok harus ditolak saat unduh.
