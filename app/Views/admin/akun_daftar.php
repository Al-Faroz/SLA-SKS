<?= $this->extend('layouts/panel') ?>
<?= $this->section('konten') ?>
<?php
    $siswa = $jenis === 'siswa';
    $judulDaftar = $siswa ? 'Siswa' : 'Pengelola';
    $lokasi = site_url('admin/' . $jenis);
?>
<div class="a-admin-akun a-page">
    <nav class="a-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= esc(site_url('admin'), 'attr') ?>">Beranda Admin</a>
        <span aria-hidden="true">/</span>
        <span aria-current="page"><?= esc($judulDaftar) ?></span>
    </nav>
    <header class="a-proto-heading a-page__heading">
        <div>
            <p class="a-proto-eyebrow">Administrasi / Akun</p>
            <h1><?= esc($judulDaftar) ?></h1>
            <p class="a-proto-muted"><?= $siswa
                ? 'Akun siswa, NISN dan penempatan Gen.'
                : 'Akun Admin, Guru dan Pimpinan dalam satu daftar.' ?></p>
        </div>
        <span class="a-admin-akun__jumlah"><?= (int) $total ?> akun sesuai filter</span>
    </header>

    <?php if ($galat = session()->getFlashdata('galat')): ?>
        <div class="a-alert a-alert--bahaya" role="alert"><?= esc($galat) ?></div>
    <?php endif ?>
    <?php if ($sukses = session()->getFlashdata('sukses')): ?>
        <div class="a-alert" role="status"><?= esc($sukses) ?></div>
    <?php endif ?>

    <div class="a-admin-akun__grid">
        <section class="a-card a-admin-akun__form" aria-labelledby="judul-tambah">
            <h2 id="judul-tambah">Tambah <?= esc($judulDaftar) ?></h2>
            <p class="a-help"><?= $siswa
                ? 'Siswa wajib memiliki NISN 10 angka dan penempatan Gen sebelum bisa belajar.'
                : 'Staf dapat memiliki satu atau dua peran sesuai kombinasi yang diizinkan.' ?></p>
            <form class="a-form" method="post" action="<?= esc($lokasi, 'attr') ?>">
                <?= csrf_field() ?>
                <div class="a-field">
                    <label class="a-label" for="akun-username"><?= $siswa ? 'NISN (username)' : 'Username / NIP' ?></label>
                    <input id="akun-username" name="username" type="text" required autocomplete="off"
                        <?= $siswa ? 'inputmode="numeric" pattern="[0-9]{10}" maxlength="10"' : 'pattern="[A-Za-z0-9_.-]{3,80}" maxlength="80"' ?>
                        value="<?= esc(old('username', ''), 'attr') ?>">
                </div>
                <div class="a-field">
                    <label class="a-label" for="akun-nama">Nama lengkap</label>
                    <input id="akun-nama" name="nama" type="text" minlength="3" maxlength="150" required
                        value="<?= esc(old('nama', ''), 'attr') ?>">
                </div>
                <?php if ($siswa): ?>
                    <div class="a-field">
                        <label class="a-label" for="akun-gen">Gen</label>
                        <select id="akun-gen" name="angkatan_id" required>
                            <option value="">Pilih Gen</option>
                            <?php foreach ($gen as $item): ?>
                                <option value="<?= (int) $item['id'] ?>" <?= (string) old('angkatan_id', '') === (string) $item['id'] ? 'selected' : '' ?>>
                                    <?= esc($item['nama']) ?> (<?= esc($item['kode']) ?>)
                                </option>
                            <?php endforeach ?>
                        </select>
                        <?php if ($gen === []): ?><p class="a-error">Belum ada Gen. Buat Gen melalui menu Gen &amp; Tahun Ajaran dahulu.</p><?php endif ?>
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="akun-semester">Semester mulai khusus (opsional)</label>
                        <select id="akun-semester" name="semester_mulai">
                            <option value="">Ikuti semester mulai bawaan Gen</option>
                            <?php for ($n = 1; $n <= 6; $n++): ?>
                                <option value="<?= $n ?>" <?= (string) old('semester_mulai', '') === (string) $n ? 'selected' : '' ?>>Semester <?= $n ?></option>
                            <?php endfor ?>
                        </select>
                    </div>
                <?php else: ?>
                    <fieldset class="a-fieldset">
                        <legend class="a-label">Peran Pengelola</legend>
                        <?php foreach (['admin' => 'Admin', 'guru' => 'Guru', 'pimpinan' => 'Pimpinan'] as $kode => $label): ?>
                            <label><input type="checkbox" name="peran[]" value="<?= esc($kode, 'attr') ?>"> <?= esc($label) ?></label>
                        <?php endforeach ?>
                        <p class="a-help">Maksimal dua peran. Admin+Guru, Admin+Pimpinan, atau Guru+Pimpinan.</p>
                    </fieldset>
                <?php endif ?>
                <div class="a-field">
                    <label class="a-label" for="akun-sandi">Sandi awal sementara</label>
                    <input id="akun-sandi" name="sandi" type="password" autocomplete="new-password" minlength="8" maxlength="200" required>
                    <span class="a-help">Minimal 8 karakter. Pengguna wajib menggantinya saat login pertama.</span>
                </div>
                <button class="a-btn a-btn--utama" type="submit" <?= $siswa && $gen === [] ? 'disabled' : '' ?>>Buat akun <?= esc($judulDaftar) ?></button>
            </form>
        </section>

        <section class="a-card a-admin-akun__daftar" aria-labelledby="judul-daftar">
            <div class="a-proto-section-head">
                <div>
                    <h2 id="judul-daftar">Daftar <?= esc($judulDaftar) ?></h2>
                    <p>Gunakan filter untuk mencari akun terdaftar.</p>
                </div>
            </div>
            <form class="a-filter a-admin-akun__filter" method="get" action="<?= esc($lokasi, 'attr') ?>">
                <div class="a-field">
                    <label class="a-label" for="filter-cari">Cari nama / <?= $siswa ? 'NISN' : 'username' ?></label>
                    <input id="filter-cari" name="cari" type="search" maxlength="80" value="<?= esc($cari, 'attr') ?>">
                </div>
                <div class="a-field">
                    <label class="a-label" for="filter-status">Status</label>
                    <select id="filter-status" name="status">
                        <option value="">Semua</option>
                        <?php foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $kode => $label): ?>
                            <option value="<?= esc($kode, 'attr') ?>" <?= $status === $kode ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <?php if ($siswa): ?>
                    <div class="a-field">
                        <label class="a-label" for="filter-gen">Gen</label>
                        <select id="filter-gen" name="gen">
                            <option value="">Semua Gen</option>
                            <option value="0" <?= $genFilter === 0 ? 'selected' : '' ?>>Belum ditempatkan</option>
                            <?php foreach ($gen as $item): ?>
                                <option value="<?= (int) $item['id'] ?>" <?= $genFilter === (int) $item['id'] ? 'selected' : '' ?>><?= esc($item['nama']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                <?php endif ?>
                <button class="a-btn a-btn--sekunder" type="submit">Terapkan</button>
                <a class="a-btn a-btn--sekunder" href="<?= esc($lokasi, 'attr') ?>">Reset filter</a>
            </form>

            <div class="a-admin-akun__xlsx">
                <div>
                    <strong>File Excel (.xlsx)</strong>
                    <span class="a-help">Ekspor sesuai filter yang diterapkan, tanpa sandi dan hash.</span>
                </div>
                <div class="a-admin-akun__xlsx-aksi">
                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/' . $jenis . '/template'), 'attr') ?>">Unduh template</a>
                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/' . $jenis . '/impor'), 'attr') ?>">Impor XLSX</a>
                    <a class="a-btn a-btn--utama"
                       href="<?= esc(site_url('admin/' . $jenis . '/ekspor') . '?' . http_build_query(['cari' => $cari, 'status' => $status, 'gen' => $siswa ? $genFilter : null]), 'attr') ?>">Ekspor XLSX</a>
                </div>
            </div>

            <?php if ($pengguna === []): ?>
                <div class="a-empty" role="status"><strong>Belum ada hasil.</strong><p>Belum ada akun atau tidak ada yang cocok dengan filter.</p></div>
            <?php else: ?>
                <div class="a-tabel-gulir">
                    <table class="a-tbl a-tbl--cards a-admin-akun__tabel">
                        <thead><tr>
                            <th scope="col">Nama</th><th scope="col"><?= $siswa ? 'NISN' : 'Username' ?></th>
                            <th scope="col"><?= $siswa ? 'Gen' : 'Peran' ?></th>
                            <th scope="col">Status</th><th scope="col">Aksi</th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($pengguna as $akun): ?>
                            <tr>
                                <th scope="row" data-label="Nama"><strong><?= esc($akun['nama']) ?></strong><small class="a-admin-akun__subtitle"><?= (int) $akun['wajib_ganti_sandi'] ? 'Wajib ganti sandi' : 'Sandi aktif' ?></small></th>
                                <td data-label="<?= $siswa ? 'NISN' : 'Username' ?>"><?= esc($akun['username']) ?></td>
                                <td data-label="<?= $siswa ? 'Gen' : 'Peran' ?>">
                                    <?php if ($siswa): ?>
                                        <?php if ($akun['angkatan_id'] === null): ?>
                                            <span class="a-badge a-badge--tunggu">Belum ditempatkan</span>
                                            <?php if ($gen !== []): ?>
                                                <form class="a-admin-akun__penempatan" method="post"
                                                    action="<?= esc(site_url('admin/siswa/' . (int) $akun['id'] . '/gen'), 'attr') ?>">
                                                    <?= csrf_field() ?>
                                                    <label class="a-label" for="gen-lama-<?= (int) $akun['id'] ?>">Tempatkan ke Gen</label>
                                                    <select id="gen-lama-<?= (int) $akun['id'] ?>" name="angkatan_id" required>
                                                        <option value="">Pilih Gen</option>
                                                        <?php foreach ($gen as $item): ?>
                                                            <option value="<?= (int) $item['id'] ?>"><?= esc($item['nama']) ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                    <button class="a-btn a-btn--sekunder" type="submit">Simpan Gen</button>
                                                </form>
                                            <?php endif ?>
                                        <?php else: ?>
                                            <?= esc($akun['gen_nama'] ?? '-') ?>
                                            <small class="a-admin-akun__subtitle"><?= $akun['semester_mulai'] === null ? 'Semester bawaan Gen' : 'Mulai semester ' . (int) $akun['semester_mulai'] ?></small>
                                        <?php endif ?>
                                    <?php else: ?>
                                        <?= esc(implode(', ', $akun['daftar_peran'])) ?>
                                    <?php endif ?>
                                </td>
                                <td data-label="Status"><span class="a-badge <?= $akun['status'] === 'aktif' ? 'a-badge--siap' : 'a-badge--tunggu' ?>"><?= esc(ucfirst($akun['status'])) ?></span></td>
                                <td data-label="Aksi"><div class="a-admin-akun__aksi">
                                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/' . $jenis . '/' . (int) $akun['id'] . '/edit'), 'attr') ?>">Ubah</a>
                                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/' . $jenis . '/' . (int) $akun['id'] . '/reset-sandi'), 'attr') ?>">Reset sandi</a>
                                    <a class="a-btn a-btn--sekunder" href="<?= esc(site_url('admin/' . $jenis . '/' . (int) $akun['id'] . '/status'), 'attr') ?>"><?= $akun['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></a>
                                </div></td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <?= view('components/pager', [
                    'urlPath' => 'admin/' . $jenis,
                    'pageParam' => 'halaman',
                    'halaman' => $halaman,
                    'perHalaman' => $perHalaman,
                    'total' => $total,
                    'filter' => ['cari' => $cari, 'status' => $status, 'gen' => $siswa ? $genFilter : null],
                    'label' => 'akun ' . $judulDaftar,
                    'anchor' => 'judul-daftar',
                ]) ?>
            <?php endif ?>
        </section>
    </div>
    <p class="a-help">Impor XLSX tersedia dengan validasi, pratinjau, konfirmasi transaksi, dan audit. Perubahan Gen siswa yang sudah ditempatkan tetap memerlukan pemeriksaan progres pada modul berikutnya.</p>
</div>
<?= $this->endSection() ?>
