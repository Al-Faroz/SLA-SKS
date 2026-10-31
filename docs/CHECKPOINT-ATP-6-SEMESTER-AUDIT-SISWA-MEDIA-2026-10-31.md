# Checkpoint — ATP 6 Semester, Impor, Materi, dan Akses Siswa (31 Oktober 2026)

Status: **branch fitur untuk uji XAMPP, belum merge ke `main`**.
Branch: `feature/v25-atp-enam-semester-audit-belajar-20261031`.
Basis: `feature/v25-atp-dropdown-bab-urutan-otomatis-20261031`.

## Perubahan

1. **Paket draf Guru 6 Semester**. Pada `/guru/peta-atp` tersedia satu formulir Gen–Mapel/Kurikulum dan Tahun Ajaran masing-masing Semester 1–6. Pembuatan paket terjadi sekaligus dalam satu transaksi, hanya untuk semester yang belum punya paket. Paket draf/aktif tidak diubah; data duplikat, arsip/kurikulum konflik ditolak. Modal Paket Draf per semester dihapus. Semester sebelum `semester_mulai` Gen dapat disiapkan Guru, **tidak otomatis dapat dibuka siswa**. Pengaturan BAB berurutan dalam pasangan 1–2/3–4/5–6 tetap; Tahun Ajaran semester berpasangan boleh berbeda.
2. **Impor Excel ATP**. Service yang ada tetap membaca enam semester dari file V25-3, membandingkan terhadap BAB/TP yang sudah ada, dan hanya menerima penambahan di semester dengan paket sah dan BAB/TP kosong. Semester identik = Tetap; perubahan terhadap BAB/TP manual = Galat. Pratinjau staging dan konfirmasi memakai fingerprint lalu revalidasi di dalam transaksi. Pembuatan enam paket sebelumnya menghilangkan penghalang bahwa guru harus membuka halaman semester satu per satu untuk membuat paket.
3. **Materi**. Penambahan materi kedua dicegah di Service untuk YouTube maupun PDF/Gambar/Audio; editor menampilkan Sunting setelah satu Materi ada. Checklist awal dan TP Siap sama-sama mensyaratkan tepat satu Materi untuk Esensial dan NonEsensial. **Tidak mengubah** validator PDF 15 MB/40 halaman dan media privat yang sudah diuji sebelumnya.
4. **Siswa**. Semester di bawah awal Gen tetap Dilewati. Paket hanya berasal dari penugasan `penugasan_paket` Gen siswa dan status aktif; URL paket dan TP divalidasi lagi di Service. Semester setelah awal Gen hanya terbuka jika semua TP NonEsensial semester wajib sebelumnya berprogres `tuntas` **milik siswa itu sendiri DAN memiliki percobaan Sumatif `selesai_sah`**. TP Esensial tidak membuka aktivitas dan tidak menjadi syarat progres. TP berikutnya memerlukan TP NonEsensial sebelumnya tuntas **dan bukti Sumatif sah**. Diagnostik lulus memindahkan progres ke `sumatif` (bukan `tuntas`), dengan hak belajar materi sukarela tetap tersedia. Status `tuntas` historis setelah Diagnostik tidak cukup membuka jalur. TP draf/tanpa snapshot tetap tidak bisa dibuka.

## Pemeriksaan ulang sebelum pull (31 Oktober 2026)

- Ditemukan bug kritis: Diagnostik lulus sebelumnya memberi status `tuntas`, padahal Sumatif wajib. Diperbaiki menjadi `sumatif`; pembaca media pasca-Diagnostik tetap dapat dibuka sukarela.
- Semua gerbang buka TP berikutnya dan semester berikutnya sekarang memerlukan bukti `percobaan_ujian.jenis=sumatif` dan `status=selesai_sah` pada progres TP siswa yang sama. Gerbang gagal tertutup bila Sumatif belum ada.
- Master Excel di repository SHA blob `fc0901acdfc7e88721658dd786862a3c4400c627`: diverifikasi 17 entri ZIP, seluruh CRC cocok, tanda V25-3 tersedia, enam Defined Name BABS, 480 rumus COUNTIFS, 18 dropdown dengan `showDropDown=0`, dan enam sheet terproteksi.
- Halaman Kelola Excel diarahkan ke Peta ATP (bukan membuat draf per semester). Label Dilewati Gen dihapus dari status persiapan Guru; batas Gen untuk siswa tidak dihapus.
- Lint PHP dan PHPUnit **belum dapat dijalankan pada salinan repository yang utuh**. PHP CLI di mesin pemeriksaan terpisah tidak mempunyai dependensi/extensi proyek dan tidak tersedia clone repository melalui jaringan; GitHub Actions pada branch mengembalikan `startup_failure` atau run gagal sebelum memulai job. **Tidak ada tes otomatis yang boleh diklaim PASS**.
- Modul **Sumatif server-side yang lengkap belum terlihat pada route/service yang diperiksa**; dengan demikian, alur perpindahan TP/semester sampai akhir belum dapat dinyatakan FIX. Ini secara sadar fail-closed, bukan layar selesai palsu. Pengujian end-to-end setelah modul Sumatif tersedia wajib dilakukan.

## Pengujian dan keterbatasan

- Tes unit/integrasi di repository diperluas untuk validasi enam Tahun Ajaran, UI/CSRF route, Gen lintas semester, progres TP/semester, larangan aktivitas Esensial, larangan Materi kedua dan pemeriksaan PDF.
- **Belum dilakukan** eksekusi PHPUnit/CI dan pengujian browser/XAMPP pada branch terbaru. Tidak tersedia database MySQL QA terbaru atau dump privat proyek di lingkungan AI. Jangan menyebut seluruh empat modul FIX sampai bukti PASS dilaporkan.
- Tes integrasi MySQL memakai database `sla_sks_qa` khusus; **jangan** diarahkan ke `sla_sks_v4` yang berisi data nyata, jangan membuat Migration/Seeder.
- Tidak ada perubahan SQL, .env, path index.php/.htaccess, atau file siswa nyata. Tidak ada pembaruan pada `main`.
- Pengujian manual prioritas: buat draf enam semester untuk satu Gen–Mapel dengan semester mulai 4; unduh/isi/impor V25-3, pratinjau & konfirmasi sekali, unggah materi masing-masing YouTube/Audio/Gambar/PDF dan uji Sunting; login siswa Gen sama pastikan S1–3 tidak bisa diakses, S4 mengikuti TP Siap/urutan, S5 baru terbuka setelah semua TP wajib S4 tuntas. Uji URL langsung milik Gen lain.

## Risiko yang harus diuji di XAMPP

- Transaksi penyimpanan enam paket dengan data semester eksisting atau arsip/kurikulum berbeda.
- Pengisian Tahun Ajaran berbeda per semester, dan pembentukan Kode TP dan BAB lintas kelas/semester.
- File Excel yang sudah diisi Guru dan data CP aktif pada database lokal harus diuji tanpa menyimpan ulang/menimpa data akademik.
- Preview/upload media dan snapshot siswa bergantung data dan modul terkait di aplikasi. Audit kode tidak menggantikan pengujian media nyata.

**DILARANG** menganggap data historis boleh dihapus untuk membuat seluruh semester kosong. Tidak ada impor ke database oleh AI. Tunggu konfirmasi hasil XAMPP sebelum merge.
