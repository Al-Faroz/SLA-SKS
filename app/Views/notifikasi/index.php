<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-informasi">
    <nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url($peran),'attr') ?>">Beranda <?= esc($peranAktif) ?></a><span aria-hidden="true">/</span><span aria-current="page">Notifikasi</span></nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Informasi · Pribadi</p>
            <h1>Notifikasi Saya</h1>
            <p class="a-proto-muted">Hanya notifikasi untuk akun dan peran yang sedang aktif. Tautan ke kegiatan diperiksa kembali ketika dibuka.</p>
        </div>
    </header>
    <?php if ($pesan=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
    <?php if ($pesan=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <section class="a-card" aria-labelledby="daftar-notifikasi">
        <h2 id="daftar-notifikasi">Daftar Notifikasi</h2>
        <p class="a-help"><?= (int)$daftar['jumlah'] ?> notifikasi; <?= (int)$daftar['belum_dibaca'] ?> belum dibaca.</p>
        <?php if (!$daftar['baris']): ?>
            <div class="a-empty" role="status"><strong>Belum ada notifikasi untuk Anda.</strong><p>Notifikasi muncul setelah kegiatan akademik yang sah diselesaikan.</p></div>
        <?php else: ?>
            <div class="a-informasi__daftar">
            <?php foreach ($daftar['baris'] as $item): ?>
                <article class="a-card" aria-label="<?= esc($item['judul'],'attr') ?>">
                    <div class="a-flex a-flex--antara">
                        <h3><?= esc($item['judul']) ?></h3>
                        <span class="a-badge"><?= $item['dibaca_pada']===null?'Belum dibaca':'Sudah dibaca' ?></span>
                    </div>
                    <p><?= esc($item['pesan']) ?></p>
                    <p class="a-help">Dibuat (UTC): <?= esc((string)$item['dibuat_pada']) ?></p>
                    <div class="a-aksi a-aksi--kiri">
                        <?php if ($item['boleh_buka']): ?>
                            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('notifikasi/'.(int)$item['id'].'/buka'),'attr') ?>">Buka kegiatan</a>
                        <?php else: ?>
                            <span class="a-help">Akses kegiatan sudah berubah atau tidak tersedia.</span>
                        <?php endif ?>
                        <?php if ($item['dibaca_pada']===null && $item['boleh_buka']): ?>
                            <form method="post" action="<?= esc(site_url('notifikasi/'.(int)$item['id'].'/dibaca'),'attr') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="a-btn a-btn--utama">Tandai dibaca</button>
                            </form>
                        <?php endif ?>
                    </div>
                </article>
            <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
    <?php if ($daftar['total_halaman']>1): ?>
    <nav class="a-informasi__pagination" aria-label="Halaman notifikasi">
        <span>Halaman <?= (int)$daftar['halaman'] ?> dari <?= (int)$daftar['total_halaman'] ?></span>
        <?php if ($daftar['halaman']>1): ?><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('notifikasi').'?halaman='.($daftar['halaman']-1),'attr') ?>">Sebelumnya</a><?php endif ?>
        <?php if ($daftar['halaman']<$daftar['total_halaman']): ?><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('notifikasi').'?halaman='.($daftar['halaman']+1),'attr') ?>">Selanjutnya</a><?php endif ?>
    </nav>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
