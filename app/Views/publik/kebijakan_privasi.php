<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<section class="a-card a-baca" aria-labelledby="judul-privasi">
    <?php if (!empty($kebijakan)): ?>
        <span class="a-badge">Terbit · Berlaku</span>
        <h1 id="judul-privasi">Kebijakan Privasi</h1>
        <p class="a-help">Versi: <strong><?= esc((string)$kebijakan['versi']) ?></strong>
            · Berlaku sejak: <strong><?= esc((string)$kebijakan['tanggal_berlaku']) ?></strong> (WIB)</p>
        <div class="a-card" style="white-space:pre-wrap;overflow-wrap:anywhere">
            <?= esc((string)$kebijakan['teks']) ?>
        </div>
        <?php if (!empty($kontak)): ?>
        <section aria-labelledby="kontak-privasi">
            <h2 id="kontak-privasi">Kontak resmi madrasah</h2>
            <?php if (!empty($kontak['nama_madrasah'])): ?><p><?= esc((string)$kontak['nama_madrasah']) ?></p><?php endif ?>
            <?php if (!empty($kontak['telepon'])): ?><p>Telepon: <?= esc((string)$kontak['telepon']) ?></p><?php endif ?>
            <?php if (!empty($kontak['email'])): ?><p>Email: <?= esc((string)$kontak['email']) ?></p><?php endif ?>
        </section>
        <?php endif ?>
    <?php else: ?>
        <span class="a-badge a-badge--tunggu">Belum diterbitkan</span>
        <h1 id="judul-privasi">Kebijakan Privasi</h1>
        <div class="a-empty" role="status">
            <strong>Kebijakan resmi madrasah belum tersedia.</strong>
            <p>Admin perlu memasukkan naskah resmi yang telah disahkan, menentukan versi serta tanggal berlaku, dan menerbitkannya sebelum halaman publik dapat menampilkannya.</p>
            <p>Belum ada kebijakan yang dapat ditampilkan. Sistem tidak menerbitkan teks yang belum disahkan sekolah.</p>
        </div>
    <?php endif ?>
    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('masuk'),'attr') ?>">Kembali ke beranda</a>
</section>
<?= $this->endSection() ?>
