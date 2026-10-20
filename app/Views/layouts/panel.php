<?php
/**
 * Shell bersama empat peran. Hanya dipakai oleh controller yang sudah
 * mengautentikasi sesi dan memasok menu rute yang benar-benar tersedia.
 *
 * $tautanNavigasi = [['label' => 'Beranda', 'path' => 'siswa', 'aktif' => true], ...]
 */
$tautanNavigasi = $tautanNavigasi ?? [];
$namaPengguna = $namaPengguna ?? '';
$peranAktif = $peranAktif ?? '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($judul ?? 'Panel') ?> · SLA-SKS</title>
    <link rel="stylesheet" href="<?= esc(base_url('public/assets/css/app.css'), 'attr') ?>">
    <script defer src="<?= esc(base_url('public/assets/js/app.js'), 'attr') ?>"></script>
</head>
<body>
<a class="a-lewati" href="#konten-utama">Lewati ke konten</a>
<div class="a-app<?= ! empty($uiPrototipe) ? ' a-app--prototipe' : '' ?>" data-shell="panel">
    <aside id="navigasi-panel" class="a-side" aria-label="Navigasi panel">
        <div class="a-side__atas">
            <span class="a-identitas__mark" aria-hidden="true">S</span>
            <strong>SLA-SKS</strong>
        </div>
        <nav class="a-side__menu" aria-label="Menu peran aktif">
            <?php foreach ($tautanNavigasi as $tautan): ?>
                <?php if (! isset($tautan['path'], $tautan['label'])) { continue; } ?>
                <a class="a-side__tautan<?= ! empty($tautan['aktif']) ? ' is-aktif' : '' ?>"
                   href="<?= esc(site_url(ltrim((string) $tautan['path'], '/')), 'attr') ?>"
                   <?= ! empty($tautan['aktif']) ? 'aria-current="page"' : '' ?>>
                    <?= esc($tautan['label']) ?>
                </a>
            <?php endforeach ?>
        </nav>
    </aside>
    <button type="button" class="a-backdrop" data-menu-close hidden aria-label="Tutup navigasi"></button>
    <div class="a-main">
        <header class="a-header">
            <button type="button" class="a-btn a-btn--sekunder a-menu-toggle"
                    data-menu-toggle aria-controls="navigasi-panel" aria-expanded="false">
                <span aria-hidden="true">☰</span> Menu
            </button>
            <div class="a-header__pengguna">
                <strong><?= esc($namaPengguna) ?></strong>
                <span class="a-badge"><?= esc($peranAktif) ?></span>
                <form method="post" action="<?= esc(site_url('keluar'), 'attr') ?>" class="a-form-keluar">
                    <?= csrf_field() ?>
                    <button class="a-btn a-btn--sekunder" type="submit">Keluar</button>
                </form>
            </div>
        </header>
        <main class="a-main__konten" id="konten-utama">
            <?= $this->renderSection('konten') ?>
        </main>
    </div>
</div>
</body>
</html>
