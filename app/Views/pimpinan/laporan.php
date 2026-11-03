<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-laporan">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('pimpinan'),'attr') ?>">Beranda Pimpinan</a>
        <span aria-hidden="true">/</span><span aria-current="page">Monitoring Akademik</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Pimpinan · Monitoring Baca-Saja</p>
            <h1>Monitoring Akademik Siswa</h1>
            <p class="a-proto-muted">Progres TP NonEsensial berdasarkan aktivitas sah.
                Semua informasi hanya untuk pemantauan, tanpa perubahan nilai atau jadwal.</p>
        </div>
    </header>
    <?php if ($pesan=session()->getFlashdata('galat_laporan')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>
    <section class="a-card a-jarak-atas" aria-labelledby="filter-monitor">
        <h2 id="filter-monitor">Pilih Gen, Mata Pelajaran, dan Semester</h2>
        <?php if ($pilihan === []): ?>
            <div class="a-alert" role="status">Belum ada paket akademik aktif yang ditugaskan.</div>
        <?php else: ?>
            <form method="get" action="<?= esc(site_url('pimpinan/laporan'),'attr') ?>"
                class="a-form a-laporan__filter">
                <div class="a-field">
                    <label class="a-label" for="pilih-paket-monitor">Paket Akademik</label>
                    <select class="a-input" name="paket" id="pilih-paket-monitor" required>
                        <?php foreach ($pilihan as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"
                                <?= (int)($terpilih['id'] ?? 0)===(int)$p['id'] ? 'selected' : '' ?>>
                                <?= esc($p['nama_gen'].' — '.$p['nama_mapel'].' — Semester '.$p['semester']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Tampilkan Monitoring</button>
            </form>
        <?php endif ?>
    </section>
    <?php if ($monitor !== null): ?>
        <section class="a-card a-jarak-atas" aria-labelledby="tabel-monitor">
            <h2 id="tabel-monitor">Progres <?= esc($monitor['paket']['nama_gen']) ?> ·
                <?= esc($monitor['paket']['nama_mapel']) ?> · Semester
                <?= (int)$monitor['paket']['semester'] ?></h2>
            <p class="a-help">Total <?= (int)$monitor['total'] ?> siswa ·
                <?= (int)$monitor['total_tp'] ?> TP NonEsensial ·
                Halaman <?= (int)$monitor['halaman'] ?> dari
                <?= (int)$monitor['jumlah_halaman'] ?>. Persentase bukan rata-rata nilai.</p>
            <?php if ($monitor['siswa'] === []): ?>
                <div class="a-alert" role="status">Belum ada siswa pada Gen ini.</div>
            <?php else: ?>
                <div class="a-tabel-gulir a-laporan__scroll" tabindex="0"
                    role="region" aria-label="Tabel monitoring dapat digulir">
                    <table class="a-tbl a-laporan__tabel">
                        <thead><tr>
                            <th scope="col">Nama Siswa</th>
                            <th scope="col">TP Selesai Sah</th>
                            <th scope="col">Progres</th>
                            <th scope="col">Laporan Individual</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($monitor['siswa'] as $s): ?>
                            <tr>
                                <th scope="row"><?= esc($s['nama']) ?></th>
                                <td><?= $s['dilewati'] ? 'Dilewati'
                                    : (int)$s['selesai'].' / '.(int)$s['total_tp'] ?></td>
                                <td>
                                    <?php if ($s['persen'] === null): ?>
                                        <span class="a-help"><?= $s['dilewati']
                                            ? 'Dilewati' : 'Belum ada TP NonEsensial' ?></span>
                                    <?php else: ?>
                                        <div class="a-laporan__progres">
                                            <progress max="100" value="<?= esc((string)$s['persen'],'attr') ?>"
                                                aria-label="Progres TP <?= esc($s['nama'],'attr') ?>"></progress>
                                            <strong><?= esc(number_format((float)$s['persen'],1,',','.')) ?>%</strong>
                                        </div>
                                    <?php endif ?>
                                </td>
                                <td><a class="a-btn a-btn--sekunder"
                                    href="<?= esc(site_url('pimpinan/laporan/'.(int)$monitor['paket']['id']
                                        .'/siswa/'.(int)$s['id']),'attr') ?>">Lihat Laporan</a></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
            <?php if ($monitor['jumlah_halaman'] > 1): ?>
                <nav class="a-penilaian-pager" aria-label="Halaman monitoring">
                    <?php $urlHalaman=static fn (int $n): string =>
                        site_url('pimpinan/laporan').'?'.http_build_query([
                            'paket'=>$monitor['paket']['id'],'halaman'=>$n,
                        ]); ?>
                    <?php if ($monitor['halaman'] > 1): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc($urlHalaman($monitor['halaman']-1),'attr') ?>">Sebelumnya</a>
                    <?php endif ?>
                    <span>Halaman <?= (int)$monitor['halaman'] ?></span>
                    <?php if ($monitor['halaman'] < $monitor['jumlah_halaman']): ?>
                        <a class="a-btn a-btn--sekunder"
                            href="<?= esc($urlHalaman($monitor['halaman']+1),'attr') ?>">Berikutnya</a>
                    <?php endif ?>
                </nav>
            <?php endif ?>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
