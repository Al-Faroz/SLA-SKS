# 03 — Peran dan Hak Akses

## 1. Empat role dan peran aktif
| Role | Area utama |
|---|---|
| **Admin** | Pengelola/Siswa, impor/ekspor, reset sandi, Gen–Mapel–Guru, CP, profil sekolah, branding/template, biodata, persetujuan koreksi, pengumuman, FAQ, laporan, pemeliharaan |
| **Guru** | BAB/TP/paket milik Gen–Mapel yang diampu G1, impor, media, soal, penilaian, keputusan ujian, monitoring, pengumuman global |
| **Siswa** | Belajar dan ujian miliknya, nilai, pengumuman, FAQ, notifikasi, sertifikat sendiri |
| **Pimpinan** | Dasbor, monitoring dan laporan secara **baca saja**; pratinjau siswa tanpa perubahan data |

Satu akun staf boleh memiliki dua role saja: Admin+Guru, Admin+Pimpinan, Guru+Pimpinan. Siswa tidak dirangkap. Role aktif disimpan di sesi, sesi diregenerasi saat ganti role; otorisasi tidak menggabungkan hak kedua role sekaligus.

## 2. Matriks fitur
| Tindakan | Admin | Guru | Siswa | Pimpinan |
|---|---|---|---|---|
| Membuat/reset/nonaktif akun | **Ya** | Tidak | Tidak | Baca terbatas |
| Memperbaiki biodata/NISN siswa | **Ya** | Tidak | Tidak | Tidak |
| Mengelola Gen dan semester mulai | **Ya** | Tidak | Tidak | Baca |
| Menetapkan Guru untuk Gen–Mapel | **Ya** | Tidak | Tidak | Baca |
| Mengelola CP resmi/publikasi | **Ya** | Usul CP mapelnya | Baca ringkas | Baca |
| Menyusun BAB/TP/materi/soal | Tidak | **Ya, Gen–Mapelnya dan masih draf** | Tidak | Tidak |
| Mengajukan koreksi BAB Siap | Tidak | **Ya, Gen–Mapelnya** | Tidak | Tidak |
| Menyetujui koreksi BAB Siap | **Ya**, bukan usulan sendiri | Tidak | Tidak | Tidak |
| Menilai resume/tugas | Tidak | **Ya, Gen–Mapelnya** | Tidak | Tidak |
| Mengajukan koreksi nilai oleh guru pengganti | Tidak | **Ya, Gen–Mapelnya** | Tidak | Tidak |
| Menyetujui koreksi akademik | **Ya**, bukan usulan sendiri | Tidak | Tidak | Tidak |
| Menangani ujian batal / izin ulang | Tidak | **Ya, Gen–Mapelnya** | Tidak | Baca |
| Menyetel logo, ikon, TTD PNG dan template | **Ya** | Tidak | Tidak | Baca |
| Mengelola pengumuman global | **Ya** | **Ya** | Baca | Baca |
| Mengelola FAQ dan teks Kebijakan Privasi sekolah | **Ya** | Tidak | FAQ baca / privasi publik | FAQ baca / privasi publik |
| Mengunduh sertifikat siswa | Seluruhnya | Gen–Mapelnya | Miliknya | Seluruhnya (baca) |
| Melihat log audit sensitif | **Ya** | Audit relevan miliknya | Tidak | Ringkasan baca sesuai kebijakan |
| Menyunting pengaturan keamanan & server | **Ya** | Tidak | Tidak | Tidak |

### Hak administratif dan profil v2.3
| Tindakan | Admin | Guru | Siswa | Pimpinan |
|---|---|---|---|---|
| Mengelola akun Pengelola/Siswa, impor/ekspor XLSX, reset per akun | Ya | Tidak | Tidak | Tidak |
| Mengubah foto/kontak pribadi/sandi sendiri | Ya | Ya | Sandi sendiri; biodata resmi baca saja | Ya |
| Mengubah nama resmi/NIP/peran/NISN/Gen | Ya, diaudit | Tidak | Tidak | Tidak |
| Melihat profil profesional Guru pengampu Gen siswa | Ya | Menurut cakupan | Ya, khusus Guru Gen-nya | Baca sesuai izin |
| Melihat hash/sandi/token/sesi pengguna lain | Tidak pernah | Tidak | Tidak | Tidak |

Siswa hanya boleh melihat identitas profesional Guru (nama, foto yang disetujui, mata pelajaran), bukan NIP, telepon pribadi atau informasi keamanan. Login dan tabel akun tetap bersama; Pengelola dan Siswa adalah dua ruang UI Admin.

## 3. Model G1 v2.3 — penugasan Guru per Gen–Mapel
Sumber kewenangan Guru adalah relasi `penugasan_guru_gen(angkatan_id, mapel_id, guru_id)`, satu Guru aktif per pasangan Gen–Mapel dan berlaku pada semua semester Gen tersebut. Guru boleh mengampu banyak Gen/mapel; tidak ada penugasan per siswa, rombel atau semester. Kolom lama `ref_mapel.guru_id` bukan sumber izin v2.3. Hanya akun berstatus aktif dengan peran `guru` yang boleh ditunjuk; akun Admin+Guru dan Guru+Pimpinan memenuhi syarat, tetapi saat mengajar harus memakai role aktif Guru.

Setiap paket/BAB/TP/soal/materi memiliki konteks Gen pemilik yang tidak dapat dilompati dengan mengubah ID URL. Guru baru dapat meneruskan draf dan pekerjaan akademik belum selesai hanya pada Gen–Mapel yang dialihkan. BAB Siap tetap terkunci; nilai, versi, audit dan penilai lama tetap utuh. Gen lain tidak terpengaruh. Bila pengampu Gen–Mapel kosong, siswa masih boleh melanjutkan BAB Siap historis yang sudah tersedia; penilaian baru menunggu Guru.

## 4. Aturan self-approval yang dibatasi tepat sasaran
- Guru A mengajukan koreksi nilai/BAB dalam mode Guru, lalu berpindah ke mode Admin **pada akun yang sama**: tidak dapat menyetujui usulan itu; pemeriksaan identitas `pengguna.id` secara server-side.
- Guru A mengajukan, Admin B menyetujui: dapat dilaksanakan.
- Admin A secara langsung **mengedit biodata administratif** siswa: tidak ada konsep usulan Guru/approval ganda. Admin A dapat menerbitkan revisi identitas sertifikat berdasarkan perbaikan tersebut; dicatat otomatis.
- Tidak disyaratkan minimal dua Admin. Jika permohonan akademik mengalami benturan self-approval, beri pesan khusus; jangan membuat akun bersama atau mengabaikan audit.

## 5. Proteksi server
Pemeriksaan izin dilakukan di Controller **dan** Service: `peran_aktif`, status akun, `penugasan_guru_gen` + Gen/Mapel objek, `siswa_id` untuk siswa, status BAB, dan versi objek. Guru tidak boleh mengakses siswa mapel lain dengan mengganti ID URL. Pimpinan tidak boleh melakukan POST mutasi maupun lewat API. Peran aktif bukan bukti kepemilikan. Semua percobaan akses ilegal dilog sewajarnya.

## 6. Peta navigasi per role
- **Siswa:** Beranda → Mapel → Semester → BAB → TP → Diagnostik/Materi/Resume/Tugas/Sumatif → Nilai → Sertifikat; Pengumuman, Notifikasi, Bantuan, Profil.
- **Guru:** Beranda → Pilih Gen–Mapel → Semester/BAB → Struktur TP & CP → Excel & Media → Bank Soal → Checklist Siap → Antrean Penilaian → Ujian Dibatalkan → Monitoring → Laporan; Pengumuman.
- **Admin:** Beranda → Pengelola → Siswa → Gen → Mapel/Guru per Gen → Referensi CP → Persetujuan Koreksi BAB/Nilai → Pengaturan (Identitas/Aset/Template/TTD) → Pengumuman/FAQ → Sertifikat → Log → Pemeliharaan → Laporan.
- **Pimpinan:** Beranda → Progres → Siswa → Mutu BAB/Guru → Ujian → Sertifikat/Laporan → Pratinjau baca-saja.
