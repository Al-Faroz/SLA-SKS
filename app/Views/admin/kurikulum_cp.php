<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-cp a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin'), 'attr') ?>">Beranda Admin</a>
        <span aria-hidden="true">/</span><span aria-current="page">Kurikulum &amp; CP</span>
    </nav>
    <header class="a-proto-heading a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Admin · Referensi Akademik</p>
            <h1>Referensi Kurikulum &amp; CP</h1>
            <p class="a-proto-muted">Admin dapat menambah dan menyunting CP langsung melalui lima isian. Riwayat akademik dipertahankan.</p>
        </div>
    </header>
    <?php if ($galat = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>
    <?php if ($sukses = session()->getFlashdata('sukses')): ?>
        <div class="a-alert" role="status"><?= esc($sukses) ?></div>
    <?php endif ?>

    <nav class="a-page__jumps" aria-label="Bagian referensi akademik">
        <a href="#cp-daftar">Daftar &amp; sunting CP</a>
        <a href="#cp-tambah">Tambah CP</a>
        <a href="#cp-operasional">Peta CP operasional</a>
        <a href="#kurikulum-daftar">Referensi kurikulum</a>
    </nav>
    <section class="a-cp__overview" aria-label="Ringkasan referensi akademik">
        <div class="a-cp__stat"><strong><?= (int) count($cpOperasional) ?></strong><span>CP operasional</span></div>
        <div class="a-cp__stat"><strong><?= (int) count($mapel) ?></strong><span>Mapel terdaftar</span></div>
        <div class="a-cp__stat"><strong><?= (int) count($kurikulum) ?></strong><span>Kurikulum tersimpan</span></div>
    </section>
    <section class="a-card" id="cp-daftar" aria-labelledby="judul-cp-daftar">
        <div class="a-cp__section-heading">
            <div>
                <h2 id="judul-cp-daftar">Daftar CP dan sunting langsung</h2>
                <p class="a-help">Temukan CP berdasarkan mapel atau kode, lalu sunting tanpa melalui pengajuan.</p>
            </div>
            <a class="a-btn a-btn--utama" href="#cp-tambah">+ Tambah CP</a>
        </div>
        <?php if (!empty($cpFilterGalat)): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($cpFilterGalat) ?></div>
        <?php endif ?>
        <form class="a-cp__filter" action="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>#cp-daftar" method="get">
            <div class="a-field">
                <label class="a-label" for="cp-cari">Cari kode, elemen, mapel atau kurikulum</label>
                <input id="cp-cari" type="search" name="cari" maxlength="80" value="<?= esc($cpFilter['cari'], 'attr') ?>" placeholder="Cari CP">
            </div>
            <div class="a-field">
                <label class="a-label" for="cp-mapel-filter">Mata pelajaran</label>
                <select id="cp-mapel-filter" name="mapel">
                    <option value="">Semua mapel</option>
                    <?php foreach ($mapel as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= $cpFilter['mapel'] === (int) $m['id'] ? 'selected' : '' ?>><?= esc($m['kode'] . ' — ' . $m['nama']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="a-field">
                <label class="a-label" for="cp-kur-filter">Kurikulum</label>
                <select id="cp-kur-filter" name="kurikulum">
                    <option value="">Semua kurikulum</option>
                    <?php foreach ($kurikulum as $k): ?>
                        <option value="<?= (int) $k['id'] ?>" <?= $cpFilter['kurikulum'] === (int) $k['id'] ? 'selected' : '' ?>><?= esc($k['nama'] . ' · ' . $k['versi']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="a-field">
                <label class="a-label" for="cp-status-filter">Status versi</label>
                <select id="cp-status-filter" name="status">
                    <option value="semua" <?= $cpFilter['status'] === 'semua' ? 'selected' : '' ?>>Semua status</option>
                    <option value="aktif" <?= $cpFilter['status'] === 'aktif' ? 'selected' : '' ?>>Punya versi aktif</option>
                    <option value="draf" <?= $cpFilter['status'] === 'draf' ? 'selected' : '' ?>>Punya draf</option>
                    <option value="belum" <?= $cpFilter['status'] === 'belum' ? 'selected' : '' ?>>Belum terbit</option>
                </select>
            </div>
            <div class="a-cp__filter-aksi">
                <button class="a-btn a-btn--utama" type="submit">Terapkan filter</button>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kurikulum-cp') . '#cp-daftar', 'attr') ?>">Reset</a>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kurikulum-cp/ekspor') . '?' . http_build_query($cpFilter), 'attr') ?>">Ekspor Excel (.xlsx)</a>
            </div>
        </form>
        <p class="a-help" role="status"><?= (int) $cpTotal ?> CP sesuai filter · 25 CP per halaman. Ekspor Excel mencakup seluruh hasil filter.</p>
        <?php if ($cp === []): ?>
            <div class="a-empty" role="status"><strong><?= $cpTotal === 0 ? 'Tidak ada CP yang cocok.' : 'Tidak ada data pada halaman ini.' ?></strong>
                <p>Sesuaikan filter atau gunakan formulir CP di atas untuk menambah CP.</p></div>
        <?php else: ?>
            <div class="a-tabel-gulir">
                <table class="a-tbl a-tbl--cards a-cp__tabel">
                    <thead><tr><th scope="col">Kode CP</th><th scope="col">Kurikulum</th><th scope="col">Mapel / Fase</th><th scope="col">Versi resmi</th><th scope="col">Draf</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($cp as $item): ?>
                        <tr>
                            <th scope="row" data-label="Kode CP"><strong><?= esc($item['kode']) ?></strong></th>
                            <td data-label="Kurikulum"><?= esc($item['kurikulum_nama']) ?><small class="a-cp__detail"><?= esc($item['kurikulum_versi']) ?></small></td>
                            <td data-label="Mapel / Fase"><?= esc($item['mapel_nama']) ?> · <?= esc($item['fase']) ?></td>
                            <td data-label="Versi resmi"><?= $item['versi_aktif_id'] === null ? '<span class="a-badge a-badge--tunggu">Belum terbit</span>' : 'v' . (int) $item['versi_aktif'] ?></td>
                            <td data-label="Draf"><?= $item['versi_draf_id'] === null ? '—' : 'v' . (int) $item['versi_draf'] ?></td>
                            <td data-label="Aksi">
                                <a class="a-btn a-btn--utama" href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $item['id'] . '/sunting'), 'attr') ?>">Sunting CP</a>
                                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $item['id']), 'attr') ?>">Riwayat</a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        <?= view('components/pager', [
            'urlPath' => 'admin/kurikulum-cp',
            'pageParam' => 'halaman',
            'halaman' => $cpHalaman,
            'perHalaman' => $cpPerHalaman,
            'total' => $cpTotal,
            'filter' => $cpFilter,
            'label' => 'referensi CP',
            'anchor' => 'cp-daftar',
        ]) ?>
    </section>
    <section class="a-card" id="cp-tambah" aria-labelledby="judul-cp-baru">
        <h2 id="judul-cp-baru">Tambah CP Fase D</h2>
        <p class="a-help">Pilih kurikulum, mata pelajaran, kode CP, elemen, dan rumusan resmi. Fase D otomatis.</p>
        <?php if ($kurikulum === [] || $mapel === []): ?>
            <div class="a-alert a-alert--info" role="status">Tambahkan kurikulum serta mapel pada menu terkait terlebih dahulu.</div>
        <?php else: ?>
            <details class="a-cp__details" <?= old('kode') !== null ? 'open' : '' ?>>
                <summary>Formulir CP resmi · 5 isian</summary>
            <form method="post" action="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>" class="a-form a-cp__form">
                <?= csrf_field() ?>
                <div class="a-cp__grid">
                    <h3 class="a-cp__group-title">Identitas CP</h3>
                    <div class="a-field"><label class="a-label" for="cp-kurikulum">Kurikulum</label>
                        <select id="cp-kurikulum" name="kurikulum_id" required>
                            <option value="">Pilih kurikulum</option>
                            <?php foreach ($kurikulum as $k): ?>
                                <option value="<?= (int) $k['id'] ?>" <?= (int) old('kurikulum_id', 0) === (int) $k['id'] ? 'selected' : '' ?>><?= esc($k['nama'] . ' · ' . $k['versi']) ?></option>
                            <?php endforeach ?>
                        </select></div>
                    <div class="a-field"><label class="a-label" for="cp-mapel">Mata pelajaran</label>
                        <select id="cp-mapel" name="mapel_id" required>
                            <option value="">Pilih mapel</option>
                            <?php foreach ($mapel as $m): ?>
                                <option value="<?= (int) $m['id'] ?>" <?= (int) old('mapel_id', 0) === (int) $m['id'] ? 'selected' : '' ?>><?= esc($m['kode'] . ' · ' . $m['nama']) ?></option>
                            <?php endforeach ?>
                        </select></div>
                    <div class="a-field"><label class="a-label" for="cp-kode">Kode CP</label>
                        <input id="cp-kode" name="kode" type="text" minlength="2" maxlength="40" pattern="[A-Za-z0-9._-]{2,40}" required value="<?= esc(old('kode', ''), 'attr') ?>">
                        <span class="a-help">Kode stabil dan unik untuk kombinasi kurikulum, mapel, Fase D.</span></div>
                    <div class="a-field"><label class="a-label" for="cp-elemen">Elemen CP</label>
                        <input id="cp-elemen" name="elemen" type="text" minlength="3" maxlength="150" required value="<?= esc(old('elemen', ''), 'attr') ?>"></div>
                    <h3 class="a-cp__group-title">Rumusan CP</h3>
                    <div class="a-field a-cp__lebar"><label class="a-label" for="cp-rumusan">Rumusan CP resmi</label>
                        <textarea id="cp-rumusan" name="rumusan" rows="5" minlength="20" maxlength="15000" required><?= esc(old('rumusan', '')) ?></textarea></div>
                </div>
                <div class="a-cp__aksi"><button class="a-btn a-btn--utama" type="submit">Simpan CP</button></div>
            </form>
            </details>
        <?php endif ?>
    </section>
    <section class="a-card" id="cp-operasional" aria-labelledby="judul-cp-operasional">
        <h2 id="judul-cp-operasional">CP Operasional per Mata Pelajaran · Fase</h2>
        <p class="a-help">Master v2.5: <strong>satu CP operasional untuk setiap kombinasi
            Kurikulum–Mapel–Fase</strong>, yang dapat mempunyai beberapa elemen aktif.
            Kode dan riwayat tiap elemen tetap dikelola sebagai referensi berversi.</p>
        <details class="a-cp__details a-cp__referensi">
            <summary>Tampilkan peta CP operasional (<?= (int) count($cpOperasional) ?> mapel)</summary>
        <?php if (empty($cpOperasional)): ?>
            <div class="a-empty" role="status">
                <strong>Belum ada CP operasional.</strong>
                <p>Buat dan terbitkan referensi elemen CP pada formulir di atas.</p>
            </div>
        <?php else: ?>
            <div class="a-tabel-gulir">
                <table class="a-tbl a-tbl--cards a-cp__tabel">
                    <thead><tr>
                        <th scope="col">Mapel / Fase</th>
                        <th scope="col">Kurikulum</th>
                        <th scope="col">Elemen CP aktif</th>
                        <th scope="col">Jumlah</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($cpOperasional as $operasional): ?>
                        <tr>
                            <th scope="row" data-label="Mapel / Fase">
                                <?= esc($operasional['kode_mapel']) ?> ·
                                <?= esc($operasional['nama_mapel']) ?>
                                <small class="a-cp__detail">Fase <?= esc($operasional['fase']) ?></small>
                            </th>
                            <td data-label="Kurikulum">
                                <?= esc($operasional['kurikulum']) ?>
                                <small class="a-cp__detail"><?= esc($operasional['versi_kurikulum']) ?></small>
                            </td>
                            <td data-label="Elemen CP aktif">
                                <?php if ($operasional['elemen_aktif'] === []): ?>
                                    <span class="a-badge a-badge--tunggu">Belum terbit</span>
                                <?php else: ?>
                                    <?php foreach ($operasional['elemen_aktif'] as $elemen): ?>
                                        <p>
                                            <a href="<?= esc(site_url('admin/kurikulum-cp/' . $elemen['referensi_id']), 'attr') ?>">
                                                <?= esc($elemen['nama']) ?>
                                            </a>
                                            <small class="a-cp__detail"><?= esc($elemen['kode']) ?></small>
                                        </p>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </td>
                            <td data-label="Jumlah"><?= count($operasional['elemen_aktif']) ?> aktif</td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        </details>
    </section>

    <div class="a-cp__dua">
        <section class="a-card a-cp__kurikulum-form" id="kurikulum-tambah" aria-labelledby="judul-kurikulum">
            <h2 id="judul-kurikulum">Tambah referensi kurikulum</h2>
            <p class="a-help">Masukkan nama, edisi, sumber dan dasar hukum sesuai dokumen resmi. Data bawaan tidak ditebak oleh aplikasi.</p>
            <details class="a-cp__details" <?= ($kurikulum === [] || old('nama') !== null) ? 'open' : '' ?>>
                <summary>Isi formulir kurikulum baru</summary>
            <form method="post" action="<?= esc(site_url('admin/kurikulum-cp/kurikulum'), 'attr') ?>" class="a-form">
                <?= csrf_field() ?>
                <div class="a-field"><label class="a-label" for="kur-nama">Nama kurikulum</label>
                    <input id="kur-nama" name="nama" type="text" minlength="3" maxlength="120" required value="<?= esc(old('nama', ''), 'attr') ?>"></div>
                <div class="a-field"><label class="a-label" for="kur-versi">Versi / edisi</label>
                    <input id="kur-versi" name="versi" type="text" maxlength="50" required value="<?= esc(old('versi', ''), 'attr') ?>"></div>
                <div class="a-field"><label class="a-label" for="kur-sumber">Sumber / instansi</label>
                    <input id="kur-sumber" name="sumber" type="text" minlength="3" maxlength="255" required value="<?= esc(old('sumber', ''), 'attr') ?>"></div>
                <div class="a-field"><label class="a-label" for="kur-dasar">Dasar hukum</label>
                    <input id="kur-dasar" name="dasar_hukum" type="text" minlength="3" maxlength="255" required value="<?= esc(old('dasar_hukum', ''), 'attr') ?>"></div>
                <button class="a-btn a-btn--utama" type="submit">Simpan kurikulum</button>
            </form>
            </details>
        </section>
        <section class="a-card" id="kurikulum-daftar" aria-labelledby="judul-daftar-kurikulum">
            <h2 id="judul-daftar-kurikulum">Kurikulum tersimpan</h2>
            <?php if ($kurikulum === []): ?>
                <div class="a-empty" role="status"><strong>Belum ada kurikulum.</strong><p>Tambahkan kurikulum resmi sebelum membuat CP.</p></div>
            <?php else: ?>
                <div class="a-tabel-gulir">
                    <table class="a-tbl">
                        <thead><tr><th scope="col">Kurikulum</th><th scope="col">Versi</th><th scope="col">Sumber / dasar hukum</th></tr></thead>
                        <tbody>
                        <?php foreach ($kurikulum as $k): ?>
                            <tr><td><?= esc($k['nama']) ?></td><td><?= esc($k['versi']) ?></td>
                                <td><?= esc($k['sumber']) ?><small class="a-cp__detail"><?= esc($k['dasar_hukum']) ?></small></td></tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
