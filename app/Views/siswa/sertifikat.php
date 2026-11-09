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
            <p class="a-proto-muted">Sertifikat diperbarui otomatis sesuai nilai TP terbaru.</p>
        </div>
    </header>
    <?php if (($otomatis['diperbarui']??0)>0): ?>
        <div class="a-alert" role="status">
            <?= (int)$otomatis['diperbarui'] ?> sertifikat diterbitkan atau diperbarui otomatis.
        </div>
    <?php endif ?>
    <?php if (($otomatis['galat']??0)>0): ?>
        <div class="a-alert a-alert--bahaya" role="alert">
            Sebagian sertifikat belum dapat diperbarui. Periksa kembali nanti atau laporkan kepada pengelola.
        </div>
    <?php endif ?>
    <section class="a-card a-jarak-atas" aria-labelledby="daftar-sertifikat">
        <h2 id="daftar-sertifikat">Daftar Sertifikat</h2>
        <?php if ($sertifikat===[]): ?>
            <div class="a-alert" role="status">Belum ada sertifikat yang tersedia. Selesaikan TP NonEsensial dan
                tunggu seluruh penilaian wajib Final; identitas madrasah serta TTD harus siap.
                Hasil Pra-Semester bukan syarat penerbitan.</div>
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
                                'siswa/sertifikat/'.(int)$s['id'].'/unduh'),'attr') ?>"
                                aria-label="Unduh PDF sertifikat <?= esc((string)$s['nomor'],'attr') ?>">
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
