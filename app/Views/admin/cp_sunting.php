<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-cp a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>">Referensi Kurikulum &amp; CP</a>
        <span aria-hidden="true">/</span><span aria-current="page">Sunting CP</span>
    </nav>
    <section class="a-card a-cp__editor">
        <h1>Sunting CP</h1>
        <p class="a-help">Perubahan disimpan langsung. Fase D ditentukan sistem; riwayat akademik tetap dipertahankan.</p>
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
                    <textarea id="edit-rumusan-cp" name="rumusan" rows="7" minlength="20" maxlength="15000" required><?= esc(old('rumusan', $dataVersi['rumusan'] ?? '')) ?></textarea>
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
