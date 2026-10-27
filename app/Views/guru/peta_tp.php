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
            <p class="a-proto-muted">Buat paket draf bila semester belum memiliki paket, lalu susun BAB dan TP per Gen–Mapel. Kode TP serta nomor BAB dibuat oleh server. Impor Excel manual dikerjakan pada tahap berikutnya.</p>
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
            <div class="a-alert a-alert--bahaya" role="alert"><?= esc(session()->getFlashdata('galat')) ?></div>
        <?php endif ?>
        <?php if (session('sukses')): ?>
            <div class="a-alert" role="status"><?= esc(session()->getFlashdata('sukses')) ?></div>
        <?php endif ?>

        <?php foreach ($semester as $nomor => $data): ?>
            <section class="a-card a-jarak-atas" aria-labelledby="judul-semester-<?= (int) $nomor ?>">
                <h2 id="judul-semester-<?= (int) $nomor ?>">Semester <?= (int) $nomor ?></h2>
                <?php if ($data['paket'] !== [] && ($data['pola_bab'] ?? false)): ?>
                    <?php $paketUntukBab = null; foreach ($data['paket'] as $candidate) { if ($candidate['status'] !== 'arsip') { $paketUntukBab = $candidate; break; } } ?>
                    <?php if ($paketUntukBab !== null && ($data['boleh_tambah_bab'] ?? true)): ?>
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
                    <?php elseif ($paketUntukBab !== null && ! ($data['boleh_tambah_bab'] ?? true)): ?>
                        <div class="a-alert" role="status">
                            <?= $nomor % 2 === 0
                                ? 'Lengkapi BAB pada semester ganjil di kelas yang sama terlebih dahulu, agar nomor BAB tetap berurutan.'
                                : 'Semester genap di kelas yang sama telah memiliki BAB. Tidak dapat menyisipkan BAB baru di semester ganjil tanpa peninjauan urutan.' ?>
                        </div>
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

                        <?php foreach ($data['bab'] as $bab): ?>
                            <?php if ($paketUntukBab === null || (int) $bab['paket_semester_id'] !== (int) $paketUntukBab['id']) { continue; } ?>
                            <details class="a-jarak-atas">
                                <summary>Tambah TP pada BAB <?= (int) $bab['nomor_bab'] ?> — <?= esc($bab['nama_bab']) ?></summary>
                                <form method="post" class="a-form a-jarak-atas" action="<?= esc(site_url('guru/tp/baru'), 'attr') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="bab_id" value="<?= (int) $bab['id'] ?>">
                                    <input type="hidden" name="pilihan" value="<?= (int) array_search($dipilih, $pilihan, true) ?>">
                                    <div class="a-field">
                                        <label class="a-label" for="nama-tp-bab-<?= (int) $bab['id'] ?>">Nama TP (maks. 35 karakter)</label>
                                        <input id="nama-tp-bab-<?= (int) $bab['id'] ?>" name="nama_tp" maxlength="35" required>
                                    </div>
                                    <div class="a-field">
                                        <label class="a-label" for="deskripsi-tp-bab-<?= (int) $bab['id'] ?>">Deskripsi TP (maks. 225 karakter)</label>
                                        <textarea id="deskripsi-tp-bab-<?= (int) $bab['id'] ?>" name="deskripsi_tp" maxlength="225" rows="3" required></textarea>
                                    </div>
                                    <div class="a-field">
                                        <label class="a-label" for="jenis-tp-bab-<?= (int) $bab['id'] ?>">Jenis TP</label>
                                        <select id="jenis-tp-bab-<?= (int) $bab['id'] ?>" name="jenis_tp" required>
                                            <option value="esensial">Esensial — tidak ada Materi dan nilai</option>
                                            <option value="non_esensial">NonEsensial — satu Materi, Diagnostik dan Sumatif</option>
                                        </select>
                                    </div>
                                    <p class="a-proto-muted">Kode dan urutan TP dibuat otomatis oleh server. TP baru berstatus draf.</p>
                                    <button class="a-btn a-btn--utama" type="submit">Simpan TP Draf</button>
                                </form>
                            </details>
                        <?php endforeach ?>
                    <?php else: ?>
                        <p class="a-proto-muted a-jarak-atas">Belum ada BAB pada semester ini.</p>
                    <?php endif ?>
                <?php endif ?>
                <?php if ($data['paket'] === []): ?>
                    <div class="a-empty" role="status"><p>Belum ada paket pembelajaran pada semester ini.</p></div>
                    <?php if ($nomor >= (int) ($dipilih['semester_mulai'] ?? 1)
                        && ($opsiPaket['tahun'] ?? []) !== [] && ($opsiPaket['kurikulum'] ?? []) !== []): ?>
                        <form method="post" class="a-form a-jarak-atas" action="<?= esc(site_url('guru/paket-semester/baru'), 'attr') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="pilihan" value="<?= (int) array_search($dipilih, $pilihan, true) ?>">
                            <input type="hidden" name="gen_id" value="<?= (int) $dipilih['angkatan_id'] ?>">
                            <input type="hidden" name="mapel_id" value="<?= (int) $dipilih['mapel_id'] ?>">
                            <input type="hidden" name="semester" value="<?= (int) $nomor ?>">
                            <div class="a-field">
                                <label class="a-label" for="tahun-paket-<?= (int) $nomor ?>">Tahun ajaran</label>
                                <select id="tahun-paket-<?= (int) $nomor ?>" name="tahun_id" required>
                                    <option value="">Pilih tahun ajaran</option>
                                    <?php foreach ($opsiPaket['tahun'] as $tahun): ?>
                                        <option value="<?= (int) $tahun['id'] ?>"><?= esc($tahun['nama']) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="a-field">
                                <label class="a-label" for="kurikulum-paket-<?= (int) $nomor ?>">Kurikulum</label>
                                <select id="kurikulum-paket-<?= (int) $nomor ?>" name="kurikulum_id" required>
                                    <option value="">Pilih kurikulum</option>
                                    <?php foreach ($opsiPaket['kurikulum'] as $kurikulum): ?>
                                        <option value="<?= (int) $kurikulum['id'] ?>"><?= esc($kurikulum['nama'] . ' — ' . $kurikulum['versi']) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <p class="a-proto-muted">Paket baru hanya disimpan sebagai draf. Siswa belum dapat mengaksesnya.</p>
                            <button type="submit" class="a-btn a-btn--utama">Buat Paket Draf Semester <?= (int) $nomor ?></button>
                        </form>
                    <?php elseif ($nomor >= (int) ($dipilih['semester_mulai'] ?? 1)): ?>
                        <div class="a-alert" role="status">Tahun ajaran atau kurikulum belum tersedia. Hubungi Admin untuk melengkapi referensi.</div>
                    <?php endif ?>
                <?php elseif ($data['tp'] === []): ?>
                    <div class="a-empty" role="status"><p>Paket telah dibuat, tetapi belum memiliki TP.</p></div>
                <?php else: ?>
                    <div class="a-tabel-gulir">
                        <table class="a-tbl a-tbl--cards a-tbl--peta-atp">
                            <thead><tr><th scope="col">BAB</th><th scope="col">Kode TP</th><th scope="col">Nama TP</th><th scope="col">Deskripsi</th><th scope="col">Jenis</th><th scope="col">Versi paket</th><th scope="col">Status TP</th></tr></thead>
                            <tbody>
                            <?php foreach ($data['tp'] as $tp): ?>
                                <?php $paketTp = null; foreach ($data['paket'] as $p) { if ((int) $p['id'] === (int) $tp['paket_semester_id']) { $paketTp = $p; break; } } ?>
                                <?php $labelBab = '—'; foreach ($data['bab'] as $itemBab) { if ((int) $itemBab['id'] === (int) ($tp['bab_id'] ?? 0)) { $labelBab = 'BAB ' . (int) $itemBab['nomor_bab']; break; } } ?>
                                <tr>
                                    <th scope="row" data-label="BAB"><?= esc($labelBab) ?></th>
                                    <td data-label="Kode TP"><?= esc($tp['kode_tp']) ?></td>
                                    <td data-label="Nama TP" class="a-tbl--peta-atp__judul">
                                        <?php if ((int) ($tp['bab_id'] ?? 0) > 0): ?>
                                            <?= esc(($tp['nama_tp'] ?? '') !== '' ? $tp['nama_tp'] : $tp['judul']) ?>
                                        <?php else: ?>
                                            <a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>"><?= esc($tp['judul']) ?></a>
                                        <?php endif ?>
                                    </td>
                                    <td data-label="Deskripsi"><?= esc($tp['deskripsi_tp'] ?? '—') ?></td>
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
