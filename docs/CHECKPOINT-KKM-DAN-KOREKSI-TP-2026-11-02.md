# Checkpoint — KKM diwariskan dan koreksi TP Siap (2 November 2026)

## Keputusan pemilik
- **Satu KKM per TP NonEsensial** digunakan pada Diagnostik dan Sumatif; keputusan memisahkan KKM **tidak dipakai**.
- KKM efektif = `tp.kkm_khusus` bila Guru menetapkan angka berbeda; selain itu `pengaturan.kkm_default` Admin. Guru dapat mengubah isian berkali-kali **selama draf**, dan tidak diwajibkan menyimpan angka default Admin sebagai khusus.
- TP Siap **tidak kembali ke draf** dan tidak bisa diubah lewat edit normal. Koreksi Materi, soal, KKM, dan rincian TP harus melalui permohonan Guru → keputusan Admin → versi konten TP baru, menjaga sejarah dan siswa yang sudah mulai (Master 02, 03, 05, 08, 10, 11, 14, 16).
- Koreksi A/B/C sesuai kategori resmi di Master; kategori D dilarang. Larangan self-approval memakai `pengguna_id` yang sama, walaupun berganti role.

## Audit dump SQL lokal privat
- Sumber: dump `sla_sks_v4` diterima pada sesi 2-11-2026 (MariaDB 10.4.32). **Dump tidak dimasukkan GitHub.**
- `pengaturan.kkm_default` ada tetapi masih **NULL**; `tp.kkm` historis DEFAULT 75 **bukan** default Admin yang disetujui. Contoh angka **80** di halaman Admin hanya contoh awal; tidak berlaku sampai Admin menyimpan.
- TP #23 berstatus **siap**, KKM khusus dan efektif tersimpan 80, snapshot TP versi 1 tersedia, 1 Materi dan 40 soal aktif; tidak ada progres/percobaan baru TP #23 pada dump yang diaudit. **Jangan reset, reimport, atau membuka kembali TP #23 secara manual.**
- Skema berisi `versi_konten_tp` dengan `snapshot_json`, `checksum`, `nomor_versi` dan `progres_tp.versi_konten_tp_id`. **Belum ada tabel permohonan koreksi TP**: mekanisme persetujuan memerlukan SQL incremental dan implementasi yang benar-benar utuh.
- Versi skema terakhir `V4-0005-NAMA-ATP-50`. Tidak ada patch SQL untuk perbaikan UI/pewarisan KKM tahap ini.

## Tahap 1: KKM default dan indikasi status (PR tahap ini)
- Ketika Admin belum mengisi default, halaman `admin/kkm` menampilkan contoh 80 secara jelas **belum aktif**; Admin menentukan dan menyimpan nilai sebenarnya.
- Guru yang membuka dua tab Bank Soal draf melihat KKM efektif dari default Admin jika tersedia; tidak menekan Simpan pun pemeriksaan kesiapan membaca nilai tersebut.
- Simpan tanpa mengubah angka Admin tidak menulis `kkm_khusus` dan tidak menaikkan `struktur_versi`; angka berbeda menjadi KKM khusus. KKM bisa diubah lagi selama status draf.
- TP Siap menampilkan **KKM snapshot yang terpin** dari `tp.kkm`, bukan nilai Admin yang mungkin telah berubah setelah publikasi.
- Tidak ada perubahan SQL, file PDF, Bank Word, soal, `.env`, atau data TP Siap.

## Tahap 2: Mekanisme koreksi TP Siap (belum diimplementasikan)
Pekerjaan berikut **tidak boleh dianggap selesai** hanya dengan tombol atau mengubah status `siap` menjadi `draf`.

1. **Patch SQL incremental V4-0006** (setelah backup dan verifikasi ulang dump berjalan saat eksekusi): tabel permohonan koreksi berisi TP, versi dasar snapshot, Guru pemohon, kategori yang sah A/B/C, alasan, status, perubahan yang diajukan, Admin pemutus, tanggal dan audit; indeks/FK sesuai skema nyata. Jangan menambah kategori D sebagai operasi yang diizinkan.
2. **Ruang penyuntingan salinan koreksi**: Guru G1 memilih area KKM, identitas TP, Materi/Resume/HOTS, atau bank Diagnostik/Sumatif. Perubahan di ruang koreksi **tidak boleh UPDATE/DELETE** konten/snapshot TP Siap atau sumber milik percobaan lama. Semua data staging harus privat; berkas lama tidak dihapus.
3. **Tinjauan dan keputusan Admin**: tampilan diff sebelum–sesudah, alasan wajib, pemeriksaan akun penentu berbeda dari pemohon, izin dan versi objek. Tolak tanpa mutasi konten; setujui dalam transaksi setelah mengecek ulang kesiapan dan konflik revisi.
4. **Publikasi snapshot baru**: `nomor_versi` bertambah, salin/pin KKM efektif dan isi Materi/soal ke snapshot baru; siswa yang sudah memulai tetap pada `progres_tp.versi_konten_tp_id` historis. Skor/percobaan sah dan sertifikat lama tidak diubah otomatis; koreksi soal/kunci yang berpengaruh pada nilai mengikuti Master 08 dan 09.
5. **E2E wajib**: dua permohonan bersamaan, usulan sendiri disetujui akun Admin+Guru sama harus ditolak, Gen lain ditolak, dokumen Word dan berkas historis aman, siswa sedang mengerjakan memakai snapshot lama, perubahan KKM tidak mengubah remedial lama, kegagalan transaksional rollback tanpa korupsi.

**Catatan batasan:** Master v2.5 menyebut kategori A/B/C dan D, tetapi tidak merinci pemetaan semua area perubahan ke huruf kategori pada dokumen yang diperiksa. Implementasi kategori tidak boleh mengarang definisi baru; tentukan pemetaan konsisten pada domain bisnis sebelum menerbitkan route persetujuan. Jangan membuat tombol 'Koreksi TP' yang tampak aktif tanpa jalur penyimpanan/otorisasi/persetujuan berfungsi.

## Pemeriksaan setelah pull
1. Admin buka `/admin/kkm`; bila belum diset, lihat contoh 80 **belum berlaku**, isi dan simpan KKM sesuai kebijakan sekolah.
2. Pada TP **draf** baru, buka Diagnostik dan Sumatif; isian menampilkan angka Admin, tidak memerlukan simpan Guru. Tekan Simpan tanpa mengubah: tetap `kkm_khusus=NULL`, `struktur_versi` tidak bertambah.
3. Ubah ke angka berbeda: KKM khusus aktif pada kedua tab; ulangi perubahan sebelum Siap. Ketik angka Admin lagi: kembali mengikuti default.
4. Pada TP #23 **Siap**, KKM 80 tetap terlihat sebagai snapshot walaupun Admin mengganti default; perubahan langsung ditolak.
5. Pastikan soal Word, Materi, dan alur TP Siap yang sudah diuji tetap bekerja.
