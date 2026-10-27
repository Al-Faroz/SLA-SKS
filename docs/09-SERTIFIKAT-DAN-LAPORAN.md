# 09 — Sertifikat, Daftar Nilai, Revisi dan Laporan

> **Master v2.5 · 26 Oktober 2026.** Daftar nilai berbasis TP Esensial/NonEsensial, tanpa nilai akhir semester. Aturan apakah Pra-Semester menjadi syarat sertifikat **belum diputuskan**.

## 1. Daftar nilai sertifikat
Sertifikat siswa satu Mapel/Semester menampilkan **semua BAB/TP menurut ATP semester**: TP **Esensial** ditampilkan tanpa nilai, TP **NonEsensial** disertai nilai TP. Sertifikat **tidak** menampilkan nilai akhir semester hasil rerata TP/Pra-Semester, karena nilai agregat tersebut tidak ada. Nilai Pra-Semester berdiri sendiri pada laporan/ekspor; pencantuman pada sertifikat tidak ditetapkan dalam diskusi ini, sehingga jangan diam-diam menambah kolom.

Penerbitan awal mensyaratkan TP NonEsensial selesai dan seluruh penilaian wajib sudah final, serta kesiapan data sertifikat/TTD. Keputusan apakah ujian Pra-Semester perlu selesai sebelum sertifikat diterbitkan **ditunda**; tidak boleh dijadikan gate otomatis sebelum keputusan pemilik.

## 2. PDF dan identitas
Kertas **A4 landscape 297×210 mm**. Background JPG/PNG unggahan Admin, hanya satu versi aktif global, versi historis tersimpan; fallback template bawaan bila belum ada. Identitas sekolah, nama siswa dan **NISN privat**, Mapel, Semester, daftar TP per BAB, nomor/kode QR, kota/tanggal, kepala madrasah/NIP dan **gambar tanda tangan PNG wajib**. Tidak mengklaim PNG sebagai TTE bersertifikasi eksternal. Preview wajib sebelum aktivasi template.

## 3. Versi dan revisi
Sertifikat awal terbit menyimpan snapshot isi, template background/logo/TTD dan data penandatangan. Template baru tidak mengubah terbitan lama. Saat revisi: background historis sama; nama/NIP dan tanda tangan mengikuti versi aktif terbaru. Jika penandatangan/PNG belum valid, penerbitan ditahan secara eksplisit.
- Koreksi resmi nilai/identitas yang mengubah isi tercetak menghasilkan nomor dan QR baru, versi terdahulu menjadi **digantikan** tanpa dihapus. Jika data cetak sama, perubahan internal cukup audit, tidak perlu PDF baru.
- Koreksi biodata dilakukan langsung oleh Admin, sertifikat terdampak diganti satu per satu; persetujuan Admin kedua tidak diwajibkan untuk koreksi biodata normal.
- Larangan self-approval tetap khusus permohonan akademik Guru+Admin akun sama.
- Tidak ada pencabutan, penghapusan sertifikat, atau dua sertifikat berlaku untuk siswa/paket sama.

## 4. Verifikasi publik
`GET /verifikasi/{kode}` berbasis token acak dan pembatasan permintaan. Publik hanya melihat nomor, nama, Mapel, Semester, daftar nilai yang boleh dibuka, status berlaku/digantikan dan tanggal terbit; **NISN, kontak, alasan koreksi, audit dan PDF privat tidak tampil**. QR historis masih valid dengan status digantikan dan rujukan versi aktif.

## 5. Laporan
- **Export Nilai Guru (prioritas)**: Gen–Mapel–Semester, semua siswa, kolom setiap TP (Esensial tanpa nilai; NonEsensial nilai) dan Pra-Semester; file dapat diunduh walaupun baru satu nilai TP tersedia, kosong ≠ 0.
- Rapor per siswa: daftar TP/kode/jenis, jalur diagnostik, Resume/HOTS bila wajib, Sumatif awal/remedial, nilai TP, nilai Pra-Semester terpisah.
- Progres per semester: persentase selesai TP NonEsensial, bukan rata-rata nilai.
- Log ujian, aktivitas, persetujuan, revisi, dan pengumuman tetap berizin sesuai peran, audit, dan privasi.

Semua tampilan termasuk Pimpinan dibatasi pembacaan yang diizinkan; data nilai antargen tidak tercampur.
