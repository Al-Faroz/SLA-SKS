<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url('admin'), 'attr') ?>">Beranda Admin</a><span aria-hidden="true">/</span><span aria-current="page">Mapel &amp; Guru</span></nav>
<h1>Mata Pelajaran &amp; Guru Pengampu (G1)</h1>
<p class="a-help">Setiap mata pelajaran hanya memiliki satu Guru pengampu aktif untuk semua Gen dan semester. Pergantian Guru dicatat; guru yang dinonaktifkan tidak dapat dipilih sebagai pengampu baru.</p>
<?php if ($pesan = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
<?php if ($pesan = session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
<section class="a-card a-baca-panel" aria-labelledby="judul-tambah-mapel">
<h2 id="judul-tambah-mapel">Tambahkan mata pelajaran</h2>
<form class="a-form" method="post" action="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">
    <?= csrf_field() ?>
    <div class="a-field"><label class="a-label" for="kode-mapel">Kode mapel</label><input id="kode-mapel" name="kode" type="text" maxlength="24" pattern="[A-Za-z0-9._-]{2,24}" required value="<?= esc(old('kode', ''), 'attr') ?>"></div>
    <div class="a-field"><label class="a-label" for="nama-mapel">Nama mata pelajaran</label><input id="nama-mapel" name="nama" type="text" maxlength="140" required value="<?= esc(old('nama', ''), 'attr') ?>"></div>
    <button class="a-btn a-btn--utama" type="submit">Tambahkan mapel</button>
</form>
</section>
<section class="a-card a-jarak-atas" aria-labelledby="judul-pengampu">
<h2 id="judul-pengampu">Penetapan pengampu</h2>
<?php if ($mapel === []): ?>
<div class="a-empty" role="status"><strong>Belum ada mata pelajaran.</strong><p>Tambahkan mapel terlebih dahulu agar bisa menetapkan Guru.</p></div>
<?php else: ?>
<?php if ($guru === []): ?><p class="a-alert a-alert--info" role="status">Belum ada akun aktif dengan peran Guru. Tambahkan melalui menu Pengguna sebelum menetapkan G1.</p><?php endif ?>
<div class="a-tabel-gulir"><table class="a-tbl a-tbl--luas">
<thead><tr><th scope="col">Kode / Mapel</th><th scope="col">Pengampu saat ini</th><th scope="col">Ubah penugasan</th></tr></thead>
<tbody>
<?php foreach ($mapel as $item): ?>
<tr>
    <td><strong><?= esc($item['kode']) ?></strong><div><?= esc($item['nama']) ?></div></td>
    <td><?= $item['guru_id'] === null ? '<span class="a-badge a-badge--tunggu">Belum ditugaskan</span>' : esc($item['guru_nama']) ?></td>
    <td>
        <form method="post" action="<?= esc(site_url('admin/mapel-guru/' . (int) $item['id'] . '/guru'), 'attr') ?>" class="a-form a-form-penugasan">
            <?= csrf_field() ?>
            <div class="a-field"><label class="a-label" for="guru-mapel-<?= (int) $item['id'] ?>">Guru pengampu</label>
                <select id="guru-mapel-<?= (int) $item['id'] ?>" name="guru_id">
                    <option value="">Belum ada pengampu</option>
                    <?php foreach ($guru as $akun): ?>
                    <option value="<?= (int) $akun['id'] ?>" <?= (int) ($item['guru_id'] ?? 0) === (int) $akun['id'] ? 'selected' : '' ?>><?= esc($akun['nama']) ?> (<?= esc($akun['username']) ?>)</option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="a-field"><label class="a-label" for="alasan-mapel-<?= (int) $item['id'] ?>">Alasan penetapan/pergantian</label><input id="alasan-mapel-<?= (int) $item['id'] ?>" name="alasan" type="text" minlength="5" maxlength="255" required></div>
            <button class="a-btn a-btn--sekunder" type="submit">Simpan pengampu</button>
        </form>
    </td>
</tr>
<?php endforeach ?>
</tbody></table></div>
<?php endif ?>
</section>
<section class="a-card a-jarak-atas" aria-labelledby="judul-riwayat">
<h2 id="judul-riwayat">Riwayat pergantian Guru</h2>
<?php if ($riwayat === []): ?><div class="a-empty" role="status">Belum ada penetapan atau pergantian Guru.</div>
<?php else: ?><p class="a-help">Menampilkan 25 catatan terbaru, seluruh riwayat tetap tersimpan di database.</p>
<div class="a-tabel-gulir"><table class="a-tbl"><thead><tr><th scope="col">Mapel</th><th scope="col">Sebelum → Sesudah</th><th scope="col">Alasan</th><th scope="col">Waktu (UTC)</th></tr></thead><tbody>
<?php foreach ($riwayat as $item): ?><tr><td><?= esc($item['mapel_nama']) ?></td><td><?= esc($item['guru_lama'] ?? 'Belum ada') ?> → <?= esc($item['guru_baru'] ?? 'Belum ada') ?></td><td><?= esc($item['alasan']) ?></td><td><?= esc($item['waktu']) ?></td></tr><?php endforeach ?>
</tbody></table></div><?php endif ?>
</section>
<?= $this->endSection() ?>
