<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<div class="a-page a-atp-detail">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('guru'), 'attr') ?>">Beranda Guru</a>
        <span aria-hidden="true">/</span>
        <a href="<?= esc(site_url('guru/peta-atp') . '?pilihan=' . (int) $nomorPilihan, 'attr') ?>">Peta ATP</a>
        <span aria-hidden="true">/</span><span aria-current="page">Semester <?= (int) $nomorSemester ?></span>
    </nav>
    <?php
        $paketAktif = null;
        foreach ($semester['paket'] as $paket) {
            if ($paket['status'] !== 'arsip') { $paketAktif = $paket; break; }
        }
        $sebelumMulaiGen = $nomorSemester < (int) $dipilih['semester_mulai'];
        $bolehTambahBab = $paketAktif !== null
            && ($semester['pola_bab'] ?? false) && ($semester['boleh_tambah_bab'] ?? false);
        $status = match ($ringkasan['status']) {
            'aktif' => 'Paket aktif', 'draf' => 'Paket draf',
            'arsip' => 'Paket arsip', default => 'Belum ada paket',
        };
    ?>
    <header class="a-atp-detail__header a-atp-warna-<?= (int) $nomorSemester ?>">
        <div>
            <p class="a-atp-detail__eyebrow"><?= esc($dipilih['kode_gen'] . ' · ' . $dipilih['nama_mapel']) ?></p>
            <h1>Semester <?= (int) $nomorSemester ?> <small>· Kelas <?= esc($kelas) ?></small></h1>
            <span class="a-atp-detail__status"><?= esc($status) ?><?= $sebelumMulaiGen ? ' · Persiapan Guru (belum untuk siswa)' : '' ?></span>
        </div>
        <div class="a-atp-detail__metrik" aria-label="Ringkasan data semester">
            <span><strong><?= (int) $ringkasan['bab'] ?></strong> BAB</span>
            <span><strong><?= (int) $ringkasan['tp'] ?></strong> TP</span>
            <span><strong><?= (int) $ringkasan['esensial'] ?></strong> Esensial</span>
            <span><strong><?= (int) $ringkasan['non_esensial'] ?></strong> NonEsensial</span>
        </div>
    </header>

    <?php $galat = session()->getFlashdata('galat'); $sukses = session()->getFlashdata('sukses'); ?>
    <?php if ($batasNamaBab < 50 || $batasNamaTp < 50): ?>
        <div class="a-alert a-alert--info" role="status">
            Database saat ini membatasi nama BAB hingga <?= (int) $batasNamaBab ?> dan nama TP hingga
            <?= (int) $batasNamaTp ?> karakter. Target 50 karakter akan aktif setelah patch pelebaran kolom diimpor.
            Data BAB dan TP yang sudah ada tetap dipertahankan.
        </div>
    <?php endif ?>
    <?php if ($galat): ?><div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div><?php endif ?>
    <?php if ($sukses): ?><div class="a-alert" role="status"><?= esc($sukses) ?></div><?php endif ?>

    <section class="a-card a-atp-detail__toolbar" aria-labelledby="panduan-atp">
        <div>
            <h2 id="panduan-atp">Susun BAB dan TP</h2>
            <p class="a-proto-muted">Mulai dengan BAB sesuai ATP. Nomor BAB dan kode TP dibuat server; TP baru disimpan sebagai draf.
                Batas nama mengikuti kapasitas database terpasang; deskripsi maksimal 225 karakter. Pilih jenis TP sesuai tujuan pembelajaran.</p>
            <?php if ($paketAktif !== null && ! $bolehTambahBab): ?>
                <p class="a-atp-pesan-terkunci">Penambahan BAB saat ini terkunci oleh aturan nomor antarsemester.
                    Periksa urutan BAB semester ganjil/genap pada kelas yang sama.</p>
            <?php elseif (! ($semester['pola_bab'] ?? false)): ?>
                <p class="a-atp-pesan-terkunci">Database v4 R2 diperlukan untuk penyusunan BAB dan TP.</p>
            <?php endif ?>
        </div>
        <div class="a-atp-detail__toolbar-actions">
            <?php if ($bolehTambahBab): ?>
                <button class="a-btn a-btn--utama" type="button" data-atp-dialog-open="dialog-bab-semester"
                    aria-haspopup="dialog" aria-controls="dialog-bab-semester">+ Tambah BAB</button>
            <?php elseif ($paketAktif === null): ?>
                <a class="a-btn a-btn--utama"
                    href="<?= esc(site_url('guru/peta-atp') . '?pilihan=' . (int) $nomorPilihan, 'attr') ?>">Buat Draf Enam Semester di Peta ATP</a>
            <?php endif ?>
        </div>
    </section>

    <?php if ($bolehTambahBab): ?>
        <dialog id="dialog-bab-semester" class="a-modal a-modal--atp" aria-labelledby="judul-dialog-bab">
            <div class="a-modal-atp__header">
                <h2 id="judul-dialog-bab">Tambah BAB — Semester <?= (int) $nomorSemester ?></h2>
                <button class="a-btn a-btn--sekunder" type="button" aria-label="Tutup dialog BAB" data-atp-dialog-close>✕</button>
            </div>
            <p class="a-proto-muted">Nomor BAB diisi otomatis berdasarkan urutan semester dalam kelas ini.</p>
            <form class="a-form" method="post" action="<?= esc(site_url('guru/bab/baru'), 'attr') ?>" data-atp-dialog-form>
                <?= csrf_field() ?>
                <input type="hidden" name="paket_id" value="<?= (int) $paketAktif['id'] ?>">
                <input type="hidden" name="pilihan" value="<?= (int) $nomorPilihan ?>">
                <input type="hidden" name="semester" value="<?= (int) $nomorSemester ?>">
                <div class="a-field">
                    <label class="a-label" for="nama-bab-semester">Nama BAB (maks. <?= (int) $batasNamaBab ?> karakter)</label>
                    <input id="nama-bab-semester" name="nama_bab" maxlength="<?= (int) $batasNamaBab ?>" required autocomplete="off" autofocus>
                </div>
                <div class="a-field">
                    <label class="a-label" for="deskripsi-bab-semester">Deskripsi BAB (maks. 225 karakter)</label>
                    <textarea id="deskripsi-bab-semester" name="deskripsi_bab" maxlength="225" rows="3" required></textarea>
                </div>
                <div class="a-modal-atp__actions">
                    <button type="button" class="a-btn a-btn--sekunder" data-atp-dialog-close>Batal</button>
                    <button type="submit" class="a-btn a-btn--utama">Simpan BAB</button>
                </div>
            </form>
        </dialog>
    <?php endif ?>

    <section class="a-atp-bab-list" aria-labelledby="daftar-bab">
        <div class="a-atp-kelompok__heading">
            <h2 id="daftar-bab">Daftar BAB Semester <?= (int) $nomorSemester ?></h2>
            <p class="a-proto-muted">Setiap BAB memuat TP yang disusun untuk semester ini.</p>
        </div>
        <?php if ($semester['bab'] === []): ?>
            <div class="a-card"><div class="a-empty" role="status">
                <h3>Belum ada BAB</h3>
                <p><?= $paketAktif === null
                    ? 'Buat paket draf enam semester dari Peta ATP terlebih dahulu.'
                    : 'Gunakan tombol Tambah BAB di atas untuk mulai menyusun ATP.' ?></p>
            </div></div>
        <?php else: ?>
            <?php foreach ($semester['bab'] as $index => $bab): ?>
                <?php
                    $tpDalamBab = array_values(array_filter($semester['tp'],
                        static fn (array $tp): bool => (int) ($tp['bab_id'] ?? 0) === (int) $bab['id']));
                    $bolehTp = $paketAktif !== null
                        && (int) $bab['paket_semester_id'] === (int) $paketAktif['id'];
                    $warna = ((int) $index % 6) + 1;
                ?>
                <article class="a-atp-bab-card a-atp-warna-<?= (int) $warna ?>" aria-labelledby="judul-bab-<?= (int) $bab['id'] ?>">
                    <header class="a-atp-bab-card__header">
                        <div>
                            <p class="a-atp-bab-card__nomor">BAB <?= (int) $bab['nomor_bab'] ?></p>
                            <h3 id="judul-bab-<?= (int) $bab['id'] ?>"><?= esc($bab['nama_bab']) ?></h3>
                            <p class="a-atp-bab-card__deskripsi"><?= esc($bab['deskripsi_bab']) ?></p>
                        </div>
                        <div class="a-atp-bab-card__aksi">
                            <span class="a-badge"><?= count($tpDalamBab) ?> TP</span>
                            <?php if ($bolehTp): ?>
                                <button class="a-btn a-btn--utama" type="button"
                                    data-atp-dialog-open="dialog-tp-<?= (int) $bab['id'] ?>"
                                    aria-haspopup="dialog" aria-controls="dialog-tp-<?= (int) $bab['id'] ?>">+ Tambah TP</button>
                            <?php endif ?>
                        </div>
                    </header>
                    <?php if ($tpDalamBab === []): ?>
                        <div class="a-atp-bab-card__kosong" role="status">Belum ada TP pada BAB ini.</div>
                    <?php else: ?>
                        <div class="a-tabel-gulir a-atp-bab-card__tabel">
                            <table class="a-tbl a-tbl--cards a-tbl--peta-atp">
                                <thead><tr>
                                    <th scope="col">Kode TP</th>
                                    <th scope="col">Nama TP</th>
                                    <th scope="col">Deskripsi</th>
                                    <th scope="col">Jenis</th>
                                    <th scope="col">Status</th>
                                </tr></thead>
                                <tbody>
                                <?php foreach ($tpDalamBab as $tp): ?>
                                    <tr>
                                        <th scope="row" data-label="Kode TP"><?= esc($tp['kode_tp']) ?></th>
                                        <td data-label="Nama TP"><?= esc(($tp['nama_tp'] ?? '') !== '' ? $tp['nama_tp'] : $tp['judul']) ?></td>
                                        <td data-label="Deskripsi"><?= esc($tp['deskripsi_tp'] ?? $tp['rumusan'] ?? '—') ?></td>
                                        <td data-label="Jenis"><?= esc(($tp['jenis_tp'] ?? '') === 'esensial' ? 'Esensial' : 'NonEsensial') ?></td>
                                        <td data-label="Status"><span class="a-badge"><?= esc($tp['status']) ?></span></td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </article>
                <?php if ($bolehTp): ?>
                    <dialog id="dialog-tp-<?= (int) $bab['id'] ?>" class="a-modal a-modal--atp"
                        aria-labelledby="judul-dialog-tp-<?= (int) $bab['id'] ?>">
                        <div class="a-modal-atp__header">
                            <h2 id="judul-dialog-tp-<?= (int) $bab['id'] ?>">Tambah TP — BAB <?= (int) $bab['nomor_bab'] ?></h2>
                            <button class="a-btn a-btn--sekunder" type="button" aria-label="Tutup dialog TP" data-atp-dialog-close>✕</button>
                        </div>
                        <p class="a-proto-muted"><?= esc($bab['nama_bab']) ?> · Kode TP dibuat otomatis oleh server.</p>
                        <form class="a-form" method="post" action="<?= esc(site_url('guru/tp/baru'), 'attr') ?>" data-atp-dialog-form>
                            <?= csrf_field() ?>
                            <input type="hidden" name="bab_id" value="<?= (int) $bab['id'] ?>">
                            <input type="hidden" name="pilihan" value="<?= (int) $nomorPilihan ?>">
                            <input type="hidden" name="semester" value="<?= (int) $nomorSemester ?>">
                            <div class="a-field">
                                <label class="a-label" for="nama-tp-<?= (int) $bab['id'] ?>">Nama TP (maks. <?= (int) $batasNamaTp ?> karakter)</label>
                                <input id="nama-tp-<?= (int) $bab['id'] ?>" name="nama_tp" maxlength="<?= (int) $batasNamaTp ?>" autocomplete="off" required autofocus>
                            </div>
                            <div class="a-field">
                                <label class="a-label" for="deskripsi-tp-<?= (int) $bab['id'] ?>">Deskripsi TP (maks. 225 karakter)</label>
                                <textarea id="deskripsi-tp-<?= (int) $bab['id'] ?>" name="deskripsi_tp" maxlength="225" rows="3" required></textarea>
                            </div>
                            <div class="a-field">
                                <label class="a-label" for="jenis-tp-<?= (int) $bab['id'] ?>">Jenis TP</label>
                                <select id="jenis-tp-<?= (int) $bab['id'] ?>" name="jenis_tp" required>
                                    <option value="">Pilih jenis TP</option>
                                    <option value="esensial">Esensial — tanpa materi dan nilai LMS</option>
                                    <option value="non_esensial">NonEsensial — materi, aktivitas, dan ujian</option>
                                </select>
                            </div>
                            <p class="a-proto-muted">TP disimpan sebagai draf. Penyiapan media dan ujian mengikuti tahap berikutnya.</p>
                            <div class="a-modal-atp__actions">
                                <button class="a-btn a-btn--sekunder" type="button" data-atp-dialog-close>Batal</button>
                                <button class="a-btn a-btn--utama" type="submit">Simpan TP Draf</button>
                            </div>
                        </form>
                    </dialog>
                <?php endif ?>
            <?php endforeach ?>
        <?php endif ?>
        <?php
            $tpTanpaBab = array_values(array_filter($semester['tp'],
                static fn (array $tp): bool => empty($tp['bab_id'])));
        ?>
        <?php if ($tpTanpaBab !== []): ?>
            <section class="a-card a-jarak-atas" aria-labelledby="judul-tp-lama">
                <h3 id="judul-tp-lama">TP lama tanpa BAB (baca-saja)</h3>
                <p class="a-proto-muted">Data historis dipertahankan; tidak dimasukkan secara otomatis ke BAB baru.</p>
                <div class="a-tabel-gulir">
                    <table class="a-tbl"><thead><tr><th>Kode</th><th>Judul</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($tpTanpaBab as $tp): ?>
                        <tr><td><?= esc($tp['kode_tp']) ?></td><td><?= esc($tp['judul']) ?></td><td><?= esc($tp['status']) ?></td></tr>
                    <?php endforeach ?></tbody></table>
                </div>
            </section>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
