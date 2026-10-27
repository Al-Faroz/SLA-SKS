<!doctype html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark light">
    <title><?= esc($judul ?? 'SLA-SKS') ?> · SLA-SKS</title>
    <?php
        $temaCssFile = FCPATH . 'public/assets/css/emerald-glass.css';
        $temaJsFile = FCPATH . 'public/assets/js/theme-init.js';
        $cssVersi = is_file(FCPATH . 'public/assets/css/app.css') ? (string) filemtime(FCPATH . 'public/assets/css/app.css') : '1';
        $temaCssVersi = is_file($temaCssFile) ? (string) filemtime($temaCssFile) : '1';
        $temaJsVersi = is_file($temaJsFile) ? (string) filemtime($temaJsFile) : '1';
    ?>
    <script src="<?= esc(base_url('public/assets/js/theme-init.js') . '?v=' . $temaJsVersi, 'attr') ?>"></script>
    <link rel="stylesheet" href="<?= esc(base_url('public/assets/css/app.css') . '?v=' . $cssVersi, 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(base_url('public/assets/css/emerald-glass.css') . '?v=' . $temaCssVersi, 'attr') ?>">
    <script defer src="<?= esc(base_url('public/assets/js/app.js'), 'attr') ?>"></script>
</head>
<body class="a-publik a-publik--prototipe">
<a class="a-lewati" href="#konten-utama">Lewati ke konten</a>
<div class="a-shell-publik">
    <header class="a-kepala-publik">
        <a class="a-identitas" href="<?= esc(site_url('masuk'), 'attr') ?>" aria-label="SLA-SKS, halaman awal">
            <span class="a-identitas__mark" aria-hidden="true">S</span>
            <span class="a-identitas__teks"><strong>SLA-SKS</strong><small>Sistem Layanan Akademik</small></span>
        </a>
        <nav class="a-navigasi-publik" aria-label="Navigasi publik">
            <a href="<?= esc(site_url('masuk'), 'attr') ?>">Beranda</a>
            <a href="<?= esc(site_url('kebijakan-privasi'), 'attr') ?>">Kebijakan Privasi</a>
            <button type="button" class="a-btn a-btn--tema" data-theme-toggle aria-label="Aktifkan tema terang" title="Aktifkan tema terang" aria-pressed="false">
                <svg class="a-icon a-tema__ikon--dark" aria-hidden="true"><use href="<?= esc(base_url('public/assets/icons/lucide-sla.svg'), 'attr') ?>#sun"></use></svg>
                <svg class="a-icon a-tema__ikon--light" aria-hidden="true"><use href="<?= esc(base_url('public/assets/icons/lucide-sla.svg'), 'attr') ?>#moon"></use></svg>
            </button>
        </nav>
    </header>
    <main id="konten-utama" class="a-konten-publik">
        <?= $this->renderSection('konten') ?>
    </main>
    <footer class="a-kaki-publik">
        <span>SLA-SKS · Pembelajaran mandiri MTs Fase D</span>
        <span>Akses pengguna dikelola madrasah.</span>
    </footer>
</div>
</body>
</html>
