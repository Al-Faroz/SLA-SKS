<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
    $siswa = $peranAktifKode === 'siswa';
    $profil = $dataProfil;
?>
<div class="a-profil a-page">
    <header class="a-proto-heading a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Akun · Profil</p>
            <h1>Profil saya</h1>
            <p class="a-proto-muted">Identitas resmi dikelola Admin. Sandi dapat diubah melalui menu yang tersedia.</p>
        </div>
    </header>
    <?php if ($galat = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>
    <?php if ($sukses = session()->getFlashdata('sukses')): ?>
        <div class="a-alert" role="status"><?= esc($sukses) ?></div>
    <?php endif ?>

    <div class="a-profil__grid">
        <?php if (! $siswa): ?>
        <section class="a-card a-profil__foto" aria-labelledby="judul-foto">
            <h2 id="judul-foto">Foto profil</h2>
            <div class="a-profil__galeri">
                <?php if ($profil['foto_aktif_id'] !== null): ?>
                    <figure>
                        <img loading="lazy" src="<?= esc(site_url('profil/foto/' . (int) $profil['foto_aktif_id']), 'attr') ?>"
                             alt="Foto profil aktif Anda" width="128" height="128">
                        <figcaption>Foto disetujui</figcaption>
                    </figure>
                <?php else: ?>
                    <div class="a-profil__avatar-kosong" role="img" aria-label="Foto profil belum tersedia">Belum ada foto aktif</div>
                <?php endif ?>
                <?php if ($profil['foto_menunggu_id'] !== null): ?>
                    <figure>
                        <img loading="lazy" src="<?= esc(site_url('profil/foto/' . (int) $profil['foto_menunggu_id']), 'attr') ?>"
                             alt="Foto profil menunggu persetujuan" width="128" height="128">
                        <figcaption>Menunggu persetujuan Admin</figcaption>
                    </figure>
                <?php endif ?>
            </div>
            <p class="a-help">Unggah JPG/PNG, maksimal 2 MB, ukuran 100–1600 piksel per sisi. Foto baru baru tampil pada profil profesional setelah Admin menyetujui. Foto lama tetap tersedia selama proses tinjauan.</p>
            <form method="post" enctype="multipart/form-data" action="<?= esc(site_url('profil/foto'), 'attr') ?>" class="a-form">
                <?= csrf_field() ?>
                <div class="a-field">
                    <label class="a-label" for="foto">Pilih foto JPG/PNG</label>
                    <input type="file" id="foto" name="foto" required
                        accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                </div>
                <button type="submit" class="a-btn a-btn--utama">Unggah foto untuk persetujuan</button>
            </form>
        </section>
        <?php endif ?>
        <section class="a-card a-profil__identitas" aria-labelledby="judul-identitas">
            <h2 id="judul-identitas">Identitas akun</h2>
            <dl class="a-rincian">
                <dt>Nama resmi</dt><dd><?= esc($namaPengguna) ?></dd>
                <dt><?= $siswa ? 'NISN / Username' : 'Username / NIP' ?></dt><dd><?= esc($username) ?></dd>
                <dt>Peran aktif</dt><dd><?= esc($peranAktif) ?></dd>
                <?php if ($siswa): ?>
                    <dt>Gen</dt>
                    <dd><?= $profil['gen'] !== null ? esc($profil['gen']) : 'Belum ditempatkan oleh Admin' ?></dd>
                    <dt>Semester mulai</dt>
                    <dd><?= $profil['semester_mulai'] === null ? 'Belum ditentukan' : 'Semester ' . (int) $profil['semester_mulai'] ?></dd>
                <?php endif ?>
            </dl>
            <p class="a-help">Perbaikan nama resmi, NISN, username dan peran hanya dapat dilakukan Admin melalui menu Pengelola/Siswa.</p>
            <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('ganti-sandi'), 'attr') ?>">Ganti sandi</a>
        </section>

        <?php if (! $siswa): ?>
        <section class="a-card a-profil__privat" aria-labelledby="judul-kontak">
            <h2 id="judul-kontak">Kontak pribadi</h2>
            <p class="a-help">Nomor kontak ini hanya digunakan sebagai data privat akun. Tidak ditampilkan dalam profil Guru untuk Siswa.</p>
            <form method="post" action="<?= esc(site_url('profil/kontak'), 'attr') ?>" class="a-form">
                <?= csrf_field() ?>
                <div class="a-field">
                    <label class="a-label" for="profil-telepon">Nomor telepon pribadi (opsional)</label>
                    <input type="tel" id="profil-telepon" name="telepon_pribadi" maxlength="25"
                        autocomplete="tel" value="<?= esc($profil['telepon_pribadi'] ?? '', 'attr') ?>"
                        placeholder="Contoh: +62 812 3456 7890">
                </div>
                <button type="submit" class="a-btn a-btn--utama">Simpan kontak pribadi</button>
            </form>
        </section>
        <?php endif ?>
    </div>

    <?php if ($siswa): ?>
        <section class="a-card a-profil__guru" aria-labelledby="judul-guru">
            <h2 id="judul-guru">Guru pengampu Gen saya</h2>
            <p class="a-help">Hanya Guru yang ditugaskan aktif pada Gen Anda. Nomor pribadi, NIP, dan data akun Guru tidak ditampilkan.</p>
            <?php if ($profil['gen'] === null): ?>
                <div class="a-empty" role="status"><strong>Gen belum ditetapkan.</strong><p>Hubungi Admin untuk penempatan Gen sebelum melihat Guru pengampu.</p></div>
            <?php elseif ($profil['guru_gen'] === []): ?>
                <div class="a-empty" role="status"><strong>Belum ada Guru pengampu.</strong><p>Admin belum menetapkan Guru aktif untuk Gen ini.</p></div>
            <?php else: ?>
                <div class="a-profil__guru-grid">
                    <?php foreach ($profil['guru_gen'] as $guru): ?>
                    <article class="a-profil__guru-card">
                        <?php if ($guru['foto_id'] !== null): ?>
                            <img src="<?= esc(site_url('profil/foto/' . (int) $guru['foto_id']), 'attr') ?>"
                                 loading="lazy" alt="Foto profesional <?= esc($guru['nama'], 'attr') ?>" width="86" height="86">
                        <?php else: ?>
                            <span class="a-profil__guru-avatar" aria-hidden="true">G</span>
                        <?php endif ?>
                        <div>
                            <strong><?= esc($guru['nama']) ?></strong>
                            <p><?= esc(implode(', ', $guru['mapel'])) ?></p>
                        </div>
                    </article>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>
    <?php endif ?>

    <?php if (count($daftarPeran) > 1): ?>
    <section class="a-card a-baca-panel" aria-labelledby="judul-peran">
        <h2 id="judul-peran">Ganti peran aktif</h2>
        <p class="a-help">Hanya izin peran yang sedang aktif berlaku. Pergantian peran memperbarui sesi keamanan.</p>
        <form method="post" action="<?= esc(site_url('peran/ganti'), 'attr') ?>" class="a-form">
            <?= csrf_field() ?>
            <div class="a-field">
                <label class="a-label" for="peran">Peran yang akan diaktifkan</label>
                <select id="peran" name="peran" required>
                    <?php foreach ($daftarPeran as $peran): ?>
                        <option value="<?= esc($peran, 'attr') ?>" <?= $peran === $peranAktifKode ? 'selected' : '' ?>><?= esc(ucfirst($peran)) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <button type="submit" class="a-btn a-btn--utama">Aktifkan peran</button>
        </form>
    </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
