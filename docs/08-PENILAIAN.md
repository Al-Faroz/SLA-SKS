# 08 — Penilaian, Ketuntasan dan Koreksi Nilai

## 1. Komponen penilaian
- Guru menilai setiap resume dan tugas HOTS wajib dengan integer **0–100**, disertai umpan balik opsional maksimal 1000 karakter.
- Nilai TP dari jalur belajar = `rata_rata_resume × bobot_resume/100 + nilai_tugas × bobot_tugas/100 + nilai_sumatif × bobot_sumatif/100`, bobot default **20/30/50** dan total harus 100. Setiap materi wajib selesai dan resume dinilai; komponen wajib bernilai bobot nol **tetap harus dinilai** jika memang diwajibkan pada jalur belajar.
- Nilai TP jalur diagnostik = skor diagnostik, tidak ditambah aktivitas sukarela. Nilai akhir mapel/semester = rata-rata `nilai_tp` pada seluruh TP paket sesuai aturan historis. Simpan presisi 2 desimal; tampilkan pembulatan sesuai jenis laporan/sertifikat.
- Nilai dengan penilaian wajib belum lengkap ditampilkan **Sementara**; sertifikat tidak diterbitkan jika satu pun komponen jalur wajib belum diberi nilai.
- Kelulusan TP ditentukan skor diagnostik/sumatif dibanding KKM; bukan nilai gabungan. Pada koreksi hasil ujian selesai, **status ketuntasan dihitung ulang**, termasuk kemungkinan turun dari tuntas ke belum tuntas.

## 2. Proses penilaian Guru
Antrean diurut tertua, tampil mapel/siswa/TP/materi, jawaban dan petunjuk. Guru aktif yang mengampu **Gen–Mapel siswa terkait** boleh menilai pengiriman belum dinilai, termasuk kiriman saat Guru lama mengampu pasangan Gen–Mapel yang sama. Guru mapel sama di Gen lain tidak berwenang. Penilai asli disimpan di `dinilai_oleh` dan `dinilai_pada`; jangan menimpa identitas penilai historis.

Guru yang menilai sendiri dapat memperbaiki penilaian lewat jalur koreksi AB-33 dengan `riwayat_nilai` dan `nilai_diubah`. Koreksi harus mematuhi konsistensi sertifikat jika paket sudah bersertifikat.

## 3. Koreksi nilai oleh guru pengganti — AB-33a
1. Guru pengampu baru **pada Gen–Mapel yang sama** mengusulkan perubahan nilai resume/tugas yang telah dinilai Guru lama; isi nilai lama, nilai baru 0–100, alasan akademik dan referensi versi asli.
2. Admin meninjau dan memutus `setujui`/`tolak`/`kembalikan_perbaikan` atas **usulan yang tersimpan**, tidak mengetik nilai langsung. Pemohon dan pemutus tidak boleh `pengguna_id` sama.
3. Setelah sah, sistem memeriksa pengampu masih aktif, nilai asal dan `row_version` belum berubah, kasus belum dipakai; penerapan idempoten dengan audit sebelum/sesudah.
4. Hitung ulang agregat; status ketuntasan TP **tidak berubah semata-mata karena koreksi resume/tugas**. Jika nilai sertifikat tercetak berubah, prosedur sertifikat revisi `09-SERTIFIKAT-DAN-LAPORAN.md` dijalankan secara konsisten.
5. Jangan mengubah snapshot atau atribusi guru lama. Siswa diberi notifikasi **setelah** penerapan resmi, bukan ketika pengajuan.

## 4. Kesalahan soal atau kunci pada ujian yang telah selesai
1. Guru pengampu menemukan kesalahan kunci/soal terhadap snapshot ujian sah; ajukan satu kasus beralasan dengan objek soal, versi, hasil telaah dan himpunan percobaan sah terdampak.
2. Admin menyetujui kasus berdasarkan bukti; bila akun sama mengajukan dan memutus, tolak approval diri sendiri. Persetujuan koreksi konten BAB Siap **bukan** otomatis koreksi nilai ujian historis.
3. Gunakan **snapshot soal/opsi dan jawaban masing-masing percobaan sah**, bukan kunci soal yang mungkin telah berubah, untuk menghitung ulang semua percobaan sah terdampak secara deterministik. Simpan skor lama/baru, aturan koreksi, actor, tanggal, dan alasan. Jangan memberi skor pada ujian batal.
4. Hitung ulang skor sumatif yang efektif (terbaik dari percobaan sah), status TP tuntas/belum, status BAB/semester dan akses berikutnya. **Keputusan pemilik: status ketuntasan dihitung ulang**, bukan dibekukan. Jangan menghapus progres lama atau attempt.
5. Jika nilai sertifikat yang sudah terbit berubah, jalankan mekanisme revisi secara aman. Jika akibat koreksi TP menjadi belum tuntas, tandai kondisi akademik sebagai `perlu_melanjutkan_pembelajaran` dan jangan mengarang pencabutan sertifikat; catat ketidaksesuaian historis secara transparan hingga ketuntasan terpenuhi lagi. Sertifikat lama tetap ada, tidak dihapus atau dicabut. Ini adalah batas konsistensi yang harus diuji agar laporan tidak memalsukan status.

## 5. Penghitungan ulang aman
`NilaiService` dijalankan setelah tindakan sah, di dalam transaksi untuk nilai dan agregat. Bila melibatkan penerbitan PDF baru, buat kandidat PDF/QR terlebih dulu dan aktifkan nilai + penggantian sertifikat secara terkoordinasi sesuai AB-39a. Perbarui pointer versi hanya sekali per peristiwa; concurrent approval tidak boleh menimbulkan dua nilai akhir aktif atau dua sertifikat berlaku.

## 6. Indikator kemiripan tulisan — AB-36
Kemiripan **bukan penilaian otomatis kecurangan**. Normalisasi jawaban (huruf kecil, tanpa tanda baca, rapatkan spasi), buat himpunan **3-gram kata** (jika kurang dari 3 kata, gunakan kata tunggal), lalu `skor_kemiripan = |A∩B| / |A∪B| × 100`. Resume dibandingkan dengan resume siswa lain **pada materi sama**, HOTS dengan tugas pada **TP sama**. Ambang bawaan `kemiripan_ambang = 60` menampilkan badge **Perlu dicek** pada antrean Guru dan informasi pasangan hanya untuk pihak berwenang. **Tidak memblokir siswa, tidak mengubah nilai atau ketuntasan**, dan tidak memberi label salah tanpa pemeriksaan Guru.

**Batas lintas Gen:** setiap antrean, koreksi nilai/kunci, skor sah, rapor dan penelusuran riwayat menampilkan konteks Gen dan paket/versi yang tepat. Guru hanya memperoleh siswa serta paket dari Gen–Mapel yang aktif ditugaskan kepadanya; Admin tetap melihat seluruh cakupan dan Pimpinan hanya baca. Pergantian Guru di satu Gen tidak membuka data Gen lain. Histori `dinilai_oleh`, peristiwa dan keputusan asli tetap utuh.

## 7. Nilai, riwayat dan komunikasi siswa
Siswa melihat angka/status sementara dan perubahan resmi setelah commit, bukan riwayat internal persetujuan sensitif. Guru/Admin dapat menelusuri penilaian dan koreksi menurut hak akses; Pimpinan ringkasan baca-saja. Rapor menunjukkan komponen TP, cara tuntas, hasil sumatif/diagnostik sah, nilai akhir dan status saat ini. Ekspor mengikuti izin dan penanda Sementara.
