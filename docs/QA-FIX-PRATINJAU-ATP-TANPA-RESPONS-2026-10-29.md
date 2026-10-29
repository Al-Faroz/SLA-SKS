# QA G05 — Klik Periksa & Pratinjau Excel tampak tidak merespons

**Laporan XAMPP pengguna 29 Oktober 2026:** tombol tidak menghasilkan tampilan pratinjau/pesan yang terlihat. Belum tersedia log jaringan/browser lokal untuk menetapkan satu penyebab pasti.

## Temuan nyata dari kode
1. Filter CSRF CI4 dapat mengirim flashdata **`error`** ketika request POST ditolak. Tampilan G05 sebelumnya hanya membaca **`galat`**, sehingga pada konfigurasi redirect terjadi **pesan hilang**.
2. Pesan galat ditaruh di atas halaman, jauh dari form upload di bagian bawah. Jika browser mempertahankan posisi scroll, pesan dapat tidak terlihat.
3. JavaScript sebelumnya langsung `disabled=true` pada submitter saat event submit. Dihilangkan untuk menghindari efek samping pengiriman form browser; pencegahan submit ganda dipertahankan melalui guard.
4. Asset JavaScript di G05 tidak menggunakan versi cache. Sekarang URL diberi penanda filemtime agar sesudah pull browser memakai logika terbaru.
5. Halaman yang dipulihkan dari bfcache bisa membawa CSRF lama. Pemulihan kini merefresh form GET sebelum POST berikutnya, **tanpa mematikan CSRF**.

## Perubahan
- G05 menerima flashdata `galat` **atau** `error` dan menampilkan dekat form dengan role="alert", auto-focus saat ada pesan.
- Controller pratinjau/konfirmasi redirect ke `#atp-upload-area`, tidak menyembunyikan pesan. Controller pratinjau mencatat penanda POST aman tanpa nama berkas maupun isi Excel, serta tahapan validasi yang gagal.
- Tidak ada perubahan SQL, konfigurasi keamanan, .env, autentikasi, atau hak Guru.
- Tes regresi statis memverifikasi semua jalur feedback dan submitter tidak disabled sebelum submit.

## Langkah uji XAMPP
1. `git fetch` dan checkout branch perbaikan. Refresh halaman **Ctrl+F5**.
2. Login Guru dan buka `/index.php/guru/peta-atp/impor`; pilih file V25-2 lalu klik **Periksa & Pratinjau Excel**.
3. Berhasil → halaman `Pratinjau ATP Excel`, menampilkan ringkasan enam semester dan baris hasil. Berkas kosong → tampil pesan `File belum berisi BAB atau TP`.
4. Gagal → pesan terlihat **di samping form**, bukan diam. Jika kegagalan CSRF, login/refresh halaman lalu pilih file dan kirim lagi; jangan nonaktifkan CSRF.
5. Jika masih tidak ada perpindahan/pesan: periksa DevTools → Network → klik tombol dan catat apakah ada `POST /guru/peta-atp/pratinjau/{id}`, status HTTP, serta lokasi redirect. Cocokkan `writable/logs/log-YYYY-MM-DD.php` dengan pesan `POST pratinjau ATP diterima`. Jika POST tidak muncul, masalah sebelum Controller (browser/JS/validasi). Jika POST muncul tetapi ada galat akademik, log menyebut tahapnya tanpa data pribadi.

**Batas validasi:** PHPUnit/CI menguji kode; browser lokal XAMPP tetap harus diuji oleh pemilik karena gejala awalnya tidak direproduksi pada server CI.
