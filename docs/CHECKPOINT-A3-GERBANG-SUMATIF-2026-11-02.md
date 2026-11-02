# Checkpoint — A3 Gerbang Syarat Sumatif Siswa v2.5

**Tanggal:** 2 November 2026  
**Branch:** `feature/v25-a3-gerbang-kesiapan-sumatif-20261102`  
**Basis:** PR #92 / commit `56fee33`. Dependensi bertingkat: PR #90 → #91 → #92 → A3.  
**Status:** Implementasi untuk ditinjau dan diuji; **belum FIX XAMPP**.

## Lingkup implementasi

1. `KelayakanSumatifSiswaService` memeriksa progres dan snapshot TP terpin melalui gerbang `MateriSiswaService` yang sudah membatasi siswa/Gen–Mapel. Status dan bukti diperiksa server, bukan parameter browser.
2. Untuk siswa yang **lulus Diagnostik** (`progres_tp.status=sumatif` pada implementasi A1–A2), syarat kegiatan Materi/Resume/HOTS tidak diwajibkan, tetapi TP belum selesai tanpa Sumatif.
3. Untuk siswa yang **tidak lulus Diagnostik** (`status=belajar`), kelayakan menuntut tepat satu Materi dalam snapshot dan tiga bukti terikat `progres_tp_id`, `versi_konten_tp_id`, dan `materi_id`: `progres_materi.media_selesai`, `resume_materi.final`, `jawaban_hots_materi.final`. Dua Materi atau bukti sebagian tidak dianggap siap.
4. Rincian TP menampilkan hasil syarat dan kemajuan tiga kegiatan, tanpa menyimpan nilai/progres melalui GET. Setelah HOTS final pada satu Materi, siswa mendapat navigasi menuju rincian TP untuk memeriksa syarat.
5. Belum ada tombol/rute Sumatif palsu. Mesin Sumatif A4 harus memvalidasi ulang seluruh prasyarat dalam transaksi sendiri saat memulai attempt; kesiapan UI A3 **bukan otorisasi untuk melewati server**.

## Batasan dan kelanjutan

- Tidak mengubah `.env`, root CI4, schema/database, data siswa, media, log ujian atau snapshot historis.
- Alur `belajar` **tidak dipaksa berubah menjadi `sumatif`** hanya karena HOTS final; pada A4 mesin Sumatif harus mengakui siswa jalur belajar yang telah memenuhi syarat. Memaksa transisi lebih dini bisa keliru mengubah media resmi menjadi sukarela pada kode saat ini.
- Ini adalah lapisan kelayakan dan UI saja, **belum** implementasi mesin ujian Sumatif, remedial, nilai TP, ekspor maupun Pra-Semester.
- Sebelum pekerjaan SQL/struktur database berikutnya, wajib terima dan verifikasi dump terbaru sesuai AGENTS.md. Dump privat tidak boleh masuk repo.

## QA

- Unit: `tests/unit/KelayakanSumatifSiswaTest.php` mencakup dua jalur, Materi bukan satu, status belum Diagnostik/TP tuntas, bukti parsial, dan keterhubungan UI/Service.
- Workflow luas `qa-edit-cp-bab-tp-v25.yml` sudah menargetkan `app/**` dan `tests/**` ketika PR dibuka; memeriksa lint seluruh PHP, PHPUnit unit dan fixture penguncian. **Lihat status Actions PR sebelum mengklaim PASS.**
- Browser XAMPP belum diuji. Uji manual berikut wajib pada siswa uji saja: sebelum dan sesudah Diagnostik, media belum selesai/selesai, Resume draf/final, HOTS draf/final, dan jalur Diagnostik lulus; pastikan tiap langkah tidak membuka Sumatif yang belum tersedia.
