# Checkpoint A14-3 — Stabilisasi integrasi & UI Guru

Tanggal: 4 November 2026. Basis: PR #132 (audit penilaian/koreksi/sertifikat), di atas PR #131, #130, #129, #128.

## Keputusan pemilik
- A14-2 **FIX pada XAMPP menurut pemilik** sebelum audit tambahan #132: Guru G1 koreksi Resume/HOTS langsung; sertifikat personal awal/revisi otomatis saat diakses, PDF/QR historis tetap.
- A14-3 **wajib** menata ulang tampilan **terutama akun Guru** berdasarkan 12 screenshot, meluruskan input dan tombol yang tidak sejajar/kurang rapi.
- Tidak mengubah alur akademik, SQL V4-0013/0014 yang sudah diimpor, .env, root CI4, URL lokal, maupun data historis.
- Tidak merge langsung ke main sebelum QA dan pemeriksaan lokal.

## Bukti dan prioritas tampilan
- Peta ATP: kartu filter Gen–Mapel terlalu sempit dibanding card lain; select dan tombol tidak proporsional.
- Kelola ATP Excel: konteks Gen–Mapel, Paket Acuan dan tombol Pilih Paket perlu perataan vertikal; tombol tidak menempel input.
- Bank Soal Diagnostik/Sumatif: KKM, tombol Simpan/Ikuti Default, input jumlah Word dan tombol unduh, input unggah DOCX dan tombol periksa tidak membentuk satu sistem grid. File chooser native kurang kontras.
- Detail TP satu Materi: textarea petunjuk Resume dan HOTS tidak berpasangan di desktop; minimal menit sendiri lebih jelas.
- Modal BAB/TP, Step Progress, kartu enam semester, bank review Word dan tabel relatif baik; cukup standardisasi padding/gap dan tinggi CTA. Jangan rombak desain yang sudah dianggap FIX.

## Implementasi
- Scope CSS baru per halaman: a-guru-peta-atp, a-guru-impor-atp, a-guru-semester-atp, a-guru-detail-tp, a-guru-bank-soal, a-guru-review-word.
- Emerald Glass CSS: input/tombol 44px dan grid responsif, KKM 3→2→1, Excel/Word field+action sejajar desktop/stack HP, file input ber-token tema, Resume/HOTS berdampingan; modal footer lebih jelas.
- Views hanya menambah class layout, tidak mengganti label, method POST, nama field, CSRF, route maupun batas hak.
- Galeri komponen memuat contoh sintetis pola input+tombol, bukan tombol operasional.
- Workflow QA sunting CP/BAB/TP: klien MariaDB 10.4/10.11 diberi --skip-ssl hanya pada TCP 127.0.0.1 di runner GitHub; bukan konfigurasi database produksi/XAMPP.
- Tes regresi source UI memeriksa kontrak view/input, skema kelas CSS dan konfigurasi workflow; lulusnya bukan pengganti QA browser.

## Review kode lama
- Controller AdminKoreksiNilai dan Service permohonan lama masih ada sebagai kode historis di branch bertingkat #130. **Tidak mempunyai route aktif** di Routes.php; Routing.php mengatur autoRoute=false. Keputusan G1 langsung dijalankan KoreksiNilaiLangsungService. Hindari menghapus histori SQL/tes lama sebelum konsolidasi semua PR; pastikan tidak ada tombol tautan ke approval Admin di navigasi aktif.
- PR #128–#132 masih Draft/bertumpuk; A14-3 disiapkan sebagai PR Draft di atas #132, bukan merge main otomatis.
- Naskah Kebijakan Privasi resmi sekolah belum disediakan; tidak dibuat/dikarang dalam tahap A14-3.

## QA yang wajib
- PHPUnit lengkap + source test, PHP lint dan kedua job MariaDB V4-0007; regresi A11/A14 terkait sertifikat/koreksi, plus HTTP root smoke XAMPP sebelum go-live.
- Browser XAMPP desktop dan 390/820px: semua form GET/POST, label, tombol, dialog, KKM, Word preview, Excel konfirmasi, Materi dan fokus keyboard berfungsi. Dark/Light dan Mode Dokumentasi.
- Setelah semua PR yang bergantung diperiksa dan XAMPP PASS, pemilik dapat menyetujui konsolidasi; **jangan merge sebelum izin**.

**SQL:** tidak ada patch tambahan, V4-0013 dan V4-0014 tidak diimpor ulang. Pengujian otomatis menggunakan data sintetis.
