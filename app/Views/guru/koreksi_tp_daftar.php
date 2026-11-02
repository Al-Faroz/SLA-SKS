<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-koreksi-tp">
    <nav class="a-breadcrumb"><a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>">Kelola TP</a> / Koreksi TP Siap</nav>
    <header class="a-page__heading"><div><p class="a-proto-eyebrow">Guru · Koreksi akademik</p>
        <h1>Koreksi TP Siap</h1><p class="a-proto-muted"><?= esc($tp['kode_tp'] . ' — ' . $tp['judul']) ?></p></div></header>
    <?php if ($pesan = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <?php if ($pesan = session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
    <?php if (!$tersedia): ?>
        <section class="a-card"><h2>Modul koreksi perlu dipasang</h2>
            <p>Admin wajib mengimpor <code>database/sql/updates/V4-0006_KOREKSI_TP_SIAP.sql</code> ke database lokal melalui phpMyAdmin. Tidak perlu impor ulang dump.</p></section>
    <?php else: ?>
        <section class="a-card">
            <h2>Buat permohonan koreksi</h2>
            <p class="a-help">TP tetap Siap dan tidak berubah selama penyusunan usulan. Pilih kategori A/B/C sesuai ketentuan akademik; kategori D dilarang. Admin meninjau perubahan sebelum versi baru diterbitkan.</p>
            <?php $adaTerbuka = count(array_filter($riwayat, static fn ($r) => in_array($r['status'], ['draf','diajukan'], true))) > 0; ?>
            <?php if ($adaTerbuka): ?>
                <div class="a-alert" role="status">Masih ada permohonan draf atau menunggu Admin. Selesaikan permohonan itu sebelum membuat yang baru.</div>
            <?php else: ?>
                <form method="post" action="<?= esc(site_url('guru/tp/' . (int) $tp['id'] . '/koreksi'), 'attr') ?>" class="a-form">
                    <?= csrf_field() ?>
                    <div class="a-field"><label class="a-label" for="kategori">Kategori koreksi</label>
                        <select id="kategori" name="kategori" required><option value="">Pilih kategori</option>
                            <?php foreach (['A','B','C'] as $kategori): ?><option value="<?= $kategori ?>"><?= $kategori ?></option><?php endforeach ?>
                        </select></div>
                    <div class="a-field"><label class="a-label" for="alasan">Alasan koreksi</label>
                        <textarea id="alasan" name="alasan" rows="3" minlength="10" maxlength="500" required><?= esc(old('alasan', '')) ?></textarea></div>
                    <button type="submit" class="a-btn a-btn--utama">Buat Draf Koreksi</button>
                </form>
            <?php endif ?>
        </section>
        <section class="a-card a-jarak-atas"><h2>Riwayat permohonan TP</h2>
            <?php if ($riwayat === []): ?><p class="a-empty">Belum ada permohonan.</p>
            <?php else: ?>
                <div class="a-tabel-gulir"><table class="a-tbl"><thead><tr><th scope="col">Nomor</th><th scope="col">Kategori</th><th scope="col">Alasan</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                    <tbody><?php foreach ($riwayat as $r): ?><tr>
                        <td>#<?= (int) $r['id'] ?></td><td><?= esc($r['kategori']) ?></td><td><?= esc($r['alasan']) ?></td>
                        <td><span class="a-badge"><?= esc(ucfirst($r['status'])) ?></span></td>
                        <td><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/koreksi-tp/' . (int) $r['id']), 'attr') ?>"><?= $r['status'] === 'draf' ? 'Lanjut Sunting' : 'Lihat Keputusan' ?></a></td>
                    </tr><?php endforeach ?></tbody></table></div>
            <?php endif ?>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>