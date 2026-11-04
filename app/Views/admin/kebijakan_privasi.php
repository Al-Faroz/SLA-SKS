<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-informasi">
    <nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url('admin'),'attr') ?>">Beranda Admin</a><span aria-hidden="true">/</span><span aria-current="page">Kebijakan Privasi</span></nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Admin · Dokumen Resmi</p>
            <h1>Kebijakan Privasi</h1>
            <p class="a-proto-muted">Isi hanya dengan teks Kebijakan Privasi yang telah disahkan madrasah. Draf tidak dapat dibaca publik; versi Terbit tidak dapat disunting atau dihapus.</p>
        </div>
        <a class="a-btn a-btn--utama" href="<?= esc(site_url('admin/kebijakan-privasi/baru'),'attr') ?>">Tambah Draf</a>
    </header>
    <?php if ($pesan=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
    <?php if ($pesan=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <section class="a-card">
        <h2>Riwayat Versi</h2>
        <p class="a-help">Penerbitan mengikuti tanggal berlaku WIB. Versi lampau tetap tersimpan dan tidak ditimpa.</p>
        <?php if (!$daftar): ?>
            <div class="a-empty" role="status"><strong>Belum ada kebijakan resmi.</strong><p>Siapkan dan periksa naskah yang telah disahkan madrasah terlebih dahulu.</p></div>
        <?php else: ?>
            <div class="a-tabel-gulir" tabindex="0" aria-label="Riwayat Kebijakan Privasi">
                <table class="a-tabel">
                    <thead><tr><th>Versi</th><th>Tanggal berlaku</th><th>Status</th><th>Revisi draf</th><th>Diterbitkan (UTC)</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($daftar as $item): ?>
                        <tr>
                            <td><?= esc($item['versi']) ?></td>
                            <td><?= esc($item['tanggal_berlaku']) ?></td>
                            <td><span class="a-badge"><?= esc(ucfirst((string)$item['status'])) ?></span></td>
                            <td><?= (int)$item['revisi_draf'] ?></td>
                            <td><?= esc((string)($item['diterbitkan_pada']??'—')) ?></td>
                            <td><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kebijakan-privasi/'.(int)$item['id']),'attr') ?>"><?= $item['status']==='draf'?'Sunting/terbitkan':'Lihat versi' ?></a></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>
    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('kebijakan-privasi'),'attr') ?>" target="_blank" rel="noopener">Pratinjau halaman publik</a>
</div>
<?= $this->endSection() ?>
