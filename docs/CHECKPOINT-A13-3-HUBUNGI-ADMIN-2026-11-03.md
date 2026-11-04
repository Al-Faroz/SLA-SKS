# Checkpoint A13-3 — Hubungi Admin dari Kontak Resmi Madrasah

Tanggal: 3 November 2026. Branch `feature/a13-3-hubungi-admin-kontak-resmi-20261103`, di atas `feature/a13-2-pengumuman-faq-20261103` (draft PR #123); belum digabung ke `main`.

## Dasar
Master 01, 03, 11, 12, 13, 14, 16 v2.5. Data diambil dari tabel `pengaturan` yang sudah tersedia pada dump privat XAMPP `sla_sks_v4 (17).sql`. Hanya kunci `nama_madrasah`, `alamat_madrasah`, `telepon`, `email` dan `website`. Tidak ada tabel/SQL baru, migrasi, seeder, atau pengubahan berkas `.env`/root.

## Pekerjaan
- `GET /hubungi-admin` dilindungi `auth`, dengan pemeriksaan ulang akun dan role aktif di Service.
- Menu **Hubungi Admin** pada Admin, Guru, Siswa, Pimpinan serta tautan dari Pusat Bantuan.
- View Emerald Glass responsif memakai identitas/kontak resmi Madrasah; halaman kosong ketika kontak tidak diisi. Tidak menampilkan kontak pribadi Pengelola/Guru dan tidak membuat tiket, chat atau pesan.
- Tautan `tel:`, `mailto:` dan HTTP(S) dibuat hanya untuk nilai tervalidasi. Semua teks dan atribut di-escape, no-store.
- Tes SQLite sintetis untuk izin empat role, konten whitelist, link aman, keadaan kosong, navigasi, dan no-mutation. Workflow GitHub Actions PHP lint + PHPUnit + regresi Bantuan.

## Verifikasi yang diperlukan
1. CI `QA A13-3 Hubungi Admin` harus PASS, kemudian audit branch/PR.
2. Pemilik pull branch A13-3 yang bertumpuk A13-2. Jika A13-2 belum terpasang, **backup database dan writable/**, lalu impor `V4-0012_PENGUMUMAN_FAQ.sql` satu kali setelah memastikan belum terpasang. A13-3 sendiri **tidak memerlukan SQL**.
3. Uji login empat peran, menu **Hubungi Admin**, tampilan nama/alamat/telepon/email/website yang diatur Admin, link tel/mailto/website, keadaan kontak kosong, akses belum login, Dark/Light, keyboard dan layar HP.
4. Pastikan pengguna tidak memperoleh kontak pribadi staf; informasi publik Kebijakan Privasi masih menampilkan status belum disahkan, bukan kebijakan rekaan.

## Pekerjaan tertunda
A13-2 tetap menunggu uji XAMPP. Kebijakan Privasi berversi harus memakai teks resmi sekolah, tidak boleh diciptakan developer. Notifikasi internal dan badge belum dibaca memerlukan SQL baru; **dump XAMPP terbaru setelah patch V4-0012 berhasil diimpor** adalah gerbang wajib sebelum merancang patch tersebut. Jangan menganggap dump (17) sudah memuat V4-0012. 
