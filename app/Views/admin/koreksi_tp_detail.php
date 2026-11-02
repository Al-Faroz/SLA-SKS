<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php $data = $koreksi['usulan']; $asal = $koreksi['asal']; $tp = $data['tp']; $tpAsal = $asal['tp']; ?>
<div class="a-page a-koreksi-tp">
    <nav class="a-breadcrumb"><a href="<?= esc(site_url('admin/koreksi-tp'), 'attr') ?>">Koreksi TP</a> / #<?= (int) $koreksi['id'] ?></nav>
    <header class="a-page__heading"><div><p class="a-proto-eyebrow">Admin · Review perbandingan</p>
        <h1>Permohonan #<?= (int) $koreksi['id'] ?> · <?= esc($tp['kode_tp']) ?></h1>
        <p class="a-proto-muted">Kategori <?= esc($koreksi['kategori']) ?> · Status <?= esc($koreksi['status']) ?> · Versi dasar #<?= (int) $koreksi['versi_dasar_id'] ?></p>
    </div></header>
    <?php if ($pesan = session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div><?php endif ?>
    <?php if ($pesan = session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($pesan) ?></div><?php endif ?>
    <section class="a-card"><h2>Alasan Guru</h2><p><?= esc($koreksi['alasan']) ?></p></section>
    <section class="a-card a-jarak-atas">
        <h2>Perbandingan perubahan TP</h2>
        <div class="a-tabel-gulir"><table class="a-tbl"><thead><tr><th scope="col">Bagian</th><th scope="col">Versi lama</th><th scope="col">Usulan Guru</th></tr></thead><tbody>
            <?php foreach (['judul'=>'Judul TP','rumusan'=>'Rumusan TP','kkm'=>'KKM Diagnostik/Sumatif'] as $field=>$label): ?>
                <tr><th scope="row"><?= esc($label) ?></th>
                    <td><?= esc((string) ($tpAsal[$field] ?? '')) ?></td><td><?= esc((string) ($tp[$field] ?? '')) ?>
                    <?= ($tpAsal[$field] ?? null) !== ($tp[$field] ?? null) ? ' · Berubah' : '' ?></td></tr>
            <?php endforeach ?>
            <?php $m = $data['materi'][0] ?? []; $mAsal = $asal['materi'][0] ?? []; ?>
            <?php foreach (['judul'=>'Judul Materi','petunjuk_resume'=>'Petunjuk Resume','petunjuk_hots'=>'Petunjuk HOTS','min_waktu_detik'=>'Durasi minimal (detik)','url_youtube'=>'URL video'] as $field=>$label): ?>
                <tr><th scope="row"><?= esc($label) ?></th><td><?= esc((string) ($mAsal[$field] ?? '')) ?></td>
                    <td><?= esc((string) ($m[$field] ?? '')) ?><?= ($mAsal[$field] ?? null) !== ($m[$field] ?? null) ? ' · Berubah' : '' ?></td></tr>
            <?php endforeach ?>
        </tbody></table></div>
    </section>
    <section class="a-card a-jarak-atas"><h2>Perubahan soal</h2>
        <?php $soalLama = []; foreach ($asal['soal'] as $item) { $soalLama[(int) $item['id']] = $item; } ?>
        <?php $jumlahBerubah = 0; ?>
        <?php foreach ($data['soal'] as $item): ?>
            <?php $sebelum = $soalLama[(int) $item['id']] ?? []; if ($sebelum === $item) continue; $jumlahBerubah++; ?>
            <details class="a-word-review-item"><summary><?= esc(ucfirst($item['jenis'])) ?> · Soal <?= (int) $item['urutan'] ?> · Diubah</summary>
                <p><strong>Sebelum:</strong> <?= esc((string) ($sebelum['pertanyaan'] ?? '')) ?> · Kunci <?= esc((string) ($sebelum['kunci'] ?? '')) ?></p>
                <p><strong>Usulan:</strong> <?= esc($item['pertanyaan']) ?> · Kunci <?= esc($item['kunci']) ?></p>
                <?php foreach (['a','b','c','d','e'] as $h): ?>
                    <?php if (($sebelum['opsi_'.$h] ?? '') !== ($item['opsi_'.$h] ?? '')): ?>
                        <p>Pilihan <?= strtoupper($h) ?>: <?= esc((string) ($sebelum['opsi_'.$h] ?? '')) ?> → <?= esc($item['opsi_'.$h]) ?></p>
                    <?php endif ?>
                <?php endforeach ?>
            </details>
        <?php endforeach ?>
        <?php if ($jumlahBerubah === 0): ?><p>Bank soal tidak berubah.</p><?php else: ?><p><?= $jumlahBerubah ?> soal memiliki perubahan.</p><?php endif ?>
    </section>
    <section class="a-card a-jarak-atas"><h2>Keputusan Admin</h2>
        <?php if ($koreksi['status'] !== 'diajukan'): ?>
            <p>Status akhir: <strong><?= esc($koreksi['status']) ?></strong></p>
            <?php if ($koreksi['catatan_keputusan']): ?><p>Catatan: <?= esc($koreksi['catatan_keputusan']) ?></p><?php endif ?>
        <?php else: ?>
            <p class="a-help">Menyetujui akan menerbitkan versi konten TP baru tanpa menghapus versi, nilai, atau progres lama. Guru yang mengajukan tidak boleh memutus dari akun yang sama walaupun berpindah peran.</p>
            <form method="post" action="<?= esc(site_url('admin/koreksi-tp/' . (int) $koreksi['id'] . '/putuskan'), 'attr') ?>" class="a-form">
                <?= csrf_field() ?>
                <div class="a-field"><label class="a-label" for="catatan-keputusan">Catatan keputusan (wajib)</label>
                    <textarea id="catatan-keputusan" name="catatan" minlength="8" maxlength="500" rows="3" required></textarea></div>
                <div class="a-cp__aksi"><button class="a-btn a-btn--utama" type="submit" name="keputusan" value="setuju">Setujui &amp; Terbitkan Versi Baru</button>
                    <button class="a-btn a-btn--sekunder" type="submit" name="keputusan" value="tolak">Tolak Permohonan</button></div>
            </form>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>