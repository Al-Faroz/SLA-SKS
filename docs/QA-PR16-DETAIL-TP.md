# QA PR #16 — Rincian TP, redaksi draf, daftar Materi

## Lingkup
- Guru dapat membuka rincian TP melalui Peta ATP; G1 diverifikasi lagi di Service berdasarkan Gen–Mapel pemilik paket dari server.
- Halaman menampilkan kode/urutan/status, rumusan/indikator, dan Materi/Submateri menurut `urutan` beserta jenis dan status aktif.
- Hanya TP `draf` menyediakan formulir edit judul, rumusan dan indikator. TP `siap` menampilkan data baca-saja.
- POST membutuhkan sesi Guru dan CSRF global. Service memeriksa G1, status draf, `struktur_versi`, validasi panjang, serta menjalankan transaksi dan rollback jika gagal.
- **Belum termasuk** pembuatan paket baru, penambahan/penyuntingan Materi, koneksi media, CP, readiness TP Siap, publikasi, dan impor Excel ATP.

## Pengujian otomatis
[QA Rincian TP run 39914811055](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39914811055) pada PHP 8.2 + MySQL 8 fixture sintetis: lint PHP **PASS**, integrasi Service **9 tests / 13 assertions PASS**. Kasus: baca TP berizin, tolak Guru lain/Admin/ID salah, simpan judul-rumusan-indikator draf, tolak perubahan memakai versi lama, tolak TP Siap, dan validasi input kosong.

[QA regresi Peta ATP run 39914811041](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39914811041) **PASS** seluruh job (unit, CI4, HTTP, SQL smoke) setelah perubahan PR #16.

**Privasi:** fixture memakai nama sintetis saja. Tidak menyentuh `.env`, database `sla_sks_v2`/`sla_sks_v3`, atau data siswa nyata. Tidak ada file SQL update skema yang diperlukan.

## Uji XAMPP setelah review
1. Jangan pull branch ini sebelum diinstruksikan; PR masih Draft.
2. Buka `/index.php/guru/peta-atp`, klik judul TP lalu periksa rincian dan daftar Materi. Data yang kosong harus ditangani tanpa error.
3. Pada TP draf, ubah redaksi dan simpan; muat ulang, pastikan versi dan isi terbaru. Pengujian ini **mengubah data uji**; lakukan pada TP sintetis/nonproduksi saja dan backup dahulu.
4. Buka TP Siap: formulir penyuntingan tidak tampil.
5. Uji langsung URL TP Gen lain sebagai Guru: akses ditolak.
6. Periksa desktop dan HP serta pesan sukses/gagal; jangan menguji penghapusan atau publikasi karena tidak tersedia.

**Belum diuji** HTTP POST sesungguhnya di XAMPP, kompatibilitas MariaDB skema lengkap, dan interaksi browser dengan CSRF, sehingga PR ini tetap Draft.

## Pengujian browser XAMPP oleh pemilik — 23 Oktober 2026

Pemilik mengonfirmasi pengujian pada localhost dengan database `sla_sks_v3` sebagai berikut: **1. Rincian TP LULUS; 2. Daftar Materi LULUS; 3. Form TP draf LULUS; 4. Simpan perubahan LULUS; 5. Validasi form LULUS; 6. Hak akses LULUS; 7. Tampilan LULUS**.

Ini merupakan laporan uji manual pemilik, bukan pengamatan browser langsung oleh CI. Keberhasilan menyimpan redaksi pada TP draf telah dikonfirmasi pengguna. Tidak ada perubahan struktur SQL atau `.env` melalui PR #16. Setelah pemeriksaan CI terakhir, PR dapat ditandai Ready for review tetapi **tidak boleh digabung sebelum izin merge eksplisit**.
