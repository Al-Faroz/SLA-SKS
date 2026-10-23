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
            <p class="a-proto-muted">Daftar TP berdasarkan urutan ATP per Gen dan mata pelajaran. Peta ini hanya dapat dibaca; penyuntingan dan impor ATP tersedia pada tahap berikutnya.</p>
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

        <?php foreach ($semester as $nomor => $data): ?>
            <section class="a-card a-jarak-atas" aria-labelledby="judul-semester-<?= (int) $nomor ?>">
                <h2 id="judul-semester-<?= (int) $nomor ?>">Semester <?= (int) $nomor ?></h2>
                <?php if ($data['paket'] === []): ?>
                    <div class="a-empty" role="status"><p>Belum ada paket pembelajaran pada semester ini.</p></div>
                <?php elseif ($data['tp'] === []): ?>
                    <div class="a-empty" role="status"><p>Paket telah dibuat, tetapi belum memiliki TP.</p></div>
                <?php else: ?>
                    <div class="a-tabel-gulir">
                        <table class="a-tbl">
                            <thead><tr><th scope="col">Urutan</th><th scope="col">Kode TP</th><th scope="col">Judul</th><th scope="col">Versi paket</th><th scope="col">Status TP</th></tr></thead>
                            <tbody>
                            <?php foreach ($data['tp'] as $tp): ?>
                                <?php $paketTp = null; foreach ($data['paket'] as $p) { if ((int) $p['id'] === (int) $tp['paket_semester_id']) { $paketTp = $p; break; } } ?>
                                <tr>
                                    <td><?= (int) $tp['urutan_atp'] ?></td>
                                    <td><?= esc($tp['kode_tp']) ?></td>
                                    <td><a href="<?= esc(site_url('guru/tp/' . (int) $tp['id']), 'attr') ?>"><?= esc($tp['judul']) ?></a></td>
                                    <td><?= $paketTp === null ? '—' : (int) $paketTp['nomor_versi'] ?></td>
                                    <td><span class="a-badge"><?= esc($tp['status']) ?></span></td>
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
