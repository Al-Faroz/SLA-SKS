# Checkpoint F1 — Stabilisasi ATP setelah impor enam semester

**Tanggal:** 1 November 2026  
**Branch pekerjaan:** `feature/f1-stabilisasi-pasca-impor-atp-20261101`  
**Basis:** `feature/v25-atp-enam-semester-audit-belajar-20261031` commit `76a1345`  
**Status:** Perubahan kode dan tes regresi sudah masuk branch; **menunggu eksekusi tes MySQL QA dan pengujian XAMPP pengguna sebelum F1 dapat dinyatakan FIX**.

## Sumber kebenaran

- `README.md`, `AGENTS.md`, `docs/README.md`, Master v2.5, terutama dokumen 04/05/06/10/11/12/14/16.
- `docs/CHECKPOINT-IMPOR-ATP-6-SEMESTER-DAN-ROADMAP-FIX-2026-11-01.md`.
- Dump privat `sla_sks_v4 (6).sql`, waktu header ekspor 01-11-2026 09.56, MariaDB 10.4.32. **Jangan menambahkan dump/hash/NISN/sesi/identitas ke Git.**

## Bukti baseline sesudah impor

- Enam paket Gen–SKI (Semester 4 sudah aktif sebelumnya, lima draf), 12 BAB, 36 TP (24 Esensial, 12 NonEsensial), tepat 36 Materi draf/1 Materi per TP, 35 PDF/1 YouTube.
- Semua Materi masih `aktif=0`, tanpa URL/berkas, tanpa durasi; seluruh TP `status=draf`, belum ada bank soal atau snapshot.
- Tidak ada izin untuk menghapus/menimpa paket/TP/Materi atau mengimpor ulang data akademik demi testing.

## Perubahan F1

1. **Pratinjau impor ulang lebih ketat:** semester yang sudah terisi hanya berstatus **Tetap** jika BAB dan TP benar-benar cocok, termasuk `cp_mapel_id`, `urutan_dalam_bab`, `urutan_atp`, serta **judul dan jenis Materi** F/G. Perubahan salah satu menjadi **Galat**, tidak memulai INSERT/UPDATE. Fingerprint konfirmasi tetap memperhitungkan Materi agar perubahan sesudah pratinjau membatalkan batch.
2. **Tes integrasi impor diselaraskan kontrak V25-3:** satu Materi draf baru per TP, bukan nol; status `aktif=0`, URL/berkas kosong, log sekali; duplikasi, perbedaan Excel F/G, perubahan Materi oleh Guru, perubahan CP/urutan, konfirmasi basi, dan rollback gagal diujikan pada fixture **MySQL QA terpisah**. Nama TP mengikuti kapasitas database V4-0005, bukan asumsi 35 karakter lama.
3. **UI Detail TP Guru:** satu Materi lebih jelas dengan status `Draf — belum terbit`, tautan aksi langsung **Lengkapi satu Materi ini** menuju form Sunting Materi tersimpan. Tombol tambah hanya muncul saat belum ada Materi. Tidak dibuat Materi baru, tidak ada fitur dummy.
4. **Verifikasi SQL baca-saja:** `database/sql/qa/V4-0005_ATP_ENAM_SEMESTER_READONLY.sql` menghitung paket/BAB/TP/Materi, mendeteksi anomali relasi CP, BAB, duplikasi Materi, status penerbitan dan nomor TP. **Tidak ada DML atau DDL**.

## Pengujian dan kondisi kelulusan

**Yang benar-benar dilakukan:** pembacaan sumber dan audit statis branch; tes otomatis GitHub Actions pada **commit `c7dc466`** berhasil **11/11 workflow**, termasuk PHP lint, PHPUnit unit, integrasi CI4 + MySQL 8 fixture anonim `sla_sks_qa`, rollback dan idempotensi ATP, Detail TP, Materi Draf/berkas, CP SKI, UI dan kesiapan TP. [Lihat hasil Actions branch](https://github.com/Al-Faroz/SLA-SKS/actions?query=branch%3Afeature%2Ff1-stabilisasi-pasca-impor-atp-20261101).

**Akar regresi CI yang diperbaiki:** fixture `tests/fixtures/atp_import_v25_mysql.sql` semula hanya menyimpan dua kolom Materi, sedangkan Service V25-3 membutuhkan rencana Materi lengkap. Fixture `tests/fixtures/peta_atp_mysql.sql` semula tidak memiliki `jenis_tp` dan `cp_mapel_id`, serta INSERT TP perlu daftar kolom eksplisit. Workflow `qa-v25-atp-global-initial-only.yml` semula tidak dapat dimulai karena nama job mengandung titik dua tak dikutip dalam YAML; sudah dibetulkan dan sekarang PASS. Tidak ada perubahan skema DB pengguna.

**Belum dijalankan:** uji browser **XAMPP Windows/MariaDB 10.4** terhadap database lokal nyata, ukuran file/mime media aktual, seluruh kondisi Dark/Light dan perangkat. Hasil otomatis CI tidak menggantikan QA pemilik sebelum F1 FIX.

### Uji lokal F1 (setelah pemilik pull branch F1)

1. Pastikan `git status --short` aman. Checkout branch F1 dan `git pull --ff-only`; **jangan merge main**.
2. Login Guru Gen–Mapel SKI → Peta ATP → buka Semester 4 → pilih salah satu TP. Pastikan **satu** Materi draf tampil dengan judul/media dari Excel dan tombol **Lengkapi satu Materi ini** membuka editor, tanpa form menambah Materi kedua.
3. Lakukan satu perubahan *non-destruktif* pada Materi draf (misalnya judul), kemudian simpan dengan URL YouTube atau file PDF sesuai jenis. Periksa data hanya satu Materi, `struktur_versi` bertambah, status TP masih `draf`; ganti berkas hanya dengan jenis media sama apabila sudah terpasang.
4. Uji hak G1: guru Gen lain dan URL TP/Materi yang bukan miliknya ditolak oleh Service dan Controller, tidak mengubah data.
5. **Jangan konfirmasi impor ulang** pada database utama. Untuk uji idempotensi, unggah workbook identik ke **pratinjau saja**; mestinya `Tetap`, konfirmasi tidak tersedia. Jika sudah mengubah judul Materi melalui aplikasi, pratinjau Excel lama mestinya **Galat**, tidak merusak data.
6. Jalankan SQL QA baca-saja bila diperlukan dan periksa keluaran agregat serta anomali. Tes destruktif dan rollback wajib hanya di `sla_sks_qa` anonim yang terpisah, jangan di `sla_sks_v4` berisi akun nyata.

**Gate F1:** editor hasil impor, pemeriksaan hak, status Tetap/Galat, dan media draf terbukti melalui XAMPP; tes regresi pada database QA aman berjalan tanpa error. Baru pindah ke **F2 — Media lengkap, bank soal, dan TP Siap**.

## Verifikasi CI terbaru (sebelum pengujian XAMPP)

Pada commit `c7dc466`, seluruh 11 workflow selesai dengan **conclusion=success**:

- QA ATP Global Initial Only v2.5 — PASS
- QA Konfirmasi ATP Transaksional v2.5 — PASS
- QA Pratinjau Impor ATP v2.5 — PASS
- QA Peta ATP v2.4 — PASS
- QA Materi Draf v2.4 — PASS
- QA Berkas Materi Privat v2.4 — PASS
- QA Rincian TP v2.4 — PASS
- QA Detail TP dan Materi Responsif — PASS
- QA Kesiapan dan Publikasi TP Siap — PASS
- QA Pratinjau YouTube Materi v2.4 — PASS
- QA CP SKI v2.5 dan ATP stale reference — PASS

Nama workflow lama `v2.4` adalah nama tes historis; bukan penggantian kebijakan Master v2.5.

## Perubahan skema dan batas proyek

**Tidak ada patch SQL atau perubahan skema untuk F1**. Patch `V4-0005` sudah terpasang berdasarkan dump terakhir; tidak ada Migration/Seeder. Tidak menyentuh `.env`, konfigurasi DB, root `index.php`/`.htaccess` atau penyimpanan media historis. Tidak ada merge main.

## Koreksi navigasi hasil uji lokal (1 November 2026)

**Laporan pemilik:** setelah pull F1, Guru tidak menemukan halaman untuk melengkapi Materi hasil impor ATP. **Penyebab terverifikasi pada kode:** tabel TP pada halaman `guru/peta-atp/semester/{n}` sebelumnya hanya menampilkan Kode/Nama/Deskripsi/Jenis/Status tanpa tautan menuju Detail TP. Route `GET guru/tp/{id}` serta editor `GET guru/tp/{tpId}/materi/{materiId}/edit` sebenarnya sudah terpasang.

**Perbaikan di branch `fix/f1-navigasi-detail-tp-materi-20261101`:**
- Tambah kolom **Aksi** per TP dengan tautan `Kelola TP / Materi` (untuk draf) atau `Lihat TP` (untuk Siap), menggunakan ID TP asli dari database, bukan nomor BAB atau urutan; akses lintas Gen tetap dibatasi `DetailTpService`.
- Dari Detail TP, tombol **Lengkapi satu Materi ini** tetap menuju editor Materi hasil impor; **tidak membuat Materi kedua**.
- Tambah pengujian kontrak navigasi Semester → Detail TP → Sunting Materi pada `AtpEnamSemesterUiTest` dan workflow pemeriksaan UI.
- **Tidak mengubah data SQL, DB lokal, .env, file materi privat atau keputusan bisnis.**

**Uji XAMPP yang wajib:** Login Guru → Peta ATP → Semester 4 → daftar BAB dan TP → tombol `Kelola TP / Materi` → Detail TP menampilkan satu Materi → `Lengkapi satu Materi ini` → form Sunting. Ulangi uji pada Semester 1 (persiapan Guru), dan pastikan Guru lintas Gen tidak dapat mengakses detail lewat URL langsung. Uji tautan dan tampilan Dark/Light. Jangan menambah Materi kedua.

**Status:** Belum FIX sebelum pemilik menyatakan jalur navigasi dan penyuntingan di XAMPP berhasil.
