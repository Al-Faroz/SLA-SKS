# Draf FAQ resmi SLA-SKS v2.5 — untuk ditinjau Admin

**Status: DRAF, BELUM DITERBITKAN.** Disusun dari aturan akademik, keamanan akun, Pengumuman/FAQ, dan pembelajaran SLA-SKS per 4 November 2026. Isi perlu ditinjau oleh pengelola madrasah melalui `/admin/faq`. Jangan otomatis memasukkan atau menerbitkan ke database; tidak ada SQL/seeder/akun uji dalam berkas ini.

**Cara pakai:** Buka menu **Informasi & Bantuan → Kelola FAQ → Tambah FAQ**. Salin pertanyaan dan jawaban pilihan dari daftar di bawah. Kolom `Urutan` memakai nomor bagian; simpan sebagai **Draf** untuk pengecekan, kemudian Admin bisa menerbitkan setelah sesuai aturan sekolah. Jangan menyalin contoh identitas siswa/kredensial.

## 01. Bagaimana cara masuk ke SLA-SKS?

**Pertanyaan:** Bagaimana cara masuk ke SLA-SKS?

**Jawaban:** Gunakan username/NISN dan sandi yang diberikan Admin madrasah pada halaman Masuk. Akun harus berstatus aktif. Bila tidak dapat masuk, gunakan kontak resmi pada menu Hubungi Admin untuk meminta bantuan.

**Urutan:** 1 · **Status awal:** Draf

## 02. Apakah sandi awal wajib diganti saat pertama masuk?

**Pertanyaan:** Apakah sandi awal wajib diganti saat pertama masuk?

**Jawaban:** Guru, Pimpinan, dan Siswa boleh langsung memakai aplikasi tanpa mengganti sandi awal. Demi keamanan, penggantian disarankan melalui Profil & Peran. Akun yang memiliki peran Admin tetap wajib mengganti sandi awal sebelum membuka layanan.

**Urutan:** 2 · **Status awal:** Draf

## 03. Apa yang terjadi ketika sandi saya direset Admin?

**Pertanyaan:** Apa yang terjadi ketika sandi saya direset Admin?

**Jawaban:** Untuk Guru, Pimpinan dan Siswa, Admin menetapkan sandi default pada proses reset. Untuk akun yang memiliki peran Admin, sistem membuat sandi sementara acak. Semua sesi akun sebelumnya dibatalkan. Penggantian sandi bagi non-Admin disarankan, sedangkan Admin wajib menggantinya.

**Urutan:** 3 · **Status awal:** Draf

## 04. Bagaimana saya mengganti sandi?

**Pertanyaan:** Bagaimana saya mengganti sandi?

**Jawaban:** Buka Profil & Peran. Guru, Pimpinan, dan Siswa dapat mengisi formulir Ganti sandi langsung pada halaman Profil dengan sandi saat ini, sandi baru, dan konfirmasi. Admin menggunakan halaman Ganti sandi khusus.

**Urutan:** 4 · **Status awal:** Draf

## 05. Mengapa saya tidak dapat membuka menu peran lain?

**Pertanyaan:** Mengapa saya tidak dapat membuka menu peran lain?

**Jawaban:** Akun staf dapat memiliki paling banyak dua peran yang ditetapkan Admin, tetapi hanya satu peran aktif pada satu sesi. Ubah peran melalui Profil & Peran. Akses dan data dari dua peran tidak digabungkan.

**Urutan:** 5 · **Status awal:** Draf

## 06. Bagaimana Siswa memulai pembelajaran?

**Pertanyaan:** Bagaimana Siswa memulai pembelajaran?

**Jawaban:** Buka Pembelajaran, pilih mata pelajaran, semester yang tersedia, lalu TP yang telah terbuka. Urutan semester dan TP diperiksa server; materi atau TP yang masih terkunci tidak dapat dilewati dengan membuka URL langsung.

**Urutan:** 6 · **Status awal:** Draf

## 07. Apa perbedaan TP Esensial dan NonEsensial?

**Pertanyaan:** Apa perbedaan TP Esensial dan NonEsensial?

**Jawaban:** TP Esensial adalah bagian perangkat pembelajaran Guru, tetapi tidak menjadi kegiatan yang dijalankan Siswa. TP NonEsensial adalah kegiatan belajar dan penilaian Siswa sesuai tahapan yang ditentukan.

**Urutan:** 7 · **Status awal:** Draf

## 08. Apa fungsi ujian Diagnostik?

**Pertanyaan:** Apa fungsi ujian Diagnostik?

**Jawaban:** Diagnostik menentukan jalur pembelajaran pada TP NonEsensial. Bila nilainya mencapai KKM, Siswa boleh melewati kewajiban Materi, Resume, dan HOTS. Bila belum mencapai KKM, Siswa mengikuti ketiga tahapan tersebut sebelum Sumatif. Sumatif tetap wajib pada kedua jalur.

**Urutan:** 8 · **Status awal:** Draf

## 09. Apakah saya tetap mengikuti Sumatif setelah lulus Diagnostik?

**Pertanyaan:** Apakah saya tetap mengikuti Sumatif setelah lulus Diagnostik?

**Jawaban:** Ya. Sumatif merupakan tahapan wajib untuk menyelesaikan TP NonEsensial meskipun nilai Diagnostik sudah mencapai KKM. Diagnostik bukan pengganti Sumatif.

**Urutan:** 9 · **Status awal:** Draf

## 10. Apa yang dilakukan jika tidak lulus Diagnostik?

**Pertanyaan:** Apa yang dilakukan jika tidak lulus Diagnostik?

**Jawaban:** Buka Materi pada TP yang sama, penuhi ketentuan durasi atau penyelesaian media yang ditampilkan, selesaikan Resume dan HOTS, kemudian lanjutkan Sumatif setelah semua gerbang terpenuhi.

**Urutan:** 10 · **Status awal:** Draf

## 11. Bagaimana penyelesaian Materi dan media dihitung?

**Pertanyaan:** Bagaimana penyelesaian Materi dan media dihitung?

**Jawaban:** Sistem memeriksa aktivitas belajar sesuai jenis media dan ketentuan pada TP. Waktu belajar dinyatakan dalam menit dengan checkpoint progres ke server. Akses kegiatan dan unduhan materi dibuka setelah persyaratan yang berlaku terpenuhi.

**Urutan:** 11 · **Status awal:** Draf

## 12. Bagaimana cara mengerjakan Resume dan HOTS?

**Pertanyaan:** Bagaimana cara mengerjakan Resume dan HOTS?

**Jawaban:** Setelah gerbang Materi terbuka, jawab tugas Resume dan HOTS pada halaman kegiatan TP. Kirim jawaban final sesuai petunjuk. Nilai akhir tugas diberikan Guru pengampu; nilai yang masih menunggu pemeriksaan bukan berarti nol.

**Urutan:** 12 · **Status awal:** Draf

## 13. Bagaimana bila ujian Diagnostik atau Sumatif terganggu?

**Pertanyaan:** Bagaimana bila ujian Diagnostik atau Sumatif terganggu?

**Jawaban:** Periksa Rincian TP. Jika sistem menyatakan perlu bantuan dan tersedia permintaan akses ulang, ajukan dari halaman TP. Guru pengampu memutuskan akses ulang sesuai aturan. Akses ulang karena gangguan bukan remedial nilai.

**Urutan:** 13 · **Status awal:** Draf

## 14. Apakah ada kesempatan remedial Sumatif?

**Pertanyaan:** Apakah ada kesempatan remedial Sumatif?

**Jawaban:** Satu kesempatan remedial Sumatif dapat diberikan bila nilai ujian awal di bawah KKM sesuai aturan TP. Nilai yang dipakai adalah nilai tertinggi dari percobaan yang sah. Penyelesaian TP dan pencapaian KKM merupakan dua status yang berbeda.

**Urutan:** 14 · **Status awal:** Draf

## 15. Di mana Siswa melihat nilai dan progres belajar?

**Pertanyaan:** Di mana Siswa melihat nilai dan progres belajar?

**Jawaban:** Buka Laporan Akademik untuk melihat nilai TP dan hasil Pra-Semester pada bagian yang terpisah. Beranda Siswa menampilkan progres TP yang sah dan tautan menuju kegiatan berikutnya yang dapat diakses.

**Urutan:** 15 · **Status awal:** Draf

## 16. Apakah Pra-Semester merupakan nilai akhir semester?

**Pertanyaan:** Apakah Pra-Semester merupakan nilai akhir semester?

**Jawaban:** Tidak. Pra-Semester adalah checkpoint penilaian tersendiri. Nilainya tidak digabung sebagai Nilai Akhir Semester dan tidak menjadi syarat penerbitan sertifikat.

**Urutan:** 16 · **Status awal:** Draf

## 17. Siapa yang menentukan jadwal Pra-Semester?

**Pertanyaan:** Siapa yang menentukan jadwal Pra-Semester?

**Jawaban:** Admin menetapkan jadwal mulai, durasi ujian, serta sesi utama, remedial dan susulan untuk paket Gen–Mapel–Semester. Guru pengampu menyiapkan bank soal dan KKM Pra-Semester.

**Urutan:** 17 · **Status awal:** Draf

## 18. Kapan sertifikat dapat diterbitkan?

**Pertanyaan:** Kapan sertifikat dapat diterbitkan?

**Jawaban:** Sertifikat mengikuti kelayakan akademik dan kelengkapan data/aset resmi yang diperiksa sistem. Progres TP NonEsensial yang sah menjadi dasar kelayakan. Hasil Pra-Semester bukan syarat dan tidak dicetak dalam sertifikat.

**Urutan:** 18 · **Status awal:** Draf

## 19. Apakah kode QR sertifikat menampilkan NISN?

**Pertanyaan:** Apakah kode QR sertifikat menampilkan NISN?

**Jawaban:** Tidak. Halaman verifikasi QR publik hanya menampilkan data verifikasi yang dibatasi. NISN berada di dokumen PDF privat yang hanya dapat diunduh pengguna berwenang.

**Urutan:** 19 · **Status awal:** Draf

## 20. Bagaimana Guru membuat Peta ATP?

**Pertanyaan:** Bagaimana Guru membuat Peta ATP?

**Jawaban:** Guru membuka Peta ATP untuk Gen–Mapel yang ditugaskan. ATP dapat disusun melalui aplikasi atau template Excel enam semester dengan pratinjau dan konfirmasi impor. Paket dan TP antar-Gen tidak otomatis saling berbagi.

**Urutan:** 20 · **Status awal:** Draf

## 21. Berapa banyak Materi untuk satu TP?

**Pertanyaan:** Berapa banyak Materi untuk satu TP?

**Jawaban:** Sesuai aturan SLA-SKS v2.5, setiap TP memiliki satu Materi. Guru melengkapi media dan kegiatan sesuai jenis TP serta memeriksa kesiapan sebelum menetapkan status Siap.

**Urutan:** 21 · **Status awal:** Draf

## 22. Kapan Guru dapat menyunting TP yang sudah Siap?

**Pertanyaan:** Kapan Guru dapat menyunting TP yang sudah Siap?

**Jawaban:** TP Draf dan TP Siap yang belum pernah dipakai Siswa dapat disunting sesuai hak Guru. Jika TP telah digunakan Siswa, konten TP terkait terkunci demi melindungi histori pembelajaran.

**Urutan:** 22 · **Status awal:** Draf

## 23. Apa ketentuan bank soal Diagnostik dan Sumatif?

**Pertanyaan:** Apa ketentuan bank soal Diagnostik dan Sumatif?

**Jawaban:** Bank Diagnostik dan Sumatif dipisah. Kesiapan TP NonEsensial memerlukan minimal 10 soal Diagnostik sah dan 30 soal Sumatif sah, disertai konfigurasi kegiatan dan KKM yang sesuai.

**Urutan:** 23 · **Status awal:** Draf

## 24. Siapa yang mengatur KKM?

**Pertanyaan:** Siapa yang mengatur KKM?

**Jawaban:** Admin menetapkan nilai KKM default. Guru dapat menetapkan KKM khusus pada TP atau Pra-Semester sesuai hak dan kondisi sebelum pengaturan dikunci. Sistem menggunakan nilai KKM yang sah pada masing-masing konteks.

**Urutan:** 24 · **Status awal:** Draf

## 25. Bagaimana Pengumuman digunakan?

**Pertanyaan:** Bagaimana Pengumuman digunakan?

**Jawaban:** Admin dan Guru dapat menerbitkan Pengumuman global. Semua akun aktif login membaca Pengumuman berstatus Terbit; draf dan arsip tidak ditampilkan. Guru hanya boleh mengelola Pengumuman yang dibuatnya sendiri, sedangkan Admin mengelola seluruhnya.

**Urutan:** 25 · **Status awal:** Draf

## 26. Siapa yang boleh menambah atau mengubah FAQ resmi?

**Pertanyaan:** Siapa yang boleh menambah atau mengubah FAQ resmi?

**Jawaban:** Hanya Admin yang dapat membuat, menyunting, menerbitkan, dan mengarsipkan FAQ resmi. Semua akun aktif dapat membacanya melalui menu Bantuan setelah status Terbit.

**Urutan:** 26 · **Status awal:** Draf

## 27. Di mana saya menghubungi pengelola madrasah?

**Pertanyaan:** Di mana saya menghubungi pengelola madrasah?

**Jawaban:** Buka Hubungi Admin pada grup Informasi & Bantuan. Halaman ini menampilkan alamat, telepon, email, dan situs resmi yang dicatat Admin pada Identitas Madrasah. SLA-SKS tidak menyediakan tiket atau pesan otomatis.

**Urutan:** 27 · **Status awal:** Draf

## 28. Apakah Pimpinan dapat mengubah nilai atau jadwal?

**Pertanyaan:** Apakah Pimpinan dapat mengubah nilai atau jadwal?

**Jawaban:** Tidak. Peran Pimpinan hanya membaca dashboard, monitoring, dan laporan yang diizinkan. Penilaian dilaksanakan Guru dan jadwal serta pengaturan administratif dikelola Admin.

**Urutan:** 28 · **Status awal:** Draf

---
Catatan: teks ini adalah draf pertanyaan/jawaban penggunaan aplikasi, bukan pernyataan kebijakan privasi atau ketentuan hukum sekolah yang sudah disahkan. Kebijakan Privasi publik tetap memerlukan teks versi resmi terpisah.
