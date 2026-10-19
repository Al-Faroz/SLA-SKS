# 13 — Design System dan Standar CSS SLA-SKS

> **Dokumen pengikat semua halaman.** Implementasi CSS utama `public/assets/css/app.css` sebagai **satu stylesheet hasil akhir**; boleh dipisah selama pengembangan lalu digabungkan, tetapi tidak membuat stylesheet liar per role. Tidak memakai Bootstrap/Tailwind/Materialize sebagai framework wajib, tidak memakai icon font.

## 1. Karakter visual
Gaya LMS/kelas daring modern dengan **hijau muda** sebagai identitas, sidebar hijau lebih gelap, bidang putih/abu netral, kartu lapang, navigasi jelas, dan sedikit ornamentasi. Siswa mendapat bilah progres/lencana yang hangat, Guru dashboard aksi, Admin dashboard kontrol, Pimpinan dashboard analitik. **Satu sistem token dan satu library komponen** digunakan semua role. Tidak mengikat desain ke merek antarmuka pihak ketiga.

## 2. Token CSS resmi awal
Nilai berikut menjadi rancangan awal yang bisa diuji terhadap kontras, lalu *dibekukan sebagai kode* saat K0; komponen wajib menggunakan token, bukan nilai heksadesimal berulang.

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
  --a-font: system-ui, -apple-system, "Segoe UI", sans-serif;
}
```

**Larangan:** class dari framework CSS luar, inline `style` bebas, warna hardcode di halaman, CSS ID yang mengikat layout khusus, `!important` sebagai perbaikan instan, SVG dekorasi tanpa aksesibilitas, shadow tebal/kartu di dalam kartu berlapis, dan animasi pada ruang ujian. Nilai warna status dan kontras harus diverifikasi otomatis sebelum produksi.

## 3. Konvensi kelas
Semua kelas komponen memakai awalan `a-`. Modifier dapat berbentuk `a-btn--utama`, state `is-aktif`, `is-gagal`, `is-terkunci` sesuai pola konsisten. **Satu komponen = satu resep** pada dokumen dan galeri, jangan mengubah styling universal lewat selector tag global yang dapat merusak komponen lain.

| Komponen | Kelas | Aturan |
|---|---|---|
| Shell | `a-app`, `a-side`, `a-main`, `a-header` | Sidebar responsif, satu header role |
| Kartu | `a-card` | Permukaan netral, satu tingkat, tanpa kartu bertumpuk |
| Tombol | `a-btn`, `a-btn--utama`, `a-btn--sekunder`, `a-btn--bahaya` | Target sentuh ≥40px; disabled benar-benar nonaktif |
| Form | `a-field`, `a-label`, `a-help`, `a-error` | Label eksplisit, help/error konsisten |
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
<section class="a-card" aria-labelledby="bab-judul">
  <div class="a-card__atas">
    <h2 id="bab-judul">BAB 1 · Bilangan</h2>
    <span class="a-badge a-badge--siap">Siap</span>
  </div>
  <p class="a-help">3 TP · 2 TP tuntas</p>
  <div class="a-bar" role="progressbar" aria-label="Progres BAB" 
       aria-valuemin="0" aria-valuemax="100" aria-valuenow="67">
    <span class="a-bar__isi" style="width:67%"></span>
  </div>
  <a class="a-btn a-btn--utama" href="/siswa/bab/123">Buka BAB</a>
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

## 5. Breakpoint dan layout
- **360–767px:** panel 1 kolom, menu ringkas, kartu lebar penuh, target 40px; layar siswa wajib nyaman.
- **768–1099px:** tablet, grid 2 kolom sesuai konten.
- **≥1100px:** sidebar tetap, hingga 12 kolom dashboard Pimpinan; batas lebar konten `--a-lebar-konten`.
- Form unggah Excel kompleks desktop-first, tetapi status, daftar dan penilaian tetap bisa dibaca mobile.
- PDF/sertifikat A4 landscape menggunakan CSS **terpisah dalam view cetak Dompdf**, bukan ukuran viewport layar; konten preview disimulasikan sesuai rasio A4.

## 6. Animasi dan media
Boleh animasi ringan **hanya** pada bilah segmen, lencana baru, dan perayaan siswa; matikan pada `prefers-reduced-motion`. Tidak ada animasi pada S06. PDF.js dan pustaka efek balik halaman diaktifkan hanya saat materi PDF; jangan membebani semua dashboard dengan library itu. Ikon SVG inline dari kumpulan ikon yang diizinkan, dengan label aksesibel pada tombol tanpa teks.

## 7. Halaman katalog/galeri
`docs/assets/galeri-komponen.html` menyediakan contoh statis tombol, status, kartu, form, tabel, step, modal, dan contoh responsive. Ketika komponen baru ditambahkan: ubah dokumen ini → ubah galeri → ubah `app.css` → uji pada 390px dan 1100px → commit bersama. Jangan mengimplementasikan CSS komponen baru tanpa memperbarui galeri.

## 8. Checklist UI sebelum diterima
Label form dan pesan galat; fokus keyboard; kontras WCAG; aksi disabled; empty state; loading; tampilan 360/390/768/1100; teks panjang; nama sekolah panjang; opsi soal 2–5; draf manual; peringatan browser tanpa fullscreen; layar ujian tanpa gangguan; logo dan TTD terlihat jelas di sertifikat preview; QR/teks tidak menabrak background; printer A4 landscape tidak memotong area aman.
