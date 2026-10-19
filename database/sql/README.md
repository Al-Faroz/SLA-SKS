# Kontrak SQL Dump — SLA-SKS v2.2

**Tidak menggunakan migration atau seeder CI4.** Programmer menyiapkan:
- `install/sla_sks_v2_2_full.sql` — dump lengkap skema dan data bawaan **tanpa data nyata siswa**; aman untuk MariaDB hosting dan MySQL lokal.
- `updates/0001_<nama>.sql`, `0002_<nama>.sql`, dan seterusnya — patch SQL urut, idempoten bila memungkinkan, menjaga seluruh data lama.

**Paket dokumentasi ini belum berisi dump SQL final.** Jangan mengimpor file yang belum dibuat dan diuji. Pada saat implementasi, sertakan tabel versi/update, indeks/FK yang diperlukan, dan instruksi import phpMyAdmin. Patch harus diuji pada DB dengan siswa/TP/sertifikat contoh tanpa kehilangan data.

**Operasional:** sebelum update, backup manual DB + storage privat; jalankan patch berurutan; verifikasi versi dan aplikasi; pulihkan dari backup jika DDL gagal. DDL MySQL/MariaDB tidak selalu dapat di-rollback dengan `ROLLBACK` SQL biasa.
