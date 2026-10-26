<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Peta ATP</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">Guru · Pembelajaran</p>
            <h1>Peta ATP enam semester</h1>
            <p class="a-proto-muted">Susun BAB per semester sesuai Gen–Mapel. Nomor BAB diatur server dan berlanjut antardua semester pada kelas yang sama. TP dan impor Excel dilanjutkan pada tahap berikutnya.</p>
        </div>
    </header>

    <?php if ($pilihan === []): ?>
        <section class="a-card" aria-labelledby="judul-kosong">
            <div class="a-empty" role="status">
                <h2 id="judul-kosong">Belum ada penugasan Guru</h2>
                <p>Hubungi Admin untuk penetapan Guru pada Gen dan mata pelajaran sebelum mengakses peta ATP.</p>
            </div>
        </section>
    <?php else: ?>
        <section class="a-card a-page__editor" aria-labelledby="judul-pilihan">
            <h2 id="judul-pilihan">Pilih Gen dan mata pelajaran</h2>
            <form method="get" action="<?= esc(site_url('guru/peta-atp'), 'attr') ?>" class="a-form">
                <div class="a-field">
                    <label class="a-label" for="pilih-g1">Penugasan Anda</label>
                    <select id="pilih-g1" name="pilihan" required>
                        <?php foreach ($pilihan as $nomor => $item): ?>
                            <option value="<?= (int) $nomor ?>" <?= $dipilih !== null && (int) $dipilih['angkatan_id'] === (int) $item['angkatan_id'] && (int) $dipilih['mapel_id'] === (int) $item['mapel_id'] ? 'selected' : '' ?>>
                                <?= esc($item['kode_gen'] . ' — ' . $item['nama_mapel']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Tampilkan peta</button>
            </form>
        </section>

        <?php if (session('galat')): ?>
            <p class="a-alert a-alert--galat" role="alert"><?= esc(session('galat')) ?></p>
        <?php endif ?>
        <?php if (session('sukses')): ?>
            <p class="a-alert a-alert--sukses" role="status"><?= esc(session('sukses')) ?></p>
        <?php endif ?>

        <?php foreach ($semester as $nomor => $data): ?>
            <section class="a-card a-jarak-atas" aria-labelledby="judul-semester-<?= (int) $nomor ?>">
                <h2 id="judul-semester-<?= (int) $nomor ?>">Semester <?= (int) $nomor ?></h2>
                <?php if ($data['paket'] !== [] && ($data['pola_bab'] ?? false)): ?>
                    <?php $paketUntukBab = null; foreach ($data['paket'] as $candidate) { if ($candidate['status'] !== 'arsip') { $paketUntukBab = $candidate; break; } } ?>
                    <?php if ($paketUntukBab !== null): ?>
                        <form class="a-form a-jarak-atas" method="post" action="<?= esc(site_url('guru/bab/baru'), 'attr') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="paket_id" value="<?= (int) $paketUntukBab['id'] ?>">
                            <input type="hidden" name="pilihan" value="<?= (int) array_search($dipilih, $pilihan, true) ?>">
                            <div class="a-field">
                                <label class="a-label" for="nama-bab-<?= (int) $nomor ?>">Nama BAB (maks. 35 karakter)</label>
                                <input id="nama-bab-<?= (int) $nomor ?>" name="nama_bab" maxlength="35" required>
                            </div>
                            <div class="a-field">
                                <label class="a-label" for="deskripsi-bab-<?= (int) $nomor ?>">Deskripsi BAB (maks. 225 karakter)</label>
                                <textarea id="deskripsi-bab-<?= (int) $nomor ?>" name="deskripsi_bab" maxlength="225" rows="2" required></textarea>
                            </div>
                            <button class="a-btn a-btn--utama" type="submit">Tambah BAB Semester <?= (int) $nomor ?></button>
                        </form>
                    <?php endif ?>
                    <?php if ($data['bab'] !== []): ?>
                        <div class="a-tabel-gulir a-jarak-atas">
                            <table class="a-tbl">
                                <thead><tr><th scope="col">Nomor BAB</th><th scope="col">Nama BAB</th><th scope="col">Deskripsi</th></tr></thead>
                                <tbody>
                                <?php foreach ($data['bab'] as $bab): ?>
                                    <tr>
                                        <th scope="row" data-label="Nomor BAB"><?= (int) $bab['nomor_bab'] ?></th>
                                        <td data-label="Nama BAB"><?= esc($bab['nama_bab']) ?></td>
                                        <td data-label="Deskripsi"><?= esc($bab['deskripsi_bab']) ?></td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="a-proto-muted a-jarak-atas">Belum ada BAB pada semester ini.</p>
                    <?php endif ?>
                <?php endif ?>
                <?php if ($data['paket'] === []): ?>
                    <div class="a-empty" role="status"><p>Belum ada paket pembelajaran pada semester ini.</p></div>
                <?php elseif ($data['tp'] === []): ?>
                    <div class="a-empty" role="status"><p>Paket telah dibuat, tetapi belum memiliki TP.</p></div>
                <?php else: ?>
                    <div class="a-tabel-gulir">
                        <table class="a-tbl a-tbl--cards a-tbl--peta-atp">
                            <thead><tr><th scope="col">BAB</th><th scope="col">Kode TP</th><th scope="col">Nama TP</th><th scope="col">Jenis</th><th scope="col">Versi paket</th><th scope="col">Status TP</th></tr></thead>
                            <tbody>
                            <?php foreach ($data['tp'] as $tp): ?>
                                <?php $paketTp = null; foreach ($data['paket'] as $p) { if ((int) $p['id'] === (int) $tp['paket_semester_id']) { $paketTp = $p; break; } } ?>
                                <?php $labelBab = '—'; foreach ($data['bab'] as $itemBab) { if ((int) $itemBab['id'] === (int) ($tp['bab_id'] ?? 0)) { $labelBab = 'BAB ' . (int) $itemBab['nomor_bab']; break; } } ?>
                                <tr>
                                    <th scope="row" data-label="BAB"><?= esc($labelBab) ?></th>
                                    <td data-label="Kode TP"><?= esc($tp['kode_tp']) ?></td>
                                    <td data-label="Nama TP" class="a-tbl--peta-atp__judul"><a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>"><?= esc($tp['nama_tp'] ?: $tp['judul']) ?></a></td>
                                    <td data-label="Jenis"><?= esc(isset($tp['jenis_tp']) ? ($tp['jenis_tp'] === 'non_esensial' ? 'NonEsensial' : 'Esensial') : 'Lama') ?></td>
                                    <td data-label="Versi paket"><?= $paketTp === null ? '—' : (int) $paketTp['nomor_versi'] ?></td>
                                    <td data-label="Status TP"><span class="a-badge"><?= esc($tp['status']) ?></span></td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </section>
        <?php endforeach ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
