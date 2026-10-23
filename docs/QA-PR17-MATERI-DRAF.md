# QA PR #17 — Pengelolaan Materi/Submateri Draf

## Lingkup
- Pada rincian TP berstatus **draf**, Guru dapat menambah Materi di akhir urutan, menyunting isi Materi, serta memindahkan urutan naik/turun.
- Field: judul, jenis media (PDF, gambar, audio atau YouTube), URL YouTube HTTPS (jika jenis YouTube), waktu belajar minimal detik, petunjuk Resume dan petunjuk HOTS.
- Semua POST menggunakan route `auth:guru` dan filter CSRF global. Service memvalidasi kepemilikan Gen–Mapel dari TP/paket di server, status TP `draf`, serta `struktur_versi`. Lock TP `FOR UPDATE` dan transaksi mencegah konflik perubahan serentak.
- Baris Materi baru mempunyai `row_uuid` acak, `urutan` berurutan, `aktif=0`; penambahan tidak menerbitkan atau mengaktifkan Materi. Swap urutan memakai urutan 0 hanya **sementara di dalam transaksi** karena skema memiliki UNIQUE(`tp_id`, `urutan`).
- Materi dengan `berkas_id` tidak boleh mengganti jenis media melalui editor sederhana ini.

## Batas implementasi
- **Belum ada endpoint unggah/pengelolaan file** PDF/audio/gambar; Materi baru berjenis berkas belum siap ditayangkan. Jangan menggunakan data tersebut sebagai Materi Siap.
- Belum ada aksi aktivasi, penghapusan, preview media, publikasi TP, CP, impor ATP Excel, dan integrasi tugas HOTS/Resume yang dijalankan siswa.
- Tidak ada pembaruan SQL untuk database pengguna. Fixture anonim `tests/fixtures/peta_atp_mysql.sql` hanya bagi QA.
- UI dan POST browser terhadap `sla_sks_v3` **belum diuji**. Tidak ada klaim aplikasi siap produksi.

## Hasil pengujian CI
- [QA Materi Draf #39918233217](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39918233217) **PASS**: PHP 8.2 lint, 9 tes Materi (15 assertions) + 9 tes regresi rincian TP (13 assertions). Menguji tambah, edit, urut pada constraint unik, G1, TP Siap, konflik versi, input URL tidak sah, ID Materi dari TP lain.
- [Regresi Rincian TP #39918233065](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39918233065) **PASS**.
- [Regresi Peta ATP #39918233173](https://github.com/Al-Faroz/SLA-SKS/actions/runs/39918233173) **PASS**.

## Checklist uji manual saat PR siap dites
1. Backup database uji `sla_sks_v3`; **uji hanya TP draf sintetis/nonproduksi**. Jangan mengubah TP Siap atau Materi milik siswa.
2. Login Guru → Peta ATP → klik judul TP draf. Periksa formulir **Tambah Materi/Submateri** dan daftar Materi.
3. Tambah Materi tipe YouTube dengan URL HTTPS sah, judul, Resume dan HOTS. Pastikan muncul di urutan terakhir, **Nonaktif**, tanpa error.
4. Sunting Materi itu dan pastikan perubahan judul, petunjuk, URL, waktu belajar tersimpan.
5. Tambah satu lagi Materi, uji tombol **Naik/Turun**. Pastikan urutannya saling bertukar tanpa error unique-key.
6. Uji URL palsu `https://youtube.com.example.org/watch?v=abc`, judul/petunjuk kosong dan tab lama (konflik versi) — harus menolak tanpa mengganti data.
7. Coba membuka/edit Materi TP Gen lain, akses Guru lain, atau TP Siap — harus ditolak/dibatasi.
8. Periksa tampilan desktop dan HP 360px, pesan status, keyboard dan tautan kembali.

**PR tetap Draft sampai uji localhost dan review.**
