<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= esc($judul ?? 'SLA-SKS') ?> · SLA-SKS</title>
    <link rel="stylesheet" href="<?= esc(base_url('public/assets/css/app.css'), 'attr') ?>">
    <script defer src="<?= esc(base_url('public/assets/js/app.js'), 'attr') ?>"></script>
</head>
<body class="a-publik<?= ! empty($uiPrototipe) ? ' a-publik--prototipe' : '' ?>">
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
