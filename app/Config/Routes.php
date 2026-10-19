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
$routes->post('peran/ganti', 'Akun::prosesGantiPeran', ['filter' => 'auth']);

$routes->get('siswa', 'Akun::berandaSiswa', ['filter' => 'auth:siswa']);
$routes->get('guru', 'Akun::berandaGuru', ['filter' => 'auth:guru']);
$routes->get('admin', 'Akun::berandaAdmin', ['filter' => 'auth:admin']);
$routes->get('pimpinan', 'Akun::berandaPimpinan', ['filter' => 'auth:pimpinan']);
$routes->get('admin/pengguna', 'Akun::pengguna', ['filter' => 'auth:admin']);
$routes->post('admin/pengguna', 'Akun::prosesBuatPengguna', ['filter' => 'auth:admin']);

$routes->set404Override('\App\Controllers\Publik::tidakDitemukan');
