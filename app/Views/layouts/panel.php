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
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($judul ?? 'Panel') ?> · SLA-SKS</title>
    <?php
        // Browser harus mengambil CSS/JS baru setelah git pull.
        // FCPATH mengarah ke root proyek (index.php berada pada root SLA-SKS).
        $cssFile = FCPATH . 'public/assets/css/app.css';
        $jsFile  = FCPATH . 'public/assets/js/app.js';
        $temaCssFile = FCPATH . 'public/assets/css/emerald-glass.css';
        $temaJsFile = FCPATH . 'public/assets/js/theme-init.js';
        $temaCssVersi = is_file($temaCssFile) ? (string) filemtime($temaCssFile) : '1';
        $temaJsVersi = is_file($temaJsFile) ? (string) filemtime($temaJsFile) : '1';
        $cssVersi = is_file($cssFile) ? (string) filemtime($cssFile) : '1';
        $jsVersi  = is_file($jsFile) ? (string) filemtime($jsFile) : '1';
    ?>
    <script src="<?= esc(base_url('public/assets/js/theme-init.js') . '?v=' . $temaJsVersi, 'attr') ?>"></script>
    <link rel="stylesheet" href="<?= esc(base_url('public/assets/css/app.css') . '?v=' . $cssVersi, 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(base_url('public/assets/css/emerald-glass.css') . '?v=' . $temaCssVersi, 'attr') ?>">
    <script defer src="<?= esc(base_url('public/assets/js/app.js') . '?v=' . $jsVersi, 'attr') ?>"></script>
</head>
<body>
<a class="a-lewati" href="#konten-utama">Lewati ke konten</a>
<div class="a-app a-app--prototipe" data-shell="panel">
    <aside id="navigasi-panel" class="a-side" aria-label="Navigasi panel">
        <div class="a-side__atas">
            <span class="a-identitas__mark" aria-hidden="true">S</span>
            <strong class="a-side__brand">SLA-SKS</strong>
            <button type="button" class="a-btn a-side__collapse" data-sidebar-collapse aria-label="Minimalkan sidebar" title="Minimalkan sidebar" aria-expanded="true">
                <svg class="a-icon a-icon--expanded" aria-hidden="true"><use href="<?= esc(base_url('public/assets/icons/lucide-sla.svg'), 'attr') ?>#panel-left-close"></use></svg>
                <svg class="a-icon a-icon--collapsed" aria-hidden="true"><use href="<?= esc(base_url('public/assets/icons/lucide-sla.svg'), 'attr') ?>#panel-left-open"></use></svg>
            </button>
        </div>
        <nav class="a-side__menu" aria-label="Menu peran aktif">
            <?php foreach ($tautanNavigasi as $tautan): ?>
                <?php if (! isset($tautan['path'], $tautan['label'])) { continue; } ?>
                <?php
                    $jalurIkon = strtolower((string) $tautan['path']);
                    $ikonMenu = match (true) {
                        str_contains($jalurIkon, 'peta-atp'), str_contains($jalurIkon, 'kurikulum'), str_contains($jalurIkon, 'cp') => 'book-open',
                        str_contains($jalurIkon, 'penilaian'), str_contains($jalurIkon, 'ujian') => 'clipboard-check',
                        str_contains($jalurIkon, 'pengelola'), str_contains($jalurIkon, 'siswa') => 'users',
                        str_contains($jalurIkon, 'profil') => 'user-round',
                        str_contains($jalurIkon, 'laporan') => 'chart-no-axes-combined',
                        str_contains($jalurIkon, 'referensi'), str_contains($jalurIkon, 'mapel') => 'library-big',
                        str_contains($jalurIkon, 'pengaturan'), str_contains($jalurIkon, 'identitas') => 'settings',
                        default => 'layout-dashboard',
                    };
                ?>
                <a class="a-side__tautan<?= ! empty($tautan['aktif']) ? ' is-aktif' : '' ?>" title="<?= esc($tautan['label'], 'attr') ?>" aria-label="<?= esc($tautan['label'], 'attr') ?>"
                   href="<?= esc(site_url(ltrim((string) $tautan['path'], '/')), 'attr') ?>"
                   <?= ! empty($tautan['aktif']) ? 'aria-current="page"' : '' ?>>
                    <svg class="a-icon" aria-hidden="true"><use href="<?= esc(base_url('public/assets/icons/lucide-sla.svg'), 'attr') ?>#<?= esc($ikonMenu, 'attr') ?>"></use></svg>
                    <span class="a-side__teks"><?= esc($tautan['label']) ?></span>
                </a>
            <?php endforeach ?>
        </nav>
    </aside>
    <button type="button" class="a-backdrop" data-menu-close hidden aria-label="Tutup navigasi"></button>
    <div class="a-main">
        <header class="a-header">
            <button type="button" class="a-btn a-btn--sekunder a-menu-toggle"
                    data-menu-toggle aria-controls="navigasi-panel" aria-expanded="false">
                <svg class="a-icon" aria-hidden="true"><use href="<?= esc(base_url('public/assets/icons/lucide-sla.svg'), 'attr') ?>#menu"></use></svg> Menu
            </button>
            <div class="a-header__pengguna">
                <button type="button" class="a-btn a-btn--tema" data-theme-toggle aria-label="Aktifkan tema terang" title="Aktifkan tema terang" aria-pressed="false">
                    <svg class="a-icon a-tema__ikon--dark" aria-hidden="true"><use href="<?= esc(base_url('public/assets/icons/lucide-sla.svg'), 'attr') ?>#sun"></use></svg>
                    <svg class="a-icon a-tema__ikon--light" aria-hidden="true"><use href="<?= esc(base_url('public/assets/icons/lucide-sla.svg'), 'attr') ?>#moon"></use></svg>
                </button>
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
