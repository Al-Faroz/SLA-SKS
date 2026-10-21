<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
<nav class="a-breadcrumb" aria-label="Breadcrumb"><a href="<?= esc(site_url('admin'), 'attr') ?>">Beranda Admin</a><span aria-hidden="true">/</span><span aria-current="page">Gen &amp; Tahun Ajaran</span></nav>
<header class="a-page__heading a-proto-heading">
    <div>
        <p class="a-proto-eyebrow">Admin · Referensi Akademik</p>
        <h1>Gen &amp; Tahun Ajaran</h1>
        <p class="a-proto-muted">Kelola data Gen dan tahun ajaran. Semester sebelum titik mulai Gen dianggap Dilewati; data referensi yang sudah digunakan tetap dipertahankan.</p>
    </div>
</header>
<?php if ($pesan = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
<?php if ($pesan = session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
<div class="a-dua-kolom">
<section class="a-card a-page__editor" aria-labelledby="judul-gen">
    <h2 id="judul-gen">Tambahkan Gen</h2>
    <form method="post" action="<?= esc(site_url('admin/referensi/gen'), 'attr') ?>" class="a-form">
        <?= csrf_field() ?>
        <div class="a-field"><label class="a-label" for="kode-gen">Kode Gen</label><input id="kode-gen" name="kode" type="text" maxlength="20" pattern="[A-Za-z0-9._-]{2,20}" value="<?= esc(old('kode', ''), 'attr') ?>" required aria-describedby="bantu-gen"><span id="bantu-gen" class="a-help">Contoh: GEN2026. Kode harus unik.</span></div>
        <div class="a-field"><label class="a-label" for="nama-gen">Nama Gen</label><input id="nama-gen" name="nama" type="text" maxlength="100" value="<?= esc(old('nama', ''), 'attr') ?>" required></div>
        <div class="a-field"><label class="a-label" for="semester-mulai">Semester mulai</label>
            <select name="semester_mulai" id="semester-mulai" required>
                <?php foreach ([1 => '1 · VII Ganjil', 2 => '2 · VII Genap', 3 => '3 · VIII Ganjil', 4 => '4 · VIII Genap', 5 => '5 · IX Ganjil', 6 => '6 · IX Genap'] as $nomor => $teks): ?>
                <option value="<?= $nomor ?>" <?= (int) old('semester_mulai', '1') === $nomor ? 'selected' : '' ?>><?= esc($teks) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <button class="a-btn a-btn--utama" type="submit">Tambahkan Gen</button>
    </form>
</section>
<section class="a-card a-page__editor" aria-labelledby="judul-tahun">
    <h2 id="judul-tahun">Tambahkan tahun ajaran</h2>
    <form method="post" action="<?= esc(site_url('admin/referensi/tahun-ajaran'), 'attr') ?>" class="a-form">
        <?= csrf_field() ?>
        <div class="a-field"><label class="a-label" for="tahun-ajaran">Tahun ajaran</label><input id="tahun-ajaran" name="tahun_ajaran" type="text" inputmode="numeric" maxlength="9" placeholder="2026/2027" pattern="[0-9]{4}/[0-9]{4}" value="<?= esc(old('tahun_ajaran', ''), 'attr') ?>" required><span class="a-help">Gunakan tahun ajaran yang benar-benar berlaku di madrasah.</span></div>
        <button class="a-btn a-btn--utama" type="submit">Tambahkan tahun ajaran</button>
    </form>
</section>
</div>
<div class="a-dua-kolom a-jarak-atas a-page__daftar">
<section class="a-card" aria-labelledby="judul-daftar-gen"><h2 id="judul-daftar-gen">Daftar Gen</h2>
    <?php if ($angkatan === []): ?><div class="a-empty" role="status"><strong>Belum ada Gen.</strong><p>Isi formulir Tambahkan Gen di atas.</p></div>
    <?php else: ?><div class="a-tabel-gulir"><table class="a-tbl"><thead><tr><th scope="col">Kode</th><th scope="col">Nama</th><th scope="col">Semester mulai</th></tr></thead><tbody>
    <?php foreach ($angkatan as $item): ?><tr><td><?= esc($item['kode']) ?></td><td><?= esc($item['nama']) ?></td><td><?= (int) $item['semester_mulai'] ?></td></tr><?php endforeach ?>
    </tbody></table></div><?php endif ?>
</section>
<section class="a-card" aria-labelledby="judul-daftar-tahun"><h2 id="judul-daftar-tahun">Daftar tahun ajaran</h2>
    <?php if ($tahun === []): ?><div class="a-empty" role="status"><strong>Belum ada tahun ajaran.</strong><p>Isi formulir Tambahkan tahun ajaran di atas.</p></div>
    <?php else: ?><div class="a-tabel-gulir"><table class="a-tbl"><thead><tr><th scope="col">Tahun ajaran</th></tr></thead><tbody>
    <?php foreach ($tahun as $item): ?><tr><td><?= esc($item['nama']) ?></td></tr><?php endforeach ?>
    </tbody></table></div><?php endif ?>
</section>
</div>
</div>
<?= $this->endSection() ?>
