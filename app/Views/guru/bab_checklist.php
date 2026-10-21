<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-belajar-guru">
    <nav class="a-breadcrumb"><a href="<?= esc(site_url('guru/bab/'.(int)$bab['id']),'attr') ?>">Editor BAB</a> / Checklist Siap</nav>
    <header class="a-page__heading a-proto-heading"><div>
        <p class="a-proto-eyebrow">Guru · Gerbang Publikasi</p><h1>Checklist BAB Siap</h1>
        <p class="a-proto-muted"><?= esc($bab['judul']) ?> · BAB #<?= (int)$bab['id'] ?> · Status <?= esc($bab['status']) ?></p>
    </div></header>
    <?php if($msg=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($msg) ?></div><?php endif ?>
    <?php if($msg=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($msg) ?></div><?php endif ?>
    <section class="a-card"><h2><?= $checklist['siap']?'Siap diterbitkan':'Masih ada syarat yang harus dipenuhi' ?></h2>
        <p class="a-help">Checklist ini diperiksa lagi dalam transaksi saat Anda menekan tombol terbitkan. Penerbitan mengunci BAB, TP, materi, berkas, soal, bobot, dan urutannya dari edit biasa.</p>
        <?php if($checklist['ringkasan']===[]): ?><div class="a-empty">Belum ada TP.</div><?php endif ?>
        <?php foreach($checklist['ringkasan'] as $row): ?>
            <div class="a-belajar-guru__subitem">
                <strong><?= esc($row['tp']) ?> · <?= (int)$row['materi'] ?> materi</strong>
                <?php if($row['masalah']===[]): ?><p class="a-badge">Memenuhi syarat</p>
                <?php else: ?>
                    <ul class="a-belajar-guru__masalah"><?php foreach($row['masalah'] as $msg): ?><li><?= esc($msg) ?></li><?php endforeach ?></ul>
                <?php endif ?>
            </div>
        <?php endforeach ?>
        <?php if($checklist['pesan']!==[]): ?>
            <div class="a-alert a-alert--bahaya" role="alert">
                <strong><?= count($checklist['pesan']) ?> masalah ditemukan.</strong>
                <ul><?php foreach($checklist['pesan'] as $msg): ?><li><?= esc($msg) ?></li><?php endforeach ?></ul>
            </div>
        <?php endif ?>
        <div class="a-belajar-guru__aksi">
            <?php if($bab['status']==='draf' && $checklist['siap']): ?>
                <form method="post" action="<?= esc(site_url('guru/bab/'.(int)$bab['id'].'/siap'),'attr') ?>">
                    <?= csrf_field() ?>
                    <button class="a-btn a-btn--utama" type="submit">Saya telah meninjau · Terbitkan BAB Siap</button>
                </form>
            <?php elseif($bab['status']==='siap'): ?>
                <span class="a-badge">Siap/Terkunci</span>
            <?php endif ?>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/bab/'.(int)$bab['id']),'attr') ?>">Kembali ke editor</a>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
