# 08 — Penilaian, Remedial, Penyelesaian dan Koreksi Nilai

> **Master v2.5 · 26 Oktober 2026.** Nilai Diagnostik bukan nilai akhir TP, bobot lama 20/30/50 dicabut, tidak ada nilai akhir semester.

## 1. Skema nilai
- Satu TP **Esensial** tidak memiliki penilaian, tetap tercantum di ATP dan sertifikat tanpa nilai.
- Pada TP **NonEsensial**, Diagnostik minimal 10 soal menentukan jalur, tidak masuk rumus nilai. Sumatif minimal 30 soal **selalu wajib**, termasuk untuk siswa yang lulus Diagnostik.
- **Diagnostik selesai sah dengan skor di bawah KKM:** `nilai_tp = Resume×0.20 + HOTS×0.20 + SumatifTerbaik×0.60`.
- **Diagnostik lulus:** `nilai_tp = SumatifTerbaik×1.00`. Resume/HOTS yang dilewati tidak diberi skor nol dan tidak masuk rumus.
- KKM efektif diambil dari pilihan khusus Guru bila disetel, selebihnya default Admin. KKM contoh 80 bukan nilai hard-coded yang selalu dipakai.
- **Remedial nilai hanya satu kali jika Sumatif awal selesai sah < KKM.** `SumatifTerbaik = MAX(SumatifAwal, SumatifRemedial)`. Tidak ada remedial jika nilai awal sudah mencapai KKM.
- **Tes Diagnostik/Sumatif yang tidak selesai** harus dimintakan **izin akses ulang kepada Guru** untuk mengerjakan tahap yang sama; ini bukan pembatalan dan bukan remedial. Tidak ada skor sah atau pengurangan kesempatan sah. Pembatalan ujian hanya untuk Pra-Semester. Histori yang sudah sah tidak dihapus. 
- Setelah semua aktivitas dan kesempatan ujian sah yang relevan diselesaikan, **status TP SELESAI meskipun nilai akhir di bawah KKM**. Informasi tercapai/belum mencapai KKM boleh tampil terpisah dari status progres.
- Guru menilai Resume/HOTS integer 0–100. Jika skor Guru wajib belum ada, **nilai TP Sementara**, bukan mengisi nol. Simpan presisi dua desimal.

## 2. Tidak ada nilai akhir semester
**Tidak menghitung, menyimpan, atau mencetak agregat nilai akhir semester.** Nilai TP NonEsensial dan nilai Ujian Pra-Semester berdiri sendiri. Progres semester dihitung hanya dari status selesai TP NonEsensial dan tidak bergantung pada rerata nilai.

## 3. Penilaian Guru dan koreksi
Guru aktif Gen–Mapel menerima antrean Resume/HOTS final tertua, menilai sesuai hak, dapat melihat snapshot dan penilai historis. Siswa boleh lanjut setelah final walaupun belum dinilai. Koreksi nilai oleh Guru lain pada Gen–Mapel sama dilakukan melalui permohonan yang diputus Admin (larangan self-approval). Jejak perubahan sebelum/sesudah, alasan, nomor versi, dan pemberitahuan siswa wajib.
- **Setelah siswa menggunakan TP, konten soal dan kunci TP tidak boleh diubah.** Jika ditemukan kesalahan akademik pada penilaian siswa yang sudah terjadi, gunakan prosedur **koreksi nilai siswa** yang terpisah dan diaudit dengan melihat snapshot/percobaan sah; jangan memperbarui sumber soal/kunci atau KKM TP yang terkunci. Tes mandiri yang tidak selesai tidak diberi nilai; Pra-Semester yang dibatalkan juga tanpa nilai. Perubahan nilai sah tidak otomatis membatalkan status TP Selesai yang ditentukan oleh aktivitas.
- Koreksi yang memengaruhi daftar nilai sertifikat mengikuti prosedur revisi dokumen 09. PDF lama tidak ditimpa/dicabut.
- Indikator kemiripan jawaban tetap 3-gram kata Jaccard dengan ambang bawaan 60% untuk **perlu ditinjau**, tanpa hukuman otomatis. Pemeriksaan dibatasi materi/TP, Gen dan hak Guru.

## 4. Nilai Pra-Semester
Bank ujian gabungan seluruh BAB pada semester, minimal 30 soal. Mesin, fullscreen, jeda dan satu remedial mengikuti pola Sumatif. Skor terbaik adalah **nilai Pra-Semester mandiri**. Jadwal ditentukan Admin dan hanya siswa yang menyelesaikan seluruh TP NonEsensial berhak mengikutinya; pelaksanaan serentak sesuai jadwal.

## 5. Ekspor Nilai Guru
- Guru dapat mengekspor **seluruh siswa dalam Gen–Mapel yang diampu** dengan identitas minimal yang berwenang, kode/nama TP, nilai masing-masing TP NonEsensial, penanda TP Esensial tanpa nilai, dan nilai Pra-Semester.
- Export **kapan saja**, termasuk saat baru terdapat **satu nilai TP** atau sebagian siswa belum memiliki nilai. Nilai yang belum tersedia berupa kosong/penanda belum ada, **bukan nol**; hasil sementara dibedakan dari final.
- Kolom **Nilai Akhir Semester tidak ada**. Izin filter dan sanitasi formula injection XLSX/CSV wajib; hash, sandi dan riwayat ujian privat tidak diekspor.


## Edit konten TP dan nilai historis (final 2 November 2026)
- Status TP Siap bukan gerbang penguncian. Guru G1 boleh menyunting KKM tunggal, soal dan konten TP Siap selama belum ada siswa yang menggunakan TP; validasi kesiapan dan snapshot aktif diulang sebelum tersedia kepada siswa.
- Setelah minimal satu siswa memakai TP, konten akademik TP terkunci dan **hasil/percobaan/nilai tidak boleh diubah oleh perubahan konten TP**. Koreksi nilai siswa yang diatur tersendiri tetap terpisah, tidak membuka hak sunting TP. KKM Diagnostik dan Sumatif tetap satu nilai.
- CP boleh disunting Admin kapan saja; perubahan CP tidak memicu perhitungan ulang nilai siswa atau perubahan bobot 20/20/60 maupun 100% Sumatif.
