<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-belajar-guru">
    <header class="a-page__heading a-proto-heading"><div>
        <p class="a-proto-eyebrow">Guru · Paket pembelajaran</p><h1>Paket Semester per Gen</h1>
        <p class="a-proto-muted">Pilih hanya Gen–Mapel yang Anda ampu. Paket, TP, materi, dan soal tidak dibagikan otomatis ke Gen lain.</p>
    </div></header>
    <?php if($msg=session()->getFlashdata('galat')): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($msg) ?></div><?php endif ?>
    <?php if($msg=session()->getFlashdata('sukses')): ?><div class="a-alert" role="status"><?= esc($msg) ?></div><?php endif ?>
    <section class="a-card" aria-labelledby="judul-buat-paket">
        <h2 id="judul-buat-paket">Buat paket Gen–Mapel–Semester</h2>
        <?php if($gen===[] || $tahun===[] || $kurikulum===[]): ?>
            <div class="a-empty" role="status"><strong>Referensi belum lengkap.</strong><p>Mintalah Admin menetapkan Guru pada Gen–Mapel, serta mengisi tahun ajaran dan kurikulum resmi.</p></div>
        <?php else: ?>
            <form method="post" action="<?= esc(site_url('guru/paket'),'attr') ?>" class="a-form">
                <?= csrf_field() ?>
                <div class="a-guru-form-grid">
                    <div class="a-field"><label class="a-label" for="gen-mapel">Gen dan Mapel yang diampu</label>
                        <select id="gen-mapel" name="konteks" required>
                            <option value="">Pilih penugasan aktif</option>
                            <?php foreach($gen as $g): ?>
                                <option value="<?= (int)$g['angkatan_id'] ?>:<?= (int)$g['mapel_id'] ?>"><?= esc($g['gen_nama'].' · '.$g['mapel_nama']) ?></option>
                            <?php endforeach ?>
                        </select></div>
                    <div class="a-field"><label class="a-label" for="semester-paket">Semester SLA</label>
                        <select name="semester" id="semester-paket" required>
                        <?php for($s=1;$s<=6;$s++): ?><option value="<?= $s ?>">Semester <?= $s ?></option><?php endfor ?>
                        </select></div>
                    <div class="a-field"><label class="a-label" for="tahun-paket">Tahun ajaran</label>
                        <select name="tahun_ajaran_id" id="tahun-paket" required>
                        <?php foreach($tahun as $t): ?><option value="<?= (int)$t['id'] ?>"><?= esc($t['nama']) ?></option><?php endforeach ?>
                        </select></div>
                    <div class="a-field"><label class="a-label" for="kurikulum-paket">Kurikulum resmi</label>
                        <select name="kurikulum_id" id="kurikulum-paket" required>
                        <?php foreach($kurikulum as $k): ?><option value="<?= (int)$k['id'] ?>"><?= esc($k['nama'].' · '.$k['versi']) ?></option><?php endforeach ?>
                        </select></div>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Buat paket semester</button>
            </form>
        <?php endif ?>
    </section>
    <section class="a-card"><h2>Daftar paket yang boleh saya kelola</h2>
        <?php if($paket===[]): ?><div class="a-empty" role="status">Belum ada paket pada penugasan Anda.</div>
        <?php else: ?>
            <div class="a-tabel-gulir"><table class="a-tbl">
                <thead><tr><th scope="col">Gen</th><th scope="col">Mapel</th><th scope="col">Semester</th><th scope="col">Tahun ajaran</th><th scope="col">Kurikulum</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                <tbody><?php foreach($paket as $p): ?><tr>
                    <td><?= esc($p['gen_nama']) ?></td><td><?= esc($p['mapel_nama']) ?></td>
                    <td><?= (int)$p['semester'] ?></td><td><?= esc($p['tahun_nama']) ?></td>
                    <td><?= esc($p['kurikulum_nama']) ?></td><td><span class="a-badge"><?= esc($p['status']) ?></span></td>
                    <td><a class="a-btn a-btn--sekunder" href="<?= esc(site_url('guru/paket/'.(int)$p['id']),'attr') ?>">Buka paket</a></td>
                </tr><?php endforeach ?></tbody>
            </table></div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
