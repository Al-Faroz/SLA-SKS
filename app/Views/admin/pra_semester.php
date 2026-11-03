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
    <section class="a-card">
        <h2>Daftar paket Pra-Semester</h2>
        <?php if ($daftar === []): ?>
            <p class="a-help" role="status">Belum ada paket semester. Guru harus menyiapkan paket dan bank soal.</p>
        <?php else: ?>
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead><tr><th>Gen</th><th>Mapel</th><th>Semester</th><th>Status</th>
                        <th>Jendela mulai (WIB)</th><th>Durasi</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($daftar as $row): ?>
                        <tr>
                            <td><?= esc((string)$row['nama_gen']) ?></td>
                            <td><?= esc((string)$row['nama_mapel']) ?></td>
                            <td><?= (int)$row['semester'] ?></td>
                            <td><?= $row['ujian_id'] === null ? 'Bank belum dibuat'
                                : esc((string)$row['ujian_status']) ?></td>
                            <td><?= $row['mulai_pada'] === null ? 'Belum dijadwalkan'
                                : esc((string)$row['mulai_pada'] . ' – ' . (string)$row['selesai_pada']) ?></td>
                            <td><?= $row['ujian_id'] === null ? '—' : (int)$row['durasi_menit'] . ' menit' ?></td>
                            <td><a class="a-btn a-btn--sekunder"
                                href="<?= esc(site_url('admin/pra-semester/' . (int)$row['paket_id']),'attr') ?>">
                                Atur jadwal
                            </a></td>
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
</div>
<?= $this->endSection() ?>
