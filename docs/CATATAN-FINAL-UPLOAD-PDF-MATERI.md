# Keputusan Final — Upload dan Penggantian PDF Materi

**Tanggal keputusan:** 24 Oktober 2026  
**Status:** FINAL untuk alur PDF materi; catatan pelaksanaan agar masalah lama tidak berulang.  
**Rujukan:** `docs/05-PAKET-TP-DAN-IMPOR.md` §5; `docs/14-KEAMANAN-DAN-AUDIT.md` §4–5; `docs/12-UI-UX-DAN-HALAMAN.md` layar G07.

## 1. Masalah yang sudah diselesaikan
Pada pengujian menu unggah/penggantian PDF materi, berkas PDF yang secara wajar dapat dibuka sebagai bahan pembelajaran pernah terhambat oleh validasi teknis tambahan yang sulit dipahami pengguna. Diskusi dan perbaikan berulang pada tahap ini menghabiskan waktu tanpa menambah manfaat yang sebanding. **Pengguna telah menyatakan tahap ini sudah fix.** Jangan membuka kembali masalah tersebut tanpa bug nyata yang dapat direproduksi.

## 2. Keputusan produk yang mengikat
1. **Batas bawaan file PDF materi: maksimal 15 MB dan 40 halaman** (sesuai Master Document 05). Fokus batas tersebut adalah menjaga kapasitas penyimpanan shared hosting dan besarnya media pembelajaran.
2. Guru dapat **mengunggah PDF baru serta mengganti PDF materi yang telah ada** lewat alur biasa. Penggantian bukan operasi impor dokumen kompleks dan tidak boleh mensyaratkan pemahaman istilah internal parser/PDF.
3. Pemeriksaan yang terlihat oleh pengguna cukup jelas dan praktis: file dipilih, jenis berkas PDF benar, ukuran tidak melampaui batas, jumlah halaman tidak melampaui batas, lalu status unggah/penggantian sukses atau pesan kegagalan spesifik.
4. Pemeriksaan keamanan dasar tetap wajib di server: otorisasi Guru dan konteks Gen–Mapel/TP, CSRF, berkas PDF asli yang dapat diproses, penyimpanan aman/privat, dan penolakan konten berbahaya yang benar-benar terdeteksi. **Jangan** memaksa syarat tambahan seperti struktur internal tertentu, optimasi atau pola penyimpanan inkremental, profil PDF khusus, metadata nonesensial, atau kompatibilitas pembuat PDF tertentu sebagai kriteria menerima materi.
5. Jangan menolak PDF yang valid **hanya berdasarkan dugaan** fitur internal PDF atau pesan generik dari parser. Jika pemeriksaan halaman gagal karena alat hitung, tangani sebagai masalah teknis terdiagnosis; jangan menyebut berkas pengguna rusak tanpa bukti. Keterbatasan pemeriksaan keamanan yang nyata tetap harus gagal aman dan dicatat untuk developer.
6. Dalam pembaruan materi yang sudah dipublikasikan, tetap patuhi aturan versi/snapshot dan larangan menimpa berkas historis menurut Master 05/14. Ini mekanisme backend, **bukan beban atau pertanyaan teknis bagi Guru**.

## 3. Pesan antarmuka yang diharapkan
- Bantuan form: **“Unggah berkas PDF maksimal 15 MB dan 40 halaman.”**
- Terlalu besar: **“Ukuran PDF melebihi 15 MB. Pilih berkas yang lebih kecil.”**
- Terlalu panjang: **“PDF melebihi 40 halaman. Pilih berkas dengan halaman lebih sedikit.”**
- Bukan PDF: **“Berkas harus berupa PDF.”**
- Gagal diproses: **“PDF belum dapat diproses. Coba unggah ulang atau hubungi pengelola jika masalah berlanjut.”** Detail diagnostik disimpan pada log internal tanpa memaparkan informasi sensitif.
- Berhasil: **“PDF materi berhasil diunggah.”** atau **“PDF materi berhasil diganti.”**

## 4. Pemeriksaan regresi untuk perubahan berikutnya
- PDF normal <=15 MB dan <=40 halaman: unggah **berhasil**.
- PDF pengganti normal <=15 MB dan <=40 halaman: penggantian **berhasil**, tautan pratinjau mengarah ke berkas yang benar.
- Ukuran >15 MB: ditolak dengan pesan ukuran.
- Halaman >40: ditolak dengan pesan halaman.
- File bukan PDF atau rusak/berbahaya: ditolak dengan pesan yang masuk akal dan log teknis.
- PDF dari aplikasi pembuat berbeda yang tetap valid: tidak otomatis ditolak karena format internal/metadata/optimasi.
- Penolakan upload pengganti: materi lama tetap tersedia; tidak meninggalkan pointer berkas rusak.
- File/versi lama yang menjadi bukti historis tetap terjaga sesuai kebijakan snapshot.
- Hak akses antar Guru/Gen–Mapel tidak bocor.

## 5. Larangan pengulangan
- **Jangan** memperkenalkan validasi PDF tambahan, pustaka parser baru, atau proses konversi PDF hanya berdasarkan dugaan peningkatan kualitas.
- **Jangan** mengubah angka batas tanpa persetujuan pemilik dan pembaruan Master Document.
- **Jangan** mengubah alur UI menjadi rumit untuk mengakomodasi detail storage internal.
- Bila nanti muncul kasus gagal, lakukan diagnosis **terhadap file contoh, pesan server dan kode terkini**, kemudian buat perbaikan paling kecil dan tes regresi. Jangan mengulang debat atau refaktor luas pada tahap yang telah fix.

**Catatan status:** Dokumen ini merekam keputusan dan skenario pengujian; tidak mengklaim pengujian localhost atau perubahan kode baru dalam PR dokumentasi ini.
