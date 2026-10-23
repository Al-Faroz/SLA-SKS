# QA sinkronisasi Master v2.4 — 23 Oktober 2026

Dokumen kontrol QA pendamping; **bukan Master Document ke-17** dan tidak mengubah keputusan akademik.

## 1. Pemeriksaan struktur Master Document
- [x] Berkas domain 01–16 tersedia pada branch `docs/master-v2-4-atp-tp-materi`.
- [x] Domain CP → ATP → TP → Materi/Submateri menjadi target dan BAB bukan objek operasional (04, 05, 06, 10).
- [x] Dua jalur siswa: Diagnostik memenuhi KKM → TP tuntas; atau Materi + Resume/HOTS tiap Materi → Sumatif (02, 05, 06, 08).
- [x] Guru aktif satu per Gen–Mapel dan seluruh validasi kepemilikan pada Service (02, 03, 11, 14).
- [x] PG lima opsi A–E dengan satu kunci dan parser Word konten kaya (05, 07, 11, 14, 16).
- [x] Dokumen 16 memuat E2E-089 sampai E2E-112 untuk fitur v2.4.
- [x] Checklist CSS diselaraskan dari opsi soal 2–5 yang tertinggal menjadi lima opsi A–E; kelas pager kanonis `a-pager` dipertegas.
- [ ] Seluruh perilaku diuji terhadap runtime CI4, SQL dan XAMPP: **belum**, di luar PR dokumentasi.

## 2. Gate template Excel ATP
**Path wajib:** `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx`.

- [ ] Berkas biner terunggah nyata pada branch; checksum dicek terhadap `STATUS-TEMPLATE-V2.4.md`.
- [ ] Buka di Excel/LibreOffice: 10 sheet terdiri atas panduan, RINGKASAN, REFERENSI CP, SEMESTER 1–6, dan CONTOH MAT VII.
- [ ] Pastikan formula dan dropdown tidak rusak, tidak ada label BAB operasional, Subbab tetap sah sebagai bagian Materi/Submateri.
- [ ] Jangan mengisi data siswa/Guru nyata atau CP palsu ke file referensi. Identitas dan CP akan diisi generator server setelah implementasi.
- [ ] Uji impor terikat Gen–Mapel, idempoten, parsial semester dan menolak TP Siap dilakukan pada PR kode terpisah.

## 3. Gate template Word
**Path wajib:** `docs/TEMPLATE_SOAL_SLA-SKS.docx`.

- [ ] Berkas biner terunggah nyata pada branch; checksum dicek terhadap `STATUS-TEMPLATE-V2.4.md`.
- [ ] Struktur `SLA-SKS-WORD-V1`, opsi A–E, tepat satu ✓ per soal, seluruh 10 contoh dapat dibaca Word.
- [ ] Equation/OMML, gambar dan tabel tertanam dapat ditinjau; contoh tidak boleh dianggap bank akademik produksi.
- [ ] Uji nyata parser di stem **dan semua pilihan**, tabel bersarang, Unicode/RTL, tautan Drive, serta file rusak saat implementasi.
- [ ] Dua output Dinamis Diagnostik/Sumatif per TP **belum** tersedia sebagai kode.

## 4. Enam area UI yang harus diverifikasi bersama E2E
| Area | Kontrak | E2E |
|---|---|---|
| Admin Pengelola | Form landscape full-width atas; daftar bawah; pagination server+filter | 108 |
| Admin Siswa | Form landscape full-width atas; daftar bawah; pagination server+filter | 108 |
| Penetapan Guru per Gen | Guru/Alasan/Simpan pada kolom berbeda; server pagination dan POST aman | 109 |
| Daftar CP/status versi | Hanya bagian tabel diubah; ekspor XLSX terfilter dan pagination | 110 |
| Identitas aplikasi | Upload logo dengan preview/fallback dan favicon hasil sumber yang sama | 111 |
| Profil semua role | Foto full-width di atas; Identitas/Kontak dua kolom desktop, satu mobile | 112 |

Verifikasi UX wajib 360, 390, 768, 1100px, keyboard, CSRF, kosong/loading/error/sukses dan izin Service. **Tidak ada implementasi UI dalam PR dokumentasi ini**.

## 5. Gate merge dan kelanjutan
1. **Jangan merge PR Master v2.4** sampai dua biner ada pada `docs/` dan pemeriksaan template PASS.
2. Pastikan link di README/docs/README tidak rusak setelah file tersedia.
3. PR implementasi berbeda: SQL dari dump lokal (setelah diserahkan dan dianalisis), generator ATP, parser Word, Service dan UI.
4. Jangan menggabungkan PR #13 berkontrak BAB lama apa adanya.
5. Jangan mengubah `.env`, root CI4, atau menghapus/reset data produksi. Uji XAMPP dilaksanakan pemilik setelah PR implementasi disetujui.
