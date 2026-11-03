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
                <p>Ganti pilihan Gen, Mapel, Semester, atau Status, lalu terapkan ulang.</p>
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
                                <td><strong><?= esc((string)$row['nama_gen']) ?></strong></td>
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
    <?php if ($pilih !== null): ?>
        <section class="a-card a-jarak-atas">
            <h2><?= esc((string)$pilih['nama_mapel']) ?> — Gen <?= esc((string)$pilih['nama_gen']) ?>,
                Semester <?= (int)$pilih['semester'] ?></h2>
            <?php if ($pilih['ujian_id'] === null): ?>
                <p class="a-help" role="status">Guru belum menyiapkan bank Pra-Semester untuk paket ini.
                    Jadwal hanya dapat disimpan setelah bank siap dan KKM tersedia.</p>
            <?php elseif ($pilih['paket_status'] !== 'aktif'): ?>
                <p class="a-help" role="status">Paket belum aktif. Aktifkan paket melalui pengelolaan ATP terlebih dahulu.</p>
            <?php else: ?>
                <form method="post" class="a-form"
                    action="<?= esc(site_url('admin/pra-semester/' . (int)$pilih['paket_id']),'attr') ?>">
                    <?= csrf_field() ?>
                    <div class="a-field">
                        <label class="a-label" for="pra-mulai">Awal jendela mulai (WIB)</label>
                        <input id="pra-mulai" name="mulai" type="datetime-local" class="a-input" required
                            value="<?= esc((string)old('mulai',substr(str_replace(' ','T',(string)$pilih['mulai_pada']),0,16)),'attr') ?>">
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="pra-akhir">Akhir jendela mulai (WIB)</label>
                        <input id="pra-akhir" name="akhir" type="datetime-local" class="a-input" required
                            value="<?= esc((string)old('akhir',substr(str_replace(' ','T',(string)$pilih['selesai_pada']),0,16)),'attr') ?>">
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="pra-durasi">Durasi per siswa (menit)</label>
                        <input id="pra-durasi" name="durasi" type="number" min="5" max="180"
                            class="a-input" required value="<?= esc((string)old('durasi',$pilih['durasi_menit']),'attr') ?>">
                    </div>
                    <p class="a-help">Contoh: jendela 08.00–09.00, durasi 60 menit. Siswa mulai
                        pukul 08.45 mendapat durasi pengerjaan 60 menit hingga 09.45.
                        Jadwal tidak otomatis berlaku bagi mapel lain.</p>
                    <button type="submit" class="a-btn a-btn--utama"
                        onclick="return confirm('Simpan jadwal ujian utama dan durasi mapel ini?');">Simpan Jadwal Utama</button>
                </form>
            <?php endif ?>
        </section>
    <?php endif ?>
    <?php if ($pilih !== null): ?>
        <?php if (!$terpasangSesi): ?>
            <section class="a-card a-jarak-atas">
                <h2>Remedial dan Susulan</h2>
                <div class="a-alert" role="status">
                    Impor pembaruan skema V4-0009 melalui phpMyAdmin untuk mengaktifkan
                    pengaturan jadwal remedial dan daftar peserta susulan.
                    Jadwal utama tetap dapat dikelola tanpa perubahan data.
                </div>
            </section>
        <?php else: ?>
            <section class="a-card a-jarak-atas" aria-labelledby="pra-jadwal-khusus">
                <h2 id="pra-jadwal-khusus">Jadwal Remedial &amp; Susulan</h2>
                <p class="a-help">Setiap sesi memiliki jendela boleh mulai dan durasi sendiri.
                    Susulan hanya untuk peserta yang ditetapkan Admin; remedial hanya bagi
                    siswa dengan ujian normal sah di bawah KKM. Jadwal harus setelah ujian utama.</p>
                <?php if ($daftarSesi === []): ?>
                    <div class="a-empty" role="status">
                        <p>Belum ada jadwal tambahan untuk paket ini.</p>
                    </div>
                <?php else: ?>
                    <div class="a-tabel-gulir a-pra-table-wrap" tabindex="0"
                        role="region" aria-label="Daftar remedial dan susulan">
                        <table class="a-tbl a-pra-table">
                            <thead><tr><th scope="col">Jenis sesi</th><th scope="col">Tahap</th>
                                <th scope="col">Jendela mulai WIB</th><th scope="col">Durasi</th>
                                <th scope="col">Peserta susulan</th><th scope="col">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach ($daftarSesi as $j): ?>
                                    <tr>
                                        <td><strong><?= $j['jenis'] === 'remedial'
                                            ? 'Remedial' : 'Susulan' ?></strong></td>
                                        <td><?= $j['tahap'] === 'remedial' ? 'Remedial' : 'Normal' ?></td>
                                        <td class="a-pra-col-time">
                                            <time><?= esc((string)$j['mulai_pada']) ?></time>
                                            <span class="a-pra-time-separator">s.d.</span>
                                            <time><?= esc((string)$j['selesai_pada']) ?></time>
                                        </td>
                                        <td class="a-pra-col-center"><?= (int)$j['durasi_menit'] ?> menit</td>
                                        <td class="a-pra-col-center"><?= $j['jenis'] === 'susulan'
                                            ? (int)$j['jumlah_peserta'].' siswa'
                                            : 'Otomatis sesuai nilai' ?></td>
                                        <td>
                                            <?php if ($j['jenis'] === 'susulan'): ?>
                                                <a class="a-btn a-btn--sekunder a-pra-action"
                                                    href="<?= esc(site_url('admin/pra-semester/'
                                                        . (int)$pilih['paket_id']) . '?sesi=' . (int)$j['id'],'attr') ?>">
                                                    Atur peserta
                                                </a>
                                            <?php else: ?>
                                                <span class="a-badge a-badge--siap">Terjadwal</span>
                                            <?php endif ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
                <?php if ($pilih['ujian_status'] !== 'siap' || $pilih['paket_status'] !== 'aktif'): ?>
                    <div class="a-alert" role="status">
                        Aktifkan paket dan simpan jadwal utama setelah bank soal siap
                        sebelum membuat sesi remedial atau susulan.
                    </div>
                <?php else: ?>
                <form method="post" class="a-form a-pra-sesi-form"
                    action="<?= esc(site_url('admin/pra-semester/'
                        . (int)$pilih['paket_id'] . '/sesi'),'attr') ?>">
                    <?= csrf_field() ?>
                    <h3>Tambah jadwal remedial atau susulan</h3>
                    <div class="a-field">
                        <label class="a-label" for="pra-sesi-tipe">Jenis kesempatan</label>
                        <select name="sesi_tipe" id="pra-sesi-tipe" class="a-input" required>
                            <option value="">Pilih jenis</option>
                            <option value="remedial/remedial">Remedial — ujian normal sah &lt; KKM</option>
                            <option value="susulan/normal">Susulan — ujian normal batal/tidak hadir</option>
                            <option value="susulan/remedial">Susulan — remedial batal</option>
                        </select>
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="pra-sesi-mulai">Awal boleh mulai (WIB)</label>
                        <input id="pra-sesi-mulai" name="mulai" type="datetime-local"
                            class="a-input" required>
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="pra-sesi-akhir">Akhir boleh mulai (WIB)</label>
                        <input id="pra-sesi-akhir" name="akhir" type="datetime-local"
                            class="a-input" required>
                    </div>
                    <div class="a-field">
                        <label class="a-label" for="pra-sesi-durasi">Durasi pengerjaan (menit)</label>
                        <input id="pra-sesi-durasi" name="durasi" type="number"
                            min="5" max="180" value="60" class="a-input" required>
                    </div>
                    <button type="submit" class="a-btn a-btn--utama"
                        onclick="return confirm('Simpan jadwal tambahan ini?');">
                        Simpan Jadwal Tambahan
                    </button>
                </form>
                <?php endif ?>
            </section>
            <?php if ($sesiTerpilih !== null): ?>
                <section class="a-card a-jarak-atas" aria-labelledby="pra-peserta-susulan">
                    <h2 id="pra-peserta-susulan">Tetapkan Peserta Susulan</h2>
                    <p class="a-help">
                        Sesi #<?= (int)$sesiTerpilih['id'] ?> ·
                        <?= $sesiTerpilih['tahap'] === 'normal'
                            ? 'Susulan tahap normal' : 'Susulan tahap remedial' ?>.
                        Penetapan peserta bersifat tambahan; peserta yang sudah ditetapkan
                        tidak dihapus. Siswa yang tidak memenuhi syarat tidak ditampilkan.
                    </p>
                    <?php if ($calonSiswa === []): ?>
                        <div class="a-empty" role="status">
                            <p>Belum ada siswa yang memenuhi syarat sesi susulan ini.</p>
                        </div>
                    <?php else: ?>
                        <form method="post" class="a-form"
                            action="<?= esc(site_url('admin/pra-semester/'
                                . (int)$pilih['paket_id'].'/sesi/'
                                . (int)$sesiTerpilih['id'].'/peserta'),'attr') ?>">
                            <?= csrf_field() ?>
                            <div class="a-tabel-gulir a-pra-table-wrap" tabindex="0"
                                role="region" aria-label="Daftar calon peserta susulan">
                                <table class="a-tbl a-pra-table">
                                    <thead><tr><th scope="col">Pilih</th>
                                        <th scope="col">Siswa</th><th scope="col">Kondisi</th>
                                        <th scope="col">Penetapan</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($calonSiswa as $siswa): ?>
                                            <tr>
                                                <td><input type="checkbox" name="siswa_id[]"
                                                    value="<?= (int)$siswa['siswa_id'] ?>"
                                                    aria-label="Pilih <?= esc((string)$siswa['nama'],'attr') ?>"
                                                    <?= !$siswa['boleh_ditambah'] ? 'disabled' : '' ?>></td>
                                                <td><?= esc((string)$siswa['nama']) ?></td>
                                                <td><?= esc(str_replace('_',' ',(string)$siswa['fase'])) ?></td>
                                                <td><span class="a-badge <?= $siswa['ditetapkan']
                                                    ? 'a-badge--siap' : 'a-badge--tunggu' ?>">
                                                    <?= $siswa['ditetapkan'] ? 'Sudah ditetapkan' : 'Belum ditetapkan' ?>
                                                </span></td>
                                            </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="a-btn a-btn--utama">
                                Tambahkan Peserta Terpilih
                            </button>
                        </form>
                    <?php endif ?>
                </section>
            <?php endif ?>
        <?php endif ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
