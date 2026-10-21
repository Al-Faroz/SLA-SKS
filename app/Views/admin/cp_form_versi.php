<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-cp">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>">Referensi CP</a><span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $cp['id']), 'attr') ?>"><?= esc($cp['kode']) ?></a>
        <span aria-hidden="true">/</span><span aria-current="page"><?= $edit ? 'Perbaiki draf' : 'Versi baru' ?></span>
    </nav>
    <section class="a-card a-cp__editor">
        <h1><?= $edit ? 'Perbaiki draf CP' : 'Buat draf versi CP baru' ?></h1>
        <p class="a-help"><?= esc($cp['kode'] . ' · ' . $cp['mapel_nama'] . ' · ' . $cp['kurikulum_nama']) ?>. Versi terbit sebelumnya tidak akan ditimpa. Setelah diterbitkan, naskah ini tidak dapat diubah lewat editor.</p>
        <?php if ($galat = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div><?php endif ?>
        <form method="post" action="<?= esc($aksi, 'attr') ?>" class="a-form">
            <?= csrf_field() ?>
            <div class="a-cp__grid">
                <div class="a-field"><label class="a-label" for="v-elemen">Elemen CP</label>
                    <input id="v-elemen" name="elemen" type="text" minlength="3" maxlength="150" required value="<?= esc(old('elemen', $dataVersi['elemen'] ?? ''), 'attr') ?>"></div>
                <div class="a-field"><label class="a-label" for="v-otoritas">Otoritas penerbit</label>
                    <input id="v-otoritas" name="otoritas" type="text" minlength="3" maxlength="150" required value="<?= esc(old('otoritas', $dataVersi['otoritas'] ?? ''), 'attr') ?>"></div>
                <div class="a-field a-cp__lebar"><label class="a-label" for="v-rumusan">Rumusan CP resmi</label>
                    <textarea id="v-rumusan" name="rumusan" minlength="20" maxlength="15000" rows="7" required><?= esc(old('rumusan', $dataVersi['rumusan'] ?? '')) ?></textarea></div>
                <div class="a-field"><label class="a-label" for="v-dok">Nomor dokumen resmi</label>
                    <input id="v-dok" name="nomor_dokumen" type="text" minlength="3" maxlength="120" required value="<?= esc(old('nomor_dokumen', $dataVersi['nomor_dokumen'] ?? ''), 'attr') ?>"></div>
                <div class="a-field"><label class="a-label" for="v-url">URL sumber dokumen</label>
                    <input id="v-url" name="sumber_url" type="url" maxlength="1024" required value="<?= esc(old('sumber_url', $dataVersi['sumber_url'] ?? ''), 'attr') ?>"></div>
                <div class="a-field"><label class="a-label" for="v-tanggal">Tanggal berlaku</label>
                    <input id="v-tanggal" name="tanggal_berlaku" type="date" required value="<?= esc(old('tanggal_berlaku', $dataVersi['tanggal_berlaku'] ?? ''), 'attr') ?>"></div>
            </div>
            <div class="a-cp__aksi">
                <button class="a-btn a-btn--utama" type="submit"><?= $edit ? 'Simpan perbaikan draf' : 'Simpan draf versi baru' ?></button>
                <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $cp['id']), 'attr') ?>">Batal</a>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
