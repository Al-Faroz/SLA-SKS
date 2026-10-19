# 03 — Peran dan Hak Akses

## 1. Empat role dan peran aktif
| Role | Area utama |
|---|---|
| **Admin** | Akun, angkatan, mapel–guru, CP, profil sekolah, branding/template, biodata, persetujuan koreksi, pengumuman, FAQ, laporan, pemeliharaan |
| **Guru** | Seluruh BAB/TP/mapel yang diampu G1, impor, media, soal, penilaian, keputusan ujian, monitoring, pengumuman global |
| **Siswa** | Belajar dan ujian miliknya, nilai, pengumuman, FAQ, notifikasi, sertifikat sendiri |
| **Pimpinan** | Dasbor, monitoring dan laporan secara **baca saja**; pratinjau siswa tanpa perubahan data |

Satu akun staf boleh memiliki dua role saja: Admin+Guru, Admin+Pimpinan, Guru+Pimpinan. Siswa tidak dirangkap. Role aktif disimpan di sesi, sesi diregenerasi saat ganti role; otorisasi tidak menggabungkan hak kedua role sekaligus.

## 2. Matriks fitur
| Tindakan | Admin | Guru | Siswa | Pimpinan |
|---|---|---|---|---|
| Membuat/reset/nonaktif akun | **Ya** | Tidak | Tidak | Baca terbatas |
| Memperbaiki biodata/NISN siswa | **Ya** | Tidak | Tidak | Tidak |
| Mengelola Gen dan semester mulai | **Ya** | Tidak | Tidak | Baca |
| Menetapkan guru untuk mapel | **Ya** | Tidak | Tidak | Baca |
| Mengelola CP resmi/publikasi | **Ya** | Usul CP mapelnya | Baca ringkas | Baca |
| Menyusun BAB/TP/materi/soal | Tidak | **Ya, mapelnya dan masih draf** | Tidak | Tidak |
| Mengajukan koreksi BAB Siap | Tidak | **Ya, mapelnya** | Tidak | Tidak |
| Menyetujui koreksi BAB Siap | **Ya**, bukan usulan sendiri | Tidak | Tidak | Tidak |
| Menilai resume/tugas | Tidak | **Ya, mapelnya** | Tidak | Tidak |
| Mengajukan koreksi nilai oleh guru pengganti | Tidak | **Ya, mapelnya** | Tidak | Tidak |
| Menyetujui koreksi akademik | **Ya**, bukan usulan sendiri | Tidak | Tidak | Tidak |
| Menangani ujian batal / izin ulang | Tidak | **Ya, mapelnya** | Tidak | Baca |
| Menyetel logo, ikon, TTD PNG dan template | **Ya** | Tidak | Tidak | Baca |
| Mengelola pengumuman global | **Ya** | **Ya** | Baca | Baca |
| Mengelola FAQ dan teks Kebijakan Privasi sekolah | **Ya** | Tidak | FAQ baca / privasi publik | FAQ baca / privasi publik |
| Mengunduh sertifikat siswa | Seluruhnya | Mapelnya | Miliknya | Seluruhnya (baca) |
| Melihat log audit sensitif | **Ya** | Audit relevan miliknya | Tidak | Ringkasan baca sesuai kebijakan |
| Menyunting pengaturan keamanan & server | **Ya** | Tidak | Tidak | Tidak |

## 3. Model G1 — kepemilikan guru
`ref_mapel.guru_id` adalah satu-satunya penetapan guru aktif mapel. Seorang Guru boleh mengampu beberapa mapel; tidak ada `guru_siswa`, `guru_rombel`, atau guru terpisah per paket/semester. Penugasan paket menentukan **konten yang berlaku untuk angkatan**, bukan kewenangan pengampu. Setelah guru diganti, hak operasional berpindah; log penilaian lama tetap atas nama guru lama. Bila mapel belum punya guru, siswa tetap boleh menuntaskan BAB Siap yang tersedia, tetapi penilaian baru menunggu guru.

## 4. Aturan self-approval yang dibatasi tepat sasaran
- Guru A mengajukan koreksi nilai/BAB dalam mode Guru, lalu berpindah ke mode Admin **pada akun yang sama**: tidak dapat menyetujui usulan itu; pemeriksaan identitas `pengguna.id` secara server-side.
- Guru A mengajukan, Admin B menyetujui: dapat dilaksanakan.
- Admin A secara langsung **mengedit biodata administratif** siswa: tidak ada konsep usulan Guru/approval ganda. Admin A dapat menerbitkan revisi identitas sertifikat berdasarkan perbaikan tersebut; dicatat otomatis.
- Tidak disyaratkan minimal dua Admin. Jika permohonan akademik mengalami benturan self-approval, beri pesan khusus; jangan membuat akun bersama atau mengabaikan audit.

## 5. Proteksi server
Pemeriksaan izin dilakukan di Controller **dan** Service: `peran_aktif`, status akun, `ref_mapel.guru_id`, `siswa_id` untuk siswa, status BAB, dan versi objek. Guru tidak boleh mengakses siswa mapel lain dengan mengganti ID URL. Pimpinan tidak boleh melakukan POST mutasi maupun lewat API. Peran aktif bukan bukti kepemilikan. Semua percobaan akses ilegal dilog sewajarnya.

## 6. Peta navigasi per role
- **Siswa:** Beranda → Mapel → Semester → BAB → TP → Diagnostik/Materi/Resume/Tugas/Sumatif → Nilai → Sertifikat; Pengumuman, Notifikasi, Bantuan, Profil.
- **Guru:** Beranda → Semester/BAB → Struktur TP & CP → Excel & Media → Bank Soal → Checklist Siap → Antrean Penilaian → Ujian Dibatalkan → Monitoring → Laporan; Pengumuman.
- **Admin:** Beranda → Akun → Gen → Mapel/Guru → Referensi CP → Persetujuan Koreksi BAB/Nilai → Pengaturan (Identitas/Aset/Template/TTD) → Pengumuman/FAQ → Sertifikat → Log → Pemeliharaan → Laporan.
- **Pimpinan:** Beranda → Progres → Siswa → Mutu BAB/Guru → Ujian → Sertifikat/Laporan → Pratinjau baca-saja.
