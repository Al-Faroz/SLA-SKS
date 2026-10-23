# 04 — Kurikulum, Fase D, CP, ATP, TP dan Paket Semester

> **Master v2.4 (22 Oktober 2026).** Struktur operasional final: **CP → ATP (urutan TP) → TP → Materi/Submateri**. Tidak ada entitas BAB wajib dalam aplikasi. Aturan akademik percepatan SKS tidak diubah.

## 1. Kontrak akademik
- SLA-SKS melayani MTs Fase D kelas VII–IX, Semester 1–6. Semester 1–2 berpadanan tingkat VII, 3–4 tingkat VIII, 5–6 tingkat IX untuk penampilan; **Gen boleh mempunyai semester mulai lebih lanjut** sesuai program SKS dua tahun.
- **CP** adalah referensi resmi sesuai mata pelajaran, fase dan kurikulum; sumber regulasi mapel dapat berbeda. Admin mengelola naskah/transkripsi resmi, versi dan penerbitannya. Versi yang sudah terbit immutable, versi lama tetap dapat dirujuk objek historis.
- **TP** adalah tujuan pembelajaran dengan **kode stabil, judul singkat, rumusan yang mengandung kompetensi dan lingkup materi, indikator ketercapaian, urutan, tautan CP**. TP dibuat Guru pengampu per Gen–Mapel; minimal satu versi CP aktif yang relevan, tepat satu CP utama. Satu TP dapat memiliki sejumlah Materi/Submateri berurutan (minimum satu untuk status Siap).
- **ATP** bukan tabel/objek tingkat baru di antara CP dan TP: ATP adalah **rangkaian TP yang urut menurut semester dan Gen–Mapel**. Pemetaan satu Gen–Mapel mencakup enam semester; jumlah TP/materi tidak ditetapkan secara nasional atau dipaksakan seragam.
- **BAB buku ajar** boleh dicantumkan sebagai referensi sumber/kelompok topik secara opsional, bukan entitas, status, prasyarat belajar, halaman atau unit penilaian tersendiri.
- **KBC Kemenag** diintegrasikan secara kontekstual melalui nilai Panca Cinta yang relevan pada materi/aktivitas/soal. KBC tidak membuat hierarki akademik tambahan atau penilaian wajib baru.

## 2. Pengelolaan CP resmi oleh Admin
1. Admin menentukan kurikulum, mapel dan Fase D dari data referensi; menulis **Elemen CP** dan **Rumusan CP** serta kode unik (boleh dibangkitkan sistem). Data sumber hukum direferensikan dari **sumber kurikulum/dokumen resmi bersama**, tidak wajib diulang setiap kali mengisi satu CP.
2. Pada form CP **otoritas penerbit, nomor dokumen resmi, URL sumber dan tanggal berlaku tidak menjadi isian wajib per rumusan**. Identitas/sumber tetap dapat ditelusuri di tingkat referensi dokumen, atau sebagai metadata versi opsional yang diwariskan dari referensi; tidak boleh dibuat data resmi fiktif.
3. Admin menyimpan draf, meninjau, kemudian menerbitkan versi aktif; setiap CP/mapel/fase/kurikulum memiliki satu versi aktif. Perubahan setelah terbit melalui versi baru, tanpa mengubah tautan historis.
4. Guru dapat mengajukan **tambahan rujukan, koreksi transkripsi, catatan lokal** beserta alasan; Admin memutus setujui/tolak/kembalikan perbaikan. Catatan lokal tidak menggantikan rumusan CP resmi.
5. Tidak ada role `cp_review` lain; **akun pengusul yang sama tidak boleh menyetujui usulan akademiknya sendiri** meskipun dapat berganti peran Admin/Guru. Administrasi CP biasa oleh Admin tidak mensyaratkan dua Admin.
6. Seleksi CP pada layar Guru dan Excel hanya dari **versi CP aktif** yang cocok Mapel, Fase D dan Kurikulum; label dan versi terlihat, sumber resmi dapat ditelusuri.

## 3. Paket semester per Gen
- Setiap `paket_semester` memiliki `angkatan_id` (Gen), mapel, semester 1–6, tahun ajaran, kurikulum dan versi. `penugasan_paket` menunjuk paket milik Gen yang sama; hanya satu aktif per Gen–Mapel–Semester–Tahun Ajaran.
- Satu Guru aktif **per Gen–Mapel** melalui `penugasan_guru_gen`, berlaku untuk semester Gen tersebut. Pergantian Guru memindahkan hak operasional, tidak menimpa penilai/progres/versi lampau. CP resmi dapat dirujuk lintas Gen; **paket, TP, materi, soal tidak otomatis dibagi lintas Gen**.
- Semester sebelum `COALESCE(siswa.semester_mulai, ref_angkatan.semester_mulai)` ditandai Dilewati dan tidak menghasilkan nilai/sertifikat.
- TP draf dapat dibuat dan dilengkapi bertahap; hanya **TP Siap/Terkunci** dapat dibuka siswa. Guru tidak harus mengisi enam semester sekaligus.

## 4. Alur Guru yang mengikat
`Login Guru → pilih Gen–Mapel yang diampu → pilih kurikulum → unduh Excel Peta Pembelajaran 6 Semester → susun TP/CP dan Materi/Submateri → unggah → pratinjau → konfirmasi → lengkapi media/petunjuk Resume/HOTS per Materi → impor Word Diagnostik dan Sumatif per TP → checklist → TP Siap`.

- Excel **membentuk struktur TP dan Materi** di aplikasi; Guru tidak diminta menginput ulang data yang telah dimasukkan melalui Excel.
- Alternatif penyuntingan draf langsung dalam aplikasi tetap boleh sebagai pelengkap dan tetap menjaga identitas urutan/versi.
- Publikasi, koreksi kategori A/B/C, dan snapshot berdasarkan **TP**, bukan BAB. Siswa yang telah membuka TP memakai versi konten yang dipin sampai alur tersebut selesai.

## 5. Validasi
CP harus aktif dan cocok; kode TP unik di dalam paket/semester Gen–Mapel; nomor urut TP dan materi tidak bercelah; tepat satu CP utama; semua relasi diperiksa kembali oleh Service. TP yang telah Siap tidak diubah oleh unggah/edit biasa. Detail impor dan readiness: [05](05-PAKET-TP-DAN-IMPOR.md); SQL: [10](10-DATABASE.md).
