# Checkpoint A13-6/A13-7 — Fondasi Aman Pra-Dump

Tanggal: 4 November 2026. Branch `feature/a13-6-a13-7-kontrak-pra-db-20261104`; dari `fix/a13-pengumuman-guru-hanya-siswa-gen-20261104` (A13-5, PR #126, belum merge main).

## Temuan
1. Belum terdapat file dump `sla_sks_v4` terbaru pasca `V4-0012-PENGUMUMAN-FAQ` yang terverifikasi pada konteks percakapan/Library yang tersedia. Dump lama dan SQL patch repo bukan bukti kondisi DB XAMPP sekarang.
2. Halaman publik `GET /kebijakan-privasi` memang sudah ada dan secara aman menjawab HTTP 503 “Belum diterbitkan”. Naskah kebijakan privasi resmi, versi dan tanggal disahkan madrasah **belum disediakan**, sehingga jangan mengarang isi.
3. Notifikasi internal belum memiliki adapter penyimpanan/penerima atau keterkaitan event yang dapat dijamin terhadap transaksi akademik. Keduanya butuh audit skema dan QA terhadap dump asli.

## Implementasi yang benar-benar dikerjakan
- `app/Services/NotifikasiKontrakService.php`: katalognya dibatasi event akademik resmi, validasi peran penerima, ID, versi sumber, pesan generik tanpa data siswa, kunci idempoten SHA-256 untuk tahap penyimpanan kelak. **Ini bukan pengirim notifikasi atau verifikasi kepemilikan sumber melalui DB**.
- `app/Services/KebijakanPrivasiValidasiService.php`: validator murni versi/tanggal berlaku/naskah, normalisasi newline, checksum serta konfirmasi pengesahan yang eksplisit. **Konfirmasi bukan bukti hukum, dan metode ini tidak menyimpan/menerbitkan teks**.
- `tests/unit/A13KontrakNotifikasiPrivasiTest.php`: tes jenis event, role, deduplikasi, ID, pembatasan data, naskah invalid, checksum, dan kejujuran state publik belum terbit.
- Master 10/11/12/14/16, indeks dokumen, dan catatan keputusan diperbarui. Workflow menjalankan PHP lint dan tes/unit regresi.
- **Tidak mengubah SQL, controller, route, view, .env, root, data XAMPP, histori akademik ataupun notifikasi aktif.** Belum ada tombol/menu baru yang hanya berfungsi sebagai dekorasi.

## Kontrak implementasi berikutnya setelah dump
**A13-6:**
1. Cocokkan versi DB, nama database, tabel/kolom/FK/index `pengguna`, `pengguna_peran`, `siswa`, `penugasan_guru_gen`, `progres_tp`, percobaan/keputusan ujian, penilaian, sertifikat, log aktivitas, pengumuman/FAQ, serta `schema_version` dan agregat aman. Pertimbangkan patch additive baru tanpa mengubah V4-0012.
2. Implementasikan tabel notifikasi/outbox dan kunci unik per event-penerima sesuai skema yang benar, tanpa plaintext PII, dengan idempoten di transaksi sumber yang sah.
3. Integrasikan event secara bertahap ke Service akademik: Resume/HOTS final, akses ulang, Pra-Semester, TP Siap, koreksi nilai, sertifikat. Jangan memasukkan notifikasi dari GET halaman atau dari aksi yang gagal/belum final.
4. Implementasikan list hanya akun penerima, badge belum dibaca, mark read POST+CSRF, tautan yang diperiksa ulang haknya; tidak ada broadcast non-Admin atau bocor lintas Gen. UI sesuai Emerald Glass dan uji di laptop/HP.
5. MariaDB sintetis dan uji XAMPP pada akun uji, termasuk retry/double click, dua pengguna, pengampu berubah, dan status dokumen terpin.

**A13-7:**
1. Periksa pengaturan resmi, audit dan histori untuk memilih struktur penyimpanan dokumen versi+tanggal berlaku+status Draf/Terbit yang bisa diaudit tanpa menghapus versi lama. Jangan merekonstruksi kebijakan dari label di aplikasi.
2. Siapkan endpoint dan form Admin setelah skema selesai: isi naskah sekolah, versi, tanggal berlaku, pratinjau, validasi, pernyataan sudah disahkan, publikasi atomik dan histori versi (tidak mencabut teks historis). Hanya Admin, CSRF, audit.
3. Tampilkan hanya versi yang sudah sah dan berlaku pada `GET /kebijakan-privasi` tanpa login, escaped/plaintext, dengan kontak resmi sekolah. Belum terbit harus tetap jujur, tidak menampilkan teks otomatis.
4. QA: anonim dapat melihat yang terbit tetapi bukan Draf, XSS test, versi berbeda, keamanan role dan CSRF, Dark/Light/HP, serta uji XAMPP.

## Prasyarat yang benar-benar dibutuhkan pengguna
- **Dump SQL terbaru dari XAMPP setelah V4-0012 diimpor**, sebagai berkas privat percakapan, bukan di GitHub.
- **Naskah Kebijakan Privasi yang disahkan madrasah**, lengkap dengan versi dan tanggal berlaku, agar bagian publik tidak menjadi kebijakan rekaan. Dapat diberikan ketika A13-7 siap diterapkan.

Status kedua modul: **fondasi non-DB siap direview; implementasi end-to-end belum selesai, belum FIX**. Tidak ada langkah SQL atau pull untuk mengaktifkan Notifikasi/Privasi dari branch ini.
