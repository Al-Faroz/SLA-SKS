<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// K0: only implemented public endpoints. Authentication/role routes are added in K1.
$routes->get('/', 'Publik::beranda');
$routes->get('masuk', 'Publik::masuk');
$routes->get('kebijakan-privasi', 'Publik::kebijakanPrivasi');
$routes->set404Override('Publik::tidakDitemukan');
