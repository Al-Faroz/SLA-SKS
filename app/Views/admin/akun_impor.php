<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $link = site_url('admin/' . $jenis); ?>
<div class="a-admin-akun a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc($link, 'attr') ?>"><?= esc(ucfirst($jenis)) ?></a>
        <span aria-hidden="true">/</span><span aria-current="page">Impor XLSX</span>
    </nav>
    <section class="a-card a-impor-akun" aria-labelledby="judul-impor">
        <p class="a-proto-eyebrow">Administrasi · <?= esc(ucfirst($jenis)) ?></p>
        <h1 id="judul-impor">Impor akun melalui Excel</h1>
        <p class="a-help">Unduh template terbaru, isi data akun, lalu unggah untuk pratinjau. Tidak ada akun yang berubah sampai Anda melakukan konfirmasi.</p>

        <?php if ($galat = session()->getFlashdata('galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
        <?php endif ?>

        <div class="a-impor-akun__petunjuk">
            <h2>Sebelum mengunggah</h2>
            <ol>
                <li><a href="<?= esc(site_url('admin/' . $jenis . '/template'), 'attr') ?>">Unduh template <?= esc(ucfirst($jenis)) ?> (.xlsx)</a>, lalu gunakan header dan kedua sheet tanpa perubahan.</li>
                <li>Isi maksimal 300 akun. Untuk akun baru wajib sandi awal minimal 8 karakter; untuk akun lama, kosongkan sandi awal.</li>
                <li>Periksa kode Gen<?= $jenis === 'siswa' ? ' dan NISN tepat 10 angka' : ' bila mengimpor Siswa' ?>. Pengelola memakai peran yang sah.</li>
                <li>Tinjau jumlah akun baru, perubahan dan penolakan sebelum menekan konfirmasi. Baris yang tidak tercantum tidak akan dihapus.</li>
            </ol>
        </div>

        <form class="a-form" method="post" enctype="multipart/form-data"
            action="<?= esc(site_url('admin/' . $jenis . '/impor'), 'attr') ?>">
            <?= csrf_field() ?>
            <div class="a-field">
                <label class="a-label" for="berkas-impor">Pilih file XLSX</label>
                <input id="berkas-impor" name="berkas" type="file"
                    accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                <p class="a-help">Batas 1 MB, maksimal 300 baris. Pratinjau berlaku 30 menit. File tidak disimpan ke folder publik.</p>
            </div>
            <div class="a-impor-akun__aksi">
                <button class="a-btn a-btn--utama" type="submit">Periksa dan tampilkan pratinjau</button>
                <a class="a-btn a-btn--sekunder" href="<?= esc($link, 'attr') ?>">Batal</a>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
