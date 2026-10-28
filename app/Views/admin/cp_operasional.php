<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-cp a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin/kurikulum-cp'), 'attr') ?>">Kurikulum &amp; CP</a>
        <span aria-hidden="true">/</span><span aria-current="page"><?= esc($cp['kode_mapel']) ?> · CP Operasional</span>
    </nav>
    <header class="a-proto-heading a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Admin · CP operasional Fase D v2.5</p>
            <h1><?= esc($cp['mapel_nama']) ?> · CP Fase <?= esc($cp['fase']) ?></h1>
            <p class="a-proto-muted"><?= esc($cp['kurikulum_nama'] . ' · ' . $cp['kurikulum_versi']) ?></p>
        </div>
    </header>
    <section class="a-card">
        <h2>Satu CP Mapel, beberapa elemen dan versi resmi</h2>
        <p class="a-help">CP operasional ini beridentitas tunggal. Setiap elemen memiliki
            kode referensi dan riwayat versi sumber yang tersimpan terpisah.
            Penerbitan versi elemen tidak menimpa versi historis atau elemen lainnya.</p>
        <?php if ($elemen === []): ?>
            <div class="a-empty"><strong>Belum ada elemen CP.</strong>
                <p>Tambahkan elemen melalui formulir pada halaman Kurikulum &amp; CP.</p>
            </div>
        <?php else: ?>
            <div class="a-tabel-gulir">
                <table class="a-tbl a-tbl--cards">
                    <thead><tr>
                        <th scope="col">Kode referensi</th>
                        <th scope="col">Elemen CP</th>
                        <th scope="col">Versi aktif</th>
                        <th scope="col">Draf</th>
                        <th scope="col">Kaitan ATP</th>
                        <th scope="col">Tindakan</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($elemen as $e): ?>
                        <tr>
                            <th scope="row" data-label="Kode referensi"><?= esc($e['kode']) ?></th>
                            <td data-label="Elemen CP"><?= esc($e['elemen_aktif'] ?: ($e['elemen_draf'] ?: 'Belum ada')) ?>
                                <?php if ($e['rumusan_aktif']): ?>
                                    <small class="a-cp__detail"><?= esc(mb_strimwidth((string) $e['rumusan_aktif'], 0, 180, '…')) ?></small>
                                <?php endif ?>
                            </td>
                            <td data-label="Versi aktif"><?= $e['versi_aktif_id'] === null ? '—' : 'v' . (int) $e['versi_aktif'] ?></td>
                            <td data-label="Draf"><?= $e['versi_draf_id'] === null ? '—' : 'v' . (int) $e['versi_draf'] ?></td>
                            <td data-label="Kaitan ATP">
                                <?php if ($e['versi_aktif_id'] === null): ?>
                                    <span class="a-badge a-badge--tunggu">Belum terbit</span>
                                <?php elseif ($e['id_tautan_aktif'] === null): ?>
                                    <span class="a-badge a-badge--tunggu">Belum ditautkan (data lama)</span>
                                <?php else: ?>
                                    <span class="a-badge a-badge--siap">Aktif · dapat dirujuk ATP</span>
                                <?php endif ?>
                            </td>
                            <td data-label="Tindakan">
                                <a class="a-btn a-btn--sekunder"
                                    href="<?= esc(site_url('admin/kurikulum-cp/' . (int) $e['ref_cp_id']), 'attr') ?>">
                                    Kelola versi sumber
                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
        <div class="a-cp__aksi">
            <a class="a-btn a-btn--utama"
                href="<?= esc(site_url('admin/kurikulum-cp') . '#cp-tambah', 'attr') ?>">Tambah elemen CP</a>
            <a class="a-btn a-btn--sekunder"
                href="<?= esc(site_url('admin/kurikulum-cp') . '#cp-daftar', 'attr') ?>">Kembali ke daftar CP</a>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
