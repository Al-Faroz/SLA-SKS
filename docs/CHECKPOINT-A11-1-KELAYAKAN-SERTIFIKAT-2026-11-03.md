# Checkpoint A11-1 — Pemeriksaan Kelayakan Sertifikat

Tanggal: 3 November 2026  
Basis: `feature/a10-1-laporan-siswa-pimpinan-20261103`.

## Tujuan dan batas implementasi

Tahap A11-1 menyediakan **pemeriksaan kelayakan akademik siswa** oleh Admin sebelum penerbitan sertifikat; bukan penerbitan sertifikat. Syarat penerbitan resmi tetap sesuai Master 09: seluruh TP NonEsensial selesai, seluruh nilai TP wajib Final, identitas serta tanda tangan PNG siap. Nilai dan penyelesaian **Pra-Semester tidak menjadi syarat ataupun isi sertifikat**.

### Fungsi yang benar-benar diimplementasikan

- Menu `Admin → Pemeriksaan Sertifikat` dengan GET `/admin/sertifikat`, filter Gen–Mapel–Semester paket aktif yang memiliki `penugasan_paket`, serta pagination seluruh siswa Gen.
- `KelayakanSertifikatService` baca-saja: setiap TP NonEsensial harus `progres_tp.status=tuntas`, **mempunyai Sumatif `selesai_sah`**, nilai TP tersimpan `status_nilai=final`, komponen Sumatif/Resume/HOTS lengkap sesuai jalurnya. Nilai 0 sah, nilai di bawah KKM bukan alasan menolak ketuntasan yang sudah sah.
- Semester sebelum `siswa.semester_mulai` (fallback Gen) ditandai Dilewati dan tidak layak otomatis; semester tanpa TP wajib juga bukan 100%.
- `a-tbl` + status Layak akademik/Belum layak, rincian alasan per siswa. Tidak ada NISN pada tabel.
- Identitas madrasah diperiksa melalui `pengaturan`; status tanda tangan dan template ditandai **belum dikonfigurasi**, tidak disamakan dengan kelayakan akademik.
- Tidak ada tombol penerbitan/PDF/QR semu. Tidak ada POST maupun perubahan nilai/percobaan/siswa.

## SQL dan keamanan

- **SQL baru: tidak ada**. Audit hanya membaca DDL dump privat `sla_sks_v4 (15).sql` bertanggal 3 November 2026 pukul 09.32 WIB, MariaDB 10.4.32. Belum ditemukan tabel `sertifikat` atau penyimpanan TTD PNG yang sesuai Master 09. Karena itu penambahan tabel versi/snapshot/arsip dan unggah TTD dilakukan bersama proses penerbitan A11-2, bukan pada A11-1.
- Struktur yang diakses read-only: `paket_semester`, `penugasan_paket`, `ref_angkatan`, `ref_mapel`, `siswa`, `pengguna`, `bab`, `tp`, `progres_tp`, `nilai_tp`, `percobaan_ujian`, `pengaturan`.
- Pemeriksaan dua lapis: filter route `auth:admin` dan Service memerlukan Admin aktif tanpa kewajiban ganti sandi. Paket draf, penugasan palsu dan ID tak sah ditolak. Tidak melakukan query `ujian_pra_semester`/hasil Pra-Semester.
- Tidak ada dump siswa yang diunggah ke GitHub. Tidak ada perubahan `.env`, konfigurasi DB, root CI4 atau folder privat.

## Berkas

- Baru: `app/Services/KelayakanSertifikatService.php`, `app/Controllers/AdminSertifikat.php`, `app/Views/admin/sertifikat_kelayakan.php`.
- Diubah: `app/Config/Routes.php`, `app/Services/NavigasiService.php`, `public/assets/css/app.css`, `public/assets/css/emerald-glass.css`.
- Baru: `tests/unit/KelayakanSertifikatDatabaseTest.php`, `.github/workflows/qa-a11-sertifikat-kelayakan.yml`.
- Master 09, 10, 12, 13, 16 diselaraskan.

## Penerimaan dan tindak lanjut

Tes sintetis: paket aktif/ditugaskan, role Admin, nilai 0 Final, gerbang Sumatif sah, tugas belum Final, semester dilewati, paginasi, identitas tanpa TTD, jalur belajar wajib lengkap. Verifikasi browser XAMPP diperlukan setelah branch dipull: filter paket, sejumlah siswa simulasi dengan variasi status, tabel desktop/HP dan pesan administratif.

**A11-2 (belum dikerjakan):** database SQL patch additive untuk template, TTD PNG privat, snapshot sertifikat, arsip versi/nomor/QR; upload dan pratinjau template A4 landscape; transaksi penerbitan idempoten; Dompdf/QR berizin, PDF privat dan halaman verifikasi publik terbatas. Jangan menyatakan penerbitan/QR sudah PASS pada tahap ini.
