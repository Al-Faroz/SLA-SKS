# Status dua template referensi v2.4

**PENTING: PR Master v2.4 belum boleh digabung sebelum dua file biner berikut benar-benar ada di `docs/` pada branch PR.**

File telah dibuat dari artefak hasil diskusi dan diverifikasi di lingkungan pembuatan. Pengunggahan biner ke GitHub **belum diselesaikan** oleh konektor penulisan file teks yang tersedia pada sesi pembuatan Master Document; tautan lokal ChatGPT bukan pengganti file GitHub.

| Lokasi final di repository | SHA-256 hasil lokal | Ukuran |
|---|---|---:|
| `docs/PETA_PEMBELAJARAN_6_SEMESTER.xlsx` | `ed8ecb3d3200fbe43709a1e7a4ef0d8044ea38b10f546505558b5657667af2e1` | 58.229 byte |
| `docs/TEMPLATE_SOAL_SLA-SKS.docx` | `8298069447fdcd30e0efc2255684a33ce00f9945b24001d823502866f1379af5` | 36.147 byte |

## Pemeriksaan yang dilakukan
- Excel: 10 worksheet seperti desain Guru (MULAI DI SINI, RINGKASAN, REFERENSI CP, SEMESTER 1–6, CONTOH MAT VII); 97 label/kalimat lama dibersihkan dari konsep BAB. Setelah ekspor masih ada 1.050 formula dan 26 aturan data validation, sama seperti referensi sebelumnya. String Subbab tetap benar karena merupakan istilah Materi/Submateri.
- Word: 10 contoh soal PG pada master dengan opsi A–E, tepat satu ✓ dari sumber contoh, satu Equation Word/OMML, satu gambar, dan tabel tertanam; metadata marker sudah `SLA-SKS-WORD-V1` tersembunyi; dirender sebagai 6 halaman dan ditinjau pratinjau halaman.
- Dua file **bukan hasil unduhan Guru nyata**. Generator CI4 belum dibuat dan harus mengisi Gen/Guru/Mapel/Kurikulum/CP pada Excel, serta jenis ujian/TP/jumlah slot soal pada Word. Bank Word Master berisi contoh dan tidak boleh diimpor sebagai data akademik sungguhan.

## Gate PR
1. Unggah dua file dengan nama persis sesuai tabel di atas **ke branch dokumentasi** (bukan data siswa/CP nyata) sebelum merge, dan cek hash jika bytes tetap.
2. Buka kedua berkas di Excel/Word; periksa sheet/formula/dropdown serta isi Equation/gambar/tabel Word.
3. Setelah Master dokumen dinyatakan PASS, kerjakan implementasi database dan kode pada PR terpisah; template dinamis belum dinyatakan selesai.
