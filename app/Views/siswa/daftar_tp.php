<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('siswa'), 'attr') ?>">Beranda Siswa</a>
        <span aria-hidden="true">/</span><a href="<?= esc(site_url('siswa/pembelajaran'), 'attr') ?>">Mata Pelajaran</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('siswa/mapel/' . (int) $paket['mapel_id']), 'attr') ?>">Semester</a>
        <span aria-hidden="true">/</span><span aria-current="page">Daftar TP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Siswa · <?= esc((string) $paket['kode_mapel']) ?> · Gen <?= esc((string) $siswa['gen_kode']) ?></p>
            <h1>Semester <?= (int) $paket['semester'] ?> — <?= esc((string) $paket['nama_mapel']) ?></h1>
            <p class="a-proto-muted">TP dibuka sesuai urutan ATP. TP berikutnya menunggu
                penyelesaian TP sebelumnya; TP Draf menunggu Guru menyiapkannya.</p>
        </div>
    </header>
    <section class="a-card">
        <h2>Tujuan Pembelajaran</h2>
        <?php if ($tp === []): ?>
            <div class="a-empty" role="status">
                <strong>Belum ada TP pada semester ini.</strong>
                <p>Guru sedang menyiapkan struktur pembelajaran.</p>
            </div>
        <?php else: ?>
            <div class="a-siswa-daftar-tp">
                <?php foreach ($tp as $item): ?>
                    <article class="a-siswa-kartu">
                        <div class="a-siswa-tp-heading">
                            <div>
                                <p class="a-proto-eyebrow">Urutan <?= (int) $item['urutan_atp'] ?> · <?= esc((string) $item['kode_tp']) ?></p>
                                <h3><?= esc((string) $item['judul']) ?></h3>
                            </div>
                            <span class="a-siswa-status <?= esc($item['akses']['kode'], 'attr') ?>"><?= esc($item['akses']['label']) ?></span>
                        </div>
                        <?php if ($item['akses']['buka']): ?>
                            <a class="a-btn a-btn--utama" href="<?= esc(site_url('siswa/tp/' . (int) $item['id']), 'attr') ?>">Lihat rincian TP</a>
                        <?php else: ?>
                            <p class="a-help">TP belum dapat dibuka. Ikuti urutan pembelajaran.</p>
                        <?php endif ?>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <p class="a-help a-jarak-atas">Halaman ini belum menjalankan Diagnostik,
            menghitung progres, ataupun menetapkan ketuntasan. Fitur tersebut akan tersedia
            setelah mesin alur pembelajaran diterapkan.</p>
    </section>
</div>
<?= $this->endSection() ?>
