<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-belajar-guru">
    <nav class="a-breadcrumb"><a href="<?= esc(site_url('guru/bab/'.(int)$babId),'attr') ?>">Editor BAB</a> / Pratinjau Impor</nav>
    <header class="a-page__heading a-proto-heading"><div>
        <p class="a-proto-eyebrow">Guru · Excel 8 Sheet</p><h1>Pratinjau impor BAB</h1>
        <p class="a-proto-muted">Tahap pemeriksaan: tidak ada perubahan TP/materi/soal sebelum konfirmasi. Batch berlaku 30 menit.</p>
    </div></header>
    <?php if($msg=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($msg) ?></div><?php endif ?>
    <?php if($msg=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($msg) ?></div><?php endif ?>
    <section class="a-card">
        <h2>Ringkasan pratinjau</h2>
        <div class="a-belajar-guru__summary">
            <div><strong><?= (int)$batch['jumlah_tambah'] ?></strong><span>Tambah</span></div>
            <div><strong><?= (int)$batch['jumlah_ubah'] ?></strong><span>Ubah</span></div>
            <div><strong><?= (int)$batch['jumlah_galat'] ?></strong><span>Galat</span></div>
            <div><strong><?= esc($batch['status']) ?></strong><span>Status</span></div>
        </div>
        <?php if($batch['status']==='diterapkan'): ?>
            <div class="a-alert" role="status">Batch ini sudah diterapkan. Konfirmasi ulang tidak akan membuat duplikasi.</div>
        <?php elseif($kedaluwarsa): ?>
            <div class="a-alert a-alert--bahaya" role="alert">Pratinjau kedaluwarsa. Unggah ulang dari template terbaru.</div>
        <?php elseif((int)$batch['jumlah_galat']>0): ?>
            <div class="a-alert a-alert--bahaya" role="alert">Ada baris salah. Tidak ada impor sebagian; perbaiki file dan ulangi pratinjau.</div>
        <?php else: ?>
            <div class="a-alert" role="status">Tidak ditemukan galat. Data sumber, UUID, struktur dan hak Guru akan diperiksa kembali saat konfirmasi.</div>
        <?php endif ?>
    </section>
    <section class="a-card"><h2>Hasil validasi per sheet dan baris</h2>
        <?php if($galat===[]): ?><div class="a-empty" role="status">Tidak ada galat baris.</div>
        <?php else: ?>
            <div class="a-tabel-gulir"><table class="a-tbl">
                <thead><tr><th scope="col">Sheet</th><th scope="col">Baris</th><th scope="col">Kolom</th><th scope="col">Kode</th><th scope="col">Perbaikan</th></tr></thead>
                <tbody><?php foreach($galat as $g): ?><tr>
                    <td><?= esc($g['sheet_nama']) ?></td><td><?= (int)$g['nomor_baris'] ?></td>
                    <td><?= esc($g['kolom']) ?></td><td><?= esc($g['kode']) ?></td><td><?= esc($g['pesan']) ?></td>
                </tr><?php endforeach ?></tbody>
            </table></div>
        <?php endif ?>
        <div class="a-belajar-guru__aksi">
            <?php if($batch['status']==='pratinjau' && !$kedaluwarsa && (int)$batch['jumlah_galat']===0): ?>
                <form method="post" action="<?= esc(site_url('guru/bab/'.(int)$babId.'/impor/'.$batch['id'].'/konfirmasi'),'attr') ?>">
                    <?= csrf_field() ?>
                    <button class="a-btn a-btn--utama" type="submit">Konfirmasi dan terapkan semua perubahan</button>
                </form>
            <?php endif ?>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/bab/'.(int)$babId),'attr') ?>">Kembali ke BAB / Unggah ulang</a>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
