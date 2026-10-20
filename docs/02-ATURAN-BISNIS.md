# 02 — Aturan Bisnis Lintas Modul

> Nomor AB yang sudah digunakan di Master v2.1 dipertahankan secara konseptual. Aturan khusus mengikuti dokumen domain, bukan dirangkum ulang secara berbeda di sini.

## 1. Invarian umum
1. **Jam server** menjadi sumber kebenaran ujian, penugasan waktu, transaksi dan log; zona Asia/Jakarta.
2. **Tidak ada penghapusan fisik** atas data siswa, nilai, sertifikat, BAB Siap, versi CP/BAB dan artefak historis. Penonaktifan/arsip berbeda dengan delete.
3. **Satu G1 aktif per Gen–Mapel** untuk seluruh semester Gen tersebut, bukan Guru global per mapel. Setiap Gen mengelola paket/BAB/TP/soal/materi sendiri. `created_by` adalah catatan sejarah, bukan sumber izin. Penggantian Guru memindahkan hak operasional hanya untuk Gen–Mapel yang dipilih, tanpa menimpa identitas penilai lama.
4. **Admin boleh mengelola biodata, referensi CP dan pengaturan**, tetapi tidak langsung mengetik nilai akademik/isi soal/materi milik Guru. Persetujuan koreksi guru dilakukan atas permintaan tersimpan.
5. **Admin tunggal diizinkan.** Larangan setuju permohonan sendiri berlaku ketika akun yang sama berperan sebagai *pengusul Guru* dan *pemutus Admin* untuk permohonan akademik; jangan memaksakan admin kedua untuk pembetulan biodata yang menjadi tugas Admin langsung.
6. Semua keputusan penting diaudit (pelaku, peran aktif, objek, sebelum/sesudah, alasan, waktu, versi). Audit tidak berarti menambah persetujuan manual yang tidak diwajibkan.
7. Tidak ada penghapusan nilai/sertifikat atau perubahan PDF sertifikat terdahulu. Koreksi menghasilkan riwayat atau pengganti, bukan menimpa sejarah.
8. BAB Siap tidak berubah dari jalur edit biasa, tidak ditarik kembali ke draf, dan tidak mengubah progres siswa yang sudah dimulai.
9. Setiap perubahan yang menyentuh banyak tabel memakai transaksi database dan pemeriksaan ID/otorisasi di service. Penerbitan sertifikat mengunci satu sertifikat berlaku per siswa/paket.
10. Teks input akademik disimpan sebagai teks biasa; tampilkan dengan escaping. Tidak membiarkan editor HTML bebas.

## 2. Batas peran dan persetujuan
- Staf dapat berperan Admin+Guru, Admin+Pimpinan, Guru+Pimpinan; hak akses hanya peran aktif.
- Pemilik aplikasi menetapkan **satu Admin cukup untuk operasional**. Manajemen biodata oleh Admin tidak memakai formulir pengajuan dan persetujuan dua Admin.
- Permohonan koreksi BAB Siap atau koreksi nilai yang **diajukan sebagai Guru** memerlukan keputusan Admin; satu `pengguna_id` tidak boleh menyetujui permohonannya sendiri sekalipun berpindah role. Bila benar-benar akun yang sama, sistem menampilkan peringatan keterbatasan persetujuan tanpa mengubah kewenangan administrasi normal.
- Penelaahan CP dilakukan **langsung oleh Admin** dan tidak memakai reviewer `cp_review` terpisah. Jika usulan CP diajukan Guru yang juga Admin, aturan larangan self-approval akademik tetap diperiksa.

### Administrasi akun dan profil v2.3
- Dua menu Admin, **Pengelola** dan **Siswa**, menggunakan satu sistem `pengguna` dan peran aktif yang sudah ada. Akun siswa tidak merangkap role staf.
- Keduanya menyediakan tambah/ubah/nonaktif, **import/export XLSX**, serta tombol reset sandi per akun. Ekspor tidak memuat hash, sandi, token, sesi, atau data rahasia.
- Impor memerlukan template, pemeriksaan kolom/role/Gen/NISN/duplikasi, pratinjau galat per baris, konfirmasi, transaksi dan idempotensi. Baris yang tak ada di file tidak dianggap permintaan menghapus data.
- Reset sandi oleh Admin menghasilkan sandi sementara **acak**, ditampilkan satu kali melalui alur aman, wajib ganti pada login berikutnya, sesi lama dibatalkan, dan audit tanpa merekam isi sandi.
- Staf hanya dapat mengubah foto, kontak pribadi dan sandi sendiri. Nama resmi/NIP/peran/penugasan dan biodata siswa dikelola Admin; siswa boleh melihat profil Guru aktif pada Gen-nya (nama, foto disetujui, mapel), bukan kontak pribadi/NIP.
- Perbaikan NISN dan username login siswa harus atomik dan menjaga `siswa_id`, progres dan histori.

## 3. Keputusan final tentang pembelajaran
- `Semester mulai = COALESCE(siswa.semester_mulai, angkatan.semester_mulai)`; semua semester sebelumnya berstatus Dilewati tanpa nilai/sertifikat.
- Semua BAB/TP berjalan berurutan. Hanya BAB `siap` tersedia; BAB draf selanjutnya tidak menghalangi siswa menyelesaikan BAB `siap` sebelumnya.
- Diagnostik wajib; skor ≥ KKM menuntaskan TP dengan nilai diagnostik. Siswa dapat membuka materi secara sukarela tetapi **tidak mengirim resume/tugas resmi**; tidak berdampak pada nilai.
- Skor diagnostik di bawah KKM: siswa wajib materi, resume setiap materi, tugas HOTS, dan sumatif. Komponen resume/tugas dinilai Guru; pengerjaan siswa tidak ditahan menunggu penilaian, tetapi sertifikat menunggu nilai lengkap.
- Kelulusan TP hanya ditentukan diagnostik atau sumatif; nilai gabungan resume/tugas/sumatif dipakai rapor dan sertifikat. Koreksi soal/kunci setelah ujian sah dapat menyebabkan hitung ulang **status ketuntasan** sesuai skor yang sudah dikoreksi.
- Resume/tugas memakai **tombol Simpan draf manual**, data disimpan di database, kemudian tombol kirim final mengunci hasil. Tidak ada autosave wajib.
- Nilai rapor/sertifikat ditampilkan **angka saja**, tanpa kategori huruf A/B/C/D sebagai predikat.

## 4. Keputusan final tentang ujian
- Satu **profil Ketat** untuk seluruh ujian; pengaturan longgar/standar tidak ditawarkan.
- Sumatif dapat dilakukan **di rumah** melalui **desktop/laptop** yang mendukung fullscreen. Diagnostik boleh melalui HP hanya bila browser/perangkat benar-benar mendukung fullscreen.
- Jika fullscreen tidak didukung, **tolak sebelum membuat percobaan** dengan pesan gunakan desktop/laptop yang mendukungnya. Pemeriksaan dilakukan di UI dan Service pada kondisi yang bisa diverifikasi, tanpa menganggap User-Agent sebagai bukti keamanan sempurna.
- Pelanggaran fullscreen berulang: jeda maksimal 90 detik/kejadian, akumulasi 180 detik, pelanggaran ketiga membatalkan percobaan. Gangguan koneksi: deteksi 20 detik, akumulasi jeda 300 detik. Batas/keputusan rinci di `07-MESIN-UJIAN.md`.

## 5. Keputusan final tentang sertifikat
- Awal: sistem otomatis menerbitkan ketika paket tuntas dan semua nilai wajib final, menggunakan template global A4 landscape aktif atau **template bawaan aplikasi** jika Admin belum mengunggah background.
- **PNG tanda tangan wajib tersedia**; tanpa PNG penandatangan valid, terbitan ditahan dan Admin diberi peringatan.
- Koreksi nilai yang mengubah isi akademik tercetak menghasilkan **nomor/QR baru** dan sertifikat lama `digantikan`; jika cetakan tidak berubah, hanya audit dan nilai internal yang berubah.
- Koreksi biodata siswa **langsung dikelola Admin**; sertifikat terdampak diganti **satu per satu**. Tidak mewajibkan Admin kedua untuk edit biodata biasa.
- Saat revisi sertifikat, background tetap versi yang dipakai sertifikat lama, tetapi **identitas penandatangan dan gambar tanda tangan memakai yang aktif saat revisi**.
- NISN **ditampilkan pada PDF sertifikat**, tidak tampil di halaman verifikasi publik. Tidak ada fitur mencabut atau menghapus sertifikat.

## 6. Status standar
| Domain | Nilai status | Makna |
|---|---|---|
| Pengguna | aktif/nonaktif/terkunci | Bisa login, tidak bisa login, ditolak sementara karena kegagalan login |
| BAB | draf/siap | Bisa diedit atau immutable lewat jalur biasa |
| Progres TP | belum_mulai/diagnostik/belajar/sumatif/perlu_bantuan/tuntas | Proses siswa; transisi harus sesuai prasyarat |
| Ujian | berjalan/jeda_fullscreen/jeda_koneksi/selesai_sah/dibatalkan_menunggu_guru | Skor hanya pada selesai_sah |
| Penilaian | belum_dinilai/dinilai/koreksi_diajukan/koreksi_diterapkan | Riwayat menyimpan perubahan |
| Sertifikat | menyiapkan_revisi/berlaku/digantikan | Tidak ada `dicabut` |
| CP/template | draf/aktif/diarsipkan | Versi aktif tunggal sesuai jenis referensi |
| Pengumuman | draf/terbit/arsip | Global untuk pengguna yang login |

## 7. Prinsip pembacaan dokumen
Jangan memperkenalkan menu, tabel, role, atau syarat persetujuan baru hanya karena contoh sebelumnya pernah menyebutnya. Persyaratan verifikasi harus **cukup aman, sederhana, dan sesuai keputusan akhir**. Rincian operasional hosting dan data pengaturan sekolah diisi melalui UI saat pemasangan.
