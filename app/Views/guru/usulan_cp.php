<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-belajar-guru">
    <header class="a-page__heading a-proto-heading"><div>
        <p class="a-proto-eyebrow">Guru · Kurikulum Fase D</p><h1>Usulan CP</h1>
        <p class="a-proto-muted">Ajukan rujukan, koreksi naskah, atau catatan lokal pada CP resmi mapel yang Anda ampu. Semua usulan ditinjau Admin.</p>
    </div></header>
    <?php if ($msg=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($msg) ?></div><?php endif ?>
    <?php if ($msg=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($msg) ?></div><?php endif ?>
    <section class="a-card" aria-labelledby="cp-pilih">
        <h2 id="cp-pilih">CP aktif yang dapat Anda usulkan</h2>
        <?php if ($cp===[]): ?>
            <div class="a-empty" role="status"><strong>Belum ada CP aktif untuk mapel Anda.</strong><p>Minta Admin membuat dan menerbitkan CP resmi sesuai mapel dahulu.</p></div>
        <?php else: ?>
            <div class="a-belajar-guru__list">
                <?php foreach ($cp as $item): ?>
                    <details class="a-cp__details">
                        <summary><?= esc($item['kode']) ?> · <?= esc($item['elemen']) ?> · v<?= (int)$item['nomor_versi'] ?></summary>
                        <div class="a-belajar-guru__inner">
                            <p class="a-help"><strong>Rumusan resmi:</strong> <?= esc($item['rumusan']) ?></p>
                            <form method="post" action="<?= esc(site_url('guru/usulan-cp'),'attr') ?>" class="a-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="cp_id" value="<?= (int)$item['id'] ?>">
                                <div class="a-field"><label class="a-label" for="jenis-<?= (int)$item['id'] ?>">Jenis usulan</label>
                                    <select id="jenis-<?= (int)$item['id'] ?>" name="jenis" required>
                                        <option value="tambahan_rujukan">Tambahan rujukan resmi</option>
                                        <option value="koreksi_transkripsi">Koreksi transkripsi naskah resmi</option>
                                        <option value="catatan_lokal">Catatan lokal (bukan pengganti CP)</option>
                                    </select></div>
                                <div class="a-field"><label class="a-label" for="alasan-<?= (int)$item['id'] ?>">Alasan dan bukti perubahan</label>
                                    <textarea id="alasan-<?= (int)$item['id'] ?>" name="alasan" minlength="15" maxlength="2000" rows="3" required></textarea></div>
                                <div class="a-guru-form-grid">
                                    <?php foreach ([
                                        'elemen'=>['Elemen CP',$item['elemen']],
                                        'otoritas'=>['Otoritas',$item['otoritas']],
                                        'nomor_dokumen'=>['Nomor dokumen resmi',$item['nomor_dokumen']],
                                        'sumber_url'=>['URL rujukan resmi',$item['sumber_url']],
                                        'tanggal_berlaku'=>['Tanggal berlaku',$item['tanggal_berlaku']],
                                    ] as $key=>[$label,$value]): ?>
                                        <div class="a-field"><label class="a-label" for="<?= esc($key.'-'.$item['id'],'attr') ?>"><?= esc($label) ?></label>
                                            <input id="<?= esc($key.'-'.$item['id'],'attr') ?>"
                                                name="<?= esc($key,'attr') ?>" type="<?= $key==='tanggal_berlaku'?'date':($key==='sumber_url'?'url':'text') ?>"
                                                value="<?= esc($value,'attr') ?>" required>
                                        </div>
                                    <?php endforeach ?>
                                    <div class="a-field a-guru-form-grid__wide"><label class="a-label" for="rumusan-<?= (int)$item['id'] ?>">Rumusan yang diusulkan</label>
                                        <textarea id="rumusan-<?= (int)$item['id'] ?>" name="rumusan" rows="5" minlength="20" required><?= esc($item['rumusan']) ?></textarea></div>
                                </div>
                                <p class="a-help">Untuk catatan lokal, sistem tetap memakai rumusan resmi dari versi asal. Pengajuan terkunci setelah dikirim.</p>
                                <button class="a-btn a-btn--utama" type="submit">Kirim usulan CP</button>
                            </form>
                        </div>
                    </details>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>
    <section class="a-card" aria-labelledby="judul-riwayat-usulan">
        <h2 id="judul-riwayat-usulan">Riwayat usulan saya</h2>
        <?php if ($riwayat===[]): ?>
            <div class="a-empty" role="status">Belum ada usulan.</div>
        <?php else: ?>
            <div class="a-tabel-gulir"><table class="a-tbl">
                <thead><tr><th scope="col">CP/Mapel</th><th scope="col">Jenis</th><th scope="col">Status</th><th scope="col">Catatan keputusan</th></tr></thead>
                <tbody><?php foreach($riwayat as $i): ?><tr>
                    <td><?= esc($i['kode'].' · '.$i['mapel_nama']) ?></td>
                    <td><?= esc(str_replace('_',' ',$i['jenis'])) ?></td>
                    <td><span class="a-badge"><?= esc($i['status']) ?></span></td>
                    <td><?= $i['catatan_keputusan']===null?'—':esc($i['catatan_keputusan']) ?></td>
                </tr><?php endforeach ?></tbody>
            </table></div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
