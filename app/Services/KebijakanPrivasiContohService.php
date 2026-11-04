<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Contoh naskah umum untuk editor Admin A15.
 * BUKAN dokumen resmi/terbit. Admin harus meninjau, menyesuaikan, meminta
 * pengesahan madrasah, lalu menyimpan draf dan menerbitkan melalui alur
 * KebijakanPrivasiService yang sudah ada. Tidak menyimpan data ke database.
 */
final class KebijakanPrivasiContohService
{
    public static function naskah(): string
    {
        return <<<'TEKS'
KEBIJAKAN PRIVASI SLA-SKS
MTsN 4 Jombang

1. Pendahuluan
Sistem Layanan Akademik – Sistem Kredit Semester (SLA-SKS) merupakan aplikasi pembelajaran dan layanan akademik yang digunakan oleh madrasah untuk mendukung proses belajar siswa MTs Fase D. Informasi ini menjelaskan secara umum pengelolaan data pribadi dalam aplikasi.

2. Jenis informasi yang diproses
SLA-SKS mengelola data yang diperlukan untuk layanan akademik, antara lain identitas dan akun pengguna, kelas/angkatan, penugasan Guru, mata pelajaran, materi pembelajaran, jawaban dan hasil asesmen, nilai, progres belajar, sertifikat hasil belajar pribadi, serta riwayat aktivitas sistem. Untuk menjaga keamanan dan kelangsungan layanan, sistem juga dapat mencatat informasi teknis yang diperlukan, seperti waktu akses, alamat IP, status sesi, dan peristiwa keamanan.

3. Tujuan penggunaan data
Data digunakan untuk mengelola akun dan hak akses, menyediakan pembelajaran mandiri, pelaksanaan ujian, penilaian, remedial sesuai aturan, penyajian capaian belajar, penerbitan dokumentasi sertifikat pribadi, komunikasi akademik, dukungan pengguna, dan pengamanan serta audit sistem. Data tidak digunakan untuk pendaftaran umum atau publikasi identitas dan nilai siswa kepada pengunjung situs.

4. Akses berdasarkan peran
Akses informasi dibatasi menurut kewenangan Admin, Guru, Siswa, dan Pimpinan. Siswa hanya dapat membuka hasil belajarnya sendiri; Guru mengakses data akademik sesuai penugasan Gen–Mapel; Pimpinan memperoleh informasi sesuai ruang lingkup laporan yang diizinkan; Admin mengelola administrasi, pengaturan, dan keamanan sistem. Beberapa informasi layanan, seperti pengumuman, mengikuti aturan penerima yang berlaku. Hak akses diperiksa oleh sistem pada setiap fungsi terkait.

5. Penyimpanan dan perlindungan
Madrasah menyelenggarakan pengelolaan akses serta langkah pengamanan teknis yang sesuai dengan fungsi aplikasi, termasuk autentikasi, pembatasan hak akses, pengamanan berkas privat, pencatatan aktivitas yang relevan, dan pengelolaan perubahan data. Keamanan sistem terus perlu diperiksa dan ditingkatkan. Tidak ada sistem digital yang dapat menjamin perlindungan mutlak terhadap seluruh risiko.

6. Penyampaian informasi
Informasi akademik pribadi hanya disajikan kepada pihak yang memiliki hak akses dalam SLA-SKS. Informasi dapat ditangani oleh petugas madrasah yang berwenang untuk keperluan layanan, bantuan, pemeliharaan, atau pemenuhan kewajiban yang berlaku. Data siswa, kredensial, dan hasil belajar tidak ditampilkan secara terbuka sebagai informasi publik.

7. Hak pengguna dan permintaan perbaikan
Pengguna dapat menghubungi Admin atau petugas madrasah yang berwenang untuk meminta penjelasan, perbaikan data identitas, pembaruan akses, atau menyampaikan persoalan terkait data dalam layanan. Perubahan nilai akademik dilakukan melalui mekanisme koreksi resmi oleh Guru pengampu dan tetap memiliki jejak riwayat sesuai aturan aplikasi.

8. Penyimpanan, riwayat, dan penghapusan
Data akademik dan riwayat perubahan dikelola sesuai kebutuhan pembelajaran serta ketentuan pengarsipan dan retensi yang ditetapkan oleh madrasah serta peraturan yang berlaku. Beberapa riwayat penilaian, aktivitas, dan dokumen versi terdahulu perlu dipertahankan untuk menjaga keterlacakan akademik. Permintaan penghapusan atau koreksi ditangani melalui madrasah dengan mempertimbangkan kewajiban pencatatan yang relevan.

9. Perangkat, sesi, dan layanan teknis
Aplikasi menggunakan sesi autentikasi dan mekanisme teknis yang diperlukan untuk menjaga pengguna tetap terhubung serta mengamankan ujian dan materi. Sebagian fitur ujian mensyaratkan perangkat dan kondisi layar tertentu. Penggunaan perangkat pribadi dan jaringan sebaiknya memperhatikan keamanan akun, termasuk menjaga kerahasiaan sandi dan keluar dari aplikasi pada perangkat bersama.

10. Perubahan kebijakan dan kontak
Naskah kebijakan yang telah disahkan dapat diperbarui dengan versi dan tanggal berlaku yang baru. Perubahan yang telah diterbitkan dicatat dalam riwayat sistem. Untuk informasi atau permohonan terkait data pribadi, hubungi Admin madrasah melalui saluran kontak resmi yang ditampilkan di aplikasi.

Dokumen ini berlaku setelah diperiksa, disahkan, dan diterbitkan oleh madrasah dengan label versi serta tanggal berlaku melalui pengelolaan Kebijakan Privasi SLA-SKS.
TEKS;
    }
}
