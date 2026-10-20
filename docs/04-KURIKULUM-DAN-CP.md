# 04 — Kurikulum, Fase D, CP, TP dan Paket Semester

## 1. Referensi resmi
SLA-SKS pada rilis ini melayani MTs Fase D kelas VII–IX dengan enam semester SLA. CP dirujuk sesuai **otoritas resmi masing-masing mata pelajaran**, bukan diasumsikan semua mapel berada pada satu ketetapan. `ref_kurikulum` menyimpan nama, versi, sumber dan dasar hukum; `ref_cp_versi` menyimpan rumusan resmi, elemen, otoritas, nomor dokumen, sumber URL, tanggal berlaku dan status. Bila dasar sumber berubah, terbitkan **versi referensi baru**; versi dipublikasikan tidak diubah diam-diam.

TP dibuat Guru pengampu dengan `kode_tp` stabil, rumusan, indikator ketercapaian, urutan ATP, serta minimal satu tautan CP yang cocok mapel/fase/kurikulum. Tepat satu tautan CP per TP ditandai `utama`. CP pada Fase D berlaku lintas VII–IX, tidak wajib dibuat CP berbeda untuk setiap kelas. BAB Siap tidak boleh mengubah tautan TP–CP melalui edit/impor biasa.

## 2. Pengelolaan CP oleh Admin
1. Guru mengusulkan `tambahan_rujukan`, `koreksi_transkripsi`, atau `catatan_lokal` dengan alasan dan sumber bila ada. Usulan dikunci setelah dikirim.
2. **Admin langsung memeriksa** kesesuaian sumber resmi, elemen/mapel/fase, kejelasan alasan, dan konflik dengan versi yang ada. Tidak ada role/permission `cp_review` dan tidak ada review oleh Guru lain sebagai gerbang wajib.
3. Admin `setujui` / `tolak` / `kembalikan_perbaikan`; identitas, waktu, bukti, alasan dan versi asal tersimpan.
4. Jika disetujui, Service membuat `ref_cp_versi` baru immutable dan mengaktifkannya untuk tautan baru; tautan BAB Siap lama tetap merujuk versi historis.
5. `catatan_lokal` merupakan metadata penjelas, bukan pengganti rumusan resmi. Admin tidak mengubah materi/soal atau nilai dengan hak mengelola CP.
6. Bila akun pengusul adalah akun Admin+Guru yang sama, persetujuan usulan akademiknya tidak dilakukan oleh akun tersebut; sediakan peringatan atas benturan identitas.

## 3. Paket semester berversi
Setiap `paket_semester` memiliki satu pemilik `angkatan_id` (Gen) dan konteks mapel/semester/tahun ajaran/kurikulum/versi. `penugasan_paket` menunjuk paket aktif **milik Gen yang sama**, tidak membuat konten bersama dan tidak memberi kewenangan Guru. Untuk `(angkatan, mapel, semester, tahun_ajaran)` hanya ada satu penugasan paket aktif; setiap revisi mempertahankan versi dan progres siswa yang telah menggunakan paket terdahulu. Tidak menghapus paket historis atau memindahkan siswa ke Gen lain otomatis.

Admin menetapkan satu Guru aktif melalui `penugasan_guru_gen` untuk Gen–Mapel, berlaku lintas semester. Guru hanya boleh menyunting draf, menerbitkan dan menilai dalam cakupan Gen–Mapel tersebut. Setelah Guru berganti, kewenangan operasional berpindah, bukan versi konten atau penilai historis. Referensi CP resmi boleh digunakan oleh banyak Gen dengan validasi mapel/fase/kurikulum.

**Semester mulai:** `COALESCE(siswa.semester_mulai, ref_angkatan.semester_mulai)`. Angkatan dapat dimulai pada semester berbeda, contoh konfigurasi awal Gen 2025 semester 4, Gen 2026 semester 1 bila relevan pada instalasi. Tahun/angkatan nyata ditentukan Admin, bukan terikat seed hardcode.

## 4. Wizard Guru
`Pilih Gen → pilih mapel yang diampu → semester → rencanakan BAB → buat TP dan kaitkan CP → unduh Excel dari struktur aktual → isi/unggah Excel → lengkapi berkas → checklist → BAB Siap`.

Halaman pembelajaran siswa hanya membuka TP dari BAB Siap, sesuai urutan dan paket yang ditugaskan ke siswa. Versi BAB Siap yang digunakan untuk siswa di-*pin* ketika TP pertama kali dibuka.

## 5. Kontrol kualitas
- CP resmi published dan cocok dengan mapel/fase/kurikulum sebelum BAB Siap.
- Kode TP unik di BAB; urutan TP/ATP dan materi tanpa celah.
- Siswa yang telah masuk TP tidak dipindahkan ke versi CP/TP baru.
- Ekspor laporan menunjukkan kode TP, rumusan, CP yang dipakai, dan versi referensi sesuai hak akses.
- Sistem menolak referensi CP yang sudah diganti apabila tidak valid untuk penautan baru, tetapi tidak menghapus keterkaitan historis.

## 6. Batas data
Tidak membuat rombel sebagai sumber kewenangan, maksimal satu Guru aktif per Gen–Mapel, tidak mengubah nilai atau rekam ujian lama akibat pembaruan CP. Detail skema ditulis di `10-DATABASE.md`, izin di `03-PERAN-DAN-HAK-AKSES.md`.
