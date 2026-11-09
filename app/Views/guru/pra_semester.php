<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span><span aria-current="page">Bank Pra-Semester</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Gen–Mapel–Semester</p>
            <h1>Bank Soal Pra-Semester</h1>
            <p class="a-proto-muted">Minimal 30 soal sah mencakup seluruh BAB. Jadwal oleh Admin.</p>
        </div>
    </header>
    <?php if ($pesan = session()->getFlashdata('sukses')): ?>
        <div class="a-alert" role="status"><?= esc($pesan) ?></div>
    <?php endif ?>
    <?php if ($pesan = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>
    <section class="a-card a-pra-daftar" aria-labelledby="pra-paket-guru">
        <div class="a-pra-section-head">
            <div>
                <p class="a-pra-kicker">BANK SOAL PER PAKET</p>
                <h2 id="pra-paket-guru">Pilih Gen dan Semester</h2>
                <p class="a-help">Hanya paket dari penugasan Guru Gen–Mapel Anda yang ditampilkan.</p>
            </div>
            <span class="a-pra-total" aria-live="polite">
                <?= count($paketGuru) ?> paket ditemukan
            </span>
        </div>
        <form method="get" class="a-pra-filter a-pra-filter--guru"
            action="<?= esc(site_url('guru/pra-semester'),'attr') ?>"
            aria-label="Filter Gen bank soal Pra-Semester">
            <div class="a-field">
                <label class="a-label" for="pra-guru-filter-gen">Gen</label>
                <select class="a-input" id="pra-guru-filter-gen" name="gen">
                    <option value="">Semua Gen yang diampu</option>
                    <?php foreach ($pilihanGen as $id => $nama): ?>
                        <option value="<?= (int)$id ?>"
                            <?= $filterGen === (int)$id ? 'selected' : '' ?>>
                            <?= esc((string)$nama) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="a-pra-filter__actions">
                <button type="submit" class="a-btn a-btn--utama">Terapkan filter</button>
                <a class="a-btn a-btn--sekunder"
                    href="<?= esc(site_url('guru/pra-semester'),'attr') ?>">Reset</a>
            </div>
        </form>
        <?php if ($paketGuru === []): ?>
            <div class="a-empty a-pra-empty" role="status">
                <strong>Tidak ada paket sesuai filter.</strong>
                <p>Periksa pilihan Gen atau penugasan Anda sebagai Guru pengampu.</p>
            </div>
        <?php else: ?>
            <div class="a-tabel-gulir a-pra-table-wrap" tabindex="0"
                role="region" aria-label="Daftar paket Pra-Semester Guru">
                <table class="a-tbl a-pra-table">
                    <thead>
                        <tr><th scope="col">Gen</th><th scope="col">Mata pelajaran</th>
                            <th scope="col">Semester</th><th scope="col">Status paket</th>
                            <th scope="col">Bank ujian</th><th scope="col">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paketGuru as $p): ?>
                            <?php $ujianStatus = $p['ujian_id'] === null ? 'belum' : (string)$p['ujian_status'];
                                $ujianLabel = ['belum'=>'Belum dibuat','draf'=>'Draf',
                                    'siap'=>'Siap','ditutup'=>'Ditutup'][$ujianStatus] ?? $ujianStatus;
                                $tujuan=site_url('guru/pra-semester/'.(int)$p['id']);
                                if ($filterGen !== null) $tujuan .= '?gen=' . $filterGen; ?>
                            <tr <?= $konteks !== null && (int)$konteks['paket']['id'] === (int)$p['id']
                                ? 'class="a-pra-table__selected"' : '' ?>>
                                <th scope="row"><strong><?= esc((string)$p['nama_gen']) ?></strong></th>
                                <td><?= esc((string)$p['nama_mapel']) ?></td>
                                <td class="a-pra-col-center"><?= (int)$p['semester'] ?></td>
                                <td><span class="a-badge <?= $p['status'] === 'aktif'
                                    ? 'a-badge--siap' : 'a-badge--tunggu' ?>">
                                    <?= esc(ucfirst((string)$p['status'])) ?></span></td>
                                <td><span class="a-badge <?= $ujianStatus === 'siap'
                                    ? 'a-badge--siap' : 'a-badge--tunggu' ?>">
                                    <?= esc($ujianLabel) ?></span></td>
                                <td><a class="a-btn a-btn--sekunder a-pra-action"
                                    href="<?= esc($tujuan,'attr') ?>"
                                    <?= $konteks !== null && (int)$konteks['paket']['id'] === (int)$p['id']
                                        ? 'aria-current="page"' : '' ?>>Kelola soal</a></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
