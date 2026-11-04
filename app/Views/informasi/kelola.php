<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $nama = $jenis === 'faq' ? 'FAQ resmi' : 'Pengumuman'; ?>
<div class="a-page a-informasi">
<nav class="a-breadcrumb" aria-label="Breadcrumb">
    <a href="<?= esc(site_url($peran),'attr') ?>">Beranda <?= esc($peranAktif) ?></a>
    <span aria-hidden="true">/</span><span aria-current="page">Kelola <?= esc($nama) ?></span>
</nav>
<header class="a-page__heading a-proto-heading">
    <div>
        <p class="a-proto-eyebrow">Informasi · Pengelolaan</p>
        <h1>Kelola <?= esc($nama) ?></h1>
        <p class="a-proto-muted"><?= $jenis === 'faq'
            ? 'Admin mengatur pertanyaan resmi untuk Pusat Bantuan. Draf tidak terlihat pengguna.'
            : ($peran === 'guru'
                ? 'Pengumuman Anda hanya muncul bagi Siswa di Gen yang saat ini Anda ampu. Guru lain dan Pimpinan tidak membacanya. Admin tetap dapat mengelola seluruh pengumuman.'
                : 'Pengumuman yang diterbitkan sebagai Admin berlaku global. Pengumuman yang dibuat Guru hanya dibaca Siswa pada Gen yang diampu Guru tersebut; Admin dapat mengelola semuanya.') ?></p>
    </div>
    <a class="a-btn a-btn--utama" href="<?= esc(site_url($prefix.'/tambah'),'attr') ?>">Tambah <?= esc($nama) ?></a>
</header>
<?php if ($pesan=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
<?php if ($pesan=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
<section class="a-card" aria-labelledby="daftar-informasi">
    <h2 id="daftar-informasi">Daftar <?= esc($nama) ?></h2>
    <p class="a-help"><?= (int)$daftar['jumlah'] ?> item sesuai hak akses. Status Draf tidak akan tampil kepada pembaca.</p>
    <?php if (!$daftar['data']): ?>
        <div class="a-informasi__kosong" role="status">Belum ada data. Gunakan tombol Tambah untuk membuat <?= esc($nama) ?> pertama.</div>
    <?php else: ?>
        <div class="a-tabel-gulir" tabindex="0" aria-label="Tabel kelola informasi">
            <table class="a-tabel">
                <thead><tr>
                    <th scope="col">Judul / pertanyaan</th><th scope="col">Status</th>
                    <?php if ($jenis === 'faq'): ?><th scope="col">Urutan</th><?php endif ?>
                    <th scope="col">Versi</th><th scope="col">Diperbarui (UTC)</th>
                    <th scope="col">Aksi</th>
                </tr></thead>
                <tbody>
                <?php foreach ($daftar['data'] as $item): ?>
                    <tr>
                        <td><?= esc($jenis === 'faq' ? $item['pertanyaan'] : $item['judul']) ?></td>
                        <td><span class="a-badge"><?= esc(ucfirst((string)$item['status'])) ?></span></td>
                        <?php if ($jenis === 'faq'): ?><td><?= (int)$item['urutan'] ?></td><?php endif ?>
                        <td><?= (int)$item['versi'] ?></td>
                        <td><?= esc($item['diubah_pada']) ?></td>
                        <td>
                            <?php if ($item['status'] !== 'arsip'): ?>
                                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($prefix.'/'.(int)$item['id'].'/sunting'),'attr') ?>">Sunting</a>
                                <form method="post" class="a-informasi__arsip"
                                      action="<?= esc(site_url($prefix.'/'.(int)$item['id'].'/arsip'),'attr') ?>"
                                      onsubmit="return confirm('Arsipkan informasi ini? Tindakan ini tidak bisa dibatalkan.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="versi" value="<?= (int)$item['versi'] ?>">
                                    <button type="submit" class="a-btn a-btn--bahaya">Arsipkan</button>
                                </form>
                            <?php else: ?>
                                <span class="a-help">Arsip dipertahankan</span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</section>
<?php if ($daftar['total_halaman'] > 1): ?>
<nav class="a-informasi__pagination" aria-label="Paginasi kelola informasi">
    <span>Halaman <?= (int)$daftar['halaman'] ?> dari <?= (int)$daftar['total_halaman'] ?></span>
    <?php if ($daftar['halaman'] > 1): ?>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($prefix).'?halaman='.($daftar['halaman']-1),'attr') ?>">Sebelumnya</a>
    <?php endif ?>
    <?php if ($daftar['halaman'] < $daftar['total_halaman']): ?>
        <a class="a-btn a-btn--sekunder" href="<?= esc(site_url($prefix).'?halaman='.($daftar['halaman']+1),'attr') ?>">Selanjutnya</a>
    <?php endif ?>
</nav>
<?php endif ?>
</div>
<?= $this->endSection() ?>
