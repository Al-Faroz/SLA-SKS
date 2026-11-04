<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * A13-1: panduan operasional dari aturan akademik v2.5.
 *
 * Ini BUKAN FAQ global terkelola Admin, pengumuman, atau notifikasi.
 * Modul tersebut memiliki persyaratan penyimpanan tersendiri.
 * Katalog ini berisi informasi statis dan tautan ke endpoint yang SUDAH ADA.
 */
final class PanduanBantuanService
{
    /** @return array{peran:string,nama_peran:string,kelompok:list<array{judul:string,keterangan:string,pertanyaan:list<array{tanya:string,jawab:string,path:string,aksi:string}>}>} */
    public static function untukPeran(string $peran): array
    {
        $umum = [
            'judul' => 'Akun dan keamanan',
            'keterangan' => 'Pengaturan yang berlaku bagi setiap pengguna SLA-SKS.',
            'pertanyaan' => [
                [
                    'tanya' => 'Bagaimana mengubah sandi akun?',
                    'jawab' => 'Guru, Pimpinan, dan Siswa disarankan mengganti sandi awal, tetapi tetap dapat masuk dan menggunakan layanan. Formulir Ganti sandi ada langsung di Profil & Peran. Akun dengan peran Admin tetap wajib mengganti sandi awal sebelum menggunakan layanan.',
                    'path' => 'profil',
                    'aksi' => 'Buka Profil & Peran',
                ],
                [
                    'tanya' => 'Bagaimana reset sandi oleh Admin bekerja?',
                    'jawab' => 'Untuk Guru, Pimpinan dan Siswa, Admin mereset ke sandi default yang ditetapkan pada formulir reset, bukan sandi acak. Untuk akun yang memiliki peran Admin, reset menghasilkan sandi acak dan pengguna wajib menggantinya. Semua sesi lama dibatalkan.',
                    'path' => 'profil',
                    'aksi' => 'Buka Profil & Peran',
                ],
                [
                    'tanya' => 'Apakah boleh menggunakan peran lain dalam akun yang sama?',
                    'jawab' => 'Akun staf hanya dapat menggunakan peran yang telah diberikan Admin. Bila memiliki dua peran, ubah peran aktif melalui Profil & Peran. Hak kedua peran tidak digabungkan dalam satu sesi.',
                    'path' => 'profil',
                    'aksi' => 'Periksa peran aktif',
                ],
            ],
        ];

        $data = match ($peran) {
            'siswa' => [
                'nama_peran' => 'Siswa',
                'kelompok' => [
                    [
                        'judul' => 'Mulai dan melanjutkan belajar',
                        'keterangan' => 'Akses semester dan urutan TP bergantung pada aturan pembelajaran.',
                        'pertanyaan' => [
                            [
                                'tanya' => 'Dari mana saya membuka TP?',
                                'jawab' => 'Pilih Pembelajaran, lalu Mata Pelajaran, Semester yang terbuka, dan TP berikutnya. Semester atau TP yang belum dibuka sistem tidak dapat dilewati dengan menebak alamat halaman.',
                                'path' => 'siswa/pembelajaran',
                                'aksi' => 'Buka Pembelajaran',
                            ],
                            [
                                'tanya' => 'Apa yang terjadi setelah Diagnostik?',
                                'jawab' => 'Jika lulus Diagnostik, Anda dapat melewati Materi, Resume, dan HOTS, tetapi Sumatif tetap wajib. Jika tidak lulus Diagnostik, lanjutkan Materi, Resume, HOTS, lalu Sumatif sesuai urutan.',
                                'path' => 'siswa/pembelajaran',
                                'aksi' => 'Lihat TP saya',
                            ],
                            [
                                'tanya' => 'Bagaimana jika ujian Diagnostik atau Sumatif tidak selesai?',
                                'jawab' => 'Buka kembali Rincian TP. Bila sistem menunjukkan perlu bantuan, ajukan permintaan akses ulang sesuai jenis ujian. Guru pengampu memutuskan izin; permintaan akses ulang bukan kesempatan remedial nilai.',
                                'path' => 'siswa/pembelajaran',
                                'aksi' => 'Kembali ke Rincian TP',
                            ],
                        ],
                    ],
                    [
                        'judul' => 'Nilai dan sertifikat',
                        'keterangan' => 'Nilai TP terpisah dari checkpoint Pra-Semester.',
                        'pertanyaan' => [
                            [
                                'tanya' => 'Di mana melihat nilai TP dan Pra-Semester?',
                                'jawab' => 'Buka Laporan Akademik. Nilai TP dan hasil Pra-Semester ditampilkan terpisah. Pra-Semester adalah checkpoint sebelum Ujian Semester yang sesungguhnya, bukan komponen Nilai Akhir Semester.',
                                'path' => 'siswa/laporan',
                                'aksi' => 'Buka Laporan Akademik',
                            ],
                            [
                                'tanya' => 'Apakah Pra-Semester harus selesai agar memperoleh sertifikat?',
                                'jawab' => 'Tidak. Pra-Semester bukan syarat penerbitan sertifikat dan nilainya tidak dicetak pada sertifikat. Persyaratan akademik sertifikat mengikuti ketuntasan TP NonEsensial yang sah.',
                                'path' => 'siswa/sertifikat',
                                'aksi' => 'Lihat Sertifikat',
                            ],
                        ],
                    ],
                ],
            ],
            'guru' => [
                'nama_peran' => 'Guru',
                'kelompok' => [
                    [
                        'judul' => 'Perangkat pembelajaran',
                        'keterangan' => 'Paket dan TP dikelola pada Gen–Mapel yang ditugaskan.',
                        'pertanyaan' => [
                            [
                                'tanya' => 'Di mana menyiapkan BAB, TP dan Materi?',
                                'jawab' => 'Gunakan Peta ATP untuk Gen–Mapel yang Anda ampu. Tiap TP Esensial maupun NonEsensial memerlukan tepat satu Materi dan media lengkap. TP baru siap setelah pemeriksaan kesiapan terpenuhi.',
                                'path' => 'guru/peta-atp',
                                'aksi' => 'Buka Peta ATP',
                            ],
                            [
                                'tanya' => 'Kapan TP Siap boleh diperbaiki?',
                                'jawab' => 'TP Draf dan TP Siap yang belum pernah digunakan siswa boleh disunting langsung oleh Guru pengampu. Setelah digunakan satu siswa, konten TP terkunci. BAB dapat diperbaiki sesuai editor yang tersedia; tidak ada fitur pindah TP antar-BAB.',
                                'path' => 'guru/peta-atp',
                                'aksi' => 'Periksa TP',
                            ],
                        ],
                    ],
                    [
                        'judul' => 'Penilaian dan akses ujian',
                        'keterangan' => 'Pisahkan penilaian tugas, izin tes ulang, dan rekap nilai.',
                        'pertanyaan' => [
                            [
                                'tanya' => 'Di mana menilai jawaban Resume dan HOTS?',
                                'jawab' => 'Buka Antrean Penilaian. Guru memberikan nilai untuk jawaban final pada Gen–Mapel yang diampu. Nilai yang belum tersedia tidak sama dengan nilai angka nol.',
                                'path' => 'guru/penilaian',
                                'aksi' => 'Buka Antrean Penilaian',
                            ],
                            [
                                'tanya' => 'Bagaimana memproses tes siswa yang gagal selesai?',
                                'jawab' => 'Periksa Permintaan Akses Ulang dan berikan izin untuk tahap Diagnostik atau Sumatif yang diminta sesuai aturan. Akses ulang tes tidak otomatis menjadi remedial nilai. Pembatalan dan penjadwalan susulan hanya untuk Pra-Semester.',
                                'path' => 'guru/akses-ulang',
                                'aksi' => 'Periksa Akses Ulang',
                            ],
                            [
                                'tanya' => 'Apakah nilai Pra-Semester digabung ke Nilai TP?',
                                'jawab' => 'Tidak. Rekap Nilai TP dan Nilai Pra-Semester menggunakan halaman dan ekspor terpisah. Tidak ada perhitungan Nilai Akhir Semester otomatis.',
                                'path' => 'guru/rekap-nilai',
                                'aksi' => 'Buka Rekap Nilai',
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'nama_peran' => 'Admin',
                'kelompok' => [
                    [
                        'judul' => 'Akun dan penugasan',
                        'keterangan' => 'Data akun, Gen dan Guru dikelola tanpa menghapus riwayat akademik.',
                        'pertanyaan' => [
                            [
                                'tanya' => 'Di mana membuat akun Pengelola dan Siswa?',
                                'jawab' => 'Gunakan Pengelola untuk akun staf dan Siswa untuk akun peserta. Penempatan Gen dilakukan oleh Admin, termasuk semester mulai khusus bila memang diperlukan.',
                                'path' => 'admin/pengelola',
                                'aksi' => 'Kelola Pengelola',
                            ],
                            [
                                'tanya' => 'Bagaimana menetapkan Guru pengampu?',
                                'jawab' => 'Buka Mapel & Guru. Penugasan Guru mengikuti Gen–Mapel, tidak otomatis berlaku untuk Gen lain. Ringkasan kombinasi Gen × Mapel adalah cakupan potensial, bukan kewajiban mengisi semua kombinasi.',
                                'path' => 'admin/mapel-guru',
                                'aksi' => 'Kelola Mapel & Guru',
                            ],
                        ],
                    ],
                    [
                        'judul' => 'Penjadwalan dan sertifikat',
                        'keterangan' => 'Penjadwalan ujian dan penerbitan sertifikat tetap berada di modul Admin.',
                        'pertanyaan' => [
                            [
                                'tanya' => 'Siapa yang menetapkan jadwal Pra-Semester?',
                                'jawab' => 'Admin menetapkan jendela boleh mulai serta durasi pengerjaan per Gen–Mapel–Semester. Jadwal remedial dan susulan memiliki sesi tersendiri sesuai peserta yang memenuhi syarat.',
                                'path' => 'admin/pra-semester',
                                'aksi' => 'Atur Pra-Semester',
                            ],
                            [
                                'tanya' => 'Apakah TTD harus aktif untuk menerbitkan sertifikat?',
                                'jawab' => 'Periksa aset dan TTD melalui menu Sertifikat. Penerbitan mengikuti kelayakan akademik TP dan aset resmi yang berlaku. Pra-Semester tidak menjadi syarat dan nilainya tidak dicetak.',
                                'path' => 'admin/sertifikat',
                                'aksi' => 'Kelola Sertifikat',
                            ],
                            [
                                'tanya' => 'Di mana mengatur KKM default?',
                                'jawab' => 'Buka Pengaturan KKM. KKM default berlaku untuk TP yang masih disiapkan dan belum menetapkan nilai khusus; TP yang sudah Siap mempertahankan snapshot KKM-nya.',
                                'path' => 'admin/kkm',
                                'aksi' => 'Buka Pengaturan KKM',
                            ],
                        ],
                    ],
                ],
            ],
            'pimpinan' => [
                'nama_peran' => 'Pimpinan',
                'kelompok' => [
                    [
                        'judul' => 'Monitoring akademik',
                        'keterangan' => 'Informasi untuk pemantauan tanpa hak mutasi.',
                        'pertanyaan' => [
                            [
                                'tanya' => 'Di mana melihat perkembangan pembelajaran siswa?',
                                'jawab' => 'Buka Monitoring Akademik untuk ringkasan progres TP NonEsensial menurut Gen–Mapel–Semester, kemudian buka laporan individual yang tersedia.',
                                'path' => 'pimpinan/laporan',
                                'aksi' => 'Buka Monitoring Akademik',
                            ],
                            [
                                'tanya' => 'Apakah Pimpinan dapat mengubah nilai atau jadwal?',
                                'jawab' => 'Tidak. Peran Pimpinan hanya memantau dan membaca laporan. Penilaian dilakukan Guru yang ditugaskan; pengaturan dan jadwal menjadi kewenangan peran terkait.',
                                'path' => 'pimpinan/laporan',
                                'aksi' => 'Lihat Laporan Baca-saja',
                            ],
                        ],
                    ],
                ],
            ],
            default => throw new DomainException('Peran aktif tidak dikenali.'),
        };

        // Hindari menampilkan panduan pergantian peran untuk Siswa yang tidak
        // dapat memiliki peran rangkap. Siswa tetap dapat mengganti sandi.
        if ($peran === 'siswa') {
            $umum['pertanyaan'] = [$umum['pertanyaan'][0]];
        }
        $data['kelompok'][] = $umum;

        return ['peran' => $peran] + $data;
    }
}
