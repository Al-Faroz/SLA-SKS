<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-sertifikat">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'),'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span><span aria-current="page">Sertifikat</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · Sertifikat Pembelajaran</p>
            <h1>Sertifikat Saya</h1>
            <p class="a-proto-muted">Unduh sertifikat yang sudah diterbitkan Admin.
                Versi lama tetap tersedia sebagai arsip jika ada revisi.</p>
        </div>
    </header>
    <section class="a-card a-jarak-atas" aria-labelledby="daftar-sertifikat">
        <h2 id="daftar-sertifikat">Daftar Sertifikat</h2>
        <?php if ($sertifikat===[]): ?>
            <div class="a-alert" role="status">Belum ada sertifikat yang diterbitkan untuk akun ini.
                Sertifikat tidak harus menunggu hasil Pra-Semester.</div>
        <?php else: ?>
            <div class="a-tabel-gulir a-sertifikat__scroll" role="region" tabindex="0"
                aria-label="Daftar sertifikat dapat digulir">
                <table class="a-tbl a-sertifikat__table">
                    <thead><tr><th scope="col">Nomor Sertifikat</th>
                        <th scope="col">Mata Pelajaran / Semester</th>
                        <th scope="col">Versi</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($sertifikat as $s): ?>
                        <tr><th scope="row"><?= esc($s['nomor']) ?></th>
                            <td><?= esc($s['nama_mapel']) ?> · Semester <?= (int)$s['semester'] ?></td>
                            <td><?= (int)$s['versi'] ?></td>
                            <td><span class="a-sertifikat__badge <?= $s['status']==='berlaku'
                                ? 'a-sertifikat__badge--layak' : 'a-sertifikat__badge--tahan' ?>">
                                <?= $s['status']==='berlaku'?'Berlaku':'Digantikan' ?></span></td>
                            <td><a class="a-btn a-btn--sekunder" href="<?= esc(site_url(
                                'siswa/sertifikat/'.(int)$s['id'].'/unduh'),'attr') ?>">
                                Unduh PDF</a></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
