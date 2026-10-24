# 13 — Design System dan Standar CSS SLA-SKS

> **Dokumen pengikat semua halaman.** Implementasi CSS utama `public/assets/css/app.css` sebagai **satu stylesheet hasil akhir**; boleh dipisah selama pengembangan lalu digabungkan, tetapi tidak membuat stylesheet liar per role. Tidak memakai Bootstrap/Tailwind/Materialize sebagai framework wajib, tidak memakai icon font.

## 1. Karakter visual
Gaya LMS/kelas daring modern dengan **hijau muda** sebagai identitas, sidebar hijau lebih gelap, bidang putih/abu netral, kartu ringkas berjarak terukur, navigasi jelas, dan sedikit ornamen. Siswa mendapat bilah progres/lencana yang hangat, Guru dashboard aksi, Admin dashboard kontrol, Pimpinan dashboard analitik. **Satu sistem token dan satu library komponen** digunakan semua role. Tidak mengikat desain ke merek antarmuka pihak ketiga.

## 2. Token CSS resmi awal
Nilai berikut adalah token dasar K0 yang harus diselaraskan dengan **target tipografi dan kepadatan v2.4** melalui prototipe; setiap perubahan diverifikasi kontras sebelum diterapkan luas; komponen wajib menggunakan token, bukan nilai heksadesimal berulang.

```css
:root {
  --a-primer: #23874f;
  --a-primer-gelap: #165b36;
  --a-latar: #f6f8f6;
  --a-permukaan: #ffffff;
  --a-teks: #1d2a23;
  --a-redup: #55675c;
  --a-garis: #d8e2d9;
  --a-bahaya: #a93232;
  --a-info: #1f5f99;
  --a-sorot: #f7bf1b;
  --a-peran-siswa: var(--a-primer);
  --a-peran-guru: var(--a-info);
  --a-peran-admin: #4f6157;
  --a-peran-pimpinan: #6b3fa0;
  --a-radius-kecil: 6px;
  --a-radius: 12px;
  --a-ruang-1: 4px;
  --a-ruang-2: 8px;
  --a-ruang-3: 12px;
  --a-ruang-4: 16px;
  --a-ruang-6: 24px;
  --a-lebar-konten: 1200px;
  --a-font: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif;
  --a-font-isi: 14px;            /* desktop, dapat 15px sesuai keterbacaan */
  --a-font-label: 13px;
  --a-font-judul: 25px;          /* rentang rancangan 24–26px */
  --a-font-judul-kartu: 17px;    /* rentang 16–18px */
  --a-tinggi-input: 42px;        /* rentang 40–44px */
  --a-sidebar-lebar: 236px;     /* rentang 228–240px */
  --a-header-tinggi: 60px;
  --a-padding-kartu: 18px;      /* rentang 16–20px */
  --a-gap-kartu: 16px;          /* rentang 12–16px */
  --a-tinggi-baris: 1.5;
}
```

**Larangan:** class dari framework CSS luar, inline `style` bebas, warna hardcode di halaman, CSS ID yang mengikat layout khusus, `!important` sebagai perbaikan instan, SVG dekorasi tanpa aksesibilitas, shadow tebal/kartu di dalam kartu berlapis, dan animasi pada ruang ujian. Nilai warna status dan kontras harus diverifikasi otomatis sebelum produksi.

### Acuan visual yang disetujui pemilik
- **Panel/dashboard:** https://demo.graygrids.com/themes/taildash/build/index.html — inspirasi sidebar, header, KPI, tabel, filter dan hirarki tanpa meniru gambar atau kode secara mentah. Ukuran/gap bawaan contoh diperkecil sesuai token SLA-SKS.
- **Area publik/login:** https://app-tailwind.preview.uideck.com/ — inspirasi tipografi, ruang kosong terarah dan komposisi; tidak mengadopsi marketing page, layanan eksternal atau komponen login palsu.
- **Teknologi:** hanya HTML + CSS kustom `public/assets/css/app.css` + JavaScript native; tidak memasang Tailwind, Bootstrap, icon font atau CDN wajib. Font Inter hanya jika dapat di-host lokal secara legal; fallback sistem harus tampil setara bila font tidak ada. Tidak menyertakan file font di repository tanpa verifikasi lisensi.
- **Prototipe disetujui sebelum rollout:** dashboard Admin dan login publik (desktop + mobile) harus memperlihatkan tipografi, gap, lebar sidebar, tombol, form, tabel, empty/galat dan responsive. Setelah disepakati, ubah galeri komponen dan `app.css` konsisten; jangan hanya menambah CSS override untuk menutupi masalah layout.

## 3. Konvensi kelas
Semua kelas komponen memakai awalan `a-`. Modifier dapat berbentuk `a-btn--utama`, state `is-aktif`, `is-gagal`, `is-terkunci` sesuai pola konsisten. **Satu komponen = satu resep** pada dokumen dan galeri, jangan mengubah styling universal lewat selector tag global yang dapat merusak komponen lain.

| Komponen | Kelas | Aturan |
|---|---|---|
| Shell | `a-app`, `a-side`, `a-main`, `a-header` | Sidebar sekitar 236px, header sekitar 60px, ringkas/responsif, satu header role |
| Kartu | `a-card` | Permukaan netral, padding 16–20px, gap 12–16px, tanpa kartu bertumpuk |
| Tombol | `a-btn`, `a-btn--utama`, `a-btn--sekunder`, `a-btn--bahaya` | Target sentuh ≥40px; disabled benar-benar nonaktif |
| Form | `a-field`, `a-label`, `a-help`, `a-error` | Label eksplisit; input 40–44px; ruang vertikal konsisten; help/error terkait field |
| Status | `a-badge`, `a-alert`, `a-empty` | Status berbentuk teks dan ikon, tidak bergantung warna |
| Data | `a-tbl`, `a-pager`, `a-filter` | Tabel scroll mobile, paginasi server |
| Progres | `a-bar`, `a-segbar`, `a-seg`, `a-seg-isi` | Label progres, width dinamis terbatas |
| Semester | `a-kartu-sem` | Status dilewati, selesai, berjalan, tersedia, terkunci, belumsiap |
| Langkah TP | `a-langkah` | Selesai/aktif/terkunci disertai alasan |
| Pencapaian | `a-hero`, `a-rak`, `a-lencana`, `a-perayaan` | Bukan di S06 |
| Analitik | `a-kpi`, `a-chart`, `a-heat`, `a-legend` | SVG + tabel aksesibel |
| Ujian | `a-kiosk`, `a-timer`, `a-pelanggaran` | Tidak ada sidebar dan animasi |
| Media | `a-buku`, `a-audio`, `a-gambar` | Media responsif dan mode alternatif |
| Umum | `a-modal`, `a-toast`, `a-spanduk`, `a-skeleton` | Dialog aksesibel, status jelas |
| Branding | `a-logo`, `a-favicon-preview`, `a-ttd-preview`, `a-sertifikat-preview` | Tidak memaksa stretch pada gambar |

## 4. Pola HTML standar
```html
<section class="a-card" aria-labelledby="tp-judul">
  <div class="a-card__atas">
    <h2 id="tp-judul">TP 1 · Bilangan</h2>
    <span class="a-badge a-badge--siap">Siap</span>
  </div>
  <p class="a-help">3 TP · 2 TP tuntas</p>
  <div class="a-bar" role="progressbar" aria-label="Progres TP" 
       aria-valuemin="0" aria-valuemax="100" aria-valuenow="67">
    <span class="a-bar__isi" style="width:67%"></span>
  </div>
  <a class="a-btn a-btn--utama" href="/siswa/tp/123">Buka TP</a>
</section>
```
Hanya lebar **bilah progres** dan **segmen** yang boleh memakai style inline yang dihitung dan telah divalidasi. Bukan untuk warna/posisi umum.

```css
.a-card { background: var(--a-permukaan); border: 1px solid var(--a-garis);
          border-radius: var(--a-radius); padding: var(--a-ruang-4); }
.a-btn { min-height: 40px; display: inline-flex; align-items: center;
         border-radius: var(--a-radius-kecil); padding: 8px 16px; }
.a-btn--utama { color: #fff; background: var(--a-primer-gelap); }
.a-btn:focus-visible { outline: 3px solid var(--a-sorot); outline-offset: 2px; }
.a-bar { height: 8px; border-radius: 999px; background: var(--a-garis); overflow: hidden; }
.a-bar__isi { display: block; background: var(--a-primer); height: 100%; }
@media (max-width: 768px) { .a-side { display: none; } .a-main { width: 100%; } }
@media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }
```

### Kepadatan halaman dan hierarki tipografi
- **Isi:** 14–15px, line-height sekitar 1,5. Label 13–14px, judul kartu 16–18px, judul halaman 24–26px dengan bobot konsisten; jangan membuat semua komponen sebesar judul halaman. Tetap izinkan font diperbesar pengguna.
- **Layout desktop:** gunakan toolbar filter horizontal yang dapat wrap, tabel padat dengan batas kolom dan pagination, kartu berdampingan bila relevan. Hindari form selalu memenuhi seluruh lebar konten atau ruang kosong besar yang tidak menambah keterbacaan.
- **Form:** label, bantuan, pesan error, input dan tombol memiliki ritme vertikal konsisten; satu CTA utama per konteks, secondary untuk tindakan lain. Tombol/destruktif memberi konfirmasi. Min-height kontrol 40–44px dan fokus keyboard terlihat.
- **Responsif:** ukuran font tidak turun di bawah keterbacaan pada 360px. Sidebar mobile berupa drawer yang menutup setelah navigasi, fokus keyboard/escape jelas. Komponen dinilai pada lebar 360, 390, 768, 1100 dan desktop lebar.

## 5. Breakpoint dan layout
- **360–767px:** panel 1 kolom, menu ringkas, kartu lebar penuh, target 40px; layar siswa wajib nyaman.
- **768–1099px:** tablet, grid 2 kolom sesuai konten.
- **≥1100px:** sidebar tetap, hingga 12 kolom dashboard Pimpinan; batas lebar konten `--a-lebar-konten`.
- Form unggah Excel kompleks desktop-first, tetapi status, daftar dan penilaian tetap bisa dibaca mobile.
- PDF/sertifikat A4 landscape menggunakan CSS **terpisah dalam view cetak Dompdf**, bukan ukuran viewport layar; konten preview disimulasikan sesuai rasio A4.

## 6. Animasi dan media
Boleh animasi ringan **hanya** pada bilah segmen, lencana baru, dan perayaan siswa; matikan pada `prefers-reduced-motion`. Tidak ada animasi pada S06. PDF.js dan pustaka efek balik halaman diaktifkan hanya saat materi PDF; jangan membebani semua dashboard dengan library itu. Ikon SVG inline dari kumpulan ikon yang diizinkan, dengan label aksesibel pada tombol tanpa teks.

## 6A. Sistem halaman tunggal v2.4 — standar implementasi semua modul

Mulai tahap UI konsistensi, `layouts/panel.php` dan `layouts/publik.php` **selalu memakai desain v2.4 yang disetujui**. Tidak ada percabangan tema lama berdasarkan `uiPrototipe`. Satu stylesheet tetap `public/assets/css/app.css`.

- Konten panel dalam `.a-page`; gunakan `header.a-page__heading.a-proto-heading` untuk kelompok judul, `.a-proto-eyebrow` untuk konteks, dan `.a-proto-muted` untuk deskripsi ringkas.
- Gunakan `.a-page__editor` untuk form sederhana, `.a-page__editor--luas` untuk form banyak atribut. Daftar data tetap full width dengan `.a-tabel-gulir`, tidak memotong tabel pada 360px.
- Halaman panjang seperti Kurikulum/CP memakai `nav.a-page__jumps` yang mengarah ke `id` bagian nyata. Form CP boleh memakai `details/summary` native dan tetap **terbuka secara default**, termasuk setelah server mengembalikan error validasi.
- Field harus memakai label eksplisit; `.a-field` mengatur tinggi input 42px, ritme label-help, dan fokus keyboard. Satu CTA utama per formulir; tindakan bahaya punya konfirmasi server.
- Badge, status kosong, galat/sukses, tabel, breadcrumb dan font menggunakan token yang sama. Gunakan satu header H1 per halaman, H2 untuk kartu, H3 untuk subbagian.
- Jangan menaruh autentikasi atau perubahan hak akses pada komponen visual. Penataan UI tidak menambah aksi/backend/SQL baru. Lakukan pengecekan 360, 390, 768, 1100px dan desktop; lint PHP serta PHPUnit pada perubahan view.

## 7. Halaman katalog/galeri
`docs/assets/galeri-komponen.html` menyediakan contoh statis tombol, status, kartu, form, tabel, step, modal, dan contoh responsive. Sebelum rollout massal, prototipe dashboard dan login mendapat persetujuan pemilik. Ketika komponen baru ditambahkan: ubah dokumen ini → ubah galeri → ubah `app.css` → uji pada 360/390/768/1100px → commit bersama. Jangan mengimplementasikan CSS komponen baru tanpa memperbarui galeri.

## 8. Checklist UI sebelum diterima
Label form dan pesan galat; fokus keyboard; kontras WCAG; aksi disabled; empty state; loading; tampilan 360/390/768/1100; teks panjang; nama sekolah panjang; opsi soal tepat lima A–E dengan satu kunci; draf manual; peringatan browser tanpa fullscreen; layar ujian tanpa gangguan; logo dan TTD terlihat jelas di sertifikat preview; QR/teks tidak menabrak background; printer A4 landscape tidak memotong area aman.

## 9. Tata letak operasional v2.4 yang wajib dicakup CSS dan galeri
- `admin-akun_form` **full-width baris atas**, formulir field `a-grid` landscape; `admin-akun_daftar` baris bawah full-width. Tidak memakai grid dua kolom yang meletakkan daftar di samping form pada desktop.
- Tabel penetapan Guru: lebar dan align input diperhitungkan untuk **kolom Guru, Alasan, Simpan terpisah**; tidak memaksa select/input/button menjadi satu kolom vertikal kecuali mode mobile sempit yang memang diperlukan. Tabel panjang bisa scroll horizontal dengan label yang jelas.
- `a-pager` sebagai kelas kanonis (alias `a-pagination` hanya untuk transisi kode lama) konsisten di Pengelola, Siswa, Mapel–Guru dan tabel Daftar CP: info total, halaman saat ini, navigasi Pertama/Sebelumnya/Berikutnya/Terakhir, disabled beralasan, link berparameter filter. Fokus keyboard dan aria label wajib.
- `/profil`: card foto full-width lalu grid dua kolom Identitas Akun/Kontak Pribadi; satu kolom di HP. Foto, tanggal status review dan aksi tidak menutupi isi.
- Logo aplikasi tersentralisasi sebagai favicon dan identitas visual; ukuran varian dihasilkan tanpa mengganti sumber/versi secara liar, nama merek konsisten di header/login.
- Pratinjau soal Word: tabel akademik di dalam sel pertanyaan/opsi harus tetap terbaca/responsif, gambar tidak overflow, equation dapat di-scroll apabila lebar melebihi mobile, pemutar Drive aman. Warna status/fokus mengikuti token existing `a-`.
- Pembaruan CSS visual wajib diikuti perubahan `docs/assets/galeri-komponen.html` ketika kode implementasi UI dikerjakan; dokumen ini **tidak** mengklaim galeri runtime sudah berubah.

## Audit tabel responsif tahap A — Admin Pengelola/Siswa dan Guru per Gen (24 Oktober 2026)

- Pola `a-tbl--cards` diterapkan **secara opt-in** pada tabel yang setiap barisnya berdiri sendiri. Pada desktop tetap memakai header kolom/tabel semantik; pada lebar ≤980px, setiap baris menjadi kartu yang memiliki `data-label` di tiap sel. Jangan memasang pola ini pada tabel matriks yang perlu hubungan baris–kolom atau data komparatif kompleks; matriks tetap gunakan scroll dengan label tetap.
- Tahap pertama: tabel daftar Pengelola dan Siswa (satu template `app/Views/admin/akun_daftar.php`), tabel penetapan Guru per Gen, dan tabel riwayat penetapan (`app/Views/admin/mapel_guru.php`). Pertahankan pemilihan Gen, label form, tindakan per baris, perlindungan CSRF, filter dan pagination server-side.
- Tabel penetapan harus tetap memberi ruang sejajar untuk input **Guru / Alasan / Simpan** di desktop; pada layar sempit, masing-masing kontrol disusun vertikal dengan label, tanpa input/tombol terpotong.
- Periksa ukuran 360, 390, 768, 980 dan 1100px, keyboard focus/tab, opsi dropdown panjang, aksi reset sandi, penempatan Gen dan pagination. Tidak mengubah format ataupun endpoint data.
- Komponen demo responsif telah ditambahkan di `docs/assets/galeri-komponen.html`. Implementasi tahap berikutnya melakukan audit tabel CP/ATP dan referensi yang membutuhkan struktur berbeda, tanpa override CSS global secara sembrono.


## Breadcrumb pill dan pagination daftar Admin (24 Oktober 2026)

- Breadcrumb kanonis `a-breadcrumb` (ukuran tetap ringkas: teks 12px, tinggi tombol sekitar 24px, padding 2px 7px; bukan tombol aksi besar): setiap elemen yang benar-benar memiliki endpoint tetap memakai elemen `<a>` dengan gaya tombol **transparan** untuk tingkat pertama, hijau sangat ringan untuk tingkat kedua/lanjut, dan hover/fokus keyboard terlihat. Posisi saat ini memakai `<span aria-current="page">` dengan latar hijau lembut **tanpa membuat tautan palsu**. Pemisah `/` memakai `aria-hidden="true"`. Di layar 360px seluruh pill dapat wrap tanpa overflow.
- Komponen `app/Views/components/pager.php` memakai `a-pager--lengkap`: rentang data, total dan halaman; tautan Pertama/Sebelumnya, angka sekitar halaman aktif, Berikutnya/Terakhir; kontrol di ujung menjadi label nonaktif, bukan tautan tanpa fungsi. URL dibangun dengan `http_build_query` dari filter tepercaya; `aria-current="page"` untuk nomor aktif dan anchor ke judul daftar untuk memudahkan navigasi.
- Pengelola/Siswa: tetap 25 akun per halaman di SQL, mempertahankan `cari`, `status` dan `gen`. Mapel–Guru: daftar penugasan 25 mapel per halaman dengan `halaman_mapel`, riwayat lintas Gen 25 perubahan per halaman dengan `halaman_riwayat`; navigasi masing-masing menjaga Gen dan halaman daftar lainnya. POST penugasan kembali ke halaman penugasan semula.
- Uji penerimaan pada 360, 390, 768, 1100px dan desktop; filter yang menghasilkan 0/1/lebih dari 25 data, halaman terakhir, ID halaman di luar rentang, perubahan Gen, penetapan dan redirect setelah POST. Pagination adalah query SQL (`LIMIT/OFFSET`), bukan JS yang menyembunyikan baris di browser.
- Struktur akses Admin dan mekanisme MySQL/MariaDB yang sudah berlaku tidak berubah. Contoh tampilan ada di `docs/assets/galeri-komponen.html`.


## Audit tabel responsif tahap B — Daftar CP dan Peta ATP (24 Oktober 2026)

- **Admin / Kurikulum & CP:** hanya tabel Daftar CP dan Status Versinya mendapat filter kode/mapel/kurikulum/status, jumlah total, pager SQL 25 data/halaman, dan ekspor XLSX sesuai filter tanpa memaksa seluruh CP dimuat. Ekspor mencakup seluruh hasil filter, dibatasi 5.000 CP demi memori shared hosting, dan menulis setiap sel sebagai teks agar awalan `=`, `+`, `-` atau `@` tidak menjadi formula.
- Tabel CP yang tiap barisnya independen memakai `a-tbl--cards` di layar ≤980px dengan `data-label` Kode, Kurikulum, Mapel/Fase, Versi resmi, Draf dan Aksi. Tabel kurikulum dan formulir CP tidak dirombak pada tahap ini.
- **Guru / Peta ATP enam semester:** setiap TP menjadi kartu berlabel pada ≤980px; nomor urut ATP, kode, judul (link Detail TP), versi paket dan status tetap tampil. Urutan serta relasi Gen–Mapel–Semester tidak diubah; enam semester dan penanganan data kosong tetap sama.
- Gunakan `a-cp__filter`, `a-cp__tabel.a-tbl--cards`, `a-tbl--peta-atp` sesuai CSS; hindari global override untuk tabel matriks akademik. Periksa 360/390/768/1100px, dropdown panjang, keyboard, status kosong dan filter >25 CP, link ke detail, serta worksheet Excel pada LibreOffice/Excel.
- QA: uji dengan 0/1/26/60 CP; pagination/filter/export konsisten dan tidak menyebabkan CP milik mapel lain ikut; status versi CP tetap immutable.

