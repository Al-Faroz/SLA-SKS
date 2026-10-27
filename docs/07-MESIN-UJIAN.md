# 07 — Mesin Ujian Diagnostik, Sumatif dan Pra-Semester

## 1. Kebijakan perangkat dan lokasi
**Satu profil keamanan: Ketat.** Tidak ada pilihan longgar/standar. **Sumatif diizinkan dari rumah** hanya melalui **desktop/laptop** yang mendukung fullscreen. Diagnostik boleh dari HP/tablet apabila browser benar-benar mampu masuk fullscreen; jika tidak, akses **ditolak sebelum percobaan tercipta**, dengan pesan `Perangkat/browser ini tidak mendukung fullscreen. Buka ujian di desktop/laptop yang mendukungnya.` Jika kapabilitas tidak terverifikasi, jangan memulai ujian tanpa pengecekan lagi pada saat user gesture.

Instruksi petunjuk S05 menyampaikan bahwa membuka ujian memerlukan koneksi stabil dan fullscreen; pengawasan fisik tidak diasumsikan karena sumatif boleh dari rumah. User-Agent hanya indikasi tipe perangkat, bukan kontrol keamanan absolut.

## 2. Percobaan sah
- Diagnostik minimal 10 soal, satu percobaan normal; skor hanya gate pembelajaran, tidak menjadi nilai final TP.
- Sumatif minimal 30 soal, **wajib bagi semua TP NonEsensial**, satu tes awal sah. Jika nilai < KKM, **satu remedial**; nilai tertinggi tes awal/remedial menjadi skor Sumatif efektif. Kuota tiga percobaan normal v2.4 tidak berlaku lagi.
- Pra-Semester minimal 30 soal dari semua BAB semester, ujian tersendiri (bukan TP), pola dan keamanan Sumatif, serta remedial satu kali bila skor < KKM. Admin menetapkan satu jendela waktu ujian serentak bagi siswa dalam paket. Siswa belum selesai semua TP NonEsensial ditolak sebelum attempt.
- Ujian batal karena koneksi/pelanggaran tidak memberi skor dan tidak menghabiskan percobaan sah; Guru menangani ulang/lanjut sesuai kebijakan. Seluruh soal PG A–E satu kunci, bank/snapshot, kunci dan jawaban tetap diproses di server.

## 3. Waktu aktif dan jeda
| Parameter | Nilai final | Aturan |
|---|---:|---|
| Jeda fullscreen per kejadian | **90 detik** | Jika tercapai, batal menunggu guru |
| Total jeda fullscreen | **180 detik** | Terakumulasi semua kejadian |
| Pelanggaran fullscreen | **3 kali** | Kejadian ketiga → batal tanpa skor |
| Timeout heartbeat koneksi | **20 detik** | Waktu aktif tetap berjalan pada 20 detik deteksi |
| Total jeda koneksi | **300 detik** | Jika tercapai, batal menunggu guru |

`fullscreenchange`, `visibilitychange` dan `blur` yang terjadi karena satu perpindahan fokus adalah **satu insiden**, bukan tiga pelanggaran. Jika fullscreen keluar: server memindahkan `berjalan → jeda_fullscreen`, menghentikan waktu aktif, menolak jawab/kirim saat jeda, serta mencatat insiden. Melanjutkan wajib melalui tombol klik `Kembali ke fullscreen` dan validasi server.

Jika heartbeat tidak diterima ≥20 detik, saat rekonsiliasi server menyatakan jeda sejak `last_seen_server_at + 20 detik`, bukan sejak saat worker kebetulan berjalan. Pada koneksi kembali, server menghitung total jeda secara otoritatif; jika melewati 300 detik, batal. Jika fullscreen dan koneksi sama-sama bermasalah, **waktu yang tumpang tindih tidak dihitung ganda sebagai dua jenis jeda**. Urutan kejadian dan prioritas alasan harus deterministik, direkam pada segmen jeda.

**Shared hosting:** jangan mensyaratkan service daemon 24 jam sebagai satu-satunya mekanisme. Rekonsiliasi wajib dijalankan **setiap request status, heartbeat, jawab, kirim, buka layar, dan kembalinya koneksi**. Cron hosting adalah pengawas tambahan. Tanpa worker/deteksi aktif, transisi persis pada detik ke-20 tidak dapat dijamin saat sama sekali tidak ada request; sistem tetap harus menghasilkan status/waktu yang benar menggunakan waktu server ketika akhirnya direkonsiliasi. Dokumentasikan batas hosting ini secara jujur dan uji pada hosting tujuan.

## 4. Status percobaan
`baru → berjalan ↔ jeda_fullscreen` atau `jeda_koneksi`, `berjalan → selesai_sah`, serta dari status berjalan/jeda ke `dibatalkan_menunggu_guru`. Jangan memberi nilai pada dibatalkan. Keluar dari ujian tidak menghapus jawaban yang tersimpan; percobaan tidak berubah menjadi baru pada refresh. Pemeriksaan status/expired dilakukan sebelum menerima setiap jawaban, bukan setelahnya.

## 5. Keputusan Guru atas pembatalan
- Diagnostik: Guru memilih **`ulang_diagnostik`** atau **`langsung_belajar`**.
- Sumatif: Guru memilih **`ulang_sumatif`** atau **`tunda_tinjauan`**.
- Ulang sumatif yang diperlukan harus disetujui Guru **sekali pakai**, dibuat sebagai percobaan pengganti, tidak memotong kuota normal karena percobaan batal.
- Alasan tindakan dan pelaku tercatat; Guru hanya mapelnya; Admin/Pimpinan tidak mengubah nilai ujian lewat menu umum.

## 6. UI S05/S06/S07
- **S05:** syarat perangkat, fullscreen, stabilitas koneksi, sisa percobaan, durasi dan tombol `Mulai ujian`; preflight memeriksa kemampuan fullscreen, perangkat sumatif dan hak siswa. Tolak sebelum insert percobaan jika gagal.
- **S06:** tata letak tanpa sidebar, timer server, soal, pilihan, navigator, indikator menyimpan, jumlah insiden terhitung, tampilan jeda penuh; tidak ada notifikasi/pengumuman/perayaan saat ujian. Tombol kembali fullscreen dan tombol kirim berkonfirmasi. Respons saat offline tidak dianggap sudah tersimpan tanpa konfirmasi server.
- **S07:** tampil skor/ketuntasan hanya `selesai_sah`, atau status `Menunggu keputusan guru` tanpa nilai bila batal. Tidak membuka kunci jawaban sebelum kebijakan perujian mengizinkan.

### Wewenang Guru per Gen–Mapel (v2.4)
Penanganan ujian batal, izin ulang, pembacaan percobaan/snapshot, dan telaah insiden hanya tersedia bagi Guru yang **saat itu ditugaskan ke pasangan Gen–Mapel siswa/ujian**. Service memeriksa relasi paket ujian ke Gen pemilik, `siswa.angkatan_id`, peran aktif Guru dan penugasan aktif. Guru Gen lain tidak dapat melakukan GET/POST dengan menebak ID ujian. Pergantian Guru dalam Gen yang sama menjaga snapshot, keputusan, dan pencatat historis. Seluruh kebijakan fullscreen, waktu server, jeda dan profil Ketat tidak berubah.

## 7. Keamanan dan hak
Throttler per akun dan percobaan, CSRF, session auth, anti replay, cek status setiap request, soal/opsi acak server, ID percobaan sulit ditebak, tanpa skor pada percobaan batal, log insiden tidak terhitung dan terhitung terpisah. Fullscreen hanya mitigasi berbasis browser, **bukan jaminan ujian anticurang**.

## 8. Kontrak soal Word dan multimedia v2.4
- Diagnostik dan Sumatif memakai **satu tipe soal PG lima opsi A–E**. Tidak menerapkan PG kompleks, menjodohkan, isian, uraian, atau tipe CBT lain. Soal dapat diacak dari bank soal TP versi aktif dan urutan opsi diacak; kunci disimpan di server.
- Impor dari dua template Word beridentitas `SLA-SKS-WORD-V1` mengikuti [05](05-PAKET-TP-DAN-IMPOR.md). Teks, Equation Word/OMML, gambar, tabel termasuk tabel bersarang dipertahankan **pada pertanyaan maupun pilihan** dalam rendering ujian.
- Audio/video soal via Google Drive boleh hanya jika URL sah, izin akses dan pemutaran telah lolos pratinjau; kegagalan media penting harus mencegah bank dinyatakan Siap. Ini tidak mengubah kebijakan YouTube untuk media pembelajaran.
- Sanitasi konten DOCX, kontrol kunci dan cache aset historis wajib; sampel ujian tidak mengirim kunci ke klien sebelum sesuai kebijakan hasil.

## 9. Kontrak Pra-Semester
Menggunakan pemeriksaan fullscreen Ketat yang sama, jeda fullscreen 90 detik per kejadian/180 akumulatif, insiden ketiga batal, koneksi 20 detik/300 akumulatif. Server memeriksa waktu mulai/akhir Admin dan status seluruh TP NonEsensial sebelum membuat percobaan. Jadwal **serentak** bukan jaminan siswa yang tidak hadir dapat mengerjakan di luar jadwal.
