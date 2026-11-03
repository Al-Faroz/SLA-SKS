<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-sertifikat">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin'),'attr') ?>">Beranda Admin</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('admin/sertifikat'),'attr') ?>">Sertifikat</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page">Template dan TTD</span>
    </nav>
    <header class="a-page__heading a-proto-heading">
        <div>
            <p class="a-proto-eyebrow">ADMIN · BERKAS PRIVAT SERTIFIKAT</p>
            <h1>Template dan Tanda Tangan</h1>
            <p class="a-proto-muted">Background A4 landscape dan tanda tangan kepala madrasah
                disimpan sebagai versi historis. Unggah tidak langsung mengaktifkan berkas.</p>
        </div>
    </header>
    <?php if ($sukses=session()->getFlashdata('sukses')): ?>
        <div class="a-alert a-alert--sukses" role="status"><?= esc($sukses) ?></div>
    <?php endif ?>
    <?php if ($galat=session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>
    <section class="a-card a-jarak-atas">
        <h2>Status Berkas Aktif</h2>
        <div class="a-sertifikat__status">
            <div class="a-sertifikat__status-item">
                <span>Background Sertifikat</span>
                <strong><?= isset($aktif['background'])
                    ? 'Template aktif #'.(int)$aktif['background']['id']
                    : 'Template bawaan (fallback)' ?></strong>
                <p class="a-help">Background boleh kosong. Unggah dan tinjau PDF contoh
                    sebelum mengaktifkan template baru.</p>
                <a class="a-btn a-btn--sekunder" target="_blank" rel="noopener"
                    href="<?= esc(site_url('admin/sertifikat/template/pratinjau/0'),'attr') ?>">
                    Pratinjau Template Bawaan
                </a>
            </div>
            <div class="a-sertifikat__status-item">
                <span>Tanda Tangan Kepala Madrasah</span>
                <strong><?= isset($aktif['ttd']) ? 'TTD aktif #'.(int)$aktif['ttd']['id']
                    : 'Belum tersedia' ?></strong>
                <p class="a-help">PNG wajib sebelum penerbitan sertifikat.
                    Berkas sumber TTD tidak boleh diakses publik.</p>
            </div>
        </div>
    </section>
    <div class="a-sertifikat__status a-jarak-atas">
        <section class="a-card" aria-labelledby="unggah-background">
            <h2 id="unggah-background">Unggah Background</h2>
            <p class="a-help">JPG/PNG maksimal 8 MB, rasio A4 landscape 297:210 ±1,5%,
                minimal 1188×840 px. Lihat pratinjau PDF sebelum aktivasi.</p>
            <form class="a-form" method="post" enctype="multipart/form-data"
                action="<?= esc(site_url('admin/sertifikat/aset/background'),'attr') ?>">
                <?= csrf_field() ?>
                <div class="a-field">
                    <label class="a-label" for="berkas-background">Gambar sertifikat</label>
                    <input class="a-input" id="berkas-background" name="gambar"
                        type="file" accept=".png,.jpg,.jpeg,image/png,image/jpeg" required>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Unggah Background</button>
            </form>
        </section>
        <section class="a-card" aria-labelledby="unggah-ttd">
            <h2 id="unggah-ttd">Unggah Tanda Tangan PNG</h2>
            <p class="a-help">PNG maksimal 8 MB, min. 150×60 px,
                maks. 2500×1600 px. Latar transparan dianjurkan.</p>
            <form class="a-form" method="post" enctype="multipart/form-data"
                action="<?= esc(site_url('admin/sertifikat/aset/ttd'),'attr') ?>">
                <?= csrf_field() ?>
                <div class="a-field">
                    <label class="a-label" for="berkas-ttd">Gambar tanda tangan</label>
                    <input class="a-input" id="berkas-ttd" name="gambar"
                        type="file" accept=".png,image/png" required>
                </div>
                <button class="a-btn a-btn--utama" type="submit">Unggah TTD</button>
            </form>
        </section>
    </div>
    <section class="a-card a-jarak-atas" aria-labelledby="daftar-aset">
        <h2 id="daftar-aset">Riwayat Berkas Sertifikat</h2>
        <?php if ($aset===[]): ?>
            <div class="a-alert" role="status">Belum ada berkas yang diunggah.
                Template bawaan tetap bisa dipratinjau.</div>
        <?php else: ?>
            <div class="a-tabel-gulir a-sertifikat__scroll" tabindex="0"
                role="region" aria-label="Tabel riwayat berkas dapat digulir">
                <table class="a-tbl a-sertifikat__table">
                    <thead><tr>
                        <th scope="col">Jenis</th>
                        <th scope="col">Pratinjau</th>
                        <th scope="col">Ukuran</th>
                        <th scope="col">Waktu Unggah UTC</th>
                        <th scope="col">Status / Aksi</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($aset as $r): ?>
                        <tr>
                            <th scope="row"><?= $r['jenis']==='background'
                                ? 'Background' : 'Tanda Tangan' ?> #<?= (int)$r['id'] ?></th>
                            <td>
                                <a href="<?= esc(site_url('admin/sertifikat/aset/'
                                    .(int)$r['id'].'/gambar'),'attr') ?>"
                                    target="_blank" rel="noopener">Lihat gambar</a>
                                <?php if ($r['jenis']==='background'): ?>
                                    <a class="a-btn a-btn--sekunder" target="_blank" rel="noopener"
                                        href="<?= esc(site_url('admin/sertifikat/template/pratinjau/'
                                            .(int)$r['id']),'attr') ?>">Pratinjau PDF</a>
                                <?php endif ?>
                            </td>
                            <td><?= (int)$r['lebar'] ?>×<?= (int)$r['tinggi'] ?> px ·
                                <?= esc(number_format((int)$r['ukuran_byte']/1024,0,',','.')) ?> KB</td>
                            <td><?= esc((string)$r['dibuat_pada']) ?></td>
                            <td>
                                <?php if ($r['aktif']): ?>
                                    <span class="a-sertifikat__badge a-sertifikat__badge--layak">Aktif</span>
                                <?php else: ?>
                                    <form method="post" action="<?= esc(site_url(
                                        'admin/sertifikat/aset/'.(int)$r['id'].'/aktifkan'),'attr') ?>">
                                        <?= csrf_field() ?>
                                        <button class="a-btn a-btn--utama" type="submit">
                                            Aktifkan <?= $r['jenis']==='background'?'Template':'TTD' ?>
                                        </button>
                                    </form>
                                    <?php if ($r['jenis']==='background'): ?>
                                        <p class="a-help">Harus membuka Pratinjau PDF lebih dahulu.</p>
                                    <?php endif ?>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
