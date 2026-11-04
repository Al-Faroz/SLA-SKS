# 02 — Aturan Bisnis Lintas Modul

> Nomor AB yang sudah digunakan di Master v2.1 dipertahankan secara konseptual. Aturan khusus mengikuti dokumen domain, bukan dirangkum ulang secara berbeda di sini.

## 1. Invarian umum
1. **Jam server** menjadi sumber kebenaran ujian, penugasan waktu, transaksi dan log; zona Asia/Jakarta.
2. **Data historis siswa, nilai, sertifikat dan percobaan tidak boleh dihapus/ditimpa.** CP/BAB serta TP yang belum digunakan siswa boleh disunting oleh pemilik kewenangan. Perlindungan riwayat berbeda dari larangan menyunting seluruh data.
3. **Satu G1 aktif per Gen–Mapel** untuk seluruh semester Gen tersebut, bukan Guru global per mapel. Setiap Gen mengelola paket/TP/soal/materi sendiri. `created_by` adalah catatan sejarah, bukan sumber izin. Penggantian Guru memindahkan hak operasional hanya untuk Gen–Mapel yang dipilih, tanpa menimpa identitas penilai lama.
4. **Admin mengelola CP langsung**, kapan pun CP sudah dipakai TP atau siswa, tanpa permohonan, kategori, atau persetujuan tambahan. Admin tidak mengetik nilai akademik atau soal/materi milik Guru.
5. **Admin tunggal diizinkan.** Larangan setuju permohonan sendiri berlaku ketika akun yang sama berperan sebagai *pengusul Guru* dan *pemutus Admin* untuk permohonan akademik; jangan memaksakan admin kedua untuk pembetulan biodata yang menjadi tugas Admin langsung.
6. Semua keputusan penting diaudit (pelaku, peran aktif, objek, sebelum/sesudah, alasan, waktu, versi). Audit tidak berarti menambah persetujuan manual yang tidak diwajibkan.
7. Tidak ada penghapusan nilai/sertifikat atau perubahan PDF sertifikat terdahulu. Koreksi menghasilkan riwayat atau pengganti, bukan menimpa sejarah.
8. **TP Siap tetap boleh disunting langsung oleh Guru G1 jika belum pernah digunakan satu pun siswa.** Sesudah minimal satu siswa mulai menggunakan TP, seluruh konten TP (termasuk KKM, Materi, soal) terkunci terhadap perubahan. Status `siap` sendiri **bukan** alasan mengunci. Pemeriksaan penggunaan berlaku saat membuka dan saat menyimpan. Tidak diperlukan kategori A/B/C atau keputusan Admin untuk perubahan TP yang belum digunakan. Tidak ada jalur koreksi konten TP sesudah digunakan pada keputusan ini.
9. Setiap perubahan yang menyentuh banyak tabel memakai transaksi database dan pemeriksaan ID/otorisasi di service. Penerbitan sertifikat mengunci satu sertifikat berlaku per siswa/paket.
10. Teks input akademik disimpan sebagai teks biasa; tampilkan dengan escaping. Tidak membiarkan editor HTML bebas.

## 2. Batas peran dan persetujuan
- Staf dapat berperan Admin+Guru, Admin+Pimpinan, Guru+Pimpinan; hak akses hanya peran aktif.
- Pemilik aplikasi menetapkan **satu Admin cukup untuk operasional**. Manajemen biodata oleh Admin tidak memakai formulir pengajuan dan persetujuan dua Admin.
- **Sunting TP Siap sebelum dipakai siswa dan sunting CP oleh Admin bukan permohonan akademik**, tidak memerlukan kategori, reviewer atau persetujuan. Prosedur koreksi *nilai siswa* (bukan konten TP) tetap mengikuti otorisasi dan auditnya sendiri, termasuk larangan menyetujui usulan nilai sendiri bila mekanisme itu dipakai.
- **CP disunting langsung oleh Admin**; tidak ada gerbang usulan Guru, reviewer, atau self-approval bagi edit langsung CP. Perubahan dicatat otomatis dan tidak memutasi progres/nilai siswa.

### Administrasi akun dan profil v2.4
- Dua menu Admin, **Pengelola** dan **Siswa**, menggunakan satu sistem `pengguna` dan peran aktif yang sudah ada. Akun siswa tidak merangkap role staf.
- Keduanya menyediakan tambah/ubah/nonaktif, **import/export XLSX**, serta tombol reset sandi per akun. Ekspor tidak memuat hash, sandi, token, sesi, atau data rahasia.
- Impor memerlukan template, pemeriksaan kolom/role/Gen/NISN/duplikasi, pratinjau galat per baris, konfirmasi, transaksi dan idempotensi. Baris yang tak ada di file tidak dianggap permintaan menghapus data.
- Reset sandi dilakukan **per akun** oleh Admin. Akun yang memiliki peran Admin (termasuk rangkap) mendapat sandi sementara **acak** dan wajib menggantinya pada login berikutnya; Guru/Pimpinan/Siswa memakai sandi **default resmi yang dimasukkan Admin saat reset**, bukan sandi acak, dan hanya **disarankan** menggantinya lewat Profil. Semua sesi lama dibatalkan dan audit tidak merekam sandi. Nilai default tidak ditanam dalam kode atau database sebagai teks biasa.
- Staf hanya dapat mengubah foto, kontak pribadi dan sandi sendiri. Nama resmi/NIP/peran/penugasan dan biodata siswa dikelola Admin; siswa boleh melihat profil Guru aktif pada Gen-nya (nama, foto disetujui, mapel), bukan kontak pribadi/NIP.
- Perbaikan NISN dan username login siswa harus atomik dan menjaga `siswa_id`, progres dan histori.

## 3. Keputusan final tentang pembelajaran
- Semester sebelum semester mulai siswa tetap Dilewati. ATP per Gen–Mapel berisi BAB dan TP berurutan, disiapkan Guru di aplikasi atau impor Excel.
- **Penyiapan Guru:** TP **Esensial maupun NonEsensial sama-sama wajib memiliki tepat satu Materi/media yang lengkap**. **Alur siswa:** Jenis TP hanya menentukan akses dan kewajiban aktivitas di sisi siswa. Pada alur siswa saat ini Esensial tidak memiliki aktivitas/penilaian wajib; NonEsensial menjalankan Diagnostik → (bila gagal: Materi → Resume → HOTS) → Sumatif. Materi Esensial tidak boleh dihapus hanya karena tidak diakses siswa.
- Diagnostik min 10 adalah gate bukan nilai TP. Sumatif min 30. Nilai gagal Diagnostik 20/20/60 (Resume/HOTS/Sumatif), lulus 100% Sumatif. Admin menentukan KKM default dan Guru dapat menetapkan khusus.
- Sumatif < KKM memperoleh satu remedial; gunakan nilai tertinggi. Setelah aktivitas dan kesempatan sah terkait selesai, **TP selesai meskipun nilai masih < KKM**. Progres semester hanya TP NonEsensial.
- Pra-Semester dijadwalkan Admin **per Gen–Mapel–Semester**, serentak bagi peserta paket yang sama setelah semua TP NonEsensial selesai; tiap mapel memiliki jendela mulai dan durasi individual tersendiri; nilai tersendiri. **Tidak ada nilai akhir semester.**
- Resume/HOTS Simpan Draf manual, Kirim Final, penilaian Guru. TP Siap tanpa penggunaan boleh disunting; begitu ada penggunaan siswa, konten dan snapshot yang digunakannya dilindungi.

## 4. Keputusan final tentang ujian
- Profil pengamanan pengerjaan Ketat (fullscreen, waktu, koneksi) dipakai pada Diagnostik, Sumatif dan Pra-Semester, **tetapi kegagalan tes mandiri ditangani dengan permintaan akses ulang Guru, bukan pembatalan**. Pembatalan hanya untuk Pra-Semester. PG lima pilihan A–E dengan satu kunci; aturan operasional lengkap pada dokumen 07.
- Diagnostik minimal 10, Sumatif minimal 30, Pra-Semester minimal 30 soal gabungan seluruh BAB semester.
- Sumatif dan Pra-Semester memberikan **satu remedial jika skor Sumatif/Pra-Semester normal selesai sah di bawah KKM**, mengambil skor tertinggi. **Diagnostik/Sumatif yang tidak selesai** tidak menghasilkan skor, bukan pembatalan dan bukan remedial; siswa meminta akses ulang Guru sesuai tahap yang gagal tanpa menghabiskan kesempatan sah. **Pembatalan ujian hanya berlaku untuk Pra-Semester**.
- Admin mengatur jendela boleh mulai, durasi dan jadwal susulan/remedial Pra-Semester per paket Gen–Mapel–Semester; akses tanpa seluruh TP NonEsensial selesai ditolak.

## 5. Keputusan final tentang sertifikat
- Daftar nilai sertifikat berisi seluruh TP: Esensial tanpa nilai, NonEsensial dengan nilai; **tidak ada rata-rata nilai akhir semester**.
- Syarat apakah Pra-Semester wajib selesai sebelum sertifikat terbit masih **menunggu keputusan**, tidak boleh ditetapkan otomatis.
- Sertifikat A4 landscape, template background global berversi, TTD PNG wajib, NISN hanya PDF privat, QR publik aman. Koreksi cetakan berubah menghasilkan sertifikat pengganti nomor/QR baru, lama Digantikan; cetakan sama cukup audit. Tidak ada pencabutan/hapus.

## 6. Status standar
| Domain | Nilai status | Makna |
|---|---|---|
| Pengguna | aktif/nonaktif/terkunci | Bisa login, tidak bisa login, ditolak sementara karena kegagalan login |
| TP | draf/siap | Draf bisa diedit; Siap juga bisa diedit selama belum pernah digunakan siswa. Status Siap tidak sama dengan terkunci |
| Progres TP | belum_mulai/diagnostik/belajar/sumatif/perlu_bantuan/tuntas | Proses siswa; transisi harus sesuai prasyarat |
| Ujian | berjalan/jeda_fullscreen/jeda_koneksi/selesai_sah; mandiri: akses_ulang_diperlukan; Pra-Semester: dibatalkan_menunggu_guru | Skor hanya pada selesai_sah; enum DB lama masih memerlukan update SQL additive setelah audit dump terbaru |
| Penilaian | belum_dinilai/dinilai/koreksi_diajukan/koreksi_diterapkan | Riwayat menyimpan perubahan |
| Sertifikat | menyiapkan_revisi/berlaku/digantikan | Tidak ada `dicabut` |
| CP/template | draf/aktif/diarsipkan | Versi aktif tunggal sesuai jenis referensi |
| Pengumuman | draf/terbit/arsip | Global untuk pengguna yang login |

## 7. Prinsip pembacaan dokumen
Jangan memperkenalkan menu, tabel, role, atau syarat persetujuan baru hanya karena contoh sebelumnya pernah menyebutnya. Persyaratan verifikasi harus **cukup aman, sederhana, dan sesuai keputusan akhir**. Rincian operasional hosting dan data pengaturan sekolah diisi melalui UI saat pemasangan.

## 8. Keputusan lintas modul v2.5
1. CP operasional satu Mapel → Semester → BAB → TP, nomor BAB berlanjut antardua semester kelas sama. Nama singkat 50, deskripsi 225, kode `KODEMAPEL.KELAS.BAB.TP`.
2. Esensial dan NonEsensial keduanya mempunyai **satu Materi** yang disiapkan Guru; Esensial tanpa nilai siswa sesuai alur saat ini. Diagnostik menjadi gate dan Sumatif wajib 30 untuk jalur NonEsensial.
3. Pra-Semester memiliki jendela boleh mulai dan durasi individual per Gen–Mapel–Semester dari Admin setelah semua TP NonEsensial selesai. Guru bisa ekspor semua siswa/TP dan Pra-Semester walaupun baru satu nilai.
4. Excel ATP **manual** tanpa macro dan tanpa tombol Proses sudah dipilih; penyusunan BAB/TP dilakukan melalui isian langsung pada workbook atau aplikasi, serta divalidasi server saat impor. KBC, izin Gen–Mapel, Word PG A–E tetap.

## 9. Aturan final hak sunting CP, BAB, TP (2 November 2026)
1. **CP**: Admin boleh memperbaiki isian CP kapan saja tanpa terhalang jumlah BAB/TP/siswa yang telah merujuk. Audit/riwayat sistem boleh dilakukan otomatis; pengguna tidak diminta kategori koreksi atau approval.
2. **BAB**: Guru G1 boleh memperbaiki isian BAB yang memang sudah tersedia, tanpa mengunci BAB semata karena ada TP berstatus Siap. Penomoran dan hubungan akademik yang sudah berlaku tetap diperiksa; tidak ada fitur baru pemindahan TP antar-BAB.
3. **TP**: status *Siap* berarti konten telah disiapkan, **bukan** otomatis terkunci. Guru G1 boleh mengubah TP Siap, KKM tunggal, satu Materi, dan soal jika TP tersebut **belum pernah digunakan siswa**. Jika satu saja siswa sudah memulai TP tersebut, konten TP **tidak dapat diubah**, termasuk lewat URL/API/Word/import. Tidak tersedia persetujuan atau jalur koreksi A/B/C untuk menerobos penguncian tersebut.
4. **Pemeriksaan penggunaan**: server mengecek riwayat progres/aktivitas/percobaan TP terkait dari sumber data sah, pada GET dan ulang secara atomik pada POST. *Melihat TP dalam daftar* atau status `siap` tidak sama dengan mulai menggunakannya. Tidak mereset progres untuk membuka kunci.
5. **Integritas**: ketika TP Siap yang belum digunakan diubah, data kesiapan dan snapshot konten aktif harus diselaraskan otomatis oleh Service sebelum boleh diakses siswa, tanpa permintaan admin atau tahapan revisi manual. Semua cek/readiness, otorisasi G1, CSRF, transaksi dan audit tetap berlaku; tidak boleh ada kondisi siswa memulai versi yang sedang diperbarui.


## Keputusan operasional Pra-Semester (3 November 2026)
- Admin menetapkan jadwal **dan durasi pengerjaan per paket Gen–Mapel–Semester**, bukan jadwal global seluruh mapel. Rentang jadwal menentukan kapan peserta boleh **memulai**, sedangkan durasi individual dihitung dari waktu mulai percobaan masing-masing sampai habis; penutupan jendela mulai tidak menghentikan percobaan yang sudah berjalan.
- Guru G1 menyiapkan bank seluruh BAB dan KKM Pra-Semester; remedial satu kali setelah ujian awal sah di bawah KKM mengikuti **jadwal remedial Admin**. Nilai terbaik digunakan; tidak ada nilai akhir semester.
- Ujian yang **dibatalkan** mengikuti jadwal **susulan Admin**, bukan permintaan akses ulang mandiri. Admin dapat memasukkan siswa tidak hadir ke susulan; pembatalan berulang boleh mendapat susulan berikutnya dengan audit. Jika remedial dibatalkan, susulannya tetap tahap remedial, bukan jatah baru. Guru tidak diminta alasan/persetujuan tambahan untuk jadwal susulan. Modul sertifikat tidak diubah oleh keputusan ini.

### A13-2 — Siklus Pengumuman dan FAQ (3 November 2026)

Pengumuman global dan FAQ resmi mengikuti status **Draf → Terbit → Arsip**. Draf hanya dilihat pengelola yang sah, Terbit terlihat **semua akun aktif login** tanpa segmentasi per Gen/peran, Arsip tidak terlihat pembaca dan tidak dapat diubah/diterbitkan ulang. Pengumuman terbit boleh diperbaiki dengan riwayat versi, tetapi tidak diturunkan ke Draf; hentikan publikasi melalui Arsip. Admin boleh mengelola seluruh Pengumuman serta semua FAQ; Guru boleh menulis dan memperbaiki **Pengumuman yang dibuatnya sendiri**; Siswa/Pimpinan hanya membaca. Tidak menghapus record ataupun mengirim email/WhatsApp. Audit setiap tindakan berhasil mencatat aktor, role, sebelum/sesudah dan timestamp UTC.
