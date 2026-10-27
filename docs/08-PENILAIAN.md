# 08 — Penilaian, Remedial, Penyelesaian dan Koreksi Nilai

> **Master v2.5 · 26 Oktober 2026.** Nilai Diagnostik bukan nilai akhir TP, bobot lama 20/30/50 dicabut, tidak ada nilai akhir semester.

## 1. Skema nilai
- Satu TP **Esensial** tidak memiliki penilaian, tetap tercantum di ATP dan sertifikat tanpa nilai.
- Pada TP **NonEsensial**, Diagnostik minimal 10 soal menentukan jalur, tidak masuk rumus nilai. Sumatif minimal 30 soal **selalu wajib**, termasuk untuk siswa yang lulus Diagnostik.
- **Diagnostik tidak lulus / langsung belajar setelah pembatalan:** `nilai_tp = Resume×0.20 + HOTS×0.20 + SumatifTerbaik×0.60`.
- **Diagnostik lulus:** `nilai_tp = SumatifTerbaik×1.00`. Resume/HOTS yang dilewati tidak diberi skor nol dan tidak masuk rumus.
- KKM efektif diambil dari pilihan khusus Guru bila disetel, selebihnya default Admin. KKM contoh 80 bukan nilai hard-coded yang selalu dipakai.
- **Remedial satu kali jika Sumatif awal < KKM.** `SumatifTerbaik = MAX(SumatifAwal, SumatifRemedial)`. Tidak ada remedial jika nilai awal sudah mencapai KKM.
- Setelah semua aktivitas dan kesempatan ujian sah yang relevan diselesaikan, **status TP SELESAI meskipun nilai akhir di bawah KKM**. Informasi tercapai/belum mencapai KKM boleh tampil terpisah dari status progres.
- Guru menilai Resume/HOTS integer 0–100. Jika skor Guru wajib belum ada, **nilai TP Sementara**, bukan mengisi nol. Simpan presisi dua desimal.

## 2. Tidak ada nilai akhir semester
**Tidak menghitung, menyimpan, atau mencetak agregat nilai akhir semester.** Nilai TP NonEsensial dan nilai Ujian Pra-Semester berdiri sendiri. Progres semester dihitung hanya dari status selesai TP NonEsensial dan tidak bergantung pada rerata nilai.

## 3. Penilaian Guru dan koreksi
Guru aktif Gen–Mapel menerima antrean Resume/HOTS final tertua, menilai sesuai hak, dapat melihat snapshot dan penilai historis. Siswa boleh lanjut setelah final walaupun belum dinilai. Koreksi nilai oleh Guru lain pada Gen–Mapel sama dilakukan melalui permohonan yang diputus Admin (larangan self-approval). Jejak perubahan sebelum/sesudah, alasan, nomor versi, dan pemberitahuan siswa wajib.
- Koreksi kesalahan soal/kunci menggunakan snapshot setiap percobaan sah dan jawaban tersimpan, bukan soal terbaru. Ujian dibatalkan tidak diberi nilai. Skor ujian sah dan hasil remedial terbaik dihitung ulang; status **selesai TP tetap berdasarkan penyelesaian aktivitas**, tidak turun menjadi belum selesai hanya karena koreksi skor di bawah KKM.
- Koreksi yang memengaruhi daftar nilai sertifikat mengikuti prosedur revisi dokumen 09. PDF lama tidak ditimpa/dicabut.
- Indikator kemiripan jawaban tetap 3-gram kata Jaccard dengan ambang bawaan 60% untuk **perlu ditinjau**, tanpa hukuman otomatis. Pemeriksaan dibatasi materi/TP, Gen dan hak Guru.

## 4. Nilai Pra-Semester
Bank ujian gabungan seluruh BAB pada semester, minimal 30 soal. Mesin, fullscreen, jeda dan satu remedial mengikuti pola Sumatif. Skor terbaik adalah **nilai Pra-Semester mandiri**. Jadwal ditentukan Admin dan hanya siswa yang menyelesaikan seluruh TP NonEsensial berhak mengikutinya; pelaksanaan serentak sesuai jadwal.

## 5. Ekspor Nilai Guru
- Guru dapat mengekspor **seluruh siswa dalam Gen–Mapel yang diampu** dengan identitas minimal yang berwenang, kode/nama TP, nilai masing-masing TP NonEsensial, penanda TP Esensial tanpa nilai, dan nilai Pra-Semester.
- Export **kapan saja**, termasuk saat baru terdapat **satu nilai TP** atau sebagian siswa belum memiliki nilai. Nilai yang belum tersedia berupa kosong/penanda belum ada, **bukan nol**; hasil sementara dibedakan dari final.
- Kolom **Nilai Akhir Semester tidak ada**. Izin filter dan sanitasi formula injection XLSX/CSV wajib; hash, sandi dan riwayat ujian privat tidak diekspor.
