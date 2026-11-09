<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin'),'attr') ?>">Beranda Admin</a>
        <span aria-hidden="true">/</span><span aria-current="page">Jadwal Pra-Semester</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Admin · Penjadwalan Per Mapel</p>
            <h1>Jadwal Pra-Semester</h1>
            <p class="a-proto-muted">Tetapkan jendela boleh mulai ujian dan durasi pengerjaan per Gen–Mapel–Semester.
                Durasi setiap siswa dimulai ketika siswa masuk ujian, bukan saat jendela dibuka.</p>
        </div>
    </header>
    <?php if ($pesan = session()->getFlashdata('sukses')): ?>
        <div class="a-alert" role="status"><?= esc($pesan) ?></div>
    <?php endif ?>
    <?php if ($pesan = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($pesan) ?></div>
    <?php endif ?>
    <?php $filterQuery = http_build_query(array_filter($filter,
        static fn($value): bool => $value !== null && $value !== '')); ?>
    <section class="a-card a-pra-daftar" aria-labelledby="pra-daftar-judul">
        <div class="a-pra-section-head">
            <div>
                <p class="a-pra-kicker">PENGELOLAAN JADWAL</p>
                <h2 id="pra-daftar-judul">Daftar Pra-Semester</h2>
                <p class="a-help">Pilih kombinasi filter untuk mempersempit daftar paket.
                    Status mengacu pada kesiapan/jadwal ujian, bukan status TP.</p>
            </div>
            <span class="a-pra-total" aria-live="polite">
                <?= count($daftar) ?> paket ditemukan
            </span>
        </div>
        <form method="get" class="a-pra-filter a-pra-filter--admin"
            action="<?= esc(site_url('admin/pra-semester'),'attr') ?>"
            aria-label="Filter daftar jadwal Pra-Semester">
            <div class="a-field">
                <label class="a-label" for="pra-filter-gen">Gen</label>
                <select class="a-input" id="pra-filter-gen" name="gen">
                    <option value="">Semua Gen</option>
                    <?php foreach ($opsiFilter['gen'] as $id => $nama): ?>
                        <option value="<?= (int)$id ?>" <?= $filter['gen'] === (int)$id ? 'selected' : '' ?>>
                            <?= esc((string)$nama) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="a-field">
                <label class="a-label" for="pra-filter-mapel">Mata pelajaran</label>
                <select class="a-input" id="pra-filter-mapel" name="mapel">
                    <option value="">Semua Mapel</option>
                    <?php foreach ($opsiFilter['mapel'] as $id => $nama): ?>
                        <option value="<?= (int)$id ?>" <?= $filter['mapel'] === (int)$id ? 'selected' : '' ?>>
                            <?= esc((string)$nama) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="a-field">
                <label class="a-label" for="pra-filter-semester">Semester</label>
                <select class="a-input" id="pra-filter-semester" name="semester">
                    <option value="">Semua Semester</option>
                    <?php for ($sm=1;$sm<=6;$sm++): ?>
                        <option value="<?= $sm ?>" <?= $filter['semester'] === $sm ? 'selected' : '' ?>>
                            Semester <?= $sm ?>
                        </option>
                    <?php endfor ?>
                </select>
            </div>
            <div class="a-field">
                <label class="a-label" for="pra-filter-status">Status</label>
                <select class="a-input" id="pra-filter-status" name="status">
                    <?php foreach ([''=>'Semua status','belum'=>'Bank belum dibuat',
                        'draf'=>'Draf','siap'=>'Siap','ditutup'=>'Ditutup'] as $nilai=>$label): ?>
                        <option value="<?= esc((string)$nilai,'attr') ?>"
                            <?= $filter['status'] === $nilai ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="a-pra-filter__actions">
                <button type="submit" class="a-btn a-btn--utama">Terapkan filter</button>
                <a class="a-btn a-btn--sekunder"
                    href="<?= esc(site_url('admin/pra-semester'),'attr') ?>">Reset</a>
            </div>
        </form>
        <?php if ($daftar === []): ?>
            <div class="a-empty a-pra-empty" role="status">
                <strong>Tidak ada paket sesuai filter.</strong>
                <p></p>
            </div>
        <?php else: ?>
            <div class="a-tabel-gulir a-pra-table-wrap" tabindex="0"
                role="region" aria-label="Tabel daftar jadwal Pra-Semester">
                <table class="a-tbl a-pra-table">
                    <thead>
                        <tr><th scope="col">Gen</th><th scope="col">Mapel</th>
                            <th scope="col">Semester</th><th scope="col">Status ujian</th>
                            <th scope="col">Jendela mulai (WIB)</th>
                            <th scope="col">Durasi</th><th scope="col">Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar as $row): ?>
                            <?php $status = $row['ujian_id'] === null ? 'belum' : (string)$row['ujian_status'];
                                $label = ['belum'=>'Belum dibuat','draf'=>'Draf',
                                    'siap'=>'Siap','ditutup'=>'Ditutup'][$status] ?? $status;
                                $style = $status === 'siap' ? 'a-badge--siap' : 'a-badge--tunggu';
                                $tujuan = site_url('admin/pra-semester/'.(int)$row['paket_id']);
                                $params = http_build_query(array_filter($filter,
                                    static fn($x): bool => $x !== null && $x !== ''));
                                if ($params !== '') $tujuan .= '?' . $params; ?>
                            <tr <?= $pilih !== null && (int)$pilih['paket_id'] === (int)$row['paket_id']
                                ? 'class="a-pra-table__selected"' : '' ?>>
                                <th scope="row"><strong><?= esc((string)$row['nama_gen']) ?></strong></th>
                                <td><?= esc((string)$row['nama_mapel']) ?></td>
                                <td class="a-pra-col-center"><?= (int)$row['semester'] ?></td>
                                <td><span class="a-badge <?= $style ?>"><?= esc($label) ?></span></td>
                                <td class="a-pra-col-time">
                                    <?php if ($row['mulai_pada'] === null): ?>
                                        <span class="a-help">Belum dijadwalkan</span>
                                    <?php else: ?>
                                        <time><?= esc((string)$row['mulai_pada']) ?></time>
                                        <span class="a-pra-time-separator">s.d.</span>
                                        <time><?= esc((string)$row['selesai_pada']) ?></time>
                                    <?php endif ?>
                                </td>
                                <td class="a-pra-col-center"><?= $row['ujian_id'] === null
                                    ? '—' : (int)$row['durasi_menit'].' menit' ?></td>
                                <td><a class="a-btn a-btn--sekunder a-pra-action"
                                    href="<?= esc($tujuan,'attr') ?>"
                                    <?= $pilih !== null && (int)$pilih['paket_id'] === (int)$row['paket_id']
                                        ? 'aria-current="page"' : '' ?>>Atur jadwal</a></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
