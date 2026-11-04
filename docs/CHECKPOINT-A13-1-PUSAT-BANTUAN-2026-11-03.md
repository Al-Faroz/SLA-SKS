# A13-1 — Pusat Bantuan dan Panduan Penggunaan

Tanggal: **3 November 2026**. Branch: `feature/a13-1-pusat-bantuan-panduan-20261103`.
Draft PR **#122** stacked pada `fix/a12-4-rapikan-form-profil-referensi-20261103` (PR #121). Belum merge `main`.

## Keputusan dan sumber

Pemilik menyatakan **FIX** atas audit UI Admin terbaru (Pengelola, Siswa, Gen/Tahun, Identitas, Profil) dan meminta lanjut. Sumber awal: `README.md`, `AGENTS.md`, `docs/README.md`, `docs/PENDING-DECISIONS.md`, Master 01, 02, 03, 06, 07, 08, 09, 11, 12, 13, 14, 16.

Master mewajibkan Pengumuman global, FAQ yang dikelola Admin, Notifikasi internal penerima, dan Bantuan/Hubungi Admin berisi kontak resmi. Ketiganya memerlukan penyimpanan, izin, audit, dan/atau data resmi sekolah. **Gerbang AGENTS.md menuntut dump XAMPP terbaru yang diambil setelah patch terakhir sebelum pekerjaan skema/pemetaan SQL. Dump tersebut tidak terlampir pada A13-1.** Oleh karena itu A13-1 hanya mengerjakan **panduan operasional login-only**, tidak membuat skema/kontak/FAQ fiktif.

## Implementasi

- Route `GET /bantuan` memakai filter `auth`; Controller `Bantuan::index` juga mengharuskan akun aktif, sandi sementara sudah diganti, dan peran aktif sah. Respons `Cache-Control: private, no-store`, tanpa ID siswa dari browser.
- `PanduanBantuanService::untukPeran()` memuat katalog **panduan berbasis keputusan resmi Master**; 4 peran memiliki materi berbeda. Siswa: alur TP, Diagnostik, Sumatif, akses ulang, laporan nilai TP vs Pra, sertifikat. Guru: G1/TP/Materi, penilaian, akses ulang, rekap. Admin: akun/G1, Pra, KKM, sertifikat. Pimpinan: monitoring baca-saja. Semua: keamanan akun. Hanya tautan yang sudah memiliki endpoint GET.
- Menu `Bantuan` muncul di `NavigasiService` untuk keempat role. `app/Views/bantuan/index.php` memakai native `details/summary` dengan output escape dan tombol menuju modul yang betul-betul ada, tanpa kerangka JS tambahan atau interaksi POST. `app.css` menambah kelas `a-bantuan` responsif Dark/Light/HP.
- Tidak menyentuh kontrak akademik, nilai siswa, status percobaan, sertifikat yang diterbitkan, atau izin G1. Tidak memulai ujian atau mengirim notifikasi/email/WhatsApp.

## Berkas

Dibuat:
- `app/Services/PanduanBantuanService.php`
- `app/Controllers/Bantuan.php`
- `app/Views/bantuan/index.php`
- `tests/unit/PanduanBantuanServiceTest.php`
- `.github/workflows/qa-a13-1-pusat-bantuan.yml`
- Checkpoint ini

Diubah:
- `app/Config/Routes.php`
- `app/Services/NavigasiService.php`
- `public/assets/css/app.css`
- Master 03, 11, 12, 13, 16; `docs/README.md`; galeri komponen; checkpoint A12-4

## QA otomatis

Workflow **QA A13-1 Pusat Bantuan Peran**, run **41657877142**: PHP lint Service, Controller, View, Routes, Navigasi, Test PASS; **9 tes/217 assertions** Panduan Bantuan PASS; regresi **9 tes/68 assertions** Audit UI Admin PASS. QA diulang setelah checkpoint terakhir, sehingga status commit terakhir harus diperiksa sebelum final.

Yang diuji: konten empat peran; hanya tautan GET benar-benar terdaftar; Siswa tidak mendapat link Admin/Guru; Pimpinan baca-saja; sandi awal/role harus sah; `/bantuan` dilindungi auth; output HTML ter-escape; tidak ada form, data pribadi atau SQL pada service.

## Uji manual XAMPP

1. Pull branch A13-1 dan login Admin, Guru, Siswa, Pimpinan secara bergantian. Buka `http://localhost/sla-sks/index.php/bantuan` dan periksa menu `Bantuan` berada sebelum `Profil & Peran`.
2. Perluas beberapa pertanyaan; pastikan bahasa dan judul sesuai peran aktif, seluruh tombol benar-benar membuka halaman yang sudah ada dan tidak membuat data baru.
3. Logout dan buka `/bantuan` langsung: harus diarahkan ke login. Periksa satu akun multi-role dengan pergantian peran melalui Profil.
4. Uji Dark/Light dan HP 360/390px, menu sidebar/drawer, fokus keyboard (Tab/Enter pada `summary`), Mode Dokumentasi.
5. Uji 403 pada role tidak sah secara unit, jangan membuat akun siswa atau data ujian nyata hanya untuk memaksa skenario. **Tidak perlu impor SQL.**

## Batas dan tahap berikutnya

A13-1 adalah **panduan pengguna**, belum FAQ terkelola Admin. Pengumuman global, FAQ editable, notifikasi per penerima serta menu Hubungi Admin dengan kontak yang mengambil identitas resmi sekolah **belum diimplementasikan**, dan tidak boleh dilabeli FIX. Untuk mulai tahapan tersebut, diperlukan dump SQL lokal terbaru sesudah patch terakhir beserta audit struktur dan status data relevan, baru dibuat file SQL pembaruan additive bernomor jika perlu. A13-1 menunggu uji XAMPP sebelum FIX.
