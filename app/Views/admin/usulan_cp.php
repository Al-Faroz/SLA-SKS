<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-belajar-guru">
    <header class="a-page__heading a-proto-heading"><div>
        <p class="a-proto-eyebrow">Admin · Keputusan CP</p><h1>Permohonan CP Guru</h1>
        <p class="a-proto-muted">Periksa sumber, versi asal, naskah yang diusulkan, dan alasan Guru sebelum memutus. Akun Admin+Guru tidak bisa memutus permohonan sendiri.</p>
    </div></header>
    <?php if($msg=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya"><?= esc($msg) ?></div><?php endif ?>
    <section class="a-card">
        <h2>Daftar usulan CP</h2>
        <?php if($daftar===[]): ?><div class="a-empty" role="status">Belum ada usulan CP dari Guru.</div>
        <?php else: ?>
            <div class="a-tabel-gulir"><table class="a-tbl">
                <thead><tr><th scope="col">Guru</th><th scope="col">CP / Mapel</th><th scope="col">Jenis</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                <tbody><?php foreach($daftar as $u): ?><tr>
                    <td><?= esc($u['pengusul_nama']) ?></td><td><?= esc($u['kode'].' · '.$u['mapel_nama']) ?></td>
                    <td><?= esc(str_replace('_',' ',$u['jenis'])) ?></td>
                    <td><span class="a-badge"><?= esc($u['status']) ?></span></td>
                    <td><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/usulan-cp/'.(int)$u['id']),'attr') ?>">Tinjau</a></td>
                </tr><?php endforeach ?></tbody>
            </table></div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
