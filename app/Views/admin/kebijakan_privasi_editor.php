<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
    $id=isset($baris['id'])?(int)$baris['id']:null;
    $terbit=$baris && $baris['status']==='terbit';
    $prefix=$id?'admin/kebijakan-privasi/'.$id:'admin/kebijakan-privasi/baru';
?>
<div class="a-page a-informasi">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin'),'attr') ?>">Beranda Admin</a><span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('admin/kebijakan-privasi'),'attr') ?>">Kebijakan Privasi</a><span aria-hidden="true">/</span>
        <span aria-current="page"><?= $id?'Rincian':'Tambah Draf' ?></span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div><p class="a-proto-eyebrow">Admin · Naskah Disahkan</p>
            <h1><?= $terbit?'Versi Kebijakan yang Telah Diterbitkan':($id?'Sunting Draf Kebijakan':'Tambah Draf Kebijakan') ?></h1>
            <p class="a-proto-muted">Naskah contoh umum telah disiapkan untuk mempermudah penyusunan. Periksa dan sesuaikan isinya dengan praktik madrasah; pengesahan resmi tetap diperlukan sebelum diterbitkan.</p>
        </div>
    </header>
    <?php if ($pesan=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
    <?php if ($pesan=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <section class="a-card" aria-labelledby="form-privasi">
        <h2 id="form-privasi"><?= $terbit?'Naskah resmi versi terbit':'Naskah versi draf' ?></h2>
        <form method="post" action="<?= esc(site_url($prefix),'attr') ?>" class="a-form" <?= $terbit?'':'data-dirty-form' ?>>
            <?= csrf_field() ?>
            <?php if ($id): ?><input type="hidden" name="revisi_draf" value="<?= (int)$baris['revisi_draf'] ?>"><?php endif ?>
            <?php if (!$id): ?>
                <div class="a-alert a-alert--info" role="note">
                    <strong>Draf contoh — belum menjadi Kebijakan Privasi resmi.</strong>
                    <p>Naskah di bawah dapat disunting oleh Admin. Periksa kembali pengelolaan data, kontak, retensi, dan prosedur madrasah sebelum pengesahan dan publikasi.</p>
                </div>
            <?php endif ?>
            <div class="a-field">
                <label class="a-label" for="versi-privasi">Versi dokumen</label>
                <input id="versi-privasi" name="versi" type="text" maxlength="40" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" required
                       value="<?= esc(old('versi',$baris['versi']??'A15-2026.11'),'attr') ?>" <?= $terbit?'readonly':'' ?>>
            </div>
            <div class="a-field">
                <label class="a-label" for="tanggal-privasi">Tanggal berlaku (WIB)</label>
                <input id="tanggal-privasi" name="tanggal_berlaku" type="date" required
                       value="<?= esc(old('tanggal_berlaku',$baris['tanggal_berlaku']??''),'attr') ?>" <?= $terbit?'readonly':'' ?>>
            </div>
            <div class="a-field">
                <label class="a-label" for="teks-privasi">Naskah Kebijakan Privasi resmi</label>
                <textarea id="teks-privasi" name="teks" rows="18" maxlength="50000" required <?= $terbit?'readonly':'' ?>><?= esc(old('teks',$baris['teks']??\App\Services\KebijakanPrivasiContohService::naskah())) ?></textarea>
                <p class="a-help">Teks biasa, maksimal 50.000 karakter. HTML tidak dijalankan pada halaman publik.</p>
            </div>
            <?php if (!$terbit): ?>
                <p class="a-help" data-dirty-status role="status">Simpan Draf terlebih dahulu. Belum diterbitkan.</p>
                <button type="submit" class="a-btn a-btn--utama" data-dirty-submit>Simpan Draf</button>
            <?php endif ?>
        </form>
    </section>
    <?php if ($id && !$terbit): ?>
    <section class="a-card" aria-labelledby="terbit-privasi">
        <h2 id="terbit-privasi">Terbitkan naskah resmi</h2>
        <p class="a-alert a-alert--info">Tindakan ini membuat versi tidak dapat disunting. Pastikan teks, tanggal, dan versinya sama dengan dokumen yang telah disahkan oleh pihak madrasah.</p>
        <form method="post" action="<?= esc(site_url('admin/kebijakan-privasi/'.$id.'/terbit'),'attr') ?>"
              onsubmit="return confirm('Terbitkan Kebijakan Privasi versi ini? Setelah diterbitkan dokumen tidak dapat diedit.');">
            <?= csrf_field() ?>
            <input type="hidden" name="revisi_draf" value="<?= (int)$baris['revisi_draf'] ?>">
            <label class="a-label" for="setuju-privasi">
                <input type="checkbox" id="setuju-privasi" name="sudah_disahkan" value="1" required>
                Saya memastikan naskah ini telah disahkan madrasah untuk diterbitkan.
            </label>
            <p class="a-help">Centang ini adalah konfirmasi tindakan Admin, bukan pengganti proses pengesahan kelembagaan.</p>
            <button type="submit" class="a-btn a-btn--utama">Terbitkan versi ini</button>
        </form>
    </section>
    <?php endif ?>
    <?php if ($id): ?>
    <section class="a-card">
        <h2>Jejak perubahan</h2>
        <p class="a-help">Riwayat versi dan checksum disimpan tanpa menghapus versi lama.</p>
        <div class="a-tabel-gulir" tabindex="0" aria-label="Audit Kebijakan Privasi">
            <table class="a-tabel">
                <thead><tr><th>Aksi</th><th>Revisi</th><th>Waktu UTC</th></tr></thead>
                <tbody>
                <?php foreach ($riwayat as $histori): ?>
                <tr><td><?= esc($histori['aksi']) ?></td><td><?= (int)$histori['revisi_baru'] ?></td><td><?= esc($histori['terjadi_pada']) ?></td></tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
