<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-belajar-guru">
    <nav class="a-breadcrumb"><a href="<?= esc(site_url('guru/paket'),'attr') ?>">Paket Semester</a> / Detail</nav>
    <header class="a-page__heading a-proto-heading"><div>
        <p class="a-proto-eyebrow">Guru · Rencana BAB</p><h1>Paket Semester <?= (int)$paket['semester'] ?></h1>
        <p class="a-proto-muted">Gen ID <?= (int)$paket['angkatan_id'] ?> · Mapel ID <?= (int)$paket['mapel_id'] ?> · Paket #<?= (int)$paket['id'] ?> · <?= esc(ucfirst($paket['status'])) ?></p>
    </div></header>
    <?php if($msg=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($msg) ?></div><?php endif ?>
    <?php if($msg=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($msg) ?></div><?php endif ?>
    <section class="a-card"><h2>Tambah BAB sesuai rencana semester</h2>
        <p class="a-help">Urutan BAB dibuat otomatis 1, 2, 3 dan seterusnya. BAB Draf boleh dilengkapi setelah BAB sebelumnya Siap.</p>
        <form method="post" action="<?= esc(site_url('guru/paket/'.(int)$paket['id'].'/bab'),'attr') ?>" class="a-form">
            <?= csrf_field() ?>
            <div class="a-field"><label class="a-label" for="judul-bab">Judul BAB</label>
                <input id="judul-bab" type="text" name="judul" minlength="4" maxlength="180" placeholder="Contoh: Konsep Bilangan" required></div>
            <button class="a-btn a-btn--utama" type="submit">Tambahkan BAB draf</button>
        </form>
    </section>
    <section class="a-card"><h2>Urutan BAB Paket</h2>
        <?php if($bab===[]): ?><div class="a-empty" role="status">Belum ada BAB. Buat BAB pertama untuk memulai struktur TP.</div>
        <?php else: ?>
            <div class="a-belajar-guru__list">
                <?php foreach($bab as $b): ?>
                <article class="a-belajar-guru__item">
                    <div><strong><?= (int)$b['urutan'] ?>. <?= esc($b['judul']) ?></strong>
                        <p class="a-help">Status <span class="a-badge"><?= esc($b['status']==='siap'?'Siap/Terkunci':'Draf') ?></span></p></div>
                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/bab/'.(int)$b['id']),'attr') ?>"><?= $b['status']==='siap'?'Lihat BAB':'Lengkapi BAB' ?></a>
                </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
