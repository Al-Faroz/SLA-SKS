<?= $this->extend('layouts/publik') ?>
<?= $this->section('konten') ?>
<div class="a-halaman-awal">
    <section class="a-intro" aria-labelledby="judul-halaman">
        <span class="a-kicker">Layanan Akademik · Sistem Kredit Semester</span>
        <h1 id="judul-halaman">Belajar mandiri, terarah, dan bertahap.</h1>
        <p>SLA-SKS adalah lingkungan pembelajaran MTs Fase D untuk Siswa, Guru, Admin, dan Pimpinan.</p>
        <p class="a-help">Akun dibuat dan dikelola oleh madrasah. Pendaftaran mandiri tidak tersedia.</p>
    </section>
    <section class="a-card a-akses" aria-labelledby="judul-akses">
        <span class="a-badge a-badge--tunggu">Fondasi K0</span>
        <h2 id="judul-akses">Akses akun belum dibuka</h2>
        <p>Kerangka aplikasi sudah tersedia. Fitur login sedang disiapkan bersama autentikasi dan struktur database agar akses setiap pengguna aman.</p>
        <div class="a-alert a-alert--info" role="status">
            <strong>Belum dapat masuk.</strong>
            <span>Form login tidak ditampilkan sebelum pemeriksaan akun dan keamanan server benar-benar berfungsi.</span>
        </div>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('kebijakan-privasi'), 'attr') ?>">Lihat status Kebijakan Privasi</a>
    </section>
</div>
<?= $this->endSection() ?>
