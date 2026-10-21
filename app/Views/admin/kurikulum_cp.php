<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-cp">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin'), 'attr') ?>">Beranda Admin</a>
        <span aria-hidden="true">/</span><span aria-current="page">Kurikulum &amp; CP</span>
    </nav>
    <header class="a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Admin · Referensi Akademik</p>
            <h1>Referensi Kurikulum &amp; CP</h1>
            <p class="a-proto-muted">Kelola sumber resmi CP Fase D per mapel. CP yang telah terbit tidak bisa disunting; perubahan diterbitkan sebagai versi baru.</p>
        </div>
    </header>
    <?php if ($galat = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>
    <?php if ($sukses = session()->getFlashdata('sukses')): ?>
        <div class="a-alert" role="status"><?= esc($sukses) ?></div>
    <?php endif ?>

    <div class="a-cp__dua">
        <section class="a-card" aria-labelledby="judul-kurikulum">
            <h2 id="judul-kurikulum">Tambah referensi kurikulum</h2>
            <p class="a-help">Masukkan nama, edisi, sumber dan dasar hukum sesuai dokumen resmi. Data bawaan tidak ditebak oleh aplikasi.</p>
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
        </section>
        <section class="a-card" aria-labelledby="judul-daftar-kurikulum">
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
    <section class="a-card" aria-labelledby="judul-cp-baru">
        <h2 id="judul-cp-baru">Buat CP Fase D (draf pertama)</h2>
        <p class="a-help">Pilih kurikulum dan mapel yang tepat. Draf belum dapat digunakan dalam TP sampai diterbitkan Admin.</p>
        <?php if ($kurikulum === [] || $mapel === []): ?>
            <div class="a-alert a-alert--info" role="status">Tambahkan kurikulum serta mapel pada menu terkait terlebih dahulu.</div>
        <?php else: ?>
            <form method="post" action="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>" class="a-form a-cp__form">
                <?= csrf_field() ?>
                <div class="a-cp__grid">
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
                    <div class="a-field a-cp__lebar"><label class="a-label" for="cp-rumusan">Rumusan CP resmi</label>
                        <textarea id="cp-rumusan" name="rumusan" rows="5" minlength="20" maxlength="15000" required><?= esc(old('rumusan', '')) ?></textarea></div>
                    <div class="a-field"><label class="a-label" for="cp-otoritas">Otoritas penerbit</label>
                        <input id="cp-otoritas" name="otoritas" type="text" minlength="3" maxlength="150" required value="<?= esc(old('otoritas', ''), 'attr') ?>"></div>
                    <div class="a-field"><label class="a-label" for="cp-dok">Nomor dokumen resmi</label>
                        <input id="cp-dok" name="nomor_dokumen" type="text" minlength="3" maxlength="120" required value="<?= esc(old('nomor_dokumen', ''), 'attr') ?>"></div>
                    <div class="a-field"><label class="a-label" for="cp-url">URL sumber dokumen</label>
                        <input id="cp-url" name="sumber_url" type="url" maxlength="1024" placeholder="https://..." required value="<?= esc(old('sumber_url', ''), 'attr') ?>"></div>
                    <div class="a-field"><label class="a-label" for="cp-tanggal">Tanggal berlaku dokumen</label>
                        <input id="cp-tanggal" name="tanggal_berlaku" type="date" required value="<?= esc(old('tanggal_berlaku', ''), 'attr') ?>"></div>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Simpan draf CP</button>
            </form>
        <?php endif ?>
    </section>
    <section class="a-card" aria-labelledby="judul-cp-daftar">
        <h2 id="judul-cp-daftar">Daftar CP dan status versinya</h2>
        <?php if ($cp === []): ?>
            <div class="a-empty" role="status"><strong>Belum ada referensi CP.</strong><p>Gunakan formulir di atas untuk menyiapkan draf CP resmi.</p></div>
        <?php else: ?>
            <div class="a-tabel-gulir">
                <table class="a-tbl a-cp__tabel">
                    <thead><tr><th scope="col">Kode CP</th><th scope="col">Kurikulum</th><th scope="col">Mapel / Fase</th><th scope="col">Versi resmi</th><th scope="col">Draf</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($cp as $item): ?>
                        <tr>
                            <td><strong><?= esc($item['kode']) ?></strong></td>
                            <td><?= esc($item['kurikulum_nama']) ?><small class="a-cp__detail"><?= esc($item['kurikulum_versi']) ?></small></td>
                            <td><?= esc($item['mapel_nama']) ?> · <?= esc($item['fase']) ?></td>
                            <td><?= $item['versi_aktif_id'] === null ? '<span class="a-badge a-badge--tunggu">Belum terbit</span>' : 'v' . (int) $item['versi_aktif'] ?></td>
                            <td><?= $item['versi_draf_id'] === null ? '—' : 'v' . (int) $item['versi_draf'] ?></td>
                            <td><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $item['id']), 'attr') ?>">Lihat versi</a></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
