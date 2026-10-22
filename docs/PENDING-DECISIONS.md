# Status keputusan SLA-SKS Master v2.4 — 22 Oktober 2026

**Keputusan arsitektur dan ruang lingkup telah final menurut pemilik.** Tidak perlu menanyakan ulang alur Diagnostik/Resume/HOTS/Sumatif, perubahan istilah BAB, lima opsi PG, media Google Drive, atau enam revisi UI. Tidak memaksa prosedur approval/dokumen administratif yang tak diminta.

## Keputusan terkunci
1. **CP → ATP (urutan TP) → TP → Materi/Submateri**, satu TP banyak Materi, tanpa BAB operasional.
2. Guru G1 per Gen–Mapel menyusun Excel peta ATP 6 semester; data identitas/CP relevan otomatis unduhan. Sumber media serta Resume/HOTS pada setiap Materi dikelola di aplikasi.
3. Satu template Word PG lima pilihan A–E satu kunci untuk Diagnostik/Sumatif, dengan OOXML kaya (gambar, tabel bersarang, OMML) dan Drive untuk audio/video; generator mengatur jumlah soal sesuai bank.
4. Diagnostik SKS percepatan dua tahun/ketuntasan dan nilai madrasah dipertahankan persis; skor HOTS beberapa Materi menjadi komponen rata-rata HOTS TP, bobot 20/30/50 tetap.
5. Admin CP tanpa kewajiban metadata resmi berulang; versi/sumber tetap dapat ditelusuri. KBC terintegrasi sesuai relevansi.
6. UI enam halaman dan pagination/export/logo/favicon/profil mengikuti dokumen 12/13.
7. Database **lokal pengembangan** nanti diganti melalui prosedur dump lama→analisis→pilih data perlu→buat dump final→backup→pemilik hapus tabel lokal→pemilik impor. Jangan menjalankan operasi destruktif di produksi.

## Data instalasi yang belum diberikan (bukan keputusan produk)
- Dump SQL lokal terkini, versi PHP/MySQL/MariaDB nyata, data identitas sekolah/Guru/Gen/CP yang resmi.
- Aset logo dan TTD final, host/domain hosting, akun privat; jangan memasukkan kredensial atau data siswa nyata ke repo.

## Implementasi belum selesai
- PR #13 menggunakan skema lama; jangan merge. Setelah dokumen v2.4 disetujui, kerjakan parser/importer Excel dan Word, pengalihan skema database, layanan TP/Materi, dan UI secara bertahap via PR kode.
- Validasi akhir harus benar-benar dilaksanakan di XAMPP pengguna. Persyaratan yang belum terimplementasi bukan fitur selesai hanya karena dokumen telah diperbarui.
