# A11-3 — QA Tampilan Hasil Cetak Sertifikat A4

Tanggal: **3 November 2026**
Basis: A11-2 \`feature/a11-2-sertifikat-pdf-qr-20261103\`.
Status: perbaikan **renderer PDF saja**; pemeriksaan browser XAMPP diperlukan.

## Bukti yang diperiksa

Pemilik mengunggah PDF hasil penerbitan sertifikat A11-2 yang berhasil diuji dengan data DEMO. PDF dibaca sebagai **1 halaman A4 landscape (841,89×595,28 pt)**, Dompdf 3.1.6, berisi dua BAB dan empat TP: Esensial tanpa nilai, dua Nilai TP NonEsensial, QR dan gambar TTD. Identitas dan data personal yang tercetak **tidak disalin ke repository**.

Masalah visual:

1. Bingkai konten mengikuti tinggi otomatis: setelah QR/TTD masih ada kira-kira seperempat tinggi kertas yang tidak terpakai.
2. Font pada nama siswa, judul, tabel TP dan nilai tergolong kecil untuk cetak A4.
3. QR, catatan, dan TTD terlalu berdekatan dengan tabel, serta komposisi tidak mengisi bidang kertas secara seimbang.
4. Pola footer absolut tidak boleh memotong tabel untuk semester dengan banyak TP.

## Perubahan

- \`app/Services/SertifikatPdfService.php\`: mode \`card--full\` dengan bingkai tinggi A4 untuk maksimal **4 TP dan 3 BAB**, sedangkan \`card--flow\` untuk TP lebih banyak; bila Dompdf tetap menghasilkan >1 halaman pada mode pendek, otomatis render ulang dengan footer normal.
- Tipografi dibuat berdasarkan satuan **pt** untuk kualitas cetak. Nama madrasah/sertifikat diperjelas; panel identitas siswa dipisahkan, judul tabel dan header BAB ditata, nilai lebih terbaca.
- QR dan TTD diletakkan dekat bagian bawah pada mode pendek dengan ruang tersendiri; footer normal untuk dokumen panjang, serta \`page-break-inside: avoid\` untuk tiap baris TP.
- Preview bawaan sekarang **2 BAB/4 TP anonim**, sehingga dapat dibandingkan dengan sertifikat DEMO tanpa membaca siswa di database.
- Tidak ada perubahan data, nilai, \`sertifikat\` historis, nomor/QR, DB, \`.env\`, root CI4, ataupun pengaturan TTD/background.

## QA otomatis

\`tests/unit/SertifikatTataLetakPdfTest.php\` membuat PDF sintetis dengan 4 TP dan 24 TP, tanpa data siswa nyata. Workflow \`.github/workflows/qa-a11-3-layout-sertifikat.yml\` menjalankan PHP lint, PHPUnit, \`pdfinfo\` (A4 landscape dan 4 TP satu halaman), \`pdftotext\` (TP pertama dan terakhir tetap ada), serta menyimpan preview dummy selama tujuh hari.

Regresi A11-2 untuk PDF/QR, TTD/asset, versi historis, dan hak siswa tetap dijalankan melalui workflow A11-2 yang sudah ada. **Tidak ada SQL baru.**

## Catatan historis wajib

Sertifikat yang **sudah diterbitkan** tetap disimpan dalam \`writable/private/sertifikat/pdf\` dengan checksum dan nomor/QR lama. Deploy renderer baru **tidak meregenerasi dan tidak menimpa V001**. Perubahan tampilan murni diuji melalui menu Admin → Sertifikat → Template & TTD → Pratinjau Template Bawaan tanpa penerbitan ulang. Jika kelak diperlukan kebijakan menerbitkan ulang karena desain, pembahasannya terpisah dan **tidak boleh dibuat sebagai revisi nilai fiktif atau memodifikasi PDF historis**.

## Checklist localhost

1. Pull branch A11-3; tekan Ctrl+F5 pada browser.
2. Admin → Sertifikat → Template & TTD → **Pratinjau Template Bawaan**. Lihat halaman A4 landscape PDF dummy 2 BAB/4 TP: posisi judul, identitas, tabel, QR-placeholder dan area TTD.
3. Jika background unggahan aktif, pratinjau dari daftar aset background tersebut (bukan hanya fallback).
4. Pastikan PDF V001 yang sudah terbit **tidak berubah**. Periksa menu Siswa → Sertifikat Saya dan QR lama masih valid.
5. Uji hasil cetak melalui browser/Print PDF, halaman A4 landscape, margin, keterbacaan tabel, dan tidak ada teks saling tumpang tindih.

Tahap ini belum dinyatakan FIX sebelum hasil visual localhost disetujui pemilik.

## Hasil QA final branch A11-3 (3 November 2026)

- Workflow \`QA A11-3 Layout Cetak Sertifikat PDF\` **PASS**, run GitHub Actions **41634475712**: PHP lint PASS; **4 tes/10 assertions** PASS.
- Sampel **4 TP / 2 BAB** menghasilkan **1 halaman A4 landscape** baik pada pratinjau placeholder maupun saat memakai **QR nyata + TTD PNG sintetis**.
- Pemeriksaan koordinat dari \`pdftotext -bbox-layout\` menghasilkan celah positif **21,7 pt dan 24,9 pt** antara teks TP terakhir dan footer, sehingga pengujian otomatis tidak hanya menghitung jumlah halaman tetapi juga mendeteksi tumpang tindih teks.
- Sampel panjang **24 TP** menghasilkan **3 halaman A4 landscape** dan kode TP awal/akhir tetap terbaca; seluruh TP dipertahankan.
- Regresi \`QA A11-2 Sertifikat PDF QR Versi\` **PASS**, run **41634475723**: 6 tes/28 assertions penerbitan, 8 tes/35 assertions kelayakan, SQL V4-0011 MariaDB 10.4 sintetis PASS. **Tidak ada SQL baru di A11-3**.
- Kedua PDF preview anonim dihasilkan dan diverifikasi tanpa mengambil data siswa/TTD pengguna. PDF historis pengguna tetap pada penyimpanan lama; belum dilakukan uji browser XAMPP dengan renderer baru.

**Kriteria penutupan:** pemilik menguji menu pratinjau cetak pada branch A11-3 di XAMPP dan menyatakan visual A4/QR/TTD sesuai. Jangan menerbitkan ulang V001 hanya untuk mengejar desain baru.
