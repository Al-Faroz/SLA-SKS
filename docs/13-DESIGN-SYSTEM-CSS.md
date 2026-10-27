# 13 — Design System dan Standar CSS SLA-SKS

> **Dokumen pengikat semua halaman.** Implementasi CSS utama `public/assets/css/app.css` sebagai **satu stylesheet hasil akhir**; boleh dipisah selama pengembangan lalu digabungkan, tetapi tidak membuat stylesheet liar per role. Tidak memakai Bootstrap/Tailwind/Materialize sebagai framework wajib, tidak memakai icon font.


## 0. KONTRAK DESAIN FINAL UNTUK IMPLEMENTASI UI — 27 Oktober 2026

Nama: **Emerald Glass Academic UI**. Default DARK, LIGHT opsional, CSS kustom + JS native, empat peran satu shell. Master 12 mengatur perilaku; bagian ini supersede palet default terang pada bagian historis berikutnya. Tidak boleh mengubah hierarki Mapel–CP–Semester–BAB–TP.

### Token tema
- Dark: wallpaper linear-gradient(145deg, #031A13, #052C20 52%, #004225); text #F0F8F3, muted #B3CFC0, mint #50DEAC, coral #FF777C, amber #FFD166, blue #80BBFF, violet #D1A7FF.
- Light: background off-white kehijauan #F3F8F5, teks gelap #19382D, garis #CCDDD3; aksen dibuat lebih gelap agar terbaca.
- Permukaan: background glass dipakai terbatas pada shell/sidebar/header/hero; card memiliki warna lebih padat; tabel, form, editor, materi dan ujian solid. Blur hemat dan CSS fallback tanpa backdrop-filter tetap harus dapat digunakan.
- Semantik: text success/attention/warning/info/accent diatur token terpisah; latar akhir harus diuji WCAG AA dengan rasio teks >=4,5:1, termasuk keadaan hover/disabled.
- Spacing 4/8/12/16/20/24/32/40px; card radius 14–16px; button/input radius 9–10px dan tinggi minimum 44px; micro-motion 120–200ms dan prefers-reduced-motion.

### Enam gradient utama (Dark)
| Varian | Nama | Gradient | Aksen |
|---|---|---|---|
| 1 | Emerald | #123D2F ke #165D48 | #A7F3D0 |
| 2 | Teal | #123943 ke #175D68 | #A5F3FC |
| 3 | Blue Emerald | #1B3155 ke #294D83 | #B9D9FF |
| 4 | Violet | #382850 ke #60417B | #E0C3FF |
| 5 | Amber Olive | #44371F ke #675329 | #FFE0A3 |
| 6 | Rose Plum | #452B3A ke #75435A | #FFCEDF |

Implementasi: variabel CSS --a-grad-1 ... --a-grad-6, masing-masing memiliki padanan pastel mode Light. Digunakan wajib pada Peta ATP 1–6; boleh dipakai ulang untuk kartu ringkasan dan BAB. Enam kartu adalah tautan yang memelihara URL Gen–Mapel, bukan dekorasi statis.

### Komponen dan shell
- Tema didefinisikan oleh html[data-theme="dark"|"light"]; default markup dark, penyimpanan browser key sla-sks-tema. Inisialisasi tema sebelum stylesheet untuk menghindari flicker.
- Sidebar layout panel desktop 240px expanded / 72px collapsed menggunakan tombol berlatar dan label aria; key preferensi sla-sks-sidebar. Mobile selalu drawer dengan Escape, fokus dan backdrop.
- Ikon SVG lokal dari subset Lucide, menggunakan sprite di public/assets/icons/lucide-sla.svg; tanpa font icon/CDN wajib. Lisensi dan atribusi lokal dipertahankan.
- Tombol: **primary Indigo–Violet** (bukan gradient hijau, agar kontras dengan latar Emerald Glass), secondary dan soft tinted penuh, danger tinted merah, icon button penuh; tidak ada tombol tanpa background. Link teks/badge bukan tombol. Warna kartu semester tidak berubah.
- Token Primary Dark: `--eg-cta-gradient: #3448B2 → #4056C2 → #6743BF`; Light: `#3448B2 → #3D50B5 → #6041B5`. Teks putih, hover lebih gelap, border violet lembut; gunakan token `--eg-cta-gradient`, `--eg-cta-hover`, `--eg-cta-border`, dan `--eg-cta-shadow`. Warna ini hanya untuk aksi primer, bukan mengganti token `--e-primary` yang dipakai teks/status emerald.
- Form, tabel, dialog native, breadcrumb, pager, status dan fokus tetap mengacu kelas a- yang telah ada; tema tidak boleh mengubah otorisasi, nilai, media, route, CSRF atau layanan.
- Layar ujian tidak menggunakan animasi/glass dekoratif; semua kontrol ujian dan timer tetap dengan server sebagai sumber kebenaran.


### Audit Light Mode dan komponen Admin/Guru (27 Oktober 2026)

Audit terhadap `public/assets/css/app.css`, `emerald-glass.css` dan view Admin/Guru menunjukkan beberapa komponen lama belum ber-token: `.a-pager__tombol` memakai foreground hijau gelap pada Dark Mode, `.a-soal-status-badge`, `.a-tp-status`, dan `.a-kesiapan-ringkasan` memakai pastel terang, `.a-cp__details` tetap `#f9fcfa`, serta `.a-page__jumps a:hover` masih hardcode. Komponen ini diperbaiki lewat lapisan **V6** di `emerald-glass.css` dengan selector yang dibatasi ke shell Admin/Guru.

- Pager: background pada setiap kontrol, state aktif tetap hijau gelap dengan tulisan putih, focus-visible dan disabled dibedakan. Navigasi dan nomor halaman bukan diubah.
- Status: token `--eg-state-*` untuk siap/aktif, menunggu, bahaya, normal; badge status tetap berupa teks dan tidak menjadi tombol. Status Bank Soal dan Kesiapan TP memakai label yang diberikan server.
- Ringkasan Kesiapan TP dan native `<details><summary>` CP: panel tinted glass Dark/Light dengan teks warna tema; keyboard focus outline tetap ada.
- Link loncat ke subbagian (`.a-page__jumps`) memperoleh latar tombol yang nyata sesuai keputusan desain, tanpa mengganti href.
- Field `.a-input` lepas dari wrapper dan `[aria-invalid=true]` menggunakan border/focus error kontras. Checkbox/radio/file tetap memakai native control; pemrosesan POST, `required`, `maxlength`, dan validasi server tidak berubah.
- Pratinjau di `docs/assets/galeri-komponen.html` adalah contoh tanpa data nyata. Warna tombol tetap hasil keputusan rollback **Indigo–Violet Primary, Secondary soft glass, Danger muted**; tidak ada Colorion oranye/cyan.
- QA wajib XAMPP: Admin Beranda/Pengelola/Kurikulum CP, Guru Bank Soal/Kesiapan TP/Antrean Penilaian/Peta ATP; Dark/Light pada 390px, 820px dan desktop; penanda aktif pager, `summary` dibuka/ditutup, status/badge, field invalid, focus ring dan seluruh tombol yang memicu navigasi POST. Jangan menyatakan selesai hanya berdasarkan pemeriksaan CSS.
- Setelah modul yang sama dinyatakan FIX, lakukan konsolidasi lapisan `emerald-glass.css` yang masih memakai beberapa override bertingkat; dokumentasi ini bukan izin mengubah aturan atau struktur akademik.

### QA responsif tablet dan drawer HP (27-10-2026)

**Audit sumber aktual:** `app.css` mengganti `.a-tbl--cards` menjadi mode kartu pada lebar <=980px, sementara override Dark/Light di `emerald-glass.css` sebelumnya berlaku <=767px. Akibatnya kartu tabel pada 768–980px masih dapat memakai `background: #fff` milik CSS historis. Penanganan sementara tahap ini: komponen kartu tabel pada **seluruh <=980px** memakai token permukaan, border, label `data-label`, zebra/hover aman, tata letak <=420px satu kolom, dan tombol/form dalam sel tidak terpotong. Tabel padat non-kartu tetap boleh digulir **di dalam kontainer tabel**, tanpa scrollbar menyeluruh.

**Drawer HP:** `public/assets/js/app.js` membatasi tab keyboard di sidebar saat drawer dibuka, menonaktifkan interaksi area `.a-main` menggunakan `inert`, mengunci scroll latar sementara, mengembalikan fokus ke tombol Menu ketika ditutup dengan Escape/backdrop, dan melepas kunci saat breakpoint kembali desktop. Menu tetap bersumber dari server; link, role, dan route tidak berubah. CSS menampilkan `100dvh` dengan fallback lama, backdrop blur sederhana, dan focus ring pada tombol Menu/link.

**Kriteria QA wajib sebelum FIX:** uji tablet 768/820/980px dan HP 360/390/420px untuk Pengelola, Siswa, Bank Soal, Peta ATP, halaman Semester; buka menu, Tab/Shift+Tab berulang, Escape, klik backdrop, klik tautan, ubah lebar browser saat menu terbuka; pastikan halaman tidak ber-scroll horizontal kecuali pada kontainer tabel yang memang membutuhkan scroll. Uji Dark dan Light. Jangan mengklaim QA lokal dari pemeriksaan sumber statis.

### Audit lanjutan komponen UI (screenshot XAMPP 27 Oktober 2026)

Temuan visual: gradasi wallpaper dan sidebar sudah mendekati Emerald Glass, tetapi masih terdapat konflik `app.css` dengan lapisan tema pada card, breadcrumb, tabel, input, ikon kartu KPI dan toolbar Excel. Penyesuaian V3 dilakukan **hanya pada CSS** dengan mempertahankan HTML dan seluruh fungsi.

- **Card**: panel pengelolaan dan ATP memakai glass semi-pekat dengan border, shadow lembut dan highlight terkontrol. Kartu KPI tetap empat warna dari enam gradient resmi. Ikon KPI dan shortcut SVG yang sudah ada menggunakan holder tinted dengan border; hindari holder putih besar pada Dark.
- **Breadcrumb**: tiap tautan berupa tombol navigasi kecil berlatar yang kontras, `[aria-current="page"]` adalah penanda lokasi (bukan tombol), dengan perbedaan state dan fokus yang tegas. Tidak mengubah target link.
- **Tabel**: seluruh `.a-page .a-tbl` memperoleh token tabel Dark/Light. Header, `th[scope=row]`, isi, zebra, dan hover harus harmonis, dan ada styling khusus untuk `.a-tbl--cards` pada <=767px; label `data-label` tetap terlihat dan tabel yang butuh gulir tetap bisa digulir. Perbaiki putih bawaan `#f4f7f8` dan `#fff` tanpa mengubah isi, pagination atau urutan data.
- **Form input**: input text, password, textarea, select punya minimum tinggi 44px, border jelas, focus ring, validasi native tetap berlaku; selector mengecualikan checkbox, radio, hidden dan file untuk menjaga perilaku aslinya. Disabled dipisah secara visual. Tidak membuat input custom yang mengubah mekanisme submit.
- **Pengelola / Siswa**: panel Excel memakai kelas nyata `.a-admin-akun__xlsx`; harus bertema gelap/terang dan tombol Unduh/Impor/Ekspor tetap ber-background. Nama pengguna, keterangan sandi dan status tetap terbaca.
- **Kartu KPI**: tautan aksi dalam kartu sengaja diberi latar sebagai CTA, berbeda dari deskripsi teks. Ikon inline SVG yang sudah ada tidak diganti secara berlebihan, cukup diberi container yang konsisten.
- **Perf & aksesibilitas**: glow dekoratif tidak boleh menimbulkan horizontal scrollbar, proses blur hanya di panel, `prefers-reduced-motion` dipertahankan, teks di atas area solid diverifikasi kontras, media/ujian tidak diberi efek yang mengganggu.
- **QA**: Perubahan ini belum boleh dianggap FIX sebelum screenshot Dark/Light dari Admin Dashboard, Admin Pengelola (toolbar XLSX + tabel), Guru Peta ATP enam kartu, Semester 4 (tabel BAB/TP dan modal), serta pemeriksaan mobile 360px dan 768px dilakukan pada XAMPP. Nilai/database/otorisasi bukan bagian dari revisi ini.

### Penghalusan glass berdasarkan contoh CSS CodeFronts (27-10-2026)

Contoh `.stp-05` milik CodeFronts dipelajari sebagai inspirasi **teknik visual**, bukan sebagai komponen onboarding untuk SLA-SKS. Implementasi tahap penyesuaian memakai `public/assets/css/emerald-glass.css`:
- Dua atau tiga radial-gradient beropacity rendah disusun pada **container utama `.a-app--prototipe` dan latar publik**, bukan hanya `body`, supaya glow tidak tertutup background shell.
- Shell/header/sidebar serta panel pembungkus memakai tinted glass semi-pekat, border translucent dan inset highlight; `backdrop-filter` hanya di komponen pilihan. Fallback solid disediakan jika blur tidak didukung.
- Enam gradient kartu Semester 1–6 **tetap identitas tersendiri**; lapisan sheen transparan tidak mengganti gradient ataupun statistik/tautannya.
- Form, input, tabel, pembaca materi dan editor tetap memakai bidang padat. Khusus tabel TP dalam BAB, warna kepala tabel, sel kode dan baris diatur konsisten pada Dark dan Light untuk menghindari area putih terang yang kurang terbaca.
- Modal memakai native `<dialog>`, border tipis, backdrop redup/blur halus, tanpa perubahan submit/validasi/CSRF/fokus bawaan.
- Jangan menyalin aturan `.stp-05 * { margin:0; padding:0; }` secara global: aturan tersebut akan merusak spacing komponen aplikasi. Jangan menyalin animasi `stp-05-bob` tak berujung, karena siswa membutuhkan layar yang tenang. Warna `--muted` contoh juga tidak langsung digunakan sebagai teks akibat risiko kontras.
- Semua tombol tetap tampak sebagai tombol dengan latar jelas, termasuk ikon, sekunder, dan tombol di dialog.
- Tahap glass ini **belum dinyatakan visual QA PASS** sampai pengujian melalui XAMPP pada login, Guru, G03, dark/light, sidebar expanded/collapsed, mobile dan interaksi modal selesai.

### Tahap implementasi
Fase pertama mengubah token/shell/galeri dan baseline G03 saja, dipisahkan dalam PR Draft berbasis branch PR #67 (PR #65 -> #66 -> #67 tetap belum merge). QA statis bukan pengganti lint PHP, browser XAMPP, keyboard, dan pemeriksaan visual 360/390/768/1100px serta semua tema. CSS historis memiliki warna hardcoded yang harus diaudit tiap modul sebelum rollout dianggap selesai.

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

## Riwayat implementasi v3 (non-normatif)
Bagian catatan implementasi bertanggal 24–26 Oktober 2026 berikut adalah bukti riwayat UI **pola lama**, bukan keputusan kontrak baru. Ketentuan baru §Kontrak desain card BAB/TP v2.5 di akhir dokumen mengganti perilaku lama yang bertentangan, terutama Diagnostik yang pernah menuntaskan TP tanpa Sumatif dan beberapa Materi per TP.

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


## Audit tabel responsif tahap C — Detail TP dan Materi Guru (24 Oktober 2026)

- Halaman `guru/tp/{id}`: tabel **Identitas TP** tetap bentuk key/value agar tidak memboroskan layar HP; lebar kolom label dibatasi, judul/kode dapat membungkus tanpa scroll horizontal.
- **Pemeriksaan TP:** halaman Rincian TP tidak lagi menampilkan tabel pemeriksaan awal yang menduplikasi checklist final. Satu-satunya daftar pemeriksaan untuk Guru ada pada `/guru/tp/{id}/kesiapan` menggunakan `a-tbl--cards a-kesiapan-tabel`, dengan status berteks, alasan, dan tautan perbaikan.
- **Daftar Materi/Submateri** delapan kolom memakai `a-tbl--cards a-tp-materi-tabel`; desktop mempertahankan kolom terpisah No., Judul, Jenis, Status, Pratinjau, Urutan, Edit, Hapus. Layar ≤980px mengubah baris menjadi kartu berlabel untuk mengurangi scroll. Aksi Naik/Turun menjadi tombol berdampingan bila muat, dan tombol Pratinjau, Sunting, Hapus tetap memiliki endpoint/form semula, CSRF dan konfirmasi hapus.
- **Form tambah dan edit Materi** memakai satu wrapper `a-materi-form-fields` pada partial field yang telah ada: satu kolom mobile, dua kolom pada desktop ≥1100px; input media dan URL YouTube mengambil lebar penuh agar informasi PDF 15 MB/40 halaman dan pesan kesalahan tetap terbaca. Input file pada browser tetap tidak dapat diisi ulang otomatis setelah gagal.
- **Tidak ada perubahan** `PdfMateriValidator.php`, `MateriService`, endpoint upload/pratinjau, otorisasi G1, .env, SQL, metadata media maupun batas PDF final. Catatan `docs/KEPUTUSAN-VALIDASI-PDF-MATERI.md` tetap berlaku.
- QA manual: 360, 390, 768, 980, 1100 dan desktop; lihat TP dengan ≥4 Materi berbeda PDF/YouTube/Audio/Gambar, urutan naik/turun, preview, edit media, aksi hapus konfirmasi, TP Siap tanpa edit. Cek opsi file dan validasi PHP masih sama.


## Audit responsivitas tahap D — Pratinjau Media Guru (24 Oktober 2026)

- **Lingkup:** halaman `/guru/tp/{tpId}/materi/{materiId}/pratinjau`, mengikuti hasil FIX audit daftar Materi. Tambahkan navigasi cepat menuju daftar Materi (`#daftar-materi`), bagian media dan petunjuk Resume/HOTS. Tautan `Sunting Materi` muncul hanya ketika TP masih `draf`; seluruh tujuan menggunakan rute yang telah ada. Navigasi bukan pengganti breadcrumb kecil.
- Bagian atas menampilkan jenis, urutan dan status Materi secara ringkas. Tombol atau label tidak menyamar sebagai aksi: tautan benar-benar menuju ID halaman atau endpoint yang tersedia.
- Pratinjau **YouTube** dan **PDF** tetap menggunakan elemen `details` (tidak otomatis mengunduh/menjalankan pemutar sebelum dibuka), ukuran video proporsional 16:9. PDF disesuaikan tinggi viewport pada HP, tetapi tautan **Buka PDF di tab baru** tetap tersedia apabila browser tidak mendukung iframe. Jangan mengubah validator PDF 15 MB/40 halaman, konten berkas atau izin akses privat.
- **Audio** tetap memakai `<audio controls preload="metadata">`, **Gambar** tetap `<img>` berukuran proporsional, keduanya tidak boleh menyebabkan scroll horizontal pada HP.
- Petunjuk **Resume** dan **HOTS** satu kolom pada HP/tablet dan dua kolom pada desktop ≥1100px, mempertahankan isi teks yang di-escape. Karena view memakai `nl2br`, teks kegiatan tidak boleh ditambahkan `white-space:pre-wrap` yang akan menduplikasi baris.
- Style dibatasi pada kelas `a-pratinjau-*` agar tidak merombak pemutar publik, media siswa, form upload, atau kartu yang sudah FIX. Uji pada lebar 360/390/768/1100px; cek YouTube, PDF, audio, gambar, Materi Nonaktif, status TP Siap, link navigasi, keyboard, dan browser tanpa pemutar PDF.


## Tahap akademik: Kesiapan dan Publikasi TP Siap (24 Oktober 2026)

- **UI Guru:** `/guru/tp/{id}/kesiapan` adalah pemeriksaan akhir, bukan checklist konten awal. Komponen `a-kesiapan-ringkasan` menampilkan progres pemeriksaan, Materi, soal dan status; `a-tbl--cards a-kesiapan-tabel` memuat alasan serta tautan perbaikan, responsif pada 360px. Halaman Detail TP memiliki tautan ke pemeriksaan akhir.
- **Pemeriksaan server:** identitas TP; CP terbit/aktif sesuai Fase D, Mapel, Kurikulum paket, dengan tepat satu CP utama; seluruh Materi dengan urutan utuh, media valid dan berkas tersedia, waktu belajar, Resume/HOTS; Diagnostik aktif ≥10 dan ≥tayang, Sumatif aktif ≥5 dan ≥tayang, semua soal PG A–E satu kunci; KKM/durasi/minimal karakter/bobot/kuota sesuai Master 05; sumber Word/gambar masih tersedia. Tidak mengulang validasi struktur PDF yang sudah FIX.
- **Penguncian:** tombol Konfirmasi & Jadikan TP Siap muncul **hanya bila seluruh pemeriksaan otomatis lulus dan TP masih draf**. Guru wajib mencentang pernyataan telah memeriksa akademik. POST dilindungi CSRF/filter Guru, Service mengulang otorisasi penugasan G1, lock baris TP, ulang readiness, cocokkan `struktur_versi`, simpan snapshot `versi_konten_tp`, tandai soal dengan versi, aktifkan Materi, ubah TP menjadi `siap`, aktifkan paket yang masih draf dan tulis log aktivitas dalam **satu transaksi**. TP Siap tidak mempunyai tombol kembali ke Draf; koreksi akademik adalah modul selanjutnya.
- **Keamanan:** soal dan kunci di snapshot tidak dicetak dalam HTML checklist atau dikirim ke browser. Checklist hanya menampilkan jumlah/status dan identitas publik. Media tetap privat, tanpa URL file fisik; pengecekan ketersediaan tidak menjadi scan PDF tambahan.
- **Tidak ada SQL update**, `.env`, migration/seeder atau revisi upload. Sudah memakai tabel `versi_konten_tp`, `soal`, `materi`, `tp`, `paket_semester` dan `log_aktivitas` yang ada dalam dump. Tabel student/attempt yang belum diimplementasikan bukan target tahap ini; tampilan siswa/ujian belum diklaim siap sebelum tahap lanjutan.
- **QA** wajib: TP belum lengkap tetap draf, CP berbeda Mapel/Fase, bank 0/di bawah target, kunci/opsi tidak lengkap, media hilang, urutan Materi loncat, versi berubah antara GET dan POST, Guru beda Gen, idempotensi dua klik, setelah sukses snapshot v1 dan status siap, refresh serta penolakan edit konten biasa; uji di XAMPP MySQL dan MariaDB sebelum merge.


## Perbaikan duplikasi checklist Rincian TP (24 Oktober 2026)

Setelah publikasi TP Siap terbukti berjalan di localhost, tampilan **Pemeriksaan awal kelengkapan konten** pada Rincian TP dihapus. Bagian yang tersisa hanya kartu navigasi **Pemeriksaan kesiapan TP** (untuk Draf) atau **Status publikasi TP** (untuk Siap). Daftar pemeriksaan akademik berada tunggal di halaman `/guru/tp/{id}/kesiapan`; ketika sudah Siap, halaman tersebut tetap bisa dibaca tanpa menyediakan aksi penerbitan ulang.

Tautan rekomendasi perbaikan CP pada checklist final diarahkan ke Peta ATP, bukan anchor checklist awal yang sudah dihapus. Penghilangan tabel awal bersifat antarmuka saja: tidak ada rollback, pembatalan publikasi, perubahan status TP, penghapusan snapshot, perubahan database, ataupun modifikasi aturan pemeriksaan akhir. Unit test mencegah tabel ganda muncul kembali.

## Tahap S01–S03 — Katalog Pembelajaran Siswa (24 Oktober 2026)

- **Tahap awal yang aman:** Beranda Siswa → `/siswa/pembelajaran` (mapel) → `/siswa/mapel/{id}` (enam semester) → `/siswa/semester/{paketId}` (daftar TP) → `/siswa/tp/{tpId}` (rincian TP pertama yang Siap). Semua halaman baca-saja; menu Pembelajaran hanya muncul untuk peran Siswa. Tidak ada tombol ujian, media belajar, Resume/HOTS, atau progres palsu.
- Hak akses diperiksa ulang di Controller dan Service: akun Siswa aktif, sandi awal diganti, `siswa.angkatan_id` valid; hanya `penugasan_paket` pada **Gen siswa**, paket `aktif`, serta pasangan Gen/Mapel/semester/tahun ajaran yang sama yang boleh ditampilkan. ID paket/TP dari Gen lain atau TP yang terkunci ditolak dengan respons generik. Kelas/rombel bukan sumber hak; `ref_mapel.guru_id` warisan tidak digunakan.
- Urutan mengikuti `COALESCE(siswa.semester_mulai, ref_angkatan.semester_mulai)`. Semester sebelumnya ditandai **Dilewati** tanpa nilai. Semester setelah semester mulai tetap terkunci sampai progres akademik disediakan; pada semester mulai, hanya TP urutan pertama yang **Siap dan sudah memiliki versi snapshot** boleh dibuka dalam mode rincian. TP Draf, TP tanpa versi, dan TP berikutnya tidak dapat diakses via mengubah ID URL.
- Detail TP menampilkan kode, judul, rumusan, indikator dan **judul daftar Materi saja**; tidak menampilkan HTML media, URL berkas privat, pertanyaan/kunci ujian maupun teks tugas sebelum Diagnostik. Pemanggilan GET tidak membuat progres/attempt/versi pin.
- Teks **Siap — lihat rincian TP** tidak sama dengan **Mulai Diagnostik**. Progres yang terpenuhi, perpindahan semester, ketuntasan dan penguncian versi historis harus dikerjakan oleh mesin pembelajaran dan ujian pada tahap berikutnya. Skema dump lokal saat tahap ini belum memiliki tabel progres operasional yang diimplementasikan: jangan mengarang status tuntas.
- UI memakai `a-siswa-kartu-grid`, `a-siswa-kartu`, `a-siswa-status` dan `a-siswa-materi-ringkas` pada 360/390/768/1100px, tanpa mengganti shell/CSS global.
- **QA penerimaan:** Siswa Gen yang punya TP Siap dapat membuka rincian pertama sesuai semester mulai; Guru/Admin/Pimpinan tidak dapat mengakses. Coba akses paket/TP Gen lain dengan ID URL, semester dilewati, semester berikutnya, TP Draf dan TP kedua; semuanya tetap terkunci. Cek kondisi tanpa Gen/paket/snapshot, semua screen mobile. Tidak ada perubahan SQL, .env, upload PDF/Word atau data TP Siap.


## Fondasi S04a — Aktivasi TP dan pengikatan snapshot versi siswa (24 Oktober 2026)

- **Aksi nyata:** ketika siswa pertama kali memilih `Aktifkan TP & simpan versi` pada S03, POST `/siswa/tp/{id}/mulai` secara transaksional mencatat satu baris `progres_tp` untuk siswa login, TP, versi paket yang ditugaskan, serta `versi_konten_tp_id`. Halaman GET tetap baca-saja; refresh dan POST berulang tidak menciptakan progres atau audit duplikat. Status awal baris `diagnostik` **bukan berarti** percobaan ujian telah dimulai.
- **Gate di Service:** Siswa aktif dari Gen pemilik, paket aktif, hanya TP pertama semester mulai yang Siap, versi snapshot bertanda checksum SHA-256 serta memiliki struktur TP/Materi/Soal yang benar. Service mengunci baris siswa dan TP dalam transaksi InnoDB, cek hak dan versi ulang, mengembalikan pin yang ada jika sudah pernah dimulai.
- **UI:** `a-siswa-aktivasi` memberi penjelasan dampak permanen dan meminta konfirmasi sebelum POST. Setelah berhasil, status dan ID versi konten terpin terlihat; tidak ada pertanyaan/kunci ujian, akses media atau tombol `Mulai Diagnostik` palsu.
- **SQL:** `database/sql/updates/0014_progres_tp_pin.sql`, manual impor satu kali sesuai `database/sql/README.md`, **tanpa migration/seeder**, tidak menghapus data. Progres siswa yang dimulai tetap bersejarah walaupun versi TP selanjutnya diterbitkan.
- **Batas tahap:** mesin ujian Ketat, preflight fullscreen, heartbeat dan skor Diagnostik belum diaktifkan. Implementasikan terpisah dan jalankan pengujian XAMPP sebelum membuka tombol ujian. Gunakan akun uji, bukan data siswa nyata, untuk pengetesan aktivasi irreversibel.


## S05 — Petunjuk & pemeriksaan fullscreen Diagnostik (24 Oktober 2026)

- Halaman `GET /siswa/tp/{id}/diagnostik/petunjuk` tersedia setelah Siswa mengaktifkan TP dan progres berada pada tahap Diagnostik. Menampilkan nama TP, jumlah soal tayang, durasi dan ketentuan **profil Ketat**, dengan pemeriksaan **server** atas Gen, TP Siap, status progres, checksum snapshot terpin, bank soal PG A–E dan kunci (pemeriksaan kunci hanya di server).
- **Privasi:** halaman hanya menerima ringkasan jumlah/status, tidak pernah mengirim teks pertanyaan, opsi, kunci atau `snapshot_json`. Tidak ada `percobaan_ujian`, timer, nilai, atau jawaban yang diciptakan lewat GET. Status kesiapan data tidak sama dengan izin menjalankan ujian.
- Tombol **Uji fullscreen perangkat** memakai `requestFullscreen()` **dari klik siswa** dan memeriksa `document.fullscreenEnabled` serta `document.fullscreenElement`, menampilkan hasil berhasil/gagal/ditolak/offline dan tombol keluar fullscreen. Tidak menyembunyikan pesan ketidakcocokan perangkat dan tidak menganggap User-Agent sebagai bukti. Semua uji ini **belum memulai ujian** dan wajib diulang saat S06 benar-benar dibangun.
- CSS `a-diagnostik-*` hanya untuk layar S05, kartu ringkasan empat metrik, checklist `a-tbl--cards`, target tombol ≥42px, fallback layar sempit dan `role=status` untuk hasil. Tidak merombak UX Guru maupun aturan upload PDF/Word.
- **Batasan implementasi:** desain penuh S06 memerlukan patch SQL berurutan berikutnya untuk `percobaan_ujian`, `jawaban_percobaan`, `riwayat_status_ujian`, `segmen_jeda_ujian`, `pelanggaran_ujian` sesuai docs/07. Jangan buat tombol **Mulai ujian** sebelum ada transaksi aman, waktu aktif server, timeout koneksi, fullscreen yang ditangani, pengacakan soal/opsi serta hasil sah/batal. Hanya browser yang berhasil fullscreen dapat melanjutkan setelah modul S06 selesai.
- QA: uji TP 1 Siap dengan progres terpin, akses Gen lain (404), siswa tanpa aktivasi (404), bank soal kurang, checksum rusak, fullscreen diterima/ditolak, perangkat HP yang tidak mendukung, putus jaringan, keyboard, lebar 360/390/768/1100px. Tidak ada SQL baru pada tahap S05; `0014_progres_tp_pin.sql` tetap menjadi prasyarat.


## S06A — Kontrak mesin ujian Ketat di server (24 Oktober 2026)

- **Tahap persiapan backend**, tidak mengubah halaman siswa S05 atau membuat tombol ujian palsu. Belum ada UI S06. Skema SQL `0015_mesin_ujian_fondasi.sql` menyediakan percobaan, snapshot butir, jawaban idempoten, riwayat status, segmen jeda, serta pelanggaran.
- `SusunButirUjianService` memilih **soal Diagnostik aktif hanya dari snapshot versi TP terpin**, mengacak tanpa penggantian menggunakan `random_int` untuk soal dan kelima opsi A–E, serta menyimpan kunci yang sudah dipetakan sesuai opsi baru di sisi server. Struktur HTML/Word opsional dipetakan per opsi untuk dipakai renderer berizin S06B; kunci/asal soal tidak dimasukkan dalam proyeksi `untukSiswa`.
- `AturanWaktuUjianService` membentuk transisi murni dari timestamp **server** dan angka durasi aktif: timeout koneksi 20 detik (20 detik deteksi tetap aktif), jeda fullscreen 90 detik/kejadian, 180 detik akumulatif, pelanggaran ketiga batal tanpa skor, koneksi maksimum 300 detik, pause tak boleh menerima jawaban, overlap pause tidak dihitung ganda. Kebijakan tidak bergantung worker 24 jam; adapter S06B wajib merekonsiliasi tiap request.
- **Belum tersedia adapter database dan antarmuka ujian**. Jika waktu aktif menjadi nol, Service hanya mengembalikan `harus_finalisasi=true` dan menolak jawaban; ia **tidak** mengubah status menjadi `selesai_sah` tanpa skor. S06B wajib menghitung nilai dan menyimpan status `selesai_sah` + skor **atomik** sebelum memberi hasil. UI S06 tanpa sidebar, fullscreen user-gesture, akses media historis, timer server, hasil S07 dan penanganan keputusan Guru masih tahap lanjutan.
- **Tanpa perubahan UI/CSS global, rute ujian, `.env`, validator PDF, atau data TP Siap.** SQL diimpor manual sekali setelah backup sesuai petunjuk database.


## S06B — Pelaksanaan Diagnostik Ketat nyata (24 Oktober 2026)

**Sudah diimplementasikan:** S05 Petunjuk → halaman /siswa/tp/{id}/diagnostik/ujian memakai shell standalone **tanpa sidebar/panel**. GET ruang ujian belum membuat percobaan. Tombol siswa meminta Fullscreen API dari gesture nyata sebelum POST mulai; perangkat yang menolak fullscreen tidak mengirim POST. CSRF session aktif pada semua POST.

- **Otorisasi:** cek peran Siswa, akun aktif, Gen/paket, TP pertama Siap, progres dan versi terpin di Service. Percobaan dan media/pertanyaan diikat ke progres pemilik serta token acak 48 heksadesimal. Belum ada izin mengulang Diagnostik dibatalkan: keputusan Guru diperlukan.
- **Integritas:** Service memilih/mengacak soal dari snapshot terpin, lalu pada satu transaksi membuat percobaan, semua butir, status dan audit. SELECT FOR UPDATE memastikan POST dua kali mengambil token sama. Bank Diagnostik tidak bercampur Sumatif.
- **Soal dan media:** server hanya mengirim proyeksi tanpa kunci/ID soal asal; teks soal/opsi di-escape, HTML kaya disanitasi sebelum diberi URL gambar privat **berizin siswa pemilik percobaan dan butir terpilih**. Word gambar-only didukung saat render dengan opsi khusus sanitizer; editor Guru lama tetap memakai validasi aslinya. Tautan media tidak membuka endpoint Guru.
- **Aksi ujian:** jawaban PG disimpan dan dikonfirmasi server melalui UPSERT unik per percobaan–butir; tanpa acknowledgment tidak dianggap tersimpan. Timer UI hanya tampilan, server sumber kebenaran. Setiap GET status, heartbeat, jawab, kirim, jeda, dan pulih merekonsiliasi state di bawah lock. Fullscreen keluar membuat jeda, riwayat dan insiden terhitung (blur/visibility/fullscreenchange tumpang tindih tidak menghitung tiga kali). Timeout koneksi 20 detik, jeda maksimal 300 detik. Jeda fullscreen 90 detik/kejadian, 180 detik total; pelanggaran ketiga batal.
- **Hasil:** kirim/waktu habis melakukan finalisasi atomik hanya saat status berjalan. Skor dihitung dari butir/kunci historis dan jawaban tersimpan; status menjadi selesai_sah dengan nilai dan jumlah benar. Progres TP menjadi tuntas bila skor ≥KKM atau belajar bila belum. Batal menjadi perlu_bantuan tanpa skor dan menunggu keputusan Guru. Modul Materi/Resume/HOTS, penilaian komponen, sertifikat dan keputusan Guru untuk mengulang belum diimplementasikan.
- **UI:** kelas .a-ujian-* terisolasi: header/timer, navigator soal, status server, soal rich HTML, kirim berkonfirmasi, overlay jeda dan hasil; responsif untuk HP. Cache-Control no-store dan Referrer-Policy no-referrer. Tidak ada notifikasi, sidebar atau perayaan selama ujian.
- **Batas pengamanan browser:** Fullscreen API adalah mitigasi best-effort, bukan bukti kriptografis anticurang. Shared hosting merekonsiliasi pada setiap request; transisi tepat detik ke-20 tidak bisa dijamin tanpa worker/request.
- **QA:** PHPUnit MariaDB sintetis menguji begin ganda idempoten, sepuluh butir tanpa kunci di JSON, upsert, submit skor 100/tuntas, tiga insiden pembatalan, Gen lain ditolak; unit test gambar-only dan HTML aktif. **Pengujian manual XAMPP wajib** dengan siswa uji, Word tabel/gambar/Equation, editor manual, mobile, refresh, offline, tab pindah, timer dan timeout 90/180/300 detik. Jangan gunakan siswa nyata untuk uji percobaan permanen.

**SQL:** tidak ada patch baru. Tabel progres_tp dari patch 0014 serta enam tabel ujian dari patch 0015 wajib sudah tersedia. Tanpa Migration/Seeder dan tanpa mengubah validasi PDF/Word.


## S08A — Pembaca Materi Siswa berbasis snapshot terpin (24 Oktober 2026)

Setelah Diagnostik menghasilkan `progres_tp.status=belajar`, halaman Rincian TP menampilkan **Buka Materi 1** yang benar-benar dapat dibuka. Materi selanjutnya **tetap terkunci** sampai aktivitas media, Resume final, dan HOTS final Materi sebelumnya tersedia; jangan memakai status palsu seperti "sudah dipelajari" ketika bukti waktu aktif belum ditangani. Jika `status=tuntas`, semua Materi boleh dibaca **secara sukarela** tanpa Kirim Resume/HOTS resmi dan tanpa mengubah nilai Diagnostik. Status `diagnostik`/`perlu_bantuan` tidak membuka media.

- Rute `GET /siswa/tp/{tp}/materi/{id}` dan `/media` dilindungi filter Siswa **serta** pemeriksaan Gen, semester, TP pertama Siap, progres dan checksum `versi_konten_tp` di Service. Daftar/isi Materi berasal dari **snapshot terpin**, bukan perubahan database Materi live. ID yang ditebak, berkas Gen lain, dan Materi lebih jauh ditolak server tanpa membuka file privat.
- Pembaca PDF, gambar, audio (HTTP Range) dan iframe YouTube-nocookie sudah tersedia dengan MIME yang dipersempit, berkas privat `writable/uploads/materi/`, larangan path traversal, header `nosniff` dan `Cache-Control: private, no-store`; link file fisik tidak dikirim ke HTML. Validasi **upload PDF yang FIX** tidak diubah.
- Petunjuk Resume dan HOTS tiap Materi tampil baca-saja. **S08A belum membuat ketuntasan media, heartbeat, pelacakan semua halaman PDF, 90% putar video/audio, maupun draf dan Kirim Final**. Implementasi S08B wajib mengikuti docs/06 (§3) dan S09/S10 sebelum membuka Materi 2. Jangan membuat tombol Kirim palsu atau otomatis menandai lengkap karena file dibuka.
- Gaya baru `a-siswa-media`, `a-siswa-materi-instruksi`, `a-siswa-materi-buka` hanya untuk pembaca Siswa; 360/390/768 dan desktop, reuses `a-media-video`, `a-materi-pdf`, `a-materi-audio`, `a-materi-gambar`.
- **SQL tidak berubah**, GET tidak menulis progres atau skor. QA sintetis harus menguji `belajar` hanya Materi 1, `tuntas` sukarela, progres belum diagnostik, akun Guru, lintas Gen, URL Materi 2, checksum rusak, media privat serta tidak ada mutasi.


## S08B — Pencatatan aktivitas media siswa (24 Oktober 2026)

- **Prasyarat SQL:** impor sekali `database/sql/updates/0016_progres_aktivitas_materi.sql` sesudah patch 0014–0015, backup dahulu, tanpa migrasi/seeder. Dua tabel: `progres_materi` unik berdasarkan progres TP + Materi + versi snapshot terpin, dan `halaman_progres_materi` untuk pencatatan PDF.js nanti.
- GET `/siswa/tp/{tp}/materi/{materi}/aktivitas` hanya membaca progres. POST URL yang sama menyimpan heartbeat di transaksi setelah cek **peran Siswa, Gen, TP, snapshot, status `belajar`, Materi pertama yang diizinkan** dan lock progres TP/row media. GET tidak membuat progres; siswa `tuntas` belajar sukarela tidak bisa menyimpan kredit resmi.
- **Gambar:** kredit berdasarkan interval server 5–20 detik. Browser hanya mengirim ketika gambar tampil ≥50% viewport, gambar sudah dimuat, tab terlihat serta fokus. Minimal 30 detik atau waktu Guru, mana yang lebih besar.
- **Audio/YouTube:** browser hanya mengirim selama pemutaran aktif dengan kecepatan normal, tab terlihat dan fokus. Server memeriksa selisih detak 5–20 detik serta delta posisi pemutar yang wajar. Seek maju, posisi mundur dan jarak heartbeat berlebihan tidak memperoleh kredit. Penetapan `media_selesai` mensyaratkan waktu aktif minimal Guru **dan** total waktu putar serta posisi terjauh ≥90% durasi. Metadata durasi dilaporkan pemutar: ini mitigasi untuk aktivitas belajar, **bukan jaminan anti-manipulasi absolut**. Video memakai YouTube IFrame API; ketika API terhalang jaringan, progress tidak boleh bertambah secara palsu.
- **PDF:** iframe browser yang telah tersedia tetap bisa dibaca, tetapi **tidak mengirim heartbeat yang mengklaim per halaman dan tidak dapat `media_selesai`**. Master 06 mensyaratkan pembaca PDF.js **lokal**, seluruh halaman disaksikan dan waktu aktif default 15 detik per halaman. Integrasi pustaka lokal dan bukti halaman masih tahap S08B lanjutan; dilarang menggunakan waktu berada pada iframe sebagai pengganti pembaca. Tidak mengubah validasi upload PDF yang sudah dinyatakan FIX.
- Media selesai **bukan** Resume final atau HOTS final. Materi 2+ tetap terkunci hingga S09/S10 sah. Tidak ada nilai TP atau status ujian yang dimutasi oleh heartbeat. Pesan berhasil/gagal, offline, media belum aktif, status server, progress bar dan tampilan 360px memakai CSS terisolasi `a-siswa-media-progres`.
- QA: pengujian deterministik atas 5–20 detik, timeout, seek, durasi, ambang waktu dan larangan PDF; SQLite sintetis untuk Gen/pin/progres idempoten; DDL pada MariaDB sementara untuk FK/UNIQUE. **Uji XAMPP nyata** dengan gambar dan audio/YouTube, tab background, pause, seek, refresh, koneksi putus, dan periksa status per siswa.


## S08C — Navigasi Materi, fullscreen pembaca dan zoom gambar (25 Oktober 2026)

- Halaman `/siswa/tp/{tp}/materi/{id}` menyediakan **Kembali ke Rincian TP** dan **Materi Selanjutnya** berdasarkan urutan `materi` pada snapshot versi TP siswa. Pada jalur `tuntas` karena lulus Diagnostik, semua Materi bersifat sukarela sehingga tautan berikutnya aktif langsung tanpa kewajiban waktu/media/Resume/HOTS. Pada jalur `belajar`, tombol berikutnya ditampilkan **terkunci** dengan alasan "media, Resume final, dan HOTS final belum terpenuhi". Belum ada layanan Resume/HOTS final yang dapat membuktikannya, sehingga tombol tidak pernah membuka Materi 2 pada S08C. **Service server tetap menolak URL Materi 2 yang ditebak.**
- Media **PDF dan gambar** dibungkus `a-pembaca-fullscreen` dengan tombol **Layar penuh / Keluar layar penuh** yang benar-benar memanggil Fullscreen API saat klik. Toolbar tetap ada di dalam area fullscreen, Escape menutup fullscreen; browser/perangkat yang menolak menampilkan pesan galat. PDF masih memakai pembaca browser/iframe; fungsi fullscreen tidak menggantikan persyaratan PDF.js lokal dan tidak memberikan kredit halaman.
- Gambar mempunyai toolbar **Perkecil − / Perbesar + / Atur ulang**, rentang **50–300%** langkah 25%, indikator persentase, scroll area saat diperbesar, dan pintasan +, −, 0 ketika fokus di area gambar. Pembesaran tidak menandai materi selesai dan tidak memodifikasi file asli, database, nilai, maupun waktu belajar.
- Heartbeat gambar S08B kini mengamati keterlihatan **area pembaca** `data-gambar-observer` (bukan rasio seluruh piksel gambar yang dapat membesar 300%). Masih memerlukan gambar telah termuat, tab terlihat/fokus, serta interval server yang sah. Ini mempertahankan pembelajaran aktif di fullscreen/zoom tanpa kredit palsu dari tab tersembunyi.
- Tampilan memakai CSS prefiks `a-pembaca-*` dan `a-materi-lanjut`, responsif HP/laptop, tanpa Bootstrap/React/Vue. Tidak ada patch SQL; tidak menyentuh fitur ujian/hasil diagnostik maupun validasi upload PDF 15MB/40 halaman.
- QA: uji siswa `tuntas` dapat berpindah Materi 1→2 tanpa waktu; `belajar` tidak dapat membuka Materi 2; Materi terakhir tidak menyediakan tautan selanjutnya; PDF dan gambar fullscreen/ESC, gambar zoom 50–300% dan reset; mode HP, browser yang menolak fullscreen, scrollbar dan heartbeat setelah zoom.


## Koreksi S08C — Zoom gambar presisi (25 Oktober 2026)

Perbaikan laporan XAMPP: zoom sebelumnya melakukan `img.style.width = zoom + '%'`, yaitu persentase **lebar area pembaca**, bukan perbesaran relatif ke gambar yang pas di layar. Hal ini menyebabkan gambar kecil/portrait terdistorsi secara pengalaman, serta posisi baca bergeser saat zoom/fullscreen.

- Pembaca kini menghitung **skala muat** berdasarkan `naturalWidth`/`naturalHeight` dan kedua dimensi viewport (`min(1, areaW/imageW, areaH/imageH)`). Pada 100% seluruh gambar terlihat dan tidak dipaksa membesar melewati resolusi asli.
- Tombol 50–300% mengubah **ukuran piksel hasil skala muat** pada stage pusat; scroll horizontal/vertikal tersedia saat gambar melebihi viewport. Perubahan zoom mempertahankan pusat gambar yang sedang dibaca, bukan selalu ke pojok kiri atas.
- `ResizeObserver` menghitung ulang pada perubahan lebar/tinggi pembaca; event `fullscreenchange` menghitung ulang setelah layout fullscreen berubah. Fullscreen PDF yang sudah FIX tidak diubah.
- Tombol plus/minus/reset, keyboard +/−/0, Ctrl+scroll, dan drag mouse pada gambar yang lebih besar dapat dipakai; interaksi sentuh mempertahankan scroll asli. Gambar yang belum/salah dimuat tidak dapat di-zoom dan menampilkan galat. File asli tidak dimodifikasi.
- Area aktivitas `data-gambar-observer` S08B, izin Media Gen, nilai, status Diagnostik, timer resmi dan SQL **tidak berubah**. Kontrol presentasi zoom tidak menandai durasi atau ketuntasan.
- Pengujian ditambah JavaScript **perilaku** (Node vm: lanskap/potret/gambar kecil, ukuran 100–300%, scroll, fullscreen, zoom terpusat, Ctrl+wheel/keyboard, gagal muat), di samping PHP/SQLite regresi. **Uji XAMPP browser tetap diperlukan** pada gambar sebenarnya serta Ctrl+F5 bila aset cache masih versi lama.


### Implementasi UI S08D PDF siswa (25-10-2026)

Pembaca PDF.js lokal dimuat hanya di halaman siswa dengan Materi jenis PDF:
toolbar halaman Sebelumnya/Berikutnya, zoom 50–200%, fullscreen,
dan area kanvas yang dapat di-scroll. Komponen menggunakan a-pembaca-* dan
a-pdf-* pada app.css, bukan framework CSS baru. Error render dan
ketidakcocokan halaman tampak langsung, tanpa mengklaim selesai.
Kontrol keyboard PageUp/PageDown/ArrowLeft/ArrowRight; toolbar responsif.


### Komponen G10/G11 (25-10-2026)

Antrean Guru memakai tabel a-tbl, filter Gen–Mapel terotorisasi dan
paginasi server 25 baris. Detail menampilkan identitas siswa/TP/Materi,
isi jawaban dengan linebreak ter-escape, pesan salah/sukses, nilai 0–100,
konfirmasi final, umpan balik dan tombol Simpan & Berikutnya. Elemen
.a-penilaian-* dalam app.css mengikuti tombol, form dan warna yang
sudah ada. Halaman tidak menampilkan aksi mengubah nilai historis;
revisi harus melalui jalur AB-33.

## Kontrak desain card BAB/TP v2.5 (26 Oktober 2026)
Gunakan token/kelas CSS kustom yang telah ada di dokumen ini, tanpa Bootstrap/Tailwind runtime. Card BAB dan TP memprioritaskan `nama_bab`/`nama_tp` maksimum **50 karakter** agar konsisten pada lebar layar HP/laptop. Deskripsi masing-masing ≤225 karakter tersedia pada detail atau ringkasan terkendali, dengan akses teks lengkap melalui interaksi yang nyata. Kode TP (contoh `BIND.8.1.1`) harus terbaca. Badges Esensial/NonEsensial hanya menjelaskan jenis, tidak menjadi klaim ketuntasan nilai. Progres semester hanya NonEsensial. Ujian Pra-Semester mengikuti UI ujian Ketat tanpa sidebar/perayaan. Komponen baru wajib dimasukkan ke galeri saat kode diimplementasikan. Contoh/galeri implementasi v3 lama tidak mengganti kontrak ini.


### G03 — Modal native untuk tambah BAB/TP (27 Oktober 2026)
Gunakan `.a-modal` yang sudah ada dengan `<dialog>`, judul `aria-labelledby`, `showModal()` untuk trap fokus native, tombol Tutup/Batal serta `Escape`. Perluas dengan `.a-modal--atp`, header/aksi flex serta batas tinggi area viewport untuk smartphone; jangan menambah Bootstrap/React/Vue. Nama BAB/TP dapat sepanjang **50** karakter tanpa memotong teks yang disimpan; deskripsi tetap 225. Contoh modal ke galeri komponen, JS native `app.js`, validasi server dan CSRF tetap wajib.


### G03 — Kartu semester dan kartu BAB (27 Oktober 2026)
Peta ATP memakai dua kelompok grid: Semester 1–3 dan Semester 4–6. Enam kartu mempunyai palet background khusus lembut (`.a-atp-warna-1`–`.a-atp-warna-6`) dan border aksen, dengan hover/fokus terlihat, informasi status paket, angka BAB, angka TP dan kategori TP. Gunakan kartu `<a>` sebagai tautan semantik; navigasi tetap bisa digunakan tanpa JavaScript.

Halaman detail `guru/semester_atp.php` menampilkan header dan toolbar kontekstual, lalu `.a-atp-bab-card` untuk setiap BAB dengan warna bergantian dan tabel TP yang tetap semantik dan responsif. Tombol Tambah BAB dan Tambah TP hanya muncul pada konteks yang diizinkan, membuka komponen native `<dialog class="a-modal a-modal--atp">` dengan JS native `app.js`. Tidak membuat layout kartu untuk HTML tombol palsu. Responsive: 3 kolom pada desktop lebar, 2 tablet, 1 HP; tabel BAB dapat digulir; dialog tinggi maksimum mengikuti viewport. Pelihara status kosong dan pesan penguncian. Galeri komponen diperbarui bersama implementasi.


### Penyegaran aset setelah Pull Git (QA 27 Oktober 2026)
Layout panel `app/Views/layouts/panel.php` **wajib memberi versi pada URL** `public/assets/css/app.css` dan `public/assets/js/app.js`, mengikuti waktu modifikasi file (`filemtime`) pada `FCPATH`. Ini diperlukan karena browser dapat menyimpan CSS lama sementara HTML/View sudah diperbarui: tautan ringkasan Peta ATP akan tampak sebagai teks menyambung dan kartu BAB kehilangan warna. Jangan menambah CSS inline sebagai jalan pintas; perbaiki pemuatan satu stylesheet design system dan gunakan token yang benar-benar didefinisikan di `:root`. Pemeriksaan setelah `git pull`: URL stylesheet mengandung parameter versi berbeda jika berkas berubah; mode 3/2/1 kolom dan enam warna kartu terlihat. Pengujian visual tidak dapat diganti hanya dengan PHP lint.
