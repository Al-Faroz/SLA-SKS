# 02 — Aturan Bisnis Lintas Modul

> Nomor AB yang sudah digunakan di Master v2.1 dipertahankan secara konseptual. Aturan khusus mengikuti dokumen domain, bukan dirangkum ulang secara berbeda di sini.

## 1. Invarian umum
1. **Jam server** menjadi sumber kebenaran ujian, penugasan waktu, transaksi dan log; zona Asia/Jakarta.
2. **Tidak ada penghapusan fisik** atas data siswa, nilai, sertifikat, TP Siap, versi CP/TP dan artefak historis. Penonaktifan/arsip berbeda dengan delete.
3. **Satu G1 aktif per Gen–Mapel** untuk seluruh semester Gen tersebut, bukan Guru global per mapel. Setiap Gen mengelola paket/TP/soal/materi sendiri. `created_by` adalah catatan sejarah, bukan sumber izin. Penggantian Guru memindahkan hak operasional hanya untuk Gen–Mapel yang dipilih, tanpa menimpa identitas penilai lama.
4. **Admin boleh mengelola biodata, referensi CP dan pengaturan**, tetapi tidak langsung mengetik nilai akademik/isi soal/materi milik Guru. Persetujuan koreksi guru dilakukan atas permintaan tersimpan.
5. **Admin tunggal diizinkan.** Larangan setuju permohonan sendiri berlaku ketika akun yang sama berperan sebagai *pengusul Guru* dan *pemutus Admin* untuk permohonan akademik; jangan memaksakan admin kedua untuk pembetulan biodata yang menjadi tugas Admin langsung.
6. Semua keputusan penting diaudit (pelaku, peran aktif, objek, sebelum/sesudah, alasan, waktu, versi). Audit tidak berarti menambah persetujuan manual yang tidak diwajibkan.
7. Tidak ada penghapusan nilai/sertifikat atau perubahan PDF sertifikat terdahulu. Koreksi menghasilkan riwayat atau pengganti, bukan menimpa sejarah.
8. TP Siap tidak berubah dari jalur edit biasa, tidak ditarik kembali ke draf, dan tidak mengubah progres siswa yang sudah dimulai.
9. Setiap perubahan yang menyentuh banyak tabel memakai transaksi database dan pemeriksaan ID/otorisasi di service. Penerbitan sertifikat mengunci satu sertifikat berlaku per siswa/paket.
10. Teks input akademik disimpan sebagai teks biasa; tampilkan dengan escaping. Tidak membiarkan editor HTML bebas.

## 2. Batas peran dan persetujuan
- Staf dapat berperan Admin+Guru, Admin+Pimpinan, Guru+Pimpinan; hak akses hanya peran aktif.
- Pemilik aplikasi menetapkan **satu Admin cukup untuk operasional**. Manajemen biodata oleh Admin tidak memakai formulir pengajuan dan persetujuan dua Admin.
- Permohonan koreksi TP Siap atau koreksi nilai yang **diajukan sebagai Guru** memerlukan keputusan Admin; satu `pengguna_id` tidak boleh menyetujui permohonannya sendiri sekalipun berpindah role. Bila benar-benar akun yang sama, sistem menampilkan peringatan keterbatasan persetujuan tanpa mengubah kewenangan administrasi normal.
- Penelaahan CP dilakukan **langsung oleh Admin** dan tidak memakai reviewer `cp_review` terpisah. Jika usulan CP diajukan Guru yang juga Admin, aturan larangan self-approval akademik tetap diperiksa.

### Administrasi akun dan profil v2.4
- Dua menu Admin, **Pengelola** dan **Siswa**, menggunakan satu sistem `pengguna` dan peran aktif yang sudah ada. Akun siswa tidak merangkap role staf.
- Keduanya menyediakan tambah/ubah/nonaktif, **import/export XLSX**, serta tombol reset sandi per akun. Ekspor tidak memuat hash, sandi, token, sesi, atau data rahasia.
- Impor memerlukan template, pemeriksaan kolom/role/Gen/NISN/duplikasi, pratinjau galat per baris, konfirmasi, transaksi dan idempotensi. Baris yang tak ada di file tidak dianggap permintaan menghapus data.
- Reset sandi oleh Admin menghasilkan sandi sementara **acak**, ditampilkan satu kali melalui alur aman, wajib ganti pada login berikutnya, sesi lama dibatalkan, dan audit tanpa merekam isi sandi.
- Staf hanya dapat mengubah foto, kontak pribadi dan sandi sendiri. Nama resmi/NIP/peran/penugasan dan biodata siswa dikelola Admin; siswa boleh melihat profil Guru aktif pada Gen-nya (nama, foto disetujui, mapel), bukan kontak pribadi/NIP.
- Perbaikan NISN dan username login siswa harus atomik dan menjaga `siswa_id`, progres dan histori.

## 3. Keputusan final tentang pembelajaran
- Semester sebelum semester mulai siswa tetap Dilewati. ATP per Gen–Mapel berisi BAB dan TP berurutan, disiapkan Guru di aplikasi atau impor Excel.
- **Esensial:** tidak ada Materi, diagnostik, Resume, HOTS, Sumatif, atau nilai. **NonEsensial:** tepat satu Materi dan alur Diagnostik → (bila gagal: Materi → Resume → HOTS) → Sumatif untuk semua siswa.
- Diagnostik min 10 adalah gate bukan nilai TP. Sumatif min 30. Nilai gagal Diagnostik 20/20/60 (Resume/HOTS/Sumatif), lulus 100% Sumatif. Admin menentukan KKM default dan Guru dapat menetapkan khusus.
- Sumatif < KKM memperoleh satu remedial; gunakan nilai tertinggi. Setelah aktivitas dan kesempatan sah terkait selesai, **TP selesai meskipun nilai masih < KKM**. Progres semester hanya TP NonEsensial.
- Pra-Semester dijadwalkan tanggal/jam oleh Admin secara serentak setelah semua TP NonEsensial selesai; nilai tersendiri. **Tidak ada nilai akhir semester.**
- Resume/HOTS Simpan Draf manual, Kirim Final, penilaian Guru. TP Siap historis/snapshot tetap dilindungi.

## 4. Keputusan final tentang ujian
- Profil Ketat sama untuk Diagnostik, Sumatif dan Pra-Semester. PG lima pilihan A–E dengan satu kunci; aturan fullscreen, jeda/insiden, koneksi di dokumen 07.
- Diagnostik minimal 10, Sumatif minimal 30, Pra-Semester minimal 30 soal gabungan seluruh BAB semester.
- Sumatif dan Pra-Semester memberikan **satu remedial jika skor < KKM**, mengambil nilai tertinggi. Percobaan batal tanpa skor tidak menghabiskan kesempatan sah; ulang memerlukan keputusan Guru.
- Admin mengatur jendela Pra-Semester serentak; akses siswa tanpa seluruh TP NonEsensial selesai ditolak.

## 5. Keputusan final tentang sertifikat
- Daftar nilai sertifikat berisi seluruh TP: Esensial tanpa nilai, NonEsensial dengan nilai; **tidak ada rata-rata nilai akhir semester**.
- Syarat apakah Pra-Semester wajib selesai sebelum sertifikat terbit masih **menunggu keputusan**, tidak boleh ditetapkan otomatis.
- Sertifikat A4 landscape, template background global berversi, TTD PNG wajib, NISN hanya PDF privat, QR publik aman. Koreksi cetakan berubah menghasilkan sertifikat pengganti nomor/QR baru, lama Digantikan; cetakan sama cukup audit. Tidak ada pencabutan/hapus.

## 6. Status standar
| Domain | Nilai status | Makna |
|---|---|---|
| Pengguna | aktif/nonaktif/terkunci | Bisa login, tidak bisa login, ditolak sementara karena kegagalan login |
| TP | draf/siap | Bisa diedit atau immutable lewat jalur biasa |
| Progres TP | belum_mulai/diagnostik/belajar/sumatif/perlu_bantuan/tuntas | Proses siswa; transisi harus sesuai prasyarat |
| Ujian | berjalan/jeda_fullscreen/jeda_koneksi/selesai_sah/dibatalkan_menunggu_guru | Skor hanya pada selesai_sah |
| Penilaian | belum_dinilai/dinilai/koreksi_diajukan/koreksi_diterapkan | Riwayat menyimpan perubahan |
| Sertifikat | menyiapkan_revisi/berlaku/digantikan | Tidak ada `dicabut` |
| CP/template | draf/aktif/diarsipkan | Versi aktif tunggal sesuai jenis referensi |
| Pengumuman | draf/terbit/arsip | Global untuk pengguna yang login |

## 7. Prinsip pembacaan dokumen
Jangan memperkenalkan menu, tabel, role, atau syarat persetujuan baru hanya karena contoh sebelumnya pernah menyebutnya. Persyaratan verifikasi harus **cukup aman, sederhana, dan sesuai keputusan akhir**. Rincian operasional hosting dan data pengaturan sekolah diisi melalui UI saat pemasangan.

## 8. Keputusan lintas modul v2.5
1. CP operasional satu Mapel → Semester → BAB → TP, nomor BAB berlanjut antardua semester kelas sama. Nama singkat 50, deskripsi 225, kode `KODEMAPEL.KELAS.BAB.TP`.
2. TP Esensial tanpa Materi/nilai, TP NonEsensial satu Materi; Diagnostik gate dan Sumatif wajib 30.
3. Pra-Semester tanggal serentak Admin setelah semua TP NonEsensial selesai. Guru bisa ekspor semua siswa/TP dan Pra-Semester walaupun baru satu nilai.
4. Excel ATP **manual** tanpa macro dan tanpa tombol Proses sudah dipilih; penyusunan BAB/TP dilakukan melalui isian langsung pada workbook atau aplikasi, serta divalidasi server saat impor. KBC, izin Gen–Mapel, Word PG A–E tetap.

