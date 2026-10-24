<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Halaman publik: tidak ada registrasi mandiri.
$routes->get('/', 'Publik::beranda');
$routes->get('masuk', 'Akun::masuk');
$routes->post('masuk', 'Akun::prosesMasuk');
$routes->get('kebijakan-privasi', 'Publik::kebijakanPrivasi');

// Semua mutasi memerlukan CSRF (Filter global) dan session.
$routes->get('ganti-sandi', 'Akun::gantiSandi', ['filter' => 'auth']);
$routes->post('ganti-sandi', 'Akun::prosesGantiSandi', ['filter' => 'auth']);
$routes->post('keluar', 'Akun::keluar', ['filter' => 'auth']);
$routes->get('profil', 'Akun::profil', ['filter' => 'auth']);
$routes->post('profil/kontak', 'Profil::simpanKontak', ['filter' => 'auth']);
$routes->post('profil/foto', 'Profil::unggahFoto', ['filter' => 'auth']);
$routes->get('profil/foto/(:num)', 'Profil::mediaFoto/$1', ['filter' => 'auth']);
$routes->get('admin/foto-profil', 'Profil::tinjauFoto', ['filter' => 'auth:admin']);
$routes->post('admin/foto-profil/(:num)/setujui', 'Profil::setujuiFoto/$1', ['filter' => 'auth:admin']);
$routes->post('peran/ganti', 'Akun::prosesGantiPeran', ['filter' => 'auth']);

$routes->get('siswa', 'Akun::berandaSiswa', ['filter' => 'auth:siswa']);
$routes->get('guru', 'Akun::berandaGuru', ['filter' => 'auth:guru']);
$routes->get('guru/peta-atp', 'GuruPetaTp::index', ['filter' => 'auth:guru']);
$routes->get('guru/tp/(:num)', 'GuruDetailTp::lihat/$1', ['filter' => 'auth:guru']);
$routes->get('guru/tp/(:num)/bank-soal', 'GuruBankSoal::index/$1', ['filter' => 'auth:guru']);
$routes->post('guru/tp/(:num)', 'GuruDetailTp::simpan/$1', ['filter' => 'auth:guru']);
$routes->post('guru/tp/(:num)/materi', 'GuruMateri::tambah/$1', ['filter' => 'auth:guru']);
$routes->get('guru/tp/(:num)/materi/(:num)/edit', 'GuruMateri::edit/$1/$2', ['filter' => 'auth:guru']);
$routes->get('guru/tp/(:num)/materi/(:num)/pratinjau', 'GuruMateri::pratinjau/$1/$2', ['filter' => 'auth:guru']);
$routes->post('guru/tp/(:num)/materi/(:num)', 'GuruMateri::simpan/$1/$2', ['filter' => 'auth:guru']);
$routes->post('guru/tp/(:num)/materi/(:num)/media', 'GuruMateri::unggahMedia/$1/$2', ['filter' => 'auth:guru']);
$routes->get('guru/tp/(:num)/materi/(:num)/media', 'GuruMateri::bukaMedia/$1/$2', ['filter' => 'auth:guru']);
$routes->post('guru/tp/(:num)/materi/(:num)/urut', 'GuruMateri::urut/$1/$2', ['filter' => 'auth:guru']);
$routes->post('guru/tp/(:num)/materi/(:num)/hapus', 'GuruMateri::hapus/$1/$2', ['filter' => 'auth:guru']);
$routes->get('admin', 'Akun::berandaAdmin', ['filter' => 'auth:admin']);
$routes->get('pimpinan', 'Akun::berandaPimpinan', ['filter' => 'auth:pimpinan']);
// K2B v2.3 — satu autentikasi, dua ruang administrasi akun.
$routes->get('admin/pengguna', 'Akun::pengguna', ['filter' => 'auth:admin']); // kompatibilitas tautan K1
$routes->get('admin/pengelola', 'AdminPengguna::pengelola', ['filter' => 'auth:admin']);
$routes->post('admin/pengelola', 'AdminPengguna::buatPengelola', ['filter' => 'auth:admin']);
$routes->get('admin/siswa', 'AdminPengguna::siswa', ['filter' => 'auth:admin']);
$routes->post('admin/siswa', 'AdminPengguna::buatSiswa', ['filter' => 'auth:admin']);
$routes->post('admin/siswa/(:num)/gen', 'AdminPengguna::tempatkanSiswa/$1', ['filter' => 'auth:admin']);
$routes->get('admin/pengelola/(:num)/reset-sandi', 'AdminPengguna::konfirmasiResetPengelola/$1', ['filter' => 'auth:admin']);
$routes->post('admin/pengelola/(:num)/reset-sandi', 'AdminPengguna::resetPengelola/$1', ['filter' => 'auth:admin']);
$routes->get('admin/siswa/(:num)/reset-sandi', 'AdminPengguna::konfirmasiResetSiswa/$1', ['filter' => 'auth:admin']);
$routes->post('admin/siswa/(:num)/reset-sandi', 'AdminPengguna::resetSiswa/$1', ['filter' => 'auth:admin']);


// K2C — Edit identitas dan konfirmasi aktif/nonaktif (semua POST CSRF + role Admin).
$routes->get('admin/pengelola/(:num)/edit', 'AdminMutasiPengguna::editPengelola/$1', ['filter' => 'auth:admin']);
$routes->post('admin/pengelola/(:num)/edit', 'AdminMutasiPengguna::simpanPengelola/$1', ['filter' => 'auth:admin']);
$routes->get('admin/pengelola/(:num)/status', 'AdminMutasiPengguna::statusPengelola/$1', ['filter' => 'auth:admin']);
$routes->post('admin/pengelola/(:num)/status', 'AdminMutasiPengguna::ubahStatusPengelola/$1', ['filter' => 'auth:admin']);
$routes->get('admin/siswa/(:num)/edit', 'AdminMutasiPengguna::editSiswa/$1', ['filter' => 'auth:admin']);
$routes->post('admin/siswa/(:num)/edit', 'AdminMutasiPengguna::simpanSiswa/$1', ['filter' => 'auth:admin']);
$routes->get('admin/siswa/(:num)/status', 'AdminMutasiPengguna::statusSiswa/$1', ['filter' => 'auth:admin']);
$routes->post('admin/siswa/(:num)/status', 'AdminMutasiPengguna::ubahStatusSiswa/$1', ['filter' => 'auth:admin']);

// K2D: template dan ekspor XLSX, khusus Admin.
$routes->get('admin/pengelola/template', 'AdminXlsx::templatePengelola', ['filter' => 'auth:admin']);
$routes->get('admin/siswa/template', 'AdminXlsx::templateSiswa', ['filter' => 'auth:admin']);
$routes->get('admin/pengelola/ekspor', 'AdminXlsx::eksporPengelola', ['filter' => 'auth:admin']);
$routes->get('admin/siswa/ekspor', 'AdminXlsx::eksporSiswa', ['filter' => 'auth:admin']);

// K2D-2: impor XLSX dengan pratinjau privat dan konfirmasi POST.
$routes->get('admin/pengelola/impor', 'AdminImporAkun::halamanPengelola', ['filter' => 'auth:admin']);
$routes->post('admin/pengelola/impor', 'AdminImporAkun::unggahPengelola', ['filter' => 'auth:admin']);
$routes->get('admin/pengelola/impor/(:segment)', 'AdminImporAkun::lihatPengelola/$1', ['filter' => 'auth:admin']);
$routes->post('admin/pengelola/impor/(:segment)/konfirmasi', 'AdminImporAkun::konfirmasiPengelola/$1', ['filter' => 'auth:admin']);
$routes->get('admin/pengelola/impor/(:segment)/galat', 'AdminImporAkun::galatPengelola/$1', ['filter' => 'auth:admin']);
$routes->get('admin/siswa/impor', 'AdminImporAkun::halamanSiswa', ['filter' => 'auth:admin']);
$routes->post('admin/siswa/impor', 'AdminImporAkun::unggahSiswa', ['filter' => 'auth:admin']);
$routes->get('admin/siswa/impor/(:segment)', 'AdminImporAkun::lihatSiswa/$1', ['filter' => 'auth:admin']);
$routes->post('admin/siswa/impor/(:segment)/konfirmasi', 'AdminImporAkun::konfirmasiSiswa/$1', ['filter' => 'auth:admin']);
$routes->get('admin/siswa/impor/(:segment)/galat', 'AdminImporAkun::galatSiswa/$1', ['filter' => 'auth:admin']);

// K3A — Kurikulum dan CP resmi berversi. Hanya Admin aktif; semua POST global CSRF.
$routes->get('admin/kurikulum-cp', 'AdminKurikulumCp::index', ['filter' => 'auth:admin']);
$routes->post('admin/kurikulum-cp/kurikulum', 'AdminKurikulumCp::simpanKurikulum', ['filter' => 'auth:admin']);
$routes->post('admin/kurikulum-cp', 'AdminKurikulumCp::simpanCp', ['filter' => 'auth:admin']);
$routes->get('admin/kurikulum-cp/(:num)', 'AdminKurikulumCp::detail/$1', ['filter' => 'auth:admin']);
$routes->get('admin/kurikulum-cp/(:num)/versi-baru', 'AdminKurikulumCp::versiBaru/$1', ['filter' => 'auth:admin']);
$routes->post('admin/kurikulum-cp/(:num)/versi-baru', 'AdminKurikulumCp::simpanVersiBaru/$1', ['filter' => 'auth:admin']);
$routes->get('admin/kurikulum-cp/versi/(:num)/edit', 'AdminKurikulumCp::editDraf/$1', ['filter' => 'auth:admin']);
$routes->post('admin/kurikulum-cp/versi/(:num)/edit', 'AdminKurikulumCp::simpanDraf/$1', ['filter' => 'auth:admin']);
$routes->get('admin/kurikulum-cp/versi/(:num)/terbit', 'AdminKurikulumCp::konfirmasiTerbit/$1', ['filter' => 'auth:admin']);
$routes->post('admin/kurikulum-cp/versi/(:num)/terbit', 'AdminKurikulumCp::terbitkan/$1', ['filter' => 'auth:admin']);

// K2 — Data induk akademik, semua akses memakai filter Admin + pemeriksaan Service.
$routes->get('admin/referensi', 'AdminReferensi::referensi', ['filter' => 'auth:admin']);
$routes->post('admin/referensi/gen', 'AdminReferensi::buatGen', ['filter' => 'auth:admin']);
$routes->post('admin/referensi/tahun-ajaran', 'AdminReferensi::buatTahunAjaran', ['filter' => 'auth:admin']);
$routes->get('admin/mapel-guru', 'AdminReferensi::mapel', ['filter' => 'auth:admin']);
$routes->post('admin/mapel-guru', 'AdminReferensi::buatMapel', ['filter' => 'auth:admin']);
$routes->post('admin/mapel-guru/(:num)/gen/(:num)/guru', 'AdminReferensi::tetapkanGuru/$1/$2', ['filter' => 'auth:admin']);
$routes->get('admin/identitas', 'AdminIdentitas::index', ['filter' => 'auth:admin']);
$routes->post('admin/identitas', 'AdminIdentitas::simpan', ['filter' => 'auth:admin']);

$routes->set404Override('\App\Controllers\Publik::tidakDitemukan');
