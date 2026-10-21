<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-belajar-guru">
    <nav class="a-breadcrumb"><a href="<?= esc(site_url('admin/usulan-cp'),'attr') ?>">Usulan CP</a> / Keputusan</nav>
    <header class="a-page__heading a-proto-heading"><div>
        <p class="a-proto-eyebrow">Admin · Tinjau versi CP</p><h1><?= esc($usulan['kode']) ?> · <?= esc($usulan['mapel_nama']) ?></h1>
        <p class="a-proto-muted">Pengusul: <?= esc($usulan['pengusul_nama']) ?> · <?= esc(str_replace('_',' ',$usulan['jenis'])) ?></p>
    </div></header>
    <?php if($msg=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($msg) ?></div><?php endif ?>
    <?php if($msg=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($msg) ?></div><?php endif ?>
    <section class="a-card">
        <h2>Bandingkan naskah</h2>
        <div class="a-guru-form-grid">
            <div><h3>Versi asal · v<?= (int)$usulan['nomor_asal'] ?></h3>
                <p class="a-belajar-guru__teks"><?= nl2br(esc($usulan['rumusan_asal'])) ?></p></div>
            <div><h3>Usulan Guru</h3>
                <p class="a-belajar-guru__teks"><?= nl2br(esc($usulan['rumusan'])) ?></p></div>
        </div>
        <dl class="a-rincian">
            <dt>Alasan</dt><dd><?= nl2br(esc($usulan['alasan'])) ?></dd>
            <dt>Sumber</dt><dd><?= esc($usulan['sumber_url']) ?></dd>
            <dt>Nomor dokumen</dt><dd><?= esc($usulan['nomor_dokumen']) ?></dd>
            <dt>Status</dt><dd><?= esc($usulan['status']) ?></dd>
            <dt>Keputusan</dt><dd><?= $usulan['catatan_keputusan']===null?'—':esc($usulan['catatan_keputusan']) ?></dd>
        </dl>
    </section>
    <?php if($usulan['status']==='diajukan'): ?>
    <section class="a-card"><h2>Putuskan usulan</h2>
        <p class="a-help">Persetujuan koreksi akan menerbitkan versi CP baru tanpa mengubah versi historis. Catatan lokal tidak mengganti teks CP resmi.</p>
        <form method="post" action="<?= esc(site_url('admin/usulan-cp/'.(int)$usulan['id']),'attr') ?>" class="a-form">
            <?= csrf_field() ?>
            <div class="a-field"><label class="a-label" for="keputusan">Keputusan</label>
                <select id="keputusan" name="keputusan" required>
                    <option value="setujui">Setujui</option>
                    <option value="tolak">Tolak</option>
                    <option value="kembalikan_perbaikan">Kembalikan untuk perbaikan</option>
                </select></div>
            <div class="a-field"><label class="a-label" for="catatan">Alasan keputusan (wajib)</label>
                <textarea id="catatan" name="catatan" minlength="12" maxlength="2000" rows="4" required></textarea></div>
            <button class="a-btn a-btn--utama" type="submit">Simpan keputusan dan audit</button>
        </form>
    </section>
    <?php else: ?>
        <div class="a-alert" role="status">Permohonan ini sudah diputus dan tidak bisa diproses ulang.</div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
