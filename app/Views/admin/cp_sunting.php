<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-cp a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>">Referensi Kurikulum &amp; CP</a>
        <span aria-hidden="true">/</span><span aria-current="page">Sunting CP</span>
    </nav>
    <section class="a-card a-cp__editor">
        <div class="a-cp__section-heading">
            <div>
                <p class="a-proto-eyebrow">Admin · Editor CP Fase D</p>
                <h1>Sunting CP</h1>
                <p class="a-cp__editor-kode"><?= esc($cp['kode']) ?></p>
            </div>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $cp['id']), 'attr') ?>">Lihat riwayat CP</a>
        </div>
        <p class="a-help">Perubahan disimpan langsung; versi sebelumnya tetap tercatat.</p>
        <?php if ($galat = session()->getFlashdata('galat')): ?>
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
        <?php endif ?>
        <form class="a-form" method="post" action="<?= esc(site_url('admin/kurikulum-cp/' . (int) $cp['id'] . '/sunting'), 'attr') ?>">
            <?= csrf_field() ?>
            <div class="a-cp__grid">
                <div class="a-field">
                    <label class="a-label" for="edit-kurikulum">Kurikulum</label>
                    <select id="edit-kurikulum" name="kurikulum_id" required>
                        <?php foreach ($kurikulum as $k): ?>
                            <option value="<?= (int) $k['id'] ?>" <?= (int) old('kurikulum_id', $cp['kurikulum_id']) === (int) $k['id'] ? 'selected' : '' ?>><?= esc($k['nama'] . ' · ' . $k['versi']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="a-field">
                    <label class="a-label" for="edit-mapel">Mata pelajaran</label>
                    <select id="edit-mapel" name="mapel_id" required>
                        <?php foreach ($mapel as $m): ?>
                            <option value="<?= (int) $m['id'] ?>" <?= (int) old('mapel_id', $cp['mapel_id']) === (int) $m['id'] ? 'selected' : '' ?>><?= esc($m['kode'] . ' · ' . $m['nama']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="a-field">
                    <label class="a-label" for="edit-kode-cp">Kode CP</label>
                    <input id="edit-kode-cp" name="kode" maxlength="40" minlength="2" pattern="[A-Za-z0-9._-]{2,40}" required value="<?= esc(old('kode', $cp['kode']), 'attr') ?>">
                </div>
                <div class="a-field">
                    <label class="a-label" for="edit-elemen-cp">Elemen CP</label>
                    <input id="edit-elemen-cp" name="elemen" maxlength="150" minlength="3" required value="<?= esc(old('elemen', $dataVersi['elemen'] ?? ''), 'attr') ?>">
                </div>
                <div class="a-field a-cp__lebar">
                    <label class="a-label" for="edit-rumusan-cp">Rumusan CP resmi</label>
                    <textarea id="edit-rumusan-cp" name="rumusan" rows="12" minlength="20" maxlength="15000" aria-describedby="edit-rumusan-help" required><?= esc(old('rumusan', $dataVersi['rumusan'] ?? '')) ?></textarea>
                    <span class="a-help" id="edit-rumusan-help">Pastikan rumusan resmi lengkap sebelum menyimpan. Batas 15.000 karakter.</span>
                </div>
            </div>
            <div class="a-cp__aksi">
                <button class="a-btn a-btn--utama" type="submit">Simpan CP</button>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>">Batal</a>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
