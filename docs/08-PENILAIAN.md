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
Bank ujian gabungan seluruh BAB pada semester, minimal 30 soal. Mesin, fullscreen, jeda dan satu remedial mengikuti pola Sumatif. Skor terbaik adalah **nilai Pra-Semester mandiri** untuk rekap/monitoring, **bukan nilai sertifikat maupun syarat penerbitannya**. Jendela boleh mulai dan durasi individual ditentukan Admin per Gen–Mapel–Semester, dan hanya siswa yang menyelesaikan seluruh TP NonEsensial berhak mengikutinya; peserta paket sama mengikuti jendela yang sama.

## 5. Ekspor Nilai Guru
- Rekap Nilai Guru menggunakan **dua halaman dan dua berkas ekspor terpisah** di bawah menu `Rekap Nilai`: **Nilai TP** dan **Nilai Pra-Semester**. Data dan sheet kedua kategori **tidak dicampur dalam satu ekspor**.
- Guru dapat mengekspor **seluruh siswa dalam Gen–Mapel yang diampu** dengan identitas minimal yang berwenang. Ekspor Nilai TP berisi kode/nama TP, nilai TP NonEsensial, serta penanda TP Esensial tanpa nilai jika secara eksplisit dipilih. Ekspor Pra-Semester berisi nilai ujian normal, remedial, nilai terbaik, dan status nilai per siswa/semester.
- Export **kapan saja**, termasuk saat baru terdapat **satu nilai TP** atau sebagian siswa belum memiliki nilai. Nilai yang belum tersedia berupa kosong/penanda belum ada, **bukan nol**; hasil sementara dibedakan dari final.
- Kolom **Nilai Akhir Semester tidak ada**. Izin filter dan sanitasi formula injection XLSX/CSV wajib; hash, sandi dan riwayat ujian privat tidak diekspor.


## Edit konten TP dan nilai historis (final 2 November 2026)
- Status TP Siap bukan gerbang penguncian. Guru G1 boleh menyunting KKM tunggal, soal dan konten TP Siap selama belum ada siswa yang menggunakan TP; validasi kesiapan dan snapshot aktif diulang sebelum tersedia kepada siswa.
- Setelah minimal satu siswa memakai TP, konten akademik TP terkunci dan **hasil/percobaan/nilai tidak boleh diubah oleh perubahan konten TP**. Koreksi nilai siswa yang diatur tersendiri tetap terpisah, tidak membuka hak sunting TP. KKM Diagnostik dan Sumatif tetap satu nilai.
- CP boleh disunting Admin kapan saja; perubahan CP tidak memicu perhitungan ulang nilai siswa atau perubahan bobot 20/20/60 maupun 100% Sumatif.


### Penilaian Pra-Semester dan pembatalan (3 November 2026)
KKM Pra-Semester: default Admin atau nilai khusus Guru untuk paket Gen–Mapel–Semester (tidak menyalin KKM salah satu TP). Nilai awal hanya dari percobaan `selesai_sah`; bila di bawah KKM tersedia **satu remedial sah** dengan jadwal tersendiri oleh Admin. Skor terbaik menjadi nilai Pra-Semester mandiri, tanpa Nilai Akhir Semester. Percobaan batal/tidak hadir tidak memiliki nilai, tidak memotong jatah sah, dan hanya boleh dikerjakan lagi pada **susulan Admin**. Batal saat remedial memerlukan **susulan remedial**, tanpa tambahan jatah sah.


### A8-4 — Penentuan nilai mandiri setelah susulan/remedial (3 November 2026)
- Hanya percobaan `selesai_sah` dapat menghasilkan nilai; `0.00` merupakan nilai sah, **bukan** `NULL`. Pembatalan tidak pernah mendapat skor.
- Satu nilai normal sah membuka nilai Pra-Semester **sementara** bila <KKM (menunggu satu remedial sah terjadwal), atau **final** bila ≥KKM.
- Satu remedial sah (setelah normal sah <KKM) membuat `nilai_terbaik = MAX(skor_normal_sah, skor_remedial_sah)` dan status **final**. Susulan normal/remedial tidak menambah jatah kesempatan sah. Tetap nilai mandiri, tidak menghitung rata-rata akhir semester.
- Logika nilai dan kesempatan dipusatkan pada `PenilaianPraSemesterService` (pure). Adapter rekap/ekspor Guru telah dikodekan pada A9 menggunakan uji database sintetis; penerimaan terhadap database XAMPP tetap mensyaratkan audit dump lokal terbaru dan uji browser.

### A9 — Tata Rekap Nilai terpisah dan penyaringan Guru (3 November 2026)

- Menu Guru: **Rekap Nilai** → **Nilai TP** dan **Nilai Pra-Semester**. Tidak menggabungkan hasil Pra-Semester ke file XLSX TP. Halaman Penilaian Resume/HOTS tetap berbeda dari kedua halaman rekap.
- Antrean Penilaian: filter penugasan **Gen–Mapel**, Semester, BAB, dan Jenis Tugas (Resume/HOTS). Indikator kemiripan persentase tidak ditampilkan lagi pada kolom Jenis di tabel antrean; pada detail dapat tetap dilihat Guru. Petunjuk dari Materi snapshot dan jawaban final siswa ditampilkan dalam dua kartu berlatar berbeda.
- Rekap Nilai TP: filter Gen–Mapel, Semester, Jenis TP dan BAB. **Default NonEsensial**; Esensial dan Semua tersedia sebagai pilihan. File Excel TP mengikuti filter; unduh semua semester mengikuti jenis TP, tetapi tidak memakai BAB khusus semester. Skor 0 sah, nilai yang belum ada kosong.
- Rekap Nilai Pra-Semester: per siswa dan semester, percobaan hanya `selesai_sah` menghasilkan skor. Nilai terbaik berasal dari `PenilaianPraSemesterService`, bukan rata-rata akhir semester. Hasil bisa kosong, Sementara, Final, menunggu susulan/remedial. Semua siswa Gen tetap masuk.
- Implementasi A9 hanya baca, filter, dan ekspor. Tidak ada perubahan SQL, aturan nilai, tahapan ujian maupun jadwal Admin. Sebelum penerimaan hasil pada database lokal, tetap wajib audit dump XAMPP terbaru sesuai `AGENTS.md`.

### Penegasan final hubungan nilai Pra-Semester dan sertifikat (3 November 2026)

- **Pra-Semester bukan syarat penerbitan sertifikat**, dan **nilainya tidak dicetak dalam sertifikat**. Ketuntasan TP NonEsensial dan seluruh nilai wajib TP yang sudah Final tetap prasyarat sertifikat sesuai Master 09.
- Hasil Pra-Semester yang belum ada, batal, masih sementara, atau mengalami remedial **tidak memblokir sertifikat** dan perubahan hasilnya tidak memicu revisi PDF/QR.
- Fungsi Pra-Semester adalah checkpoint akademik sebelum Ujian Semester yang sesungguhnya; keputusan ini tidak mengesahkan perhitungan Nilai Akhir Semester maupun menambahkan mesin Ujian Semester baru.


### A14-2 — Pengajuan dan keputusan koreksi nilai Guru (4 November 2026)

Implementasi khusus **nilai Resume/HOTS yang sudah dinilai pada TP NonEsensial dengan progres Tuntas dan nilai Sumatif tersedia**. Tidak membuka fitur ubah soal/kunci/TP, dan **tidak** memakai tabel lama `permohonan_koreksi_tp` kategori A/B/C. Guru G1 yang sedang ditugaskan dapat melihat jawaban/snapshot final, skor penilai historis dan pratinjau nilai TP memakai rumus resmi 20/20/60; alasan akademik wajib, nilai usulan 0–100 integer. Pengajuan menjadi **Diajukan** tanpa mengubah nilai. Satu sumber skor hanya boleh mempunyai satu pengajuan terbuka; pengajuan yang sudah diputus memiliki nomor versi berurutan.

Admin aktif **yang bukan akun Guru pengusul** dapat menyetujui/menolak beserta catatan wajib. Sebelum persetujuan, Service memeriksa ulang Guru G1, status sumber, ID dan snapshot TP, skor lama, status nilai TP serta bobot hasil hitung. Persetujuan atomik memperbarui skor sumber dan komponen/total `nilai_tp`, tanpa mengganti `dinilai_oleh` dan umpan balik historis; penolakan tidak mengubah nilai. Keduanya menyimpan audit, versi dan riwayat sebelum/sesudah serta notifikasi per penerima. Nilai TP tetap berstatus Final atau Sementara sesuai kalkulator sah; progres TP Tuntas **tidak diubah**.

Jika ada sertifikat aktif dan nilai TP yang tercetak berubah, koreksi ditandai **Perlu Tinjau Sertifikat**. Admin perlu menerbitkan versi pengganti satu per satu melalui Master 09; PDF/QR historis tidak dihapus, ditimpa, dicabut atau diperbarui otomatis.

**Batas tahap ini:** koreksi angka hasil mesin Diagnostik, Sumatif dan Pra-Semester **belum dilaksanakan**. Perlu audit terpisah atas butir/attempt `selesai_sah`, bukan menyunting bank soal atau mengubah nilai secara bebas.
