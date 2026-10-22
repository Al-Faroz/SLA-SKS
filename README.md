# SLA-SKS — Master Document v2.4 (Kontrak Target)
**MTs Fase D · LMS pembelajaran mandiri · program SKS percepatan dua tahun**

Dokumen resmi proyek terdiri atas **16 Master Document** di [docs/README.md](docs/README.md). Spesifikasi v2.4 menggantikan aturan v2.3 **jika bertentangan**. PR dokumentasi ini bukan klaim kode, SQL, dan browser sudah mengimplementasikannya.

## Urutan membaca
1. [AGENTS.md](AGENTS.md) dan [indeks 16 dokumen](docs/README.md).
2. [Aturan bisnis](docs/02-ATURAN-BISNIS.md), [hak akses](docs/03-PERAN-DAN-HAK-AKSES.md) dan [kurikulum](docs/04-KURIKULUM-DAN-CP.md).
3. Modul terkait + [database](docs/10-DATABASE.md) + [API](docs/11-ARSITEKTUR-DAN-API.md) + [UI/UX](docs/12-UI-UX-DAN-HALAMAN.md) + [CSS](docs/13-DESIGN-SYSTEM-CSS.md) + [pengujian](docs/16-PENGUJIAN-DAN-PENERIMAAN.md).

## Ketentuan final v2.4
- **CP → ATP (urutan TP) → TP → Materi/Submateri**. BAB bukan entitas akademik/aplikasi; TP adalah unit Diagnostik, Sumatif, status Siap, versi konten dan penilaian.
- CP resmi berversi dikendalikan Admin; formulir CP tanpa metadata sumber hukum berulang yang wajib. Guru G1 satu aktif per **Gen–Mapel**, paket/TP/soal/materi **khusus Gen**.
- Excel pemetaan **enam semester per Gen–Mapel**, identitas + CP aktif mapel/kurikulum diisi otomatis saat Guru mengunduh. Excel membentuk struktur TP/Materi setelah unggah, pratinjau dan konfirmasi; konten/media dan Resume/HOTS dibuat langsung dalam aplikasi.
- **Setiap Materi mempunyai Resume dan HOTS**. Siswa lulus Diagnostik ≥ KKM dapat melewati kewajiban Materi/Resume/HOTS dan mendapat nilai Diagnostik; bila belum, menjalani semua Materi berurutan lalu Sumatif. KKM/penilaian/keamanan ujian tetap kebijakan internal madrasah, tidak diubah sepihak.
- Dua unduhan Word bank soal **Diagnostik/Sumatif**, satu format **PG lima opsi wajib A–E, satu kunci ✓**. Parser wajib menjaga teks Unicode, OMML/Equation Word, gambar dan tabel termasuk tabel bersarang pada stem **dan pilihan**, plus URL audio/video Google Drive yang sah.
- Pembelajaran Fase D mengikuti referensi CP sesuai mapel dan integrasi nilai KBC/Panca Cinta yang relevan, **tanpa hierarki BAB/KBC tambahan**.
- UI: Admin Pengelola/Siswa form landscape atas, daftar paginasi bawah; Guru per Gen dropdown/alasan/simpan kolom terpisah + paginasi; daftar CP pagination+XLSX; identitas upload logo sekaligus favicon; profil semua role foto full-width di atas, Identitas/Kontak dua kolom di bawah.
- SQL melalui **dump/install atau patch bernomor**, tanpa CI4 migration/seeder. Pemilik akan mengirim dump lokal untuk dianalisis; **khusus DB lokal yang ditentukan dan setelah backup**, pemilik mengosongkan tabel lalu impor dump final dengan data terpilih. Hosting/produksi **tidak** memakai reset destruktif.
- CI4 `index.php` dan `.htaccess` berada di **root**; jangan ubah `.env`, base URL atau struktur root yang telah berfungsi. HTML/CSS custom `app.css` dan native JS; empat role Admin, Guru, Siswa, Pimpinan.
- Satu Admin cukup; larangan menyetujui **usulan akademik diri sendiri** tetap berlaku. Riwayat nilai, soal, percobaan dan sertifikat tidak dihapus atau dicabut. Sertifikat A4 landscape, tanda tangan PNG wajib dan NISN hanya pada PDF privat.

## Kondisi repository dan pengerjaan berikutnya
- PR kode terdahulu **#13** (K3B/K3C/K4A/K4B) masih memakai hierarki dan impor lama sehingga harus **dirombak di PR implementasi terpisah**, tidak boleh otomatis digabung.
- Perubahan Master v2.4 hanya dokumentasi dan referensi; pelaksanaan SQL, generator Excel, parser DOCX serta revisi UI/UX **menunggu tahap implementasi serta pengujian XAMPP**.
- Template kerja acuan: `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx` dan `docs/TEMPLATE_SOAL_SLA-SKS.docx`; aplikasi akan menghasilkan unduhan yang terkait identitas/TP aktual.
