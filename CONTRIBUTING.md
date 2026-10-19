# Kontribusi dan Perubahan — SLA-SKS

## Pola kerja
1. Pilih fitur dan tulis rujukan dokumen (`05`, `07`, `08`, dst.) + daftar tes E2E yang harus lulus.
2. Kerjakan perubahan **berkas SQL**, service, controller, view, CSS, dan tes yang relevan dalam PR yang dapat ditinjau.
3. Lakukan kode review terhadap hak role, state server, audit dan kondisi error.
4. Uji pada **MySQL lokal dan MariaDB hosting** bila skema/SQL berubah.
5. Pastikan `.env`, dump data produksi, kunci API/sesi, PDF siswa asli, TTD PNG asli dan file privat tidak masuk Git.
6. PR boleh memperbaiki typo/spesifikasi teknis kecil; perubahan kebijakan besar harus mendapat keputusan pemilik yang jelas dan memperbarui dokumen utama, bukan menambahkan addendum di sampingnya.

## Format ringkasan PR
- Tujuan fitur dan dokumen acuan.
- UI: layar, state dan galeri komponen yang berubah.
- Backend/API: aturan bisnis/hak dan service yang berubah.
- SQL: file install/patch, kompatibilitas, kebutuhan backup dan rollback.
- Pengujian: ID E2E yang lulus, perangkat dan server uji.
- Bukti: screenshot UI bila relevan; jangan unggah data siswa nyata.

## Larangan perubahan diam-diam
Tidak boleh membuka role baru, mengaktifkan autosave, mewajibkan dua Admin, menambahkan status `dicabut`, menyunting soal/hasil historis langsung, mengubah background sertifikat historis, memindah front controller ke folder `public/`, atau memperkenalkan migrations/seeders tanpa keputusan pemilik baru.
