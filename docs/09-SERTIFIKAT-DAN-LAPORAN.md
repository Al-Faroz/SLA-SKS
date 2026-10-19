# 09 — Sertifikat, Revisi, Verifikasi dan Laporan

## 1. Syarat penerbitan sertifikat pertama
`SertifikatService::periksaPenerbitan()` menerbitkan satu sertifikat per **siswa + paket semester** apabila semua BAB yang direncanakan telah Siap/tuntas, semua TP tuntas dan seluruh resume/tugas **yang wajib di jalur belajar** telah dinilai, tanpa nilai sementara. TP diagnostik tuntas tidak mewajibkan kiriman dari jalur yang dilewati. Semester `Dilewati` tidak mendapat sertifikat. Nilai akhir = rata-rata nilai TP, pembulatan tampilan konsisten sesuai template. Penerbitan terkoordinasi transaksi dan idempoten.

## 2. Isi PDF dan template
| Komponen | Aturan |
|---|---|
| Kertas | **A4 landscape** 297×210 mm |
| Background | **JPG/PNG unggahan Admin**; tepat satu template global aktif, versi sebelumnya tidak dihapus |
| Fallback | Jika belum ada background unggahan, gunakan **template bawaan** berupa desain sederhana aplikasi |
| Identitas | Logo/nama madrasah sesuai snapshot, **nama siswa dan NISN**, mapel, semester, nilai akhir |
| Penerbitan | Kota, tanggal, nomor unik format `SLA/{tahun}/{urut 4 digit}` |
| Penandatangan | Nama/NIP dan **gambar tanda tangan PNG** kepala madrasah yang aktif ketika terbit |
| Verifikasi | QR dan kode verifikasi unik 32 karakter heksadesimal dari generator kriptografis |
| Sumber render | Data snapshot sertifikat, **bukan data siswa/profil sekolah yang terus berubah** |

**Tanda tangan PNG wajib.** Bila gambar belum tersedia/valid, sistem menahan penerbitan, menampilkan peringatan Admin dan keterangan pada halaman siswa. PNG yang ditempel **bukan TTE tersertifikasi/kriptografis**; jangan memberi cap/klaim seolah-olah memakai sertifikat digital otoritas eksternal.

**Admin → Template Sertifikat:** upload dan validasi JPG/PNG saat **unggah** (MIME sungguhan, ukuran file, orientasi, rasio dan dimensi), pilih preset penempatan terbatas, lihat pratinjau A4, `Aktifkan`. Pengaturan rasio/ukuran harus divalidasi sebelum disimpan; tidak ada editor bebas. Template dan berkas digunakan dalam bentuk immutable. Tidak otomatis memaksa resolusi gambar materi ke background sertifikat.

## 3. Versi template dan tanda tangan
- Saat pertama terbit, simpan `template_sertifikat_versi_id` atau penanda `template_bawaan`, aset logo yang digunakan, `ttd_png_versi_id`, dan snapshot nama/NIP penandatangan.
- Jika template global berubah kemudian, sertifikat terbit **tidak diubah**.
- Saat revisi nilai atau identitas, **background tetap template versi sertifikat sebelumnya**, tetapi **nama/NIP penandatangan dan gambar tanda tangan memakai data/PNG terbaru yang aktif saat revisi** (pilihan pemilik K11=B).
- Jika background historis tidak tersedia, jangan diam-diam gunakan background terbaru; beri galat dan lakukan pemulihan dari arsip/backup. Untuk fallback bawaan, gunakan versi desain bawaan yang sama.

## 4. Revisi karena koreksi nilai — AB-39a
1. Usulan koreksi akademik sah diputus oleh Admin sesuai `08-PENILAIAN.md`; tidak diperlukan persetujuan penerbitan ulang tambahan.
2. Hitung calon nilai TP dan rata-rata semester tanpa mengubah data resmi; bandingkan **seluruh isi akademik yang benar-benar tercetak** (format angka, pembulatan, dsb.) dengan PDF/snapshot berlaku. Nomor/QR/tanggal baru **bukan** alasan memaksa revisi.
3. Jika konten cetak tak berubah: terapkan koreksi nilai dan audit, sertifikat dan PDF lama tetap `berlaku`.
4. Jika konten cetak berubah: siapkan snapshot, nomor/QR baru, background versi lama, **TTD/penandatangan aktif terbaru**, lalu render PDF kandidat; periksa berkas/QR/checksum.
5. Aktivasi dalam transaksi: terapkan nilai dan agregat, ubah sertifikat awal `berlaku → digantikan`, aktifkan revisi baru, ubah pointer `sertifikat_aktif`, audit dan notifikasi setelah commit. **Jangan membiarkan dua sertifikat berlaku.**
6. Bila render atau aktivasi gagal: pertahankan sertifikat/nilai lama, catat job gagal dapat diulang, jangan publish kandidat. Retry idempoten.

## 5. Koreksi biodata — AB-39b
- **Admin mengoreksi nama/NISN siswa langsung melalui menu biodata**, tanpa memaksa dua Admin atau workflow persetujuan untuk pekerjaan administrasi normal. Simpan sebelum/sesudah, alasan, identitas Admin, waktu dan sumber pendukung bila digunakan.
- NISN perlu cek keunikan, format dan hubungan dengan username login. Perubahan NISN memperbarui username secara aman tanpa membuat `siswa_id` baru/menghapus progres/attempt/sertifikat.
- Sertifikat yang isi cetaknya terdampak diganti **satu per satu** sesuai pilihan pemilik, dari panel sertifikat siswa/mapel/semester; tidak otomatis *batch* seluruh sertifikat siswa. Setiap penggantian baru punya nomor dan QR baru; sebelumnya `digantikan`, tetap tersimpan.
- Pada setiap penggantian gunakan background historis sertifikat itu dan penandatangan aktif terbaru. Jika file gagal, tidak boleh menyebabkan dua sertifikat berlaku.
- Kesalahan identitas penerbit (nama/NIP sekolah) dapat diperbaiki Admin melalui layanan revisi administratif berversi, tanpa menghapus sertifikat lama.

## 6. Tidak ada pencabutan dan penghapusan
Hanya status publik **`berlaku`** dan **`digantikan`**; status internal `menyiapkan_revisi` tidak menjadi PDF sah. Tidak membuat tombol/API `cabut`. Data siswa dan seluruh versi sertifikat tidak memiliki penghapusan permanen pada fitur aplikasi. Akun siswa lulusan dapat memperoleh akses **arsip baca-saja**; petugas memantau tanpa memutasi data akademik.

## 7. Konflik ketuntasan setelah koreksi soal
Koreksi soal/kunci yang sah dapat menghitung ulang TP dari tuntas menjadi belum tuntas. Sistem harus mencatat nilai/status baru dan kebutuhan siswa melanjutkan belajar. Karena **pencabutan sertifikat dilarang**, jangan secara diam-diam menghapus atau membatalkan sertifikat lama. Tampilan internal memperlihatkan kondisi akademik yang memerlukan penyelesaian ulang, dan tidak membuat sertifikat baru yang menyatakan ketuntasan sampai prasyarat terpenuhi. Riwayat verifikasi lama tetap jujur dan tersedia. Implementasi peristiwa ini wajib diuji end-to-end sebelum rilis.

## 8. Verifikasi publik
`GET /verifikasi/{kode}` tanpa login, token acak sulit ditebak, rate limit. Tampilkan **nomor, nama, mapel, semester, nilai akhir tercetak pada versi yang ditanyakan, tanggal terbit, status, dan tautan versi terbaru bila digantikan**. **Jangan tampilkan NISN, HP, tanggal lahir, alasan koreksi atau audit.** QR lama tetap bisa diakses dengan label `Digantikan — bukan versi terbaru`. Kode tidak ada: `Sertifikat tidak ditemukan`. Akses PDF asli hanya untuk pemilik dan peran berwenang; halaman publik bukan endpoint download PDF.

## 9. Daftar laporan resmi
| Kode | Laporan | Format/isi |
|---|---|---|
| R1 | Rapor satu siswa/mapel/semester | PDF + Excel; TP, cara tuntas, resume, HOTS, sumatif, nilai TP dan nilai akhir |
| R2 | Rekap nilai mapel | Excel; siswa × TP, rata-rata, status sementara |
| R3 | Progres siswa | Excel; status TP, jalur tuntas, tanggal, BAB menunggu |
| R4 | Log ujian | Excel; status dan insiden, tanpa skor pada percobaan batal |
| R5 | Sertifikat | Excel; nomor/versi/status, siswa, mapel, nilai, tanggal |
| R6 | Kesiapan BAB | Excel/PDF; BAB siap/rencana, kekurangan materi, target dan kebutuhan Gen |
| R7 | Ringkasan progres program | Excel/PDF sesuai dasbor |
| R8 | Aktivitas penilaian guru | Excel; antrean, jumlah/umur item, durasi penilaian |
| R9 | Daftar akun | Excel; identitas/status/role/login, **tanpa password** |

Rapor siswa dapat dilihat siswa pemilik; Guru hanya mapelnya; Admin seluruhnya; Pimpinan baca-saja. Excel dinetralkan dari formula injection (`=`, `+`, `-`, `@`), nilai sementara **wajib dilabel**, laporan besar dapat dibatasi filter dengan pesan jelas. PDF laporan A4 portrait kecuali sertifikat A4 landscape.
