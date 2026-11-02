<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb"><a href="<?= esc(site_url('admin'), 'attr') ?>">Admin</a> / Koreksi TP</nav>
    <header class="a-page__heading"><div><p class="a-proto-eyebrow">Admin · Persetujuan Akademik</p><h1>Permohonan Koreksi TP</h1>
        <p class="a-proto-muted">Tinjau perubahan versi baru sebelum diterbitkan; pengusul tidak boleh memutus permohonannya sendiri.</p></div></header>
    <?php if ($pesan = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <section class="a-card">
        <?php if (!$tersedia): ?>
            <div class="a-alert a-alert--bahaya">Modul koreksi belum terpasang. Impor SQL <code>V4-0006_KOREKSI_TP_SIAP.sql</code> melalui phpMyAdmin setelah backup.</div>
        <?php elseif ($daftar === []): ?>
            <p class="a-empty">Belum ada permohonan koreksi.</p>
        <?php else: ?>
            <div class="a-tabel-gulir"><table class="a-tbl"><thead><tr><th scope="col">Permohonan</th><th scope="col">TP</th><th scope="col">Pengusul</th><th scope="col">Kategori</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                <tbody><?php foreach ($daftar as $r): ?><tr>
                    <td>#<?= (int) $r['id'] ?></td><td><?= esc($r['kode_tp']) ?></td><td><?= esc($r['guru_nama']) ?></td>
                    <td><?= esc($r['kategori']) ?></td><td><span class="a-badge"><?= esc($r['status']) ?></span></td>
                    <td><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/koreksi-tp/' . (int) $r['id']), 'attr') ?>">Tinjau</a></td>
                </tr><?php endforeach ?></tbody>
            </table></div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>