<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
<nav class="a-breadcrumb" aria-label="Breadcrumb">
    <a href="<?= esc(site_url('admin'), 'attr') ?>">Beranda Admin</a>
    <span aria-hidden="true">/</span><span aria-current="page">Mapel &amp; Guru per Gen</span>
</nav>
<header class="a-page__heading a-proto-heading">
    <div>
        <p class="a-proto-eyebrow">Admin · Data Akademik</p>
        <h1>Mata Pelajaran &amp; Guru per Gen</h1>
        <p class="a-proto-muted">Satu Guru aktif setiap pasangan Gen–Mapel; penetapan Guru pada satu Gen tidak mengubah penugasan Gen lain.</p>
    </div>
</header>
<p class="a-alert a-alert--info" role="status">Penugasan Guru dari K2 lama tidak disalin otomatis. Pilih Guru pada setiap Gen dan mapel sesuai keputusan madrasah. Data penugasan lama tetap tersimpan sebagai riwayat.</p>
<?php if ($pesan = session()->getFlashdata('galat')): ?>
    <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
<?php endif ?>
<?php if ($pesan = session()->getFlashdata('sukses')): ?>
    <div class="a-alert" role="status"><?= esc($pesan) ?></div>
<?php endif ?>

<section class="a-card a-page__editor" aria-labelledby="judul-buat-mapel">
    <h2 id="judul-buat-mapel">Tambahkan mata pelajaran</h2>
    <form class="a-form" method="post" action="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>">
        <?= csrf_field() ?>
        <div class="a-field"><label class="a-label" for="kode-mapel">Kode mapel</label>
            <input id="kode-mapel" name="kode" type="text" maxlength="24" pattern="[A-Za-z0-9._-]{2,24}" required value="<?= esc(old('kode', ''), 'attr') ?>">
        </div>
        <div class="a-field"><label class="a-label" for="nama-mapel">Nama mata pelajaran</label>
            <input id="nama-mapel" name="nama" type="text" maxlength="140" required value="<?= esc(old('nama', ''), 'attr') ?>">
        </div>
        <button class="a-btn a-btn--utama" type="submit">Tambahkan mapel</button>
    </form>
</section>

<section class="a-card a-jarak-atas" aria-labelledby="judul-pengampu">
    <h2 id="judul-pengampu">Penetapan Guru per Gen</h2>
    <?php if ($angkatan === []): ?>
        <div class="a-empty" role="status"><strong>Belum ada Gen.</strong>
            <p>Buat Gen terlebih dahulu melalui menu Gen &amp; Tahun Ajaran sebelum menetapkan Guru.</p>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/referensi'), 'attr') ?>">Buka Gen &amp; Tahun Ajaran</a>
        </div>
    <?php else: ?>
        <form method="get" action="<?= esc(site_url('admin/mapel-guru'), 'attr') ?>" class="a-form a-pilih-gen">
            <div class="a-field">
                <label class="a-label" for="pilih-gen">Gen yang akan dikelola</label>
                <select id="pilih-gen" name="gen" required>
                    <?php foreach ($angkatan as $gen): ?>
                        <option value="<?= (int) $gen['id'] ?>" <?= $angkatanAktif === (int) $gen['id'] ? 'selected' : '' ?>>
                            <?= esc($gen['nama']) ?> (<?= esc($gen['kode']) ?>)
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <button class="a-btn a-btn--sekunder" type="submit">Tampilkan Gen</button>
        </form>

        <?php if ($mapel === []): ?>
            <div class="a-empty" role="status"><strong>Belum ada mata pelajaran.</strong>
                <p>Tambahkan mata pelajaran melalui formulir di atas.</p>
            </div>
        <?php else: ?>
            <?php if ($guru === []): ?>
                <div class="a-alert a-alert--info" role="status">Belum ada akun aktif dengan peran Guru. Buat akun Guru melalui pengelolaan pengguna terlebih dahulu.</div>
            <?php endif ?>
            <div class="a-tabel-gulir"><table class="a-tbl a-tbl--cards a-tbl--penugasan">
                <thead><tr>
                    <th scope="col">Mata pelajaran</th>
                    <th scope="col">Guru pengampu Gen ini</th>
                    <th scope="col">Ubah pengampu</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($mapel as $item): ?>
                        <tr>
                            <th scope="row" data-label="Mata pelajaran"><strong><?= esc($item['kode']) ?></strong><div><?= esc($item['nama']) ?></div></th>
                            <td data-label="Guru pengampu"><?= $item['guru_id'] === null
                                ? '<span class="a-badge a-badge--tunggu">Belum ditugaskan</span>'
                                : esc($item['guru_nama'] ?? 'Guru tidak tersedia') ?></td>
                            <td data-label="Ubah pengampu">
                                <form method="post" class="a-form a-form-penugasan"
                                    action="<?= esc(site_url('admin/mapel-guru/' . (int) $item['id'] . '/gen/' . (int) $angkatanAktif . '/guru'), 'attr') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="halaman_mapel" value="<?= (int) $mapelHalaman ?>">
                                    <input type="hidden" name="halaman_riwayat" value="<?= (int) $riwayatHalaman ?>">
                                    <div class="a-field">
                                        <label class="a-label" for="guru-mapel-<?= (int) $item['id'] ?>">Guru pengampu</label>
                                        <select id="guru-mapel-<?= (int) $item['id'] ?>" name="guru_id">
                                            <option value="">Belum ada pengampu</option>
                                            <?php foreach ($guru as $akun): ?>
                                                <option value="<?= (int) $akun['id'] ?>" <?= (int) ($item['guru_id'] ?? 0) === (int) $akun['id'] ? 'selected' : '' ?>><?= esc($akun['nama']) ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                    <div class="a-field">
                                        <label class="a-label" for="alasan-mapel-<?= (int) $item['id'] ?>">Alasan penetapan/pergantian</label>
                                        <input id="alasan-mapel-<?= (int) $item['id'] ?>" name="alasan" type="text" minlength="5" maxlength="255" required>
                                    </div>
                                    <button class="a-btn a-btn--sekunder" type="submit">Simpan pengampu</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table></div>
            <?= view('components/pager', [
                'urlPath' => 'admin/mapel-guru',
                'pageParam' => 'halaman_mapel',
                'halaman' => $mapelHalaman,
                'perHalaman' => $mapelPerHalaman,
                'total' => $mapelTotal,
                'filter' => ['gen' => $angkatanAktif, 'halaman_riwayat' => $riwayatHalaman],
                'label' => 'penetapan Guru per Gen',
                'anchor' => 'judul-pengampu',
            ]) ?>
        <?php endif ?>
    <?php endif ?>
</section>

<section class="a-card a-jarak-atas" aria-labelledby="judul-riwayat">
    <h2 id="judul-riwayat">Riwayat penetapan Guru per Gen</h2>
    <?php if ($riwayat === []): ?>
        <div class="a-empty" role="status">Belum ada penetapan atau pergantian Guru per Gen.</div>
    <?php else: ?>
        <p class="a-help">Menampilkan 25 perubahan per halaman. Seluruh riwayat tetap tersimpan dan dapat dibuka melalui navigasi halaman.</p>
        <div class="a-tabel-gulir"><table class="a-tbl a-tbl--cards a-tbl--riwayat-guru">
            <thead><tr>
                <th scope="col">Gen</th><th scope="col">Mapel</th>
                <th scope="col">Sebelum → Sesudah</th><th scope="col">Alasan</th><th scope="col">Waktu (UTC)</th>
            </tr></thead>
            <tbody>
                <?php foreach ($riwayat as $item): ?>
                    <tr>
                        <th scope="row" data-label="Gen"><?= esc($item['gen_kode']) ?></th>
                        <td data-label="Mapel"><?= esc($item['mapel_nama']) ?></td>
                        <td data-label="Sebelum → Sesudah"><?= esc($item['guru_lama'] ?? 'Belum ada') ?> → <?= esc($item['guru_baru'] ?? 'Belum ada') ?></td>
                        <td data-label="Alasan"><?= esc($item['alasan']) ?></td>
                        <td data-label="Waktu (UTC)"><?= esc($item['waktu']) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table></div>
        <?= view('components/pager', [
            'urlPath' => 'admin/mapel-guru',
            'pageParam' => 'halaman_riwayat',
            'halaman' => $riwayatHalaman,
            'perHalaman' => $riwayatPerHalaman,
            'total' => $riwayatTotal,
            'filter' => ['gen' => $angkatanAktif, 'halaman_mapel' => $mapelHalaman],
            'label' => 'riwayat penetapan Guru',
            'anchor' => 'judul-riwayat',
        ]) ?>
    <?php endif ?>
</section>
</div>
<?= $this->endSection() ?>
