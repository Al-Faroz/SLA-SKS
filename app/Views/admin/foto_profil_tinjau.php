<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-profil a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin/pengelola'), 'attr') ?>">Pengelola</a>
        <span aria-hidden="true">/</span><span aria-current="page">Persetujuan foto</span>
    </nav>
    <header class="a-proto-heading a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Admin · Foto profil</p>
            <h1>Persetujuan foto profil</h1>
            <p class="a-proto-muted">Foto yang disetujui dapat terlihat oleh Siswa hanya jika pengunggahnya merupakan Guru aktif yang mengampu Gen siswa tersebut.</p>
        </div>
    </header>
    <?php if ($galat = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>
    <?php if ($sukses = session()->getFlashdata('sukses')): ?>
        <div class="a-alert" role="status"><?= esc($sukses) ?></div>
    <?php endif ?>
    <section class="a-card a-profil__tinjau" aria-labelledby="judul-tinjau">
        <h2 id="judul-tinjau">Foto yang menunggu persetujuan</h2>
        <?php if ($daftarFoto === []): ?>
            <div class="a-empty" role="status"><strong>Tidak ada foto menunggu.</strong><p>Unggahan foto staf baru akan ditampilkan di sini.</p></div>
        <?php else: ?>
            <div class="a-profil__guru-grid">
                <?php foreach ($daftarFoto as $foto): ?>
                    <article class="a-profil__tinjau-card">
                        <img src="<?= esc(site_url('profil/foto/' . (int) $foto['id']), 'attr') ?>"
                            alt="Foto menunggu persetujuan milik <?= esc($foto['nama'], 'attr') ?>"
                            loading="lazy" width="152" height="152">
                        <div>
                            <strong><?= esc($foto['nama']) ?></strong>
                            <p class="a-help">Pengguna ID <?= (int) $foto['pengguna_id'] ?> · Foto ID <?= (int) $foto['id'] ?></p>
                            <form method="post" action="<?= esc(site_url('admin/foto-profil/' . (int) $foto['id'] . '/setujui'), 'attr') ?>">
                                <?= csrf_field() ?>
                                <button class="a-btn a-btn--utama" type="submit"
                                    aria-label="Setujui foto <?= esc($foto['nama'], 'attr') ?>">Setujui foto ini</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
