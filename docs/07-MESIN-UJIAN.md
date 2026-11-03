# 07 — Mesin Ujian Diagnostik, Sumatif TP, dan Pra-Semester

> **Keputusan final 2 November 2026 — pembetulan terminologi dan alur:** Diagnostik dan Sumatif TP adalah tes **mandiri**, dengan **permintaan akses ulang kepada Guru** apabila percobaan tidak dapat diselesaikan. **Pembatalan ujian hanya untuk Pra-Semester**. Keputusan ini menggantikan semua pernyataan terdahulu yang menyamakan tes mandiri dengan ujian dibatalkan. Pertahankan ketentuan akademik v2.5: satu Sumatif awal sah, satu remedial bila nilainya di bawah KKM, nilai tertinggi, TP tetap Selesai walaupun belum mencapai KKM.

## 1. Tiga tujuan dan pola pelaksanaan berbeda
- **Diagnostik:** dilakukan mandiri saat awal TP NonEsensial, minimal 10 PG tunggal lima opsi A–E; skor dibandingkan dengan **satu KKM efektif TP** untuk memilih jalur. Lulus boleh melewati Materi/Resume/HOTS; tidak lulus harus menuntaskannya. Keduanya tetap wajib Sumatif. Skor Diagnostik **tidak masuk** nilai akhir TP; **tidak ada remedial Diagnostik**.
- **Sumatif TP:** dilakukan mandiri setelah prasyarat jalur siswa terpenuhi, minimal 30 PG A–E. Ada **satu tes awal sah**. Jika skor tes awal sah di bawah KKM, tersedia **satu remedial nilai**; ambil skor tertinggi. TP selesai setelah kewajiban dan kesempatan sah terpenuhi meskipun nilai belum mencapai KKM.
- **Pra-Semester:** ujian tersendiri di tingkat paket semester, minimal 30 soal gabungan semua BAB, hanya setelah seluruh TP NonEsensial selesai dan **hanya dalam jendela boleh mulai per Gen–Mapel–Semester yang ditetapkan Admin**. Pola penilaian Sumatif (satu remedial bila di bawah KKM), nilai mandiri tanpa nilai akhir semester.

## 2. Perangkat dan pengamanan saat pengerjaan
Pertahankan satu profil keamanan pengerjaan **Ketat** (fullscreen, waktu dan deteksi koneksi), tanpa menyamakan **konsekuensi kegagalan**. Sumatif TP wajib desktop/laptop dan boleh dikerjakan dari rumah. Diagnostik boleh dari HP/tablet **hanya jika** fullscreen benar-benar didukung; bila tidak, tolak sebelum membuat percobaan dan berikan pesan penggunaan desktop/laptop. Pra-Semester mengikuti persyaratan fullscreen dan perangkat Sumatif.

| Parameter | Nilai | Perlakuan selama ujian |
|---|---:|---|
| Jeda fullscreen per kejadian | 90 detik | Penghentian pengerjaan saat jeda |
| Jeda fullscreen total | 180 detik | Akumulasi tanpa menghitung waktu tumpang tindih ganda |
| Jumlah insiden fullscreen | 3 | Insiden ketiga menghentikan percobaan |
| Timeout heartbeat koneksi | 20 detik | Jeda dihitung sejak last_seen + 20 detik |
| Total jeda koneksi | 300 detik | Melampaui batas menghentikan percobaan |

`fullscreenchange`, `visibilitychange`, dan `blur` yang berasal dari satu perpindahan fokus dihitung **satu insiden**. Ketika jeda, server menghentikan waktu aktif dan menolak penyimpanan jawaban/kirim sampai pulih. Memulihkan fullscreen memerlukan gesture klik dan validasi server. Waktu, sisa durasi, peristiwa, dan jawaban yang sudah sah disimpan tetap bersumber dari server.

Jika layanan shared hosting tidak memiliki worker terus-menerus, rekonsiliasi dilakukan pada setiap status, heartbeat, jawab, kirim, buka layar, dan pemulihan koneksi; cron hanya tambahan. Tidak mengklaim peristiwa pasti terdeteksi persis pada detik ke-20 tanpa request.

## 3. **Diagnostik dan Sumatif: permintaan akses ulang, bukan pembatalan**
1. Siswa yang menyelesaikan percobaan secara sah memperoleh hasil sesuai jenis tes.
2. Jika Diagnostik atau Sumatif **tidak dapat diselesaikan** akibat batas insiden/jeda keamanan, percobaan **berakhir tanpa skor sah**, bukti jawaban/insiden tetap tersimpan, dan status kebutuhan siswa adalah **memerlukan akses ulang Guru**, **bukan ujian dibatalkan**.
3. Siswa **meminta akses ulang** kepada Guru pengampu saat ini pada Gen–Mapel-nya. Jenis tes (**Diagnostik** atau **Sumatif**, termasuk tahap **normal/remedial**) **ditentukan server dari percobaan terakhir TP yang tidak selesai**, bukan berdasarkan tombol atau halaman ujian yang terbuka. Permintaan/izin mencatat identitas percobaan, tahap, **alasan teknis dan audit otomatis**, waktu, dan pelaku. Guru **cukup menekan satu tombol Izinkan Akses Ulang, tanpa mengisi alasan atau memilih jenis tes**; sistem hanya mengizinkan tahap yang terhenti.
4. Sebelum ada izin Guru, sistem **melarang** percobaan pengganti. Izin Guru **sekali pakai**, digunakan atomik saat attempt pengganti dibuat. Tidak memberikan skor otomatis, tidak mereset progres/materi/Resume/HOTS, dan tidak mengubah kuota tes/remedial yang sah.
5. **Diagnostik tidak selesai** → setelah izin Guru, mengerjakan Diagnostik ulang untuk memperoleh keputusan jalur dari skor sah. Bukan keputusan otomatis `langsung_belajar`.
6. **Sumatif normal tidak selesai** → setelah izin Guru, mengerjakan **Sumatif normal pengganti**, **bukan remedial**. **Remedial tidak selesai** → setelah izin Guru, mengerjakan **remedial pengganti**, bukan remedial tambahan. Dua kasus ini tidak mengulang pembelajaran/Resume/HOTS yang sudah sah.
7. **Remedial nilai** hanya timbul sesudah **Sumatif normal selesai sah dan skornya di bawah KKM**. Batasnya **satu remedial sah**; skor tertinggi antara Sumatif awal sah dan remedial sah digunakan untuk nilai TP. Jika keduanya masih di bawah KKM, TP tetap Selesai.
8. Kesempatan tes yang belum selesai **tidak boleh** dicatat sebagai tes sah ataupun sebagai remedial terpakai. Permintaan ulang tidak boleh memberi jalur untuk melewati ujian sah.

**Terminologi antarmuka:** `Tes tidak selesai — ajukan/minta akses ulang kepada Guru`, `Menunggu izin akses ulang`, `Izinkan akses ulang Diagnostik`, `Izinkan akses ulang Sumatif (normal/remedial)`. Jangan memakai label `Ujian Dibatalkan` pada Diagnostik/Sumatif.

## 4. **Pra-Semester: pembatalan ujian**
Hanya Pra-Semester yang memakai nomenklatur pembatalan (kode status database saat ini **`dibatalkan_menunggu_guru`**, merupakan label historis). Percobaan batal tidak menghasilkan skor sah; bukti insiden dan jawaban sebelumnya diaudit. **Admin**, bukan Guru, membuka jadwal susulan dengan peserta terpilih yang ujian Pra-Semester-nya dibatalkan atau yang tidak mengikuti jadwal utama; pembatalan susulan dapat dijadwalkan kembali. Guru G1 hanya memonitor histori pembatalan pada Gen–Mapel sendiri, tanpa memberi izin ulang yang dapat melewati jadwal Admin. Remedial memiliki jadwal Admin tersendiri dan susulan remedial tidak menambah kesempatan remedial sah.

## 5. Status dan kompatibilitas database
**Kontrak semantik baru:** status selesai sah adalah `selesai_sah`; tes mandiri yang terhenti dan memerlukan izin Guru menggunakan istilah domain **`akses_ulang_diperlukan`**, sedangkan Pra-Semester dapat menggunakan `dibatalkan_menunggu_guru`. Status `jeda_fullscreen`, `jeda_koneksi`, `berjalan`, dan `baru` tetap digunakan saat pengerjaan.

**Catatan implementasi bertahap:** dump database lokal sebelum patch terakhir masih memiliki enum `dibatalkan_menunggu_guru` sebagai satu-satunya kode kegagalan. Sebelum perubahan enum/schema dan migrasi data **wajib audit dump terbaru sesudah patch terakhir**, versi SQL additive dan backup. Kode kompatibilitas yang sementara membaca kode status historis **tidak boleh** menampilkan istilah pembatalan untuk Diagnostik/Sumatif dan **tidak boleh menganggapnya aturan akademik**. Jangan me-reset histori.

## 6. Hak akses, bank soal dan UI
- Hanya Guru G1 aktif Gen–Mapel siswa dapat melihat permintaan akses ulang dan memberi izin; validasi di Controller **dan** Service, termasuk saat izin digunakan. Guru pengganti yang sah mewarisi hak operasional, tetapi pelaku lama tetap tersimpan dalam audit.
- Soal PG satu kunci A–E, urutan soal/opsi acak server, snapshot soal dan kunci privat, CSRF, anti-replay, throttle, serta pemeriksaan status sebelum menjawab/kirim. Word memakai struktur `SLA-SKS-WORD-V1`; dukungan teks/Arab/Equation/gambar/tabel/media eksternal mengikuti Master 05.
- Layar S05 menampilkan petunjuk/kapabilitas fullscreen; S06 tanpa sidebar, timer, navigator, indikator tersimpan dan jeda; S07 hanya memberi skor pada `selesai_sah`. Bila tes mandiri terhenti, tampil **permintaan akses ulang**, tanpa skor.
- Konten ujian TP Siap boleh disunting Guru G1 sampai pertama kali **digunakan siswa**; setelah itu soal/kunci, KKM, durasi, dan snapshot terkunci sesuai Master 02/05/08. Penyuntingan CP/BAB tidak mereset histori ujian.
- Fullscreen adalah mitigasi tingkat browser, bukan jaminan anticurang.


## 7. Keputusan final Pra-Semester — 3 November 2026
- **Jadwal berbeda per Gen–Mapel–Semester.** Admin menentukan jendela **boleh mulai ujian** (tanggal/jam mulai dan akhir dalam WIB) serta **durasi pengerjaan dalam menit** per mata pelajaran. Jam server menjadi otoritas. Jadwal satu mapel tidak otomatis berlaku bagi mapel lain.
- **Durasi adalah waktu pengerjaan individual.** Timer peserta dihitung sejak ia benar-benar memulai percobaan, bukan sejak jadwal Admin dibuka. Contoh jendela boleh mulai 08.00–09.00 dengan durasi 60 menit: siswa mulai 08.45 berhak mengerjakan hingga 09.45 (ditambah mekanisme jeda sah), meskipun jendela mulai berakhir 09.00. Siswa yang baru meminta mulai pada/selepas 09.00 ditolak. Status selesai, skor, jawaban dan timer ditentukan server.
- **Satu paket ujian** adalah Gen–Mapel–Semester. Guru G1 menyiapkan bank PG A–E tersendiri (minimal 30 sah dan semua BAB terwakili) serta KKM Pra-Semester (default Admin atau override khusus Guru; **bukan KKM sembarang TP**). Admin mengatur jadwal serta durasi. Setiap kegiatan utama/remedial/susulan mempunyai jendela boleh mulai sesuai pengaturan Admin.
- **Remedial nilai** hanya bagi skor awal **selesai sah** di bawah KKM: maksimal satu remedial sah, pada **jadwal remedial tersendiri yang dibuka Admin**, dan skor terbaik dipakai. Remedial bukan pengganti ujian yang dibatalkan.
- **Pembatalan hanya Pra-Semester:** jika batas keamanan dilewati, percobaan tercatat batal tanpa skor sah, tidak mengurangi jatah normal/remedial sah. Sistem menentukan siswa memenuhi kriteria **susulan**, Admin membuka jadwal susulan dan memasukkan peserta yang dibatalkan; Guru melihat audit tanpa persetujuan tambahan. Siswa yang tidak memulai pada jadwal utama dapat dipilih Admin untuk susulan. Jadwal susulan hanya berlaku pada peserta berhak; tidak otomatis membuka seluruh Gen. Bila susulan kembali batal, Admin boleh membuat jadwal susulan lain dengan histori utuh. Jika yang dibatalkan adalah kesempatan remedial, susulan menggantikan **remedial yang sama**, bukan memberi tambahan remedial.
- **Tidak ada reset** nilai/percobaan/jawaban/snapshot; setiap jadwal susulan, peserta, pembatalan, dan keputusan Admin diaudit. Pra-Semester menghasilkan satu **nilai mandiri**, tidak menghasilkan Nilai Akhir Semester. Persyaratan sertifikat **tidak menjadi bagian pekerjaan Pra-Semester ini**.
- **Implementasi bertahap:** tabel v4 yang ada saat ini menyimpan satu jendela utama dan durasi per paket pada `ujian_pra_semester`. Jadwal remedial/susulan beserta peserta spesifik perlu **SQL update incremental setelah pemeriksaan dump XAMPP terbaru pasca-impor**. Sampai skema baru terpasang, jangan menampilkan tombol remedial/susulan yang pura-pura berfungsi atau membuka ujian di luar jendela.


### A8-4 — Kontrak penentu kesempatan ulang dan nilai (3 November 2026)
Sebelum mesin membuat percobaan baru, Service menghitung histori **per satu siswa + satu bank Pra-Semester**, urut id/awal attempt, dan menolak data yang melanggar aturan:
1. **Belum ada percobaan** → tahap normal pada jadwal utama; jika tidak hadir pada jadwal utama, **hanya** susulan normal bagi peserta yang secara eksplisit ditunjuk Admin.
2. **Normal dibatalkan** → tidak punya nilai, tidak mengurangi satu kesempatan normal sah; hanya susulan **normal** untuk peserta yang ditunjuk Admin. Bila susulan kembali batal, jadwal susulan baru boleh dibuat Admin dengan audit; bukan percobaan mandiri.
3. **Normal selesai sah dengan skor ≥KKM** → selesai; tidak punya hak remedial atau susulan.
4. **Normal selesai sah dengan skor <KKM** → satu **remedial sah** pada jendela remedial Admin, bukan jadwal utama atau susulan normal. Sebelum remedial sah, nilai normal ditampilkan sebagai **sementara**, bukan nol.
5. **Remedial dibatalkan** → nilai normal sah tetap ada; remedial pengganti hanya pada **susulan remedial** dari Admin bagi peserta ditunjuk; bukan remedial tambahan. Jika remedial akhirnya selesai sah, nilai terbaik normal/remedial menjadi **final** walaupun masih <KKM.
6. **Percobaan masih berjalan/jeda** → lanjutkan **token percobaan sama**; penutupan jendela *boleh mulai* tidak memotong durasi yang telah dimulai dan tidak membuat attempt baru.
7. KKM satu siswa tetap konsisten antarpercobaan; peserta tidak boleh memakai skor dari percobaan batal, dua normal sah, dua remedial sah, atau remedial tanpa normal sah. Tidak ada nilai akhir semester agregat.
**Implementasi sementara:** `PenilaianPraSemesterService` dan `AturanJadwalPraSemesterService` merupakan Service domain murni; adapter penjadwalan/roster/DB, mesin remedial dan susulan menunggu verifikasi dump XAMPP pascapatch. Jangan menampilkan tombol remedial/susulan sebelum benar-benar tersedia.

### A8-5 — Sesi operasional susulan/remedial (3 November 2026)
- Setelah audit dump terbaru `sla_sks_v4 (13).sql`, jadwal susulan dan remedial memakai skema additive `V4-0009-PRA-SESI-TERJADWAL`. Jadwal utama di `ujian_pra_semester` **tidak dipindahkan**.
- Admin membuat jendela boleh mulai berikut durasi **untuk tiap sesi tambahan** dengan jenis Remedial (tahap remedial), Susulan Normal (untuk batal normal atau tidak hadir), atau Susulan Remedial (untuk remedial batal). Jendela tambahan harus setelah jadwal utama dan tidak bentrok dengan sesi Gen–semester yang sama.
- Peserta susulan dipilih eksplisit oleh Admin dan dicatat pada tabel `peserta_pra_susulan`; tidak menambah/menghapus riwayat lama. Hanya peserta Gen dan tahap yang sesuai yang dapat ikut. Remedial terbuka berdasarkan normal selesai sah <KKM, bukan semua siswa.
- Sebelum setiap attempt, sistem membaca histori satu siswa/satu ujian; menggunakan nomor percobaan berurut dan `jadwal_pra_sesi_id` yang terverifikasi. Pembatalan tidak menciptakan skor atau kesempatan sah. Attempt yang masih berjalan tetap bisa dilanjutkan tanpa harus menunggu jadwal lain.
- Tidak mengubah kebijakan tes mandiri Diagnostik/Sumatif, tidak membuka remedial/susulan tanpa jadwal Admin, dan tidak membahas sertifikat pada tahap ini.
