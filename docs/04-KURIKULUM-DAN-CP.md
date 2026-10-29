# 04 — Kurikulum, CP, Semester, BAB, TP dan ATP

> **Master v2.5 · Keputusan 26 Oktober 2026.** Menggantikan model v2.4 tanpa BAB. Kontrak akademik: **Mapel → satu CP operasional → Semester → BAB → TP**. ATP adalah rangkaian BAB/TP yang disusun Guru.

## 1. Hierarki dan cakupan
- MTs Fase D: semester SLA 1–2 = kelas VII, 3–4 = VIII, 5–6 = IX. Gen/angkatan, guru pengampu dan paket tetap terpisah menurut **Gen–Mapel** (G1). Kebijakan SKS dua tahun dan semester mulai siswa tidak berubah.
- **Satu CP untuk satu Mapel** pada satu kurikulum/fase yang berlaku. CP dapat mempunyai beberapa elemen/rumusan; ini bukan beberapa CP operasional. Elemen dan versi sumber resmi lama tidak dihapus.
- Setiap Semester terdiri dari beberapa BAB; BAB mempunyai beberapa TP; setiap TP hanya mempunyai satu jenis, **Esensial** atau **NonEsensial**.
- **BAB:** `nama_bab` maksimal 50 karakter, `deskripsi_bab` maksimal 225 karakter, `nomor_bab` positif.
- **TP:** `nama_tp` maksimal 50 karakter, `deskripsi_tp` maksimal 225 karakter berupa bunyi asli tujuan pembelajaran, `jenis_tp`, `kode_tp`, dan `urutan_dalam_bab`. Rumusan akademik lengkap tetap terlihat pada detail, tidak dipotong permanen oleh card.
- **TP Esensial:** tetap wajib masuk ATP dan memiliki **satu Materi lengkap** sebagai konten akademik Guru; pemakaian/aktivitas serta nilai siswa mengikuti alur siswa (saat ini tidak dihitung progres/nilai).
- **TP NonEsensial:** juga tepat **1 Materi** lengkap dengan satu media jenis YouTube/audio/gambar/PDF, Resume, HOTS dan bank soal Diagnostik/Sumatif sesuai alur siswa.

## 2. Kode dan penomoran
Kode TP: `KODEMAPEL.KELAS.NOMOR_BAB.NOMOR_TP_DALAM_BAB`, misalnya `BIND.8.1.1`. Dibangkitkan/validasi server dari konteks mapel, tingkat kelas, nomor BAB dan urutan TP dalam BAB; guru tidak mengetik kode yang bertentangan dengan konteks.
- Nomor BAB berlanjut antara semester 1–2 (kelas VII), 3–4 (VIII) dan 5–6 (IX), lalu kembali dari BAB 1 pada tingkat kelas berikutnya.
- Nomor TP dalam BAB dimulai dari 1; tidak sama dengan nomor TP global semester.
- Contoh: `BIND.7.1.1` s.d. `BIND.7.6.6` untuk dua semester kelas VII bila BAB semester genap berlanjut sampai 6. Jumlah riil BAB/TP tidak dibatasi contoh.
- Unik menurut Gen–Mapel–Tingkat Kelas–Tahun Ajaran–Kurikulum; validasi lintas paket semester berpasangan dilakukan server. Pengurutan tidak mengubah kode TP yang sudah dipakai siswa; koreksi melalui prosedur akademik berversi.

## 3. Pengelolaan CP oleh Admin
Admin mengelola CP resmi, sumber dan versi menurut Mapel/Fase/Kurikulum. Versi resmi aktif immutable; elemen CP dapat lebih dari satu tanpa menggandakan identitas CP Mapel. Usulan koreksi oleh Guru diputus Admin, larangan self-approval untuk akun sama tetap berlaku. Jangan membuat teks sumber hukum fiktif.

## 4. Paket, Gen dan ATP Guru
Satu `paket_semester` per Gen–Mapel–Semester–Tahun Ajaran/Kurikulum/versi. Guru aktif G1 menyusun **semua BAB dan TP, baik Esensial maupun NonEsensial**, langsung di aplikasi **atau impor dari template Excel ATP yang sudah ada dan akan direvisi**. Impor dilakukan dari halaman khusus Guru **Peta ATP → Kelola ATP Excel** (G05), untuk workbook enam semester yang boleh berisi sebagian semester. **Hanya semester dengan paket sah dan tanpa BAB/TP apa pun yang boleh menerima penambahan**. Semester terisi tidak pernah diperbarui dari Excel; unggah ulang identik dibaca sebagai *Tetap* tanpa penulisan. Baris hilang tidak menghapus data. Pratinjau dan konfirmasi atomik tetap wajib.
- Template awal `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx` sudah dipakai sebagai acuan desain. **Keputusan terbaru: Excel ATP manual (.xlsx), tanpa macro dan tombol Proses.** File manual `.xlsx` telah diajukan sebagai template resmi pada branch fitur sesudah PR #68, dengan kontrak rentang sel di `KONTRAK-TEMPLATE-ATP-MANUAL-V25.md`. Guru mengisi BAB dan TP pada 6 sheet semester; Kode TP **tidak diisi Guru** dan selalu dibentuk oleh server saat pratinjau/konfirmasi impor berdasarkan Mapel, Kelas, Nomor BAB, Urutan TP. Generator unduhan akan menambahkan konteks Gen–Mapel/CP dari server.
- Guru mengelola BAB, TP, materi tunggal setiap TP, petunjuk Resume/HOTS dan bank soal; TP Siap/riwayat snapshot serta koreksi A/B/C mengikuti aturan lama yang kompatibel.
- Paket, konten, soal dan nilai antargen tidak otomatis dibagikan, walaupun kode TP dapat serupa.

## 5. Validasi publikasi
**Seluruh TP**, tanpa membedakan Esensial dan NonEsensial, wajib mempunyai tepat satu Materi lengkap dan media sah saat disiapkan. Pembatasan akses siswa/penilaian berada di Service siswa, bukan pada izin membuat/menyimpan Materi Guru. Kesiapan akademik, versi, dan snapshot tetap diaudit. TP lama dengan beberapa materi harus diaudit/diarsip, bukan diam-diam ditetapkan aktif.

Detail: [05](05-PAKET-TP-DAN-IMPOR.md), [06](06-ALUR-PEMBELAJARAN.md), [10](10-DATABASE.md).

**Keputusan final 29-10-2026:** Guru mengisi Nomor BAB, Urutan TP, Jenis, Nama, Deskripsi, rencana Materi/media, dan Catatan. Kode TP dibangkitkan `KodeTpService`; kolom Elemen CP dihapus dari input Excel. Sheet REFERENSI CP tetap wajib. Format V25-1 tidak diterima lagi setelah V25-2.
