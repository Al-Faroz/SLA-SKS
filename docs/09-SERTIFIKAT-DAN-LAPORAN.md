# 09 — Sertifikat, Daftar Nilai, Revisi dan Laporan

> **Master v2.5 · revisi keputusan final 3 November 2026.** Daftar nilai sertifikat berbasis TP Esensial/NonEsensial, tanpa nilai akhir semester. **Pra-Semester bukan syarat penerbitan sertifikat dan nilainya tidak dicetak pada sertifikat.**

## 1. Daftar nilai sertifikat
Sertifikat siswa satu Mapel/Semester menampilkan **semua BAB/TP menurut ATP semester**: TP **Esensial** ditampilkan tanpa nilai, TP **NonEsensial** disertai nilai TP. Sertifikat **tidak** menampilkan nilai akhir semester hasil rerata TP/Pra-Semester, karena nilai agregat tersebut tidak ada. Nilai Pra-Semester tetap berdiri sendiri pada halaman rekap/laporan dan ekspor khusus Pra-Semester; **tidak boleh dicetak atau ditambahkan sebagai kolom, ringkasan, maupun penghitungan pada sertifikat**.

Penerbitan awal mensyaratkan seluruh TP NonEsensial pada paket selesai dan seluruh penilaian wajib TP sudah final, serta kesiapan data sertifikat/TTD. **Tidak perlu menunggu Pra-Semester**: belum dijadwalkan, belum diikuti, belum mendapat nilai, dibatalkan, remedial, atau belum selesai Pra-Semester **tidak menjadi alasan menahan penerbitan sertifikat**. Ketuntasan TP dan finalisasi nilai TP tetap wajib; keputusan ini tidak melonggarkan syarat tersebut.

### Keputusan final fungsi Pra-Semester (3 November 2026)

1. Pra-Semester adalah **checkpoint akademik** sebelum **Ujian Semester yang sesungguhnya**. Nilainya tetap terpisah untuk informasi, rekap, monitoring, dan ekspor, bukan bagian nilai atau kelulusan sertifikat SLA-SKS.
2. Siswa yang memenuhi **seluruh syarat TP NonEsensial selesai, nilai TP wajib final, serta identitas/TTD sertifikat siap** berhak diterbitkan sertifikat, **terlepas dari status ujian Pra-Semester**. Perlu membedakan `Belum mengikuti Pra-Semester` dari `Belum final nilai TP` agar tidak ada gerbang tersembunyi.
3. Sertifikat hanya mencantumkan **semua BAB/TP**, TP Esensial **tanpa nilai**, dan TP NonEsensial **dengan nilai final**. **Tidak ada nilai Pra-Semester** dan tidak ada Nilai Akhir Semester.
4. Hasil Pra-Semester yang muncul/berubah kemudian **tidak memicu revisi, regenerasi, pergantian nomor, atau QR sertifikat**. Revisi tetap hanya karena perubahan sah pada konten sertifikat, misalnya biodata atau nilai TP yang tercetak, mengikuti ketentuan histori.
5. Pernyataan "Ujian Semester yang sesungguhnya" menjelaskan tujuan checkpoint; **tidak berarti menambahkan modul, jadwal, nilai, atau aturan baru untuk Ujian Semester** dalam tahap ini.

## 2. PDF dan identitas
Kertas **A4 landscape 297×210 mm**. Background JPG/PNG unggahan Admin, hanya satu versi aktif global, versi historis tersimpan; fallback template bawaan bila belum ada. Identitas sekolah, nama siswa dan **NISN privat**, Mapel, Semester, daftar TP per BAB, nomor/kode QR, kota/tanggal, kepala madrasah/NIP dan **gambar tanda tangan PNG wajib**. Tidak mengklaim PNG sebagai TTE bersertifikasi eksternal. Preview wajib sebelum aktivasi template.

## 3. Versi dan revisi
Sertifikat awal terbit menyimpan snapshot isi, template background/logo/TTD dan data penandatangan. Template baru tidak mengubah terbitan lama. Saat revisi: background historis sama; nama/NIP dan tanda tangan mengikuti versi aktif terbaru. Jika penandatangan/PNG belum valid, penerbitan ditahan secara eksplisit.
- Koreksi resmi nilai/identitas yang mengubah isi tercetak menghasilkan nomor dan QR baru, versi terdahulu menjadi **digantikan** tanpa dihapus. Jika data cetak sama, perubahan internal cukup audit, tidak perlu PDF baru.
- Koreksi biodata dilakukan langsung oleh Admin, sertifikat terdampak diganti satu per satu; persetujuan Admin kedua tidak diwajibkan untuk koreksi biodata normal.
- Larangan self-approval tetap khusus permohonan akademik Guru+Admin akun sama.
- Tidak ada pencabutan, penghapusan sertifikat, atau dua sertifikat berlaku untuk siswa/paket sama.

## 4. Verifikasi publik
`GET /verifikasi/{kode}` berbasis token acak dan pembatasan permintaan. Publik hanya melihat nomor, nama, Mapel, Semester, daftar nilai yang boleh dibuka, status berlaku/digantikan dan tanggal terbit; **NISN, kontak, alasan koreksi, audit dan PDF privat tidak tampil**. QR historis masih valid dengan status digantikan dan rujukan versi aktif.

## 5. Laporan
- **Rekap/ekspor Nilai Guru**: dua halaman dan dua file terpisah: **Nilai TP** (Gen–Mapel–Semester, Esensial tanpa nilai, NonEsensial bernilai) dan **Nilai Pra-Semester** (skor sah/riwayat tersendiri). Ekspor dapat dilakukan walaupun baru satu nilai TP tersedia; kosong ≠ 0. Tidak ada file gabungan.
- Rapor per siswa: daftar TP/kode/jenis, jalur diagnostik, Resume/HOTS bila wajib, Sumatif awal/remedial, nilai TP, nilai Pra-Semester terpisah.
- Progres per semester: persentase selesai TP NonEsensial, bukan rata-rata nilai.
- Log ujian, aktivitas, persetujuan, revisi, dan pengumuman tetap berizin sesuai peran, audit, dan privasi.

Semua tampilan termasuk Pimpinan dibatasi pembacaan yang diizinkan; data nilai antargen tidak tercampur.


## Perlindungan laporan/sertifikat pada penyuntingan akademik (2 November 2026)
Penyuntingan CP oleh Admin dan isian BAB oleh Guru bukan tindakan menerbitkan/mencabut sertifikat atau menghitung ulang nilai. TP Siap hanya dapat disunting Guru G1 sebelum digunakan siswa; sesudah ada aktivitas siswa, konten TP terkunci. Seluruh riwayat nilai, PDF sertifikat dan QR yang sudah ada tetap dilindungi. Keputusan ini **tidak menambahkan mekanisme koreksi sertifikat baru**.

### A10-1 — Laporan Akademik Siswa dan Monitoring Pimpinan (3 November 2026)

- Siswa memiliki **Daftar Laporan** untuk paket pembelajaran yang sudah terbuka dan **Detail Laporan** sendiri per Mapel–Semester; Pimpinan memiliki **Monitoring** siswa per paket Gen–Mapel–Semester serta pratinjau individual baca-saja.
- Detail menampilkan seluruh BAB/TP nonarsip menurut ATP. TP Esensial ditampilkan tanpa skor; NonEsensial menampilkan jalur Diagnostik, skor Diagnostik yang sah, Resume/HOTS bila relevan, Sumatif normal/remedial sah, Sumatif terbaik tersimpan, status progres, nilai TP dan status Final/Sementara. Tidak ada kalkulasi atau penyimpanan ulang nilai.
- **Progres semester** = TP NonEsensial berstatus tuntas **dan** mempunyai Sumatif selesai sah dibagi seluruh TP NonEsensial nonarsip pada paket. Semester sebelum `semester_mulai` ditandai **Dilewati**, bukan 100%; semester tanpa TP NonEsensial tidak otomatis 100%.
- Panel **Hasil Pra-Semester** mandiri menampilkan KKM, skor normal/remedial sah, nilai terbaik, status; angka `0,00` bukan NULL. Tidak ada Nilai Akhir Semester maupun dependensi sertifikat pada Pra-Semester.
- A10-1 tidak mengubah peran Guru, penerbitan sertifikat, monitoring tingkat madrasah di dashboard akhir, tabel SQL, ataupun histori siswa. Tes otomatis dan uji browser diperlukan sebelum penerimaan.

### A11-1 — Pemeriksaan kelayakan sertifikat sebelum penerbitan (3 November 2026)

- Admin membuka `GET /admin/sertifikat` untuk **memeriksa** per Gen–Mapel–Semester dan melihat seluruh siswa pada paket yang ditugaskan. Ini belum proses penerbitan atau pratinjau PDF final.
- Status **Layak akademik** hanya berarti seluruh TP NonEsensial semester yang tidak diarsipkan telah `tuntas`, masing-masing mempunyai percobaan Sumatif `selesai_sah`, nilai TP final **tidak NULL**, Sumatif tersimpan, serta komponen Resume/HOTS final bila jalur `belajar_wajib`. Skor 0,00 sah; nilai di bawah KKM tidak otomatis menggagalkan TP yang tuntas.
- Semester dilewati dan paket tanpa TP NonEsensial **tidak dinyatakan layak secara otomatis**. TP Esensial tetap tercatat pada daftar ATP/sertifikat tetapi tidak mempunyai nilai dan tidak menjadi syarat aktivitas siswa.
- Periksa identitas Madrasah dari `pengaturan` yang sudah ada; **TTD PNG dan template/riwayat** baru akan diimplementasikan dalam A11-2, sehingga status kelayakan akademik **bukan** izin mengunduh sertifikat. Tidak ada tombol terbit, nomor/QR/PDF atau SQL baru pada A11-1.
- **Pra-Semester tidak diperiksa sama sekali** dalam kelayakan akademik dan tidak masuk data cetak, sesuai keputusan final pemilik. Pemeriksaan izin Admin server-side; tidak ada proses tulis pada halaman ini.

### A11-2 — Implementasi penerbitan PDF/QR dan histori (3 November 2026)

- Admin mengelola aset privat pada `/admin/sertifikat/aset`: **background JPG/PNG A4 landscape** (rasio 297:210 ±1,5%, min. 1188×840 px) serta **TTD PNG wajib**, masing-masing maksimal 8 MB. Berkas di-decode/re-encode, checksum dan versi lama dipertahankan. Hanya satu pointer aktif global per jenis, template fallback bila background belum diunggah. **Pratinjau PDF dengan data contoh wajib sebelum aktivasi background**, bukan data siswa asli.
- Jika siswa/paket memenuhi syarat A11-1 dan identitas/TTD siap, Admin menggunakan **POST** untuk penerbitan. Penerbitan berulang atas isi sama harus **idempoten**; bukan membuat nomor/QR baru. Setiap penerbitan menyimpan snapshot identitas madrasah, NISN privat, BAB/semua TP dan nilai Final hanya NonEsensial, versi aset, SHA-256, PDF privat, nomor dan token QR acak. Tidak ada nilai Pra-Semester atau Nilai Akhir Semester.
- **Revisi hanya satu siswa/paket tiap tindakan**, dengan alasan 5–225 karakter. Jika isi cetak benar-benar berubah (nilai TP/biodata/identitas penandatangan/TTD), nomor, QR, PDF dan versi baru diterbitkan. Background historis tetap sama; TTD dari pointer aktif terbaru. Versi lama menjadi `digantikan`, tidak dihapus. Perubahan internal yang tidak mengubah isi cetak tidak mengganti PDF.
- Tabel `sertifikat_aktif` memastikan satu pointer aktif bagi pasangan siswa–paket. Semua mutasi diaudit. PDF yang telah disimpan tidak ditimpa; berkas gagal sebelum commit boleh dibersihkan, bukan berkas yang sudah tercatat historis.
- Siswa melihat `/siswa/sertifikat` dan mengunduh PDF berizin miliknya, Admin berhak unduh untuk audit. Verifikasi publik `/verifikasi/{kode}` menampilkan whitelist nomor, nama, mapel, semester, nilai TP dan status berlaku/digantikan (serta tautan versi aktif). **Tidak ada NISN, TTD, kontak, alasan revisi, token sesi atau PDF** pada laman publik.
- URL verifikasi dalam QR mengikuti `site_url`: di XAMPP beralamat localhost dan hanya cocok untuk uji lokal; untuk dokumen nyata sekolah domain **HTTPS permanen** wajib disiapkan terlebih dahulu (Master 15). Tidak mengklaim QR sebagai TTE tersertifikasi.
- Tahap ini mensyaratkan patch SQL additive `V4-0011-SERTIFIKAT-ASSET-DAN-VERSI.sql`, bukan Migration/Seeder. Audit dump privat v4 (15) dilakukan; pengujian MariaDB aktif dan XAMPP tetap dibutuhkan untuk PASS.

### A11-3 — Koreksi desain hasil cetak A4 dari uji XAMPP (3 November 2026)

- Sumber QA: PDF sertifikat demo A11-2 yang berhasil diterbitkan di XAMPP, satu halaman A4 landscape, berisi dua BAB/empat TP, QR dan TTD. Ditemukan ruang kosong besar di bawah bingkai karena `SertifikatPdfService` membuat kartu setinggi konten saja; ukuran teks cetak juga relatif kecil.
- Renderer cetak menggunakan tinggi halaman penuh pada sertifikat pendek **maksimum 4 TP dan 3 BAB**, dengan tipografi fisik satuan `pt`, ruang identitas siswa lebih jelas, tabel TP/BAB lebih terbaca, dan QR/TTD ditempatkan seimbang dekat bawah bingkai.
- Untuk jumlah TP lebih banyak, gunakan footer dalam alur halaman normal, bukan footer absolut yang dapat menumpuk tabel. Jika Dompdf masih membentuk halaman ekstra pada mode pendek, renderer mengulang menggunakan mode alur normal. Semua TP tidak boleh disembunyikan untuk memaksakan satu halaman.
- Pratinjau template bawaan kini menggunakan **dua BAB/empat TP sintetis**, tidak menggunakan data siswa atau TTD nyata. Perubahan ini **hanya berlaku pada PDF yang dirender setelah kode diperbarui**; PDF yang sudah diterbitkan tidak boleh dihapus/ditimpa dan tidak otomatis menghasilkan nomor/QR baru.
- Koreksi estetika/layout tidak mengubah nilai TP, syarat kelayakan, versi/QR lama, aturan revisi, ataupun pemisahan Pra-Semester. Tetap tidak ada Nilai Akhir Semester.
