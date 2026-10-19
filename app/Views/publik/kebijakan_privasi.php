<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<section class="a-card a-baca" aria-labelledby="judul-privasi">
    <span class="a-badge a-badge--tunggu">Belum diterbitkan</span>
    <h1 id="judul-privasi">Kebijakan Privasi</h1>
    <div class="a-empty" role="status">
        <strong>Kebijakan resmi madrasah belum tersedia.</strong>
        <p>Halaman ini akan menampilkan teks, versi, tanggal berlaku, serta kontak resmi setelah disahkan dan diterbitkan oleh Admin madrasah.</p>
        <p>Belum ada kebijakan yang dapat ditampilkan. Sistem tidak akan menerbitkan teks kebijakan yang belum disahkan sekolah.</p>
    </div>
    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('masuk'), 'attr') ?>">Kembali ke beranda</a>
</section>
<?= $this->endSection() ?>
